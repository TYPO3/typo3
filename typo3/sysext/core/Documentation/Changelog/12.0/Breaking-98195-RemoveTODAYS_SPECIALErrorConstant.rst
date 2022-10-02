.. include:: /Includes.rst.txt

.. _breaking-98195-1661274247:

=======================================================
Breaking: #98195 - Remove TODAYS_SPECIAL error constant
=======================================================

See :issue:`98195`

Description
===========

The constant :php:`TODAYS_SPECIAL` in :php:`\TYPO3\CMS\Core\SysLog\Error` has never been
used in TYPO3 Core and is therefore removed without replacement.

Impact
======

Third-party extensions using the extension will fail with a PHP error.

Affected installations
======================

3rd party extensions who use the :php:`TODAYS_SPECIAL` constant.

Migration
=========

3rd party extensions using the :php:`TODAYS_SPECIAL` constant should replace
all usages with the integer value `100` or use a custom class with a user
defined constant.

.. index:: Backend, PHP-API, NotScanned, ext:core
