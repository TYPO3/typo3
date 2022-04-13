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
