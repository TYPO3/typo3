.. include:: /Includes.rst.txt

=============================================================================
Deprecation: #86439 - Mark several methods within TemplateService as internal
=============================================================================

See :issue:`86439`

Description
===========

The following methods in :php:`TYPO3\CMS\Core\TypoScript\TemplateService` have been marked as protected:

* :php:`prependStaticExtra()`
* :php:`versionOL()`
* :php:`processIncludes()`
* :php:`mergeConstantsFromPageTSconfig()`
* :php:`flattenSetup()`
* :php:`substituteConstants()`


Impact
======

Calling the methods in a public context  will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom extensions working with the :php:`TemplateService` class.

Migration
=========

Avoid using the methods, and re-implement the functionality on your own, if necessary.

.. index:: Backend, FullyScanned, ext:core
