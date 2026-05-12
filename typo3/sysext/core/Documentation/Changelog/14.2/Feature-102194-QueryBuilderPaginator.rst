..  include:: /Includes.rst.txt

..  _feature-102194-1772779432:

==================================================
Feature: #102194 - Introduce QueryBuilderPaginator
==================================================

See :issue:`102194`

Description
===========

A new :php:`\TYPO3\CMS\Core\Pagination\QueryBuilderPaginator` is introduced to
enable pagination of
:php-short:`\TYPO3\CMS\Core\Database\Query\QueryBuilder` instances.

The paginator implements the existing
:php-short:`\TYPO3\CMS\Core\Pagination\PaginatorInterface` and integrates
seamlessly with the existing
:php-short:`\TYPO3\CMS\Core\Pagination\SimplePagination` and
:php-short:`\TYPO3\CMS\Core\Pagination\SlidingWindowPagination` classes.

The paginated items are fetched only once per page request by storing the
result internally, avoiding double execution of the database statement.

The total item count is determined robustly using a common table expression
(CTE) wrapping the passed :php:`QueryBuilder` instance. This approach correctly
handles advanced queries involving `UNION`, nested CTEs, window functions, and
grouping.

..  note::

    The :php-short:`\TYPO3\CMS\Core\Pagination\QueryBuilderPaginator` does
    **not** handle language overlays. Applying overlays to the result set can
    lead to unexpected item count differences between pages when some records
    are hidden after overlay processing. Use
    :php-short:`\TYPO3\CMS\Extbase\Pagination\QueryResultPaginator` or
    :php-short:`\TYPO3\CMS\Core\Pagination\ArrayPaginator` when language
    overlay handling is required.

    The paginator also takes **full control** over `LIMIT` and `OFFSET`
    and does not respect any existing limit or offset constraints on the
    passed :php:`QueryBuilder` instance.

Impact
======

A new :php-short:`\TYPO3\CMS\Core\Pagination\QueryBuilderPaginator` is
available to paginate
:php-short:`\TYPO3\CMS\Core\Database\Query\QueryBuilder` result sets using
the TYPO3 pagination API.

Example
-------

..  code-block:: php
    :caption: EXT:my_extension/Classes/Controller/MyController.php

    use TYPO3\CMS\Core\Pagination\QueryBuilderPaginator;
    use TYPO3\CMS\Core\Pagination\SimplePagination;

    $paginator = new QueryBuilderPaginator(
        queryBuilder: $queryBuilder,
        currentPageNumber: $currentPage,
        itemsPerPage: 10,
    );
    $pagination = new SimplePagination($paginator);

    // Retrieve the items for the current page
    $items = $paginator->getPaginatedItems();

..  index:: Database, PHP-API, ext:core
