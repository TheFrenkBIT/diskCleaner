<?php
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

require_once($_SERVER['DOCUMENT_ROOT'].'/local/bx-robots/Logger.php');
require_once($_SERVER['DOCUMENT_ROOT'] . "/local/twig/vendor/autoload.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/local/bx-robots/BXConnector.php");

$bx = new \BX\BXConnector('https://' . $_REQUEST['DOMAIN'] . '/rest/', $_REQUEST['AUTH_ID']);
$bxWolf = new \BX\BXConnector('https://crm.web-wolf.net/rest/5/asecpfgsumqflht8/');
$logger=new \BX\Logger('app_install');
$logger->saveFile($_REQUEST,'init.txt');
$currentUser = $bx->request('user.current')['ID'];
$logger->saveFile($currentUser,'currentUser.txt');
$result = $bxWolf->request('lists.hl.add',[
    'user_id' => $currentUser,
    'hl_id' => 1,
    'domain' => $_REQUEST['DOMAIN'],
    'settings' => '',
    'refresh' => $_REQUEST['REFRESH_ID'],
    'status' => 'STOPPED'
]);

$loader = new FilesystemLoader('views');
$twig = new Environment($loader);
$template= $twig -> load('install.twig');
echo $template->render();