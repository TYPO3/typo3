<?php
/**
 * Net_URL2, a class representing a URL as per RFC 3986.
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2007-2009, Peytz & Co. A/S
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the distribution.
 *   * Neither the name of the Net_URL2 nor the names of its contributors may
 *     be used to endorse or promote products derived from this software
 *     without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category  Networking
 * @package   Net_URL2
 * @author    Christian Schmidt <schmidt@php.net>
 * @copyright 2007-2009 Peytz & Co. A/S
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   CVS: $Id: URL2.php 309223 2011-03-14 14:26:32Z till $
 * @link      http://www.rfc-editor.org/rfc/rfc3986.txt
 */

/**
 * Represents a URL as per RFC 3986.
 *
 * @category  Networking
 * @package   Net_URL2
 * @author    Christian Schmidt <schmidt@php.net>
 * @copyright 2007-2009 Peytz & Co. A/S
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/Net_URL2
 */
class Net_URL2
{
    /**
     * Do strict parsing in resolve() (see RFC 3986, section 5.2.2). Default
     * is true.
     */
    const OPTION_STRICT = 'strict';

    /**
     * Represent arrays in query using PHP's [] notation. Default is true.
     */
    const OPTION_USE_BRACKETS = 'use_brackets';

    /**
     * URL-encode query variable keys. Default is true.
     */
    const OPTION_ENCODE_KEYS = 'encode_keys';

    /**
     * Query variable separators when parsing the query string. Every character
     * is considered a separator. Default is "&".
     */
    const OPTION_SEPARATOR_INPUT = 'input_separator';

    /**
     * Query variable separator used when generating the query string. Default
     * is "&".
     */
    const OPTION_SEPARATOR_OUTPUT = 'output_separator';

    /**
     * Default options corresponds to how PHP handles $_GET.
     */
    private $_options = array(
        self::OPTION_STRICT           => true,
        self::OPTION_USE_BRACKETS     => true,
        self::OPTION_ENCODE_KEYS      => true,
        self::OPTION_SEPARATOR_INPUT  => '&',
        self::OPTION_SEPARATOR_OUTPUT => '&',
        );

    /**
     * @var  string|bool
     */
    private $_scheme = false;

    /**
     * @var  string|bool
     */
    private $_userinfo = false;

    /**
     * @var  string|bool
     */
    private $_host = false;

    /**
     * @var  string|bool
     */
    private $_port = false;

    /**
     * @var  string
     */
    private $_path = '';

    /**
     * @var  string|bool
     */
    private $_query = false;

    /**
     * @var  string|bool
     */
    private $_fragment = false;

    /**
     * Constructor.
     *
     * @param string $url     an absolute or relative URL
     * @param array  $options an array of OPTION_xxx constants
     *
     * @return $this
     * @uses   self::parseUrl()
     */
    public function __construct($url, array $options = array())
    {
        foreach ($options as $optionName => $value) {
            if (array_key_exists($optionName, $this->_options)) {
                $this->_options[$optionName] = $value;
            }
        }

        $this->parseUrl($url);
    }

    /**
     * Magic Setter.
     *
     * This method will magically set the value of a private variable ($var)
     * with the value passed as the args
     *
     * @param  string $var      The private variable to set.
     * @param  mixed  $arg      An argument of any type.
     * @return void
     */
    public function __set($var, $arg)
    {
        $method = 'set' . $var;
        if (method_exists($this, $method)) {
            $this->$method($arg);
        }
    }

    /**
     * Magic Getter.
     *
     * This is the magic get method to retrieve the private variable
     * that was set by either __set() or it's setter...
     *
     * @param  string $var         The property name to retrieve.
     * @return mixed  $this->$var  Either a boolean false if the
     *                             property is not set or the value
     *                             of the private property.
     */
    public function __get($var)
    {
        $method = 'get' . $var;
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return false;
    }

    /**
     * Returns the scheme, e.g. "http" or "urn", or false if there is no
     * scheme specified, i.e. if this is a relative URL.
     *
     * @return  string|bool
     */
    public function getScheme()
    {
        return $this->_scheme;
    }

    /**
     * Sets the scheme, e.g. "http" or "urn". Specify false if there is no
     * scheme specified, i.e. if this is a relative URL.
     *
     * @param string|bool $scheme e.g. "http" or "urn", or false if there is no
     *                            scheme specified, i.e. if this is a relative
     *                            URL
     *
     * @return $this
     * @see    getScheme()
     */
    public function setScheme($scheme)
    {
        $this->_scheme = $scheme;
        return $this;
    }

    /**
     * Returns the user part of the userinfo part (the part preceding the first
     *  ":"), or false if there is no userinfo part.
     *
     * @return  string|bool
     */
    public function getUser()
    {
        return $this->_userinfo !== false
            ? preg_replace('@:.*$@', '', $this->_userinfo)
            : false;
    }

    /**
     * Returns the password part of the userinfo part (the part after the first
     *  ":"), or false if there is no userinfo part (i.e. the URL does not
     * contain "@" in front of the hostname) or the userinfo part does not
     * contain ":".
     *
     * @return  string|bool
     */
    public function getPassword()
    {
        return $this->_userinfo !== false
            ? substr(strstr($this->_userinfo, ':'), 1)
            : false;
    }

    /**
     * Returns the userinfo part, or false if there is none, i.e. if the
     * authority part does not contain "@".
     *
     * @return  string|bool
     */
    public function getUserinfo()
    {
        return $this->_userinfo;
    }

    /**
     * Sets the userinfo part. If two arguments are passed, they are combined
     * in the userinfo part as username ":" password.
     *
     * @param string|bool $userinfo userinfo or username
     * @param string|bool $password optional password, or false
     *
     * @return $this
     */
    public function setUserinfo($userinfo, $password = false)
    {
        $this->_userinfo = $userinfo;
        if ($password !== false) {
            $this->_userinfo .= ':' . $password;
        }
        return $this;
    }

    /**
     * Returns the host part, or false if there is no authority part, e.g.
     * relative URLs.
     *
     * @return  string|bool a hostname, an IP address, or false
     */
    public function getHost()
    {
        return $this->_host;
    }

    /**
     * Sets the host part. Specify false if there is no authority part, e.g.
     * relative URLs.
     *
     * @param string|bool $host a hostname, an IP address, or false
     *
     * @return $this
     */
    public function setHost($host)
    {
        $this->_host = $host;
        return $this;
    }

    /**
     * Returns the port number, or false if there is no port number specified,
     * i.e. if the default port is to be used.
     *
     * @return  string|bool
     */
    public function getPort()
    {
        return $this->_port;
    }

    /**
     * Sets the port number. Specify false if there is no port number specified,
     * i.e. if the default port is to be used.
     *
     * @param string|bool $port a port number, or false
     *
     * @return $this
     */
    public function setPort($port)
    {
        $this->_port = $port;
        return $this;
    }

    /**
     * Returns the authority part, i.e. [ userinfo "@" ] host [ ":" port ], or
     * false if there is no authority.
     *
     * @return string|bool
     */
    public function getAuthority()
    {
        if (!$this->_host) {
            return false;
        }

        $authority = '';

        if ($this->_userinfo !== false) {
            $authority .= $this->_userinfo . '@';
        }

        $authority .= $this->_host;

        if ($this->_port !== false) {
            $authority .= ':' . $this->_port;
        }

        return $authority;
    }

    /**
     * Sets the authority part, i.e. [ userinfo "@" ] host [ ":" port ]. Specify
     * false if there is no authority.
     *
     * @param string|false $authority a hostname or an IP addresse, possibly
     *                                with userinfo prefixed and port number
     *                                appended, e.g. "foo:bar@example.org:81".
     *
     * @return $this
     */
    public function setAuthority($authority)
    {
        $this->_userinfo = false;
        $this->_host     = false;
        $this->_port     = false;
        if (preg_match('@^(([^\@]*)\@)?([^:]+)(:(\d*))?$@', $authority, $reg)) {
            if ($reg[1]) {
                $this->_userinfo = $reg[2];
            }

            $this->_host = $reg[3];
            if (isset($reg[5])) {
                $this->_port = $reg[5];
            }
        }
        return $this;
    }

    /**
     * Returns the path part (possibly an empty string).
     *
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Sets the path part (possibly an empty string).
     *
     * @param string $path a path
     *
     * @return $this
     */
    public function setPath($path)
    {
        $this->_path = $path;
        return $this;
    }

    /**
     * Returns the query string (excluding the leading "?"), or false if "?"
     * is not present in the URL.
     *
     * @return  string|bool
     * @see     self::getQueryVariables()
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * Sets the query string (excluding the leading "?"). Specify false if "?"
     * is not present in the URL.
     *
     * @param string|bool $query a query string, e.g. "foo=1&bar=2"
     *
     * @return $this
     * @see    self::setQueryVariables()
     */
    public function setQuery($query)
    {
        $this->_query = $query;
        return $this;
    }

    /**
     * Returns the fragment name, or false if "#" is not present in the URL.
     *
     * @return  string|bool
     */
    public function getFragment()
    {
        return $this->_fragment;
    }

    /**
     * Sets the fragment name. Specify false if "#" is not present in the URL.
     *
     * @param string|bool $fragment a fragment excluding the leading "#", or
     *                              false
     *
     * @return $this
     */
    public function setFragment($fragment)
    {
        $this->_fragment = $fragment;
        return $this;
    }

    /**
     * Returns the query string like an array as the variables would appear in
     * $_GET in a PHP script. If the URL does not contain a "?", an empty array
     * is returned.
     *
     * @return  array
     */
    public function getQueryVariables()
    {
        $pattern = '/[' .
                   preg_quote($this->getOption(self::OPTION_SEPARATOR_INPUT), '/') .
                   ']/';
        $parts   = preg_split($pattern, $this->_query, -1, PREG_SPLIT_NO_EMPTY);
        $return  = array();

        foreach ($parts as $part) {
            if (strpos($part, '=') !== false) {
                list($key, $value) = explode('=', $part, 2);
            } else {
                $key   = $part;
                $value = null;
            }

            if ($this->getOption(self::OPTION_ENCODE_KEYS)) {
                $key = rawurldecode($key);
            }
            $value = rawurldecode($value);

            if ($this->getOption(self::OPTION_USE_BRACKETS) &&
                preg_match('#^(.*)\[([0-9a-z_-]*)\]#i', $key, $matches)) {

                $key = $matches[1];
                $idx = $matches[2];

                // Ensure is an array
                if (empty($return[$key]) || !is_array($return[$key])) {
                    $return[$key] = array();
                }

                // Add data
                if ($idx === '') {
                    $return[$key][] = $value;
                } else {
                    $return[$key][$idx] = $value;
                }
            } elseif (!$this->getOption(self::OPTION_USE_BRACKETS)
                      && !empty($return[$key])
            ) {
                $return[$key]   = (array) $return[$key];
                $return[$key][] = $value;
            } else {
                $return[$key] = $value;
            }
        }

        return $return;
    }

    /**
     * Sets the query string to the specified variable in the query string.
     *
     * @param array $array (name => value) array
     *
     * @return $this
     */
    public function setQueryVariables(array $array)
    {
        if (!$array) {
            $this->_query = false;
        } else {
            $this->_query = $this->buildQuery(
                $array,
                $this->getOption(self::OPTION_SEPARATOR_OUTPUT)
            );
        }
        return $this;
    }

    /**
     * Sets the specified variable in the query string.
     *
     * @param string $name  variable name
     * @param mixed  $value variable value
     *
     * @return $this
     */
    public function setQueryVariable($name, $value)
    {
        $array = $this->getQueryVariables();
        $array[$name] = $value;
        $this->setQueryVariables($array);
        return $this;
    }

    /**
     * Removes the specifed variable from the query string.
     *
     * @param string $name a query string variable, e.g. "foo" in "?foo=1"
     *
     * @return void
     */
    public function unsetQueryVariable($name)
    {
        $array = $this->getQueryVariables();
        unset($array[$name]);
        $this->setQueryVariables($array);
    }

    /**
     * Returns a string representation of this URL.
     *
     * @return  string
     */
    public function getURL()
    {
        // See RFC 3986, section 5.3
        $url = "";

        if ($this->_scheme !== false) {
            $url .= $this->_scheme . ':';
        }

        $authority = $this->getAuthority();
        if ($authority !== false) {
            $url .= '//' . $authority;
        }
        $url .= $this->_path;

        if ($this->_query !== false) {
            $url .= '?' . $this->_query;
        }

        if ($this->_fragment !== false) {
            $url .= '#' . $this->_fragment;
        }
    
        return $url;
    }

    /**
     * Returns a string representation of this URL.
     *
     * @return  string
     * @see toString()
     */
    public function __toString()
    {
        return $this->getURL();
    }

    /** 
     * Returns a normalized string representation of this URL. This is useful
     * for comparison of URLs.
     *
     * @return  string
     */
    public function getNormalizedURL()
    {
        $url = clone $this;
        $url->normalize();
        return $url->getUrl();
    }

    /** 
     * Returns a normalized Net_URL2 instance.
     *
     * @return  Net_URL2
     */
    public function normalize()
    {
        // See RFC 3886, section 6

        // Schemes are case-insensitive
        if ($this->_scheme) {
            $this->_scheme = strtolower($this->_scheme);
        }

        // Hostnames are case-insensitive
        if ($this->_host) {
            $this->_host = strtolower($this->_host);
        }

        // Remove default port number for known schemes (RFC 3986, section 6.2.3)
        if ($this->_port &&
            $this->_scheme &&
            $this->_port == getservbyname($this->_scheme, 'tcp')) {

            $this->_port = false;
        }

        // Normalize case of %XX percentage-encodings (RFC 3986, section 6.2.2.1)
        foreach (array('_userinfo', '_host', '_path') as $part) {
            if ($this->$part) {
                $this->$part = preg_replace('/%[0-9a-f]{2}/ie',
                                            'strtoupper("\0")',
                                            $this->$part);
            }
        }

        // Path segment normalization (RFC 3986, section 6.2.2.3)
        $this->_path = self::removeDotSegments($this->_path);

        // Scheme based normalization (RFC 3986, section 6.2.3)
        if ($this->_host && !$this->_path) {
            $this->_path = '/';
        }
    }

    /**
     * Returns whether this instance represents an absolute URL.
     *
     * @return  bool
     */
    public function isAbsolute()
    {
        return (bool) $this->_scheme;
    }

    /**
     * Returns an Net_URL2 instance representing an absolute URL relative to
     * this URL.
     *
     * @param Net_URL2|string $reference relative URL
     *
     * @return Net_URL2
     */
    public function resolve($reference)
    {
        if (!$reference instanceof Net_URL2) {
            $reference = new self($reference);
        }
        if (!$this->isAbsolute()) {
            throw new Exception('Base-URL must be absolute');
        }

        // A non-strict parser may ignore a scheme in the reference if it is
        // identical to the base URI's scheme.
        if (!$this->getOption(self::OPTION_STRICT) && $reference->_scheme == $this->_scheme) {
            $reference->_scheme = false;
        }

        $target = new self('');
        if ($reference->_scheme !== false) {
            $target->_scheme = $reference->_scheme;
            $target->setAuthority($reference->getAuthority());
            $target->_path  = self::removeDotSegments($reference->_path);
            $target->_query = $reference->_query;
        } else {
            $authority = $reference->getAuthority();
            if ($authority !== false) {
                $target->setAuthority($authority);
                $target->_path  = self::removeDotSegments($reference->_path);
                $target->_query = $reference->_query;
            } else {
                if ($reference->_path == '') {
                    $target->_path = $this->_path;
                    if ($reference->_query !== false) {
                        $target->_query = $reference->_query;
                    } else {
                        $target->_query = $this->_query;
                    }
                } else {
                    if (substr($reference->_path, 0, 1) == '/') {
                        $target->_path = self::removeDotSegments($reference->_path);
                    } else {
                        // Merge paths (RFC 3986, section 5.2.3)
                        if ($this->_host !== false && $this->_path == '') {
                            $target->_path = '/' . $this->_path;
                        } else {
                            $i = strrpos($this->_path, '/');
                            if ($i !== false) {
                                $target->_path = substr($this->_path, 0, $i + 1);
                            }
                            $target->_path .= $reference->_path;
                        }
                        $target->_path = self::removeDotSegments($target->_path);
                    }
                    $target->_query = $reference->_query;
                }
                $target->setAuthority($this->getAuthority());
            }
            $target->_scheme = $this->_scheme;
        }

        $target->_fragment = $reference->_fragment;

        return $target;
    }

    /**
     * Removes dots as described in RFC 3986, section 5.2.4, e.g.
     * "/foo/../bar/baz" => "/bar/baz"
     *
     * @param string $path a path
     *
     * @return string a path
     */
    public static function removeDotSegments($path)
    {
        $output = '';

        // Make sure not to be trapped in an infinite loop due to a bug in this
        // method
        $j = 0;
        while ($path && $j++ < 100) {
            if (substr($path, 0, 2) == './') {
                // Step 2.A
                $path = substr($path, 2);
            } elseif (substr($path, 0, 3) == '../') {
                // Step 2.A
                $path = substr($path, 3);
            } elseif (substr($path, 0, 3) == '/./' || $path == '/.') {
                // Step 2.B
                $path = '/' . substr($path, 3);
            } elseif (substr($path, 0, 4) == '/../' || $path == '/..') {
                // Step 2.C
                $path   = '/' . substr($path, 4);
                $i      = strrpos($output, '/');
                $output = $i === false ? '' : substr($output, 0, $i);
            } elseif ($path == '.' || $path == '..') {
                // Step 2.D
                $path = '';
            } else {
                // Step 2.E
                $i = strpos($path, '/');
                if ($i === 0) {
                    $i = strpos($path, '/', 1);
                }
                if ($i === false) {
                    $i = strlen($path);
                }
                $output .= substr($path, 0, $i);
                $path = substr($path, $i);
            }
        }

        return $output;
    }

    /**
     * Percent-encodes all non-alphanumeric characters except these: _ . - ~
     * Similar to PHP's rawurlencode(), except that it also encodes ~ in PHP
     * 5.2.x and earlier.
     *
     * @param  $raw the string to encode
     * @return string
     */
    public static function urlencode($string)
    {
    	$encoded = rawurlencode($string);

        // This is only necessary in PHP < 5.3.
        $encoded = str_replace('%7E', '~', $encoded);
        return $encoded;
    }

    /**
     * Returns a Net_URL2 instance representing the canonical URL of the
     * currently executing PHP script.
     *
     * @return  string
     */
    public static function getCanonical()
    {
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            // ALERT - no current URL
            throw new Exception('Script was not called through a webserver');
        }

        // Begin with a relative URL
        $url = new self($_SERVER['PHP_SELF']);
        $url->_scheme = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        $url->_host   = $_SERVER['SERVER_NAME'];
        $port = $_SERVER['SERVER_PORT'];
        if ($url->_scheme == 'http' && $port != 80 ||
            $url->_scheme == 'https' && $port != 443) {

            $url->_port = $port;
        }
        return $url;
    }

    /**
     * Returns the URL used to retrieve the current request.
     *
     * @return  string
     */
    public static function getRequestedURL()
    {
        return self::getRequested()->getUrl();
    }

    /**
     * Returns a Net_URL2 instance representing the URL used to retrieve the
     * current request.
     *
     * @return  Net_URL2
     */
    public static function getRequested()
    {
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            // ALERT - no current URL
            throw new Exception('Script was not called through a webserver');
        }

        // Begin with a relative URL
        $url = new self($_SERVER['REQUEST_URI']);
        $url->_scheme = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        // Set host and possibly port
        $url->setAuthority($_SERVER['HTTP_HOST']);
        return $url;
    }

    /**
     * Returns the value of the specified option.
     *
     * @param string $optionName The name of the option to retrieve
     *
     * @return  mixed
     */
    public function getOption($optionName)
    {
        return isset($this->_options[$optionName])
            ? $this->_options[$optionName] : false;
    }

    /**
     * A simple version of http_build_query in userland. The encoded string is
     * percentage encoded according to RFC 3986.
     *
     * @param array  $data      An array, which has to be converted into
     *                          QUERY_STRING. Anything is possible.
     * @param string $seperator See {@link self::OPTION_SEPARATOR_OUTPUT}
     * @param string $key       For stacked values (arrays in an array).
     *
     * @return string
     */
    protected function buildQuery(array $data, $separator, $key = null)
    {
        $query = array();
        foreach ($data as $name => $value) {
            if ($this->getOption(self::OPTION_ENCODE_KEYS) === true) {
                $name = rawurlencode($name);
            }
            if ($key !== null) {
                if ($this->getOption(self::OPTION_USE_BRACKETS) === true) {
                    $name = $key . '[' . $name . ']';
                } else {
                    $name = $key;
                }
            }
            if (is_array($value)) {
                $query[] = $this->buildQuery($value, $separator, $name);
            } else {
                $query[] = $name . '=' . rawurlencode($value);
            }
        }
        return implode($separator, $query);
    }

    /**
     * This method uses a funky regex to parse the url into the designated parts.
     *
     * @param string $url
     *
     * @return void
     * @uses   self::$_scheme, self::setAuthority(), self::$_path, self::$_query,
     *         self::$_fragment
     * @see    self::__construct()
     */
    protected function parseUrl($url)
    {
        // The regular expression is copied verbatim from RFC 3986, appendix B.
        // The expression does not validate the URL but matches any string.
        preg_match('!^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?!',
                   $url,
                   $matches);

        // "path" is always present (possibly as an empty string); the rest
        // are optional.
        $this->_scheme   = !empty($matches[1]) ? $matches[2] : false;
        $this->setAuthority(!empty($matches[3]) ? $matches[4] : false);
        $this->_path     = $matches[5];
        $this->_query    = !empty($matches[6]) ? $matches[7] : false;
        $this->_fragment = !empty($matches[8]) ? $matches[9] : false;
    }
}
