.. include:: /Includes.rst.txt

.. _breaking-97816-1664800747:

====================================================
Breaking: #97816 - New TypoScript parser in Frontend
====================================================

See :issue:`97816`

Description
===========

The rewrite of the TypoScript parser has been enabled for Frontend
rendering.

See :ref:`breaking-97816-1656350406` and :ref:`feature-97816-1656350667`
for more details on the new parser.


Impact
======

The change has impact on Frontend caching, hooks, some classes and properties. In detail:

* Hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Core/TypoScript/TemplateService']['runThroughTemplatesPostProcessing']`
  is gone and substituted by :php:`AfterTemplatesHaveBeenDeterminedEvent`. See :ref:`feature-97816-1664801053` for more details.

* The classes :php:`TYPO3\CMS\Core\TypoScript\TemplateService` and :php:`TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser`
  have been marked as deprecated and shouldn't be used anymore.
  An instance of :php:`TemplateService` is still kept as property :php:`TypoScriptFrontendController->tmpl` (:php:`$GLOBALS['TSFE']->tmpl)
  as backwards compatible layer, and the most important properties within the class, namely especially :php:`TemplateService->setup` is
  still set. To avoid using these properties, the Frontend request object will contain this state.
  In rare cases, where extensions need to parse TypoScript on their own, they should switch to the Tokenizer and AstBuilder structures
  of the new parser. Note these classes are still young and currently marked @internal, the API may still slightly change with further
  v12 development.

* The :php:`pagesection` cache has been removed. This was a helper cache that grew O(n) with the number of
  called Frontend pages. The new :php:`typoscript` cache is used instead: This grows only O(n) with the
  number of different sys_template and condition combinations and is a filesystem based :php:`PhpFrontend` implementation.
  When upgrading, the database tables :sql:`cache_pagesection` and :sql:`cache_pagesections_tags` can be safely removed, the
  install tool will also silently remove any existing entries from :file:`settings.php` that reconfigure the cache.

* The Frontend rendering changed TypoScript cache behavior slightly, which may have an impact on integrators developing
  and testing TypoScript in the Frontend. The short version is: When changing :sql:`sys_template` records, changes have
  immediate effect, and when changing included TypoScript files, the Frontend browser tab should be reloaded using "shift-reload"
  or the browser inspector should be opened and the "Disable cache" toggle turned on. Note when changes like this should
  go live for everyone, Frontend caches must still be cleared using the Backend toolbar "Flush frontend caches" to have
  an effect on "normal users" without active Backend login.

  Some more details on this: Frontend TypoScript in general now uses caches across multiple pages, which
  increases rendering performance. After calling a first page with empty caches, a second page call to a different
  page will re-use most, if not all, TypoScript from cache entries created by the first page access.

  This has impact on cache invalidation when developing Frontend TypoScript:
  First, changing :sql:`sys_template` records always has immediate effect to all requests, even without clearing caches manually.
  The system detects field changes of :sql:`sys_template` changes automatically, reloading a page in the Frontend will trigger
  re-calculation of TypoScript and thus re-rendering of the page. Note this is only true for directly loaded :sql:`sys_template`
  records. Changes on records included indirectly via the relatively seldom used :sql:`basedOn` field are *not* detected
  automatically, and the same systematics as outlined below for file includes kicks in.

  The cache behavior is slightly different for files included using :typoscript:`@import`, :typoscript:`<INCLUDE_TYPOSCRIPT: ...`
  and for :sql:`sys_template` records included using the :sql:`basedOn` field. To suppress expensive filesystem calls in production,
  the cache layer for included files is more aggressive and does *not* automatically trigger Frontend page re-rendering when included
  TypoScript files are changed. There are however some ways to easily work around this as an integrator: When a backend user is
  logged in, a Frontend call recognizes this since various functionality is bound to logged in Backend users in the Frontend, most notably
  the ability to preview hidden pages or hidden content, and the admin panel functionality. When changing Frontend TypoScript in included
  files, being logged in with a Backend user, and then pressing "shift-reload" for the Frontend page, this will trigger "no cache",
  which forces content re-rendering including TypoScript re-calculation. Additionally, the "Browser Inspectors" in Chrome and
  Firefox both have a "Disable cache" toggle, which sends the same HTTP header as done with "shift-reload", which will *also*
  force re-rendering. Note integrators should still "Flush frontend caches" when changes in included TypoScript files should
  go-live for all other Frontend requests and thus "normal users" as well.

* The following properties and methods in :php:`TypoScriptFrontendController` have been set to :php:`@internal` and should not
  be used any longer since they may vanish without further notice:

  * :php:`TypoScriptFrontendController->no_cache`
  * :php:`TypoScriptFrontendController->tmpl`
  * :php:`TypoScriptFrontendController->pageContentWasLoadedFromCache`
  * :php:`TypoScriptFrontendController->getFromCache_queryRow()`
  * :php:`TypoScriptFrontendController->populatePageDataFromCache()`
  * :php:`TypoScriptFrontendController->shouldAcquireCacheData()`
  * :php:`TypoScriptFrontendController->acquireLock()`
  * :php:`TypoScriptFrontendController->releaseLock()`

* The following methods in :php:`TypoScriptFrontendController` have been removed:

  * :php:`TypoScriptFrontendController->getHash()`
  * :php:`TypoScriptFrontendController->getLockHash()`
  * :php:`TypoScriptFrontendController->getConfigArray()`
  * :php:`TypoScriptFrontendController->()`


Affected installations
======================

Many instances will only recognize that the :php:`pagesection` cache is gone and should continue to work.
Instances with extensions that use :php:`TemplateService` or :php:`TypoScriptParser`, or access the
property :php:`TypoScriptFrontendController->tmpl` may need adaptions.


Migration
=========

See the impact description above for some migration hints.

.. index:: Database, Frontend, PHP-API, TypoScript, LocalConfiguration, PartiallyScanned, ext:frontend
