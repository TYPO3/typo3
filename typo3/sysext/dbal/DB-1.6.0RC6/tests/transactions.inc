<?php

// View the table from a separate connection so we don't disturb
// the transaction.
$dbh2 = DB::connect($dbh->dsn);

function dumptable() {
    global $dbh, $dbh2;
    printf("%s ops=%d\n", implode(" ", $dbh->getCol("SELECT b FROM phptest")),
           $dbh->transaction_opcount);
}
$dbh->setErrorHandling(PEAR_ERROR_DIE);
$dbh->autoCommit(true);
$dbh->query("INSERT INTO phptest VALUES(1, 'one', 'One', '2001-02-19')");
print "after autocommit: ";
dumptable();
$dbh->autoCommit(false);
$dbh->query("INSERT INTO phptest VALUES(2, 'two', 'Two', '2001-02-20')");
$dbh->query("INSERT INTO phptest VALUES(3, 'three', 'Three', '2001-02-21')");
print "before commit: ";
dumptable();
$dbh->commit();
print "after commit: ";
dumptable();
$dbh->query("INSERT INTO phptest VALUES(4, 'four', 'Four', '2001-02-22')");
$dbh->query("INSERT INTO phptest VALUES(5, 'five', 'Five', '2001-02-23')");
print "before rollback: ";
dumptable();
$dbh->rollback();
print "after rollback: ";
dumptable();
$dbh->autoCommit(true);
$dbh->query("INSERT INTO phptest VALUES(6, 'six', 'Six', '2001-02-24')");
$dbh->query("INSERT INTO phptest VALUES(7, 'seven', 'Seven', '2001-02-25')");
print "before autocommit+rollback: ";
dumptable();
$dbh->rollback();
print "after autocommit+rollback: ";
dumptable();

print "testing that select doesn't disturbe opcount: ";
$dbh->autoCommit(false);
$dbh->simpleQuery("SELECT * FROM phptest");
$dbh->simpleQuery("SELECT a,c FROM phptest");
$dbh->simpleQuery("SELECT b,d FROM phptest");
if ($dbh->transaction_opcount == 0) {
    print "ok\n";
} else {
    print "failed (count=$dbh->transaction_opcount)\n";
}

?>
