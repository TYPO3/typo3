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
 *  A copy is found in the textfile GPL.txt and important notices to the license
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

use \TYPO3\CMS\Core\Utility;

require 'SystemEnvironmentBuilder.php';

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
	 * Disable direct creation of this object.
	 */
	protected function __construct() {
		$this->requestId = uniqid();
	}

	/**
	 * Disable direct cloning of this object.
	 */
	protected function __clone() {

	}

	/**
	 * Return 'this' as singleton
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	static public function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new \TYPO3\CMS\Core\Core\Bootstrap();
		}
		return self::$instance;
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
	 * Prevent any unwanted output that may corrupt AJAX/compression.
	 * This does not interfere with "die()" or "echo"+"exit()" messages!
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function startOutputBuffering() {
		ob_start();
		return $this;
	}

	/**
	 * Run the base setup that checks server environment,
	 * determines pathes, populates base files and sets common configuration.
	 *
	 * Script execution will be aborted if something fails here.
	 *
	 * @param string $relativePathPart Relative path of the entry script back to document root
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function baseSetup($relativePathPart = '') {
		\TYPO3\CMS\Core\Core\SystemEnvironmentBuilder::run($relativePathPart);
		return $this;
	}

	/**
	 * Redirect to install tool if LocalConfiguration.php is missing
	 *
	 * @param string $pathUpToDocumentRoot Can contain eg. '../' if called from a sub directory
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function redirectToInstallToolIfLocalConfigurationFileDoesNotExist($pathUpToDocumentRoot = '') {
		/** @var $configurationManager \TYPO3\CMS\Core\Configuration\ConfigurationManager */
		$configurationManager = Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
		if (
			!file_exists($configurationManager->getLocalConfigurationFileLocation())
			&& !file_exists($configurationManager->getLocalconfFileLocation())
		) {
			require_once __DIR__ . '/../Utility/HttpUtility.php';
			Utility\HttpUtility::redirect($pathUpToDocumentRoot . 'typo3/install/index.php?mode=123&step=1&password=joh316');
		}
		return $this;
	}

	/**
	 * Includes LocalConfiguration.php and sets several
	 * global settings depending on configuration.
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function loadConfigurationAndInitialize() {
		$this->getInstance()
			->populateLocalConfiguration()
			->registerExtDirectComponents()
			->initializeCachingFramework()
			->registerAutoloader()
			->checkUtf8DatabaseSettingsOrDie()
			->transferDeprecatedCurlSettings()
			->setCacheHashOptions()
			->enforceCorrectProxyAuthScheme()
			->setDefaultTimezone()
			->initializeL10nLocales()
			->configureImageProcessingOptions()
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
	 * Load TYPO3_LOADED_EXT and ext_localconf
	 *
	 * @param boolean $allowCaching
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function loadTypo3LoadedExtAndExtLocalconf($allowCaching = TRUE) {
		$this->getInstance()
			->populateTypo3LoadedExtGlobal($allowCaching)
			->loadAdditionalConfigurationFromExtensions($allowCaching);
		return $this;
	}

	/**
	 * Load TYPO3_LOADED_EXT, recreate class loader registry and load ext_localconf
	 *
	 * @param boolean $allowCaching
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function reloadTypo3LoadedExtAndClassLoaderAndExtLocalconf() {
		$bootstrap = $this->getInstance();
		$bootstrap->populateTypo3LoadedExtGlobal(FALSE);
		\TYPO3\CMS\Core\Core\ClassLoader::loadClassLoaderCache();
		$bootstrap->loadAdditionalConfigurationFromExtensions(FALSE);
		return $this;
	}

	/**
	 * Sets up additional configuration applied in all scopes
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function applyAdditionalConfigurationSettings() {
		$this->getInstance()
			->deprecationLogForOldExtCacheSetting()
			->initializeExceptionHandling()
			->setFinalCachingFrameworkCacheConfiguration()
			->defineLoggingAndExceptionConstants()
			->unsetReservedGlobalVariables();
		return $this;
	}

	/**
	 * Throws an exception if no browser could be identified
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
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
	 * Populate the local configuration.
	 * Merge default TYPO3_CONF_VARS with content of typo3conf/LocalConfiguration.php,
	 * execute typo3conf/AdditionalConfiguration.php, define database related constants.
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 */
	protected function populateLocalConfiguration() {
		try {
			Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager')
				->exportConfiguration();
		} catch (\Exception $e) {
			die($e->getMessage());
		}
		define('TYPO3_db', $GLOBALS['TYPO3_CONF_VARS']['DB']['database']);
		define('TYPO3_db_username', $GLOBALS['TYPO3_CONF_VARS']['DB']['username']);
		define('TYPO3_db_password', $GLOBALS['TYPO3_CONF_VARS']['DB']['password']);
		define('TYPO3_db_host', $GLOBALS['TYPO3_CONF_VARS']['DB']['host']);
		define('TYPO3_extTableDef_script',
			isset($GLOBALS['TYPO3_CONF_VARS']['DB']['extTablesDefinitionScript'])
			? $GLOBALS['TYPO3_CONF_VARS']['DB']['extTablesDefinitionScript']
			: 'extTables.php');
		define('TYPO3_user_agent', 'User-Agent: ' . $GLOBALS['TYPO3_CONF_VARS']['HTTP']['userAgent']);
		return $this;
	}

	/**
	 * Register default ExtDirect components
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 */
	protected function registerExtDirectComponents() {
		if (TYPO3_MODE === 'BE') {
			Utility\ExtensionManagementUtility::registerExtDirectComponent('TYPO3.Components.PageTree.DataProvider', 'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\ExtdirectTreeDataProvider', 'web', 'user,group');
			Utility\ExtensionManagementUtility::registerExtDirectComponent('TYPO3.Components.PageTree.Commands', 'TYPO3\\CMS\\Backend\\Tree\\Pagetree\\ExtdirectTreeCommands', 'web', 'user,group');
			Utility\ExtensionManagementUtility::registerExtDirectComponent('TYPO3.Components.PageTree.ContextMenuDataProvider', 'TYPO3\\CMS\\Backend\\ContextMenu\\Pagetree\\Extdirect\\ContextMenuConfiguration', 'web', 'user,group');
			Utility\ExtensionManagementUtility::registerExtDirectComponent('TYPO3.LiveSearchActions.ExtDirect', 'TYPO3\\CMS\\Backend\\Search\\LiveSearch\\ExtDirect\\LiveSearchDataProvider', 'web_list', 'user,group');
			Utility\ExtensionManagementUtility::registerExtDirectComponent('TYPO3.BackendUserSettings.ExtDirect', 'TYPO3\\CMS\\Backend\\User\\ExtDirect\\BackendUserSettingsDataProvider');
			Utility\ExtensionManagementUtility::registerExtDirectComponent('TYPO3.CSH.ExtDirect', 'TYPO3\\CMS\\ContextHelp\\ExtDirect\\ContextHelpDataProvider');
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
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 */
	protected function initializeCachingFramework() {
		\TYPO3\CMS\Core\Cache\Cache::initializeCachingFramework();
		return $this;
	}

	/**
	 * Register autoloader
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 */
	protected function registerAutoloader() {
		if (PHP_VERSION_ID < 50307) {
			\TYPO3\CMS\Core\Compatibility\CompatbilityClassLoaderPhpBelow50307::registerAutoloader();
		} else {
			\TYPO3\CMS\Core\Core\ClassLoader::registerAutoloader();
		}
		return $this;
	}

	/**
	 * Checking for UTF-8 in the settings since TYPO3 4.5
	 *
	 * Since TYPO3 4.5, everything other than UTF-8 is deprecated.
	 *
	 * [BE][forceCharset] is set to the charset that TYPO3 is using
	 * [SYS][setDBinit] is used to set the DB connection
	 * and both settings need to be adjusted for UTF-8 in order to work properly
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 */
	protected function checkUtf8DatabaseSettingsOrDie() {
		// Check if [BE][forceCharset] has been set in localconf.php
		if (isset($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'])) {
			// die() unless we're already on UTF-8
			if ($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] != 'utf-8' &&
				$GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] &&
				TYPO3_enterInstallScript !== '1') {

				die('This installation was just upgraded to a new TYPO3 version. Since TYPO3 4.7, utf-8 is always enforced.<br />' .
					'The configuration option $GLOBALS[\'TYPO3_CONF_VARS\'][BE][forceCharset] was marked as deprecated in TYPO3 4.5 and is now ignored.<br />' .
					'You have configured the value to something different, which is not supported anymore.<br />' .
					'Please proceed to the Update Wizard in the TYPO3 Install Tool to update your configuration.'
				);
			} else {
				unset($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']);
			}
		}

		if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit']) &&
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit'] !== '-1' &&
			preg_match('/SET NAMES [\'"]?utf8[\'"]?/i', $GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit']) === FALSE &&
			TYPO3_enterInstallScript !== '1') {

			// Only accept "SET NAMES utf8" for this setting, otherwise die with a nice error
			die('This TYPO3 installation is using the $GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'setDBinit\'] property with the following value:' . chr(10) .
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit'] . chr(10) . chr(10) .
				'It looks like UTF-8 is not used for this connection.' . chr(10) . chr(10) .
				'Everything other than UTF-8 is unsupported since TYPO3 4.7.' . chr(10) .
				'The DB, its connection and TYPO3 should be migrated to UTF-8 therefore. Please check your setup.'
			);
		} else {
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit'] = 'SET NAMES utf8;';
		}
		return $this;
	}

	/**
	 * Parse old curl options and set new http ones instead
	 *
	 * @TODO : This code segment must still be finished
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 */
	protected function transferDeprecatedCurlSettings() {
		if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer'])) {
			$proxyParts = explode(':', $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer'], 2);
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
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
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
	 * $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_auth_scheme'] must be either
	 * 'digest' or 'basic' with fallback to 'basic'
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 */
	protected function enforceCorrectProxyAuthScheme() {
		$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_auth_scheme'] === 'digest' ?: ($GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_auth_scheme'] = 'basic');
		return $this;
	}

	/**
	 * Set default timezone
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
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
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 */
	protected function initializeL10nLocales() {
		\TYPO3\CMS\Core\Localization\Locales::initialize();
		return $this;
	}

	/**
	 * Based on the configuration of the image processing some options are forced
	 * to simplify configuration settings and combinations
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 */
	protected function configureImageProcessingOptions() {
		if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['image_processing']) {
			$GLOBALS['TYPO3_CONF_VARS']['GFX']['im'] = 0;
			$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'] = 0;
		}
		if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['im']) {
			$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path'] = '';
			$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw'] = '';
			$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] = 'gif,jpg,jpeg,png';
			$GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] = 0;
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5']) {
			$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_negate_mask'] = 1;
			$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_no_effects'] = 1;
			$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_mask_temp_ext_gif'] = 1;
			if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5'] === 'gm') {
				$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_negate_mask'] = 0;
				$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_imvMaskState'] = 0;
				$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_no_effects'] = 1;
				$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_v5effects'] = -1;
			}
		}
		if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_imvMaskState']) {
			$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_negate_mask'] = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_negate_mask'] ? 0 : 1;
		}
		return $this;
	}

	/**
	 * Convert type of "pageNotFound_handling" setting in case it was written as a
	 * string (e.g. if edited in Install Tool)
	 *
	 * @TODO : Remove, if the Install Tool handles such data types correctly
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
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
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
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
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 */
	protected function configureExceptionHandling() {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionHandler'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['productionExceptionHandler'];
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionalErrors'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors'];
		// Turn error logging on/off.
		if (($displayErrors = intval($GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'])) != '-1') {
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
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 */
	protected function setMemoryLimit() {
		if (intval($GLOBALS['TYPO3_CONF_VARS']['SYS']['setMemoryLimit']) > 16) {
			@ini_set('memory_limit', (intval($GLOBALS['TYPO3_CONF_VARS']['SYS']['setMemoryLimit']) . 'm'));
		}
		return $this;
	}

	/**
	 * Define TYPO3_REQUESTTYPE* constants
	 * so devs exactly know what type of request it is
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
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
	 * Set up $GLOBALS['TYPO3_LOADED_EXT'] array with basic information
	 * about extensions.
	 *
	 * @param boolean $allowCaching
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 */
	protected function populateTypo3LoadedExtGlobal($allowCaching = TRUE) {
		$GLOBALS['TYPO3_LOADED_EXT'] = Utility\ExtensionManagementUtility::loadTypo3LoadedExtensionInformation($allowCaching);
		return $this;
	}

	/**
	 * Load extension configuration files (ext_localconf.php)
	 *
	 * The ext_localconf.php files in extensions are meant to make changes
	 * to the global $TYPO3_CONF_VARS configuration array.
	 *
	 * @param boolean $allowCaching
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 */
	protected function loadAdditionalConfigurationFromExtensions($allowCaching = TRUE) {
		Utility\ExtensionManagementUtility::loadExtLocalconf($allowCaching);
		return $this;
	}

	/**
	 * Write deprecation log if deprecated extCache setting was set in the instance.
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @deprecated since 6.0, the check will be removed two version later.
	 */
	protected function deprecationLogForOldExtCacheSetting() {
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['extCache']) && $GLOBALS['TYPO3_CONF_VARS']['SYS']['extCache'] !== -1) {
			Utility\GeneralUtility::deprecationLog('Setting $GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'extCache\'] is unused and can be removed from localconf.php');
		}
		return $this;
	}

	/**
	 * Initialize exception handling
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 */
	protected function initializeExceptionHandling() {
		if ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionHandler'] !== '') {
			if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandler'] !== '') {
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
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 */
	protected function setFinalCachingFrameworkCacheConfiguration() {
		$GLOBALS['typo3CacheManager']->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
		return $this;
	}

	/**
	 * Define logging and exception constants
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
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
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 */
	protected function unsetReservedGlobalVariables() {
		unset($GLOBALS['PAGES_TYPES']);
		unset($GLOBALS['TCA']);
		unset($GLOBALS['TBE_MODULES']);
		unset($GLOBALS['TBE_STYLES']);
		unset($GLOBALS['FILEICONS']);
		// Those set in init.php:
		unset($GLOBALS['WEBMOUNTS']);
		unset($GLOBALS['FILEMOUNTS']);
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
	 * @param boolean $connect Whether db should be connected
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

		return $this;
	}

	/**
	 * Check adminOnly configuration variable and redirects
	 * to an URL in file typo3conf/LOCK_BACKEND or exit the script
	 *
	 * @throws \RuntimeException
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function checkLockedBackendAndRedirectOrDie() {
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly'] < 0) {
			throw new \RuntimeException('TYPO3 Backend locked: Backend and Install Tool are locked for maintenance. [BE][adminOnly] is set to "' . intval($GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly']) . '".', 1294586847);
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
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function checkBackendIpOrDie() {
		if (trim($GLOBALS['TYPO3_CONF_VARS']['BE']['IPmaskList'])) {
			if (!Utility\GeneralUtility::cmpIP(Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['BE']['IPmaskList'])) {
				// Send Not Found header - if the webserver can make use of it
				header('Status: 404 Not Found');
				// Just point us away from here...
				header('Location: http://');
				// ... and exit good!
				die;
			}
		}
		return $this;
	}

	/**
	 * Check lockSSL configuration variable and redirect
	 * to https version of the backend if needed
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function checkSslBackendAndRedirectIfNeeded() {
		if (intval($GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'])) {
			if (intval($GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSLPort'])) {
				$sslPortSuffix = ':' . intval($GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSLPort']);
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
				if (intval($GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL']) === 2) {
					list(, $url) = explode('://', Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir, 2);
					list($server, $address) = explode('/', $url, 2);
					header('Location: https://' . $server . $sslPortSuffix . '/' . $address);
				} else {
					// Send Not Found header - if the webserver can make use of it...
					header('Status: 404 Not Found');
					// Just point us away from here...
					header('Location: http://');
				}
				// ... and exit good!
				die;
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
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function loadCachedTca() {
		$cacheIdentifier = 'tca_fe_' . sha1((TYPO3_version . PATH_site . 'tca_fe'));
		/** @var $codeCache \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend */
		$codeCache = $GLOBALS['typo3CacheManager']->getCache('cache_core');
		if ($codeCache->has($cacheIdentifier)) {
			$codeCache->requireOnce($cacheIdentifier);
		} else {
			$this->loadExtensionTables(TRUE);
			$phpCodeToCache = '$GLOBALS[\'TCA\'] = ';
			$phpCodeToCache .= Utility\ArrayUtility::arrayExport($GLOBALS['TCA']);
			$phpCodeToCache .= ';';
			$codeCache->set($cacheIdentifier, $phpCodeToCache);
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
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
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

		// Apply TCA onto tables to be categorized
		\TYPO3\CMS\Core\Category\CategoryRegistry::getInstance()->applyTca();
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
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function initializeSpriteManager() {
		\TYPO3\CMS\Backend\Sprite\SpriteManager::initialize();
		return $this;
	}

	/**
	 * Initialize backend user object in globals
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function initializeBackendUser() {
		/** @var $backendUser \TYPO3\CMS\Core\Authentication\BackendUserAuthentication */
		$backendUser = Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$backendUser->warningEmail = $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'];
		$backendUser->lockIP = $GLOBALS['TYPO3_CONF_VARS']['BE']['lockIP'];
		$backendUser->auth_timeout_field = intval($GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout']);
		$backendUser->OS = TYPO3_OS;
		if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) {
			$backendUser->dontSetCookie = TRUE;
		}
		// The global must be available very early, because methods below
		// might trigger code which relies on it. See: #45625
		$GLOBALS['BE_USER'] = $backendUser;
		$backendUser->start();
		$backendUser->checkCLIuser();
		$backendUser->backendCheckLogin();
		return $this;
	}

	/**
	 * Initialize backend user mount points
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function initializeBackendUserMounts() {
		// Includes deleted mount pages as well! @TODO: Figure out why ...
		$GLOBALS['WEBMOUNTS'] = $GLOBALS['BE_USER']->returnWebmounts();
		$GLOBALS['BE_USER']->getFileStorages();
		$GLOBALS['FILEMOUNTS'] = $GLOBALS['BE_USER']->groupData['filemounts'];
		return $this;
	}

	/**
	 * Initialize language object
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
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
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function endOutputBufferingAndCleanPreviousOutput() {
		ob_clean();
		return $this;
	}

	/**
	 * Initialize output compression if configured
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
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
	 * Initialize module menu object
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function initializeModuleMenuObject() {
		/** @var $moduleMenuUtility \TYPO3\CMS\Backend\Module\ModuleController */
		$moduleMenuUtility = Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Module\\ModuleController');
		$moduleMenuUtility->createModuleMenu();
		return $this;
	}

	/**
	 * Things that should be performed to shut down the framework.
	 * This method is called in all important scripts for a clean
	 * shut down of the system.
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function shutdown() {
		if (PHP_VERSION_ID < 50307) {
			\TYPO3\CMS\Core\Compatibility\CompatbilityClassLoaderPhpBelow50307::unregisterAutoloader();
		} else {
			\TYPO3\CMS\Core\Core\ClassLoader::unregisterAutoloader();
		}
		return $this;
	}

	/**
	 * Provides an instance of "template" for backend-modules to
	 * work with.
	 *
	 * @return \TYPO3\CMS\Core\Core\Bootstrap
	 * @internal This is not a public API method, do not use in own extensions
	 */
	public function initializeBackendTemplate() {
		$GLOBALS['TBE_TEMPLATE'] = Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		return $this;
	}

}


?>
