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

    protected $_headers = array();
    protected $_cookies = array();
    protected $_body = '';

    public function __construct(array $options = array())
    {
        if (isset($options['template_dir'])) {
            $this->templateDir = $options['template_dir'];
        }
        if (isset($options['encoding'])) {
            $this->encoding = $encoding;
        }
        ob_start();
    }

    public function __destruct()
    {
        // send headers
        foreach ($this->_headers as $name => $value) {
            if (ctype_digit(strval($name)) {
                header($value);
            } else {
                header("{$name}: {$value}");
            }
        }
        // send cookies
        foreach ($this->_cookies as $c) {
            setcookie($c['name'], $c['value'], $c['expire'],
                $c['path'], $c['domain'], $c['secure'], $c['http_only']);
        }
        // output
        echo $this->_body;
        ob_end_flush();
    }

    public function error($callback)
    {
    }

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

    public function print($text)
    {
        $this->_body .= $text;
    }

    public function fetch($template, $values)
    {
        $template = $this->templateDir . DIRECTORY_SEPARATOR . $template;
        if (!is_file($template) || !is_readable($template)) {
            throw new Exception("{$template} is unavailable");
        }

        extract($values, EXTR_SKIP);

        ob_start();
        ob_implicit_flush(false);
        include $template;
        $result = ob_get_clean();

        return $result;
    }

    public function render($template, $values)
    {
        $result = '';
        try {
            $result = $this->fetch($template, $values);
        } catch (Exception $e) {
            $this->code(404);
        }
        $this->print($result);
    }

    public function json($values)
    {
        $json = json_encode($values);
        $this->contentType('application/json');
        $this->print($json);
    }

    public function redirect($url, $code = 302, $isSecure = false)
    {
        if (substr($url, 0, 1) === '/') {
            $httpHost = Utils::env('HTTP_HOST');
            $url = ($isSecure ? 'https://' : 'http://') . $httpHost . $url;
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $this->code($code);
        $this->header('Location', $url);
        $this->print('<html><head><meta http-equiv="refresh" content="0;'
            . 'url=' . htmlentities($url, ENT_QUOTES) . '"></head></html>');
    }

    public function header($name, $value)
    {
        if (is_null($name)) {
            $this->_headers[] = $value;
        } else {
            $this->_headers[$name] = $value;
        }
    }

    public function contentType($type, $charset = null)
    {
        if (is_null($charset)) {
            $charset = $this->encoding;
        }
        $this->header('Content-Type', "{$type}; charset={$charset}");
    }

    public function contentTypeHtml($charset = null)
    {
        $this->contentType('text/html', $charset);
    }

    public function code($code)
    {
    }

    public function cookie($name, $value, $expire = null,
        $path = '/', $domain = '', $secure = false, $httpOnly = false)
    {
    }

    public function session()
    {
    }

    public function run()
    {
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
