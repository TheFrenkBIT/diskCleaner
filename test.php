<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/local/bx-robots/BXConnector.php");
$bx= new \BX\BXConnector('https://crm.web-wolf.net/rest/5/v7d7g5n09uto2paj/');

$task=$bx->request('tasks.task.list',[
    'id' => 2
]);
echo '<pre>';
echo print_r($task, 1);
echo '</pre>';