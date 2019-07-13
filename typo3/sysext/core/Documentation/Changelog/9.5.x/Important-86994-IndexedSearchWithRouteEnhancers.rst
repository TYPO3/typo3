.. include:: ../../Includes.txt

======================================================================
Important: #86994 - Indexed Search indexes pages using route enhancers
======================================================================

See :issue:`86994`

Description
===========

Because of pages that use route enhancers do not use `cHash` functionality in most cases, it is necessary to add the
static arguments of the indexed page to generate the phash values used by `indexed_search`.

For administration of the indexed pages, the static arguments of the page need to be stored in the `index_phash`
database table as well, which makes their enhancing inevitable.

In order to make indexed search work with Site Handling, update database schema using the Database Analyzer in maintenance
module to add the necessary database field. Once done, the search index needs to be rebuilt.

.. index:: Backend, Database, Frontend, ext:indexed_search
