
.. include:: /Includes.rst.txt

=======================================================================
Breaking: #77557 - Signature of QueryView->getQueryResultCode() changed
=======================================================================

See :issue:`77557`

Description
===========

The method signature of :php:`QueryView->getQueryResultCode()` has changed
from :php:`getQueryResultCode($mQ, $res, $table)` to :php:`getQueryResultCode($type, array $dataRows, $table)`.

The second argument is no longer a MySQLi or DBAL result object, but an array of rows.

Impact
======

Extensions using this method will throw a fatal error.


Affected Installations
======================

Extensions using :php:`QueryView->getQueryResultCode()`


Migration
=========

Move away from the method or feed it with an array of database rows.

.. index:: PHP-API, Database
