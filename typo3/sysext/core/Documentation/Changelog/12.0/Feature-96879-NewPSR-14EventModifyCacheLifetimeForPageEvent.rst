.. include:: /Includes.rst.txt

.. _feature-96879-1663513042:

==================================================================
Feature: #96879 - New PSR-14 event ModifyCacheLifetimeForPageEvent
==================================================================

See :issue:`96879`

Description
===========

A new PSR-14 event :php:`ModifyCacheLifetimeForPageEvent` has been introduced.
This event serves as a successor for the
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['get_cache_timeout']`
hook.

Impact
======

The new event allows to modify the lifetime how long a rendered page of a
frontend call should be stored in the "pages" cache.

Common registration and usage for a listener:

..  code-block:: yaml

    services:
      MyCompany\MyPackage\EventListener\ChangeCacheTimeout:
        tags:
          - name: event.listener
            identifier: 'mycompany/mypackage/cache-timeout'

..  code-block:: php

    <?php

    namespace MyCompany\MyPackage\EventListener;

    class ChangeCacheTimeout
    {
        public function __invoke(ModifyCacheLifetimeForPageEvent $event): void
        {
            // Only cache all pages for 30 seconds when in development context
            if (Environment::getContext()->isDevelopment()) {
                $event->setCacheLifetime(30);
            }
        }
    }

.. index:: Frontend, PHP-API, ext:frontend
