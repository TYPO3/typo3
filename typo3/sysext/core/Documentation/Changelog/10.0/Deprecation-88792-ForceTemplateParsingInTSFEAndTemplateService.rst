.. include:: /Includes.rst.txt

======================================================================
Deprecation: #88792 - forceTemplateParsing in TSFE and TemplateService
======================================================================

See :issue:`88792`

Description
===========

* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::forceTemplateParsing` and
* :php:`TYPO3\CMS\Core\TypoScript\TemplateService::forceTemplateParsing`

have been marked as deprecated and replaced by Context API.


Impact
======

Setting either :php:`forceTemplateParsing` of :php:`TypoScriptFrontendController` or :php:`TemplateService`
will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All installations setting or reading :php:`$TSFE->forceTemplateParsing` or :php:`TemplateService->forceTemplateParsing`.


Migration
=========

Use the Context API ::

   GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('typoscript', 'forcedTemplateParsing');
   $context->setAspect('typoscript', GeneralUtility::makeInstance(TypoScriptAspect::class, true));

.. index:: Frontend, PHP-API, PartiallyScanned, ext:core
