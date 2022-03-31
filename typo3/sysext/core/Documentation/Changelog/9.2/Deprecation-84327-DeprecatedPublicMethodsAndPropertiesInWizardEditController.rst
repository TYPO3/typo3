.. include:: /Includes.rst.txt

=======================================================================================
Deprecation: #84327 - Deprecated public methods and properties in Wizard/EditController
=======================================================================================

See :issue:`84327`

Description
===========

This file is about third party usage (consumer that call the class as well as
signals or hooks depending on it) of :php:`TYPO3\CMS\Backend\Controller\Wizard\EditController`.

A series of class properties has been set to protected.
They will throw deprecation warnings if called public from outside:

* [not scanned] :php:`$P`
* :php:`$doClose`

The following method will be refactored/set to protected in v10 and should no longer be used:

* [not scanned] :php:`main()`


Impact
======

Calling one of the above methods or accessing one of the above properties on an instance of
:php:`Wizard/EditController` will throw a deprecation warning in v9 and a PHP fatal in v10.


Affected Installations
======================

The extension scanner will detect only detect usage of :php:`$doClose`, other calls are not scanned to prevent false positives.


Migration
=========

In general, extensions should not instantiate and re-use controllers of the core. Existing
usages should be rewritten to be free of calls like these.

.. index:: Backend, PHP-API, PartiallyScanned, ext:backend
