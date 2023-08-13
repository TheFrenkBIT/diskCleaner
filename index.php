<?php
use Twig\Loader\FilesystemLoader;
use Twig\Environment;

include_once($_SERVER['DOCUMENT_ROOT'] . '/local/bx-robots/BXConnector.php');
require_once($_SERVER['DOCUMENT_ROOT'] . "/local/twig/vendor/autoload.php");
require_once($_SERVER['DOCUMENT_ROOT'] . '/local/bx-robots/Logger.php');
$logger=new \BX\Logger('clear_disk');


$bx = new \BX\BXConnector('https://' . $_REQUEST['DOMAIN'] . '/rest/', $_REQUEST['AUTH_ID']);
$bxWolf = new \BX\BXConnector('https://crm.web-wolf.net/rest/5/asecpfgsumqflht8/');
$logger->saveFile($_REQUEST, 'init.txt');
$logger->saveFile($_REQUEST['AUTH_ID'], 'auth.txt');
$isAdmin = $bx->request('user.admin');
if (!$isAdmin) {
    echo 'Доступ запрещен';
    return 1;
}
$currentUser = $bx->request('user.current')['ID'];
$data = $bxWolf->request('lists.hl.get',[
    'hl_id' => 1,
    'user_id' => $currentUser,
    'domain' => $_REQUEST['DOMAIN']
]);
$loader = new FilesystemLoader('views');
$twig = new Environment($loader);
$template= $twig -> load('admin.twig');
echo $template->render([
    'data' => json_decode($data['UF_SETTINGS']),
    'domain' => $_REQUEST['DOMAIN'],
    'auth' => $_REQUEST['AUTH_ID'],
    'refresh' => $_REQUEST['REFRESH_ID']
]);
