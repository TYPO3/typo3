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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Log\LogManager;
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
 * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
 */
class AbstractDatabaseRecordList extends AbstractRecordList
{
    /**
     * Specify a list of tables which are the only ones allowed to be displayed.
     *
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $tableList = '';

    /**
     * Return URL
     *
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $returnUrl = '';

    /**
     * Thumbnails on records containing files (pictures)
     *
     * @var bool
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $thumbs = 0;

    /**
     * default Max items shown per table in "multi-table mode", may be overridden by tables.php
     *
     * @var int
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $itemsLimitPerTable = 20;

    /**
     * default Max items shown per table in "single-table mode", may be overridden by tables.php
     *
     * @var int
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $itemsLimitSingleTable = 100;

    /**
     * Current script name
     *
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $script = 'index.php';

    /**
     * Indicates if all available fields for a user should be selected or not.
     *
     * @var int
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $allFields = 0;

    /**
     * If set, csvList is outputted.
     *
     * @var bool
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $csvOutput = false;

    /**
     * Field, to sort list by
     *
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $sortField;

    /**
     * Field, indicating to sort in reverse order.
     *
     * @var bool
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $sortRev;

    /**
     * Containing which fields to display in extended mode
     *
     * @var string[]
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $displayFields;

    /**
     * String, can contain the field name from a table which must have duplicate values marked.
     *
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $duplicateField;

    /**
     * Page id
     *
     * @var int
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $id;

    /**
     * Tablename if single-table mode
     *
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $table = '';

    /**
     * If TRUE, records are listed only if a specific table is selected.
     *
     * @var bool
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $listOnlyInSingleTableMode = false;

    /**
     * Pointer for browsing list
     *
     * @var int
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $firstElementNumber = 0;

    /**
     * Search string
     *
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $searchString = '';

    /**
     * Levels to search down.
     *
     * @var int
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $searchLevels = '';

    /**
     * Number of records to show
     *
     * @var int
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $showLimit = 0;

    /**
     * Page select permissions
     *
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $perms_clause = '';

    /**
     * Some permissions...
     *
     * @var int
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $calcPerms = 0;

    /**
     * Mode for what happens when a user clicks the title of a record.
     *
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $clickTitleMode = '';

    /**
     * Shared module configuration, used by localization features
     *
     * @var array
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $modSharedTSconfig = [];

    /**
     * Loaded with page record with version overlay if any.
     *
     * @var string[]
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $pageRecord = [];

    /**
     * Tables which should not get listed
     *
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $hideTables = '';

    /**
     * Tables which should not list their translations
     *
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $hideTranslations = '';

    /**
     * TSconfig which overwrites TCA-Settings
     *
     * @var mixed[][]
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $tableTSconfigOverTCA = [];

    /**
     * Array of collapsed / uncollapsed tables in multi table view
     *
     * @var int[][]
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $tablesCollapsed = [];

    /**
     * JavaScript code accumulation
     *
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $JScode = '';

    /**
     * HTML output
     *
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $HTMLcode = '';

    /**
     * "LIMIT " in SQL...
     *
     * @var int
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $iLimit = 0;

    /**
     * Counting the elements no matter what...
     *
     * @var int
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $eCounter = 0;

    /**
     * Set to the total number of items for a table when selecting.
     *
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $totalItems = '';

    /**
     * Cache for record path
     *
     * @var mixed[]
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $recPath_cache = [];

    /**
     * Fields to display for the current table
     *
     * @var string[]
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $setFields = [];

    /**
     * Used for tracking next/prev uids
     *
     * @var int[][]
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $currentTable = [];

    /**
     * Used for tracking duplicate values of fields
     *
     * @var string[]
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $duplicateStack = [];

    /**
     * @var array[] Module configuration
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $modTSconfig;

    /**
     * Override/add urlparameters in listUrl() method
     * @var string[]
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    protected $overrideUrlParameters = [];

    /**
     * Override the page ids taken into account by getPageIdConstraint()
     *
     * @var array
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    protected $overridePageIdList = [];

    /**
     * Array with before/after setting for tables
     * Structure:
     * 'tableName' => [
     *    'before' => ['A', ...]
     *    'after' => []
     *  ]
     * @var array[]
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function start($id, $table, $pointer, $search = '', $levels = 0, $showLimit = 0)
    {
        $backendUser = $this->getBackendUserAuthentication();
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
            $this->itemsLimitPerTable = MathUtility::forceIntegerInRange(
                (int)$this->modTSconfig['properties']['itemsLimitPerTable'],
                1,
                10000
            );
        }
        if (isset($this->modTSconfig['properties']['itemsLimitSingleTable'])) {
            $this->itemsLimitSingleTable = MathUtility::forceIntegerInRange(
                (int)$this->modTSconfig['properties']['itemsLimitSingleTable'],
                1,
                10000
            );
        }

        // $table might be NULL at this point in the code. As the expressionBuilder
        // is used to limit returned records based on the page permissions and the
        // uid field of the pages it can hardcoded to work on the pages table.
        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages')
            ->expr();
        $permsClause = $expressionBuilder->andX($backendUser->getPagePermsClause(Permission::PAGE_SHOW));
        // This will hide records from display - it has nothing to do with user rights!!
        $pidList = GeneralUtility::intExplode(',', $backendUser->getTSConfig()['options.']['hideRecords.']['pages'] ?? '', true);
        if (!empty($pidList)) {
            $permsClause->add($expressionBuilder->notIn('pages.uid', $pidList));
        }
        $this->perms_clause = (string)$permsClause;

        // Get configuration of collapsed tables from user uc and merge with sanitized GP vars
        $this->tablesCollapsed = is_array($backendUser->uc['moduleData']['list'])
            ? $backendUser->uc['moduleData']['list']
            : [];
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
        $this->initializeLanguages();
    }

    /**
     * Traverses the table(s) to be listed and renders the output code for each:
     * The HTML is accumulated in $this->HTMLcode
     * Finishes off with a stopper-gif
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
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
                $hideTable = $hideTable
                    || !empty($GLOBALS['TCA'][$tableName]['ctrl']['hideTable'])
                    || in_array($tableName, $hideTablesArray, true)
                    || in_array('*', $hideTablesArray, true);
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

        $orderedTableNames = GeneralUtility::makeInstance(DependencyOrderingService::class)
            ->orderByDependencies($tableNames);

        foreach ($orderedTableNames as $tableName => $_) {
            // check if we are in single- or multi-table mode
            if ($this->table) {
                $this->iLimit = isset($GLOBALS['TCA'][$tableName]['interface']['maxSingleDBListItems'])
                    ? (int)$GLOBALS['TCA'][$tableName]['interface']['maxSingleDBListItems']
                    : $this->itemsLimitSingleTable;
            } else {
                // if there are no records in table continue current foreach
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($tableName);
                $queryBuilder->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                    ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
                $queryBuilder = $this->addPageIdConstraint($tableName, $queryBuilder);
                $firstRow = $queryBuilder->select('uid')
                    ->from($tableName)
                    ->execute()
                    ->fetch();
                if (!is_array($firstRow)) {
                    continue;
                }
                $this->iLimit = isset($GLOBALS['TCA'][$tableName]['interface']['maxDBListItems'])
                    ? (int)$GLOBALS['TCA'][$tableName]['interface']['maxDBListItems']
                    : $this->itemsLimitPerTable;
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function getSearchBox($formFields = true)
    {
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $lang = $this->getLanguageService();
        // Setting form-elements, if applicable:
        $formElements = ['', ''];
        if ($formFields) {
            $formElements = ['<form action="' . htmlspecialchars($this->listURL('', '-1', 'firstElementNumber,search_field')) . '" method="post">', '</form>'];
        }
        // Make level selector:
        $opt = [];

        // "New" generation of search levels ... based on TS config
        $config = BackendUtility::getPagesTSconfig($this->id);
        $searchLevelsFromTSconfig = $config['mod.']['web_list.']['searchLevel.']['items.'];
        $searchLevelItems = [];

        // get translated labels for search levels from pagets
        foreach ($searchLevelsFromTSconfig as $keySearchLevel => $labelConfigured) {
            $label = $lang->sL('LLL:' . $labelConfigured);
            if ($label === '') {
                $label = $labelConfigured;
            }
            $searchLevelItems[$keySearchLevel] = $label;
        }

        foreach ($searchLevelItems as $kv => $label) {
            $opt[] = '<option value="' . $kv . '"' . ($kv === $this->searchLevels ? ' selected="selected"' : '') . '>' . htmlspecialchars($label) . '</option>';
        }
        $lMenu = '<select class="form-control" name="search_levels" title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.title.search_levels')) . '" id="search_levels">' . implode('', $opt) . '</select>';
        // Table with the search box:
        $content = '<div class="db_list-searchbox-form db_list-searchbox-toolbar module-docheader-bar module-docheader-bar-search t3js-module-docheader-bar t3js-module-docheader-bar-search" id="db_list-searchbox-toolbar" style="display: ' . ($this->searchString == '' ? 'none' : 'block') . ';">
			' . $formElements[0] . '
                <div id="typo3-dblist-search">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <div class="row">
                                <div class="form-group col-xs-12">
                                    <label for="search_field">' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.label.searchString')) . ': </label>
									<input class="form-control" type="search" placeholder="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enterSearchString')) . '" title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.title.searchString')) . '" name="search_field" id="search_field" value="' . htmlspecialchars($this->searchString) . '" />
                                </div>
                                <div class="form-group col-xs-12 col-sm-6">
									<label for="search_levels">' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.label.search_levels')) . ': </label>
									' . $lMenu . '
                                </div>
                                <div class="form-group col-xs-12 col-sm-6">
									<label for="showLimit">' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.label.limit')) . ': </label>
									<input class="form-control" type="number" min="0" max="10000" placeholder="10" title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.title.limit')) . '" name="showLimit" id="showLimit" value="' . htmlspecialchars(($this->showLimit ? $this->showLimit : '')) . '" />
                                </div>
                                <div class="form-group col-xs-12">
                                    <div class="form-control-wrap">
                                        <button type="submit" class="btn btn-default" name="search" title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.title.search')) . '">
                                            ' . $iconFactory->getIcon('actions-search', Icon::SIZE_SMALL)->render() . ' ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.search')) . '
                                        </button>
                                    </div>
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function thumbCode($row, $table, $field)
    {
        return BackendUtility::thumbCode($row, $table, $field);
    }

    /**
     * Returns a QueryBuilder configured to select $fields from $table where the pid is restricted
     * depending on the current searchlevel setting.
     *
     * @param string $table Table name
     * @param int $pageId Page id Only used to build the search constraints, getPageIdConstraint() used for restrictions
     * @param string[] $additionalConstraints Additional part for where clause
     * @param string[] $fields Field list to select, * for all
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function getQueryBuilder(
        string $table,
        int $pageId,
        array $additionalConstraints = [],
        array $fields = ['*']
    ): QueryBuilder {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
        $queryBuilder
            ->select(...$fields)
            ->from($table);

        if (!empty($additionalConstraints)) {
            $queryBuilder->andWhere(...$additionalConstraints);
        }

        $queryBuilder = $this->prepareQueryBuilder($table, $pageId, $fields, $additionalConstraints, $queryBuilder);

        return $queryBuilder;
    }

    /**
     * Return the modified QueryBuilder object ($queryBuilder) which will be
     * used to select the records from a table $table with pid = $this->pidList
     *
     * @param string $table Table name
     * @param int $pageId Page id Only used to build the search constraints, $this->pidList is used for restrictions
     * @param string[] $fieldList List of fields to select from the table
     * @param string[] $additionalConstraints Additional part for where clause
     * @param QueryBuilder $queryBuilder
     * @param bool $addSorting
     * @return QueryBuilder
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    protected function prepareQueryBuilder(
        string $table,
        int $pageId,
        array $fieldList = ['*'],
        array $additionalConstraints = [],
        QueryBuilder $queryBuilder,
        bool $addSorting = true
    ): QueryBuilder {
        $parameters = [
            'table' => $table,
            'fields' => $fieldList,
            'groupBy' => null,
            'orderBy' => null,
            'firstResult' => $this->firstElementNumber ?: null,
            'maxResults' => $this->iLimit ?: null
        ];

        if ($this->iLimit > 0) {
            $queryBuilder->setMaxResults($this->iLimit);
        }

        if ($addSorting) {
            if ($this->sortField && in_array($this->sortField, $this->makeFieldList($table, 1))) {
                $queryBuilder->orderBy($this->sortField, $this->sortRev ? 'DESC' : 'ASC');
            } else {
                $orderBy = $GLOBALS['TCA'][$table]['ctrl']['sortby'] ?: $GLOBALS['TCA'][$table]['ctrl']['default_sortby'];
                $orderBys = QueryHelper::parseOrderBy((string)$orderBy);
                foreach ($orderBys as $orderBy) {
                    $queryBuilder->orderBy($orderBy[0], $orderBy[1]);
                }
            }
        }

        // Build the query constraints
        $queryBuilder = $this->addPageIdConstraint($table, $queryBuilder);
        $searchWhere = $this->makeSearchString($table, $pageId);
        if (!empty($searchWhere)) {
            $queryBuilder->andWhere($searchWhere);
        }

        // Filtering on displayable pages (permissions):
        if ($table === 'pages' && $this->perms_clause) {
            $queryBuilder->andWhere($this->perms_clause);
        }

        // Filter out records that are translated, if TSconfig mod.web_list.hideTranslations is set
        if (!empty($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'])
            && (GeneralUtility::inList($this->hideTranslations, $table) || $this->hideTranslations === '*')
        ) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq(
                $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'],
                0
            ));
        }

        $hookName = DatabaseRecordList::class;
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$hookName]['buildQueryParameters'])) {
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, the modifyQuery hook should be used instead.
            trigger_error('The hook ($GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][' . $hookName . '][\'buildQueryParameters\']) will be removed in TYPO3 v10.0, the modifyQuery hook should be used instead.', E_USER_DEPRECATED);
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$hookName]['buildQueryParameters'] as $className) {
                $hookObject = GeneralUtility::makeInstance($className);
                if (method_exists($hookObject, 'buildQueryParametersPostProcess')) {
                    $hookObject->buildQueryParametersPostProcess(
                        $parameters,
                        $table,
                        $pageId,
                        $additionalConstraints,
                        $fieldList,
                        $this,
                        $queryBuilder
                    );
                }
            }
        }

        // array_unique / array_filter used to eliminate empty and duplicate constraints
        // the array keys are eliminated by this as well to facilitate argument unpacking
        // when used with the querybuilder.
        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
        if (!empty($parameters['where'])) {
            $parameters['where'] = array_unique(array_filter(array_values($parameters['where'])));
        }
        if (!empty($parameters['where'])) {
            $this->logDeprecation('where');
            $queryBuilder->where(...$parameters['where']);
        }
        if (!empty($parameters['orderBy'])) {
            $this->logDeprecation('orderBy');
            foreach ($parameters['orderBy'] as $fieldNameAndSorting) {
                list($fieldName, $sorting) = $fieldNameAndSorting;
                $queryBuilder->addOrderBy($fieldName, $sorting);
            }
        }
        if (!empty($parameters['firstResult'])) {
            $this->logDeprecation('firstResult');
            $queryBuilder->setFirstResult((int)$parameters['firstResult']);
        }
        if (!empty($parameters['maxResults']) && $parameters['maxResults'] !== $this->iLimit) {
            $this->logDeprecation('maxResults');
            $queryBuilder->setMaxResults((int)$parameters['maxResults']);
        }
        if (!empty($parameters['groupBy'])) {
            $this->logDeprecation('groupBy');
            $queryBuilder->groupBy($parameters['groupBy']);
        }

        return $queryBuilder;
    }

    /**
     * Executed a query to set $this->totalItems to the number of total
     * items, eg. for pagination
     *
     * @param string $table Table name
     * @param int $pageId Only used to build the search constraints, $this->pidList is used for restrictions
     * @param array $constraints Additional constraints for where clause
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function setTotalItems(string $table, int $pageId, array $constraints)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);

        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
        $queryBuilder
            ->from($table);

        if (!empty($constraints)) {
            $queryBuilder->andWhere(...$constraints);
        }

        $queryBuilder = $this->prepareQueryBuilder($table, $pageId, ['*'], $constraints, $queryBuilder, false);
        // Reset limit and offset for full count query
        $queryBuilder->setFirstResult(0);
        $queryBuilder->setMaxResults(1);

        $this->totalItems = (int)$queryBuilder->count('*')
            ->execute()
            ->fetchColumn();
    }

    /**
     * Creates part of query for searching after a word ($this->searchString)
     * fields in input table.
     *
     * @param string $table Table, in which the fields are being searched.
     * @param int $currentPid Page id for the possible search limit. -1 only if called from an old XCLASS.
     * @return string Returns part of WHERE-clause for searching, if applicable.
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function makeSearchString($table, $currentPid = -1)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $expressionBuilder = $queryBuilder->expr();
        $constraints = [];
        $currentPid = (int)$currentPid;
        $tablePidField = $table === 'pages' ? 'uid' : 'pid';
        // Make query only if table is valid and a search string is actually defined
        if (empty($this->searchString)) {
            return '';
        }

        $searchableFields = $this->getSearchFields($table);
        if (MathUtility::canBeInterpretedAsInteger($this->searchString)) {
            $constraints[] = $expressionBuilder->eq('uid', (int)$this->searchString);
            foreach ($searchableFields as $fieldName) {
                if (!isset($GLOBALS['TCA'][$table]['columns'][$fieldName])) {
                    continue;
                }
                $fieldConfig = $GLOBALS['TCA'][$table]['columns'][$fieldName]['config'];
                $fieldType = $fieldConfig['type'];
                $evalRules = $fieldConfig['eval'] ?: '';
                if ($fieldType === 'input' && $evalRules && GeneralUtility::inList($evalRules, 'int')) {
                    if (is_array($fieldConfig['search'])
                        && in_array('pidonly', $fieldConfig['search'], true)
                        && $currentPid > 0
                    ) {
                        $constraints[] = $expressionBuilder->andX(
                            $expressionBuilder->eq($fieldName, (int)$this->searchString),
                            $expressionBuilder->eq($tablePidField, (int)$currentPid)
                        );
                    }
                } elseif ($fieldType === 'text'
                    || $fieldType === 'flex'
                    || ($fieldType === 'input' && (!$evalRules || !preg_match('/date|time|int/', $evalRules)))
                ) {
                    $constraints[] = $expressionBuilder->like(
                        $fieldName,
                        $queryBuilder->quote('%' . (int)$this->searchString . '%')
                    );
                }
            }
        } elseif (!empty($searchableFields)) {
            $like = $queryBuilder->quote('%' . $queryBuilder->escapeLikeWildcards($this->searchString) . '%');
            foreach ($searchableFields as $fieldName) {
                if (!isset($GLOBALS['TCA'][$table]['columns'][$fieldName])) {
                    continue;
                }
                $fieldConfig = $GLOBALS['TCA'][$table]['columns'][$fieldName]['config'];
                $fieldType = $fieldConfig['type'];
                $evalRules = $fieldConfig['eval'] ?: '';
                $searchConstraint = $expressionBuilder->andX(
                    $expressionBuilder->comparison(
                        'LOWER(' . $queryBuilder->quoteIdentifier($fieldName) . ')',
                        'LIKE',
                        'LOWER(' . $like . ')'
                    )
                );
                if (is_array($fieldConfig['search'])) {
                    $searchConfig = $fieldConfig['search'];
                    if (in_array('case', $searchConfig)) {
                        // Replace case insensitive default constraint
                        $searchConstraint = $expressionBuilder->andX($expressionBuilder->like($fieldName, $like));
                    }
                    if (in_array('pidonly', $searchConfig) && $currentPid > 0) {
                        $searchConstraint->add($expressionBuilder->eq($tablePidField, (int)$currentPid));
                    }
                    if ($searchConfig['andWhere']) {
                        $searchConstraint->add(
                            QueryHelper::stripLogicalOperatorPrefix($fieldConfig['search']['andWhere'])
                        );
                    }
                }
                if ($fieldType === 'text'
                    || $fieldType === 'flex'
                    || $fieldType === 'input' && (!$evalRules || !preg_match('/date|time|int/', $evalRules))
                ) {
                    if ($searchConstraint->count() !== 0) {
                        $constraints[] = $searchConstraint;
                    }
                }
            }
        }
        // If no search field conditions have been built ensure no results are returned
        if (empty($constraints)) {
            return '0=1';
        }

        return $expressionBuilder->orX(...$constraints);
    }

    /**
     * Fetches a list of fields to use in the Backend search for the given table.
     *
     * @param string $tableName
     * @return string[]
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
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
        $hookParameters = [
            'tableHasSearchConfiguration' => $fieldListWasSet,
            'tableName' => $tableName,
            'searchFields' => &$fieldArray,
            'searchString' => $this->searchString
        ];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['mod_list']['getSearchFieldList'] ?? [] as $hookFunction) {
            GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function linkWrapItems($table, $uid, $code, $row)
    {
        $lang = $this->getLanguageService();
        $origCode = $code;
        // If the title is blank, make a "no title" label:
        if ((string)$code === '') {
            $code = '<i>[' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title')) . ']</i> - '
                . htmlspecialchars(BackendUtility::getRecordTitle($table, $row));
        } else {
            $code = htmlspecialchars($code, ENT_QUOTES, 'UTF-8', false);
            if ($code != htmlspecialchars($origCode)) {
                $code = '<span title="' . htmlspecialchars($origCode, ENT_QUOTES, 'UTF-8', false) . '">' . $code . '</span>';
            }
        }
        switch ((string)$this->clickTitleMode) {
            case 'edit':
                // If the listed table is 'pages' we have to request the permission settings for each page:
                if ($table === 'pages') {
                    $localCalcPerms = $this->getBackendUserAuthentication()->calcPerms(BackendUtility::getRecord('pages', $row['uid']));
                    $permsEdit = $localCalcPerms & Permission::PAGE_EDIT;
                } else {
                    $permsEdit = $this->calcPerms & Permission::CONTENT_EDIT;
                }
                // "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
                if ($permsEdit) {
                    $params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
                    $code = '<a href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, '', -1)) . '" title="' . htmlspecialchars($lang->getLL('edit')) . '">' . $code . '</a>';
                }
                break;
            case 'show':
                // "Show" link (only pages and tt_content elements)
                if ($table === 'pages' || $table === 'tt_content') {
                    $code = '<a href="#" onclick="' . htmlspecialchars(
                        BackendUtility::viewOnClick(($table === 'tt_content' ? $this->id . '#' . $row['uid'] : $row['uid']))
                    ) . '" title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage')) . '">' . $code . '</a>';
                }
                break;
            case 'info':
                // "Info": (All records)
                $code = '<a href="#" onclick="' . htmlspecialchars('top.TYPO3.InfoWindow.showItem(\'' . $table . '\', \'' . $row['uid'] . '\'); return false;') . '" title="' . htmlspecialchars($lang->getLL('showInfo')) . '">' . $code . '</a>';
                break;
            default:
                // Output the label now:
                if ($table === 'pages') {
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
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
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $url = (string)$uriBuilder->buildUriFromRoutePath($routePath, $urlParameters);
        } else {
            $url = GeneralUtility::getIndpEnv('SCRIPT_NAME') . HttpUtility::buildQueryString($urlParameters, '?');
        }
        return $url;
    }

    /**
     * Returns "requestUri" - which is basically listURL
     * @return string Content of ->listURL()
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
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
                    if ($dontCheckUser || (!$fieldValue['exclude'] || $backendUser->check('non_exclude_fields', $table . ':' . $fN)) && $fieldValue['config']['type'] !== 'passthrough') {
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
                    if (ExtensionManagementUtility::isLoaded('workspaces') && $GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
                        $fieldListArr[] = 't3ver_id';
                        $fieldListArr[] = 't3ver_state';
                        $fieldListArr[] = 't3ver_wsid';
                    }
                }
            } else {
                GeneralUtility::makeInstance(LogManager::class)
                    ->getLogger(__CLASS__)
                    ->error('TCA is broken for the table "' . $table . '": no required "columns" entry in TCA.');
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    protected function getSearchableWebmounts($id, $depth, $perms_clause)
    {
        $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime');
        $cacheIdentifier = md5('pidList_' . $id . '_' . $depth . '_' . $perms_clause);
        $idList = $runtimeCache->get($cacheIdentifier);
        if ($idList) {
            return $idList;
        }

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
        $runtimeCache->set($cacheIdentifier, $idList);
        return $idList;
    }

    /**
     * Redirects to FormEngine if a record is just localized.
     *
     * @param string $justLocalized String with table, orig uid and language separated by ":
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function localizationRedirect($justLocalized)
    {
        list($table, $orig_uid, $language) = explode(':', $justLocalized);
        if ($GLOBALS['TCA'][$table]
            && $GLOBALS['TCA'][$table]['ctrl']['languageField']
            && $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']
        ) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

            $localizedRecordUid = $queryBuilder->select('uid')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA'][$table]['ctrl']['languageField'],
                        $queryBuilder->createNamedParameter($language, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'],
                        $queryBuilder->createNamedParameter($orig_uid, \PDO::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->execute()
                ->fetchColumn();

            if ($localizedRecordUid !== false) {
                // Create parameters and finally run the classic page module for creating a new page translation
                $url = $this->listURL();
                /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
                $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
                $editUserAccountUrl = (string)$uriBuilder->buildUriFromRoute(
                    'record_edit',
                    [
                        'edit[' . $table . '][' . $localizedRecordUid . ']' => 'edit',
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
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
     * @return array
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function getOverridePageIdList(): array
    {
        return $this->overridePageIdList;
    }

    /**
     * @param int[]|array $overridePageIdList
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function setOverridePageIdList(array $overridePageIdList)
    {
        $this->overridePageIdList = array_map('intval', $overridePageIdList);
    }

    /**
     * Add conditions to the QueryBuilder object ($queryBuilder) to limit a
     * query to a list of page IDs based on the current search level setting.
     *
     * @param string $tableName
     * @param QueryBuilder $queryBuilder
     * @return QueryBuilder Modified QueryBuilder object
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    protected function addPageIdConstraint(string $tableName, QueryBuilder $queryBuilder): QueryBuilder
    {
        // Set search levels:
        $searchLevels = $this->searchLevels;

        // Set search levels to 999 instead of -1 as the following methods
        // do not support -1 as valid value for infinite search.
        if ($searchLevels === -1) {
            $searchLevels = 999;
        }

        if ($searchLevels === 0) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    $tableName . '.pid',
                    $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)
                )
            );
        } elseif ($searchLevels > 0) {
            $allowedPidList = $this->getSearchableWebmounts($this->id, $searchLevels, $this->perms_clause);
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    $tableName . '.pid',
                    $queryBuilder->createNamedParameter($allowedPidList, Connection::PARAM_INT_ARRAY)
                )
            );
        }

        if (!empty($this->getOverridePageIdList())) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    $tableName . '.pid',
                    $queryBuilder->createNamedParameter($this->getOverridePageIdList(), Connection::PARAM_INT_ARRAY)
                )
            );
        }

        return $queryBuilder;
    }

    /**
     * Method used to log deprecated usage of old buildQueryParametersPostProcess hook arguments
     *
     * @param string $index
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    protected function logDeprecation(string $index)
    {
        trigger_error('[index: ' . $index . '] $parameters in "buildQueryParameters"-Hook will be removed in TYPO3 v10.0, use $queryBuilder instead.', E_USER_DEPRECATED);
    }

    /**
     * @return BackendUserAuthentication
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
