
.. include:: ../../Includes.txt

===================================================================
Breaking: #73611 - Removed ResourceCompressor relative path methods
===================================================================

See :issue:`73611`

Description
===========

The methods `ResourceCompressor::setInitialPaths()` and `ResourceCompressor::setRelativePath()` have been removed.


Impact
======

Calling one of the methods above will result in a fatal PHP error.


Affected Installations
======================

Any TYPO3 instance with custom extensions manually using the ResourceCompressor instead of using the PageRenderer API.


Migration
=========

Simply remove the methods from the affected code, as these methods are not needed anymore. All calculations
for the paths are now done automatically.

.. index:: PHP-API
