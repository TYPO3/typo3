
.. include:: ../../Includes.txt

===============================================================================
Breaking: #68562 - Bool values need to be cast to integer for MySQL strict mode
===============================================================================

See :issue:`68562`

Description
===========

MySQL strict mode doesn't accept '' as a valid value to store in an integer
column if the MySQL server is running in strict mode.

mysqli_real_escape() casts boolean values to string using '1' (for `TRUE`)
and '' (for `FALSE`). Due to this special handling is required for boolean
values to result in '0' and '1' for FALSE/TRUE.


Impact
======

All TYPO3 CMS installations using MySQL as DBMS.


Affected Installations
======================

Installations where 3rd party extension are relying on `FALSE` being cast to ''
when they are storing boolean values in character type columns. In this case new
values will get stored as '0'


Migration
=========

Adjust the code to either store boolean values in integer type columns or
manually cast the boolean value to string before storing it in the database.


.. index:: PHP-API, Database
