<?php
namespace TYPO3\CMS\Core\Core;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Christian Kuhn <lolli@schwarzbu.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility;

require_once __DIR__ . '/SystemEnvironmentBuilder.php';

/**
 * This class encapsulates bootstrap related methods.
 * It is required directly as the very first thing in entry scripts and
 * used to define all base things like constants and pathes and so on.
 *
 * Most methods in this class have dependencies to each other. They can
 * not be called in arbitrary order. The methods are ordered top down, so
 * a method at the beginning has lower dependencies than a method further
 * down. Do not fiddle with the load order in own scripts except you know
 * exactly what you are doing!
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class Bootstrap {

	/**
	 * @var \TYPO3\CMS\Core\Core\Bootstrap
	 */
	static protected $instance = NULL;

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
	protected $earlyInstances = array();

	/**
	 * @var string Path to install tool
	 */
	protected $installToolPath;

	/**
	 * Disable direct creation of this object.
	 * Set unique requestId and the application context
	 *
	 * @var string Application context
	 */
	protected function __construct($applicationContext) {
		$this->requestId = uniqid();
		$this->applicationContext = new ApplicationContext($applicationContext);
	}

	/**
	 * Disable direct cloning of this object.
	 */
	protected function __clone() {

	}

	/**
	 * Return 'this' as singleton
	 *
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	static public function getInstance() {
		if (is_null(static::$instance)) {
			require_once(__DIR__ . '/../Exception.php');
			require_once(__DIR__ . '/ApplicationContext.php');
			$applicationContext = getenv('TYPO3_CONTEXT') ?: (getenv('REDIRECT_TYPO3_CONTEXT') ?: 'Production');
			self::$instance = new static($applicationContext);
			// Establish an alias for Flow/Package interoperability
			class_alias(get_class(static::$instance), 'TYPO3\\Flow\\Core\\Bootstrap');
		}
		return static::$instance;
	}

	/**
	 * Gets the request's unique ID
	 *
	 * @return string Unique request ID
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function getRequestId() {
		return $this->requestId;
	}

	/**
	 * Returns the application context this bootstrap was started in.
	 *
	 * @return \TYPO3\CMS\Core\Core\ApplicationContext The application context encapsulated in an object
	 * @internal This is not a public API method, do not use in own extensions.
	 * Use \TYPO3\CMS\Core\Utility\GeneralUtility::getApplicationContext() instead
	 */
	public function getApplicationContext() {
		return $this->applicationContext;
	}

	/**
	 * Prevent any unwanted output that may corrupt AJAX/compression.
	 * This does not interfere with "die()" or "echo"+"exit()" messages!
	 *
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function startOutputBuffering() {
		ob_start();
		return $this;
	}

	/**
	 * Run the base setup that checks server environment, determines pathes,
	 * populates base files and sets common configuration.
	 *
	 * Script execution will be aborted if something fails here.
	 *
	 * @param string $relativePathPart Relative path of entry script back to document root
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function baseSetup($relativePathPart = '') {
		SystemEnvironmentBuilder::run($relativePathPart);
		Utility\GeneralUtility::presetApplicationContext($this->applicationContext);
		return $this;
	}

	/**
	 * Redirect to install tool if LocalConfiguration.php is missing.
	 *
	 * @param string $pathUpToDocumentRoot Can contain '../' if called from a sub directory
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function redirectToInstallerIfEssentialConfigurationDoesNotExist($pathUpToDocumentRoot = '') {
		$configurationManager = new \TYPO3\CMS\Core\Configuration\ConfigurationManager;
		$this->setEarlyInstance('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager', $configurationManager);
		if (!file_exists($configurationManager->getLocalConfigurationFileLocation()) || !file_exists(PATH_typo3conf . 'PackageStates.php')) {
			require_once __DIR__ . '/../Utility/HttpUtility.php';
			Utility\HttpUtility::redirect($pathUpToDocumentRoot . 'typo3/sysext/install/Start/Install.php');
		}
		return $this;
	}

	/**
	 * Registers the instance of the specified object for an early boot stage.
	 * On finalizing the Object Manager initialization, all those instances will
	 * be transferred to the Object Manager's registry.
	 *
	 * @param string $objectName Object name, as later used by the Object Manager
	 * @param object $instance The instance to register
	 * @return void
	 */
	public function setEarlyInstance($objectName, $instance) {
		$this->earlyInstances[$objectName] = $instance;
	}

	/**
	 * Returns an instance which was registered earlier through setEarlyInstance()
	 *
	 * @param string $objectName Object name of the registered instance
	 * @return object
	 * @throws \TYPO3\CMS\Core\Exception
	 */
	public function getEarlyInstance($objectName) {
		if (!isset($this->earlyInstances[$objectName])) {
			throw new \TYPO3\CMS\Core\Exception('Unknown early instance "' . $objectName . '"', 1365167380);
		}
		return $this->earlyInstances[$objectName];
	}

	/**
	 * Returns all registered early instances indexed by object name
	 *
	 * @return array
	 */
	public function getEarlyInstances() {
		return $this->earlyInstances;
	}

	/**
	 * Includes LocalConfiguration.php and sets several
	 * global settings depending on configuration.
	 *
	 * @param boolean $allowCaching Whether to allow caching - affects cache_core (autoloader)
	 * @param string $packageManagerClassName Define an alternative package manager implementation (usually for the installer)
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function loadConfigurationAndInitialize($allowCaching = TRUE, $packageManagerClassName = 'TYPO3\\CMS\\Core\\Package\\PackageManager') {
		$this->initializeClassLoader()
			->populateLocalConfiguration();
		if (!$allowCaching) {
			$this->disableCoreAndClassesCache();
		}
		$this->initializeCachingFramework()
			->initializeClassLoaderCaches()
			->initializePackageManagement($packageManagerClassName)
			->initializeRuntimeActivatedPackagesFromConfiguration();

		$this->defineDatabaseConstants()
			->defineUserAgentConstant()
			->registerExtDirectComponents()
			->transferDeprecatedCurlSettings()
			->setCacheHashOptions()
			->setDefaultTimezone()
			->initializeL10nLocales()
			->convertPageNotFoundHandlingToBoolean()
			->registerGlobalDebugFunctions()
			// SwiftMailerAdapter is
			// @deprecated since 6.1, will be removed two versions later - will be removed together with \TYPO3\CMS\Core\Utility\MailUtility::mail()
			->registerSwiftMailer()
			->configureExceptionHandling()
			->setMemoryLimit()
			->defineTypo3RequestTypes();
		return $this;
	}

	/**
	 * Initializes the Class Loader
	 *
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function initializeClassLoader() {
		$classLoader = new ClassLoader($this->applicationContext);
		$this->setEarlyInstance('TYPO3\\CMS\\Core\\Core\\ClassLoader', $classLoader);
		$classLoader->setRuntimeClassLoadingInformationFromAutoloadRegistry((array) include __DIR__ . '/../../ext_autoload.php');
		$classAliasMap = new ClassAliasMap();
		$classAliasMap->injectClassLoader($classLoader);
		$this->setEarlyInstance('TYPO3\\CMS\\Core\\Core\\ClassAliasMap', $classAliasMap);
		$classLoader->injectClassAliasMap($classAliasMap);
		spl_autoload_register(array($classLoader, 'loadClass'), TRUE, TRUE);
		return $this;
	}

	/**
	 * Unregister class loader
	 *
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function unregisterClassLoader() {
		$currentClassLoader = $this->getEarlyInstance('TYPO3\\CMS\\Core\\Core\\ClassLoader');
		spl_autoload_unregister(array($currentClassLoader, 'loadClass'));
		return $this;
	}

	/**
	 * Initialize class loader cache.
	 *
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function initializeClassLoaderCaches() {
		/** @var $classLoader ClassLoader */
		$classLoader = $this->getEarlyInstance('TYPO3\\CMS\\Core\\Core\\ClassLoader');
		$classLoader->injectCoreCache($this->getEarlyInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->getCache('cache_core'));
		$classLoader->injectClassesCache($this->getEarlyInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->getCache('cache_classes'));
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
	public function initializePackageManagement($packageManagerClassName) {
		/** @var \TYPO3\CMS\Core\Package\PackageManager $packageManager */
		$packageManager = new $packageManagerClassName();
		$this->setEarlyInstance('TYPO3\\Flow\\Package\\PackageManager', $packageManager);
		Utility\ExtensionManagementUtility::setPackageManager($packageManager);
		$packageManager->injectClassLoader($this->getEarlyInstance('TYPO3\\CMS\\Core\\Core\\ClassLoader'));
		$packageManager->injectCoreCache($this->getEarlyInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->getCache('cache_core'));
		$packageManager->injectDependencyResolver(Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Package\\DependencyResolver'));
		$packageManager->initialize($this);
		Utility\GeneralUtility::setSingletonInstance('TYPO3\\CMS\\Core\\Package\\PackageManager', $packageManager);
		return $this;
	}

	/**
	 * Activates a package during runtime. This is used in AdditionalConfiguration.php
	 * to enable extensions under conditions.
	 *
	 * @return Bootstrap
	 */
	protected function initializeRuntimeActivatedPackagesFromConfiguration() {
		if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['runtimeActivatedPackages']) && is_array($GLOBALS['TYPO3_CONF_VARS']['EXT']['runtimeActivatedPackages'])) {
			/** @var \TYPO3\CMS\Core\Package\PackageManager $packageManager */
			$packageManager = $this->getEarlyInstance('TYPO3\\Flow\\Package\\PackageManager');
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXT']['runtimeActivatedPackages'] as $runtimeAddedPackageKey) {
				$packageManager->activatePackageDuringRuntime($runtimeAddedPackageKey);
			}
		}
		return $this;
	}

	/**
	 * Load ext_localconf of extensions
	 *
	 * @param boolean $allowCaching
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function loadTypo3LoadedExtAndExtLocalconf($allowCaching = TRUE) {
		$this->getInstance()
			->loadAdditionalConfigurationFromExtensions($allowCaching);
		return $this;
	}

	/**
	 * Sets up additional configuration applied in all scopes
	 *
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function applyAdditionalConfigurationSettings() {
		$this->getInstance()
			->initializeExceptionHandling()
			->setFinalCachingFrameworkCacheConfiguration()
			->defineLoggingAndExceptionConstants()
			->unsetReservedGlobalVariables();
		return $this;
	}

	/**
	 * Throws an exception if no browser could be identified
	 *
	 * @return Bootstrap
	 * @throws \RuntimeException
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function checkValidBrowserOrDie() {
		// Checks for proper browser
		if (empty($GLOBALS['CLIENT']['BROWSER'])) {
			throw new \RuntimeException('Browser Error: Your browser version looks incompatible with this TYPO3 version!', 1294587023);
		}
		return $this;
	}

	/**
	 * We need an early instance of the configuration manager.
	 * Since makeInstance relies on the object configuration, we create it here with new instead.
	 *
	 * @return Bootstrap
	 */
	public function populateLocalConfiguration() {
		try {
			$configurationManager = $this->getEarlyInstance('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
		} catch(\TYPO3\CMS\Core\Exception $exception) {
			$configurationManager = new \TYPO3\CMS\Core\Configuration\ConfigurationManager();
			$this->setEarlyInstance('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager', $configurationManager);
		}
		$configurationManager->exportConfiguration();
		return $this;
	}

	/**
	 * Set cache_core to null backend, effectively disabling eg. the autoloader cache
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function disableCoreAndClassesCache() {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_core']['backend']
			= 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend';
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_classes']['backend']
			= 'TYPO3\\CMS\\Core\\Cache\\Backend\\TransientMemoryBackend';
		return $this;
	}

	/**
	 * Define database constants
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 */
	protected function defineDatabaseConstants() {
		define('TYPO3_db', $GLOBALS['TYPO3_CONF_VARS']['DB']['database']);
		define('TYPO3_db_username', $GLOBALS['TYPO3_CONF_VARS']['DB']['username']);
		define('TYPO3_db_password', $GLOBALS['TYPO3_CONF_VARS']['DB']['password']);
		define('TYPO3_db_host', $GLOBALS['TYPO3_CONF_VARS']['DB']['host']);
		define('TYPO3_extTableDef_script',
			isset($GLOBALS['TYPO3_CONF_VARS']['DB']['extTablesDefinitionScript'])
			? $GLOBALS['TYPO3_CONF_VARS']['DB']['extTablesDefinitionScript']
			: 'extTables.php');
		return $this;
	}

	/**
	 * Define user agent constant
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 */
	protected function defineUserAgentConstant() {
		define('TYPO3_user_agent', 'User-Agent: ' . $GLOBALS['TYPO3_CONF_VARS']['HTTP']['userAgent']);
		return $this;
	}

	/**
	 * Register default ExtDirect components
	 *
	 * @return Bootstrap
	 */
	protected function registerExtDirectComponents() {
		if (TYPO3_MODE === 'BE') {
			Utility\ExtensionManagementUtility::registerExtDirectComponent('TYPO3.Components.PageTree.DataProvider', 'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\ExtdirectTreeDataProvider', 'web', 'user,group');
			Utility\ExtensionManagementUtility::registerExtDirectComponent('TYPO3.Components.PageTree.Commands', 'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\ExtdirectTreeCommands', 'web', 'user,group');
			Utility\ExtensionManagementUtility::registerExtDirectComponent('TYPO3.Components.PageTree.ContextMenuDataProvider', 'TYPO3\\CMS\\Backend\\ContextMenu\\Pagetree\\Extdirect\\ContextMenuConfiguration', 'web', 'user,group');
			Utility\ExtensionManagementUtility::registerExtDirectComponent('TYPO3.LiveSearchActions.ExtDirect', 'TYPO3\\CMS\\Backend\\Search\\LiveSearch\\ExtDirect\\LiveSearchDataProvider', 'web_list', 'user,group');
			Utility\ExtensionManagementUtility::registerExtDirectComponent('TYPO3.BackendUserSettings.ExtDirect', 'TYPO3\\CMS\\Backend\\User\\ExtDirect\\BackendUserSettingsDataProvider');
			if (Utility\ExtensionManagementUtility::isLoaded('context_help')) {
				Utility\ExtensionManagementUtility::registerExtDirectComponent('TYPO3.CSH.ExtDirect', 'TYPO3\\CMS\\ContextHelp\\ExtDirect\\ContextHelpDataProvider');
			}
			Utility\ExtensionManagementUtility::registerExtDirectComponent('TYPO3.ExtDirectStateProvider.ExtDirect', 'TYPO3\\CMS\\Backend\\InterfaceState\\ExtDirect\\DataProvider');
			Utility\ExtensionManagementUtility::registerExtDirectComponent(
				'TYPO3.Components.DragAndDrop.CommandController',
				Utility\ExtensionManagementUtility::extPath('backend') . 'Classes/View/PageLayout/Extdirect/ExtdirectPageCommands.php:TYPO3\\CMS\\Backend\\View\\PageLayout\\ExtDirect\\ExtdirectPageCommands', 'web', 'user,group'
			);
		}
		return $this;
	}

	/**
	 * Initialize caching framework
	 *
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function initializeCachingFramework() {
		$this->setEarlyInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager', \TYPO3\CMS\Core\Cache\Cache::initializeCachingFramework());
		// @deprecated since 6.2 will be removed in two versions
		$GLOBALS['typo3CacheManager'] = new \TYPO3\CMS\Core\Compatibility\GlobalObjectDeprecationDecorator('TYPO3\\CMS\\Core\\Cache\\CacheManager');
		$GLOBALS['typo3CacheFactory'] = new \TYPO3\CMS\Core\Compatibility\GlobalObjectDeprecationDecorator('TYPO3\\CMS\\Core\\Cache\\CacheFactory');
		return $this;
	}

	/**
	 * Parse old curl options and set new http ones instead
	 *
	 * @TODO: This code segment must still be finished
	 * @return Bootstrap
	 */
	protected function transferDeprecatedCurlSettings() {
		if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer'])) {
			$proxyParts = Utility\GeneralUtility::revExplode(':', $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer'], 2);
			$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_host'] = $proxyParts[0];
			$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_port'] = $proxyParts[1];
		}
		if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass'])) {
			$userPassParts = explode(':', $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass'], 2);
			$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_user'] = $userPassParts[0];
			$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_password'] = $userPassParts[1];
		}
		return $this;
	}

	/**
	 * Set cacheHash options
	 *
	 * @return Bootstrap
	 */
	protected function setCacheHashOptions() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash'] = array(
			'cachedParametersWhiteList' => Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['cHashOnlyForParameters'], TRUE),
			'excludedParameters' => Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['cHashExcludedParameters'], TRUE),
			'requireCacheHashPresenceParameters' => Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['cHashRequiredParameters'], TRUE)
		);
		if (trim($GLOBALS['TYPO3_CONF_VARS']['FE']['cHashExcludedParametersIfEmpty']) === '*') {
			$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludeAllEmptyParameters'] = TRUE;
		} else {
			$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParametersIfEmpty'] = Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['cHashExcludedParametersIfEmpty'], TRUE);
		}
		return $this;
	}

	/**
	 * Set default timezone
	 *
	 * @return Bootstrap
	 */
	protected function setDefaultTimezone() {
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
	protected function initializeL10nLocales() {
		\TYPO3\CMS\Core\Localization\Locales::initialize();
		return $this;
	}

	/**
	 * Convert type of "pageNotFound_handling" setting in case it was written as a
	 * string (e.g. if edited in Install Tool)
	 *
	 * @TODO : Remove, if the Install Tool handles such data types correctly
	 * @return Bootstrap
	 */
	protected function convertPageNotFoundHandlingToBoolean() {
		if (!strcasecmp($GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'], 'TRUE')) {
			$GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling'] = TRUE;
		}
		return $this;
	}

	/**
	 * Register xdebug(), debug(), debugBegin() and debugEnd() as global functions
	 *
	 * Note: Yes, this is possible in php! xdebug() is then a global function, even
	 * if registerGlobalDebugFunctions() is encapsulated in class scope.
	 *
	 * @return Bootstrap
	 */
	protected function registerGlobalDebugFunctions() {
		require_once('GlobalDebugFunctions.php');
		return $this;
	}

	/**
	 * Mail sending via Swift Mailer
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @deprecated since 6.1, will be removed two versions later - will be removed together with \TYPO3\CMS\Core\Utility\MailUtility::mail()
	 */
	protected function registerSwiftMailer() {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/utility/class.t3lib_utility_mail.php']['substituteMailDelivery'][] =
			'TYPO3\\CMS\\Core\\Mail\\SwiftMailerAdapter';
		return $this;
	}

	/**
	 * Configure and set up exception and error handling
	 *
	 * @return Bootstrap
	 */
	protected function configureExceptionHandling() {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionHandler'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['productionExceptionHandler'];
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionalErrors'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors'];
		// Turn error logging on/off.
		if (($displayErrors = (int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors']) != '-1') {
			// Special value "2" enables this feature only if $GLOBALS['TYPO3_CONF_VARS'][SYS][devIPmask] matches
			if ($displayErrors == 2) {
				if (Utility\GeneralUtility::cmpIP(Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'])) {
					$displayErrors = 1;
				} else {
					$displayErrors = 0;
				}
			}
			if ($displayErrors == 0) {
				$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionalErrors'] = 0;
			}
			if ($displayErrors == 1) {
				$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionHandler'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['debugExceptionHandler'];
				define('TYPO3_ERRORHANDLER_MODE', 'debug');
			}
			@ini_set('display_errors', $displayErrors);
		} elseif (Utility\GeneralUtility::cmpIP(Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'])) {
			// With displayErrors = -1 (default), turn on debugging if devIPmask matches:
			$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionHandler'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['debugExceptionHandler'];
		}
		return $this;
	}

	/**
	 * Set PHP memory limit depending on value of
	 * $GLOBALS['TYPO3_CONF_VARS']['SYS']['setMemoryLimit']
	 *
	 * @return Bootstrap
	 */
	protected function setMemoryLimit() {
		if ((int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['setMemoryLimit'] > 16) {
			@ini_set('memory_limit', ((int)$GLOBALS['TYPO3_CONF_VARS']['SYS']['setMemoryLimit'] . 'm'));
		}
		return $this;
	}

	/**
	 * Define TYPO3_REQUESTTYPE* constants
	 * so devs exactly know what type of request it is
	 *
	 * @return Bootstrap
	 */
	protected function defineTypo3RequestTypes() {
		define('TYPO3_REQUESTTYPE_FE', 1);
		define('TYPO3_REQUESTTYPE_BE', 2);
		define('TYPO3_REQUESTTYPE_CLI', 4);
		define('TYPO3_REQUESTTYPE_AJAX', 8);
		define('TYPO3_REQUESTTYPE_INSTALL', 16);
		define('TYPO3_REQUESTTYPE', (TYPO3_MODE == 'FE' ? TYPO3_REQUESTTYPE_FE : 0) | (TYPO3_MODE == 'BE' ? TYPO3_REQUESTTYPE_BE : 0) | (defined('TYPO3_cliMode') && TYPO3_cliMode ? TYPO3_REQUESTTYPE_CLI : 0) | (defined('TYPO3_enterInstallScript') && TYPO3_enterInstallScript ? TYPO3_REQUESTTYPE_INSTALL : 0) | ($GLOBALS['TYPO3_AJAX'] ? TYPO3_REQUESTTYPE_AJAX : 0));
		return $this;
	}

	/**
	 * Load extension configuration files (ext_localconf.php)
	 *
	 * The ext_localconf.php files in extensions are meant to make changes
	 * to the global $TYPO3_CONF_VARS configuration array.
	 *
	 * @param boolean $allowCaching
	 * @return Bootstrap
	 */
	protected function loadAdditionalConfigurationFromExtensions($allowCaching = TRUE) {
		Utility\ExtensionManagementUtility::loadExtLocalconf($allowCaching);
		return $this;
	}

	/**
	 * Initialize exception handling
	 *
	 * @return Bootstrap
	 */
	protected function initializeExceptionHandling() {
		if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionHandler'])) {
			if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandler'])) {
				// Register an error handler for the given errorHandlerErrors
				$errorHandler = Utility\GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandler'], $GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandlerErrors']);
				// Set errors which will be converted in an exception
				$errorHandler->setExceptionalErrors($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionalErrors']);
			}
			// Instantiate the exception handler once to make sure object is registered
			// @TODO: Figure out if this is really needed
			Utility\GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionHandler']);
		}
		return $this;
	}

	/**
	 * Extensions may register new caches, so we set the
	 * global cache array to the manager again at this point
	 *
	 * @return Bootstrap
	 */
	protected function setFinalCachingFrameworkCacheConfiguration() {
		$this->getEarlyInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
		return $this;
	}

	/**
	 * Define logging and exception constants
	 *
	 * @return Bootstrap
	 */
	protected function defineLoggingAndExceptionConstants() {
		define('TYPO3_DLOG', $GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_DLOG']);
		define('TYPO3_ERROR_DLOG', $GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_errorDLOG']);
		define('TYPO3_EXCEPTION_DLOG', $GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_exceptionDLOG']);
		return $this;
	}

	/**
	 * Unsetting reserved global variables:
	 * Those are set in "ext:core/ext_tables.php" file:
	 *
	 * @return Bootstrap
	 */
	protected function unsetReservedGlobalVariables() {
		unset($GLOBALS['PAGES_TYPES']);
		unset($GLOBALS['TCA']);
		unset($GLOBALS['TBE_MODULES']);
		unset($GLOBALS['TBE_STYLES']);
		unset($GLOBALS['FILEICONS']);
		// Those set in init.php:
		unset($GLOBALS['WEBMOUNTS']);
		unset($GLOBALS['BE_USER']);
		// Those set otherwise:
		unset($GLOBALS['TBE_MODULES_EXT']);
		unset($GLOBALS['TCA_DESCR']);
		unset($GLOBALS['LOCAL_LANG']);
		unset($GLOBALS['TYPO3_AJAX']);
		return $this;
	}

	/**
	 * Initialize database connection in $GLOBALS and connect if requested
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function initializeTypo3DbGlobal() {
		/** @var $databaseConnection \TYPO3\CMS\Core\Database\DatabaseConnection */
		$databaseConnection = Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
		$databaseConnection->setDatabaseName(TYPO3_db);
		$databaseConnection->setDatabaseUsername(TYPO3_db_username);
		$databaseConnection->setDatabasePassword(TYPO3_db_password);

		$databaseHost = TYPO3_db_host;
		if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['port'])) {
			$databaseConnection->setDatabasePort($GLOBALS['TYPO3_CONF_VARS']['DB']['port']);
		} elseif (strpos($databaseHost, ':') > 0) {
			// @TODO: Find a way to handle this case in the install tool and drop this
			list($databaseHost, $databasePort) = explode(':', $databaseHost);
			$databaseConnection->setDatabasePort($databasePort);
		}
		if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['socket'])) {
			$databaseConnection->setDatabaseSocket($GLOBALS['TYPO3_CONF_VARS']['DB']['socket']);
		}
		$databaseConnection->setDatabaseHost($databaseHost);

		$databaseConnection->debugOutput = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sqlDebug'];

		if (
			isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['no_pconnect'])
			&& !$GLOBALS['TYPO3_CONF_VARS']['SYS']['no_pconnect']
		) {
			$databaseConnection->setPersistentDatabaseConnection(TRUE);
		}

		$isDatabaseHostLocalHost = $databaseHost === 'localhost' || $databaseHost === '127.0.0.1' || $databaseHost === '::1';
		if (
			isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['dbClientCompress'])
			&& $GLOBALS['TYPO3_CONF_VARS']['SYS']['dbClientCompress']
			&& !$isDatabaseHostLocalHost
		) {
			$databaseConnection->setConnectionCompression(TRUE);
		}

		if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit'])) {
			$commandsAfterConnect = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(
				LF,
				str_replace('\' . LF . \'', LF, $GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit']),
				TRUE
			);
			$databaseConnection->setInitializeCommandsAfterConnect($commandsAfterConnect);
		}

		$GLOBALS['TYPO3_DB'] = $databaseConnection;
		// $GLOBALS['TYPO3_DB'] needs to be defined first in order to work for DBAL
		$GLOBALS['TYPO3_DB']->initialize();

		return $this;
	}

	/**
	 * Check adminOnly configuration variable and redirects
	 * to an URL in file typo3conf/LOCK_BACKEND or exit the script
	 *
	 * @throws \RuntimeException
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function checkLockedBackendAndRedirectOrDie() {
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly'] < 0) {
			throw new \RuntimeException('TYPO3 Backend locked: Backend and Install Tool are locked for maintenance. [BE][adminOnly] is set to "' . (int)$GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly'] . '".', 1294586847);
		}
		if (@is_file((PATH_typo3conf . 'LOCK_BACKEND'))) {
			if (TYPO3_PROCEED_IF_NO_USER === 2) {

			} else {
				$fileContent = Utility\GeneralUtility::getUrl(PATH_typo3conf . 'LOCK_BACKEND');
				if ($fileContent) {
					header('Location: ' . $fileContent);
				} else {
					throw new \RuntimeException('TYPO3 Backend locked: Browser backend is locked for maintenance. Remove lock by removing the file "typo3conf/LOCK_BACKEND" or use CLI-scripts.', 1294586848);
				}
				die;
			}
		}
		return $this;
	}

	/**
	 * Compare client IP with IPmaskList and exit the script run
	 * if the client is not allowed to access the backend
	 *
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 * @throws \RuntimeException
	 */
	public function checkBackendIpOrDie() {
		if (trim($GLOBALS['TYPO3_CONF_VARS']['BE']['IPmaskList'])) {
			if (!Utility\GeneralUtility::cmpIP(Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['BE']['IPmaskList'])) {
				throw new \RuntimeException('TYPO3 Backend access denied: The IP address of your client does not match the list of allowed IP addresses.', 1389265900);
			}
		}
		return $this;
	}

	/**
	 * Check lockSSL configuration variable and redirect
	 * to https version of the backend if needed
	 *
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 * @throws \RuntimeException
	 */
	public function checkSslBackendAndRedirectIfNeeded() {
		if ((int)$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL']) {
			if ((int)$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSLPort']) {
				$sslPortSuffix = ':' . (int)$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSLPort'];
			} else {
				$sslPortSuffix = '';
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] == 3) {
				$requestStr = substr(Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_SCRIPT'), strlen(Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir));
				if ($requestStr === 'index.php' && !Utility\GeneralUtility::getIndpEnv('TYPO3_SSL')) {
					list(, $url) = explode('://', Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'), 2);
					list($server, $address) = explode('/', $url, 2);
					header('Location: https://' . $server . $sslPortSuffix . '/' . $address);
					die;
				}
			} elseif (!Utility\GeneralUtility::getIndpEnv('TYPO3_SSL')) {
				if ((int)$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] === 2) {
					list(, $url) = explode('://', Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir, 2);
					list($server, $address) = explode('/', $url, 2);
					header('Location: https://' . $server . $sslPortSuffix . '/' . $address);
					die;
				} else {
					throw new \RuntimeException('TYPO3 Backend not accessed via SSL: TYPO3 Backend is configured to only be accessible through SSL. Change the URL in your browser and try again.', 1389265726);
				}
			}
		}
		return $this;
	}

	/**
	 * Load TCA for frontend
	 *
	 * This method is *only* executed in frontend scope. The idea is to execute the
	 * whole TCA and ext_tables (which manipulate TCA) on first frontend access,
	 * and then cache the full TCA on disk to be used for the next run again.
	 *
	 * This way, ext_tables.php ist not executed every time, but $GLOBALS['TCA']
	 * is still always there.
	 *
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function loadCachedTca() {
		$cacheIdentifier = 'tca_fe_' . sha1((TYPO3_version . PATH_site . 'tca_fe'));
		/** @var $codeCache \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend */
		$codeCache = $this->getEarlyInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager')->getCache('cache_core');
		if ($codeCache->has($cacheIdentifier)) {
			// substr is necessary, because the php frontend wraps php code around the cache value
			$GLOBALS['TCA'] = unserialize(substr($codeCache->get($cacheIdentifier), 6, -2));
		} else {
			$this->loadExtensionTables(TRUE);
			$codeCache->set($cacheIdentifier, serialize($GLOBALS['TCA']));
		}
		return $this;
	}

	/**
	 * Load ext_tables and friends.
	 *
	 * This will mainly set up $TCA and several other global arrays
	 * through API's like extMgm.
	 * Executes ext_tables.php files of loaded extensions or the
	 * according cache file if exists.
	 *
	 * @param boolean $allowCaching True, if reading compiled ext_tables file from cache is allowed
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function loadExtensionTables($allowCaching = TRUE) {
		Utility\ExtensionManagementUtility::loadBaseTca($allowCaching);
		Utility\ExtensionManagementUtility::loadExtTables($allowCaching);
		$this->executeExtTablesAdditionalFile();
		$this->runExtTablesPostProcessingHooks();
		return $this;
	}

	/**
	 * Execute TYPO3_extTableDef_script if defined and exists
	 *
	 * Note: For backwards compatibility some global variables are
	 * explicitly set as global to be used without $GLOBALS[] in
	 * the extension table script. It is discouraged to access variables like
	 * $TBE_MODULES directly, but we can not prohibit
	 * this without heavily breaking backwards compatibility.
	 *
	 * @TODO : We could write a scheduler / reports module or an update checker
	 * @TODO : It should be defined, which global arrays are ok to be manipulated
	 *
	 * @return void
	 */
	protected function executeExtTablesAdditionalFile() {
		// It is discouraged to use those global variables directly, but we
		// can not prohibit this without breaking backwards compatibility
		global $T3_SERVICES, $T3_VAR, $TYPO3_CONF_VARS;
		global $TBE_MODULES, $TBE_MODULES_EXT, $TCA;
		global $PAGES_TYPES, $TBE_STYLES, $FILEICONS;
		global $_EXTKEY;
		// Load additional ext tables script if the file exists
		$extTablesFile = PATH_typo3conf . TYPO3_extTableDef_script;
		if (file_exists($extTablesFile) && is_file($extTablesFile)) {
			include $extTablesFile;
		}
	}

	/**
	 * Check for registered ext tables hooks and run them
	 *
	 * @throws \UnexpectedValueException
	 * @return void
	 */
	protected function runExtTablesPostProcessingHooks() {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing'] as $classReference) {
				/** @var $hookObject \TYPO3\CMS\Core\Database\TableConfigurationPostProcessingHookInterface */
				$hookObject = Utility\GeneralUtility::getUserObj($classReference);
				if (!$hookObject instanceof \TYPO3\CMS\Core\Database\TableConfigurationPostProcessingHookInterface) {
					throw new \UnexpectedValueException('$hookObject must implement interface TYPO3\\CMS\\Core\\Database\\TableConfigurationPostProcessingHookInterface', 1320585902);
				}
				$hookObject->processData();
			}
		}
	}

	/**
	 * Initialize sprite manager
	 *
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function initializeSpriteManager() {
		\TYPO3\CMS\Backend\Sprite\SpriteManager::initialize();
		return $this;
	}

	/**
	 * Initialize backend user object in globals
	 *
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function initializeBackendUser() {
		/** @var $backendUser \TYPO3\CMS\Core\Authentication\BackendUserAuthentication */
		$backendUser = Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$backendUser->warningEmail = $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'];
		$backendUser->lockIP = $GLOBALS['TYPO3_CONF_VARS']['BE']['lockIP'];
		$backendUser->auth_timeout_field = (int)$GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout'];
		$backendUser->OS = TYPO3_OS;
		if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) {
			$backendUser->dontSetCookie = TRUE;
		}
		// The global must be available very early, because methods below
		// might trigger code which relies on it. See: #45625
		$GLOBALS['BE_USER'] = $backendUser;
		$backendUser->start();
		return $this;
	}

	/**
	 * Initializes and ensures authenticated access
	 *
	 * @internal This is not a public API method, do not use in own extensions
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 */
	public function initializeBackendAuthentication() {
		$GLOBALS['BE_USER']->checkCLIuser();
		$GLOBALS['BE_USER']->backendCheckLogin();
		return $this;
	}

	/**
	 * Initialize backend user mount points
	 *
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function initializeBackendUserMounts() {
		// Includes deleted mount pages as well! @TODO: Figure out why ...
		$GLOBALS['WEBMOUNTS'] = $GLOBALS['BE_USER']->returnWebmounts();
		return $this;
	}

	/**
	 * Initialize language object
	 *
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function initializeLanguageObject() {
		/** @var $GLOBALS['LANG'] \TYPO3\CMS\Lang\LanguageService */
		$GLOBALS['LANG'] = Utility\GeneralUtility::makeInstance('TYPO3\CMS\Lang\LanguageService');
		$GLOBALS['LANG']->init($GLOBALS['BE_USER']->uc['lang']);
		return $this;
	}

	/**
	 * Throw away all output that may have happened during bootstrapping by weird extensions
	 *
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function endOutputBufferingAndCleanPreviousOutput() {
		ob_clean();
		return $this;
	}

	/**
	 * Initialize output compression if configured
	 *
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function initializeOutputCompression() {
		if (extension_loaded('zlib') && $GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel']) {
			if (Utility\MathUtility::canBeInterpretedAsInteger($GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel'])) {
				@ini_set('zlib.output_compression_level', $GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel']);
			}
			ob_start('ob_gzhandler');
		}
		return $this;
	}

	/**
	 * Send HTTP headers if configured
	 *
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function sendHttpHeaders() {
		if (!empty($GLOBALS['TYPO3_CONF_VARS']['BE']['HTTP']['Response']['Headers']) && is_array($GLOBALS['TYPO3_CONF_VARS']['BE']['HTTP']['Response']['Headers'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['BE']['HTTP']['Response']['Headers'] as $header) {
				header($header);
			}
		}
		return $this;
	}

	/**
	 * Things that should be performed to shut down the framework.
	 * This method is called in all important scripts for a clean
	 * shut down of the system.
	 *
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function shutdown() {
		return $this;
	}

	/**
	 * Provides an instance of "template" for backend-modules to
	 * work with.
	 *
	 * @return Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function initializeBackendTemplate() {
		$GLOBALS['TBE_TEMPLATE'] = Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		return $this;
	}
}
