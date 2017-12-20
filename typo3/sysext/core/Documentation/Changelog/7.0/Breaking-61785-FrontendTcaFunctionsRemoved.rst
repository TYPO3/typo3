
.. include:: ../../Includes.txt

================================================================================================
Breaking: #61785 - getCompressedTCarray and includeTCA from TypoScriptFrontendController removed
================================================================================================

See :issue:`61785`

Description
===========

Methods :code:`getCompressedTCarray()` and :code:`includeTCA()` from :code:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController`
have been removed.

Impact
======

Extensions that still use :code:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::getCompressedTCarray()`
or :code:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::includeTCA()` will trigger a fatal error.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses one of the removed functions.


Migration
=========

Full TCA is always loaded during bootstrap in FE, the methods are obsolete.
If an eid script calls this method to load TCA, use :code:`\TYPO3\CMS\Frontend\Utility\EidUtility::initTCA()` instead.


.. index:: PHP-API, Frontend
