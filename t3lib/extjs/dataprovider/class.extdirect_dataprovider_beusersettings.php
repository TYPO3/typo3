<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Steffen Kamper <steffen@typo3.org>
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
 */
class extDirect_DataProvider_BackendUserSettings {

	/**
	 * Get user settings
	 *
	 * Returns all user settings, if $key is not specified, otherwise it retuns the value for $key
	 *
	 * @param  string $key  identifier, allows also dotted notation for subarrays
	 * @return mixed value associated
	 */
	public function get($key = '') {
		if (strpos($key, '.') !== FALSE) {
			$return = $this->getFromDottedNotation($key);
		} else {
			$return = ($key === '' ? $GLOBALS['BE_USER']->uc : $GLOBALS['BE_USER']->uc[$key]);
		}
		return $return;
	}

	/**
	 * Sets user settings by key/value pair
	 *
	 * @param  string $key
	 * @param  mixed $value
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
	 * @param  array $array
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
	 * @param  string $key
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
			if (!t3lib_div::inList($list, $value)) {
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
		if (t3lib_div::inList($list, $value)) {
			$list = t3lib_div::trimExplode(',', $list, TRUE);
			$list = t3lib_div::removeArrayEntryByValue($list, $value);
			$this->set($key, implode(',' ,$list));
		}
	}

	/**
	 * Computes the subarray from dotted notation
	 *
	 * @param  $key dotted notation of subkeys like moduleData.module1.general.checked
	 * @return mixed $array value of the settings
	 */
	protected function getFromDottedNotation($key) {
		$subkeys = t3lib_div::trimExplode('.', $key);
		$array =& $GLOBALS['BE_USER']->uc;
		foreach ($subkeys as $subkey) {
			$array =& $array[$subkey];
		}
		return $array;
	}

	/**
	 * Sets the value of a key written in dotted notation
	 *
	 * @param  string $key
	 * @param  mixed $value
	 * @return void
	 */
	protected function setFromDottedNotation($key, $value) {
		$subkeys = t3lib_div::trimExplode('.', $key, TRUE);
		$lastKey = $subkeys[count($subkeys) - 1];
		$array =& $GLOBALS['BE_USER']->uc;
		foreach ($subkeys as $subkey) {
			if ($subkey === $lastKey) {
				$array[$subkey] = $value;
			} else {
				$array =& $array[$subkey];
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
		$subkeys = t3lib_div::trimExplode('.', $key, TRUE);
		return $subkeys[count($subkeys) - 1];
	}
}

?>