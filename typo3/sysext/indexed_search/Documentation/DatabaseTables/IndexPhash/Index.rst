.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _index-phash:

index\_phash
^^^^^^^^^^^^

This table contains references to TYPO3 pages or external documents.
The fields are like this:


.. _index-phash-phash:

phash
"""""

.. container:: table-row

   Field
         phash

   Description
         7md5/int hash. It's an integer based on a 7-char md5-hash.

         This is a unique representation of the 'page' indexed.

         For TYPO3 pages this is a serialization of id,type,gr\_list (see
         later), MP and cHashParams (which enables 'subcaching' with extra
         parameters). This concept is also used for TYPO3 caching (although the
         caching hash includes the all-array and thus takes the template into
         account, which this hash does not! It's expected that template changes
         through conditions would not seriously alter the page content)

         For external media this is a serialization of 1) unique filename id,
         2) any subpage indication (parallel to cHashParams). gr\_list is NOT
         taken into consideration here!



.. _index-phash-phash-grouping:

phash\_grouping
"""""""""""""""

.. container:: table-row

   Field
         phash\_grouping

   Description
         7md5/int hash.

         This is a non-unique hash exactly like phash, but WITHOUT the gr\_list
         and (in addition) for external media without subpage indication. Thus
         this field will indicate a 'unique' page (or file) while this page may
         exist twice or more due to gr\_list. Use this field to GROUP BY the
         search so you get only one hit per page when selecting with gr\_list
         in mind.

         Currently a seach result does not either group or limit by this, but
         rather the result display may group the result into logical units.



.. _index-phash-item-mtime:

item\_mtime
"""""""""""

.. container:: table-row

   Field
         item\_mtime

   Description
         Modification time:

         For TYPO3 pages: the SYS\_LASTCHANGED value

         For external media: The filemtime() value.

         Depending on config, if mtime hasn't changed compared to this value
         the file/page is not indexed again.



.. _index-phash-tstamp:

tstamp
""""""

.. container:: table-row

   Field
         tstamp

   Description
         time stamp of the indexing operation. You can configure min/max ages
         which are checked with this timestamp.

         A min-age defines how long an indexed page must be indexed before it's
         reconsidered to index it again.

         A max-age defines an absolute point at which re-indexing will occur
         (unless the content has not changed according to an md5-hash)



.. _index-phash-chashparams:

cHashParams
"""""""""""

.. container:: table-row

   Field
         cHashParams

   Description
         The cHashParams.

         For TYPO3 pages: These are used to re-generate the actual url of the
         TYPO3 page in question

         For files this is an empty array. Not used.



.. _index-phash-item-type:

item\_type
""""""""""

.. container:: table-row

   Field
         item\_type

   Description
         An integer indicating the content type,

         0 is TYPO3 pages

         1- external files like pdf (2), doc (3), html (1), txt (4) and so on.
         See the class.indexer.php file



.. _index-phash-item-title:

item\_title
"""""""""""

.. container:: table-row

   Field
         item\_title

   Description
         Title:

         For TYPO3 pages, the page title

         For files, the basename of the file (no path)



.. _index-phash-item-description:

item\_description
"""""""""""""""""

.. container:: table-row

   Field
         item\_description

   Description
         Short description of the item. Top information on the page. Used in
         search result.



.. _index-phash-data-page-id:

data\_page\_id
""""""""""""""

.. container:: table-row

   Field
         data\_page\_id

   Description
         For TYPO3 pages: The id



.. _index-phash-data-page-type:

data\_page\_type
""""""""""""""""

.. container:: table-row

   Field
         data\_page\_type

   Description
         For TYPO3 pages: The type



.. _index-phash-data-filename:

data\_filename
""""""""""""""

.. container:: table-row

   Field
         data\_filename

   Description
         For external files: The filepath (relative) or URL (not used yet)



.. _index-phash-contenthash:

contentHash
"""""""""""

.. container:: table-row

   Field
         contentHash

   Description
         md5 hash of the content indexed. Before reindexing this is compared
         with the content to be indexed and if it matches there is obviously no
         need for reindexing.



.. _index-phash-crdate:

crdate
""""""

.. container:: table-row

   Field
         crdate

   Description
         The creation date of the INDEXING - not the page/file! (see
         item\_crdate)



.. _index-phash-parsetime:

parsetime
"""""""""

.. container:: table-row

   Field
         parsetime

   Description
         The parsetime of the indexing operation.



.. _index-phash-sys-language-uid:

sys\_language\_uid
""""""""""""""""""

.. container:: table-row

   Field
         sys\_language\_uid

   Description
         Will contain the value of GLOBALS["TSFE"]->sys\_language\_uid, which
         tells us the language of the page indexed.



.. _index-phash-item-crdate:

item\_crdate
""""""""""""

.. container:: table-row

   Field
         item\_crdate

   Description
         The creation date. For files only the modification date can be read
         from the files, so here it will be the filemtime().



.. _index-phash-gr-list:

gr\_list
""""""""

.. container:: table-row

   Field
         gr\_list

   Description
         Contains the gr\_list of the user initiating the indexing of the
         document.


