.. include:: ../../Includes.txt

================================================================
Deprecation: #86180 - Protected methods in SetupModuleController
================================================================

See :issue:`86180`

Description
===========

The following methods of class :php:`TYPO3\CMS\Setup\Controller\SetupModuleController`
changed their visibility from public to protected and should not be called any longer:

* [not scanned] :php:`main()`
* [not scanned] :php:`init()`
* :php:`storeIncomingData()`


Impact
======

Calling one of the above methods from an external object will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

These methods are usually called internally only, extensions should not be affected by this.


Migration
=========

Use the entry method :php:`mainAction()` that returns a PSR-7 response object.

.. index:: Backend, PHP-API, PartiallyScanned, ext:setup