.. include:: /Includes.rst.txt

================================================
Deprecation: #91806 - BackendUtility viewOnClick
================================================

See :issue:`91806`

Description
===========

Method :php:`BackendUtility::viewOnClick()` is discouraged to be used
due to its inline JavaScript and has been deprecated now.


Impact
======

Using the method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Extensions calling this static method will be affected. The extension
scanner will find usages as strong match.


Migration
=========

The :php:`\TYPO3\CMS\Backend\Routing\PreviewUriBuilder` should be used
instead as described in
:doc:`/Changelog/11.0/Important-91123-AvoidUsingBackendUtilityViewOnClick`.

.. index:: Backend, JavaScript, PHP-API, FullyScanned, ext:backend
