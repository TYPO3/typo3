--TEST--
DB_driver::row limit
--SKIPIF--
<?php chdir(dirname(__FILE__)); require_once './skipif.inc'; ?>
--FILE--
<?php
require_once './connect.inc';
require_once '../limit.inc';
?>
--EXPECT--
======= From: 0 || Number of rows to fetch: 10 =======
1.- result 0
2.- result 1
3.- result 2
4.- result 3
5.- result 4
6.- result 5
7.- result 6
8.- result 7
9.- result 8
10.- result 9
======= From: 10 || Number of rows to fetch: 10 =======
11.- result 10
12.- result 11
13.- result 12
14.- result 13
15.- result 14
16.- result 15
17.- result 16
18.- result 17
19.- result 18
20.- result 19
======= From: 20 || Number of rows to fetch: 10 =======
21.- result 20
22.- result 21
23.- result 22
24.- result 23
25.- result 24
26.- result 25
27.- result 26
28.- result 27
29.- result 28
30.- result 29
======= From: 30 || Number of rows to fetch: 10 =======
31.- result 30
32.- result 31
33.- result 32
