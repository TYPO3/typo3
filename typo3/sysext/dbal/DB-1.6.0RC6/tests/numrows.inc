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
switch ($dbh->phptype) {
    case 'ibase':
        /*
         * Interbase doesn't allow manipulating tables that have result
         * sets still open.
         */
        $dbh->freeResult($sth->result);
        break;
}
if (!DB::isError($rows = $sth->numRows())) {
    print "$rows\n";
} else {
    print "\n";
}
for ($i = 0; $i < 5; $i++) {
    $sth = $dbh->query("INSERT INTO phptest (a) VALUES ($i)");
    $sth = $dbh->query("SELECT a FROM phptest");
    if (!DB::isError($rows = $sth->numRows())) {
        print "$rows\n";
    } else {
        print "\n";
    }
}

$dbh->query("DELETE FROM phptest WHERE a < 4");
$sth = $dbh->query("SELECT a FROM phptest");
if (!DB::isError($rows = $sth->numRows())) {
    print "$rows\n";
} else {
    print "\n";
}
$sth = $dbh->query("SELECT a FROM phptest where a < 0");
if (!DB::isError($rows = $sth->numRows())) {
    print "$rows\n";
} else {
    print "\n";
}


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
