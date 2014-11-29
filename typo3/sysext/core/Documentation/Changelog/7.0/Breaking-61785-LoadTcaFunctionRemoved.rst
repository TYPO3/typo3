=============================================================
Breaking: #61785 - loadTCA function in GeneralUtility removed
=============================================================

Description
===========

Method :php:`loadTCA()` from :php:`\TYPO3\CMS\Core\Utility\GeneralUtility` is removed.

Impact
======

Extensions that still use :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::loadTCA()` will trigger a fatal error.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension still calls :php:`loadTCA`.


Migration
=========

The method is obsolete, full TCA is always loaded in all context except eID.
It is safe to remove the method call.

