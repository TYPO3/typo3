.. include:: /Includes.rst.txt

.. _breaking-98375-1663598608:

===============================================
Breaking: #98375 - Removed hooks in Page Module
===============================================

See :issue:`98375`

Description
===========

Since TYPO3 v10, TYPO3 Backend's Page Module is based on Fluid and custom
rendering functionality. The internal class "PageLayoutView" is now removed,
along with its interfaces and hooks.

The following hooks are removed with a PSR-14 equivalent:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['record_is_used']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][PageLayoutView::class]['modifyQuery']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']`

The following hooks have been removed without substitution:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawFooter']`

Existing patterns such as the PreviewRenderer concept can be used instead of
the latter hooks.

Impact
======

Registering one of the hooks above in TYPO3 v12+ has no effect anymore.

Affected installations
======================

TYPO3 installations with modifications to the page module in third-party
extensions via one of the hooks.

Migration
=========

Use :php:`TYPO3\CMS\Backend\View\Event\IsContentUsedOnPageLayoutEvent` as a
drop-in alternative for :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['record_is_used']`

Use :php:`TYPO3\CMS\Backend\View\Event\ModifyDatabaseQueryForContentEvent`
as a drop-in replacement for :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][PageLayoutView::class]['modifyQuery']`

Use :php:`TYPO3\CMS\Backend\View\Event\PageContentPreviewRenderingEvent` as a
drop-in replacement for :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']`

Extension authors that use these hooks can register a new event listener
and keep the hook registration to stay compatible with TYPO3 v11 and TYPO3 v12
at the same time.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
