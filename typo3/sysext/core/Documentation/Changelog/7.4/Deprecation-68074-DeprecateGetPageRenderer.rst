
.. include:: ../../Includes.txt

=========================================================
Deprecation: #68074 - Deprecate getPageRenderer() methods
=========================================================

See :issue:`68074`

Description
===========

The following public functions have been marked as deprecated as the instance they return is a singleton:

* `TYPO3\CMS\Backend\Controller\BackendController::getPageRenderer()`
* `TYPO3\CMS\Backend\Template\DocumentTemplate::getPageRenderer()`
* `TYPO3\CMS\Backend\Template\FrontendDocumentTemplate::getPageRenderer()`
* `TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::getPageRenderer()`


Impact
======

Using one of these functions will throw a deprecation message.


Migration
=========

As the PageRenderer implements a SingletonInterface you can get your own (shared) instance with
`\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class)` and work with that one.


.. index:: PHP-API
