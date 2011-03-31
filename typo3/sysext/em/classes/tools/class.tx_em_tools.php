<?php
/* **************************************************************
*  Copyright notice
*
*  (c) webservices.nl
*  (c) 2006-2010 Karsten Dambekalns <karsten@typo3.org>
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
 * class.tx_em_tools.php
 */

/**
 * Static tools for extension manager
 * Some of them should be moved later to t3lib static libraries
 *
 */
final class tx_em_Tools {

	/**
	 * Keeps default categories.
	 *
	 * @var  array
	 */
	protected static $defaultCategories = array(
		'be' => 0,
		'module' => 1,
		'fe' => 2,
		'plugin' => 3,
		'misc' => 4,
		'services' => 5,
		'templates' => 6,
		'doc' => 8,
		'example' => 9,
	);
	/**
	 * Keeps default states.
	 *
	 * @var  array
	 */
	protected static $defaultStates = array(
		'alpha' => 0,
		'beta' => 1,
		'stable' => 2,
		'experimental' => 3,
		'test' => 4,
		'obsolete' => 5,
		'excludeFromUpdates' => 6,
		'n/a' => 999,
	);

	/**
	 * Colors for states
	 *
	 * @var array
	 */
	protected static $stateColors = array(
		'alpha' => '#d12438',
		'beta' => '#97b17e',
		'stable' => '#3bb65c',
		'experimental' => '#007eba',
		'test' => '#979797',
		'obsolete' => '#000000',
		'excludeFromUpdates' => '#cf7307'
	);

	/**
	 * Gets the stateColor array
	 *
	 * @static
	 * @return array
	 */
	public static function getStateColors() {
		return self::$stateColors;
	}

	/**
	 * Unzips a zip file in the given path.
	 *
	 * Uses unzip binary if available, otherwise a pure PHP unzip is used.
	 *
	 * @param string $file		Full path to zip file
	 * @param string $path		Path to change to before extracting
	 * @return boolean	True on success, false in failure
	 */
	public static function unzip($file, $path) {
		$unzipPath = trim($GLOBALS['TYPO3_CONF_VARS']['BE']['unzip_path']);
		if (strlen($unzipPath)) {
			chdir($path);
				// for compatiblity reasons, we have to accept the full path of the unzip command
				// or the directory containing the unzip binary
			if (substr($unzipPath, -1) === '/') {
				$cmd = $unzipPath . 'unzip -o ' . escapeshellarg($file);
			} else {
				$cmd = $unzipPath . ' -o ' . escapeshellarg($file);
			}
			t3lib_utility_Command::exec($cmd, $list, $ret);
			return ($ret === 0);
		} else {
				// we use a pure PHP unzip
			$unzip = t3lib_div::makeInstance('tx_em_Tools_Unzip', $file);
			$ret = $unzip->extract(array('add_path' => $path));
			return (is_array($ret));
		}
	}


	/**
	 * Refreshes the global extension list
	 *
	 * @return void
	 */
	public static function refreshGlobalExtList() {
		global $TYPO3_LOADED_EXT;

		$TYPO3_LOADED_EXT = t3lib_extMgm::typo3_loadExtensions();
		if ($TYPO3_LOADED_EXT['_CACHEFILE']) {
			require(PATH_typo3conf . $TYPO3_LOADED_EXT['_CACHEFILE'] . '_ext_localconf.php');
		}
		return;

		$GLOBALS['TYPO3_LOADED_EXT'] = t3lib_extMgm::typo3_loadExtensions();
		if ($TYPO3_LOADED_EXT['_CACHEFILE']) {
			require(PATH_typo3conf . $TYPO3_LOADED_EXT['_CACHEFILE'] . '_ext_localconf.php');
		} else {
			$temp_TYPO3_LOADED_EXT = $TYPO3_LOADED_EXT;
			foreach ($temp_TYPO3_LOADED_EXT as $_EXTKEY => $temp_lEDat) {
				if (is_array($temp_lEDat) && $temp_lEDat['ext_localconf.php']) {
					$_EXTCONF = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$_EXTKEY];
					require($temp_lEDat['ext_localconf.php']);
				}
			}
		}
	}

	/**
	 * Set category array entries for extension
	 *
	 * @param	array		Category index array
	 * @param	array		Part of list array for extension.
	 * @param	string		Extension key
	 * @return	array		Modified category index array
	 */
	public static function setCat(&$cat, $listArrayPart, $extKey) {

			// Getting extension title:
		$extTitle = $listArrayPart['EM_CONF']['title'];

			// Category index:
		$index = $listArrayPart['EM_CONF']['category'];
		$cat['cat'][$index][$extKey] = $extTitle;

			// Author index:
		$index = $listArrayPart['EM_CONF']['author'] . ($listArrayPart['EM_CONF']['author_company'] ? ', ' . $listArrayPart['EM_CONF']['author_company'] : '');
		$cat['author_company'][$index][$extKey] = $extTitle;

			// State index:
		$index = $listArrayPart['EM_CONF']['state'];
		$cat['state'][$index][$extKey] = $extTitle;

			// Type index:
		$index = $listArrayPart['type'];
		$cat['type'][$index][$extKey] = $extTitle;

			// Return categories:
		return $cat;
	}

	/**
	 * Returns upload folder for extension
	 *
	 * @param	string		Extension key
	 * @return	string		Upload folder for extension
	 */
	public static function uploadFolder($extKey) {
		return 'uploads/tx_' . str_replace('_', '', $extKey) . '/';
	}


	/**
	 * Returns image tag for "uninstall"
	 *
	 * @return	string		<img> tag
	 */
	public static function removeButton() {
		return t3lib_iconWorks::getSpriteIcon('actions-system-extension-uninstall', array('title' => $GLOBALS['LANG']->getLL('ext_details_remove_ext')));
	}

	/**
	 * Returns image for "install"
	 *
	 * @return	string		<img> tag
	 */
	public static function installButton() {
		return t3lib_iconWorks::getSpriteIcon('actions-system-extension-install', array('title' => $GLOBALS['LANG']->getLL('helperFunction_install_extension')));
	}

	/**
	 * Warning (<img> + text string) message about the impossibility to import extensions (both local and global locations are disabled...)
	 *
	 * @return	string		<img> + text string.
	 */
	public static function noImportMsg() {
		return t3lib_iconWorks::getSpriteIcon('status-dialog-warning') .
			   '<strong>' . $GLOBALS['LANG']->getLL('helperFunction_import_not_possible') . '</strong>';
	}


	/**
	 * Fixes an old style ext_emconf.php array by adding constraints if needed and removing deprecated keys
	 *
	 * @param	array		$emConf
	 * @return	array
	 */
	public static function fixEMCONF($emConf) {
		if (!isset($emConf['constraints']) || !isset($emConf['constraints']['depends']) || !isset($emConf['constraints']['conflicts']) || !isset($emConf['constraints']['suggests'])) {
			if (!isset($emConf['constraints']) || !isset($emConf['constraints']['depends'])) {
				$emConf['constraints']['depends'] = self::stringToDep($emConf['dependencies']);
				if (strlen($emConf['PHP_version'])) {
					$versionRange = self::splitVersionRange($emConf['PHP_version']);
					if (version_compare($versionRange[0], '3.0.0', '<')) {
						$versionRange[0] = '3.0.0';
					}
					if (version_compare($versionRange[1], '3.0.0', '<')) {
						$versionRange[1] = '0.0.0';
					}
					$emConf['constraints']['depends']['php'] = implode('-', $versionRange);
				}
				if (strlen($emConf['TYPO3_version'])) {
					$versionRange = self::splitVersionRange($emConf['TYPO3_version']);
					if (version_compare($versionRange[0], '3.5.0', '<')) {
						$versionRange[0] = '3.5.0';
					}
					if (version_compare($versionRange[1], '3.5.0', '<')) {
						$versionRange[1] = '0.0.0';
					}
					$emConf['constraints']['depends']['typo3'] = implode('-', $versionRange);
				}
			}
			if (!isset($emConf['constraints']) || !isset($emConf['constraints']['conflicts'])) {
				$emConf['constraints']['conflicts'] = self::stringToDep($emConf['conflicts']);
			}
			if (!isset($emConf['constraints']) || !isset($emConf['constraints']['suggests'])) {
				$emConf['constraints']['suggests'] = array();
			}
		} elseif (isset($emConf['constraints']) && isset($emConf['dependencies'])) {
			$emConf['suggests'] = isset($emConf['suggests']) ? $emConf['suggests'] : array();
			$emConf['dependencies'] = self::depToString($emConf['constraints']);
			$emConf['conflicts'] = self::depToString($emConf['constraints'], 'conflicts');
		}

			// sanity check for version numbers, intentionally only checks php and typo3
		if (isset($emConf['constraints']['depends']) && isset($emConf['constraints']['depends']['php'])) {
			$versionRange = self::splitVersionRange($emConf['constraints']['depends']['php']);
			if (version_compare($versionRange[0], '3.0.0', '<')) {
				$versionRange[0] = '3.0.0';
			}
			if (version_compare($versionRange[1], '3.0.0', '<')) {
				$versionRange[1] = '0.0.0';
			}
			$emConf['constraints']['depends']['php'] = implode('-', $versionRange);
		}
		if (isset($emConf['constraints']['depends']) && isset($emConf['constraints']['depends']['typo3'])) {
			$versionRange = self::splitVersionRange($emConf['constraints']['depends']['typo3']);
			if (version_compare($versionRange[0], '3.5.0', '<')) {
				$versionRange[0] = '3.5.0';
			}
			if (version_compare($versionRange[1], '3.5.0', '<')) {
				$versionRange[1] = '0.0.0';
			}
			$emConf['constraints']['depends']['typo3'] = implode('-', $versionRange);
		}

		unset($emConf['private']);
		unset($emConf['download_password']);
		unset($emConf['TYPO3_version']);
		unset($emConf['PHP_version']);

		return $emConf;
	}


	/**
	 * Returns the $EM_CONF array from an extensions ext_emconf.php file
	 *
	 * @param	string		Absolute path to EMCONF file.
	 * @param	string		Extension key.
	 * @return	array		EMconf array values.
	 */
	public static function includeEMCONF($path, $_EXTKEY) {
		$EM_CONF = NULL;
		@include($path);
		if (is_array($EM_CONF[$_EXTKEY])) {
			return self::fixEMCONF($EM_CONF[$_EXTKEY]);
		}
		return FALSE;
	}


	/**
	 * Extracts the directories in the $files array
	 *
	 * @param	array		Array of files / directories
	 * @return	array		Array of directories from the input array.
	 */
	public static function extractDirsFromFileList($files) {
		$dirs = array();

		if (is_array($files)) {
				// Traverse files / directories array:
			foreach ($files as $file) {
				if (substr($file, -1) == '/') {
					$dirs[$file] = $file;
				} else {
					$pI = pathinfo($file);
					if (strcmp($pI['dirname'], '') && strcmp($pI['dirname'], '.')) {
						$dirs[$pI['dirname'] . '/'] = $pI['dirname'] . '/';
					}
				}
			}
		}
		return $dirs;
	}

	/**
	 * Splits a version range into an array.
	 *
	 * If a single version number is given, it is considered a minimum value.
	 * If a dash is found, the numbers left and right are considered as minimum and maximum. Empty values are allowed.
	 *
	 * @param	string		$ver A string with a version range.
	 * @return	array
	 */
	public static function splitVersionRange($ver) {
		$versionRange = array();
		if (strstr($ver, '-')) {
			$versionRange = explode('-', $ver, 2);
		} else {
			$versionRange[0] = $ver;
			$versionRange[1] = '';
		}

		if (!$versionRange[0]) {
			$versionRange[0] = '0.0.0';
		}
		if (!$versionRange[1]) {
			$versionRange[1] = '0.0.0';
		}

		return $versionRange;
	}

	/**
	 * Checks whether the passed dependency is TER2-style (array) and returns a single string for displaying the dependencies.
	 *
	 * It leaves out all version numbers and the "php" and "typo3" dependencies, as they are implicit and of no interest without the version number.
	 *
	 * @param	mixed		$dep Either a string or an array listing dependencies.
	 * @param	string		$type The dependency type to list if $dep is an array
	 * @return	string		A simple dependency list for display
	 */
	public static function depToString($dep, $type = 'depends') {
		if (is_array($dep)) {
			unset($dep[$type]['php']);
			unset($dep[$type]['typo3']);
			$s = (count($dep[$type])) ? implode(',', array_keys($dep[$type])) : '';
			return $s;
		}
		return '';
	}

	/**
	 * Checks whether the passed dependency is TER-style (string) or TER2-style (array) and returns a single string for displaying the dependencies.
	 *
	 * It leaves out all version numbers and the "php" and "typo3" dependencies, as they are implicit and of no interest without the version number.
	 *
	 * @param	mixed		$dep Either a string or an array listing dependencies.
	 * @param	string		$type The dependency type to list if $dep is an array
	 * @return	string		A simple dependency list for display
	 */
	public static function stringToDep($dep) {
		$constraint = array();
		if (is_string($dep) && strlen($dep)) {
			$dep = explode(',', $dep);
			foreach ($dep as $v) {
				$constraint[$v] = '';
			}
		}
		return $constraint;
	}


	/**
	 * Returns version information
	 *
	 * @param	string		Version code, x.x.x
	 * @param	string		part: "", "int", "main", "sub", "dev"
	 * @return	string
	 * @see renderVersion()
	 */
	public static function makeVersion($v, $mode) {
		$vDat = self::renderVersion($v);
		return $vDat['version_' . $mode];
	}

	/**
	 * Parses the version number x.x.x and returns an array with the various parts.
	 *
	 * @param	string		Version code, x.x.x
	 * @param	string		Increase version part: "main", "sub", "dev"
	 * @return	string
	 */
	public static function renderVersion($v, $raise = '') {
		$parts = t3lib_div::intExplode('.', $v . '..');
		$parts[0] = t3lib_div::intInRange($parts[0], 0, 999);
		$parts[1] = t3lib_div::intInRange($parts[1], 0, 999);
		$parts[2] = t3lib_div::intInRange($parts[2], 0, 999);

		switch ((string) $raise) {
			case 'main':
				$parts[0]++;
				$parts[1] = 0;
				$parts[2] = 0;
				break;
			case 'sub':
				$parts[1]++;
				$parts[2] = 0;
				break;
			case 'dev':
				$parts[2]++;
				break;
		}

		$res = array();
		$res['version'] = $parts[0] . '.' . $parts[1] . '.' . $parts[2];
		$res['version_int'] = intval($parts[0] * 1000000 + $parts[1] * 1000 + $parts[2]);
		$res['version_main'] = $parts[0];
		$res['version_sub'] = $parts[1];
		$res['version_dev'] = $parts[2];

		return $res;
	}

	/**
	 * Render version from intVersion
	 *
	 * @static
	 * @param  int  $intVersion
	 * @return string version
	 */
	public static function versionFromInt($intVersion) {
		$versionString = str_pad($intVersion, 9, '0', STR_PAD_LEFT);
		$parts = array(
			substr($versionString, 0, 3),
			substr($versionString, 3, 3),
			substr($versionString, 6, 3)
		);
		return intval($parts[0]) . '.' . intval($parts[1]) . '.' . intval($parts[2]);
	}

	/**
	 * Evaluates differences in version numbers with three parts, x.x.x. Returns true if $v1 is greater than $v2
	 *
	 * @param	string		Version number 1
	 * @param	string		Version number 2
	 * @param	integer		Tolerance factor. For instance, set to 1000 to ignore difference in dev-version (third part)
	 * @return	boolean		True if version 1 is greater than version 2
	 */
	public static function versionDifference($v1, $v2, $div = 1) {
		return floor(self::makeVersion($v1, 'int') / $div) > floor(self::makeVersion($v2, 'int') / $div);
	}


	/**
	 * Returns true if the $str is found as the first part of a string in $array
	 *
	 * @param	string		String to test with.
	 * @param	array		Input array
	 * @param	boolean		If set, the test is case insensitive
	 * @return	boolean		True if found.
	 */
	public static function first_in_array($str, $array, $caseInsensitive = FALSE) {
		if ($caseInsensitive) {
			$str = strtolower($str);
		}
		if (is_array($array)) {
			foreach ($array as $cl) {
				if ($caseInsensitive) {
					$cl = strtolower($cl);
				}
				if (t3lib_div::isFirstPartOfStr($cl, $str)) {
					return TRUE;
				}
			}
		}
		return FALSE;
	}

	/**
	 * Compares two arrays with MD5-hash values for analysis of which files has changed.
	 *
	 * @param	array		Current values
	 * @param	array		Past values
	 * @return	array		Affected files
	 */
	public static function findMD5ArrayDiff($current, $past) {
		if (!is_array($current)) {
			$current = array();
		}
		if (!is_array($past)) {
			$past = array();
		}
		$filesInCommon = array_intersect($current, $past);
		$diff1 = array_keys(array_diff($past, $filesInCommon));
		$diff2 = array_keys(array_diff($current, $filesInCommon));
		$affectedFiles = array_unique(array_merge($diff1, $diff2));
		return $affectedFiles;
	}

	/**
	 * Returns title and style attribute for mouseover help text.
	 *
	 * @param	string		Help text.
	 * @return	string		title="" attribute prepended with a single space
	 */
	public static function labelInfo($str) {
		return ' title="' . htmlspecialchars($str) . '" style="cursor:help;"';
	}


	/**
	 * Returns the absolute path where the extension $extKey is installed (based on 'type' (SGL))
	 *
	 * @param	string		Extension key
	 * @param	string		Install scope type: L, G, S
	 * @return	string		Returns the absolute path to the install scope given by input $type variable. It is checked if the path is a directory. Slash is appended.
	 */
	public static function getExtPath($extKey, $type, $returnWithoutExtKey = FALSE) {
		$typePath = self::typePath($type);

		if ($typePath) {
			$path = $typePath . ($returnWithoutExtKey ? '' : $extKey . '/');
			return $path; # @is_dir($path) ? $path : '';
		} else {
			return '';
		}
	}

	/**
	 * Get type of extension (G,S,L) from extension path
	 *
	 * @param string $path
	 */
	public static function getExtTypeFromPath($path) {
		if (strpos($path, TYPO3_mainDir . 'sysext/') !== FALSE) {
			return 'S';
		} elseif (strpos($path, TYPO3_mainDir . 'ext/') !== FALSE) {
			return 'G';
		} elseif (strpos($path, 'typo3conf/ext/') !== FALSE) {
			return 'L';
		}
	}

	/**
	 * Get path from type
	 *
	 * @param string $type S/G/L
	 */
	public static function typePath($type) {
		if ($type === 'S') {
			return PATH_typo3 . 'sysext/';
		} elseif ($type === 'G') {
			return PATH_typo3 . 'ext/';
		} elseif ($type === 'L') {
			return PATH_typo3conf . 'ext/';
		}
	}

	/**
	 * Get relative path from type
	 *
	 * @param string $type S/G/L
	 */
	public static function typeRelPath($type) {
		if ($type === 'S') {
			return 'sysext/';
		} elseif ($type === 'G') {
			return 'ext/';
		} elseif ($type === 'L') {
			return '../typo3conf/ext/';
		}
	}

	/**
	 * Get backpath from type
	 *
	 * @param string $type S/G/L
	 */
	public static function typeBackPath($type) {
		if ($type === 'L') {
			return '../../../../' . TYPO3_mainDir;
		} else {
			return '../../../';
		}
	}

	/**
	 * Reads locallang file into array (for possible include in header)
	 *
	 * @param $file
	 * @return array
	 * @deprecated  since TYPO3 4.5.1, will be removed in TYPO3 4.7 - use pageRenderer->addInlineLanguageLabelFile() instead
	 */
	public static function getArrayFromLocallang($file, $key = 'default') {
		$content = t3lib_div::getURL($file);
		$array = t3lib_div::xml2array($content);

		return $array['data'][$key];
	}

	/**
	 * Include a locallang file and return the $LOCAL_LANG array serialized.
	 *
	 * @param	string		Absolute path to locallang file to include.
	 * @param	string		Old content of a locallang file (keeping the header content)
	 * @return	array		Array with header/content as key 0/1
	 * @see makeUploadarray()
	 */
	public static function getSerializedLocalLang($file, $content) {
		$LOCAL_LANG = NULL;
		$returnParts = explode('$LOCAL_LANG', $content, 2);

		include($file);
		if (is_array($LOCAL_LANG)) {
			$returnParts[1] = serialize($LOCAL_LANG);
			return $returnParts;
		} else {
			return array();
		}
	}


	/**
	 * Enter description here...
	 *
	 * @param	unknown_type		$array
	 * @param	unknown_type		$lines
	 * @param	unknown_type		$level
	 * @return	unknown
	 */
	public static function arrayToCode($array, $level = 0) {
		$lines = 'array(' . LF;
		$level++;
		foreach ($array as $k => $v) {
			if (strlen($k) && is_array($v)) {
				$lines .= str_repeat(TAB, $level) . "'" . $k . "' => " . self::arrayToCode($v, $level);
			} elseif (strlen($k)) {
				$lines .= str_repeat(TAB, $level) . "'" . $k . "' => " . (t3lib_div::testInt($v) ? intval($v) : "'" . t3lib_div::slashJS(trim($v), 1) . "'") . ',' . LF;
			}
		}

		$lines .= str_repeat(TAB, $level - 1) . ')' . ($level - 1 == 0 ? '' : ',' . LF);
		return $lines;
	}


	/**
	 * Traverse the array of installed extensions keys and arranges extensions in the priority order they should be in
	 *
	 * @param	array		Array of extension keys as values
	 * @param	array		Extension information array
	 * @return	array		Modified array of extention keys as values
	 * @see addExtToList()
	 */
	public static function managesPriorities($listArr, $instExtInfo) {

			// Initialize:
		$levels = array(
			'top' => array(),
			'middle' => array(),
			'bottom' => array(),
		);

			// Traverse list of extensions:
		foreach ($listArr as $ext) {
			$prio = trim($instExtInfo[$ext]['EM_CONF']['priority']);
			switch ((string) $prio) {
				case 'top':
				case 'bottom':
					$levels[$prio][] = $ext;
					break;
				default:
					$levels['middle'][] = $ext;
					break;
			}
		}
		return array_merge(
			$levels['top'],
			$levels['middle'],
			$levels['bottom']
		);
	}


	/**
	 * Returns either array with all default categories or index/title
	 * of a category entry.
	 *
	 * @access  public
	 * @param   mixed   $cat  category title or category index
	 * @return  mixed
	 */
	public static function getDefaultCategory($cat = NULL) {
		if (is_null($cat)) {
			return self::$defaultCategories;
		} else {
			if (is_string($cat)) {
					// default category
				$catIndex = 4;
				if (array_key_exists(strtolower($cat), self::$defaultCategories)) {
					$catIndex = self::$defaultCategories[strtolower($cat)];
				}
				return $catIndex;
			} else {
				if (is_int($cat) && $cat >= 0) {
					$catTitle = array_search($cat, self::$defaultCategories);
						// default category
					if (!$catTitle) {
						$catTitle = 'misc';
					}
					return $catTitle;
				}
			}
		}
	}

	/**
	 * Returns either array with all default states or index/title
	 * of a state entry.
	 *
	 * @access  public
	 * @param   mixed   $state  state title or state index
	 * @return  mixed
	 */
	public static function getDefaultState($state = NULL) {
		if (is_null($state)) {
			return self::$defaultStates;
		} else {
			if (is_string($state)) {
					// default state
				$stateIndex = 999;
				if (array_key_exists(strtolower($state), self::$defaultStates)) {
					$stateIndex = self::$defaultStates[strtolower($state)];
				}
				return $stateIndex;
			} else {
				if (is_int($state) && $state >= 0) {
					$stateTitle = array_search($state, self::$defaultStates);
						// default state
					if (!$stateTitle) {
						$stateTitle = 'n/a';
					}
					return $stateTitle;
				}
			}
		}
	}

	/**
	 * Extension States
	 * Content must be redundant with the same internal variable as in class.tx_extrep.php!
	 *
	 * @static
	 * @return array
	 */
	public static function getStates() {
		return array(
			'alpha' => $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:state_alpha'),
			'beta' => $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:state_beta'),
			'stable' => $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:state_stable'),
			'experimental' => $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:state_experimental'),
			'test' => $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:state_test'),
			'obsolete' => $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:state_obsolete'),
			'excludeFromUpdates' => $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:state_exclude_from_updates')
		);
	}

	/**
	 * Reports back if installation in a certain scope is possible.
	 *
	 * @param	string		Scope: G, L, S
	 * @param	string		Extension lock-type (eg. "L" or "G")
	 * @return	boolean		True if installation is allowed.
	 */
	public static function importAsType($type, $lockType = '') {
		switch ($type) {
			case 'G':
				return $GLOBALS['TYPO3_CONF_VARS']['EXT']['allowGlobalInstall'] && (!$lockType || !strcmp($lockType, $type));
			break;
			case 'L':
				return $GLOBALS['TYPO3_CONF_VARS']['EXT']['allowLocalInstall'] && (!$lockType || !strcmp($lockType, $type));
			break;
			case 'S':
				return isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['allowSystemInstall']) && $GLOBALS['TYPO3_CONF_VARS']['EXT']['allowSystemInstall'];
			break;
			default:
				return FALSE;
		}
	}

	/**
	 * Returns true if extensions in scope, $type, can be deleted (or installed for that sake)
	 *
	 * @param	string		Scope: "G" or "L"
	 * @return	boolean		True if possible.
	 */
	public static function deleteAsType($type) {
		switch ($type) {
			case 'G':
				return $GLOBALS['TYPO3_CONF_VARS']['EXT']['allowGlobalInstall'];
			break;
			case 'L':
				return $GLOBALS['TYPO3_CONF_VARS']['EXT']['allowLocalInstall'];
			break;
			default:
				return FALSE;
		}
	}


	/**
	 * Creates directories in $extDirPath
	 *
	 * @param	array		Array of directories to create relative to extDirPath, eg. "blabla", "blabla/blabla" etc...
	 * @param	string		Absolute path to directory.
	 * @return	mixed		Returns false on success or an error string
	 */
	public static function createDirsInPath($dirs, $extDirPath) {
		if (is_array($dirs)) {
			foreach ($dirs as $dir) {
				$error = t3lib_div::mkdir_deep($extDirPath, $dir);
				if ($error) {
					return $error;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Analyses the php-scripts of an available extension on server
	 *
	 * @param	string		Absolute path to extension
	 * @param	string		Prefix for tables/classes.
	 * @param	string		Extension key
	 * @return	array		Information array.
	 * @see makeDetailedExtensionAnalysis()
	 */
	public static function getClassIndexLocallangFiles($absPath, $table_class_prefix, $extKey) {
		$excludeForPackaging = $GLOBALS['TYPO3_CONF_VARS']['EXT']['excludeForPackaging'];
		$filesInside = t3lib_div::removePrefixPathFromList(t3lib_div::getAllFilesAndFoldersInPath(array(), $absPath, 'php,inc', 0, 99, $excludeForPackaging), $absPath);
		$out = array();
		$reg = array();

		foreach ($filesInside as $fileName) {
			if (substr($fileName, 0, 4) != 'ext_' && substr($fileName, 0, 6) != 'tests/') { // ignore supposed-to-be unit tests as well
				$baseName = basename($fileName);
				if (substr($baseName, 0, 9) == 'locallang' && substr($baseName, -4) == '.php') {
					$out['locallang'][] = $fileName;
				} elseif ($baseName != 'conf.php') {
					if (filesize($absPath . $fileName) < 500 * 1024) {
						$fContent = t3lib_div::getUrl($absPath . $fileName);
						unset($reg);
						if (preg_match('/\n[[:space:]]*class[[:space:]]*([[:alnum:]_]+)([[:alnum:][:space:]_]*)/', $fContent, $reg)) {

								// Find classes:
							$lines = explode(LF, $fContent);
							foreach ($lines as $l) {
								$line = trim($l);
								unset($reg);
								if (preg_match('/^class[[:space:]]*([[:alnum:]_]+)([[:alnum:][:space:]_]*)/', $line, $reg)) {
									$out['classes'][] = $reg[1];
									$out['files'][$fileName]['classes'][] = $reg[1];
									if ($reg[1] !== 'ext_update' && substr($reg[1], 0, 3) != 'ux_' && !t3lib_div::isFirstPartOfStr($reg[1], $table_class_prefix) && strcmp(substr($table_class_prefix, 0, -1), $reg[1])) {
										$out['NSerrors']['classname'][] = $reg[1];
									} else {
										$out['NSok']['classname'][] = $reg[1];
									}
								}
							}
								// If class file prefixed 'class.'....
							if (substr($baseName, 0, 6) == 'class.') {
								$fI = pathinfo($baseName);
								$testName = substr($baseName, 6, -(1 + strlen($fI['extension'])));
								if ($testName !== 'ext_update' && substr($testName, 0, 3) != 'ux_' && !t3lib_div::isFirstPartOfStr($testName, $table_class_prefix) && strcmp(substr($table_class_prefix, 0, -1), $testName)) {
									$out['NSerrors']['classfilename'][] = $baseName;
								} else {
									$out['NSok']['classfilename'][] = $baseName;
									if (is_array($out['files'][$fileName]['classes']) && tx_em_Tools::first_in_array($testName, $out['files'][$fileName]['classes'], 1)) {
										$out['msg'][] = sprintf($GLOBALS['LANG']->getLL('detailedExtAnalysis_class_ok'),
																$fileName, $testName
										);
									} else {
										$out['errors'][] = sprintf($GLOBALS['LANG']->getLL('detailedExtAnalysis_class_not_ok'),
																   $fileName, $testName
										);
									}
								}
							}
								// Check for proper XCLASS definition
								// Match $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS'] with single or doublequotes
							$XclassSearch = '\$TYPO3_CONF_VARS\[TYPO3_MODE\]\[[\'"]XCLASS[\'"]\]';
							$XclassParts = preg_split('/if \(defined\([\'"]TYPO3_MODE[\'"]\)(.*)' . $XclassSearch . '/', $fContent, 2);
							if (count($XclassParts) !== 2) {
									// Match $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS'] with single or doublequotes
								$XclassSearch = '\$GLOBALS\[[\'"]TYPO3_CONF_VARS[\'"]\]\[TYPO3_MODE\]\[[\'"]XCLASS[\'"]\]';
								$XclassParts = preg_split('/if \(defined\([\'"]TYPO3_MODE[\'"]\)(.*)' . $XclassSearch . '/', $fContent, 2);
							}

							if (count($XclassParts) == 2) {
								unset($reg);
								preg_match('/^\[[\'"]([[:alnum:]_\/\.]*)[\'"]\]/', $XclassParts[1], $reg);
								if ($reg[1]) {
									$cmpF = 'ext/' . $extKey . '/' . $fileName;
									if (!strcmp($reg[1], $cmpF)) {
										if (preg_match('/_once[[:space:]]*\(' . $XclassSearch . '\[[\'"]' . preg_quote($cmpF, '/') . '[\'"]\]\);/', $XclassParts[1])) {
											$out['msg'][] = sprintf($GLOBALS['LANG']->getLL('detailedExtAnalysis_xclass_ok'), $fileName);
										} else {
											$out['errors'][] = $GLOBALS['LANG']->getLL('detailedExtAnalysis_xclass_no_include');
										}
									} else {
										$out['errors'][] = sprintf($GLOBALS['LANG']->getLL('detailedExtAnalysis_xclass_incorrect'),
																   $reg[1], $cmpF
										);
									}
								} else {
									$out['errors'][] = sprintf($GLOBALS['LANG']->getLL('detailedExtAnalysis_no_xclass_filename'), $fileName);
								}
							} elseif (!tx_em_Tools::first_in_array('ux_', $out['files'][$fileName]['classes'])) {
									// No Xclass definition required if classname starts with 'ux_'
								$out['errors'][] = sprintf($GLOBALS['LANG']->getLL('detailedExtAnalysis_no_xclass_found'), $fileName);
							}
						}
					}
				}
			}
		}
		return $out;
	}

	/**
	 * Write new TYPO3_MOD_PATH to "conf.php" file.
	 *
	 * @param	string		Absolute path to a "conf.php" file of the backend module which we want to write back to.
	 * @param	string		Install scope type: L, G, S
	 * @param	string		Relative path for the module folder in extension
	 * @return	string		Returns message about the status.
	 * @see modConfFileAnalysis()
	 */
	public static function writeTYPO3_MOD_PATH($confFilePath, $type, $mP) {
		$lines = explode(LF, t3lib_div::getUrl($confFilePath));
		$confFileInfo = array();
		$confFileInfo['lines'] = $lines;
		$reg = array();

		$flag_M = 0;
		$flag_B = 0;
		$flag_Dispatch = 0;

		foreach ($lines as $k => $l) {
			$line = trim($l);

			unset($reg);
			if (preg_match('/^define[[:space:]]*\([[:space:]]*["\']TYPO3_MOD_PATH["\'][[:space:]]*,[[:space:]]*["\']([[:alnum:]_\/\.]+)["\'][[:space:]]*\)[[:space:]]*;/', $line, $reg)) {
				$lines[$k] = str_replace($reg[0], 'define(\'TYPO3_MOD_PATH\', \'' . self::typeRelPath($type) . $mP . '\');', $lines[$k]);
				$flag_M = $k + 1;
			}

			unset($reg);
			if (preg_match('/^\$BACK_PATH[[:space:]]*=[[:space:]]*["\']([[:alnum:]_\/\.]+)["\'][[:space:]]*;/', $line, $reg)) {
				$lines[$k] = str_replace($reg[0], '$BACK_PATH=\'' . self::typeBackPath($type) . '\';', $lines[$k]);
				$flag_B = $k + 1;
			}

				// Check if this module uses new API (see http://bugs.typo3.org/view.php?id=5278)
				// where TYPO3_MOD_PATH and BACK_PATH are not required
			unset($reg);
			if (preg_match('/^\$MCONF\[["\']script["\']\][[:space:]]*=[[:space:]]*["\']_DISPATCH["\'][[:space:]]*;/', $line, $reg)) {
				$flag_Dispatch = $k + 1;
			}

		}

		if ($flag_B && $flag_M) {
			t3lib_div::writeFile($confFilePath, implode(LF, $lines));
			return sprintf($GLOBALS['LANG']->getLL('writeModPath_ok'),
						   substr($confFilePath, strlen(PATH_site)));
		} elseif ($flag_Dispatch) {
			return sprintf(
				$GLOBALS['LANG']->getLL('writeModPath_notRequired'),
				substr($confFilePath, strlen(PATH_site))
			);
		} else {
			return self::rfw(
				sprintf($GLOBALS['LANG']->getLL('writeModPath_error'),
						$confFilePath)
			);
		}
	}

	/**
	 * Sends content of file for download
	 *
	 * @static
	 * @param  $path
	 * @return void
	 */
	public static function sendFile($path) {
		$path = t3lib_div::resolveBackPath(PATH_site . $path);

		if (is_file($path) && is_readable($path) && t3lib_div::isAllowedAbsPath($path)) {
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename=' . basename($path));
			readfile($path);
			exit;
		}
	}

	/**
	 * Rename a file / folder
	 * @static
	 * @param  $file
	 * @param  $newName
	 * @return bool
	 */
	public static function renameFile($file, $newName) {
		if($file[0] == '/') {
			$file = substr($file, 1);
		}
		if($newName[0] == '/') {
			$newName = substr($newName, 1);
		}

		$file = t3lib_div::resolveBackPath(PATH_site . $file);
		$newName = t3lib_div::resolveBackPath(PATH_site . $newName);
		if (is_writable($file) && t3lib_div::isAllowedAbsPath($file) && t3lib_div::isAllowedAbsPath($newName)) {
			return rename($file, $newName);
		}

		return false;
	}


	/**
	 * Creates a new file
	 *
	 * Returns an array with
	 * 0: boolean success
	 * 1: string absolute path of written file/folder
	 * 2: error code
	 *
	 * The error code returns
	 * 0: no error
	 * -1: not writable
	 * -2: not allowed path
	 * -3: already exists
	 * -4: not able to create
	 *
	 * @static
	 * @param  $folder
	 * @param  $file
	 * @param  $isFolder
	 * @return array
	 */
	public static function createNewFile($folder, $file, $isFolder) {
		$success = FALSE;
		$error = 0;

		if (substr($folder, -1) !== '/') {
			$folder .= '/';
		}


		$newFile = t3lib_div::resolveBackPath(PATH_site . $folder . $file);

		if (!is_writable(dirname($newFile))) {
			$error = -1;
		} elseif (!t3lib_div::isAllowedAbsPath($newFile)) {
			$error = -2;
		} elseif (file_exists($newFile)) {
			$error = -3;
		} else {
			if ($isFolder) {
				$success = t3lib_div::mkdir($newFile);
			} else {
				$success = t3lib_div::writeFile($newFile, '');
			}

			if (!$success) {
				$error = -4;
			}
		}

		return array(
			$success,
			$newFile,
			$error
		);
	}


	/**
	 * Wrapping input string in a link tag with link to email address
	 *
	 * @param	string		Input string, being wrapped in <a> tags
	 * @param	string		Email address for use in link.
	 * @return	string		Output
	 */
	public static function wrapEmail($str, $email) {
		if ($email) {
			$str = '<a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($str) . '</a>';
		}
		return $str;
	}

	/**
	 * red-fontwrap. Returns the string wrapped in a <span>-tag defining the color to be red
	 *
	 * @param	string		Input string
	 * @return	string		Output string
	 */
	public static function rfw($string) {
		return '<span class="typo3-red">' . $string . '</span>';
	}

	/**
	 * dimmed-fontwrap. Returns the string wrapped in a <span>-tag defining the color to be gray/dimmed
	 *
	 * @param	string		Input string
	 * @return	string		Output string
	 */
	public static function dfw($string) {
		return '<span class="typo3-dimmed">' . $string . '</span>';
	}
}

?>