--TEST--
DB::parseDSN test
--SKIPIF--
<?php if (!@include 'DB.php') print 'skip could not find DB.php'; ?>
--FILE--
<?php // -*- C++ -*-
require_once './include.inc';

// Test for: DB::parseDSN()

require_once 'DB.php';

function test($dsn) {
    echo "DSN: $dsn\n";
    print_r(DB::parseDSN($dsn));
}

function testArray($dsn) {
    echo "DSN: array\n";
    print_r(DB::parseDSN($dsn));
}

print "testing DB::parseDSN...\n\n";

test("mysql");
test("odbc(mssql)");
test('odbc(db2)://user:password@/database');
test('odbc(access):///database');
test('odbc://admin@/datasourceName');
test("mysql://localhost");
test("mysql://remote.host.com/db");
test("oci8://system:manager@");
test("oci8://user:pass@tns-name");
test("odbc(solid)://foo:bar@tcp+localhost+1313");  // deprecated
test("pgsql://user@unix+localhost/pear");          // deprecated
test("ibase://user%40domain:password@host");
test("ibase://user@domain:pass@word@/database");   // also supported
test("ifx://user@domain:pass@word@host.com//usr/db/general.db");
test('ifx://remote.host.com/c:\windows\my.db');
test('oci8://SHOOTOUT:******@(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.101.161)(PORT=1521)))(CONNECT_DATA=(SERVICE_NAME=TIS)))');

// new formats
test("odbc(solid)://foo:bar@localhost:1313");
test("pgsql://user@unix()/pear");
test("mysql://user@unix(/path/to/socket)/pear");
test("pgsql://user@tcp()/pear");
test("pgsql://user@tcp(somehost)/pear");
test("pgsql://user:pass@word@tcp(somehost:7777)/pear");

// special backend options
test('ibase://user:pass@localhost//var/lib/dbase.dbf?role=foo');
test('dbase://@/?role=foo&dialect=bar');
test('sqlite:////unix/path/to/database?option=value&anotheroption=anothervalue');
test('sqlite:///c:/win/path/to/database?option=value');

// some examples from manual
test('mysql://username@hostspec');
test('mysql://hostspec/database');
test('mysql://hostspec');
test('mysql:///database');

// array tests
$array = array(
    'phptype'  => 'mysql',
    'hostspec' => 'foobar',
);
testArray($array);

?>
--GET--
--POST--
--EXPECT--
testing DB::parseDSN...

DSN: mysql
Array
(
    [phptype] => mysql
    [dbsyntax] => mysql
    [username] => 
    [password] => 
    [protocol] => 
    [hostspec] => 
    [port] => 
    [socket] => 
    [database] => 
)
DSN: odbc(mssql)
Array
(
    [phptype] => odbc
    [dbsyntax] => mssql
    [username] => 
    [password] => 
    [protocol] => 
    [hostspec] => 
    [port] => 
    [socket] => 
    [database] => 
)
DSN: odbc(db2)://user:password@/database
Array
(
    [phptype] => odbc
    [dbsyntax] => db2
    [username] => user
    [password] => password
    [protocol] => tcp
    [hostspec] => 
    [port] => 
    [socket] => 
    [database] => database
)
DSN: odbc(access):///database
Array
(
    [phptype] => odbc
    [dbsyntax] => access
    [username] => 
    [password] => 
    [protocol] => tcp
    [hostspec] => 
    [port] => 
    [socket] => 
    [database] => database
)
DSN: odbc://admin@/datasourceName
Array
(
    [phptype] => odbc
    [dbsyntax] => odbc
    [username] => admin
    [password] => 
    [protocol] => tcp
    [hostspec] => 
    [port] => 
    [socket] => 
    [database] => datasourceName
)
DSN: mysql://localhost
Array
(
    [phptype] => mysql
    [dbsyntax] => mysql
    [username] => 
    [password] => 
    [protocol] => tcp
    [hostspec] => localhost
    [port] => 
    [socket] => 
    [database] => 
)
DSN: mysql://remote.host.com/db
Array
(
    [phptype] => mysql
    [dbsyntax] => mysql
    [username] => 
    [password] => 
    [protocol] => tcp
    [hostspec] => remote.host.com
    [port] => 
    [socket] => 
    [database] => db
)
DSN: oci8://system:manager@
Array
(
    [phptype] => oci8
    [dbsyntax] => oci8
    [username] => system
    [password] => manager
    [protocol] => tcp
    [hostspec] => 
    [port] => 
    [socket] => 
    [database] => 
)
DSN: oci8://user:pass@tns-name
Array
(
    [phptype] => oci8
    [dbsyntax] => oci8
    [username] => user
    [password] => pass
    [protocol] => tcp
    [hostspec] => tns-name
    [port] => 
    [socket] => 
    [database] => 
)
DSN: odbc(solid)://foo:bar@tcp+localhost+1313
Array
(
    [phptype] => odbc
    [dbsyntax] => solid
    [username] => foo
    [password] => bar
    [protocol] => tcp
    [hostspec] => localhost+1313
    [port] => 
    [socket] => 
    [database] => 
)
DSN: pgsql://user@unix+localhost/pear
Array
(
    [phptype] => pgsql
    [dbsyntax] => pgsql
    [username] => user
    [password] => 
    [protocol] => unix
    [hostspec] => 
    [port] => 
    [socket] => localhost
    [database] => pear
)
DSN: ibase://user%40domain:password@host
Array
(
    [phptype] => ibase
    [dbsyntax] => ibase
    [username] => user@domain
    [password] => password
    [protocol] => tcp
    [hostspec] => host
    [port] => 
    [socket] => 
    [database] => 
)
DSN: ibase://user@domain:pass@word@/database
Array
(
    [phptype] => ibase
    [dbsyntax] => ibase
    [username] => user@domain
    [password] => pass@word
    [protocol] => tcp
    [hostspec] => 
    [port] => 
    [socket] => 
    [database] => database
)
DSN: ifx://user@domain:pass@word@host.com//usr/db/general.db
Array
(
    [phptype] => ifx
    [dbsyntax] => ifx
    [username] => user@domain
    [password] => pass@word
    [protocol] => tcp
    [hostspec] => host.com
    [port] => 
    [socket] => 
    [database] => /usr/db/general.db
)
DSN: ifx://remote.host.com/c:\windows\my.db
Array
(
    [phptype] => ifx
    [dbsyntax] => ifx
    [username] => 
    [password] => 
    [protocol] => tcp
    [hostspec] => remote.host.com
    [port] => 
    [socket] => 
    [database] => c:\windows\my.db
)
DSN: oci8://SHOOTOUT:******@(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.101.161)(PORT=1521)))(CONNECT_DATA=(SERVICE_NAME=TIS)))
Array
(
    [phptype] => oci8
    [dbsyntax] => oci8
    [username] => SHOOTOUT
    [password] => ******
    [protocol] => tcp
    [hostspec] => (DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.101.161)(PORT=1521)))(CONNECT_DATA=(SERVICE_NAME=TIS)))
    [port] => 
    [socket] => 
    [database] => 
)
DSN: odbc(solid)://foo:bar@localhost:1313
Array
(
    [phptype] => odbc
    [dbsyntax] => solid
    [username] => foo
    [password] => bar
    [protocol] => tcp
    [hostspec] => localhost
    [port] => 1313
    [socket] => 
    [database] => 
)
DSN: pgsql://user@unix()/pear
Array
(
    [phptype] => pgsql
    [dbsyntax] => pgsql
    [username] => user
    [password] => 
    [protocol] => unix
    [hostspec] => 
    [port] => 
    [socket] => 
    [database] => pear
)
DSN: mysql://user@unix(/path/to/socket)/pear
Array
(
    [phptype] => mysql
    [dbsyntax] => mysql
    [username] => user
    [password] => 
    [protocol] => unix
    [hostspec] => 
    [port] => 
    [socket] => /path/to/socket
    [database] => pear
)
DSN: pgsql://user@tcp()/pear
Array
(
    [phptype] => pgsql
    [dbsyntax] => pgsql
    [username] => user
    [password] => 
    [protocol] => tcp
    [hostspec] => 
    [port] => 
    [socket] => 
    [database] => pear
)
DSN: pgsql://user@tcp(somehost)/pear
Array
(
    [phptype] => pgsql
    [dbsyntax] => pgsql
    [username] => user
    [password] => 
    [protocol] => tcp
    [hostspec] => somehost
    [port] => 
    [socket] => 
    [database] => pear
)
DSN: pgsql://user:pass@word@tcp(somehost:7777)/pear
Array
(
    [phptype] => pgsql
    [dbsyntax] => pgsql
    [username] => user
    [password] => pass@word
    [protocol] => tcp
    [hostspec] => somehost
    [port] => 7777
    [socket] => 
    [database] => pear
)
DSN: ibase://user:pass@localhost//var/lib/dbase.dbf?role=foo
Array
(
    [phptype] => ibase
    [dbsyntax] => ibase
    [username] => user
    [password] => pass
    [protocol] => tcp
    [hostspec] => localhost
    [port] => 
    [socket] => 
    [database] => /var/lib/dbase.dbf
    [role] => foo
)
DSN: dbase://@/?role=foo&dialect=bar
Array
(
    [phptype] => dbase
    [dbsyntax] => dbase
    [username] => 
    [password] => 
    [protocol] => tcp
    [hostspec] => 
    [port] => 
    [socket] => 
    [database] => 
    [role] => foo
    [dialect] => bar
)
DSN: sqlite:////unix/path/to/database?option=value&anotheroption=anothervalue
Array
(
    [phptype] => sqlite
    [dbsyntax] => sqlite
    [username] => 
    [password] => 
    [protocol] => tcp
    [hostspec] => 
    [port] => 
    [socket] => 
    [database] => /unix/path/to/database
    [option] => value
    [anotheroption] => anothervalue
)
DSN: sqlite:///c:/win/path/to/database?option=value
Array
(
    [phptype] => sqlite
    [dbsyntax] => sqlite
    [username] => 
    [password] => 
    [protocol] => tcp
    [hostspec] => 
    [port] => 
    [socket] => 
    [database] => c:/win/path/to/database
    [option] => value
)
DSN: mysql://username@hostspec
Array
(
    [phptype] => mysql
    [dbsyntax] => mysql
    [username] => username
    [password] => 
    [protocol] => tcp
    [hostspec] => hostspec
    [port] => 
    [socket] => 
    [database] => 
)
DSN: mysql://hostspec/database
Array
(
    [phptype] => mysql
    [dbsyntax] => mysql
    [username] => 
    [password] => 
    [protocol] => tcp
    [hostspec] => hostspec
    [port] => 
    [socket] => 
    [database] => database
)
DSN: mysql://hostspec
Array
(
    [phptype] => mysql
    [dbsyntax] => mysql
    [username] => 
    [password] => 
    [protocol] => tcp
    [hostspec] => hostspec
    [port] => 
    [socket] => 
    [database] => 
)
DSN: mysql:///database
Array
(
    [phptype] => mysql
    [dbsyntax] => mysql
    [username] => 
    [password] => 
    [protocol] => tcp
    [hostspec] => 
    [port] => 
    [socket] => 
    [database] => database
)
DSN: array
Array
(
    [phptype] => mysql
    [dbsyntax] => mysql
    [username] => 
    [password] => 
    [protocol] => 
    [hostspec] => foobar
    [port] => 
    [socket] => 
    [database] => 
)
