<?php
namespace TYPO3\CMS\Backend\User\ExtDirect;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Steffen Kamper <steffen@typo3.org>
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
 * ExtDirect DataProvider for BE User Settings
 *
 * @author Steffen Kamper <steffen@typo3.org>
 */
class BackendUserSettingsDataProvider {

	/**
	 * Get user settings
	 *
	 * Returns all user settings, if $key is not specified, otherwise it retuns the value for $key
	 *
	 * @param string $key Identifier, allows also dotted notation for subarrays
	 * @return mixed Value associated
	 */
	public function get($key = '') {
		if (strpos($key, '.') !== FALSE) {
			$return = $this->getFromDottedNotation($key);
		} else {
			$return = $key === '' ? $GLOBALS['BE_USER']->uc : $GLOBALS['BE_USER']->uc[$key];
		}
		return $return;
	}

	/**
	 * Sets user settings by key/value pair
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function set($key, $value) {
		if (strpos($key, '.') !== FALSE) {
			$this->setFromDottedNotation($key, $value);
		} else {
			$GLOBALS['BE_USER']->uc[$key] = $value;
		}
		$GLOBALS['BE_USER']->writeUC($GLOBALS['BE_USER']->uc);
	}

	/**
	 * Sets user settings by array and merges them with current settings
	 *
	 * @param array $array
	 * @return void
	 */
	public function setFromArray(array $array) {
		$GLOBALS['BE_USER']->uc = array_merge($GLOBALS['BE_USER']->uc, $array);
		$GLOBALS['BE_USER']->writeUC($GLOBALS['BE_USER']->uc);
	}

	/**
	 * Resets the user settings to the default
	 *
	 * @return void
	 */
	public function reset() {
		$GLOBALS['BE_USER']->resetUC();
	}

	/**
	 * Unsets a key in user settings
	 *
	 * @param string $key
	 * @return void
	 */
	public function unsetKey($key) {
		if (isset($GLOBALS['BE_USER']->uc[$key])) {
			unset($GLOBALS['BE_USER']->uc[$key]);
			$GLOBALS['BE_USER']->writeUC($GLOBALS['BE_USER']->uc);
		}
	}

	/**
	 * Adds an value to an Comma-separated list
	 * stored $key  of user settings
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function addToList($key, $value) {
		$list = $this->get($key);
		if (!isset($list)) {
			$list = $value;
		} else {
			if (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList($list, $value)) {
				$list .= ',' . $value;
			}
		}
		$this->set($key, $list);
	}

	/**
	 * Removes an value from an Comma-separated list
	 * stored $key of user settings
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function removeFromList($key, $value) {
		$list = $this->get($key);
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($list, $value)) {
			$list = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $list, TRUE);
			$list = \TYPO3\CMS\Core\Utility\GeneralUtility::removeArrayEntryByValue($list, $value);
			$this->set($key, implode(',', $list));
		}
	}

	/**
	 * Computes the subarray from dotted notation
	 *
	 * @param $key Dotted notation of subkeys like moduleData.module1.general.checked
	 * @return mixed $array value of the settings
	 */
	protected function getFromDottedNotation($key) {
		$subkeys = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('.', $key);
		$array = &$GLOBALS['BE_USER']->uc;
		foreach ($subkeys as $subkey) {
			$array = &$array[$subkey];
		}
		return $array;
	}

	/**
	 * Sets the value of a key written in dotted notation
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	protected function setFromDottedNotation($key, $value) {
		$subkeys = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('.', $key, TRUE);
		$lastKey = $subkeys[count($subkeys) - 1];
		$array = &$GLOBALS['BE_USER']->uc;
		foreach ($subkeys as $subkey) {
			if ($subkey === $lastKey) {
				$array[$subkey] = $value;
			} else {
				$array = &$array[$subkey];
			}
		}
	}

	/**
	 * Gets the last part of of an Dotted Notation
	 *
	 * @param string $key
	 * @return void
	 */
	protected function getLastKeyFromDottedNotation($key) {
		$subkeys = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('.', $key, TRUE);
		return $subkeys[count($subkeys) - 1];
	}

}


?>