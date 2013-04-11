<?php
namespace TYPO3\CMS\Backend\Module;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * This document provides a class that loads the modules for the TYPO3 interface.
 *
 * Load Backend Interface modules
 *
 * Typically instantiated like this:
 * $this->loadModules = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Backend\Module\ModuleLoader');
 * $this->loadModules->load($TBE_MODULES);
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @internal
 */
class ModuleLoader {

	// After the init() function this array will contain the structure of available modules for the backend user.
	/**
	 * @todo Define visibility
	 */
	public $modules = array();

	// Array with paths pointing to the location of modules from extensions
	/**
	 * @todo Define visibility
	 */
	public $absPathArray = array();

	// This array will hold the elements that should go into the select-list of modules for groups...
	/**
	 * @todo Define visibility
	 */
	public $modListGroup = array();

	// This array will hold the elements that should go into the select-list of modules for users...
	/**
	 * @todo Define visibility
	 */
	public $modListUser = array();

	/**
	 * The backend user for use internally
	 *
	 * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 * @todo Define visibility
	 */
	public $BE_USER;

	// If set TRUE, workspace "permissions" will be observed so non-allowed modules will not be included in the array of modules.
	/**
	 * @todo Define visibility
	 */
	public $observeWorkspaces = FALSE;

	/**
	 * Contains the registered navigation components
	 *
	 * @var array
	 */
	protected $navigationComponents = array();

	/**
	 * Init.
	 * The outcome of the load() function will be a $this->modules array populated with the backend module structure available to the BE_USER
	 * Further the global var $LANG will have labels and images for the modules loaded in an internal array.
	 *
	 * @param array $modulesArray Should be the global var $TBE_MODULES, $BE_USER can optionally be set to an alternative Backend user object than the global var $BE_USER (which is the currently logged in user)
	 * @param object $BE_USER Optional backend user object to use. If not set, the global BE_USER object is used.
	 * @return void
	 * @todo Define visibility
	 */
	public function load($modulesArray, $BE_USER = '') {
		// Setting the backend user for use internally
		if (is_object($BE_USER)) {
			$this->BE_USER = $BE_USER;
		} else {
			$this->BE_USER = $GLOBALS['BE_USER'];
		}
		/*$modulesArray might look like this when entering this function.
		Notice the two modules added by extensions - they have a path attachedArray
		(
		[web] => list,info,perm,func
		[file] => list
		[user] =>
		[tools] => em,install,txphpmyadmin
		[help] => about
		[_PATHS] => Array
		(
		[tools_install] => /www/htdocs/typo3/32/coreinstall/typo3/ext/install/mod/
		[tools_txphpmyadmin] => /www/htdocs/typo3/32/coreinstall/typo3/ext/phpmyadmin/modsub/
		))
		 */
		$this->absPathArray = $modulesArray['_PATHS'];
		unset($modulesArray['_PATHS']);
		// Unset the array for calling external backend module dispatchers in typo3/mod.php
		unset($modulesArray['_dispatcher']);
		// Unset the array for calling backend modules based on external backend module dispatchers in typo3/mod.php
		unset($modulesArray['_configuration']);
		$this->navigationComponents = $modulesArray['_navigationComponents'];
		unset($modulesArray['_navigationComponents']);
		$theMods = $this->parseModulesArray($modulesArray);
		// Originally modules were found in typo3/mod/
		// User defined modules were found in ../typo3conf/
		// Today almost all modules reside in extensions and they are found by the _PATHS array of the incoming $TBE_MODULES array
		// Setting paths for 1) core modules (old concept from mod/) and 2) user-defined modules (from ../typo3conf)
		$paths = array();
		// Path of static modules
		$paths['defMods'] = PATH_typo3 . 'mod/';
		// Local modules (maybe frontend specific)
		$paths['userMods'] = PATH_typo3 . '../typo3conf/';
		// Traverses the module setup and creates the internal array $this->modules
		foreach ($theMods as $mods => $subMod) {
			$path = NULL;
			$extModRelPath = $this->checkExtensionModule($mods);
			// EXTENSION module:
			if ($extModRelPath) {
				$theMainMod = $this->checkMod($mods, PATH_site . $extModRelPath);
				if (is_array($theMainMod) || $theMainMod != 'notFound') {
					// ... just so it goes on... submodules cannot be within this path!
					$path = 1;
				}
			} else {
				// 'CLASSIC' module
				// Checking for typo3/mod/ module existence...
				$theMainMod = $this->checkMod($mods, $paths['defMods'] . $mods);
				if (is_array($theMainMod) || $theMainMod != 'notFound') {
					$path = $paths['defMods'];
				} else {
					// If not typo3/mod/ then it could be user-defined in typo3conf/ ...?
					$theMainMod = $this->checkMod($mods, $paths['userMods'] . $mods);
					if (is_array($theMainMod) || $theMainMod != 'notFound') {
						$path = $paths['userMods'];
					}
				}
			}
			// If $theMainMod is not set (FALSE) there is no access to the module !(?)
			if ($theMainMod && !is_null($path)) {
				$this->modules[$mods] = $theMainMod;
				// SUBMODULES - if any - are loaded
				if (is_array($subMod)) {
					foreach ($subMod as $valsub) {
						$extModRelPath = $this->checkExtensionModule($mods . '_' . $valsub);
						if ($extModRelPath) {
							// EXTENSION submodule:
							$theTempSubMod = $this->checkMod($mods . '_' . $valsub, PATH_site . $extModRelPath);
							// Default sub-module in either main-module-path, be it the default or the userdefined.
							if (is_array($theTempSubMod)) {
								$this->modules[$mods]['sub'][$valsub] = $theTempSubMod;
							}
						} else {
							// 'CLASSIC' submodule
							// Checking for typo3/mod/xxx/ module existence...
							// FIXME what about $path = 1; from above and using $path as string here?
							$theTempSubMod = $this->checkMod($mods . '_' . $valsub, $path . $mods . '/' . $valsub);
							// Default sub-module in either main-module-path, be it the default or the userdefined.
							if (is_array($theTempSubMod)) {
								$this->modules[$mods]['sub'][$valsub] = $theTempSubMod;
							} elseif ($path == $paths['defMods']) {
								// If the submodule did not exist in the default module path, then check if there is a submodule in the submodule path!
								$theTempSubMod = $this->checkMod($mods . '_' . $valsub, $paths['userMods'] . $mods . '/' . $valsub);
								if (is_array($theTempSubMod)) {
									$this->modules[$mods]['sub'][$valsub] = $theTempSubMod;
								}
							}
						}
					}
				}
			} else {
				// This must be done in order to fill out the select-lists for modules correctly!!
				if (is_array($subMod)) {
					foreach ($subMod as $valsub) {
						// FIXME path can only be NULL here, or not?
						$this->checkMod($mods . '_' . $valsub, $path . $mods . '/' . $valsub);
					}
				}
			}
		}
	}

	/**
	 * If the module name ($name) is a module from an extension (has path in $this->absPathArray) then that path is returned relative to PATH_site
	 *
	 * @param string $name Module name
	 * @return string If found, the relative path from PATH_site
	 * @todo Define visibility
	 */
	public function checkExtensionModule($name) {
		if (isset($this->absPathArray[$name])) {
			return rtrim(substr($this->absPathArray[$name], strlen(PATH_site)), '/');
		}
	}

	/**
	 * Here we check for the module.
	 * Return values:
	 * 'notFound':	If the module was not found in the path (no "conf.php" file)
	 * FALSE:		If no access to the module (access check failed)
	 * array():	Configuration array, in case a valid module where access IS granted exists.
	 *
	 * @param string $name Module name
	 * @param string $fullpath Absolute path to module
	 * @return mixed See description of function
	 * @todo Define visibility
	 */
	public function checkMod($name, $fullpath) {
		if ($name == 'user_ws' && !\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('version')) {
			return FALSE;
		}
		// Check for own way of configuring module
		if (is_array($GLOBALS['TBE_MODULES']['_configuration'][$name]['configureModuleFunction'])) {
			$obj = $GLOBALS['TBE_MODULES']['_configuration'][$name]['configureModuleFunction'];
			if (is_callable($obj)) {
				$MCONF = call_user_func($obj, $name, $fullpath);
				if ($this->checkModAccess($name, $MCONF) !== TRUE) {
					return FALSE;
				}
				return $MCONF;
			}
		}
		// Check if this is a submodule
		if (strpos($name, '_') !== FALSE) {
			list($mainModule, ) = explode('_', $name, 2);
		}
		$modconf = array();
		// Because 'path/../path' does not work
		$path = preg_replace('/\\/[^\\/.]+\\/\\.\\.\\//', '/', $fullpath);
		if (@is_dir($path) && file_exists($path . '/conf.php')) {
			$MCONF = array();
			$MLANG = array();
			// The conf-file is included. This must be valid PHP.
			include $path . '/conf.php';
			if (!$MCONF['shy'] && $this->checkModAccess($name, $MCONF) && $this->checkModWorkspace($name, $MCONF)) {
				$modconf['name'] = $name;
				// Language processing. This will add module labels and image reference to the internal ->moduleLabels array of the LANG object.
				if (is_object($GLOBALS['LANG'])) {
					// $MLANG['default']['tabs_images']['tab'] is for modules the reference to the module icon.
					// Here the path is transformed to an absolute reference.
					if ($MLANG['default']['tabs_images']['tab']) {
						// Initializing search for alternative icon:
						// Alternative icon key (might have an alternative set in $TBE_STYLES['skinImg']
						$altIconKey = 'MOD:' . $name . '/' . $MLANG['default']['tabs_images']['tab'];
						$altIconAbsPath = is_array($GLOBALS['TBE_STYLES']['skinImg'][$altIconKey]) ? \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath(PATH_typo3 . $GLOBALS['TBE_STYLES']['skinImg'][$altIconKey][0]) : '';
						// Setting icon, either default or alternative:
						if ($altIconAbsPath && @is_file($altIconAbsPath)) {
							$MLANG['default']['tabs_images']['tab'] = $this->getRelativePath(PATH_typo3, $altIconAbsPath);
						} else {
							// Setting default icon:
							$MLANG['default']['tabs_images']['tab'] = $this->getRelativePath(PATH_typo3, $fullpath . '/' . $MLANG['default']['tabs_images']['tab']);
						}
						// Finally, setting the icon with correct path:
						if (substr($MLANG['default']['tabs_images']['tab'], 0, 3) == '../') {
							$MLANG['default']['tabs_images']['tab'] = PATH_site . substr($MLANG['default']['tabs_images']['tab'], 3);
						} else {
							$MLANG['default']['tabs_images']['tab'] = PATH_typo3 . $MLANG['default']['tabs_images']['tab'];
						}
					}
					// If LOCAL_LANG references are used for labels of the module:
					if ($MLANG['default']['ll_ref']) {
						// Now the 'default' key is loaded with the CURRENT language - not the english translation...
						$MLANG['default']['labels']['tablabel'] = $GLOBALS['LANG']->sL($MLANG['default']['ll_ref'] . ':mlang_labels_tablabel');
						$MLANG['default']['labels']['tabdescr'] = $GLOBALS['LANG']->sL($MLANG['default']['ll_ref'] . ':mlang_labels_tabdescr');
						$MLANG['default']['tabs']['tab'] = $GLOBALS['LANG']->sL($MLANG['default']['ll_ref'] . ':mlang_tabs_tab');
						$GLOBALS['LANG']->addModuleLabels($MLANG['default'], $name . '_');
					} else {
						// ... otherwise use the old way:
						$GLOBALS['LANG']->addModuleLabels($MLANG['default'], $name . '_');
						$GLOBALS['LANG']->addModuleLabels($MLANG[$GLOBALS['LANG']->lang], $name . '_');
					}
				}
				// Default script setup
				if ($MCONF['script'] === '_DISPATCH') {
					if ($MCONF['extbase']) {
						$modconf['script'] = 'mod.php?M=Tx_' . rawurlencode($name);
					} else {
						$modconf['script'] = 'mod.php?M=' . rawurlencode($name);
					}
				} elseif ($MCONF['script'] && file_exists($path . '/' . $MCONF['script'])) {
					$modconf['script'] = $this->getRelativePath(PATH_typo3, $fullpath . '/' . $MCONF['script']);
				} else {
					$modconf['script'] = 'dummy.php';
				}
				// Default tab setting
				if ($MCONF['defaultMod']) {
					$modconf['defaultMod'] = $MCONF['defaultMod'];
				}
				// Navigation Frame Script (GET params could be added)
				if ($MCONF['navFrameScript']) {
					$navFrameScript = explode('?', $MCONF['navFrameScript']);
					$navFrameScript = $navFrameScript[0];
					if (file_exists($path . '/' . $navFrameScript)) {
						$modconf['navFrameScript'] = $this->getRelativePath(PATH_typo3, $fullpath . '/' . $MCONF['navFrameScript']);
					}
				}
				// additional params for Navigation Frame Script: "&anyParam=value&moreParam=1"
				if ($MCONF['navFrameScriptParam']) {
					$modconf['navFrameScriptParam'] = $MCONF['navFrameScriptParam'];
				}
				// check if there is a navigation component (like the pagetree)
				if (is_array($this->navigationComponents[$name])) {
					$modconf['navigationComponentId'] = $this->navigationComponents[$name]['componentId'];
				} elseif ($mainModule && is_array($this->navigationComponents[$mainModule])) {
					$modconf['navigationComponentId'] = $this->navigationComponents[$mainModule]['componentId'];
				}
			} else {
				return FALSE;
			}
		} else {
			$modconf = 'notFound';
		}
		return $modconf;
	}

	/**
	 * Returns TRUE if the internal BE_USER has access to the module $name with $MCONF (based on security level set for that module)
	 *
	 * @param string $name Module name
	 * @param array $MCONF MCONF array (module configuration array) from the modules conf.php file (contains settings about what access level the module has)
	 * @return boolean TRUE if access is granted for $this->BE_USER
	 * @todo Define visibility
	 */
	public function checkModAccess($name, $MCONF) {
		if ($MCONF['access']) {
			$access = strtolower($MCONF['access']);
			// Checking if admin-access is required
			// If admin-permissions is required then return TRUE if user is admin
			if (strstr($access, 'admin')) {
				if ($this->BE_USER->isAdmin()) {
					return TRUE;
				}
			}
			// This will add modules to the select-lists of user and groups
			if (strstr($access, 'user')) {
				$this->modListUser[] = $name;
			}
			if (strstr($access, 'group')) {
				$this->modListGroup[] = $name;
			}
			// This checks if a user is permitted to access the module
			if ($this->BE_USER->isAdmin() || $this->BE_USER->check('modules', $name)) {
				return TRUE;
			}
		} else {
			return TRUE;
		}
	}

	/**
	 * Check if a module is allowed inside the current workspace for be user
	 * Processing happens only if $this->observeWorkspaces is TRUE
	 *
	 * @param string $name Module name
	 * @param array $MCONF MCONF array (module configuration array) from the modules conf.php file (contains settings about workspace restrictions)
	 * @return boolean TRUE if access is granted for $this->BE_USER
	 * @todo Define visibility
	 */
	public function checkModWorkspace($name, $MCONF) {
		if ($this->observeWorkspaces) {
			$status = TRUE;
			if ($MCONF['workspaces']) {
				$status = FALSE;
				if ($this->BE_USER->workspace === 0 && \TYPO3\CMS\Core\Utility\GeneralUtility::inList($MCONF['workspaces'], 'online') || $this->BE_USER->workspace === -1 && \TYPO3\CMS\Core\Utility\GeneralUtility::inList($MCONF['workspaces'], 'offline') || $this->BE_USER->workspace > 0 && \TYPO3\CMS\Core\Utility\GeneralUtility::inList($MCONF['workspaces'], 'custom')) {
					$status = TRUE;
				}
			} elseif ($this->BE_USER->workspace === -99) {
				$status = FALSE;
			}
			return $status;
		} else {
			return TRUE;
		}
	}

	/**
	 * Parses the moduleArray ($TBE_MODULES) into a internally useful structure.
	 * Returns an array where the keys are names of the module and the values may be TRUE (only module) or an array (of submodules)
	 *
	 * @param array $arr ModuleArray ($TBE_MODULES)
	 * @return array Output structure with available modules
	 * @todo Define visibility
	 */
	public function parseModulesArray($arr) {
		$theMods = array();
		if (is_array($arr)) {
			foreach ($arr as $mod => $subs) {
				// Clean module name to alphanum
				$mod = $this->cleanName($mod);
				if ($mod) {
					if ($subs) {
						$subsArr = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $subs);
						foreach ($subsArr as $subMod) {
							$subMod = $this->cleanName($subMod);
							if ($subMod) {
								$theMods[$mod][] = $subMod;
							}
						}
					} else {
						$theMods[$mod] = 1;
					}
				}
			}
		}
		return $theMods;
	}

	/**
	 * The $str is cleaned so that it contains alphanumerical characters only. Modules must only consist of these characters
	 *
	 * @param string $str String to clean up
	 * @return string
	 * @todo Define visibility
	 */
	public function cleanName($str) {
		return preg_replace('/[^a-z0-9]/i', '', $str);
	}

	/**
	 * Get relative path for $destDir compared to $baseDir
	 *
	 * @param string $baseDir Base directory
	 * @param string $destDir Destination directory
	 * @return string The relative path of destination compared to base.
	 * @todo Define visibility
	 */
	public function getRelativePath($baseDir, $destDir) {
		// A special case , the dirs are equals
		if ($baseDir == $destDir) {
			return './';
		}
		// Remove beginning
		$baseDir = ltrim($baseDir, '/');
		$destDir = ltrim($destDir, '/');
		$found = TRUE;
		$slash_pos = 0;
		do {
			$slash_pos = strpos($destDir, '/');
			if (substr($destDir, 0, $slash_pos) == substr($baseDir, 0, $slash_pos)) {
				$baseDir = substr($baseDir, $slash_pos + 1);
				$destDir = substr($destDir, $slash_pos + 1);
			} else {
				$found = FALSE;
			}
		} while ($found == TRUE);
		$slashes = strlen($baseDir) - strlen(str_replace('/', '', $baseDir));
		for ($i = 0; $i < $slashes; $i++) {
			$destDir = '../' . $destDir;
		}
		return \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($destDir);
	}

}


?>