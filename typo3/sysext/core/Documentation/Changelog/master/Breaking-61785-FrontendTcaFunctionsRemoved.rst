====================================================================
Breaking: #61785 - getCompressedTCarray and includeTCA from TypoScriptFrontendController removed
====================================================================

Description
===========

Methods getCompressedTCarray() and includeTCA() from \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController are removed.

Impact
======

Extensions that still use \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::getCompressedTCarray() or \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::includeTCA will trigger a fatal error.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses one of the removed functions.


Migration
=========

Full TCA is always loaded during bootstrap in FE, the methods are obsolete.
If an eid script calls this method to load TCA, use \TYPO3\CMS\Frontend\Utility\EidUtility::initTCA() instead.

