.. include:: ../../Includes.txt

========================================================================================
Deprecation: #77296 - Deprecate public member parentMenuArr in AbstractMenuContentObject
========================================================================================

See :issue:`77296`

Description
===========

The previously undefined member `parentMenuArr` has been added as public member and marked as deprecated.


Impact
======

The parentMenuArr will be publicly accessible until it is changed to protected in TYPO3 v9.


Affected Installations
======================

Instances that have menus with sublevels and using this member in the itemArrayProcFunc.


Migration
=========

Use the provided API function :php:`getParentMenuArr()` to get the parentMenuArr instead.
This method always returns an array.

If you need the direct parent menuitem of the current sublevel use :php:`getParentMenuItem()` method.

.. index:: Frontend, PHP-API
