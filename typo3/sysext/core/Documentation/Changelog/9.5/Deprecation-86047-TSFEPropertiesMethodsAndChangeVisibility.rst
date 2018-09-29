.. include:: ../../Includes.txt

=====================================================================
Deprecation: #86047 - TSFE properties / methods and change visibility
=====================================================================

See :issue:`86047`

Description
===========

The following properties have changed their visibility from public to protected.

* :php:`TypoScriptFrontendController->pageAccessFailureHistory`
* :php:`TypoScriptFrontendController->workspacePreview` (not in use anymore)
* :php:`TypoScriptFrontendController->ADMCMD_preview_BEUSER_uid` (not in use anymore)
* :php:`TypoScriptFrontendController->debug` (not in use anymore)
* :php:`TypoScriptFrontendController->MP_defaults` (not in use anymore outside of TSFE)
* :php:`TypoScriptFrontendController->loginAllowedInBranch` (use checkIfLoginAllowedInBranch())

The following methods have changed their signature to be protected, as their purpose is to be called from
within :php:`TypoScriptFrontendController`.

* :php:`TypoScriptFrontendController->tempPageCacheContent()`
* :php:`TypoScriptFrontendController->realPageCacheContent()`
* :php:`TypoScriptFrontendController->setPageCacheContent()`
* :php:`TypoScriptFrontendController->clearPageCacheContent_pidList()`
* :php:`TypoScriptFrontendController->setSysLastChanged()`
* :php:`TypoScriptFrontendController->contentStrReplace()`


Impact
======

Calling any of the PHP methods will trigger a PHP :php:`E_USER_DEPRECATED` error, as well as accessing any of the
previously public properties.


Affected Installations
======================

Any TYPO3 installation with extensions directly calling one of the methods or using one of the
public properties.


Migration
=========

For :php:`TypoScriptFrontendController->ADMCMD_preview_BEUSER_uid` use the backend.user aspect of the Context API.
For :php:`TypoScriptFrontendController->workspacePreview` use the workspace aspect of the Context API.
For :php:`TypoScriptFrontendController->loginAllowedInBranch` use the method :php:`checkIfLoginAllowedInBranch()` instead.

.. index:: Frontend, FullyScanned, ext:frontend
