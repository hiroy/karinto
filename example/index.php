<?php
require_once dirname(dirname(__FILE__)) . '/Karinto.php';

use Karinto\Application;
use Karinto\Request;

session_set_cookie_params(1800);

$app = new Application();
$app->sessionSecretKey = 'your session secret key';

$app->get('/', function(Request $req) use ($app) {
    $app->contentTypeHtml();
    $app->render('default.php');
});

$app->get('/foo', function(Request $req) use ($app) {
    $session = $app->session();
    $session->name = $req->name;
    $app->contentTypeHtml();
    $app->render('foo.php', array('name' => $req->name));
});

$app->get('/bar', function(Request $req) use ($app) {
    $session = $app->session();
    $app->contentTypeHtml();
    $app->render('bar.php', array('name' => $session->name));
});

$app->run();
