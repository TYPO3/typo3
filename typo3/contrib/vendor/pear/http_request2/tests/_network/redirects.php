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

$redirects = isset($_GET['redirects'])? $_GET['redirects']: 1;
$https     = !empty($_SERVER['HTTPS']) && ('off' != strtolower($_SERVER['HTTPS']));
$special   = isset($_GET['special'])? $_GET['special']: null;

if ('ftp' == $special) {
    header('Location: ftp://localhost/pub/exploit.exe', true, 301);

} elseif ('relative' == $special) {
    header('Location: ./getparameters.php?msg=did%20relative%20redirect', true, 302);

} elseif ('cookie' == $special) {
    setcookie('cookie_on_redirect', 'success');
    header('Location: ./cookies.php', true, 302);

} elseif ($redirects > 0) {
    $url = ($https? 'https': 'http') . '://' . $_SERVER['SERVER_NAME']
           . (($https && 443 == $_SERVER['SERVER_PORT'] || !$https && 80 == $_SERVER['SERVER_PORT'])
              ? '' : ':' . $_SERVER['SERVER_PORT'])
           . $_SERVER['PHP_SELF'] . '?redirects=' . (--$redirects);
    header('Location: ' . $url, true, 302);

} else {
    echo "Method=" . $_SERVER['REQUEST_METHOD'] . ';';
    var_dump($_POST);
    var_dump($_GET);
}
?>