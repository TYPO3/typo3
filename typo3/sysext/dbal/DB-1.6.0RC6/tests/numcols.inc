<?php

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


$sth = $dbh->query("SELECT a FROM phptest");
printf("%d\n", $sth->numCols());
$sth = $dbh->query("SELECT a,b FROM phptest");
printf("%d\n", $sth->numCols());
$sth = $dbh->query("SELECT a,b,c FROM phptest");
printf("%d\n", $sth->numCols());
$sth = $dbh->query("SELECT * FROM phptest");
printf("%d\n", $sth->numCols());


switch ($dbh->phptype) {
    case 'ibase':
        /*
         * Interbase doesn't allow dropping tables that have result
         * sets still open.
         */
        $dbh->freeResult($sth->result);
        break;
}
$dbh->setErrorHandling(PEAR_ERROR_RETURN);
$dbh->query('DROP TABLE phptest');

?>
