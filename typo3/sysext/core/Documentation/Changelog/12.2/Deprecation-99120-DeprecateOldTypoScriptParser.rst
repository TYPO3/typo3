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
before parsing. The old event :php:`\TYPO3\CMS\Core\Configuration\Event\ModifyLoadedPageTsConfigEvent`
has been marked as deprecated and will be removed with TYPO3 v13, the new event
:php:`\TYPO3\CMS\Core\TypoScript\IncludeTree\Event\ModifyLoadedPageTsConfigEvent` has been
created with same signature. The TYPO3 v12 Core triggers *both* the old
and the new event, and TYPO3 v13 will stop calling the old event.

Extension that want to stay compatible with both TYPO3 v11 and v12 and prepare v13
compatibility as much as possible should start listening for the new event as well,
and suppress handling of the old event in TYPO3 v12 to not handle things twice.

Example from b13/bolt extension:

Register for both events in Services.yaml:

.. code-block:: yaml

  B13\Bolt\TsConfig\Loader:
    public: true
    tags:
      # Remove when TYPO3 v11 compat is dropped
      - name: event.listener
        identifier: 'add-site-configuration-v11'
        event: TYPO3\CMS\Core\Configuration\Event\ModifyLoadedPageTsConfigEvent
        method: 'addSiteConfigurationCore11'
      # TYPO3 v12 and above
      - name: event.listener
        identifier: 'add-site-configuration'
        event: TYPO3\CMS\Core\TypoScript\IncludeTree\Event\ModifyLoadedPageTsConfigEvent
        method: 'addSiteConfiguration'

Handle old event in TYPO3 v11, but skip old event with TYPO3 v12:

.. code-block:: php

    use TYPO3\CMS\Core\Configuration\Event\ModifyLoadedPageTsConfigEvent as LegacyModifyLoadedPageTsConfigEvent;
    use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\ModifyLoadedPageTsConfigEvent;

    class Loader
    {
        public function addSiteConfigurationCore11(LegacyModifyLoadedPageTsConfigEvent $event): void
        {
            if (class_exists(ModifyLoadedPageTsConfigEvent::class)) {
                // TYPO3 v12 calls both old and new event. Check for class existence of new event to
                // skip handling of old event in v12, but continue to work with < v12.
                // Simplify this construct when v11 compat is dropped, clean up Services.yaml.
                return;
            }
            $this->findAndAddConfiguration($event);
        }

        public function addSiteConfiguration(ModifyLoadedPageTsConfigEvent $event): void
        {
            $this->findAndAddConfiguration($event);
        }

        protected function findAndAddConfiguration($event): void
        {
            // Business code
        }
    }


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
