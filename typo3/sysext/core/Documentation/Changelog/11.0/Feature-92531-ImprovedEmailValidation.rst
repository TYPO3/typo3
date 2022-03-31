.. include:: /Includes.rst.txt

===========================================
Feature: #92531 - Improved Email Validation
===========================================

See :issue:`92531`

Description
===========

The method :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::validEmail()` is used to
validate a given email address through the core and TYPO3 extensions.

The validation can now be configured by providing the used validators in
the :file:`LocalConfiguration.php` or :file:`AdditionalConfiguration.php`:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['MAIL']['validators'] = [
     \Egulias\EmailValidator\Validation\RFCValidation::class,
     \Egulias\EmailValidator\Validation\DNSCheckValidation::class
   ];

By default, the validator :php:`\Egulias\EmailValidator\Validation\RFCValidation`
is used. The following validators are available by default:

- :php:`\Egulias\EmailValidator\Validation\DNSCheckValidation`
- :php:`\Egulias\EmailValidator\Validation\SpoofCheckValidation`
- :php:`\Egulias\EmailValidator\Validation\NoRFCWarningsValidation`

Additionally it is possible to provide an own implementation by implementing the
interface :php:`\Egulias\EmailValidator\Validation\EmailValidation`.

If multiple validators are provided, each validator must return `TRUE`.


Impact
======

Using additional validators can help to identify if a provided email address is
valid or not.

.. index:: LocalConfiguration, PHP-API, ext:core
