<?php
namespace Karinto;

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});

class Application
{
    protected $templateDir = 'templates';
    protected $encoding = 'UTF-8';

    protected $routes = array(
        'GET' => array(), 'POST' => array(),
        'PUT' => array(), 'DELETE' => array());

    public function error($callback) {}

    public function get($url, $callback)
    {
        if (is_callable($callback)) {
            $this->routes['GET'][$url] = $callback;
        }
    }

    public function post($url, $callback)
    {
        if (is_callable($callback)) {
            $this->routes['POST'][$url] = $callback;
        }
    }

    public function put($url, $callback)
    {
        if (is_callable($callback)) {
            $this->routes['PUT'][$url] = $callback;
        }
    }

    public function delete($url, $callback)
    {
        if (is_callable($callback)) {
            $this->routes['DELETE'][$url] = $callback;
        }
    }

    public function fetch($template, $values) {}

    public function render($template, $values) {}

    public function redirect($url) {}

    public function header($name)
    {
    }

    public function contentType($type, $charset = null)
    {
    }

    public function contentTypeHtml($charset = null)
    {
    }

    public function code($code)
    {
    }

    public function cookie($name, $value, $expire = null,
        $path = '/', $domain = '', $secure = false, $httpOnly = false)
    {
    }

    public function session() {}

    public function run(array $options)
    {
        if (isset($options['template_dir'])) {
            $this->templateDir = $options['template_dir'];
        }
        if (isset($options['encoding'])) {
            $this->encoding = $encoding;
        }

        
    }
}

class Request
{
    protected $_params;
    protected $_urlParams;
    protected $_cookies;

    public function __get($name)
    {
    }

    public function __isset($name)
    {
    }

    public function param($name, $isMultiple = false)
    {
    }

    public function urlParam($index)
    {
    }

    public function cookie($name)
    {
    }
}

class Session
{
}

class Utils
{
    public static function env($name)
    {
        $value = getenv($name);
        if ($value === false) {
            return null;
        }
        return $value;
    }

    public static function uri()
    {
        $uri = self::env('REQUEST_URI');
        if (is_null($uri)) {
            // IIS
            return self::env('URI');
        }
        $pos = strpos($uri, '?');
        if ($pos !== false) {
            $uri = substr($uri, 0, $pos);
        }
        return $uri;
    }

    public static function pathInfo()
    {
        $uri = self::uri();
        $scriptName = self::env('SCRIPT_NAME');
        $trimPattern = '';
        if (preg_match('/^' . preg_quote($scriptName, '/') . '/', $uri)) {
            // without mod_rewrite
            $trimPattern = preg_quote($scriptName, '/');
        } else {
            // with mod_rewrite, hiding a file name
            $trimPattern = preg_quote(dirname($scriptName), '/');
        }
        return preg_replace("/^{$trimPattern}/", '', $uri);
    }

    public static function requestMethod()
    {
        $requestMethod = strtoupper(self::env('REQUEST_METHOD'));
        if ($requestMethod === 'POST' && isset($_POST['_method'])) {
            $pseudoRequestMethod = strtoupper($_POST['_method']);
            if ($pseudoRequestMethod === 'PUT'
                || $pseudoRequestMethod === 'DELETE') {
                $requestMethod = $pseudoRequestMethod;
            }
        }
        return $requestMethod;
    }
}

class Exception extends \Exception
{
}
