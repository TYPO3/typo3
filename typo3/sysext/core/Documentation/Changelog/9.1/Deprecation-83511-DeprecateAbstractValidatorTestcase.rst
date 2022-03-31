.. include:: /Includes.rst.txt

=========================================================
Deprecation: #83511 - Deprecate AbstractValidatorTestcase
=========================================================

See :issue:`83511`


Description
===========

The class AbstractValidatorTestcase has been marked as deprecated and will be removed in CMS 10.


Impact
======

Test cases for validators that extend that class will no longer work.


Affected Installations
======================

All installations that make use of that class


Migration
=========

Put the logic of that test case into your own test cases.

.. index:: PHP-API, FullyScanned
