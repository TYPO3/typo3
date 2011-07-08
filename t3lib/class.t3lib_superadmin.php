<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
/**
 * Super Admin class has functions for the administration of multiple TYPO3 sites in folders
 * See 'misc/superadmin.php' for details on how to use!
 *
 * Revised for TYPO3 3.6 February/2004 by Kasper Skårhøj
 * XHTML Compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */


	// *******************************
	// Set error reporting
	// *******************************
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);

define('TYPO3_mainDir', 'typo3/'); // This is the directory of the backend administration for the sites of this TYPO3 installation.


	// Dependency:
$path_t3lib = './typo3_src/t3lib/';
include_once($path_t3lib . 'class.t3lib_div.php');
include_once($path_t3lib . 'class.t3lib_db.php');
$TYPO3_DB = t3lib_div::makeInstance('t3lib_DB');


t3lib_div::deprecationLog('class.t3lib_superadmin.php is deprecated since TYPO3 4.5, this file will be removed in TYPO3 4.7.');


/**
 * Debug function. Substitute since no config_default.php file is included anywhere
 *
 * @param	mixed		Debug var
 * @param	string		Header string
 * @return	void
 */
function debug($p1, $p2 = '') {
	t3lib_div::debug($p1, $p2);
}


/**
 * Super Admin class has functions for the administration of multiple TYPO3 sites in folders
 * NOTICE: Only compliant with single MySQL database usage per installation!
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_superadmin {

		// External, static:
	var $targetWindow = 'superAdminWindow';
	var $targetWindowAdmin = 'superAdminWindowAdmin';
	var $targetWindowInstall = 'superAdminWindowInstall';
	var $scriptName = 'superadmin.php';

		// GP vars:
	var $show; // "menu", "all", "admin", "info", "rmTempCached", "localext"
	var $type; // "phpinfo", "page" - default renders a frameset
	var $exp; // Additional parameter, typically a md5-hash pointing to an installation of TYPO3

		// Internal, static:
	var $parentDirs = array(); // Configured directories to search
	var $globalSiteInfo = array(); // Array with information about found TYPO3 installations

	var $currentUrl = '';
	var $mapDBtoKey = array();
	var $collectAdminPasswords = array();
	var $changeAdminPasswords = array();
	var $collectInstallPasswords = array();

		// Control:
	var $full = 0; // If set, the full information array per site is printed.

	var $noCVS = 0; // See tools/em/index.php....


	/**********************************
	 *
	 * Initialize stuff
	 *
	 **********************************/

	/**
	 * Constructor, setting GP vars
	 *
	 * @return	void
	 */
	function __construct() {
		$this->show = t3lib_div::_GP('show');
		$this->type = t3lib_div::_GP('type');
		$this->exp = t3lib_div::_GP('exp');
	}

	/**
	 * Initialize with configuration - from the 'superadmin.php' script. See misc/superadmin.php for example.
	 *
	 * @param	array		Numerical array with arrays having two keys, 'dir' and 'url' where 'dir' is the absolute path to a directory with TYPO3 installations inside.
	 * @return	void
	 */
	function init($parentDirs) {
		$this->parentDirs = $parentDirs;
	}


	/**************************
	 *
	 * Main functions
	 *
	 **************************/

	/**
	 * Main function, creating HTML content; frameset, menu, content frames.
	 * Outputs the full HTML to browser.
	 *
	 * @return	void
	 */
	function defaultSet() {

			// Creating content based on "type" variable:
		switch ($this->type) {
			case 'phpinfo':
				phpinfo();
			break;
			case 'page':
				?>
				<!DOCTYPE html
						PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
						"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
				<html>
				<head>
					<style type="text/css">
						.redclass {
							color: red;
						}

						P {
							font-family: Verdana, Arial, Helvetica, sans-serif;
							font-size: 11px
						}

						BODY {
							font-family: Verdana, Arial, Helvetica, sans-serif;
							font-size: 10px
						}

						H1 {
							font-family: Verdana, Arial, Helvetica, sans-serif;
							font-size: 20px;
							color: #000066;
						}

						H2 {
							font-family: Verdana, Arial, Helvetica, sans-serif;
							font-size: 17px;
							color: #000066;
						}

						H3 {
							font-family: Verdana, Arial, Helvetica, sans-serif;
							font-size: 14px;
							color: #000066;
						}

						H4 {
							font-family: Verdana, Arial, Helvetica, sans-serif;
							font-size: 11px;
							color: maroon;
						}

						TD {
							font-family: Verdana, Arial, Helvetica, sans-serif;
							font-size: 10px
						}
					</style>
					<title>TYPO3 Super Admin</title>
				</head>
				<body>
					<?php
	 	echo $this->make();
				?>
				</body>
				</html>
					<?php
			break;
			default:
				?>
				<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
				<html>
				<head>
					<title>TYPO3 Super Admin</title>
				</head>
				<frameset cols="250,*">
					<frame name="TSAmenu" src="superadmin.php?type=page&show=menu" marginwidth="10" marginheight="10"
						   scrolling="auto" frameborder="0">
					<frame name="TSApage" src="superadmin.php?type=page" marginwidth="10" marginheight="10"
						   scrolling="auto" frameborder="0">
				</frameset>
				</html>
				<?php
			break;
		}
	}

	/**
	 * Main function, creating page content.
	 *
	 * @return	string		HTML content.
	 */
	function make() {

		$retVal = '';

			// Creating information about the sites found:
		$content = $this->initProcess();

			// Output mode:
		switch ($this->show) {
			case 'menu':
				$lines = array();
				$lines[] = $this->setMenuItem('info', 'INFO');
				$lines[] = $this->setMenuItem('update', 'UPDATE');
				$lines[] = '';
				$lines[] = '<a href="' . htmlspecialchars($this->scriptName . '?type=page') . '" target="TSApage">Default</a>';
				$lines[] = '<a href="' . htmlspecialchars($this->scriptName . '?type=page&show=all') . '" target="TSApage">All details</a>';
				$lines[] = '<a href="' . htmlspecialchars($this->scriptName . '?type=page&show=admin') . '" target="TSApage">Admin logins</a>';
				$lines[] = '<a href="' . htmlspecialchars($this->scriptName . '?type=phpinfo') . '" target="TSApage">phpinfo()</a>';
				$lines[] = '<a href="' . htmlspecialchars($this->scriptName . '?type=page&show=localext') . '" target="TSApage">Local extensions</a>';
				$lines[] = '';
				$content = implode('<br />', $lines);
				$content .= '<hr />';
				$content .= $this->menuContent($this->exp);
				$retVal = '<h2 align="center">TYPO3<br />Super Admin</h2>' . $content;
			break;
			case 'all':
				$retVal = '
					<h1>All details:</h1>
					<h2>Overview:</h2>
					' . $this->makeTable() . '
					<br /><hr /><br />
					<h1>Details per site:</h1>
					' . $content;
			break;
			case 'admin':
				$content = $this->setNewPasswords();
				$this->makeTable();
				$retVal = $content . '
					<h1>Admin options:</h1>

					<h2>Admin logins:</h2>
					' . $this->makeAdminLogin() . '
					<br /><hr /><br />

					<h2>TBE Admin Passwords:</h2>
					' . t3lib_div::view_array($this->collectAdminPasswords) . '
					<br /><hr /><br />

					<h2>Install Tool Passwords:</h2>
					' . t3lib_div::view_array($this->collectInstallPasswords) . '
					<br /><hr /><br />

					<h2>Change TBE Admin Passwords:</h2>
					' . $this->changeAdminPasswordsForm() . '
					<br /><hr /><br />';
			break;
			case 'info':
				$retVal = '
					<h1>Single site details</h1>
					' . $this->singleSite($this->exp) .
						  '<br />';
			break;
			case 'rmTempCached':
				$retVal = '
					<h1>Removing temp_CACHED_*.php files</h1>
					' . $this->rmCachedFiles($this->exp) .
						  '<br />';
			break;
			case 'localext':
				$retVal = '
					<h1>Local Extensions Found:</h1>
					' . $this->localExtensions() .
						  '<br />';
			break;
			default:
				$retVal = '
					<h1>Default info:</h1>' .
						  $content;
			break;
		}
		return $retVal;
	}


	/********************************
	 *
	 * Output preparation
	 *
	 *******************************/

	/**
	 * Creates menu item from input.
	 *
	 * @param	string		Value for "&exp" parameter
	 * @param	string		The label
	 * @return	string		Wrapped value
	 */
	function setMenuItem($code, $label) {
		$out = '<a href="' . htmlspecialchars($this->scriptName . '?type=page&show=menu&exp=' . $code) . '" target="TSAmenu">' . htmlspecialchars($label) . '</a>';
		if ($code == $this->exp) {
			$out = '<span style="color:red;">&gt;&gt;</span>' . $out;
		}
		return $out;
	}

	/**
	 * Wrap string in red span tag (for errors)
	 *
	 * @param	string		Input string
	 * @return	string		Output string
	 */
	function error($str) {
		$out = '<span style="color:red; font-size: 14px; font-weight: bold;">' . htmlspecialchars($str) . '</span>';
		return $out;
	}

	/**
	 * Wraps input string in <h2>
	 *
	 * @param	string		Input string
	 * @return	string		Output string, wrapped in <h2>
	 */
	function headerParentDir($str) {
		$out = '<h2>' . htmlspecialchars($str) . '</h2>';
		return $out;
	}

	/**
	 * Wraps input string in <h3>
	 *
	 * @param	string		Input string
	 * @return	string		Output string, wrapped in <h3>
	 */
	function headerSiteDir($str) {
		$out = '<h3>' . htmlspecialchars($str) . '</h3>';
		return $out;
	}


	/********************************
	 *
	 * Collection information
	 *
	 *******************************/

	/**
	 * Traverses the parent dirs, collecting the list of TYPO3 installations into $this->globalSiteInfo
	 *
	 * @return	string		HTML content (The default view seen when starting the superadmin.php script)
	 */
	function initProcess() {
		$content = '';

		foreach ($this->parentDirs as $k => $v) {
			$dir = rtrim($v['dir'], '/');
			$baseUrl = rtrim($v['url'], '/');
			$content .= '<br /><br /><br />';
			$content .= $this->headerParentDir($dir);
			if (@is_dir($dir)) {
				$in_dirs = t3lib_div::get_dirs($dir);
				asort($in_dirs);
				$dirArr = array();
				foreach ($in_dirs as $k => $v) {
					if (substr($v, 0, 9) != 'typo3_src') {
						$this->currentUrl = $baseUrl . '/' . $v;
						$content .= $this->headerSiteDir($v);
						$content .= $this->processSiteDir($dir . '/' . $v, $dir);
					}
				}
			} else {
				$content .= $this->error('"' . $dir . '" was not a directory!');
			}
		}

		return $content;
	}

	/**
	 * Creating information array for a specific TYPO3 installation
	 * Information about site is stored in ->globalSiteInfo array
	 *
	 * @param	string		Absolute path to installation (PATH_site)
	 * @param	string		Directory of main directory (level under PATH_site)
	 * @return	string		HTML content with information about the site.
	 * @access private
	 * @see initProcess()
	 */
	function processSiteDir($path, $dir) {
		$out = '';
		if (@is_dir($path)) {
			$localconf = $path . '/typo3conf/localconf.php';
			if (@is_file($localconf)) {
				$key = md5($localconf);
				$this->includeLocalconf($localconf);

				$this->mapDBtoKey[$this->globalSiteInfo[$key]['siteInfo']['TYPO3_db']] = $key;
				$this->globalSiteInfo[$key]['siteInfo']['MAIN_DIR'] = $dir;
				$this->globalSiteInfo[$key]['siteInfo']['SA_PATH'] = $path;
				$this->globalSiteInfo[$key]['siteInfo']['URL'] = $this->currentUrl . '/';
				$this->globalSiteInfo[$key]['siteInfo']['ADMIN_URL'] = $this->currentUrl . '/' . TYPO3_mainDir;
				$this->globalSiteInfo[$key]['siteInfo']['INSTALL_URL'] = $this->currentUrl . '/' . TYPO3_mainDir . 'install/';

					// Connect to database:
				$conMsg = $this->connectToDatabase($this->globalSiteInfo[$key]['siteInfo']);
				if (!$conMsg) {
					$this->getDBInfo($key);
					$out .= '';
				} else {
					$out = $conMsg;
				}

					// Show details:
				if ($this->full) {
					$out .= t3lib_div::view_array($this->globalSiteInfo[$key]);
				} else {
					$out .= t3lib_div::view_array($this->globalSiteInfo[$key]['siteInfo']);
				}
			} else {
				$out = $this->error($localconf . ' is not a file!');
			}
		} else {
			$out = $this->error($path . ' is not a directory!');
		}
		return $out;
	}

	/**
	 * Includes "localconf" of a TYPO3 installation an loads $this->globalSiteInfo with this information.
	 *
	 * @param	string		Absolute path to localconf.php file to include.
	 * @return	array		Array with information about the site.
	 * @access private
	 * @see processSiteDir()
	 */
	function includeLocalconf($localconf) {
		$TYPO3_CONF_VARS = array();
		$typo_db = '';
		$typo_db_username = '';
		$typo_db_password = '';
		$typo_db_host = '';

		include($localconf);

		$siteInfo = array();
		$siteInfo['sitename'] = $TYPO3_CONF_VARS['SYS']['sitename'];
		$siteInfo['TYPO3_db'] = $typo_db;
		$siteInfo['TYPO3_db_username'] = $typo_db_username;
		$siteInfo['TYPO3_db_password'] = $typo_db_password;
		$siteInfo['TYPO3_db_host'] = $typo_db_host;
		$siteInfo['installToolPassword'] = $TYPO3_CONF_VARS['BE']['installToolPassword'];
		$siteInfo['warningEmailAddress'] = $TYPO3_CONF_VARS['BE']['warning_email_addr'];
		$siteInfo['warningMode'] = $TYPO3_CONF_VARS['BE']['warning_mode'];

		$this->globalSiteInfo[md5($localconf)] = array('siteInfo' => $siteInfo, 'TYPO3_CONF_VARS' => $TYPO3_CONF_VARS);
		return $siteInfo;
	}

	/**
	 * Connects to a MySQL database with the TYPO3 db host/username/password and database as found in the localconf.php file!
	 * This is NOT compatible with DBAL and connection will obviously just fail with an error message if it turns out that the _DEFAULT handler of a site is not in a MySQL database
	 *
	 * @param	array		$siteInfo array, containing username/password/host/database values.
	 * @return	string		Array message if any
	 */
	function connectToDatabase($siteInfo) {
		if (@mysql_pconnect($siteInfo['TYPO3_db_host'], $siteInfo['TYPO3_db_username'], $siteInfo['TYPO3_db_password'])) {
			if (!$siteInfo['TYPO3_db']) {
				return $this->error('No database selected');
			} elseif (!mysql_select_db($siteInfo['TYPO3_db'])) {
				return $this->error('Cannot connect to the current database, "' . $siteInfo['TYPO3_db'] . '"');
			}
		} else {
			return $this->error('The current username, password or host was not accepted when the connection to the database was attempted to be established!');
		}
	}


	/**
	 * Get database information, assuming standard tables like "be_users"
	 * Adding the information to ->globalSiteInfo
	 *
	 * @param	string		Key for site in ->globalSiteInfo
	 * @return	void
	 * @access private
	 * @see processSiteDir()
	 */
	function getDBInfo($key) {
		$DB = $this->globalSiteInfo[$key]['siteInfo']['TYPO3_db'];

			// Non-admin users
		$query = $GLOBALS['TYPO3_DB']->SELECTquery('count(*)', 'be_users', 'admin=0 AND deleted=0');
		$res = mysql($DB, $query);
		$row = mysql_fetch_row($res);
		$this->globalSiteInfo[$key]['siteInfo']['BE_USERS_NONADMIN'] = $row[0];

			// Admin users
		$query = $GLOBALS['TYPO3_DB']->SELECTquery('count(*)', 'be_users', 'admin!=0 AND deleted=0');
		$res = mysql($DB, $query);
		$row = mysql_fetch_row($res);
		$this->globalSiteInfo[$key]['siteInfo']['BE_USERS_ADMIN'] = $row[0];

			// Select Admin users
		$query = $GLOBALS['TYPO3_DB']->SELECTquery('uid,username,password,email,realName,disable', 'be_users', 'admin!=0 AND deleted=0');
		$res = mysql($DB, $query);
		while ($row = mysql_fetch_assoc($res)) {
			$this->globalSiteInfo[$key]['siteInfo']['ADMINS'][] = $row;
		}
	}


	/******************************
	 *
	 * Content: Installation Overview
	 *
	 ******************************/

	/**
	 * Creates big table with information about all installations in ->globalSiteInfo
	 *
	 * @return	string		HTML table
	 */
	function makeTable() {

			// Header row
		$info = array();
		$info[] = 'Site:';
		$info[] = 'Path:';
		$info[] = 'Database:';
		$info[] = 'Username';
		$info[] = 'Password';
		$info[] = 'Host';
		$info[] = 'Links (new win)';
		$info[] = '#Users NA/A';
		$info[] = 'Admin be_users Info';
		$info[] = 'Install Tool Password';
		$info[] = 'Warning email address';
		$info[] = 'W.mode';
		$mainArrRows[] = '
			<tr bgcolor="#eeeeee">
				<td nowrap="nowrap" valign="top">' . implode('</td>
				<td nowrap="nowrap" valign="top">', $info) . '</td>
			</tr>';

			// Traverse globalSiteInfo for each site:
		foreach ($this->globalSiteInfo as $k => $all) {
			$info = array();

				// Sitename and Database details:
			$info[] = htmlspecialchars($all['siteInfo']['sitename']);
			$info[] = '<span style="color:#666666;">' . htmlspecialchars($all['siteInfo']['MAIN_DIR']) . '</span>' . htmlspecialchars(substr($all['siteInfo']['SA_PATH'], strlen($all['siteInfo']['MAIN_DIR'])));
			$info[] = htmlspecialchars($all['siteInfo']['TYPO3_db']);
			$info[] = htmlspecialchars($all['siteInfo']['TYPO3_db_username']);
			$info[] = htmlspecialchars($all['siteInfo']['TYPO3_db_password']);
			$info[] = htmlspecialchars($all['siteInfo']['TYPO3_db_host']);

				// URL
			$info[] = '<a href="' . htmlspecialchars($all['siteInfo']['URL']) . '" target="' . $this->targetWindow . '">Site</a>' .
					  ' / <a href="' . htmlspecialchars($all['siteInfo']['ADMIN_URL']) . '" target="' . $this->targetWindowAdmin . '">Admin</a>' .
					  ' / <a href="' . htmlspecialchars($all['siteInfo']['INSTALL_URL']) . '" target="' . $this->targetWindowInstall . '">Install</a>';
			$info[] = htmlspecialchars($all['siteInfo']['BE_USERS_NONADMIN'] . '/' . $all['siteInfo']['BE_USERS_ADMIN']);

				// Admin
			if (is_array($all['siteInfo']['ADMINS'])) {
				$lines = array();
				foreach ($all['siteInfo']['ADMINS'] as $vArr) {
					$lines[] = htmlspecialchars($vArr['password'] . ' - ' . $vArr['username'] . ' (' . $vArr['realName'] . ', ' . $vArr['email'] . ')');
					$this->collectAdminPasswords[$vArr['password']][] = array(
						'path' => $all['siteInfo']['SA_PATH'],
						'site' => $all['siteInfo']['sitename'],
						'database' => $all['siteInfo']['TYPO3_db'],
						'user' => $vArr['username'],
						'name_email' => $vArr['realName'] . ', ' . $vArr['email']
					);
					$this->changeAdminPasswords[$vArr['password']][] = $all['siteInfo']['TYPO3_db'] . ':' . $vArr['uid'] . ':' . $vArr['username'];
				}
				$info[] = implode('<br />', $lines);
			} else {
				$info[] = $this->error('No DB connection!');
			}
				// Install
			$info[] = htmlspecialchars($all['siteInfo']['installToolPassword']);
			$this->collectInstallPasswords[$all['siteInfo']['installToolPassword']][] = $all['siteInfo']['SA_PATH'] . ' - ' . $all['siteInfo']['sitename'] . ' - (' . $all['siteInfo']['TYPO3_db'] . ')';

			$info[] = htmlspecialchars($all['siteInfo']['warningEmailAddress']);
			$info[] = htmlspecialchars($all['siteInfo']['warningMode']);

				// compile
			$mainArrRows[] = '
				<tr>
					<td nowrap="nowrap" valign="top">' . implode('</td>
					<td nowrap="nowrap" valign="top">', $info) . '</td>
				</tr>';
		}

			// Compile / return table finally:
		$table = '<table border="1" cellpadding="1" cellspacing="1">' . implode('', $mainArrRows) . '</table>';
		return $table;
	}


	/******************************
	 *
	 * Content: Local extensions
	 *
	 ******************************/

	/**
	 * Based on the globalSiteInfo array, this prints information about local extensions for each site.
	 * In particular version number and most recent mod-time is interesting!
	 *
	 * @return	string		HTML
	 */
	function localExtensions() {
		$this->extensionInfoArray = array();

			// Traverse $this->globalSiteInfo for local extensions:
		foreach ($this->globalSiteInfo as $k => $all) {
			if ($all['siteInfo']['SA_PATH']) {
				$extDir = $all['siteInfo']['SA_PATH'] . '/typo3conf/ext/';
				if (@is_dir($extDir)) {
					$this->extensionInfoArray['site'][$k] = array();

						// Get extensions in local directory
					$extensions = t3lib_div::get_dirs($extDir);
					if (is_array($extensions)) {
						foreach ($extensions as $extKey) {
								// Getting and setting information for extension:
							$eInfo = $this->getExtensionInfo($extDir, $extKey, $k);
							$this->extensionInfoArray['site'][$k][$extKey] = $eInfo;
							$this->extensionInfoArray['ext'][$extKey][$k] = $eInfo;
						}
					}
				}
			}
		}

			// Display results:
		$out = '';
		$headerRow = '
					<tr bgcolor="#ccccee" style="font-weight:bold;">
						<td>Extension key</td>
						<td>Path</td>
						<td>Title</td>
						<td>Ver.</td>
						<td>Files</td>
						<td><span title="If M, then there is a manual.">M</span></td>
						<td>Last mod. time</td>
						<td>Hash off all file mod. times:</td>
						<td>TYPO3 ver. req.</td>
						<td>CGL compliance</td>
					</tr>
					';

			// PER EXTENSION:
		if (is_array($this->extensionInfoArray['ext'])) {
			$extensionKeysCollect = array();

			ksort($this->extensionInfoArray['ext']);
			reset($this->extensionInfoArray['ext']);
			$rows = array(
				'reg' => array(),
				'user' => array()
			);

			foreach ($this->extensionInfoArray['ext'] as $extKey => $instances) {
				$mtimes = array();

					// Find most recent mtime of the options:
				foreach ($instances as $k => $eInfo) {
					$mtimes[] = $eInfo['mtime'];
				}
					// Max mtime:
				$maxMtime = max($mtimes);
				$c = 0;

					// So, traverse all sites with the extension present:
				foreach ($instances as $k => $eInfo) {
						// Set background color if mtime matches
					if ($maxMtime == $eInfo['mtime']) {
						$this->extensionInfoArray['site'][$k][$extKey]['_highlight'] = 1;
						$bgCol = $eInfo['dirtype'] == 'link' ? ' bgcolor="#ffcccc"' : ' bgcolor="#eeeeee"';
					} else {
						$bgCol = ' style="color: #999999; font-style: italic;"';
					}

						// Make row:
					$type = substr($extKey, 0, 5) != 'user_' ? 'reg' : 'user';
					if ($type == 'reg') {
						$extensionKeysCollect[] = $extKey;
					}

					if (!is_array($eInfo)) {
							// Standard listing:
						$rows[$type][] = '
						<tr>
							' . (!$c ? '<td rowspan="' . count($instances) . '">' . htmlspecialchars($extKey) . '</td>' : '') . '
							<td nowrap="nowrap" bgcolor="#ccddcc">' . htmlspecialchars($this->globalSiteInfo[$k]['siteInfo']['SA_PATH']) . '</td>
							<td nowrap="nowrap" bgcolor="#ccddcc" colspan="8"><em>' . htmlspecialchars($eInfo) . '</em></td>
						</tr>
						';
					} else {
							// Standard listing:
						$rows[$type][] = '
						<tr>
							' . (!$c ? '<td rowspan="' . count($instances) . '">' . htmlspecialchars($extKey) . '</td>' : '') . '
							<td nowrap="nowrap"' . $bgCol . '>' . htmlspecialchars($this->globalSiteInfo[$k]['siteInfo']['SA_PATH']) . '</td>
							<td nowrap="nowrap"' . $bgCol . '>' . htmlspecialchars($eInfo['title']) . '</td>
							<td nowrap="nowrap"' . $bgCol . '>' . htmlspecialchars($eInfo['version']) . '</td>
							<td nowrap="nowrap"' . $bgCol . '>' . htmlspecialchars($eInfo['numberfiles']) . '</td>
							<td nowrap="nowrap"' . $bgCol . '>' . htmlspecialchars($eInfo['manual'] ? 'M' : '-') . '</td>
							<td nowrap="nowrap"' . $bgCol . '>' . htmlspecialchars($eInfo['mtime'] ? date('d-m-y H:i:s', $eInfo['mtime']) : '') . '</td>
							<td nowrap="nowrap"' . $bgCol . '>' . htmlspecialchars($eInfo['mtime_hash']) . '</td>

							<td' . $bgCol . '>' . htmlspecialchars($eInfo['TYPO3_version']) . '</td>
							<td' . $bgCol . '><img src="clear.gif" width="150" height="1" alt="" /><br />' . htmlspecialchars($eInfo['CGLcompliance'] . ($eInfo['CGLcompliance_note'] ? ' - ' . $eInfo['CGLcompliance_note'] : '')) . '</td>
						</tr>
						';
					}
					$c++;
				}
			}

				// Extensions with registered extension keys:
			$out .= '
				<h3>Registered extensions:</h3>
				<table border="1">' . $headerRow . implode('', $rows['reg']) . '</table>';

				// List of those extension keys in a form field:
			$extensionKeysCollect = array_unique($extensionKeysCollect);
			asort($extensionKeysCollect);
			$out .= '<form action=""><textarea cols="80" rows="10">' . implode(LF, $extensionKeysCollect) . '</textarea></form>';

				// USER extension (prefixed "user_")
			$out .= '<br />
				<h3>User extensions:</h3>
				<table border="1">' . $headerRow . implode('', $rows['user']) . '</table>';
		}

			// PER SITE:
		if (is_array($this->extensionInfoArray['site'])) {
			$rows = array();
			foreach ($this->extensionInfoArray['site'] as $k => $extensions) {

					// So, traverse all sites with the extension present:
				$c = 0;
				foreach ($extensions as $extKey => $eInfo) {

						// Set background color if mtime matches
					if ($eInfo['_highlight']) {
						$bgCol = $eInfo['dirtype'] == 'link' ? ' bgcolor="#ffcccc"' : ' bgcolor="#eeeeee"';
					} else {
						$bgCol = ' style="color: #999999; font-style: italic;"';
					}

						// Make row:
					$rows[] = '
					<tr>
						' . (!$c ? '<td rowspan="' . count($extensions) . '">' . htmlspecialchars($this->globalSiteInfo[$k]['siteInfo']['SA_PATH']) . '</td>' : '') . '
						<td nowrap="nowrap"' . $bgCol . '>' . htmlspecialchars($extKey) . '</td>
						<td nowrap="nowrap"' . $bgCol . '>' . htmlspecialchars($eInfo['title']) . '</td>
						<td nowrap="nowrap"' . $bgCol . '>' . htmlspecialchars($eInfo['version']) . '</td>
						<td nowrap="nowrap"' . $bgCol . '>' . htmlspecialchars($eInfo['numberfiles']) . '</td>
						<td nowrap="nowrap"' . $bgCol . '>' . htmlspecialchars($eInfo['mtime'] ? date('d-m-y H:i:s', $eInfo['mtime']) : '') . '</td>
						<td nowrap="nowrap"' . $bgCol . '>' . htmlspecialchars($eInfo['mtime_hash']) . '</td>
					</tr>
					';
					$c++;
				}
			}
			$out .= '<br />
				<h3>Sites:</h3>
				<table border="1">' . implode('', $rows) . '</table>';
		}

			// Return content
		return $out;
	}

	/**
	 * Gets information for an extension, eg. version and most-recently-edited-script
	 *
	 * @param	string		Path to local extension folder
	 * @param	string		Extension key
	 * @param	string		Key to globalSiteInformation array
	 * @return	array		Information array (unless an error occured)
	 */
	function getExtensionInfo($path, $extKey, $k) {
		$file = $path . $extKey . '/ext_emconf.php';
		if (@is_file($file)) {
			$_EXTKEY = $extKey;
			$EM_CONF = array();
			include($file);

			$eInfo = array();
				// Info from emconf:
			$eInfo['title'] = $EM_CONF[$extKey]['title'];
			$eInfo['version'] = $EM_CONF[$extKey]['version'];
			$eInfo['CGLcompliance'] = $EM_CONF[$extKey]['CGLcompliance'];
			$eInfo['CGLcompliance_note'] = $EM_CONF[$extKey]['CGLcompliance_note'];
			$eInfo['TYPO3_version'] = $EM_CONF[$extKey]['TYPO3_version'];
			$filesHash = unserialize($EM_CONF[$extKey]['_md5_values_when_last_written']);

			if (!is_array($filesHash) || count($filesHash) < 500) {

					// Get all files list (may take LOONG time):
				$extPath = $path . $extKey . '/';
				$fileArr = array();
				$fileArr = $this->removePrefixPathFromList($this->getAllFilesAndFoldersInPath($fileArr, $extPath), $extPath);

				if (!is_array($fileArr)) {
					debug(array($fileArr, $extKey, $extPath, $this->getAllFilesAndFoldersInPath(array(), $extPath)), 'ERROR');
				}

					// Number of files:
				$eInfo['numberfiles'] = count($fileArr);
				$eInfo['dirtype'] = filetype($path . $extKey);

					// Most recent modification:
				$eInfo['mtime_files'] = $this->findMostRecent($fileArr, $extPath);
				if (count($eInfo['mtime_files'])) {
					$eInfo['mtime'] = max($eInfo['mtime_files']);
				}
				$eInfo['mtime_hash'] = md5(implode(',', $eInfo['mtime_files']));
			} else {
				$eInfo['mtime_hash'] = 'No calculation done, too many files in extension: ' . count($filesHash);
			}

			$eInfo['manual'] = @is_file($path . $extKey . '/doc/manual.sxw');

			return $eInfo;
		} else {
			return 'ERROR: No emconf.php file: ' . $file;
		}
	}

	/**
	 * Recursively gather all files and folders of extension path.
	 *
	 * @param	array		Array of files to which new files are added
	 * @param	string		Path to look up files in
	 * @param	string		List of file extensions to include. Blank = all
	 * @param	boolean		If set, directories are included as well.
	 * @return	array		$fileArr with new entries added.
	 */
	function getAllFilesAndFoldersInPath($fileArr, $extPath, $extList = '', $regDirs = 0) {
		if ($regDirs) {
			$fileArr[] = $extPath;
		}
		$fileArr = array_merge($fileArr, t3lib_div::getFilesInDir($extPath, $extList, 1, 1));

		$dirs = t3lib_div::get_dirs($extPath);
		if (is_array($dirs)) {
			foreach ($dirs as $subdirs) {
				if ($subdirs && (strcmp($subdirs, 'CVS') || !$this->noCVS)) {
					$fileArr = $this->getAllFilesAndFoldersInPath($fileArr, $extPath . $subdirs . '/', $extList, $regDirs);
				}
			}
		}
		return $fileArr;
	}

	/**
	 * Creates an array with modification times of all files in $fileArr
	 *
	 * @param	array		Files in extension (rel path)
	 * @param	string		Abs path prefix for files.
	 * @return	array		Array with modification times of files (filenames are keys)
	 */
	function findMostRecent($fileArr, $extPath) {
		$mtimeArray = array();
		foreach ($fileArr as $fN) {
			if ($fN != 'ext_emconf.php') {
				$mtime = filemtime($extPath . $fN);
				$mtimeArray[$fN] = $mtime;
			}
		}
		return $mtimeArray;
	}

	/**
	 * Removes the absolute part of all files/folders in fileArr
	 *
	 * @param	array		File array
	 * @param	string		Prefix to remove
	 * @return	array		Modified file array (or error string)
	 */
	function removePrefixPathFromList($fileArr, $extPath) {
		foreach ($fileArr as $k => $absFileRef) {
			if (t3lib_div::isFirstPartOfStr($absFileRef, $extPath)) {
				$fileArr[$k] = substr($absFileRef, strlen($extPath));
			} else {
				return 'ERROR: One or more of the files was NOT prefixed with the prefix-path!';
			}
		}
		return $fileArr;
	}


	/******************************
	 *
	 * Content: Other
	 *
	 ******************************/

	/**
	 * Shows detailed information for a single installation of TYPO3
	 *
	 * @param	string		KEY pointing to installation
	 * @return	string		HTML content
	 */
	function singleSite($exp) {
		$all = $this->globalSiteInfo[$exp];

			// General information:
		$content = '
			<h2>' . htmlspecialchars($all['siteInfo']['sitename'] . ' (DB: ' . $all['siteInfo']['TYPO3_db']) . ')</h2>
			<hr />

			<h3>Main details:</h3>

			LINKS: <a href="' . htmlspecialchars($all['siteInfo']['URL']) . '" target="' . $this->targetWindow . '">Site</a> / <a href="' . htmlspecialchars($all['siteInfo']['ADMIN_URL']) . '" target="' . $this->targetWindowAdmin . '">Admin</a> / <a href="' . htmlspecialchars($all['siteInfo']['INSTALL_URL']) . '" target="' . $this->targetWindowInstall . '">Install</a>
			<br /><br />';

			// Add all information in globalSiteInfo array:
		$content .= t3lib_div::view_array($all);

			// Last-login:
		$content .= '
			<h3>Login-Log for last month:</h3>';
		$content .= $this->loginLog($all['siteInfo']['TYPO3_db']);

			// Return content
		return $content;
	}

	/**
	 * Get last-login log for database.
	 *
	 * @param	string		Database
	 * @return	string		HTML
	 */
	function loginLog($DB) {
			// Non-admin users
			//1=login, 2=logout, 3=failed login (+ errorcode 3), 4=failure_warning_email sent
		$query = $GLOBALS['TYPO3_DB']->SELECTquery(
			'sys_log.*, be_users.username  AS username, be_users.admin AS admin',
			'sys_log,be_users',
			'be_users.uid=sys_log.userid AND sys_log.type=255 AND sys_log.tstamp > ' . ($GLOBALS['EXEC_TIME'] - (60 * 60 * 24 * 30)),
			'',
			'sys_log.tstamp DESC'
		);
		$res = mysql($DB, $query);

		$dayRef = '';
		$lines = array();

		while ($row = mysql_fetch_assoc($res)) {
			$day = date('d-m-Y', $row['tstamp']);
			if ($dayRef != $day) {
				$lines[] = '
				<h4>' . $day . ':</h4>';
				$dayRef = $day;
			}
			$theLine = date('H:i', $row['tstamp']) . ':   ' . str_pad(substr($row['username'], 0, 10), 10) . '    ' . $this->log_getDetails($row['details'], unserialize($row['log_data']));
			$theLine = htmlspecialchars($theLine);
			$lines[] = $row['admin'] ? '<span class="redclass">' . $theLine . '</span>' : $theLine;
		}
		return '<pre>' . implode(LF, $lines) . '</pre>';
	}

	/**
	 * Compile log details into template string
	 *
	 * @param	string		Log message (template)
	 * @param	array		Data array to insert in log message
	 * @return	string		Log details.
	 */
	function log_getDetails($text, $data) {
			// $code is used later on to substitute errormessages with language-corrected values...
		if (is_array($data)) {
			return sprintf($text, $data[0], $data[1], $data[2], $data[3], $data[4]);
		} else {
			return $text;
		}
	}


	/**
	 * Removing temp_CACHED files for installation
	 *
	 * @param	string		KEY pointing to installation
	 * @return	string		HTML content
	 */
	function rmCachedFiles($exp) {
		$all = $this->globalSiteInfo[$exp];
		$content = '
			<h2>' . htmlspecialchars($all['siteInfo']['sitename'] . ' (DB: ' . $all['siteInfo']['TYPO3_db']) . ')</h2>
			<hr />
			<h3>typo3conf/temp_CACHED_* files:</h3>';

		$path = $all['siteInfo']['SA_PATH'] . '/typo3conf/';
		if (@is_dir($path)) {
			$filesInDir = t3lib_div::getFilesInDir($path, 'php', 1);

			foreach ($filesInDir as $kk => $vv) {
				if (t3lib_div::isFirstPartOfStr(basename($vv), 'temp_CACHED_')) {
					if (strstr(basename($vv), 'ext_localconf.php') || strstr(basename($vv), 'ext_tables.php')) {
						$content .= 'REMOVED: ' . $vv . '<br />';
						unlink($vv);
						if (file_exists($vv)) {
							$content .= $this->error('ERROR: File still exists, so could not be removed anyways!') . '<br />';
						}
					}
				}
			}
		} else {
			$content .= $this->error('ERROR: ' . $path . ' was not a directory!');
		}

		return $content;
	}

	/**
	 * Menu for either update/information, showing links for each installation found
	 *
	 * @param	string		Action key "update" or "info"
	 * @return	string		HTML output.
	 */
	function menuContent($exp) {
		if ($exp) {

				// Initialize:
			$lines = array();
			$head = '';

			foreach ($this->globalSiteInfo as $k => $all) {

					// Setting section header, if needed.
				if ($head != $all['siteInfo']['MAIN_DIR']) {
					$lines[] = '
						<h4 style="white-space: nowrap;">' . htmlspecialchars(t3lib_div::fixed_lgd_cs($all['siteInfo']['MAIN_DIR'], -18)) . '</h4>';
					$head = $all['siteInfo']['MAIN_DIR']; // Set new head...
				}

					// Display mode:
				switch ($exp) {
					case 'update':

							// Label:
						$label = $all['siteInfo']['sitename'] ? $all['siteInfo']['sitename'] : '(DB: ' . $all['siteInfo']['TYPO3_db'] . ')';
						$lines[] = '
							<hr />
							<strong>' . htmlspecialchars($label) . '</strong> (' . htmlspecialchars(substr($all['siteInfo']['SA_PATH'], strlen($all['siteInfo']['MAIN_DIR']) + 1)) . ')<br />';

							// To avoid "visited links" display on next hit:
						$tempVal = '&_someUniqueValue=' . $GLOBALS['EXEC_TIME'];

							// Add links for update:
						$url = $this->scriptName . '?type=page&show=rmTempCached&exp=' . $k . $tempVal;
						$lines[] = '<span style="white-space: nowrap;"><a href="' . htmlspecialchars($url) . '" target="TSApage">Remove temp_CACHED files</a></span>';

						$url = $all['siteInfo']['INSTALL_URL'] . 'index.php?TYPO3_INSTALL[type]=database&TYPO3_INSTALL[database_type]=import|CURRENT_STATIC' . "&presetWholeTable=1" . $tempVal . '#bottom';
						$lines[] = '<span style="white-space: nowrap;"><a href="' . htmlspecialchars($url) . '" target="TSApage">CURRENT_STATIC</a></span>';

						$url = $all['siteInfo']['INSTALL_URL'] . 'index.php?TYPO3_INSTALL[type]=database&TYPO3_INSTALL[database_type]=cmpFile|CURRENT_TABLES' . $tempVal . '#bottom';
						$lines[] = '<span style="white-space: nowrap;"><a href="' . htmlspecialchars($url) . '" target="TSApage">CURRENT_TABLES</a></span>';

							// Cache
						$url = $all['siteInfo']['INSTALL_URL'] . 'index.php?TYPO3_INSTALL[type]=database&TYPO3_INSTALL[database_type]=cache|' .
							   "&PRESET[database_clearcache][cache_pages]=1" .
							   '&PRESET[database_clearcache][cache_pagesection]=1' .
							   "&PRESET[database_clearcache][cache_hash]=1" .
							   $tempVal .
							   '#bottom';
						$lines[] = '<span style="white-space: nowrap;"><a href="' . htmlspecialchars($url) . '" target="TSApage">Clear cache</a></span>';

							// Admin link
						$url = $all['siteInfo']['ADMIN_URL'] . 'index.php';
						$lines[] = '<span style="white-space: nowrap;"><a href="' . htmlspecialchars($url) . '" target="' . $this->targetWindowAdmin . '">Admin -></a></span>';
					break;
					case 'info':
							// item
						$label = $all['siteInfo']['sitename'] ? $all['siteInfo']['sitename'] : '(DB: ' . $all['siteInfo']['TYPO3_db'] . ')';

						$url = $this->scriptName . '?type=page&show=info&exp=' . $k;
						$lines[] = '<span style="white-space: nowrap;"><a href="' . htmlspecialchars($url) . '" target="TSApage">' . htmlspecialchars($label) . '</a> (' . htmlspecialchars(substr($all['siteInfo']['SA_PATH'], strlen($all['siteInfo']['MAIN_DIR']) + 1)) . '/)</span>';
					break;
				}
			}

				// Return result.
			return implode('<br />', $lines) . '<br />';
		}
	}

	/**
	 * Create list of admin logins.
	 *
	 * @return	string		HTML table
	 */
	function makeAdminLogin() {

			// Initialize:
		$lines = array();
		$head = '';

			// Traverse installations found:
		foreach ($this->globalSiteInfo as $k => $all) {

				// Setting section header, if needed.
			if ($head != $all['siteInfo']['MAIN_DIR']) {
				$lines[] = '
					<tr>
						<td colspan="2"><br />
						<h4>' . htmlspecialchars($all['siteInfo']['MAIN_DIR']) . '</h4>
						</td>
					</tr>';
				$head = $all['siteInfo']['MAIN_DIR'];
			}

				// item
			$label = $all['siteInfo']['sitename'] ? $all['siteInfo']['sitename'] : '(DB: ' . $all['siteInfo']['TYPO3_db'] . ')';
			$unique = md5(microtime());

			$opts = array();
			$defUName = '';

			if (is_array($all['siteInfo']['ADMINS'])) {

				foreach ($all['siteInfo']['ADMINS'] as $vArr) {
					$chalVal = md5($vArr['username'] . ':' . $vArr['password'] . ':' . $unique);
					$opts[] = '<option value="' . $chalVal . '">' . htmlspecialchars($vArr['username'] . ($vArr['disable'] ? ' [DISABLED]' : '')) . '</option>';
					if (!$defUName) {
						$defUName = $vArr['username'];
					}
				}
			}
			if (count($opts) > 1) {
				$userident = '
					<select name="userident" onchange="document[\'' . $k . '\'].username.value=this.options[this.selectedIndex].text;">
						' . implode('
						', $opts) . '
					</select>
				';
			} else {
				$userident = '
					(' . $defUName . ')<br />
					<input type="hidden" name="userident" value="' . $chalVal . '" />';
			}

			$form = '
			<form name="' . $k . '" action="' . $all['siteInfo']['ADMIN_URL'] . 'index.php" target="EXTERnalWindow" method="post">
				<input type="submit" name="submit" value="Login" />
				<input type="hidden" name="username" value="' . $defUName . '" />
				<input type="hidden" name="challenge" value="' . $unique . '" />
				<input type="hidden" name="redirect_url" value="" />
				<input type="hidden" name="login_status" value="login" />
				' . trim($userident) . '
			</form>';

			$lines[] = '
				<tr>
					<td><strong>' . htmlspecialchars($label) . '</strong></td>
					<td nowrap="nowrap">' . trim($form) . '</td>
				</tr>';
		}

			// Return login table:
		return '<table border="1" cellpadding="5" cellspacing="1">' . implode('', $lines) . '</table>';
	}

	/**
	 * For for changing admin passwords
	 *
	 * @return	string		Form content.
	 */
	function changeAdminPasswordsForm() {
		$content = '';

		foreach ($this->changeAdminPasswords as $k => $p) {
			$content .= '
				<h3>' . $k . '</h3>';

			foreach ($p as $kk => $pp) {
				$content .= '<span style="white-space: nowrap;">';
				$content .= '<input type="checkbox" name="SETFIELDS[]" value="' . $pp . '" /> ' . $pp . ' - ';
				$content .= htmlspecialchars(implode(' - ', $this->collectAdminPasswords[$k][$kk]));
				$content .= '</span><br />';
			}
		}

		$content .= 'New password: <input type="text" name="NEWPASS" /><br />';
		$content .= 'New password (md5): <input type="text" name="NEWPASS_md5" /><br />
			(This overrules any plain password above!)
		<br />';
		$content = '
		<form action="' . htmlspecialchars($this->scriptName . '?type=page&show=admin') . '" method="post">
		' . $content . '
		<input type="submit" name="Set" />
		</form>
		';

		return $content;
	}

	/**
	 * Setting new passwords
	 *
	 * @return	string		Status
	 * @see changeAdminPasswordsForm()
	 */
	function setNewPasswords() {
		$whichFields = t3lib_div::_POST('SETFIELDS');
		$pass = trim(t3lib_div::_POST('NEWPASS'));
		$passMD5 = t3lib_div::_POST('NEWPASS_md5');

		$content = '';
		$updatedFlag = 0;
		if (($pass || $passMD5) && is_array($whichFields)) {
			$pass = $passMD5 ? $passMD5 : md5($pass);

			foreach ($whichFields as $values) {
				$parts = explode(':', $values);
				if (count($parts) > 2) {
					$key = $this->mapDBtoKey[$parts[0]];
					if ($key && isset($this->globalSiteInfo[$key]['siteInfo'])) {
						$error = $this->connectToDatabase($this->globalSiteInfo[$key]['siteInfo']);
						if (!$error) {
							$DB = $this->globalSiteInfo[$key]['siteInfo']['TYPO3_db'];
							$content .= '<h3>Updating ' . $DB . ':</h3>';

							$query = $GLOBALS['TYPO3_DB']->UPDATEquery(
								'be_users',
								'uid=' . intval($parts[1]) . ' AND username="' . addslashes($parts[2]) . '" AND admin!=0',
								array('password' => $pass)
							); // username/admin are added to security. But they are certainly redundant!!
							mysql($DB, $query);

							$content .= 'Affected rows: ' . mysql_affected_rows() . '<br /><hr />';
							$updatedFlag = '1';
						}
					}
				}
			}
		}

		$this->initProcess();
		return $content;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_superadmin.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_superadmin.php']);
}

?>