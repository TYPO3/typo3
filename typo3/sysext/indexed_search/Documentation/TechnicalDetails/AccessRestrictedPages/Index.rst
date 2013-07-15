.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _access-restricted-pages:

Access restricted pages
^^^^^^^^^^^^^^^^^^^^^^^

A TYPO3 page will always be available in the search result only if
there is access to the page. This is secured in the final result
query. Whether extendToSubpages is taken into account depends on the
join\_pages-flag (see above). But the page will only be listed if the
user has access.

However a page may be indexed more than once if the content differs
from usergroup to usergroup or just without login. Still the result
display will display only one occurrence, because similar pages
(determined based on phash\_grouping) will be detected.


.. _access-restricted-tricky:

The tricky scenario
"""""""""""""""""""

Say that a page has a content element with some secret information
visible for only one usergroup. The page as a whole will be visible
for all users. The page will be indexed twice - both without login and
with login because page content differs. The problem is that if a
search is conducted and matching one of the secret words in the access
restricted section, then the page will be in the search result even if
the user is not logged in!

The best solution to this problem is to allow the result to be listed
anyway, but then HIDE the resume if the index\_grlist table cannot
confirm positively that the combination of usergroups of the user has
access to the result. So the result is there, but no resume shown (The
resume might contain hidden text).

.. _access-restricted-media:

External media
""""""""""""""

Equally for external media they are linked from a TYPO3 page. When an
external media is selected we can be sure that the page linking to it
can be selected. But we cannot be sure that the link was in a section
accessible for the user. Similarly we should make a lookup in the
index\_grlist table selecting the phash/gr\_list by the
phash\_t3-value of the section record for the search-result. If this
is not available we should not display a link to the document and not
show resume, but rather link to the page, from which the user can see
the real link to the document.

.. note::

   These tricky scenarios exist only if the content on a page differs
   based on login. It does not affect situations with access restriction
   to the page as a whole. A general lesson from this is to reduce the
   number of hidden content elements! Instead use hidden pages. Better,
   more reliable.

