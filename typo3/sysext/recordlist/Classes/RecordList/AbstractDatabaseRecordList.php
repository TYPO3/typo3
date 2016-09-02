<?php
namespace TYPO3\CMS\Recordlist\RecordList;

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

use TYPO3\CMS\Backend\RecordList\AbstractRecordList;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Child class for rendering of Web > List (not the final class)
 * Shared between Web>List and Web>Page
 * @see \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList
 */
class AbstractDatabaseRecordList extends AbstractRecordList
{
    /**
     * Specify a list of tables which are the only ones allowed to be displayed.
     *
     * @var string
     */
    public $tableList = '';

    /**
     * Return URL
     *
     * @var string
     */
    public $returnUrl = '';

    /**
     * Thumbnails on records containing files (pictures)
     *
     * @var bool
     */
    public $thumbs = 0;

    /**
     * default Max items shown per table in "multi-table mode", may be overridden by tables.php
     *
     * @var int
     */
    public $itemsLimitPerTable = 20;

    /**
     * default Max items shown per table in "single-table mode", may be overridden by tables.php
     *
     * @var int
     */
    public $itemsLimitSingleTable = 100;

    /**
     * Current script name
     *
     * @var string
     */
    public $script = 'index.php';

    /**
     * Indicates if all available fields for a user should be selected or not.
     *
     * @var int
     */
    public $allFields = 0;

    /**
     * Whether to show localization view or not
     *
     * @var bool
     */
    public $localizationView = false;

    /**
     * If set, csvList is outputted.
     *
     * @var bool
     */
    public $csvOutput = false;

    /**
     * Field, to sort list by
     *
     * @var string
     */
    public $sortField;

    /**
     * Field, indicating to sort in reverse order.
     *
     * @var bool
     */
    public $sortRev;

    /**
     * Containing which fields to display in extended mode
     *
     * @var string[]
     */
    public $displayFields;

    /**
     * String, can contain the field name from a table which must have duplicate values marked.
     *
     * @var string
     */
    public $duplicateField;

    /**
     * Page id
     *
     * @var int
     */
    public $id;

    /**
     * Tablename if single-table mode
     *
     * @var string
     */
    public $table = '';

    /**
     * If TRUE, records are listed only if a specific table is selected.
     *
     * @var bool
     */
    public $listOnlyInSingleTableMode = false;

    /**
     * Pointer for browsing list
     *
     * @var int
     */
    public $firstElementNumber = 0;

    /**
     * Search string
     *
     * @var string
     */
    public $searchString = '';

    /**
     * Levels to search down.
     *
     * @var int
     */
    public $searchLevels = '';

    /**
     * Number of records to show
     *
     * @var int
     */
    public $showLimit = 0;

    /**
     * Query part for either a list of ids "pid IN (1,2,3)" or a single id "pid = 123" from
     * which to select/search etc. (when search-levels are set high). See start()
     *
     * @var string
     */
    public $pidSelect = '';

    /**
     * Page select permissions
     *
     * @var string
     */
    public $perms_clause = '';

    /**
     * Some permissions...
     *
     * @var int
     */
    public $calcPerms = 0;

    /**
     * Mode for what happens when a user clicks the title of a record.
     *
     * @var string
     */
    public $clickTitleMode = '';

    /**
     * Shared module configuration, used by localization features
     *
     * @var array
     */
    public $modSharedTSconfig = [];

    /**
     * Loaded with page record with version overlay if any.
     *
     * @var string[]
     */
    public $pageRecord = [];

    /**
     * Tables which should not get listed
     *
     * @var string
     */
    public $hideTables = '';

    /**
     * Tables which should not list their translations
     *
     * @var string
     */
    public $hideTranslations = '';

    /**
     * TSconfig which overwrites TCA-Settings
     *
     * @var mixed[][]
     */
    public $tableTSconfigOverTCA = [];

    /**
     * Array of collapsed / uncollapsed tables in multi table view
     *
     * @var int[][]
     */
    public $tablesCollapsed = [];

    /**
     * JavaScript code accumulation
     *
     * @var string
     */
    public $JScode = '';

    /**
     * HTML output
     *
     * @var string
     */
    public $HTMLcode = '';

    /**
     * "LIMIT " in SQL...
     *
     * @var int
     */
    public $iLimit = 0;

    /**
     * Counting the elements no matter what...
     *
     * @var int
     */
    public $eCounter = 0;

    /**
     * Set to the total number of items for a table when selecting.
     *
     * @var string
     */
    public $totalItems = '';

    /**
     * Cache for record path
     *
     * @var mixed[]
     */
    public $recPath_cache = [];

    /**
     * Fields to display for the current table
     *
     * @var string[]
     */
    public $setFields = [];

    /**
     * Used for tracking next/prev uids
     *
     * @var int[][]
     */
    public $currentTable = [];

    /**
     * Used for tracking duplicate values of fields
     *
     * @var string[]
     */
    public $duplicateStack = [];

    /**
     * @var array[] Module configuration
     */
    public $modTSconfig;

    /**
     * Override/add urlparameters in listUrl() method
     * @var string[]
     */
    protected $overrideUrlParameters = [];

    /**
     * Array with before/after setting for tables
     * Structure:
     * 'tableName' => [
     *    'before' => ['A', ...]
     *    'after' => []
     *  ]
     * @var array[]
     */
    protected $tableDisplayOrder = [];

    /**
     * Initializes the list generation
     *
     * @param int $id Page id for which the list is rendered. Must be >= 0
     * @param string $table Tablename - if extended mode where only one table is listed at a time.
     * @param int $pointer Browsing pointer.
     * @param string $search Search word, if any
     * @param int $levels Number of levels to search down the page tree
     * @param int $showLimit Limit of records to be listed.
     * @return void
     */
    public function start($id, $table, $pointer, $search = '', $levels = 0, $showLimit = 0)
    {
        $backendUser = $this->getBackendUserAuthentication();
        $db = $this->getDatabaseConnection();
        // Setting internal variables:
        // sets the parent id
        $this->id = (int)$id;
        if ($GLOBALS['TCA'][$table]) {
            // Setting single table mode, if table exists:
            $this->table = $table;
        }
        $this->firstElementNumber = $pointer;
        $this->searchString = trim($search);
        $this->searchLevels = (int)$levels;
        $this->showLimit = MathUtility::forceIntegerInRange($showLimit, 0, 10000);
        // Setting GPvars:
        $this->csvOutput = (bool)GeneralUtility::_GP('csv');
        $this->sortField = GeneralUtility::_GP('sortField');
        $this->sortRev = GeneralUtility::_GP('sortRev');
        $this->displayFields = GeneralUtility::_GP('displayFields');
        $this->duplicateField = GeneralUtility::_GP('duplicateField');
        if (GeneralUtility::_GP('justLocalized')) {
            $this->localizationRedirect(GeneralUtility::_GP('justLocalized'));
        }
        // Init dynamic vars:
        $this->counter = 0;
        $this->JScode = '';
        $this->HTMLcode = '';
        // Limits
        if (isset($this->modTSconfig['properties']['itemsLimitPerTable'])) {
            $this->itemsLimitPerTable = MathUtility::forceIntegerInRange((int)$this->modTSconfig['properties']['itemsLimitPerTable'], 1, 10000);
        }
        if (isset($this->modTSconfig['properties']['itemsLimitSingleTable'])) {
            $this->itemsLimitSingleTable = MathUtility::forceIntegerInRange((int)$this->modTSconfig['properties']['itemsLimitSingleTable'], 1, 10000);
        }
        // Set search levels:
        $searchLevels = $this->searchLevels;
        $this->perms_clause = $backendUser->getPagePermsClause(1);
        // This will hide records from display - it has nothing to do with user rights!!
        if ($pidList = $backendUser->getTSConfigVal('options.hideRecords.pages')) {
            if ($pidList = $db->cleanIntList($pidList)) {
                $this->perms_clause .= ' AND pages.uid NOT IN (' . $pidList . ')';
            }
        }
        // Get configuration of collapsed tables from user uc and merge with sanitized GP vars
        $this->tablesCollapsed = is_array($backendUser->uc['moduleData']['list']) ? $backendUser->uc['moduleData']['list'] : [];
        $collapseOverride = GeneralUtility::_GP('collapse');
        if (is_array($collapseOverride)) {
            foreach ($collapseOverride as $collapseTable => $collapseValue) {
                if (is_array($GLOBALS['TCA'][$collapseTable]) && ($collapseValue == 0 || $collapseValue == 1)) {
                    $this->tablesCollapsed[$collapseTable] = $collapseValue;
                }
            }
            // Save modified user uc
            $backendUser->uc['moduleData']['list'] = $this->tablesCollapsed;
            $backendUser->writeUC($backendUser->uc);
            $returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
            if ($returnUrl !== '') {
                HttpUtility::redirect($returnUrl);
            }
        }
        if ($searchLevels > 0) {
            $allowedMounts = $this->getSearchableWebmounts($this->id, $searchLevels, $this->perms_clause);
            $pidList = implode(',', $db->cleanIntArray($allowedMounts));
            $this->pidSelect = 'pid IN (' . $pidList . ')';
        } elseif ($searchLevels < 0) {
            // Search everywhere
            $this->pidSelect = '1=1';
        } else {
            $this->pidSelect = 'pid=' . (int)$id;
        }
        // Initialize languages:
        if ($this->localizationView) {
            $this->initializeLanguages();
        }
    }

    /**
     * Traverses the table(s) to be listed and renders the output code for each:
     * The HTML is accumulated in $this->HTMLcode
     * Finishes off with a stopper-gif
     *
     * @return void
     */
    public function generateList()
    {
        // Set page record in header
        $this->pageRecord = BackendUtility::getRecordWSOL('pages', $this->id);
        $hideTablesArray = GeneralUtility::trimExplode(',', $this->hideTables);

        $backendUser = $this->getBackendUserAuthentication();

        // pre-process tables and add sorting instructions
        $tableNames = array_flip(array_keys($GLOBALS['TCA']));
        foreach ($tableNames as $tableName => &$config) {
            $hideTable = false;

            // Checking if the table should be rendered:
            // Checks that we see only permitted/requested tables:
            if ($this->table && $tableName !== $this->table
                || $this->tableList && !GeneralUtility::inList($this->tableList, $tableName)
                || !$backendUser->check('tables_select', $tableName)
            ) {
                $hideTable = true;
            }

            if (!$hideTable) {
                // Don't show table if hidden by TCA ctrl section
                // Don't show table if hidden by pageTSconfig mod.web_list.hideTables
                $hideTable = $hideTable || !empty($GLOBALS['TCA'][$tableName]['ctrl']['hideTable']) || in_array($tableName, $hideTablesArray, true);
                // Override previous selection if table is enabled or hidden by TSconfig TCA override mod.web_list.table
                if (isset($this->tableTSconfigOverTCA[$tableName . '.']['hideTable'])) {
                    $hideTable = (bool)$this->tableTSconfigOverTCA[$tableName . '.']['hideTable'];
                }
            }
            if ($hideTable) {
                unset($tableNames[$tableName]);
            } else {
                if (isset($this->tableDisplayOrder[$tableName])) {
                    // Copy display order information
                    $tableNames[$tableName] = $this->tableDisplayOrder[$tableName];
                } else {
                    $tableNames[$tableName] = [];
                }
            }
        }
        unset($config);

        $orderedTableNames = GeneralUtility::makeInstance(DependencyOrderingService::class)->orderByDependencies($tableNames);

        $db = $this->getDatabaseConnection();
        foreach ($orderedTableNames as $tableName => $_) {
            // check if we are in single- or multi-table mode
            if ($this->table) {
                $this->iLimit = isset($GLOBALS['TCA'][$tableName]['interface']['maxSingleDBListItems']) ? (int)$GLOBALS['TCA'][$tableName]['interface']['maxSingleDBListItems'] : $this->itemsLimitSingleTable;
            } else {
                // if there are no records in table continue current foreach
                $firstRow = $db->exec_SELECTgetSingleRow(
                    'uid',
                    $tableName,
                    $this->pidSelect . BackendUtility::deleteClause($tableName) . BackendUtility::versioningPlaceholderClause($tableName)
                );
                if ($firstRow === false) {
                    continue;
                }
                $this->iLimit = isset($GLOBALS['TCA'][$tableName]['interface']['maxDBListItems']) ? (int)$GLOBALS['TCA'][$tableName]['interface']['maxDBListItems'] : $this->itemsLimitPerTable;
            }
            if ($this->showLimit) {
                $this->iLimit = $this->showLimit;
            }
            // Setting fields to select:
            if ($this->allFields) {
                $fields = $this->makeFieldList($tableName);
                $fields[] = 'tstamp';
                $fields[] = 'crdate';
                $fields[] = '_PATH_';
                $fields[] = '_CONTROL_';
                if (is_array($this->setFields[$tableName])) {
                    $fields = array_intersect($fields, $this->setFields[$tableName]);
                } else {
                    $fields = [];
                }
            } else {
                $fields = [];
            }
            // Find ID to use (might be different for "versioning_followPages" tables)
            if ($this->searchLevels === 0) {
                $this->pidSelect = 'pid=' . (int)$this->id;
            }
            // Finally, render the list:
            $this->HTMLcode .= $this->getTable($tableName, $this->id, implode(',', $fields));
        }
    }

    /**
     * To be implemented in extending classes.
     *
     * @param string $tableName
     * @param int $id
     * @param string $fields List of fields to show in the listing. Pseudo fields will be added including the record header.
     * @return string HTML code
     */
    public function getTable($tableName, $id, $fields = '')
    {
        return '';
    }

    /**
     * Creates the search box
     *
     * @param bool $formFields If TRUE, the search box is wrapped in its own form-tags
     * @return string HTML for the search box
     */
    public function getSearchBox($formFields = true)
    {
        /** @var $iconFactory IconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $lang = $this->getLanguageService();
        // Setting form-elements, if applicable:
        $formElements = ['', ''];
        if ($formFields) {
            $formElements = ['<form action="' . htmlspecialchars($this->listURL('', '-1', 'firstElementNumber,search_field')) . '" method="post">', '</form>'];
        }
        // Make level selector:
        $opt = [];
        $parts = explode('|', $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.enterSearchLevels'));
        foreach ($parts as $kv => $label) {
            $opt[] = '<option value="' . $kv . '"' . ($kv === $this->searchLevels ? ' selected="selected"' : '') . '>' . htmlspecialchars($label) . '</option>';
        }
        $lMenu = '<select class="form-control" name="search_levels" title="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.title.search_levels', true) . '" id="search_levels">' . implode('', $opt) . '</select>';
        // Table with the search box:
        $content = '<div class="db_list-searchbox-form db_list-searchbox-toolbar module-docheader-bar module-docheader-bar-search t3js-module-docheader-bar t3js-module-docheader-bar-search" id="db_list-searchbox-toolbar" style="display: ' . ($this->searchString == '' ? 'none' : 'block') . ';">
			' . $formElements[0] . '
                <div id="typo3-dblist-search">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <div class="form-inline form-inline-spaced">
                                <div class="form-group">
									<input class="form-control" type="search" placeholder="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.enterSearchString', true) . '" title="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.title.searchString', true) . '" name="search_field" id="search_field" value="' . htmlspecialchars($this->searchString) . '" />
                                </div>
                                <div class="form-group">
									<label for="search_levels">' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.label.search_levels', true) . ': </label>
									' . $lMenu . '
                                </div>
                                <div class="form-group">
									<label for="showLimit">' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.label.limit', true) . ': </label>
									<input class="form-control" type="number" min="0" max="10000" placeholder="10" title="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.title.limit', true) . '" name="showLimit" id="showLimit" value="' . htmlspecialchars(($this->showLimit ? $this->showLimit : '')) . '" />
                                </div>
                                <div class="form-group">
									<button type="submit" class="btn btn-default" name="search" title="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.title.search', true) . '">
										' . $iconFactory->getIcon('actions-search', Icon::SIZE_SMALL)->render() . ' ' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.search', true) . '
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
			' . $formElements[1] . '</div>';
        return $content;
    }

    /******************************
     *
     * Various helper functions
     *
     ******************************/
    /**
     * Setting the field names to display in extended list.
     * Sets the internal variable $this->setFields
     *
     * @return void
     */
    public function setDispFields()
    {
        $backendUser = $this->getBackendUserAuthentication();
        // Getting from session:
        $dispFields = $backendUser->getModuleData('list/displayFields');
        // If fields has been inputted, then set those as the value and push it to session variable:
        if (is_array($this->displayFields)) {
            reset($this->displayFields);
            $tKey = key($this->displayFields);
            $dispFields[$tKey] = $this->displayFields[$tKey];
            $backendUser->pushModuleData('list/displayFields', $dispFields);
        }
        // Setting result:
        $this->setFields = $dispFields;
    }

    /**
     * Create thumbnail code for record/field
     *
     * @param mixed[] $row Record array
     * @param string $table Table (record is from)
     * @param string $field Field name for which thumbnail are to be rendered.
     * @return string HTML for thumbnails, if any.
     */
    public function thumbCode($row, $table, $field)
    {
        return BackendUtility::thumbCode($row, $table, $field);
    }

    /**
     * Returns the SQL-query array to select the records from a table $table with pid = $id
     *
     * @param string $table Table name
     * @param int $id Page id (NOT USED! $this->pidSelect is used instead)
     * @param string $addWhere Additional part for where clause
     * @param string $fieldList Field list to select, * for all (for "SELECT [fieldlist] FROM ...")
     * @return string[] Returns query array
     */
    public function makeQueryArray($table, $id, $addWhere = '', $fieldList = '*')
    {
        $hookObjectsArr = [];
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list.inc']['makeQueryArray'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list.inc']['makeQueryArray'] as $classRef) {
                $hookObjectsArr[] = GeneralUtility::getUserObj($classRef);
            }
        }
        // Set ORDER BY:
        $orderBy = $GLOBALS['TCA'][$table]['ctrl']['sortby'] ? 'ORDER BY ' . $GLOBALS['TCA'][$table]['ctrl']['sortby'] : $GLOBALS['TCA'][$table]['ctrl']['default_sortby'];
        if ($this->sortField) {
            if (in_array($this->sortField, $this->makeFieldList($table, 1))) {
                $orderBy = 'ORDER BY ' . $this->sortField;
                if ($this->sortRev) {
                    $orderBy .= ' DESC';
                }
            }
        }
        // Set LIMIT:
        $limit = $this->iLimit ? ($this->firstElementNumber ? $this->firstElementNumber . ',' : '') . $this->iLimit : '';
        // Filtering on displayable pages (permissions):
        $pC = $table == 'pages' && $this->perms_clause ? ' AND ' . $this->perms_clause : '';
        // Adding search constraints:
        $search = $this->makeSearchString($table, $id);
        // Compiling query array:
        $queryParts = [
            'SELECT' => $fieldList,
            'FROM' => $table,
            'WHERE' => $this->pidSelect . ' ' . $pC . BackendUtility::deleteClause($table) . BackendUtility::versioningPlaceholderClause($table) . ' ' . $addWhere . ' ' . $search,
            'GROUPBY' => '',
            'ORDERBY' => $this->getDatabaseConnection()->stripOrderBy($orderBy),
            'LIMIT' => $limit
        ];
        // Filter out records that are translated, if TSconfig mod.web_list.hideTranslations is set
        if ((in_array($table, GeneralUtility::trimExplode(',', $this->hideTranslations)) || $this->hideTranslations === '*') && !empty($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']) && $table !== 'pages_language_overlay') {
            $queryParts['WHERE'] .= ' AND ' . $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] . '=0 ';
        }
        // Apply hook as requested in http://forge.typo3.org/issues/16634
        foreach ($hookObjectsArr as $hookObj) {
            if (method_exists($hookObj, 'makeQueryArray_post')) {
                $_params = [
                    'orderBy' => $orderBy,
                    'limit' => $limit,
                    'pC' => $pC,
                    'search' => $search
                ];
                $hookObj->makeQueryArray_post($queryParts, $this, $table, $id, $addWhere, $fieldList, $_params);
            }
        }
        // Return query:
        return $queryParts;
    }

    /**
     * Based on input query array (query for selecting count(*) from a table) it will select the number of records and set the value in $this->totalItems
     *
     * @param string[] $queryParts Query array
     * @return void
     * @see makeQueryArray()
     */
    public function setTotalItems($queryParts)
    {
        $this->totalItems = $this->getDatabaseConnection()->exec_SELECTcountRows('*', $queryParts['FROM'], $queryParts['WHERE']);
    }

    /**
     * Creates part of query for searching after a word ($this->searchString)
     * fields in input table.
     *
     * @param string $table Table, in which the fields are being searched.
     * @param int $currentPid Page id for the possible search limit. -1 only if called from an old XCLASS.
     * @return string Returns part of WHERE-clause for searching, if applicable.
     */
    public function makeSearchString($table, $currentPid = -1)
    {
        $result = '';
        $currentPid = (int)$currentPid;
        $tablePidField = $table === 'pages' ? 'uid' : 'pid';
        // Make query, only if table is valid and a search string is actually defined:
        if ($this->searchString) {
            $result = ' AND 0=1';
            $searchableFields = $this->getSearchFields($table);
            if (!empty($searchableFields)) {
                if (MathUtility::canBeInterpretedAsInteger($this->searchString)) {
                    $whereParts = [
                        'uid=' . $this->searchString
                    ];
                    foreach ($searchableFields as $fieldName) {
                        if (isset($GLOBALS['TCA'][$table]['columns'][$fieldName])) {
                            $fieldConfig = &$GLOBALS['TCA'][$table]['columns'][$fieldName]['config'];
                            $condition = $fieldName . '=' . $this->searchString;
                            if ($fieldConfig['type'] == 'input' && $fieldConfig['eval'] && GeneralUtility::inList($fieldConfig['eval'], 'int')) {
                                if (is_array($fieldConfig['search']) && in_array('pidonly', $fieldConfig['search']) && $currentPid > 0) {
                                    $condition = '(' . $condition . ' AND ' . $tablePidField . '=' . $currentPid . ')';
                                }
                                $whereParts[] = $condition;
                            } elseif ($fieldConfig['type'] == 'text' ||
                                $fieldConfig['type'] == 'flex' ||
                                ($fieldConfig['type'] == 'input' && (!$fieldConfig['eval'] || !preg_match('/date|time|int/', $fieldConfig['eval'])))) {
                                $condition = $fieldName . ' LIKE \'%' . $this->searchString . '%\'';
                                $whereParts[] = $condition;
                            }
                        }
                    }
                } else {
                    $whereParts = [];
                    $db = $this->getDatabaseConnection();
                    $like = '\'%' . $db->quoteStr($db->escapeStrForLike($this->searchString, $table), $table) . '%\'';
                    foreach ($searchableFields as $fieldName) {
                        if (isset($GLOBALS['TCA'][$table]['columns'][$fieldName])) {
                            $fieldConfig = &$GLOBALS['TCA'][$table]['columns'][$fieldName]['config'];
                            $format = 'LOWER(%s) LIKE LOWER(%s)';
                            if (is_array($fieldConfig['search'])) {
                                if (in_array('case', $fieldConfig['search'])) {
                                    $format = '%s LIKE %s';
                                }
                                if (in_array('pidonly', $fieldConfig['search']) && $currentPid > 0) {
                                    $format = '(' . $format . ' AND ' . $tablePidField . '=' . $currentPid . ')';
                                }
                                if ($fieldConfig['search']['andWhere']) {
                                    $format = '((' . $fieldConfig['search']['andWhere'] . ') AND (' . $format . '))';
                                }
                            }
                            if ($fieldConfig['type'] == 'text' || $fieldConfig['type'] == 'flex' || $fieldConfig['type'] == 'input' && (!$fieldConfig['eval'] || !preg_match('/date|time|int/', $fieldConfig['eval']))) {
                                $whereParts[] = sprintf($format, $fieldName, $like);
                            }
                        }
                    }
                }
                // If search-fields were defined (and there always are) we create the query:
                if (!empty($whereParts)) {
                    $result = ' AND (' . implode(' OR ', $whereParts) . ')';
                }
            }
        }
        return $result;
    }

    /**
     * Fetches a list of fields to use in the Backend search for the given table.
     *
     * @param string $tableName
     * @return string[]
     */
    protected function getSearchFields($tableName)
    {
        $fieldArray = [];
        $fieldListWasSet = false;
        // Get fields from ctrl section of TCA first
        if (isset($GLOBALS['TCA'][$tableName]['ctrl']['searchFields'])) {
            $fieldArray = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$tableName]['ctrl']['searchFields'], true);
            $fieldListWasSet = true;
        }
        // Call hook to add or change the list
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['mod_list']['getSearchFieldList'])) {
            $hookParameters = [
                'tableHasSearchConfiguration' => $fieldListWasSet,
                'tableName' => $tableName,
                'searchFields' => &$fieldArray,
                'searchString' => $this->searchString
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['mod_list']['getSearchFieldList'] as $hookFunction) {
                GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
            }
        }
        return $fieldArray;
    }

    /**
     * Returns the title (based on $code) of a table ($table) with the proper link around. For headers over tables.
     * The link will cause the display of all extended mode or not for the table.
     *
     * @param string $table Table name
     * @param string $code Table label
     * @return string The linked table label
     */
    public function linkWrapTable($table, $code)
    {
        if ($this->table !== $table) {
            return '<a href="' . htmlspecialchars($this->listURL('', $table, 'firstElementNumber')) . '">' . $code . '</a>';
        }
        return '<a href="' . htmlspecialchars($this->listURL('', '', 'sortField,sortRev,table,firstElementNumber')) . '">' . $code . '</a>';
    }

    /**
     * Returns the title (based on $code) of a record (from table $table) with the proper link around (that is for 'pages'-records a link to the level of that record...)
     *
     * @param string $table Table name
     * @param int $uid Item uid
     * @param string $code Item title (not htmlspecialchars()'ed yet)
     * @param mixed[] $row Item row
     * @return string The item title. Ready for HTML output (is htmlspecialchars()'ed)
     */
    public function linkWrapItems($table, $uid, $code, $row)
    {
        $lang = $this->getLanguageService();
        $origCode = $code;
        // If the title is blank, make a "no title" label:
        if ((string)$code === '') {
            $code = '<i>[' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.no_title', 1) . ']</i> - ' . htmlspecialchars(GeneralUtility::fixed_lgd_cs(
                BackendUtility::getRecordTitle($table, $row),
                $this->getBackendUserAuthentication()->uc['titleLen']
            ));
        } else {
            $code = htmlspecialchars(GeneralUtility::fixed_lgd_cs($code, $this->fixedL), ENT_QUOTES, 'UTF-8', false);
            if ($code != htmlspecialchars($origCode)) {
                $code = '<span title="' . htmlspecialchars($origCode, ENT_QUOTES, 'UTF-8', false) . '">' . $code . '</span>';
            }
        }
        switch ((string)$this->clickTitleMode) {
            case 'edit':
                // If the listed table is 'pages' we have to request the permission settings for each page:
                if ($table == 'pages') {
                    $localCalcPerms = $this->getBackendUserAuthentication()->calcPerms(BackendUtility::getRecord('pages', $row['uid']));
                    $permsEdit = $localCalcPerms & Permission::PAGE_EDIT;
                } else {
                    $permsEdit = $this->calcPerms & Permission::CONTENT_EDIT;
                }
                // "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
                if ($permsEdit) {
                    $params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
                    $code = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, '', -1)) . '" title="' . $lang->getLL('edit', true) . '">' . $code . '</a>';
                }
                break;
            case 'show':
                // "Show" link (only pages and tt_content elements)
                if ($table == 'pages' || $table == 'tt_content') {
                    $code = '<a href="#" onclick="' . htmlspecialchars(
                        BackendUtility::viewOnClick(($table == 'tt_content' ? $this->id . '#' . $row['uid'] : $row['uid']))
                    ) . '" title="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage', true) . '">' . $code . '</a>';
                }
                break;
            case 'info':
                // "Info": (All records)
                $code = '<a href="#" onclick="' . htmlspecialchars(('top.launchView(\'' . $table . '\', \'' . $row['uid'] . '\'); return false;')) . '" title="' . $lang->getLL('showInfo', true) . '">' . $code . '</a>';
                break;
            default:
                // Output the label now:
                if ($table == 'pages') {
                    $code = '<a href="' . htmlspecialchars($this->listURL($uid, '', 'firstElementNumber')) . '" onclick="setHighlight(' . $uid . ')">' . $code . '</a>';
                } else {
                    $code = $this->linkUrlMail($code, $origCode);
                }
        }
        return $code;
    }

    /**
     * Wrapping input code in link to URL or email if $testString is either.
     *
     * @param string $code code to wrap
     * @param string $testString String which is tested for being a URL or email and which will be used for the link if so.
     * @return string Link-Wrapped $code value, if $testString was URL or email.
     */
    public function linkUrlMail($code, $testString)
    {
        // Check for URL:
        $scheme = parse_url($testString, PHP_URL_SCHEME);
        if ($scheme === 'http' || $scheme === 'https' || $scheme === 'ftp') {
            return '<a href="' . htmlspecialchars($testString) . '" target="_blank">' . $code . '</a>';
        }
        // Check for email:
        if (GeneralUtility::validEmail($testString)) {
            return '<a href="mailto:' . htmlspecialchars($testString) . '" target="_blank">' . $code . '</a>';
        }
        // Return if nothing else...
        return $code;
    }

    /**
     * Creates the URL to this script, including all relevant GPvars
     * Fixed GPvars are id, table, imagemode, returnUrl, search_field, search_levels and showLimit
     * The GPvars "sortField" and "sortRev" are also included UNLESS they are found in the $exclList variable.
     *
     * @param string $altId Alternative id value. Enter blank string for the current id ($this->id)
     * @param string $table Table name to display. Enter "-1" for the current table.
     * @param string $exclList Comma separated list of fields NOT to include ("sortField", "sortRev" or "firstElementNumber")
     * @return string URL
     */
    public function listURL($altId = '', $table = '-1', $exclList = '')
    {
        $urlParameters = [];
        if ((string)$altId !== '') {
            $urlParameters['id'] = $altId;
        } else {
            $urlParameters['id'] = $this->id;
        }
        if ($table === '-1') {
            $urlParameters['table'] = $this->table;
        } else {
            $urlParameters['table'] = $table;
        }
        if ($this->thumbs) {
            $urlParameters['imagemode'] = $this->thumbs;
        }
        if ($this->returnUrl) {
            $urlParameters['returnUrl'] = $this->returnUrl;
        }
        if ((!$exclList || !GeneralUtility::inList($exclList, 'search_field')) && $this->searchString) {
            $urlParameters['search_field'] = $this->searchString;
        }
        if ($this->searchLevels) {
            $urlParameters['search_levels'] = $this->searchLevels;
        }
        if ($this->showLimit) {
            $urlParameters['showLimit'] = $this->showLimit;
        }
        if ((!$exclList || !GeneralUtility::inList($exclList, 'firstElementNumber')) && $this->firstElementNumber) {
            $urlParameters['pointer'] = $this->firstElementNumber;
        }
        if ((!$exclList || !GeneralUtility::inList($exclList, 'sortField')) && $this->sortField) {
            $urlParameters['sortField'] = $this->sortField;
        }
        if ((!$exclList || !GeneralUtility::inList($exclList, 'sortRev')) && $this->sortRev) {
            $urlParameters['sortRev'] = $this->sortRev;
        }

        $urlParameters = array_merge_recursive($urlParameters, $this->overrideUrlParameters);

        if ($routePath = GeneralUtility::_GP('route')) {
            $router = GeneralUtility::makeInstance(Router::class);
            $route = $router->match($routePath);
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $url = (string)$uriBuilder->buildUriFromRoute($route->getOption('_identifier'), $urlParameters);
        } elseif ($moduleName = GeneralUtility::_GP('M')) {
            $url = BackendUtility::getModuleUrl($moduleName, $urlParameters);
        } else {
            $url = GeneralUtility::getIndpEnv('SCRIPT_NAME') . '?' . ltrim(GeneralUtility::implodeArrayForUrl('', $urlParameters), '&');
        }
        return $url;
    }

    /**
     * Returns "requestUri" - which is basically listURL
     *
     * @return string Content of ->listURL()
     */
    public function requestUri()
    {
        return $this->listURL();
    }

    /**
     * Makes the list of fields to select for a table
     *
     * @param string $table Table name
     * @param bool $dontCheckUser If set, users access to the field (non-exclude-fields) is NOT checked.
     * @param bool $addDateFields If set, also adds crdate and tstamp fields (note: they will also be added if user is admin or dontCheckUser is set)
     * @return string[] Array, where values are fieldnames to include in query
     */
    public function makeFieldList($table, $dontCheckUser = false, $addDateFields = false)
    {
        $backendUser = $this->getBackendUserAuthentication();
        // Init fieldlist array:
        $fieldListArr = [];
        // Check table:
        if (is_array($GLOBALS['TCA'][$table]) && isset($GLOBALS['TCA'][$table]['columns']) && is_array($GLOBALS['TCA'][$table]['columns'])) {
            if (isset($GLOBALS['TCA'][$table]['columns']) && is_array($GLOBALS['TCA'][$table]['columns'])) {
                // Traverse configured columns and add them to field array, if available for user.
                foreach ($GLOBALS['TCA'][$table]['columns'] as $fN => $fieldValue) {
                    if ($dontCheckUser || (!$fieldValue['exclude'] || $backendUser->check('non_exclude_fields', $table . ':' . $fN)) && $fieldValue['config']['type'] != 'passthrough') {
                        $fieldListArr[] = $fN;
                    }
                }

                $fieldListArr[] = 'uid';
                $fieldListArr[] = 'pid';

                // Add date fields
                if ($dontCheckUser || $backendUser->isAdmin() || $addDateFields) {
                    if ($GLOBALS['TCA'][$table]['ctrl']['tstamp']) {
                        $fieldListArr[] = $GLOBALS['TCA'][$table]['ctrl']['tstamp'];
                    }
                    if ($GLOBALS['TCA'][$table]['ctrl']['crdate']) {
                        $fieldListArr[] = $GLOBALS['TCA'][$table]['ctrl']['crdate'];
                    }
                }
                // Add more special fields:
                if ($dontCheckUser || $backendUser->isAdmin()) {
                    if ($GLOBALS['TCA'][$table]['ctrl']['cruser_id']) {
                        $fieldListArr[] = $GLOBALS['TCA'][$table]['ctrl']['cruser_id'];
                    }
                    if ($GLOBALS['TCA'][$table]['ctrl']['sortby']) {
                        $fieldListArr[] = $GLOBALS['TCA'][$table]['ctrl']['sortby'];
                    }
                    if (ExtensionManagementUtility::isLoaded('version') && $GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
                        $fieldListArr[] = 't3ver_id';
                        $fieldListArr[] = 't3ver_state';
                        $fieldListArr[] = 't3ver_wsid';
                    }
                }
            } else {
                GeneralUtility::sysLog(sprintf('$TCA is broken for the table "%s": no required "columns" entry in $TCA.', $table), 'core', GeneralUtility::SYSLOG_SEVERITY_ERROR);
            }
        }
        return $fieldListArr;
    }

    /**
     * Get all allowed mount pages to be searched in.
     *
     * @param int $id Page id
     * @param int $depth Depth to go down
     * @param string $perms_clause select clause
     * @return int[]
     */
    protected function getSearchableWebmounts($id, $depth, $perms_clause)
    {
        $backendUser = $this->getBackendUserAuthentication();
        /** @var PageTreeView $tree */
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $tree->init('AND ' . $perms_clause);
        $tree->makeHTML = 0;
        $tree->fieldArray = ['uid', 'php_tree_stop'];
        $idList = [];

        $allowedMounts = !$backendUser->isAdmin() && $id === 0
            ? $backendUser->returnWebmounts()
            : [$id];

        foreach ($allowedMounts as $allowedMount) {
            $idList[] = $allowedMount;
            if ($depth) {
                $tree->getTree($allowedMount, $depth, '');
            }
            $idList = array_merge($idList, $tree->ids);
        }

        return $idList;
    }

    /**
     * Redirects to FormEngine if a record is just localized.
     *
     * @param string $justLocalized String with table, orig uid and language separated by ":
     * @return void
     */
    public function localizationRedirect($justLocalized)
    {
        list($table, $orig_uid, $language) = explode(':', $justLocalized);
        if ($GLOBALS['TCA'][$table] && $GLOBALS['TCA'][$table]['ctrl']['languageField'] && $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']) {
            $localizedRecord = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('uid', $table, $GLOBALS['TCA'][$table]['ctrl']['languageField'] . '=' . (int)$language . ' AND ' . $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] . '=' . (int)$orig_uid . BackendUtility::deleteClause($table) . BackendUtility::versioningPlaceholderClause($table));
            if (is_array($localizedRecord)) {
                // Create parameters and finally run the classic page module for creating a new page translation
                $url = $this->listURL();
                $editUserAccountUrl = BackendUtility::getModuleUrl(
                    'record_edit',
                    [
                        'edit[' . $table . '][' . $localizedRecord['uid'] . ']' => 'edit',
                        'returnUrl' => $url
                    ]
                );
                HttpUtility::redirect($editUserAccountUrl);
            }
        }
    }

    /**
     * Set URL parameters to override or add in the listUrl() method.
     *
     * @param string[] $urlParameters
     * @return void
     */
    public function setOverrideUrlParameters(array $urlParameters)
    {
        $this->overrideUrlParameters = $urlParameters;
    }

    /**
     * Set table display order information
     *
     * Structure of $orderInformation:
     *   'tableName' => [
     *      'before' => // comma-separated string list or array of table names
     *      'after' => // comma-separated string list or array of table names
     * ]
     *
     * @param array $orderInformation
     * @throws \UnexpectedValueException
     */
    public function setTableDisplayOrder(array $orderInformation)
    {
        foreach ($orderInformation as $tableName => &$configuration) {
            if (isset($configuration['before'])) {
                if (is_string($configuration['before'])) {
                    $configuration['before'] = GeneralUtility::trimExplode(',', $configuration['before'], true);
                } elseif (!is_array($configuration['before'])) {
                    throw new \UnexpectedValueException('The specified "before" order configuration for table "' . $tableName . '" is invalid.', 1436195933);
                }
            }
            if (isset($configuration['after'])) {
                if (is_string($configuration['after'])) {
                    $configuration['after'] = GeneralUtility::trimExplode(',', $configuration['after'], true);
                } elseif (!is_array($configuration['after'])) {
                    throw new \UnexpectedValueException('The specified "after" order configuration for table "' . $tableName . '" is invalid.', 1436195934);
                }
            }
        }
        $this->tableDisplayOrder = $orderInformation;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
