<?php
require_once dirname(dirname(__FILE__)) . '/Karinto.php';

use Karinto\Application;
use Karinto\Request;

session_set_cookie_params(1800);

$app = new Application();
$app->sessionSecretKey = 'your session secret key';

$app->error(function($code) use ($app) {
    switch ($code) {
    case 404:
        $app->render('error_404.php');
        break;
    default:
        $app->render('error_general.php');
        break;
    }
});

$app->get('/', function(Request $req) use ($app) {
    $app->render('default.php');
});

$app->get('/foo', function(Request $req) use ($app) {
    $session = $app->session();
    $session->name = $req->name;
    $app->render('foo.php', array('name' => $req->name));
});

$app->get('/bar', function(Request $req) use ($app) {
    $session = $app->session();
    $app->render('bar.php', array('name' => $session->name));
});

$app->run();
