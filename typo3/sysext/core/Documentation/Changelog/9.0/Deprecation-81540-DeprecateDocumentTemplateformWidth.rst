.. include:: /Includes.rst.txt

===========================================================
Deprecation: #81540 - Deprecate DocumentTemplate::formWidth
===========================================================

See :issue:`81540`

Description
===========

The method :php:`DocumentTemplate::formWidth()` has been marked as deprecated.


Impact
======

Calling the method will trigger a deprecation log entry.


Affected Installations
======================

Any installation using third-party extension that call this method.


Migration
=========

Use CSS classes from Bootstrap or if needed inline styles directly.

.. index:: Backend, PHP-API, FullyScanned
