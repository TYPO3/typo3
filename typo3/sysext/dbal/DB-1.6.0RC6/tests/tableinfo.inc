<?php
function pe($o){
    die($o->toString());
}
$dbh->setErrorHandling(PEAR_ERROR_RETURN);
$dbh->query("DROP TABLE phptest_fk");

$dbh->setErrorHandling(PEAR_ERROR_CALLBACK,'pe');

$dbh->query("DELETE FROM phptest");
$dbh->query("INSERT INTO phptest VALUES(1, 'one', 'One', '2001-02-16')");
$dbh->query("INSERT INTO phptest VALUES(2, 'two', 'Two', '2001-02-15')");
$dbh->query("INSERT INTO phptest VALUES(3, 'three', 'Three', '2001-02-14')");

$test_mktable_query2 = "CREATE TABLE phptest_fk (a INTEGER, fk INTEGER, c TEXT, d DATE)";
$dbh->query($test_mktable_query2);

$dbh->query("INSERT INTO phptest_fk VALUES(1,  1, 'One', '2001-02-16')");
$dbh->query("INSERT INTO phptest_fk VALUES(2,  1, ' Two', '2001-02-15')");
$dbh->query("INSERT INTO phptest_fk VALUES(3,  2, 'Three', '2001-02-14')");



print "testing tableInfo:\n";
$sth = $dbh->query("SELECT * FROM phptest left join phptest_fk on phptest.a = phptest_fk.fk");

$tableInfo = $sth->tableInfo();
print "\nfirst field:\n";
print_foreach($tableInfo[0]) ;
print "\neight (last) field:\n";
print_foreach($tableInfo[7]) ;

print "\ntesting tableInfo (DB_TABLEINFO_ORDER):\n";
$tableInfo =$sth->tableInfo(DB_TABLEINFO_ORDER);
print "\nfirst field:\n";
print_foreach($tableInfo[0]) ;
print "\neight field:\n";
print_foreach($tableInfo[3]) ;
print "\nnum_fields:\n";
print "$tableInfo[num_fields]\n" ;
print "\norder:\n";
print_foreach($tableInfo['order']) ;

print "\ntesting tableInfo (DB_TABLEINFO_ORDERTABLE):\n";
$tableInfo =$sth->tableInfo(DB_TABLEINFO_ORDERTABLE);
print "\nfirst field:\n";
print_foreach($tableInfo[0]) ;
print "\neight field:\n";
print_foreach($tableInfo[3]) ;
print "\nnum_fields:\n";
print "$tableInfo[num_fields]\n" ;
print "\nordertable:\n";
print_foreach($tableInfo['ordertable']) ;
print "\nordertable[phptest]:\n";
print_foreach($tableInfo['ordertable']['phptest']) ;
print "\nordertable[phptest_fk]:\n";
print_foreach($tableInfo['ordertable']['phptest_fk']) ;

print "\ntesting tableInfo (table without query-result):\n";

$tableInfo = $dbh->tableInfo("phptest");
print "\nfirst field:\n";
print_foreach($tableInfo[0]) ;
print "\nfourth (last) field:\n";
print_foreach($tableInfo[3]) ;

print "\ntesting tableInfo (table without query-result and DB_TABLEINFO_FULL):\n";

$tableInfo = $dbh->tableInfo("phptest",DB_TABLEINFO_FULL);
print "\nfirst field:\n";
print_foreach($tableInfo[0]) ;
print "\norder:\n";
print_foreach($tableInfo['order']) ;
print "\nordertable:\n";
print_foreach($tableInfo['ordertable']) ;
print "\nordertable[phptest]:\n";
print_foreach($tableInfo['ordertable']['phptest']) ;



//print_foreach($tableInfo[0]) ;
$dbh->query("DELETE FROM phptest WHERE a <> 42");
$dbh->query("DROP TABLE phptest_fk");

function print_foreach ($array) {
    foreach ($array as $key => $value) {
        print "$key => $value \n";
    }

}
?>
