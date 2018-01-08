.. include:: ../../Includes.txt

=========================================================
Deprecation: #83511 - Deprecate AbstractValidatorTestcase
=========================================================

See :issue:`83511`

Description
===========

The class AbstractValidatorTestcase is deprecated and will be removed in TYPO3 version 10.0.


Impact
======

Test cases for validators that extend that class will no longer work.


Affected Installations
======================

All installations that make use of that class


Migration
=========

Put the logic of that test case into your own test cases.

.. index:: PHP-API, PartiallyScanned
