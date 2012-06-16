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

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Abstract.php';

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
class Typo3_Bootstrap_Backend extends Typo3_Bootstrap_Abstract {
	/**
	 * @var Typo3_Bootstrap_Backend
	 */
	protected static $instance = NULL;

	/**
	 * @return Typo3_Bootstrap_Backend
	 */
	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new Typo3_Bootstrap_Backend();
		}
		return self::$instance;
	}

	/**
	 * Check several a priori conditions like the current
	 * php version or exit the script with an error.
	 *
	 * @return Typo3_Bootstrap_Backend
	 */
	public function checkEnvironmentOrDie() {
		$this->checkPhpVersionOrDie();
		$this->checkGlobalsAreNotSetViaPostOrGet();

		return $this;
	}

	/**
	 * Define all simple base constants
	 *
	 * @return Typo3_Bootstrap_Backend
	 */
	public function defineBaseConstants() {
			// This version, branch and copyright
		define('TYPO3_version', '6.0-dev');
		define('TYPO3_branch', '6.0');
		define('TYPO3_copyright_year', '1998-2012');

			// TYPO3 external links
		define('TYPO3_URL_GENERAL', 'http://typo3.org/');
		define('TYPO3_URL_ORG', 'http://typo3.org/');
		define('TYPO3_URL_LICENSE', 'http://typo3.org/licenses');
		define('TYPO3_URL_EXCEPTION', 'http://typo3.org/go/exception/v4/');
		define('TYPO3_URL_MAILINGLISTS', 'http://lists.typo3.org/cgi-bin/mailman/listinfo');
		define('TYPO3_URL_DOCUMENTATION', 'http://typo3.org/documentation/');
		define('TYPO3_URL_DOCUMENTATION_TSREF', 'http://typo3.org/documentation/document-library/core-documentation/doc_core_tsref/current/view/');
		define('TYPO3_URL_DOCUMENTATION_TSCONFIG', 'http://typo3.org/documentation/document-library/core-documentation/doc_core_tsconfig/current/view/');
		define('TYPO3_URL_CONSULTANCY', 'http://typo3.org/support/professional-services/');
		define('TYPO3_URL_CONTRIBUTE', 'http://typo3.org/contribute/');
		define('TYPO3_URL_SECURITY', 'http://typo3.org/teams/security/');
		define('TYPO3_URL_DOWNLOAD', 'http://typo3.org/download/');
		define('TYPO3_URL_SYSTEMREQUIREMENTS', 'http://typo3.org/about/typo3-the-cms/system-requirements/');
		define('TYPO3_URL_DONATE', 'http://typo3.org/donate/online-donation/');

			// A tabulator, a linefeed, a carriage return, a CR-LF combination
		define('TAB', chr(9));
		define('LF', chr(10));
		define('CR', chr(13));
		define('CRLF', CR . LF);

			// Security related constant: Default value of fileDenyPattern
		define('FILE_DENY_PATTERN_DEFAULT', '\.(php[3-6]?|phpsh|phtml)(\..*)?$|^\.htaccess$');

			// Security related constant: List of file extensions that should be registered as php script file extensions
		define('PHP_EXTENSIONS_DEFAULT', 'php,php3,php4,php5,php6,phpsh,inc,phtml');

			// List of extensions required to run the core
		define('REQUIRED_EXTENSIONS', 'cms,lang,sv,em,recordlist,extbase,fluid');

			// Operating system identifier
			// Either "WIN" or empty string
		define('TYPO3_OS', $this->getTypo3Os());

		return $this;
	}

	/**
	 * Calculate all required base paths and set as constants.
	 * The script execution will be aborted if this fails.
	 *
	 * @param string $relativePathPart The relative path of the entry script to the document root
	 * @return Typo3_Bootstrap_Backend
	 */
	public function defineAndCheckPaths($relativePathPart = '') {
			// Relative path from document root to typo3/ directory
			// Hardcoded to "typo3/"
		define('TYPO3_mainDir', 'typo3/');

			// Absolute path of the entry script that was called
			// All paths are unified between Windows and Unix, so the \ of Windows is substituted to a /
			// Example "/var/www/instance-name/htdocs/typo3conf/ext/wec_map/mod1/index.php"
			// Example "c:/var/www/instance-name/htdocs/typo3/backend.php" for a path in Windows
		define('PATH_thisScript', $this->getPathThisScript());

			// Absolute path of the document root of the instance with trailing slash
			// Example "/var/www/instance-name/htdocs/"
		define('PATH_site', $this->getPathSite($relativePathPart));

			// Absolute path of the typo3 directory of the instance with trailing slash
			// Example "/var/www/instance-name/htdocs/typo3/"
		define('PATH_typo3', PATH_site . TYPO3_mainDir);

			// Relative path (from the PATH_typo3) to a BE module NOT using mod.php dispatcher with trailing slash
			// Example "sysext/perms/mod/" for an extension installed in typo3/sysext/
			// Example "install/" for the install tool entry script
			// Example "../typo3conf/ext/templavoila/mod2/ for an extension installed in typo3conf/ext/
		define('PATH_typo3_mod', defined('TYPO3_MOD_PATH') ? TYPO3_MOD_PATH : '');

			// Absolute path to the t3lib directory with trailing slash
			// Example "/var/www/instance-name/htdocs/t3lib/"
		define('PATH_t3lib', PATH_site . 't3lib/');

			// Absolute path to the typo3conf directory with trailing slash
			// Example "/var/www/instance-name/htdocs/typo3conf/"
		define('PATH_typo3conf', PATH_site . 'typo3conf/');

			// Absolute path to the tslib directory with trailing slash
			// Example "/var/www/instance-name/htdocs/typo3/sysext/cms/tslib/"
		define('PATH_tslib', PATH_typo3 . 'sysext/cms/tslib/');

			// Check above defines and exit with error message on error
		$this->checkMainPathsExist();

		return $this;
	}

	/**
	 * Load several base classes during bootstrap
	 *
	 * @return Typo3_Bootstrap_Backend
	 */
	public function requireBaseClasses() {
		require_once(PATH_t3lib . 'class.t3lib_div.php');

		require_once(PATH_t3lib . 'class.t3lib_extmgm.php');

		require_once(PATH_t3lib . 'class.t3lib_cache.php');
		require_once(PATH_t3lib . 'cache/class.t3lib_cache_exception.php');
		require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_nosuchcache.php');
		require_once(PATH_t3lib . 'cache/exception/class.t3lib_cache_exception_invaliddata.php');
		require_once(PATH_t3lib . 'interfaces/interface.t3lib_singleton.php');
		require_once(PATH_t3lib . 'cache/class.t3lib_cache_factory.php');
		require_once(PATH_t3lib . 'cache/class.t3lib_cache_manager.php');
		require_once(PATH_t3lib . 'cache/frontend/interfaces/interface.t3lib_cache_frontend_frontend.php');
		require_once(PATH_t3lib . 'cache/frontend/class.t3lib_cache_frontend_abstractfrontend.php');
		require_once(PATH_t3lib . 'cache/frontend/class.t3lib_cache_frontend_stringfrontend.php');
		require_once(PATH_t3lib . 'cache/frontend/class.t3lib_cache_frontend_phpfrontend.php');
		require_once(PATH_t3lib . 'cache/backend/interfaces/interface.t3lib_cache_backend_backend.php');
		require_once(PATH_t3lib . 'cache/backend/class.t3lib_cache_backend_abstractbackend.php');
		require_once(PATH_t3lib . 'cache/backend/interfaces/interface.t3lib_cache_backend_phpcapablebackend.php');
		require_once(PATH_t3lib . 'cache/backend/class.t3lib_cache_backend_filebackend.php');
		require_once(PATH_t3lib . 'cache/backend/class.t3lib_cache_backend_nullbackend.php');

		require_once(PATH_t3lib . 'class.t3lib_autoloader.php');

		return $this;
	}

	/**
	 * Set up php error reporting and various things like time tracking
	 *
	 * @return Typo3_Bootstrap_Backend
	 */
	public function setUpEnvironment() {
			// Core should be notice free at least until this point ...
			// @TODO: Move further down / get rid of it until errorHandler is initialized
		error_reporting(E_ALL & ~(E_STRICT | E_NOTICE | E_DEPRECATED));

			// Unset variable(s) in global scope (security issue #13959)
		unset($GLOBALS['error']);

			// Include information about the browser/user-agent
		$GLOBALS['CLIENT'] = t3lib_div::clientInfo();

			// Is set to the system time in milliseconds.
			// This could be used to output script parsetime in the end of the script
		$GLOBALS['PARSETIME_START'] = t3lib_div::milliseconds();
		$GLOBALS['TYPO3_MISC'] = array();
		$GLOBALS['TYPO3_MISC']['microtime_start'] = microtime(TRUE);

			// Compatibility layer for magic quotes
		if (!get_magic_quotes_gpc()) {
			t3lib_div::addSlashesOnArray($_GET);
			t3lib_div::addSlashesOnArray($_POST);
			$GLOBALS['HTTP_GET_VARS'] = $_GET;
			$GLOBALS['HTTP_POST_VARS'] = $_POST;
		}

		return $this;
	}

	/**
	 * Load default TYPO3_CONF_VARS
	 *
	 * @return Typo3_Bootstrap_Backend
	 */
	public function loadDefaultTypo3ConfVars() {
		$GLOBALS['TYPO3_CONF_VARS'] = require(PATH_t3lib . 'stddb/DefaultSettings.php');

		return $this;
	}

	/**
	 * Register default ExtDirect components
	 *
	 * @return Typo3_Bootstrap_Backend
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
	 * Initialize some globals
	 *
	 * @return Typo3_Bootstrap_Backend
	 */
	public function initializeGlobalVariables() {
		$GLOBALS['T3_VAR'] = array();
		$GLOBALS['T3_SERVICES'] = array();

		return $this;
	}

	/**
	 * Check typo3conf/localconf.php exists
	 *
	 * @throws RuntimeException
	 * @return Typo3_Bootstrap_Backend
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
	 * @return Typo3_Bootstrap_Backend
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
	 * Define the database setup as constants
	 * and unset no longer needed global variables
	 *
	 * @return Typo3_Bootstrap_Backend
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
	 * Initialize caching framework
	 *
	 * @return Typo3_Bootstrap_Backend
	 */
	public function initializeCachingFramework() {
		t3lib_cache::initializeCachingFramework();

		return $this;
	}

	/**
	 * Register autoloader
	 *
	 * @return Typo3_Bootstrap_Backend
	 */
	public function registerAutoloader() {
		t3lib_autoloader::registerAutoloader();

		return $this;
	}

	/**
	 * Add typo3/contrib/pear/ as first include folder in
	 * include path, because the shipped PEAR packages use
	 * relative paths to include their files.
	 *
	 * This is required for t3lib_http_Request to work.
	 *
	 * Having the TYPO3 folder first will make sure that the
	 * shipped version is loaded before any local PEAR package,
	 * thus avoiding any incompatibilities with newer or older
	 * versions.
	 *
	 * @return Typo3_Bootstrap_Backend
	 */
	public function addCorePearPathToIncludePath() {
		set_include_path(PATH_typo3 . 'contrib/pear/' . PATH_SEPARATOR . get_include_path());

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
	 * @return Typo3_Bootstrap_Backend
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
	 * @return Typo3_Bootstrap_Backend
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
	 * @return Typo3_Bootstrap_Backend
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
	 * @return Typo3_Bootstrap_Backend
	 */
	public function enforceCorrectProxyAuthScheme() {
		$GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_auth_scheme'] === 'digest' ?
			: $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_auth_scheme'] = 'basic';

		return $this;
	}

	/**
	 * Set default timezone
	 *
	 * @return Typo3_Bootstrap_Backend
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
	 * @return Typo3_Bootstrap_Backend
	 */
	public function initializeL10nLocales() {
		t3lib_l10n_Locales::initialize();

		return $this;
	}

	/**
	 * Based on the configuration of the image processing some options are forced
	 * to simplify configuration settings and combinations
	 *
	 * @return Typo3_Bootstrap_Backend
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
	 * @return Typo3_Bootstrap_Backend
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
	 * @return Typo3_Bootstrap_Backend
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
	 * @return Typo3_Bootstrap_Backend
	 */
	public function registerSwiftMailer() {
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/utility/class.t3lib_utility_mail.php']['substituteMailDelivery'][]
			= 't3lib_mail_SwiftMailerAdapter';

		return $this;
	}

	/**
	 * Configure and set up exception and error handling
	 *
	 * @return Typo3_Bootstrap_Backend
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
	 * @return Typo3_Bootstrap_Backend
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
	 * @return Typo3_Bootstrap_Backend
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
	 * Write deprecation log if the TYPO3 instance uses deprecated XCLASS
	 * registrations via $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']
	 *
	 * @return Typo3_Bootstrap_Backend
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
	 * Initialize exception handling
	 *
	 * @return Typo3_Bootstrap_Backend
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
	 * @return Typo3_Bootstrap_Backend
	 */
	public function requireAdditionalExtensionFiles() {
		require_once(t3lib_extMgm::extPath('lang') . 'lang.php');

		return $this;
	}

	/**
	 * Extensions may register new caches, so we set the
	 * global cache array to the manager again at this point
	 *
	 * @return Typo3_Bootstrap_Backend
	 */
	public function setFinalCachingFrameworkCacheConfiguration() {
		$GLOBALS['typo3CacheManager']->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);

		return $this;
	}

	/**
	 * Define logging and exception constants
	 *
	 * @return Typo3_Bootstrap_Backend
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
	 * @return Typo3_Bootstrap_Backend
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
	 * Initialize some global time variables
	 *
	 * @return Typo3_Bootstrap_Backend
	 */
	public function initializeGlobalTimeVariables() {
			// $EXEC_TIME is set so that the rest of the script has a common value for the script execution time
		$GLOBALS['EXEC_TIME'] = time();
			// $SIM_EXEC_TIME is set to $EXEC_TIME but can be altered later in the script if we want to
			// simulate another execution-time when selecting from eg. a database
		$GLOBALS['SIM_EXEC_TIME'] = $GLOBALS['EXEC_TIME'];
			// $ACCESS_TIME is a common time in minutes for access control
		$GLOBALS['ACCESS_TIME'] = $GLOBALS['EXEC_TIME'] - ($GLOBALS['EXEC_TIME'] % 60);
			// If $SIM_EXEC_TIME is changed this value must be set accordingly
		$GLOBALS['SIM_ACCESS_TIME'] = $GLOBALS['ACCESS_TIME'];

		return $this;
	}

	/**
	 * Initialize t3lib_db in $GLOBALS and connect if requested
	 *
	 * @param bool $connect Whether or not the db should be connected already
	 * @return Typo3_Bootstrap_Backend
	 */
	public function initializeTypo3DbGlobal($connect = TRUE) {
			/** @var TYPO3_DB t3lib_db */
		$GLOBALS['TYPO3_DB'] = t3lib_div::makeInstance('t3lib_DB');
		$GLOBALS['TYPO3_DB']->debugOutput = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sqlDebug'];
		if ($connect) {
			$GLOBALS['TYPO3_DB']->connectDB();
		}

		return $this;
	}

	/**
	 * Check adminOnly configuration variable and redirects
	 * to an URL in file typo3conf/LOCK_BACKEND or exit the script
	 *
	 * @throws RuntimeException
	 * @return Typo3_Bootstrap_Backend
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
	 * @return Typo3_Bootstrap_Backend
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
	 * @return Typo3_Bootstrap_Backend
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
	 * Load ext_tables and friends.
	 *
	 * This will mainly set up $TCA and several other global arrays
	 * through API's like extMgm.
	 * Executes ext_tables.php files of loaded extensions or the
	 * according typo3conf/temp_CACHED_*_ext_tables.php if exists.
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
	 * @return Typo3_Bootstrap_Backend
	 */
	public function loadExtensionTables() {
		global $T3_SERVICES, $T3_VAR, $TYPO3_CONF_VARS;
		global $TBE_MODULES, $TBE_MODULES_EXT, $TCA;
		global $PAGES_TYPES, $TBE_STYLES, $FILEICONS;

			// Include standard tables.php file
		require(PATH_t3lib . 'stddb/tables.php');

		if (
			$GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE']
			&& file_exists(PATH_typo3conf . $GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'] . '_ext_tables.php')
		) {
				// Load temp_CACHED_x_ext_tables.php file if exists
			require(PATH_typo3conf . $GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'] . '_ext_tables.php');
		} else {
				// Load each ext_tables.php file of loaded extensions
			foreach ($GLOBALS['TYPO3_LOADED_EXT'] as $_EXTKEY => $extensionInformation) {
				if (is_array($extensionInformation) && $extensionInformation['ext_tables.php']) {
						// $_EXTKEY and $_EXTCONF are available in ext_tables.php
						// and are explicitly set in temp_CACHED file as well
					$_EXTCONF = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY];
					require($extensionInformation['ext_tables.php']);
				}
			}
		}

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
	 * @return Typo3_Bootstrap_Backend
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
	 * @param bool $allowRegeneration
	 * @return Typo3_Bootstrap_Backend
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
	 * @return Typo3_Bootstrap_Backend
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
	 * @return Typo3_Bootstrap_Backend
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
	 * @return Typo3_Bootstrap_Backend
	 */
	public function initializeLanguageObject() {
			/** @var $GLOBALS['LANG'] language */
		$GLOBALS['LANG'] = t3lib_div::makeInstance('language');
		$GLOBALS['LANG']->init($GLOBALS['BE_USER']->uc['lang']);

		return $this;
	}

	/**
	 * Things that should be performed to shut down the framework.
	 * This method is called in all important scripts for a clean
	 * shut down of the system.
	 *
	 * @return Typo3_Bootstrap_Backend
	 */
	public function shutdown() {
		t3lib_autoloader::unregisterAutoloader();

		return $this;
	}

	/**
	 * Check php version requirement or exit script
	 *
	 * @return void
	 */
	protected function checkPhpVersionOrDie() {
		if (version_compare(phpversion(), '5.3', '<')) {
			die('TYPO3 requires PHP 5.3.0 or higher.');
		}
	}

	/**
	 * Exit script if globals are set via post or get
	 *
	 * @return void
	 */
	protected function checkGlobalsAreNotSetViaPostOrGet() {
		if (isset($_POST['GLOBALS']) || isset($_GET['GLOBALS'])) {
			die('You cannot set the GLOBALS array from outside the script.');
		}
	}

	/**
	 * Determine the operating system TYPO3 is running on.
	 *
	 * @return string Either 'WIN' if running on Windows, else empty string
	 */
	protected function getTypo3Os() {
		$typoOs = '';
		if (!stristr(PHP_OS, 'darwin') && stristr(PHP_OS, 'win')) {
			$typoOs = 'WIN';
		}
		return $typoOs;
	}

	/**
	 * Calculate PATH_thisScript
	 *
	 * First step in path calculation: Goal is to find the absolute path of the entry script
	 * that was called without resolving any links. This is important since the TYPO3 entry
	 * points are often linked to a central core location, so we can not use the php magic
	 * __FILE__ here, but resolve the called script path from given server environments.
	 *
	 * This path is important to calculate the document root (PATH_site). The strategy is to
	 * find out the script name that was called in the first place and to subtract the local
	 * part from it to find the document root.
	 *
	 * @return string Absolute path to entry script
	 */
	protected function getPathThisScript() {
		if (defined('TYPO3_cliMode') && TYPO3_cliMode === TRUE) {
			return $this->getPathThisScriptCli();
		} else {
			return $this->getPathThisScriptNonCli();
		}
	}

	/**
	 * Calculate path to entry script if not in cli mode.
	 *
	 * Depending on the environment, the script path is found in different $_SERVER variables.
	 *
	 * @return string Absolute path to entry script
	 */
	protected function getPathThisScriptNonCli() {
		$cgiPath = '';
		if (isset($_SERVER['ORIG_PATH_TRANSLATED'])) {
			$cgiPath = $_SERVER['ORIG_PATH_TRANSLATED'];
		} elseif (isset($_SERVER['PATH_TRANSLATED'])) {
			$cgiPath = $_SERVER['PATH_TRANSLATED'];
		}
		if ($cgiPath && (PHP_SAPI === 'fpm-fcgi' || PHP_SAPI === 'cgi' || PHP_SAPI === 'isapi' || PHP_SAPI === 'cgi-fcgi')) {
			$scriptPath = $cgiPath;
		} else {
			if (isset($_SERVER['ORIG_SCRIPT_FILENAME'])) {
				$scriptPath = $_SERVER['ORIG_SCRIPT_FILENAME'];
			} else {
				$scriptPath = $_SERVER['SCRIPT_FILENAME'];
			}
		}
			// Replace \ to / for Windows
		$scriptPath = str_replace('\\', '/', $scriptPath);
			// Replace double // to /
		$scriptPath = str_replace('//', '/', $scriptPath);

		return $scriptPath;
	}

	/**
	 * Calculate path to entry script if in cli mode.
	 *
	 * First argument of a cli script is the path to the script that was called. If the script does not start
	 * with / (or A:\ for Windows), the path is not absolute yet, and the current working directory is added.
	 *
	 * @return string Absolute path to entry script
	 */
	protected function getPathThisScriptCli() {
			// Possible relative path of the called script
		if (isset($_SERVER['argv'][0])) {
			$scriptPath = $_SERVER['argv'][0];
		} elseif (isset($_ENV['_'])) {
			$scriptPath = $_ENV['_'];
		} else {
			$scriptPath = $_SERVER['_'];
		}

			// Find out if path is relative or not
		$isRelativePath = FALSE;
		if (TYPO3_OS === 'WIN') {
			if (!preg_match('/^([A-Z]:)?\\\/', $scriptPath)) {
				$isRelativePath = TRUE;
			}
		} else {
			if (substr($scriptPath, 0, 1) !== '/') {
				$isRelativePath = TRUE;
			}
		}

			// Concatenate path to current working directory with relative path and remove "/./" constructs
		if ($isRelativePath) {
			if (isset($_SERVER['PWD'])) {
				$workingDirectory = $_SERVER['PWD'];
			} else {
				$workingDirectory = getcwd();
			}
			$scriptPath = $workingDirectory . '/' . preg_replace('/\.\//', '', $scriptPath);
		}

		return $scriptPath;
	}

	/**
	 * Calculate the document root part to the instance from PATH_thisScript
	 *
	 * There are two ways to hint correct calculation:
	 * Either an explicit specified sub path or the defined constant TYPO3_MOD_PATH. Which one is
	 * used depends on which entry script was called in the first place.
	 *
	 * We have two main scenarios for entry points:
	 * - Directly called documentRoot/index.php (-> FE call or eiD include): index.php sets $relativePathPart to
	 *   empty string to hint this code that the document root is identical to the directory the script is located at.
	 * - An indirect include of typo3/init.php (-> a backend module, the install tool, or scripts like thumbs.php).
	 *   If init.php is included we distinguish two cases:
	 *   -- A backend module defines 'TYPO3_MOD_PATH': This is the case for "old" modules that are not called through
	 *      "mod.php" dispatcher, and in the install tool. The TYPO3_MOD_PATH defines the relative path to the typo3/
	 *      directory. This is taken as base to calculate the document root.
	 *   -- A script includes init.php and does not define 'TYPO3_MOD_PATH': This is the case for the mod.php dispatcher
	 *      and other entry scripts like 'cli_dispatch.phpsh' or 'thumbs.php' that are located parallel to init.php. In
	 *      this case init.php sets 'typo3/' as $relativePathPart as base to calculate the document root.
	 *
	 * This basically boils down to the following code:
	 * If TYPO3_MOD_PATH is defined, subtract this 'local' part from the entry point directory, else use
	 * $relativePathPart to subtract this from the the script entry point to find out the document root.
	 *
	 * @param string $relativePathPart Relative directory part from document root to script path if TYPO3_MOD_PATH is not used
	 * @return string Absolute path to document root of installation
	 */
	protected function getPathSite($relativePathPart) {
			// If end of path is not "typo3/" and TYPO3_MOD_PATH is given
		if (defined('TYPO3_MOD_PATH')) {
			return $this->getPathSiteByTypo3ModulePath();
		} else {
			return $this->getPathSiteByRelativePathPart($relativePathPart);
		}
	}

	/**
	 * Calculate document root by TYPO3_MOD_PATH
	 *
	 * TYPO3_MOD_PATH can have the following values:
	 * - "sysext/extensionName/path/entryScript.php" -> extension is below 'docRoot'/typo3/sysext
	 * - "ext/extensionName/path/entryScript.php" -> extension is below 'docRoot'/typo3/ext
	 * - "../typo3conf/ext/extensionName/path/entryScript.php" -> extension is below 'docRoot'/typo3conf/ext
	 *- "install/index.php" -> install tool in 'docRoot'/typo3/install/
	 *
	 * The method unifies the above and subtracts the calculated path part from PATH_thisScript
	 *
	 * @return string Absolute path to document root of installation
	 */
	protected function getPathSiteByTypo3ModulePath() {
		if (
			substr(TYPO3_MOD_PATH, 0, strlen('sysext/')) === 'sysext/'
			|| substr(TYPO3_MOD_PATH, 0, strlen('ext/')) === 'ext/'
			|| substr(TYPO3_MOD_PATH, 0, strlen('install/')) === 'install/'
		) {
			$pathPartRelativeToDocumentRoot = TYPO3_mainDir . TYPO3_MOD_PATH;
		} elseif (substr(TYPO3_MOD_PATH, 0, strlen('../typo3conf/')) === '../typo3conf/') {
			$pathPartRelativeToDocumentRoot = substr(TYPO3_MOD_PATH, 3);
		} else {
			die('Unable to determine TYPO3 document root.');
		}

		$entryScriptDirectory = $this->getUnifiedDirectoryNameWithTrailingSlash(PATH_thisScript);

		return substr($entryScriptDirectory, 0, -strlen($pathPartRelativeToDocumentRoot));
	}

	/**
	 * Find out document root by subtracting $relativePathPart from PATH_thisScript
	 *
	 * @param string $relativePathPart Relative part of script from document root
	 * @return string Absolute path to document root of installation
	 */
	protected function getPathSiteByRelativePathPart($relativePathPart) {
		$entryScriptDirectory = $this->getUnifiedDirectoryNameWithTrailingSlash(PATH_thisScript);
		if (strlen($relativePathPart) > 0) {
			$pathSite = substr($entryScriptDirectory, 0, -strlen($relativePathPart));
		} else {
			$pathSite = $entryScriptDirectory;
		}
		return $pathSite;
	}

	/**
	 * Remove file name from script path and unify for Windows and Unix
	 *
	 * @param string $absolutePath Absolute path to script
	 * @return string Directory name of script file location, unified for Windows and Unix
	 */
	protected function getUnifiedDirectoryNameWithTrailingSlash($absolutePath) {
		$directory = dirname($absolutePath);
		if (TYPO3_OS === 'WIN') {
			$directory = str_replace('\\', '/', $directory);
		}
		return $directory . '/';
	}

	/**
	 * Check if path and script file name calculation was successful, exit if not.
	 *
	 * @return void
	 */
	protected function checkMainPathsExist() {
		if (!is_file(PATH_thisScript)) {
			die('Unable to determine path to entry script.');
		}
		if (!is_dir(PATH_t3lib)) {
			die('Calculated absolute path to t3lib directory does not exist.');
		}
		if (!is_dir(PATH_tslib)) {
			die('Calculated absolute path to tslib directory does not exist.');
		}
		if (!is_dir(PATH_typo3conf)) {
			die('Calculated absolute path to typo3conf directory does not exist');
		}
	}
}
?>