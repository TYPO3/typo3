.. include:: /Includes.rst.txt

================================================
Deprecation: #83853 - Backend AjaxRequestHandler
================================================

See :issue:`83853`

Description
===========

The class :php:`\TYPO3\CMS\Backend\Http\AjaxRequestHandler` has been marked as deprecated and will be removed in TYPO3 v10.
This functionality has been moved into the backend's generic Request Handler functionality.

The AJAX functionality itself is not deprecated and can be used as before.


Impact
======

Installations that use :php:`\TYPO3\CMS\Backend\Http\AjaxRequestHandler` will trigger a deprecation warning.


Affected Installations
======================

All installations that use custom extensions that add classes derived from :php:`\TYPO3\CMS\Backend\Http\AjaxRequestHandler`.


Migration
=========

Use a PSR-15 middleware for the Backend Middleware Stack or extend from the generic
:php:`\TYPO3\CMS\Backend\Http\RequestHandler` instead.

.. index:: Backend, PHP-API, FullyScanned
