
.. include:: /Includes.rst.txt

=========================================================
Breaking: #74029 - Remove ModuleLoader->getRelativePath()
=========================================================

See :issue:`74029`

Description
===========

The method `ModuleLoader->getRelativePath()` has been removed. It was previously part when registering
traditional script-based modules, which did not use the new Icon API for the backend.


Impact
======

Calling the method above directly will result in a fatal PHP error.


Affected Installations
======================

Any installation working with extensions that set up the ModuleLoader class and call the method `getRelativePath()` directly.


Migration
=========

Use `PathUtility::getRelativePath()` when the functionality is still needed.

.. index:: PHP-API, Backend
