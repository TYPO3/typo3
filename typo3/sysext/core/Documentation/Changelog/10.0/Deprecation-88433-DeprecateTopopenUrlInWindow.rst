.. include:: /Includes.rst.txt

===================================================
Deprecation: #88433 - Deprecate top.openUrlInWindow
===================================================

See :issue:`88433`

Description
===========

The global JavaScript function :js:`top.openUrlInWindow()` has been marked as deprecated. This method was used to open
links in a full size popup.


Impact
======

Calling this function will trigger a deprecation log entry in the browser's console.


Affected Installations
======================

All installations using third party extensions that use :js:`top.openUrlInWindow()` are affected.


Migration
=========

Instead of using this method, consider using plain HTML and open the link in a new tab:
:html:`<a href="/path/to/my/document", target="_blank">Linked content</a>`

.. index:: Backend, JavaScript, NotScanned, ext:backend
