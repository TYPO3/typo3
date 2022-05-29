<?php

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

namespace TYPO3\CMS\Recordlist\RecordList;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Backend\Avatar\Avatar;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\RecordList\RecordListGetTableHookInterface;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Recordlist\Event\ModifyRecordListHeaderColumnsEvent;
use TYPO3\CMS\Recordlist\Event\ModifyRecordListRecordActionsEvent;
use TYPO3\CMS\Recordlist\Event\ModifyRecordListTableActionsEvent;

/**
 * Class for rendering of Web>List module
 * @internal This class is a specific TYPO3 Backend implementation and is not part of the TYPO3's Core API.
 */
class DatabaseRecordList
{
    // *********
    // External:
    // *********

    /**
     * Used to indicate which tables (values in the array) that can have a
     * create-new-record link. If the array is empty, all tables are allowed.
     *
     * @var string[]
     */
    public $allowedNewTables = [];

    /**
     * Used to indicate which tables (values in the array) that cannot have a
     * create-new-record link. If the array is empty, all tables are allowed.
     *
     * @var string[]
     */
    public $deniedNewTables = [];

    /**
     * If TRUE, will show the clipboard related actions in the table header.
     *
     * @var bool
     */
    public $showClipboardActions = false;

    /**
     * If TRUE, will DISABLE all control panels in lists. (Takes precedence)
     *
     * @var bool
     */
    public $noControlPanels = false;

    /**
     * If TRUE, clickmenus will be rendered
     *
     * @var bool
     */
    public $clickMenuEnabled = true;

    /**
     * Space icon used for alignment
     *
     * @var string
     */
    protected string $spaceIcon;

    /**
     * Disable single table view
     *
     * @var bool
     */
    public $disableSingleTableView = false;

    // *********
    // Internal:
    // *********

    /**
     * Set to the page record (see writeTop())
     *
     * @var string[]
     */
    public $pageRow = [];

    /**
     * Tables which should not list their translations
     *
     * @var string
     */
    public $hideTranslations = '';

    /**
     * Cache for record path
     *
     * @var mixed[]
     */
    protected array $recPath_cache = [];

    /**
     * Field, to sort list by
     *
     * @var string
     */
    public $sortField;

    /**
     * Module data
     *
     * @internal
     * @var array
     */
    protected array $moduleData = [];

    /**
     * Page id
     *
     * @var int
     */
    public $id;

    /**
     * Used for tracking duplicate values of fields
     *
     * @var string[]
     */
    protected array $duplicateStack = [];

    /**
     * If TRUE, records are listed only if a specific table is selected.
     *
     * @var bool
     */
    public $listOnlyInSingleTableMode = false;

    protected TranslationConfigurationProvider $translateTools;

    /**
     * @var array[] Module configuration
     */
    public $modTSconfig;

    /**
     * Keys are fieldnames and values are td-css-classes to add in addElement();
     *
     * @var array
     */
    protected array $addElement_tdCssClass = [];

    /**
     * Used for tracking next/prev uids
     *
     * @var int[][]
     */
    public $currentTable = [];

    /**
     * Number of records to show
     *
     * @var int
     */
    public int $showLimit = 0;

    /**
     * Decides the columns shown. Filled with values that refers to the keys of the data-array. $this->fieldArray[0] is the title column.
     *
     * @var array
     */
    public $fieldArray = [];

    /**
     * Tables which should not get listed
     *
     * @var string
     */
    public $hideTables = '';

    /**
     * Page select permissions
     *
     * @var string
     */
    public $perms_clause = '';

    /**
     * Return URL
     *
     * @var string
     */
    public $returnUrl = '';

    /**
     * Tablename if single-table mode
     *
     * @var string
     */
    public $table = '';

    /**
     * Some permissions...
     *
     * @var Permission
     */
    public $calcPerms;

    /**
     * Mode for what happens when a user clicks the title of a record.
     *
     * @var string
     */
    public $clickTitleMode = '';

    /**
     * Levels to search down.
     *
     * @var int
     */
    protected int $searchLevels = 0;

    /**
     * TSconfig which overwrites TCA-Settings
     *
     * @var mixed[][]
     */
    public $tableTSconfigOverTCA = [];

    /**
     * Fields to display for the current table
     *
     * @var string[][]
     */
    public $setFields = [];

    /**
     * Paging for the single table view
     *
     * @var int
     */
    protected $page = 0;

    /**
     * Search string
     *
     * @var string
     */
    public $searchString = '';

    /**
     * Field, indicating to sort in reverse order.
     *
     * @var bool
     */
    public $sortRev;

    /**
     * String, can contain the field name from a table which must have duplicate values marked.
     *
     * @var string
     */
    protected $duplicateField;

    /**
     * Specify a list of tables which are the only ones allowed to be displayed.
     *
     * @var string
     */
    public $tableList = '';

    /**
     * Clipboard object
     *
     * @var Clipboard
     */
    public $clipObj;

    /**
     * Tracking names of elements (for clipboard use)
     *
     * @var string[]
     */
    public $CBnames = [];

    /**
     * Whether the column selector should be displayed in the tables' header
     *
     * @var bool
     * @internal
     */
    public bool $displayColumnSelector = true;

    /**
     * Whether the record download should be displayed in the tables' header
     *
     * @var bool
     * @internal
     */
    public bool $displayRecordDownload = true;

    /**
     * [$tablename][$uid] = number of references to this record
     *
     * @var int[][]
     */
    protected array $referenceCount = [];

    /**
     * If defined the records are editable
     *
     * @var bool
     */
    protected bool $editable = true;
    protected IconFactory $iconFactory;
    protected UriBuilder $uriBuilder;

    /**
     * Array with before/after setting for tables
     * Structure:
     * 'tableName' => [
     *    'before' => ['A', ...]
     *    'after' => []
     *  ]
     *
     * @var array[]
     */
    protected array $tableDisplayOrder = [];

    /**
     * Override the page ids taken into account by getPageIdConstraint()
     *
     * @var array
     */
    protected array $overridePageIdList = [];

    /**
     * Override/add urlparameters in listUrl() method
     * @var mixed[]
     */
    protected array $overrideUrlParameters = [];

    /**
     * Current link: array with table names and uid
     *
     * @var array
     */
    protected array $currentLink = [];

    /**
     * Only used to render translated records, used in list module to show page translations
     *
     * @var bool
     */
    protected bool $showOnlyTranslatedRecords = false;

    /**
     * This array contains all possible language uids, which could be translations of a record (excluding pages) in the default language
     *
     * It mainly depends on the current pageUid.
     * Translations are possible, depending on
     * - the site config
     * - already translated page records
     *
     * @var int[]
     */
    protected array $possibleTranslations = [];

    /**
     * All languages that are allowed by the user
     *
     * This is used for the translation handling of pages only.
     *
     * @var array
     */
    protected array $languagesAllowedForUser = [];

    /**
     * A runtime first-level cache to avoid unneeded calls to BackendUtility::getRecord()
     * @var array
     */
    protected array $pagePermsCache = [];

    /**
     * A runtime first-level cache to avoid unneeded calls to BackendUtility::getRecord()
     * @var array
     */
    protected array $backendUserCache = [];

    protected array $showLocalizeColumn = [];

    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(
        IconFactory $iconFactory,
        UriBuilder $uriBuilder,
        TranslationConfigurationProvider $translateTools,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->iconFactory = $iconFactory;
        $this->uriBuilder = $uriBuilder;
        $this->translateTools = $translateTools;
        $this->eventDispatcher = $eventDispatcher;
        $this->calcPerms = new Permission();
        $this->spaceIcon = '<span class="btn btn-default disabled" aria-hidden="true">' . $this->iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
    }

    /**
     * Returns a list of all fields / columns including meta columns such as
     * "_REF_" or "_PATH_" which should be rendered for the databsae table.
     *
     * @param string $table
     * @param bool $includeMetaColumns
     * @return array
     */
    public function getColumnsToRender(string $table, bool $includeMetaColumns): array
    {
        $titleCol = $GLOBALS['TCA'][$table]['ctrl']['label'] ?? '';

        // Setting fields selected in columnSelectorBox (saved in uc)
        $rowListArray = [];
        if (is_array($this->setFields[$table] ?? null)) {
            $rowListArray = BackendUtility::getAllowedFieldsForTable($table);
            if ($includeMetaColumns) {
                $rowListArray[] = '_PATH_';
                $rowListArray[] = '_REF_';
            }
            $rowListArray = array_intersect($rowListArray, $this->setFields[$table]);
        }
        // if no columns have been specified, show description (if configured)
        if (!empty($GLOBALS['TCA'][$table]['ctrl']['descriptionColumn']) && empty($rowListArray)) {
            $rowListArray[] = $GLOBALS['TCA'][$table]['ctrl']['descriptionColumn'];
        }

        // Initialize columns to select
        $columnsToSelect = [$titleCol];
        if ($includeMetaColumns) {
            // If meta columns are enabled, add the record icon
            array_unshift($columnsToSelect, 'icon');

            if ($this->noControlPanels === false) {
                // Add _SELECTOR_ as first item in case control panels are not disabled
                array_unshift($columnsToSelect, '_SELECTOR_');

                // Control-Panel
                $columnsToSelect[] = '_CONTROL_';
            }
            // Path
            if (!in_array('_PATH_', $rowListArray, true) && $this->searchLevels) {
                $columnsToSelect[] = '_PATH_';
            }
            // Localization
            if (BackendUtility::isTableLocalizable($table)) {
                $columnsToSelect[] = '_LOCALIZATION_';
                // Do not show the "Localize to:" field when only translated records should be shown
                if (!$this->showOnlyTranslatedRecords) {
                    $columnsToSelect[] = '_LOCALIZATION_b';
                }
            }
        }
        return array_unique(array_merge($columnsToSelect, $rowListArray));
    }

    /**
     * Based on the columns which should be rendered this method returns a list of actual
     * database fields to be selected from the query string.
     *
     * @param string $table
     * @param array $columnsToRender
     * @return string[] a list of all database table fields
     */
    public function getFieldsToSelect(string $table, array $columnsToRender): array
    {
        $selectFields = $columnsToRender;
        $selectFields[] = 'uid';
        $selectFields[] = 'pid';
        if ($table === 'pages') {
            $selectFields[] = 'module';
            $selectFields[] = 'extendToSubpages';
            $selectFields[] = 'nav_hide';
            $selectFields[] = 'doktype';
            $selectFields[] = 'shortcut';
            $selectFields[] = 'shortcut_mode';
            $selectFields[] = 'mount_pid';
        }
        if (is_array($GLOBALS['TCA'][$table]['ctrl']['enablecolumns'] ?? null)) {
            $selectFields = array_merge($selectFields, array_values($GLOBALS['TCA'][$table]['ctrl']['enablecolumns']));
        }
        foreach (['type', 'typeicon_column', 'editlock'] as $field) {
            if ($GLOBALS['TCA'][$table]['ctrl'][$field] ?? false) {
                $selectFields[] = $GLOBALS['TCA'][$table]['ctrl'][$field];
            }
        }
        if (BackendUtility::isTableWorkspaceEnabled($table)) {
            $selectFields[] = 't3ver_state';
            $selectFields[] = 't3ver_wsid';
            $selectFields[] = 't3ver_oid';
        }
        if (BackendUtility::isTableLocalizable($table)) {
            $selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
            $selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
        }
        if ($GLOBALS['TCA'][$table]['ctrl']['label_alt'] ?? false) {
            $selectFields = array_merge(
                $selectFields,
                GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['label_alt'], true)
            );
        }
        // Unique list!
        $selectFields = array_unique($selectFields);
        $fieldListFields = BackendUtility::getAllowedFieldsForTable($table, false);
        // Making sure that the fields in the field-list ARE in the field-list from TCA!
        return array_intersect($selectFields, $fieldListFields);
    }

    /**
     * Creates the listing of records from a single table
     *
     * @param string $table Table name
     * @param int $id Page id
     * @throws \UnexpectedValueException
     * @return string HTML table with the listing for the record.
     */
    public function getTable($table, $id)
    {
        // Finding the total amount of records on the page
        $queryBuilderTotalItems = $this->getQueryBuilder($table, $id, [], ['*'], false, 0, 1);
        $totalItems = (int)$queryBuilderTotalItems->count('*')
            ->executeQuery()
            ->fetchOne();
        if ($totalItems === 0) {
            return '';
        }
        // set the limits
        // Use default value and overwrite with page ts config and tca config depending on the current view
        // Force limit in range 5, 10000
        // default 100
        $itemsLimitSingleTable = MathUtility::forceIntegerInRange((int)(
            $GLOBALS['TCA'][$table]['interface']['maxSingleDBListItems'] ??
            $this->modTSconfig['itemsLimitSingleTable'] ??
            100
        ), 5, 10000);

        // default 20
        $itemsLimitPerTable = MathUtility::forceIntegerInRange((int)(
            $GLOBALS['TCA'][$table]['interface']['maxDBListItems'] ??
            $this->modTSconfig['itemsLimitPerTable'] ??
            20
        ), 5, 10000);

        // Set limit depending on the view (single table vs. default)
        $itemsPerPage = $this->table ? $itemsLimitSingleTable : $itemsLimitPerTable;

        // Set limit defined by calling code
        if ($this->showLimit) {
            $itemsPerPage = $this->showLimit;
        }

        // Set limit from search
        if ($this->searchString) {
            $itemsPerPage = $totalItems;
        }

        // Init
        $titleCol = $GLOBALS['TCA'][$table]['ctrl']['label'];
        $l10nEnabled = BackendUtility::isTableLocalizable($table);

        $this->fieldArray = $this->getColumnsToRender($table, true);
        // Creating the list of fields to include in the SQL query
        $selectFields = $this->getFieldsToSelect($table, $this->fieldArray);

        $firstElement = ($this->page - 1) * $itemsPerPage;
        if ($firstElement > 2 && $itemsPerPage > 0) {
            // Get the two previous rows for sorting if displaying page > 1
            $firstElement -= 2;
            $itemsPerPage += 2;
            $queryBuilder = $this->getQueryBuilder($table, $id, [], $selectFields, true, $firstElement, $itemsPerPage);
            $firstElement += 2;
            $itemsPerPage -= 2;
        } else {
            $queryBuilder = $this->getQueryBuilder($table, $id, [], $selectFields, true, $firstElement, $itemsPerPage);
        }

        $queryResult = $queryBuilder->executeQuery();
        $columnsOutput = '';
        $onlyShowRecordsInSingleTableMode = $this->listOnlyInSingleTableMode && !$this->table;
        // Fetch records only if not in single table mode
        if ($onlyShowRecordsInSingleTableMode) {
            $dbCount = $totalItems;
        } elseif ($firstElement + $itemsPerPage <= $totalItems) {
            $dbCount = $itemsPerPage + 2;
        } else {
            $dbCount = $totalItems - $firstElement + 2;
        }
        // If any records was selected, render the list:
        if ($dbCount === 0) {
            return '';
        }

        // Get configuration of collapsed tables from user uc
        $lang = $this->getLanguageService();

        $tableIdentifier = $table;
        // Use a custom table title for translated pages
        if ($table === 'pages' && $this->showOnlyTranslatedRecords) {
            // pages records in list module are split into two own sections, one for pages with
            // sys_language_uid = 0 "Page" and an own section for sys_language_uid > 0 "Page Translation".
            // This if sets the different title for the page translation case and a unique table identifier
            // which is used in DOM as id.
            $tableTitle = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:pageTranslation'));
            $tableIdentifier = 'pages_translated';
        } else {
            $tableTitle = htmlspecialchars($lang->sL($GLOBALS['TCA'][$table]['ctrl']['title']));
            if ($tableTitle === '') {
                $tableTitle = $table;
            }
        }

        $backendUser = $this->getBackendUserAuthentication();
        $tablesCollapsed = $backendUser->getModuleData('list') ?? [];
        $tableCollapsed = (bool)($tablesCollapsed[$table] ?? false);

        // Header line is drawn
        $theData = [];
        if ($this->disableSingleTableView) {
            $theData[$titleCol] = BackendUtility::wrapInHelp($table, '', $tableTitle) . ' (<span class="t3js-table-total-items">' . $totalItems . '</span>)';
        } else {
            $icon = $this->table // @todo separate table header from contract/expand link
                ? '<span title="' . htmlspecialchars($lang->getLL('contractView')) . '">' . $this->iconFactory->getIcon('actions-view-table-collapse', Icon::SIZE_SMALL)->render() . '</span>'
                : '<span title="' . htmlspecialchars($lang->getLL('expandView')) . '">' . $this->iconFactory->getIcon('actions-view-table-expand', Icon::SIZE_SMALL)->render() . '</span>';
            $theData[$titleCol] = $this->linkWrapTable($table, $tableTitle . ' (<span class="t3js-table-total-items">' . $totalItems . '</span>) ' . $icon);
        }
        $tableActions = '';
        if ($onlyShowRecordsInSingleTableMode) {
            $tableHeader = BackendUtility::wrapInHelp($table, '', $theData[$titleCol]);
        } else {
            $tableHeader = $theData[$titleCol];
            // Add the "new record" button
            $tableActions .= $this->createNewRecordButton($table);
            // Render collapse button if in multi table mode
            if (!$this->table) {
                $title = sprintf(htmlspecialchars($lang->getLL('collapseExpandTable')), $tableTitle);
                $icon = '<span class="collapseIcon">' . $this->iconFactory->getIcon(($tableCollapsed ? 'actions-view-list-expand' : 'actions-view-list-collapse'), Icon::SIZE_SMALL)->render() . '</span>';
                $tableActions .= '<button type="button"'
                    . ' class="btn btn-default btn-sm pull-right t3js-toggle-recordlist"'
                    . ' title="' . $title . '"'
                    . ' aria-label="' . $title . '"'
                    . ' aria-expanded="' . ($tableCollapsed ? 'false' : 'true') . '"'
                    . ' data-table="' . htmlspecialchars($tableIdentifier) . '"'
                    . ' data-bs-toggle="collapse"'
                    . ' data-bs-target="#recordlist-' . htmlspecialchars($tableIdentifier) . '">'
                    . $icon
                    . '</button>';
            }
            // Show the select box
            $tableActions .= $this->columnSelector($table);
            // Create the Download button
            $tableActions .= $this->createDownloadButtonForTable($table, $totalItems);
        }
        $currentIdList = [];
        // Render table rows only if in multi table view or if in single table view
        $rowOutput = '';
        if (!$onlyShowRecordsInSingleTableMode || $this->table) {
            // Fixing an order table for sortby tables
            $this->currentTable = [];
            $allowManualSorting = ($GLOBALS['TCA'][$table]['ctrl']['sortby'] ?? false) && !$this->sortField;
            $prevUid = 0;
            $prevPrevUid = 0;
            // Get first two rows and initialize prevPrevUid and prevUid if on page > 1
            if ($firstElement > 2 && $itemsPerPage > 0) {
                $row = $queryResult->fetchAssociative();
                $prevPrevUid = -((int)$row['uid']);
                $row = $queryResult->fetchAssociative();
                $prevUid = $row['uid'];
            }
            $accRows = [];
            // Accumulate rows here
            while ($row = $queryResult->fetchAssociative()) {
                if (!$this->isRowListingConditionFulfilled($table, $row)) {
                    continue;
                }
                // In offline workspace, look for alternative record
                BackendUtility::workspaceOL($table, $row, $backendUser->workspace, true);
                if (is_array($row)) {
                    $accRows[] = $row;
                    $currentIdList[] = $row['uid'];
                    if ($allowManualSorting) {
                        if ($prevUid) {
                            $this->currentTable['prev'][$row['uid']] = $prevPrevUid;
                            $this->currentTable['next'][$prevUid] = '-' . $row['uid'];
                            $this->currentTable['prevUid'][$row['uid']] = $prevUid;
                        }
                        $prevPrevUid = isset($this->currentTable['prev'][$row['uid']]) ? -$prevUid : $row['pid'];
                        $prevUid = $row['uid'];
                    }
                }
            }
            // Render items:
            $this->CBnames = [];
            $this->duplicateStack = [];
            $cc = 0;

            // If no search happened it means that the selected
            // records are either default or All language and here we will not select translations
            // which point to the main record:
            $listTranslatedRecords = $l10nEnabled && $this->searchString === '' && !($this->hideTranslations === '*' || GeneralUtility::inList($this->hideTranslations, $table));
            foreach ($accRows as $row) {
                // Render item row if counter < limit
                if ($cc < $itemsPerPage) {
                    $cc++;
                    // Reset translations
                    $translations = [];
                    // Initialize with FALSE which causes the localization panel to not be displayed as
                    // the record is already localized, in free mode or has sys_language_uid -1 set.
                    // Only set to TRUE if TranslationConfigurationProvider::translationInfo() returns
                    // an array indicating the record can be translated.
                    $translationEnabled = false;
                    // Guard clause so we can quickly return if a record is localized to "all languages"
                    // It should only be possible to localize a record off default (uid 0)
                    if ($l10nEnabled && ($row[$GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? null] ?? false) !== -1) {
                        $translationsRaw = $this->translateTools->translationInfo($table, $row['uid'], 0, $row, $selectFields);
                        if (is_array($translationsRaw)) {
                            $translationEnabled = true;
                            $translations = $translationsRaw['translations'] ?? [];
                        }
                    }
                    $rowOutput .= $this->renderListRow($table, $row, 0, $translations, $translationEnabled);
                    if ($listTranslatedRecords) {
                        foreach ($translations ?? [] as $lRow) {
                            if (!$this->isRowListingConditionFulfilled($table, $lRow)) {
                                continue;
                            }
                            // In offline workspace, look for alternative record:
                            BackendUtility::workspaceOL($table, $lRow, $backendUser->workspace, true);
                            if (is_array($lRow) && $backendUser->checkLanguageAccess($lRow[$GLOBALS['TCA'][$table]['ctrl']['languageField']])) {
                                $currentIdList[] = $lRow['uid'];
                                $rowOutput .= $this->renderListRow($table, $lRow, 18, [], false);
                            }
                        }
                    }
                }
            }
            // Record navigation is added to the beginning and end of the table if in single table mode
            if ($this->table) {
                $pagination = $this->renderListNavigation($this->table, $totalItems, $itemsPerPage);
                $rowOutput = $pagination . $rowOutput . $pagination;
            } elseif ($totalItems > $itemsLimitPerTable) {
                // Show that there are more records than shown
                $rowOutput .= '
                    <tr>
                        <td colspan="' . (count($this->fieldArray)) . '">
                            <a href="' . htmlspecialchars($this->listURL() . '&table=' . rawurlencode($tableIdentifier)) . '" class="btn btn-default">
                                ' . $this->iconFactory->getIcon('actions-caret-down', Icon::SIZE_SMALL)->render() . '
                                ' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.expandTable') . '
                            </a>
                        </td>
                    </tr>';
            }
            // The header row for the table is now created
            $columnsOutput = $this->renderListHeader($table, $currentIdList);
        }

        // Initialize multi record selection actions
        $multiRecordSelectionActions = '';
        if ($this->noControlPanels === false) {
            $multiRecordSelectionActions = '
                <div class="col t3js-multi-record-selection-actions hidden">
                    <div class="row row-cols-auto align-items-center g-2">
                        <div class="col">
                            <strong>
                                ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.selection')) . '
                            </strong>
                        </div>
                        ' . $this->renderMultiRecordSelectionActions($table, $currentIdList) . '
                    </div>
                </div>
            ';
        }

        $collapseClass = $tableCollapsed && !$this->table ? 'collapse' : 'collapse show';
        $dataState = $tableCollapsed && !$this->table ? 'collapsed' : 'expanded';
        return '
            <div class="recordlist mb-5 mt-4 border" id="t3-table-' . htmlspecialchars($tableIdentifier) . '" data-multi-record-selection-identifier="t3-table-' . htmlspecialchars($tableIdentifier) . '">
                <form action="' . htmlspecialchars($this->listURL()) . '#t3-table-' . htmlspecialchars($tableIdentifier) . '" method="post" name="list-table-form-' . htmlspecialchars($tableIdentifier) . '">
                    <input type="hidden" name="cmd_table" value="' . htmlspecialchars($tableIdentifier) . '" />
                    <input type="hidden" name="cmd" />
                    <div class="recordlist-heading row m-0 p-2 g-0 gap-1 align-items-center ' . ($multiRecordSelectionActions !== '' ? 'multi-record-selection-panel' : '') . '">
                        ' . $multiRecordSelectionActions . '
                        <div class="col ms-2">
                            <span class="text-truncate">
                            ' . $tableHeader . '
                            </span>
                        </div>
                        <div class="col-auto">
                         ' . $tableActions . '
                        </div>
                    </div>
                    <div class="' . $collapseClass . '" data-state="' . $dataState . '" id="recordlist-' . htmlspecialchars($tableIdentifier) . '">
                        <div class="table-fit mb-0">
                            <table data-table="' . htmlspecialchars($tableIdentifier) . '" class="table table-striped table-hover mb-0">
                                <thead>
                                    ' . $columnsOutput . '
                                </thead>
                                <tbody data-multi-record-selection-row-selection="true">
                                    ' . $rowOutput . '
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
        ';
    }

    /**
     * If new records can be created on this page, create a button
     *
     * @param string $table
     * @return string
     */
    protected function createNewRecordButton(string $table): string
    {
        if (!$this->isEditable($table)) {
            return '';
        }
        if (!$this->showNewRecLink($table)) {
            return '';
        }
        $permsAdditional = ($table === 'pages' ? Permission::PAGE_NEW : Permission::CONTENT_EDIT);
        if (!$this->calcPerms->isGranted($permsAdditional)) {
            return '';
        }

        $iconIdentifier = 'actions-add';
        $title = $this->getLanguageService()->getLL('new');
        $attributes = [
            'title' => $title,
            'aria-label' => $title,
            'class' => 'btn btn-default btn-sm',
        ];

        if ($table === 'tt_content') {
            $url = (string)$this->uriBuilder->buildUriFromRoute(
                BackendUtility::getPagesTSconfig($this->id)['mod.']['newContentElementWizard.']['override'] ?? 'new_content_element_wizard',
                [
                    'id' => $this->id,
                    'returnUrl' => $this->listURL(),
                ]
            );
            return '
                <div class="btn-group me-2">
                    <typo3-backend-new-content-element-wizard-button url="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($title) . '">
                        <button type="button" ' . GeneralUtility::implodeAttributes($attributes, true) . '>
                            ' . $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render() . '
                            ' . htmlspecialchars($title) . '
                        </button>
                    </typo3-backend-new-content-element-wizard-button>
                </div>';
        }

        if ($table === 'pages') {
            $iconIdentifier = 'actions-page-new';
            $attributes['data-new'] = 'page';
            $attributes['href'] = (string)$this->uriBuilder->buildUriFromRoute(
                'db_new_pages',
                ['id' => $this->id, 'returnUrl' => $this->listURL()]
            );
        } else {
            $attributes['href'] = $this->uriBuilder->buildUriFromRoute(
                'record_edit',
                [
                    'edit' => [
                        $table => [
                            $this->id => 'new',
                        ],
                    ],
                    'returnUrl' => $this->listURL(),
                ]
            );
        }

        return '
            <div class="btn-group me-2">
                <a ' . GeneralUtility::implodeAttributes($attributes, true) . '>
                    ' . $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render() . '
                    ' . htmlspecialchars($title) . '
                </a>
            </div>';
    }

    protected function createDownloadButtonForTable(string $table, int $totalItems): string
    {
        // Do not render the download button for page translations or in case it is disabled
        if (!$this->displayRecordDownload
            || ($this->modTSconfig['noExportRecordsLinks'] ?? false)
            || $this->showOnlyTranslatedRecords
        ) {
            return '';
        }

        $downloadButtonLabel = $this->getLanguageService()->sL('LLL:EXT:recordlist/Resources/Private/Language/locallang_download.xlf:download');
        $downloadButtonTitle = sprintf($this->getLanguageService()->sL('LLL:EXT:recordlist/Resources/Private/Language/locallang_download.xlf:' . ($totalItems === 1 ? 'downloadRecord' : 'downloadRecords')), $totalItems);
        $downloadCancelTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cancel');
        $downloadSettingsUrl = $this->uriBuilder->buildUriFromRoute(
            'ajax_record_download_settings',
            ['id' => $this->id, 'table' => $table, 'searchString' => $this->searchString, 'searchLevels' => $this->searchLevels]
        );
        $downloadSettingsTitle = sprintf(
            $this->getLanguageService()->sL('LLL:EXT:recordlist/Resources/Private/Language/locallang_download.xlf:' . ($totalItems === 1 ? 'downloadRecordSettings' : 'downloadRecordsSettings')),
            $this->getLanguageService()->sL($GLOBALS['TCA'][$table]['ctrl']['title'] ?? '') ?: $table,
            $totalItems
        );

        return '
            <div class="pull-right">
                <typo3-recordlist-record-download-button
                    url="' . htmlspecialchars($downloadSettingsUrl) . '"
                    title="' . htmlspecialchars($downloadSettingsTitle) . '"
                    ok="' . htmlspecialchars($downloadButtonTitle) . '"
                    close="' . htmlspecialchars($downloadCancelTitle) . '"
                >
                    <button type="button" class="btn btn-default btn-sm me-2" title="' . htmlspecialchars($downloadButtonTitle) . '">' .
                        $this->iconFactory->getIcon('actions-database-export', Icon::SIZE_SMALL) . ' ' .
                        htmlspecialchars($downloadButtonLabel) .
                    '</button>
                </typo3-recordlist-record-download-button>
            </div>';
    }

    /**
     * Get preview link for pages or tt_content records
     *
     * @param string $table
     * @param array $row
     *
     * @return PreviewUriBuilder
     */
    protected function getPreviewUriBuilder(string $table, array $row): PreviewUriBuilder
    {
        if ($table === 'tt_content') {
            // Link to a content element, possibly translated and with anchor
            $additionalParams = '';
            $language = $row[$GLOBALS['TCA']['tt_content']['ctrl']['languageField'] ?? null] ?? 0;
            if ($language > 0) {
                $additionalParams = '&L=' . $language;
            }
            $previewUriBuilder = PreviewUriBuilder::create((int)$this->id)
                ->withSection('#c' . $row['uid'])
                ->withAdditionalQueryParameters($additionalParams);
        } elseif ($table === 'pages' && ($row[$GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'] ?? null] ?? 0) > 0) {
            // Link to a page translation needs uid of default language page as id
            $languageParentId = $row[$GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField']] ?? 0;
            $language = $row[$GLOBALS['TCA']['pages']['ctrl']['languageField']] ?? 0;
            $previewUriBuilder = PreviewUriBuilder::create($languageParentId)
                ->withSection('#c' . $row['uid'])
                ->withAdditionalQueryParameters('&L=' . $language);
        } else {
            // Link to a page in the default language
            $previewUriBuilder = PreviewUriBuilder::create((int)($row['uid'] ?? 0));
        }
        return $previewUriBuilder;
    }

    /**
     * Check if all row listing conditions are fulfilled.
     *
     * This function serves as a dummy method to be overridden in extending classes.
     *
     * @param string $table Table name
     * @param string[] $row Record
     * @return bool True, if all conditions are fulfilled.
     */
    protected function isRowListingConditionFulfilled($table, $row)
    {
        return true;
    }

    /**
     * Rendering a single row for the list
     *
     * @param string $table Table name
     * @param mixed[] $row Current record
     * @param int $indent Indent from left.
     * @param array $translations Array of already existing translations for the current record
     * @param bool $translationEnabled Whether the record can be translated
     * @return string Table row for the element
     * @internal
     * @see getTable()
     */
    public function renderListRow($table, array $row, int $indent, array $translations, bool $translationEnabled)
    {
        $titleCol = $GLOBALS['TCA'][$table]['ctrl']['label'] ?? '';
        $languageService = $this->getLanguageService();
        $rowOutput = '';
        $id_orig = $this->id;
        // If in search mode, make sure the preview will show the correct page
        if ((string)$this->searchString !== '') {
            $this->id = $row['pid'];
        }

        $tagAttributes = [
            'class' => [],
            'data-table' => $table,
            'title' => 'id=' . $row['uid'],
        ];

        // Add active class to record of current link
        if (
            isset($this->currentLink['tableNames'])
            && (int)$this->currentLink['uid'] === (int)$row['uid']
            && GeneralUtility::inList($this->currentLink['tableNames'], $table)
        ) {
            $tagAttributes['class'][] = 'active';
        }
        // Overriding with versions background color if any:
        if (!empty($row['_CSSCLASS'])) {
            $tagAttributes['class'] = [$row['_CSSCLASS']];
        }

        $tagAttributes['class'][] = 't3js-entity';

        // Preparing and getting the data-array
        $theData = [];
        $deletePlaceholderClass = '';
        foreach ($this->fieldArray as $fCol) {
            if ($fCol === $titleCol) {
                $recTitle = BackendUtility::getRecordTitle($table, $row, false, true);
                $warning = '';
                // If the record is edit-locked	by another user, we will show a little warning sign:
                $lockInfo = BackendUtility::isRecordLocked($table, $row['uid']);
                if ($lockInfo) {
                    $warning = '<span tabindex="0" data-bs-toggle="tooltip" data-bs-placement="right"'
                        . ' title="' . htmlspecialchars($lockInfo['msg']) . '"'
                        . ' aria-label="' . htmlspecialchars($lockInfo['msg']) . '">'
                        . $this->iconFactory->getIcon('warning-in-use', Icon::SIZE_SMALL)->render()
                        . '</span>';
                }
                if ($this->isRecordDeletePlaceholder($row)) {
                    // Delete placeholder records do not link to formEngine edit and are rendered strike-through
                    $deletePlaceholderClass = ' deletePlaceholder';
                    $theData[$fCol] = $theData['__label'] =
                        $warning
                        . '<span title="' . htmlspecialchars($languageService->sL('LLL:EXT:recordlist/Resources/Private/Language/locallang.xlf:row.deletePlaceholder.title')) . '">'
                            . htmlspecialchars($recTitle)
                        . '</span>';
                } else {
                    $theData[$fCol] = $theData['__label'] = $warning . $this->linkWrapItems($table, $row['uid'], $recTitle, $row);
                }
            } elseif ($fCol === 'pid') {
                $theData[$fCol] = $row[$fCol];
            } elseif ($fCol !== '' && $fCol === ($GLOBALS['TCA'][$table]['ctrl']['cruser_id'] ?? '')) {
                $theData[$fCol] = $this->getBackendUserInformation((int)$row[$fCol]);
            } elseif ($fCol === '_SELECTOR_') {
                if ($table !== 'pages' || !$this->showOnlyTranslatedRecords) {
                    // Add checkbox for all tables except the special page translations table
                    $theData[$fCol] = $this->makeCheckbox($table, $row);
                } else {
                    // Remove "_SELECTOR_", which is always the first item, from the field list
                    array_splice($this->fieldArray, 0, 1);
                }
            } elseif ($fCol === 'icon') {
                $iconImg = '
                    <span ' . BackendUtility::getRecordToolTip($row, $table) . ' ' . ($indent ? ' style="margin-left: ' . $indent . 'px;"' : '') . '>
                        ' . $this->iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render() . '
                    </span>';
                $theData[$fCol] = ($this->clickMenuEnabled && !$this->isRecordDeletePlaceholder($row)) ? BackendUtility::wrapClickMenuOnIcon($iconImg, $table, $row['uid']) : $iconImg;
            } elseif ($fCol === '_PATH_') {
                $theData[$fCol] = $this->recPath($row['pid']);
            } elseif ($fCol === '_REF_') {
                $theData[$fCol] = $this->generateReferenceToolTip($table, $row['uid']);
            } elseif ($fCol === '_CONTROL_') {
                $theData[$fCol] = $this->makeControl($table, $row);
            } elseif ($fCol === '_LOCALIZATION_') {
                // Language flag an title
                $theData[$fCol] = $this->languageFlag($table, $row);
                // Localize record
                $localizationPanel = $translationEnabled ? $this->makeLocalizationPanel($table, $row, $translations) : '';
                if ($localizationPanel !== '') {
                    $theData['_LOCALIZATION_b'] = '<div class="btn-group">' . $localizationPanel . '</div>';
                    $this->showLocalizeColumn[$table] = true;
                }
            } elseif ($fCol !== '_LOCALIZATION_b') {
                // default for all other columns, except "_LOCALIZATION_b"
                $pageId = $table === 'pages' ? $row['uid'] : $row['pid'];
                $tmpProc = BackendUtility::getProcessedValueExtra($table, $fCol, $row[$fCol], 100, $row['uid'], true, $pageId);
                $theData[$fCol] = $this->linkUrlMail(htmlspecialchars((string)$tmpProc), (string)($row[$fCol] ?? ''));
            }
        }
        // Reset the ID if it was overwritten
        if ((string)$this->searchString !== '') {
            $this->id = $id_orig;
        }
        // Add classes to table cells
        $this->addElement_tdCssClass['_SELECTOR_'] = 'col-selector';
        $this->addElement_tdCssClass[$titleCol] = 'col-title col-responsive' . $deletePlaceholderClass;
        $this->addElement_tdCssClass['__label'] = $this->addElement_tdCssClass[$titleCol];
        $this->addElement_tdCssClass['icon'] = 'col-icon';
        $this->addElement_tdCssClass['_CONTROL_'] = 'col-control';
        $this->addElement_tdCssClass['_PATH_'] = 'col-path';
        $this->addElement_tdCssClass['_LOCALIZATION_'] = 'col-localizationa';
        $this->addElement_tdCssClass['_LOCALIZATION_b'] = 'col-localizationb';
        // Create element in table cells:
        $theData['uid'] = $row['uid'];
        if (isset($GLOBALS['TCA'][$table]['ctrl']['languageField'])
            && isset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'])
        ) {
            $theData['_l10nparent_'] = $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']];
        }

        $tagAttributes = array_map(
            static function ($attributeValue) {
                if (is_array($attributeValue)) {
                    return implode(' ', $attributeValue);
                }
                return $attributeValue;
            },
            $tagAttributes
        );

        $rowOutput .= $this->addElement($theData, GeneralUtility::implodeAttributes($tagAttributes, true));
        // Finally, return table row element:
        return $rowOutput;
    }

    /**
     * Gets the number of records referencing the record with the UID $uid in
     * the table $tableName.
     *
     * @param string $tableName
     * @param int $uid
     * @return int The number of references to record $uid in table
     */
    protected function getReferenceCount($tableName, $uid)
    {
        if (!isset($this->referenceCount[$tableName][$uid])) {
            $referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
            $numberOfReferences = $referenceIndex->getNumberOfReferencedRecords($tableName, $uid);
            $this->referenceCount[$tableName][$uid] = $numberOfReferences;
        }
        return $this->referenceCount[$tableName][$uid];
    }

    /**
     * Rendering the header row for a table
     *
     * @param string $table Table name
     * @param int[] $currentIdList Array of the currently displayed uids of the table
     * @throws \UnexpectedValueException
     * @return string Header table row
     * @internal
     * @see getTable()
     */
    public function renderListHeader($table, $currentIdList)
    {
        $tsConfig = BackendUtility::getPagesTSconfig($this->id)['TCEFORM.'][$table . '.'] ?? null;
        $tsConfigOfTable = is_array($tsConfig) ? $tsConfig : null;

        $lang = $this->getLanguageService();
        // Init:
        $theData = [];
        // Traverse the fields:
        foreach ($this->fieldArray as $fCol) {
            // Calculate users permissions to edit records in the table:
            if ($table === 'pages') {
                $permsEdit = $this->calcPerms->editPagePermissionIsGranted();
            } else {
                $permsEdit = $this->calcPerms->editContentPermissionIsGranted();
            }

            $permsEdit = $permsEdit && $this->overlayEditLockPermissions($table);
            switch ((string)$fCol) {
                case '_SELECTOR_':
                    if ($table !== 'pages' || !$this->showOnlyTranslatedRecords) {
                        // Add checkbox actions for all tables except the special page translations table
                        $theData[$fCol] = $this->renderCheckboxActions();
                    } else {
                        // Remove "_SELECTOR_", which is always the first item, from the field list
                        array_splice($this->fieldArray, 0, 1);
                    }
                    break;
                case 'icon':
                    // In case no checkboxes are rendered (page translations or disabled) add the icon
                    // column, otherwise the selector column is using "colspan=2"
                    if (!in_array('_SELECTOR_', $this->fieldArray, true)
                        || ($table === 'pages' && $this->showOnlyTranslatedRecords)
                    ) {
                        $theData[$fCol] = '';
                    }
                    break;
                case '_CONTROL_':
                    $theData[$fCol] = '<i>[' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels._CONTROL_')) . ']</i>';
                    // In single table view, add button to edit displayed fields of marked / listed records
                    if ($this->table && $permsEdit && is_array($currentIdList) && $this->isEditable($table)) {
                        $theData[$fCol] = '<button type="button"'
                            . ' class="btn btn-default t3js-record-edit-multiple"'
                            . ' title="' . htmlspecialchars($lang->getLL('editShownColumns')) . '"'
                            . ' aria-label="' . htmlspecialchars($lang->getLL('editShownColumns')) . '"'
                            . ' data-return-url="' . htmlspecialchars($this->listURL()) . '"'
                            . ' data-columns-only="' . htmlspecialchars(implode(',', $this->fieldArray)) . '">'
                            . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render()
                            . '</button>';
                    }
                    break;
                case '_PATH_':
                    // Path
                    $theData[$fCol] = '<i>[' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels._PATH_')) . ']</i>';
                    break;
                case '_REF_':
                    // References
                    $theData[$fCol] = '<i>[' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels._REF_')) . ']</i>';
                    break;
                case '_LOCALIZATION_':
                    // Show language of record
                    $theData[$fCol] = '<i>[' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels._LOCALIZATION_')) . ']</i>';
                    break;
                case '_LOCALIZATION_b':
                    // Show translation options
                    if ($this->showLocalizeColumn[$table] ?? false) {
                        $theData[$fCol] = htmlspecialchars($lang->getLL('Localize'));
                    }
                    break;
                default:
                    // Regular fields header:
                    $theData[$fCol] = '';

                    // Check if $fCol is really a field and get the label and remove the colons
                    // at the end
                    $sortLabel = BackendUtility::getItemLabel($table, $fCol);
                    if ($sortLabel !== null) {
                        $sortLabel = rtrim(trim($lang->sL($sortLabel)), ':');

                        // Field label
                        $fieldTSConfig = [];
                        if (isset($tsConfigOfTable[$fCol . '.'])
                            && is_array($tsConfigOfTable[$fCol . '.'])
                        ) {
                            $fieldTSConfig = $tsConfigOfTable[$fCol . '.'];
                        }
                        if (!empty($fieldTSConfig['label'])) {
                            $sortLabel = $lang->sL($fieldTSConfig['label']);
                        }
                        if (!empty($fieldTSConfig['label.'][$lang->lang])) {
                            $sortLabel = $lang->sL($fieldTSConfig['label.'][$lang->lang]);
                        }
                        $sortLabel = htmlspecialchars($sortLabel);
                    } elseif ($specialLabel = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.' . $fCol)) {
                        // Special label exists for this field (Probably a management field, e.g. sorting)
                        $sortLabel = htmlspecialchars($specialLabel);
                    } else {
                        // No TCA field, only output the $fCol variable with square brackets []
                        $sortLabel = htmlspecialchars($fCol);
                        $sortLabel = '<i>[' . rtrim(trim($sortLabel), ':') . ']</i>';
                    }

                    if ($this->table && is_array($currentIdList)) {
                        // If the numeric clipboard pads are selected, show duplicate sorting link:
                        if ($this->noControlPanels === false
                            && $this->isClipboardFunctionalityEnabled($table)
                            && $this->clipObj->current !== 'normal'
                        ) {
                            $theData[$fCol] .= '<a class="btn btn-default" href="' . htmlspecialchars($this->listURL() . '&duplicateField=' . $fCol)
                                . '" title="' . htmlspecialchars($lang->getLL('clip_duplicates')) . '">'
                                . $this->iconFactory->getIcon('actions-document-duplicates-select', Icon::SIZE_SMALL)->render() . '</a>';
                        }
                        // If the table can be edited, add link for editing THIS field for all
                        // listed records:
                        if ($this->isEditable($table) && $permsEdit && ($GLOBALS['TCA'][$table]['columns'][$fCol] ?? false)) {
                            $iTitle = sprintf($lang->getLL('editThisColumn'), $sortLabel);
                            $theData[$fCol] .= '<button type="button"'
                                . ' class="btn btn-default t3js-record-edit-multiple"'
                                . ' title="' . htmlspecialchars($iTitle) . '"'
                                . ' aria-label="' . htmlspecialchars($iTitle) . '"'
                                . ' data-return-url="' . htmlspecialchars($this->listURL()) . '"'
                                . ' data-columns-only="' . htmlspecialchars($fCol) . '">'
                                . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render()
                                . '</button>';
                        }
                        if (strlen($theData[$fCol]) > 0) {
                            $theData[$fCol] = '<div class="btn-group">' . $theData[$fCol] . '</div> ';
                        }
                    }
                    $theData[$fCol] .= $this->addSortLink($sortLabel, $fCol, $table);
            }
        }

        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
            trigger_error(
                'The hook $TYPO3_CONF_VARS[SC_OPTIONS][typo3/class.db_list_extra.inc][actions] for calling method "renderListHeader" is deprecated and will stop working in TYPO3 v12.0. Use the ModifyRecordListHeaderColumnsEvent instead.',
                E_USER_DEPRECATED
            );
        }

        /*
         * hook:  renderListHeader: Allows to change the contents of columns/cells of the Web>List table headers
         * usage: Above each listed table in Web>List a header row is shown.
         *        Containing the labels of all shown fields and additional icons to create new records for this
         *        table or perform special clipboard tasks like mark and copy all listed records to clipboard, etc.
         *
         * @deprecated in v11, will be removed in TYPO3 v12.0.
         */
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof RecordListHookInterface) {
                throw new \UnexpectedValueException($className . ' must implement interface ' . RecordListHookInterface::class, 1195567855);
            }
            $theData = $hookObject->renderListHeader($table, $currentIdList, $theData, $this);
        }

        $event = $this->eventDispatcher->dispatch(
            new ModifyRecordListHeaderColumnsEvent($theData, $table, $currentIdList, $this)
        );

        // Create and return header table row:
        return $this->addElement($event->getColumns(), GeneralUtility::implodeAttributes($event->getHeaderAttributes(), true), 'th');
    }

    /**
     * Creates a page browser for tables with many records
     *
     * @param string $table
     * @param int $totalItems
     * @param int $itemsPerPage
     * @return string Navigation HTML
     */
    protected function renderListNavigation(string $table, int $totalItems, int $itemsPerPage): string
    {
        $currentPage = $this->page;
        $paginationColumns = count($this->fieldArray);
        $totalPages = (int)ceil($totalItems / $itemsPerPage);
        // Show page selector if not all records fit into one page
        if ($totalPages <= 1) {
            return '';
        }
        if ($totalItems > $currentPage * $itemsPerPage) {
            $lastElementNumber = $currentPage * $itemsPerPage;
        } else {
            $lastElementNumber = $totalItems;
        }
        return $this->getFluidTemplateObject('ListNavigation.html')
            ->assignMultiple([
                'currentUrl' => $this->listURL('', $table, 'pointer'),
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'firstElement' => ((($currentPage -1) * $itemsPerPage) + 1),
                'lastElement' => $lastElementNumber,
                'colspan' => $paginationColumns,
            ])
            ->render();
    }

    /*********************************
     *
     * Rendering of various elements
     *
     *********************************/

    /**
     * Creates the control panel for a single record in the listing.
     *
     * @param string $table The table
     * @param mixed[] $row The record for which to make the control panel.
     * @throws \UnexpectedValueException
     * @return string HTML table with the control panel (unless disabled)
     */
    public function makeControl($table, $row)
    {
        $backendUser = $this->getBackendUserAuthentication();
        $userTsConfig = $backendUser->getTSConfig();
        $rowUid = $row['uid'];
        if (ExtensionManagementUtility::isLoaded('workspaces') && isset($row['_ORIG_uid'])) {
            $rowUid = $row['_ORIG_uid'];
        }
        $isDeletePlaceHolder = $this->isRecordDeletePlaceholder($row);
        $cells = [
            'primary' => [],
            'secondary' => [],
        ];

        // Hide the move elements for localized records - doesn't make much sense to perform these options for them
        $isL10nOverlay = (int)($row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? null] ?? 0) !== 0;
        $localCalcPerms = $this->getPagePermissionsForRecord($table, $row);
        if ($table === 'pages') {
            $permsEdit = ($backendUser->checkLanguageAccess($row[$GLOBALS['TCA']['pages']['ctrl']['languageField'] ?? null] ?? 0))
                && $localCalcPerms->editPagePermissionIsGranted();
        } else {
            $permsEdit = $localCalcPerms->editContentPermissionIsGranted() && $backendUser->recordEditAccessInternals($table, $row);
        }
        $permsEdit = $this->overlayEditLockPermissions($table, $row, $permsEdit);

        // "Show" link (only pages and tt_content elements)
        $tsConfig = BackendUtility::getPagesTSconfig($this->id)['mod.']['web_list.'] ?? [];
        if ((
            $table === 'pages'
                && isset($row['doktype'])
                && !in_array((int)$row['doktype'], $this->getNoViewWithDokTypes($tsConfig), true)
        )
            || (
                $table === 'tt_content'
                && isset($this->pageRow['doktype'])
                && !in_array((int)$this->pageRow['doktype'], $this->getNoViewWithDokTypes($tsConfig), true)
            )
        ) {
            if (!$isDeletePlaceHolder) {
                $attributes = $this->getPreviewUriBuilder($table, $row)->serializeDispatcherAttributes();
                $viewAction = '<a href="#"'
                    . ' class="btn btn-default" ' . $attributes
                    . ' title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage')) . '">';
                if ($table === 'pages') {
                    $viewAction .= $this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL)->render();
                } else {
                    $viewAction .= $this->iconFactory->getIcon('actions-view', Icon::SIZE_SMALL)->render();
                }
                $viewAction .= '</a>';
                $this->addActionToCellGroup($cells, $viewAction, 'view');
            } else {
                $this->addActionToCellGroup($cells, $this->spaceIcon, 'view');
            }
        } else {
            $this->addActionToCellGroup($cells, $this->spaceIcon, 'view');
        }

        // "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
        if ($permsEdit && !$isDeletePlaceHolder && $this->isEditable($table)) {
            $params = [
                'edit' => [
                    $table => [
                        $row['uid'] => 'edit',
                    ],
                ],
            ];
            $iconIdentifier = 'actions-open';
            if ($table === 'pages') {
                // Disallow manual adjustment of the language field for pages
                $params['overrideVals']['pages']['sys_language_uid'] = $row[$GLOBALS['TCA']['pages']['ctrl']['languageField'] ?? null] ?? 0;
                $iconIdentifier = 'actions-page-open';
            }
            $params['returnUrl'] = $this->listURL();
            $editLink = $this->uriBuilder->buildUriFromRoute('record_edit', $params);
            $editAction = '<a class="btn btn-default" href="' . htmlspecialchars($editLink) . '"'
                . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('edit')) . '">' . $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render() . '</a>';
        } else {
            $editAction = $this->spaceIcon;
        }
        $this->addActionToCellGroup($cells, $editAction, 'edit');

        // "Info"
        if (!$isDeletePlaceHolder) {
            $viewBigAction = '<button type="button" aria-haspopup="dialog"'
                . ' class="btn btn-default" '
                . $this->createShowItemTagAttributes($table . ',' . ($row['uid'] ?? 0))
                . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('showInfo')) . '"'
                . ' aria-label="' . htmlspecialchars($this->getLanguageService()->getLL('showInfo')) . '">'
                . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render()
                . '</button>';
            $this->addActionToCellGroup($cells, $viewBigAction, 'viewBig');
        } else {
            $this->addActionToCellGroup($cells, $this->spaceIcon, 'viewBig');
        }

        // "Move" wizard link for pages/tt_content elements:
        if ($permsEdit && ($table === 'tt_content' || $table === 'pages') && $this->isEditable($table)) {
            if ($isL10nOverlay || $isDeletePlaceHolder) {
                $moveAction = $this->spaceIcon;
            } else {
                $linkTitleLL = htmlspecialchars($this->getLanguageService()->getLL('move_' . ($table === 'tt_content' ? 'record' : 'page')));
                $icon = ($table === 'pages' ? $this->iconFactory->getIcon('actions-page-move', Icon::SIZE_SMALL) : $this->iconFactory->getIcon('actions-document-move', Icon::SIZE_SMALL));
                $url = (string)$this->uriBuilder->buildUriFromRoute('move_element', [
                    'table' => $table,
                    'uid' => $row['uid'],
                    'returnUrl' => $this->listURL(),
                ]);
                $moveAction = '<a class="btn btn-default" href="' . htmlspecialchars($url) . '" aria-label="' . $linkTitleLL . '">' . $icon->render() . '</a>';
            }
            $this->addActionToCellGroup($cells, $moveAction, 'move');
        }

        // If the table is NOT a read-only table, then show these links:
        if ($this->isEditable($table)) {
            // "Revert" link (history/undo)
            if (\trim($userTsConfig['options.']['showHistory.'][$table] ?? $userTsConfig['options.']['showHistory'] ?? '1')) {
                if (!$isDeletePlaceHolder) {
                    $moduleUrl = $this->uriBuilder->buildUriFromRoute('record_history', [
                            'element' => $table . ':' . $row['uid'],
                            'returnUrl' => $this->listURL(),
                        ]) . '#latest';
                    $historyAction = '<a class="btn btn-default" href="' . htmlspecialchars($moduleUrl) . '" title="'
                        . htmlspecialchars($this->getLanguageService()->getLL('history')) . '">'
                        . $this->iconFactory->getIcon('actions-document-history-open', Icon::SIZE_SMALL)->render() . '</a>';
                    $this->addActionToCellGroup($cells, $historyAction, 'history');
                } else {
                    $this->addActionToCellGroup($cells, $this->spaceIcon, 'history');
                }
            }

            // "Edit Perms" link:
            if ($table === 'pages' && $backendUser->check('modules', 'system_BeuserTxPermission') && ExtensionManagementUtility::isLoaded('beuser')) {
                if ($isL10nOverlay || $isDeletePlaceHolder) {
                    $permsAction = $this->spaceIcon;
                } else {
                    $params = [
                        'id' => $row['uid'],
                        'action' => 'edit',
                        'returnUrl' => $this->listURL(),
                    ];
                    $href = (string)$this->uriBuilder->buildUriFromRoute('system_BeuserTxPermission', $params);
                    $permsAction = '<a class="btn btn-default" href="' . htmlspecialchars($href) . '" title="'
                        . htmlspecialchars($this->getLanguageService()->getLL('permissions')) . '">'
                        . $this->iconFactory->getIcon('actions-lock', Icon::SIZE_SMALL)->render() . '</a>';
                }
                $this->addActionToCellGroup($cells, $permsAction, 'perms');
            }

            // "New record after" link (ONLY if the records in the table are sorted by a "sortby"-row
            // or if default values can depend on previous record):
            if ((($GLOBALS['TCA'][$table]['ctrl']['sortby'] ?? false) || ($GLOBALS['TCA'][$table]['ctrl']['useColumnsForDefaultValues'] ?? false)) && $permsEdit) {
                $neededPermission = $table === 'pages' ? Permission::PAGE_NEW : Permission::CONTENT_EDIT;
                if ($this->calcPerms->isGranted($neededPermission)) {
                    if ($isL10nOverlay || $isDeletePlaceHolder) {
                        $this->addActionToCellGroup($cells, $this->spaceIcon, 'new');
                    } elseif ($this->showNewRecLink($table)) {
                        $params = [
                            'edit' => [
                                $table => [
                                    (0-(($row['_MOVE_PLH'] ?? 0) ? $row['_MOVE_PLH_uid'] : $row['uid'])) => 'new',
                                ],
                            ],
                            'returnUrl' => $this->listURL(),
                        ];
                        $icon = ($table === 'pages' ? $this->iconFactory->getIcon('actions-page-new', Icon::SIZE_SMALL) : $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL));
                        $titleLabel = 'new';
                        if ($GLOBALS['TCA'][$table]['ctrl']['sortby'] ?? false) {
                            $titleLabel .= ($table === 'pages' ? 'Page' : 'Record');
                        }
                        $newLink = $this->uriBuilder->buildUriFromRoute('record_edit', $params);
                        $newAction = '<a class="btn btn-default" href="' . htmlspecialchars($newLink) . '" title="' . htmlspecialchars($this->getLanguageService()->getLL($titleLabel)) . '">'
                            . $icon->render() . '</a>';
                        $this->addActionToCellGroup($cells, $newAction, 'new');
                    }
                }
            }

            // "Hide/Unhide" links:
            $hiddenField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'] ?? null;
            if ($hiddenField !== null
                && !empty($GLOBALS['TCA'][$table]['columns'][$hiddenField])
                && (empty($GLOBALS['TCA'][$table]['columns'][$hiddenField]['exclude']) || $backendUser->check('non_exclude_fields', $table . ':' . $hiddenField))
            ) {
                if (!$permsEdit || $isDeletePlaceHolder || $this->isRecordCurrentBackendUser($table, $row)) {
                    $hideAction = $this->spaceIcon;
                } else {
                    $hideTitle = htmlspecialchars($this->getLanguageService()->getLL('hide' . ($table === 'pages' ? 'Page' : '')));
                    $unhideTitle = htmlspecialchars($this->getLanguageService()->getLL('unHide' . ($table === 'pages' ? 'Page' : '')));
                    if ($row[$hiddenField]) {
                        $params = 'data[' . $table . '][' . $rowUid . '][' . $hiddenField . ']=0';
                        $hideAction = '<button type="button"'
                                        . ' class="btn btn-default t3js-record-hide"'
                                        . ' data-state="hidden"'
                                        . ' data-params="' . htmlspecialchars($params) . '"'
                                        . ' data-toggle-title="' . $hideTitle . '"'
                                        . ' title="' . $unhideTitle . '">'
                                        . $this->iconFactory->getIcon('actions-edit-unhide', Icon::SIZE_SMALL)->render()
                                        . '</button>';
                    } else {
                        $params = 'data[' . $table . '][' . $rowUid . '][' . $hiddenField . ']=1';
                        $hideAction = '<button type="button"'
                                        . ' class="btn btn-default t3js-record-hide"'
                                        . ' data-state="visible"'
                                        . ' data-params="' . htmlspecialchars($params) . '"'
                                        . ' data-toggle-title="' . $unhideTitle . '"'
                                        . ' title="' . $hideTitle . '">'
                                        . $this->iconFactory->getIcon('actions-edit-hide', Icon::SIZE_SMALL)->render()
                                        . '</button>';
                    }
                }
                $this->addActionToCellGroup($cells, $hideAction, 'hide');
            }

            // "Up/Down" links
            if ($permsEdit && ($GLOBALS['TCA'][$table]['ctrl']['sortby'] ?? false) && !$this->sortField && !$this->searchLevels) {
                if (!$isL10nOverlay && !$isDeletePlaceHolder && isset($this->currentTable['prev'][$row['uid']])) {
                    // Up
                    $params = [];
                    $params['redirect'] = $this->listURL();
                    $params['cmd'][$table][$row['uid']]['move'] = $this->currentTable['prev'][$row['uid']];
                    $url = (string)$this->uriBuilder->buildUriFromRoute('tce_db', $params);
                    $moveUpAction = '<a class="btn btn-default" href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('moveUp')) . '">'
                        . $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render() . '</a>';
                } else {
                    $moveUpAction = $this->spaceIcon;
                }
                $this->addActionToCellGroup($cells, $moveUpAction, 'moveUp');

                if (!$isL10nOverlay && !$isDeletePlaceHolder && !empty($this->currentTable['next'][$row['uid']])) {
                    // Down
                    $params = [];
                    $params['redirect'] = $this->listURL();
                    $params['cmd'][$table][$row['uid']]['move'] = $this->currentTable['next'][$row['uid']];
                    $url = (string)$this->uriBuilder->buildUriFromRoute('tce_db', $params);
                    $moveDownAction = '<a class="btn btn-default" href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('moveDown')) . '">'
                        . $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render() . '</a>';
                } else {
                    $moveDownAction = $this->spaceIcon;
                }
                $this->addActionToCellGroup($cells, $moveDownAction, 'moveDown');
            }

            // "Delete" link:
            $disableDelete = (bool)\trim($userTsConfig['options.']['disableDelete.'][$table] ?? $userTsConfig['options.']['disableDelete'] ?? false);
            if ($permsEdit
                && !$disableDelete
                && (($table === 'pages' && $localCalcPerms->deletePagePermissionIsGranted()) || ($table !== 'pages' && $this->calcPerms->editContentPermissionIsGranted()))
                && !$this->isRecordCurrentBackendUser($table, $row)
                && !$isDeletePlaceHolder
            ) {
                $actionName = 'delete';
                $refCountMsg = BackendUtility::referenceCount(
                    $table,
                    $row['uid'],
                    ' ' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToRecord'),
                    (string)$this->getReferenceCount($table, $row['uid'])
                ) . BackendUtility::translationCount(
                    $table,
                    $row['uid'],
                    ' ' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.translationsOfRecord')
                );
                $title = BackendUtility::getRecordTitle($table, $row);
                $warningText = $this->getLanguageService()->getLL($actionName . 'Warning') . ' "' . $title . '" [' . $table . ':' . $row['uid'] . ']' . $refCountMsg;
                $params = 'cmd[' . $table . '][' . $row['uid'] . '][delete]=1';
                $icon = $this->iconFactory->getIcon('actions-edit-' . $actionName, Icon::SIZE_SMALL)->render();
                $linkTitle = htmlspecialchars($this->getLanguageService()->getLL($actionName));
                $l10nParentField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? '';
                $deleteAction = '<button type="button" class="btn btn-default t3js-record-delete"'
                                . ' title="' . $linkTitle . '"'
                                . ' aria-label="' . $linkTitle . '"'
                                . ' aria-haspopup="dialog"'
                                . ' data-button-ok-text="' . htmlspecialchars($linkTitle) . '"'
                                . ' data-l10parent="' . ($l10nParentField ? htmlspecialchars($row[$l10nParentField]) : '') . '"'
                                . ' data-params="' . htmlspecialchars($params) . '"'
                                . ' data-message="' . htmlspecialchars($warningText) . '">'
                                . $icon
                                . '</button>';
            } else {
                $deleteAction = $this->spaceIcon;
            }
            $this->addActionToCellGroup($cells, $deleteAction, 'delete');

            // "Levels" links: Moving pages into new levels...
            if ($permsEdit && $table === 'pages' && !$this->searchLevels) {
                // Up (Paste as the page right after the current parent page)
                if ($this->calcPerms->createPagePermissionIsGranted()) {
                    if (!$isDeletePlaceHolder && !$isL10nOverlay) {
                        $params = [];
                        $params['redirect'] = $this->listURL();
                        $params['cmd'][$table][$row['uid']]['move'] = -$this->id;
                        $url = (string)$this->uriBuilder->buildUriFromRoute('tce_db', $params);
                        $moveLeftAction = '<a class="btn btn-default"'
                            . ' href="' . htmlspecialchars($url) . '"'
                            . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('prevLevel')) . '"'
                            . ' aria-label="' . htmlspecialchars($this->getLanguageService()->getLL('prevLevel')) . '">'
                            . $this->iconFactory->getIcon('actions-move-left', Icon::SIZE_SMALL)->render()
                            . '</a>';
                        $this->addActionToCellGroup($cells, $moveLeftAction, 'moveLeft');
                    } else {
                        $this->addActionToCellGroup($cells, $this->spaceIcon, 'moveLeft');
                    }
                }
                // Down (Paste as subpage to the page right above)
                if (!$isL10nOverlay && !$isDeletePlaceHolder && !empty($this->currentTable['prevUid'][$row['uid']])) {
                    $localCalcPerms = $this->getPagePermissionsForRecord(
                        'pages',
                        BackendUtility::getRecord('pages', $this->currentTable['prevUid'][$row['uid']]) ?? []
                    );
                    if ($localCalcPerms->createPagePermissionIsGranted()) {
                        $params = [];
                        $params['redirect'] = $this->listURL();
                        $params['cmd'][$table][$row['uid']]['move'] = $this->currentTable['prevUid'][$row['uid']];
                        $url = (string)$this->uriBuilder->buildUriFromRoute('tce_db', $params);
                        $moveRightAction = '<a class="btn btn-default"'
                            . ' href="' . htmlspecialchars($url) . '"'
                            . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('nextLevel')) . '"'
                            . ' aria-label="' . htmlspecialchars($this->getLanguageService()->getLL('nextLevel')) . '">'
                            . $this->iconFactory->getIcon('actions-move-right', Icon::SIZE_SMALL)->render() . '</a>';
                    } else {
                        $moveRightAction = $this->spaceIcon;
                    }
                } else {
                    $moveRightAction = $this->spaceIcon;
                }
                $this->addActionToCellGroup($cells, $moveRightAction, 'moveRight');
            }
        }

        /*
         * hook: recStatInfoHooks: Allows to insert HTML before record icons on various places
         */
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] ?? [];
        if (!empty($hooks)) {
            $stat = '';
            $_params = [$table, $row['uid']];
            foreach ($hooks as $_funcRef) {
                $stat .= GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
            $this->addActionToCellGroup($cells, $stat, 'stat');
        }

        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
            trigger_error(
                'The hook $TYPO3_CONF_VARS[SC_OPTIONS][typo3/class.db_list_extra.inc][actions] for calling method "makeControl" is deprecated and will stop working in TYPO3 v12.0. Use the ModifyRecordListRecordActionsEvent instead.',
                E_USER_DEPRECATED
            );
        }

        /*
         * hook:  makeControl: Allows to change control icons of records in list-module
         * usage: This hook method gets passed the current $cells array as third parameter.
         *        This array contains values for the icons/actions generated for each record in Web>List.
         *        Each array entry is accessible by an index-key.
         *        The order of the icons is depending on the order of those array entries.
         *
         * @deprecated in v11, will be removed in TYPO3 v12.0.
         */
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] ?? false)) {
            // for compatibility reason, we move all icons to the rootlevel
            // before calling the hooks
            foreach ($cells as $section => $actions) {
                foreach ($actions as $actionKey => $action) {
                    $cells[$actionKey] = $action;
                }
            }
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $className) {
                $hookObject = GeneralUtility::makeInstance($className);
                if (!$hookObject instanceof RecordListHookInterface) {
                    throw new \UnexpectedValueException($className . ' must implement interface ' . RecordListHookInterface::class, 1195567840);
                }
                $cells = $hookObject->makeControl($table, $row, $cells, $this);
            }
            // now sort icons again into primary and secondary sections
            // after all hooks are processed
            $hookCells = $cells;
            foreach ($hookCells as $key => $value) {
                if ($key === 'primary' || $key === 'secondary') {
                    continue;
                }
                $this->addActionToCellGroup($cells, $value, $key);
            }
        }

        // Add clipboard related actions
        $this->makeClip($table, $row, $cells);

        $event = $this->eventDispatcher->dispatch(
            new ModifyRecordListRecordActionsEvent($cells, $table, $row, $this)
        );

        $output = '';
        foreach ($event->getActions() as $classification => $actions) {
            if ($classification !== 'primary') {
                $cellOutput = '';
                foreach ($actions as $action) {
                    if ($action === $this->spaceIcon) {
                        continue;
                    }
                    // This is a backwards-compat layer for the existing hook items, which will be removed in TYPO3 v12.
                    $action = str_replace('btn btn-default', 'dropdown-item', $action);
                    $title = [];
                    preg_match('/title="([^"]*)"/', $action, $title);
                    if (empty($title)) {
                        preg_match('/aria-label="([^"]*)"/', $action, $title);
                    }
                    if (!empty($title[1] ?? '')) {
                        $action = str_replace(
                            [
                                '</a>',
                                '</button>',
                            ],
                            [
                                ' ' . $title[1] . '</a>',
                                ' ' . $title[1] . '</button>',
                            ],
                            $action
                        );
                        // In case we added the title as tag content, we can remove the attribute,
                        // since this is duplicated and would trigger a tooltip with the same content.
                        if (!empty($title[0] ?? '')) {
                            $action = str_replace($title[0], '', $action);
                        }
                    }
                    $cellOutput .= '<li>' . $action . '</li>';
                }

                if ($cellOutput !== '') {
                    $icon = $this->iconFactory->getIcon('actions-menu-alternative', Icon::SIZE_SMALL);
                    $title = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.more');
                    $output .= ' <div class="btn-group dropdown position-static" data-bs-toggle="tooltip" title="' . htmlspecialchars($title) . '">' .
                        '<a href="#actions_' . $table . '_' . $row['uid'] . '" class="btn btn-default dropdown-toggle dropdown-toggle-no-chevron" data-bs-toggle="dropdown" data-bs-boundary="window" aria-expanded="false">' . $icon->render() . '</a>' .
                        '<ul id="actions_' . $table . '_' . $row['uid'] . '" class="dropdown-menu dropdown-list">' . $cellOutput . '</ul>' .
                        '</div>';
                } else {
                    $output .= ' <div class="btn-group">' . $this->spaceIcon . '</div>';
                }
            } else {
                $output .= ' <div class="btn-group">' . implode('', $actions) . '</div>';
            }
        }

        return $output;
    }

    /**
     * Creates the clipboard actions for a single record in the listing.
     *
     * @param string $table The table
     * @param array $row The record for which to create the clipboard actions
     * @param array $cells The already defined cells from makeControl
     */
    public function makeClip(string $table, array $row, array &$cells): void
    {
        // Return, if disabled:
        if (!$this->isClipboardFunctionalityEnabled($table, $row)) {
            return;
        }
        $clipboardCells = [];
        $isEditable = $this->isEditable($table);

        if ($this->clipObj->current !== 'normal') {
            $clipboardCells['copy'] = $clipboardCells['cut'] = $this->spaceIcon;
        } else {
            $this->addDividerToCellGroup($cells);
            $isSel = $this->clipObj->isSelected($table, $row['uid']);

            $copyTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.' . ($isSel === 'copy' ? 'copyrelease' : 'copy'));
            $copyUrl = $this->clipObj->selUrlDB($table, (int)$row['uid'], true, $isSel === 'copy');
            $clipboardCells['copy'] = '
                <a class="btn btn-default" href="' . htmlspecialchars($copyUrl) . '" title="' . htmlspecialchars($copyTitle) . '" aria-label="' . htmlspecialchars($copyTitle) . '">
                    ' . $this->iconFactory->getIcon($isSel === 'copy' ? 'actions-edit-copy-release' : 'actions-edit-copy', Icon::SIZE_SMALL)->render() . '
                </a>';

            // Calculate permission to cut page or content
            if ($table === 'pages') {
                $localCalcPerms = $this->getPagePermissionsForRecord('pages', $row);
                $permsEdit = $localCalcPerms->editPagePermissionIsGranted();
            } else {
                $permsEdit = $this->calcPerms->editContentPermissionIsGranted() && $this->getBackendUserAuthentication()->recordEditAccessInternals($table, $row);
            }
            if (!$isEditable || !$this->overlayEditLockPermissions($table, $row, $permsEdit)) {
                $clipboardCells['cut'] = $this->spaceIcon;
            } else {
                $cutTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.' . ($isSel === 'cut' ? 'cutrelease' : 'cut'));
                $cutUrl = $this->clipObj->selUrlDB($table, (int)$row['uid'], false, $isSel === 'cut');
                $clipboardCells['cut'] = '
                    <a class="btn btn-default" href="' . htmlspecialchars($cutUrl) . '" title="' . htmlspecialchars($cutTitle) . '" aria-label="' . htmlspecialchars($cutTitle) . '">
                        ' . $this->iconFactory->getIcon($isSel === 'cut' ? 'actions-edit-cut-release' : 'actions-edit-cut', Icon::SIZE_SMALL)->render() . '
                    </a>';
            }
        }

        // Now, looking for selected elements from the current table:
        $elFromTable = $this->clipObj->elFromTable($table);
        if (!$isEditable
            || empty($GLOBALS['TCA'][$table]['ctrl']['sortby'])
            || $this->clipObj->elFromTable($table) === []
            || !$this->overlayEditLockPermissions($table, $row)
        ) {
            $clipboardCells['pasteAfter'] = $this->spaceIcon;
        } else {
            $this->addDividerToCellGroup($cells);
            $pasteAfterUrl = $this->clipObj->pasteUrl($table, -$row['uid']);
            $pasteAfterTitle = $this->getLanguageService()->getLL('clip_pasteAfter');
            $pasteAfterContent = $this->clipObj->confirmMsgText($table, $row, 'after', $elFromTable);
            $clipboardCells['pasteAfter'] = '
                <button type="button" class="btn btn-default t3js-modal-trigger" data-severity="warning" aria-haspopup="dialog" title="' . htmlspecialchars($pasteAfterTitle) . '" aria-label="' . htmlspecialchars($pasteAfterTitle) . '" data-uri="' . htmlspecialchars($pasteAfterUrl) . '" data-bs-content="' . htmlspecialchars($pasteAfterContent) . '">
                    ' . $this->iconFactory->getIcon('actions-document-paste-after', Icon::SIZE_SMALL)->render() . '
                </button>';
        }

        // Now, looking for elements in general:
        if ($table !== 'pages' || !$isEditable || $this->clipObj->elFromTable() === []) {
            $clipboardCells['pasteInto'] = $this->spaceIcon;
        } else {
            $this->addDividerToCellGroup($cells);
            $pasteIntoUrl = $this->clipObj->pasteUrl('', $row['uid']);
            $pasteIntoTitle = $this->getLanguageService()->getLL('clip_pasteInto');
            $pasteIntoContent = $this->clipObj->confirmMsgText($table, $row, 'into', $elFromTable);
            $clipboardCells['pasteInto'] = '
                <button type="button" class="btn btn-default t3js-modal-trigger" aria-haspopup="dialog" data-severity="warning" title="' . htmlspecialchars($pasteIntoTitle) . '" aria-label="' . htmlspecialchars($pasteIntoTitle) . '" data-uri="' . htmlspecialchars($pasteIntoUrl) . '" data-bs-content="' . htmlspecialchars($pasteIntoContent) . '">
                    ' . $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)->render() . '
                </button>';
        }

        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
            trigger_error(
                'The hook $TYPO3_CONF_VARS[SC_OPTIONS][typo3/class.db_list_extra.inc][actions] for calling method "makeClip" is deprecated and will stop working in TYPO3 v12.0. Use the ModifyRecordListRecordActionsEvent instead.',
                E_USER_DEPRECATED
            );
        }

        /*
         * hook:  makeClip: Allows to change clip-icons of records in list-module
         * usage: This hook method gets passed the current $cells array as third parameter.
         *        This array contains values for the clipboard icons generated for each record in Web>List.
         *        Each array entry is accessible by an index-key.
         *        The order of the icons is depending on the order of those array entries.
         *
         * @deprecated in v11, will be removed in TYPO3 v12.0.
         */
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] ?? false)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] as $className) {
                $hookObject = GeneralUtility::makeInstance($className);
                if (!$hookObject instanceof RecordListHookInterface) {
                    throw new \UnexpectedValueException($className . ' must implement interface ' . RecordListHookInterface::class, 1195567845);
                }
                $clipboardCells = $hookObject->makeClip($table, $row, $clipboardCells, $this);
            }
        }

        // Add the clipboard actions to the cell group
        foreach ($clipboardCells as $key => $value) {
            if ($key === 'primary' || $key === 'secondary') {
                continue;
            }
            $this->addActionToCellGroup($cells, $value, $key);
        }
    }

    /**
     * Adds the checkbox to select a single record in the listing
     *
     * @param string $table The table
     * @param array $row The record for which to make the checkbox
     * @return string The checkbox for the record
     */
    public function makeCheckbox(string $table, array $row): string
    {
        // Early return if current record is a "delete placeholder" or a translation
        if ($this->isRecordDeletePlaceholder($row)
            || (int)($row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? null] ?? 0) !== 0
        ) {
            return '';
        }

        // In case clipObj is not set, just add a checkbox without any clipboard functionality
        if ($this->clipObj === null) {
            return '
                <span class="form-check form-toggle">
                    <input class="form-check-input t3js-multi-record-selection-check" type="checkbox" />
                </span>';
        }

        // For the numeric clipboard pads (showing checkboxes where one can select elements on/off)
        // Setting name of the element in ->CBnames array:
        $identifier = $table . '|' . $row['uid'];
        $this->CBnames[] = $identifier;
        $isSelected = false;
        // If the "duplicateField" value is set then select all elements which are duplicates...
        if ($this->duplicateField && isset($row[$this->duplicateField])) {
            $isSelected = in_array((string)$row[$this->duplicateField], $this->duplicateStack, true);
            $this->duplicateStack[] = (string)$row[$this->duplicateField];
        }
        // Adding the checkbox to the panel:
        return '
            <span class="form-check form-toggle">
                <input class="form-check-input t3js-multi-record-selection-check" type="checkbox" name="CBC[' . $identifier . ']" value="1" ' . ($isSelected ? 'checked="checked" ' : '') . '/>
            </span>';
    }

    /**
     * Creates the localization panel
     *
     * @param string $table The table
     * @param mixed[] $row The record for which to make the localization panel.
     * @param array $translations
     * @return string
     */
    public function makeLocalizationPanel($table, $row, array $translations): string
    {
        $out = '';
        // All records excluding pages
        $possibleTranslations = $this->possibleTranslations;
        if ($table === 'pages') {
            // Calculate possible translations for pages
            $possibleTranslations = array_map(static fn ($siteLanguage) => $siteLanguage->getLanguageId(), $this->languagesAllowedForUser);
            $possibleTranslations = array_filter($possibleTranslations, static fn ($languageUid) => $languageUid > 0);
        }

        // Traverse page translations and add icon for each language that does NOT yet exist and is included in site configuration:
        $pageId = (int)($table === 'pages' ? $row['uid'] : $row['pid']);
        $languageInformation = $this->translateTools->getSystemLanguages($pageId);

        foreach ($possibleTranslations as $lUid_OnPage) {
            if ($this->isEditable($table)
                && !$this->isRecordDeletePlaceholder($row)
                && !isset($translations[$lUid_OnPage])
                && $this->getBackendUserAuthentication()->checkLanguageAccess($lUid_OnPage)
            ) {
                $redirectUrl = (string)$this->uriBuilder->buildUriFromRoute(
                    'record_edit',
                    [
                            'justLocalized' => $table . ':' . $row['uid'] . ':' . $lUid_OnPage,
                            'returnUrl' => $this->listURL(),
                        ]
                );
                $params = [];
                $params['redirect'] = $redirectUrl;
                $params['cmd'][$table][$row['uid']]['localize'] = $lUid_OnPage;
                $href = (string)$this->uriBuilder->buildUriFromRoute('tce_db', $params);
                $title = htmlspecialchars($languageInformation[$lUid_OnPage]['title'] ?? '');

                $lC = ($languageInformation[$lUid_OnPage]['flagIcon'] ?? false)
                    ? $this->iconFactory->getIcon($languageInformation[$lUid_OnPage]['flagIcon'], Icon::SIZE_SMALL)->render()
                    : $title;

                $out .= '<a href="' . htmlspecialchars($href) . '"'
                    . '" class="btn btn-default t3js-action-localize"'
                    . ' title="' . $title . '">'
                    . $lC . '</a> ';
            }
        }
        return $out;
    }

    /**
     * Creates a button, which triggers a modal for the column selection
     *
     * @param string $table Table name
     * @return string HTML content with the button
     */
    protected function columnSelector(string $table): string
    {
        if ($this->displayColumnSelector === false) {
            // Early return in case column selector is disabled
            return '';
        }

        $shouldRenderSelector = true;
        // See if it is disabled in general
        if (isset($this->modTSconfig['displayColumnSelector'])) {
            $shouldRenderSelector = (bool)$this->modTSconfig['displayColumnSelector'];
        }
        // Table override was explicitly set to false
        if (isset($this->modTSconfig['table.'][$table . '.']['displayColumnSelector'])) {
            $shouldRenderSelector = (bool)$this->modTSconfig['table.'][$table . '.']['displayColumnSelector'];
        }
        // Do not render button if column selector is disabled
        if ($shouldRenderSelector === false) {
            return '';
        }

        $lang = $this->getLanguageService();
        $tableIdentifier = $table . (($table === 'pages' && $this->showOnlyTranslatedRecords) ? '_translated' : '');
        $columnSelectorUrl = $this->uriBuilder->buildUriFromRoute(
            'ajax_show_columns_selector',
            ['id' => $this->id, 'table' => $table]
        );
        $columnSelectorTitle = sprintf(
            $lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_column_selector.xlf:showColumnsSelection'),
            $lang->sL($GLOBALS['TCA'][$table]['ctrl']['title'] ?? '') ?: $table,
        );

        return '
            <div class="pull-right me-2 p-0">
                <typo3-backend-column-selector-button
                    url="' . htmlspecialchars($columnSelectorUrl) . '"
                    target="' . htmlspecialchars($this->listURL() . '#t3-table-' . $tableIdentifier) . '"
                    title="' . htmlspecialchars($columnSelectorTitle) . '"
                    ok="' . htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_column_selector.xlf:updateColumnView')) . '"
                    close="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cancel')) . '"
                    error="' . htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_column_selector.xlf:updateColumnView.error')) . '"
                >
                    <button type="button" class="btn btn-default btn-sm" title="' . htmlspecialchars($columnSelectorTitle) . '">' .
                        $this->iconFactory->getIcon('actions-options', Icon::SIZE_SMALL) . ' ' .
                        htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_column_selector.xlf:showColumns')) .
                    '</button>
                </typo3-backend-column-selector-button>
            </div>';
    }

    /*********************************
     *
     * Helper functions
     *
     *********************************/

    /**
     * Creates a sort-by link on the input string ($code).
     * It will automatically detect if sorting should be ascending or descending depending on $this->sortRev.
     * Also some fields will not be possible to sort (including if single-table-view is disabled).
     *
     * @param string $code The string to link (text)
     * @param string $field The fieldname represented by the title ($code)
     * @param string $table Table name
     * @return string Linked $code variable
     */
    public function addSortLink($code, $field, $table)
    {
        // Certain circumstances just return string right away (no links):
        if ($this->disableSingleTableView
            || in_array($field, ['_SELECTOR', '_CONTROL_', '_LOCALIZATION_', '_REF_'], true)
        ) {
            return $code;
        }
        // If "_PATH_" (showing record path) is selected, force sorting by pid field (will at least group the records!)
        if ($field === '_PATH_') {
            $field = 'pid';
        }
        //	 Create the sort link:
        $sortUrl = $this->listURL('', $table, 'sortField,sortRev,table,pointer')
            . '&sortField=' . $field . '&sortRev=' . ($this->sortRev || $this->sortField != $field ? 0 : 1);
        $sortArrow = $this->sortField === $field
            ? $this->iconFactory->getIcon('status-status-sorting-' . ($this->sortRev ? 'desc' : 'asc'), Icon::SIZE_SMALL)->render()
            : '';
        // Return linked field:
        return '<a href="' . htmlspecialchars($sortUrl) . '">' . $code . $sortArrow . '</a>';
    }

    /**
     * Returns the path for a certain pid
     * The result is cached internally for the session, thus you can call
     * this function as much as you like without performance problems.
     *
     * @param int $pid The page id for which to get the path
     * @return mixed[] The path.
     */
    public function recPath($pid)
    {
        if (!isset($this->recPath_cache[$pid])) {
            $this->recPath_cache[$pid] = BackendUtility::getRecordPath($pid, $this->perms_clause, 20);
        }
        return $this->recPath_cache[$pid];
    }

    /**
     * Helper method around fetching the permissions of a record, by incorporating the record information AND the
     * current user information.
     */
    protected function getPagePermissionsForRecord(string $table, array $row): Permission
    {
        // If the listed table is 'pages' we have to request the permission settings for each page.
        // If the listed table is not 'pages' we have to request the permission settings from the parent page
        $pageId = (int)($table === 'pages' ? ($row['l10n_parent'] ?: $row['uid']) : $row['pid']);
        if (!isset($this->pagePermsCache[$pageId])) {
            $this->pagePermsCache[$pageId] = new Permission($this->getBackendUserAuthentication()->calcPerms(BackendUtility::getRecord('pages', $pageId)));
        }
        return $this->pagePermsCache[$pageId];
    }

    /**
     * Helper method around fetching a "cruser_id" information for a record, with a cache, so the same information
     * does not have to be processed for the same user over and over again.
     */
    protected function getBackendUserInformation(int $backendUserId): string
    {
        if (!isset($this->backendUserCache[$backendUserId])) {
            $beUserRecord = BackendUtility::getRecord('be_users', $backendUserId);
            if (is_array($beUserRecord)) {
                $avatar = GeneralUtility::makeInstance(Avatar::class);
                $label = htmlspecialchars(BackendUtility::getRecordTitle('be_users', $beUserRecord));
                $content = $avatar->render($beUserRecord) . '<strong>' . $label . '</strong>';
            } else {
                $content = '<strong>&ndash;</strong>';
            }
            $this->backendUserCache[$backendUserId] = $content;
        }
        return $this->backendUserCache[$backendUserId];
    }

    /**
     * Returns TRUE if a link for creating new records should be displayed for $table
     *
     * @param string $table Table name
     * @return bool Returns TRUE if a link for creating new records should be displayed for $table
     */
    public function showNewRecLink($table)
    {
        // No deny/allow tables are set:
        if (empty($this->allowedNewTables) && empty($this->deniedNewTables)) {
            return true;
        }
        return !in_array($table, $this->deniedNewTables)
            && (empty($this->allowedNewTables) || in_array($table, $this->allowedNewTables));
    }

    /**
     * add action into correct section
     *
     * @param array $cells
     * @param string $action
     * @param string $actionKey
     */
    public function addActionToCellGroup(&$cells, $action, $actionKey)
    {
        $cellsMap = [
            'primary' => [
                'edit', 'hide', 'delete', 'moveUp', 'moveDown',
            ],
            'secondary' => [
                'view', 'viewBig', 'history', 'stat', 'perms', 'new', 'move', 'moveLeft', 'moveRight', 'version', 'divider', 'copy', 'cut', 'pasteAfter', 'pasteInto',
            ],
        ];
        $classification = in_array($actionKey, $cellsMap['primary']) ? 'primary' : 'secondary';
        $cells[$classification][$actionKey] = $action;
        unset($cells[$actionKey]);
    }

    /**
     * Check if the record represents the current backend user
     *
     * @param string $table
     * @param array $row
     * @return bool
     */
    protected function isRecordCurrentBackendUser($table, $row)
    {
        return $table === 'be_users' && (int)($row['uid'] ?? 0) === (int)$this->getBackendUserAuthentication()->user['uid'];
    }

    /**
     * Check if user is in workspace and given record is a delete placeholder
     */
    protected function isRecordDeletePlaceholder(array $row): bool
    {
        return $this->getBackendUserAuthentication()->workspace > 0
            && isset($row['t3ver_state'])
            && VersionState::cast($row['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER);
    }

    /**
     * @param bool $isEditable
     */
    public function setIsEditable(bool $isEditable): void
    {
        $this->editable = $isEditable;
    }

    /**
     * Check if the table is readonly or editable
     * @param string $table
     * @return bool
     */
    public function isEditable(string $table): bool
    {
        $backendUser = $this->getBackendUserAuthentication();
        return !($GLOBALS['TCA'][$table]['ctrl']['readOnly'] ?? false)
            && $this->editable
            && ($backendUser->isAdmin() || $backendUser->check('tables_modify', $table));
    }

    /**
     * Check if the current record is locked by editlock. Pages are locked if their editlock flag is set,
     * records are if they are locked themselves or if the page they are on is locked (a page’s editlock
     * is transitive for its content elements).
     *
     * @param string $table
     * @param array $row
     * @param bool $editPermission
     * @return bool
     */
    protected function overlayEditLockPermissions($table, $row = [], $editPermission = true)
    {
        if ($editPermission && !$this->getBackendUserAuthentication()->isAdmin()) {
            // If no $row is submitted we only check for general edit lock of current page (except for table "pages")
            if (empty($row)) {
                return ($table === 'pages') || !($this->pageRow['editlock'] ?? false);
            }
            if (($table === 'pages' && $row['editlock']) || ($table !== 'pages' && $this->pageRow['editlock'])) {
                $editPermission = false;
            } elseif (isset($GLOBALS['TCA'][$table]['ctrl']['editlock']) && $row[$GLOBALS['TCA'][$table]['ctrl']['editlock']]) {
                $editPermission = false;
            }
        }
        return $editPermission;
    }

    /**
     * Set the module data
     *
     * See BackendUtility::getModuleData
     *
     * @param array $moduleData
     */
    public function setModuleData(array $moduleData = []): void
    {
        $this->moduleData = $moduleData;
    }

    /**
     * Initializes the list generation
     *
     * @param int $id Page id for which the list is rendered. Must be >= 0
     * @param string $table Tablename - if extended mode where only one table is listed at a time.
     * @param int $pointer Browsing pointer.
     * @param string $search Search word, if any
     * @param int $levels Number of levels to search down the page tree
     * @param int $showLimit Limit of records to be listed.
     */
    public function start($id, $table, $pointer, $search = '', $levels = 0, $showLimit = 0)
    {
        $backendUser = $this->getBackendUserAuthentication();
        // Setting internal variables:
        // sets the parent id
        $this->id = (int)$id;
        if ($GLOBALS['TCA'][$table] ?? false) {
            // Setting single table mode, if table exists:
            $this->table = $table;
        }
        $this->page = MathUtility::forceIntegerInRange((int)$pointer, 1, 1000);
        $this->showLimit = MathUtility::forceIntegerInRange((int)$showLimit, 0, 10000);
        $this->searchString = trim($search);
        $this->searchLevels = (int)$levels;
        // Setting GPvars:
        $this->sortField = GeneralUtility::_GP('sortField');
        $this->sortRev = GeneralUtility::_GP('sortRev');
        $this->duplicateField = GeneralUtility::_GP('duplicateField');

        // If there is a current link to a record, set the current link uid and get the table name from the link handler configuration
        $currentLinkValue = trim($this->overrideUrlParameters['P']['currentValue'] ?? '');
        if ($currentLinkValue) {
            $linkService = GeneralUtility::makeInstance(LinkService::class);
            try {
                $currentLinkParts = $linkService->resolve($currentLinkValue);
                if ($currentLinkParts['type'] === 'record' && isset($currentLinkParts['identifier'])) {
                    $this->currentLink['tableNames'] = $this->tableList;
                    $this->currentLink['uid'] = (int)$currentLinkParts['uid'];
                }
            } catch (UnknownLinkHandlerException $e) {
            }
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

        $this->possibleTranslations = $this->getPossibleTranslations($this->id);
    }

    /**
     * Traverses the table(s) to be listed and renders the output code for each.
     *
     * @return string Rendered HTML
     */
    public function generateList(): string
    {
        $tableNames = $this->getTablesToRender();
        $output = '';
        foreach ($tableNames as $tableName) {
            $output .= $this->getTable($tableName, $this->id);
        }
        return $output;
    }

    /**
     * Depending on various options returns a list of all TCA tables which should be shown
     * and are allowed by the current user.
     *
     * @return array a list of all TCA tables
     */
    protected function getTablesToRender(): array
    {
        $hideTablesArray = GeneralUtility::trimExplode(',', $this->hideTables);
        $backendUser = $this->getBackendUserAuthentication();

        // pre-process tables and add sorting instructions
        $tableNames = array_flip(array_keys($GLOBALS['TCA']));
        foreach ($tableNames as $tableName => $_) {
            $hideTable = false;

            // Checking if the table should be rendered:
            // Checks that we see only permitted/requested tables:
            if (($this->table && $tableName !== $this->table)
                || ($this->tableList && !GeneralUtility::inList($this->tableList, (string)$tableName))
                || !$backendUser->check('tables_select', $tableName)
            ) {
                $hideTable = true;
            }

            if (!$hideTable) {
                // Don't show table if hidden by TCA ctrl section
                // Don't show table if hidden by pageTSconfig mod.web_list.hideTables
                $hideTable = !empty($GLOBALS['TCA'][$tableName]['ctrl']['hideTable'])
                    || in_array($tableName, $hideTablesArray, true)
                    || in_array('*', $hideTablesArray, true);
                // Override previous selection if table is enabled or hidden by TSconfig TCA override mod.web_list.table
                $hideTable = (bool)($this->tableTSconfigOverTCA[$tableName . '.']['hideTable'] ?? $hideTable);
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
        $orderedTableNames = GeneralUtility::makeInstance(DependencyOrderingService::class)
            ->orderByDependencies($tableNames);
        return array_keys($orderedTableNames);
    }

    /**
     * Fetching the user defined fields to be displayed.
     * Sets the internal variable $this->setFields.
     */
    public function setDispFields(): void
    {
        $this->setFields = $this->getBackendUserAuthentication()->getModuleData('list/displayFields');
    }

    /**
     * Returns a QueryBuilder configured to select $fields from $table where the pid is restricted
     * depending on the current searchlevel setting.
     *
     * @param string $table Table name
     * @param int $pageId Page id Only used to build the search constraints, getPageIdConstraint() used for restrictions
     * @param string[] $additionalConstraints Additional part for where clause
     * @param string[] $fields Field list to select, * for all
     * @param bool $addSorting
     * @param int $firstResult
     * @param int $maxResult
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    public function getQueryBuilder(
        string $table,
        int $pageId,
        array $additionalConstraints,
        array $fields,
        bool $addSorting,
        int $firstResult,
        int $maxResult
    ): QueryBuilder {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->getBackendUserAuthentication()->workspace));
        $queryBuilder
            ->select(...$fields)
            ->from($table);
        if (!empty($additionalConstraints)) {
            $queryBuilder->andWhere(...$additionalConstraints);
        }
        // Legacy hook
        $addWhere = '';
        $selFieldList = implode(',', $fields);
        if ($selFieldList === '*') {
            $selFieldList = '';
        }
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof RecordListGetTableHookInterface) {
                throw new \UnexpectedValueException($className . ' must implement interface ' . RecordListGetTableHookInterface::class, 1195114460);
            }
            $hookObject->getDBlistQuery($table, $pageId, $addWhere, $selFieldList, $this);
        }
        if (!empty($addWhere)) {
            $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($addWhere));
        }
        $fields = GeneralUtility::trimExplode(',', $selFieldList, true);
        if ($fields === []) {
            $fields = ['*'];
        }
        // Additional constraints from getTable
        if (($GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? false)
            && ($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? false)) {
            // Only restrict to the default language if no search request is in place
            // And if only translations should be shown
            if ($this->searchString === '' && !$this->showOnlyTranslatedRecords) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->lte($GLOBALS['TCA'][$table]['ctrl']['languageField'], 0),
                        $queryBuilder->expr()->eq($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'], 0)
                    )
                );
            }
        }
        if ($table === 'pages' && $this->showOnlyTranslatedRecords) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    $GLOBALS['TCA']['pages']['ctrl']['languageField'],
                    array_keys($this->languagesAllowedForUser)
                )
            );
        }
        // Former prepareQueryBuilder
        if ($maxResult > 0) {
            $queryBuilder->setMaxResults($maxResult);
        }
        if ($firstResult > 0) {
            $queryBuilder->setFirstResult($firstResult);
        }
        if ($addSorting) {
            if ($this->sortField && in_array($this->sortField, BackendUtility::getAllowedFieldsForTable($table, false))) {
                $queryBuilder->orderBy($this->sortField, $this->sortRev ? 'DESC' : 'ASC');
            } else {
                $orderBy = ($GLOBALS['TCA'][$table]['ctrl']['sortby'] ?? '') ?: $GLOBALS['TCA'][$table]['ctrl']['default_sortby'] ?? '';
                $orderBys = QueryHelper::parseOrderBy($orderBy);
                foreach ($orderBys as $orderBy) {
                    $queryBuilder->addOrderBy($orderBy[0], $orderBy[1]);
                }
            }
        }

        // Build the query constraints
        $queryBuilder = $this->addPageIdConstraint($table, $queryBuilder, $this->searchLevels);
        $searchWhere = $this->makeSearchString($table, $pageId, $queryBuilder);
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
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'],
                    0
                )
            );
        } elseif (!empty($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']) && $this->showOnlyTranslatedRecords) {
            // When only translated records should be shown, it is necessary to use l10n_parent=pageId, instead of
            // a check to the PID
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter(
                        $this->id,
                        \PDO::PARAM_INT
                    )
                )
            );
        }

        $parameters = [
            'table' => $table,
            'fields' => $fields,
            'groupBy' => null,
            'orderBy' => null,
            'firstResult' => $firstResult,
            'maxResults' => $maxResult,
        ];
        $hookName = DatabaseRecordList::class;
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$hookName]['modifyQuery'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (method_exists($hookObject, 'modifyQuery')) {
                $hookObject->modifyQuery(
                    $parameters,
                    $table,
                    $pageId,
                    $additionalConstraints,
                    $fields,
                    $queryBuilder
                );
            }
        }
        return $queryBuilder;
    }

    /**
     * Creates part of query for searching after a word ($this->searchString)
     * fields in input table.
     *
     * @param string $table Table, in which the fields are being searched.
     * @param int $currentPid Page id for the possible search limit
     * @param QueryBuilder $queryBuilder
     * @return string Returns part of WHERE-clause for searching, if applicable.
     */
    public function makeSearchString($table, int $currentPid, QueryBuilder $queryBuilder)
    {
        $expressionBuilder = $queryBuilder->expr();
        $constraints = [];
        $tablePidField = $table === 'pages' ? 'uid' : 'pid';
        // Make query only if table is valid and a search string is actually defined
        if (empty($this->searchString)) {
            return '';
        }

        $searchableFields = [];
        // Get fields from ctrl section of TCA first
        if (isset($GLOBALS['TCA'][$table]['ctrl']['searchFields'])) {
            $searchableFields = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['searchFields'], true);
        }

        if (MathUtility::canBeInterpretedAsInteger($this->searchString)) {
            $constraints[] = $expressionBuilder->eq('uid', (int)$this->searchString);
            foreach ($searchableFields as $fieldName) {
                if (!isset($GLOBALS['TCA'][$table]['columns'][$fieldName])) {
                    continue;
                }
                $fieldConfig = $GLOBALS['TCA'][$table]['columns'][$fieldName]['config'];
                $fieldType = $fieldConfig['type'];
                $evalRules = ($fieldConfig['eval'] ?? false) ?: '';
                if ($fieldType === 'input' && $evalRules && GeneralUtility::inList($evalRules, 'int')) {
                    if (!isset($fieldConfig['search']['pidonly'])
                        || ($fieldConfig['search']['pidonly'] && $currentPid > 0)
                    ) {
                        $constraints[] = $expressionBuilder->andX(
                            $expressionBuilder->eq($fieldName, (int)$this->searchString),
                            $expressionBuilder->eq($tablePidField, (int)$currentPid)
                        );
                    }
                } elseif ($fieldType === 'text'
                    || $fieldType === 'flex'
                    || $fieldType === 'slug'
                    || ($fieldType === 'input' && (!$evalRules || !preg_match('/\b(?:date|time|int)\b/', $evalRules)))
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
                $evalRules = ($fieldConfig['eval'] ?? false) ?: '';
                $searchConstraint = $expressionBuilder->andX(
                    $expressionBuilder->comparison(
                        'LOWER(' . $queryBuilder->castFieldToTextType($fieldName) . ')',
                        'LIKE',
                        'LOWER(' . $like . ')'
                    )
                );
                if (is_array($fieldConfig['search'] ?? null)) {
                    $searchConfig = $fieldConfig['search'];
                    if ($searchConfig['case'] ?? false) {
                        // Replace case insensitive default constraint
                        $searchConstraint = $expressionBuilder->andX($expressionBuilder->like($fieldName, $like));
                    }
                    if (($searchConfig['pidonly'] ?? false) && $currentPid > 0) {
                        $searchConstraint->add($expressionBuilder->eq($tablePidField, (int)$currentPid));
                    }
                    if ($searchConfig['andWhere'] ?? false) {
                        if (GeneralUtility::makeInstance(Features::class)->isFeatureEnabled('runtimeDbQuotingOfTcaConfiguration')) {
                            $searchConstraint->add(
                                QueryHelper::quoteDatabaseIdentifiers($queryBuilder->getConnection(), QueryHelper::stripLogicalOperatorPrefix($fieldConfig['search']['andWhere']))
                            );
                        } else {
                            $searchConstraint->add(
                                QueryHelper::stripLogicalOperatorPrefix($fieldConfig['search']['andWhere'])
                            );
                        }
                    }
                }
                if ($fieldType === 'text'
                    || $fieldType === 'flex'
                    || $fieldType === 'slug'
                    || ($fieldType === 'input' && (!$evalRules || !preg_match('/\b(?:date|time|int)\b/', $evalRules)))
                ) {
                    if ($searchConstraint->count() !== 0) {
                        $constraints[] = $searchConstraint;
                    }
                }
            }
        }
        // Call hook to add or change the list
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][DatabaseRecordList::class]['makeSearchStringConstraints'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (method_exists($hookObject, 'makeSearchStringConstraints')) {
                $constraints = $hookObject->makeSearchStringConstraints(
                    $queryBuilder,
                    $constraints,
                    $this->searchString,
                    $table,
                    $currentPid
                );
            }
        }
        // If no search field conditions have been built ensure no results are returned
        if (empty($constraints)) {
            return '0=1';
        }

        return $expressionBuilder->orX(...$constraints);
    }

    /**
     * Returns the title (based on $label) of a table ($table) with the proper link around. For headers over tables.
     * The link will cause the display of all extended mode or not for the table.
     *
     * @param string $table Table name
     * @param string $label Table label
     * @return string The linked table label
     */
    public function linkWrapTable(string $table, string $label): string
    {
        if ($this->table !== $table) {
            $url = $this->listURL('', $table, 'pointer');
        } else {
            $url = $this->listURL('', '', 'sortField,sortRev,table,pointer');
        }
        return '<a href="' . htmlspecialchars($url) . '">' . $label . '</a>';
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
            $code = '<i>[' . htmlspecialchars(
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title')
            ) . ']</i> - '
                . htmlspecialchars(BackendUtility::getRecordTitle($table, $row));
        } else {
            $code = htmlspecialchars($code);
        }
        switch ((string)$this->clickTitleMode) {
            case 'edit':
                // If the listed table is 'pages' we have to request the permission settings for each page:
                if ($table === 'pages') {
                    $localCalcPerms = $this->getPagePermissionsForRecord('pages', $row);
                    $permsEdit = $localCalcPerms->editPagePermissionIsGranted();
                } else {
                    $backendUser = $this->getBackendUserAuthentication();
                    $permsEdit = $this->calcPerms->editContentPermissionIsGranted() && $backendUser->recordEditAccessInternals($table, $row);
                }
                // "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
                if ($permsEdit && $this->isEditable($table)) {
                    $params = [
                        'edit' => [
                            $table => [
                                $row['uid'] => 'edit',
                            ],
                        ],
                        'returnUrl' => $this->listURL(),
                    ];
                    $editLink = $this->uriBuilder->buildUriFromRoute('record_edit', $params);
                    $code = '<a href="' . htmlspecialchars($editLink) . '"'
                        . ' title="' . htmlspecialchars($lang->getLL('edit')) . '"'
                        . ' aria-label="' . htmlspecialchars($lang->getLL('edit')) . '">'
                        . $code . '</a>';
                }
                break;
            case 'show':
                // "Show" link (only pages and tt_content elements)
                if ($table === 'pages' || $table === 'tt_content') {
                    $attributes = $this->getPreviewUriBuilder($table, $row)->serializeDispatcherAttributes();
                    $title = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'));
                    $code = '<a href="#" ' . $attributes
                        . ' title="' . $title . '"'
                        . ' aria-label="' . $title . '">'
                        . $code . '</a>';
                }
                break;
            case 'info':
                // "Info": (All records)
                $code = '<a href="#" role="button"' // @todo add handler that triggers click on space key
                    . $this->createShowItemTagAttributes($table . ',' . (int)$row['uid'])
                    . ' title="' . htmlspecialchars($lang->getLL('showInfo')) . '"'
                    . ' aria-label="' . htmlspecialchars($lang->getLL('showInfo')) . '"'
                    . ' aria-haspopup="dialog">'
                    . $code
                    . '</a>';
                break;
            default:
                // Output the label now:
                if ($table === 'pages') {
                    $code = '<a href="' . htmlspecialchars(
                        $this->listURL((string)$uid, '', 'pointer')
                    ) . '>' . $code . '</a>';
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
     * @todo Change signature to (string $code, string $testString): string as breaking change in v12. Also protect method.
     */
    public function linkUrlMail($code, $testString)
    {
        // @todo Remove next two lines after changing signature in v12.
        $code = (string)$code;
        $testString = (string)$testString;

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
     * Fixed GPvars are id, table, returnUrl, search_field, and search_levels
     * The GPvars "sortField" and "sortRev" are also included UNLESS they are found in the $exclList variable.
     *
     * @param string $altId Alternative id value. Enter blank string for the current id ($this->id)
     * @param string $table Table name to display. Enter "-1" for the current table.
     * @param string $exclList Comma separated list of fields NOT to include ("sortField", "sortRev" or "pointer")
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
        if ($this->returnUrl) {
            $urlParameters['returnUrl'] = $this->returnUrl;
        }
        if ((!$exclList || !GeneralUtility::inList($exclList, 'search_field')) && $this->searchString) {
            $urlParameters['search_field'] = $this->searchString;
        }
        if ($this->searchLevels) {
            $urlParameters['search_levels'] = $this->searchLevels;
        }
        if ((!$exclList || !GeneralUtility::inList($exclList, 'pointer')) && $this->page) {
            $urlParameters['pointer'] = $this->page;
        }
        if ((!$exclList || !GeneralUtility::inList($exclList, 'sortField')) && $this->sortField) {
            $urlParameters['sortField'] = $this->sortField;
        }
        if ((!$exclList || !GeneralUtility::inList($exclList, 'sortRev')) && $this->sortRev) {
            $urlParameters['sortRev'] = $this->sortRev;
        }

        return (string)$this->uriBuilder->buildUriFromRoute(
            $GLOBALS['TYPO3_REQUEST']->getAttribute('route')->getOption('_identifier'),
            array_replace($urlParameters, $this->overrideUrlParameters)
        );
    }

    /**
     * Set URL parameters to override or add in the listUrl() method.
     *
     * @param string[] $urlParameters
     */
    public function setOverrideUrlParameters(array $urlParameters)
    {
        $currentUrlParameter = GeneralUtility::_GP('curUrl');
        if (isset($currentUrlParameter['url'])) {
            $urlParameters['P']['currentValue'] = $currentUrlParameter['url'];
        }
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
                    throw new \UnexpectedValueException(
                        'The specified "before" order configuration for table "' . $tableName . '" is invalid.',
                        1504793406
                    );
                }
            }
            if (isset($configuration['after'])) {
                if (is_string($configuration['after'])) {
                    $configuration['after'] = GeneralUtility::trimExplode(',', $configuration['after'], true);
                } elseif (!is_array($configuration['after'])) {
                    throw new \UnexpectedValueException(
                        'The specified "after" order configuration for table "' . $tableName . '" is invalid.',
                        1504793407
                    );
                }
            }
        }
        $this->tableDisplayOrder = $orderInformation;
    }

    /**
     * @return array
     */
    public function getOverridePageIdList(): array
    {
        return $this->overridePageIdList;
    }

    /**
     * @param int[]|array $overridePageIdList
     */
    public function setOverridePageIdList(array $overridePageIdList)
    {
        $this->overridePageIdList = array_map('intval', $overridePageIdList);
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
        $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
        $hash = 'webmounts_list' . md5($id . '-' . $depth . '-' . $perms_clause);
        $idList = $runtimeCache->get($hash);
        if ($idList === false) {
            $backendUser = $this->getBackendUserAuthentication();
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
                    $tree->getTree($allowedMount, $depth);
                }
                $idList = array_merge($idList, $tree->ids);
            }
            $runtimeCache->set($hash, $idList);
        }

        return $idList;
    }

    /**
     * Add conditions to the QueryBuilder object ($queryBuilder) to limit a
     * query to a list of page IDs based on the current search level setting.
     *
     * @param string $tableName
     * @param QueryBuilder $queryBuilder
     * @param int $searchLevels
     * @return QueryBuilder Modified QueryBuilder object
     */
    protected function addPageIdConstraint(string $tableName, QueryBuilder $queryBuilder, int $searchLevels): QueryBuilder
    {
        // Set search levels to 999 instead of -1 as the following methods
        // do not support -1 as valid value for infinite search.
        if ($searchLevels === -1) {
            $searchLevels = 999;
        }

        // When querying translated pages, the PID of the translated pages should be the same as the
        // the PID of the current page
        if ($tableName === 'pages' && $this->showOnlyTranslatedRecords) {
            $pageRecord = BackendUtility::getRecordWSOL('pages', $this->id);
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    $tableName . '.pid',
                    $queryBuilder->createNamedParameter($pageRecord['pid'] ?? 0, \PDO::PARAM_INT)
                )
            );
        } elseif ($searchLevels === 0) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    $tableName . '.pid',
                    $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)
                )
            );
        } elseif ($searchLevels > 0) {
            $allowedMounts = $this->getSearchableWebmounts($this->id, $searchLevels, $this->perms_clause);
            $queryBuilder->andWhere(
                $queryBuilder->expr()->in(
                    $tableName . '.pid',
                    $queryBuilder->createNamedParameter($allowedMounts, Connection::PARAM_INT_ARRAY)
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
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns a table-row with the content from the fields in the input data array.
     * OBS: $this->fieldArray MUST be set! (represents the list of fields to display)
     *
     * @param array $data Is the data array, record with the fields. Notice: These fields are (currently) NOT htmlspecialchar'ed before being wrapped in <td>-tags
     * @param string $rowParams Is insert in the <tr>-tags. Must carry a ' ' as first character
     * @param string $colType Defines the tag being used for the columns. Default is td.
     *
     * @return string HTML content for the table row
     */
    protected function addElement($data, $rowParams = '', $colType = 'td')
    {
        $colType = ($colType === 'th') ? 'th' : 'td';
        $dataUid = ($colType === 'td') ? ($data['uid'] ?? 0) : 0;
        $l10nParent = $data['_l10nparent_'] ?? 0;
        $out = '<tr ' . $rowParams . ' data-uid="' . $dataUid . '" data-l10nparent="' . $l10nParent . '">';

        // Init rendering.
        $colsp = '';
        $lastKey = '';
        $c = 0;
        // __label is used as the label key to circumvent problems with uid used as label (see #67756)
        // as it was introduced later on, check if it really exists before using it
        $fields = $this->fieldArray;
        if ($colType === 'td' && isset($data['__label'])) {
            // The title label column does always follow the icon column. Since
            // in some cases the first column - "_SELECTOR_" - might not be rendered,
            // we always have to calculate the key by searching for the icon column.
            $titleLabelKey = (int)(array_search('icon', $fields, true)) + 1;
            $fields[$titleLabelKey] = '__label';
        }
        // Traverse field array which contains the data to present:
        foreach ($fields as $vKey) {
            if (isset($data[$vKey])) {
                if ($lastKey) {
                    $cssClass = $this->addElement_tdCssClass[$lastKey] ?? '';
                    $out .= '
						<' . $colType . ' class="' . $cssClass . ' nowrap' . '"' . $colsp . '>' . $data[$lastKey] . '</' . $colType . '>';
                }
                $lastKey = $vKey;
                $c = 1;
            } else {
                if (!$lastKey) {
                    $lastKey = $vKey;
                }
                $c++;
            }
            if ($c > 1) {
                $colsp = ' colspan="' . $c . '"';
            } else {
                $colsp = '';
            }
        }
        if ($lastKey) {
            $cssClass = $this->addElement_tdCssClass[$lastKey] ?? '';
            $out .= '
				<' . $colType . ' class="' . $cssClass . ' nowrap' . '"' . $colsp . '>' . $data[$lastKey] . '</' . $colType . '>';
        }
        // End row
        $out .= '
		</tr>';
        return $out;
    }

    /**
     * Fetches all possible translations for the given page
     *
     * This depends on the site config and the current translations of the page record
     * It is used to set the possible translations for all records excluding pages
     *
     * @param int $pageUid
     * @return int[]
     */
    protected function getPossibleTranslations(int $pageUid): array
    {
        // Store languages that are included in the site configuration for the current page.
        $availableSystemLanguageUids = array_keys($this->translateTools->getSystemLanguages($pageUid));
        if ($availableSystemLanguageUids === []) {
            return [];
        }
        // Look up page overlays:
        $localizationParentField = $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'] ?? '';
        $languageField = $GLOBALS['TCA']['pages']['ctrl']['languageField'] ?? '';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->getBackendUserAuthentication()->workspace));
        $result = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq($localizationParentField, $queryBuilder->createNamedParameter($pageUid, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->in($languageField, $queryBuilder->createNamedParameter($availableSystemLanguageUids, Connection::PARAM_INT_ARRAY)),
                    $queryBuilder->expr()->gt(
                        $languageField,
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                )
            )
            ->executeQuery();
        $allowedTranslationsOnPage = [];
        while ($row = $result->fetchAssociative()) {
            $allowedTranslationsOnPage[] = (int)$row[$languageField];
        }
        return $allowedTranslationsOnPage;
    }

    /**
     * Return the icon for the language
     *
     * @param string $table
     * @param array $row
     * @return string Language icon
     */
    protected function languageFlag(string $table, array $row): string
    {
        $pageId = (int)($table === 'pages' ? ($row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] ?: $row['uid']) : $row['pid']);
        $languageUid = (int)($row[$GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? null] ?? 0);
        $languageInformation = $this->translateTools->getSystemLanguages($pageId);
        $title = htmlspecialchars($languageInformation[$languageUid]['title'] ?? '');
        $indent = ($table !== 'pages' && $this->isLocalized($table, $row)) ? ' style="margin-left: 16px;"' : '';
        if ($languageInformation[$languageUid]['flagIcon'] ?? false) {
            return '<span title="' . $title . '"' . $indent . '>' . $this->iconFactory->getIcon(
                $languageInformation[$languageUid]['flagIcon'],
                Icon::SIZE_SMALL
            )->render() . '</span>&nbsp;' . $title;
        }
        return $title;
    }

    /**
     * Generates HTML code for a Reference tooltip out of
     * sys_refindex records you hand over
     */
    protected function generateReferenceToolTip(string $table, int $uid): string
    {
        $numberOfReferences = $this->getReferenceCount($table, $uid);
        if (!$numberOfReferences) {
            $htmlCode = '<button type="button" class="btn btn-default" disabled><span style="display:inline-block;min-width:16px">-</span></button>';
        } else {
            $showReferences = $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:show_references');
            $htmlCode = '<button type="button"'
                . ' class="btn btn-default"'
                . ' aria-haspopup="dialog"'
                . ' ' . $this->createShowItemTagAttributes($table . ',' . $uid)
                . ' title="' . htmlspecialchars($showReferences) . ' (' . $numberOfReferences . ')' . '">'
                . '<span style="display:inline-block;min-width:16px">'
                . $numberOfReferences
                . '<span class="sr-only">' . $showReferences . '</span>'
                . '</span>'
                . '</button>';
        }
        return $htmlCode;
    }

    /**
     * Render convenience actions, such as "check all"
     *
     * @return string HTML markup for the checkbox actions
     */
    protected function renderCheckboxActions(): string
    {
        $lang = $this->getLanguageService();

        $dropdownItems['checkAll'] = '
            <li>
                <button type="button" class="btn btn-link dropdown-item disabled" data-multi-record-selection-check-action="check-all" title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.checkAll')) . '">
                    ' . $this->iconFactory->getIcon('actions-check-square', Icon::SIZE_SMALL)->render() . '
                    ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.checkAll')) . '
                </button>
            </li>';

        $dropdownItems['checkNone'] = '
            <li>
                <button type="button" class="btn btn-link dropdown-item disabled" data-multi-record-selection-check-action="check-none" title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.uncheckAll')) . '">
                    ' . $this->iconFactory->getIcon('actions-square', Icon::SIZE_SMALL)->render() . '
                    ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.uncheckAll')) . '
                </button>
            </li>';

        $dropdownItems['toggleSelection'] = '
            <li>
                <button type="button" class="btn btn-link dropdown-item disabled" data-multi-record-selection-check-action="toggle" title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.toggleSelection')) . '">
                    ' . $this->iconFactory->getIcon('actions-document-select', Icon::SIZE_SMALL)->render() . '
                    ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.toggleSelection')) . '
                </button>
            </li>';

        return '
            <div class="btn-group dropdown position-static">
                <button type="button" class="btn btn-borderless dropdown-toggle t3js-multi-record-selection-check-actions-toggle" data-bs-toggle="dropdown" data-bs-boundary="window" aria-expanded="false">
                    ' . $this->iconFactory->getIcon('content-special-div', Icon::SIZE_SMALL) . '
                </button>
                <ul class="dropdown-menu t3js-multi-record-selection-check-actions">
                    ' . implode(PHP_EOL, $dropdownItems) . '
                </ul>
            </div>';
    }

    /**
     * Render the multi record selection actions, which are shown as soon as one record is selected
     *
     * @param string $table
     * @param array $currentIdList
     * @return string
     */
    protected function renderMultiRecordSelectionActions(string $table, array $currentIdList): string
    {
        $actions = [];
        $lang = $this->getLanguageService();
        $userTsConfig = $this->getBackendUserAuthentication()->getTSConfig();
        $addClipboardActions = $this->showClipboardActions && $this->isClipboardFunctionalityEnabled($table);
        $editPermission = (
            ($table === 'pages') ? $this->calcPerms->editPagePermissionIsGranted() : $this->calcPerms->editContentPermissionIsGranted()
        ) && $this->overlayEditLockPermissions($table);

        // Add actions in case table can be modified by the current user
        if ($editPermission && $this->isEditable($table)) {
            $editActionConfiguration = GeneralUtility::jsonEncodeForHtmlAttribute([
                'idField' => 'uid',
                'tableName' => $table,
                'returnUrl' =>  $this->listURL(),
            ], true);
            $actions['edit'] = '
                <button type="button" class="btn btn-default btn-sm" data-multi-record-selection-action="edit" data-multi-record-selection-action-config="' . $editActionConfiguration . '">
                    <span title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.edit')) . '">
                        ' . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . ' ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.edit')) . '
                    </span>
                </button>';

            if (!(bool)trim(($userTsConfig['options.']['disableDelete.'][$table] ?? $userTsConfig['options.']['disableDelete'] ?? false))) {
                $deleteActionConfiguration = GeneralUtility::jsonEncodeForHtmlAttribute([
                    'idField' => 'uid',
                    'ok' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete'),
                    'title' => $lang->getLL('clip_deleteMarked'),
                    'content' => sprintf($lang->getLL('clip_deleteMarkedWarning'), $lang->sL($GLOBALS['TCA'][$table]['ctrl']['title'])),
                ], true);
                $actions['delete'] = '
                    <button type="button" class="btn btn-default btn-sm" data-multi-record-selection-action="delete" data-multi-record-selection-action-config="' . $deleteActionConfiguration . '" aria-haspopup="dialog">
                        <span title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete')) . '">
                            ' . $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render() . ' ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete')) . '
                        </span>
                    </button>';
            }
        }

        // Add clipboard actions in case they  are enabled and clipboard is not deactivated
        if ($addClipboardActions && (string)($this->modTSconfig['enableClipBoard'] ?? '') !== 'deactivated') {
            $copyMarked = '
                <button type="button" class="btn btn-default btn-sm ' . ($this->clipObj->current === 'normal' ? 'disabled' : '') . '" data-multi-record-selection-action="copyMarked">
                    <span title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.transferToClipboard')) . '">
                        ' . $this->iconFactory->getIcon('actions-edit-copy', Icon::SIZE_SMALL)->render() . ' ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.transferToClipboard')) . '
                    </span>
                </button>';
            $removeMarked = '
                <button type="button" class="btn btn-default btn-sm ' . ($this->clipObj->current === 'normal' ? 'disabled' : '') . '" data-multi-record-selection-action="removeMarked">
                    <span title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.removeFromClipboard')) . '">
                        ' . $this->iconFactory->getIcon('actions-remove', Icon::SIZE_SMALL)->render() . ' ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.removeFromClipboard')) . '
                    </span>
                </button>';
            // Add "copy marked" after "edit", or in case "edit" is not set, as first item
            if (!isset($actions['edit'])) {
                $actions = array_merge(['copyMarked' => $copyMarked], $actions);
            } else {
                $end = array_splice($actions, (int)(array_search('edit', array_keys($actions), true)) + 1);
                $actions = array_merge($actions, ['copyMarked' => $copyMarked, 'removeMarked' => $removeMarked], $end);
            }
        }

        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'])) {
            trigger_error(
                'The hook $TYPO3_CONF_VARS[SC_OPTIONS][typo3/class.db_list_extra.inc][actions] for calling method "renderListHeaderActions" is deprecated and will stop working in TYPO3 v12.0. Use the ModifyRecordListTableActionsEvent instead.',
                E_USER_DEPRECATED
            );
        }

        /*
         * hook:  renderListHeaderActions: Allows to influence the table header actions
         * usage: Above each listed table in Web>List a header row is shown.
         *        This hook allows to modify the icons responsible for the clipboard functions
         *        or other "Action" functions which perform operations on the listed records.
         *
         * @deprecated in v11, will be removed in TYPO3 v12.0.
         */
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof RecordListHookInterface) {
                throw new \UnexpectedValueException($className . ' must implement interface ' . RecordListHookInterface::class, 1195567850);
            }
            $actions = $hookObject->renderListHeaderActions($table, $currentIdList, $actions, $this);
        }

        $event = $this->eventDispatcher->dispatch(
            new ModifyRecordListTableActionsEvent($actions, $table, $currentIdList, $this)
        );
        $actions = $event->getActions();

        if ($actions === []) {
            // In case the user does not have permissions to execute on of the above
            // actions or a hook removed all remaining actions, inform the user about this.
            return '
                <div class="col pt-1 pb-1">
                    <span class="label label-info">
                    ' . htmlspecialchars($lang->sL($event->getNoActionLabel() ?: 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noActionAvailable')) . '
                    </span>
                </div>';
        }

        // In case both clipboard actions should be rendered, wrap them into a button group
        if (($actions['copyMarked'] ?? false) && ($actions['removeMarked'] ?? false)) {
            $actions['copyMarked'] = '<div class="btn-group">' . $actions['copyMarked'] . $actions['removeMarked'] . '</div>';
            unset($actions['removeMarked']);
        }

        return implode(LF, array_map(static fn (string $action): string => '<div class="col">' . $action . '</div>', $actions));
    }

    /**
     * If enabled, only translations are shown (= only with l10n_parent)
     * See the use case in RecordList class, where a list of page translations is rendered before.
     *
     * @param bool $showOnlyTranslatedRecords
     */
    public function showOnlyTranslatedRecords(bool $showOnlyTranslatedRecords)
    {
        $this->showOnlyTranslatedRecords = $showOnlyTranslatedRecords;
    }

    /**
     * Creates data attributes to be handles in moddule `TYPO3/CMS/Backend/ActionDispatcher`
     *
     * @param string $arguments
     * @return string
     */
    protected function createShowItemTagAttributes(string $arguments): string
    {
        return GeneralUtility::implodeAttributes([
            'data-dispatch-action' => 'TYPO3.InfoWindow.showItem',
            'data-dispatch-args-list' => $arguments,
        ], true);
    }

    /**
     * Returns the language service
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @param array $languagesAllowedForUser
     * @return DatabaseRecordList
     */
    public function setLanguagesAllowedForUser(array $languagesAllowedForUser): DatabaseRecordList
    {
        $this->languagesAllowedForUser = $languagesAllowedForUser;
        return $this;
    }

    /**
     * Returns a new standalone view, shorthand function
     *
     * @param string $filename Which templateFile should be used.
     * @return StandaloneView
     */
    protected function getFluidTemplateObject(string $filename): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths(['EXT:recordlist/Resources/Private/Layouts']);
        $view->setPartialRootPaths(['EXT:recordlist/Resources/Private/Partials']);
        $view->setTemplateRootPaths(['EXT:recordlist/Resources/Private/Templates']);
        $view->setTemplate($filename);
        return $view;
    }

    /**
     * Check if a given record is a localization
     *
     * @param string $table
     * @param array $row
     *
     * @return bool
     */
    protected function isLocalized(string $table, array $row): bool
    {
        $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? '';
        $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? '';

        return ($row[$languageField] ?? false) && ($row[$transOrigPointerField] ?? false);
    }

    /**
     * Returns the configuration of mod.web_list.noViewWithDokTypes or the
     * default value 254 (Sys Folders) and 255 (Recycler), if not set.
     *
     * @param array $tsConfig
     * @return array
     */
    protected function getNoViewWithDokTypes(array $tsConfig): array
    {
        if (isset($tsConfig['noViewWithDokTypes'])) {
            $noViewDokTypes = GeneralUtility::intExplode(',', $tsConfig['noViewWithDokTypes'], true);
        } else {
            $noViewDokTypes = [
                PageRepository::DOKTYPE_SPACER,
                PageRepository::DOKTYPE_SYSFOLDER,
                PageRepository::DOKTYPE_RECYCLER,
            ];
        }

        return $noViewDokTypes;
    }

    /**
     * Check whether the clipboard functionality is generally enabled.
     * In case a row is given, this checks if the record is neither
     * a "delete placeholder", nor a translation.
     *
     * @param string $table
     * @param array $row
     * @return bool
     */
    protected function isClipboardFunctionalityEnabled(string $table, array $row = []): bool
    {
        return $this->clipObj !== null
            && ($table !== 'pages' || !$this->showOnlyTranslatedRecords)
            && (
                $row === []
                || (
                    !$this->isRecordDeletePlaceholder($row)
                    && (int)($row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? null] ?? 0) === 0
                )
            );
    }

    /**
     * Add a divider to the secondary cell gorup, if not already present
     *
     * @param array $cells
     */
    protected function addDividerToCellGroup(array &$cells): void
    {
        if (!($cells['secondary']['divider'] ?? false)) {
            $this->addActionToCellGroup($cells, '<hr class="dropdown-divider me-0 ms-0 border-white">', 'divider');
        }
    }
}
