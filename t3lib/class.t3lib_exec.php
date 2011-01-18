<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2002-2011 René Fritz (r.fritz@colorcube.de)
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * t3lib_exec finds executables (programs) on Unix and Windows without knowing where they are
 *
 * $Id$
 *
 * @author	René Fritz <r.fritz@colorcube.de>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   85: class t3lib_exec
 *   95:	 function checkCommand($cmd, $handler='')
 *  166:	 function getCommand($cmd, $handler='', $handlerOpt='')
 *  199:	 function addPaths($paths)
 *  211:	 function getPaths($addInvalid=false)
 *  237:	 function _init()
 *  259:	 function _initPaths($paths='')
 *  312:	 function _getConfiguredApps()
 *  339:	 function _getPaths()
 *  400:	 function _fixPath($path)
 *
 * TOTAL FUNCTIONS: 9
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * returns exec command for a program
 * or false
 *
 * This class is meant to be used without instance:
 * $cmd = t3lib_exec::getCommand ('awstats','perl');
 *
 * The data of this class is cached.
 * That means if a program is found once it don't have to be searched again.
 *
 * user functions:
 *
 * addPaths() could be used to extend the search paths
 * getCommand() get a command string
 * checkCommand() returns true if a command is available
 *
 * Search paths that are included:
 * $TYPO3_CONF_VARS['GFX']['im_path_lzw'] or $TYPO3_CONF_VARS['GFX']['im_path']
 * $TYPO3_CONF_VARS['SYS']['binPath']
 * $GLOBALS['_SERVER']['PATH']
 * '/usr/bin/,/usr/local/bin/' on Unix
 *
 * binaries can be preconfigured with
 * $TYPO3_CONF_VARS['SYS']['binSetup']
 *
 * @author		René Fritz <r.fritz@colorcube.de>
 * @package		TYPO3
 * @subpackage	t3lib
 */
class t3lib_exec {

	/** Tells if object is already initialized */
	protected static $initialized = FALSE;

	/**
	 * Contains application list. This is an array with the following structure:
	 * - app => file name to the application (like 'tar' or 'bzip2')
	 * - path => full path to the application without application name (like '/usr/bin/' for '/usr/bin/tar')
	 * - valid => true or false
	 * Array key is identical to 'app'.
	 *
	 * @var	array
	 */
	protected static $applications = array();

	/**
	 * Paths where to search for applications
	 *
	 * @var	array
	 */
	protected static $paths = NULL;

	/**
	 * Checks if a command is valid or not, updates global variables
	 *
	 * @param	string		the command that should be executed. eg: "convert"
	 * @param	string		executer for the command. eg: "perl"
	 * @return	boolean		false if cmd is not found, or -1 if the handler is not found
	 */
	public static function checkCommand($cmd, $handler = '') {
		if (!self::init()) {
			return FALSE;
		}

		if ($handler && !self::checkCommand($handler)) {
			return -1;
		}
			// Already checked and valid
		if (self::$applications[$cmd]['valid']) {
			return TRUE;
		}
			// Is set but was (above) not true
		if (isset(self::$applications[$cmd]['valid'])) {
			return FALSE;
		}

		foreach (self::$paths as $path => $validPath) {
				// Ignore invalid (false) paths
			if ($validPath) {
				if (TYPO3_OS == 'WIN') {
						// Windows OS
						// TODO Why is_executable() is not called here?
					if (@is_file($path . $cmd)) {
						self::$applications[$cmd]['app'] = $cmd;
						self::$applications[$cmd]['path'] = $path;
						self::$applications[$cmd]['valid'] = TRUE;
						return TRUE;
					}
					if (@is_file($path . $cmd . '.exe')) {
						self::$applications[$cmd]['app'] = $cmd . '.exe';
						self::$applications[$cmd]['path'] = $path;
						self::$applications[$cmd]['valid'] = TRUE;
						return TRUE;
					}
				} else {
						// Unix-like OS
					$filePath = realpath($path . $cmd);
					if ($filePath && @is_executable($filePath)) {
						self::$applications[$cmd]['app'] = $cmd;
						self::$applications[$cmd]['path'] = $path;
						self::$applications[$cmd]['valid'] = TRUE;
						return TRUE;
					}
				}
			}
		}

			// Try to get the executable with the command 'which'.
			// It does the same like already done, but maybe on other paths
		if (TYPO3_OS != 'WIN') {
			$cmd = @t3lib_utility_Command::exec('which ' . $cmd);
			if (@is_executable($cmd)) {
				self::$applications[$cmd]['app'] = $cmd;
				self::$applications[$cmd]['path'] = dirname($cmd) . '/';
				self::$applications[$cmd]['valid'] = TRUE;
				return TRUE;
			}
		}

		return FALSE;
	}


	/**
	 * Returns a command string for exec(), system()
	 *
	 * @param	string		the command that should be executed. eg: "convert"
	 * @param	string		handler (executor) for the command. eg: "perl"
	 * @param	string		options for the handler, like '-w' for "perl"
	 * @return	mixed		returns command string, or false if cmd is not found, or -1 if the handler is not found
	 */
	public static function getCommand($cmd, $handler = '', $handlerOpt = '') {
		if (!self::init()) {
			return FALSE;
		}

			// handler
		if ($handler) {
			$handler = self::getCommand($handler);

			if (!$handler) {
				return -1;
			}
			$handler .= ' ' . $handlerOpt . ' ';
		}

			// command
		if (!self::checkCommand($cmd)) {
			return FALSE;
		}
		$cmd = self::$applications[$cmd]['path'] . self::$applications[$cmd]['app'] . ' ';

		return trim($handler . $cmd);
	}


	/**
	 * Extend the preset paths. This way an extension can install an executable and provide the path to t3lib_exec.
	 *
	 * @param	string		comma separated list of extra paths where a command should be searched. Relative paths (without leading "/") are prepend with site root path (PATH_site).
	 * @return	void
	 */
	public static function addPaths($paths) {
		self::initPaths($paths);
	}


	/**
	 * Returns an array of search paths
	 *
	 * @param	boolean		If set the array contains invalid path too. Then the key is the path and the value is empty
	 * @return	array		Array of search paths (empty if exec is disabled)
	 */
	public static function getPaths($addInvalid = FALSE) {
		if (!self::init()) {
			return array();
		}

		$paths = self::$paths;

		if (!$addInvalid) {
			foreach ($paths as $path => $validPath) {
				if (!$validPath) {
					unset($paths[$path]);
				}
			}
		}
		return $paths;
	}


	/**
	 * Initializes this class
	 *
	 * @return	void
	 */
	protected static function init() {
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['disable_exec_function']) {
			return FALSE;
		}
		if (!self::$initialized) {
			self::initPaths();
			self::$applications = self::getConfiguredApps();
			self::$initialized = TRUE;
		}
		return TRUE;
	}


	/**
	 * Initializes and extends the preset paths with own
	 *
	 * @param	string		Comma seperated list of extra paths where a command should be searched. Relative paths (without leading "/") are prepend with site root path (PATH_site).
	 * @return	void
	 */
	protected static function initPaths($paths = '') {
		$doCheck = FALSE;

			// init global paths array if not already done
		if (!is_array(self::$paths)) {
			self::$paths = self::getPathsInternal();
			$doCheck = TRUE;
		}
			// merge the submitted paths array to the global
		if ($paths) {
			$paths = t3lib_div::trimExplode(',', $paths, 1);
			if (is_array($paths)) {
				foreach ($paths as $path) {
						// make absolute path of relative
					if (!preg_match('#^/#', $path)) {
						$path = PATH_site . $path;
					}
					if (!isset(self::$paths[$path])) {
						if (@is_dir($path)) {
							self::$paths[$path] = $path;
						} else {
							self::$paths[$path] = FALSE;
						}
					}
				}
			}
		}
			// check if new paths are invalid
		if ($doCheck) {
			foreach (self::$paths as $path => $valid) {
					// ignore invalid (false) paths
				if ($valid AND !@is_dir($path)) {
					self::$paths[$path] = FALSE;
				}
			}
		}
	}


	/**
	 * Processes and returns the paths from $GLOBALS['TYPO3_CONF_VARS']['SYS']['binSetup']
	 *
	 * @return	array	Array of commands and path
	 */
	protected static function getConfiguredApps() {
		$cmdArr = array();

		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['binSetup']) {
			$pathSetup = preg_split('/[\n,]+/', $GLOBALS['TYPO3_CONF_VARS']['SYS']['binSetup']);
			foreach ($pathSetup as $val) {
				list($cmd, $cmdPath) = t3lib_div::trimExplode('=', $val, 1);
				$cmdArr[$cmd]['app'] = basename($cmdPath);
				$cmdArr[$cmd]['path'] = dirname($cmdPath) . '/';
				$cmdArr[$cmd]['valid'] = TRUE;
			}
		}

		return $cmdArr;
	}


	/**
	 * Sets the search paths from different sources, internal
	 *
	 * @return	array		Array of absolute paths (keys and values are equal)
	 */
	protected static function getPathsInternal() {

		$pathsArr = array();
		$sysPathArr = array();

			// image magick paths first
			// im_path_lzw take precedence over im_path
		if (($imPath = ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw'] ? $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw'] : $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path']))) {
			$imPath = self::fixPath($imPath);
			$pathsArr[$imPath] = $imPath;
		}

			// add configured paths
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['binPath']) {
			$sysPath = t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['SYS']['binPath'], 1);
			foreach ($sysPath as $val) {
				$val = self::fixPath($val);
				$sysPathArr[$val] = $val;
			}
		}


			// add path from environment
			// TODO: how does this work for WIN
		if ($GLOBALS['_SERVER']['PATH']) {
			$sep = (TYPO3_OS == 'WIN' ? ';' : ':');
			$envPath = t3lib_div::trimExplode($sep, $GLOBALS['_SERVER']['PATH'], 1);
			foreach ($envPath as $val) {
				$val = self::fixPath($val);
				$sysPathArr[$val] = $val;
			}
		}

			// Set common paths for Unix (only)
		if (TYPO3_OS !== 'WIN') {
			$sysPathArr = array_merge($sysPathArr, array(
				'/usr/bin/' => '/usr/bin/',
				'/usr/local/bin/' => '/usr/local/bin/',
			));
		}

		$pathsArr = array_merge($pathsArr, $sysPathArr);

		return $pathsArr;
	}


	/**
	 * Set a path to the right format
	 *
	 * @param	string		Input path
	 * @return	string		Output path
	 */
	protected static function fixPath($path) {
		return str_replace('//', '/', $path . '/');
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_exec.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_exec.php']);
}
?>