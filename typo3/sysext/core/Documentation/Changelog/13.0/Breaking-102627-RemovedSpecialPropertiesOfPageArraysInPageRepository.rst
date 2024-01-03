.. include:: /Includes.rst.txt

.. _breaking-102627-1704301828:

===============================================================================
Breaking: #102627 - Removed special properties of page arrays in PageRepository
===============================================================================

See :issue:`102627`

Description
===========

When requesting a page with a translation, the following special properties of
an overlaid page have been removed:

:php:`_PAGES_OVERLAY_UID`: The property denounced the UID of the overlaid
database record entry, keeping "uid" as the original uid field.

:php:`_PAGES_OVERLAY`: A boolean flag being set to true if a page was actually
overlaid with a found record.

:php:`_PAGES_OVERLAY_LANGUAGE`: The value of the database record's
"sys_language" field value (the "language ID" of the overlaid record)

:php:`_PAGES_OVERLAY_REQUESTEDLANGUAGE`: A special property used to set the
actual requested language when having multi-level fallbacks while overlaying a
record. When a requested overlay of language=5 is not available, but its
fallback to language=2 is available, this property is set to "5" even though
the page records' "sys_language_uid" field is set to 2.

These special properties have been relevant especially when generating menus,
or when fetching overlays for Extbase domain models, and have been used due
to historical reasons, because translations of pages have been set in
"pages_language_overlay" instead of the database table "pages" until TYPO3 v9.0.

Any other record, where translations have been stored in the database,
received the special property "_LOCALIZED_UID".


Impact
======

When calling :php:`PageRepository->getPage()` or
:php:`PageRepository->getLanguageOverlay()` these special page-related
properties are not set anymore when overlaying a page.


Affected installations
======================

TYPO3 installations with custom extensions working on the low-level API
using these properties.


Migration
=========

The value of the previous :php:`_PAGES_OVERLAY_UID` property is now available
in :php:`_LOCALIZED_UID` making it consistent with all database record overlays
across the system.

The property :php:`_PAGES_OVERLAY` is removed in favor of a
:php:`isset($page['_LOCALIZED_UID')` check instead.

The property :php:`_PAGES_OVERLAY_LANGUAGE` is removed in favor of the property
:php:`$page['sys_language_uid']` which holds the same value.

The property :php:`_PAGES_OVERLAY_REQUESTEDLANGUAGE` is moved to a new property
called :php:`_REQUESTED_OVERLAY_LANGUAGE` which is available now for any kind
of overlaid record, and not just pages.

.. index:: Database, PHP-API, NotScanned, ext:core
