<?php

function errhandler($obj)
{
    if ($obj->getCode() == DB_ERROR) {
        print "  unknown: ".$obj->getUserInfo()."\n";
    } else {
        $msg = $obj->getMessage();
        print "  $msg";
        if (substr($msg, -1) != "\n") {
            print "\n";
        }
    }
}

$dbh->setErrorHandling(PEAR_ERROR_CALLBACK, "errhandler");


print "Trying to provoke DB_ERROR_NOSUCHTABLE\n";
$dbh->query("SELECT * FROM tableThatDoesNotExist");

print "Trying to provoke DB_ERROR_ALREADY_EXISTS for create table\n";
$dbh->query($test_mktable_query);

print "Trying to provoke DB_ERROR_NOSUCHTABLE\n";
$dbh->query("DROP TABLE tableThatDoesNotExist");


print "Trying to provoke DB_ERROR_CONSTRAINT for primary key insert duplicate\n";
$dbh->setErrorHandling(PEAR_ERROR_RETURN);
$dbh->query('DROP TABLE a');
$dbh->setErrorHandling(PEAR_ERROR_CALLBACK, 'errhandler');
$dbh->query('CREATE TABLE a (a INTEGER NOT NULL, PRIMARY KEY (a))');
$dbh->query('INSERT INTO a VALUES (1)');
$dbh->query('INSERT INTO a VALUES (1)');

print "Trying to provoke DB_ERROR_CONSTRAINT for primary key update duplicate\n";
$dbh->query('INSERT INTO a VALUES (2)');
$dbh->query('UPDATE a SET a=1 WHERE a=2');


print "Trying to provoke DB_ERROR_CONSTRAINT for unique key insert duplicate\n";
$dbh->setErrorHandling(PEAR_ERROR_RETURN);
$dbh->query('DROP TABLE a');
$dbh->setErrorHandling(PEAR_ERROR_CALLBACK, 'errhandler');
$dbh->query('CREATE TABLE a (a INTEGER NOT NULL, UNIQUE (a))');
$dbh->query('INSERT INTO a VALUES (1)');
$dbh->query('INSERT INTO a VALUES (1)');

print "Trying to provoke DB_ERROR_CONSTRAINT for unique key update duplicate\n";
$dbh->query('INSERT INTO a VALUES (2)');
$dbh->query('UPDATE a SET a=1 WHERE a=2');


print "Trying to provoke DB_ERROR_CONSTRAINT for foreign key\n";
$dbh->setErrorHandling(PEAR_ERROR_RETURN);
$dbh->query("DROP TABLE b");
$dbh->query("DROP TABLE a");
$dbh->setErrorHandling(PEAR_ERROR_CALLBACK, "errhandler");
$dbh->query("CREATE TABLE a (a INTEGER NOT NULL, PRIMARY KEY(a))");
$dbh->query("CREATE TABLE b (b INTEGER REFERENCES a(a))");
$dbh->query("INSERT INTO a VALUES(1)");
$dbh->query("INSERT INTO b VALUES(2)");
switch ($dbh->phptype) {
    case 'mysql':
    case 'sqlite':
        print "  $dbh->phptype doesn't consider this an error\n";
        break;
}

print "Trying to provoke DB_ERROR_DIVZERO\n";
// Interbase detects the error on fetching
$dbh->getAll("SELECT 0/0 FROM phptest");
switch ($dbh->phptype) {
    case 'mssql':
    case 'sybase':
        print "  PHP's $dbh->phptype extension doesn't report this error\n";
        break;
    case 'odbc':
        switch ($dbh->dbsyntax) {
            case 'access':
                print "  odbc($dbh->dbsyntax) doesn't report this error\n";
                break;
        }
        break;
    case 'mysql':
    case 'sqlite':
        print "  $dbh->phptype doesn't consider this an error\n";
        break;
}

print "Trying to provoke DB_ERROR_INVALID_NUMBER\n";
$dbh->query("UPDATE phptest SET a = 'abc' WHERE a = 42");
switch ($dbh->phptype) {
    case 'mysql':
    case 'sqlite':
        print "  $dbh->phptype doesn't consider this an error\n";
        break;
}

print "Trying to provoke DB_ERROR_NOSUCHFIELD\n";
$dbh->query("SELECT e FROM phptest");

print "Trying to provoke DB_ERROR_SYNTAX\n";
$dbh->query("CREATE");


$dbh->setErrorHandling(PEAR_ERROR_RETURN);
$dbh->query('DROP TABLE phptest');
$dbh->query('DROP TABLE b');
$dbh->query('DROP TABLE a');

?>
