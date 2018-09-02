.. include:: ../../Includes.txt

========================================================================
Deprecation: #85822 - Static class TYPO3\CMS\Frontend\Page\PageGenerator
========================================================================

See :issue:`85822`

Description
===========

The PSR-15 RequestHandler is responsible for compiling content. There is no need anymore to directly access and set global objects, which are available already in the RequestHandler.

Therefore this logic is moved into RequestHandler and the PHP class :php:`TYPO3\CMS\Frontend\Page\PageGenerator`
has been marked as deprecated.


Impact
======

Calling any of the methods within the PHP class will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with a custom extension calling the static class.


Migration
=========

Move the render logic to your own extension or use the RequestHandler to compile the functionality.

The unrelated method :php:`PageRenderer::inline2TempFile()` has been moved into proper methods found at

* :php:`GeneralUtility::writeJavaScriptContentToTemporaryFile($content)`
* :php:`GeneralUtility::writeStyleSheetContentToTemporaryFile($content)`

.. index:: Frontend, FullyScanned, ext:frontend
