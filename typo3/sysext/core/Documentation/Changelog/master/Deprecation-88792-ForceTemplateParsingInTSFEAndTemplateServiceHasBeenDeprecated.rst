.. include:: ../../Includes.txt

==========================================================================================
Deprecation: #88792 - forceTemplateParsing in TSFE and TemplateService has been deprecated
==========================================================================================

See :issue:`88792`

Description
===========

The setting `forceTemplateParsing` in `TypoScriptFrontendController` as well as an `TemplateService` has been deprecated and replaced by Context API.


Impact
======

Setting either `forceTemplateParsing` of `TypoScriptFrontendController` or `TemplateService` will result in a deprecation notice.


Affected Installations
======================

All installations setting or reading `$TSFE->forceTemplateParsing` or `TemplateService->forceTemplateParsing`.


Migration
=========

Use the Context API ::

   GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('typoscript', 'forcedTemplateParsing');
   $context->setAspect('typoscript', GeneralUtility::makeInstance(TypoScriptAspect::class, true));

.. index:: Frontend, PHP-API, PartiallyScanned, ext:core