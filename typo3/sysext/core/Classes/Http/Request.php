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

namespace TYPO3\CMS\Core\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Default implementation for the RequestInterface of the PSR-7 standard
 * It is the base for any request sent BY PHP.
 *
 * Please see ServerRequest for the typical use cases in the framework.
 *
 * Highly inspired by https://github.com/phly/http/
 *
 * @internal Note that this is not public API yet.
 */
class Request extends Message implements RequestInterface
{
    /**
     * The request-target, if it has been provided or calculated.
     */
    protected ?string $requestTarget = null;

    /**
     * The HTTP method.
     */
    protected string $method = 'GET';

    /**
     * Supported HTTP methods
     *
     * @var non-empty-string[]
     */
    protected array $supportedMethods = [
        'CONNECT',
        'DELETE',
        'GET',
        'HEAD',
        'OPTIONS',
        'PATCH',
        'POST',
        'PUT',
        'TRACE',
        // WebDAV methods
        'COPY',
        'LOCK',
        'MKCOL',
        'MOVE',
        'PROPFIND',
        'PROPPATCH',
        'REPORT',
        'UNLOCK',
        // Custom methods
        'PURGE',
        'BAN',
    ];

    /**
     * An instance of the Uri object
     *
     * @todo It is a PSR-7 spec violation for this to be null. This should be corrected.
     */
    protected ?UriInterface $uri;

    /**
     * Constructor, the only place to set all parameters of this Request
     *
     * @param string|UriInterface|null $uri URI for the request, if any.
     * @param string $method HTTP method for the request, if any.
     * @param string|resource|StreamInterface|null $body Message body, if any.
     * @param array $headers Headers for the message, if any.
     * @throws \InvalidArgumentException for any invalid value.
     */
    public function __construct(UriInterface|string|null $uri = null, string $method = 'GET', $body = 'php://input', array $headers = [])
    {
        // Upcast the body to a streamable object, or error
        // if it's an invalid type.
        if ($body instanceof StreamInterface) {
            $this->body = $body;
        } else {
            $this->body = match (get_debug_type($body)) {
                'string', 'resource (stream)' => new Stream($body),
                'null' => null,
                default => throw new \InvalidArgumentException('Body must be a string stream resource identifier, a stream resource, or a StreamInterface instance', 1436717271),
            };
        }

        if (is_string($uri)) {
            $uri = new Uri($uri);
        }

        $this->validateMethod($method);

        $this->method = $method;
        $this->uri = $uri;
        [$this->lowercasedHeaderNames, $headers] = $this->filterHeaders($headers);
        $this->assertHeaders($headers);
        $this->headers = $headers;
    }

    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     * ```
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     * ```
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return array Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings
     *     for that header.
     */
    public function getHeaders(): array
    {
        $headers = parent::getHeaders();
        if (!$this->hasHeader('host') && ($this->uri?->getHost())) {
            $headers['host'] = [$this->getHostFromUri()];
        }
        return $headers;
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     *
     * If the header does not appear in the message, this method MUST return an
     * empty array.
     *
     * @param string $name Case-insensitive header field name.
     * @return string[] An array of string values as provided for the given
     *    header. If the header does not appear in the message, this method MUST
     *    return an empty array.
     */
    public function getHeader(string $name): array
    {
        if (!$this->hasHeader($name) && strtolower($name) === 'host' && ($this->uri?->getHost())) {
            return [$this->getHostFromUri()];
        }
        return parent::getHeader($name);
    }

    /**
     * Retrieve the host from the URI instance
     */
    protected function getHostFromUri(): string
    {
        $host  = $this->uri->getHost();
        $host .= $this->uri->getPort() ? ':' . $this->uri->getPort() : '';
        return $host;
    }

    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     */
    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        if (!$this->uri) {
            return '/';
        }

        $target = $this->uri->getPath();

        if ($this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }

        if (empty($target)) {
            $target = '/';
        }
        return $target;
    }

    /**
     * Return an instance with the specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request target.
     *
     * @link https://tools.ietf.org/html/rfc7230#section-2.7 (for the various
     *     request-target forms allowed in request messages)
     */
    public function withRequestTarget(mixed $requestTarget): static
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new \InvalidArgumentException('Invalid request target provided which contains whitespaces.', 1436717273);
        }
        $clonedObject = clone $this;
        $clonedObject->requestTarget = $requestTarget;
        return $clonedObject;
    }

    /**
     * Retrieves the HTTP method of the request, defaults to GET
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request method.
     *
     * @param string $method Case-sensitive method.
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod(string $method): static
    {
        $clonedObject = clone $this;
        $clonedObject->method = $method;
        return $clonedObject;
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * This method MUST update the Host header of the returned request by
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     *
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     *
     * - If the the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method MUST NOT update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method MUST NOT update
     *   the Host header in the returned request.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-4.3
     *
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $clonedObject = clone $this;
        $clonedObject->uri = $uri;

        if ($preserveHost) {
            return $clonedObject;
        }

        if (!$uri->getHost()) {
            return $clonedObject;
        }

        $host = $uri->getHost();

        if ($uri->getPort()) {
            $host .= ':' . $uri->getPort();
        }

        $clonedObject->lowercasedHeaderNames['host'] = 'Host';
        $clonedObject->headers['Host'] = [$host];
        return $clonedObject;
    }

    /**
     * Validate the HTTP method, helper function.
     *
     * @throws \InvalidArgumentException on invalid HTTP method.
     */
    protected function validateMethod(?string $method): void
    {
        if (is_null($method)) {
            return;
        }

        $method = strtoupper($method);
        if (!in_array($method, $this->supportedMethods, true)) {
            throw new \InvalidArgumentException('Unsupported HTTP method "' . $method . '".', 1436717275);
        }
    }
}
