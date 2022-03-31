.. include:: /Includes.rst.txt

=========================================================================================
Deprecation: #86433 - Various stdWrap functions and ContentObjectRenderer-related methods
=========================================================================================

See :issue:`86433`

Description
===========

The following TypoScript :typoscript:`stdWrap` sub-properties and functions have been marked as deprecated:

* :typoscript:`stdWrap.addParams`
* :typoscript:`stdWrap.filelist`
* :typoscript:`stdWrap.filelink`

In conjunction with the properties, the following methods of class :php:`TYPO3\CMS\Frontend\ContentObjectRenderer` have been marked as deprecated:

* :php:`stdWrap_addParams()`
* :php:`stdWrap_filelink()`
* :php:`stdWrap_filelist()`
* :php:`addParams()`
* :php:`filelink()`
* :php:`filelist()`
* :php:`typolinkWrap()`
* :php:`currentPageUrl()`

These functions were part of TYPO3 Core due to legacy functionality related
to ContentObject "TABLE" and "CSS Styled Content".


Impact
======

Calling any of the methods or using the properties will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom TypoScript options which have not been migrated to FAL
or Fluid Styled Content.


Migration
=========

Use Fluid Styled Content, or DataProcessors instead.

.. index:: Frontend, TypoScript, FullyScanned
