
.. include:: ../../Includes.txt

===========================================================
Breaking: #72888 - Removed HtmlParser mapTags functionality
===========================================================

See :issue:`72888`

Description
===========

The functionality to map tags explicitly from the HtmlParser code has been removed:

    * `HtmlParser::mapTags()`
    * `RteHtmlParser::defaultTStagMapping()`


Impact
======

Calling one of the two methods above will result in a PHP fatal error.


Affected Installations
======================

Any installation using custom RTE transformation and wanting to remap tags while parsing HTML.


Migration
=========

Use the "remap" functionality of the `keepTags` logic within HtmlParser to achieve the same in custom transformations.

.. index:: PHP-API, RTE
