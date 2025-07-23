.. include:: /Includes.rst.txt

.. _breaking-33747-1737642868:

===================================================================
Breaking: #33747 - Remove non-implemented sortable Collection logic
===================================================================

See :issue:`33747`

Description
===========

The :php:`SortableCollectionInterface` has been removed from TYPO3 Core.

This interface was never properly implemented and served no purpose in the
codebase. It defined methods for sorting collections via callback functions
and moving items within collections, but no concrete implementations existed.

The interface defined the following methods:

- :php:`usort($callbackFunction)` - For sorting collection via given callback function
- :php:`moveItemAt($currentPosition, $newPosition = 0)` - For moving items within the collection

Impact
======

Any code that implements or references :php:`\TYPO3\CMS\Core\Collection\SortableCollectionInterface`
will cause PHP fatal errors.

Since this interface was never implemented in TYPO3 Core and had no real-world usage,
the impact should be minimal for most installations.

Affected installations
======================

Installations with custom extensions that implement or reference the
:php:`SortableCollectionInterface` are affected.

Migration
=========

Remove any references to :php:`\TYPO3\CMS\Core\Collection\SortableCollectionInterface`
from your code.

If you need sortable collection functionality, implement your own sorting logic
directly in your collection classes or use PHP's built-in array sorting functions
like :php:`usort()`, :php:`uasort()`, or :php:`uksort()`.

.. index:: PHP-API, FullyScanned
