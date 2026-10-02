..  include:: /Includes.rst.txt

..  _feature-98988-1790210934:

===========================================================
Feature: #98988 - Index records on change in indexed_search
===========================================================

See :issue:`98988`

Description
===========

Indexing configurations of type "Database Records" provide the option
"Index Records immediately when saved?" (`records_indexonchange`).
Since the removal of the EXT:crawler integration in TYPO3 v11 this option
has had no effect anymore.

EXT:indexed_search now takes care of this on its own. When a record of the
configured table is saved in the backend, it is indexed right away, using the
fields configured in the indexing configuration. Hiding or deleting a record
removes it from the index again. Deleting a page removes its index, as does
hiding it or marking it as "no search".

The indexing is done by the new internal service
:php:`\TYPO3\CMS\IndexedSearch\Service\RecordIndexer`, which works with
the Record API.

Additionally, clearing the cache of a page now only removes the index of the
page's own content, which is created when the page is rendered. Entries
created by indexing configurations on that page are kept.

Impact
======

Records covered by an indexing configuration with the option "Index Records
immediately when saved?" enabled are searchable as soon as an editor saves
them, without any further indexing run.

..  index:: Backend, ext:indexed_search
