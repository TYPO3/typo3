<?php
/**
 * Exception classes for HTTP_Request2 package
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * Copyright (c) 2008-2012, Alexey Borzov <avb@php.net>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * The names of the authors may not be used to endorse or promote products
 *      derived from this software without specific prior written permission.
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
 * @category HTTP
 * @package  HTTP_Request2
 * @author   Alexey Borzov <avb@php.net>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  SVN: $Id: Exception.php 324415 2012-03-21 10:50:50Z avb $
 * @link     http://pear.php.net/package/HTTP_Request2
 */

/**
 * Base class for exceptions in PEAR
 */
require_once 'PEAR/Exception.php';

/**
 * Base exception class for HTTP_Request2 package
 *
 * @category HTTP
 * @package  HTTP_Request2
 * @author   Alexey Borzov <avb@php.net>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 2.1.1
 * @link     http://pear.php.net/package/HTTP_Request2
 * @link     http://pear.php.net/pepr/pepr-proposal-show.php?id=132
 */
class HTTP_Request2_Exception extends PEAR_Exception
{
    /** An invalid argument was passed to a method */
    const INVALID_ARGUMENT   = 1;
    /** Some required value was not available */
    const MISSING_VALUE      = 2;
    /** Request cannot be processed due to errors in PHP configuration */
    const MISCONFIGURATION   = 3;
    /** Error reading the local file */
    const READ_ERROR         = 4;

    /** Server returned a response that does not conform to HTTP protocol */
    const MALFORMED_RESPONSE = 10;
    /** Failure decoding Content-Encoding or Transfer-Encoding of response */
    const DECODE_ERROR       = 20;
    /** Operation timed out */
    const TIMEOUT            = 30;
    /** Number of redirects exceeded 'max_redirects' configuration parameter */
    const TOO_MANY_REDIRECTS = 40;
    /** Redirect to a protocol other than http(s):// */
    const NON_HTTP_REDIRECT  = 50;

    /**
     * Native error code
     * @var int
     */
    private $_nativeCode;

    /**
     * Constructor, can set package error code and native error code
     *
     * @param string $message    exception message
     * @param int    $code       package error code, one of class constants
     * @param int    $nativeCode error code from underlying PHP extension
     */
    public function __construct($message = null, $code = null, $nativeCode = null)
    {
        parent::__construct($message, $code);
        $this->_nativeCode = $nativeCode;
    }

    /**
     * Returns error code produced by underlying PHP extension
     *
     * For Socket Adapter this may contain error number returned by
     * stream_socket_client(), for Curl Adapter this will contain error number
     * returned by curl_errno()
     *
     * @return integer
     */
    public function getNativeCode()
    {
        return $this->_nativeCode;
    }
}

/**
 * Exception thrown in case of missing features
 *
 * @category HTTP
 * @package  HTTP_Request2
 * @author   Alexey Borzov <avb@php.net>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 2.1.1
 * @link     http://pear.php.net/package/HTTP_Request2
 */
class HTTP_Request2_NotImplementedException extends HTTP_Request2_Exception
{
}

/**
 * Exception that represents error in the program logic
 *
 * This exception usually implies a programmer's error, like passing invalid
 * data to methods or trying to use PHP extensions that weren't installed or
 * enabled. Usually exceptions of this kind will be thrown before request even
 * starts.
 *
 * The exception will usually contain a package error code.
 *
 * @category HTTP
 * @package  HTTP_Request2
 * @author   Alexey Borzov <avb@php.net>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 2.1.1
 * @link     http://pear.php.net/package/HTTP_Request2
 */
class HTTP_Request2_LogicException extends HTTP_Request2_Exception
{
}

/**
 * Exception thrown when connection to a web or proxy server fails
 *
 * The exception will not contain a package error code, but will contain
 * native error code, as returned by stream_socket_client() or curl_errno().
 *
 * @category HTTP
 * @package  HTTP_Request2
 * @author   Alexey Borzov <avb@php.net>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 2.1.1
 * @link     http://pear.php.net/package/HTTP_Request2
 */
class HTTP_Request2_ConnectionException extends HTTP_Request2_Exception
{
}

/**
 * Exception thrown when sending or receiving HTTP message fails
 *
 * The exception may contain both package error code and native error code.
 *
 * @category HTTP
 * @package  HTTP_Request2
 * @author   Alexey Borzov <avb@php.net>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 2.1.1
 * @link     http://pear.php.net/package/HTTP_Request2
 */
class HTTP_Request2_MessageException extends HTTP_Request2_Exception
{
}
?>