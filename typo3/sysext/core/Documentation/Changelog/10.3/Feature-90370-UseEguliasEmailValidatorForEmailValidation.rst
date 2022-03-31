.. include:: /Includes.rst.txt

=================================================================
Feature: #90370 - Use Egulias\EmailValidator for email validation
=================================================================

See :issue:`90370`

Description
===========

:php:`\TYPO3\CMS\Core\Utility\GeneralUtility::validEmail` now uses the package `Egulias\EmailValidator` and the `RFCValidation` for validating the provided email address.

This allows to follow the RFC more closely.


Impact
======

The following email addresses are now valid:

- `foo@äöüfoo.com`
- `foo@bar.123`
- `test@localhost`
- `äöüfoo@bar.com`
- `Abc@def"@example.com`

.. index:: PHP-API, ext:core
