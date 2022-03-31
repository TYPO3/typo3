.. include:: /Includes.rst.txt

=========================================================================
Feature: #82260 - Separation of search result path into title,uri,linkTag
=========================================================================

See :issue:`82260`

Description
===========

For styling and individual html markup of the result of indexed_search it is now possible to get the path information
in separate keys. Introduced keys: `pathTitle`, `pathUri`.
Enclose the whole result in a link or show the destination (link) as string. (e.g. bootstrap list item).


Impact
======

The protected method :php:`linkPage()` returns an array with the "uri" and "target".
To build an ATag outside of the fluid template, you have to use the introduced wrapper :php:`linkPageATagWrap()`.

Using the keys in your fluid template:

.. code-block:: html

    <div class="path">{row.path}</div>
    <div class="path-title">{row.pathTitle}</div>
    <div class="path-uri">{row.pathUri}</div>


.. index:: Frontend, ext:indexed_search
