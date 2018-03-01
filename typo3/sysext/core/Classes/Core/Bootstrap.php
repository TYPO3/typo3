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

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use TYPO3\CMS\Core\Log\LogManager;
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
     * @var \TYPO3\CMS\Core\Core\Bootstrap
     */
    protected static $instance = null;

    /**
     * Unique Request ID
     *
     * @var string
     */
    protected $requestId;

    /**
     * The application context
     *
     * @var \TYPO3\CMS\Core\Core\ApplicationContext
     */
    protected $applicationContext;

    /**
     * @var array List of early instances
     */
    protected $earlyInstances = [];

    /**
     * @var bool
     */
    protected static $usesComposerClassLoading = false;

    /**
     * Disable direct creation of this object.
     * Set unique requestId and the application context
     *
     * @var string Application context
     */
    protected function __construct($applicationContext)
    {
        $this->requestId = substr(md5(uniqid('', true)), 0, 13);
        $this->applicationContext = new ApplicationContext($applicationContext);
    }

    /**
     * @return bool
     */
    public static function usesComposerClassLoading()
    {
        return self::$usesComposerClassLoading;
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
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            $applicationContext = getenv('TYPO3_CONTEXT') ?: (getenv('REDIRECT_TYPO3_CONTEXT') ?: 'Production');
            self::$instance = new static($applicationContext);
            self::$instance->defineTypo3RequestTypes();
            GeneralUtility::setSingletonInstance(LogManager::class, new LogManager(self::$instance->requestId));
        }
        return static::$instance;
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
     */
    public function configure()
    {
        $this->startOutputBuffering()
            ->loadConfigurationAndInitialize()
            ->loadTypo3LoadedExtAndExtLocalconf(true)
            ->setFinalCachingFrameworkCacheConfiguration()
            ->unsetReservedGlobalVariables()
            ->loadBaseTca();
        if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            throw new \RuntimeException(
                'TYPO3 Encryption is empty. $GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'encryptionKey\'] needs to be set for TYPO3 to work securely',
                1502987245
            );
        }
        return $this;
    }

    /**
     * Run the base setup that checks server environment, determines paths,
     * populates base files and sets common configuration.
     *
     * Script execution will be aborted if something fails here.
     *
     * @param int $entryPointLevel Number of subdirectories where the entry script is located under the document root
     * @return Bootstrap
     * @throws \RuntimeException when TYPO3_REQUESTTYPE was not set before, setRequestType() needs to be called before
     * @internal This is not a public API method, do not use in own extensions
     */
    public function baseSetup($entryPointLevel = 0)
    {
        if (!defined('TYPO3_REQUESTTYPE')) {
            throw new \RuntimeException('No Request Type was set, TYPO3 does not know in which context it is run.', 1450561838);
        }
        GeneralUtility::presetApplicationContext($this->applicationContext);
        SystemEnvironmentBuilder::run($entryPointLevel);
        if (!self::$usesComposerClassLoading && ClassLoadingInformation::isClassLoadingInformationAvailable()) {
            ClassLoadingInformation::registerClassLoadingInformation();
        }
        return $this;
    }

    /**
     * Sets the class loader to the bootstrap
     *
     * @param \Composer\Autoload\ClassLoader $classLoader an instance of the class loader
     * @return Bootstrap
     * @internal This is not a public API method, do not use in own extensions
     */
    public function initializeClassLoader($classLoader)
    {
        $this->setEarlyInstance(\Composer\Autoload\ClassLoader::class, $classLoader);
        ClassLoadingInformation::setClassLoader($classLoader);
        if (defined('TYPO3_COMPOSER_MODE') && TYPO3_COMPOSER_MODE) {
            self::$usesComposerClassLoading = true;
        }

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

        return $this;
    }

    /**
     * checks if LocalConfiguration.php or PackageStates.php is missing,
     * used to see if a redirect to the install tool is needed
     *
     * @return bool TRUE when the essential configuration is available, otherwise FALSE
     * @internal This is not a public API method, do not use in own extensions
     */
    public function checkIfEssentialConfigurationExists()
    {
        $configurationManager = new \TYPO3\CMS\Core\Configuration\ConfigurationManager;
        $this->setEarlyInstance(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class, $configurationManager);
        return file_exists($configurationManager->getLocalConfigurationFileLocation()) && file_exists(PATH_typo3conf . 'PackageStates.php');
    }

    /**
     * Registers the instance of the specified object for an early boot stage.
     * On finalizing the Object Manager initialization, all those instances will
     * be transferred to the Object Manager's registry.
     *
     * @param string $objectName Object name, as later used by the Object Manager
     * @param object $instance The instance to register
     * @internal This is not a public API method, do not use in own extensions
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
     */
    public function getEarlyInstances()
    {
        return $this->earlyInstances;
    }

    /**
     * Includes LocalConfiguration.php and sets several
     * global settings depending on configuration.
     *
     * @param bool $allowCaching Whether to allow caching - affects cache_core (autoloader)
     * @param string $packageManagerClassName Define an alternative package manager implementation (usually for the installer)
     * @return Bootstrap
     * @internal This is not a public API method, do not use in own extensions
     */
    public function loadConfigurationAndInitialize($allowCaching = true, $packageManagerClassName = \TYPO3\CMS\Core\Package\PackageManager::class)
    {
        $this->populateLocalConfiguration()
            ->initializeErrorHandling()
            ->initializeCachingFramework($allowCaching)
            ->initializePackageManagement($packageManagerClassName)
            ->initializeRuntimeActivatedPackagesFromConfiguration()
            ->setDefaultTimezone()
            ->initializeL10nLocales()
            ->setMemoryLimit();
        return $this;
    }

    /**
     * Initializes the package system and loads the package configuration and settings
     * provided by the packages.
     *
     * @param string $packageManagerClassName Define an alternative package manager implementation (usually for the installer)
     * @return Bootstrap
     * @internal This is not a public API method, do not use in own extensions
     */
    public function initializePackageManagement($packageManagerClassName)
    {
        /** @var \TYPO3\CMS\Core\Package\PackageManager $packageManager */
        $packageManager = new $packageManagerClassName();
        GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\Package\PackageManager::class, $packageManager);
        $this->setEarlyInstance(\TYPO3\CMS\Core\Package\PackageManager::class, $packageManager);
        ExtensionManagementUtility::setPackageManager($packageManager);
        $packageManager->injectCoreCache(GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('cache_core'));
        $dependencyResolver = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Package\DependencyResolver::class);
        $dependencyResolver->injectDependencyOrderingService(GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\DependencyOrderingService::class));
        $packageManager->injectDependencyResolver($dependencyResolver);
        $packageManager->initialize();
        return $this;
    }

    /**
     * Activates a package during runtime. This is used in AdditionalConfiguration.php
     * to enable extensions under conditions.
     *
     * @return Bootstrap
     */
    protected function initializeRuntimeActivatedPackagesFromConfiguration()
    {
        $packages = $GLOBALS['TYPO3_CONF_VARS']['EXT']['runtimeActivatedPackages'] ?? [];
        if (!empty($packages)) {
            $packageManager = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Package\PackageManager::class);
            foreach ($packages as $runtimeAddedPackageKey) {
                $packageManager->activatePackageDuringRuntime($runtimeAddedPackageKey);
            }
        }
        return $this;
    }

    /**
     * Load ext_localconf of extensions
     *
     * @param bool $allowCaching
     * @return Bootstrap|null
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function loadTypo3LoadedExtAndExtLocalconf($allowCaching = true)
    {
        ExtensionManagementUtility::loadExtLocalconf($allowCaching);
        return static::$instance;
    }

    /**
     * We need an early instance of the configuration manager.
     * Since makeInstance relies on the object configuration, we create it here with new instead.
     *
     * @return Bootstrap
     * @internal This is not a public API method, do not use in own extensions
     */
    public function populateLocalConfiguration()
    {
        try {
            $configurationManager = $this->getEarlyInstance(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class);
        } catch (\TYPO3\CMS\Core\Exception $exception) {
            $configurationManager = new \TYPO3\CMS\Core\Configuration\ConfigurationManager();
            $this->setEarlyInstance(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class, $configurationManager);
        }
        $configurationManager->exportConfiguration();
        return $this;
    }

    /**
     * Set cache_core to null backend, effectively disabling eg. the cache for ext_localconf and PackageManager etc.
     * Used in unit tests.
     *
     * @return Bootstrap|null
     * @internal This is not a public API method, do not use in own extensions
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
     * @param bool $allowCaching
     * @return Bootstrap
     * @internal This is not a public API method, do not use in own extensions
     */
    public function initializeCachingFramework(bool $allowCaching = true)
    {
        $cacheManager = new \TYPO3\CMS\Core\Cache\CacheManager(!$allowCaching);
        $cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
        GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\Cache\CacheManager::class, $cacheManager);
        $this->setEarlyInstance(\TYPO3\CMS\Core\Cache\CacheManager::class, $cacheManager);
        return $this;
    }

    /**
     * Set default timezone
     *
     * @return Bootstrap
     */
    protected function setDefaultTimezone()
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
        return $this;
    }

    /**
     * Initialize the locales handled by TYPO3
     *
     * @return Bootstrap
     */
    protected function initializeL10nLocales()
    {
        \TYPO3\CMS\Core\Localization\Locales::initialize();
        return $this;
    }

    /**
     * Configure and set up exception and error handling
     *
     * @return Bootstrap
     * @throws \RuntimeException
     */
    protected function initializeErrorHandling()
    {
        $productionExceptionHandlerClassName = $GLOBALS['TYPO3_CONF_VARS']['SYS']['productionExceptionHandler'];
        $debugExceptionHandlerClassName = $GLOBALS['TYPO3_CONF_VARS']['SYS']['debugExceptionHandler'];

        $errorHandlerClassName = $GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandler'];
        $errorHandlerErrors = $GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandlerErrors'];
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
        }
        if (!empty($exceptionHandlerClassName)) {
            // Registering the exception handler is done in the constructor
            GeneralUtility::makeInstance($exceptionHandlerClassName);
        }
        return $this;
    }

    /**
     * Set PHP memory limit depending on value of
     * $GLOBALS['TYPO3_CONF_VARS']['SYS']['setMemoryLimit']
     *
     * @return Bootstrap
     */
    protected function setMemoryLimit()
    {
        if ((int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['setMemoryLimit'] > 16) {
            @ini_set('memory_limit', (string)((int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['setMemoryLimit'] . 'm'));
        }
        return $this;
    }

    /**
     * Define TYPO3_REQUESTTYPE* constants that can be used for developers to see if any context has been hit
     * also see setRequestType(). Is done at the very beginning so these parameters are always available.
     */
    protected function defineTypo3RequestTypes()
    {
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
     * @return Bootstrap
     * @internal This is not a public API method, do not use in own extensions
     */
    public function setFinalCachingFrameworkCacheConfiguration()
    {
        GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
        return $this;
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
     * @return Bootstrap|null
     * @internal This is not a public API method, do not use in own extensions
     */
    public static function loadBaseTca(bool $allowCaching = true)
    {
        ExtensionManagementUtility::loadBaseTca($allowCaching);
        return static::$instance;
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
            /** @var $hookObject \TYPO3\CMS\Core\Database\TableConfigurationPostProcessingHookInterface */
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
        $cacheIdentifier = 'BackendRoutesFromPackages_' . sha1((TYPO3_version . PATH_site . 'BackendRoutesFromPackages'));

        /** @var $codeCache \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface */
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
        /** @var $backendUser \TYPO3\CMS\Core\Authentication\BackendUserAuthentication */
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
}
