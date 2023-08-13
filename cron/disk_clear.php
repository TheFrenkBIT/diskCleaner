<?php
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Loader;

set_time_limit(1800);

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

$_SERVER['DOCUMENT_ROOT']='/home/bitrix/www';
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER['DOCUMENT_ROOT'] . '/local/bx-robots/BXConnector.php');
require_once($_SERVER['DOCUMENT_ROOT'] . "/local/twig/vendor/autoload.php");
require_once($_SERVER['DOCUMENT_ROOT'] . '/local/controllers/DiskCleaner.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/local/bx-robots/Logger.php');

global $USER;
$USER->Authorize('1');

$logger=new \BX\Logger('cron_disk_clear');

Loader::includeModule("highloadblock");


$hlblock = HL\HighloadBlockTable::getById(1)->fetch();
$entity = HL\HighloadBlockTable::compileEntity($hlblock);
$entity_data_class = $entity->getDataClass();

$rows = $entity_data_class::getList()->fetchAll();
$current_client=[];
$last_element=false;
foreach ($rows as $key=>$row){
    if ($row['UF_STATUS']==='STOPPED'){
        if (($key+1)==count($rows)){
            $last_element=true;
        }
        $current_client=$row;
        break;
    }
}
if ($last_element){
    foreach ($rows as $row){
        $entity_data_class::update($row['ID'], [
            "UF_STATUS" => 'STOPPED'
        ]);
    }

} else{
    $entity_data_class::update($current_client['ID'], [
        "UF_STATUS" => 'ACTIVE'
    ]);
}
if (empty($current_client['UF_SETTINGS'])){
    die;
}
$settings=(array)json_decode($current_client['UF_SETTINGS']);
$controller= new \Wolf\DiskCleaner(
    $logger,
    $current_client['UF_REFRESH_ID'],
    $current_client['UF_AUTH_SETTINGS'],
    $current_client['UF_DOMAIN']
);

$controller->getAuth();

$getSettings=$controller->setSettings($settings);
if (!$controller->appIsActive()){
    die;
}
$storages=$controller->getStorages();
$blockedFiles=$controller->blockedFilesByTask();
$allFiles=[];
foreach ($storages as $storage){
    $allFiles=array_merge($allFiles,$controller->getFilesByStorage($storage['ID']));
}
$controller->deleteFiles($allFiles);
$USER->Logout();