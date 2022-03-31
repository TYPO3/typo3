.. include:: /Includes.rst.txt

==============================================================
Breaking: #88741 - cHash calculation in indexed search removed
==============================================================

See :issue:`88741`

Description
===========

When indexing a page, the indexer of `Indexed search` previously kept the used cHash and the
used "cHashParams" for storing search entries. This is not necessary anymore, as Site Handling now
contains the relevant arguments already in the search entry as well. This can be removed now.

In addition, when setting up a Indexing configuration, the option to respect cHash is removed,
as this is done automatically when needed.

The public property :php:`TYPO3\CMS\IndexedSearch\Indexer->cHashParams` has been removed.

The sixth method argument of :php:`TYPO3\CMS\IndexedSearch\Indexer->backend_initIndexer()`
has been removed.

The following database fields are unused and have been removed:

* :sql:`index_config.chashcalc`
* :sql:`index_phash.cHashParams`

The database field :sql:`index_debug.debuginfo` now contains data stored in a JSON-formatted string
instead of a serialized PHP string.


Impact
======

Manual database queries accessing the database fields will result in SQL errors.

In addition, accessing the removed property or using the sixth argument of the changed public method
will have no effect anymore.


Affected Installations
======================

TYPO3 installations using Indexed Search and custom configuration or extending functionality
of Indexed Search.


Migration
=========

No migration needed, as everything works as before. The data is now stored in
the database field as JSON-encoded string `index_phash.static_page_arguments`.

In case of using debug information for Indexed Search (index with enabled debug information),
where data was previously stored in `index_debug.debuginfo` as serialized PHP string,
indexing needs to be rebuilt, but only to render the debug information properly in the TYPO3 Backend
module. If debug information is not enabled, re-indexing is not necessary.

.. index:: Database, PHP-API, PartiallyScanned, ext:indexed_search
