--TEST--
DB_driver::bug22328
--SKIPIF--
<?php chdir(dirname(__FILE__)); require_once './skipif.inc'; ?>
--FILE--
<?php
require_once './connect.inc';

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
    $dbh->query('DROP TABLE php_limit');

    die($o->toString());
}


$dbh->setErrorHandling(PEAR_ERROR_RETURN);
$dbh->query('DROP TABLE php_limit');

$dbh->setErrorHandling(PEAR_ERROR_CALLBACK, 'pe');

$dbh->query('CREATE TABLE php_limit (a VARCHAR(20))');


$res = $dbh->query('select * from php_limit');
$error = 0;
while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	if (DB::isError($row) && $error) {
		die('bug');
	}
	$res2 = $dbh->query("FAKE QUERY");
	if (!DB::isError($res2)) {
		die('bug');
	}
	$error = true;
}


$dbh->setErrorHandling(PEAR_ERROR_RETURN);
$dbh->query('DROP TABLE php_limit');

?>
--EXPECT--
