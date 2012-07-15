<?php
namespace TYPO3\CMS\Core\Category;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Fabien Udriot <fabien.udriot@typo3.org>
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
	 * @param array $options Additional configuration options
	 *              + fieldList: field configuration to be added to showitems
	 *              + typesList: list of types that shall visualize the categories field
	 *              + position: insert position of the categories field
	 *              + fieldConfiguration: TCA field config array to override defaults
	 * @return bool
	 */
	public function add($extensionKey, $tableName, $fieldName = 'categories', $options = array()) {
		$result = FALSE;

			// Makes sure there is an existing table configuration and nothing registered yet:
		if (!$this->isRegistered($tableName, $fieldName)) {
			$this->registry[$extensionKey][$tableName] = array (
				'fieldName' => $fieldName,
				'options' => $options,
			);
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
	 * Gets all categorized tables
	 *
	 * @return array
	 */
	public function getCategorizedTables() {
		$categorizedTables = array();

		foreach ($this->registry as $registry) {
			$categorizedTables = array_merge($categorizedTables, array_keys($registry));
		}

		return $categorizedTables;
	}

	/**
	 * Tells whether a table has a category configuration in the registry.
	 *
	 * @param string $tableName Name of the table to be looked up
	 * @param string $fieldName Name of the field to be looked up
	 * @return boolean
	 */
	public function isRegistered($tableName, $fieldName = 'categories') {
		$isRegistered = FALSE;
		foreach ($this->registry as $configuration) {
			if (!empty($configuration[$tableName]['fieldName']) && $configuration[$tableName]['fieldName'] === $fieldName) {
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

		foreach ($this->registry[$extensionKey] as $tableName => $tableInfo) {
			$sql .= sprintf($this->template, $tableName, $tableInfo['fieldName']);
		}
		return $sql;
	}

	/**
	 * Apply TCA to all registered tables
	 *
	 * @return void
	 */
	public function applyTca() {

		$this->registerDefaultCategorizedTables();

		foreach ($this->registry as $registry) {
			foreach ($registry as $tableName => $tableInfo) {
				$this->addTcaColumn($tableName, $tableInfo['fieldName'], $tableInfo['options']);
				$this->addToAllTCAtypes($tableName, $tableInfo['fieldName'], $tableInfo['options']);
			}
		}
	}

	/**
	 * Add default categorized tables to the registry
	 *
	 * @return void
	 */
	protected function registerDefaultCategorizedTables() {

		$defaultCategorizedTables = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultCategorizedTables']);
		foreach ($defaultCategorizedTables as $defaultCategorizedTable) {
			if (!$this->isRegistered($defaultCategorizedTable)) {
				$this->add('core', $defaultCategorizedTable, 'categories');
			}
		}
	}

	/**
	 * Add a new field into the TCA types -> showitem
	 *
	 * @param string $tableName Name of the table to be categorized
	 * @param string $fieldName Name of the field to be used to store categories
	 * @param array $options Additional configuration options
	 *              + fieldList: field configuration to be added to showitems
	 *              + typesList: list of types that shall visualize the categories field
	 *              + position: insert position of the categories field
	 * @return void
	 */
	protected function addToAllTCAtypes($tableName, $fieldName, $options) {

		// Makes sure to add more TCA to an existing structure
		if (isset($GLOBALS['TCA'][$tableName]['columns'])) {

			$fieldList = '--div--;LLL:EXT:lang/locallang_tca.xlf:sys_category.tabs.category, ' . $fieldName;
			if (!empty($options['fieldList'])) {
				$fieldList = $options['fieldList'];
			}

			$typesList = '';
			if (!empty($options['typesList'])) {
				$typesList = $options['typesList'];
			}

			$position = '';
			if (!empty($options['position'])) {
				$position = $options['position'];
			}

			// Makes the new "categories" field to be visible in TSFE.
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes($tableName, $fieldList, $typesList, $position);

		}
	}

	/**
	 * Add a new TCA Column
	 *
	 * @param string $tableName Name of the table to be categorized
	 * @param string $fieldName Name of the field to be used to store categories
	 * @param array $options Additional configuration options
	 *              + fieldConfiguration: TCA field config array to override defaults
	 * @return void
	 */
	protected function addTcaColumn($tableName, $fieldName, $options) {

		// Makes sure to add more TCA to an existing structure
		if (isset($GLOBALS['TCA'][$tableName]['columns'])) {

			// Forges a new field, default name is "categories"
			$fieldConfiguration = array(
				'type' => 'select',
				'foreign_table' => 'sys_category',
				'foreign_table_where' => ' ORDER BY sys_category.title ASC',
				'MM' => 'sys_category_record_mm',
				'MM_opposite_field' => 'items',
				'MM_match_fields' => array('tablenames' => $tableName),
				'size' => 10,
				'autoSizeMax' => 50,
				'maxitems' => 9999,
				'renderMode' => 'tree',
				'treeConfig' => array(
					'parentField' => 'parent',
					'appearance' => array(
						'expandAll' => TRUE,
						'showHeader' => TRUE,
					),
				),
				'wizards' => array(
					'_PADDING' => 1,
					'_VERTICAL' => 1,
					'edit' => array(
						'type' => 'popup',
						'title' => 'Edit',
						'script' => 'wizard_edit.php',
						'icon' => 'edit2.gif',
						'popup_onlyOpenIfSelected' => 1,
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
					'add' => Array(
						'type' => 'script',
						'title' => 'Create new',
						'icon' => 'add.gif',
						'params' => array(
							'table' => 'sys_category',
							'pid' => '###CURRENT_PID###',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
				),
			);

			if (!empty($options['fieldConfiguration'])) {
				$fieldConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule(
					$fieldConfiguration,
					$options['fieldConfiguration']
				);
			}

			$columns = array(
				$fieldName => array(
					'exclude' => 0,
					'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_category.categories',
					'config' => $fieldConfiguration,
				),
			);

			// Adding fields to an existing table definition
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($tableName, $columns);
		}
	}
}

?>