<?php

error_reporting(E_ALL);

if (!defined('PATH_SEPARATOR')) {
    if (stristr(PHP_OS, 'WIN')) {
        define('PATH_SEPARATOR', ';');
    } else {
        define('PATH_SEPARATOR', ':');
    }
}
ini_set('include_path', realpath(dirname(__FILE__) . '/..')
        . PATH_SEPARATOR . '.' . PATH_SEPARATOR
        . ini_get('include_path'));

?>
