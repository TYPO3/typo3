.. include:: /Includes.rst.txt

==================================================
Deprecation: #95322 - Legacy Element Browser logic
==================================================

See :issue:`95322`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/browse_links.php']['browserRendering']`
has been marked as deprecated as it has been superseded by the :php:`ElementBrowser` API,
introduced in TYPO3 v7.6.

Calling the backend routing endpoint "wizard_element_browser"
called via :html:`?mode=wizard` or :html:`?mode=rte` has been marked as deprecated.


Impact
======

Calling the backend routing endpoint "wizard_element_browser"
called via :html:`?mode=wizard` or :html:`?mode=rte` will trigger a PHP :php:`E_USER_DEPRECATED` error.

Accessing the Element Browser with a registered hook will also
trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with legacy code (such as an old element
browser hook) or with old links to "wizard_element_browser"
prior to TYPO3 v8 which hasn't been updated yet.


Migration
=========

Use the Element Browser API, introduced in TYPO3 v7.6 instead of the
deprecated hook
`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/browse_links.php']['browserRendering']`.

Instead of referencing "wizard_element_browser" for accessing
the wizard, the link wizard with BE Routing Endpoint "wizard_link"
should be used.

.. index:: Backend, PHP-API, PartiallyScanned, ext:recordlist
