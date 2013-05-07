<?php
/**
 * SOCKS5 proxy connection class
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
 * @version  SVN: $Id: SOCKS5.php 324953 2012-04-08 07:24:12Z avb $
 * @link     http://pear.php.net/package/HTTP_Request2
 */

/** Socket wrapper class used by Socket Adapter */
require_once 'HTTP/Request2/SocketWrapper.php';

/**
 * SOCKS5 proxy connection class (used by Socket Adapter)
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
class HTTP_Request2_SOCKS5 extends HTTP_Request2_SocketWrapper
{
    /**
     * Constructor, tries to connect and authenticate to a SOCKS5 proxy
     *
     * @param string $address    Proxy address, e.g. 'tcp://localhost:1080'
     * @param int    $timeout    Connection timeout (seconds)
     * @param array  $sslOptions SSL context options
     * @param string $username   Proxy user name
     * @param string $password   Proxy password
     *
     * @throws HTTP_Request2_LogicException
     * @throws HTTP_Request2_ConnectionException
     * @throws HTTP_Request2_MessageException
     */
    public function __construct(
        $address, $timeout = 10, array $sslOptions = array(),
        $username = null, $password = null
    ) {
        parent::__construct($address, $timeout, $sslOptions);

        if (strlen($username)) {
            $request = pack('C4', 5, 2, 0, 2);
        } else {
            $request = pack('C3', 5, 1, 0);
        }
        $this->write($request);
        $response = unpack('Cversion/Cmethod', $this->read(3));
        if (5 != $response['version']) {
            throw new HTTP_Request2_MessageException(
                'Invalid version received from SOCKS5 proxy: ' . $response['version'],
                HTTP_Request2_Exception::MALFORMED_RESPONSE
            );
        }
        switch ($response['method']) {
        case 2:
            $this->performAuthentication($username, $password);
        case 0:
            break;
        default:
            throw new HTTP_Request2_ConnectionException(
                "Connection rejected by proxy due to unsupported auth method"
            );
        }
    }

    /**
     * Performs username/password authentication for SOCKS5
     *
     * @param string $username Proxy user name
     * @param string $password Proxy password
     *
     * @throws HTTP_Request2_ConnectionException
     * @throws HTTP_Request2_MessageException
     * @link http://tools.ietf.org/html/rfc1929
     */
    protected function performAuthentication($username, $password)
    {
        $request  = pack('C2', 1, strlen($username)) . $username
                    . pack('C', strlen($password)) . $password;

        $this->write($request);
        $response = unpack('Cvn/Cstatus', $this->read(3));
        if (1 != $response['vn'] || 0 != $response['status']) {
            throw new HTTP_Request2_ConnectionException(
                'Connection rejected by proxy due to invalid username and/or password'
            );
        }
    }

    /**
     * Connects to a remote host via proxy
     *
     * @param string $remoteHost Remote host
     * @param int    $remotePort Remote port
     *
     * @throws HTTP_Request2_ConnectionException
     * @throws HTTP_Request2_MessageException
     */
    public function connect($remoteHost, $remotePort)
    {
        $request = pack('C5', 0x05, 0x01, 0x00, 0x03, strlen($remoteHost))
                   . $remoteHost . pack('n', $remotePort);

        $this->write($request);
        $response = unpack('Cversion/Creply/Creserved', $this->read(1024));
        if (5 != $response['version'] || 0 != $response['reserved']) {
            throw new HTTP_Request2_MessageException(
                'Invalid response received from SOCKS5 proxy',
                HTTP_Request2_Exception::MALFORMED_RESPONSE
            );
        } elseif (0 != $response['reply']) {
            throw new HTTP_Request2_ConnectionException(
                "Unable to connect to {$remoteHost}:{$remotePort} through SOCKS5 proxy",
                0, $response['reply']
            );
        }
    }
}
?>