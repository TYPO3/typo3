.. include:: /Includes.rst.txt

=========================================================
Feature: #88799 - Introduced PSR-3 compatible Logging API
=========================================================

See :issue:`88799`

Description
===========

The existing Logging API evolved pretty similar to the later established PSR-3 logging standard.
There was one key difference between the standard and the TYPO3 implementation though:
The log levels were represented by numbers in TYPO3 whereas PSR-3 requires the string representation.

TYPO3 therefore finally adapted to the standard and opens up for better integration with other libraries
also following the standard.


Impact
======

The adaption was not possible without a few breaking changes to the code base.
Luckily no configuration changes are necessary, hence integrators are not affected by those.

.. index:: PHP-API, ext:core
