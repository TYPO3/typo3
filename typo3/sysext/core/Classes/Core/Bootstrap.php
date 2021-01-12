<?php
namespace TYPO3\CMS\Core\Core;

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

use Composer\Autoload\ClassLoader;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\IO\PharStreamWrapperInterceptor;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Package\FailsafePackageManager;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\PharStreamWrapper\Behavior;
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
     * @var \TYPO3\CMS\Core\Core\Bootstrap
     */
    protected static $instance;

    /**
     * @var array List of early instances
     */
    protected $earlyInstances = [];

    /**
     * @var bool
     */
    protected $limbo = false;

    /**
     * Disable direct creation of this object.
     */
    protected function __construct()
    {
    }

    /**
     * Bootstrap TYPO3 and return a Container that may be used
     * to initialize an Application class.
     *
     * @param ClassLoader $classLoader an instance of the class loader
     * @param bool $failsafe true if no caching and a failsaife package manager should be used
     * @return ContainerInterface
     */
    public static function init(
        ClassLoader $classLoader,
        bool $failsafe = false
    ): ContainerInterface {
        $requestId = substr(md5(uniqid('', true)), 0, 13);

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

        $logManager = new LogManager($requestId);
        // LogManager is used by the core ErrorHandler (using GeneralUtility::makeInstance),
        // therefore we have to push the LogManager to GeneralUtility, to ensure the requestId is set
        GeneralUtility::setSingletonInstance(LogManager::class, $logManager);

        static::initializeErrorHandling();
        static::initializeIO();

        $cacheManager = static::createCacheManager($failsafe ? true : false);
        $coreCache = $cacheManager->getCache('cache_core');
        $assetsCache = $cacheManager->getCache('assets');
        $cacheManager->setLimbo(true);
        $packageManager = static::createPackageManager(
            $failsafe ? FailsafePackageManager::class : PackageManager::class,
            $coreCache
        );

        // Push singleton instances to GeneralUtility and ExtensionManagementUtility
        // They should be fetched through a container (later) but currently a PackageManager
        // singleton instance is required by PackageManager->activePackageDuringRuntime
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager);
        GeneralUtility::setSingletonInstance(PackageManager::class, $packageManager);
        ExtensionManagementUtility::setPackageManager($packageManager);

        static::initializeRuntimeActivatedPackagesFromConfiguration($packageManager);

        static::setDefaultTimeZone();
        $locales = Locales::initialize();
        static::setMemoryLimit();

        // Create (to be deprecated) bootstrap instance with (to be deprecated) early instances
        static::$instance = new static();
        static::$instance->earlyInstances = [
            ClassLoader::class => $classLoader,
            ConfigurationManager::class => $configurationManager,
            CacheManager::class => $cacheManager,
            PackageManager::class => $packageManager,
        ];

        if (!$failsafe) {
            IconRegistry::setCache($assetsCache);
            PageRenderer::setCache($assetsCache);
            static::loadTypo3LoadedExtAndExtLocalconf(true, $coreCache);
            static::setFinalCachingFrameworkCacheConfiguration($cacheManager);
            static::unsetReservedGlobalVariables();
            static::loadBaseTca(true, $coreCache);
            static::checkEncryptionKey();
        }
        $cacheManager->setLimbo(false);

        $defaultContainerEntries = [
            ClassLoader::class => $classLoader,
            'request.id' => $requestId,
            ConfigurationManager::class => $configurationManager,
            LogManager::class => $logManager,
            CacheManager::class => $cacheManager,
            PackageManager::class => $packageManager,
            Locales::class => $locales,
        ];

        return new class($defaultContainerEntries) implements ContainerInterface {
            /**
             * @var array
             */
            private $entries;

            /**
             * @param array $entries
             */
            public function __construct(array $entries)
            {
                $this->entries = $entries;
            }

            /**
             * @param string $id Identifier of the entry to look for.
             * @return bool
             */
            public function has($id)
            {
                if (isset($this->entries[$id])) {
                    return true;
                }

                switch ($id) {
                case \TYPO3\CMS\Frontend\Http\Application::class:
                case \TYPO3\CMS\Backend\Http\Application::class:
                case \TYPO3\CMS\Install\Http\Application::class:
                case \TYPO3\CMS\Core\Console\CommandApplication::class:
                    return true;
                }

                return false;
            }

            /**
             * Method get() as specified in ContainerInterface
             *
             * @param string $id
             * @return mixed
             * @throws NotFoundExceptionInterface
             */
            public function get($id)
            {
                $entry = null;

                if (isset($this->entries[$id])) {
                    return $this->entries[$id];
                }

                switch ($id) {
                case \TYPO3\CMS\Frontend\Http\Application::class:
                case \TYPO3\CMS\Backend\Http\Application::class:
                    $entry = new $id($this->get(ConfigurationManager::class));
                    break;
                case \TYPO3\CMS\Install\Http\Application::class:
                    $entry = new $id(
                        GeneralUtility::makeInstance(\TYPO3\CMS\Install\Http\RequestHandler::class, $this->get(ConfigurationManager::class)),
                        GeneralUtility::makeInstance(\TYPO3\CMS\Install\Http\InstallerRequestHandler::class)
                    );
                    break;
                case \TYPO3\CMS\Core\Console\CommandApplication::class:
                    $entry = new $id;
                    break;
                default:
                    throw new class($id . ' not found', 1518638338) extends \Exception implements NotFoundExceptionInterface {
                    };
                    break;
                }

                $this->entries[$id] = $entry;

                return $entry;
            }
        };
    }

    /**
     * @return bool
     * @deprecated will be removed in TYPO3 v10.0. Use the Environment API instead.
     */
    public static function usesComposerClassLoading()
    {
        trigger_error('Bootstrap::usesComposerClassLoading() will be removed in TYPO3 v10.0. Use Environment::isComposerMode() instead.', E_USER_DEPRECATED);
        return Environment::isComposerMode();
    }

    /**
     * Disable direct cloning of this object.
     */
    protected function __clone()
    {
    }

    /**
     * Return 'this' as singleton
     *
     * @return Bootstrap
     * @internal This is not a public API method, do not use in own extensions
     * @deprecated will be removed in TYPO3 v10.0. Use methods directly or create an instance, depending on your use-case.
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            self::$instance = new static();
            self::$instance->defineTypo3RequestTypes();
            $requestId = substr(md5(uniqid('', true)), 0, 13);
            GeneralUtility::setSingletonInstance(LogManager::class, new LogManager($requestId));
        }
        return static::$instance;
    }

    /**
     * @return ApplicationContext
     * @internal This is not a public API method, do not use in own extensions
     * @deprecated will be removed in TYPO3 v10.0.
     */
    public static function createApplicationContext(): ApplicationContext
    {
        $applicationContext = getenv('TYPO3_CONTEXT') ?: (getenv('REDIRECT_TYPO3_CONTEXT') ?: 'Production');

        return new ApplicationContext($applicationContext);
    }

    /**
     * Prevent any unwanted output that may corrupt AJAX/compression.
     * This does not interfere with "die()" or "echo"+"exit()" messages!
     *
     * @return Bootstrap|null
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function startOutputBuffering()
    {
        ob_start();
        return static::$instance;
    }

    /**
     * Main entry point called at every request usually from Global scope. Checks if everything is correct,
     * and loads the Configuration.
     *
     * Make sure that the baseSetup() is called before and the class loader is present
     *
     * @return Bootstrap
     * @deprecated will be removed in TYPO3 v10.0. Use init() method instead.
     */
    public function configure()
    {
        $this->startOutputBuffering()
            ->loadConfigurationAndInitialize(true, \TYPO3\CMS\Core\Package\PackageManager::class, true)
            ->loadTypo3LoadedExtAndExtLocalconf(true)
            ->setFinalCachingFrameworkCacheConfiguration()
            ->unsetReservedGlobalVariables()
            ->loadBaseTca()
            ->checkEncryptionKey();
        return $this;
    }

    /**
     * Run the base setup that checks server environment, determines paths,
     * populates base files and sets common configuration.
     *
     * Script execution will be aborted if something fails here.
     *
     * @param int $entryPointLevel Number of subdirectories where the entry script is located under the document root.
     * @return Bootstrap
     * @throws \RuntimeException when TYPO3_REQUESTTYPE was not set before, setRequestType() needs to be called before
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function baseSetup($entryPointLevel = null)
    {
        if (!defined('TYPO3_REQUESTTYPE')) {
            throw new \RuntimeException('No Request Type was set, TYPO3 does not know in which context it is run.', 1450561838);
        }
        // @deprecated: remove this code block in TYPO3 v10.0
        if (GeneralUtility::getApplicationContext() === null) {
            SystemEnvironmentBuilder::run($entryPointLevel ?? 0, PHP_SAPI === 'cli' ? SystemEnvironmentBuilder::REQUESTTYPE_CLI : SystemEnvironmentBuilder::REQUESTTYPE_FE);
        }
        if (!Environment::isComposerMode() && ClassLoadingInformation::isClassLoadingInformationAvailable()) {
            ClassLoadingInformation::registerClassLoadingInformation();
        }
        return static::$instance;
    }

    /**
     * Sets the class loader to the bootstrap
     *
     * @param ClassLoader $classLoader an instance of the class loader
     * @return Bootstrap
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function initializeClassLoader(ClassLoader $classLoader)
    {
        ClassLoadingInformation::setClassLoader($classLoader);

        /** @see initializeAnnotationRegistry */
        AnnotationRegistry::registerLoader([$classLoader, 'loadClass']);

        /*
         * All annotations defined by and for Extbase need to be
         * ignored during their deprecation. Later, their usage may and
         * should throw an Exception
         */
        AnnotationReader::addGlobalIgnoredName('inject');
        AnnotationReader::addGlobalIgnoredName('transient');
        AnnotationReader::addGlobalIgnoredName('lazy');
        AnnotationReader::addGlobalIgnoredName('validate');
        AnnotationReader::addGlobalIgnoredName('cascade');
        AnnotationReader::addGlobalIgnoredName('ignorevalidation');
        AnnotationReader::addGlobalIgnoredName('cli');
        AnnotationReader::addGlobalIgnoredName('flushesCaches');
        AnnotationReader::addGlobalIgnoredName('uuid');
        AnnotationReader::addGlobalIgnoredName('identity');

        // Annotations used in unit tests
        AnnotationReader::addGlobalIgnoredName('test');

        // Annotations that control the extension scanner
        AnnotationReader::addGlobalIgnoredName('extensionScannerIgnoreFile');
        AnnotationReader::addGlobalIgnoredName('extensionScannerIgnoreLine');

        return static::$instance;
    }

    /**
     * checks if LocalConfiguration.php or PackageStates.php is missing,
     * used to see if a redirect to the install tool is needed
     *
     * @param ConfigurationManager $configurationManager
     * @return bool TRUE when the essential configuration is available, otherwise FALSE
     * @internal This is not a public API method, do not use in own extensions
     */
    protected static function checkIfEssentialConfigurationExists(ConfigurationManager $configurationManager = null): bool
    {
        if ($configurationManager === null) {
            // @deprecated A configurationManager instance needs to be handed into Bootstrap::checkIfEssentialConfigurationExists and will become mandatory in TYPO3 v10.0
            $configurationManager = new ConfigurationManager;
            static::$instance->setEarlyInstance(ConfigurationManager::class, $configurationManager);
        }
        return file_exists($configurationManager->getLocalConfigurationFileLocation())
            && file_exists(Environment::getLegacyConfigPath() . '/PackageStates.php');
    }

    /**
     * Registers the instance of the specified object for an early boot stage.
     * On finalizing the Object Manager initialization, all those instances will
     * be transferred to the Object Manager's registry.
     *
     * @param string $objectName Object name, as later used by the Object Manager
     * @param object $instance The instance to register
     * @internal This is not a public API method, do not use in own extensions
     * @deprecated since TYPO3 9.4, will be removed in TYPO3 v10.0 as this concept of early instances is not needed anymore
     */
    public function setEarlyInstance($objectName, $instance)
    {
        $this->earlyInstances[$objectName] = $instance;
    }

    /**
     * Returns an instance which was registered earlier through setEarlyInstance()
     *
     * @param string $objectName Object name of the registered instance
     * @return object
     * @throws \TYPO3\CMS\Core\Exception
     * @internal This is not a public API method, do not use in own extensions
     * @deprecated since TYPO3 9.4, will be removed in TYPO3 v10.0 as this concept of early instances is not needed anymore
     */
    public function getEarlyInstance($objectName)
    {
        if (!isset($this->earlyInstances[$objectName])) {
            throw new \TYPO3\CMS\Core\Exception('Unknown early instance "' . $objectName . '"', 1365167380);
        }
        return $this->earlyInstances[$objectName];
    }

    /**
     * Returns all registered early instances indexed by object name
     *
     * @return array
     * @internal This is not a public API method, do not use in own extensions
     * @deprecated since TYPO3 9.4, will be removed in TYPO3 v10.0 as this concept of early instances is not needed anymore
     */
    public function getEarlyInstances()
    {
        trigger_error('Bootstrap->getEarlyInstances() will be removed in TYPO3 v10.0. Use a simple singleton instance instead.', E_USER_DEPRECATED);
        return $this->earlyInstances;
    }

    /**
     * Includes LocalConfiguration.php and sets several
     * global settings depending on configuration.
     * For functional and acceptance tests.
     *
     * @param bool $allowCaching Whether to allow caching - affects cache_core (autoloader)
     * @param string $packageManagerClassName Define an alternative package manager implementation (usually for the installer)
     * @param bool $isInternalCall Set to true by bootstrap, not by extensions
     * @return Bootstrap|null
     * @internal This is not a public API method, do not use in own extensions
     * @deprecated will be set to removed in TYPO3 v10.0.
     */
    public static function loadConfigurationAndInitialize(
        $allowCaching = true,
        $packageManagerClassName = \TYPO3\CMS\Core\Package\PackageManager::class,
        $isInternalCall = false
    ) {
        if (!$isInternalCall) {
            // Suppress duplicate deprecation calls
            trigger_error('Bootstrap::loadConfigurationAndInitialize() will removed in TYPO3 v10.0.', E_USER_DEPRECATED);
        }

        $configurationManager = static::createConfigurationManager();
        static::populateLocalConfiguration($configurationManager);
        static::initializeErrorHandling();

        $cacheManager = static::createCacheManager(!$allowCaching);
        $packageManager = static::createPackageManager($packageManagerClassName, $cacheManager->getCache('cache_core'));

        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager);
        GeneralUtility::setSingletonInstance(PackageManager::class, $packageManager);
        ExtensionManagementUtility::setPackageManager($packageManager);

        static::initializeRuntimeActivatedPackagesFromConfiguration($packageManager);
        static::setDefaultTimezone();
        Locales::initialize();
        static::setMemoryLimit();
        return static::$instance;
    }

    /**
     * Initializes the package system and loads the package configuration and settings
     * provided by the packages.
     *
     * @param string $packageManagerClassName Define an alternative package manager implementation (usually for the installer)
     * @param FrontendInterface $coreCache
     * @return PackageManager
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function createPackageManager($packageManagerClassName, FrontendInterface $coreCache): PackageManager
    {
        $dependencyOrderingService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\DependencyOrderingService::class);
        /** @var \TYPO3\CMS\Core\Package\PackageManager $packageManager */
        $packageManager = new $packageManagerClassName($dependencyOrderingService);
        $packageManager->injectCoreCache($coreCache);
        $packageManager->initialize();

        return $packageManager;
    }

    /**
     * Initializes the package system and loads the package configuration and settings
     * provided by the packages.
     *
     * @param string $packageManagerClassName Define an alternative package manager implementation (usually for the installer)
     * @return Bootstrap
     * @internal This is not a public API method, do not use in own extensions
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0. Use createPackageManager instead.
     */
    public function initializePackageManagement($packageManagerClassName)
    {
        trigger_error('Bootstrap->initializePackageManagement() will be removed in TYPO3 v10.0. Use createPackageManager() instead.', E_USER_DEPRECATED);
        $packageManager = static::createPackageManager(
            $packageManagerClassName,
            GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_core')
        );
        GeneralUtility::setSingletonInstance(PackageManager::class, $packageManager);
        ExtensionManagementUtility::setPackageManager($packageManager);
        $this->setEarlyInstance(PackageManager::class, $packageManager);

        return $this;
    }

    /**
     * Activates a package during runtime. This is used in AdditionalConfiguration.php
     * to enable extensions under conditions.
     *
     * @param PackageManager $packageManager
     */
    protected static function initializeRuntimeActivatedPackagesFromConfiguration(PackageManager $packageManager)
    {
        $packages = $GLOBALS['TYPO3_CONF_VARS']['EXT']['runtimeActivatedPackages'] ?? [];
        if (!empty($packages)) {
            foreach ($packages as $runtimeAddedPackageKey) {
                $packageManager->activatePackageDuringRuntime($runtimeAddedPackageKey);
            }
        }
    }

    /**
     * Load ext_localconf of extensions
     *
     * @param bool $allowCaching
     * @param FrontendInterface $coreCache
     * @return Bootstrap|null
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function loadTypo3LoadedExtAndExtLocalconf($allowCaching = true, FrontendInterface $coreCache = null)
    {
        if ($allowCaching) {
            $coreCache = $coreCache ?? GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_core');
        }
        ExtensionManagementUtility::loadExtLocalconf($allowCaching, $coreCache);
        return static::$instance;
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
     * @return Bootstrap
     * @internal This is not a public API method, do not use in own extensions
     */
    protected static function populateLocalConfiguration(ConfigurationManager $configurationManager = null)
    {
        if ($configurationManager === null) {
            $configurationManager = new ConfigurationManager();
            static::$instance->setEarlyInstance(ConfigurationManager::class, $configurationManager);
        }

        $configurationManager->exportConfiguration();

        return static::$instance;
    }

    /**
     * Set cache_core to null backend, effectively disabling eg. the cache for ext_localconf and PackageManager etc.
     * Used in unit tests.
     *
     * @return Bootstrap|null
     * @internal This is not a public API method, do not use in own extensions
     * @deprecated as this workaround is not needed anymore. Will be removed in TYPO3 v10.0.
     */
    public static function disableCoreCache()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_core']['backend']
            = \TYPO3\CMS\Core\Cache\Backend\NullBackend::class;
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_core']['options']);
        return static::$instance;
    }

    /**
     * Initialize caching framework, and re-initializes it (e.g. in the install tool) by recreating the instances
     * again despite the Singleton instance
     *
     * @param bool $disableCaching
     * @return CacheManager
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function createCacheManager(bool $disableCaching = false): CacheManager
    {
        $cacheManager = new CacheManager($disableCaching);
        $cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
        return $cacheManager;
    }

    /**
     * Initialize caching framework, and re-initializes it (e.g. in the install tool) by recreating the instances
     * again despite the Singleton instance
     *
     * @param bool $allowCaching
     * @return Bootstrap
     * @internal This is not a public API method, do not use in own extensions
     * @deprecated as this workaround is not needed anymore. Will be removed in TYPO3 v10.0.
     */
    public function initializeCachingFramework(bool $allowCaching = true)
    {
        $cacheManager = static::createCacheManager(!$allowCaching);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager);
        $this->setEarlyInstance(CacheManager::class, $cacheManager);
        return $this;
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
                if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
                    // Throw exception if an invalid option is set.
                    throw new \RuntimeException(
                        'The option $TYPO3_CONF_VARS[SYS][displayErrors] is not set to "-1", "0" or "1".',
                        1476046290
                    );
                }
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
                    ->withAssertion(new PharStreamWrapperInterceptor())
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
     * Define TYPO3_REQUESTTYPE* constants that can be used for developers to see if any context has been hit
     * also see setRequestType(). Is done at the very beginning so these parameters are always available.
     *
     * Note: The definition of TYPO3_REQUEST_TYPE_* constants has been moved to the SystemEnvironmentBuilder
     * and is included here for backwards compatibility reasons (as Bootstrap::getInstance() is expected
     * to implicitly define these constants).
     *
     * @deprecated with TYPO3 v9.4, will be removed in TYPO3 v10.0, once Bootstrap::getInstance is removed
     */
    protected function defineTypo3RequestTypes()
    {
        if (defined('TYPO3_REQUESTTYPE_FE')) {
            return;
        }
        define('TYPO3_REQUESTTYPE_FE', 1);
        define('TYPO3_REQUESTTYPE_BE', 2);
        define('TYPO3_REQUESTTYPE_CLI', 4);
        define('TYPO3_REQUESTTYPE_AJAX', 8);
        define('TYPO3_REQUESTTYPE_INSTALL', 16);
    }

    /**
     * Defines the TYPO3_REQUESTTYPE constant so the environment knows which context the request is running.
     *
     * @param int $requestType
     * @throws \RuntimeException if the method was already called during a request
     * @return Bootstrap
     * @deprecated with TYPO3 v9.4, will be removed in TYPO3 v10.0, use SystemEnvironmentBuilder instead.
     */
    public function setRequestType($requestType)
    {
        if (defined('TYPO3_REQUESTTYPE')) {
            throw new \RuntimeException('TYPO3_REQUESTTYPE has already been set, cannot be called multiple times', 1450561878);
        }
        define('TYPO3_REQUESTTYPE', $requestType);
        return $this;
    }

    /**
     * Extensions may register new caches, so we set the
     * global cache array to the manager again at this point
     *
     * @param CacheManager $cacheManager
     * @return Bootstrap|null
     * @internal This is not a public API method, do not use in own extensions
     */
    protected static function setFinalCachingFrameworkCacheConfiguration(CacheManager $cacheManager = null)
    {
        if ($cacheManager === null) {
            $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        }
        $cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
        return static::$instance;
    }

    /**
     * Unsetting reserved global variables:
     * Those are set in "ext:core/ext_tables.php" file:
     *
     * @return Bootstrap|null
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
        unset($GLOBALS['LOCAL_LANG']);
        return static::$instance;
    }

    /**
     * Load $TCA
     *
     * This will mainly set up $TCA through extMgm API.
     *
     * @param bool $allowCaching True, if loading TCA from cache is allowed
     * @param FrontendInterface $coreCache
     * @return Bootstrap|null
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function loadBaseTca(bool $allowCaching = true, FrontendInterface $coreCache = null)
    {
        if ($allowCaching) {
            $coreCache = $coreCache ?? GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_core');
        }
        ExtensionManagementUtility::loadBaseTca($allowCaching, $coreCache);
        return static::$instance;
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
     * @return Bootstrap|null
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function loadExtTables(bool $allowCaching = true)
    {
        ExtensionManagementUtility::loadExtTables($allowCaching);
        static::runExtTablesPostProcessingHooks();
        return static::$instance;
    }

    /**
     * Check for registered ext tables hooks and run them
     *
     * @throws \UnexpectedValueException
     */
    protected static function runExtTablesPostProcessingHooks()
    {
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing'] ?? [] as $className) {
            /** @var \TYPO3\CMS\Core\Database\TableConfigurationPostProcessingHookInterface $hookObject */
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof \TYPO3\CMS\Core\Database\TableConfigurationPostProcessingHookInterface) {
                throw new \UnexpectedValueException(
                    '$hookObject "' . $className . '" must implement interface TYPO3\\CMS\\Core\\Database\\TableConfigurationPostProcessingHookInterface',
                    1320585902
                );
            }
            $hookObject->processData();
        }
    }

    /**
     * Initialize the Routing for the TYPO3 Backend
     * Loads all routes registered inside all packages and stores them inside the Router
     *
     * @return Bootstrap|null
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function initializeBackendRouter()
    {
        // See if the Routes.php from all active packages have been built together already
        $cacheIdentifier = 'BackendRoutesFromPackages_' . sha1(TYPO3_version . Environment::getProjectPath() . 'BackendRoutesFromPackages');

        /** @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $codeCache */
        $codeCache = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('cache_core');
        $routesFromPackages = [];
        if ($codeCache->has($cacheIdentifier)) {
            // substr is necessary, because the php frontend wraps php code around the cache value
            $routesFromPackages = unserialize(substr($codeCache->get($cacheIdentifier), 6, -2));
        } else {
            // Loop over all packages and check for a Configuration/Backend/Routes.php file
            $packageManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Package\PackageManager::class);
            $packages = $packageManager->getActivePackages();
            foreach ($packages as $package) {
                $routesFileNameForPackage = $package->getPackagePath() . 'Configuration/Backend/Routes.php';
                if (file_exists($routesFileNameForPackage)) {
                    $definedRoutesInPackage = require $routesFileNameForPackage;
                    if (is_array($definedRoutesInPackage)) {
                        $routesFromPackages = array_merge($routesFromPackages, $definedRoutesInPackage);
                    }
                }
                $routesFileNameForPackage = $package->getPackagePath() . 'Configuration/Backend/AjaxRoutes.php';
                if (file_exists($routesFileNameForPackage)) {
                    $definedRoutesInPackage = require $routesFileNameForPackage;
                    if (is_array($definedRoutesInPackage)) {
                        foreach ($definedRoutesInPackage as $routeIdentifier => $routeOptions) {
                            // prefix the route with "ajax_" as "namespace"
                            $routeOptions['path'] = '/ajax' . $routeOptions['path'];
                            $routesFromPackages['ajax_' . $routeIdentifier] = $routeOptions;
                            $routesFromPackages['ajax_' . $routeIdentifier]['ajax'] = true;
                        }
                    }
                }
            }
            // Store the data from all packages in the cache
            $codeCache->set($cacheIdentifier, serialize($routesFromPackages));
        }

        // Build Route objects from the data
        $router = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\Router::class);
        foreach ($routesFromPackages as $name => $options) {
            $path = $options['path'];
            unset($options['path']);
            $route = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\Route::class, $path, $options);
            $router->addRoute($name, $route);
        }
        return static::$instance;
    }

    /**
     * Initialize backend user object in globals
     *
     * @param string $className usually \TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class but can be used for CLI
     * @return Bootstrap|null
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function initializeBackendUser($className = \TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class)
    {
        /** @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $backendUser */
        $backendUser = GeneralUtility::makeInstance($className);
        // The global must be available very early, because methods below
        // might trigger code which relies on it. See: #45625
        $GLOBALS['BE_USER'] = $backendUser;
        $backendUser->start();
        return static::$instance;
    }

    /**
     * Initializes and ensures authenticated access
     *
     * @internal This is not a public API method, do not use in own extensions
     * @param bool $proceedIfNoUserIsLoggedIn if set to TRUE, no forced redirect to the login page will be done
     * @return Bootstrap|null
     */
    public static function initializeBackendAuthentication($proceedIfNoUserIsLoggedIn = false)
    {
        $GLOBALS['BE_USER']->backendCheckLogin($proceedIfNoUserIsLoggedIn);
        return static::$instance;
    }

    /**
     * Initialize language object
     *
     * @return Bootstrap|null
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function initializeLanguageObject()
    {
        /** @var $GLOBALS['LANG'] \TYPO3\CMS\Core\Localization\LanguageService */
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Localization\LanguageService::class);
        $GLOBALS['LANG']->init($GLOBALS['BE_USER']->uc['lang']);
        return static::$instance;
    }

    /**
     * Backwards compatibility for usages of now protected methods
     *
     * @param $methodName
     * @param array $arguments
     * @throws \Error
     * @deprecated Will be removed in TYPO3 v10.0
     * @return mixed
     */
    public static function __callStatic($methodName, array $arguments)
    {
        switch ($methodName) {
            case 'checkIfEssentialConfigurationExists':
            case 'setFinalCachingFrameworkCacheConfiguration':
            case 'populateLocalConfiguration':
                return call_user_func_array([self::class, $methodName], $arguments);
            default:
                throw new \Error(sprintf('Call to undefined method "%s"', $methodName), 1534156090);
        }
    }

    /**
     * Backwards compatibility for usages of now protected methods
     *
     * @param string $methodName
     * @param array $arguments
     * @throws \Error
     * @deprecated Will be removed in TYPO3 v10.0
     * @return mixed
     */
    public function __call($methodName, array $arguments)
    {
        return self::__callStatic($methodName, $arguments);
    }
}
