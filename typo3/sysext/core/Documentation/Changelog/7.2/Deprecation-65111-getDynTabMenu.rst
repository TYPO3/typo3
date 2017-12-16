
.. include:: ../../Includes.txt

===================================
Deprecation: #65111 - getDynTabMenu
===================================

See :issue:`65111`

Description
===========

The DocumentTemplate method `getDynTabMenu()` has been marked as deprecated.


Impact
======

The method has been refactored and renamed. The new method `getDynamicTabMenu()` should be used.
The method `getDynTabMenu()` is now marked as deprecated.


Affected installations
======================

All installations which make use of `DocumentTemplate::getDynTabMenu()`


Migration
=========

Use `ModuleTemplate::getDynamicTabMenu()` instead of `DocumentTemplate::getDynTabMenu()`
