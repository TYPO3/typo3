--TEST--
DB_driver::fetch
--SKIPIF--
<?php chdir(dirname(__FILE__)); require_once './skipif.inc'; ?>
--FILE--
<?php
require_once './mktable.inc';
require_once '../fetchmodes.inc';
?>
--EXPECT--
testing fetchrow:
row 1: 42, bing, This is a test, 1999-11-21
row 2: 1, one, One, 2001-02-16
row 3: 2, two, Two, 2001-02-15
row 4: 3, three, Three, 2001-02-14
row 5: NULL
testing fetchmodes: fetchrow default default, portability mode DB_PORTABILITY_ALL ^ DB_PORTABILITY_RTRIM
0 1 2 3
output matched expected format
testing fetchmodes: fetchinto default default
0 1 2 3
42 bing This is a test 1999-11-21
testing fetchmodes: fetchrow ordered default
0 1 2 3
testing fetchmodes: fetchrow assoc default
a b c d
testing fetchmodes: fetchrow ordered default with assoc specified
a b c d
testing fetchmodes: fetchrow assoc default with ordered specified
0 1 2 3
testing fetchmodes: fetchinto ordered default
0 1 2 3
testing fetchmodes: fetchinto assoc default
a b c d
testing fetchmodes: fetchinto ordered default with assoc specified
a b c d
testing fetchmodes: fetchinto assoc default with ordered specified
0 1 2 3
