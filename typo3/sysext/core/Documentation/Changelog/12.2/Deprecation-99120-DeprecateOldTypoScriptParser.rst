.. include:: /Includes.rst.txt

.. _deprecation-99120-1670428555:

====================================================
Deprecation: #99120 - Deprecate old TypoScriptParser
====================================================

See :issue:`99120`

Description
===========

To phase out usages of the old TypoScript parser by switching to the
:ref:`new parser approach <breaking-97816-1664800747>`, a couple of classes
and methods have been marked deprecated in TYPO3 v12 that will be
removed in TYPO3 v13:

*   Class :php:`\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser`
*   Class :php:`\TYPO3\CMS\Core\Configuration\Loader\PageTsConfigLoader`
*   Class :php:`\TYPO3\CMS\Core\Configuration\PageTsConfig`
*   Class :php:`\TYPO3\CMS\Core\Configuration\Parser\PageTsConfigParser`
*   Event :php:`\TYPO3\CMS\Core\Configuration\Event\ModifyLoadedPageTsConfigEvent`
*   Method :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getPagesTSconfig()`

The existing main API to retrieve page TSconfig using
:php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig()`
and user TSconfig using :php:`$backendUser->getTSConfig()` is kept.


Impact
======

Using one of the above classes will raise a deprecation level log entry and
will stop working with TYPO3 v13.


Affected installations
======================

Instances with extensions that use one of the above classes are affected. The
extension scanner will find usages with a mixture of weak and strong matches
depending on the usage.

Most deprecations are rather "internal" since extensions most likely use the
existing outer API already. Some may be affected when using the
:php:`\TYPO3\CMS\Core\Configuration\Event\ModifyLoadedPageTsConfigEvent` event
or the frontend related method :php:`TypoScriptFrontendController->getPagesTSconfig()`,
though.


Migration
=========

:php:`\TYPO3\CMS\Core\Configuration\Event\ModifyLoadedPageTsConfigEvent`
------------------------------------------------------------------------

This event is consumed by some extensions to modify calculated page TSconfig strings
before parsing. The event moved its namespace from
:php:`\TYPO3\CMS\Core\Configuration\Event\ModifyLoadedPageTsConfigEvent` to
:php:`\TYPO3\CMS\Core\TypoScript\IncludeTree\Event\ModifyLoadedPageTsConfigEvent` in
TYPO3 v12, but is kept as-is apart from that. The TYPO3 v12 Core triggers *both* the old
and the new event, and TYPO3 v13 will stop calling the old event.

Extension that want to stay compatible with both TYPO3 v11 and v12 should continue to
implement listen for the old event only. This will *not* raise a deprecation level log
entry in v12, but it will stop working with TYPO3 v13.
Extensions with compatibility for TYPO3 12 and above should switch to the new event.

:php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getPagesTSconfig()`
--------------------------------------------------------------------------------------

The TYPO3 frontend should usually not need to retrieve backend related page TSconfig.
Extensions using the method should avoid relying on bringing backend related configuration
into frontend scope. However, the TYPO3 Core comes with one place that does this: Class
:php:`\TYPO3\CMS\Frontend\Typolink\DatabaseRecordLinkBuilder` uses page TSconfig related
information in frontend scope. Extensions with similar use cases could have a similar
implementation as done with :php:`DatabaseRecordLinkBuilder->getPageTsConfig()`. Note that
any implementation of this will have to rely on :php:`@internal` usages of the new
TypoScript parser approach, and using this low level API may thus break without
further notice. Extensions are encouraged to cover usages with functional tests to find
issues quickly in case the TYPO3 Core still changes used classes.

:php:`\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser`
---------------------------------------------------------

In general, extensions probably don't need to use the old :php:`TypoScriptParser`
often: Frontend TypoScript is :ref:`available as request attribute <deprecation-99020-1667911024>`,
page TSconfig should be retrieved using :php:`BackendUtility::getPagesTSconfig()` and
user TSconfig should be retrieved using :php:`$backendUser->getTSConfig()`.

In case extensions want to parse any other strings that follow a TypoScript-a-like syntax,
they can use :php:`\TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory`, or could set up
their own factory using the new parser classes for more complex scenarios. Note that the new parser
approach is still marked :php:`@internal`, using this low level API may thus break without
further notice. Extensions are encouraged to cover usages with functional tests to find
issues quickly in case the TYPO3 Core still changes used classes.

:php:`\TYPO3\CMS\Core\Configuration\PageTsConfig`
-------------------------------------------------

There is little need to use :php:`\TYPO3\CMS\Core\Configuration\PageTsConfig` and their helper
classes :php:`\TYPO3\CMS\Core\Configuration\Loader\PageTsConfigLoader` and
:php:`\TYPO3\CMS\Core\Configuration\Parser\PageTsConfigParser` directly: The main API
in backend context is :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig()`.

See the hint on :php:`\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->getPagesTSconfig()`
above for notes on how to migrate usages in frontend context.


.. index:: PHP-API, TSConfig, TypoScript, FullyScanned, ext:core
