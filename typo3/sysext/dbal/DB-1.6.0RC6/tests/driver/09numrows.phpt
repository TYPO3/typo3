--TEST--
DB_driver::numRows
--SKIPIF--
<?php chdir(dirname(__FILE__)); require_once './skipif.inc'; ?>
--FILE--
<?php
require_once './mktable.inc';
require_once '../numrows.inc';
?>
--EXPECT--
1
2
3
4
5
6
2
0
