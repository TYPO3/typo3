
<?php // -*- PHP -*-

function error_handler(&$obj) {
    print "sequences.inc error_handler:\n    ";
    print $obj->getMessage();
}
ob_implicit_flush(true);

$drop = $dbh->dropSequence('test');
if (DB::isError($drop) &&
    $drop->getMessage() != 'DB Error: no such table')
{
    print 'Could not drop sequence... ' . $drop->getMessage() . "\n";
    if ($dbh->phptype == 'ibase' &&
        $drop->getMessage() == 'DB Error: insufficient permissions')
    {
        print "Use this query to provide the permissions needed:\n";
        print '   grant all on RDB$GENERATORS to <USERNAME>';
    }
    exit;
}

// 1) test that sequences are not created if "ondemand" is false

$e = $dbh->nextId("test", false);
if (DB::isError($e)) {
    print $e->getMessage() . "\n";
} else {
    print "test 1) failed!\n";
}

// 2) test that the sequence is not created but the error is
// handled by the class error handler
$dbh->setErrorHandling(PEAR_ERROR_PRINT, "%s <- good error catched\n");
$e = $dbh->nextId("test", false);
if (!DB::isError($e)) {
    print "test 2) failed!\n";
}
$dbh->_default_error_mode = null;

// 3) test that sequences are created if "ondemand" is true, and that
// two successive nextIds return adjacent values
$a = $dbh->nextId("test");
$b = $dbh->nextId("test");
if (DB::isError($a)) {
    print "a: ".$a->toString()."\n";
} else {
    print "a=$a\n";
}
if (DB::isError($b)) {
    print "b: ".$b->toString()."\n";
} else {
    print "b=$b\n";
}
print "b-a=".($b-$a)."\n";

// 4) test that the user-specified error handler is really disabled
// during nextId, with per-object handler as well as global handler
$dbh->dropSequence("test");

$dbh->setErrorHandling(PEAR_ERROR_CALLBACK, "error_handler");
$c = $dbh->nextId("test");
if (DB::isError($c)) {
    print "c: ".$c->toString()."\n";
} else {
    print "c=$c\n";
}
$dbh->dropSequence("test");
$dbh->_default_error_mode = null;
PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, "error_handler");
$d = $dbh->nextId("test");
if (DB::isError($d)) {
    print "d: ".$d->toString()."\n";
} else {
    print "d=$d\n";
}

// 5) test that the sequence is handled right when the table is empty

// Backend with real sequences may don't like that
PEAR::pushErrorHandling(PEAR_ERROR_RETURN);
$dbh->query('DELETE FROM test_seq');
PEAR::popErrorHandling();
$e = $dbh->nextID('test');
if (DB::isError($d)) {
    print "e: ".$d->toString()."\n";
} else {
    print "e=$d\n";
}

// final clean-up
$dbh->dropSequence("test");

?>
