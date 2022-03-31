.. include:: /Includes.rst.txt



.. _html-content:

HTML content
^^^^^^^^^^^^

HTML content is weighted by the indexing engine in this order:

#. <title>-data

#. <meta-keywords>

#. <meta-description>

#. <body>

In addition you can insert markers as HTML comments which define which
part of the body-text to include or exclude in the indexing:

The marker is :code:`<!--TYPO3SEARCH_begin-->` or
:code:`<!--TYPO3SEARCH_end-->`.

Rules:

#. If there is no marker at all, everything is included.

#. If the first found marker is an "end" marker, the previous content
   until that point is included and the preceding code until next
   "begin" marker is excluded.

#. If the first found marker is a "begin" marker, the previous content
   until that point is excluded and preceding content until next "end"
   marker is included.

#. If there are multiple marker pairs in HTML, content from in between
   all pairs is included.
