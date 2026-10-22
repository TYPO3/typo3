.. include:: /Includes.rst.txt

.. _deprecation-85735:

=====================================================================
Deprecation: #85735 - Various method and property in DocumentTemplate
=====================================================================

See :issue:`85735`

Description
===========

The method :php-short:`\TYPO3\CMS\Backend\Template\DocumentTemplate::addStyleSheet()` has been marked as deprecated.

The property :php-short:`\TYPO3\CMS\Backend\Template\DocumentTemplate::hasDocheader` has been marked as protected as the property is not evaluated anymore in the core.


Impact
======

Calling :php-short:`\TYPO3\CMS\Backend\Template\DocumentTemplate::addStyleSheet()` will trigger a PHP :php:`E_USER_DEPRECATED` error.

Using the property :php-short:`\TYPO3\CMS\Backend\Template\DocumentTemplate::hasDocheader` will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Instances with third party code directly accessing the method or the property.


Migration
=========

:php-short:`\TYPO3\CMS\Backend\Template\DocumentTemplate::addStyleSheet()` can be replaced by using :php-short:`\TYPO3\CMS\Core\Page\PageRenderer::addCssFile()`.

The property has no migration available.

.. index:: Backend, FullyScanned, PHP-API
