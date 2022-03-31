.. include:: /Includes.rst.txt

======================================================================
Deprecation: #86193 - Protect methods in AbstractLinkBrowserController
======================================================================

See :issue:`86193`

Description
===========

The following methods changed their visibility from public to protected and
should not be called any longer:

* :php:`TYPO3\CMS\Recordlist\Controller\AbstractLinkBrowserController->renderLinkAttributeFields()`
* :php:`TYPO3\CMS\Recordlist\Controller\AbstractLinkBrowserController->getDisplayedLinkHandlerId()`
* :php:`TYPO3\CMS\RteCKEditor\Controller\BrowseLinksController->renderLinkAttributeFields()`
* :php:`TYPO3\CMS\RteCKEditor\Controller\BrowseLinksController->getPageConfigLabel()`
* :php:`TYPO3\CMS\RteCKEditor\Controller\BrowseLinksController->getDisplayedLinkHandlerId()`


Impact
======

Calling one of the above methods from an external object will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

These link browser specific methods are usually not used by extensions externally. The extension
scanner will reveal possible usages.


Migration
=========

No migration possible.

.. index:: Backend, PHP-API, FullyScanned, ext:reports
