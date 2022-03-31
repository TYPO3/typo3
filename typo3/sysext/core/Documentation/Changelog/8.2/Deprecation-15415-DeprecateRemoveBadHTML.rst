
.. include:: /Includes.rst.txt

=============================================
Deprecation: #15415 - Deprecate removeBadHTML
=============================================

See :issue:`15415`

Description
===========

Due to the wrong approach of removeBadHTML it is not 100% complete and does not keep its promise.

-  :php:`ContentObjectRenderer::stdWrap_removeBadHTML()`
-  :php:`ContentObjectRenderer::removeBadHTML()`
-  :typoscript:`stdWrap.removeBadHTML`


Impact
======

Using the mentioned method or stdWrap property will trigger a deprecation log entry.


Affected Installations
======================

Instances that use the method or stdWrap property.


Migration
=========

Implement a proper encoding by yourself. Use :php:`htmlspecialchars()` or :typoscript:`stdWrap.htmlSpecialChars`
in the context of HTML, :php:`GeneralUtility::quoteJSvalue()` or :typoscript:`stdWrap.encodeForJavaScriptValue`
in the context of JavaScript.

.. index:: Frontend, TypoScript, PHP-API
