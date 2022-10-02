.. include:: /Includes.rst.txt

.. _breaking-92508:

=========================================================
Breaking: #92508 - Removed hook for filtering HMENU items
=========================================================

See :issue:`92508`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/tslib/class.tslib_menu.php']['filterMenuPages']`
has been removed in favor of a new PSR-14 event :php:`TYPO3\CMS\Frontend\Event\FilterMenuItemsEvent`.

The event is called with all menu items instead of operating on
one single item.

Impact
======

Any hook implementation registered is not executed anymore
in TYPO3 v12.0+.

Affected Installations
======================

TYPO3 installations with custom menus using this hook.

Migration
=========

The hook is removed without deprecation in order to allow extensions
to work with TYPO3 v11 (using the hook) and v12+ (using the new event).

Use the :doc:`PSR-14 event <../12.0/Feature-92508-PSR-14EventForModifyingMenuItems>`
to allow greater influence in the functionality.

.. index:: Frontend, FullyScanned, ext:frontend
