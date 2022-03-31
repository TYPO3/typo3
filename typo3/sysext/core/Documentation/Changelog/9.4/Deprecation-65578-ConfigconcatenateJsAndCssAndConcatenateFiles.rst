.. include:: /Includes.rst.txt

=====================================================================
Deprecation: #65578 - config.concatenateJsAndCss and concatenateFiles
=====================================================================

See :issue:`65578`

Description
===========

The TypoScript property `config.concatenateJsAndCss` and the related methods within :php:`PageRenderer` have
been marked as deprecated:

* :php:`PageRenderer->getConcatenateFiles()`
* :php:`PageRenderer->enableConcatenateFiles()`
* :php:`PageRenderer->disableConcatenateFiles()`


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
