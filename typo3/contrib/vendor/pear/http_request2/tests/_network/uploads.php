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

if (isset($_GET['slowpoke'])) {
    sleep(3);
}

if (!empty($_FILES)) {
    foreach ($_FILES as $name => $file) {
        if (is_array($file['name'])) {
            foreach($file['name'] as $k => $v) {
                echo "{$name}[{$k}] {$v} {$file['type'][$k]} {$file['size'][$k]}\n";
            }
        } else {
            echo "{$name} {$file['name']} {$file['type']} {$file['size']}\n";
        }
    }
}
?>