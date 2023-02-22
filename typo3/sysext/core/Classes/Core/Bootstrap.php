<?php

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

namespace TYPO3\CMS\Core\Core;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use Doctrine\Common\Annotations\AnnotationReader;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\Backend\BackendInterface;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\InvalidBackendException;
use TYPO3\CMS\Core\Cache\Exception\InvalidCacheException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Core\Event\BootCompletedEvent;
use TYPO3\CMS\Core\DependencyInjection\Cache\ContainerBackend;
use TYPO3\CMS\Core\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Package\Cache\ComposerPackageArtifact;
use TYPO3\CMS\Core\Package\Cache\PackageCacheInterface;
use TYPO3\CMS\Core\Package\Cache\PackageStatesPackageCache;
use TYPO3\CMS\Core\Package\FailsafePackageManager;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class encapsulates bootstrap related methods.
 * It is required directly as the very first thing in entry scripts and
 * used to define all base things like constants and paths and so on.
 *
 * Most methods in this class have dependencies to each other. They can
 * not be called in arbitrary order. The methods are ordered top down, so
 * a method at the beginning has lower dependencies than a method further
 * down. Do not fiddle with the load order in own scripts except you know
 * exactly what you are doing!
 */
class Bootstrap
{
    /**
     * Bootstrap TYPO3 and return a Container that may be used
     * to initialize an Application class.
     *
     * @param ClassLoader $classLoader an instance of the class loader
     * @param bool $failsafe true if no caching and a failsafe package manager should be used
     */
    public static function init(
        ClassLoader $classLoader,
        bool $failsafe = false
    ): ContainerInterface {
        $requestId = new RequestId();

        static::initializeClassLoader($classLoader);
        if (!Environment::isComposerMode() && ClassLoadingInformation::isClassLoadingInformationAvailable()) {
            ClassLoadingInformation::registerClassLoadingInformation();
        }

        static::startOutputBuffering();

        $configurationManager = static::createConfigurationManager();
        if (!static::checkIfEssentialConfigurationExists($configurationManager)) {
            $failsafe = true;
        }
        static::populateLocalConfiguration($configurationManager);

        $logManager = new LogManager((string)$requestId);
        // LogManager is used by the core ErrorHandler (using GeneralUtility::makeInstance),
        // therefore we have to push the LogManager to GeneralUtility, in case there
        // happen errors before we call GeneralUtility::setContainer().
        GeneralUtility::setSingletonInstance(LogManager::class, $logManager);

        static::initializeErrorHandling();

        $disableCaching = $failsafe ? true : false;
        $coreCache = static::createCache('core', $disableCaching);
        $packageCache = static::createPackageCache($coreCache);
        $packageManager = static::createPackageManager(
            $failsafe ? FailsafePackageManager::class : PackageManager::class,
            $packageCache
        );

        static::setDefaultTimezone();
        static::setMemoryLimit();

        $assetsCache = static::createCache('assets', $disableCaching);
        $dependencyInjectionContainerCache = static::createCache('di');

        $bootState = new \stdClass();
        $bootState->complete = false;
        $bootState->cacheDisabled = $disableCaching;

        $builder = new ContainerBuilder([
            ClassLoader::class => $classLoader,
            ApplicationContext::class => Environment::getContext(),
            ConfigurationManager::class => $configurationManager,
            LogManager::class => $logManager,
            RequestId::class => $requestId,
            'cache.di' => $dependencyInjectionContainerCache,
            'cache.core' => $coreCache,
            'cache.assets' => $assetsCache,
            PackageManager::class => $packageManager,

            // @internal
            'boot.state' => $bootState,
        ]);

        $container = $builder->createDependencyInjectionContainer($packageManager, $dependencyInjectionContainerCache, $failsafe);

        // Push the container to GeneralUtility as we want to make sure its
        // makeInstance() method creates classes using the container from now on.
        GeneralUtility::setContainer($container);

        // Reset LogManager singleton instance in order for GeneralUtility::makeInstance()
        // to proxy LogManager retrieval to ContainerInterface->get() from now on.
        GeneralUtility::removeSingletonInstance(LogManager::class, $logManager);

        // Push PackageManager instance to ExtensionManagementUtility
        ExtensionManagementUtility::setPackageManager($packageManager);

        if ($failsafe) {
            $bootState->complete = true;
            return $container;
        }

        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        ExtensionManagementUtility::setEventDispatcher($eventDispatcher);
        static::loadTypo3LoadedExtAndExtLocalconf(true, $coreCache);
        static::unsetReservedGlobalVariables();
        static::loadBaseTca(true, $coreCache);
        static::checkEncryptionKey();
        $bootState->complete = true;
        $eventDispatcher->dispatch(new BootCompletedEvent($disableCaching));

        return $container;
    }

    /**
     * Prevent any unwanted output that may corrupt AJAX/compression.
     * This does not interfere with "die()" or "echo"+"exit()" messages!
     *
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function startOutputBuffering()
    {
        ob_start();
    }

    /**
     * Run the base setup that checks server environment, determines paths,
     * populates base files and sets common configuration.
     *
     * Script execution will be aborted if something fails here.
     *
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function baseSetup()
    {
        if (!Environment::isComposerMode() && ClassLoadingInformation::isClassLoadingInformationAvailable()) {
            ClassLoadingInformation::registerClassLoadingInformation();
        }
    }

    /**
     * Sets the class loader to the bootstrap
     *
     * @param ClassLoader $classLoader an instance of the class loader
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function initializeClassLoader(ClassLoader $classLoader)
    {
        ClassLoadingInformation::setClassLoader($classLoader);

        // Annotations used in unit tests
        AnnotationReader::addGlobalIgnoredName('test');

        // Annotations that control the extension scanner
        AnnotationReader::addGlobalIgnoredName('extensionScannerIgnoreFile');
        AnnotationReader::addGlobalIgnoredName('extensionScannerIgnoreLine');
    }

    /**
     * checks if config/system/settings.php or PackageStates.php is missing,
     * used to see if a redirect to the installer is needed
     *
     * All file_exists checks are delayed as far as possible to avoid I/O impact
     *
     * @return bool TRUE when the essential configuration is available, otherwise FALSE
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function checkIfEssentialConfigurationExists(ConfigurationManager $configurationManager): bool
    {
        if (!Environment::isComposerMode()
            && !file_exists(Environment::getLegacyConfigPath() . '/PackageStates.php')
        ) {
            // Early return in case system is not properly set up
            return false;
        }

        $systemConfigurationPath = $configurationManager->getSystemConfigurationFileLocation();
        $additionalConfigurationPath = $configurationManager->getAdditionalConfigurationFileLocation();

        $systemConfigurationFileExists = file_exists($systemConfigurationPath);
        $additionalConfigurationFileExists = file_exists($additionalConfigurationPath);
        if ($systemConfigurationFileExists && $additionalConfigurationFileExists) {
            // We have a complete configuration, off we go
            return true;
        }

        // If system configuration file exists and no legacy additional configuration is present, we are good
        $legacyAdditionConfigurationPath = Environment::getLegacyConfigPath() . '/AdditionalConfiguration.php';
        $legacyAdditionalConfigurationFileExists = file_exists($legacyAdditionConfigurationPath);
        if ($systemConfigurationFileExists && !$legacyAdditionalConfigurationFileExists) {
            return true;
        }

        // @deprecated All code below is deprecated and can be removed with TYPO3 v14.0 and replaced with `return false;`

        // All other cases will probably need some migration work
        $migrated = false;

        // In case no system configuration file exists at this point, check for the legacy "LocalConfiguration"
        // file. If it exists, move it to the new location. Otherwise, the system is not complete.
        if (!$systemConfigurationFileExists) {
            $legacyLocalConfigurationPath = $configurationManager->getLocalConfigurationFileLocation();
            $legacySystemConfigurationFileExists = file_exists($legacyLocalConfigurationPath);
            if ($legacySystemConfigurationFileExists) {
                mkdir(dirname($systemConfigurationPath), 02775, true);
                rename($legacyLocalConfigurationPath, $systemConfigurationPath);
                $migrated = true;
            } else {
                // Directly return as essential system configuration does not exist
                return false;
            }
        }
        // In case no additional configuration file exists at this point, check for the legacy
        // "AdditionalConfiguration" file. If it exists, move it to the new location as well.
        if (!$additionalConfigurationFileExists && $legacyAdditionalConfigurationFileExists) {
            rename($legacyAdditionConfigurationPath, $additionalConfigurationPath);
            $migrated = true;
        }

        return $migrated;
    }

    /**
     * Initializes the package system and loads the package configuration and settings
     * provided by the packages.
     *
     * @param string $packageManagerClassName Define an alternative package manager implementation (usually for the installer)
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function createPackageManager($packageManagerClassName, PackageCacheInterface $packageCache): PackageManager
    {
        $dependencyOrderingService = GeneralUtility::makeInstance(DependencyOrderingService::class);
        /** @var PackageManager $packageManager */
        $packageManager = new $packageManagerClassName($dependencyOrderingService);
        $packageManager->setPackageCache($packageCache);
        $packageManager->initialize();

        return $packageManager;
    }

    /**
     * @internal
     */
    public static function createPackageCache(FrontendInterface $coreCache): PackageCacheInterface
    {
        if (!Environment::isComposerMode()) {
            return new PackageStatesPackageCache(Environment::getLegacyConfigPath() . '/PackageStates.php', $coreCache);
        }

        $composerInstallersPath = InstalledVersions::getInstallPath('typo3/cms-composer-installers');
        if ($composerInstallersPath === null) {
            throw new \RuntimeException('Package "typo3/cms-composer-installers" not found. Replacing the package is not allowed. Fork the package instead and pull in the fork with the same name.', 1636145677);
        }

        return new ComposerPackageArtifact(dirname($composerInstallersPath));
    }

    /**
     * Load ext_localconf of extensions
     *
     * @param bool $allowCaching
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function loadTypo3LoadedExtAndExtLocalconf($allowCaching = true, FrontendInterface $coreCache = null)
    {
        if ($allowCaching) {
            $coreCache = $coreCache ?? GeneralUtility::makeInstance(CacheManager::class)->getCache('core');
        }
        ExtensionManagementUtility::loadExtLocalconf($allowCaching, $coreCache);
    }

    /**
     * We need an early instance of the configuration manager.
     * Since makeInstance relies on the object configuration, we create it here with new instead.
     */
    public static function createConfigurationManager(): ConfigurationManager
    {
        return new ConfigurationManager();
    }

    /**
     * We need an early instance of the configuration manager.
     * Since makeInstance relies on the object configuration, we create it here with new instead.
     *
     * @internal This is not a public API method, do not use in own extensions
     */
    protected static function populateLocalConfiguration(ConfigurationManager $configurationManager)
    {
        $configurationManager->exportConfiguration();
    }

    /**
     * Instantiates an early cache instance
     *
     * Creates a cache instances independently from the CacheManager.
     * The is used to create the core cache during early bootstrap when the CacheManager
     * is not yet available (i.e. configuration is not yet loaded).
     *
     * @param string $identifier
     * @param bool $disableCaching
     * @internal
     */
    public static function createCache(string $identifier, bool $disableCaching = false): FrontendInterface
    {
        $cacheConfigurations = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] ?? [];
        $cacheConfigurations['di']['frontend'] = PhpFrontend::class;
        $cacheConfigurations['di']['backend'] = ContainerBackend::class;
        $cacheConfigurations['di']['options'] = [];
        $configuration = $cacheConfigurations[$identifier] ?? [];

        $frontend = $configuration['frontend'] ?? VariableFrontend::class;
        $backend = $configuration['backend'] ?? Typo3DatabaseBackend::class;
        $options = $configuration['options'] ?? [];

        if ($disableCaching) {
            $backend = NullBackend::class;
            $options = [];
        }

        $backendInstance = new $backend('production', $options);
        if (!$backendInstance instanceof BackendInterface) {
            throw new InvalidBackendException('"' . $backend . '" is not a valid cache backend object.', 1545260108);
        }
        if (is_callable([$backendInstance, 'initializeObject'])) {
            $backendInstance->initializeObject();
        }

        $frontendInstance = new $frontend($identifier, $backendInstance);
        if (!$frontendInstance instanceof FrontendInterface) {
            throw new InvalidCacheException('"' . $frontend . '" is not a valid cache frontend object.', 1545260109);
        }
        if (is_callable([$frontendInstance, 'initializeObject'])) {
            $frontendInstance->initializeObject();
        }

        return $frontendInstance;
    }

    /**
     * Set default timezone
     */
    protected static function setDefaultTimezone()
    {
        $timeZone = $GLOBALS['TYPO3_CONF_VARS']['SYS']['phpTimeZone'];
        if (empty($timeZone)) {
            // Time zone from the server environment (TZ env or OS query)
            $defaultTimeZone = @date_default_timezone_get();
            if ($defaultTimeZone !== '') {
                $timeZone = $defaultTimeZone;
            } else {
                $timeZone = 'UTC';
            }
        }
        // Set default to avoid E_WARNINGs with PHP > 5.3
        date_default_timezone_set($timeZone);
    }

    /**
     * Configure and set up exception and error handling
     *
     * @throws \RuntimeException
     */
    protected static function initializeErrorHandling()
    {
        $productionExceptionHandlerClassName = $GLOBALS['TYPO3_CONF_VARS']['SYS']['productionExceptionHandler'];
        $debugExceptionHandlerClassName = $GLOBALS['TYPO3_CONF_VARS']['SYS']['debugExceptionHandler'];

        $errorHandlerClassName = $GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandler'];
        $errorHandlerErrors = $GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandlerErrors'] | E_USER_DEPRECATED;
        $exceptionalErrors = $GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors'];

        $displayErrorsSetting = (int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'];
        switch ($displayErrorsSetting) {
            case -1:
                $ipMatchesDevelopmentSystem = GeneralUtility::cmpIP(GeneralUtility::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']);
                $exceptionHandlerClassName = $ipMatchesDevelopmentSystem ? $debugExceptionHandlerClassName : $productionExceptionHandlerClassName;
                $displayErrors = $ipMatchesDevelopmentSystem ? 1 : 0;
                $exceptionalErrors = $ipMatchesDevelopmentSystem ? $exceptionalErrors : 0;
                break;
            case 0:
                $exceptionHandlerClassName = $productionExceptionHandlerClassName;
                $displayErrors = 0;
                break;
            case 1:
                $exceptionHandlerClassName = $debugExceptionHandlerClassName;
                $displayErrors = 1;
                break;
            default:
                // Throw exception if an invalid option is set. A default for displayErrors is set
                // in very early install tool, coming from DefaultConfiguration.php. It is safe here
                // to just throw if there is no value for whatever reason.
                throw new \RuntimeException(
                    'The option $TYPO3_CONF_VARS[SYS][displayErrors] is not set to "-1", "0" or "1".',
                    1476046290
                );
        }
        @ini_set('display_errors', (string)$displayErrors);

        if (!empty($errorHandlerClassName)) {
            // Register an error handler for the given errorHandlerError
            $errorHandler = GeneralUtility::makeInstance($errorHandlerClassName, $errorHandlerErrors);
            $errorHandler->setExceptionalErrors($exceptionalErrors);
            if (is_callable([$errorHandler, 'setDebugMode'])) {
                $errorHandler->setDebugMode($displayErrors === 1);
            }
            if (is_callable([$errorHandler, 'registerErrorHandler'])) {
                $errorHandler->registerErrorHandler();
            }
        }
        if (!empty($exceptionHandlerClassName)) {
            // Registering the exception handler is done in the constructor
            GeneralUtility::makeInstance($exceptionHandlerClassName);
        }
    }

    /**
     * Set PHP memory limit depending on value of
     * $GLOBALS['TYPO3_CONF_VARS']['SYS']['setMemoryLimit']
     */
    protected static function setMemoryLimit()
    {
        if ((int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['setMemoryLimit'] > 16) {
            @ini_set('memory_limit', (string)((int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['setMemoryLimit'] . 'm'));
        }
    }

    /**
     * Unsetting reserved global variables:
     * Those are set in "ext:core/ext_tables.php" file:
     *
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function unsetReservedGlobalVariables()
    {
        unset($GLOBALS['TCA']);
        unset($GLOBALS['TBE_STYLES']);
        unset($GLOBALS['BE_USER']);
    }

    /**
     * Load $TCA
     *
     * This will mainly set up $TCA through extMgm API.
     *
     * @param bool $allowCaching True, if loading TCA from cache is allowed
     * @param FrontendInterface $coreCache
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function loadBaseTca(bool $allowCaching = true, FrontendInterface $coreCache = null)
    {
        if ($allowCaching) {
            $coreCache = $coreCache ?? GeneralUtility::makeInstance(CacheManager::class)->getCache('core');
        }
        ExtensionManagementUtility::loadBaseTca($allowCaching, $coreCache);
    }

    /**
     * Check if a configuration key has been configured
     */
    protected static function checkEncryptionKey()
    {
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            throw new \RuntimeException(
                'TYPO3 Encryption is empty. $GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'encryptionKey\'] needs to be set for TYPO3 to work securely',
                1502987245
            );
        }
    }

    /**
     * Load ext_tables and friends.
     *
     * This will mainly load and execute ext_tables.php files of loaded extensions
     * or the according cache file if exists.
     *
     * @param bool $allowCaching True, if reading compiled ext_tables file from cache is allowed
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function loadExtTables(bool $allowCaching = true, FrontendInterface $coreCache = null)
    {
        if ($allowCaching) {
            $coreCache = $coreCache ?? GeneralUtility::makeInstance(CacheManager::class)->getCache('core');
        }
        ExtensionManagementUtility::loadExtTables($allowCaching, $coreCache);
    }

    /**
     * Initialize backend user object in globals
     *
     * @param string $className usually \TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class but can be used for CLI
     * @param ServerRequestInterface|null $request
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function initializeBackendUser($className = BackendUserAuthentication::class, ServerRequestInterface $request = null)
    {
        /** @var BackendUserAuthentication $backendUser */
        $backendUser = GeneralUtility::makeInstance($className);
        // The global must be available very early, because methods below
        // might trigger code which relies on it. See: #45625
        $GLOBALS['BE_USER'] = $backendUser;
        $backendUser->start($request);
    }

    /**
     * Initializes and ensures authenticated access
     *
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function initializeBackendAuthentication()
    {
        $GLOBALS['BE_USER']->backendCheckLogin();
    }

    /**
     * Initialize language object
     *
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function initializeLanguageObject()
    {
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->createFromUserPreferences($GLOBALS['BE_USER']);
    }
}
