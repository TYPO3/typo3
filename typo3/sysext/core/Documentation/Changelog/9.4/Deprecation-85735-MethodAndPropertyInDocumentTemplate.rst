.. include:: ../../Includes.txt

=====================================================================
Deprecation: #85735 - Various method and property in DocumentTemplate
=====================================================================

See :issue:`85735`

Description
===========

The method :php:`DocumentTemplate::addStyleSheet()` has been marked as deprecated.

The property :php:`DocumentTemplate::hasDocheader` has been marked as protected as the property is not evaluated anymore in the core.


Impact
======

Calling :php:`DocumentTemplate::addStyleSheet()` will trigger a PHP :php:`E_USER_DEPRECATED` error.

Using the property :php:`DocumentTemplate::hasDocheader` will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Instances with third party code directly accessing the method or the property.


Migration
=========

:php:`DocumentTemplate::addStyleSheet()` can be replaced by using :php:`PageRenderer::addCssFile()`.

The property has no migration available.

.. index:: Backend, FullyScanned, PHP-API
