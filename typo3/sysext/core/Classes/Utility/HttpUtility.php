<?php

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
    const HTTP_STATUS_100 = 'HTTP/1.1 100 Continue';
    const HTTP_STATUS_101 = 'HTTP/1.1 101 Switching Protocols';
    const HTTP_STATUS_102 = 'HTTP/1.1 102 Processing';
    const HTTP_STATUS_103 = 'HTTP/1.1 103 Early Hints';
    // SUCCESS CODES
    const HTTP_STATUS_200 = 'HTTP/1.1 200 OK';
    const HTTP_STATUS_201 = 'HTTP/1.1 201 Created';
    const HTTP_STATUS_202 = 'HTTP/1.1 202 Accepted';
    const HTTP_STATUS_203 = 'HTTP/1.1 203 Non-Authoritative Information';
    const HTTP_STATUS_204 = 'HTTP/1.1 204 No Content';
    const HTTP_STATUS_205 = 'HTTP/1.1 205 Reset Content';
    const HTTP_STATUS_206 = 'HTTP/1.1 206 Partial Content';
    const HTTP_STATUS_207 = 'HTTP/1.1 207 Multi-status';
    const HTTP_STATUS_208 = 'HTTP/1.1 208 Already Reported';
    const HTTP_STATUS_226 = 'HTTP/1.1 226 IM Used';
    // REDIRECTION CODES
    const HTTP_STATUS_300 = 'HTTP/1.1 300 Multiple Choices';
    const HTTP_STATUS_301 = 'HTTP/1.1 301 Moved Permanently';
    const HTTP_STATUS_302 = 'HTTP/1.1 302 Found';
    const HTTP_STATUS_303 = 'HTTP/1.1 303 See Other';
    const HTTP_STATUS_304 = 'HTTP/1.1 304 Not Modified';
    const HTTP_STATUS_305 = 'HTTP/1.1 305 Use Proxy';
    const HTTP_STATUS_306 = 'HTTP/1.1 306 Switch Proxy'; // Deprecated
    const HTTP_STATUS_307 = 'HTTP/1.1 307 Temporary Redirect';
    const HTTP_STATUS_308 = 'HTTP/1.1 308 Permanent Redirect';
    // CLIENT ERROR
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
    const HTTP_STATUS_414 = 'HTTP/1.1 414 URI Too Long';
    const HTTP_STATUS_415 = 'HTTP/1.1 415 Unsupported Media Type';
    const HTTP_STATUS_416 = 'HTTP/1.1 416 Requested range not satisfiable';
    const HTTP_STATUS_417 = 'HTTP/1.1 417 Expectation Failed';
    const HTTP_STATUS_418 = 'HTTP/1.1 418 I\'m a teapot';
    const HTTP_STATUS_422 = 'HTTP/1.1 422 Unprocessable Entity';
    const HTTP_STATUS_423 = 'HTTP/1.1 423 Locked';
    const HTTP_STATUS_424 = 'HTTP/1.1 424 Failed Dependency';
    const HTTP_STATUS_425 = 'HTTP/1.1 425 Unordered Collection';
    const HTTP_STATUS_426 = 'HTTP/1.1 426 Upgrade Required';
    const HTTP_STATUS_428 = 'HTTP/1.1 428 Precondition Required';
    const HTTP_STATUS_429 = 'HTTP/1.1 429 Too Many Requests';
    const HTTP_STATUS_431 = 'HTTP/1.1 431 Request Header Fields Too Large';
    const HTTP_STATUS_451 = 'HTTP/1.1 451 Unavailable For Legal Reasons';
    // SERVER ERROR
    const HTTP_STATUS_500 = 'HTTP/1.1 500 Internal Server Error';
    const HTTP_STATUS_501 = 'HTTP/1.1 501 Not Implemented';
    const HTTP_STATUS_502 = 'HTTP/1.1 502 Bad Gateway';
    const HTTP_STATUS_503 = 'HTTP/1.1 503 Service Unavailable';
    const HTTP_STATUS_504 = 'HTTP/1.1 504 Gateway Time-out';
    const HTTP_STATUS_505 = 'HTTP/1.1 505 Version not Supported';
    const HTTP_STATUS_506 = 'HTTP/1.1 506 Variant Also Negotiates';
    const HTTP_STATUS_507 = 'HTTP/1.1 507 Insufficient Storage';
    const HTTP_STATUS_508 = 'HTTP/1.1 508 Loop Detected';
    const HTTP_STATUS_509 = 'HTTP/1.1 509 Bandwidth Limit Exceeded';
    const HTTP_STATUS_511 = 'HTTP/1.1 511 Network Authentication Required';
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
     * @deprecated since v11, will be removed in v12.
     */
    public static function redirect($url, $httpStatus = self::HTTP_STATUS_303)
    {
        // Deprecation logged by setResponseCode()
        self::setResponseCode($httpStatus);
        header('Location: ' . GeneralUtility::locationHeaderUrl($url));
        die;
    }

    /**
     * Set a specific response code like 404.
     *
     * @param string $httpStatus One of the HTTP_STATUS_* class class constants, default to self::HTTP_STATUS_303
     * @deprecated since v11, will be removed in v12.
     */
    public static function setResponseCode($httpStatus = self::HTTP_STATUS_303)
    {
        trigger_error(
            'All methods in ' . __CLASS__ . ', manipulationg HTTP headers, are deprecated and will be removed in v12.',
            E_USER_DEPRECATED
        );

        header($httpStatus);
    }

    /**
     * Set a specific response code and exit script execution.
     *
     * @param string $httpStatus One of the HTTP_STATUS_* class class constants, default to self::HTTP_STATUS_303
     * @deprecated since v11, will be removed in v12.
     */
    public static function setResponseCodeAndExit($httpStatus = self::HTTP_STATUS_303)
    {
        // Deprecation logged by setResponseCode()
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
