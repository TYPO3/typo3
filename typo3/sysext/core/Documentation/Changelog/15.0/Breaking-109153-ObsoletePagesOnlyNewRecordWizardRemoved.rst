.. include:: /Includes.rst.txt

.. _breaking-109153-1781184782:

=================================================================
Breaking: #109153 - Obsolete pages-only new record wizard removed
=================================================================

See :issue:`109153`

Description
===========

The page creation wizard introduced with :issue:`108915` replaced the legacy
"pages only" position selection flow of the records module. Its now unused
predecessors have been removed:

*   The backend route :php:`db_new_pages` (path ``/record/new-page``).
*   The page TSconfig option
    :typoscript:`mod.wizards.newRecord.pages.show.pageSelectPosition`.

Impact
======

Linking to the :php:`db_new_pages` route raises an error. The page TSconfig
option :typoscript:`mod.wizards.newRecord.pages.show.pageSelectPosition` no longer
has any effect.

Affected installations
======================

Instances with third-party extensions that link to the :php:`db_new_pages`
route, or set the :typoscript:`pageSelectPosition` page TSconfig option.
Such extensions are quite rare, and a left over page TSconfig shouldn't harm.

Migration
=========

Use the page creation wizard instead. New pages are created through the
:php:`db_new` route and the ``typo3-backend-new-page-wizard-button`` component,
which guide position and page type selection. No replacement for
:php:`PagePositionMap` or the :typoscript:`pageSelectPosition` option is needed.

.. index:: Backend, PHP-API, TSConfig, NotScanned, ext:backend
