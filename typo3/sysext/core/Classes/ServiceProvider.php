<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core;

use ArrayObject;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\SymfonyPsrEventDispatcherAdapter\EventDispatcherAdapter as SymfonyEventDispatcher;

/**
 * @internal
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected static function getPackagePath(): string
    {
        return __DIR__ . '/../';
    }

    public function getFactories(): array
    {
        return [
            SymfonyEventDispatcher::class => [ static::class, 'getSymfonyEventDispatcher' ],
            Cache\CacheManager::class => [ static::class, 'getCacheManager' ],
            Database\ConnectionPool::class => [ static::class, 'getConnectionPool' ],
            Charset\CharsetConverter::class => [ static::class, 'getCharsetConverter' ],
            Configuration\SiteConfiguration::class => [ static::class, 'getSiteConfiguration' ],
            Command\ListCommand::class => [ static::class, 'getListCommand' ],
            HelpCommand::class => [ static::class, 'getHelpCommand' ],
            Command\CacheFlushCommand::class => [ static::class, 'getCacheFlushCommand' ],
            Command\CacheWarmupCommand::class => [ static::class, 'getCacheWarmupCommand' ],
            Command\DumpAutoloadCommand::class => [ static::class, 'getDumpAutoloadCommand' ],
            Console\CommandApplication::class => [ static::class, 'getConsoleCommandApplication' ],
            Console\CommandRegistry::class => [ static::class, 'getConsoleCommandRegistry' ],
            Context\Context::class => [ static::class, 'getContext' ],
            Core\BootService::class => [ static::class, 'getBootService' ],
            Crypto\PasswordHashing\PasswordHashFactory::class => [ static::class, 'getPasswordHashFactory' ],
            EventDispatcher\EventDispatcher::class => [ static::class, 'getEventDispatcher' ],
            EventDispatcher\ListenerProvider::class => [ static::class, 'getEventListenerProvider' ],
            Http\MiddlewareStackResolver::class => [ static::class, 'getMiddlewareStackResolver' ],
            Http\RequestFactory::class => [ static::class, 'getRequestFactory' ],
            Imaging\IconFactory::class => [ static::class, 'getIconFactory' ],
            Imaging\IconProvider\FontawesomeIconProvider::class => [ static::class, 'getFontawesomeIconProvider' ],
            Imaging\IconRegistry::class => [ static::class, 'getIconRegistry' ],
            Localization\LanguageServiceFactory::class => [ static::class, 'getLanguageServiceFactory' ],
            Localization\LanguageStore::class => [ static::class, 'getLanguageStore' ],
            Localization\Locales::class => [ static::class, 'getLocales' ],
            Localization\LocalizationFactory::class => [ static::class, 'getLocalizationFactory' ],
            Mail\TransportFactory::class => [ static::class, 'getMailTransportFactory' ],
            Messaging\FlashMessageService::class => [ static::class, 'getFlashMessageService' ],
            Middleware\ResponsePropagation::class => [ static::class, 'getResponsePropagationMiddleware' ],
            Middleware\VerifyHostHeader::class => [ static::class, 'getVerifyHostHeaderMiddleware' ],
            Package\FailsafePackageManager::class => [ static::class, 'getFailsafePackageManager' ],
            Package\Cache\PackageDependentCacheIdentifier::class => [ static::class, 'getPackageDependentCacheIdentifier' ],
            Registry::class => [ static::class, 'getRegistry' ],
            Resource\Index\FileIndexRepository::class => [ static::class, 'getFileIndexRepository' ],
            Resource\Index\MetaDataRepository::class => [ static::class, 'getMetaDataRepository' ],
            Resource\Driver\DriverRegistry::class => [ static::class, 'getDriverRegistry' ],
            Resource\ProcessedFileRepository::class => [ static::class, 'getProcessedFileRepository' ],
            Resource\ResourceFactory::class => [ static::class, 'getResourceFactory' ],
            Resource\StorageRepository::class => [ static::class, 'getStorageRepository' ],
            Service\DependencyOrderingService::class => [ static::class, 'getDependencyOrderingService' ],
            Service\FlexFormService::class => [ static::class, 'getFlexFormService' ],
            Service\OpcodeCacheService::class => [ static::class, 'getOpcodeCacheService' ],
            TimeTracker\TimeTracker::class => [ static::class, 'getTimeTracker' ],
            TypoScript\Parser\ConstantConfigurationParser::class => [ static::class, 'getTypoScriptConstantConfigurationParser' ],
            TypoScript\TypoScriptService::class => [ static::class, 'getTypoScriptService' ],
            'icons' => [ static::class, 'getIcons' ],
            'middlewares' => [ static::class, 'getMiddlewares' ],
        ];
    }

    public function getExtensions(): array
    {
        return [
            Console\CommandRegistry::class => [ static::class, 'configureCommands' ],
            Imaging\IconRegistry::class => [ static::class, 'configureIconRegistry' ],
            EventDispatcherInterface::class => [ static::class, 'provideFallbackEventDispatcher' ],
            EventDispatcher\ListenerProvider::class => [ static::class, 'extendEventListenerProvider' ],
        ] + parent::getExtensions();
    }

    public static function getSymfonyEventDispatcher(ContainerInterface $container): SymfonyEventDispatcherInterface
    {
        return self::new($container, SymfonyEventDispatcher::class, [
            $container->get(EventDispatcherInterface::class),
        ]);
    }

    public static function getCacheManager(ContainerInterface $container): Cache\CacheManager
    {
        if (!$container->get('boot.state')->done) {
            throw new \LogicException(Cache\CacheManager::class . ' can not be injected/instantiated during ext_localconf.php loading. Use lazy loading instead.', 1549446998);
        }
        if (!$container->get('boot.state')->complete) {
            trigger_error(Cache\CacheManager::class . ' can not be injected/instantiated during ext_localconf.php/TCA/ext_tables.php loading. Use lazy loading instead.', E_USER_DEPRECATED);
            // @todo: Deprecation will be turned into the following LogicException after the deprecation grace period, likely ->complete will then be merged with ->done.
            //throw new \LogicException(Cache\CacheManager::class . ' can not be injected/instantiated during ext_localconf.php/TCA/ext_tables.php loading. Use lazy loading instead.', 1623925235);
        }

        $cacheConfigurations = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] ?? [];
        $disableCaching = $container->get('boot.state')->cacheDisabled;
        $defaultCaches = [
            $container->get('cache.core'),
            $container->get('cache.assets'),
            $container->get('cache.di'),
        ];

        $cacheManager = self::new($container, Cache\CacheManager::class, [$disableCaching]);
        $cacheManager->setCacheConfigurations($cacheConfigurations);
        $cacheConfigurations['di']['groups'] = ['system'];
        foreach ($defaultCaches as $cache) {
            $cacheManager->registerCache($cache, $cacheConfigurations[$cache->getIdentifier()]['groups'] ?? ['all']);
        }

        return $cacheManager;
    }

    public static function getConnectionPool(ContainerInterface $container): Database\ConnectionPool
    {
        if (!$container->get('boot.state')->complete) {
            trigger_error(Database\ConnectionPool::class . ' can not be injected/instantiated during ext_localconf.php/TCA/ext_tables.php loading. Use lazy loading instead.', E_USER_DEPRECATED);
            // @todo: Deprecation will be turned into the following LogicException after the deprecation grace period, likely ->complete will then be merged with ->done.
            //throw new \LogicException(Database\ConnectionPool::class . ' can not be injected/instantiated during ext_localconf.php/TCA/ext_tables.php loading. Use lazy loading instead.', 1623914347);
        }

        return self::new($container, Database\ConnectionPool::class);
    }

    public static function getCharsetConverter(ContainerInterface $container): Charset\CharsetConverter
    {
        return self::new($container, Charset\CharsetConverter::class);
    }

    public static function getSiteConfiguration(ContainerInterface $container): Configuration\SiteConfiguration
    {
        return self::new($container, Configuration\SiteConfiguration::class, [
            Environment::getConfigPath() . '/sites',
            $container->get('cache.core'),
        ]);
    }

    public static function getListCommand(ContainerInterface $container): Command\ListCommand
    {
        return new Command\ListCommand(
            $container,
            $container->get(Core\BootService::class)
        );
    }

    public static function getHelpCommand(ContainerInterface $container): HelpCommand
    {
        return new HelpCommand();
    }

    public static function getCacheFlushCommand(ContainerInterface $container): Command\CacheFlushCommand
    {
        return new Command\CacheFlushCommand(
            $container->get(Core\BootService::class),
            $container->get('cache.di')
        );
    }

    public static function getCacheWarmupCommand(ContainerInterface $container): Command\CacheWarmupCommand
    {
        return new Command\CacheWarmupCommand(
            $container->get(ContainerBuilder::class),
            $container->get(Package\PackageManager::class),
            $container->get(Core\BootService::class),
            $container->get('cache.di')
        );
    }

    public static function getDumpAutoloadCommand(ContainerInterface $container): Command\DumpAutoloadCommand
    {
        return new Command\DumpAutoloadCommand();
    }

    public static function getConsoleCommandApplication(ContainerInterface $container): Console\CommandApplication
    {
        return new Console\CommandApplication(
            $container->get(Context\Context::class),
            $container->get(Console\CommandRegistry::class),
            $container->get(SymfonyEventDispatcher::class),
            $container->get(Configuration\ConfigurationManager::class),
            $container->get(Core\BootService::class),
            $container->get(Localization\LanguageServiceFactory::class)
        );
    }

    public static function getConsoleCommandRegistry(ContainerInterface $container): Console\CommandRegistry
    {
        return new Console\CommandRegistry($container);
    }

    public static function getEventDispatcher(ContainerInterface $container): EventDispatcher\EventDispatcher
    {
        return new EventDispatcher\EventDispatcher(
            $container->get(EventDispatcher\ListenerProvider::class)
        );
    }

    public static function getEventListenerProvider(ContainerInterface $container): EventDispatcher\ListenerProvider
    {
        return new EventDispatcher\ListenerProvider($container);
    }

    public static function extendEventListenerProvider(
        ContainerInterface $container,
        EventDispatcher\ListenerProvider $listenerProvider
    ): EventDispatcher\ListenerProvider {
        $listenerProvider->addListener(
            Package\Event\PackagesMayHaveChangedEvent::class,
            Package\PackageManager::class,
            'packagesMayHaveChanged'
        );

        $cacheWarmers = [
            Configuration\SiteConfiguration::class,
            Http\MiddlewareStackResolver::class,
            Imaging\IconRegistry::class,
            Package\PackageManager::class,
        ];
        foreach ($cacheWarmers as $service) {
            $listenerProvider->addListener(Cache\Event\CacheWarmupEvent::class, $service, 'warmupCaches');
        }

        $listenerProvider->addListener(Cache\Event\CacheFlushEvent::class, Cache\CacheManager::class, 'handleCacheFlushEvent');

        return $listenerProvider;
    }

    public static function getContext(ContainerInterface $container): Context\Context
    {
        return new Context\Context();
    }

    public static function getBootService(ContainerInterface $container): Core\BootService
    {
        if ($container->has('_early.boot-service')) {
            return $container->get('_early.boot-service');
        }
        return new Core\BootService(
            $container->get(ContainerBuilder::class),
            $container
        );
    }

    public static function getPasswordHashFactory(ContainerInterface $container): Crypto\PasswordHashing\PasswordHashFactory
    {
        return new Crypto\PasswordHashing\PasswordHashFactory();
    }

    public static function getIconFactory(ContainerInterface $container): Imaging\IconFactory
    {
        return self::new($container, Imaging\IconFactory::class, [
            $container->get(EventDispatcherInterface::class),
            $container->get(Imaging\IconRegistry::class),
            $container,
        ]);
    }

    public static function configureIconRegistry(ContainerInterface $container, IconRegistry $iconRegistry): IconRegistry
    {
        $cache = $container->get('cache.core');

        $cacheIdentifier = $container->get(Package\Cache\PackageDependentCacheIdentifier::class)->withPrefix('Icons')->toString();
        $iconsFromPackages = $cache->require($cacheIdentifier);
        if ($iconsFromPackages === false) {
            $iconsFromPackages = $container->get('icons')->getArrayCopy();
            $cache->set($cacheIdentifier, 'return ' . var_export($iconsFromPackages, true) . ';');
        }

        foreach ($iconsFromPackages as $icon => $options) {
            $provider = $options['provider'] ?? null;
            unset($options['provider']);
            $options ??= [];
            if ($provider === null && ($options['source'] ?? false)) {
                $provider = $iconRegistry->detectIconProvider($options['source']);
            }
            if ($provider === null) {
                continue;
            }
            $iconRegistry->registerIcon($icon, $provider, $options);
        }
        return $iconRegistry;
    }

    public static function getIcons(ContainerInterface $container): ArrayObject
    {
        return new ArrayObject();
    }

    public static function getFontawesomeIconProvider(ContainerInterface $container): Imaging\IconProvider\FontawesomeIconProvider
    {
        return self::new($container, Imaging\IconProvider\FontawesomeIconProvider::class, [
            $container->get('cache.assets'),
            $container->get(Package\Cache\PackageDependentCacheIdentifier::class)->withPrefix('FontawesomeSvgIcons')->toString(),
        ]);
    }

    public static function getIconRegistry(ContainerInterface $container): Imaging\IconRegistry
    {
        return self::new($container, Imaging\IconRegistry::class, [$container->get('cache.assets'), $container->get(Package\Cache\PackageDependentCacheIdentifier::class)->withPrefix('BackendIcons')->toString()]);
    }

    public static function getLanguageServiceFactory(ContainerInterface $container): Localization\LanguageServiceFactory
    {
        return self::new($container, Localization\LanguageServiceFactory::class, [
            $container->get(Localization\Locales::class),
            $container->get(Localization\LocalizationFactory::class),
            $container->get(Cache\CacheManager::class)->getCache('runtime'),
        ]);
    }

    public static function getLanguageStore(ContainerInterface $container): Localization\LanguageStore
    {
        return self::new($container, Localization\LanguageStore::class, [$container->get(PackageManager::class)]);
    }

    public static function getLocales(ContainerInterface $container): Localization\Locales
    {
        return self::new($container, Localization\Locales::class);
    }

    public static function getLocalizationFactory(ContainerInterface $container): Localization\LocalizationFactory
    {
        return self::new($container, Localization\LocalizationFactory::class, [
            $container->get(Localization\LanguageStore::class),
            $container->get(Cache\CacheManager::class),
        ]);
    }

    public static function getMailTransportFactory(ContainerInterface $container): Mail\TransportFactory
    {
        return self::new($container, Mail\TransportFactory::class, [
            $container->get(SymfonyEventDispatcher::class),
            $container->get(Log\LogManager::class),
        ]);
    }

    public static function getFlashMessageService(ContainerInterface $container): Messaging\FlashMessageService
    {
        return self::new($container, Messaging\FlashMessageService::class);
    }

    public static function getResponsePropagationMiddleware(ContainerInterface $container): Middleware\ResponsePropagation
    {
        return self::new($container, Middleware\ResponsePropagation::class);
    }

    public static function getVerifyHostHeaderMiddleware(ContainerInterface $container): Middleware\VerifyHostHeader
    {
        return self::new($container, Middleware\VerifyHostHeader::class, [
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] ?? '',
        ]);
    }

    public static function getFailsafePackageManager(ContainerInterface $container): Package\FailsafePackageManager
    {
        $packageManager = $container->get(Package\PackageManager::class);
        if ($packageManager instanceof Package\FailsafePackageManager) {
            return $packageManager;
        }
        throw new \RuntimeException('FailsafePackageManager can only be instantiated in failsafe (maintenance tool) mode.', 1586861816);
    }

    public static function getPackageDependentCacheIdentifier(ContainerInterface $container): Package\Cache\PackageDependentCacheIdentifier
    {
        return new Package\Cache\PackageDependentCacheIdentifier($container->get(Package\PackageManager::class));
    }

    public static function getRegistry(ContainerInterface $container): Registry
    {
        return self::new($container, Registry::class);
    }

    public static function getFileIndexRepository(ContainerInterface $container): Resource\Index\FileIndexRepository
    {
        return self::new($container, Resource\Index\FileIndexRepository::class, [
            $container->get(EventDispatcherInterface::class),
        ]);
    }

    public static function getMetaDataRepository(ContainerInterface $container): Resource\Index\MetaDataRepository
    {
        return self::new($container, Resource\Index\MetaDataRepository::class, [
            $container->get(EventDispatcherInterface::class),
        ]);
    }

    public static function getDriverRegistry(ContainerInterface $container): Resource\Driver\DriverRegistry
    {
        return self::new($container, Resource\Driver\DriverRegistry::class);
    }

    public static function getProcessedFileRepository(ContainerInterface $container): Resource\ProcessedFileRepository
    {
        return self::new($container, Resource\ProcessedFileRepository::class);
    }

    public static function getResourceFactory(ContainerInterface $container): Resource\ResourceFactory
    {
        return self::new($container, Resource\ResourceFactory::class, [
            $container->get(Resource\StorageRepository::class),
        ]);
    }

    public static function getStorageRepository(ContainerInterface $container): Resource\StorageRepository
    {
        return self::new($container, Resource\StorageRepository::class, [
            $container->get(EventDispatcherInterface::class),
            $container->get(Resource\Driver\DriverRegistry::class),
        ]);
    }

    public static function getDependencyOrderingService(ContainerInterface $container): Service\DependencyOrderingService
    {
        return new Service\DependencyOrderingService();
    }

    public static function getFlexFormService(ContainerInterface $container): Service\FlexFormService
    {
        return self::new($container, Service\FlexFormService::class);
    }

    public static function getOpcodeCacheService(ContainerInterface $container): Service\OpcodeCacheService
    {
        return self::new($container, Service\OpcodeCacheService::class);
    }

    public static function getTimeTracker(ContainerInterface $container): TimeTracker\TimeTracker
    {
        return self::new($container, TimeTracker\TimeTracker::class);
    }

    public static function getTypoScriptConstantConfigurationParser(ContainerInterface $container): TypoScript\Parser\ConstantConfigurationParser
    {
        return self::new($container, TypoScript\Parser\ConstantConfigurationParser::class);
    }

    public static function getTypoScriptService(ContainerInterface $container): TypoScript\TypoScriptService
    {
        return self::new($container, TypoScript\TypoScriptService::class);
    }

    public static function getRequestFactory(ContainerInterface $container): Http\RequestFactory
    {
        return new Http\RequestFactory();
    }

    public static function getMiddlewareStackResolver(ContainerInterface $container): Http\MiddlewareStackResolver
    {
        return new Http\MiddlewareStackResolver(
            $container,
            $container->get(Service\DependencyOrderingService::class),
            $container->get('cache.core'),
            $container->get(Package\Cache\PackageDependentCacheIdentifier::class)->toString(),
        );
    }

    public static function getMiddlewares(ContainerInterface $container): ArrayObject
    {
        return new ArrayObject();
    }

    public static function provideFallbackEventDispatcher(
        ContainerInterface $container,
        EventDispatcherInterface $eventDispatcher = null
    ): EventDispatcherInterface {
        // Provide a dummy / empty event dispatcher for the install tool when $eventDispatcher is null (that means when we run without symfony DI)
        return $eventDispatcher ?? new EventDispatcher\EventDispatcher(
            new EventDispatcher\ListenerProvider($container)
        );
    }

    public static function configureCommands(ContainerInterface $container, Console\CommandRegistry $commandRegistry): Console\CommandRegistry
    {
        $commandRegistry->addLazyCommand('list', Command\ListCommand::class, 'Lists commands');

        $commandRegistry->addLazyCommand('help', HelpCommand::class, 'Displays help for a command');

        $commandRegistry->addLazyCommand('cache:warmup', Command\CacheWarmupCommand::class, 'Cache warmup for all, system or, if implemented, frontend caches.');

        $commandRegistry->addLazyCommand('cache:flush', Command\CacheFlushCommand::class, 'Cache clearing for all, system or frontend caches.');

        $commandRegistry->addLazyCommand('dumpautoload', Command\DumpAutoloadCommand::class, 'Updates class loading information in non-composer mode.', Environment::isComposerMode());
        $commandRegistry->addLazyCommand('extensionmanager:extension:dumpclassloadinginformation', Command\DumpAutoloadCommand::class, null, Environment::isComposerMode(), false, 'dumpautoload');
        $commandRegistry->addLazyCommand('extension:dumpclassloadinginformation', Command\DumpAutoloadCommand::class, null, Environment::isComposerMode(), false, 'dumpautoload');

        return $commandRegistry;
    }
}
