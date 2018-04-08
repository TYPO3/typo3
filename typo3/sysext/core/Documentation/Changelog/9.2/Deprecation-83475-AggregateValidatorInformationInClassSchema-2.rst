.. include:: ../../Includes.txt

=====================================================================
Deprecation: #83475 - Aggregate validator information in class schema
=====================================================================

See :issue:`83475`

Description
===========

The method `\TYPO3\CMS\Extbase\Validation\ValidatorResolver::buildMethodArgumentsValidatorConjunctions` has been marked
as deprecated and will be removed in TYPO3 v10.0


Impact
======

The method was not considered public API and it is unlikely that the methods is used in the wild. If you rely on that
method, you will need to implement the logic yourself.


Affected Installations
======================

All installations that use `\TYPO3\CMS\Extbase\Validation\ValidatorResolver::buildMethodArgumentsValidatorConjunctions`.


Migration
=========

There is no migration

.. index:: PHP-API, FullyScanned
