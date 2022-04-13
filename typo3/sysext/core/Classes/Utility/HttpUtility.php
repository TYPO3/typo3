<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Utility;

/**
 * HTTP Utility class
 */
class HttpUtility
{
    // HTTP Headers, see https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
    // INFORMATIONAL CODES
    public const HTTP_STATUS_100 = 'HTTP/1.1 100 Continue';
    public const HTTP_STATUS_101 = 'HTTP/1.1 101 Switching Protocols';
    public const HTTP_STATUS_102 = 'HTTP/1.1 102 Processing';
    public const HTTP_STATUS_103 = 'HTTP/1.1 103 Early Hints';
    // SUCCESS CODES
    public const HTTP_STATUS_200 = 'HTTP/1.1 200 OK';
    public const HTTP_STATUS_201 = 'HTTP/1.1 201 Created';
    public const HTTP_STATUS_202 = 'HTTP/1.1 202 Accepted';
    public const HTTP_STATUS_203 = 'HTTP/1.1 203 Non-Authoritative Information';
    public const HTTP_STATUS_204 = 'HTTP/1.1 204 No Content';
    public const HTTP_STATUS_205 = 'HTTP/1.1 205 Reset Content';
    public const HTTP_STATUS_206 = 'HTTP/1.1 206 Partial Content';
    public const HTTP_STATUS_207 = 'HTTP/1.1 207 Multi-status';
    public const HTTP_STATUS_208 = 'HTTP/1.1 208 Already Reported';
    public const HTTP_STATUS_226 = 'HTTP/1.1 226 IM Used';
    // REDIRECTION CODES
    public const HTTP_STATUS_300 = 'HTTP/1.1 300 Multiple Choices';
    public const HTTP_STATUS_301 = 'HTTP/1.1 301 Moved Permanently';
    public const HTTP_STATUS_302 = 'HTTP/1.1 302 Found';
    public const HTTP_STATUS_303 = 'HTTP/1.1 303 See Other';
    public const HTTP_STATUS_304 = 'HTTP/1.1 304 Not Modified';
    public const HTTP_STATUS_305 = 'HTTP/1.1 305 Use Proxy';
    public const HTTP_STATUS_306 = 'HTTP/1.1 306 Switch Proxy'; // Deprecated
    public const HTTP_STATUS_307 = 'HTTP/1.1 307 Temporary Redirect';
    public const HTTP_STATUS_308 = 'HTTP/1.1 308 Permanent Redirect';
    // CLIENT ERROR
    public const HTTP_STATUS_400 = 'HTTP/1.1 400 Bad Request';
    public const HTTP_STATUS_401 = 'HTTP/1.1 401 Unauthorized';
    public const HTTP_STATUS_402 = 'HTTP/1.1 402 Payment Required';
    public const HTTP_STATUS_403 = 'HTTP/1.1 403 Forbidden';
    public const HTTP_STATUS_404 = 'HTTP/1.1 404 Not Found';
    public const HTTP_STATUS_405 = 'HTTP/1.1 405 Method Not Allowed';
    public const HTTP_STATUS_406 = 'HTTP/1.1 406 Not Acceptable';
    public const HTTP_STATUS_407 = 'HTTP/1.1 407 Proxy Authentication Required';
    public const HTTP_STATUS_408 = 'HTTP/1.1 408 Request Timeout';
    public const HTTP_STATUS_409 = 'HTTP/1.1 409 Conflict';
    public const HTTP_STATUS_410 = 'HTTP/1.1 410 Gone';
    public const HTTP_STATUS_411 = 'HTTP/1.1 411 Length Required';
    public const HTTP_STATUS_412 = 'HTTP/1.1 412 Precondition Failed';
    public const HTTP_STATUS_413 = 'HTTP/1.1 413 Request Entity Too Large';
    public const HTTP_STATUS_414 = 'HTTP/1.1 414 URI Too Long';
    public const HTTP_STATUS_415 = 'HTTP/1.1 415 Unsupported Media Type';
    public const HTTP_STATUS_416 = 'HTTP/1.1 416 Requested range not satisfiable';
    public const HTTP_STATUS_417 = 'HTTP/1.1 417 Expectation Failed';
    public const HTTP_STATUS_418 = 'HTTP/1.1 418 I\'m a teapot';
    public const HTTP_STATUS_422 = 'HTTP/1.1 422 Unprocessable Entity';
    public const HTTP_STATUS_423 = 'HTTP/1.1 423 Locked';
    public const HTTP_STATUS_424 = 'HTTP/1.1 424 Failed Dependency';
    public const HTTP_STATUS_425 = 'HTTP/1.1 425 Unordered Collection';
    public const HTTP_STATUS_426 = 'HTTP/1.1 426 Upgrade Required';
    public const HTTP_STATUS_428 = 'HTTP/1.1 428 Precondition Required';
    public const HTTP_STATUS_429 = 'HTTP/1.1 429 Too Many Requests';
    public const HTTP_STATUS_431 = 'HTTP/1.1 431 Request Header Fields Too Large';
    public const HTTP_STATUS_451 = 'HTTP/1.1 451 Unavailable For Legal Reasons';
    // SERVER ERROR
    public const HTTP_STATUS_500 = 'HTTP/1.1 500 Internal Server Error';
    public const HTTP_STATUS_501 = 'HTTP/1.1 501 Not Implemented';
    public const HTTP_STATUS_502 = 'HTTP/1.1 502 Bad Gateway';
    public const HTTP_STATUS_503 = 'HTTP/1.1 503 Service Unavailable';
    public const HTTP_STATUS_504 = 'HTTP/1.1 504 Gateway Time-out';
    public const HTTP_STATUS_505 = 'HTTP/1.1 505 Version not Supported';
    public const HTTP_STATUS_506 = 'HTTP/1.1 506 Variant Also Negotiates';
    public const HTTP_STATUS_507 = 'HTTP/1.1 507 Insufficient Storage';
    public const HTTP_STATUS_508 = 'HTTP/1.1 508 Loop Detected';
    public const HTTP_STATUS_509 = 'HTTP/1.1 509 Bandwidth Limit Exceeded';
    public const HTTP_STATUS_511 = 'HTTP/1.1 511 Network Authentication Required';
    // URL Schemes
    public const SCHEME_HTTP = 1;
    public const SCHEME_HTTPS = 2;

    /**
     * Builds a URL string from an array with the URL parts, as e.g. output by parse_url().
     *
     * @see http://www.php.net/parse_url
     */
    public static function buildUrl(array $urlParts): string
    {
        return (isset($urlParts['scheme']) ? $urlParts['scheme'] . '://' : '') .
            (isset($urlParts['user']) ? $urlParts['user'] .
            (isset($urlParts['pass']) ? ':' . $urlParts['pass'] : '') . '@' : '') .
            ($urlParts['host'] ?? '') .
            (isset($urlParts['port']) ? ':' . $urlParts['port'] : '') .
            ($urlParts['path'] ?? '') .
            (isset($urlParts['query']) ? '?' . $urlParts['query'] : '') .
            (isset($urlParts['fragment']) ? '#' . $urlParts['fragment'] : '');
    }

    /**
     * Implodes a multidimensional array of query parameters to a string of GET parameters (eg. param[key][key2]=value2&param[key][key3]=value3)
     * and properly encodes parameter names as well as values. Spaces are encoded as %20
     *
     * @param array $parameters The (multidimensional) array of query parameters with values
     * @param string $prependCharacter If the created query string is not empty, prepend this character "?" or "&" else no prepend
     * @param bool $skipEmptyParameters If true, empty parameters (blank string, empty array, null) are removed.
     * @return string Imploded result, for example param[key][key2]=value2&param[key][key3]=value3
     * @see explodeUrl2Array()
     */
    public static function buildQueryString(array $parameters, string $prependCharacter = '', bool $skipEmptyParameters = false): string
    {
        if (empty($parameters)) {
            return '';
        }

        if ($skipEmptyParameters) {
            // This callback filters empty strings, array and null but keeps zero integers
            $parameters = ArrayUtility::filterRecursive(
                $parameters,
                static function ($item) {
                    return $item !== '' && $item !== [] && $item !== null;
                }
            );
        }

        $queryString = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        $prependCharacter = $prependCharacter === '?' || $prependCharacter === '&' ? $prependCharacter : '';

        return $queryString && $prependCharacter ? $prependCharacter . $queryString : $queryString;
    }
}
