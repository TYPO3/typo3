--TEST--
DB_driver::tableInfo
--SKIPIF--
<?php

/**
 * Calls tableInfo() in various ways and checks to see that the output
 * matches what's expected.
 *
 * These tests account for each DBMS using different column types and
 * reporting back different information.  These differences are accounted
 * for in the <var>$quirks</var> array, which has the following format:
 *
 * <pre>
 * 'driver' => array(
 *     'clob' => DBMS's column type for creating CLOB fields
 *     'date' => DBMS's column type for creating DATE fields
 *     'finds_table' => Can this DBMS determine table names from queries?
 *     'commands' => array(
 *         Extra commands to be passed to PHP's eval() function
 *     )
 *     0 => array(
 *         //  Info expected to be reported for phptest_fk.a
 *         'type' => Column type reported by the DBMS
 *         'len' => Column size reported by the DBMS
 *         'flags' => Flags reported by the DBMS
 *     )
 *     1 => array()  Info expected to be reported for phptest_fk.fk
 *     2 => array()  Info expected to be reported for phptest_fk.c
 *     3 => array()  Info expected to be reported for phptest_fk.d
 *     4 => array()  Info expected to be reported for phptest_fk.e
 *     5 => array()  Info expected to be reported for phptest_fk.f
 *     9 => array()  Info expected to be reported for phptest.d
 * )
 * </pre>
 *
 * @see      DB_common::tableInfo()
 * 
 * @package  DB
 * @version  $Id$
 * @category Database
 * @author   Daniel Convissor <danielc@analysisandsolutions.com>
 * @internal
 */

chdir(dirname(__FILE__));
require_once './skipif.inc';
$tableInfo = $db->tableInfo('ajkdslfajoijkadie');
if (DB::isError($tableInfo) && $tableInfo->code == DB_ERROR_NOT_CAPABLE) {
    die("skip $tableInfo->message");
}

?>
--FILE--
<?php

//  $Id$

/**
 * Connect to the database and make the phptest table.
 */
require_once './mktable.inc';

/**
 * Local error callback handler.
 *
 * Prints out an error message and kills the process.
 *
 * @param object  $o  PEAR error object automatically passed to this method
 * @return void
 * @see PEAR::setErrorHandling()
 */
function pe($o){
    global $dbh;

    if ($o->getMessage() == "DB Error: can't distinguish duplicate field names") {
        print "NOTICE: $dbh->phptype can't distinguish duplicate field names\n";
        return;
    }

    $dbh->setErrorHandling(PEAR_ERROR_RETURN);
    $dbh->query('DROP TABLE phptest');
    $dbh->query('DROP TABLE phptest_fk');

    die($o->toString());
}

/**
 * Loop through an array returned from tableInfo(), compare the actual
 * contents to the expected contents and print out what is found.
 *
 * @param array   $array  the array to be examined
 * @param string  $field  field index number of $quriks and table
 * @param boolean $query  true if array is from a query or false if array
 *                        is tableInfo()
 * @return void
 */
function examineArrayData($array, $field = false, $query = true) {
    global $dbh, $quirks;

    foreach ($array as $key => $value) {
        if ($field !== false &&
            isset($quirks[$dbh->phptype][$field][$key]))
        {
            if ($key == 'flags' && $value == '' && $query &&
                !$quirks[$dbh->phptype]['finds_table'])
            {
                print "$key ... matched expected value\n";
            } else {
                if ($quirks[$dbh->phptype][$field][$key] == $value) {
                    print "$key ... matched expected value\n";
                } else {
                    print "$key ... was '$value' but we expected ";
                    print "'{$quirks[$dbh->phptype][$field][$key]}'\n";
                }
            }
        } else {
            if ($key == 'table') {
                if ($field <= 5) {
                    if ($value == 'phptest_fk') {
                        print "$key ... matched expected value\n";
                    } else {
                        if ($value == '' && $query &&
                            !$quirks[$dbh->phptype]['finds_table'])
                        {
                            print "$key ... matched expected value\n";
                        } else {
                            print "$key ... was '$value' but we expected 'phptest_fk'\n";
                        }
                    }
                } else {
                    if ($value == 'phptest') {
                        print "$key ... matched expected value\n";
                    } else {
                        if ($value == '' && $query &&
                            !$quirks[$dbh->phptype]['finds_table'])
                        {
                            print "$key ... matched expected value\n";
                        } else {
                            print "$key ... was '$value' but we expected 'phptest_fk'\n";
                        }
                    }
                }
            } else {
                print "$key => $value\n";
            }
        }
    }
}

/**
 * Loop through an array of table info data and return the results.
 *
 * @param array  $array  the array to be examined
 * @return string
 */
function returnArrayData($array) {
    $out = '';
    foreach ($array as $key => $value) {
        $out .= "$key => $value\n";
    }
    return $out;
}


$dbh->setErrorHandling(PEAR_ERROR_CALLBACK, 'pe');


$quirks = array(
    'ibase' => array(
        'clob' => 'VARCHAR(50)',
        'date' => 'DATE',
        'finds_table' => false,
        'commands' => array(
        ),
        0 => array(
            'type' => 'INTEGER',
            'len' => 4,
            'flags' => 'unique_key not_null',
        ),
        1 => array(
            'type' => 'INTEGER',
            'len' => 4,
            'flags' => 'primary_key not_null',
        ),
        2 => array(
            'type' => 'VARCHAR',
            'len' => 50,
            'flags' => '',
        ),
        3 => array(
            'type' => 'DATE',
            'len' => 4,
            'flags' => 'unique_key not_null',
        ),
        4 => array(
            'type' => 'CHAR',
            'len' => 4,
            'flags' => 'not_null default',
        ),
        5 => array(
            'type' => 'NUMERIC(9,1)',
            'len' => 4,
            'flags' => '',
        ),
        9 => array(
            'type' => 'VARCHAR',
            'len' => 20,
            'flags' => '',
        ),
    ),

    'ifx' => array(
        'clob' => 'CHAR(29)',
        'date' => 'CHAR(10)',
        'finds_table' => false,
        'commands' => array(
        ),
        0 => array(
            'type' => 'SQLINT',
            'len' => 4,
            'flags' => 'not_null',
        ),
        1 => array(
            'type' => 'SQLINT',
            'len' => 4,
            'flags' => 'not_null',
        ),
        2 => array(
            'type' => 'SQLCHAR',
            'len' => 29,
            'flags' => '',
        ),
        3 => array(
            'type' => 'SQLCHAR',
            'len' => 10,
            'flags' => 'not_null',
        ),
        4 => array(
            'type' => 'SQLCHAR',
            'len' => 4,
            'flags' => 'not_null default_df%20t',
        ),
        5 => array(
            'type' => 'SQLREAL',
            'len' => 4,
            'flags' => '',
        ),
        9 => array(
            'type' => 'SQLCHAR',
            'len' => 20,
            'flags' => '',
        ),
    ),

    'mssql' => array(
        'clob' => 'TEXT',
        'date' => 'SMALLDATETIME',
        'finds_table' => false,
        'commands' => array(
            'ini_set("mssql.datetimeconvert", "Off");',
            '$dbh->query("SET DATEFORMAT ymd");',
        ),
        0 => array(
            'type' => 'int',
            'len' => 4,
            'flags' => 'multiple_key unique_key not_null',
        ),
        1 => array(
            'type' => 'int',
            'len' => 4,
            'flags' => 'primary_key not_null',
        ),
        2 => array(
            'type' => 'text',
            'len' => 4096,
            'flags' => '',
        ),
        3 => array(
            'type' => 'datetime',
            'len' => 4,
            'flags' => 'multiple_key unique_key not_null',
        ),
        4 => array(
            'type' => 'char',
            'len' => 4,
            'flags' => 'not_null',
        ),
        5 => array(
            'type' => 'real',
            'len' => 19,
            'flags' => '',
        ),
        9 => array(
            'type' => 'char',
            'len' => 20,
            'flags' => '',
        ),
    ),

    'mysql' => array(
        'clob' => 'TEXT',
        'date' => 'DATE',
        'finds_table' => true,
        'commands' => array(
        ),
        0 => array(
            'type' => 'int',
            'len' => 11,
            'flags' => 'not_null multiple_key',
        ),
        1 => array(
            'type' => 'int',
            'len' => 11,
            'flags' => 'not_null primary_key',
        ),
        2 => array(
            'type' => 'blob',
            'len' => 65535,
            'flags' => 'blob',
        ),
        3 => array(
            'type' => 'date',
            'len' => 10,
            'flags' => 'not_null multiple_key',
        ),
        4 => array(
            'type' => 'string',
            'len' => 4,
            'flags' => 'not_null',
        ),
        5 => array(
            'type' => 'real',
            'len' => 4,
            'flags' => '',
        ),
        9 => array(
            'type' => 'string',
            'len' => 20,
            'flags' => '',
        ),
    ),

    'oci8' => array(
        'clob' => 'CLOB',
        'date' => 'DATE',
        'finds_table' => false,
        'commands' => array(
            '$dbh->query("ALTER SESSION SET NLS_DATE_FORMAT = \'YYYY-MM-DD\'");',
        ),
        0 => array(
            'type' => 'NUMBER',
            'len' => 22,
            'flags' => 'not_null',
        ),
        1 => array(
            'type' => 'NUMBER',
            'len' => 22,
            'flags' => 'not_null',
        ),
        2 => array(
            'type' => 'CLOB',
            'len' => 4000,
            'flags' => '',
        ),
        3 => array(
            'type' => 'DATE',
            'len' => 7,
            'flags' => 'not_null',
        ),
        4 => array(
            'type' => 'CHAR',
            'len' => 4,
            'flags' => 'not_null',
        ),
        5 => array(
            'type' => 'NUMBER',
            'len' => 22,
            'flags' => '',
        ),
        9 => array(
            'type' => 'VARCHAR',
            'len' => 20,
            'flags' => '',
        ),
    ),

    'pgsql' => array(
        'clob' => 'TEXT',
        'date' => 'DATE',
        'finds_table' => false,
        'commands' => array(
            '$dbh->query("SET DATESTYLE = ISO");',
        ),
        0 => array(
            'type' => 'int4',
            'len' => 4,
            'flags' => 'not_null unique_key multiple_key',
        ),
        1 => array(
            'type' => 'int4',
            'len' => 4,
            'flags' => 'not_null primary_key',
        ),
        2 => array(
            'type' => 'text',
            'len' => -1,
            'flags' => '',
        ),
        3 => array(
            'type' => 'date',
            'len' => 4,
            'flags' => 'not_null unique_key multiple_key',
        ),
        4 => array(
            'type' => 'bpchar',
            'len' => -1,
            'flags' => 'not_null default_df%20t',
        ),
        5 => array(
            'type' => 'numeric',
            'len' => -1,
            'flags' => '',
        ),
        9 => array(
            'type' => 'varchar',
            'len' => -1,
            'flags' => '',
        ),
    ),

    'sybase' => array(
        'clob' => 'TEXT',
        'date' => 'SMALLDATETIME',
        'finds_table' => false,
        'commands' => array(
            '$dbh->query("SET DATEFORMAT ymd");',
        ),
        0 => array(
            'type' => 'int',
            'len' => 11,
            'flags' => 'multiple_key unique_key',
        ),
        1 => array(
            'type' => 'int',
            'len' => 11,
            'flags' => 'unique_key',
        ),
        2 => array(
            'type' => 'string',
            'len' => 32768,
            'flags' => '',
        ),
        3 => array(
            'type' => 'datetime',
            'len' => 29,
            'flags' => 'multiple_key unique_key',
        ),
        4 => array(
            'type' => 'string',
            'len' => 4,
            'flags' => '',
        ),
        5 => array(
            'type' => 'real',
            'len' => 4,
            'flags' => '',
        ),
        9 => array(
            'type' => 'string',
            'len' => 20,
            'flags' => '',
        ),
    ),
);


if (!isset($quirks[$dbh->phptype])) {
    die("This test does not yet support $dbh->phptype");
}

if (count($quirks[$dbh->phptype]['commands'])) {
    foreach ($quirks[$dbh->phptype]['commands'] as $value) {
        eval($value);
    }
}


$dbh->query('DELETE FROM phptest');
$dbh->query("INSERT INTO phptest VALUES (1, 'one', 'One', '2001-02-16')");
$dbh->query("INSERT INTO phptest VALUES (2, 'two', 'Two', '2001-02-15')");
$dbh->query("INSERT INTO phptest VALUES (3, 'three', 'Three', '2001-02-14')");


$dbh->setErrorHandling(PEAR_ERROR_RETURN);
$dbh->query('DROP TABLE phptest_fk');
$dbh->setErrorHandling(PEAR_ERROR_CALLBACK, 'pe');


// $null is set in mktable.inc

$dbh->query("
    CREATE TABLE phptest_fk (
        a INTEGER NOT NULL,
        fk INTEGER NOT NULL,
        c {$quirks[$dbh->phptype]['clob']} $null,
        d {$quirks[$dbh->phptype]['date']} NOT NULL,
        e CHAR(4) DEFAULT 'df t' NOT NULL,
        f DECIMAL(2,1) $null,
        PRIMARY KEY (fk),
        UNIQUE (a, d)
    )
");
$dbh->query("CREATE INDEX thedidx ON phptest_fk (d)");
$dbh->query("INSERT INTO phptest_fk VALUES (10, 1, 'One', '2001-02-16',  'c1', 1.1)");
$dbh->query("INSERT INTO phptest_fk VALUES (20, 2, 'Two', '2001-02-15', 'c2', 2.2)");
$dbh->query("INSERT INTO phptest_fk VALUES (30, 3, 'Three', '2001-02-14', 'c3', 3.3)");


$resultobj =& $dbh->query('SELECT phptest_fk.a, phptest_fk.fk,
        phptest_fk.c, phptest_fk.d, phptest_fk.e, phptest_fk.f,
        phptest.a, phptest.b, phptest.c, phptest.d
        FROM phptest_fk, phptest WHERE phptest.a = phptest_fk.fk');



print "\n==========================================\n";
print "Passing result OBJECT to method in DB_<type>.\n";
print "Output = default.\n";
print "------------------------------------------\n";
$array = $dbh->tableInfo($resultobj);

print "\nfirst field:\n";
examineArrayData($array[0], 0);

print "\ntenth field:\n";
examineArrayData($array[9], 9);



print "\n==========================================\n";
print "Passing result ID to method in DB_<type>.\n";
print "Output = DB_TABLEINFO_ORDER.\n";
print "------------------------------------------\n";
$array = $dbh->tableInfo($resultobj->result, DB_TABLEINFO_ORDER);

print "\nfirst field:\n";
examineArrayData($array[0], 0);

print "\nfourth field:\n";
examineArrayData($array[3], 3);

print "\nnum_fields:\n";
print "{$array['num_fields']}\n";

print "\norder:\n";
ksort($array['order']);
examineArrayData($array['order']);



print "\n==========================================\n";
print "Passing DB_TABLEINFO_ORDERTABLE to method in DB_result.\n";
print "Output = DB_TABLEINFO_ORDERTABLE.\n";
print "------------------------------------------\n";
$array = $resultobj->tableInfo(DB_TABLEINFO_ORDERTABLE);
// Free this to keep interbase happy.
$resultobj->free();

print "\nfirst field:\n";
examineArrayData($array[0], 0);

print "\nfourth field:\n";
examineArrayData($array[3], 3);

print "\nnum_fields:\n";
print "{$array['num_fields']}\n";


print "\nordertable[phptest]:\n";
$expected = 'a => 6
b => 7
c => 8
d => 9
';
if (isset($array['ordertable']['phptest'])) {
    $actual = returnArrayData($array['ordertable']['phptest']);
} else {
    $actual = '';
}
if ($actual == $expected) {
    print "matched expected values\n";
} else {
    if ($quirks[$dbh->phptype]['finds_table'] === false && $actual == '') {
        print "matched expected values\n";
    } else {
        print "DIDN'T match expected values...\n";
        print "~~~~~~~~\nExpected:\n$expected\n";
        print "~~~~\nActual:\n$actual\n~~~~~~~~\n\n";
    }
}


print "\nordertable[phptest_fk]:\n";
$expected = 'a => 0
fk => 1
c => 2
d => 3
e => 4
f => 5
';
if (isset($array['ordertable']['phptest_fk'])) {
    $actual = returnArrayData($array['ordertable']['phptest_fk']);
} else {
    $actual = '';
}
if ($actual == $expected) {
    print "matched expected values\n";
} else {
    if ($quirks[$dbh->phptype]['finds_table'] === false && $actual == '') {
        print "matched expected values\n";
    } else {
        print "DIDN'T match expected values...\n";
        print "~~~~~~~~\nExpected:\n$expected\n";
        print "~~~~\nActual:\n$actual\n~~~~~~~~\n\n";
    }
}


print "\n==========================================\n";
print "Passing TABLE NAME 'phptest_fk' to method in DB_<driver>.\n";
print "Output = default.\n";
print "------------------------------------------\n";
$array = $dbh->tableInfo('phptest_fk');

print "\nfirst field:\n";
examineArrayData($array[0], 0, false);

print "\nsecond field:\n";
examineArrayData($array[1], 1, false);

print "\nthird field:\n";
examineArrayData($array[2], 2, false);

print "\nfourth field:\n";
examineArrayData($array[3], 3, false);

print "\nfifth field:\n";
examineArrayData($array[4], 4, false);

print "\nsixth field:\n";
examineArrayData($array[5], 5, false);


print "\n==========================================\n";
print "Passing TABLE NAME 'phptest_fk' to method in DB_<driver>.\n";
print "Output = DB_TABLEINFO_FULL.\n";
print "------------------------------------------\n";
$array = $dbh->tableInfo('phptest_fk', DB_TABLEINFO_FULL);

print "\nfirst field:\n";
examineArrayData($array[0], 0, false);

print "\norder:\n";
examineArrayData($array['order'], false);

print "\nordertable[phptest_fk]:\n";
examineArrayData($array['ordertable']['phptest_fk']);



print "\n==========================================\n";
print "Passing TABLE NAME 'phptest_fk' to method in DB_<driver> AGAIN.\n";
print "Output = DB_TABLEINFO_FULL, lowercasing turned off.\n";
print "------------------------------------------\n";
$dbh->setOption('portability', DB_PORTABILITY_ALL ^ DB_PORTABILITY_LOWERCASE);
$array = $dbh->tableInfo('phptest_fk', DB_TABLEINFO_FULL);

// testing non-lowercasing above to ensure program doesn't die.
// lowercase the names here to ensure test uniformity.
$array[0]['table'] = strtolower($array[0]['table']);
$array[0]['name'] = strtolower($array[0]['name']);

print "\nfirst field:\n";
examineArrayData($array[0], 0, false);


$dbh->setErrorHandling(PEAR_ERROR_RETURN);
$dbh->query('DROP TABLE phptest');
$dbh->query('DROP TABLE phptest_fk');

?>
--EXPECT--
==========================================
Passing result OBJECT to method in DB_<type>.
Output = default.
------------------------------------------

first field:
table ... matched expected value
name => a
type ... matched expected value
len ... matched expected value
flags ... matched expected value

tenth field:
table ... matched expected value
name => d
type ... matched expected value
len ... matched expected value
flags ... matched expected value

==========================================
Passing result ID to method in DB_<type>.
Output = DB_TABLEINFO_ORDER.
------------------------------------------

first field:
table ... matched expected value
name => a
type ... matched expected value
len ... matched expected value
flags ... matched expected value

fourth field:
table ... matched expected value
name => d
type ... matched expected value
len ... matched expected value
flags ... matched expected value

num_fields:
10

order:
a => 6
b => 7
c => 8
d => 9
e => 4
f => 5
fk => 1

==========================================
Passing DB_TABLEINFO_ORDERTABLE to method in DB_result.
Output = DB_TABLEINFO_ORDERTABLE.
------------------------------------------

first field:
table ... matched expected value
name => a
type ... matched expected value
len ... matched expected value
flags ... matched expected value

fourth field:
table ... matched expected value
name => d
type ... matched expected value
len ... matched expected value
flags ... matched expected value

num_fields:
10

ordertable[phptest]:
matched expected values

ordertable[phptest_fk]:
matched expected values

==========================================
Passing TABLE NAME 'phptest_fk' to method in DB_<driver>.
Output = default.
------------------------------------------

first field:
table ... matched expected value
name => a
type ... matched expected value
len ... matched expected value
flags ... matched expected value

second field:
table ... matched expected value
name => fk
type ... matched expected value
len ... matched expected value
flags ... matched expected value

third field:
table ... matched expected value
name => c
type ... matched expected value
len ... matched expected value
flags ... matched expected value

fourth field:
table ... matched expected value
name => d
type ... matched expected value
len ... matched expected value
flags ... matched expected value

fifth field:
table ... matched expected value
name => e
type ... matched expected value
len ... matched expected value
flags ... matched expected value

sixth field:
table ... matched expected value
name => f
type ... matched expected value
len ... matched expected value
flags ... matched expected value

==========================================
Passing TABLE NAME 'phptest_fk' to method in DB_<driver>.
Output = DB_TABLEINFO_FULL.
------------------------------------------

first field:
table ... matched expected value
name => a
type ... matched expected value
len ... matched expected value
flags ... matched expected value

order:
a => 0
fk => 1
c => 2
d => 3
e => 4
f => 5

ordertable[phptest_fk]:
a => 0
fk => 1
c => 2
d => 3
e => 4
f => 5

==========================================
Passing TABLE NAME 'phptest_fk' to method in DB_<driver> AGAIN.
Output = DB_TABLEINFO_FULL, lowercasing turned off.
------------------------------------------

first field:
table ... matched expected value
name => a
type ... matched expected value
len ... matched expected value
flags ... matched expected value
