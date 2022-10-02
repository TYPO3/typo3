.. include:: /Includes.rst.txt

.. _feature-97862-1657195761:

=================================================================================================
Feature: #97862 - New PSR-14 events for manipulating frontend page generation and cache behaviour
=================================================================================================

See :issue:`97862`

Description
===========

Two new PSR-14 events have been added:

* :php:`TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent`
* :php:`TYPO3\CMS\Frontend\Event\AfterCachedPageIsPersistedEvent`

They are added in favor of the :doc:`removed hooks <../12.0/Breaking-97862-HooksRelatedToGeneratingPageContentRemoved>`:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['usePageCache']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['insertPageIncache']`

Both events are called when the content of a page has been
generated in the TYPO3 Frontend.

Example
=======

Registration of the `AfterCacheableContentIsGeneratedEvent` in your extension's :file:`Services.yaml`:

..  code-block:: yaml

    MyVendor\MyPackage\Backend\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-package/content-modifier'

The corresponding event listener class:

..  code-block:: php

    use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;

    class MyEventListener {

        public function __invoke(AfterCacheableContentIsGeneratedEvent $event): void
        {
            // Only do this when caching is enabled
            if (!$event->isCachingEnabled()) {
                return;
            }
            $event->getController()->content = str_replace('foo', 'bar', $event->getController()->content);
        }
    }

Impact
======

The event :php:`AfterCacheableContentIsGeneratedEvent` can be used
to decide if a page should be stored in cache and is executed right after
all cacheable content is generated. It can also be used to manipulate
the content before it is stored in TYPO3's page cache. The event is used
in indexed search to index cacheable content.

The :php:`AfterCacheableContentIsGeneratedEvent` contains the
information if a generated page is able to store in cache via the
:php:`$event->isCachingEnabled()` method. This can be used to
differentiate between the previous hooks `contentPostProc-cached` and
`contentPostProc-all` (do something regardless if caching is enabled or not).

The :php:`AfterCachedPageIsPersistedEvent` is commonly used to
generate a static file cache. This event is only called if the
page was actually stored in TYPO3's page cache.

.. index:: Frontend, PHP-API, ext:frontend
