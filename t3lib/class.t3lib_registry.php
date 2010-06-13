<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Ingo Renner <ingo@typo3.org>
*
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
 * A class to store and retrieve entries in a registry database table.
 *
 * The intention is to have a place where we can store things (mainly settings)
 * that should live for more than one request, longer than a session, and that
 * shouldn't expire like it would with a cache. You can actually think of it
 * being like the Windows Registry in some ways.
 *
 * Credits: Heavily inspired by Drupal's variable_*() functions.
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @author	Bastian Waidelich <bastian@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_Registry implements t3lib_Singleton {

	/**
	 * @var	array
	 */
	protected $entries = array();


	/**
	 * Returns a persistent entry.
	 *
	 * @param	string	Extension key for extensions starting with 'tx_' / 'user_' or 'core' for core registry entries
	 * @param	string	The key of the entry to return.
	 * @param	mixed	Optional default value to use if this entry has never been set. Defaults to null.
	 * @return	mixed	The value of the entry.
	 * @throws	InvalidArgumentException	Throws an exception if the given namespace is not valid
	 */
	public function get($namespace, $key, $defaultValue = null) {
		if (!isset($this->entries[$namespace])) {
			$this->loadEntriesByNamespace($namespace);
		}

		return isset($this->entries[$namespace][$key]) ? $this->entries[$namespace][$key] : $defaultValue;
	}

	/**
	 * Sets a persistent entry.
	 * Do not store binary data into the registry, it's not build to do that,
	 * instead use the proper way to store binary data: The filesystem.
	 *
	 * @param	string	Extension key for extensions starting with 'tx_' / 'user_' or 'core' for core registry entries.
	 * @param	string	The key of the entry to set.
	 * @param	mixed	The value to set. This can be any PHP data type; this class takes care of serialization if necessary.
	 * @return	void
	 * @throws	InvalidArgumentException	Throws an exception if the given namespace is not valid
	 */
	public function set($namespace, $key, $value) {
		$this->validateNamespace($namespace);
		$serializedValue = serialize($value);

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid',
			'sys_registry',
			'entry_namespace = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($namespace, 'sys_registry')
			. ' AND entry_key = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($key, 'sys_registry')
		);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) < 1) {
			$GLOBALS['TYPO3_DB']->exec_INSERTquery(
				'sys_registry',
				array(
					'entry_namespace' => $namespace,
					'entry_key'       => $key,
					'entry_value'     => $serializedValue
				)
			);
		} else {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'sys_registry',
				'entry_namespace = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($namespace, 'sys_registry')
				. ' AND entry_key = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($key, 'sys_registry'),
				array(
					'entry_value' => $serializedValue
				)
			);
		}

		$this->entries[$namespace][$key] = $value;
	}

	/**
	 * Unsets a persistent entry.
	 *
	 * @param	string	Namespace. extension key for extensions or 'core' for core registry entries
	 * @param	string	The key of the entry to unset.
	 * @return	void
	 * @throws	InvalidArgumentException	Throws an exception if the given namespace is not valid
	 */
	public function remove($namespace, $key) {
		$this->validateNamespace($namespace);

		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'sys_registry',
			'entry_namespace = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($namespace, 'sys_registry')
			. ' AND entry_key = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($key, 'sys_registry')
		);

		unset($this->entries[$namespace][$key]);
	}

	/**
	 * Unsets all persistent entries of the given namespace.
	 *
	 * @param	string	Namespace. extension key for extensions or 'core' for core registry entries
	 * @return	void
	 * @throws	InvalidArgumentException	Throws an exception if the given namespace is not valid
	 */
	public function removeAllByNamespace($namespace) {
		$this->validateNamespace($namespace);

		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'sys_registry',
			'entry_namespace = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($namespace, 'sys_registry')
		);

		unset($this->entries[$namespace]);
	}

	/**
	 * Loads all entries of the given namespace into the internal $entries cache.
	 *
	 * @param	string	Namespace. extension key for extensions or 'core' for core registry entries
	 * @return	void
	 * @throws	InvalidArgumentException	Throws an exception if the given namespace is not valid
	 */
	protected function loadEntriesByNamespace($namespace) {
		$this->validateNamespace($namespace);

		$storedEntries = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'sys_registry',
			'entry_namespace = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($namespace, 'sys_registry')
		);

		foreach ($storedEntries as $storedEntry) {
			$key = $storedEntry['entry_key'];
			$this->entries[$namespace][$key] = unserialize($storedEntry['entry_value']);
		}
	}

	/**
	 * Checks the given namespace. If it does not have a valid format an
	 * exception is thrown.
	 * Allowed namespaces are 'core', 'tx_*' and 'user_*'
	 *
	 * @param	string	Namespace. extension key for extensions or 'core' for core registry entries
	 * @return	void
	 * @throws	InvalidArgumentException	Throws an exception if the given namespace is not valid
	 */
	protected function validateNamespace($namespace) {
		if (t3lib_div::isFirstPartOfStr($namespace, 'tx_') || t3lib_div::isFirstPartOfStr($namespace, 'user_')) {
			return;
		}

		if ($namespace !== 'core') {
			throw new InvalidArgumentException(
				'"' . $namespace . '" is no valid Namespace. The namespace has to be prefixed with "tx_", "user_" or must be equal to "core"',
				1249755131
			);
		}
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_registry.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_registry.php']);
}

?>