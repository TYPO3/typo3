.. include:: ../../Includes.txt

========================================================================================
Deprecation: #84637 - TemplateService->linkData() functionality moved in PageLinkBuilder
========================================================================================

See :issue:`84637`

Description
===========

In the process of streamlining the link generation to pages in the Frontend, the master method
:php:`TemplateService->linkData` and all functionality regarding resolving of the according Mount Point parameters
have been migrated into the TypoLink PageLinkBuilder class.

The following methods have been marked as deprecated:

* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->linkData`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->getFromMPmap`
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService->initMPmap_create`


Impact
======

Calling any of the methods above will trigger a PHP deprecation warning.


Affected Installations
======================

Any TYPO3 installations with third-party extensions calling the methods directly, extensions using the
existing hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['linkData-PostProc']`
will work the same way.


Migration
=========

Access the corresponding new methods within :php:`PageLinkBuilder` instead of the TemplateService-related
methods, or use the existing hook to modify parameters for a URL.

.. index:: FullyScanned