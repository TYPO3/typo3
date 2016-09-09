
.. include:: ../../Includes.txt

=====================================================
Breaking: #69148 - Backend Module Dispatching removed
=====================================================

See :issue:`69148`

Description
===========

Dispatching Backend modules through custom dispatchers have been removed. The corresponding Extbase functionality
called "ModuleRunner" and its Interface have been removed as well.


Impact
======

Any dispatcher registered via `$TBE_MODULES['_dispatcher']` is not evaluated anymore.


Affected Installations
======================

All TYPO3 Instances with an extension that registers a custom backend module dispatcher.


Migration
=========

Use a custom RequestHandler.
