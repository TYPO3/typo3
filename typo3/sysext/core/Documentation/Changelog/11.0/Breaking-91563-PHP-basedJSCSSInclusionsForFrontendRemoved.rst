.. include:: /Includes.rst.txt

=====================================================================
Breaking: #91563 - PHP-based JS + CSS inclusions for Frontend removed
=====================================================================

See :issue:`91563`

Description
===========

In the past, TYPO3's :php:`TSFE` object allowed to manually add CSS or JavaScript snippets via PHP code with the following method and properties:

* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->setJS()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->additionalJavaScript`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->additionalCSS`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->JSCode`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->inlineJS`

These have been removed due to better APIs like :php:`PageRenderer` (available since TYPO3 v4.5) and :php:`AssetCollector` (available since TYPO3 v10).


Impact
======

Accessing the method and properties will have no effect and trigger PHP errors.


Affected Installations
======================

TYPO3 installations with custom extensions using this functionality directly to inject custom CSS or JavaScript.


Migration
=========

Use the :php:`AssetCollector` API in PHP to add JavaScript and CSS code or use files directly.

.. index:: PHP-API, FullyScanned, ext:frontend
