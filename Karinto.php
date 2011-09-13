<?php
namespace Karinto;

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});

class Application
{
    public $templateDir = 'templates';
    public $encoding = 'UTF-8';
    public $httpVersion = '1.1';

    protected $routes = array(
        'GET' => array(), 'POST' => array(),
        'PUT' => array(), 'DELETE' => array());

    protected $_headers = array();
    protected $_cookies = array();
    protected $_body = '';

    public function __construct()
    {
        ob_start();
    }

    public function __destruct()
    {
        // send headers
        foreach ($this->_headers as $name => $value) {
            if (ctype_digit(strval($name))) {
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

    public function output($text)
    {
        $this->_body .= $text;
    }

    public function template($template)
    {
        return $this->templateDir . DIRECTORY_SEPARATOR . $template;
    }

    public function fetch($template, $values)
    {
        $template = $this->template($template);
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
        $this->output($result);
    }

    public function json($values)
    {
        $json = json_encode($values);
        $this->contentType('application/json');
        $this->output($json);
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
        $this->output('<html><head><meta http-equiv="refresh" content="0;'
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
        $messages = array(
            // Informational 1xx
            100 => 'Continue',
            101 => 'Switching Protocols',
            // Success 2xx
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            // Redirection 3xx
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',  // 1.1
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            // 306 is no longer used but still reserved
            307 => 'Temporary Redirect',
            // Client Error 4xx
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            // Server Error 5xx
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            509 => 'Bandwidth Limit Exceeded',
        );
        if (isset($messages[$code])) {
            if ($this->httpVersion !== '1.1') {
                // HTTP/1.0
                $messages[302] = 'Moved Temporarily';
            }
            $message = $messages[$code];
            $this->header('Status', "{$code} {$message}");
            $this->header(null,
                "HTTP/{$this->httpVersion} {$code} {$message}");
        }
        if ($code >= 400) {
            // error
        }
    }

    public function cookie($name, $value, $expire = null,
        $path = '/', $domain = '', $secure = false, $httpOnly = false)
    {
        $this->_cookies[] = array(
            'name'      => $name,
            'value'     => $value,
            'expire'    => $expire,
            'path'      => $path,
            'domain'    => $domain,
            'secure'    => $secure ? true : false,
            'http_only' => $httpOnly,
        );
    }

    public function session()
    {
    }

    public function run()
    {
        mb_internal_encoding($this->encoding);
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

    public static function escapeHtml($var)
    {
        if (is_array($var)) {
            return array_map(array(__CLASS__, __METHOD__), $var);
        }
        if (is_scalar($var)) {
            $var = htmlspecialchars(
                $var, ENT_QUOTES, mb_internal_encoding());
        }
        return $var;
    }
}

function h($var)
{
    return Utils::escapeHtml($var);
}

class Exception extends \Exception
{
}
