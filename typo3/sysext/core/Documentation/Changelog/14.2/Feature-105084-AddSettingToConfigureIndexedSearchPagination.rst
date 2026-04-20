..  include:: /Includes.rst.txt

..  _feature-105084-1771501624:

=====================================================================
Feature: #105084 - Add setting to configure indexed_search pagination
=====================================================================

See :issue:`105084`

Description
===========

A new TypoScript setting
:typoscript:`plugin.tx_indexedsearch.settings.pagination_type` has been
introduced to set the pagination implementation used by
`EXT:indexed_search`.

Available values:

*   :typoscript:`simple`: uses
    :php-short:`\TYPO3\CMS\Core\Pagination\SimplePagination` and renders
    all result pages.
*   :typoscript:`slidingWindow`: uses
    :php-short:`\TYPO3\CMS\Core\Pagination\SlidingWindowPagination` and
    limits the displayed page links as set in
    :typoscript:`plugin.tx_indexedsearch.settings.page_links`.

The default is :typoscript:`simple` to preserve existing behavior.
Integrators can switch to :typoscript:`slidingWindow` to make
:typoscript:`page_links` effective for indexed_search result browsing.

Impact
======

Integrators can now switch between core pagination implementations using
TypoScript, without having to use custom PHP code.

Advanced, fully-customized pagination logic can still be implemented using
:php-short:`\TYPO3\CMS\IndexedSearch\Event\ModifySearchResultSetsEvent`.

..  index:: Frontend, TypoScript, ext:indexed_search
