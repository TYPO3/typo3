.. include:: /Includes.rst.txt

.. _feature-96968-1663513232:

==========================================================================
Feature: #96968 - PSR-14 event for avoid loading Frontend pages from cache
==========================================================================

See :issue:`96968`

Description
===========

A new PSR-14 event :php:`ShouldUseCachedPageDataIfAvailableEvent` is added which
allows TYPO3 Extensions to register event listeners to modify if a page should
be read from cache (if it has been created in store already), or if it should
be re-built completely ignoring the cache entry for the request.

Impact
======

The new PSR-14 event can be used for avoiding loading from cache when indexing
via CLI happens from an external source, or if the cache should be ignored when
logged in from a certain IP address.

Registration of the event in your extension's :file:`Services.yaml`:

..  code-block:: yaml

    MyVendor\MyPackage\MyEventListener:
      tags:
        - name: event.listener
          identifier: 'my-package/avoid-cache-loading'

The corresponding event listener class:

..  code-block:: php

    use TYPO3\CMS\Frontend\Event\ShouldUseCachedPageDataIfAvailableEvent;

    class MyEventListener {

        public function __invoke(ShouldUseCachedPageDataIfAvailableEvent $event): void
        {
            if (!($event->getRequest()->getServerParams()['X-SolR-API'] ?? null)) {
                return;
            }
            $event->setShouldUseCachedPageData(false);
        }
    }

.. index:: Frontend, PHP-API, ext:frontend
