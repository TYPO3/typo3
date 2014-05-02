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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

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
	 * @var array
	 */
	protected $extensions = array();

	/**
	 * @var array
	 */
	protected $addedCategoryTabs = array();

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
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(__CLASS__);
	}

	/**
	 * Creates this object.
	 */
	public function __construct() {
		$this->template = str_repeat(PHP_EOL, 3) . 'CREATE TABLE %s (' . PHP_EOL
			. '  %s int(11) DEFAULT \'0\' NOT NULL' . PHP_EOL . ');' . str_repeat(PHP_EOL, 3);
	}

	/**
	 * Adds a new category configuration to this registry.
	 * TCA changes are directly applied
	 *
	 * @param string $extensionKey Extension key to be used
	 * @param string $tableName Name of the table to be registered
	 * @param string $fieldName Name of the field to be registered
	 * @param array $options Additional configuration options
	 *              + fieldList: field configuration to be added to showitems
	 *              + typesList: list of types that shall visualize the categories field
	 *              + position: insert position of the categories field
	 *              + label: backend label of the categories field
	 *              + fieldConfiguration: TCA field config array to override defaults
	 * @return boolean
	 * @throws \InvalidArgumentException
	 * @throws \RuntimeException
	 */
	public function add($extensionKey, $tableName, $fieldName = 'categories', array $options = array()) {
		$didRegister = FALSE;
		if (empty($tableName) || !is_string($tableName)) {
			throw new \InvalidArgumentException('No or invalid table name "' . $tableName . '" given.', 1369122038);
		}
		if (empty($extensionKey) || !is_string($extensionKey)) {
			throw new \InvalidArgumentException('No or invalid extension key "' . $extensionKey . '" given.', 1397836158);
		}

		if (!$this->isRegistered($tableName, $fieldName)) {
			$this->registry[$tableName][$fieldName] = $options;
			$this->extensions[$extensionKey][$tableName][$fieldName] = $fieldName;

			if (!isset($GLOBALS['TCA'][$tableName]['columns']) && isset($GLOBALS['TCA'][$tableName]['ctrl']['dynamicConfigFile'])) {
				// Handle deprecated old style dynamic TCA column loading.
				ExtensionManagementUtility::loadNewTcaColumnsConfigFiles();
			}

			if (isset($GLOBALS['TCA'][$tableName]['columns'])) {
				$this->applyTcaForTableAndField($tableName, $fieldName);
				$didRegister = TRUE;
			}
		}

		return $didRegister;
	}

	/**
	 * Gets the registered category configurations.
	 *
	 * @deprecated since 6.2 will be removed two versions later - Use ->isRegistered to get information about registered category fields.
	 * @return array
	 */
	public function get() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		return $this->registry;
	}

	/**
	 * Gets all extension keys that registered a category configuration.
	 *
	 * @return array
	 */
	public function getExtensionKeys() {
		return array_keys($this->extensions);
	}

	/**
	 * Gets all categorized tables
	 *
	 * @return array
	 */
	public function getCategorizedTables() {
		return array_keys($this->registry);
	}

	/**
	 * Returns a list of category fields for a given table for populating selector "category_field"
	 * in tt_content table (called as itemsProcFunc).
	 *
	 * @param array $configuration Current field configuration
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $formObject Back-reference to the calling object
	 * @throws \UnexpectedValueException
	 * @return void
	 */
	public function getCategoryFieldsForTable(array &$configuration, \TYPO3\CMS\Backend\Form\FormEngine $formObject) {
		$table = '';
		// Define the table being looked up from the type of menu
		if ($configuration['row']['menu_type'] == 'categorized_pages') {
			$table = 'pages';
		} elseif ($configuration['row']['menu_type'] == 'categorized_content') {
			$table = 'tt_content';
		}
		// Return early if no table is defined
		if (empty($table)) {
			throw new \UnexpectedValueException('The given menu_type is not supported.', 1381823570);
		}
		// Loop on all registries and find entries for the correct table
		foreach ($this->registry as $tableName => $fields) {
			if ($tableName === $table) {
				foreach ($fields as $fieldName => $options) {
					$fieldLabel = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['label']);
					$configuration['items'][] = array($fieldLabel, $fieldName);
				}
			}
		}
	}

	/**
	 * Tells whether a table has a category configuration in the registry.
	 *
	 * @param string $tableName Name of the table to be looked up
	 * @param string $fieldName Name of the field to be looked up
	 * @return boolean
	 */
	public function isRegistered($tableName, $fieldName = 'categories') {
		return isset($this->registry[$tableName][$fieldName]);
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
		if (!isset($this->extensions[$extensionKey]) || !is_array($this->extensions[$extensionKey])) {
			return '';
		}
		$sql = '';

		foreach ($this->extensions[$extensionKey] as $tableName => $fields) {
			foreach ($fields as $fieldName) {
				$sql .= sprintf($this->template, $tableName, $fieldName);
			}
		}
		return $sql;
	}

	/**
	 * @deprecated Since 6.2.2. This method was never intended to be called by extensions. Is is now deprecated and will be removed without substitution after two versions.
	 */
	public function applyTca() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
	}

	/**
	 * Apply TCA to all registered tables
	 *
	 * @return void
	 * @internal
	 */
	public function applyTcaForPreRegisteredTables() {
		$this->registerDefaultCategorizedTables();
		foreach ($this->registry as $tableName => $fields) {
			foreach (array_keys($fields) as $fieldName) {
				$this->applyTcaForTableAndField($tableName, $fieldName);
			}
		}
	}

	/**
	 * Applies the additions directly to the TCA
	 *
	 * @param string $tableName
	 * @param string $fieldName
	 */
	protected function applyTcaForTableAndField($tableName, $fieldName) {
		$this->addTcaColumn($tableName, $fieldName, $this->registry[$tableName][$fieldName]);
		$this->addToAllTCAtypes($tableName, $fieldName, $this->registry[$tableName][$fieldName]);
	}

	/**
	 * Add default categorized tables to the registry
	 *
	 * @return void
	 */
	protected function registerDefaultCategorizedTables() {
		$defaultCategorizedTables = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(
			',',
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultCategorizedTables'],
			TRUE
		);
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
	protected function addToAllTCAtypes($tableName, $fieldName, array $options) {

		// Makes sure to add more TCA to an existing structure
		if (isset($GLOBALS['TCA'][$tableName]['columns'])) {

			if (empty($options['fieldList'])) {
				$fieldList = $this->addCategoryTab($tableName, $fieldName);
			} else {
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
			ExtensionManagementUtility::addToAllTCAtypes($tableName, $fieldList, $typesList, $position);

		}
	}

	/**
	 * Creates the 'fieldList' string for $fieldName which includes a categories tab.
	 * But only one categories tab is added per table.
	 *
	 * @param string $tableName
	 * @param string $fieldName
	 * @return string
	 */
	protected function addCategoryTab($tableName, $fieldName) {
		$fieldList = '';
		if (!in_array($tableName, $this->addedCategoryTabs)) {
			$fieldList .= '--div--;LLL:EXT:lang/locallang_tca.xlf:sys_category.tabs.category, ';
			$this->addedCategoryTabs[] = $tableName;
		}
		$fieldList .= $fieldName;
		return $fieldList;
	}

	/**
	 * Add a new TCA Column
	 *
	 * @param string $tableName Name of the table to be categorized
	 * @param string $fieldName Name of the field to be used to store categories
	 * @param array $options Additional configuration options
	 *              + fieldConfiguration: TCA field config array to override defaults
	 *              + label: backend label of the categories field
	 * @return void
	 */
	protected function addTcaColumn($tableName, $fieldName, array $options) {
		// Makes sure to add more TCA to an existing structure
		if (isset($GLOBALS['TCA'][$tableName]['columns'])) {
			// Take specific label into account
			$label = 'LLL:EXT:lang/locallang_tca.xlf:sys_category.categories';
			if (!empty($options['label'])) {
				$label = $options['label'];
			}

			// Take specific value of exclude flag into account
			$exclude = TRUE;
			if (isset($options['exclude'])) {
				$exclude = (bool)$options['exclude'];
			}

			$fieldConfiguration = empty($options['fieldConfiguration']) ? array() : $options['fieldConfiguration'];

			$columns = array(
				$fieldName => array(
					'exclude' => $exclude,
					'label' => $label,
					'config' =>  static::getTcaFieldConfiguration($tableName, $fieldName, $fieldConfiguration),
				),
			);

			if (empty($GLOBALS['TCA']['sys_category']['columns']['items']['config']['MM_oppositeUsage'][$tableName])) {
				$GLOBALS['TCA']['sys_category']['columns']['items']['config']['MM_oppositeUsage'][$tableName] = array();
			}
			if (!in_array($fieldName, $GLOBALS['TCA']['sys_category']['columns']['items']['config']['MM_oppositeUsage'][$tableName])) {
				$GLOBALS['TCA']['sys_category']['columns']['items']['config']['MM_oppositeUsage'][$tableName][] = $fieldName;
			}

			// Adding fields to an existing table definition
			ExtensionManagementUtility::addTCAcolumns($tableName, $columns);
		}
	}

	/**
	 * Get the config array for given table and field.
	 * This method does NOT take care of adding sql fields, adding the field to TCA types
	 * nor does it set the MM_oppositeUsage in the sys_category TCA. This has to be taken care of manually!
	 *
	 * @param string $tableName The table name
	 * @param string $fieldName The field name (default categories)
	 * @param array $fieldConfigurationOverride Changes to the default configuration
	 * @return array
	 * @api
	 */
	static public function getTcaFieldConfiguration($tableName, $fieldName = 'categories', array $fieldConfigurationOverride = array()) {
		// Forges a new field, default name is "categories"
		$fieldConfiguration = array(
			'type' => 'select',
			'foreign_table' => 'sys_category',
			'foreign_table_where' => ' AND sys_category.sys_language_uid IN (-1, 0) ORDER BY sys_category.sorting ASC',
			'MM' => 'sys_category_record_mm',
			'MM_opposite_field' => 'items',
			'MM_match_fields' => array(
				'tablenames' => $tableName,
				'fieldname' => $fieldName,
			),
			'size' => 10,
			'autoSizeMax' => 50,
			'maxitems' => 9999,
			'renderMode' => 'tree',
			'treeConfig' => array(
				'parentField' => 'parent',
				'appearance' => array(
					'expandAll' => TRUE,
					'showHeader' => TRUE,
					'maxLevels' => 99,
				),
			),
		);

		// Merge changes to TCA configuration
		if (!empty($fieldConfigurationOverride)) {
			ArrayUtility::mergeRecursiveWithOverrule(
				$fieldConfiguration,
				$fieldConfigurationOverride
			);
		}

		return $fieldConfiguration;
	}

	/**
	 * A slot method to inject the required category database fields to the
	 * tables definition string
	 *
	 * @param array $sqlString
	 * @return array
	 */
	public function addCategoryDatabaseSchemaToTablesDefinition(array $sqlString) {
		$this->registerDefaultCategorizedTables();
		$sqlString[] = $this->getDatabaseTableDefinitions();
		return array('sqlString' => $sqlString);
	}

	/**
	 * A slot method to inject the required category database fields of an
	 * extension to the tables definition string
	 *
	 * @param array $sqlString
	 * @param string $extensionKey
	 * @return array
	 */
	public function addExtensionCategoryDatabaseSchemaToTablesDefinition(array $sqlString, $extensionKey) {
		$sqlString[] = $this->getDatabaseTableDefinition($extensionKey);
		return array('sqlString' => $sqlString, 'extensionKey' => $extensionKey);
	}
}
