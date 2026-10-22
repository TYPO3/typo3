.. include:: /Includes.rst.txt

.. _deprecation-65578:

=====================================================================
Deprecation: #65578 - config.concatenateJsAndCss and concatenateFiles
=====================================================================

See :issue:`65578`

Description
===========

The TypoScript property `config.concatenateJsAndCss` and the related methods within :php-short:`\TYPO3\CMS\Core\Page\PageRenderer` have
been marked as deprecated:

* :php-short:`\TYPO3\CMS\Core\Page\PageRenderer->getConcatenateFiles()`
* :php-short:`\TYPO3\CMS\Core\Page\PageRenderer->enableConcatenateFiles()`
* :php-short:`\TYPO3\CMS\Core\Page\PageRenderer->disableConcatenateFiles()`


Impact
======

Setting the TypoScript property or calling one of the methods above will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations setting the TypoScript property or calling one of the PHP methods directly.


Migration
=========

Use the TypoScript properties :typoscript:`config.concatenateJs` and :typoscript:`config.concatenateCss`
and the corresponding methods in PageRenderer class directly.

.. index:: Frontend, PHP-API, TypoScript, PartiallyScanned
