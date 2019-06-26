.. include:: ../../Includes.txt

===================================================================================
Deprecation: #82869 - Replace @inject with @TYPO3\\CMS\\Extbase\\Annotation\\Inject
===================================================================================

See :issue:`82869`

Description
===========

The :php:`@inject` annotation has been deprecated and must be replaced with the doctrine annotation
:php:`@TYPO3\CMS\Extbase\Annotation\Inject`.


Impact
======

From version 9.0 on, :php:`@inject` is deprecated and will be removed in version 10.


Affected Installations
======================

All extensions that use :php:`@inject` for dependency injection


Migration
=========

Use :php:`@TYPO3\CMS\Extbase\Annotation\Inject` instead.

.. index:: PHP-API, ext:extbase, FullyScanned
