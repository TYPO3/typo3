.. include:: /Includes.rst.txt

=========================================================
Deprecation: #83083 - GeneralUtility::llXmlAutoFileName()
=========================================================

See :issue:`83083`

Description
===========

The method :php:`GeneralUtility::llXmlAutoFileName()`, which detects a XLF/XML translation file, has been moved into
AbstractXmlParser, as the functionality is solely used in there, and the code belongs in this area.


Impact
======

Calling the method will trigger a deprecation warning.


Affected Installations
======================

Any TYPO3 instance with an extension using the method directly.


Migration
=========

If necessary, use the XmlParser functionality, or implement the code directly in your own extension.

.. index:: PHP-API, FullyScanned
