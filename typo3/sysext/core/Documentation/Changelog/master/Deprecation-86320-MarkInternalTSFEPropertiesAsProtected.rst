.. include:: ../../Includes.txt

=================================================================
Deprecation: #86320 - Mark internal $TSFE properties as protected
=================================================================

See :issue:`86320`

Description
===========

The following properties have changed their visibility to be protected from public. The properties are only used and needed internally.

* :php:`TypoScriptFrontendController->loginAllowedInBranch_mode`
* :php:`TypoScriptFrontendController->cacheTimeOutDefault`
* :php:`TypoScriptFrontendController->cacheContentFlag`
* :php:`TypoScriptFrontendController->cacheExpires`
* :php:`TypoScriptFrontendController->isClientCachable`
* :php:`TypoScriptFrontendController->no_cacheBeforePageGen`
* :php:`TypoScriptFrontendController->tempContent`
* :php:`TypoScriptFrontendController->pagesTSconfig`
* :php:`TypoScriptFrontendController->uniqueCounter`
* :php:`TypoScriptFrontendController->uniqueString`
* :php:`TypoScriptFrontendController->lang`


Impact
======

Calling any of the properties will trigger a deprecation warning.


Affected Installations
======================

Any TYPO3 installation with extensions directly calling one of the methods or using one of the public properties.


Migration
=========

Properties are only for internal use, no migration available.

.. index:: Frontend, FullyScanned, ext:frontend