.. include:: /Includes.rst.txt

.. _breaking-96604:

==============================================================
Breaking: #96604 - Removed ModuleTemplate->addJavaScriptCode()
==============================================================

See :issue:`96604`

Description
===========

The backend module related class method :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate->addJavaScriptCode()`
has been removed.

The method allowed to add JavaScript inline code to the document
body of backend modules. This collides with `Content-Security-Policy` HTTP headers
and needs to be avoided.

The method has been marked :php:`@internal` in late TYPO3 v11 development and
has been removed in v12.

Impact
======

Calling the method in an instance triggers a fatal PHP error.

Affected Installations
======================

The extension scanner finds usage candidates as weak match. In general,
instances with extensions that come with own backend modules may be affected.

Migration
=========

There are various ways to migrate away from inline JavaScript in backend modules, a modern TYPO3 v12
solution is :doc:`JavaScript ES6 modules <Feature-96510-InfrastructureForJavaScriptModulesAndImportmaps>`.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
