.. include:: ../../Includes.txt

==========================================================
Breaking: #78581 - FlexFormTools public properties dropped
==========================================================

See :issue:`78581`

Description
===========

Two public properties have been dropped from PHP class :php:`FlexFormTools`:

* :php:`FlexFormTools->traverseFlexFormXMLData_DS`
* :php:`FlexFormTools->traverseFlexFormXMLData_Data`


Impact
======

Accessing those properties will throw a warning.


Affected Installations
======================

Extensions that access these properties. These two properties were of little use from an extensions point of view,
it is very unlikely this actually breaks an extension.


Migration
=========

No migration possible.

.. index:: PHP-API, FlexForm, Backend