.. include:: /Includes.rst.txt



.. _index-words-index-rel:

index\_words, index\_rel
^^^^^^^^^^^^^^^^^^^^^^^^

Words-table and word-relation table. Almost self-explanatory. For the
index\_rel table some fields require explanation:


.. _index-words-index-rel-count:

count
"""""

.. container:: table-row

   Field
         count

   Description
         Number of occurrences on the page



.. _index-words-index-rel-first:

first
"""""

.. container:: table-row

   Field
         first

   Description
         How close to the top (low number is better)



.. _index-words-index-rel-freq:

freq
""""

.. container:: table-row

   Field
         freq

   Description
         Frequency (please see source for the calculations. This is converted
         from some floating point to an integer)



.. _index-words-index-rel-flags:

flags
"""""

.. container:: table-row

   Field
         flags

   Description
         Bits, which describes the weight of the words:

         8th bit (128) = word found in title,

         7th bit (64) = word found in keywords,

         6th bit (32) = word found in description,

         Last 5 bits are not used yet, but if used they will enter the weight
         hierarchy. The result rows are ordered by this value if the
         "Weight/Frequency" sorting is selected. Thus results with a hit in the
         title, keywords or description are ranked higher in the result list.


