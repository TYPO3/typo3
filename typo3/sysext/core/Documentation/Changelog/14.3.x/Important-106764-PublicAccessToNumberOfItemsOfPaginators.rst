..  include:: /Includes.rst.txt

..  _important-106764-1790697600:

=================================================================
Important: #106764 - Public access to number of items of paginators
=================================================================

See :issue:`106764`

Description
===========

The abstract class :php:`\TYPO3\CMS\Core\Pagination\AbstractPaginator`
provides two new public methods:

*   :php:`getTotalNumberOfItems()` returns the total number of all items,
    regardless of the current page.
*   :php:`getNumberOfItemsOnCurrentPage()` returns the number of items on
    the current page.

Both values have been calculated by the paginators before, but were only
accessible from within the paginator classes. As all paginators of the Core,
for example :php:`\TYPO3\CMS\Core\Pagination\ArrayPaginator`,
:php:`\TYPO3\CMS\Core\Pagination\QueryBuilderPaginator` and
:php:`\TYPO3\CMS\Extbase\Pagination\QueryResultPaginator`, extend this class,
the information is available for them without an additional query, for
example in a Fluid template:

..  code-block:: html

    Showing {paginator.numberOfItemsOnCurrentPage} of
    {paginator.totalNumberOfItems} records

Custom paginators extending :php:`AbstractPaginator` provide the new methods
automatically. Custom paginators which already implement methods with the
same names must ensure these are public and return an integer.

..  index:: PHP-API, ext:core
