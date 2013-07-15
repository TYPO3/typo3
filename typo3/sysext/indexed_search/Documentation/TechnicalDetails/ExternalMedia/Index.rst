.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _external-media:

External media
^^^^^^^^^^^^^^

External media (pdf, doc, html, txt) is tricky. External media is
always detected as links to local files in the content of a TYPO3 page
which is being indexed. But external media can the linked to from more
than one page. So the index\_section table may hold many entries for a
single external phash-record, one for each position it's found. Also
it's important to notice that external media is only indexed or
updated if a "parent" TYPO3 page is re-indexed. Only then will the
links to the external files be found. In a searching operation
external media will be listed only once (grouping by phash), but say
two TYPO3 pages are linking to the document, then only one of them
will be shown as the path where the link can be found. However if both
TYPO3 pages are not available, then the document will not be shown.

