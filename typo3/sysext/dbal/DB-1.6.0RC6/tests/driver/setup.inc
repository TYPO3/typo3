<?php

if (!defined('PATH_SEPARATOR')) {
    if (stristr(PHP_OS, 'WIN')) {
        define('PATH_SEPARATOR', ';');
    } else {
        define('PATH_SEPARATOR', ':');
    }
}

ini_set('include_path', realpath(dirname(__FILE__) . '/../..')
        . PATH_SEPARATOR . '.' . PATH_SEPARATOR
        . ini_get('include_path')
);

require_once 'DB.php';

// Options used when connecting
$options = array(
    //'optimize' => 'portability',
    'portability' => DB_PORTABILITY_ALL,
    'debug' => 2,
);

define('DRIVER_DSN', '');
//define('DRIVER_DSN', 'pgsql://postgres@localhost/test');
//define('DRIVER_DSN', 'mysql://root@localhost/test');
//define('DRIVER_DSN', 'oci8://system:manager@');
//define('DRIVER_DSN', 'ibase(firebird)://SYSDBA:masterkey@//opt/interbase/examples/employee.gdb?dialect=3');
//define('DRIVER_DSN', 'mssql://sa@somehost/pubs');
//define('DRIVER_DSN', 'odbc(db2)://db2inst1:XXXX@/SAMPLE');
//define('DRIVER_DSN', 'sqlite://dummy:@localhost/' . getcwd() . DIRECTORY_SEPARATOR . 'test.db?mode=0644');

/*
 * This test array is used only for one test in 01connect.phpt.
 * ALL tests require setting the DRIVER_DSN constant.
 */
$test_array_dsn = array(
    'phptype'  => 'mysql',
    'username' => 'root',
    'password' => 'pass',
    'database' => 'test',
);

?>
