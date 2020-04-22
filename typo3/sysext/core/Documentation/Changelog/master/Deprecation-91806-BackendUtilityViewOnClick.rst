.. include:: ../../Includes.txt

================================================
Deprecation: #91806 - BackendUtility viewOnClick
================================================

See :issue:`91806`

Description
===========

Method :php:`BackendUtility::viewOnClick()` has been obsoleted in late v10
development and is discouraged to be used due to its inline JavaScript.

The method is now deprecated in v11 as advertised with v10 already.


Impact
======

Using the class will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Extensions calling this static method will be affected. The extension
scanner will find all usages as strong match.


Migration
=========

The :php:`\TYPO3\CMS\Backend\Routing\PreviewUriBuilder` should be used
instead as described in this `Changelog`_ file.

.. _`Changelog`: https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/10.4.x/Important-91132-AvoidJavaScriptInUserSettingsConfigurationOptions.html

.. index:: Backend, JavaScript, PHP-API, FullyScanned, ext:backend
