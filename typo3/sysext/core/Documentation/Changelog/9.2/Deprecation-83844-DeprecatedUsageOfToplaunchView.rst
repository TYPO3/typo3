.. include:: ../../Includes.txt

========================================================
Deprecation: #83844 - Deprecated usage of top.launchView
========================================================

See :issue:`83844`

Description
===========

The usage of :js:`top.launchView()`, that opens certain information in a popup window, has been marked as deprecated.


Impact
======

Calling :js:`top.launchView()` will trigger a deprecation warning in the browser console.


Affected Installations
======================

Every 3rd party extension that uses :js:`top.launchView` is affected.


Migration
=========

Either use :js:`top.TYPO3.InfoWindow.showItem()` directly or import the RequireJS module `TYPO3/CMS/Backend/InfoWindow`
and call :js:`showItem()`.

.. index:: Backend, JavaScript, NotScanned
