<?php
namespace TYPO3\CMS\Core\Category;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class to register category configurations.
 */
class CategoryRegistry implements SingletonInterface
{
    /**
     * @var array
     */
    protected $registry = [];

    /**
     * @var array
     */
    protected $extensions = [];

    /**
     * @var array
     */
    protected $addedCategoryTabs = [];

    /**
     * @var string
     */
    protected $template = '';

    /**
     * Returns a class instance
     *
     * @return CategoryRegistry
     */
    public static function getInstance()
    {
        return GeneralUtility::makeInstance(__CLASS__);
    }

    /**
     * Creates this object.
     */
    public function __construct()
    {
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
     * @param bool $override If TRUE, any category configuration for the same table / field is removed before the new configuration is added
     * @return bool
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function add($extensionKey, $tableName, $fieldName = 'categories', array $options = [], $override = false)
    {
        $didRegister = false;
        if (empty($tableName) || !is_string($tableName)) {
            throw new \InvalidArgumentException('No or invalid table name "' . $tableName . '" given.', 1369122038);
        }
        if (empty($extensionKey) || !is_string($extensionKey)) {
            throw new \InvalidArgumentException('No or invalid extension key "' . $extensionKey . '" given.', 1397836158);
        }

        if ($override) {
            $this->remove($tableName, $fieldName);
        }

        if (!$this->isRegistered($tableName, $fieldName)) {
            $this->registry[$tableName][$fieldName] = $options;
            $this->extensions[$extensionKey][$tableName][$fieldName] = $fieldName;

            if (isset($GLOBALS['TCA'][$tableName]['columns'])) {
                $this->applyTcaForTableAndField($tableName, $fieldName);
                $didRegister = true;
            }
        }

        return $didRegister;
    }

    /**
     * Gets all extension keys that registered a category configuration.
     *
     * @return array
     */
    public function getExtensionKeys()
    {
        return array_keys($this->extensions);
    }

    /**
     * Gets all categorized tables
     *
     * @return array
     */
    public function getCategorizedTables()
    {
        return array_keys($this->registry);
    }

    /**
     * Returns a list of category fields for a given table for populating selector "category_field"
     * in tt_content table (called as itemsProcFunc).
     *
     * @param array $configuration Current field configuration
     * @throws \UnexpectedValueException
     */
    public function getCategoryFieldsForTable(array &$configuration)
    {
        $table = $configuration['config']['itemsProcConfig']['table'] ?? '';
        // Lookup table for legacy menu content element
        if (empty($table)) {
            $menuType = $configuration['row']['menu_type'][0] ?? '';
            // Define the table being looked up from the type of menu
            if ($menuType === 'categorized_pages') {
                $table = 'pages';
            } elseif ($menuType === 'categorized_content') {
                $table = 'tt_content';
            }
        }
        // Return early if no table is defined
        if (empty($table)) {
            throw new \UnexpectedValueException('The given menu_type is not supported.', 1381823570);
        }
        // Loop on all registries and find entries for the correct table
        foreach ($this->registry as $tableName => $fields) {
            if ($tableName === $table) {
                foreach ($fields as $fieldName => $options) {
                    $fieldLabel = $this->getLanguageService()->sL($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['label']);
                    $configuration['items'][] = [$fieldLabel, $fieldName];
                }
            }
        }
    }

    /**
     * Tells whether a table has a category configuration in the registry.
     *
     * @param string $tableName Name of the table to be looked up
     * @param string $fieldName Name of the field to be looked up
     * @return bool
     */
    public function isRegistered($tableName, $fieldName = 'categories')
    {
        return isset($this->registry[$tableName][$fieldName]);
    }

    /**
     * Generates tables definitions for all registered tables.
     *
     * @return string
     */
    public function getDatabaseTableDefinitions()
    {
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
    public function getDatabaseTableDefinition($extensionKey)
    {
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
     * Apply TCA to all registered tables
     *
     * @internal
     */
    public function applyTcaForPreRegisteredTables()
    {
        $this->registerDefaultCategorizedTables();
        foreach ($this->registry as $tableName => $fields) {
            foreach ($fields as $fieldName => $_) {
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
    protected function applyTcaForTableAndField($tableName, $fieldName)
    {
        $this->addTcaColumn($tableName, $fieldName, $this->registry[$tableName][$fieldName]);
        $this->addToAllTCAtypes($tableName, $fieldName, $this->registry[$tableName][$fieldName]);
    }

    /**
     * Add default categorized tables to the registry
     */
    protected function registerDefaultCategorizedTables()
    {
        $defaultCategorizedTables = GeneralUtility::trimExplode(
            ',',
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['defaultCategorizedTables'],
            true
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
     */
    protected function addToAllTCAtypes($tableName, $fieldName, array $options)
    {

        // Makes sure to add more TCA to an existing structure
        if (isset($GLOBALS['TCA'][$tableName]['columns'])) {
            if (empty($options['fieldList'])) {
                $fieldList = $this->addCategoryTab($tableName, $fieldName);
            } else {
                $fieldList = $options['fieldList'];
            }

            $typesList = '';
            if (isset($options['typesList']) && $options['typesList'] !== '') {
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
    protected function addCategoryTab($tableName, $fieldName)
    {
        $fieldList = '';
        if (!isset($this->addedCategoryTabs[$tableName])) {
            $fieldList .= '--div--;LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_category.tabs.category, ';
            $this->addedCategoryTabs[$tableName] = $tableName;
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
     *              + interface: boolean if the category should be included in the "interface" section of the TCA table
     *              + l10n_mode
     *              + l10n_display
     */
    protected function addTcaColumn($tableName, $fieldName, array $options)
    {
        // Makes sure to add more TCA to an existing structure
        if (isset($GLOBALS['TCA'][$tableName]['columns'])) {
            // Take specific label into account
            $label = 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_category.categories';
            if (!empty($options['label'])) {
                $label = $options['label'];
            }

            // Take specific value of exclude flag into account
            $exclude = true;
            if (isset($options['exclude'])) {
                $exclude = (bool)$options['exclude'];
            }

            $fieldConfiguration = empty($options['fieldConfiguration']) ? [] : $options['fieldConfiguration'];

            $columns = [
                $fieldName => [
                    'exclude' => $exclude,
                    'label' => $label,
                    'config' =>  static::getTcaFieldConfiguration($tableName, $fieldName, $fieldConfiguration),
                ],
            ];

            if (isset($options['l10n_mode'])) {
                $columns[$fieldName]['l10n_mode'] = $options['l10n_mode'];
            }
            if (isset($options['l10n_display'])) {
                $columns[$fieldName]['l10n_display'] = $options['l10n_display'];
            }
            if (isset($options['displayCond'])) {
                $columns[$fieldName]['displayCond'] = $options['displayCond'];
            }

            // Register opposite references for the foreign side of a relation
            if (empty($GLOBALS['TCA']['sys_category']['columns']['items']['config']['MM_oppositeUsage'][$tableName])) {
                $GLOBALS['TCA']['sys_category']['columns']['items']['config']['MM_oppositeUsage'][$tableName] = [];
            }
            if (!in_array($fieldName, $GLOBALS['TCA']['sys_category']['columns']['items']['config']['MM_oppositeUsage'][$tableName])) {
                $GLOBALS['TCA']['sys_category']['columns']['items']['config']['MM_oppositeUsage'][$tableName][] = $fieldName;
            }

            // Add field to interface list per default (unless the 'interface' property is FALSE)
            if (
                (!isset($options['interface']) || $options['interface'])
                && !empty($GLOBALS['TCA'][$tableName]['interface']['showRecordFieldList'])
                && !GeneralUtility::inList($GLOBALS['TCA'][$tableName]['interface']['showRecordFieldList'], $fieldName)
            ) {
                $GLOBALS['TCA'][$tableName]['interface']['showRecordFieldList'] .= ',' . $fieldName;
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
    public static function getTcaFieldConfiguration($tableName, $fieldName = 'categories', array $fieldConfigurationOverride = [])
    {
        // Forges a new field, default name is "categories"
        $fieldConfiguration = [
            'type' => 'select',
            'renderType' => 'selectTree',
            'foreign_table' => 'sys_category',
            'foreign_table_where' => ' AND sys_category.sys_language_uid IN (-1, 0) ORDER BY sys_category.sorting ASC',
            'MM' => 'sys_category_record_mm',
            'MM_opposite_field' => 'items',
            'MM_match_fields' => [
                'tablenames' => $tableName,
                'fieldname' => $fieldName,
            ],
            'size' => 20,
            'maxitems' => 9999,
            'treeConfig' => [
                'parentField' => 'parent',
                'appearance' => [
                    'expandAll' => true,
                    'showHeader' => true,
                    'maxLevels' => 99,
                ],
            ],
        ];

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
    public function addCategoryDatabaseSchemaToTablesDefinition(array $sqlString)
    {
        $this->registerDefaultCategorizedTables();
        $sqlString[] = $this->getDatabaseTableDefinitions();
        return ['sqlString' => $sqlString];
    }

    /**
     * A slot method to inject the required category database fields of an
     * extension to the tables definition string
     *
     * @param array $sqlString
     * @param string $extensionKey
     * @return array
     */
    public function addExtensionCategoryDatabaseSchemaToTablesDefinition(array $sqlString, $extensionKey)
    {
        $sqlString[] = $this->getDatabaseTableDefinition($extensionKey);
        return ['sqlString' => $sqlString, 'extensionKey' => $extensionKey];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Removes the given field in the given table from the registry if it is found.
     *
     * @param string $tableName The name of the table for which the registration should be removed.
     * @param string $fieldName The name of the field for which the registration should be removed.
     */
    protected function remove($tableName, $fieldName)
    {
        if (!$this->isRegistered($tableName, $fieldName)) {
            return;
        }

        unset($this->registry[$tableName][$fieldName]);

        foreach ($this->extensions as $extensionKey => $tableFieldConfig) {
            foreach ($tableFieldConfig as $extTableName => $fieldNameArray) {
                if ($extTableName === $tableName && isset($fieldNameArray[$fieldName])) {
                    unset($this->extensions[$extensionKey][$tableName][$fieldName]);
                    break;
                }
            }
        }

        // If no more fields are configured we unregister the categories tab.
        if (empty($this->registry[$tableName]) && isset($this->addedCategoryTabs[$tableName])) {
            unset($this->addedCategoryTabs[$tableName]);
        }
    }
}
