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

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Default implementation for the MessageInterface of the PSR-7 standard
 * It is the base for any request or response for PSR-7.
 *
 * Highly inspired by https://github.com/phly/http/
 *
 * @internal Note that this is not public API yet.
 */
class Message implements MessageInterface
{
    /**
     * The HTTP Protocol version, defaults to 1.1
     * @var string
     */
    protected $protocolVersion = '1.1';

    /**
     * Associative array containing all headers of this Message
     * This is a mixed-case list of the headers (as due to the specification)
     * @var array
     */
    protected $headers = [];

    /**
     * Lowercased version of all headers, in order to check if a header is set or not
     * this way a lot of checks are easier to be set
     * @var array
     */
    protected $lowercasedHeaderNames = [];

    /**
     * The body as a Stream object
     * @var StreamInterface
     */
    protected $body;

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new protocol version.
     *
     * @param string $version HTTP protocol version
     * @return Message
     */
    public function withProtocolVersion($version)
    {
        $clonedObject = clone $this;
        $clonedObject->protocolVersion = $version;
        return $clonedObject;
    }

    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
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
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return array Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings
     *     for that header.
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader($name)
    {
        return isset($this->lowercasedHeaderNames[strtolower($name)]);
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
    public function getHeader($name)
    {
        if (!$this->hasHeader($name)) {
            return [];
        }
        $header = $this->lowercasedHeaderNames[strtolower($name)];
        $headerValue = $this->headers[$header];
        if (is_array($headerValue)) {
            return $headerValue;
        }
        return [$headerValue];
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * If the header does not appear in the message, this method MUST return
     * an empty string.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    public function getHeaderLine($name)
    {
        $headerValue = $this->getHeader($name);
        if (empty($headerValue)) {
            return '';
        }
        return implode(',', $headerValue);
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new and/or updated header and value.
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return Message
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($name, $value)
    {
        if (is_string($value)) {
            $value = [$value];
        }

        if (!is_array($value) || !$this->arrayContainsOnlyStrings($value)) {
            throw new \InvalidArgumentException('Invalid header value for header "' . $name . '"". The value must be a string or an array of strings.', 1436717266);
        }

        $this->validateHeaderName($name);
        $this->validateHeaderValues($value);
        $lowercasedHeaderName = strtolower($name);

        $clonedObject = clone $this;
        $clonedObject->headers[$name] = $value;
        $clonedObject->lowercasedHeaderNames[$lowercasedHeaderName] = $name;
        return $clonedObject;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new header and/or value.
     *
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return Message
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($name, $value)
    {
        if (is_string($value)) {
            $value = [$value];
        }
        if (!is_array($value) || !$this->arrayContainsOnlyStrings($value)) {
            throw new \InvalidArgumentException('Invalid header value for header "' . $name . '". The header value must be a string or array of strings', 1436717267);
        }
        $this->validateHeaderName($name);
        $this->validateHeaderValues($value);
        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }
        $name = $this->lowercasedHeaderNames[strtolower($name)];
        $clonedObject = clone $this;
        $clonedObject->headers[$name] = array_merge($this->headers[$name], $value);
        return $clonedObject;
    }

    /**
     * Return an instance without the specified header.
     *
     * Header resolution MUST be done without case-sensitivity.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the named header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return Message
     */
    public function withoutHeader($name)
    {
        if (!$this->hasHeader($name)) {
            return clone $this;
        }
        // fetch the original header from the lowercased version
        $lowercasedHeader = strtolower($name);
        $name = $this->lowercasedHeaderNames[$lowercasedHeader];
        $clonedObject = clone $this;
        unset($clonedObject->headers[$name], $clonedObject->lowercasedHeaderNames[$lowercasedHeader]);
        return $clonedObject;
    }

    /**
     * Gets the body of the message.
     *
     * @return \Psr\Http\Message\StreamInterface Returns the body as a stream.
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Return an instance with the specified message body.
     *
     * The body MUST be a StreamInterface object.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param \Psr\Http\Message\StreamInterface $body Body.
     * @return Message
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body)
    {
        $clonedObject = clone $this;
        $clonedObject->body = $body;
        return $clonedObject;
    }

    /**
     * Ensure header names and values are valid.
     *
     * @param array $headers
     * @throws \InvalidArgumentException
     */
    protected function assertHeaders(array $headers)
    {
        foreach ($headers as $name => $headerValues) {
            $this->validateHeaderName($name);
            // check if all values are correct
            array_walk($headerValues, function ($value, $key, Message $messageObject) {
                if (!$messageObject->isValidHeaderValue($value)) {
                    throw new \InvalidArgumentException('Invalid header value for header "' . $key . '"', 1436717268);
                }
            }, $this);
        }
    }

    /**
     * Filter a set of headers to ensure they are in the correct internal format.
     *
     * Used by message constructors to allow setting all initial headers at once.
     *
     * @param array $originalHeaders Headers to filter.
     * @return array Filtered headers and names.
     */
    protected function filterHeaders(array $originalHeaders)
    {
        $headerNames = $headers = [];
        foreach ($originalHeaders as $header => $value) {
            if (!is_string($header) || (!is_array($value) && !is_string($value))) {
                continue;
            }
            if (!is_array($value)) {
                $value = [$value];
            }
            $headerNames[strtolower($header)] = $header;
            $headers[$header] = $value;
        }
        return [$headerNames, $headers];
    }

    /**
     * Helper function to test if an array contains only strings
     *
     * @param array $data
     * @return bool
     */
    protected function arrayContainsOnlyStrings(array $data)
    {
        return array_reduce($data, function ($original, $item) {
            return is_string($item) ? $original : false;
        }, true);
    }

    /**
     * Assert that the provided header values are valid.
     *
     * @see http://tools.ietf.org/html/rfc7230#section-3.2
     * @param string[] $values
     * @throws \InvalidArgumentException
     */
    protected function validateHeaderValues(array $values)
    {
        array_walk($values, function ($value, $key, Message $messageObject) {
            if (!$messageObject->isValidHeaderValue($value)) {
                throw new \InvalidArgumentException('Invalid header value for header "' . $key . '"', 1436717269);
            }
        }, $this);
    }

    /**
     * Filter a header value
     *
     * Ensures CRLF header injection vectors are filtered.
     *
     * Per RFC 7230, only VISIBLE ASCII characters, spaces, and horizontal
     * tabs are allowed in values; header continuations MUST consist of
     * a single CRLF sequence followed by a space or horizontal tab.
     *
     * This method filters any values not allowed from the string, and is
     * lossy.
     *
     * @see http://en.wikipedia.org/wiki/HTTP_response_splitting
     * @param string $value
     * @return string
     */
    public function filter($value)
    {
        $value  = (string)$value;
        $length = strlen($value);
        $string = '';
        for ($i = 0; $i < $length; $i += 1) {
            $ascii = ord($value[$i]);

            // Detect continuation sequences
            if ($ascii === 13) {
                $lf = ord($value[$i + 1]);
                $ws = ord($value[$i + 2]);
                if ($lf === 10 && in_array($ws, [9, 32], true)) {
                    $string .= $value[$i] . $value[$i + 1];
                    $i += 1;
                }
                continue;
            }

            // Non-visible, non-whitespace characters
            // 9 === horizontal tab
            // 32-126, 128-254 === visible
            // 127 === DEL
            // 255 === null byte
            if (($ascii < 32 && $ascii !== 9) || $ascii === 127 || $ascii > 254) {
                continue;
            }

            $string .= $value[$i];
        }

        return $string;
    }

    /**
     * Check whether or not a header name is valid and throw an exception.
     *
     * @see http://tools.ietf.org/html/rfc7230#section-3.2
     * @param string $name
     * @throws \InvalidArgumentException
     */
    public function validateHeaderName($name)
    {
        if (!preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $name)) {
            throw new \InvalidArgumentException('Invalid header name, given "' . $name . '"', 1436717270);
        }
    }

    /**
     * Checks if an HTTP header value is valid.
     *
     * Per RFC 7230, only VISIBLE ASCII characters, spaces, and horizontal
     * tabs are allowed in values; header continuations MUST consist of
     * a single CRLF sequence followed by a space or horizontal tab.
     *
     * @see http://en.wikipedia.org/wiki/HTTP_response_splitting
     * @param string $value
     * @return bool
     */
    public function isValidHeaderValue($value)
    {
        $value = (string)$value;

        // Any occurrence of \r or \n is invalid
        if (strpbrk($value, "\r\n") !== false) {
            return false;
        }

        $length = strlen($value);
        for ($i = 0; $i < $length; $i += 1) {
            $ascii = ord($value[$i]);

            // Non-visible, non-whitespace characters
            // 9 === horizontal tab
            // 10 === line feed
            // 13 === carriage return
            // 32-126, 128-254 === visible
            // 127 === DEL
            // 255 === null byte
            if (($ascii < 32 && ! in_array($ascii, [9, 10, 13], true)) || $ascii === 127 || $ascii > 254) {
                return false;
            }
        }

        return true;
    }
}
