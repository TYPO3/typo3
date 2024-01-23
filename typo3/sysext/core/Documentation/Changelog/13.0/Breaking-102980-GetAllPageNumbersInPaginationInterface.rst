.. include:: /Includes.rst.txt

.. _breaking-102980-1706534274:

==============================================================
Breaking: #102980 - getAllPageNumbers() in PaginationInterface
==============================================================

See :issue:`102980`

Description
===========

A method has been added to :php:`\TYPO3\CMS\Core\Pagination\PaginationInterface`
with this signature: :php:`public function getAllPageNumbers(): array;`. It should
return a list of all available page numbers.

The method has already been implemented in
:php:`\TYPO3\CMS\Core\Pagination\SimplePagination` and
:php:`\TYPO3\CMS\Core\Pagination\SlidingWindowPagination`.


Impact
======

Custom implementations of :php:`PaginationInterface` must implement the method.


Affected installations
======================

Instances with extensions that provide own pagination classes that implement
:php:`PaginationInterface` may be affected.



Migration
=========

See the two Core classes :php:`SimplePagination` and :php:`SlidingWindowPagination`
for examples on how the method is implemented.


.. index:: PHP-API, NotScanned, ext:core
