.. include:: /Includes.rst.txt

======================================================
Deprecation: #87200 - EmailFinisher FORMAT_* constants
======================================================

See :issue:`87200`

Description
===========

The constants

* :php:`TYPO3\CMS\Form\Domain\Finishers\EmailFinisher::FORMAT_PLAINTEXT` and
* :php:`TYPO3\CMS\Form\Domain\Finishers\EmailFinisher::FORMAT_HTML`

have been marked as deprecated and will be removed in TYPO3 11.0.


Impact
======

Accessing these constants will lead to a fatal :php:`E_ERROR` in TYPO3 11.0.


Affected Installations
======================

All installations which use EXT:form and directly access these constants.


Migration
=========

Do not use these constants anymore.

.. index:: PHP-API, FullyScanned, ext:form
