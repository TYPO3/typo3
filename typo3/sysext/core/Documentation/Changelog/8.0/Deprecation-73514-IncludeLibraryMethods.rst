
.. include:: ../../Includes.txt

============================================
Deprecation: #73514 - IncludeLibrary Methods
============================================

See :issue:`73514`

Description
===========

The PHP methods `\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->includeLibraries()`
and `\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->includeLibs()`
to include PHP libraries during frontend output have been marked as deprecated.


Impact
======

Calling any of the methods above will trigger a deprecation log entry.


Affected Installations
======================

Any installation using custom Frontend output modified via PHP and TypoScript through e.g. a custom CType
provided by a special extension.


Migration
=========

Use proper object orientation and class loading methods to load code in the Frontend when necessary.

.. index:: PHP-API, Frontend
