.. include:: /Includes.rst.txt

=======================================================
Deprecation: #88854 - jumpExt() of RecordListController
=======================================================

See :issue:`88854`

Description
===========

The JavaScript function :js:`jumpExt()` used to modify URLs by attaching `returnUrl` and `anchors`
arguments have been marked as deprecated.

Impact
======

Calling :js:`jumpExt()` will trigger a deprecation warning in the browser console.


Affected Installations
======================

All third party extensions using :js:`jumpExt()` are affected.


Migration
=========

It is only possible to call this function via hooks. To migrate this call, append a `returnUrl` argument to the URL if
required and move the URL to the :html:`href` argument of the button the function was attached to.

.. index:: Backend, JavaScript, PHP-API, NotScanned, ext:backend
