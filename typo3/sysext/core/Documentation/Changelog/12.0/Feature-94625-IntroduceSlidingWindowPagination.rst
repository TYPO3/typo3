.. include:: /Includes.rst.txt

.. _feature-94625:

=====================================================
Feature: #94625 - Introduce sliding window pagination
=====================================================

See :issue:`94625`

Description
===========

Since TYPO3 v10 a new `Pagination API <https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Pagination/Index.html>`__
is shipped, which supersedes the pagination widget controller, which had
been removed in TYPO3 v11.

This patch provides an improved pagination which can be used to paginate array
items or query results from Extbase. The main advantage is that it reduces the
amount of pages shown.

**Example**: Imagine 1000 records and 20 items per page which would lead to
50 links. Using the `SlidingWindowPagination`, you will get something like
`< 1 2 ... 21 22 23 24 ... 100 >`.

Usage
=====

Just replace the usage of :php:`SimplePagination` with
:php:`\TYPO3\CMS\Core\Pagination\SlidingWindowPagination` and you are done.
Set the 2nd argument to the maximum number of links which should be rendered.

..  code-block:: php

    use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
    use TYPO3\CMS\Core\Pagination\SlidingWindowPagination

    $currentPage = $this->request->hasArgument('currentPage')
       ? (int)$this->request->getArgument('currentPage')
       : 1;
    $itemsPerPage = 10;
    $maximumLinks = 15;

    $paginator = new QueryResultPaginator(
       $allItems,
       $currentPage,
       $itemsPerPage
    );
    $pagination = new SlidingWindowPagination(
       $paginator,
       $maximumLinks
    );

    $this->view->assign(
       'pagination',
       [
          'pagination' => $pagination,
          'paginator' => $paginator
       ]
    );

Credits
=======

This patch is loosely based on the "`numbered_pagination <https://github.com/georgringer/numbered_pagination>`__"
extension by Georg Ringer.

Thanks to him.

.. index:: PHP-API, ext:core
