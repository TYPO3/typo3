..  include:: /Includes.rst.txt

..  _breaking-110578-1788251919:

=================================================================
Breaking: #110578 - SingletonInterface removed from core services
=================================================================

See :issue:`110578`

Description
===========

:php:`\TYPO3\CMS\Core\SingletonInterface` predates dependency injection.
Today it has exactly one effect: it turns a class into a *shared* and
*public* service in the dependency injection container, so that the legacy
:php:`GeneralUtility::makeInstance()` can still reach it.

Both properties are already the default for injected services. Every
service in the container is shared, so every class that receives a service
through constructor injection gets the very same instance - that is what
the marker used to guarantee. A service only needs to be *public* on top
of that if it is pulled out of the container by hand.

The marker has therefore been removed from a number of Core services that
are resolved through dependency injection anyway. Their lifetime does not
change: they are still shared.

Impact
======

These classes do not implement :php:`SingletonInterface` anymore:

-   :php:`\TYPO3\CMS\Adminpanel\Service\ConfigurationService`
-   :php:`\TYPO3\CMS\Adminpanel\Service\ProcessedImageCollector`
-   :php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderCollection`
-   :php:`\TYPO3\CMS\Core\Cache\CacheManager`
-   :php:`\TYPO3\CMS\Core\Console\CommandRegistry`
-   :php:`\TYPO3\CMS\Core\Error\AbstractExceptionHandler`
-   :php:`\TYPO3\CMS\Core\Localization\Locales`
-   :php:`\TYPO3\CMS\Core\Messaging\FlashMessageService`
-   :php:`\TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry`
-   :php:`\TYPO3\CMS\Core\PageTitle\PageTitleProviderManager`
-   :php:`\TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry`
-   :php:`\TYPO3\CMS\Core\Resource\Processing\TaskTypeRegistry`
-   :php:`\TYPO3\CMS\Core\Resource\Rendering\RendererRegistry`
-   :php:`\TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorRegistry`
-   :php:`\TYPO3\CMS\Install\Service\SessionService`
-   :php:`\TYPO3\CMS\Scheduler\Scheduler`
-   :php:`\TYPO3\CMS\Workspaces\Service\Dependency\CollectionService`

:php:`GeneralUtility::setSingletonInstance()` and
:php:`GeneralUtility::removeSingletonInstance()` only accept
:php:`SingletonInterface` instances and raise a :php:`TypeError` for these
classes. This mostly affects tests that substitute one of them.

Some of these services are not public anymore either, so fetching them
with :php:`GeneralUtility::makeInstance()` or :php:`$container->get()`
fails with an :php:`ArgumentCountError` or a
:php:`ServiceNotFoundException`. Whether a Core service is public is an
implementation detail and must not be relied upon.

Affected installations
======================

Installations with extensions that fetch one of the listed classes through
:php:`GeneralUtility::makeInstance()` or the container, or that replace one
of them in tests via :php:`GeneralUtility::setSingletonInstance()`.

Migration
=========

Inject the service instead of fetching it. Since services are shared, the
injected instance is the same one the rest of the request uses:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Service/MyService.php

    use TYPO3\CMS\Core\Localization\Locales;

    final readonly class MyService
    {
        public function __construct(
            private Locales $locales,
        ) {}

        public function doSomething(): void
        {
            // instead of GeneralUtility::makeInstance(Locales::class)
            $this->locales->createLocale('de');
        }
    }

The same applies to tests that replaced one of these classes globally with
:php:`GeneralUtility::setSingletonInstance()`. Hand the dependency to the
subject instead: a stub or a mock if the test only needs the collaborator
to be there, or a functional test if it should exercise the real one.

This is cleaner and framework-agnostic: the subject is built with plain
PHP, and everything it works with is visible in the test itself, instead
of being smuggled in through a global registry that the code under test
happens to read from.

:php:`SingletonInterface` itself is not deprecated and keeps working for
custom classes. If a class of your own cannot use dependency injection -
typically because it is instantiated with constructor arguments, which
bypasses the container - it can also be made public explicitly, without
the marker:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Service/MyLegacyService.php

    use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

    #[Autoconfigure(public: true)]
    final class MyLegacyService
    {
    }

..  index:: PHP-API, NotScanned, ext:core
