.. include:: /Includes.rst.txt
.. _index-fulltext:

===============
index\_fulltext
===============

For free text searching, e.g. with a sentence, in all content: title,
description, keywords, body.


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

Currently the MySQL FULLTEXT search is not used (something with MATCH
... AGAINST), but this will be added in the future.
