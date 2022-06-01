.. include:: /Includes.rst.txt

=============================================
Breaking: #97454 - Removed Link Browser hooks
=============================================

See :issue:`97454`

Description
===========

The hooks array :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['LinkBrowser']['hooks']` has been
removed in favor of new PSR-14 Events :php:`\TYPO3\CMS\Recordlist\Event\ModifyLinkHandlersEvent`
and :php:`\TYPO3\CMS\Recordlist\Event\ModifyAllowedItemsEvent`.

Impact
======

Any hook implementation registered is not executed anymore in
TYPO3 v12.0+. The extension scanner will report possible usages.

Affected Installations
======================

All TYPO3 installations using this hook in custom extension code.

Migration
=========

The hooks are removed without deprecation in order to allow extensions
to work with TYPO3 v11 (using the hook) and v12+ (using the new Event)
when implementing the Event as well without any further deprecations.
Use the :doc:`PSR-14 Event <../12.0/Feature-97454-PSR-14EventsForLinkBrowserLifecycle>`
as an improved replacement.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
