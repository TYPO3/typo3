.. include:: /Includes.rst.txt

=================================================================
Deprecation: #90377 - Param types $ref of method callUserFunction
=================================================================

See :issue:`90377`

Description
===========

:php:`GeneralUtility::callUserFunction()` accepts a reference variable which is
used to pass on the caller to the called function. Said variable :php:`$ref`
does not have a type hint, therefore it's possible to hand over any type of variable
whilst it's purpose is to only accept objects.


Impact
======

Passing :php:`$ref` into :php:`GeneralUtility::callUserFunction()` with a type other than :php:`object` or :php:`null` will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All installations that pass a non :php:`object` or non :php:`null` type :php:`$ref` variable into :php:`GeneralUtility::callUserFunction()`.


Migration
=========

There is none. :php:`$ref` is meant to be the calling object. Using it to pass arbitrary data to the user function will eventually be forbidden.

.. index:: PHP-API, NotScanned, ext:core
