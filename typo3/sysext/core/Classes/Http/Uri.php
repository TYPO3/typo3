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

use Psr\Http\Message\UriInterface;

/**
 * Represents a URI based on the PSR-7 Standard.
 *
 * Highly inspired by https://github.com/phly/http/
 *
 * @internal Note that this is not public API yet.
 */
class Uri implements UriInterface
{
    /**
     * Sub-delimiters used in query strings and fragments.
     *
     * @const string
     */
    const SUBDELIMITER_CHARLIST = '!\$&\'\(\)\*\+,;=';

    /**
     * Unreserved characters used in paths, query strings, and fragments.
     *
     * @const string
     */
    const UNRESERVED_CHARLIST = 'a-zA-Z0-9_\-\.~';

    /**
     * The default scheme for the URI
     * @var string
     */
    protected $scheme;

    /**
     * @var int[] Associative array containing schemes and their default ports.
     */
    protected $supportedSchemes = [
        'http'  => 80,
        'https' => 443
    ];

    /**
     * The authority part of the URI
     * @var string
     */
    protected $authority = '';

    /**
     * The userInfo part of the URI
     * @var string
     */
    protected $userInfo = '';

    /**
     * The host part of the URI
     * @var string
     */
    protected $host = '';

    /**
     * The port of the URI (empty if it is the standard port for the scheme)
     * @var int|NULL
     */
    protected $port = null;

    /**
     * The path part of the URI (can be empty or /)
     * @var string
     */
    protected $path = '';

    /**
     * The query part of the URI without the ?
     * @var string
     */
    protected $query;

    /**
     * The fragment part of the URI without the # before
     * @var string
     */
    protected $fragment;

    /**
     * @param string|null $uri The full URI including query string and fragment
     * @throws \InvalidArgumentException when the URI is not a string
     */
    public function __construct($uri = '')
    {
        if (!is_string($uri)) {
            $argumentType = is_object($uri) ? get_class($uri) : gettype($uri);
            throw new \InvalidArgumentException('URI passed must be a string, but is of type "' . $argumentType . '"', 1436717320);
        }
        if (!empty($uri)) {
            $this->parseUri($uri);
        }
    }

    /**
     * helper function for parsing the full URI string
     * @param string $uri
     * @throws \InvalidArgumentException if the URI is malformed.
     */
    protected function parseUri($uri)
    {
        $uriParts = parse_url($uri);

        if ($uriParts === false) {
            throw new \InvalidArgumentException('The parsedUri string appears to be malformed', 1436717322);
        }

        if (isset($uriParts['scheme'])) {
            $this->scheme = $this->sanitizeScheme($uriParts['scheme']);
        }

        if (isset($uriParts['user'])) {
            $this->userInfo = $uriParts['user'];
            if (isset($uriParts['pass'])) {
                $this->userInfo .= ':' . $uriParts['pass'];
            }
        }

        if (isset($uriParts['host'])) {
            $this->host = $uriParts['host'];
        }

        if (isset($uriParts['port'])) {
            $this->port = (int)$uriParts['port'];
        }

        if (isset($uriParts['path'])) {
            $this->path = $this->sanitizePath($uriParts['path']);
        }

        if (isset($uriParts['query'])) {
            $this->query = $this->sanitizeQuery($uriParts['query']);
        }

        if (isset($uriParts['fragment'])) {
            $this->fragment = $this->sanitizeFragment($uriParts['fragment']);
        }
    }

    /**
     * Retrieve the scheme component of the URI.
     *
     * If no scheme is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     *
     * The trailing ":" character is not part of the scheme and MUST NOT be
     * added.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string The URI scheme.
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Retrieve the authority component of the URI.
     *
     * If no authority information is present, this method MUST return an empty
     * string.
     *
     * The authority syntax of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority()
    {
        if (empty($this->host)) {
            return '';
        }

        $authority = $this->host;
        if (!empty($this->userInfo)) {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->isNonStandardPort($this->scheme, $this->host, $this->port)) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    /**
     * Retrieve the user information component of the URI.
     *
     * If no user information is present, this method MUST return an empty
     * string.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * The trailing "@" character is not part of the user information and MUST
     * NOT be added.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * Retrieve the host component of the URI.
     *
     * If no host is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @return string The URI host.
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Retrieve the port component of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer. If the port is the standard port
     * used with the current scheme, this method SHOULD return null.
     *
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     *
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     *
     * @return null|int The URI port.
     */
    public function getPort()
    {
        return $this->isNonStandardPort($this->scheme, $this->host, $this->port) ? $this->port : null;
    }

    /**
     * Retrieve the path component of the URI.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
     * do this normalization because in contexts with a trimmed base path, e.g.
     * the front controller, this difference becomes significant. It's the task
     * of the user to handle both "" and "/".
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.3.
     *
     * As an example, if the value should include a slash ("/") not intended as
     * delimiter between path segments, that value MUST be passed in encoded
     * form (e.g., "%2F") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     * @return string The URI path.
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Retrieve the query string of the URI.
     *
     * If no query string is present, this method MUST return an empty string.
     *
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.4.
     *
     * As an example, if a value in a key/value pair of the query string should
     * include an ampersand ("&") not intended as a delimiter between values,
     * that value MUST be passed in encoded form (e.g., "%26") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     * @return string The URI query string.
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Retrieve the fragment component of the URI.
     *
     * If no fragment is present, this method MUST return an empty string.
     *
     * The leading "#" character is not part of the fragment and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.5.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     * @return string The URI fragment.
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Return an instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     *
     * Implementations MUST support the schemes "http" and "https" case
     * insensitively, and MAY accommodate other schemes if required.
     *
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     *
     * @return self A new instance with the specified scheme.
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme($scheme)
    {
        $scheme = $this->sanitizeScheme($scheme);

        $clonedObject = clone $this;
        $clonedObject->scheme = $scheme;
        return $clonedObject;
    }

    /**
     * Return an instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user; an empty string for the user is equivalent to removing user
     * information.
     *
     * @param string $user The user name to use for authority.
     * @param null|string $password The password associated with $user.
     *
     * @return self A new instance with the specified user information.
     */
    public function withUserInfo($user, $password = null)
    {
        $userInfo = $user;
        if (!empty($password)) {
            $userInfo .= ':' . $password;
        }

        $clonedObject = clone $this;
        $clonedObject->userInfo = $userInfo;
        return $clonedObject;
    }

    /**
     * Return an instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified host.
     *
     * An empty host value is equivalent to removing the host.
     *
     * @param string $host The hostname to use with the new instance.
     *
     * @return self A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host)
    {
        $clonedObject = clone $this;
        $clonedObject->host = $host;
        return $clonedObject;
    }

    /**
     * Return an instance with the specified port.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified port.
     *
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param null|int $port The port to use with the new instance; a null value
     *     removes the port information.
     *
     * @return self A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort($port)
    {
        if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($port) === false) {
            $argumentType = is_object($port) ? get_class($port) : gettype($port);
            throw new \InvalidArgumentException('Invalid port "' . $argumentType . '" specified, must be an integer.', 1436717324);
        }

        $port = (int)$port;
        if ($port < 1 || $port > 65535) {
            throw new \InvalidArgumentException('Invalid port "' . $port . '" specified, must be a valid TCP/UDP port.', 1436717326);
        }

        $clonedObject = clone $this;
        $clonedObject->port = $port;
        return $clonedObject;
    }

    /**
     * Return an instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified path.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * If the path is intended to be domain-relative rather than path relative then
     * it must begin with a slash ("/"). Paths not starting with a slash ("/")
     * are assumed to be relative to some base path known to the application or
     * consumer.
     *
     * Users can provide both encoded and decoded path characters.
     * Implementations ensure the correct encoding as outlined in getPath().
     *
     * @param string $path The path to use with the new instance.
     *
     * @return self A new instance with the specified path.
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPath($path)
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException('Invalid path provided. Must be of type string.', 1436717328);
        }

        if (strpos($path, '?') !== false) {
            throw new \InvalidArgumentException('Invalid path provided. Must not contain a query string.', 1436717330);
        }

        if (strpos($path, '#') !== false) {
            throw new \InvalidArgumentException('Invalid path provided; must not contain a URI fragment', 1436717332);
        }

        $path = $this->sanitizePath($path);
        $clonedObject = clone $this;
        $clonedObject->path = $path;
        return $clonedObject;
    }

    /**
     * Return an instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified query string.
     *
     * Users can provide both encoded and decoded query characters.
     * Implementations ensure the correct encoding as outlined in getQuery().
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string $query The query string to use with the new instance.
     *
     * @return self A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query)
    {
        if (!is_string($query)) {
            throw new \InvalidArgumentException('Query string must be a string.', 1436717334);
        }

        if (strpos($query, '#') !== false) {
            throw new \InvalidArgumentException('Query string must not include a URI fragment.', 1436717336);
        }

        $query = $this->sanitizeQuery($query);
        $clonedObject = clone $this;
        $clonedObject->query = $query;
        return $clonedObject;
    }

    /**
     * Return an instance with the specified URI fragment.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified URI fragment.
     *
     * Users can provide both encoded and decoded fragment characters.
     * Implementations ensure the correct encoding as outlined in getFragment().
     *
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param string $fragment The fragment to use with the new instance.
     *
     * @return self A new instance with the specified fragment.
     */
    public function withFragment($fragment)
    {
        $fragment = $this->sanitizeFragment($fragment);
        $clonedObject = clone $this;
        $clonedObject->fragment = $fragment;
        return $clonedObject;
    }

    /**
     * Return the string representation as a URI reference.
     *
     * Depending on which components of the URI are present, the resulting
     * string is either a full URI or relative reference according to RFC 3986,
     * Section 4.1. The method concatenates the various components of the URI,
     * using the appropriate delimiters:
     *
     * - If a scheme is present, it MUST be suffixed by ":".
     * - If an authority is present, it MUST be prefixed by "//".
     * - The path can be concatenated without delimiters. But there are two
     *   cases where the path has to be adjusted to make the URI reference
     *   valid as PHP does not allow to throw an exception in __toString():
     *     - If the path is rootless and an authority is present, the path MUST
     *       be prefixed by "/".
     *     - If the path is starting with more than one "/" and no authority is
     *       present, the starting slashes MUST be reduced to one.
     * - If a query is present, it MUST be prefixed by "?".
     * - If a fragment is present, it MUST be prefixed by "#".
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     * @return string
     */
    public function __toString()
    {
        $uri = '';

        if (!empty($this->scheme)) {
            $uri .= $this->scheme . '://';
        }

        $authority = $this->getAuthority();
        if (!empty($authority)) {
            $uri .= $authority;
        }

        $path = $this->getPath();
        if (!empty($path)) {
            $uri .= '/' . ltrim($path, '/');
        }

        if ($this->query) {
            $uri .= '?' . $this->query;
        }
        if ($this->fragment) {
            $uri .= '#' . $this->fragment;
        }
        return $uri;
    }

    /**
     * Is a given port non-standard for the current scheme?
     *
     * @param string $scheme
     * @param string $host
     * @param int $port
     * @return bool
     */
    protected function isNonStandardPort($scheme, $host, $port)
    {
        if (empty($scheme)) {
            return true;
        }

        if (empty($host) || empty($port)) {
            return false;
        }

        return !isset($this->supportedSchemes[$scheme]) || $port !== $this->supportedSchemes[$scheme];
    }

    /**
     * Filters the scheme to ensure it is a valid scheme.
     *
     * @param string $scheme Scheme name.
     *
     * @return string Filtered scheme.
     * @throws \InvalidArgumentException when a scheme is given which is not supported
     */
    protected function sanitizeScheme($scheme)
    {
        $scheme = strtolower($scheme);
        $scheme = preg_replace('#:(//)?$#', '', $scheme);

        if (empty($scheme)) {
            return '';
        }

        if (!array_key_exists($scheme, $this->supportedSchemes)) {
            throw new \InvalidArgumentException('Unsupported scheme "' . $scheme . '"; must be any empty string or in the set (' . implode(', ', array_keys($this->supportedSchemes)) . ')', 1436717338);
        }

        return $scheme;
    }

    /**
     * Filters the path of a URI to ensure it is properly encoded.
     *
     * @param string $path
     * @return string
     */
    protected function sanitizePath($path)
    {
        return preg_replace_callback(
            '/(?:[^' . self::UNRESERVED_CHARLIST . ':@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/',
            function ($matches) {
                return rawurlencode($matches[0]);
            },
            $path
        );
    }

    /**
     * Filter a query string to ensure it is propertly encoded.
     *
     * Ensures that the values in the query string are properly urlencoded.
     *
     * @param string $query
     * @return string
     */
    protected function sanitizeQuery($query)
    {
        if (!empty($query) && strpos($query, '?') === 0) {
            $query = substr($query, 1);
        }

        $parts = explode('&', $query);
        foreach ($parts as $index => $part) {
            list($key, $value) = $this->splitQueryValue($part);
            if ($value === null) {
                $parts[$index] = $this->sanitizeQueryOrFragment($key);
                continue;
            }
            $parts[$index] = $this->sanitizeQueryOrFragment($key) . '=' . $this->sanitizeQueryOrFragment($value);
        }

        return implode('&', $parts);
    }

    /**
     * Split a query value into a key/value tuple.
     *
     * @param string $value
     * @return array A value with exactly two elements, key and value
     */
    protected function splitQueryValue($value)
    {
        $data = explode('=', $value, 2);
        if (count($data) === 1) {
            $data[] = null;
        }
        return $data;
    }

    /**
     * Filter a fragment value to ensure it is properly encoded.
     *
     * @param null|string $fragment
     * @return string
     */
    protected function sanitizeFragment($fragment)
    {
        if ($fragment === null) {
            $fragment = '';
        }

        if (!empty($fragment) && strpos($fragment, '#') === 0) {
            $fragment = substr($fragment, 1);
        }

        return $this->sanitizeQueryOrFragment($fragment);
    }

    /**
     * Filter a query string key or value, or a fragment.
     *
     * @param string $value
     * @return string
     */
    protected function sanitizeQueryOrFragment($value)
    {
        return preg_replace_callback(
            '/(?:[^' . self::UNRESERVED_CHARLIST . self::SUBDELIMITER_CHARLIST . '%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            function ($matches) {
                return rawurlencode($matches[0]);
            },
            $value
        );
    }
}
