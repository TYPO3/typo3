.. include:: /Includes.rst.txt

.. _feature-98375-1663598746:

==============================================
Feature: #98375 - PSR-14 events in Page Module
==============================================

See :issue:`98375`

Description
===========

Three new PSR-14 events have been added to TYPO3's page module to modify
the preparation and rendering of content elements:

* :php:`TYPO3\CMS\Backend\View\Event\IsContentUsedOnPageLayoutEvent`
* :php:`TYPO3\CMS\Backend\View\Event\ModifyDatabaseQueryForContentEvent`
* :php:`TYPO3\CMS\Backend\View\Event\PageContentPreviewRenderingEvent`

They are drop-in replacement to the removed hooks:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['record_is_used']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][PageLayoutView::class]['modifyQuery']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']`

Impact
======

Use :php:`IsContentUsedOnPageLayoutEvent` to identify if a content has been used
in a column that isn't on a Backend Layout.

Use :php:`ModifyDatabaseQueryForContentEvent` to filter out certain content elements
from being shown in the Page Module.

Use :php:`PageContentPreviewRenderingEvent` to ship an alternative rendering for
a specific content type or to manipulate the content elements' record data.

.. index:: Backend, PHP-API, ext:backend
