<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012
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
 * Class to register what table is registered to have category
 *
 * @author Olivier Hader <olivier.hader@typo3.org>
 * @author Fabien Udriot <fabien.udriot@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_CategoryRegistry implements t3lib_Singleton {

	/**
	 * @var array
	 */
	protected $registry = array();

	/**
	 * @var string
	 */
	protected $template = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->template = str_repeat(PHP_EOL, 3) . 'CREATE TABLE %s (' . PHP_EOL .
			'%s int(11) DEFAULT \'0\' NOT NULL' . PHP_EOL .
			');' . str_repeat(PHP_EOL, 3);
	}

	/**
	 * Return a class instance
	 *
	 * @return t3lib_CategoryRegistry
	 */
	public static function getInstance() {
		return t3lib_div::makeInstance('t3lib_CategoryRegistry');
	}

	/**
	 * Add a new value into the registry
	 *
	 * @param string $extensionKey
	 * @param string $tableName
	 * @param string $fieldName
	 * @return boolean
	 */
	public function add($extensionKey, $tableName, $fieldName) {

		$result = FALSE;

		t3lib_div::loadTCA($tableName);

			// Makes sure there is an existing table definition
		if (! empty($GLOBALS['TCA'][$tableName])) {
			$this->registry[$extensionKey][$tableName] = $fieldName;
			$result = TRUE;;
		}
		return $result;
	}

	/**
	 * Return the registry
	 *
	 * @return array
	 */
	public function getRegistry() {
		return $this->registry;
	}

	/**
	 * Tells whether a table has a category registered or not
	 *
	 * @param string $tableName
	 * @return boolean
	 */
	public function isRegistered($tableName) {
		$isRegister = FALSE;
		foreach ($this->registry as $extensions) {
			if (isset($extensions[$tableName])) {
				$isRegister = TRUE;
				break;
			}
		}
		return $isRegister;
	}

	/**
	 * Generates tables definition for all registered tables
	 *
	 * @return string
	 */
	public function getDatabaseTableDefinitions() {
		$sql = '';
		foreach ($this->registry as $extensionKey => $configuration) {
			$sql .= $this->getDatabaseTableDefinition($extensionKey);
		}

		return $sql;
	}

	/**
	 * Generates table definitions for registered tables by an extension.
	 *
	 * @param string $extensionKey
	 * @return string
	 */
	public function getDatabaseTableDefinition($extensionKey) {
		$sql = '';

		foreach ($this->registry[$extensionKey] as $tableName => $fieldName) {
			$sql .= sprintf($this->template, $tableName, $fieldName);
		}

		return $sql;
	}
}
?>