.. include:: /Includes.rst.txt

=======================================================================
Feature: #89054 - Provide core cache frontends via dependency injection
=======================================================================

See :issue:`89054`

Description
===========

With TYPO3 v10.0 dependency injection has been introduced. To work with
the cache, currently only the :php:`\TYPO3\CMS\Core\Cache\CacheManager` is available as a service within
the dependency injection container. To foster the „Inversion of Control“ pattern,
the instances of :php:`\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface` should
be injected to the objects rather than using the :php:`\TYPO3\CMS\Core\Cache\CacheManager`.

Classes should be adapted to avoid :php:`\TYPO3\CMS\Core\Cache\CacheManager` whenever possible.

The TYPO3 core provides all core caches as dependency injection services.
The name of the service follows the scheme :php:`cache.[CONFIGURATION NAME]`.
E.g. the core cache frontend will have the service id :php:`cache.core`.

Third party extensions are encouraged to do the same and provide a :php:`cache.my_cache`
service in :file:`Configuration/Services.yaml` for cache configuration they define
in :file:`ext_localconf.php`.

Usage
=====

Given a class needs the "my_cache" cache Frontend, then the code before TYPO3 v10.1
looked like the following example:

.. code-block:: php

   class MyClass
   {
       /**
        * @var TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
        */
       private $cache;

       public function __construct()
       {
           $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
           $this->cache = $cacheManager->getCache('my_cache');
       }
   }

The instance of :php:`\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface` was retrieved by creating an instance
of :php:`\TYPO3\CMS\Core\Cache\CacheManager` and then by calling the :php:`getCache()` method.

To inject the cache directly, the class needs to be changed as follows. The instance
of :php:`\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface` will be passed as an argument to the constructor.

.. code-block:: php

   class MyClass
   {
       /**
        * @var TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
        */
       private $cache;

       public function __construct(FrontendInterface $cache)
       {
           $this->cache = $cache;
       }
   }

Since the auto-wiring feature of the dependency injection container cannot detect,
which cache configuration should be used for the :php:`$cache` argument, the container
service configuration needs to be extended as well:

.. code-block:: yaml

    services:
      cache.my_cache:
        class: TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
        factory: ['@TYPO3\CMS\Core\Cache\CacheManager', 'getCache']
        arguments: ['my_cache']

      MyClass:
        arguments:
          $cache: '@cache.my_cache'

.. index:: PHP-API, ext:core
