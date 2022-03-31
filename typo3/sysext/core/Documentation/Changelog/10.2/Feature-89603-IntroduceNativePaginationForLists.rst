.. include:: /Includes.rst.txt

=======================================================
Feature: #89603 - Introduce native pagination for lists
=======================================================

See :issue:`89603`

Description
===========

The TYPO3 core provides an interface to implement the native pagination of lists like arrays or
query results of Extbase.

The foundation of that new interface :php:`\TYPO3\CMS\Core\Pagination\PaginatorInterface` is that
it's type agnostic. It means, that it doesn't define the type of paginatable objects. It's up to the
concrete implementations to enable pagination for specific types. The interface only forces you to
reduce the incoming list of items to an :php:`iterable` sub set of items.

Along with that interface, an abstract paginator class :php:`\TYPO3\CMS\Core\Pagination\AbstractPaginator`
has been created that implements the base pagination logic for any kind of :php:`Countable` set of
items while it leaves the processing of items to the concrete paginator class.


Impact
======

Two concrete paginators have been introduced. One for :php:`array` and one for
:php:`\TYPO3\CMS\Extbase\Persistence\QueryResultInterface` objects.

The introduction of this native support for the pagination of lists enables the creation of a new
paginate ViewHelper that is type agnostic.

Code-Example for the :php:`ArrayPaginator`:

.. code-block:: php

   // use TYPO3\CMS\Core\Pagination\ArrayPaginator;
   // use TYPO3\CMS\Core\Pagination\SimplePagination;

   $itemsToBePaginated = ['apple', 'banana', 'strawberry', 'raspberry', 'ananas'];
   $itemsPerPage = 2;
   $currentPageNumber = 3;

   $paginator = new ArrayPaginator($itemsToBePaginated, $currentPageNumber, $itemsPerPage);
   $paginator->getNumberOfPages(); // returns 3
   $paginator->getCurrentPageNumber(); // returns 3, basically just returns the input value
   $paginator->getKeyOfFirstPaginatedItem(); // returns 4
   $paginator->getKeyOfLastPaginatedItem(); // returns 4

   $pagination = new SimplePagination($paginator);
   $pagination->getAllPageNumbers(); // returns [1, 2, 3]
   $pagination->getPreviousPageNumber(); // returns 2
   $pagination->getNextPageNumber(); // returns null

   // …

.. index:: PHP-API, ext:core
