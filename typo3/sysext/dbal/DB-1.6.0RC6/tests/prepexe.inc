<?php

$tmpfile = tempnam("/tmp", "phptmp");
register_shutdown_function("my_shutdown");
$fp = fopen($tmpfile, "w");
$filedata = "opaque\nplaceholder's\ntest";
fwrite($fp, $filedata);
fclose($fp);

// 1) Multiple prepare/exec INSERT queries
echo "------------1------------\n";

$sth1 = $dbh->prepare("INSERT INTO phptest (a, b) VALUES (?, 'a')");
$sth2 = $dbh->prepare("INSERT INTO phptest (a,b) VALUES (!,?)");
$sth3 = $dbh->prepare("INSERT INTO phptest (a,b,c) VALUES (?,!,&)");
$sth4 = $dbh->prepare("INSERT INTO phptest (a, b) VALUES (72, 'direct')");
print "sth1,sth2,sth3,sth4 created\n";
print 'sth1: ? as param, passing as array... ';
if (($res = $dbh->execute($sth1, array(72))) == DB_OK) {
    print "sth1 executed\n";
}
print 'sth2: ! and ? as params, passing as array... ';
if (($res = $dbh->execute($sth2, array(72, "that's right"))) == DB_OK) {
    print "sth2 executed\n";
}
print 'sth3: ?, ! and & as params, passing as array... ';
if (($res = $dbh->execute($sth3, array(72, "'it''s good'", $tmpfile))) == DB_OK) {
    print "sth3 executed\n";
}
print 'sth4: no params... ';
if (($res = $dbh->execute($sth4)) == DB_OK) {
    print "sth4 executed\n";
}
print_results();


// 2) One prepared, multiple time executed
echo "\n------------2------------\n";

$dbh->query('DELETE FROM phptest');
$sth = $dbh->prepare('INSERT INTO phptest (a, b, c, d) VALUES (?, ?, &, ?)');
$data = array(
    0 => array(72, 'set1', $tmpfile, '1234-56-78'),
    1 => array(72, 'set2', $tmpfile, null),
    2 => array(72, 'set3', $tmpfile, null)
);
$dbh->executeMultiple($sth, $data);
print_results();


// 3) freePrepared() test
echo "\n------------3------------\n";

if ($dbh->freePrepared($sth)) {
    echo 'TRUE';
} else {
    echo 'FALSE';
}
echo "\n";
if ($dbh->freePrepared(666)) {
    echo 'TRUE';
} else {
    echo 'FALSE';
}
echo "\n";


// 4) SELECTs tests
echo "\n------------4------------\n";

$sth1 = $dbh->prepare("SELECT * FROM phptest WHERE a = ? ORDER BY b");
print_4($sth1, 72);
print_4($sth1, 71);
$sth2 = $dbh->prepare("SELECT * FROM phptest WHERE d = ? ORDER BY b");
print_4($sth2, '1234-56-78');
$sth3 = $dbh->prepare("SELECT * FROM phptest WHERE c = & ORDER BY b");
print_4($sth3, $tmpfile);


// 5) ASSOCIATIVE ARRAY queries
echo "\n------------5------------\n";

$sth5 = $dbh->prepare('INSERT INTO phptest (a, b, d) VALUES (?, ?, ?)');
$array = array(
    'foo' => 11,
    'bar' => 'three',
    'baz' => null,
);
$res = $dbh->execute($sth5, $array);
print 'insert: ' . ($res == DB_OK ? 'okay' : 'error') . "\n";

$sth6 = $dbh->prepare('SELECT a, b, d FROM phptest WHERE a = ?');
$res = $dbh->execute($sth6, array(11));
$row = $res->fetchRow(DB_FETCHMODE_ASSOC);
print "a = {$row['a']}, b = {$row['b']}, d = " . gettype($row['d']) . "\n";



function my_shutdown() {
    global $tmpfile, $dbh, $sth1, $sth2, $sth3, $sth4, $sth5, $sth6, $res;

    switch ($dbh->phptype) {
        case 'ibase':
            /*
             * Interbase doesn't allow dropping tables that have result
             * sets still open.
             */
            $dbh->freePrepared($sth1);
            $dbh->freePrepared($sth2);
            $dbh->freePrepared($sth3);
            $dbh->freePrepared($sth4);
            $dbh->freePrepared($sth5);
            $dbh->freePrepared($sth6);
            $dbh->freeResult($res->result);
            break;
    }

    $dbh->setErrorHandling(PEAR_ERROR_RETURN);
    $dbh->query('DROP TABLE phptest');

    unlink($tmpfile);
}

function print_results() {
    global $dbh;
    print "results:\n";
    $sth = $dbh->query("SELECT * FROM phptest WHERE a = 72 ORDER BY b");
    while ($row = $sth->fetchRow(DB_FETCHMODE_ORDERED)) {
        print '|' . implode(" - ", $row) . "|\n";
    }
}

function print_4($sth, $bind) {
    global $dbh;
    $res = $dbh->execute($sth, $bind);
    while ($row = $res->fetchRow(DB_FETCHMODE_ORDERED)) {
        print '|' . implode(" - ", $row) . "|\n";
    }
    echo "~~\n";
}

?>