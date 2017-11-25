.. include:: ../../Includes.txt

=====================================================================================
Deprecation: #83093 - Replace @cascade with @TYPO3\CMS\Extbase\Annotation\ORM\Cascade
=====================================================================================

See :issue:`83093`

Description
===========

The `@cascade` annotation has been deprecated and must be replaced with the doctrine annotation `@TYPO3\CMS\Extbase\Annotation\ORM\Cascade`.


Impact
======

From version 9.0 on, `@cascade` is deprecated and will be removed in version 10.


Affected Installations
======================

All extensions that use `@cascade`


Migration
=========

Use `@TYPO3\CMS\Extbase\Annotation\ORM\Cascade` instead.

A tyical example has been `@cascade remove` which is now `@TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")`

.. index:: PHP-API, ext:extbase, FullyScanned
