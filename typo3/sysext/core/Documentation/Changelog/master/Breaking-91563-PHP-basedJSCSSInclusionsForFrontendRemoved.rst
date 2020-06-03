.. include:: ../../Includes.txt

=====================================================================
Breaking: #91563 - PHP-based JS + CSS inclusions for Frontend removed
=====================================================================

See :issue:`91563`

Description
===========

In the past, TYPO3's "TSFE" object allowed to manually add CSS or JavaScript snippets via PHP code via the following method and properties:

* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->setJS()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->additionalJavaScript`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->additionalCSS`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->JSCode`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->inlineJS`

These have been removed due to better APIs like PageRenderer (available since TYPO3 v4.5) and AssetCollector (TYPO3 v10).


Impact
======

Accessing the method and properties will have no effect and trigger a PHP notice.


Affected Installations
======================

TYPO3 installations with custom extensions using this functionality directly to inject custom CSS or JavaScript.


Migration
=========

Use the AssetCollector API in PHP to add JavaScript and CSS code or use files directly.

.. index:: PHP-API, FullyScanned, ext:frontend
