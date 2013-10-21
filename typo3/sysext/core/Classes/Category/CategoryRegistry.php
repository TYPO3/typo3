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
	 */
	public function add($extensionKey, $tableName, $fieldName = 'categories', array $options = array()) {
		$result = FALSE;

		if ($tableName === '') {
			throw new \InvalidArgumentException('TYPO3\\CMS\\Core\\Category\\CategoryRegistry No tableName given.', 1369122038);
		}

		// Makes sure nothing was registered yet.
		if (!$this->isRegistered($tableName, $fieldName)) {
			$this->registry[$extensionKey][$tableName][$fieldName] = $options;
			$result = TRUE;
		}
		return $result;
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
		if ($configuration['row']['menu_type'] == 9) {
			$table = 'pages';
		} elseif ($configuration['row']['menu_type'] == 'categorized_content') {
			$table = 'tt_content';
		}
		// Return early if no table is defined
		if (empty($table)) {
			throw new \UnexpectedValueException('The given menu_type is not supported.', 1381823570);
		}
		// Loop on all registries and find entries for the correct table
		foreach ($this->registry as $registry) {
			foreach ($registry as $tableName => $fields) {
				if ($tableName === $table) {
					foreach ($fields as $fieldName => $options) {
						$fieldLabel = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['label']);
						$configuration['items'][] = array($fieldLabel, $fieldName);
					}
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
		$isRegistered = FALSE;
		foreach ($this->registry as $configuration) {
			if (isset($configuration[$tableName][$fieldName])) {
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

		foreach ($this->registry[$extensionKey] as $tableName => $fields) {
			foreach (array_keys($fields) as $fieldName) {
				$sql .= sprintf($this->template, $tableName, $fieldName);
			}
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
			foreach ($registry as $tableName => $fields) {
				foreach ($fields as $fieldName => $options) {
					$this->addTcaColumn($tableName, $fieldName, $options);
					$this->addToAllTCAtypes($tableName, $fieldName, $options);
				}
			}
		}
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
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes($tableName, $fieldList, $typesList, $position);

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
					),
				),
			);

			if (!empty($options['fieldConfiguration'])) {
				$fieldConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule(
					$fieldConfiguration,
					$options['fieldConfiguration']
				);
			}

			$label = 'LLL:EXT:lang/locallang_tca.xlf:sys_category.categories';
			if (!empty($options['label'])) {
				$label = $options['label'];
			}

			$columns = array(
				$fieldName => array(
					'exclude' => 0,
					'label' => $label,
					'config' => $fieldConfiguration,
				),
			);

			// Adding fields to an existing table definition
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($tableName, $columns);
		}
	}

	/**
	 * A slot method to inject the required category database fields to the
	 * tables defintion string
	 *
	 * @param array $sqlString
	 * @return array
	 */
	public function addCategoryDatabaseSchemaToTablesDefintion(array $sqlString) {
		$sqlString[] = $this->getDatabaseTableDefinitions();
		return array('sqlString' => $sqlString);
	}

	/**
	 * A slot method to inject the required category database fields of an
	 * extension to the tables defintion string
	 *
	 * @param array $sqlString
	 * @param string $extensionKey
	 * @return array
	 */
	public function addExtensionCategoryDatabaseSchemaToTablesDefintion(array $sqlString, $extensionKey) {
		$sqlString[] = $this->getDatabaseTableDefinition($extensionKey);
		return array('sqlString' => $sqlString, 'extensionKey' => $extensionKey);
	}
}
