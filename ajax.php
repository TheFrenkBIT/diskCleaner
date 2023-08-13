<?php
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

include_once($_SERVER['DOCUMENT_ROOT'].'/local/bx-robots/BXConnector.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/local/bx-robots/Logger.php');

$logger=new \BX\Logger('clear_disk_ajax');
$logger->saveFile($_REQUEST,'init.txt');

$bx = new \BX\BXConnector('https://'.$_REQUEST['domain'].'/rest/',$_REQUEST['auth']);
$bxWolf = new \BX\BXConnector('https://crm.web-wolf.net/rest/5/asecpfgsumqflht8/');

$isAdmin = $bx->request('user.admin');

if (!$isAdmin) {
    echo json_encode('Доступ запрещен');
    return 1;
}
$settings=array_slice($_REQUEST, 2);

$currentUser = $bx->request('user.current')['ID'];
$record = $bxWolf->request('lists.hl.get',[
    'hl_id' => 1,
    'user_id' => $currentUser,
    'domain' => $_REQUEST['domain']
]);
$logger->saveFile($record,'settings.txt');

if (empty($record)){
    $result = $bxWolf->request('lists.hl.add',[
        'user_id' => $currentUser,
        'hl_id' => 1,
        'domain' => $_REQUEST['domain'],
        'settings' => $settings,
        'refresh' => $_REQUEST['refresh']
    ]);
    echo json_encode('Настройки сохранены');
    return 1;
}
$current_settings=(array)json_decode($record['UF_SETTINGS']);
foreach ($settings as $key=>$value){
    $current_settings[$key]=$value;
}
if (!array_key_exists('active',$settings)){
    $current_settings['active']=false;
} else{
    $current_settings['active']=true;
}
$logger->saveFile($current_settings,'update_settings.txt');
$result = $bxWolf->request('lists.hl.update',[
    'hl_id' => 1,
    'id' => $record['ID'],
    'current_settings' => $current_settings,
    'refresh' => $_REQUEST['refresh']
]);
echo json_encode('Настройки обновлены');
return 1;