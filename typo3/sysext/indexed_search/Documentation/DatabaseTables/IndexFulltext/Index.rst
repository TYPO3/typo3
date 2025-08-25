.. include:: /Includes.rst.txt
.. _index-fulltext:

===============
index\_fulltext
===============

For free text searching, e.g. with a sentence, in all content: title,
description, keywords, body.

This table is used when `basic.useMysqlFulltext` extension configuration
is enabled.


.. _index-fulltext-phash:

phash
=====

..  versionchanged:: 13.0
    The field has been transformed to a varchar field, full md5 hashes are
    stored.

.. container:: table-row

   Field
         phash

   Description
         The md5 hash of the indexed document.



.. _index-fulltext-fulltextdata:

fulltextdata
============

.. container:: table-row

   Field
         fulltextdata

   Description
         The total content stripped for any HTML codes.
