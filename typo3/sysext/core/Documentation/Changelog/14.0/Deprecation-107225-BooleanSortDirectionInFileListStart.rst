..  include:: /Includes.rst.txt

..  _deprecation-107225-1754640245:

==================================================================
Deprecation: #107225 - Boolean sort direction in FileList->start()
==================================================================

See :issue:`107225`

Description
===========

The fourth parameter of the method :php:`\TYPO3\CMS\Filelist\FileList::start()`
has been renamed from `$sortRev` to `$sortDirection` and now accepts both boolean
values (for backward compatibility) and
:php-short:`\TYPO3\CMS\Filelist\Type\SortDirection` enum values.

Passing a boolean value for the sort direction (fourth parameter of the method
:php:`\TYPO3\CMS\Filelist\FileList::start()`) has been deprecated in favor of
the new :php:`\TYPO3\CMS\Filelist\Type\SortDirection` enum to provide better
type safety and clarity. The parameter name has also been changed from
:php:`$sortRev` to :php:`$sortDirection` to better reflect its purpose.

Impact
======

Calling :php:`FileList::start()` with a boolean value as the fourth parameter
will trigger a deprecation warning. The functionality will continue to work
but will be removed in TYPO3 v15.

Affected installations
======================

All installations using :php:`FileList::start()` directly with a boolean
value for the sort direction parameter. This mainly affects custom file
browser implementations or extensions that directly instantiate and configure
the FileList class, even if it is actually marked as `@internal`.

Migration
=========

Replace boolean values with the corresponding :php:`SortDirection` enum values:

.. code-block:: php

    use TYPO3\CMS\Filelist\Type\SortDirection;

    // Before (deprecated)
    $fileList->start($folder, $currentPage, $sortField, false, $mode);
    $fileList->start($folder, $currentPage, $sortField, true, $mode);

    // After
    $fileList->start($folder, $currentPage, $sortField, SortDirection::ASCENDING, $mode);
    $fileList->start($folder, $currentPage, $sortField, SortDirection::DESCENDING, $mode);

The migration maintains the same functionality:

* :php:`true` (descending) → :php:`SortDirection::DESCENDING`
* :php:`false` (ascending) → :php:`SortDirection::ASCENDING`

..  index:: Backend, FileList, PartiallyScanned, ext:filelist
