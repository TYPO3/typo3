<?php
namespace TYPO3\CMS\Core\Utility;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * HTTP Utility class
 */
class HttpUtility
{
    // HTTP Headers, see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html for Details
    const HTTP_STATUS_100 = 'HTTP/1.1 100 Continue';
    const HTTP_STATUS_101 = 'HTTP/1.1 101 Switching Protocols';
    const HTTP_STATUS_200 = 'HTTP/1.1 200 OK';
    const HTTP_STATUS_201 = 'HTTP/1.1 201 Created';
    const HTTP_STATUS_202 = 'HTTP/1.1 202 Accepted';
    const HTTP_STATUS_203 = 'HTTP/1.1 203 Non-Authoritative Information';
    const HTTP_STATUS_204 = 'HTTP/1.1 204 No Content';
    const HTTP_STATUS_205 = 'HTTP/1.1 205 Reset Content';
    const HTTP_STATUS_206 = 'HTTP/1.1 206 Partial Content';
    const HTTP_STATUS_300 = 'HTTP/1.1 300 Multiple Choices';
    const HTTP_STATUS_301 = 'HTTP/1.1 301 Moved Permanently';
    const HTTP_STATUS_302 = 'HTTP/1.1 302 Found';
    const HTTP_STATUS_303 = 'HTTP/1.1 303 See Other';
    const HTTP_STATUS_304 = 'HTTP/1.1 304 Not Modified';
    const HTTP_STATUS_305 = 'HTTP/1.1 305 Use Proxy';
    const HTTP_STATUS_307 = 'HTTP/1.1 307 Temporary Redirect';
    const HTTP_STATUS_400 = 'HTTP/1.1 400 Bad Request';
    const HTTP_STATUS_401 = 'HTTP/1.1 401 Unauthorized';
    const HTTP_STATUS_402 = 'HTTP/1.1 402 Payment Required';
    const HTTP_STATUS_403 = 'HTTP/1.1 403 Forbidden';
    const HTTP_STATUS_404 = 'HTTP/1.1 404 Not Found';
    const HTTP_STATUS_405 = 'HTTP/1.1 405 Method Not Allowed';
    const HTTP_STATUS_406 = 'HTTP/1.1 406 Not Acceptable';
    const HTTP_STATUS_407 = 'HTTP/1.1 407 Proxy Authentication Required';
    const HTTP_STATUS_408 = 'HTTP/1.1 408 Request Timeout';
    const HTTP_STATUS_409 = 'HTTP/1.1 409 Conflict';
    const HTTP_STATUS_410 = 'HTTP/1.1 410 Gone';
    const HTTP_STATUS_411 = 'HTTP/1.1 411 Length Required';
    const HTTP_STATUS_412 = 'HTTP/1.1 412 Precondition Failed';
    const HTTP_STATUS_413 = 'HTTP/1.1 413 Request Entity Too Large';
    const HTTP_STATUS_414 = 'HTTP/1.1 414 Request-URI Too Long';
    const HTTP_STATUS_415 = 'HTTP/1.1 415 Unsupported Media Type';
    const HTTP_STATUS_416 = 'HTTP/1.1 416 Requested Range Not Satisfiable';
    const HTTP_STATUS_417 = 'HTTP/1.1 417 Expectation Failed';
    const HTTP_STATUS_500 = 'HTTP/1.1 500 Internal Server Error';
    const HTTP_STATUS_501 = 'HTTP/1.1 501 Not Implemented';
    const HTTP_STATUS_502 = 'HTTP/1.1 502 Bad Gateway';
    const HTTP_STATUS_503 = 'HTTP/1.1 503 Service Unavailable';
    const HTTP_STATUS_504 = 'HTTP/1.1 504 Gateway Timeout';
    const HTTP_STATUS_505 = 'HTTP/1.1 505 Version Not Supported';
    // URL Schemes
    const SCHEME_HTTP = 1;
    const SCHEME_HTTPS = 2;

    /**
     * Sends a redirect header response and exits. Additionally the URL is
     * checked and if needed corrected to match the format required for a
     * Location redirect header. By default the HTTP status code sent is
     * a 'HTTP/1.1 303 See Other'.
     *
     * @param string $url The target URL to redirect to
     * @param string $httpStatus An optional HTTP status header. Default is 'HTTP/1.1 303 See Other'
     */
    public static function redirect($url, $httpStatus = self::HTTP_STATUS_303)
    {
        self::setResponseCode($httpStatus);
        header('Location: ' . GeneralUtility::locationHeaderUrl($url));
        die;
    }

    /**
     * Set a specific response code like 404.
     *
     * @param string $httpStatus One of the HTTP_STATUS_* class class constants, default to self::HTTP_STATUS_303
     * @return void
     */
    public static function setResponseCode($httpStatus = self::HTTP_STATUS_303)
    {
        header($httpStatus);
    }

    /**
     * Set a specific response code and exit script execution.
     *
     * @param string $httpStatus One of the HTTP_STATUS_* class class constants, default to self::HTTP_STATUS_303
     * @return void
     */
    public static function setResponseCodeAndExit($httpStatus = self::HTTP_STATUS_303)
    {
        self::setResponseCode($httpStatus);
        die;
    }

    /**
     * Builds a URL string from an array with the URL parts, as e.g. output by parse_url().
     *
     * @param array $urlParts
     * @return string
     * @see http://www.php.net/parse_url
     */
    public static function buildUrl(array $urlParts)
    {
        return (isset($urlParts['scheme']) ? $urlParts['scheme'] . '://' : '') .
            (isset($urlParts['user']) ? $urlParts['user'] .
            (isset($urlParts['pass']) ? ':' . $urlParts['pass'] : '') . '@' : '') .
            (isset($urlParts['host']) ? $urlParts['host'] : '') .
            (isset($urlParts['port']) ? ':' . $urlParts['port'] : '') .
            (isset($urlParts['path']) ? $urlParts['path'] : '') .
            (isset($urlParts['query']) ? '?' . $urlParts['query'] : '') .
            (isset($urlParts['fragment']) ? '#' . $urlParts['fragment'] : '');
    }
}
