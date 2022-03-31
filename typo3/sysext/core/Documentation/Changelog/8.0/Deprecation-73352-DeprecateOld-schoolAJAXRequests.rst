
.. include:: /Includes.rst.txt

========================================================
Deprecation: #73352 - Deprecate old-school AJAX requests
========================================================

See :issue:`73352`

Description
===========

The class `\TYPO3\CMS\Core\Http\AjaxRequestHandler` and the associated methods
`ExtensionManagementUtility::registerAjaxHandler()` and
`\TYPO3\CMS\Backend\Http\AjaxRequestHandler::dispatchTraditionalAjaxRequest()`
have been marked as deprecated and will be removed in TYPO3 v9.


Impact
======

Calling any of the methods will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 installation with a third-party extensions using the class
`\TYPO3\CMS\Core\Http\AjaxRequestHandler` or calling any of the methods directly.


Migration
=========

Use the Backend Ajax Routes logic instead.

.. index:: JavaScript, Backend, PHP-API
