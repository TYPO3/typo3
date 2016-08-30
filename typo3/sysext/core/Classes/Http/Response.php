<?php
namespace TYPO3\CMS\Core\Http;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Default implementation for the ResponseInterface of the PSR-7 standard.
 *
 * Highly inspired by https://github.com/phly/http/
 *
 * @internal Note that this is not public API yet.
 */
class Response extends Message implements ResponseInterface
{
    /**
     * The HTTP status code of the response
     * @var int $statusCode
     */
    protected $statusCode;

    /**
     * The reason phrase of the response
     * @var string $reasonPhrase
     */
    protected $reasonPhrase = '';

    /**
     * The standardized and other important HTTP Status Codes
     * @var array
     */
    protected $availableStatusCodes = [
        // INFORMATIONAL CODES
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // SUCCESS CODES
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        // REDIRECTION CODES
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated
        307 => 'Temporary Redirect',
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        // SERVER ERROR
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        509 => 'Bandwidth Limit Exceeded',
        511 => 'Network Authentication Required'
    ];

    /**
     * Constructor for generating new responses
     *
     * @param StreamInterface|string $body
     * @param int $statusCode
     * @param array $headers
     * @throws \InvalidArgumentException if any of the given arguments are given
     */
    public function __construct($body = 'php://temp', $statusCode = 200, $headers = [])
    {
        // Build a streamable object for the body
        if (!is_string($body) && !is_resource($body) && !$body instanceof StreamInterface) {
            throw new \InvalidArgumentException('Body must be a string stream resource identifier, a stream resource, or a StreamInterface instance', 1436717277);
        }

        if (!$body instanceof StreamInterface) {
            $body = new Stream($body, 'rw');
        }
        $this->body = $body;

        if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($statusCode) === false || !array_key_exists((int)$statusCode, $this->availableStatusCodes)) {
            throw new \InvalidArgumentException('The given status code is not a valid HTTP status code.', 1436717278);
        }
        $this->statusCode = (int)$statusCode;

        $this->reasonPhrase = $this->availableStatusCodes[$this->statusCode];
        list($this->headerNames, $headers) = $this->filterHeaders($headers);
        $this->assertHeaders($headers);
        $this->headers = $headers;
    }

    /**
     * Gets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int Status code.
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     *
     * If no reason phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * status code.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated status and reason phrase.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     *
     * @param int $code The 3-digit integer result code to set.
     * @param string $reasonPhrase The reason phrase to use with the
     *     provided status code; if none is provided, implementations MAY
     *     use the defaults as suggested in the HTTP specification.
     * @return Response
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($code) === false || !array_key_exists((int)$code, $this->availableStatusCodes)) {
            throw new \InvalidArgumentException('The given status code is not a valid HTTP status code', 1436717279);
        }
        $clonedObject = clone $this;
        $clonedObject->statusCode = $code;
        $clonedObject->reasonPhrase = $reasonPhrase !== '' ? $reasonPhrase : $this->availableStatusCodes[$code];
        return $clonedObject;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * Because a reason phrase is not a required element in a response
     * status line, the reason phrase value MAY be null. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * status code.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string Reason phrase; must return an empty string if none present.
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }
}
