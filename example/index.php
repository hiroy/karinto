<?php
require_once dirname(dirname(__FILE__)) . '/Karinto.php';

use Karinto\Application;
use Karinto\Request;

$app = new Application();

$app->get('/', function(Request $req) use ($app) {
    $app->contentTypeHtml();
    $app->render('default.php');
});

$app->get('/foo', function(Request $req) use ($app) {
    $app->contentTypeHtml();
    $app->render('foo.php', array('name' => $req->name));
});

$app->run();
