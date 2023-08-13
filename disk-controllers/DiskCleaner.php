<?php

namespace Wolf;

class DiskCleaner
{
    public $logger;
    public $refresh_id;
    public $auth_settings;
    public $auth_id;
    public $bx;
    public $domain;
    public $settings=[];
    public $blockedFiles=[];
    public function __construct($logger, $refresh_id, $auth_settings, $domain)
    {
        $this->logger=$logger;
        $this->refresh_id=$refresh_id;
        $this->auth_settings=(array)json_decode($auth_settings);
        $this->domain=$domain;
    }

    public function getAuth()
    {
        $queryUrl = 'https://oauth.bitrix.info/oauth/token/';
        $queryData = http_build_query([
            'grant_type'=>'refresh_token',
            'client_id'=>$this->auth_settings['client_id'],
            'client_secret'=>$this->auth_settings['client_secret'],
            'refresh_token'=> $this->refresh_id
        ]);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $queryUrl,
            CURLOPT_POSTFIELDS => $queryData,
        ));
        $json = curl_exec($curl);
        curl_close($curl);
        $answer= json_decode($json,1);
        $this->refresh_id=$answer['refresh_token'];
        $this->auth_id=$answer['access_token'];
        $this->bx=new \BX\BXConnector('https://'.$this->domain.'/rest/',$this->auth_id);
        $this->logger->saveFile($this->domain, 'domain.txt');
        return 1;
    }
    public function getStorages(){
        $storages=$this->bx->request('disk.storage.getlist');
        return $storages;
    }
    public function getFilesByStorage($storage_id){
        $files=$this->bx->request('disk.storage.getchildren',[
            'id' => $storage_id
        ]);
        $allFiles=[];
        foreach ($files as $file){
            if ($file['TYPE']=='folder'){
                if (in_array($file['ID'], $this->settings['folders'])){
                    continue;
                }
                $allFiles=array_merge($allFiles,$this->getAllFilesByFolder($file['ID']));
            } else {
                if (in_array($file['ID'], $this->blockedFiles)){
                    continue;
                }
                if ($file['SIZE']<=(int)$this->settings['size']){
                    continue;
                }
                if (time()-strtotime($file['UPDATE_TIME'])<(86400*(int)$this->settings['days'])){
                    continue;
                }
                $file_format=explode('.',$file['NAME'],2);
                if (!in_array(end($file_format), $this->settings['file_formats'])
                    &&
                    !empty($this->settings['file_formats'])){
                    continue;
                }
                $allFiles[]=$file;
            }

        }
        return $allFiles;
    }
    public function getAllFilesByFolder($folderID){
        $files=$this->bx->request('disk.folder.getchildren',[
            'id' => $folderID
        ]);
        $allFiles=[];
        foreach ($files as $file){
            if ($file['TYPE']=='folder'){
                if (in_array($file['ID'], $this->settings['folders'])){
                    continue;
                }
                $allFiles=array_merge($allFiles,$this->getAllFilesByFolder($file['ID']));
            } else {
                if (in_array($file['ID'], $this->blockedFiles)){
                    continue;
                }
                if ($file['SIZE']<=(int)$this->settings['size']){
                    continue;
                }
                if (time()-strtotime($file['UPDATE_TIME'])<(86400*(int)$this->settings['days'])){
                    continue;
                }
                $file_format=explode('.',$file['NAME'],2);
                if (!in_array(end($file_format), $this->settings['file_formats'])
                    &&
                    !empty($this->settings['file_formats'])){
                    continue;
                }
                $allFiles[]=$file;
            }
        }
        return $allFiles;
    }
    public function setSettings($settings){
        if (empty($settings['folders'])){
            $this->settings['folders']=[];
        } else{
            $this->settings['folders']=explode(',',$settings['folders']);
            foreach ($this->settings['folders'] as $key=>$folder){
                $this->settings['folders'][$key]=trim($folder);
            }
        }
        if (empty($settings['formats'])){
            $this->settings['file_formats']=[];
        } else{
            $this->settings['file_formats']=explode(',',$settings['formats']);
            foreach ($this->settings['file_formats'] as $key=>$format){
                $this->settings['file_formats'][$key]=trim($format);
            }
        }
        if (empty($settings['tasks'])){
            $this->settings['tasks']=[];
        } else{
            $this->settings['tasks']=explode(',',$settings['tasks']);
            foreach ($this->settings['tasks'] as $key=>$task){
                $this->settings['tasks'][$key]=trim($task);
            }
        }
        if (empty($settings['days'])){
            $this->settings['days']=0;
        } else{
            $this->settings['days']=$settings['days'];
        }
        $this->settings['size']=$settings['size'];
        $this->settings['active']=$settings['active'];
        return $this->settings;
    }
    public function removeFiles($files){
        foreach ($files as $file){

        }
    }
    public function blockedFilesByTask(){
        foreach ($this->settings['tasks'] as $task_id){
            $data = $this->bx->request('task.item.getfiles',[
                'TASKID' => $task_id,
            ]);
        }
        foreach ($data as $datum) {
            if (empty($datum['FILE_ID']))
            {
                continue;
            }
            $this->blockedFiles[] = $datum['FILE_ID'];
        }

        $data = $this->bx->getListBatch('task.commentitem.getlist', [
            'TASKID' => $task_id,
        ]);
        foreach($data as $comment){
            if(isset($comment['ATTACHED_OBJECTS'])){
                foreach ($comment['ATTACHED_OBJECTS'] as $attachment){
                    if (empty($attachment['FILE_ID']))
                    {
                        continue;
                    }
                    $this->blockedFiles[] = $attachment['FILE_ID'];
                }
            }
        }
        return 1;
    }
    public function deleteFiles($files){
        foreach ($files as $file){
            $deleteFile=$this->bx->request('disk.file.delete', [
                'id' => $file['ID']
            ]);
            $this->logger->saveFile([$file, $deleteFile], 'deleted_files.txt');
        }
    }

    public function appIsActive(){
        if ($this->settings['active']==false){
            return false;
        }
        return true;
    }

}