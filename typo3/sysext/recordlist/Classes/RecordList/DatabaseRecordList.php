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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Backend\Avatar\Avatar;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\RecordList\RecordListGetTableHookInterface;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
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
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\CsvUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Fluid\View\StandaloneView;

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
     * If TRUE, will disable the rendering of clipboard + control panels.
     *
     * @var bool
     */
    public $dontShowClipControlPanels = false;

    /**
     * If TRUE, will show the clipboard in the field list.
     *
     * @var bool
     */
    public $showClipboard = false;

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
    public $spaceIcon;

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
     * Contains sys language icons and titles
     *
     * @var array
     */
    public $languageIconTitles = [];

    /**
     * Tables which should not list their translations
     *
     * @var string
     */
    public $hideTranslations = '';

    /**
     * If set, the listing is returned as CSV instead.
     *
     * @var bool
     */
    public $csvOutput = false;

    /**
     * Cache for record path
     *
     * @var mixed[]
     */
    public $recPath_cache = [];

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
    protected $moduleData =  [];

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
    public $duplicateStack = [];

    /**
     * If TRUE, records are listed only if a specific table is selected.
     *
     * @var bool
     */
    public $listOnlyInSingleTableMode = false;

    /**
     * @var TranslationConfigurationProvider
     */
    public $translateTools;

    /**
     * @var array[] Module configuration
     */
    public $modTSconfig;

    /**
     * Keys are fieldnames and values are td-css-classes to add in addElement();
     *
     * @var array
     */
    public $addElement_tdCssClass = [];

    /**
     * Used for tracking next/prev uids
     *
     * @var int[][]
     */
    public $currentTable = [];

    /**
     * Indicates if all available fields for a user should be selected or not.
     *
     * @var int
     */
    public $allFields = 0;

    /**
     * Number of records to show
     *
     * @var int
     */
    public $showLimit = 0;

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
     * Containing which fields to display in extended mode
     *
     * @var string[]
     */
    public $displayFields;

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
    public $searchLevels = 0;

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
    public $duplicateField;

    /**
     * Specify a list of tables which are the only ones allowed to be displayed.
     *
     * @var string
     */
    public $tableList = '';

    /**
     * Used to accumulate CSV lines for CSV export.
     *
     * @var string[]
     */
    protected $csvLines = [];

    /**
     * Clipboard object
     *
     * @var \TYPO3\CMS\Backend\Clipboard\Clipboard
     */
    public $clipObj;

    /**
     * Tracking names of elements (for clipboard use)
     *
     * @var string[]
     */
    public $CBnames = [];

    /**
     * [$tablename][$uid] = number of references to this record
     *
     * @var int[][]
     */
    protected $referenceCount = [];

    /**
     * Injected by RecordList
     *
     * @var string[]
     */
    public $MOD_MENU;

    /**
     * If defined the records are editable
     *
     * @var bool
     */
    protected $editable = true;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

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
    protected $tableDisplayOrder = [];

    /**
     * Override the page ids taken into account by getPageIdConstraint()
     *
     * @var array
     */
    protected $overridePageIdList = [];

    /**
     * Override/add urlparameters in listUrl() method
     * @var mixed[]
     */
    protected $overrideUrlParameters = [];

    /**
     * Current link: array with table names and uid
     *
     * @var array
     */
    protected $currentLink = [];

    /**
     * Only used to render translated records, used in list module to show page translations
     *
     * @var bool
     */
    protected $showOnlyTranslatedRecords = false;

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
    protected $possibleTranslations = [];

    /**
     * All languages that are allowed by the user
     *
     * This is used for the translation handling of pages only.
     *
     * @var array
     */
    protected $languagesAllowedForUser = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->translateTools = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
        $this->calcPerms = new Permission();
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform
     * operations.
     *
     * @param ModuleTemplate $moduleTemplate
     * @param ServerRequestInterface $request
     */
    public function getDocHeaderButtons(ModuleTemplate $moduleTemplate, ServerRequestInterface $request)
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $modulePageTsConfig = BackendUtility::getPagesTSconfig($this->id)['mod.']['web_list.'] ?? [];
        $backendUser = $this->getBackendUserAuthentication();
        $lang = $this->getLanguageService();
        // Get users permissions for this page record:
        $localCalcPerms = new Permission($backendUser->calcPerms($this->pageRow));
        // CSH
        if ((string)$this->id === '') {
            $fieldName = 'list_module_noId';
        } elseif (!$this->id) {
            $fieldName = 'list_module_root';
        } else {
            $fieldName = 'list_module';
        }
        $cshButton = $buttonBar->makeHelpButton()
            ->setModuleName('xMOD_csh_corebe')
            ->setFieldName($fieldName);
        $buttonBar->addButton($cshButton);
        if (isset($this->id)) {
            // New record on pages that are not locked by editlock
            if (!($modulePageTsConfig['noCreateRecordsLink'] ?? false) && $this->editLockPermissions()) {
                $newRecordButton = $buttonBar->makeLinkButton()
                    ->setHref((string)$this->uriBuilder->buildUriFromRoute('db_new', ['id' => $this->id, 'returnUrl' => $this->listURL()]))
                    ->setTitle($lang->getLL('newRecordGeneral'))
                    ->setIcon($this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL));
                $buttonBar->addButton($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
            }
            if (!in_array($this->pageRow['doktype'] ?? null, $this->getNoViewWithDokTypes($modulePageTsConfig))) {
                $previewDataAttributes = PreviewUriBuilder::create((int)$this->id)
                    ->withRootLine(BackendUtility::BEgetRootLine($this->id))
                    ->buildDispatcherDataAttributes();
                $viewButton = $buttonBar->makeLinkButton()
                    ->setHref('#')
                    ->setDataAttributes($previewDataAttributes ?? [])
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                    ->setIcon($this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL));
                $buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 20);
            }
            // If edit permissions are set, see
            // \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
            if ($localCalcPerms->editPagePermissionIsGranted() && !empty($this->id) && $this->editLockPermissions() && $backendUser->checkLanguageAccess(0)) {
                // Edit
                $editLink = $this->uriBuilder->buildUriFromRoute('record_edit', [
                    'edit' => [
                        'pages' => [
                            $this->pageRow['uid'] => 'edit'
                        ]
                    ],
                    'returnUrl' => $this->listURL()
                ]);
                $editButton = $buttonBar->makeLinkButton()
                    ->setHref($editLink)
                    ->setTitle($lang->getLL('editPage'))
                    ->setIcon($this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL));
                $buttonBar->addButton($editButton, ButtonBar::BUTTON_POSITION_LEFT, 20);
            }
            // Paste
            if ($this->showClipboard && ($localCalcPerms->createPagePermissionIsGranted() || $localCalcPerms->editContentPermissionIsGranted()) && $this->editLockPermissions()) {
                $elFromTable = $this->clipObj->elFromTable('');
                if (!empty($elFromTable)) {
                    $confirmMessage = $this->clipObj->confirmMsgText('pages', $this->pageRow, 'into', $elFromTable);
                    $pasteButton = $buttonBar->makeLinkButton()
                        ->setHref($this->clipObj->pasteUrl('', $this->id))
                        ->setTitle($lang->getLL('clip_paste'))
                        ->setClasses('t3js-modal-trigger')
                        ->setDataAttributes([
                            'severity' => 'warning',
                            'content' => $confirmMessage,
                            'title' => $lang->getLL('clip_paste')
                        ])
                        ->setIcon($this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL));
                    $buttonBar->addButton($pasteButton, ButtonBar::BUTTON_POSITION_LEFT, 40);
                }
            }
            // Cache
            if ($this->id !== 0) {
                $clearCacheButton = $buttonBar->makeLinkButton()
                    ->setHref('#')
                    ->setDataAttributes(['id' => $this->id])
                    ->setClasses('t3js-clear-page-cache')
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.clear_cache'))
                    ->setIcon($this->iconFactory->getIcon('actions-system-cache-clear', Icon::SIZE_SMALL));
                $buttonBar->addButton($clearCacheButton, ButtonBar::BUTTON_POSITION_RIGHT);
            }
            if ($this->table && (!isset($modulePageTsConfig['noExportRecordsLinks'])
                || (isset($modulePageTsConfig['noExportRecordsLinks'])
                    && !$modulePageTsConfig['noExportRecordsLinks']))
            ) {
                // CSV
                $csvButton = $buttonBar->makeLinkButton()
                    ->setHref($this->listURL() . '&csv=1')
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.csv'))
                    ->setIcon($this->iconFactory->getIcon('actions-document-export-csv', Icon::SIZE_SMALL))
                    ->setShowLabelText(true);
                $buttonBar->addButton($csvButton, ButtonBar::BUTTON_POSITION_LEFT, 40);
                // Export
                if (ExtensionManagementUtility::isLoaded('impexp')) {
                    $url = (string)$this->uriBuilder->buildUriFromRoute('tx_impexp_export');
                    $exportButton = $buttonBar->makeLinkButton()
                        ->setHref($url . '&tx_impexp[list][]=' . rawurlencode($this->table . ':' . $this->id))
                        ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.export'))
                        ->setIcon($this->iconFactory->getIcon('actions-document-export-t3d', Icon::SIZE_SMALL))
                        ->setShowLabelText(true);
                    $buttonBar->addButton($exportButton, ButtonBar::BUTTON_POSITION_LEFT, 40);
                }
            }
            // Reload
            $reloadButton = $buttonBar->makeLinkButton()
                ->setHref($this->listURL())
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
                ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
            $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);

            // Shortcut
            $shortCutButton = $buttonBar->makeShortcutButton()->setRouteIdentifier('web_list');
            $queryParams = $request->getQueryParams();
            $arguments = [
                'id' => $this->id
            ];
            $potentialArguments = [
                'pointer',
                'table',
                'search_field',
                'search_levels',
                'showLimit',
                'sortField',
                'sortRev'
            ];
            foreach ($potentialArguments as $argument) {
                if (!empty($queryParams[$argument])) {
                    $arguments[$argument] = $queryParams[$argument];
                }
            }
            $moduleSettings = $this->moduleData;
            foreach ($moduleSettings as $moduleSettingKey => $moduleSettingValue) {
                $arguments['GET'][$moduleSettingKey] = $moduleSettingValue;
            }
            $shortCutButton->setArguments($arguments);
            $shortCutButton->setDisplayName($this->getShortcutTitle($arguments));
            $buttonBar->addButton($shortCutButton, ButtonBar::BUTTON_POSITION_RIGHT);

            // Back
            if ($this->returnUrl) {
                $backButton = $buttonBar->makeLinkButton()
                    ->setHref($this->returnUrl)
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                    ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
                $buttonBar->addButton($backButton, ButtonBar::BUTTON_POSITION_LEFT);
            }
        }
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
            ->execute()
            ->fetchColumn();
        if ($totalItems === 0) {
            return '';
        }
        // set the limits
        // Use default value and overwrite with page ts config and tca config depending on the current view
        // Force limit in range 5, 10000

        // default 100
        $itemsLimitSingleTable = MathUtility::forceIntegerInRange((int)(
            $GLOBALS['TCA'][$table]['interface']['maxSingleDBListItems'] ??
            $this->modTSconfig['properties']['itemsLimitSingleTable'] ??
            100
        ), 5, 10000);

        // default 20
        $itemsLimitPerTable = MathUtility::forceIntegerInRange((int)(
            $GLOBALS['TCA'][$table]['interface']['maxDBListItems'] ??
            $this->modTSconfig['properties']['itemsLimitPerTable'] ??
            20
        ), 5, 10000);

        // Set limit depending on the view (single table vs. default)
        $iLimit = $this->table ? $itemsLimitSingleTable : $itemsLimitPerTable;

        // Set limit from search
        if ($this->showLimit) {
            $iLimit = $this->showLimit;
        }

        // csv export - set no limit at all
        if ($this->csvOutput) {
            $iLimit = 0;
        }

        // Init
        $titleCol = $GLOBALS['TCA'][$table]['ctrl']['label'];
        $l10nEnabled = $GLOBALS['TCA'][$table]['ctrl']['languageField']
            && $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];

        // Setting fields selected in fieldselectBox (saved in uc)
        $rowListArray = [];
        if ($this->allFields && is_array($this->setFields[$table] ?? null)) {
            $rowListArray = $this->makeFieldList($table, false, true);
            if ($this->csvOutput === false) {
                $rowListArray[] = '_PATH_';
                $rowListArray[] = '_REF_';
            }
            $rowListArray = array_intersect($rowListArray, $this->setFields[$table]);
        }
        // if no columns have been specified, show description (if configured)
        if (!empty($GLOBALS['TCA'][$table]['ctrl']['descriptionColumn']) && empty($rowListArray)) {
            $rowListArray[] = $GLOBALS['TCA'][$table]['ctrl']['descriptionColumn'];
        }
        $backendUser = $this->getBackendUserAuthentication();
        $lang = $this->getLanguageService();
        // Get configuration of collapsed tables from user uc
        $tablesCollapsed = is_array($backendUser->uc['moduleData']['list'])
            ? $backendUser->uc['moduleData']['list']
            : [];
        $tableCollapsed = (bool)($tablesCollapsed[$table] ?? false);
        // prepare space icon
        $this->spaceIcon = '<span class="btn btn-default disabled">' . $this->iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
        // Place the $titleCol as the first column always!
        $this->fieldArray = [];
        // Add title column
        $this->fieldArray[] = $titleCol;
        if ($this->csvOutput === false) {
            // Control-Panel
            if ($this->noControlPanels === false) {
                $this->fieldArray[] = '_CONTROL_';
            }
            // Clipboard
            if ($this->showClipboard && $this->noControlPanels === false) {
                $this->fieldArray[] = '_CLIPBOARD_';
            }
            // Ref
            if (!in_array('_REF_', $rowListArray, true) && !$this->dontShowClipControlPanels) {
                $this->fieldArray[] = '_REF_';
            }
            // Path
            if (!in_array('_PATH_', $rowListArray, true) && $this->searchLevels) {
                $this->fieldArray[] = '_PATH_';
            }
            // Localization
            if ($l10nEnabled) {
                $this->fieldArray[] = '_LOCALIZATION_';
                // Do not show the "Localize to:" field when only translated records should be shown
                if (!$this->showOnlyTranslatedRecords) {
                    $this->fieldArray[] = '_LOCALIZATION_b';
                }
            }
        }
        // Cleaning up:
        $this->fieldArray = array_unique(array_merge($this->fieldArray, $rowListArray));
        // Creating the list of fields to include in the SQL query:
        $selectFields = $this->fieldArray;
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
        if (is_array($GLOBALS['TCA'][$table]['ctrl']['enablecolumns'])) {
            $selectFields = array_merge($selectFields, $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']);
        }
        foreach (['type', 'typeicon_column', 'editlock'] as $field) {
            if ($GLOBALS['TCA'][$table]['ctrl'][$field]) {
                $selectFields[] = $GLOBALS['TCA'][$table]['ctrl'][$field];
            }
        }
        if (BackendUtility::isTableWorkspaceEnabled($table)) {
            $selectFields[] = 't3ver_state';
            $selectFields[] = 't3ver_wsid';
            $selectFields[] = 't3ver_oid';
        }
        if ($l10nEnabled) {
            $selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
            $selectFields[] = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
        }
        if ($GLOBALS['TCA'][$table]['ctrl']['label_alt']) {
            $selectFields = array_merge(
                $selectFields,
                GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['label_alt'], true)
            );
        }
        // Unique list!
        $selectFields = array_unique($selectFields);
        $fieldListFields = $this->makeFieldList($table, true);
        // Making sure that the fields in the field-list ARE in the field-list from TCA!
        $selectFields = array_intersect($selectFields, $fieldListFields);

        // Implode it into a list of fields for the SQL-statement.
        $selFieldList = implode(',', $selectFields);

        if ($this->firstElementNumber > 2 && $iLimit > 0) {
            // Get the two previous rows for sorting if displaying page > 1
            $this->firstElementNumber -= 2;
            $iLimit += 2;
            $queryBuilder = $this->getQueryBuilder($table, $id, [], array_values($selectFields), true, $this->firstElementNumber, $iLimit);
            $this->firstElementNumber += 2;
            $iLimit -= 2;
        } else {
            $queryBuilder = $this->getQueryBuilder($table, $id, [], array_values($selectFields), true, $this->firstElementNumber, $iLimit);
        }

        // Init:
        $queryResult = $queryBuilder->execute();
        $dbCount = 0;
        $out = '';
        $tableHeader = '';
        $listOnlyInSingleTableMode = $this->listOnlyInSingleTableMode && !$this->table;
        // If the count query returned any number of records, we perform the real query,
        // selecting records.
        if ($totalItems) {
            // Fetch records only if not in single table mode
            if ($listOnlyInSingleTableMode) {
                $dbCount = $totalItems;
            } else {
                // Set the showLimit to the number of records when outputting as CSV
                if ($this->csvOutput) {
                    $this->showLimit = $totalItems;
                    $iLimit = $totalItems;
                    $dbCount = $totalItems;
                } else {
                    if ($this->firstElementNumber + $this->showLimit <= $totalItems) {
                        $dbCount = $this->showLimit + 2;
                    } else {
                        $dbCount = $totalItems - $this->firstElementNumber + 2;
                    }
                }
            }
        }
        // If any records was selected, render the list:
        if ($dbCount) {
            $tableIdentifier = $table;
            // Use a custom table title for translated pages
            if ($table == 'pages' && $this->showOnlyTranslatedRecords) {
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
            // Header line is drawn
            $theData = [];
            if ($this->disableSingleTableView) {
                $theData[$titleCol] = '<span class="c-table">' . BackendUtility::wrapInHelp($table, '', $tableTitle)
                    . '</span> (<span class="t3js-table-total-items">' . $totalItems . '</span>)';
            } else {
                $icon = $this->table
                    ? '<span title="' . htmlspecialchars($lang->getLL('contractView')) . '">' . $this->iconFactory->getIcon('actions-view-table-collapse', Icon::SIZE_SMALL)->render() . '</span>'
                    : '<span title="' . htmlspecialchars($lang->getLL('expandView')) . '">' . $this->iconFactory->getIcon('actions-view-table-expand', Icon::SIZE_SMALL)->render() . '</span>';
                $theData[$titleCol] = $this->linkWrapTable($table, $tableTitle . ' (<span class="t3js-table-total-items">' . $totalItems . '</span>) ' . $icon);
            }
            if ($listOnlyInSingleTableMode) {
                $tableHeader .= BackendUtility::wrapInHelp($table, '', $theData[$titleCol]);
            } else {
                // Render collapse button if in multi table mode
                $collapseButton = '';
                if (!$this->table) {
                    $icon = '<span class="collapseIcon">' . $this->iconFactory->getIcon(($tableCollapsed ? 'actions-view-list-expand' : 'actions-view-list-collapse'), Icon::SIZE_SMALL)->render() . '</span>';
                    $collapseButton = '<button type="button"'
                        . ' class="btn btn-default btn-sm pull-right t3js-toggle-recordlist"'
                        . ' aria-expanded="' . ($tableCollapsed ? 'false' : 'true') . '"'
                        . ' aria-label="' . sprintf(htmlspecialchars($lang->getLL('collapseExpandTable')), $tableTitle) . '"'
                        . ' data-table="' . htmlspecialchars($tableIdentifier) . '"'
                        . ' data-bs-toggle="collapse"'
                        . ' data-bs-target="#recordlist-' . htmlspecialchars($tableIdentifier) . '">'
                        . $icon
                        . '</button>';
                }
                $tableHeader .= $theData[$titleCol] . $collapseButton;
            }
            // Render table rows only if in multi table view or if in single table view
            $rowOutput = '';
            if (!$listOnlyInSingleTableMode || $this->table) {
                // Fixing an order table for sortby tables
                $this->currentTable = [];
                $currentIdList = [];
                $doSort = $GLOBALS['TCA'][$table]['ctrl']['sortby'] && !$this->sortField;
                $prevUid = 0;
                $prevPrevUid = 0;
                // Get first two rows and initialize prevPrevUid and prevUid if on page > 1
                if ($this->firstElementNumber > 2 && $iLimit > 0) {
                    $row = $queryResult->fetch();
                    $prevPrevUid = -((int)$row['uid']);
                    $row = $queryResult->fetch();
                    $prevUid = $row['uid'];
                }
                $accRows = [];
                // Accumulate rows here
                while ($row = $queryResult->fetch()) {
                    if (!$this->isRowListingConditionFulfilled($table, $row)) {
                        continue;
                    }
                    // In offline workspace, look for alternative record:
                    BackendUtility::workspaceOL($table, $row, $backendUser->workspace, true);
                    if (is_array($row)) {
                        $accRows[] = $row;
                        $currentIdList[] = $row['uid'];
                        if ($doSort) {
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
                // CSV initiated
                if ($this->csvOutput) {
                    $this->addHeaderRowToCSV();
                }
                // Render items:
                $this->CBnames = [];
                $this->duplicateStack = [];
                $cc = 0;
                foreach ($accRows as $row) {
                    // Render item row if counter < limit
                    if ($cc < $iLimit) {
                        $cc++;
                        // Reset translations
                        $translations = [];
                        // Initialize with FALSE which causes the localization panel to not beeing displayed as
                        // the record is already localized, in free mode or has sys_language_uid -1 set.
                        // Only set to TRUE if TranslationConfigurationProvider::translationInfo() returns
                        // an array indicating the record can be translated.
                        $translationEnabled = false;
                        // Guard clause so we can quickly return if a record is localized to "all languages"
                        // It should only be possible to localize a record off default (uid 0)
                        if ($l10nEnabled && (int)$row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] !== -1) {
                            $translationsRaw = $this->translateTools->translationInfo($table, $row['uid'], 0, $row, $selFieldList);
                            if (is_array($translationsRaw)) {
                                $translationEnabled = true;
                                if (is_array($translationsRaw['translations'])) {
                                    $translations = $translationsRaw['translations'];
                                }
                            }
                        }
                        $rowOutput .= $this->renderListRow($table, $row, $cc, $titleCol, '', 0, $translations, $translationEnabled);
                        // If no search happened it means that the selected
                        // records are either default or All language and here we will not select translations
                        // which point to the main record:
                        if ($l10nEnabled && $this->searchString === '' && !($this->hideTranslations === '*' || GeneralUtility::inList($this->hideTranslations, $table))) {
                            foreach ($translations as $lRow) {
                                if (!$this->isRowListingConditionFulfilled($table, $lRow)) {
                                    continue;
                                }
                                // In offline workspace, look for alternative record:
                                BackendUtility::workspaceOL($table, $lRow, $backendUser->workspace, true);
                                if (is_array($lRow) && $backendUser->checkLanguageAccess($lRow[$GLOBALS['TCA'][$table]['ctrl']['languageField']])) {
                                    $currentIdList[] = $lRow['uid'];
                                    $rowOutput .= $this->renderListRow($table, $lRow, $cc, $titleCol, '', 18, [], false);
                                }
                            }
                        }
                    }
                }
                // Record navigation is added to the beginning and end of the table if in single
                // table mode
                if ($this->table) {
                    $rowOutput = $this->renderListNavigation('top', $totalItems, $iLimit, (string)$table) . $rowOutput . $this->renderListNavigation('bottom', $totalItems, $iLimit, (string)$table);
                } else {
                    // Show that there are more records than shown
                    if ($totalItems > $itemsLimitPerTable) {
                        $countOnFirstPage = $totalItems > $itemsLimitSingleTable ? $itemsLimitSingleTable : $totalItems;
                        $hasMore = $totalItems > $itemsLimitSingleTable;
                        $colspan =  (count($this->fieldArray) + 1);
                        $rowOutput .= '<tr><td colspan="' . $colspan . '">
								<a href="' . htmlspecialchars($this->listURL() . '&table=' . rawurlencode($tableIdentifier)) . '" class="btn btn-default">'
                            . '<span class="t3-icon fa fa-chevron-down"></span> <i>[1 - ' . $countOnFirstPage . ($hasMore ? '+' : '') . ']</i></a>
								</td></tr>';
                    }
                }
                // The header row for the table is now created:
                $out .= $this->renderListHeader($table, $currentIdList);
            }

            $collapseClass = $tableCollapsed && !$this->table ? 'collapse' : 'collapse show';
            $dataState = $tableCollapsed && !$this->table ? 'collapsed' : 'expanded';

            // The list of records is added after the header:
            $out .= $rowOutput;
            // ... and it is all wrapped in a table:
            $out = '



			<!--
				DB listing of elements:	"' . htmlspecialchars($tableIdentifier) . '"
			-->
				<div class="panel panel-space panel-default recordlist">
					<div class="panel-heading">
					' . $tableHeader . '
					</div>
					<div class="' . $collapseClass . '" data-state="' . $dataState . '" id="recordlist-' . htmlspecialchars($tableIdentifier) . '">
						<div class="table-fit">
							<table data-table="' . htmlspecialchars($tableIdentifier) . '" class="table table-striped table-hover' . ($listOnlyInSingleTableMode ? ' typo3-dblist-overview' : '') . '">
								' . $out . '
							</table>
						</div>
					</div>
				</div>
			';
            // Output csv if...
            // This ends the page with exit.
            if ($this->csvOutput) {
                $this->outputCSV($table);
            }
        }
        // Return content:
        return $out;
    }

    /**
     * Get viewOnClick link for pages or tt_content records
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
            $language = (int)$row[$GLOBALS['TCA']['tt_content']['ctrl']['languageField']];
            if ($language > 0) {
                $additionalParams = '&L=' . $language;
            }
            $previewUriBuilder = PreviewUriBuilder::create((int)$this->id)
                ->withSection('#c' . $row['uid'])
                ->withAdditionalQueryParameters($additionalParams);
        } elseif ($table === 'pages' && $row[$GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField']] > 0) {
            // Link to a page translation needs uid of default language page as id
            $languageParentId = (int)$row[$GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField']];
            $language = (int)$row[$GLOBALS['TCA']['pages']['ctrl']['languageField']];
            $previewUriBuilder = PreviewUriBuilder::create($languageParentId)
                ->withSection('#c' . $row['uid'])
                ->withAdditionalQueryParameters('&L=' . $language);
        } else {
            // Link to a page in the default language
            $previewUriBuilder = PreviewUriBuilder::create((int)$row['uid']);
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
     * @param int $cc Counter, counting for each time an element is rendered (used for alternating colors)
     * @param string $titleCol Table field (column) where header value is found
     * @param string $thumbsCol Table field (column) where (possible) thumbnails can be found
     * @param int $indent Indent from left.
     * @param array $translations Array of already existing translations for the current record
     * @param bool $translationEnabled Whether the record can be translated
     * @return string Table row for the element
     * @internal
     * @see getTable()
     */
    public function renderListRow($table, $row, $cc, $titleCol, $thumbsCol, $indent, array $translations, bool $translationEnabled)
    {
        if (!is_array($row)) {
            return '';
        }
        $languageService = $this->getLanguageService();
        $rowOutput = '';
        $id_orig = null;
        // If in search mode, make sure the preview will show the correct page
        if ((string)$this->searchString !== '') {
            $id_orig = $this->id;
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

        // The icon with link
        $toolTip = BackendUtility::getRecordToolTip($row, $table);
        $additionalStyle = $indent ? ' style="margin-left: ' . $indent . 'px;"' : '';
        $iconImg = '<span ' . $toolTip . ' ' . $additionalStyle . '>'
            . $this->iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render()
            . '</span>';
        $theIcon = ($this->clickMenuEnabled && !$this->isRecordDeletePlaceholder($row)) ? BackendUtility::wrapClickMenuOnIcon($iconImg, $table, $row['uid']) : $iconImg;
        // Preparing and getting the data-array
        $theData = [];
        $deletePlaceholderClass = '';
        foreach ($this->fieldArray as $fCol) {
            if ($fCol == $titleCol) {
                $recTitle = BackendUtility::getRecordTitle($table, $row, false, true);
                $warning = '';
                // If the record is edit-locked	by another user, we will show a little warning sign:
                $lockInfo = BackendUtility::isRecordLocked($table, $row['uid']);
                if ($lockInfo) {
                    $warning = '<span data-bs-toggle="tooltip" data-bs-placement="right" data-title="' . htmlspecialchars($lockInfo['msg']) . '">'
                        . $this->iconFactory->getIcon('warning-in-use', Icon::SIZE_SMALL)->render() . '</span>';
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
                $beUserRecord = BackendUtility::getRecord('be_users', (int)$row[$fCol]);
                if (is_array($beUserRecord)) {
                    $avatar = GeneralUtility::makeInstance(Avatar::class);
                    $label = htmlspecialchars(BackendUtility::getRecordTitle('be_users', $beUserRecord));
                    $theData[$fCol] = $avatar->render($beUserRecord) . '<strong>' . $label . '</strong>';
                } else {
                    $theData[$fCol] = '<strong>&ndash;</strong>';
                }
            } elseif ($fCol === '_PATH_') {
                $theData[$fCol] = $this->recPath($row['pid']);
            } elseif ($fCol === '_REF_') {
                $theData[$fCol] = $this->generateReferenceToolTip($table, $row['uid']);
            } elseif ($fCol === '_CONTROL_') {
                $theData[$fCol] = $this->makeControl($table, $row);
            } elseif ($fCol === '_CLIPBOARD_') {
                $theData[$fCol] = $this->makeClip($table, $row);
            } elseif ($fCol === '_LOCALIZATION_') {
                // Language flag an title
                $theData[$fCol] = $this->languageFlag($table, $row);
                // Localize record
                $localizationPanel = $translationEnabled ? $this->makeLocalizationPanel($table, $row, $translations) : '';
                $theData[$fCol . 'b'] = '<div class="btn-group">' . $localizationPanel . '</div>';
            } elseif ($fCol === '_LOCALIZATION_b') {
                // deliberately empty
            } else {
                $pageId = $table === 'pages' ? $row['uid'] : $row['pid'];
                $tmpProc = BackendUtility::getProcessedValueExtra($table, $fCol, $row[$fCol], 100, $row['uid'], true, $pageId);
                $theData[$fCol] = $this->linkUrlMail(htmlspecialchars($tmpProc), $row[$fCol]);
                if ($this->csvOutput) {
                    $row[$fCol] = BackendUtility::getProcessedValueExtra($table, $fCol, $row[$fCol], 0, $row['uid']);
                }
            }
        }
        // Reset the ID if it was overwritten
        if ((string)$this->searchString !== '') {
            $this->id = $id_orig;
        }
        // Add row to CSV list:
        if ($this->csvOutput) {
            $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][__CLASS__]['customizeCsvRow'] ?? [];
            if (!empty($hooks)) {
                $hookParameters = [
                    'databaseRow' => &$row,
                    'tableName' => $table,
                    'pageId' => $this->id
                ];
                foreach ($hooks as $hookFunction) {
                    GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
                }
            }
            $this->setCsvRow($row);
        }
        // Add classes to table cells
        $this->addElement_tdCssClass[$titleCol] = 'col-title col-responsive' . $deletePlaceholderClass;
        $this->addElement_tdCssClass['__label'] = $this->addElement_tdCssClass[$titleCol];
        $this->addElement_tdCssClass['_CONTROL_'] = 'col-control';
        if ($this->moduleData['clipBoard']) {
            $this->addElement_tdCssClass['_CLIPBOARD_'] = 'col-clipboard';
        }
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
            function ($attributeValue) {
                if (is_array($attributeValue)) {
                    return implode(' ', $attributeValue);
                }
                return $attributeValue;
            },
            $tagAttributes
        );

        $rowOutput .= $this->addElement(1, $theIcon, $theData, GeneralUtility::implodeAttributes($tagAttributes, true));
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
        $tsConfig = BackendUtility::getPagesTSconfig($this->id);
        $tsConfigOfTable = is_array($tsConfig['TCEFORM.'][$table . '.']) ? $tsConfig['TCEFORM.'][$table . '.'] : null;

        $lang = $this->getLanguageService();
        // Init:
        $theData = [];
        $icon = '';
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
                case '_PATH_':
                    // Path
                    $theData[$fCol] = '<i>[' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels._PATH_')) . ']</i>';
                    break;
                case '_REF_':
                    // References
                    $theData[$fCol] = '<i>[' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels._REF_')) . ']</i>';
                    break;
                case '_LOCALIZATION_':
                    // Path
                    $theData[$fCol] = '<i>[' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels._LOCALIZATION_')) . ']</i>';
                    break;
                case '_LOCALIZATION_b':
                    // Path
                    $theData[$fCol] = htmlspecialchars($lang->getLL('Localize'));
                    break;
                case '_CLIPBOARD_':
                    if (!$this->moduleData['clipBoard']) {
                        break;
                    }
                    // Clipboard:
                    $cells = [];
                    // If there are elements on the clipboard for this table, and the parent page is not locked by editlock
                    // then display the "paste into" icon:
                    $elFromTable = $this->clipObj->elFromTable($table);
                    if (!empty($elFromTable) && $this->overlayEditLockPermissions($table)) {
                        $href = htmlspecialchars($this->clipObj->pasteUrl($table, $this->id));
                        $confirmMessage = $this->clipObj->confirmMsgText('pages', $this->pageRow, 'into', $elFromTable);
                        $cells['pasteAfter'] = '<a class="btn btn-default t3js-modal-trigger"'
                            . ' href="' . $href . '"'
                            . ' title="' . htmlspecialchars($lang->getLL('clip_paste')) . '"'
                            . ' data-title="' . htmlspecialchars($lang->getLL('clip_paste')) . '"'
                            . ' data-bs-content="' . htmlspecialchars($confirmMessage) . '"'
                            . ' data-severity="warning">'
                            . $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)->render()
                            . '</a>';
                    }
                    // If the numeric clipboard pads are enabled, display the control icons for that:
                    if ($this->clipObj->current !== 'normal') {
                        // The "select" link:
                        $spriteIcon = $this->iconFactory->getIcon('actions-edit-copy', Icon::SIZE_SMALL)->render();
                        $cells['copyMarked'] = $this->linkClipboardHeaderIcon($spriteIcon, $table, 'setCB', '', $lang->getLL('clip_selectMarked'));
                        // The "edit marked" link:
                        $editUri = (string)$this->uriBuilder->buildUriFromRoute('record_edit', ['returnUrl' => $this->listURL()])
                            . '&edit[' . $table . '][{entityIdentifiers:editList}]=edit';
                        $cells['edit'] = '<a class="btn btn-default t3js-record-edit-multiple" href="#"'
                            . ' data-uri="' . htmlspecialchars($editUri) . '"'
                            . ' title="' . htmlspecialchars($lang->getLL('clip_editMarked')) . '">'
                            . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
                        // The "Delete marked" link:
                        $cells['delete'] = $this->linkClipboardHeaderIcon(
                            $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render(),
                            $table,
                            'delete',
                            sprintf($lang->getLL('clip_deleteMarkedWarning'), $lang->sL($GLOBALS['TCA'][$table]['ctrl']['title'])),
                            $lang->getLL('clip_deleteMarked')
                        );
                        // The "Select all" link:
                        $cells['markAll'] = '<a class="btn btn-default t3js-toggle-all-checkboxes" data-checkboxes-names="' . htmlspecialchars(implode(',', $this->CBnames)) . '" rel="" href="#" title="'
                            . htmlspecialchars($lang->getLL('clip_markRecords')) . '">'
                            . $this->iconFactory->getIcon('actions-document-select', Icon::SIZE_SMALL)->render() . '</a>';
                    } else {
                        $cells['empty'] = '';
                    }
                    /*
                     * hook:  renderListHeaderActions: Allows to change the clipboard icons of the Web>List table headers
                     * usage: Above each listed table in Web>List a header row is shown.
                     *        This hook allows to modify the icons responsible for the clipboard functions
                     *        (shown above the clipboard checkboxes when a clipboard other than "Normal" is selected),
                     *        or other "Action" functions which perform operations on the listed records.
                     */
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] ?? [] as $className) {
                        $hookObject = GeneralUtility::makeInstance($className);
                        if (!$hookObject instanceof RecordListHookInterface) {
                            throw new \UnexpectedValueException($className . ' must implement interface ' . RecordListHookInterface::class, 1195567850);
                        }
                        $cells = $hookObject->renderListHeaderActions($table, $currentIdList, $cells, $this);
                    }
                    $theData[$fCol] = '';
                    if (isset($cells['edit']) && isset($cells['delete'])) {
                        $theData[$fCol] .= '<div class="btn-group" role="group">' . $cells['edit'] . $cells['delete'] . '</div>';
                        unset($cells['edit'], $cells['delete']);
                    }
                    $theData[$fCol] .= '<div class="btn-group" role="group">' . implode('', $cells) . '</div>';
                    break;
                case '_CONTROL_':
                    // Control panel:
                    if ($this->isEditable($table)) {
                        // If new records can be created on this page, add links:
                        $permsAdditional = ($table === 'pages' ? Permission::PAGE_NEW : Permission::CONTENT_EDIT);
                        if ($this->calcPerms->isGranted($permsAdditional) && $this->showNewRecLink($table)) {
                            $spriteIcon = $table === 'pages'
                                ? $this->iconFactory->getIcon('actions-page-new', Icon::SIZE_SMALL)
                                : $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL);
                            if ($table === 'tt_content') {
                                // If mod.newContentElementWizard.override is set, use that extension's create new content wizard instead:
                                $newContentElementWizard = $tsConfig['mod.']['newContentElementWizard.']['override']
                                    ?? 'new_content_element_wizard';
                                $url = (string)$this->uriBuilder->buildUriFromRoute(
                                    $newContentElementWizard,
                                    [
                                        'id' => $this->id,
                                        'returnUrl' => $this->listURL(),
                                    ]
                                );
                                $title = htmlspecialchars($lang->getLL('new'));
                                $icon = '<a href="' . htmlspecialchars($url) . '" '
                                    . 'title="' . $title . '"'
                                    . 'data-title="' . $title . '"'
                                    . 'class="btn btn-default t3js-toggle-new-content-element-wizard disabled">'
                                    . $spriteIcon->render()
                                    . '</a>';
                            } elseif ($table === 'pages') {
                                $parameters = ['id' => $this->id, 'pagesOnly' => 1, 'returnUrl' => $this->listURL()];
                                $href = (string)$this->uriBuilder->buildUriFromRoute('db_new', $parameters);
                                $icon = '<a class="btn btn-default" href="' . htmlspecialchars($href) . '" title="' . htmlspecialchars($lang->getLL('new')) . '">'
                                    . $spriteIcon->render() . '</a>';
                            } else {
                                $params = [
                                    'edit' => [
                                        $table => [
                                            $this->id => 'new'
                                        ]
                                    ]
                                ];
                                if ($table === 'pages') {
                                    $params['overrideVals']['pages']['doktype'] = (int)$this->pageRow['doktype'];
                                }
                                $params['returnUrl'] = $this->listURL();
                                $newLink = $this->uriBuilder->buildUriFromRoute('record_edit', $params);
                                $icon = '<a class="btn btn-default" href="' . htmlspecialchars($newLink) . '" title="' . htmlspecialchars($lang->getLL('new')) . '">' . $spriteIcon->render() . '</a>';
                            }
                        }
                        // If the table can be edited, add link for editing ALL SHOWN fields for all listed records:
                        if ($permsEdit && $this->table && is_array($currentIdList)) {
                            $entityIdentifiers = 'entityIdentifiers';
                            if ($this->clipNumPane()) {
                                $entityIdentifiers .= ':editList';
                            }
                            $editUri = (string)$this->uriBuilder->buildUriFromRoute('record_edit', ['returnUrl' => $this->listURL()])
                                . '&edit[' . $table . '][{' . $entityIdentifiers . '}]=edit'
                                . '&columnsOnly=' . implode(',', $this->fieldArray);
                            $icon .= '<a class="btn btn-default t3js-record-edit-multiple" href="#"'
                                . ' data-uri="' . htmlspecialchars($editUri) . '"'
                                . ' title="' . htmlspecialchars($lang->getLL('editShownColumns')) . '">'
                                . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
                            $icon = '<div class="btn-group" role="group">' . $icon . '</div>';
                        }
                        // Add an empty entry, so column count fits again after moving this into $icon
                        $theData[$fCol] = '&nbsp;';
                    } else {
                        $icon = $this->spaceIcon;
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
                    } else {
                        // No TCA field, only output the $fCol variable with square brackets []
                        $sortLabel = htmlspecialchars($fCol);
                        $sortLabel = '<i>[' . rtrim(trim($sortLabel), ':') . ']</i>';
                    }

                    if ($this->table && is_array($currentIdList)) {
                        // If the numeric clipboard pads are selected, show duplicate sorting link:
                        if ($this->clipNumPane()) {
                            $theData[$fCol] .= '<a class="btn btn-default" href="' . htmlspecialchars($this->listURL('', '-1') . '&duplicateField=' . $fCol)
                                . '" title="' . htmlspecialchars($lang->getLL('clip_duplicates')) . '">'
                                . $this->iconFactory->getIcon('actions-document-duplicates-select', Icon::SIZE_SMALL)->render() . '</a>';
                        }
                        // If the table can be edited, add link for editing THIS field for all
                        // listed records:
                        if ($this->isEditable($table) && $permsEdit && $GLOBALS['TCA'][$table]['columns'][$fCol]) {
                            $entityIdentifiers = 'entityIdentifiers';
                            if ($this->clipNumPane()) {
                                $entityIdentifiers .= ':editList';
                            }
                            $editUri = (string)$this->uriBuilder->buildUriFromRoute('record_edit', ['returnUrl' => $this->listURL()])
                                . '&edit[' . $table . '][{' . $entityIdentifiers . '}]=edit'
                                . '&columnsOnly=' . $fCol;
                            $iTitle = sprintf($lang->getLL('editThisColumn'), $sortLabel);
                            $theData[$fCol] .= '<a class="btn btn-default t3js-record-edit-multiple" href="#"'
                                . ' data-uri="' . htmlspecialchars($editUri) . '"'
                                . ' title="' . htmlspecialchars($iTitle) . '">'
                                . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
                        }
                        if (strlen($theData[$fCol]) > 0) {
                            $theData[$fCol] = '<div class="btn-group" role="group">' . $theData[$fCol] . '</div> ';
                        }
                    }
                    $theData[$fCol] .= $this->addSortLink($sortLabel, $fCol, $table);
            }
        }
        /*
         * hook:  renderListHeader: Allows to change the contents of columns/cells of the Web>List table headers
         * usage: Above each listed table in Web>List a header row is shown.
         *        Containing the labels of all shown fields and additional icons to create new records for this
         *        table or perform special clipboard tasks like mark and copy all listed records to clipboard, etc.
         */
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof RecordListHookInterface) {
                throw new \UnexpectedValueException($className . ' must implement interface ' . RecordListHookInterface::class, 1195567855);
            }
            $theData = $hookObject->renderListHeader($table, $currentIdList, $theData, $this);
        }

        // Create and return header table row:
        return '<thead>' . $this->addElement(1, $icon, $theData, '', '', '', 'th') . '</thead>';
    }

    /**
     * Get pointer for first element on the page
     *
     * @param int $page Page number starting with 1
     * @param int $iLimit
     * @return int Pointer to first element on the page (starting with 0)
     */
    protected function getPointerForPage($page, int $iLimit)
    {
        return ($page - 1) * $iLimit;
    }

    /**
     * Creates a page browser for tables with many records
     *
     * @param string $renderPart Distinguish between 'top' and 'bottom' part of the navigation (above or below the records)
     * @param int $totalItems
     * @param int $iLimit
     * @param string $table
     * @return string Navigation HTML
     */
    protected function renderListNavigation($renderPart, int $totalItems, int $iLimit, string $table)
    {
        $totalPages = (int)ceil($totalItems / $iLimit);
        // Show page selector if not all records fit into one page
        if ($totalPages <= 1) {
            return '';
        }
        $content = '';
        $listURL = $this->listURL('', $this->table, 'firstElementNumber');
        // 1 = first page
        // 0 = first element
        $currentPage = (int)floor($this->firstElementNumber / $iLimit) + 1;
        // Compile first, previous, next, last and refresh buttons
        if ($currentPage > 1) {
            $labelFirst = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:first'));
            $labelPrevious = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:previous'));
            $first = '<li class="page-item"><a href="' . $listURL . '&pointer=' . $this->getPointerForPage(1, $iLimit) . '" title="' . $labelFirst . '" class="page-link">'
                . $this->iconFactory->getIcon('actions-view-paging-first', Icon::SIZE_SMALL)->render() . '</a></li>';
            $previous = '<li class="page-item"><a href="' . $listURL . '&pointer=' . $this->getPointerForPage($currentPage - 1, $iLimit) . '" title="' . $labelPrevious . '" class="page-link">'
                . $this->iconFactory->getIcon('actions-view-paging-previous', Icon::SIZE_SMALL)->render() . '</a></li>';
        } else {
            $first = '<li class="page-item disabled"><span class="page-link">' . $this->iconFactory->getIcon('actions-view-paging-first', Icon::SIZE_SMALL)->render() . '</span></li>';
            $previous = '<li class="page-item disabled"><span class="page-link">' . $this->iconFactory->getIcon('actions-view-paging-previous', Icon::SIZE_SMALL)->render() . '</span></li>';
        }
        if ($currentPage < $totalPages) {
            $labelNext = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:next'));
            $labelLast = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:last'));
            $next = '<li class="page-item"><a href="' . $listURL . '&pointer=' . $this->getPointerForPage($currentPage + 1, $iLimit) . '" title="' . $labelNext . '" class="page-link">'
                . $this->iconFactory->getIcon('actions-view-paging-next', Icon::SIZE_SMALL)->render() . '</a></li>';
            $last = '<li class="page-item"><a href="' . $listURL . '&pointer=' . $this->getPointerForPage($totalPages, $iLimit) . '" title="' . $labelLast . '" class="page-link">'
                . $this->iconFactory->getIcon('actions-view-paging-last', Icon::SIZE_SMALL)->render() . '</a></li>';
        } else {
            $next = '<li class="page-item disabled"><span class="page-link">' . $this->iconFactory->getIcon('actions-view-paging-next', Icon::SIZE_SMALL)->render() . '</span></li>';
            $last = '<li class="page-item disabled"><span class="page-link">' . $this->iconFactory->getIcon('actions-view-paging-last', Icon::SIZE_SMALL)->render() . '</span></li>';
        }
        $reload = '<li class="page-item"><a href="#" onclick="document.dblistForm.action=' . GeneralUtility::quoteJSvalue($listURL
            . '&pointer=') . '+calculatePointer(document.getElementById(' . GeneralUtility::quoteJSvalue('jumpPage-' . $renderPart)
            . ').value); document.dblistForm.submit(); return true;" title="'
            . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:reload')) . '" class="page-link">'
            . $this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL)->render() . '</a></li>';
        if ($renderPart === 'top') {
            // Add js to traverse a page select input to a pointer value
            $content = '
<script>
/*<![CDATA[*/
	function calculatePointer(page) {
		if (page > ' . $totalPages . ') {
			page = ' . $totalPages . ';
		}
		if (page < 1) {
			page = 1;
		}
		return (page - 1) * ' . $iLimit . ';
	}
/*]]>*/
</script>
';
        }
        $pageNumberInput = '
			<input type="number" min="1" max="' . $totalPages . '" value="' . $currentPage . '" size="3" class="form-control form-control-sm paginator-input" id="jumpPage-' . $renderPart . '" name="jumpPage-'
            . $renderPart . '" onkeyup="if (event.keyCode == 13) { document.dblistForm.action=' . htmlspecialchars(GeneralUtility::quoteJSvalue($listURL . '&pointer='))
            . '+calculatePointer(this.value); document.dblistForm.submit(); } return true;" />
			';
        $pageIndicatorText = sprintf(
            $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:pageIndicator'),
            $pageNumberInput,
            $totalPages
        );
        $pageIndicator = '<li class="page-item"><span class="page-link">' . $pageIndicatorText . '</span></li>';
        if ($totalItems > $this->firstElementNumber + $iLimit) {
            $lastElementNumber = $this->firstElementNumber + $iLimit;
        } else {
            $lastElementNumber = $totalItems;
        }
        $rangeIndicator = '<li class="page-item"><span class="page-link">' . sprintf(
            $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:rangeIndicator'),
            $this->firstElementNumber + 1,
            $lastElementNumber
        ) . '</span></li>';

        $titleColumn = $GLOBALS['TCA'][$table]['ctrl']['label'];
        $data = [
            $titleColumn => $content . '
				<nav class="pagination-wrap">
					<ul class="pagination">
						' . $first . '
						' . $previous . '
						' . $rangeIndicator . '
						' . $pageIndicator . '
						' . $next . '
						' . $last . '
						' . $reload . '
					</ul>
				</nav>
			'
        ];
        return $this->addElement(1, '', $data);
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
            'secondary' => []
        ];

        // Enables to hide the move elements for localized records - doesn't make much sense to perform these options for them
        $isL10nOverlay = $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] != 0;
        if ($table === 'pages') {
            // If the listed table is 'pages' we have to request the permission settings for each page.
            $localCalcPerms = new Permission($backendUser->calcPerms(BackendUtility::getRecord('pages', $row['uid'])));
        } else {
            // If the listed table is not 'pages' we have to request the permission settings from the parent page
            $localCalcPerms = new Permission($backendUser->calcPerms(BackendUtility::getRecord('pages', $row['pid'])));
        }
        $permsEdit = $table === 'pages'
                     && $backendUser->checkLanguageAccess((int)$row[$GLOBALS['TCA']['pages']['ctrl']['languageField']])
                     && $localCalcPerms->editPagePermissionIsGranted()
                     || $table !== 'pages'
                        && $localCalcPerms->editContentPermissionIsGranted()
                        && $backendUser->recordEditAccessInternals($table, $row);
        $permsEdit = $this->overlayEditLockPermissions($table, $row, $permsEdit);

        // "Show" link (only pages and tt_content elements)
        $tsConfig = BackendUtility::getPagesTSconfig($this->id)['mod.']['web_list.'] ?? [];
        if (
            ($table === 'pages' && !in_array($row['doktype'] ?? null, $this->getNoViewWithDokTypes($tsConfig)))
            || $table === 'tt_content'
        ) {
            if (!$isDeletePlaceHolder) {
                $attributes = $this->getPreviewUriBuilder($table, $row)->serializeDispatcherAttributes();
                $viewAction = '<a class="btn btn-default" href="#" ' . $attributes
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
                        $row['uid'] => 'edit'
                    ]
                ]
            ];
            $iconIdentifier = 'actions-open';
            if ($table === 'pages') {
                // Disallow manual adjustment of the language field for pages
                $params['overrideVals']['pages']['sys_language_uid'] = (int)$row[$GLOBALS['TCA']['pages']['ctrl']['languageField']];
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
            $viewBigAction = '<a class="btn btn-default" href="#" ' . $this->createShowItemTagAttributes($table . ',' . (int)$row['uid']) . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('showInfo')) . '">'
                . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render() . '</a>';
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
                $moveAction = '<a class="btn btn-default" href="' . htmlspecialchars($url) . '" title="' . $linkTitleLL . '">' . $icon->render() . '</a>';
            }
            $this->addActionToCellGroup($cells, $moveAction, 'move');
        }

        // If the table is NOT a read-only table, then show these links:
        if ($this->isEditable($table)) {
            // "Revert" link (history/undo)
            if ((bool)\trim($userTsConfig['options.']['showHistory.'][$table] ?? $userTsConfig['options.']['showHistory'] ?? '1')) {
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
                        'tx_beuser_system_beusertxpermission' => [
                            'action' => 'edit'
                        ],
                        'returnUrl' => $this->listURL()
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
            if (($GLOBALS['TCA'][$table]['ctrl']['sortby'] || $GLOBALS['TCA'][$table]['ctrl']['useColumnsForDefaultValues']) && $permsEdit) {
                $neededPermission = $table === 'pages' ? Permission::PAGE_NEW : Permission::CONTENT_EDIT;
                if ($this->calcPerms->isGranted($neededPermission)) {
                    if ($isL10nOverlay || $isDeletePlaceHolder) {
                        $this->addActionToCellGroup($cells, $this->spaceIcon, 'new');
                    } elseif ($this->showNewRecLink($table)) {
                        $params = [
                            'edit' => [
                                $table => [
                                    (0-($row['_MOVE_PLH'] ? $row['_MOVE_PLH_uid'] : $row['uid'])) => 'new'
                                ]
                            ],
                            'returnUrl' => $this->listURL()
                        ];
                        $icon = ($table === 'pages' ? $this->iconFactory->getIcon('actions-page-new', Icon::SIZE_SMALL) : $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL));
                        $titleLabel = 'new';
                        if ($GLOBALS['TCA'][$table]['ctrl']['sortby']) {
                            $titleLabel .= ($table === 'pages' ? 'Page' : 'Record');
                        }
                        $newLink = $this->uriBuilder->buildUriFromRoute('record_edit', $params);
                        $newAction = '<a class="btn btn-default" href="' . htmlspecialchars($newLink) . '" title="' . htmlspecialchars($this->getLanguageService()->getLL($titleLabel)) . '">'
                            . $icon->render() . '</a>';
                        $this->addActionToCellGroup($cells, $newAction, 'new');
                    }
                }
            }

            // "Up/Down" links
            if ($permsEdit && $GLOBALS['TCA'][$table]['ctrl']['sortby'] && !$this->sortField && !$this->searchLevels) {
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

                if (!$isL10nOverlay && !$isDeletePlaceHolder && $this->currentTable['next'][$row['uid']]) {
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

            // "Hide/Unhide" links:
            $hiddenField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
            if (!empty($GLOBALS['TCA'][$table]['columns'][$hiddenField])
                && (empty($GLOBALS['TCA'][$table]['columns'][$hiddenField]['exclude']) || $backendUser->check('non_exclude_fields', $table . ':' . $hiddenField))
            ) {
                if (!$permsEdit || $isDeletePlaceHolder || $this->isRecordCurrentBackendUser($table, $row)) {
                    $hideAction = $this->spaceIcon;
                } else {
                    $hideTitle = htmlspecialchars($this->getLanguageService()->getLL('hide' . ($table === 'pages' ? 'Page' : '')));
                    $unhideTitle = htmlspecialchars($this->getLanguageService()->getLL('unHide' . ($table === 'pages' ? 'Page' : '')));
                    if ($row[$hiddenField]) {
                        $params = 'data[' . $table . '][' . $rowUid . '][' . $hiddenField . ']=0';
                        $hideAction = '<a class="btn btn-default t3js-record-hide" data-state="hidden" href="#"'
                                        . ' data-params="' . htmlspecialchars($params) . '"'
                                        . ' title="' . $unhideTitle . '"'
                                        . ' data-toggle-title="' . $hideTitle . '">'
                                        . $this->iconFactory->getIcon('actions-edit-unhide', Icon::SIZE_SMALL)->render() . '</a>';
                    } else {
                        $params = 'data[' . $table . '][' . $rowUid . '][' . $hiddenField . ']=1';
                        $hideAction = '<a class="btn btn-default t3js-record-hide" data-state="visible" href="#"'
                                        . ' data-params="' . htmlspecialchars($params) . '"'
                                        . ' title="' . $hideTitle . '"'
                                        . ' data-toggle-title="' . $unhideTitle . '">'
                                        . $this->iconFactory->getIcon('actions-edit-hide', Icon::SIZE_SMALL)->render() . '</a>';
                    }
                }
                $this->addActionToCellGroup($cells, $hideAction, 'hide');
            }

            // "Delete" link:
            $disableDelete = (bool)\trim($userTsConfig['options.']['disableDelete.'][$table] ?? $userTsConfig['options.']['disableDelete'] ?? '0');
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
                $deleteAction = '<a class="btn btn-default t3js-record-delete" href="#" '
                                . ' data-button-ok-text="' . htmlspecialchars($linkTitle) . '"'
                                . ' data-l10parent="' . ($l10nParentField ? htmlspecialchars($row[$l10nParentField]) : '') . '"'
                                . ' data-params="' . htmlspecialchars($params) . '" data-title="' . htmlspecialchars($title) . '"'
                                . ' data-message="' . htmlspecialchars($warningText) . '" title="' . $linkTitle . '"'
                                . '>' . $icon . '</a>';
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
                        $moveLeftAction = '<a class="btn btn-default" href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('prevLevel')) . '">'
                            . $this->iconFactory->getIcon('actions-move-left', Icon::SIZE_SMALL)->render() . '</a>';
                        $this->addActionToCellGroup($cells, $moveLeftAction, 'moveLeft');
                    } else {
                        $this->addActionToCellGroup($cells, $this->spaceIcon, 'moveLeft');
                    }
                }
                // Down (Paste as subpage to the page right above)
                if (!$isL10nOverlay && !$isDeletePlaceHolder && $this->currentTable['prevUid'][$row['uid']]) {
                    $localCalcPerms = new Permission($backendUser->calcPerms(BackendUtility::getRecord('pages', $this->currentTable['prevUid'][$row['uid']])));
                    if ($localCalcPerms->createPagePermissionIsGranted()) {
                        $params = [];
                        $params['redirect'] = $this->listURL();
                        $params['cmd'][$table][$row['uid']]['move'] = $this->currentTable['prevUid'][$row['uid']];
                        $url = (string)$this->uriBuilder->buildUriFromRoute('tce_db', $params);
                        $moveRightAction = '<a class="btn btn-default" href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('nextLevel')) . '">'
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

        /*
         * hook:  makeControl: Allows to change control icons of records in list-module
         * usage: This hook method gets passed the current $cells array as third parameter.
         *        This array contains values for the icons/actions generated for each record in Web>List.
         *        Each array entry is accessible by an index-key.
         *        The order of the icons is depending on the order of those array entries.
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

        $output = '<!-- CONTROL PANEL: ' . $table . ':' . $row['uid'] . ' -->';
        foreach ($cells as $classification => $actions) {
            $visibilityClass = ($classification !== 'primary' && !$this->moduleData['bigControlPanel'] ? 'collapsed' : 'expanded');
            if ($visibilityClass === 'collapsed') {
                $cellOutput = '';
                foreach ($actions as $action) {
                    $cellOutput .= $action;
                }
                $output .= ' <div class="btn-group">' .
                    '<span id="actions_' . $table . '_' . $row['uid'] . '" class="btn-group collapse collapse-horizontal width">' . $cellOutput . '</span>' .
                    '<a href="#actions_' . $table . '_' . $row['uid'] . '" class="btn btn-default collapsed" data-bs-toggle="collapse" aria-expanded="false"><span class="t3-icon fa fa-ellipsis-h"></span></a>' .
                    '</div>';
            } else {
                $output .= ' <div class="btn-group" role="group">' . implode('', $actions) . '</div>';
            }
        }

        return $output;
    }

    /**
     * Creates the clipboard panel for a single record in the listing.
     *
     * @param string $table The table
     * @param mixed[] $row The record for which to make the clipboard panel.
     * @throws \UnexpectedValueException
     * @return string HTML table with the clipboard panel (unless disabled)
     */
    public function makeClip($table, $row)
    {
        // Return blank, if disabled:
        if (!$this->moduleData['clipBoard'] || !$this->isEditable($table) || $this->isRecordDeletePlaceholder($row)) {
            return '';
        }
        $cells = [];
        $cells['pasteAfter'] = ($cells['pasteInto'] = $this->spaceIcon);
        // Enables to hide the copy, cut and paste icons for localized records - doesn't make much sense to perform these options for them
        $isL10nOverlay = $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] != 0;
        $isRecordDeletePlaceholder = $this->isRecordDeletePlaceholder($row);
        // Return blank, if disabled:
        // Whether a numeric clipboard pad is active or the normal pad we will see different content of the panel:
        // For the "Normal" pad:
        if ($this->clipObj->current === 'normal') {
            // Show copy/cut icons:
            $isSel = (string)$this->clipObj->isSelected($table, $row['uid']);
            if ($isL10nOverlay || $isRecordDeletePlaceholder) {
                $cells['copy'] = $this->spaceIcon;
                $cells['cut'] = $this->spaceIcon;
            } else {
                $copyIcon = $this->iconFactory->getIcon('actions-edit-copy', Icon::SIZE_SMALL);
                $cutIcon = $this->iconFactory->getIcon('actions-edit-cut', Icon::SIZE_SMALL);

                if ($isSel === 'copy') {
                    $copyIcon = $this->iconFactory->getIcon('actions-edit-copy-release', Icon::SIZE_SMALL);
                } elseif ($isSel === 'cut') {
                    $cutIcon = $this->iconFactory->getIcon('actions-edit-cut-release', Icon::SIZE_SMALL);
                }

                $cells['copy'] = '<a class="btn btn-default" href="'
                    . htmlspecialchars($this->clipObj->selUrlDB(
                        $table,
                        $row['uid'],
                        1,
                        $isSel === 'copy',
                        ['returnUrl' => $this->listURL()]
                    ))
                    . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.copy')) . '">'
                    . $copyIcon->render() . '</a>';

                // Check permission to cut page or content
                if ($table === 'pages') {
                    $localCalcPerms = new Permission($this->getBackendUserAuthentication()->calcPerms(BackendUtility::getRecord('pages', $row['uid'])));
                    $permsEdit = $localCalcPerms->editPagePermissionIsGranted();
                } else {
                    $permsEdit = $this->calcPerms->editContentPermissionIsGranted() && $this->getBackendUserAuthentication()->recordEditAccessInternals($table, $row);
                }
                $permsEdit = $this->overlayEditLockPermissions($table, $row, $permsEdit);
                if ($permsEdit) {
                    $cells['cut'] = '<a class="btn btn-default" href="'
                        . htmlspecialchars($this->clipObj->selUrlDB(
                            $table,
                            $row['uid'],
                            0,
                            $isSel === 'cut',
                            ['returnUrl' => $this->listURL()]
                        ))
                        . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.cut')) . '">'
                        . $cutIcon->render() . '</a>';
                } else {
                    $cells['cut'] = $this->spaceIcon;
                }
            }
        } else {
            // For the numeric clipboard pads (showing checkboxes where one can select elements on/off)
            // Setting name of the element in ->CBnames array:
            $n = $table . '|' . $row['uid'];
            $this->CBnames[] = $n;
            // Check if the current element is selected and if so, prepare to set the checkbox as selected:
            $checked = $this->clipObj->isSelected($table, $row['uid']) ? 'checked="checked" ' : '';
            // If the "duplicateField" value is set then select all elements which are duplicates...
            if ($this->duplicateField && isset($row[$this->duplicateField])) {
                $checked = '';
                if (in_array($row[$this->duplicateField], $this->duplicateStack)) {
                    $checked = 'checked="checked" ';
                }
                $this->duplicateStack[] = $row[$this->duplicateField];
            }
            // Adding the checkbox to the panel:
            $cells['select'] = $isL10nOverlay
                ? $this->spaceIcon
                : '<input type="hidden" name="CBH[' . $n . ']" value="0" /><label class="btn btn-default btn-checkbox"><input type="checkbox"'
                    . ' name="CBC[' . $n . ']" value="1" ' . $checked . '/><span class="t3-icon fa"></span></label>';
        }
        // Now, looking for selected elements from the current table:
        $elFromTable = $this->clipObj->elFromTable($table);
        if (!empty($elFromTable) && $GLOBALS['TCA'][$table]['ctrl']['sortby'] && !$isRecordDeletePlaceholder) {
            // IF elements are found, they can be individually ordered and are not locked by editlock, then add a "paste after" icon:
            $cells['pasteAfter'] = $isL10nOverlay || !$this->overlayEditLockPermissions($table, $row)
                ? $this->spaceIcon
                : '<a class="btn btn-default t3js-modal-trigger"'
                    . ' href="' . htmlspecialchars($this->clipObj->pasteUrl($table, -$row['uid'])) . '"'
                    . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_pasteAfter')) . '"'
                    . ' data-title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_pasteAfter')) . '"'
                    . ' data-bs-content="' . htmlspecialchars($this->clipObj->confirmMsgText($table, $row, 'after', $elFromTable)) . '"'
                    . ' data-severity="warning">'
                    . $this->iconFactory->getIcon('actions-document-paste-after', Icon::SIZE_SMALL)->render() . '</a>';
        }
        // Now, looking for elements in general:
        $elFromTable = $this->clipObj->elFromTable('');
        if ($table === 'pages' && !$isL10nOverlay && !empty($elFromTable) && !$isRecordDeletePlaceholder) {
            $cells['pasteInto'] = '<a class="btn btn-default t3js-modal-trigger"'
                . ' href="' . htmlspecialchars($this->clipObj->pasteUrl('', $row['uid'])) . '"'
                . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_pasteInto')) . '"'
                . ' data-title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_pasteInto')) . '"'
                . ' data-bs-content="' . htmlspecialchars($this->clipObj->confirmMsgText($table, $row, 'into', $elFromTable)) . '"'
                . ' data-severity="warning">'
                . $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)->render() . '</a>';
        }
        /*
         * hook:  makeClip: Allows to change clip-icons of records in list-module
         * usage: This hook method gets passed the current $cells array as third parameter.
         *        This array contains values for the clipboard icons generated for each record in Web>List.
         *        Each array entry is accessible by an index-key.
         *        The order of the icons is depending on the order of those array entries.
         */
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['actions'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof RecordListHookInterface) {
                throw new \UnexpectedValueException($className . ' must implement interface ' . RecordListHookInterface::class, 1195567845);
            }
            $cells = $hookObject->makeClip($table, $row, $cells, $this);
        }
        return '<div class="btn-group" role="group">' . implode('', $cells) . '</div>';
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
            $possibleTranslations = array_map(static function ($siteLanguage) { return $siteLanguage->getLanguageId(); }, $this->languagesAllowedForUser);
            $possibleTranslations = array_filter($possibleTranslations, static function ($languageUid) { return $languageUid > 0;});
        }

        // Traverse page translations and add icon for each language that does NOT yet exist and is included in site configuration:
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
                            'returnUrl' => $this->listURL()
                        ]
                );
                $params = [];
                $params['redirect'] = $redirectUrl;
                $params['cmd'][$table][$row['uid']]['localize'] = $lUid_OnPage;
                $href = (string)$this->uriBuilder->buildUriFromRoute('tce_db', $params);
                $language = BackendUtility::getRecord('sys_language', $lUid_OnPage, 'title');
                if ($this->languageIconTitles[$lUid_OnPage]['flagIcon']) {
                    $lC = $this->iconFactory->getIcon($this->languageIconTitles[$lUid_OnPage]['flagIcon'], Icon::SIZE_SMALL)->render();
                } else {
                    $lC = $this->languageIconTitles[$lUid_OnPage]['title'];
                }
                $lC = '<a href="' . htmlspecialchars($href) . '" title="'
                        . htmlspecialchars($language['title']) . '" class="btn btn-default t3js-action-localize">'
                        . $lC . '</a> ';
                $out .= $lC;
            }
        }
        return $out;
    }

    /**
     * Creates a checkbox list for selecting fields to display from a table:
     *
     * @param string $table Table name
     * @return string HTML table with the selector check box (name: displayFields['.$table.'][])
     */
    public function fieldSelectBox($table)
    {
        $lang = $this->getLanguageService();
        // Load already selected fields, if any:
        $setFields = (array)($this->setFields[$table]?? []);
        // Request fields from table:
        $fields = $this->makeFieldList($table, false, true);
        // Add pseudo "control" fields
        $fields[] = '_PATH_';
        $fields[] = '_REF_';
        // Create a checkbox for each field:
        $checkboxes = [];
        $checkAllChecked = true;
        $tsConfig = BackendUtility::getPagesTSconfig($this->id);
        $tsConfigOfTable = is_array($tsConfig['TCEFORM.'][$table . '.']) ? $tsConfig['TCEFORM.'][$table . '.'] : null;
        foreach ($fields as $fieldName) {
            // Hide field if hidden
            if ($tsConfigOfTable && is_array($tsConfigOfTable[$fieldName . '.']) && isset($tsConfigOfTable[$fieldName . '.']['disabled']) && (int)$tsConfigOfTable[$fieldName . '.']['disabled'] === 1) {
                continue;
            }
            // Determine, if checkbox should be checked
            if (in_array($fieldName, $setFields, true) || $fieldName === $GLOBALS['TCA'][$table]['ctrl']['label']) {
                $checked = true;
            } else {
                $checkAllChecked = false;
                $checked = false;
            }

            // Field label
            $fieldTSConfig = [];
            $fieldLabel = '';
            if (isset($tsConfigOfTable[$fieldName . '.'])
                && is_array($tsConfigOfTable[$fieldName . '.'])
            ) {
                $fieldTSConfig = $tsConfigOfTable[$fieldName . '.'];
            }
            if (!empty($fieldTSConfig['label'])) {
                $fieldLabel = $fieldTSConfig['label'];
            }
            if (!empty($fieldTSConfig['label.'][$lang->lang])) {
                $fieldLabel = $fieldTSConfig['label.'][$lang->lang];
            }

            $fieldLabel = $fieldLabel ?: BackendUtility::getItemLabel($table, $fieldName);
            $checkboxes[] = [
                'fieldName' => $fieldName,
                'checked' => $checked,
                'disabled' => ($fieldName === $GLOBALS['TCA'][$table]['ctrl']['label']),
                'fieldLabel' => $fieldLabel
            ];
        }
        // Table with the field selector
        return $this->getFluidTemplateObject('FieldSelectBox.html')
            ->assignMultiple([
                'formUrl' => $this->listURL(),
                'table' => $table,
                'allChecked' => $checkAllChecked,
                'checkboxes' => $checkboxes
            ])
            ->render();
    }

    /*********************************
     *
     * Helper functions
     *
     *********************************/
    /**
     * Creates a link around $string. The link contains an onclick action
     * which submits the script with some clipboard action.
     * Currently, this is used for setting elements / delete elements.
     *
     * @param string $string The HTML content to link (image/text)
     * @param string $table Table name
     * @param string $cmd Clipboard command (eg. "setCB" or "delete")
     * @param string $warning Warning text, if any ("delete" uses this for confirmation
     * @param string $title title attribute for the anchor
     * @return string HTML <a> tag wrapped link.
     */
    public function linkClipboardHeaderIcon($string, $table, $cmd, $warning = '', $title = '')
    {
        $jsCode = 'document.dblistForm.cmd.value=' . GeneralUtility::quoteJSvalue($cmd)
            . ';document.dblistForm.cmd_table.value='
            . GeneralUtility::quoteJSvalue($table)
            . ';document.dblistForm.submit();';

        $attributes = [];
        if ($title !== '') {
            $attributes['title'] = $title;
        }
        if ($warning) {
            $attributes['class'] = 'btn btn-default t3js-modal-trigger';
            $attributes['data-href'] = 'javascript:' . $jsCode;
            $attributes['data-severity'] = 'warning';
            $attributes['data-title'] = $title;
            $attributes['data-bs-content'] = $warning;
        } else {
            $attributes['class'] = 'btn btn-default';
            $attributes['onclick'] = $jsCode . 'return false;';
        }

        $attributesString = '';
        foreach ($attributes as $key => $value) {
            $attributesString .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        return '<a href="#" ' . $attributesString . '>' . $string . '</a>';
    }

    /**
     * Returns TRUE if a numeric clipboard pad is selected/active
     *
     * @return bool
     */
    public function clipNumPane()
    {
        return $this->showClipboard && $this->noControlPanels === false && $this->csvOutput === false && $this->clipObj->current !== 'normal';
    }

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
        if ($field === '_CONTROL_' || $field === '_LOCALIZATION_' || $field === '_CLIPBOARD_' || $field === '_REF_' || $this->disableSingleTableView) {
            return $code;
        }
        // If "_PATH_" (showing record path) is selected, force sorting by pid field (will at least group the records!)
        if ($field === '_PATH_') {
            $field = 'pid';
        }
        //	 Create the sort link:
        $sortUrl = $this->listURL('', '-1', 'sortField,sortRev,table,firstElementNumber') . '&table=' . $table
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

    /************************************
     *
     * CSV related functions
     *
     ************************************/

    /**
     * Add header line with field names as CSV line
     */
    protected function addHeaderRowToCSV()
    {
        $fieldArray = array_combine($this->fieldArray, $this->fieldArray);
        $fieldArray = is_array($fieldArray) ? $fieldArray : [];
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][__CLASS__]['customizeCsvHeader'] ?? [];
        if (!empty($hooks)) {
            $hookParameters = [
                'fields' => &$fieldArray
            ];
            foreach ($hooks as $hookFunction) {
                GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
            }
        }
        $this->setCsvRow($fieldArray);
    }

    /**
     * Adds input row of values to the internal csvLines array as a CSV formatted line
     *
     * @param mixed[] $csvRow Array with values to be listed.
     */
    public function setCsvRow($csvRow)
    {
        $csvRow = array_intersect_key($csvRow, array_flip($this->fieldArray));
        $csvDelimiter = $this->modTSconfig['properties']['csvDelimiter'] ?? ',';
        $csvQuote = $this->modTSconfig['properties']['csvQuote'] ?? '"';
        $this->csvLines[] = CsvUtility::csvValues($csvRow, $csvDelimiter, $csvQuote);
    }

    /**
     * Compiles the internal csvLines array to a csv-string and outputs it to the browser.
     *
     * @param string $prefix Filename prefix:
     */
    public function outputCSV($prefix)
    {
        // Setting filename:
        $filename = $prefix . '_' . date('dmy-Hi') . '.csv';
        // Creating output header:
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $filename);
        // Cache-Control header is needed here to solve an issue with browser IE and
        // versions lower than 9. See for more information: http://support.microsoft.com/kb/323308
        header("Cache-Control: ''");
        // Printing the content of the CSV lines:
        echo implode(CRLF, $this->csvLines);
        // Exits:
        die;
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
                'view', 'edit', 'hide', 'delete', 'stat'
            ],
            'secondary' => [
                'viewBig', 'history', 'perms', 'new', 'move', 'moveUp', 'moveDown', 'moveLeft', 'moveRight', 'version'
            ]
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
        return $table === 'be_users' && (int)$row['uid'] === $this->getBackendUserAuthentication()->user['uid'];
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
        return !$GLOBALS['TCA'][$table]['ctrl']['readOnly']
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
                return $table === 'pages' ? true : !$this->pageRow['editlock'];
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
     * Check whether or not the current backend user is an admin or the current page is
     * locked by editlock.
     *
     * @return bool
     */
    protected function editLockPermissions()
    {
        return $this->getBackendUserAuthentication()->isAdmin() || !$this->pageRow['editlock'];
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

        // If there is a current link to a record, set the current link uid and get the table name from the link handler configuration
        $currentLinkValue = isset($this->overrideUrlParameters['P']['currentValue']) ? trim($this->overrideUrlParameters['P']['currentValue']) : '';
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
        $this->languageIconTitles = $this->translateTools->getSystemLanguages($this->id);
    }

    /**
     * Traverses the table(s) to be listed and renders the output code for each.
     *
     * @return string Rendered HTML
     */
    public function generateList(): string
    {
        $output = '';
        // Set page record in header
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
            $output .= $this->getTable($tableName, $this->id);
        }
        return $output;
    }

    /**
     * Creates the search box
     *
     * @return string HTML for the search box
     */
    public function getSearchBox(): string
    {
        return $this->getFluidTemplateObject('Search.html')
            ->assignMultiple([
                'formUrl' => $this->listURL('', '-1', 'firstElementNumber,search_field'),
                'searchLevelsFromTSconfig' => (array)(BackendUtility::getPagesTSconfig($this->id)['mod.']['web_list.']['searchLevel.']['items.'] ?? []),
                'selectedSearchLevel' => $this->searchLevels,
                'searchString' => $this->searchString,
                'showLimit' => $this->showLimit
            ])
            ->render();
    }

    /**
     * Setting the field names to display in extended list.
     * Sets the internal variable $this->setFields
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
            $queryBuilder->andWhere([QueryHelper::stripLogicalOperatorPrefix($addWhere)]);
        }
        $fields = GeneralUtility::trimExplode(',', $selFieldList, true);
        if ($fields === []) {
            $fields = ['*'];
        }
        // Additional constraints from getTable
        if ($GLOBALS['TCA'][$table]['ctrl']['languageField']
            && $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']) {
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
            if ($this->sortField && in_array($this->sortField, $this->makeFieldList($table, true))) {
                $queryBuilder->orderBy($this->sortField, $this->sortRev ? 'DESC' : 'ASC');
            } else {
                $orderBy = $GLOBALS['TCA'][$table]['ctrl']['sortby'] ?: $GLOBALS['TCA'][$table]['ctrl']['default_sortby'];
                $orderBys = QueryHelper::parseOrderBy((string)$orderBy);
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
            'maxResults' => $maxResult
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
                $evalRules = $fieldConfig['eval'] ?: '';
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
                $evalRules = $fieldConfig['eval'] ?: '';
                $searchConstraint = $expressionBuilder->andX(
                    $expressionBuilder->comparison(
                        'LOWER(' . $queryBuilder->castFieldToTextType($fieldName) . ')',
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
                    || $fieldType === 'slug'
                    || $fieldType === 'input' && (!$evalRules || !preg_match('/\b(?:date|time|int)\b/', $evalRules))
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
            return '<a href="' . htmlspecialchars(
                $this->listURL('', $table, 'firstElementNumber')
            ) . '">' . $code . '</a>';
        }
        return '<a href="' . htmlspecialchars(
            $this->listURL('', '', 'sortField,sortRev,table,firstElementNumber')
        ) . '">' . $code . '</a>';
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
            $code = htmlspecialchars($code, ENT_QUOTES, 'UTF-8', false);
            if ($code != htmlspecialchars($origCode)) {
                $code = '<span title="' . htmlspecialchars(
                    $origCode,
                    ENT_QUOTES,
                    'UTF-8',
                    false
                ) . '">' . $code . '</span>';
            } else {
                $code = '<span title="' . $code . '">' . $code . '</span>';
            }
        }
        switch ((string)$this->clickTitleMode) {
            case 'edit':
                // If the listed table is 'pages' we have to request the permission settings for each page:
                if ($table === 'pages') {
                    $localCalcPerms = new Permission($this->getBackendUserAuthentication()->calcPerms(
                        BackendUtility::getRecord('pages', $row['uid'])
                    ));
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
                                $row['uid'] => 'edit'
                            ]
                        ],
                        'returnUrl' => $this->listURL()
                    ];
                    $editLink = $this->uriBuilder->buildUriFromRoute('record_edit', $params);
                    $code = '<a href="' . htmlspecialchars($editLink) . '" title="' . htmlspecialchars($lang->getLL('edit')) . '">' . $code . '</a>';
                }
                break;
            case 'show':
                // "Show" link (only pages and tt_content elements)
                if ($table === 'pages' || $table === 'tt_content') {
                    $attributes = $this->getPreviewUriBuilder($table, $row)->serializeDispatcherAttributes();
                    $code = '<a href="#" ' . $attributes . ' title="' . htmlspecialchars(
                        $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage')
                    ) . '">' . $code . '</a>';
                }
                break;
            case 'info':
                // "Info": (All records)
                $code = '<a href="#" ' . $this->createShowItemTagAttributes($table . ',' . (int)$row['uid'])
                    . ' title="' . htmlspecialchars($lang->getLL('showInfo')) . '">' . $code . '</a>';
                break;
            default:
                // Output the label now:
                if ($table === 'pages') {
                    $code = '<a href="' . htmlspecialchars(
                        $this->listURL((string)$uid, '', 'firstElementNumber')
                    ) . '" onclick="setHighlight(' . (int)$uid . ')">' . $code . '</a>';
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
     * Fixed GPvars are id, table, returnUrl, search_field, search_levels and showLimit
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
            $url = (string)$this->uriBuilder->buildUriFromRoutePath($routePath, $urlParameters);
        } else {
            $url = GeneralUtility::getIndpEnv('SCRIPT_NAME') . HttpUtility::buildQueryString($urlParameters, '?');
        }
        return $url;
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
        if (is_array($GLOBALS['TCA'][$table]) && isset($GLOBALS['TCA'][$table]['columns']) && is_array(
            $GLOBALS['TCA'][$table]['columns']
        )) {
            if (isset($GLOBALS['TCA'][$table]['columns']) && is_array($GLOBALS['TCA'][$table]['columns'])) {
                // Traverse configured columns and add them to field array, if available for user.
                foreach ($GLOBALS['TCA'][$table]['columns'] as $fN => $fieldValue) {
                    if ($fieldValue['config']['type'] === 'none') {
                        // Never render or fetch type=none fields from db
                        continue;
                    }
                    if ($dontCheckUser
                        || (!$fieldValue['exclude'] || $backendUser->check('non_exclude_fields', $table . ':' . $fN)) && $fieldValue['config']['type'] !== 'passthrough'
                    ) {
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
                    if (BackendUtility::isTableWorkspaceEnabled($table)) {
                        $fieldListArr[] = 't3ver_state';
                        $fieldListArr[] = 't3ver_wsid';
                        $fieldListArr[] = 't3ver_oid';
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
                    $queryBuilder->createNamedParameter($pageRecord['pid'], \PDO::PARAM_INT)
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
     * @param int $h Is an integer >=0 and denotes how tall an element is. Set to '0' makes a half line, -1 = full line, set to 1 makes a 'join' and above makes 'line'
     * @param string $icon Is the <img>+<a> of the record. If not supplied the first 'join'-icon will be a 'line' instead
     * @param array $data Is the data array, record with the fields. Notice: These fields are (currently) NOT htmlspecialchar'ed before being wrapped in <td>-tags
     * @param string $rowParams Is insert in the <tr>-tags. Must carry a ' ' as first character
     * @param string $_ OBSOLETE - NOT USED ANYMORE. $lMargin is the leftMargin (int)
     * @param string $_2 OBSOLETE - NOT USED ANYMORE. Is the HTML <img>-tag for an alternative 'gfx/ol/line.gif'-icon (used in the top)
     * @param string $colType Defines the tag being used for the columns. Default is td.
     *
     * @return string HTML content for the table row
     */
    public function addElement($h, $icon, $data, $rowParams = '', $_ = '', $_2 = '', $colType = 'td')
    {
        $colType = ($colType === 'th') ? 'th' : 'td';
        // Start up:
        $l10nParent = isset($data['_l10nparent_']) ? (int)$data['_l10nparent_'] : 0;
        $out = '
		<!-- Element, begin: -->
		<tr ' . $rowParams . ' data-uid="' . (int)$data['uid'] . '" data-l10nparent="' . $l10nParent . '">';
        // Show icon and lines
        $out .= '
			<' . $colType . ' class="col-icon nowrap">';
        if (!$h) {
            $out .= '&nbsp;';
        } else {
            for ($a = 0; $a < $h; $a++) {
                if (!$a) {
                    if ($icon) {
                        $out .= $icon;
                    }
                }
            }
        }
        $out .= '</' . $colType . '>';

        // Init rendering.
        $colsp = '';
        $lastKey = '';
        $c = 0;
        $ccount = 0;
        // __label is used as the label key to circumvent problems with uid used as label (see #67756)
        // as it was introduced later on, check if it really exists before using it
        $fields = $this->fieldArray;
        if ($colType === 'td' && array_key_exists('__label', $data)) {
            $fields[0] = '__label';
        }
        // Traverse field array which contains the data to present:
        foreach ($fields as $vKey) {
            if (isset($data[$vKey])) {
                if ($lastKey) {
                    $cssClass = $this->addElement_tdCssClass[$lastKey];
                    $out .= '
						<' . $colType . ' class="' . $cssClass . ' nowrap' . '"' . $colsp . '>' . $data[$lastKey] . '</' . $colType . '>';
                }
                $lastKey = $vKey;
                $c = 1;
                $ccount++;
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
            $cssClass = $this->addElement_tdCssClass[$lastKey];
            $out .= '
				<' . $colType . ' class="' . $cssClass . ' nowrap' . '"' . $colsp . '>' . $data[$lastKey] . '</' . $colType . '>';
        }
        // End row
        $out .= '
		</tr>';
        // Return row.
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
        $localizationParentField = $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'];
        $languageField = $GLOBALS['TCA']['pages']['ctrl']['languageField'];
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
            ->execute();
        $allowedTranslationsOnPage = [];
        while ($row = $result->fetch()) {
            $allowedTranslationsOnPage[] = (int)$row[$languageField];
        }
        return $allowedTranslationsOnPage;
    }

    /**
     * Return the icon for the language
     *
     * @param string $table
     * @param array $row
     * @param bool $addAsAdditionalText If set to true, only the flag is returned
     * @return string Language icon
     */
    public function languageFlag(string $table, array $row, bool $addAsAdditionalText = true): string
    {
        $out = '';
        $languageUid = (int)$row[$GLOBALS['TCA'][$table]['ctrl']['languageField']];
        $title = htmlspecialchars($this->languageIconTitles[$languageUid]['title']);
        $indent = ($table !== 'pages' && $this->isLocalized($table, $row)) ? ' style="margin-left: 16px;"' : '';
        if ($this->languageIconTitles[$languageUid]['flagIcon']) {
            $out .= '<span title="' . $title . '"' . $indent . '>' . $this->iconFactory->getIcon(
                $this->languageIconTitles[$languageUid]['flagIcon'],
                Icon::SIZE_SMALL
            )->render() . '</span>';
            if (!$addAsAdditionalText) {
                return $out;
            }
            $out .= '&nbsp;';
        }
        $out .= $title;
        return $out;
    }

    /**
     * Generates HTML code for a Reference tooltip out of
     * sys_refindex records you hand over
     */
    protected function generateReferenceToolTip(string $table, int $uid): string
    {
        $numberOfReferences = $this->getReferenceCount($table, $uid);
        if (!$numberOfReferences) {
            $htmlCode = '-';
        } else {
            $htmlCode = '<a href="#"';
            $htmlCode .= ' ' . $this->createShowItemTagAttributes($table . ',' . $uid);

            $htmlCode .= ' title="' . htmlspecialchars(
                $this->getLanguageService()->sL(
                    'LLL:EXT:backend/Resources/Private/Language/locallang.xlf:show_references'
                ) . ' (' . $numberOfReferences . ')'
            ) . '">';
            $htmlCode .= $numberOfReferences;
            $htmlCode .= '</a>';
        }
        return $htmlCode;
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
     * Returns the shortcut title for the current page
     *
     * @param array $arguments
     * @return string
     */
    protected function getShortcutTitle(array $arguments): string
    {
        $pageTitle = '';
        $tableTitle = '';
        $languageService = $this->getLanguageService();

        if (isset($arguments['table'])) {
            $tableTitle = ': ' . $languageService->sL($GLOBALS['TCA'][$arguments['table']]['ctrl']['title'] ?? '') ?: $arguments['table'];
        }

        if ($this->pageRow !== []) {
            $pageTitle = BackendUtility::getRecordTitle('pages', $this->pageRow);
        }

        return trim(sprintf(
            $languageService->sL('LLL:EXT:recordlist/Resources/Private/Language/locallang.xlf:shortcut.title'),
            $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:mlang_tabs_tab'),
            $tableTitle,
            $pageTitle,
            $this->id
        ));
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
            $noViewDokTypes = GeneralUtility::trimExplode(',', $tsConfig['noViewWithDokTypes'], true);
        } else {
            $noViewDokTypes = [
                PageRepository::DOKTYPE_SYSFOLDER,
                PageRepository::DOKTYPE_RECYCLER
            ];
        }

        return $noViewDokTypes;
    }
}
