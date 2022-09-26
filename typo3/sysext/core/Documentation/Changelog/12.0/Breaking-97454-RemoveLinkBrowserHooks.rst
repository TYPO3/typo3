.. include:: /Includes.rst.txt

.. _breaking-97454-1657327622:

=============================================
Breaking: #97454 - Removed Link Browser hooks
=============================================

See :issue:`97454`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['LinkBrowser']['hooks']`
with its two functions :php:`modifyLinkHandlers()` and
:php:`modifyAllowedItems()` has been removed in favor of two new PSR-14 events
:php:`\TYPO3\CMS\Backend\Controller\Event\ModifyLinkHandlersEvent`
and :php:`\TYPO3\CMS\Backend\Controller\Event\ModifyAllowedItemsEvent`.

.. seealso::

    *   :ref:`feature-97454-1657327622`
    *   :ref:`t3coreapi:modifyLinkHandlers`
    *   :ref:`t3coreapi:ModifyLinkHandlersEvent`
    *   :ref:`t3coreapi:ModifyAllowedItemsEvent`

Impact
======

Any hook implementation registered is not executed anymore in
TYPO3 v12.0+. The extension scanner will report possible usages.

Affected Installations
======================

All TYPO3 installations using this hook in custom extension code.

Migration
=========

The hook is removed without deprecation in order to allow extensions
to work with TYPO3 v11 (using the hook) and v12+ (using the new event).

Use the :ref:`PSR-14 event <feature-97454-1657327622>`
as an improved replacement.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
