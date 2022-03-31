.. include:: /Includes.rst.txt

============================================================
Deprecation: #86178 - Class ElementBrowserFramesetController
============================================================

See :issue:`86178`

Description
===========

Class :php:`TYPO3\CMS\Recordlist\Controller\ElementBrowserFramesetController` and the route
target of :php:`browser` have been marked as deprecated and should not be used any longer.


Impact
======

If calling that controller class a PHP :php:`E_USER_DEPRECATED` error is triggered.


Affected Installations
======================

The route target is unused in core for a while already. Extensions are only affected
if they call the Backend route target :php:`browser` that renders the element browser
in a frameset.


Migration
=========

Use the modal based element browser with the route :php:`wizard_element_browser` instead.

.. index:: Backend, PHP-API, NotScanned, ext:recordlist
