.. include:: ../../Includes.txt

===============================================================================
Deprecation: #82869 - Replace @inject with @TYPO3\CMS\Extbase\Annotation\Inject
===============================================================================

See :issue:`82869`

Description
===========

The `@inject` annotation has been deprecated and must be replaced with the doctrine annotation `@TYPO3\CMS\Extbase\Annotation\Inject`.


Impact
======

From version 9.0 on, `@inject` is deprecated and will be removed in version 10.


Affected Installations
======================

All extensions that use `@inject` for dependency injection


Migration
=========

Use `@TYPO3\CMS\Extbase\Annotation\Inject` instead.

.. index:: PHP-API, ext:extbase, FullyScanned
