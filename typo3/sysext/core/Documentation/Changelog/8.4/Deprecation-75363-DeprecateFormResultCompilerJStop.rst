.. include:: /Includes.rst.txt

===========================================================
Deprecation: #75363 - Deprecate FormResultCompiler->JStop()
===========================================================

See :issue:`75363`

Description
===========

The method `JStop()` has been renamed to `addCssFiles()`. The old method name is
still present as a deprecated alias, which will be removed in TYPO3 v9.

Keep in mind that the method reads "JS top", not "J Stop".


Impact
======

Calling `JStop()` method will trigger a deprecation log entry.


Affected Installations
======================

Any extensions calling `JStop()`.


Migration
=========

Instead of :php:`JStop()` use :php:`addCssFiles()`.

.. index:: PHP-API, Backend
