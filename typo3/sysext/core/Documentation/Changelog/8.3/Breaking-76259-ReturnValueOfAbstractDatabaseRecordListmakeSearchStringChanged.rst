
.. include:: ../../Includes.txt

=======================================================================================
Breaking: #76259 - Return value of AbstractDatabaseRecordList::makeSearchString changed
=======================================================================================

See :issue:`76259`

Description
===========

The value returned by :php:`AbstractDatabaseRecordList::makeSearchString`
has been adjusted.

The SQL fragment no longer includes the leading `AND` SQL operator and the
method returns "1=1" if no search word is specified or if the table contains
no searchable fields.


Impact
======

3rd Party extensions need to ensure that valid SQL queries are being built
using the returned fragment.


Affected Installations
======================

Installations using 3rd party extensions that use :php:`AbstractDatabaseRecordList::makeSearchString`
and expect the leading `AND`.


Migration
=========

Migrate your code to use the Doctrine QueryBuilder where the `AND`
is no longer needed or prepend the missing `AND` before using the
return value.

.. index:: Database, PHP-API, Backend
