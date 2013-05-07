<?php
/**
 * Socket wrapper class used by Socket Adapter
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
 * @version  SVN: $Id: SocketWrapper.php 324935 2012-04-07 07:10:50Z avb $
 * @link     http://pear.php.net/package/HTTP_Request2
 */

/** Exception classes for HTTP_Request2 package */
require_once 'HTTP/Request2/Exception.php';

/**
 * Socket wrapper class used by Socket Adapter
 *
 * Needed to properly handle connection errors, global timeout support and
 * similar things. Loosely based on Net_Socket used by older HTTP_Request.
 *
 * @category HTTP
 * @package  HTTP_Request2
 * @author   Alexey Borzov <avb@php.net>
 * @license  http://opensource.org/licenses/bsd-license.php New BSD License
 * @version  Release: 2.1.1
 * @link     http://pear.php.net/package/HTTP_Request2
 * @link     http://pear.php.net/bugs/bug.php?id=19332
 * @link     http://tools.ietf.org/html/rfc1928
 */
class HTTP_Request2_SocketWrapper
{
    /**
     * PHP warning messages raised during stream_socket_client() call
     * @var array
     */
    protected $connectionWarnings = array();

    /**
     * Connected socket
     * @var resource
     */
    protected $socket;

    /**
     * Sum of start time and global timeout, exception will be thrown if request continues past this time
     * @var  integer
     */
    protected $deadline;

    /**
     * Global timeout value, mostly for exception messages
     * @var integer
     */
    protected $timeout;

    /**
     * Class constructor, tries to establish connection
     *
     * @param string $address    Address for stream_socket_client() call,
     *                           e.g. 'tcp://localhost:80'
     * @param int    $timeout    Connection timeout (seconds)
     * @param array  $sslOptions SSL context options
     *
     * @throws HTTP_Request2_LogicException
     * @throws HTTP_Request2_ConnectionException
     */
    public function __construct($address, $timeout, array $sslOptions = array())
    {
        $context = stream_context_create();
        foreach ($sslOptions as $name => $value) {
            if (!stream_context_set_option($context, 'ssl', $name, $value)) {
                throw new HTTP_Request2_LogicException(
                    "Error setting SSL context option '{$name}'"
                );
            }
        }
        set_error_handler(array($this, 'connectionWarningsHandler'));
        $this->socket = stream_socket_client(
            $address, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context
        );
        restore_error_handler();
        if (!$this->socket) {
            $error = $errstr ? $errstr : implode("\n", $this->connectionWarnings);
            throw new HTTP_Request2_ConnectionException(
                "Unable to connect to {$address}. Error: {$error}", 0, $errno
            );
        }
    }

    /**
     * Destructor, disconnects socket
     */
    public function __destruct()
    {
        fclose($this->socket);
    }

    /**
     * Wrapper around fread(), handles global request timeout
     *
     * @param int $length Reads up to this number of bytes
     *
     * @return   string Data read from socket
     * @throws   HTTP_Request2_MessageException     In case of timeout
     */
    public function read($length)
    {
        if ($this->deadline) {
            stream_set_timeout($this->socket, max($this->deadline - time(), 1));
        }
        $data = fread($this->socket, $length);
        $this->checkTimeout();
        return $data;
    }

    /**
     * Reads until either the end of the socket or a newline, whichever comes first
     *
     * Strips the trailing newline from the returned data, handles global
     * request timeout. Method idea borrowed from Net_Socket PEAR package.
     *
     * @param int $bufferSize buffer size to use for reading
     *
     * @return   string Available data up to the newline (not including newline)
     * @throws   HTTP_Request2_MessageException     In case of timeout
     */
    public function readLine($bufferSize)
    {
        $line = '';
        while (!feof($this->socket)) {
            if ($this->deadline) {
                stream_set_timeout($this->socket, max($this->deadline - time(), 1));
            }
            $line .= @fgets($this->socket, $bufferSize);
            $this->checkTimeout();
            if (substr($line, -1) == "\n") {
                return rtrim($line, "\r\n");
            }
        }
        return $line;
    }

    /**
     * Wrapper around fwrite(), handles global request timeout
     *
     * @param string $data String to be written
     *
     * @return int
     * @throws HTTP_Request2_MessageException
     */
    public function write($data)
    {
        if ($this->deadline) {
            stream_set_timeout($this->socket, max($this->deadline - time(), 1));
        }
        $written = fwrite($this->socket, $data);
        $this->checkTimeout();
        // http://www.php.net/manual/en/function.fwrite.php#96951
        if ($written < strlen($data)) {
            throw new HTTP_Request2_MessageException('Error writing request');
        }
        return $written;
    }

    /**
     * Tests for end-of-file on a socket
     *
     * @return bool
     */
    public function eof()
    {
        return feof($this->socket);
    }

    /**
     * Sets request deadline
     *
     * @param int $deadline Exception will be thrown if request continues
     *                      past this time
     * @param int $timeout  Original request timeout value, to use in
     *                      Exception message
     */
    public function setDeadline($deadline, $timeout)
    {
        $this->deadline = $deadline;
        $this->timeout  = $timeout;
    }

    /**
     * Turns on encryption on a socket
     *
     * @throws HTTP_Request2_ConnectionException
     */
    public function enableCrypto()
    {
        $modes = array(
            STREAM_CRYPTO_METHOD_TLS_CLIENT,
            STREAM_CRYPTO_METHOD_SSLv3_CLIENT,
            STREAM_CRYPTO_METHOD_SSLv23_CLIENT,
            STREAM_CRYPTO_METHOD_SSLv2_CLIENT
        );

        foreach ($modes as $mode) {
            if (stream_socket_enable_crypto($this->socket, true, $mode)) {
                return;
            }
        }
        throw new HTTP_Request2_ConnectionException(
            'Failed to enable secure connection when connecting through proxy'
        );
    }

    /**
     * Throws an Exception if stream timed out
     *
     * @throws HTTP_Request2_MessageException
     */
    protected function checkTimeout()
    {
        $info = stream_get_meta_data($this->socket);
        if ($info['timed_out'] || $this->deadline && time() > $this->deadline) {
            $reason = $this->deadline
                ? "after {$this->timeout} second(s)"
                : 'due to default_socket_timeout php.ini setting';
            throw new HTTP_Request2_MessageException(
                "Request timed out {$reason}", HTTP_Request2_Exception::TIMEOUT
            );
        }
    }

    /**
     * Error handler to use during stream_socket_client() call
     *
     * One stream_socket_client() call may produce *multiple* PHP warnings
     * (especially OpenSSL-related), we keep them in an array to later use for
     * the message of HTTP_Request2_ConnectionException
     *
     * @param int    $errno  error level
     * @param string $errstr error message
     *
     * @return bool
     */
    protected function connectionWarningsHandler($errno, $errstr)
    {
        if ($errno & E_WARNING) {
            array_unshift($this->connectionWarnings, $errstr);
        }
        return true;
    }
}
?>
