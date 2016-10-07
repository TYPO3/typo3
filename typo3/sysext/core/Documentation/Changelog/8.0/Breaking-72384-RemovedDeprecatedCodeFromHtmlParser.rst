
.. include:: ../../Includes.txt

==========================================================
Breaking: #72384 - Removed deprecated code from HtmlParser
==========================================================

See :issue:`72384`

Description
===========

The following methods have been removed from HtmlParser:

* `getSubpart()`
* `substituteSubpart()`
* `substituteSubpartArray()`
* `substituteMarker()`
* `substituteMarkerArray()`
* `substituteMarkerAndSubpartArrayRecursive()`
* `XHTML_clean()`
* `processTag()`
* `processContent()`

The following method has been removed from RteHtmlParser:

* `evalWriteFile`

The TSconfig option `xhtml_cleaning` has been removed as well.

Impact
======

Using the methods above directly in any third party extension will result in a fatal error. Setting the xhtml
processing option when parsing HTML has no effect anymore as well.


Affected Installations
======================

Instances which use custom calls to HtmlParser via the methods above.


Migration
=========

`getSubpart()` use `MarkerBasedTemplateService::getSubpart()` instead
`substituteSubpart()` call `MarkerBasedTemplateService::substituteSubpart()` instead
`substituteSubpartArray()` call `MarkerBasedTemplateService::substituteSubpartArray()` instead
`substituteMarker()` call `MarkerBasedTemplateService::substituteMarker()` instead
`substituteMarkerArray()` call `MarkerBasedTemplateService::substituteMarkerArray()` instead
`substituteMarkerAndSubpartArrayRecursive()` call `MarkerBasedTemplateService::substituteMarkerAndSubpartArrayRecursive()` instead
`XHTML_clean()` call `HtmlParser::HTMLcleaner()` instead

.. index:: PHP-API, Frontend
