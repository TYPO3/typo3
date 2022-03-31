.. include:: /Includes.rst.txt

================================================
Deprecation: #94684 - GeneralUtility::shortMD5()
================================================

See :issue:`94684`

Description
===========

:php:`\TYPO3\CMS\Core\Utility\GeneralUtility\GeneralUtility::shortMD5()` is a
shorthand method to create an MD5 string trimmed to a defined length, by default
10 characters.

Such shortened checksums are highly susceptible to collisions, thus this method
has been marked as deprecated.


Impact
======

Calling :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5()` will trigger a
PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any extension using :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5()` is
affected. The extension scanner will find usages of that method.


Migration
=========

Use the native :php:`md5()` function to create checksums. In conjunction with
:php:`substr()` the old behavior can be recovered: :php:`substr(md5($string), 0, 10)`.

If checksums are stored in the database, adapt the respective
:file:`ext_tables.sql` file to use :sql:`VARCHAR(32)` for the affected database
fields.

.. index:: Backend, PHP-API, FullyScanned, ext:core
