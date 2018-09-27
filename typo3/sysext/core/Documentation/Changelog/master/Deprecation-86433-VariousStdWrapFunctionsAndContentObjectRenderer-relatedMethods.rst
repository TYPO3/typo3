.. include:: ../../Includes.txt

=========================================================================================
Deprecation: #86433 - Various stdWrap functions and ContentObjectRenderer-related methods
=========================================================================================

See :issue:`86433`

Description
===========

The following TypoScript `stdWrap` sub-properties and functionalities have been deprecated:

- :ts:`stdWrap.addParams`
- :ts:`stdWrap.filelist`
- :ts:`stdWrap.filelink`

In conjunction with the properties, the following methods have been deprecated:
- :php:`TYPO3\CMS\Frontend\ContentObjectRenderer->stdWrap_addParams()`
- :php:`TYPO3\CMS\Frontend\ContentObjectRenderer->stdWrap_filelink()`
- :php:`TYPO3\CMS\Frontend\ContentObjectRenderer->stdWrap_filelist()`
- :php:`TYPO3\CMS\Frontend\ContentObjectRenderer->addParams()`
- :php:`TYPO3\CMS\Frontend\ContentObjectRenderer->filelink()`
- :php:`TYPO3\CMS\Frontend\ContentObjectRenderer->filelist()`
- :php:`TYPO3\CMS\Frontend\ContentObjectRenderer->typolinkWrap()`
- :php:`TYPO3\CMS\Frontend\ContentObjectRenderer->currentPageUrl()`

These functionalities have been part of TYPO3 Core due to legacy functionalities related
to ContentObject "TABLE" and "CSS Styled Content".


Impact
======

Calling any of the methods or using the properties will trigger a PHP deprecation notice.


Affected Installations
======================

TYPO3 installations with custom TypoScript options which have not been migrated to FAL
or Fluid Styled Content.


Migration
=========

Use Fluid Styled Content, or DataProcessors instead.

.. index:: Frontend, TypoScript, FullyScanned