.. include:: ../../Includes.txt

===============================================================
Deprecation: #86182 - Protected methods in TaskModuleController
===============================================================

See :issue:`86182`

Description
===========

The following methods of class :php:`TYPO3\CMS\Taskcenter\Controller\TaskModuleController`
changed their visibility from public to protected and should not be called any longer:

* [not scanned] :php:`main()`
* :php:`urlInIframe()` Will be removed in TYPO3 v10

Impact
======

Calling one of the above methods from an external object triggers a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

The :php:`main()` methods is usually called internally only, extensions should not be affected by this.
Method :php:`urlInIframe()` has been used by extensions that delivered task center tasks that have
not been updated for a long time, the method is deprecated and usage should be removed.


Migration
=========

No migration possible.

.. index:: Backend, PHP-API, PartiallyScanned, ext:taskcenter