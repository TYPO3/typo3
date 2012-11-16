<?php
namespace TYPO3\CMS\Core\Category;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Fabien Udriot <fabien.udriot@typo3.org>
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
 * Class to register category configurations.
 *
 * @author Fabien Udriot <fabien.udriot@typo3.org>
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class CategoryRegistry implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array
	 */
	protected $registry = array();

	/**
	 * @var string
	 */
	protected $template = '';

	/**
	 * Returns a class instance
	 *
	 * @return \TYPO3\CMS\Core\Category\CategoryRegistry
	 */
	static public function getInstance() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Category\\CategoryRegistry');
	}

	/**
	 * Creates this object.
	 */
	public function __construct() {
		$this->template = str_repeat(PHP_EOL, 3) . 'CREATE TABLE %s (' . PHP_EOL . '  %s int(11) DEFAULT \'0\' NOT NULL' . PHP_EOL . ');' . str_repeat(PHP_EOL, 3);
	}

	/**
	 * Adds a new category configuration to this registry.
	 *
	 * @param string $extensionKey Extension key to be used
	 * @param string $tableName Name of the table to be registered
	 * @param string $fieldName Name of the field to be registered
	 * @return boolean Whether fieldName of tableName is registered
	 */
	public function add($extensionKey, $tableName, $fieldName) {
		$result = FALSE;
		// Makes sure there is an existing table configuration and nothing registered yet:
		if (!empty($GLOBALS['TCA'][$tableName])) {
			if (!$this->isRegistered($tableName, $fieldName)) {
				$this->registry[$extensionKey][$tableName] = $fieldName;
			}
			$result = TRUE;
		}
		return $result;
	}

	/**
	 * Gets the registered category configurations.
	 *
	 * @return array
	 */
	public function get() {
		return $this->registry;
	}

	/**
	 * Gets all extension keys that registered a category configuration.
	 *
	 * @return array
	 */
	public function getExtensionKeys() {
		return array_keys($this->registry);
	}

	/**
	 * Tells whether a table has a category configuration in the registry.
	 *
	 * @param string $tableName Name of the table to be looked up
	 * @param string $fieldName Name of the field to be looked up
	 * @return boolean
	 */
	public function isRegistered($tableName, $fieldName) {
		$isRegistered = FALSE;
		foreach ($this->registry as $configuration) {
			if (!empty($configuration[$tableName]) && $configuration[$tableName] === $fieldName) {
				$isRegistered = TRUE;
				break;
			}
		}
		return $isRegistered;
	}

	/**
	 * Generates tables definitions for all registered tables.
	 *
	 * @return string
	 */
	public function getDatabaseTableDefinitions() {
		$sql = '';
		foreach ($this->getExtensionKeys() as $extensionKey) {
			$sql .= $this->getDatabaseTableDefinition($extensionKey);
		}
		return $sql;
	}

	/**
	 * Generates table definitions for registered tables by an extension.
	 *
	 * @param string $extensionKey Extension key to have the database definitions created for
	 * @return string
	 */
	public function getDatabaseTableDefinition($extensionKey) {
		if (!isset($this->registry[$extensionKey]) || !is_array($this->registry[$extensionKey])) {
			return '';
		}
		$sql = '';
		foreach ($this->registry[$extensionKey] as $tableName => $fieldName) {
			$sql .= sprintf($this->template, $tableName, $fieldName);
		}
		return $sql;
	}

}


?>