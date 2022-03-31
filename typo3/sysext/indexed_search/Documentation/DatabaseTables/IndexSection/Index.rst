.. include:: /Includes.rst.txt



.. _index-section:

index\_section
^^^^^^^^^^^^^^

Points out the section where an entry in index\_phash belongs.


.. _index-section-phash:

phash
"""""

.. container:: table-row

   Field
         phash

   Description
         The phash of the indexed document.



.. _index-section-phash-t3:

phash\_t3
"""""""""

.. container:: table-row

   Field
         phash\_t3

   Description
         The phash of the "parent" TYPO3 page of the indexed document.

         If the "document" being indexed is a TYPO3 page, then phash and
         phash\_t3 are the same.

         But if the document is an external file (PDF, Word etc) which are
         found as a LINK on a TYPO3 page, then this phash\_t3 points to the
         phash of that TYPO3 page. Normally it goes like this when indexing: 1)
         The TYPO3 document is indexed (this has a phash-value of course), then
         2) if any external files are found on the page, they are indexed as
         well AND their phash\_t3 will become the phash of the TYPO3 page they
         were on.

         The significance of this value is that indexed external files may have
         more than one record in "index\_section" (with the same phash), a
         record for each parent page where a link to the document was found!
         There are details about this in the section of this document that
         describes the complexities of indexing pages.



.. _index-section-rl0:

rl0
"""

.. container:: table-row

   Field
         rl0

   Description
         The id of the root-page of the site.



.. _index-section-rl1:

rl1
"""

.. container:: table-row

   Field
         rl1

   Description
         The id of the level-1 page (if any) of the indexed page.



.. _index-section-rl2:

rl2
"""

.. container:: table-row

   Field
         rl2

   Description
         The id of the level-2 page (if any) of the indexed page.



.. _index-section-page-id:

page\_id
""""""""

.. container:: table-row

   Field
         page\_id

   Description
         The page id of the indexed page.



.. _index-section-uniqid:

uniqid
""""""

.. container:: table-row

   Field
         uniqid

   Description
         This is just an autoincremented unique, primary key. Generally not
         used (i think)


