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

use Exception;

abstract class Message
{

    /**
     * @link https://tools.ietf.org/html/rfc1945
     */
    const HTTP_1_0 = 'HTTP/1.0';

    /**
     * @link https://tools.ietf.org/html/rfc2616
     */
    const HTTP_1_1 = 'HTTP/1.1';

    /**
     * @link https://tools.ietf.org/html/draft-ietf-httpbis-http2-17
     */
    const HTTP_2 = 'HTTP/2';

    /**
     * @var string Name of the protocol
     */
    protected string $protocol;

    /**
     * @var array all headers
     */
    protected array  $headers          = [];

    protected array  $headers_names    = [];

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

}
