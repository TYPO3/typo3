.. include:: ../../Includes.txt

===========================================================================
Feature: #87610 - New FAL API to search for files including their meta data
===========================================================================

See :issue:`87610`

Description
===========

A new API is introduced to search for files in a storage or folder, which includes matches in meta data of those files.
The given search term is looked for in all search fields defined in TCA of `sys_file` and `sys_file_metadata` tables.

A new driver capability :php:`\TYPO3\CMS\Core\Resource\ResourceStorageInterface::CAPABILITY_HIERARCHICAL_IDENTIFIERS`
is introduced to allow implementing an optimized search with good performance.
Drivers can optionally add this capability in case the identifiers that are constructed by the driver
include the directory structure.
Adding this capability to drivers can provide a big performance boost
when it comes to recursive search (which is default in the file list and file browser UI).

Impact
======

This change is fully backwards compatible. Custom driver implementations will continue to work like before,
but they won't benefit from the performance gain unless the new capability is added.

Searching for files in a folder works like this:

.. code-block:: php

   $searchDemand = FileSearchDemand::createForSearchTerm($searchWord)->withRecursive();
   $files = $folder->searchFiles($searchDemand);

Searching for files in a complete storage works like this:

.. code-block:: php

   $searchDemand = FileSearchDemand::createForSearchTerm($searchWord)->withRecursive();
   $files = $storage->searchFiles($searchDemand);

It is possible to further limit the result set, by adding additional restrictions to :php:`TYPO3\CMS\Core\Resource\Folder\FileSearchDemand`.
Please note, that :php:`TYPO3\CMS\Core\Resource\Folder\FileSearchDemand` is an immutable value object, but allows chaining methods for ease of use:

.. code-block:: php

   $searchDemand = FileSearchDemand::createForSearchTerm($this->searchWord)
       ->withRecursive()
       ->withMaxResults(10)
       ->withOrdering('fileext');
   $files = $storage->searchFiles($searchDemand);


.. index:: Backend, PHP-API, ext:filelist
