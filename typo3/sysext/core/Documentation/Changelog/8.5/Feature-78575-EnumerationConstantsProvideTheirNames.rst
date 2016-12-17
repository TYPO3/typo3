.. include:: ../../Includes.txt

=================================================================
Feature: #78575 - Enumeration constants don't provide their names
=================================================================

See :issue:`78575`

Description
===========

Requesting the name of Enumeration constants has been introduced.

There are two methods you can use:
:php:`MyEnumerationClass::getName($value);` will return the name exactly as it is. Usually this will be all uppercase and underscores.
:php:`MyEnumerationClass::getHumanReadableName($value);` replaces underscores with spaces and turns all words into lowercase with the first letter uppercase.


Impact
======

You can use the constant names as part of your application more easily.

.. index:: PHP-API
