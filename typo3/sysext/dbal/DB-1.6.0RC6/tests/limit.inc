<?php
error_reporting(E_ALL);

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


$from    = 0;
$count   = 10;
$numrows = 30;

for ($i=0; $i<=$numrows+2; $i++) {
    $dbh->query("INSERT INTO php_limit VALUES('result $i')");
}
for ($i = 0; $i <= 3; $i++) {
    $from = 10 * $i;
    $res = $dbh->limitQuery("select * from php_limit", $from, $count);
    echo "======= From: $from || Number of rows to fetch: $count =======\n";
    while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        echo $res->getRowCounter() . '.- ' . $row['a'] . "\n";
    }
}


switch ($dbh->phptype) {
    case 'ibase':
        /*
         * Interbase doesn't allow dropping tables that have result
         * sets still open.
         */
        $dbh->freeResult($res->result);
        break;
}
$dbh->setErrorHandling(PEAR_ERROR_RETURN);
$dbh->query('DROP TABLE php_limit');

?>