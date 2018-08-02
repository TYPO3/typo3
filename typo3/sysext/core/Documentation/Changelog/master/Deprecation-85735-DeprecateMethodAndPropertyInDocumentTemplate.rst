.. include:: ../../Includes.txt

=======================================================================
Deprecation: #85735 - Deprecate method and property in DocumentTemplate
=======================================================================

See :issue:`85735`

Description
===========

The method :php:`addStyleSheet` in the class :php:`DocumentTemplate` has been marked as deprecated.

The property :php:`$hasDocheader` is marked as protected as the property is not evaluated anymore in the core.


Impact
======

Calling the :php:`addStyleSheet` method will trigger a deprecation warning.

Using the property :php:`$hasDocheader` will trigger a deprecation warning.


Affected Installations
======================

Calling the method or property will trigger a deprecation warning.


Migration
=========

The :php:`addStyleSheet` method can be replaced by using :php:`PageRenderer::addCssFile()`

The property has no migration available.

.. index:: Backend, FullyScanned