.. include:: ../../Includes.txt

==========================================================================================
Deprecation: #57385 - Deprecate parameter $caseSensitive of Extbase Query->like comparison
==========================================================================================

See :issue:`57385`

Description
===========

The argument :php:`$caseSensitive` of the method :php:`Query->like` has been marked as deprecated.


Impact
======

Using the argument will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 installation using custom calls to :php:`Query->like` using the mentioned argument.


Migration
=========

For MySQL change the collation of the queried field to be stored in a case sensitive fashion.
This requires using a collation with a suffix of `_cs` for the field or table. Alternatively
a binary column type can be used. Both solutions will ensure the field will be queried in a
case sensitive fashion.

.. index:: Database, PHP-API, ext:extbase
