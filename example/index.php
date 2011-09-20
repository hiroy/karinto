<?php
require_once dirname(dirname(__FILE__)) . '/Karinto.php';

use Karinto\Application;
use Karinto\Request;

session_set_cookie_params(1800);

$app = new Application();
$app->layoutTemplate = 'layout.php';
$app->sessionSecretKey = 'your session secret key';

$app->error(function($code, \Exception $e = null) use ($app) {
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
    $session['name'] = $req->param('name');
    $app['name'] = $req->param('name');
    $app->render('foo.php');
});

$app->get('/bar', function(Request $req) use ($app) {
    $session = $app->session();
    $app['name'] = $session['name'];
    $app->render('bar.php');
});

$app->run();
