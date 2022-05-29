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
use Doctrine\Common\Annotations\AnnotationRegistry;
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
use TYPO3\CMS\Core\Database\TableConfigurationPostProcessingHookInterface;
use TYPO3\CMS\Core\DependencyInjection\Cache\ContainerBackend;
use TYPO3\CMS\Core\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\IO\PharStreamWrapperInterceptor;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Package\Cache\ComposerPackageArtifact;
use TYPO3\CMS\Core\Package\Cache\PackageCacheInterface;
use TYPO3\CMS\Core\Package\Cache\PackageStatesPackageCache;
use TYPO3\CMS\Core\Package\FailsafePackageManager;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\PharStreamWrapper\Behavior;
use TYPO3\PharStreamWrapper\Interceptor\ConjunctionInterceptor;
use TYPO3\PharStreamWrapper\Interceptor\PharMetaDataInterceptor;
use TYPO3\PharStreamWrapper\Manager;
use TYPO3\PharStreamWrapper\PharStreamWrapper;

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
     * @return ContainerInterface
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
        static::initializeIO();

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
        $bootState->done = false;
        // After a deprecation grace period, only one of those flag will remain, likely ->done
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
            $bootState->done = true;
            $bootState->complete = true;
            return $container;
        }

        $eventDispatcher = $container->get(EventDispatcherInterface::class);
        PageRenderer::setCache($assetsCache);
        ExtensionManagementUtility::setEventDispatcher($eventDispatcher);
        static::loadTypo3LoadedExtAndExtLocalconf(true, $coreCache);
        static::unsetReservedGlobalVariables();
        $bootState->done = true;
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
     * @throws \RuntimeException when TYPO3_REQUESTTYPE was not set before, setRequestType() needs to be called before
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function baseSetup()
    {
        if (!defined('TYPO3_REQUESTTYPE')) {
            throw new \RuntimeException('No Request Type was set, TYPO3 does not know in which context it is run.', 1450561838);
        }
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

        /** @see initializeAnnotationRegistry */
        AnnotationRegistry::registerLoader([$classLoader, 'loadClass']);

        // Annotations used in unit tests
        AnnotationReader::addGlobalIgnoredName('test');

        // Annotations that control the extension scanner
        AnnotationReader::addGlobalIgnoredName('extensionScannerIgnoreFile');
        AnnotationReader::addGlobalIgnoredName('extensionScannerIgnoreLine');
    }

    /**
     * checks if LocalConfiguration.php or PackageStates.php is missing,
     * used to see if a redirect to the install tool is needed
     *
     * @param ConfigurationManager $configurationManager
     * @return bool TRUE when the essential configuration is available, otherwise FALSE
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function checkIfEssentialConfigurationExists(ConfigurationManager $configurationManager): bool
    {
        return file_exists($configurationManager->getLocalConfigurationFileLocation())
            && (Environment::isComposerMode() || file_exists(Environment::getLegacyConfigPath() . '/PackageStates.php'));
    }

    /**
     * Initializes the package system and loads the package configuration and settings
     * provided by the packages.
     *
     * @param string $packageManagerClassName Define an alternative package manager implementation (usually for the installer)
     * @param PackageCacheInterface $packageCache
     * @return PackageManager
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
     *
     * @param FrontendInterface $coreCache
     * @return PackageCacheInterface
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
     * @param FrontendInterface $coreCache
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
     *
     * @return ConfigurationManager
     */
    public static function createConfigurationManager(): ConfigurationManager
    {
        return new ConfigurationManager();
    }

    /**
     * We need an early instance of the configuration manager.
     * Since makeInstance relies on the object configuration, we create it here with new instead.
     *
     * @param ConfigurationManager $configurationManager
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
     * @return FrontendInterface
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
     * Initializes IO and stream wrapper related behavior.
     */
    protected static function initializeIO()
    {
        if (in_array('phar', stream_get_wrappers())) {
            // destroy and re-initialize PharStreamWrapper for TYPO3 core
            Manager::destroy();
            Manager::initialize(
                (new Behavior())
                    ->withAssertion(new ConjunctionInterceptor([
                        new PharStreamWrapperInterceptor(),
                        new PharMetaDataInterceptor(),
                    ]))
            );

            stream_wrapper_unregister('phar');
            stream_wrapper_register('phar', PharStreamWrapper::class);
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
        unset($GLOBALS['PAGES_TYPES']);
        unset($GLOBALS['TCA']);
        unset($GLOBALS['TBE_MODULES']);
        unset($GLOBALS['TBE_STYLES']);
        unset($GLOBALS['BE_USER']);
        // Those set otherwise:
        unset($GLOBALS['TBE_MODULES_EXT']);
        unset($GLOBALS['TCA_DESCR']);
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
        static::runExtTablesPostProcessingHooks();
    }

    /**
     * Check for registered ext tables hooks and run them
     *
     * @throws \UnexpectedValueException
     * @deprecated will be removed in TYPO3 v12.0, use the PSR-14 based BootCompletedEvent instead.
     */
    protected static function runExtTablesPostProcessingHooks()
    {
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing'])) {
            trigger_error('Using the hook $TYPO3_CONF_VARS[SC_OPTIONS][GLOBAL][extTablesInclusion-PostProcessing] will be removed in TYPO3 v12.0. in favor of the new PSR-14 BootCompletedEvent', E_USER_DEPRECATED);
        }
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing'] ?? [] as $className) {
            /** @var TableConfigurationPostProcessingHookInterface $hookObject */
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof TableConfigurationPostProcessingHookInterface) {
                throw new \UnexpectedValueException(
                    '$hookObject "' . $className . '" must implement interface TYPO3\\CMS\\Core\\Database\\TableConfigurationPostProcessingHookInterface',
                    1320585902
                );
            }
            $hookObject->processData();
        }
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
     * @param bool $proceedIfNoUserIsLoggedIn if set to TRUE, no forced redirect to the login page will be done
     */
    public static function initializeBackendAuthentication($proceedIfNoUserIsLoggedIn = false)
    {
        $GLOBALS['BE_USER']->backendCheckLogin($proceedIfNoUserIsLoggedIn);
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
