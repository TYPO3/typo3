.. include:: /Includes.rst.txt

.. _breaking-102621-1701937690:

==================================================================
Breaking: #102621 - Most TSFE members marked internal or read only
==================================================================

See :issue:`102621`

Description
===========

Most properties and methods of class :php:`TypoScriptFrontendController` have
been marked :php:`@internal` or "read only".

:php:`TypoScriptFrontendController` ("TSFE") is a god object within the TYPO3 Frontend
rendering chain: It is used by multiple middlewares that call TSFE methods to create
state in it, it is used within :php:`ContentObjectRenderer` and various other
classes to update and retrieve state. It is also registered as :php:`$GLOBALS['TSFE']`
at some point and thus available as global state object.

This makes the class the biggest anti-pattern we have within the Frontend - class
:php:`ContentObjectRenderer` is problematic as well, but that is a different story.
The current role of :php:`TypoScriptFrontendController` leads to very complex and
opaque state handling within the Frontend rendering, is the true source of many
hard to fix issues and prevents core development from implementing cool new features.

The TYPO3 core strives to resolve large parts of this with TYPO3 v13: State needed
by lower level code is being modeled as request attributes or handled locally in
middlewares, methods are moved out of the class into middlewares to improve
encapsulation and code flow.

To do that within continued TYPO3 v13 development, the core needs to mark various
methods and properties :php:`@internal`, and needs to mark more strict access patterns
on others.

The solution is to look at public properties of :php:`TypoScriptFrontendController`,
and to declare those as :php:`@internal`, which extensions typically should not need to
deal with at all. Others (like for instance :php:`id`) are actively used by extensions and
will be substituted by something different later, and are thus marked as "allowed to read,
but never write" for extensions". This allows implementation of a deprecation layer for those
"read only" properties later, while those marked :php:`@internal` can vanish without
further notice. A similar strategy is added for methods, leaving only a few not
marked :php:`@internal`, which the core will deprecate with a compatibility layer later.

The following public class properties have been marked "read only":

* :php:`TypoScriptFrontendController->id`
* :php:`TypoScriptFrontendController->rootLine`
* :php:`TypoScriptFrontendController->page`
* :php:`TypoScriptFrontendController->contentPid`
* :php:`TypoScriptFrontendController->sys_page`
* :php:`TypoScriptFrontendController->config` - Reading :php:`$tsfe->config['config']`
  and :php:`$tsfe->config['rootLine']` is allowed
* :php:`TypoScriptFrontendController->absRefPrefix`
* :php:`TypoScriptFrontendController->cObj`

The following public class properties have been marked :php:`@internal` - in general
all properties not listed above:

* :php:`TypoScriptFrontendController->no_cache` - Use Request attribute :php:`frontend.cache.instruction` instead
* :php:`TypoScriptFrontendController->additionalHeaderData`
* :php:`TypoScriptFrontendController->additionalFooterData`
* :php:`TypoScriptFrontendController->register`
* :php:`TypoScriptFrontendController->registerStack`
* :php:`TypoScriptFrontendController->recordRegister`
* :php:`TypoScriptFrontendController->currentRecord`
* :php:`TypoScriptFrontendController->content`
* :php:`TypoScriptFrontendController->lastImgResourceInfo`

The following methods have been marked :php:`@internal`:

* :php:`TypoScriptFrontendController->__construct` - extensions should not create own instances of TSFE
* :php:`TypoScriptFrontendController->determineId()`
* :php:`TypoScriptFrontendController->getPageAccessFailureReasons()`
* :php:`TypoScriptFrontendController->calculateLinkVars()`
* :php:`TypoScriptFrontendController->isGeneratePage()`
* :php:`TypoScriptFrontendController->preparePageContentGeneration()`
* :php:`TypoScriptFrontendController->generatePage_postProcessing()`
* :php:`TypoScriptFrontendController->generatePageTitle()`
* :php:`TypoScriptFrontendController->INTincScript()`
* :php:`TypoScriptFrontendController->INTincScript_loadJSCode()`
* :php:`TypoScriptFrontendController->isINTincScript()`
* :php:`TypoScriptFrontendController->applyHttpHeadersToResponse()`
* :php:`TypoScriptFrontendController->isStaticCacheble()`
* :php:`TypoScriptFrontendController->newCObj()`
* :php:`TypoScriptFrontendController->logDeprecatedTyposcript()`
* :php:`TypoScriptFrontendController->uniqueHash()`
* :php:`TypoScriptFrontendController->set_cache_timeout_default()`
* :php:`TypoScriptFrontendController->get_cache_timeout()`
* :php:`TypoScriptFrontendController->getRequestedId()` - Use :php:`Request->getAttribute('routing')->getPageId()` instead
* :php:`TypoScriptFrontendController->getLanguage()`
* :php:`TypoScriptFrontendController->getSite()`
* :php:`TypoScriptFrontendController->getContext()` - Use dependency injection or :php:`GeneralUtility::makeInstance()` instead
* :php:`TypoScriptFrontendController->getPageArguments()`

Impact
======

Writing to the listed read only properties may break the Frontend rendering,
using the properties or methods marked as :php:`@internal` may raise fatal PHP errors.


Affected installations
======================

The majority of extensions should already use the above properties that are marked read only
for reading only: Updating their state can easily lead to unexpected behavior. Most
extensions also don't consume the properties or methods marked as :php:`@internal`.

Extension developers should watch out for usages of :php:`TypoScriptFrontendController` in
general and reduce usages as much as possible.


Migration
=========

The migration strategy depends on the specific use case. The Frontend rendering chain
continues to add state that is needed by extensions as :php:`Request` attributes. Debugging
the incoming request within an extension often reveals a proper alternative.


.. index:: Frontend, PHP-API, PartiallyScanned, ext:frontend
