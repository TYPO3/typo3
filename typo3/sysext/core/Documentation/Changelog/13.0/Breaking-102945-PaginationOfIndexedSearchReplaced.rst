.. include:: /Includes.rst.txt

.. _breaking-102945-1706274593:

=========================================================
Breaking: #102945 - Pagination of Indexed Search replaced
=========================================================

See :issue:`102945`

Description
===========

Indexed Search used a custom crafted pagination, implemented with several
ViewHelpers known as `is:pageBrowsingResults` and `is:pageBrowsing`.
These ViewHelpers have been removed in favor of the existing Pagination API,
leading to template changes.


Impact
======

In case Fluid templates of EXT:indexed_search are overridden, the frontend will
render an exception due to the missing ViewHelpers.


Affected installations
======================

All installations overriding the Fluid template `Templates/Search/Search.html`
of EXT:indexed_search are affected.


Migration
=========

`is:pageBrowsingResults` has been replaced with a short HTML snippet:

..  code-block:: html

    <f:sanitize.html>
        <f:translate key="displayResults" arguments="{0: result.pagination.startRecordNumber, 1: result.pagination.endRecordNumber, 2: result.count}" />
    </f:sanitize.html>

`is:pageBrowsing` has been replaced with a new Fluid partial file:

..  code-block:: html

    <f:render partial="Pagination" arguments="{pagination: result.pagination, searchParams: searchParams, freeIndexUid: freeIndexUid}" />

.. index:: Fluid, Frontend, NotScanned, ext:indexed_search
