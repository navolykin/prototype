<?php declare(strict_types=1);


namespace Services\Http;

use Exception;

class Request
{
    //https://tools.ietf.org/html/rfc7231#section-4.3.1
    const GET       = 'GET';

    //https://tools.ietf.org/html/rfc7231#section-4.3.2
    const HEAD      = 'HEAD';

    //https://tools.ietf.org/html/rfc7231#section-4.3.3
    const POST      = 'POST';

    //https://tools.ietf.org/html/rfc7231#section-4.3.4
    const PUT       = 'PUT';

    //https://tools.ietf.org/html/rfc7231#section-4.3.5
    const DELETE    = 'DELETE';

    //https://tools.ietf.org/html/rfc7231#section-4.3.6
    const CONNECT   = 'CONNECT';

    //https://tools.ietf.org/html/rfc7231#section-4.3.7
    const OPTIONS   = 'OPTIONS';

    //https://tools.ietf.org/html/rfc7231#section-4.3.8
    const TRACE     = 'TRACE';

    //https://tools.ietf.org/html/rfc5789#section-2
    const PATCH     = 'PATCH';

    const DEFAULT_PROTOCOL = 'HTTP/1.1';

    const HEADER_VALUE_SEPARATOR = ',';

    protected $standart_methods = [
        self::GET, self::HEAD, self::POST, self::PUT, self::DELETE, self::CONNECT, self::OPTIONS, self::TRACE, self::PATCH
    ];

    protected string $method;

    protected string $protocol;

    protected array  $headers          = [];

    protected array  $headers_names    = [];

    protected array  $cookies          = [];

    protected string $ip               = '';

    protected array  $params           = [];

    public function __construct($method, $protocol, $headers, $params, $ip)
    {
        $this->method   = $method;
        $this->protocol = $protocol;
        $this->headers  = $headers;
        $this->params   = $params;
        $this->ip       = $ip;

        foreach ($headers as $header_name => $value) {
            $this->headers_names[] = $header_name;
        }

        /*$cookie_header = $this->getHeaderLine('cookie');
        die($cookie_header);
        if ($cookie_header) {
            $cookies = explode(', ', $cookie_header);
            foreach ($cookies as $cookie) {
                list($key, $value) = explode('=', $cookie);
                $this->cookies[$key] = $value;
            }
        }*/
    }

    public static function fromGlobals(): self
    {
        $method = self::GET;
        if (!empty($_SERVER['REQUEST_METHOD'])) {
            $method = \strtoupper($_SERVER['REQUEST_METHOD']);
        }

        $protocol = self::DEFAULT_PROTOCOL;
        if (!empty($_SERVER['SERVER_PROTOCOL'])) {
            $protocol = $_SERVER['SERVER_PROTOCOL'];
        }

        $params = $_REQUEST;

        $ip = '';
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header_name = str_replace(['HTTP_', '_'], ['', '-'], $key);
                $headers[$header_name] = explode(self::HEADER_VALUE_SEPARATOR, $value);
            }
        }

        return new self($method, $protocol, $headers, $params, $ip);
    }

    //method
    public function getMethod()
    {
        return $this->method;
    }

    public function isMethod(string $method): bool
    {
        return \strtoupper($method) === $this->method;
    }

    public function inMethods(array $methods): bool
    {
        $methods = array_map('strtoupper', $methods);
        return \in_array($this->method, $methods);
    }

    public function outOfMethods(array $methods): bool
    {
        return !$this->inMethods($methods);
    }

    public function isCustomMethod(): bool
    {
        return !\in_array($this->method, $this->standart_methods);
    } 

    public function method($method = null)
    {
        if (is_array($method)) {
            return $this->inMethods($method);
        } elseif (is_string($method)) {
            return $this->isMethod($method);
        }
        return $this->getMethod();
    }

    //ip
    public function ip(): string
    {
        return $this->ip;
    }

    //protocol
    public function getProtocol(): string
    {
        return $this->protocol;
    }

    public function getProtocolVersion():string
    {
        return str_replace('HTTP/', '', $this->protocol);
    }

    public function protocol(): string
    {
        return $this->protocol;
    }

    //headers
    private static function _normolizeHeaderName($name)
    {
        if (!is_string($name)) {
            throw new Exception("The title name must be a string, and the received " . gettype($name));
        }

        return strtoupper(str_replace(' ', '-', trim($name)));
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader($name): bool
    {
        return in_array(self::_normolizeHeaderName($name), $this->headers_names);
    }

    public function getHeader($name): array
    {
        $name = self::_normolizeHeaderName($name);
        if (isset($this->headers[$name])) {
            return $this->headers[$name];
        }
        return [];
    }

    public function getHeaderLine($name): string
    {
        $name = self::_normolizeHeaderName($name);
        if (isset($this->headers[$name])) {
            return implode(',', $this->headers[$name]);
        }
        return '';
    }

    public function getHost(): string
    {
        return $this->getHeaderLine('host');
    }

    public function getUserAgent(): string
    {
        return $this->getHeaderLine('user-agent');
    }

    public function getAcceptTypes(): array
    {
        return $this->getHeader('accept');
    }

    public function hasAcceptType($name): bool
    {
        foreach ($this->getAcceptTypes() as $type) {
            if (strpos($type, $name) === 0 || strpos($type, '*/*') === 0) {
                return true;
            }
        }
        return false;
    }

    public function isAjax(): bool
    {
        if ($this->getHeaderLine('X-REQUESTED-WITH') === 'XMLHttpRequest') {
            return true;
        }
        return false;
    } 

    //cookies
    /*public function getCookies(): array
    {
        return $this->cookies;
    }*/






    /**
     * Checks for the presence of the parameter
     *
     * @param string $name
     * @return boolean
     */
    protected function hasParam(string $name): bool
    {
        return isset($this->params[$name]);
    }

    /**
     * Wrapper, $name string, or array, if an array, each element must exist
     *
     * @param string|array $name
     * @return boolean
     */
    public function has($name): bool
    {
        if (is_string($name)) {
            $result = $this->hasParam($name);
        } elseif (is_array($name)) {
            foreach ($name as $name_item) {
                $result = $this->hasParam($name_item);
            }
        }

        return $result;
    }

    public function hasAny(array $names): bool
    {
        foreach ($names as $name_item) {
            if ($this->hasParam($name_item)) {
                return true;
            }
        }
        return false;
    }

    public function get($name, $default = null)
    {
        $result = $default;
        if (is_string($name)) {
            if ($this->hasParam($name)) {
                $result = $this->params[$name];
            }

        } elseif (is_array($name)) {
            foreach ($name as $name_item) {
                $_result[$name_item] = null;
                if ($this->hasParam($name_item)) {
                    $_result[$name_item] = $this->params[$name_item];
                }
            }
            if (!empty($_result)) {
                $result = $_result;
            }
        }

        return $result;
    }

    public function __get($name)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }
        return null;
    }

    public function __set($name, $value)
    {
        throw new Exception('Immutable');
    }

    public function __call($name, $arguments)
    {
        if ('is' . \ucwords(\strtolower($this->getMethod())) . 'Method' == $name) {
            return true;
        } elseif ((bool) \preg_match('/^(is).+(Method)$/', $name)) {
            return false;
        }
    }
}
