--TEST--
DB_driver::prepare/execute
--SKIPIF--
<?php chdir(dirname(__FILE__)); require_once './skipif.inc'; ?>
--FILE--
<?php
require_once './mktable.inc';
require_once '../prepexe.inc';
?>
--EXPECT--
------------1------------
sth1,sth2,sth3,sth4 created
sth1: ? as param, passing as array... sth1 executed
sth2: ! and ? as params, passing as array... sth2 executed
sth3: ?, ! and & as params, passing as array... sth3 executed
sth4: no params... sth4 executed
results:
|72 - a -  - |
|72 - direct -  - |
|72 - it's good - opaque
placeholder's
test - |
|72 - that's right -  - |

------------2------------
results:
|72 - set1 - opaque
placeholder's
test - 1234-56-78|
|72 - set2 - opaque
placeholder's
test - |
|72 - set3 - opaque
placeholder's
test - |

------------3------------
TRUE
FALSE

------------4------------
|72 - set1 - opaque
placeholder's
test - 1234-56-78|
|72 - set2 - opaque
placeholder's
test - |
|72 - set3 - opaque
placeholder's
test - |
~~
~~
|72 - set1 - opaque
placeholder's
test - 1234-56-78|
~~
|72 - set1 - opaque
placeholder's
test - 1234-56-78|
|72 - set2 - opaque
placeholder's
test - |
|72 - set3 - opaque
placeholder's
test - |
~~

------------5------------
insert: okay
a = 11, b = three, d = NULL
