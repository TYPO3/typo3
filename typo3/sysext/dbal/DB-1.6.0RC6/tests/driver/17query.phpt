--TEST--
DB_driver::query
--SKIPIF--
<?php

/**
 * Calls the query() method in various ways against any DBMS.
 *
 * @see      DB_common::query()
 * 
 * @package  DB
 * @version  $Id$
 * @category Database
 * @author   Daniel Convissor <danielc@analysisandsolutions.com>
 * @internal
 */

chdir(dirname(__FILE__));
require_once './skipif.inc';

?>
--FILE--
<?php

// $Id$

/**
 * Connect to the database and make the phptest table.
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
function pe($o) {
    global $dbh;

    $dbh->setErrorHandling(PEAR_ERROR_RETURN);
    $dbh->query('DROP TABLE phptest');

    die($o->toString());
}

$dbh->setErrorHandling(PEAR_ERROR_CALLBACK, 'pe');


$dbh->setFetchMode(DB_FETCHMODE_ASSOC);


$res =& $dbh->query('DELETE FROM phptest WHERE a = 17');
print 'delete: ' . ($res == DB_OK ? 'okay' : 'error') . "\n";

$res =& $dbh->query("INSERT INTO phptest (a, b, c) VALUES (17, 'one', 'One')");
print 'insert: ' . ($res == DB_OK ? 'okay' : 'error') . "\n";

$res =& $dbh->query('INSERT INTO phptest (a, b, c) VALUES (?, ?, ?)', array(17, 'two', 'Two'));
print 'insert: ' . ($res == DB_OK ? 'okay' : 'error') . "\n";


$res =& $dbh->query('SELECT a, b FROM phptest WHERE a = 17');
$row = $res->fetchRow();
print "a = {$row['a']}, b = {$row['b']}\n";

$res =& $dbh->query('SELECT a, b FROM phptest WHERE c = ?', array('Two'));
$row = $res->fetchRow();
print "a = {$row['a']}, b = {$row['b']}\n";


$array = array(
    'foo' => 11,
    'bar' => 'three',
    'baz' => null,
);
$res =& $dbh->query('INSERT INTO phptest (a, b, d) VALUES (?, ?, ?)', $array);
print 'insert: ' . ($res == DB_OK ? 'okay' : 'error') . "\n";

$res =& $dbh->query('SELECT a, b, d FROM phptest WHERE a = ?', 11);
$row = $res->fetchRow();
print "a = {$row['a']}, b = {$row['b']}, d = " . gettype($row['d']) . "\n";


$res =& $dbh->query('DELETE FROM phptest WHERE a = ?', array(17));
print 'delete: ' . ($res == DB_OK ? 'okay' : 'error') . "\n";


$dbh->setErrorHandling(PEAR_ERROR_RETURN);
$dbh->query('DROP TABLE phptest');

?>
--EXPECT--
delete: okay
insert: okay
insert: okay
a = 17, b = one
a = 17, b = two
insert: okay
a = 11, b = three, d = NULL
delete: okay
