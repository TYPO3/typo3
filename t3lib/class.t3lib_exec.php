<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2002-2003 René Fritz (r.fritz@colorcube.de)
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
 * t3lib_exec find executables (programs) on unix and windows without knowing where they are
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
 *   81: class t3lib_exec 
 *   91:     function checkCommand($cmd, $handler='')	
 *  162:     function getCommand($cmd, $handler='', $handlerOpt='')	
 *  191:     function addPaths($paths)	
 *  201:     function _getPaths()	
 *  269:     function _init()	
 *  285:     function _initPaths($paths='')	
 *  340:     function _getOS()	
 *  351:     function _fixPath($path)	
 *
 * TOTAL FUNCTIONS: 8
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
 * @author	René Fritz <r.fritz@colorcube.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_exec {

	/**
	 * checks if a command is valid or not
	 * updates global vars
	 * 
	 * @param	string		the command that should be executed. eg: "convert"
	 * @param	string		executer for the command. eg: "perl"
	 * @return	boolean		false if cmd is not found, or -1 if the handler is not found
	 */
	function checkCommand($cmd, $handler='')	{

		t3lib_exec::_init();
		$osType = t3lib_exec::_getOS();


#debug($GLOBALS['t3lib_exec'], 't3lib_exec', __LINE__, __FILE__);

		if ($handler && !t3lib_exec::checkCommand($handler)) {
			return -1;
		}
			// already checked and valid
		if ($GLOBALS['t3lib_exec']['apps'][$cmd]['valid']) {
			return true;
		}
			// is set but was (above) not true
		if (isset($GLOBALS['t3lib_exec']['apps'][$cmd]['valid'])) {
			return false;
		}
		
		reset($GLOBALS['t3lib_exec']['paths']);
		foreach($GLOBALS['t3lib_exec']['paths'] as $path => $validPath) {
				// ignore invalid (false) paths
			if ($validPath) {
				if ($osType=='WIN') {
					if (@is_file($path.$cmd)) {
						$GLOBALS['t3lib_exec']['apps'][$cmd]['app'] = $cmd;
						$GLOBALS['t3lib_exec']['apps'][$cmd]['path'] = $path;
						$GLOBALS['t3lib_exec']['apps'][$cmd]['valid'] = true;
						return true;
					}
					if (@is_file($path.$cmd.'.exe')) {
						$GLOBALS['t3lib_exec']['apps'][$cmd]['app'] = $cmd.'.exe';
						$GLOBALS['t3lib_exec']['apps'][$cmd]['path'] = $path;
						$GLOBALS['t3lib_exec']['apps'][$cmd]['valid'] = true;
						return true;
					}
				} else { // UNIX
					if (@is_executable($path.$cmd)) {
						$GLOBALS['t3lib_exec']['apps'][$cmd]['app'] = $cmd;
						$GLOBALS['t3lib_exec']['apps'][$cmd]['path'] = $path;
						$GLOBALS['t3lib_exec']['apps'][$cmd]['valid'] = true;
						return true;
					}
				}
			}
		}

			// try to get the executable with the command 'which'. It do the same like already done, but maybe on other paths??
		if ($osType=='UNIX') {
			$cmd = @exec ('which '.$val['cmd']);

			if (@is_executable($cmd)) {
				$GLOBALS['t3lib_exec']['apps'][$cmd]['app'] = $cmd;
				$GLOBALS['t3lib_exec']['apps'][$cmd]['path'] = dirname($cmd).'/';
				$GLOBALS['t3lib_exec']['apps'][$cmd]['valid'] = true;
				return true;
			}
		}

		return false;
	}
	
	/**
	 * returns a command string for exec(), system()
	 * 
	 * @param	string		the command that should be executed. eg: "convert"
	 * @param	string		handler (executor) for the command. eg: "perl"
	 * @param	string		options for the handler, like '-w' for "perl"
	 * @return	mixed		returns command string, or false if cmd is not found, or -1 if the handler is not found
	 */
	function getCommand($cmd, $handler='', $handlerOpt='')	{

		t3lib_exec::_init();

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
		$cmd = $GLOBALS['t3lib_exec']['apps'][$cmd]['path'].$GLOBALS['t3lib_exec']['apps'][$cmd]['app'].' ';

		return $handler.$cmd;
	}

	/**
	 * Extend the preset paths. This way an extension can install an axecutable and provide the path to t3lib_exec.
	 * 
	 * @param	string		comma seperated list of extra paths where a command should be searched. Relative paths (without leading "/") are prepend with site root path (PATH_site).
	 * @return	[type]		...
	 */
	function addPaths($paths)	{
		t3lib_exec::_initPaths($paths);
	}

	/**
	 * set the search paths from different sources
	 * 
	 * @return	[type]		...
	 * @internal
	 */
	function _getPaths()	{
		global $TYPO3_CONF_VARS;

		$pathsArr = array();
		$sysPathArr = array();
		$osType = t3lib_exec::_getOS();

			// image magick paths first
			// im_path_lzw take precedence over im_path
		if ($imPath = ($TYPO3_CONF_VARS['GFX']['im_path_lzw'] ? $TYPO3_CONF_VARS['GFX']['im_path_lzw'] : $TYPO3_CONF_VARS['GFX']['im_path'])) {
			$imPath = t3lib_exec::_fixPath($imPath);
			$pathsArr[$imPath] = $imPath;
		}

			// add configured paths
		if ($TYPO3_CONF_VARS['SYS']['binPath']) {
			$sysPath = t3lib_div::trimExplode(',',$TYPO3_CONF_VARS['SYS']['binPath'],1);
			reset($sysPath);
			while(list(,$val)=each($sysPath)) {
				$val = t3lib_exec::_fixPath($val);
				$sysPathArr[$val]=$val;
			}
		}


		

# ???? t3lib_div::getIndpEnv('REQUEST_URI');


			// add path from environment
#TODO: how does this work for WIN
		if ($GLOBALS['_SERVER']['PATH']) {
			$sep = ($osType=='WIN') ? ';' : ':';
			$envPath = t3lib_div::trimExplode($sep,$GLOBALS['_SERVER']['PATH'],1);
			reset($envPath);
			while(list(,$val)=each($envPath)) {
				$val = t3lib_exec::_fixPath($val);
				$sysPathArr[$val]=$val;
			}
		}

		if ($osType=='WIN') {
#TODO: add the most common paths for WIN
			$sysPathArr = array_merge($sysPathArr, array (
				'/usr/bin/' => '/usr/bin/',
				'/perl/bin/' => '/perl/bin/',
			));
		} else { // UNIX
			$sysPathArr = array_merge($sysPathArr, array (
				'/usr/bin/' => '/usr/bin/',
				'/usr/local/bin/' => '/usr/local/bin/',
			));
		}

#debug($pathsArr, '$pathsArr', __LINE__, __FILE__);
#debug($GLOBALS['_SERVER']['PATH'], 'PATH', __LINE__, __FILE__);

		$pathsArr = array_merge($pathsArr, $sysPathArr);
		return $pathsArr;
	}

	/**
	 * init
	 * 
	 * @return	[type]		...
	 * @internal
	 */
	function _init()	{
		if (!$GLOBALS['t3lib_exec']['init']) {

			t3lib_exec::_initPaths();
			$GLOBALS['t3lib_exec']['apps'] = array();
			$GLOBALS['t3lib_exec']['init'] = true;
		}
	}

	/**
	 * init and extend the preset paths with own
	 * 
	 * @param	string		comma seperated list of extra paths where a command should be searched. Relative paths (without leading "/") are prepend with site root path (PATH_site).
	 * @return	[type]		...
	 * @internal
	 */
	function _initPaths($paths='')	{
		$doCeck=false;

			// init global paths array if not already done
		if (!is_array($GLOBALS['t3lib_exec']['paths'])) {
			$GLOBALS['t3lib_exec']['paths'] = t3lib_exec::_getPaths();
			$doCeck=true;
		}
			// merge the submitted paths array to the global
		if ($paths) {
			$paths = t3lib_div::trimExplode(',',$paths,1);
			if (is_array($paths)) {
				reset($paths);
				while(list(,$path)=each($paths)) {
						// make absolute path of relative
					if (!preg_match('#^/#',$path)) {
						$path = PATH_site.$path;
					}
					if (!isset($GLOBALS['t3lib_exec']['paths'][$path])) {
						if (@is_dir($path)) {
							$GLOBALS['t3lib_exec']['paths'][$path] = $path;
							$GLOBALS['t3lib_exec']['allPaths'].=','.$path;
							// $doCeck=true; just done
						} else {
							$GLOBALS['t3lib_exec']['paths'][$path] = false;
						}
					}
				}
			}
		}
			// check if new paths are invalid
		if ($doCeck) {
			$GLOBALS['t3lib_exec']['allPaths']='';
			reset($GLOBALS['t3lib_exec']['paths']);
			while(list($path,$valid)=each($GLOBALS['t3lib_exec']['paths'])) {
					// ignore invalid (false) paths
#TODO: what's the idea not to remove invalid paths?
				if ($valid) {
					if (!@is_dir($path)) {
						$GLOBALS['t3lib_exec']['paths'][$path] = false;
					}
				}
				if ($path = $GLOBALS['t3lib_exec']['paths'][$path]) {
					$GLOBALS['t3lib_exec']['allPaths'].=','.$path;
				}
			}
		}
	}

	/**
	 * returns on which OS we're runing
	 * 
	 * @return	string		the OS type: "UNIX" or "WIN"
	 * @internal
	 */
	function _getOS()	{
		return stristr(PHP_OS,'win')&&!stristr(PHP_OS,'darwin')?'WIN':'UNIX';
	}

	/**
	 * set a path to the right format
	 * 
	 * @param	string		path
	 * @return	string		path
	 * @internal
	 */
	function _fixPath($path)	{
		return str_replace ('//',"/",$path.'/');
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_exec.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_exec.php']);
}
?>


