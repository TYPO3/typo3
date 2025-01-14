.. include:: /Includes.rst.txt

.. _breaking-102925-1706182267:

======================================================
Breaking: #102925 - Template changes in Indexed Search
======================================================

See :issue:`102925`

Description
===========

Due to some major refactorings within EXT:indexed_search, Fluid templates in the
frontend plugins were adapted.


Impact
======

In case Fluid templates of EXT:indexed_search are overridden, the rendered output
may look different and behave unpleasant.


Affected installations
======================

All installations overriding Fluid templates of EXT:indexed_search are affected.


Migration
=========

Pagination
----------

The pagination ViewHelpers have been removed in favor of native pagination API
shipped with TYPO3. Usages of the ViewHelpers `is:pageBrowsingResults` and
`is:pageBrowsing` have been removed.

The Fluid template file `Private/Templates/Search/Search.html` loads a new
JavaScript via :html:`<f:asset.script>`:

..  code-block:: html

    <f:asset.script useNonce="true" identifier="indexed_search_pagination" src="EXT:indexed_search/Resources/Public/JavaScript/pagination.js" />


`is:pageBrowsingResults` has been replaced with a short HTML snippet:

..  code-block:: html

    <f:sanitize.html>
        <f:translate key="displayResults" arguments="{0: result.pagination.startRecordNumber, 1: result.pagination.endRecordNumber, 2: result.count}" />
    </f:sanitize.html>

`is:pageBrowsing` has been replaced with a new Fluid partial file:

..  code-block:: html

    <f:render partial="Pagination" arguments="{pagination: result.pagination, searchParams: searchParams, freeIndexUid: freeIndexUid}" />

Search result items
-------------------

The following options are now passed to the `Searchresult` partial:

* `row: row`
* `searchParams: searchParams`
* `firstRow: firstRow`

The `Searchresult` partial now registers the `is` namespace for Fluid ViewHelpers:

..  code-block:: html

    <html
        xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
        xmlns:is="http://typo3.org/ns/TYPO3/CMS/IndexedSearch/ViewHelpers"
        data-namespace-typo3-fluid="true">


Within the `Searchresult` partial, `{row.rating}` has been replaced with a
ViewHelper invocation:

..  code-block:: html

    {is:searchResult.rating(firstRow: firstRow, sortOrder: searchParams.sortOrder, row: row)}


Rules
-----

Remove any overrides for the partial file :file:`Resources/Private/Partials/Rules.html`,
as well as the :html:`<f:render partial="Rules" />` invocation from a potentially
overridden :file:`Resources/Private/Partials/Form.html` partial file.

.. index:: Fluid, Frontend, NotScanned, ext:indexed_search
