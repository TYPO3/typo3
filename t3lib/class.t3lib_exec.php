<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2002-2009 René Fritz (r.fritz@colorcube.de)
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
 *   95:     function checkCommand($cmd, $handler='')
 *  166:     function getCommand($cmd, $handler='', $handlerOpt='')
 *  199:     function addPaths($paths)
 *  211:     function getPaths($addInvalid=false)
 *  237:     function _init()
 *  259:     function _initPaths($paths='')
 *  312:     function _getConfiguredApps()
 *  339:     function _getPaths()
 *  400:     function _fixPath($path)
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
 * The data of this class is hold in a global variable. Doing it this way the setup is cached.
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
 * @author	René Fritz <r.fritz@colorcube.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_exec {

	/**
	 * Checks if a command is valid or not
	 * updates global vars
	 *
	 * @param	string		the command that should be executed. eg: "convert"
	 * @param	string		executer for the command. eg: "perl"
	 * @return	boolean		false if cmd is not found, or -1 if the handler is not found
	 */
	function checkCommand($cmd, $handler='')	{
		global $T3_VAR;

		if (!t3lib_exec::_init()) {
			return false;
		}


		if ($handler && !t3lib_exec::checkCommand($handler)) {
			return -1;
		}
			// already checked and valid
		if ($T3_VAR['t3lib_exec']['apps'][$cmd]['valid']) {
			return true;
		}
			// is set but was (above) not true
		if (isset($T3_VAR['t3lib_exec']['apps'][$cmd]['valid'])) {
			return false;
		}

		foreach($T3_VAR['t3lib_exec']['paths'] as $path => $validPath) {
				// ignore invalid (false) paths
			if ($validPath) {
				if (TYPO3_OS=='WIN') {
					if (@is_file($path.$cmd)) {
						$T3_VAR['t3lib_exec']['apps'][$cmd]['app'] = $cmd;
						$T3_VAR['t3lib_exec']['apps'][$cmd]['path'] = $path;
						$T3_VAR['t3lib_exec']['apps'][$cmd]['valid'] = true;
						return true;
					}
					if (@is_file($path.$cmd.'.exe')) {
						$T3_VAR['t3lib_exec']['apps'][$cmd]['app'] = $cmd.'.exe';
						$T3_VAR['t3lib_exec']['apps'][$cmd]['path'] = $path;
						$T3_VAR['t3lib_exec']['apps'][$cmd]['valid'] = true;
						return true;
					}
				} else { // UNIX
					if (@is_executable($path.$cmd)) {
						$T3_VAR['t3lib_exec']['apps'][$cmd]['app'] = $cmd;
						$T3_VAR['t3lib_exec']['apps'][$cmd]['path'] = $path;
						$T3_VAR['t3lib_exec']['apps'][$cmd]['valid'] = true;
						return true;
					}
				}
			}
		}

			// try to get the executable with the command 'which'. It do the same like already done, but maybe on other paths??
		if (TYPO3_OS!='WIN') {
			$cmd = @exec ('which '.$cmd);

			if (@is_executable($cmd)) {
				$T3_VAR['t3lib_exec']['apps'][$cmd]['app'] = $cmd;
				$T3_VAR['t3lib_exec']['apps'][$cmd]['path'] = dirname($cmd).'/';
				$T3_VAR['t3lib_exec']['apps'][$cmd]['valid'] = true;
				return true;
			}
		}

		return false;
	}


	/**
	 * Returns a command string for exec(), system()
	 *
	 * @param	string		the command that should be executed. eg: "convert"
	 * @param	string		handler (executor) for the command. eg: "perl"
	 * @param	string		options for the handler, like '-w' for "perl"
	 * @return	mixed		returns command string, or false if cmd is not found, or -1 if the handler is not found
	 */
	function getCommand($cmd, $handler='', $handlerOpt='')	{
		global $T3_VAR;

		if (!t3lib_exec::_init()) {
			return false;
		}

			// handler
		if ($handler) {
			$handler = t3lib_exec::getCommand($handler);

			if (!$handler) {
				return -1;
			}
			$handler .= ' '.$handlerOpt.' ';
		}

			// command
		if (!t3lib_exec::checkCommand($cmd)) {
			return false;
		}
		$cmd = $T3_VAR['t3lib_exec']['apps'][$cmd]['path'].$T3_VAR['t3lib_exec']['apps'][$cmd]['app'].' ';

		return trim($handler.$cmd);
	}


	/**
	 * Extend the preset paths. This way an extension can install an executable and provide the path to t3lib_exec.
	 *
	 * @param	string		comma seperated list of extra paths where a command should be searched. Relative paths (without leading "/") are prepend with site root path (PATH_site).
	 * @return	void
	 */
	function addPaths($paths)	{
		t3lib_exec::_initPaths($paths);
	}



	/**
	 * Returns an array of search paths
	 *
	 * @param	boolean		If set the array contains invalid path too. Then the key is the path and the value is empty
	 * @return	array		Array of search paths (empty if exec is disabled)
	 */
	function getPaths($addInvalid=false)	{
		global $T3_VAR;

		if (!t3lib_exec::_init()) {
			return array();
		}

		$paths = $T3_VAR['t3lib_exec']['paths'];
		if(!$addInvalid) {

			foreach($paths as $path => $validPath) {
				if(!$validPath) {
					unset($paths);
				}
			}
		}
		return $paths;
	}


	/**
	 * Initialization, internal
	 *
	 * @return	void
	 * @internal
	 */
	function _init()	{
		global $T3_VAR, $TYPO3_CONF_VARS;

		if ($TYPO3_CONF_VARS['BE']['disable_exec_function']) {
			return false;
		}
		if (!$T3_VAR['t3lib_exec']['init']) {
			t3lib_exec::_initPaths();
			$T3_VAR['t3lib_exec']['apps'] = t3lib_exec::_getConfiguredApps();;
			$T3_VAR['t3lib_exec']['init'] = true;
		}
		return true;
	}


	/**
	 * Init and extend the preset paths with own
	 *
	 * @param	string		Comma seperated list of extra paths where a command should be searched. Relative paths (without leading "/") are prepend with site root path (PATH_site).
	 * @return	void
	 * @internal
	 */
	function _initPaths($paths='')	{
		global $T3_VAR;

		$doCeck=false;

			// init global paths array if not already done
		if (!is_array($T3_VAR['t3lib_exec']['paths'])) {
			$T3_VAR['t3lib_exec']['paths'] = t3lib_exec::_getPaths();
			$doCeck=true;
		}
			// merge the submitted paths array to the global
		if ($paths) {
			$paths = t3lib_div::trimExplode(',',$paths,1);
			if (is_array($paths)) {
				foreach($paths as $path) {
						// make absolute path of relative
					if (!preg_match('#^/#',$path)) {
						$path = PATH_site.$path;
					}
					if (!isset($T3_VAR['t3lib_exec']['paths'][$path])) {
						if (@is_dir($path)) {
							$T3_VAR['t3lib_exec']['paths'][$path] = $path;
							$T3_VAR['t3lib_exec']['allPaths'].=','.$path;
							// $doCeck=true; just done
						} else {
							$T3_VAR['t3lib_exec']['paths'][$path] = false;
						}
					}
				}
			}
		}
			// check if new paths are invalid
		if ($doCeck) {
			$T3_VAR['t3lib_exec']['allPaths']='';
			foreach($T3_VAR['t3lib_exec']['paths'] as $path => $valid) {
					// ignore invalid (false) paths
				if ($valid AND !@is_dir($path)) {
					$T3_VAR['t3lib_exec']['paths'][$path] = false;
				}
				if ($path = $T3_VAR['t3lib_exec']['paths'][$path]) {
					$T3_VAR['t3lib_exec']['allPaths'].=','.$path;
				}
			}
		}
	}


	/**
	 * Processes and returns the paths from $TYPO3_CONF_VARS['SYS']['binSetup']
	 *
	 * @return	array		Array of commands and path
	 * @internal
	 */
	function _getConfiguredApps()	{
		global $TYPO3_CONF_VARS;

		$cmdArr = array();

		if ($TYPO3_CONF_VARS['SYS']['binSetup']) {
			$pathSetup = implode("\n", t3lib_div::trimExplode(',',$TYPO3_CONF_VARS['SYS']['binSetup'],1));
			$pathSetup = t3lib_div::trimExplode("\n",$pathSetup,1);
			foreach($pathSetup as $val) {
				list($cmd, $cmdPath) = t3lib_div::trimExplode('=',$val,1);

				$cmdArr[$cmd]['app'] = basename($cmdPath);
				$cmdArr[$cmd]['path'] = dirname($cmdPath).'/';
				$cmdArr[$cmd]['valid'] = true;
			}
		}

		return $cmdArr;
	}


	/**
	 * Set the search paths from different sources, internal
	 *
	 * @return	array		Array of absolute paths (keys and values are equal)
	 * @internal
	 */
	function _getPaths()	{
		global $T3_VAR, $TYPO3_CONF_VARS;

		$pathsArr = array();
		$sysPathArr = array();

			// image magick paths first
			// im_path_lzw take precedence over im_path
		if ($imPath = ($TYPO3_CONF_VARS['GFX']['im_path_lzw'] ? $TYPO3_CONF_VARS['GFX']['im_path_lzw'] : $TYPO3_CONF_VARS['GFX']['im_path'])) {
			$imPath = t3lib_exec::_fixPath($imPath);
			$pathsArr[$imPath] = $imPath;
		}

			// add configured paths
		if ($TYPO3_CONF_VARS['SYS']['binPath']) {
			$sysPath = t3lib_div::trimExplode(',',$TYPO3_CONF_VARS['SYS']['binPath'],1);
			foreach($sysPath as $val) {
				$val = t3lib_exec::_fixPath($val);
				$sysPathArr[$val]=$val;
			}
		}


			// add path from environment
// TODO: how does this work for WIN
		if ($GLOBALS['_SERVER']['PATH']) {
			$sep = (TYPO3_OS=='WIN') ? ';' : ':';
			$envPath = t3lib_div::trimExplode($sep,$GLOBALS['_SERVER']['PATH'],1);
			foreach($envPath as $val) {
				$val = t3lib_exec::_fixPath($val);
				$sysPathArr[$val]=$val;
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
	 * @internal
	 */
	function _fixPath($path)	{
		return str_replace ('//','/',$path.'/');
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_exec.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_exec.php']);
}
?>