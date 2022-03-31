
.. include:: /Includes.rst.txt

=======================================================
Breaking: #72667 - RTE: Unused internal methods removed
=======================================================

See :issue:`72667`

Description
===========

The HTML parsing features for the Rich Text Editor feature related to
xhtml_cleaning have been removed. The following now obsolete methods have been
removed as well:

* `HtmlParser->checkTagTypeCounts()`
* `HtmlParser->unprotectTags()`
* `HtmlParser->get_tag_attributes_classic()`
* `HtmlParser->cleanFontTags()`
* `HtmlParser->indentLines()`

Additionally, the third parameter for the method `HtmlParser->getAllParts()` has been removed, resulting that the method will always include
the parsed tags in the result set.


Impact
======

Calling any of the methods will result in a fatal PHP error.


Affected Installations
======================

Any installation which uses a third-party extension that modifies the HtmlParsing via PHP.

.. index:: PHP-API, Backend, RTE
