
.. include:: ../../Includes.txt

=====================================================
Deprecation: #69028 - RelationHandler convertPosNeg()
=====================================================

See :issue:`69028`


Description
===========

Method `convertPosNeg()` of class `TYPO3\CMS\Core\Database\RelationHandler` has been marked as deprecated.


Impact
======

The method should not be used any longer and will be removed with TYPO3 CMS 8.


Affected Installations
======================

The method is rather internal and relatively unlikely to be used by third party modules.
Searching for the string `convertPosNeg` may reveal possible usages.


Migration
=========

The method was used together with the dropped `neg_foreign_table` setting for `TCA` `select`
fields. If this functionality is still needed, the method could be copied over to the third party
application that uses it.


.. index:: PHP-API, Backend
