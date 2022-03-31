.. include:: /Includes.rst.txt

===================================================================
Deprecation: #86192 - Protected methods in ElementBrowserController
===================================================================

See :issue:`86192`

Description
===========

The following methods of class :php:`TYPO3\CMS\Recordlist\Controller\ElementBrowserController`
changed their visibility from public to protected and should not be called any longer:

* :php:`main()`


Impact
======

Calling the above method from an external object will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

The method is called internally only. Extensions are usually not affected by this.


Migration
=========

Use the entry method :php:`mainAction()` that returns a PSR-7 response object.

.. index:: Backend, PHP-API, NotScanned, ext:recordlist
