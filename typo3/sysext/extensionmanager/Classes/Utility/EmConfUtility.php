<?php
namespace TYPO3\CMS\Extensionmanager\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Susanne Moog <susanne.moog@typo3.org>
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
 * Utility for dealing with ext_emconf
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 */
class EmConfUtility implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Returns the $EM_CONF array from an extensions ext_emconf.php file
	 *
	 * @param array $extension Extension information array
	 * @return array EMconf array values.
	 */
	public function includeEmConf(array $extension) {
		$_EXTKEY = $extension['key'];
		$path = PATH_site . $extension['siteRelPath'] . '/ext_emconf.php';
		$EM_CONF = NULL;
		if (file_exists($path)) {
			include $path;
			if (is_array($EM_CONF[$_EXTKEY])) {
				return $EM_CONF[$_EXTKEY];
			}
		}
		return FALSE;
	}

	/**
	 * Generates the content for the ext_emconf.php file
	 * Sets dependencies from TER data if any
	 *
	 * @internal
	 * @param array $extensionData
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension Extension object from TER data
	 * @return string
	 */
	public function constructEmConf(array $extensionData, \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension = NULL) {
		if (is_object($extension)) {
			$extensionData['EM_CONF']['constraints'] = unserialize($extension->getSerializedDependencies());
		}
		$emConf = $this->fixEmConf($extensionData['EM_CONF']);
		$emConf = var_export($emConf, TRUE);
		$code = '<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "' . $extensionData['extKey'] . '".
 *
 * Auto generated ' . date('d-m-Y H:i') . '
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = ' . $emConf . ';

?>';
		return str_replace('  ', TAB, $code);
	}

	/**
	 * Fix the em conf - Converts old / ter em_conf format to new format
	 *
	 * @param array $emConf
	 * @return array
	 */
	public function fixEmConf(array $emConf) {
		if (!isset($emConf['constraints']) || !isset($emConf['constraints']['depends']) || !isset($emConf['constraints']['conflicts']) || !isset($emConf['constraints']['suggests'])) {
			if (!isset($emConf['constraints']) || !isset($emConf['constraints']['depends'])) {
				$emConf['constraints']['depends'] = $this->stringToDependency($emConf['dependencies']);
				if (strlen($emConf['PHP_version'])) {
					$emConf['constraints']['depends']['php'] = $emConf['PHP_version'];
				}
				if (strlen($emConf['TYPO3_version'])) {
					$emConf['constraints']['depends']['typo3'] = $emConf['TYPO3_version'];
				}
			}
			if (!isset($emConf['constraints']) || !isset($emConf['constraints']['conflicts'])) {
				$emConf['constraints']['conflicts'] = $this->dependencyToString($emConf['conflicts']);
			}
			if (!isset($emConf['constraints']) || !isset($emConf['constraints']['suggests'])) {
				$emConf['constraints']['suggests'] = array();
			}
		} elseif (isset($emConf['constraints']) && isset($emConf['dependencies'])) {
			$emConf['suggests'] = isset($emConf['suggests']) ? $emConf['suggests'] : array();
			$emConf['dependencies'] = $this->dependencyToString($emConf['constraints']);
			$emConf['conflicts'] = $this->dependencyToString($emConf['constraints'], 'conflicts');
		}
		unset($emConf['private']);
		unset($emConf['download_password']);
		unset($emConf['TYPO3_version']);
		unset($emConf['PHP_version']);
		return $emConf;
	}

	/**
	 * Checks whether the passed dependency is TER2-style (array) and returns a
	 * single string for displaying the dependencies.
	 *
	 * It leaves out all version numbers and the "php" and "typo3" dependencies,
	 * as they are implicit and of no interest without the version number.
	 *
	 * @param mixed $dependency Either a string or an array listing dependencies.
	 * @param string $type The dependency type to list if $dep is an array
	 * @return string A simple dependency list for display
	 */
	static public function dependencyToString($dependency, $type = 'depends') {
		if (is_array($dependency)) {
			if (isset($dependency[$type]['php'])) {
				unset($dependency[$type]['php']);
			}
			if (isset($dependency[$type]['typo3'])) {
				unset($dependency[$type]['typo3']);
			}
			$dependencyString = count($dependency[$type]) ? implode(',', array_keys($dependency[$type])) : '';
			return $dependencyString;
		}
		return '';
	}

	/**
	 * Checks whether the passed dependency is TER-style (string) or
	 * TER2-style (array) and returns a single string for displaying the
	 * dependencies.
	 *
	 * It leaves out all version numbers and the "php" and "typo3" dependencies,
	 * as they are implicit and of no interest without the version number.
	 *
	 * @param mixed $dependency Either a string or an array listing dependencies.
	 * @return string A simple dependency list for display
	 */
	public function stringToDependency($dependency) {
		$constraint = array();
		if (is_string($dependency) && strlen($dependency)) {
			$dependency = explode(',', $dependency);
			foreach ($dependency as $v) {
				$constraint[$v] = '';
			}
		}
		return $constraint;
	}

}


?>