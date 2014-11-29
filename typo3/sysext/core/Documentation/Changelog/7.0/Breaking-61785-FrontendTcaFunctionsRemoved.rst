================================================================================================
Breaking: #61785 - getCompressedTCarray and includeTCA from TypoScriptFrontendController removed
================================================================================================

Description
===========

Methods :php:`getCompressedTCarray()` and :php:`includeTCA()` from :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` have been removed.

Impact
======

Extensions that still use :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::getCompressedTCarray()` or :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::includeTCA()` will trigger a fatal error.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses one of the removed functions.


Migration
=========

Full TCA is always loaded during bootstrap in FE, the methods are obsolete.
If an eid script calls this method to load TCA, use :php:`\TYPO3\CMS\Frontend\Utility\EidUtility::initTCA()` instead.

