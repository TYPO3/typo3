.. include:: ../../Includes.txt

===============================================================================
Deprecation: #83078 - Replace @lazy with @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
===============================================================================

See :issue:`83078`

Description
===========

The `@lazy` annotation has been deprecated and must be replaced with the doctrine annotation `@TYPO3\CMS\Extbase\Annotation\ORM\Lazy`.


Impact
======

From version 9.0 on, `@lazy` is deprecated and will be removed in version 10.


Affected Installations
======================

All extensions that use `@lazy`


Migration
=========

Use `@TYPO3\CMS\Extbase\Annotation\ORM\Lazy` instead.

.. index:: PHP-API, ext:extbase, FullyScanned
