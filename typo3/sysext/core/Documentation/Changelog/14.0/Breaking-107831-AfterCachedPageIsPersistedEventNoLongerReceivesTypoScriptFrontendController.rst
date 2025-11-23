..  include:: /Includes.rst.txt

..  _breaking-107831-1761307146:

===================================================================================================
Breaking: #107831 - AfterCachedPageIsPersistedEvent no longer receives TypoScriptFrontendController
===================================================================================================

See :issue:`107831`

Description
===========

The frontend rendering related event
:php:`\TYPO3\CMS\Frontend\Event\AfterCachedPageIsPersistedEvent` has been
modified due to the removal of the class
:php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController`.

The method :php:`getController()` has been removed.

Impact
======

Event listeners that call :php:`getController()` will now trigger a fatal
PHP error and and must be adapted.

Affected installations
======================

Instances with extensions listening for the event
:php-short:`\TYPO3\CMS\Frontend\Event\AfterCachedPageIsPersistedEvent` may be
affected.

The extension scanner will detect and report such usages.

Migration
=========

In most cases, data that was previously retrieved from the
:php-short:`\TYPO3\CMS\Frontend\Controller\`TypoScriptFrontendController`
instance can now be accessed through the request object, available via
:php:`$event->getRequest()`.

See :ref:`breaking-102621-1701937690` for further details about accessing
frontend-related data via the PSR-7 request.

..  index:: Frontend, NotScanned, ext:frontend
