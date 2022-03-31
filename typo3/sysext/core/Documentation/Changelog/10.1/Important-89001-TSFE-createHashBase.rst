.. include:: /Includes.rst.txt

========================================
Important: #89001 - TSFE->createHashBase
========================================

See :issue:`89001`

Description
===========

The method :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->createHashBase()`
calculates all components that are relevant for a specific cached version of a page.

With TYPO3 v10.1, the keys in :php:`$hashParameters` used for calculating the hash have been modified:

- `gr_list` has been replaced by `groupIds` but contains the same values
- `cHash` has been replaced by `dynamicArguments` but contains the same values
- `domainStartPage` has been replaced by `site` (identifier of the site)

.. index:: Frontend, PHP-API, ext:frontend
