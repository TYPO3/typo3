--TEST--
DB_Error
--SKIPIF--
<?php if (!@include 'DB.php') print 'skip could not find DB.php'; ?>
--FILE--
<?php // -*- C++ -*-
require_once './include.inc';

// Test for: DB.php
// Parts tested: DB_Error

function test_error_handler($errno, $errmsg, $file, $line, $vars) {
    if (defined('E_STRICT')) {
        if ($errno & E_STRICT
            && (error_reporting() & E_STRICT) != E_STRICT) {
            // Ignore E_STRICT notices unless they have been turned on
            return;
        }
    } else {
        define('E_STRICT', 2048);
    }
    $errortype = array (
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parsing Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Strict Notice',
    );
    $prefix = $errortype[$errno];
    print "\n$prefix: $errmsg in " . basename($file) . " on line XXX\n";
}

error_reporting(E_ALL);
set_error_handler('test_error_handler');
require_once 'DB.php';

print "testing different error codes...\n";
$e = new DB_Error(); print $e->toString()."\n";
$e = new DB_Error("test error"); print $e->toString()."\n";
$e = new DB_Error(DB_OK); print $e->toString()."\n";
$e = new DB_Error(DB_ERROR); print $e->toString()."\n";
$e = new DB_Error(DB_ERROR_SYNTAX); print $e->toString()."\n";
$e = new DB_Error(DB_ERROR_DIVZERO); print $e->toString()."\n";

print "testing different error modes...\n";
$e = new DB_Error(DB_ERROR, PEAR_ERROR_PRINT); print $e->toString()."\n";
$e = new DB_Error(DB_ERROR_SYNTAX, PEAR_ERROR_TRIGGER);

print "testing different error serverities...\n";
$e = new DB_Error(DB_ERROR_SYNTAX, PEAR_ERROR_TRIGGER, E_USER_NOTICE);
$e = new DB_Error(DB_ERROR_SYNTAX, PEAR_ERROR_TRIGGER, E_USER_WARNING);
$e = new DB_Error(DB_ERROR_SYNTAX, PEAR_ERROR_TRIGGER, E_USER_ERROR);

?>
--GET--
--POST--
--EXPECT--
testing different error codes...
[db_error: message="DB Error: unknown error" code=-1 mode=return level=notice prefix="" info=""]
[db_error: message="DB Error: test error" code=-1 mode=return level=notice prefix="" info=""]
[db_error: message="DB Error: no error" code=1 mode=return level=notice prefix="" info=""]
[db_error: message="DB Error: unknown error" code=-1 mode=return level=notice prefix="" info=""]
[db_error: message="DB Error: syntax error" code=-2 mode=return level=notice prefix="" info=""]
[db_error: message="DB Error: division by zero" code=-13 mode=return level=notice prefix="" info=""]
testing different error modes...
DB Error: unknown error[db_error: message="DB Error: unknown error" code=-1 mode=print level=notice prefix="" info=""]

User Notice: DB Error: syntax error in PEAR.php on line XXX
testing different error serverities...

User Notice: DB Error: syntax error in PEAR.php on line XXX

User Warning: DB Error: syntax error in PEAR.php on line XXX

User Error: DB Error: syntax error in PEAR.php on line XXX
