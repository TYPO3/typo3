<?php
/**
 * Helper files for HTTP_Request2 unit tests. Should be accessible via HTTP.
 *
 * PHP version 5
 *
 * LICENSE
 *
 * This source file is subject to BSD 3-Clause License that is bundled
 * with this package in the file LICENSE and available at the URL
 * https://raw.github.com/pear/HTTP_Request2/trunk/docs/LICENSE
 *
 * @category  HTTP
 * @package   HTTP_Request2
 * @author    Alexey Borzov <avb@php.net>
 * @copyright 2008-2014 Alexey Borzov <avb@php.net>
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link      http://pear.php.net/package/HTTP_Request2
 */

/**
 * Mostly borrowed from PHP manual and Socket Adapter implementation
 *
 * @link http://php.net/manual/en/features.http-auth.php
 */

/**
 * Parses the Digest auth header
 *
 * @param string $txt
 */
function http_digest_parse($txt)
{
    $token  = '[^\x00-\x1f\x7f-\xff()<>@,;:\\\\"/\[\]?={}\s]+';
    $quoted = '"(?:\\\\.|[^\\\\"])*"';

    // protect against missing data
    $needed_parts = array_flip(array('nonce', 'nc', 'cnonce', 'qop', 'username', 'uri', 'response'));
    $data         = array();

    preg_match_all("!({$token})\\s*=\\s*({$token}|{$quoted})!", $txt, $matches);
    for ($i = 0; $i < count($matches[0]); $i++) {
        // ignore unneeded parameters
        if (isset($needed_parts[$matches[1][$i]])) {
            unset($needed_parts[$matches[1][$i]]);
            if ('"' == substr($matches[2][$i], 0, 1)) {
                $data[$matches[1][$i]] = substr($matches[2][$i], 1, -1);
            } else {
                $data[$matches[1][$i]] = $matches[2][$i];
            }
        }
    }

    return !empty($needed_parts) ? false : $data;
}

$realm      = 'HTTP_Request2 tests';
$wantedUser = isset($_GET['user']) ? $_GET['user'] : null;
$wantedPass = isset($_GET['pass']) ? $_GET['pass'] : null;
$validAuth  = false;

if (!empty($_SERVER['PHP_AUTH_DIGEST'])
    && ($data = http_digest_parse($_SERVER['PHP_AUTH_DIGEST']))
    && $wantedUser == $data['username']
) {
    // generate the valid response
    $a1       = md5($data['username'] . ':' . $realm . ':' . $wantedPass);
    $a2       = md5($_SERVER['REQUEST_METHOD'] . ':' . $data['uri']);
    $response = md5($a1. ':' . $data['nonce'] . ':' . $data['nc'] . ':'
                    . $data['cnonce'] . ':' . $data['qop'] . ':' . $a2);

    // check valid response against existing one
    $validAuth = ($data['response'] == $response);
}

if (!$validAuth || empty($_SERVER['PHP_AUTH_DIGEST'])) {
    header('WWW-Authenticate: Digest realm="' . $realm .
           '",qop="auth",nonce="' . uniqid() . '"', true, 401);
    echo "Login required";
} else {
    echo "Username={$user}";
}
?>