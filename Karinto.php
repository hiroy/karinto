<?php
/**
 * Karinto - a minimal web application framework
 *
 * PHP version 5.3 or later
 *
 * @author    Hiroyuki Yamaoka
 * @copyright 2011 Hiroyuki Yamaoka
 * @link      https://github.com/hiroy/karinto
 * @license   http://opensource.org/licenses/bsd-license.php New BSD License
 */

namespace Karinto {

abstract class Vars implements \ArrayAccess
{
    protected $_vars = array();

    public function offsetSet($offset, $value)
    {
        $this->_vars[$offset] = $value;
    }

    public function offsetGet($offset)
    {
        if (isset($this->_vars[$offset])) {
            return $this->_vars[$offset];
        }
        return null;
    }

    public function offsetExists($offset)
    {
        return isset($this->_vars[$offset]);
    }

    public function offsetUnset($offset)
    {
        if (isset($this->_vars[$offset])) {
            unset($this->_vars[$offset]);
        }
    }
}

class Application extends Vars
{
    public $templateDir = 'templates';
    public $layoutTemplate;
    public $layoutContentVarName = 'karinto_content_for_layout';
    public $encoding = 'UTF-8';
    public $defaultContentType = 'text/html';
    public $httpVersion = '1.1';
    public $sessionSecretKey;

    protected $_errorCallback;
    protected $_routes = array(
        'GET' => array(), 'POST' => array(),
        'PUT' => array(), 'DELETE' => array());

    protected $_headers = array();
    protected $_cookies = array();
    protected $_body = '';

    protected $_session;

    public function __construct()
    {
        ob_start();
    }

    public function __destruct()
    {
        // send headers
        if (!isset($this->_headers['Content-Type'])
            && strlen($this->defaultContentType) > 0) {
            $this->contentType($this->defaultContentType);
        }
        foreach ($this->_headers as $name => $value) {
            if (ctype_digit(strval($name))) {
                header($value);
            } else {
                header("{$name}: {$value}");
            }
        }
        // session cookies
        if (!is_null($this->_session)) {
            $this->_session->saveInApplication();
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
        if (is_callable($callback)) {
            $this->_errorCallback = $callback;
        }
    }

    public function get($url, $callback)
    {
        if (is_callable($callback)) {
            $this->_routes['GET'][$url] = $callback;
        }
    }

    public function post($url, $callback)
    {
        if (is_callable($callback)) {
            $this->_routes['POST'][$url] = $callback;
        }
    }

    public function put($url, $callback)
    {
        if (is_callable($callback)) {
            $this->_routes['PUT'][$url] = $callback;
        }
    }

    public function delete($url, $callback)
    {
        if (is_callable($callback)) {
            $this->_routes['DELETE'][$url] = $callback;
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

    public function fetch($template, array $values = array())
    {
        $layoutTemplate = null;
        if (!is_null($this->layoutTemplate)) {
            $layoutTemplate = $this->template($this->layoutTemplate);
            if (!is_file($layoutTemplate) || !is_readable($layoutTemplate)) {
                throw new Exception("{$layoutTemplate} is unavailable");
            }
        }

        $template = $this->template($template);
        if (!is_file($template) || !is_readable($template)) {
            throw new Exception("{$template} is unavailable");
        }

        $values = array_merge($this->_vars, $values);
        extract($values, EXTR_SKIP);

        ob_start();
        ob_implicit_flush(false);
        include $template;
        $result = ob_get_clean();

        if (!is_null($layoutTemplate)) {
            ${$this->layoutContentVarName} = $result;
            ob_start();
            ob_implicit_flush(false);
            include $layoutTemplate;
            $result = ob_get_clean();
        }

        return $result;
    }

    public function render($template, array $values = array())
    {
        $result = '';
        try {
            $result = $this->fetch($template, $values);
        } catch (Exception $e) {
            $this->code(404, $e);
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

    public function code($code, \Exception $e = null)
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
            if (is_callable($this->_errorCallback)) {
                call_user_func($this->_errorCallback, $code, $e);
            } elseif ($e instanceof \Exception) {
                throw $e;
            }
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
        if (strlen($this->sessionSecretKey) === 0) {
            throw new Exception('session secret key is not set');
        }
        if (is_null($this->_session)) {
            $this->_session = new Session($this);
        }
        return $this->_session;
    }

    public function run()
    {
        mb_internal_encoding($this->encoding);

        // path info
        $pathInfo = Utils::pathInfo();
        $pathInfoPieces = explode('/', strtolower(trim($pathInfo, '/')));

        // routes
        $requestMethod = Utils::requestMethod();
        $routes = array();
        if (isset($this->_routes[$requestMethod])) {
            $routes = $this->_routes[$requestMethod];
        }

        $urlParams = array();
        while (count($pathInfoPieces) > 0) {

            $urlPath = '/' . implode('/', $pathInfoPieces);

            if (isset($routes[$urlPath])) {
                $callback = $routes[$urlPath];
                if (is_callable($callback)) {
                    $req = new Request(array_reverse($urlParams));
                    try {
                        call_user_func($callback, $req);
                    } catch (\Exception $e) {
                        // uncaught exception
                        $this->code(500, $e);
                    }
                    return;
                }
            }
            // not found
            $urlParams[] = array_pop($pathInfoPieces);
        }
        // not found at last
        $this->code(404);
    }
}

class Request
{
    protected $_params;
    protected $_urlParams;
    protected $_cookies;

    public function __construct(array $urlParams = array())
    {
        $this->_params = $_POST + $_GET;
        $this->_urlParams = $urlParams;
        $this->_cookies = $_COOKIE;
    }

    public function param($name, $isMultiple = false)
    {
        if (isset($this->_params[$name])) {
            if ($isMultiple === is_array($this->_params[$name])) {
                return $this->_params[$name];
            }
        }
        return null;
    }

    public function urlParam($index)
    {
        if (isset($this->_urlParams[$index])) {
            return $this->_urlParams[$index];
        }
        return null;
    }

    public function cookie($name)
    {
        if (isset($this->_cookies[$name])) {
            return $this->_cookies[$name];
        }
        return null;
    }
}

class Session extends Vars
{
    const COOKIE_MAX_LENGTH = 4096;

    protected $_app;
    protected $_isAvailable = false;
    protected $_cookieName;
    protected $_cookieParams;

    public function __construct(Application $app)
    {
        $this->_app = $app;
        $this->_isAvailable = true;

        // use session settings
        $this->_cookieName = session_name();
        $this->_cookieParams = session_get_cookie_params();
        $this->_restore();
    }

    // called at Application::__desctruct internally
    public function saveInApplication()
    {
        if (!$this->_isAvailable) {
            return;
        }
        $expire = time() + $this->_cookieParams['lifetime'];
        $cookieData = base64_encode(serialize($this->_vars))
            . '--' . $this->_digest($this->_vars);
        if (strlen($cookieData) > self::COOKIE_MAX_LENGTH) {
            throw new Exception('The session data is too large.');
        }
        $this->_cookie($cookieData, $expire);
    }

    public function setLifetime($seconds)
    {
        $this->_cookieParams['lifetime'] = $seconds;
    }

    public function destroy()
    {
        $this->_isAvailable = false;
        $this->_vars = array();
        $this->_cookie('', time() - 3600);
    }

    protected function _restore()
    {
        if (!isset($_COOKIE[$this->_cookieName])) {
            // cookie not exists
            return;
        }
        $cookieData = $_COOKIE[$this->_cookieName];
        $dataList = explode('--', $cookieData);
        if (count($dataList) !== 2) {
            // invalid cookie value
            $this->destroy();
            return;
        }
        $vars = unserialize(base64_decode($dataList[0]));
        if ($vars === false || !is_array($vars)) {
            // broken cookie value
            $this->destroy();
            return;
        }
        if ($this->_digest($vars) !== $dataList[1]) {
            // tampered
            $this->destroy();
            return;
        }
        $this->_vars = $vars;
    }

    protected function _digest($data)
    {
        $serializedData = serialize($data);
        return hash_hmac('sha1', $serializedData, $this->_app->sessionSecretKey);
    }

    protected function _cookie($value, $expire)
    {
        $name = $this->_cookieName;
        $params = $this->_cookieParams;

        if (empty($params['domain']) && empty ($params['secure'])) {
            $this->_app->cookie($name, $value, $expire, $params['path']);
        } elseif (empty($params['secure'])) {
            $this->_app->cookie($name, $value, $expire,
                $params['path'], $params['domain']);
        } else {
            $this->_app->cookie($name, $value, $expire,
                $params['path'], $params['domain'], $params['secure']);
        }
    }
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

class Exception extends \Exception
{
}

}

namespace {

if (!function_exists('h')) {
    function h($var)
    {
        return Karinto\Utils::escapeHtml($var);
    }
}

}
