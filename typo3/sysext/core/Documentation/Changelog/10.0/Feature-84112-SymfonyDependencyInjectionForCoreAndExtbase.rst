.. include:: /Includes.rst.txt

===================================================================
Feature: #84112 - Symfony dependency injection for core and extbase
===================================================================

See :issue:`84112`

Description
===========

The PHP library `symfony/dependency-injection` has been integrated and is used
to manage system wide dependency management and injection for classes.
With the integration provided in TYPO3 the symfony dependency injection container
features support for Extbase and non-Extbase classes and is thus intended to
replace the Extbase dependency injection container and object manager.
The symfony container implements :php:`\Psr\Container\ContainerInterface`
as specified by PSR-11. This interface should be used when requiring access
to the container.
Therefore :php:`\TYPO3\CMS\Extbase\Object\ObjectManager` now resorts to this
new dependency injection container and prioritizes its entries over classical
Extbase dependency injection (which is still available), also
:php:`\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance()` has been adapted
to retrieve instances from the container, if possible.

Classes should be adapted to avoid both, :php:`ObjectManager` and
:php:`GeneralUtility::makeInstance()` whenever possible.
Service dependencies should be injected via constructor injection or
setter methods (inject methods as in Extbase are supported).

Configuration
^^^^^^^^^^^^^

Extensions are encouraged to configure their classes to make use of the new
dependency injection. A symfony flavored yaml (or, for advanced functionality,
php) service configuration file may be used to do so. That means symfony
dependency injection is not applied automatically, extensions need to
define the desired dependency injection strategies. Extensions that do not
configure dependency injection will keep working – the legacy instance
management in :php:`\TYPO3\CMS\Extbase\Object\ObjectManager` and
:php:`\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance()` is
still available.

Whenever service configuration or class dependencies change, the core cache needs
to be flushed to rebuild the compiled symfony container.

Autowiring
----------

A :file:`Configuration/Services.yaml` which uses autowiring pretty much
reflects the current feature set of Extbase DI. The configuration looks like:

.. code-block:: yaml

    # Configuration/Services.yaml
    services:
      _defaults:
        autowire: true
        autoconfigure: true
        public: false

      Your\Namespace\:
        resource: '../Classes/*'

Extensions which have used Extbase dependency injection in the past, will want
to enable :yaml:`autowire` for a smooth migration. :yaml:`autowire: true` instructs symfony
to calculate the required dependencies from type declarations of the constructor
and inject methods. This calculation yields to a service initialization recipe
which is cached in php code (in TYPO3 core cache).
Note: An extension doesn't need to use autowiring, it is free to manually
wire dependencies in the service configuration file.

It is suggested to enable :yaml:`autoconfigure: true` as this will automatically
add symfony service tags based on implemented interfaces or base classes.
An Example: autoconfiguration ensured that classes which implement
:php:`\TYPO3\CMS\Core\SingletonInterface` will be publicly available from the
symfony container (which is required for legacy singleton lookups through
:php:`\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance()`).

:yaml:`public: false` is a performance optimization and is therefore suggested to be
enabled in extensions (symfony does not enable this by default for backwards
compatibility reasons only). This settings controls which services are available
through :php:`\Psr\Container\ContainerInterface->get()`. Services that need to be public
(e.g. Singletons, because they need to be shared with legacy code that uses
:php:`\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance()`, or Extbase controllers)
will be marked public automatically due to :yaml:`autoconfigure: true` by custom
TYPO3 provided symfony compiler passes.


Manual wiring
-------------

Manual dependency wiring and service configuration can be used instead of
autowiring (it can actually be combined). This speeds up container compilation
and allows for custom service configuration/wiring. It has the drawback of
having to write some boilerplate.

.. code-block:: yaml

    # Configuration/Services.yaml
    services:
      _defaults:
        autoconfigure: false
        public: false

      Your\Namespace\Service\ExampleService:
       # mark public – means this service should be accessible from $container->get()
       # and (often more important), both GeneralUtility::makeInstance() and the Extbase
       # ObjectManager will be able to use the Symfony DI managed service
       public: true
       # Defining a service to be shared is equal to TYPO3's SingletonInterface behaviour
       shared: true
       # Configure constructor arguments
       arguments:
         $siteConfiguration: '@TYPO3\CMS\Core\Configuration\SiteConfiguration'

      # Example Extbase controller
      Your\Namespace\Controller\ExampleController:
       # mark public to be dispatchable
       public: true
       # Defining to be a prototype, as Extbase controllers are stateful (i. e. could not be defined as singleton)
       shared: false
       # Configure constructor arguments
       arguments:
         $exampleService: '@Your\Namespace\Service\ExampleService'


For more information please refer to the official documentation:
https://symfony.com/doc/4.3/service_container.html


Advanced functionality
----------------------

Container compilation and configuration can be enhanced using
a callback function returned from :file:`Configuration/Services.php`.
Here is an example: Given an interface :php:`MyCustomInterface`,
you can automatically add a symfony tag for (autoconfigured) services that
implement this interface. A compiler pass can use that tag and configure
autoregistration into a registry service :php:`MyRegistry`.

.. code-block:: php

    # Configuration/Services.php
    <?php
    declare(strict_types = 1);
    namespace Your\Namespace;

    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

    return function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
        $containerBuilder->registerForAutoconfiguration(MyCustomInterface::class)->addTag('my.custom.interface');

        $containerBuilder->addCompilerPass(new DependencyInjection\MyCustomPass('my.custom.interface'));
    };

.. code-block:: php

    # Classes/DependencyInjection/MyCustomPass.php
    <?php
    declare(strict_types = 1);
    namespace Your\Namespace\DependencyInjection;

    use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Your\Namespace\MyRegistry;

    final class MyCustomPass implements CompilerPassInterface
    {
        public function process(ContainerBuilder $container)
        {
            $myRegistry = $container->findDefinition(MyRegistry::class);

            foreach ($container->findTaggedServiceIds('my.custom.interface') as $id => $tags) {
                $definition = $container->findDefinition($id);
                if (!$definition->isAutoconfigured() || $definition->isAbstract()) {
                    continue;
                }

                // Services that implement MyCustomInterface need to be public,
                // to be lazy loadable by the registry via $container->get()
                $container->findDefinition($id)->setPublic(true);
                // Add a method call to the registry class to the (auto-generated) factory for
                // the registry service.
                // This supersedes explicit registrations in ext_localconf.php (which're
                // still possible and can be combined with this autoconfiguration).
                $myRegistry->addMethodCall('registerMyCustomInterfaceImplementation', [$id]);
            }
        }
    }

Impact
======

* Symfony automatically resolves interfaces to classes when only one class
  implementing an interface is available. Otherwise an explicit alias is required.
  That means you SHOULD define an alias for interface to class mappings where
  the implementation currently defaults to the interface minus the trailing Interface
  suffix (which is the default for Extbase).

* Dependency Injection can be added to constructors of existing services
  without being breaking. :php:`GeneralUtility::makeInstance(ServiceName::class)`
  will keep working,  as :php:`makeInstance` has been adapted to resort to the
  symfony container.

* Cyclic dependencies are not supported with Symfony DI (Extbase DI did so).

* Prototypes/Data classes (non singletons, e.g. models) that need both,
  runtime constructor arguments (as passed to
  :php:`\TYPO3\CMS\Extbase\Object\ObjectManager->get()`) and injected dependencies
  are not supported in :php:`\Psr\Container\ContainerInterface->get()`.
  It is suggested to switch to factories or stick with the object manager for now.


.. index:: PHP-API, ext:core
