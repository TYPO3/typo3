.. include:: /Includes.rst.txt

=====================================================================
Deprecation: #86047 - TSFE properties / methods and change visibility
=====================================================================

See :issue:`86047`

Description
===========

The following properties of class :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` have changed their visibility from public to protected and should not be called any longer.

* :php:`pageAccessFailureHistory`
* :php:`workspacePreview` (not in use anymore)
* :php:`ADMCMD_preview_BEUSER_uid` (not in use anymore)
* :php:`debug` (not in use anymore)
* :php:`MP_defaults` (not in use anymore outside of TSFE)
* :php:`loginAllowedInBranch` (use checkIfLoginAllowedInBranch())

The following methods  of class :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` have changed their visibility from public to protected, as their purpose is to be called from
within :php:`TypoScriptFrontendController`.

* :php:`tempPageCacheContent()`
* :php:`realPageCacheContent()`
* :php:`setPageCacheContent()`
* :php:`clearPageCacheContent_pidList()`
* :php:`setSysLastChanged()`
* :php:`contentStrReplace()`


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
