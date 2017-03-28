.. include:: ../../Includes.txt

=====================================================================
Deprecation: #80527 - Marker-related methods in ContentObjectRenderer
=====================================================================

See :issue:`80527`

Description
===========

The following methods within php:`ContentObjectRenderer` PHP class have been marked as deprecated:

* getSubpart()
* substituteSubpart()
* substituteSubpartArray()
* substituteMarker()
* substituteMarkerArrayCached()
* substituteMarkerArray()
* substituteMarkerInObject()
* substituteMarkerAndSubpartArrayRecursive()
* fillInMarkerArray()


Impact
======

Calling any of the methods above will trigger a deprecation log entry.


Affected Installations
======================

Any installation using custom extensions calling these API methods.


Migration
=========

Instantiate the class `MarkerBasedTemplateService` available in TYPO3 v7, which contains equivalents
to all methods that have been marked as deprecated with the same functionality and namings.

.. index:: PHP-API