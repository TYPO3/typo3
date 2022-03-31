.. include:: /Includes.rst.txt

==========================================================
Deprecation: #88428 - top.rawurlencode and top.str_replace
==========================================================

See :issue:`88428`

Description
===========

The global JavaScript functions :js:`top.rawurlencode()` and :js:`top.str_replace()` have been marked as deprecated.


Impact
======

Calling any of these two functions will trigger a deprecation log entry in the browser's console.


Affected Installations
======================

All installations using third party extensions with these functions are affected.


Migration
=========

For :js:`top.rawurlencode()` it's safe to use native JavaScript function :js:`encodeURIComponent()` instead. The only
difference is that this function does not escape asterisk characters, which may be additionally achieved via
:js:`encodeURIComponent('*my_string*').replace(/\*/g, '%2A')`.

For :js:`top.str_replace()` consider using JavaScript's string function `.replace()` instead.

.. index:: Backend, JavaScript, NotScanned, ext:backend
