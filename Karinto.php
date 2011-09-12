<?php
namespace Karinto;

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});

class Application
{
    public function error($callback) {}
    public function get($url, $callback) {}
    public function post($url, $callback) {}
    public function put($url, $callback) {}
    public function delete($url, $callback) {}
    public function fetch($template, $values) {}
    public function render($template, $values) {}
    public function redirect($url) {}
    public function session() {}
    public function run() {}
}

class Request
{
}

class Session
{
}

class Utils
{
}

class Exception extends \Exception
{
}
