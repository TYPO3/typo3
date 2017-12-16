
.. include:: ../../Includes.txt

===============================================================================
Breaking: #67204 - DatabaseConnection::exec_SELECTgetRows() may throw exception
===============================================================================

See :issue:`67204`

Description
===========

`DatabaseConnection::exec_SELECTgetRows()` validates `$uidIndexField` parameter now.
If the specified field is not present in the database result an `InvalidArgumentException` is thrown.


Impact
======

This change will affect only broken usages of `DatabaseConnection::exec_SELECTgetRows()` with an invalid last
parameter.

It is very unlikely that existing code affected by this change, since using the method in a wrong way had the
consequence that it only returned the last row from the result.


Affected Installations
======================

Any code using the `DatabaseConnection::exec_SELECTgetRows()` method with `$uidIndexField` being set to a field
name not present in the queried result set.


Migration
=========

Fix your call to the method and correct the `$uidIndexField` parameter.
