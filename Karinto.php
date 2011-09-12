<?php
namespace Karinto;

class Application
{
    public function get($url, $callback) {}
    public function post($url, $callback) {}
    public function put($url, $callback) {}
    public function delete($url, $callback) {}
    public function fetch($template, $values) {}
    public function render($template, $values) {}
    public function redirect($url) {}
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
