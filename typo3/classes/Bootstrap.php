<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Christian Kuhn <lolli@schwarzbu.ch>
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

require('Bootstrap' . DIRECTORY_SEPARATOR . 'BaseSetup.php');

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
 * @package TYPO3
 * @subpackage core
 */
class Typo3_Bootstrap {
	/**
	 * @var Typo3_Bootstrap
	 */
	protected static $instance = NULL;

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
	 * @return Typo3_Bootstrap
	 */
	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new Typo3_Bootstrap();
		}
		return self::$instance;
	}

	/**
	 * Gets the request's unique ID
	 *
	 * @return string Unique request ID
	 */
	public function getRequestId() {
		return $this->requestId;
	}

	/**
	 * Prevent any unwanted output that may corrupt AJAX/compression.
	 * This does not interfeer with "die()" or "echo"+"exit()" messages!
	 *
	 * @return Typo3_Bootstrap
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
	 * @return Typo3_Bootstrap
	 */
	public function baseSetup($relativePathPart = '') {
		Typo3_Bootstrap_BaseSetup::run($relativePathPart);

		return $this;
	}

	/**
	 * Throws an exception if no browser could be identified
	 *
	 * @return Typo3_Bootstrap
	 * @throws RuntimeException
	 */
	public function checkValidBrowserOrDie() {
			// Checks for proper browser
		if (empty($GLOBALS['CLIENT']['BROWSER'])) {
			throw new RuntimeException('Browser Error: Your browser version looks incompatible with this TYPO3 version!', 1294587023);
		}

		return $this;
	}

	/**
	 * Register default ExtDirect components
	 *
	 * @return Typo3_Bootstrap
	 */
	public function registerExtDirectComponents() {
		if (TYPO3_MODE === 'BE') {
			t3lib_extMgm::registerExtDirectComponent(
				'TYPO3.Components.PageTree.DataProvider',
				PATH_t3lib . 'tree/pagetree/extdirect/class.t3lib_tree_pagetree_extdirect_tree.php:t3lib_tree_pagetree_extdirect_Tree',
				'web',
				'user,group'
			);

			t3lib_extMgm::registerExtDirectComponent(
				'TYPO3.Components.PageTree.Commands',
				PATH_t3lib . 'tree/pagetree/extdirect/class.t3lib_tree_pagetree_extdirect_tree.php:t3lib_tree_pagetree_extdirect_Commands',
				'web',
				'user,group'
			);

			t3lib_extMgm::registerExtDirectComponent(
				'TYPO3.Components.PageTree.ContextMenuDataProvider',
				PATH_t3lib . 'contextmenu/pagetree/extdirect/class.t3lib_contextmenu_pagetree_extdirect_contextmenu.php:t3lib_contextmenu_pagetree_extdirect_ContextMenu',
				'web',
				'user,group'
			);

			t3lib_extMgm::registerExtDirectComponent(
				'TYPO3.LiveSearchActions.ExtDirect',
				PATH_t3lib . 'extjs/dataprovider/class.extdirect_dataprovider_backendlivesearch.php:extDirect_DataProvider_BackendLiveSearch',
				'web_list',
				'user,group'
			);

			t3lib_extMgm::registerExtDirectComponent(
				'TYPO3.BackendUserSettings.ExtDirect',
				PATH_t3lib . 'extjs/dataprovider/class.extdirect_dataprovider_beusersettings.php:extDirect_DataProvider_BackendUserSettings'
			);

			t3lib_extMgm::registerExtDirectComponent(
				'TYPO3.CSH.ExtDirect',
				PATH_t3lib . 'extjs/dataprovider/class.extdirect_dataprovider_contexthelp.php:extDirect_DataProvider_ContextHelp'
			);

			t3lib_extMgm::registerExtDirectComponent(
				'TYPO3.ExtDirectStateProvider.ExtDirect',
				PATH_t3lib . 'extjs/dataprovider/class.extdirect_dataprovider_state.php:extDirect_DataProvider_State'
			);
		}

		return $this;
	}

	/**
	 * Check typo3conf/localconf.php exists
	 *
	 * @throws RuntimeException
	 * @return Typo3_Bootstrap
	 */
	public function checkLocalconfExistsOrDie() {
		if (!@is_file(PATH_typo3conf . 'localconf.php')) {
			throw new RuntimeException('localconf.php is not found!', 1333754332);
		}

		return $this;
	}

	/**
	 * Set global database variables to empty string.
	 * Database-variables are cleared!
	 *
	 * @TODO: Figure out why we do this (security reasons with register globals?)
	 * @return Typo3_Bootstrap
	 */
	public function setGlobalDatabaseVariablesToEmptyString() {
			// The database name
		$GLOBALS['typo_db'] = '';
			// The database username
		$GLOBALS['typo_db_username'] = '';
			// The database password
		$GLOBALS['typo_db_password'] = '';
			// The database host
		$GLOBALS['typo_db_host'] = '';
			// The filename of an additional script in typo3conf/-folder which is included after
			// tables.php. Code in this script should modify the tables.php-configuration only,
			// and this provides a good way to extend the standard-distributed tables.php file.
		$GLOBALS['typo_db_extTableDef_script'] = '';

		return $this;
	}

	/**
	 * Loads the main configuration file (localconf.php)
	 *
	 * @return Typo3_Bootstrap
	 */
	public function loadMainConfigurationFile() {
		global $TYPO3_CONF_VARS, $typo_db, $typo_db_username, $typo_db_password, $typo_db_host, $typo_db_extTableDef_script;
		require(PATH_typo3conf . 'localconf.php');

		return $this;
	}

	/**
	 * Define the database setup as constants
	 * and unset no longer needed global variables
	 *
	 * @return Typo3_Bootstrap
	 */
	public function defineTypo3DatabaseConstants() {
		define('TYPO3_db', $GLOBALS['typo_db']);
		define('TYPO3_db_username', $GLOBALS['typo_db_username']);
		define('TYPO3_db_password', $GLOBALS['typo_db_password']);
		define('TYPO3_db_host', $GLOBALS['typo_db_host']);
		define('TYPO3_extTableDef_script', $GLOBALS['typo_db_extTableDef_script']);
		define('TYPO3_user_agent', 'User-Agent: '. $GLOBALS['TYPO3_CONF_VARS']['HTTP']['userAgent']);

		unset($GLOBALS['typo_db']);
		unset($GLOBALS['typo_db_username']);
		unset($GLOBALS['typo_db_password']);
		unset($GLOBALS['typo_db_host']);
		unset($GLOBALS['typo_db_extTableDef_script']);

		return $this;
	}

	/**
	 * Redirect to install tool if database host and database are not defined
	 *
	 * @return Typo3_Bootstrap
	 */
	public function redirectToInstallToolIfDatabaseCredentialsAreMissing() {
		if (!TYPO3_db_host && !TYPO3_db) {
			t3lib_utility_Http::redirect('install/index.php?mode=123&step=1&password=joh316');
		}

		return $this;
	}

	/**
	 * Initialize caching framework
	 *
	 * @return Typo3_Bootstrap
	 */
	public function initializeCachingFramework() {
		t3lib_cache::initializeCachingFramework();

		return $this;
	}

	/**
	 * Register autoloader
	 *
	 * @return Typo3_Bootstrap
	 */
	public function registerAutoloader() {
		t3lib_autoloader::registerAutoloader();

		return $this;
	}

	/**
	 * Checking for UTF-8 in the settings since TYPO3 4.5
	 *
	 * Since TYPO3 4.5, everything other than UTF-8 is deprecated.
	 *
	 *   [BE][forceCharset] is set to the charset that TYPO3 is using
	 *   [SYS][setDBinit] is used to set the DB connection
	 * and both settings need to be adjusted for UTF-8 in order to work properly
	 *
	 * @return Typo3_Bootstrap
	 */
	public function checkUtf8DatabaseSettingsOrDie() {
			// Check if [BE][forceCharset] has been set in localconf.php
		if (isset($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'])) {
				// die() unless we're already on UTF-8
			if ($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] != 'utf-8' &&
				$GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] &&
				TYPO3_enterInstallScript !== '1'
			) {
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
			preg_match('/SET NAMES utf8/', $GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit']) === FALSE &&
			TYPO3_enterInstallScript !== '1'
		) {
				// Only accept "SET NAMES utf8" for this setting, otherwise die with a nice error
			die('This TYPO3 installation is using the $GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'setDBinit\'] property with the following value:' . chr(10) .
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit'] . chr(10) . chr(10) .
				'It looks like UTF-8 is not used for this connection.' . chr(10) . chr(10) .
				'Everything other than UTF-8 is unsupported since TYPO3 4.7.' . chr(10) .
				'The DB, its connection and TYPO3 should be migrated to UTF-8 therefore. Please check your setup.');
		} else {
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['setDBinit'] = 'SET NAMES utf8;';
		}

		return $this;
	}

	/**
	 * Parse old curl options and set new http ones instead
	 *
	 * @TODO: This code segment must still be finished
	 * @return Typo3_Bootstrap
	 */
	public function transferDeprecatedCurlSettings() {
		if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer'])) {
			$proxyParts = explode(':', $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer'], 2);
			$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_host'] = $proxyParts[0];
			$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_port'] = $proxyParts[1];
			/* TODO: uncomment after refactoring getUrl()
			t3lib_div::deprecationLog(
				'This TYPO3 installation is using the $GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'curlProxyServer\'] property with the following value: ' .
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyServer'] . LF . 'Please make sure to set $GLOBALS[\'TYPO3_CONF_VARS\'][\'HTTP\'][\'proxy_host\']' .
				' and $GLOBALS['TYPO3_CONF_VARS'][\'HTTP\'][\'proxy_port\'] instead.' . LF . 'Remove this line from your localconf.php.'
			);*/
		}
		if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass'])) {
			$userPassParts = explode(':', $GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass'], 2);
			$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_user'] = $userPassParts[0];
			$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_password'] = $userPassParts[1];
			/* TODO: uncomment after refactoring getUrl()
			t3lib_div::deprecationLog(
				'This TYPO3 installation is using the $GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'curlProxyUserPass\'] property with the following value: ' .
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['curlProxyUserPass'] . LF . 'Please make sure to set $GLOBALS[\'TYPO3_CONF_VARS\'][\'HTTP\'][\'proxy_user\']' .
				' and $GLOBALS['TYPO3_CONF_VARS'][\'HTTP\'][\'proxy_password\'] instead.' . LF . 'Remove this line from your localconf.php.'
			);*/
		}

		return $this;
	}

	/**
	 * Set cacheHash options
	 *
	 * @return Typo3_Bootstrap
	 */
	public function setCacheHashOptions() {
		$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash'] = array(
			'cachedParametersWhiteList' => t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['cHashOnlyForParameters'], TRUE),
			'excludedParameters' => t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['cHashExcludedParameters'], TRUE),
			'requireCacheHashPresenceParameters' => t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['cHashRequiredParameters'], TRUE),
		);
		if (trim($GLOBALS['TYPO3_CONF_VARS']['FE']['cHashExcludedParametersIfEmpty']) === '*') {
			$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludeAllEmptyParameters'] = TRUE;
		} else {
			$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParametersIfEmpty'] = t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['cHashExcludedParametersIfEmpty'], TRUE);
		}

		return $this;
	}

	/**
	 * $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_auth_scheme'] must be either
	 * 'digest' or 'basic' with fallback to 'basic'
	 *
	 * @return Typo3_Bootstrap
	 */
	public function enforceCorrectProxyAuthScheme() {
		$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_auth_scheme'] === 'digest' ?
			: $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_auth_scheme'] = 'basic';

		return $this;
	}

	/**
	 * Set default timezone
	 *
	 * @return Typo3_Bootstrap
	 */
	public function setDefaultTimezone() {
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
	 * @return Typo3_Bootstrap
	 */
	public function initializeL10nLocales() {
		t3lib_l10n_Locales::initialize();

		return $this;
	}

	/**
	 * Based on the configuration of the image processing some options are forced
	 * to simplify configuration settings and combinations
	 *
	 * @return Typo3_Bootstrap
	 */
	public function configureImageProcessingOptions() {
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
			if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5']==='gm') {
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
	 * @TODO: Remove, if the Install Tool handles such data types correctly
	 * @return Typo3_Bootstrap
	 */
	public function convertPageNotFoundHandlingToBoolean() {
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
	 * @return Typo3_Bootstrap
	 */
	public function registerGlobalDebugFunctions() {
			// Simple debug function which prints output immediately
		function xdebug($var = '', $debugTitle = 'xdebug') {
				// If you wish to use the debug()-function, and it does not output something,
				// please edit the IP mask in TYPO3_CONF_VARS
			if (!t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'])) {
				return;
			}
			t3lib_utility_Debug::debug($var, $debugTitle);
		}

			// Debug function which calls $GLOBALS['error'] error handler if available
		function debug($variable = '', $name = '*variable*', $line = '*line*', $file = '*file*', $recursiveDepth = 3, $debugLevel = E_DEBUG) {
				// If you wish to use the debug()-function, and it does not output something,
				// please edit the IP mask in TYPO3_CONF_VARS
			if (!t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'])) {
				return;
			}
			if (is_object($GLOBALS['error']) && @is_callable(array($GLOBALS['error'], 'debug'))) {
				$GLOBALS['error']->debug($variable, $name, $line, $file, $recursiveDepth, $debugLevel);
			} else {
				$title = ($name === '*variable*') ? '' : $name;
				$group = ($line === '*line*') ? NULL : $line;
				t3lib_utility_Debug::debug($variable, $title, $group);
			}
		}

		function debugBegin() {
			if (is_object($GLOBALS['error']) && @is_callable(array($GLOBALS['error'],'debugBegin'))) {
				$GLOBALS['error']->debugBegin();
			}
		}

		function debugEnd() {
			if (is_object($GLOBALS['error']) && @is_callable(array($GLOBALS['error'],'debugEnd'))) {
				$GLOBALS['error']->debugEnd();
			}
		}

		return $this;
	}

	/**
	 * Mail sending via Swift Mailer
	 *
	 * @return Typo3_Bootstrap
	 */
	public function registerSwiftMailer() {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/utility/class.t3lib_utility_mail.php']['substituteMailDelivery'][]
			= 't3lib_mail_SwiftMailerAdapter';

		return $this;
	}

	/**
	 * Configure and set up exception and error handling
	 *
	 * @return Typo3_Bootstrap
	 */
	public function configureExceptionHandling() {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionHandler'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['productionExceptionHandler'];
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionalErrors'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors'];

			// Turn error logging on/off.
		if (($displayErrors = intval($GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'])) != '-1') {
				// Special value "2" enables this feature only if $GLOBALS['TYPO3_CONF_VARS'][SYS][devIPmask] matches
			if ($displayErrors == 2) {
				if (t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'])) {
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
		} elseif (t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'])) {
				// With displayErrors = -1 (default), turn on debugging if devIPmask matches:
			$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionHandler'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['debugExceptionHandler'];
		}

		return $this;
	}

	/**
	 * Set PHP memory limit depending on value of
	 * $GLOBALS['TYPO3_CONF_VARS']['SYS']['setMemoryLimit']
	 *
	 * @return Typo3_Bootstrap
	 */
	public function setMemoryLimit() {
		if (intval($GLOBALS['TYPO3_CONF_VARS']['SYS']['setMemoryLimit']) > 16) {
			@ini_set('memory_limit', intval($GLOBALS['TYPO3_CONF_VARS']['SYS']['setMemoryLimit']) . 'm');
		}

		return $this;
	}

	/**
	 * Define TYPO3_REQUESTTYPE* constants
	 * so devs exactly know what type of request it is
	 *
	 * @return Typo3_Bootstrap
	 */
	public function defineTypo3RequestTypes() {
		define('TYPO3_REQUESTTYPE_FE', 1);
		define('TYPO3_REQUESTTYPE_BE', 2);
		define('TYPO3_REQUESTTYPE_CLI', 4);
		define('TYPO3_REQUESTTYPE_AJAX', 8);
		define('TYPO3_REQUESTTYPE_INSTALL', 16);
		define('TYPO3_REQUESTTYPE',
			(TYPO3_MODE == 'FE' ? TYPO3_REQUESTTYPE_FE : 0) |
			(TYPO3_MODE == 'BE' ? TYPO3_REQUESTTYPE_BE : 0) |
			((defined('TYPO3_cliMode') && TYPO3_cliMode) ? TYPO3_REQUESTTYPE_CLI : 0) |
			((defined('TYPO3_enterInstallScript') && TYPO3_enterInstallScript) ? TYPO3_REQUESTTYPE_INSTALL : 0) |
			($GLOBALS['TYPO3_AJAX'] ? TYPO3_REQUESTTYPE_AJAX : 0)
		);

		return $this;
	}

	/**
	 * Set up $GLOBALS['TYPO3_LOADED_EXT'] array with basic information
	 * about extensions.
	 *
	 * @param boolean $allowCaching
	 * @return Typo3_Bootstrap
	 */
	public function populateTypo3LoadedExtGlobal($allowCaching = TRUE) {
		$GLOBALS['TYPO3_LOADED_EXT'] = t3lib_extMgm::loadTypo3LoadedExtensionInformation($allowCaching);

		return $this;
	}

	/**
	 * Load extension configuration files (ext_localconf.php)
	 *
	 * The ext_localconf.php files in extensions are meant to make changes
	 * to the global $TYPO3_CONF_VARS configuration array.
	 *
	 * @param boolean $allowCaching
	 * @return Typo3_Bootstrap
	 */
	public function loadAdditionalConfigurationFromExtensions($allowCaching = TRUE) {
		t3lib_extMgm::loadExtLocalconf($allowCaching);

		return $this;
	}

	/**
	 * Write deprecation log if the TYPO3 instance uses deprecated XCLASS
	 * registrations via $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']
	 *
	 * @return Typo3_Bootstrap
	 */
	public function deprecationLogForOldXclassRegistration() {
		if (count($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']) > 0) {
			t3lib_div::deprecationLog(
				'This installation runs with extensions that use XCLASSing by setting the XCLASS path in ext_localconf.php. ' .
				'This is deprecated and will be removed in TYPO3 6.2 and later. It is preferred to define XCLASSes in ' .
				'ext_autoload.php instead. See http://wiki.typo3.org/Autoload for more information.'
			);
		}

		return $this;
	}

	/**
	 * Write deprecation log if deprecated extCache setting was set in the instance.
	 *
	 * @return Typo3_Bootstrap
	 * @deprecated since 6.0, the check will be removed two version later.
	 */
	public function deprecationLogForOldExtCacheSetting() {
		if (
			isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['extCache'])
			&& $GLOBALS['TYPO3_CONF_VARS']['SYS']['extCache'] !== -1
		) {
			t3lib_div::deprecationLog(
				'Setting $GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'extCache\'] is unused and can be removed from localconf.php'
			);
		}

		return $this;
	}

	/**
	 * Initialize exception handling
	 *
	 * @return Typo3_Bootstrap
	 */
	public function initializeExceptionHandling() {
		if ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionHandler'] !== '') {
			if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandler'] !== '') {
					// Register an error handler for the given errorHandlerErrors
				$errorHandler = t3lib_div::makeInstance(
					$GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandler'],
					$GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandlerErrors']
				);
					// Set errors which will be converted in an exception
				$errorHandler->setExceptionalErrors($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionalErrors']);
			}
				// Instantiate the exception handler once to make sure object is registered
				// @TODO: Figure out if this is really needed
			t3lib_div::makeInstance($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['errors']['exceptionHandler']);
		}

		return $this;
	}

	/**
	 * Load some additional classes that are encapsulated in extensions
	 *
	 * @return Typo3_Bootstrap
	 */
	public function requireAdditionalExtensionFiles() {
		require_once(t3lib_extMgm::extPath('lang') . 'lang.php');

		return $this;
	}

	/**
	 * Extensions may register new caches, so we set the
	 * global cache array to the manager again at this point
	 *
	 * @return Typo3_Bootstrap
	 */
	public function setFinalCachingFrameworkCacheConfiguration() {
		$GLOBALS['typo3CacheManager']->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);

		return $this;
	}

	/**
	 * Define logging and exception constants
	 *
	 * @return Typo3_Bootstrap
	 */
	public function defineLoggingAndExceptionConstants() {
		define('TYPO3_DLOG', $GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_DLOG']);
		define('TYPO3_ERROR_DLOG', $GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_errorDLOG']);
		define('TYPO3_EXCEPTION_DLOG', $GLOBALS['TYPO3_CONF_VARS']['SYS']['enable_exceptionDLOG']);

		return $this;
	}

	/**
	 * Unsetting reserved global variables:
	 * Those which are/can be set in "stddb/tables.php" files:
	 *
	 * @return Typo3_Bootstrap
	 */
	public function unsetReservedGlobalVariables() {
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
	 * Initialize t3lib_db in $GLOBALS and connect if requested
	 *
	 * @param boolean $connect Whether or not the db should be connected already
	 * @return Typo3_Bootstrap
	 */
	public function initializeTypo3DbGlobal($connect = TRUE) {
			/** @var TYPO3_DB t3lib_db */
		$GLOBALS['TYPO3_DB'] = t3lib_div::makeInstance('t3lib_DB');
		$GLOBALS['TYPO3_DB']->debugOutput = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sqlDebug'];
		if ($connect) {
			$this->establishDatabaseConnection();
		}

		return $this;
	}

	/**
	 * Check adminOnly configuration variable and redirects
	 * to an URL in file typo3conf/LOCK_BACKEND or exit the script
	 *
	 * @throws RuntimeException
	 * @return Typo3_Bootstrap
	 */
	public function checkLockedBackendAndRedirectOrDie() {
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly'] < 0) {
			throw new RuntimeException(
				'TYPO3 Backend locked: Backend and Install Tool are locked for maintenance. [BE][adminOnly] is set to "' . intval($GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly']) . '".',
				1294586847
			);
		}
		if (@is_file(PATH_typo3conf . 'LOCK_BACKEND')) {
			if (TYPO3_PROCEED_IF_NO_USER === 2) {
				// Ajax poll for login, let it pass
			} else {
				$fileContent = t3lib_div::getUrl(PATH_typo3conf . 'LOCK_BACKEND');
				if ($fileContent) {
					header('Location: ' . $fileContent);
				} else {
					throw new RuntimeException(
						'TYPO3 Backend locked: Browser backend is locked for maintenance. Remove lock by removing the file "typo3conf/LOCK_BACKEND" or use CLI-scripts.',
						1294586848
					);
				}
				exit;
			}
		}

		return $this;
	}

	/**
	 * Compare client IP with IPmaskList and exit the script run
	 * if the client is not allowed to access the backend
	 *
	 * @return Typo3_Bootstrap
	 */
	public function checkBackendIpOrDie() {
		if (trim($GLOBALS['TYPO3_CONF_VARS']['BE']['IPmaskList'])) {
			if (!t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $GLOBALS['TYPO3_CONF_VARS']['BE']['IPmaskList'])) {
					// Send Not Found header - if the webserver can make use of it
				header('Status: 404 Not Found');
					// Just point us away from here...
				header('Location: http://');
					// ... and exit good!
				exit;
			}
		}

		return $this;
	}

	/**
	 * Check lockSSL configuration variable and redirect
	 * to https version of the backend if needed
	 *
	 * @return Typo3_Bootstrap
	 */
	public function checkSslBackendAndRedirectIfNeeded() {
		if (intval($GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'])) {
			if(intval($GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSLPort'])) {
				$sslPortSuffix = ':' . intval($GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSLPort']);
			} else {
				$sslPortSuffix = '';
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] == 3) {
				$requestStr = substr(t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT'), strlen(t3lib_div::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir));
				if ($requestStr === 'index.php' && !t3lib_div::getIndpEnv('TYPO3_SSL')) {
					list(, $url) = explode('://', t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'), 2);
					list($server, $address) = explode('/', $url, 2);
					header('Location: https://' . $server . $sslPortSuffix . '/' . $address);
					exit;
				}
			} elseif (!t3lib_div::getIndpEnv('TYPO3_SSL')) {
				if (intval($GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL']) === 2) {
					list(, $url) = explode('://', t3lib_div::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir, 2);
					list($server, $address) = explode('/', $url, 2);
					header('Location: https://' . $server . $sslPortSuffix . '/' . $address);
				} else {
						// Send Not Found header - if the webserver can make use of it...
					header('Status: 404 Not Found');
						// Just point us away from here...
					header('Location: http://');
				}
					// ... and exit good!
				exit;
			}
		}

		return $this;
	}

	/**
	 * Establish connection to the database
	 *
	 * @return Typo3_Bootstrap
	 */
	public function establishDatabaseConnection() {
		$GLOBALS['TYPO3_DB']->connectDB();

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
	 * Note: For backwards compatibility some global variables are
	 * explicitly set as global to be used without $GLOBALS[] in
	 * ext_tables.php. It is discouraged to access variables like
	 * $TBE_MODULES directly in ext_tables.php, but we can not prohibit
	 * this without heavily breaking backwards compatibility.
	 * @TODO: We could write a scheduler / reports module or an update checker
	 * that hints extension authors about discouraged direct variable access.
	 *
	 * Note: include / require are used instead of include_once / require_once on
	 * purpose here: in FE (tslib_fe), this method here can be loaded mulitple times
	 *
	 * @TODO: It should be defined, which global arrays are ok to be manipulated
	 *
	 * @param boolean $allowCaching True, if reading compiled ext_tables file from cache is allowed
	 * @return Typo3_Bootstrap
	 */
	public function loadExtensionTables($allowCaching = TRUE) {
			// It is discouraged to use those global variables directly, but we
			// can not prohibit this without breaking backwards compatibility
		global $T3_SERVICES, $T3_VAR, $TYPO3_CONF_VARS;
		global $TBE_MODULES, $TBE_MODULES_EXT, $TCA;
		global $PAGES_TYPES, $TBE_STYLES, $FILEICONS;

			// Include standard tables.php file
		require(PATH_t3lib . 'stddb/tables.php');

		t3lib_extMgm::loadExtTables($allowCaching);

			// Load additional ext tables script if registered
		if (TYPO3_extTableDef_script) {
			include(PATH_typo3conf . TYPO3_extTableDef_script);
		}

			// Run post hook for additional manipulation
		$this->runExtTablesPostProcessingHooks();

		return $this;
	}

	/**
	 * Check for registered ext tables hooks and run them
	 *
	 * @throws UnexpectedValueException
	 * @return Typo3_Bootstrap
	 */
	protected function runExtTablesPostProcessingHooks() {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing'] as $classReference) {
					/** @var $hookObject t3lib_extTables_PostProcessingHook */
				$hookObject = t3lib_div::getUserObj($classReference);
				if (!$hookObject instanceof t3lib_extTables_PostProcessingHook) {
					throw new UnexpectedValueException('$hookObject must implement interface t3lib_extTables_PostProcessingHook', 1320585902);
				}
				$hookObject->processData();
			}
		}

		return $this;
	}

	/**
	 * Initialize sprite manager global
	 *
	 * @param boolean $allowRegeneration
	 * @return Typo3_Bootstrap
	 */
	public function initializeSpriteManager($allowRegeneration = TRUE) {
			/** @var $spriteManager t3lib_SpriteManager */
		$GLOBALS['spriteManager'] = t3lib_div::makeInstance('t3lib_SpriteManager', $allowRegeneration);
		$GLOBALS['spriteManager']->loadCacheFile();

		return $this;
	}

	/**
	 * Initialize backend user object in globals
	 *
	 * @return Typo3_Bootstrap
	 */
	public function initializeBackendUser() {
			/** @var $backendUser t3lib_beUserAuth */
		$backendUser = t3lib_div::makeInstance('t3lib_beUserAuth');
		$backendUser->warningEmail = $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'];
		$backendUser->lockIP = $GLOBALS['TYPO3_CONF_VARS']['BE']['lockIP'];
		$backendUser->auth_timeout_field = intval($GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout']);
		$backendUser->OS = TYPO3_OS;
		if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI) {
			$backendUser->dontSetCookie = TRUE;
		}
		$backendUser->start();
		$backendUser->checkCLIuser();
		$backendUser->backendCheckLogin();
		$GLOBALS['BE_USER'] = $backendUser;

		return $this;
	}

	/**
	 * Initialize backend user mount points
	 *
	 * @return Typo3_Bootstrap
	 */
	public function initializeBackendUserMounts() {
			// Includes deleted mount pages as well! @TODO: Figure out why ...
		$GLOBALS['WEBMOUNTS'] = $GLOBALS['BE_USER']->returnWebmounts();
		$GLOBALS['FILEMOUNTS'] = $GLOBALS['BE_USER']->returnFilemounts();

		return $this;
	}

	/**
	 * Initialize language object
	 *
	 * @return Typo3_Bootstrap
	 */
	public function initializeLanguageObject() {
			/** @var $GLOBALS['LANG'] language */
		$GLOBALS['LANG'] = t3lib_div::makeInstance('language');
		$GLOBALS['LANG']->init($GLOBALS['BE_USER']->uc['lang']);

		return $this;
	}

	/**
	 * Throw away all output that may have happened during bootstrapping by weird extensions
	 *
	 * @return Typo3_Bootstrap
	 */
	public function endOutputBufferingAndCleanPreviousOutput() {
		ob_clean();

		return $this;
	}

	/**
	 * Initialize output compression if configured
	 *
	 * @return Typo3_Bootstrap
	 */
	public function initializeOutputCompression() {
		if (extension_loaded('zlib') && $GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel']) {
			if (t3lib_utility_Math::canBeInterpretedAsInteger($GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel'])) {
				@ini_set('zlib.output_compression_level', $GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel']);
			}
			ob_start('ob_gzhandler');
		}

		return $this;
	}

	/**
	 * Initialize module menu object
	 *
	 * @return Typo3_Bootstrap
	 */
	public function initializeModuleMenuObject() {
			/** @var $moduleMenuUtility Typo3_BackendModule_Utility */
		$moduleMenuUtility = t3lib_div::makeInstance('Typo3_Utility_BackendModuleUtility');
		$moduleMenuUtility->createModuleMenu();

		return $this;
	}

	/**
	 * Things that should be performed to shut down the framework.
	 * This method is called in all important scripts for a clean
	 * shut down of the system.
	 *
	 * @return Typo3_Bootstrap
	 */
	public function shutdown() {
		t3lib_autoloader::unregisterAutoloader();

		return $this;
	}

	/**
	 * Provides an instance of "template" for backend-modules to
	 * work with.
	 *
	 * @return Typo3_Bootstrap
	 */
	public function initializeBackendTemplate() {
		$GLOBALS['TBE_TEMPLATE'] = t3lib_div::makeInstance('template');

		return $this;
	}

}
?>