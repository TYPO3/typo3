.. include:: /Includes.rst.txt

======================================================
Deprecation: #94958 - ContentObjectRenderer properties
======================================================

See :issue:`94958`

Description
===========

A couple of outdated and mostly unused properties of class
:php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer` have been marked
as deprecated:

* :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->align` - Unused
* :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->oldData` - Unused
* :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->alternativeData` - Never set, only output during debug
* :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->currentRecordTotal` - Set, but never used
* :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->recordRegister` - Unused


Impact
======

Those properties did not have a purpose. Extensions shouldn't see
negative impact.


Affected Installations
======================

Instances with extensions that set or read these properties may be affected.
This is rather unlikely. The extension scanner finds candidates.


Migration
=========

Drop usages. The properties will vanish in v12.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
