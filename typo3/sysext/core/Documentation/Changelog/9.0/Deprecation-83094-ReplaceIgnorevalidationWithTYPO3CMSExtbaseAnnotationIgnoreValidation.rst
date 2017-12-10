.. include:: ../../Includes.txt

===================================================================================================
Deprecation: #83094 - Replace @ignorevalidation with @TYPO3\CMS\Extbase\Annotation\IgnoreValidation
===================================================================================================

See :issue:`83094`

Description
===========

The :php:`@ignorevalidation` annotation has been deprecated and must be replaced with the doctrine annotation
:php:`@TYPO3\CMS\Extbase\Annotation\IgnoreValidation`.


Impact
======

From version 9.0 on, :php:`@ignorevalidation` is deprecated and will be removed in version 10.


Affected Installations
======================

All extensions that use :php:`@ignorevalidation`


Migration
=========

Use :php:`@TYPO3\CMS\Extbase\Annotation\IgnoreValidation` instead.

.. index:: PHP-API, ext:extbase, FullyScanned
