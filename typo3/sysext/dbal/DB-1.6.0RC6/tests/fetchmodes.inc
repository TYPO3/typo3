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


$dbh->query("INSERT INTO phptest VALUES (1, 'one', 'One', '2001-02-16')");
$dbh->query("INSERT INTO phptest VALUES (2, 'two', 'Two', '2001-02-15')");
$dbh->query("INSERT INTO phptest VALUES (3, 'three', 'Three', '2001-02-14')");

print "testing fetchrow:\n";
$sth = $dbh->query("SELECT * FROM phptest");
for ($i = 1; $i <= 5; $i++) {
    print "row $i: ";
    $row = $sth->fetchRow();
    if (DB::isError($row)) {
        print $row->toString() . "\n";
        continue;
    }
    if (is_array($row)) {
        print implode(', ', $row) . "\n";
    } else {
        var_dump($row);
    }
}

$dbh->query('DELETE FROM phptest WHERE a <> 42');


print "testing fetchmodes: fetchrow default default, portability mode DB_PORTABILITY_ALL ^ DB_PORTABILITY_RTRIM\n";
$dbh->setOption('portability', DB_PORTABILITY_ALL ^ DB_PORTABILITY_RTRIM);
$sth = $dbh->query("SELECT * FROM phptest");
$row = $sth->fetchRow();
print implode(" ", array_keys($row))."\n";
$actual = implode(' ', array_values($row));
switch ($dbh->phptype) {
    case 'mysql':
    case 'mysql4':
    case 'sqlite':
        $expected = '42 bing This is a test 1999-11-21';
        break;
    default:
        $expected = '42 bing                                     This is a test 1999-11-21';
}
if ($actual == $expected) {
    echo "output matched expected format\n";
} else {
    echo "DIDN'T MATCH!  Expected output: '$expected'.  Actual output: '$actual'.\n";
}

print "testing fetchmodes: fetchinto default default\n";
$dbh->setOption('portability', DB_PORTABILITY_ALL);
$sth = $dbh->query("SELECT * FROM phptest");
$row = array();
$sth->fetchInto($row);
print implode(" ", array_keys($row))."\n";
print implode(' ', array_values($row))."\n";

print "testing fetchmodes: fetchrow ordered default\n";
$dbh->setFetchMode(DB_FETCHMODE_ORDERED);
$sth = $dbh->query("SELECT * FROM phptest");
$row = $sth->fetchRow();
print implode(" ", array_keys($row))."\n";

print "testing fetchmodes: fetchrow assoc default\n";
$dbh->setFetchMode(DB_FETCHMODE_ASSOC);
$sth = $dbh->query("SELECT * FROM phptest");
$row = $sth->fetchRow();
print implode(" ", array_keys($row))."\n";

print "testing fetchmodes: fetchrow ordered default with assoc specified\n";
$dbh->setFetchMode(DB_FETCHMODE_ORDERED);
$sth = $dbh->query("SELECT * FROM phptest");
$row = $sth->fetchRow(DB_FETCHMODE_ASSOC);
print implode(" ", array_keys($row))."\n";

print "testing fetchmodes: fetchrow assoc default with ordered specified\n";
$dbh->setFetchMode(DB_FETCHMODE_ASSOC);
$sth = $dbh->query("SELECT * FROM phptest");
$row = $sth->fetchRow(DB_FETCHMODE_ORDERED);
print implode(" ", array_keys($row))."\n";

print "testing fetchmodes: fetchinto ordered default\n";
$dbh->setFetchMode(DB_FETCHMODE_ORDERED);
$sth = $dbh->query("SELECT * FROM phptest");
$row = array();
$sth->fetchInto($row);
print implode(" ", array_keys($row))."\n";

print "testing fetchmodes: fetchinto assoc default\n";
$dbh->setFetchMode(DB_FETCHMODE_ASSOC);
$sth = $dbh->query("SELECT * FROM phptest");
$row = array();
$sth->fetchInto($row);
print implode(" ", array_keys($row))."\n";

print "testing fetchmodes: fetchinto ordered default with assoc specified\n";
$dbh->setFetchMode(DB_FETCHMODE_ORDERED);
$sth = $dbh->query("SELECT * FROM phptest");
$row = array();
$sth->fetchInto($row, DB_FETCHMODE_ASSOC);
print implode(" ", array_keys($row))."\n";

print "testing fetchmodes: fetchinto assoc default with ordered specified\n";
$dbh->setFetchMode(DB_FETCHMODE_ASSOC);
$sth = $dbh->query("SELECT * FROM phptest");
$row = array();
$sth->fetchInto($row, DB_FETCHMODE_ORDERED);
print implode(" ", array_keys($row))."\n";

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
