..  include:: /Includes.rst.txt

..  _breaking-111510-1795159809:

===========================================================================
Breaking: #111510 - CSS class of previous link in indexed_search pagination
===========================================================================

See :issue:`111510`

Description
===========

The pagination partial of EXT:indexed_search rendered the "previous" list item
with the CSS class :css:`tx-indexedsearch-browselist-next`, the same class as
the "next" list item.

The "previous" list item now uses the CSS class
:css:`tx-indexedsearch-browselist-prev`, which was also used by the former PHP
based pagination before TYPO3 v13.0.

Impact
======

The frontend output of the search result pagination has changed. The "previous"
list item no longer matches :css:`.tx-indexedsearch-browselist-next`.

Affected installations
======================

Installations that style or select the list item of the "previous" link in the
indexed_search pagination via :css:`tx-indexedsearch-browselist-next`, for
example with custom CSS or JavaScript, as the class was used by both the
"previous" and the "next" list item.

Migration
=========

Adjust custom CSS or JavaScript to also target the new class for the "previous"
list item:

..  code-block:: css
    :caption: EXT:my_sitepackage/Resources/Public/Css/indexed-search.css

    /* Before */
    .tx-indexedsearch-browselist-next {
        margin-left: 1rem;
    }

    /* After */
    .tx-indexedsearch-browselist-prev,
    .tx-indexedsearch-browselist-next {
        margin-left: 1rem;
    }

..  index:: Frontend, NotScanned, ext:indexed_search
