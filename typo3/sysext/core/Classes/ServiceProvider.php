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

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Yaml\Command\LintCommand as SymfonyLintCommand;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;
use TYPO3\CMS\Core\Adapter\EventDispatcherAdapter as SymfonyEventDispatcher;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Database\Schema\DefaultTcaSchema;
use TYPO3\CMS\Core\Database\Schema\Parser\Lexer;
use TYPO3\CMS\Core\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\Map;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LossyTokenizer;

/**
 * @internal
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected static function getPackagePath(): string
    {
        return __DIR__ . '/../';
    }

    protected static function getPackageName(): string
    {
        return 'typo3/cms-core';
    }

    public function getFactories(): array
    {
        return [
            SymfonyEventDispatcher::class => self::getSymfonyEventDispatcher(...),
            SymfonyLintCommand::class => self::getSymfonyLintCommand(...),
            Cache\CacheManager::class => self::getCacheManager(...),
            Database\ConnectionPool::class => self::getConnectionPool(...),
            Database\DriverMiddlewareService::class => self::getDriverMiddlewaresService(...),
            Charset\CharsetConverter::class => self::getCharsetConverter(...),
            Configuration\Loader\YamlFileLoader::class => self::getYamlFileLoader(...),
            Configuration\SiteWriter::class => self::getSiteWriter(...),
            Command\ListCommand::class => self::getListCommand(...),
            HelpCommand::class => self::getHelpCommand(...),
            Command\CacheFlushCommand::class => self::getCacheFlushCommand(...),
            Command\CacheWarmupCommand::class => self::getCacheWarmupCommand(...),
            Command\DumpAutoloadCommand::class => self::getDumpAutoloadCommand(...),
            Console\CommandApplication::class => self::getConsoleCommandApplication(...),
            Console\CommandRegistry::class => self::getConsoleCommandRegistry(...),
            Context\Context::class => self::getContext(...),
            Core\BootService::class => self::getBootService(...),
            Crypto\HashService::class => self::getHashService(...),
            Crypto\PasswordHashing\PasswordHashFactory::class => self::getPasswordHashFactory(...),
            Database\Schema\SchemaMigrator::class => self::getSchemaMigrator(...),
            Database\Schema\Parser\Parser::class => self::getSchemaParser(...),
            EventDispatcher\EventDispatcher::class => self::getEventDispatcher(...),
            EventDispatcher\ListenerProvider::class => self::getEventListenerProvider(...),
            FormProtection\FormProtectionFactory::class => self::getFormProtectionFactory(...),
            Http\Application::class => self::getHttpApplication(...),
            Http\RequestHandler::class => self::getHttpRequestHandler(...),
            Http\Client\GuzzleClientFactory::class => self::getGuzzleClientFactory(...),
            Http\MiddlewareStackResolver::class => self::getMiddlewareStackResolver(...),
            Http\RequestFactory::class => self::getRequestFactory(...),
            Imaging\IconFactory::class => self::getIconFactory(...),
            Imaging\IconRegistry::class => self::getIconRegistry(...),
            Localization\LanguageServiceFactory::class => self::getLanguageServiceFactory(...),
            Localization\LanguageStore::class => self::getLanguageStore(...),
            Localization\Locales::class => self::getLocales(...),
            Localization\LocalizationFactory::class => self::getLocalizationFactory(...),
            Mail\Mailer::class => self::getMailer(...),
            Mail\TransportFactory::class => self::getMailTransportFactory(...),
            Messaging\FlashMessageService::class => self::getFlashMessageService(...),
            Middleware\ResponsePropagation::class => self::getResponsePropagationMiddleware(...),
            Middleware\VerifyHostHeader::class => self::getVerifyHostHeaderMiddleware(...),
            Package\FailsafePackageManager::class => self::getFailsafePackageManager(...),
            Package\Cache\PackageDependentCacheIdentifier::class => self::getPackageDependentCacheIdentifier(...),
            Routing\BackendEntryPointResolver::class => self::getBackendEntryPointResolver(...),
            Routing\RequestContextFactory::class => self::getRequestContextFactory(...),
            Registry::class => self::getRegistry(...),
            Resource\Index\FileIndexRepository::class => self::getFileIndexRepository(...),
            Resource\Index\MetaDataRepository::class => self::getMetaDataRepository(...),
            Resource\Driver\DriverRegistry::class => self::getDriverRegistry(...),
            Resource\ProcessedFileRepository::class => self::getProcessedFileRepository(...),
            Resource\ResourceFactory::class => self::getResourceFactory(...),
            Resource\StorageRepository::class => self::getStorageRepository(...),
            Service\DependencyOrderingService::class => self::getDependencyOrderingService(...),
            Service\FlexFormService::class => self::getFlexFormService(...),
            Service\OpcodeCacheService::class => self::getOpcodeCacheService(...),
            TypoScript\TypoScriptStringFactory::class => self::getTypoScriptStringFactory(...),
            TypoScript\TypoScriptService::class => self::getTypoScriptService(...),
            TypoScript\AST\Traverser\AstTraverser::class => self::getAstTraverser(...),
            TypoScript\AST\CommentAwareAstBuilder::class => self::getCommentAwareAstBuilder(...),
            TypoScript\Tokenizer\LosslessTokenizer::class => [ self::class, 'getLosslessTokenizer'],
            'icons' => self::getIcons(...),
            'middlewares' => self::getMiddlewares(...),
            'cache.assets' => self::getAssetsCache(...),
            'cache.runtime' => self::getRuntimeCache(...),
            'core.middlewares' => self::getCoreMiddlewares(...),
            'content.security.policies' => self::getContentSecurityPolicies(...),
        ];
    }

    public function getExtensions(): array
    {
        return [
            Console\CommandRegistry::class => self::configureCommands(...),
            Imaging\IconRegistry::class => self::configureIconRegistry(...),
            EventDispatcherInterface::class => self::provideFallbackEventDispatcher(...),
            EventDispatcher\ListenerProvider::class => self::extendEventListenerProvider(...),
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
        if (!$container->get('boot.state')->complete) {
            throw new \LogicException(Cache\CacheManager::class . ' can not be injected/instantiated during ext_localconf.php or TCA loading. Use lazy loading instead.', 1638976434);
        }

        $cacheConfigurations = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] ?? [];
        $disableCaching = $container->get('boot.state')->cacheDisabled;
        $defaultCaches = [
            $container->get('cache.core'),
            $container->get('cache.assets'),
            $container->get('cache.runtime'),
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
            throw new \LogicException(Database\ConnectionPool::class . ' can not be injected/instantiated during ext_localconf.php or TCA loading. Use lazy loading instead.', 1638976490);
        }

        return self::new($container, Database\ConnectionPool::class);
    }

    public static function getDriverMiddlewaresService(ContainerInterface $container): Database\DriverMiddlewareService
    {
        return self::new($container, Database\DriverMiddlewareService::class, [
            $container->get(Service\DependencyOrderingService::class),
        ]);
    }

    public static function getCharsetConverter(ContainerInterface $container): Charset\CharsetConverter
    {
        return self::new($container, Charset\CharsetConverter::class);
    }

    public static function getYamlFileLoader(ContainerInterface $container): Configuration\Loader\YamlFileLoader
    {
        return self::new($container, Configuration\Loader\YamlFileLoader::class, [
            $container->get(Log\LogManager::class)->getLogger(Configuration\Loader\YamlFileLoader::class),
        ]);
    }

    public static function getSiteWriter(ContainerInterface $container): Configuration\SiteWriter
    {
        return self::new($container, Configuration\SiteWriter::class, [
            Environment::getConfigPath() . '/sites',
            $container->get(EventDispatcherInterface::class),
            $container->get(YamlFileLoader::class),
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

    public static function getSymfonyLintCommand(ContainerInterface $container): SymfonyLintCommand
    {
        return new SymfonyLintCommand();
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

    public static function getSchemaMigrator(ContainerInterface $container): Database\Schema\SchemaMigrator
    {
        return self::new($container, Database\Schema\SchemaMigrator::class, [
            $container->get(Database\ConnectionPool::class),
            $container->get(Database\Schema\Parser\Parser::class),
            new DefaultTcaSchema(),
        ]);
    }

    public static function getSchemaParser(ContainerInterface $container): Database\Schema\Parser\Parser
    {
        return self::new($container, Database\Schema\Parser\Parser::class, [
            new Lexer(),
        ]);
    }

    public static function getIconFactory(ContainerInterface $container): Imaging\IconFactory
    {
        return self::new($container, Imaging\IconFactory::class, [
            $container->get(EventDispatcherInterface::class),
            $container->get(Imaging\IconRegistry::class),
            $container,
            $container->get('cache.runtime'),
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

    public static function getIcons(ContainerInterface $container): \ArrayObject
    {
        return new \ArrayObject();
    }

    public static function getIconRegistry(ContainerInterface $container): Imaging\IconRegistry
    {
        if ($container->get('boot.state')->complete === false) {
            trigger_error(
                'Instantiating \TYPO3\CMS\Core\Imaging\IconRegistry in ext_localconf.php should be replaced by'
                . ' either Configuration/Icons.php or by listening to \TYPO3\CMS\Core\Core\Event\BootCompletedEvent',
                E_USER_DEPRECATED
            );
        }
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

    public static function getMailer(ContainerInterface $container): Mail\Mailer
    {
        return self::new($container, Mail\Mailer::class, [
            null,
            $container->get(EventDispatcherInterface::class),
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
        return self::new($container, Resource\ProcessedFileRepository::class, [
            $container->get(ResourceFactory::class),
            $container->get(Resource\Processing\TaskTypeRegistry::class),
        ]);
    }

    public static function getResourceFactory(ContainerInterface $container): Resource\ResourceFactory
    {
        return self::new($container, Resource\ResourceFactory::class, [
            $container->get(Resource\StorageRepository::class),
            $container->get('cache.runtime'),
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

    public static function getTypoScriptStringFactory(ContainerInterface $container): TypoScript\TypoScriptStringFactory
    {
        return new TypoScript\TypoScriptStringFactory($container, new LossyTokenizer());
    }

    public static function getTypoScriptService(ContainerInterface $container): TypoScript\TypoScriptService
    {
        return self::new($container, TypoScript\TypoScriptService::class);
    }

    public static function getAstTraverser(ContainerInterface $container): TypoScript\AST\Traverser\AstTraverser
    {
        return self::new($container, TypoScript\AST\Traverser\AstTraverser::class);
    }

    public static function getCommentAwareAstBuilder(ContainerInterface $container): TypoScript\AST\CommentAwareAstBuilder
    {
        return self::new($container, TypoScript\AST\CommentAwareAstBuilder::class, [
            $container->get(EventDispatcherInterface::class),
        ]);
    }

    public static function getLosslessTokenizer(ContainerInterface $container): TypoScript\Tokenizer\LosslessTokenizer
    {
        return self::new($container, TypoScript\Tokenizer\LosslessTokenizer::class);
    }

    public static function getBackendEntryPointResolver(ContainerInterface $container): Routing\BackendEntryPointResolver
    {
        return self::new($container, Routing\BackendEntryPointResolver::class);
    }

    public static function getHttpApplication(ContainerInterface $container): Http\Application
    {
        $requestHandler = new Http\MiddlewareDispatcher(
            $container->get(Http\RequestHandler::class),
            $container->get('core.middlewares'),
        );

        return self::new($container, Http\Application::class, [
            $requestHandler,
            $container->get(Configuration\ConfigurationManager::class),
        ]);
    }

    public static function getHttpRequestHandler(ContainerInterface $container): Http\RequestHandler
    {
        return new Http\RequestHandler(
            $container,
            $container->get(Routing\BackendEntryPointResolver::class),
        );
    }

    public static function getRequestContextFactory(ContainerInterface $container): Routing\RequestContextFactory
    {
        return self::new($container, Routing\RequestContextFactory::class, [
            $container->get(Routing\BackendEntryPointResolver::class),
        ]);
    }

    public static function getFormProtectionFactory(ContainerInterface $container): FormProtection\FormProtectionFactory
    {
        return self::new(
            $container,
            FormProtection\FormProtectionFactory::class,
            [
                $container->get(Messaging\FlashMessageService::class),
                $container->get(Localization\LanguageServiceFactory::class),
                $container->get(Registry::class),
                $container->get(CacheManager::class)->getCache('runtime'),
            ]
        );
    }

    public static function getGuzzleClientFactory(ContainerInterface $container): Http\Client\GuzzleClientFactory
    {
        return new Http\Client\GuzzleClientFactory();
    }

    public static function getRequestFactory(ContainerInterface $container): Http\RequestFactory
    {
        return new Http\RequestFactory(
            $container->get(Http\Client\GuzzleClientFactory::class)
        );
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

    public static function getMiddlewares(ContainerInterface $container): \ArrayObject
    {
        return new \ArrayObject();
    }

    public static function getContentSecurityPolicies(ContainerInterface $container): Map
    {
        return new Map();
    }

    public static function getAssetsCache(ContainerInterface $container): FrontendInterface
    {
        return Bootstrap::createCache('assets');
    }

    public static function getRuntimeCache(ContainerInterface $container): FrontendInterface
    {
        $defaultBackend = Cache\Backend\TransientMemoryBackend::class;
        $cacheBackend = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['runtime']['backend'] ?? $defaultBackend;
        if (!array_key_exists(Cache\Backend\TransientBackendInterface::class, class_implements($cacheBackend))) {
            $cacheBackend = $defaultBackend;
        }
        return Bootstrap::createCache('runtime', false, $cacheBackend);
    }

    public static function getCoreMiddlewares(ContainerInterface $container): \ArrayObject
    {
        return new \ArrayObject($container->get(Http\MiddlewareStackResolver::class)->resolve('core'));
    }

    public static function getHashService(): HashService
    {
        return new HashService();
    }

    public static function provideFallbackEventDispatcher(
        ContainerInterface $container,
        ?EventDispatcherInterface $eventDispatcher = null
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

        $commandRegistry->addLazyCommand('lint:yaml', SymfonyLintCommand::class, 'Lint yaml files.');

        return $commandRegistry;
    }
}
