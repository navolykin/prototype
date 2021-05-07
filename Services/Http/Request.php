<?php

declare(strict_types=1);

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * /
 *  ___                   _        _                 ___   *
 * |  _|                 | |      | |               |_  |  *
 * | |    _ __  _ __ ___ | |_ ___ | |_ _   _ _ __     | |  *
 * | |   | '_ \| '__/ _ \| __/ _ \| __| | | | '_ \    | |  *
 * | |   | |_) | | | (_) | || (_) | |_| |_| | |_) |   | |  *
 * | |_  | .__/|_|  \___/ \__\___/ \__|\__, | .__/   _| |  *
 * |___| | |                            __/ | |     |___|  *
 *       |_|                           |___/|_|            *
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

namespace Services\Http;

use Services\Http\Message;
use Exception;

class Request extends Message
{
    /**
     * @link https://tools.ietf.org/html/rfc7231#section-4.3.1
     */
    const GET       = 'GET';

    /**
     * @link https://tools.ietf.org/html/rfc7231#section-4.3.2
     */
    const HEAD      = 'HEAD';

    /**
     * @link https://tools.ietf.org/html/rfc7231#section-4.3.3
     */
    const POST      = 'POST';

    /**
     * @link https://tools.ietf.org/html/rfc7231#section-4.3.4
     */
    const PUT       = 'PUT';

    /**
     * @link https://tools.ietf.org/html/rfc7231#section-4.3.5
     */
    const DELETE    = 'DELETE';

    /**
     * @link https://tools.ietf.org/html/rfc7231#section-4.3.6
     */
    const CONNECT   = 'CONNECT';

    /**
     * @link https://tools.ietf.org/html/rfc7231#section-4.3.7
     */
    const OPTIONS   = 'OPTIONS';

    /**
     * @link https://tools.ietf.org/html/rfc7231#section-4.3.8
     */
    const TRACE     = 'TRACE';

    /**
     * @link https://tools.ietf.org/html/rfc5789#section-2
     */
    const PATCH     = 'PATCH';

    protected $standart_methods = [
        self::GET,
        self::HEAD,
        self::POST,
        self::PUT,
        self::DELETE,
        self::CONNECT,
        self::OPTIONS,
        self::TRACE,
        self::PATCH
    ];

    protected string $method;

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

    }

    public static function fromGlobals(): self
    {
        $method = self::GET;
        if (!empty($_SERVER['REQUEST_METHOD'])) {
            $method = \strtoupper($_SERVER['REQUEST_METHOD']);
        }

        $protocol = self::HTTP_1_1;
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
                $headers[$header_name] = explode(',', $value);
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

    // headers
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
