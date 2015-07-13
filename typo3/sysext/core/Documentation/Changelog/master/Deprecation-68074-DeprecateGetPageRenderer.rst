=========================================================
Deprecation: #68074 - Deprecate getPageRenderer() methods
=========================================================

Description
===========

The following public functions have been marked for deprecation as the instance they return is a singleton:

* TYPO3\CMS\Backend\Controller\BackendController::getPageRenderer()
* TYPO3\CMS\Backend\Template\DocumentTemplate::getPageRenderer()
* TYPO3\CMS\Backend\Template\FrontendDocumentTemplate::getPageRenderer()
* TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::getPageRenderer()


Impact
======

Using ``BackendController::getPageRenderer`` or ``FrontendDocumentTemplate::getPageRenderer`` will throw a deprecation message.
The public functions ``DocumentTemplate::getPageRenderer`` and ``TypoScriptFrontendController::getPageRenderer`` will become
protected methods with TYPO3 CMS 8. As those functions have to be used within the classes themselves no deprecation message can be thrown.


Migration
=========

As the PageRenderer implements a SingletonInterface you can get your own (shared) instance with
\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class) and work with that one.
