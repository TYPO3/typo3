.. include:: /Includes.rst.txt

.. _breaking-102975-1706525161:

===========================================================
Breaking: #102975 - Use full md5 hashes in `indexed_search`
===========================================================

See :issue:`102975`

Description
===========

For historical reasons an integer representation for castrated md5 hashes has
been used in several places for the `ext:indexed_search` provided database schema
and functionality. This led to conflicts that manifested as "duplicate key" errors.

Therefore, the database fields are transformed to varchar fields and the whole
indexed search codebase changed to work with full md5 hashes now.

Due to the database changes it is necessary to truncate the indexed search tables,
which is done within the database analyzer. Reindexing the data is therefore
required.

Field types of following table fields are changed now:

* `index_phash`: `phash`, `phash_grouping`, `contentHash`
* `index_fulltext`: `phash`
* `index_rel`: `phash`, `wid`
* `index_words`: `wid`
* `index_section`: `phash`, `phash_t3`
* `index_grlist`: `phash`, `phash_x`, `hash_gr_list`
* `index_debug`: `phash`

..  note::

    Remember to reindex your installation to fill the index again.

Impact
======

Installations using the `ext:indexed_search` need to apply a database schema
change which involves the truncation of the corresponding tables and reindex
the installation.

Affected installations
======================

All installations using `EXT:indexed_search` are affected.


Migration
=========

The database analyzer takes care of updating affected columns and truncates
index related tables to be ready for reindexing.


.. index:: Database, NotScanned, ext:indexed_search
