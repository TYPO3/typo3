.. include:: /Includes.rst.txt

.. _breaking-102621-1701937690:

==================================================================
Breaking: #102621 - Most TSFE members marked internal or read-only
==================================================================

See :issue:`102621`

Description
===========

Most properties and methods of class :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` have
been marked :php:`@internal` or "read-only".

:php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` ("TSFE") is a god object within the TYPO3 frontend
rendering chain: It is used by multiple middlewares that call TSFE methods to create
state in it, it is used within :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer` and various other
classes to update and retrieve state. It is also registered as :php:`$GLOBALS['TSFE']`
at some point and thus available as global state object.

This makes the class the biggest anti-pattern we have within the frontend - class
:php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer` is problematic as well, but that is a different story.
The current role of :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` leads to very complex and
opaque state handling within the frontend rendering, is the true source of many
hard to fix issues and prevents Core development from implementing cool new features.

The TYPO3 Core strives to resolve large parts of this with TYPO3 v13: State needed
by lower level code is being modeled as request attributes or handled locally in
middlewares, methods are moved out of the class into middlewares to improve
encapsulation and code flow.

To do that within continued TYPO3 v13 development, the Core needs to mark various
methods and properties :php:`@internal`, and needs to mark more strict access patterns
on others.

The solution is to look at public properties of :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController`,
and to declare those as :php:`@internal`, which extensions typically should not need to
deal with at all. Others (like for instance :php:`id`) are actively used by extensions and
will be substituted by something different later, and are thus marked as "allowed to read,
but never write" for extensions. This allows implementation of a deprecation layer for those
"read-only" properties later, while those marked :php:`@internal` can vanish without
further notice. A similar strategy is added for methods, leaving only a few not
marked :php:`@internal`, which the Core will deprecate with a compatibility layer later.

The following public class properties have been marked "read-only", and have later been
deprecated with the full deprecation of :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` in
TYPO3 v13.4, see :ref:`deprecation-105230-1728374467`.

* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->id` - Use :php:`$request->getAttribute('frontend.page.information')->getId()` instead
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->rootLine` - Use :php:`$request->getAttribute('frontend.page.information')->getRootLine()` instead
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->page` - Use :php:`$request->getAttribute('frontend.page.information')->getPageRecord()` instead
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->contentPid` - Avoid usages altogether, available as :php:`@internal` call using
  :php:`$request->getAttribute('frontend.page.information')->getContentFromPid()`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->sys_page` - Avoid altogether, create own instance using :php-short:`\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(PageRepository::class)`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->config['config']` - Use :php:`$request->getAttribute('frontend.typoscript')->getConfigArray()` instead
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->config['rootLine']` - Use :php:`$request->getAttribute('frontend.page.information')->getLocalRootLine()` instead
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->cObj` - Create an own :php-short:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer` instance, call :php:`setRequest($request)`
  and :php:`start($request->getAttribute('frontend.page.information')->getPageRecord(), 'pages')`

The following public class properties have been marked :php:`@internal` - in general
all properties not listed above. They contain information usually not relevant within
extensions. The TYPO3 core will model them differently.

* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->absRefPrefix`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->no_cache` - Use request attribute :php:`frontend.cache.instruction` instead
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->additionalHeaderData`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->additionalFooterData`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->register`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->registerStack`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->recordRegister`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->currentRecord`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->content`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->lastImgResourceInfo`

The following methods have been marked :php:`@internal` and may vanish anytime:

* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->__construct()` - extensions should not create own instances of TSFE
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->determineId()`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getPageAccessFailureReasons()`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->calculateLinkVars()`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->isGeneratePage()`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->preparePageContentGeneration()`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->generatePage_postProcessing()`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->generatePageTitle()`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->INTincScript()`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->INTincScript_loadJSCode()`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->isINTincScript()`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->applyHttpHeadersToResponse()`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->isStaticCacheble()`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->newCObj()`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->logDeprecatedTyposcript()`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->uniqueHash()`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->set_cache_timeout_default()`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->set_no_cache()` - Use :php:`$request->getAttribute('frontend.cache.instruction')->disableCache()` instead
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->sL()` - Use :php-short:`\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(LanguageServiceFactory::class)->createFromSiteLanguage($request->getAttribute('language'))->sL()` instead
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->get_cache_timeout()`
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getRequestedId()` - Use :php:`$request->getAttribute('routing')->getPageId()` instead
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getLanguage()` - Use :php:`$request->getAttribute('language') ?? $request->getAttribute('site')->getDefaultLanguage()` instead
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getSite()` - Use :php:`$request->getAttribute('site')` instead
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getContext()` - Use dependency injection or :php-short:`\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance()` instead
* :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getPageArguments()` - Use :php:`$request->getAttribute('routing')` instead

Impact
======

Writing to the listed read-only properties may break the frontend rendering,
using the properties or methods marked as :php:`@internal` may raise fatal PHP errors.


Affected installations
======================

The majority of extensions should already use the above properties that are marked read-only
for reading only: Updating their state can easily lead to unexpected behavior. Most
extensions also don't consume the properties or methods marked as :php:`@internal`.

Extension developers should watch out for usages of :php-short:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` in
general and reduce usages as much as possible.


Migration
=========

The migration strategy depends on the specific use case. The frontend rendering chain
continues to add state that is needed by extensions as PSR-7 request attributes. Debugging
the incoming request within an extension often reveals a proper alternative.


.. index:: Frontend, PHP-API, PartiallyScanned, ext:frontend
