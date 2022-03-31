.. include:: /Includes.rst.txt

====================================================================
Deprecation: #86179 - Protected render() method in BackendController
====================================================================

See :issue:`86179`

Description
===========

Method :php:`TYPO3\CMS\Backend\Controller\BackendController->render()` has changed visibility
from public to protected and should not be called any longer.


Impact
======

Calling the method from an external object triggers a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

This internal method is usually not called by extensions directly. Since the method name
is so generic, the extension scanner is not configured to search for usages, it would
trigger far too many false positives.


Migration
=========

Use route target :php:`main` instead that calls method :php:`mainAction` and returns a
proper PSR-7 Response object.

.. index:: Backend, PHP-API, NotScanned
