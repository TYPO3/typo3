.. include:: /Includes.rst.txt

==============================================================================
Deprecation: #84375 - Protected methods and properties in PageLayoutController
==============================================================================

See :issue:`84375`

Description
===========

Some methods in the :php:`TYPO3\CMS\Backend\Controller\PageLayoutController`
have been marked as deprecated and will be removed in TYPO3 v10:

* [not scanned] :php:`init()`
* [not scanned] :php:`main()`
* [not scanned] :php:`menuConfig()`
* [not scanned] :php:`renderContent()`
* [not scanned] :php:`clearCache()`
* [not scanned] :php:`getModuleTemplate()`
* :php:`getLocalizedPageTitle()`
* :php:`getNumberOfHiddenElements()`
* :php:`local_linkThisScript()`
* :php:`pageIsNotLockedForEditors()`
* :php:`contentIsNotLockedForEditors()`

Likewise some properties have been marked as deprecated:

* [not scanned] :php:`pointer`
* [not scanned] :php:`imagemode`
* [not scanned] :php:`search_field`
* [not scanned] :php:`search_levels`
* [not scanned] :php:`showLimit`
* [not scanned] :php:`returnUrl`
* [not scanned] :php:`clear_cache`
* :php:`popView`
* [not scanned] :php:`perms_clause`
* [not scanned] :php:`modTSconfig`
* :php:`modSharedTSconfig`
* [not scanned] :php:`descrTable`
* :php:`colPosList`
* :php:`EDIT_CONTENT`
* :php:`CALC_PERMS`
* :php:`current_sys_language`
* :php:`MCONF`
* :php:`MOD_MENU`
* [not scanned] :php:`content`
* :php:`activeColPosList`


Impact
======

Accessing the properties or calling the methods will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Third party code which accesses the properties directly or calls the methods.


Migration
=========

In general, extensions should not instantiate and re-use controllers of the core. Existing
usages should be rewritten to be free of calls like these.

.. index:: Backend, PHP-API, PartiallyScanned
