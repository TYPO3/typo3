--TEST--
DB::isManip test
--SKIPIF--
<?php if (!@include 'DB.php') print 'skip could not find DB.php'; ?>
--FILE--
<?php // -*- C++ -*-
include_once './include.inc';

// Test for: DB.php
// Parts tested: DB::isManip

require_once 'DB.php';

function test($query) {
    printf("%s : %d\n", preg_replace('/\s+.*/', '', $query),
           DB::isManip($query));
}

print "testing DB::isManip...\n";

test("SELECT * FROM table");
test("Select * from table");
test("select * From table");
test("sElECt * frOm table");
test("SELECT DISTINCT name FROM table");
test("UPDATE table SET foo = 'bar'");
test("DELETE FROM table");
test("delete from table where id is null");
test("create table (id integer, name varchar(100))");
test("CREATE SEQUENCE foo");
test("\"CREATE PROCEDURE foo\"");
test("GRANT SELECT ON table TO user");
test("REVOKE SELECT ON table FROM user");
test("SHOW OPTIONS");
test("DROP TABLE foo");
test("ALTER TABLE foo ADD COLUMN (bar INTEGER)");
test("  SELECT * FROM table");
test("  DELETE FROM table");
?>
--GET--
--POST--
--EXPECT--
testing DB::isManip...
SELECT : 0
Select : 0
select : 0
sElECt : 0
SELECT : 0
UPDATE : 1
DELETE : 1
delete : 1
create : 1
CREATE : 1
"CREATE : 1
GRANT : 1
REVOKE : 1
SHOW : 0
DROP : 1
ALTER : 1
 : 0
 : 1
