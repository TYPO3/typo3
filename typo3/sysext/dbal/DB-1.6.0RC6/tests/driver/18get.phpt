--TEST--
DB_driver::get
--SKIPIF--
<?php

/**
 * Calls the get*() methods in various ways against any DBMS.
 *
 * @see DB_Common::getAll(), DB_Common::getAssoc(), DB_Common::getCol()
 *      DB_Common::getListOf(), DB_Common::getOne(), DB_Common::getRow()
 *
 * @package  DB
 * @version  $Id$
 * @category Database
 * @author   Daniel Convissor <danielc@analysisandsolutions.com>
 * @internal
 */

chdir(dirname(__FILE__));
require_once './driver/skipif.inc';

?>
--FILE--
<?php

// $Id$

/**
 * Connect to the database and make the <kbd>phptest</kbd> table.
 */
require_once './mktable.inc';


/**
 * Local error callback handler.
 *
 * Drops the phptest table, prints out an error message and kills the
 * process.
 *
 * @param object  $o  PEAR error object automatically passed to this method
 * @return void
 * @see PEAR::setErrorHandling()
 */
function pe($o){
    global $dbh;

    $dbh->setErrorHandling(PEAR_ERROR_RETURN);
    $dbh->query('DROP TABLE phptest');

    die($o->toString());
}

$dbh->setErrorHandling(PEAR_ERROR_CALLBACK, 'pe');


$dbh->query("INSERT INTO phptest VALUES (2, 'two', 'Two', '2002-02-22')");
$dbh->query("INSERT INTO phptest VALUES (42, 'three', 'Three', '2003-03-23')");


print "===================================================\n";
print 'testing getOne: ';
$ret =& $dbh->getOne("SELECT * FROM phptest WHERE c = 'Two'");
print_r($ret);
print "\n";

print 'testing getOne with string params: ';
$ret =& $dbh->getOne('SELECT * FROM phptest WHERE c = ?', 'Three');
print_r($ret);
print "\n";

print 'testing getOne with array params: ';
$ret =& $dbh->getOne('SELECT * FROM phptest WHERE c = ?', array('Two'));
print_r($ret);
print "\n";

print "\n===================================================\n";
print "testing getRow:\n";
$ret =& $dbh->getRow("SELECT * FROM phptest WHERE c = 'Two'");
print_r($ret);

print "testing getRow with null params, DB_FETCHMODE_ORDERED:\n";
$ret =& $dbh->getRow("SELECT * FROM phptest WHERE c = 'Two'",
        null, DB_FETCHMODE_ORDERED);
print_r($ret);

// THIS DOESN'T WORK DUE TO BACKWARDS COMPATIBILITY CRAP
// print "testing getRow with string params, DB_FETCHMODE_ORDERED:\n";
// $ret =& $dbh->getRow('SELECT * FROM phptest WHERE c = ?',
//         'Two', DB_FETCHMODE_ORDERED);
// print_r($ret);
//
// testing getRow with string params, DB_FETCHMODE_ORDERED:
// Array
// (
//     [0] => 2
//     [1] => two
//     [2] => Two
//     [3] => 2002-02-22
// )

   print "testing getRow with REVERSED args: DB_FETCHMODE_ASSOC, array params:\n";
   $ret =& $dbh->getRow('SELECT * FROM phptest WHERE c = ?',
           DB_FETCHMODE_ASSOC, array('Two'));
   print_r($ret);
   
   print "testing getRow with REVERSED args: DB_FETCHMODE_ASSOC:\n";
   $ret =& $dbh->getRow("SELECT * FROM phptest WHERE c = 'Two'",
           DB_FETCHMODE_ASSOC);
   print_r($ret);

print "testing getRow with array params, DB_FETCHMODE_ASSOC:\n";
$ret =& $dbh->getRow('SELECT * FROM phptest WHERE c = ?',
        array('Two'), DB_FETCHMODE_ASSOC);
print_r($ret);

print "testing getRow with array params, DB_FETCHMODE_OBJECT:\n";
$ret =& $dbh->getRow('SELECT * FROM phptest WHERE c = ?',
        array('Two'), DB_FETCHMODE_OBJECT);
print_r($ret);


print "\n===================================================\n";
print "testing getCol:\n";
$ret =& $dbh->getCol("SELECT * FROM phptest ORDER BY b");
print_r($ret);

print "testing getCol with 1 col:\n";
$ret =& $dbh->getCol("SELECT * FROM phptest ORDER BY b", 1);
print_r($ret);

print "testing getCol with b col:\n";
$ret =& $dbh->getCol("SELECT * FROM phptest ORDER BY b", 'b');
print_r($ret);

print "testing getCol with b col, scalar params:\n";
$ret =& $dbh->getCol("SELECT * FROM phptest WHERE a < ? ORDER BY b",
        'b', 100);
print_r($ret);

print "testing getCol with b col, array params:\n";
$ret =& $dbh->getCol("SELECT * FROM phptest WHERE a < ? ORDER BY b",
        'b', array(100));
print_r($ret);


print "\n===================================================\n";
print "testing getAssoc:\n";
$ret =& $dbh->getAssoc('SELECT a, b, c FROM phptest WHERE a < 100 ORDER BY b');
print_r($ret);

print "testing getAssoc with false force, null params, DB_FETCHMODE_ORDERED:\n";
$ret =& $dbh->getAssoc("SELECT a, b, c FROM phptest WHERE a < 100 ORDER BY b",
                        false, null, DB_FETCHMODE_ORDERED);
print_r($ret);

print "testing getAssoc with false force, scalar params, DB_FETCHMODE_ASSOC:\n";
$ret =& $dbh->getAssoc('SELECT a, b, c FROM phptest WHERE a < ? ORDER BY b',
                        false, 100, DB_FETCHMODE_ASSOC);
print_r($ret);

print "testing getAssoc with two cols, false force, scalar params, DB_FETCHMODE_ASSOC:\n";
$ret =& $dbh->getAssoc('SELECT a, b FROM phptest WHERE a < ? ORDER BY b',
                        false, 100, DB_FETCHMODE_ASSOC);
print_r($ret);

print "testing getAssoc with two cols, true force, scalar params, DB_FETCHMODE_ASSOC:\n";
$ret =& $dbh->getAssoc('SELECT a, b FROM phptest WHERE a < ? ORDER BY b',
                        true, 100, DB_FETCHMODE_ASSOC);
print_r($ret);

print "testing getAssoc with false force, scalar params, DB_FETCHMODE_ASSOC, true group:\n";
$ret =& $dbh->getAssoc('SELECT a, b, c FROM phptest WHERE a < ? ORDER BY b',
                        false, 100, DB_FETCHMODE_ASSOC, true);
print_r($ret);

print "testing getAssoc with false force, array params, DB_FETCHMODE_OBJECT:\n";
$ret =& $dbh->getAssoc('SELECT a, b, c FROM phptest WHERE a < ? ORDER BY b',
                        false, array(100), DB_FETCHMODE_OBJECT);
print_r($ret);

print "testing getAssoc with true force, array params, DB_FETCHMODE_OBJECT, true group:\n";
$ret =& $dbh->getAssoc('SELECT a, b, c FROM phptest WHERE a < ? ORDER BY b',
                        false, array(100), DB_FETCHMODE_OBJECT, true);
print_r($ret);


print "\n===================================================\n";
print "testing getAll:\n";
$ret =& $dbh->getAll("SELECT * FROM phptest WHERE c = 'Two' OR c = 'Three'");
print_r($ret);

print "testing getAll with null params, DB_FETCHMODE_ORDERED:\n";
$ret =& $dbh->getAll("SELECT * FROM phptest WHERE c = 'Two' OR c = 'Three'",
        null, DB_FETCHMODE_ORDERED);
print_r($ret);

// THIS DOESN'T WORK DUE TO BACKWARDS COMPATIBILITY CRAP
// print "testing getAll with string params, DB_FETCHMODE_ORDERED:\n";
// $ret =& $dbh->getAll('SELECT * FROM phptest WHERE c = ?',
//         'Two', DB_FETCHMODE_ORDERED);
// print_r($ret);
//
// testing getAll with string params, DB_FETCHMODE_ORDERED:
// Array
// (
//     [0] => 2
//     [1] => two
//     [2] => Two
//     [3] => 2002-02-22
// )

   print "testing getAll with REVERSED args: DB_FETCHMODE_ASSOC, array params:\n";
   $ret =& $dbh->getAll('SELECT * FROM phptest WHERE c = ? OR c = ? ORDER BY c',
           DB_FETCHMODE_ASSOC, array('Two', 'Three'));
   print_r($ret);
   
   print "testing getAll with REVERSED args: DB_FETCHMODE_ASSOC:\n";
   $ret =& $dbh->getAll("SELECT * FROM phptest WHERE c = 'Two' OR c = 'Three'",
           DB_FETCHMODE_ASSOC);
   print_r($ret);

print "testing getAll with array params, DB_FETCHMODE_ASSOC:\n";
$ret =& $dbh->getAll('SELECT * FROM phptest WHERE c = ? OR c = ? ORDER BY c',
        array('Two', 'Three'), DB_FETCHMODE_ASSOC);
print_r($ret);

print "testing getAll with array params, DB_FETCHMODE_OBJECT:\n";
$ret =& $dbh->getAll('SELECT * FROM phptest WHERE c = ? OR c = ? ORDER BY c',
        array('Two', 'Three'), DB_FETCHMODE_OBJECT);
print_r($ret);


print "\n===================================================\n";


$dbh->setErrorHandling(PEAR_ERROR_RETURN);
$dbh->query('DROP TABLE phptest');

?>
--EXPECT--
===================================================
testing getOne: 2
testing getOne with string params: 42
testing getOne with array params: 2

===================================================
testing getRow:
Array
(
    [0] => 2
    [1] => two
    [2] => Two
    [3] => 2002-02-22
)
testing getRow with null params, DB_FETCHMODE_ORDERED:
Array
(
    [0] => 2
    [1] => two
    [2] => Two
    [3] => 2002-02-22
)
testing getRow with REVERSED args: DB_FETCHMODE_ASSOC, array params:
Array
(
    [a] => 2
    [b] => two
    [c] => Two
    [d] => 2002-02-22
)
testing getRow with REVERSED args: DB_FETCHMODE_ASSOC:
Array
(
    [a] => 2
    [b] => two
    [c] => Two
    [d] => 2002-02-22
)
testing getRow with array params, DB_FETCHMODE_ASSOC:
Array
(
    [a] => 2
    [b] => two
    [c] => Two
    [d] => 2002-02-22
)
testing getRow with array params, DB_FETCHMODE_OBJECT:
stdClass Object
(
    [a] => 2
    [b] => two
    [c] => Two
    [d] => 2002-02-22
)

===================================================
testing getCol:
Array
(
    [0] => 42
    [1] => 42
    [2] => 2
)
testing getCol with 1 col:
Array
(
    [0] => bing
    [1] => three
    [2] => two
)
testing getCol with b col:
Array
(
    [0] => bing
    [1] => three
    [2] => two
)
testing getCol with b col, scalar params:
Array
(
    [0] => bing
    [1] => three
    [2] => two
)
testing getCol with b col, array params:
Array
(
    [0] => bing
    [1] => three
    [2] => two
)

===================================================
testing getAssoc:
Array
(
    [42] => Array
        (
            [0] => three
            [1] => Three
        )

    [2] => Array
        (
            [0] => two
            [1] => Two
        )

)
testing getAssoc with false force, null params, DB_FETCHMODE_ORDERED:
Array
(
    [42] => Array
        (
            [0] => three
            [1] => Three
        )

    [2] => Array
        (
            [0] => two
            [1] => Two
        )

)
testing getAssoc with false force, scalar params, DB_FETCHMODE_ASSOC:
Array
(
    [42] => Array
        (
            [b] => three
            [c] => Three
        )

    [2] => Array
        (
            [b] => two
            [c] => Two
        )

)
testing getAssoc with two cols, false force, scalar params, DB_FETCHMODE_ASSOC:
Array
(
    [42] => three
    [2] => two
)
testing getAssoc with two cols, true force, scalar params, DB_FETCHMODE_ASSOC:
Array
(
    [42] => Array
        (
            [b] => three
        )

    [2] => Array
        (
            [b] => two
        )

)
testing getAssoc with false force, scalar params, DB_FETCHMODE_ASSOC, true group:
Array
(
    [42] => Array
        (
            [0] => Array
                (
                    [b] => bing
                    [c] => This is a test
                )

            [1] => Array
                (
                    [b] => three
                    [c] => Three
                )

        )

    [2] => Array
        (
            [0] => Array
                (
                    [b] => two
                    [c] => Two
                )

        )

)
testing getAssoc with false force, array params, DB_FETCHMODE_OBJECT:
Array
(
    [42] => stdClass Object
        (
            [a] => 42
            [b] => three
            [c] => Three
        )

    [2] => stdClass Object
        (
            [a] => 2
            [b] => two
            [c] => Two
        )

)
testing getAssoc with true force, array params, DB_FETCHMODE_OBJECT, true group:
Array
(
    [42] => Array
        (
            [0] => stdClass Object
                (
                    [a] => 42
                    [b] => bing
                    [c] => This is a test
                )

            [1] => stdClass Object
                (
                    [a] => 42
                    [b] => three
                    [c] => Three
                )

        )

    [2] => Array
        (
            [0] => stdClass Object
                (
                    [a] => 2
                    [b] => two
                    [c] => Two
                )

        )

)

===================================================
testing getAll:
Array
(
    [0] => Array
        (
            [0] => 2
            [1] => two
            [2] => Two
            [3] => 2002-02-22
        )

    [1] => Array
        (
            [0] => 42
            [1] => three
            [2] => Three
            [3] => 2003-03-23
        )

)
testing getAll with null params, DB_FETCHMODE_ORDERED:
Array
(
    [0] => Array
        (
            [0] => 2
            [1] => two
            [2] => Two
            [3] => 2002-02-22
        )

    [1] => Array
        (
            [0] => 42
            [1] => three
            [2] => Three
            [3] => 2003-03-23
        )

)
testing getAll with REVERSED args: DB_FETCHMODE_ASSOC, array params:
Array
(
    [0] => Array
        (
            [a] => 42
            [b] => three
            [c] => Three
            [d] => 2003-03-23
        )

    [1] => Array
        (
            [a] => 2
            [b] => two
            [c] => Two
            [d] => 2002-02-22
        )

)
testing getAll with REVERSED args: DB_FETCHMODE_ASSOC:
Array
(
    [0] => Array
        (
            [a] => 2
            [b] => two
            [c] => Two
            [d] => 2002-02-22
        )

    [1] => Array
        (
            [a] => 42
            [b] => three
            [c] => Three
            [d] => 2003-03-23
        )

)
testing getAll with array params, DB_FETCHMODE_ASSOC:
Array
(
    [0] => Array
        (
            [a] => 42
            [b] => three
            [c] => Three
            [d] => 2003-03-23
        )

    [1] => Array
        (
            [a] => 2
            [b] => two
            [c] => Two
            [d] => 2002-02-22
        )

)
testing getAll with array params, DB_FETCHMODE_OBJECT:
Array
(
    [0] => stdClass Object
        (
            [a] => 42
            [b] => three
            [c] => Three
            [d] => 2003-03-23
        )

    [1] => stdClass Object
        (
            [a] => 2
            [b] => two
            [c] => Two
            [d] => 2002-02-22
        )

)

===================================================
