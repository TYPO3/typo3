--TEST--
DB_driver::connect
--SKIPIF--
<?php chdir(dirname(__FILE__)); require_once './skipif.inc'; ?>
--FILE--
<?php
require_once './connect.inc';
if (is_object($dbh)) {
    print "\$dbh is an object\n";
}
if (is_resource($dbh->connection)) {
    print "\$dbh is connected\n";
}


$dbha =& DB::connect($test_array_dsn, $options);
if (DB::isError($dbha)) {
    die("connect.inc: ".$dbha->toString());
}

if (is_object($dbha)) {
    print "\$dbha is an object\n";
}

if (is_resource($dbha->connection)) {
    print "\$dbha is connected\n";
}

?>
--EXPECT--
$dbh is an object
$dbh is connected
$dbha is an object
$dbha is connected
