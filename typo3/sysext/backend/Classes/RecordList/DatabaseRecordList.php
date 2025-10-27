<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\RecordList;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\RecordList\Event\BeforeRecordDownloadPresetsAreDisplayedEvent;
use TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListHeaderColumnsEvent;
use TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListRecordActionsEvent;
use TYPO3\CMS\Backend\RecordList\Event\ModifyRecordListTableActionsEvent;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ActionGroup;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonSize;
use TYPO3\CMS\Backend\Template\Components\Buttons\GenericButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\Components\ComponentGroup;
use TYPO3\CMS\Backend\Template\Components\ComponentInterface;
use TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Backend\View\Event\ModifyDatabaseQueryForRecordListingEvent;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Domain\Persistence\RecordIdentityMap;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\Field\DateTimeFieldType;
use TYPO3\CMS\Core\Schema\Field\NumberFieldType;
use TYPO3\CMS\Core\Schema\SearchableSchemaFieldsCollector;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Class for rendering of Web>List module
 * @internal This class is a specific TYPO3 Backend implementation and is not part of the TYPO3's Core API.
 */
#[Autoconfigure(public: true, shared: false)]
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
    public string $hideTranslations = '';

    /**
     * Cache for record path
     *
     * @var mixed[]
     */
    protected array $recPath_cache = [];

    /**
     * Field, to sort list by
     */
    public string $sortField = '';

    /**
     * Data of the module from the user's session
     */
    protected ?ModuleData $moduleData = null;

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

    /**
     * @var array[] Module configuration
     */
    public $modTSconfig;

    /**
     * Keys are fieldnames and values are td-css-classes to add in addElement();
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
     */
    protected int $searchLevels = 0;

    /**
     * TSconfig which overwrites TCA-Settings
     *
     * @var string[][]
     */
    public array $tableTSconfigOverTCA = [];

    /**
     * Fields to display for the current table
     *
     * @var string[][]
     */
    public array $setFields = [];

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
    public bool $sortRev = false;

    /**
     * String, can contain the field name from a table which must have duplicate values marked.
     */
    protected string $duplicateField = '';

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
     * @internal
     */
    public bool $displayColumnSelector = true;

    /**
     * Whether the record download should be displayed in the tables' header
     *
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
     */
    protected bool $editable = true;

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
     */
    protected array $overridePageIdList = [];

    /**
     * Override/add urlparameters in listUrl() method
     * @var mixed[]
     */
    protected array $overrideUrlParameters = [];

    /**
     * Current link: array with table names and uid
     */
    protected array $currentLink = [];

    /**
     * Only used to render translated records, used in list module to show page translations
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
     */
    protected array $languagesAllowedForUser = [];

    /**
     * A runtime first-level cache to avoid unneeded calls to BackendUtility::getRecord()
     */
    protected array $pagePermsCache = [];
    protected array $showLocalizeColumn = [];

    protected ServerRequestInterface $request;

    protected ?RecordIdentityMap $recordIdentityMap = null;

    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly TranslationConfigurationProvider $translateTools,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly BackendViewFactory $backendViewFactory,
        protected readonly ModuleProvider $moduleProvider,
        protected readonly SearchableSchemaFieldsCollector $searchableSchemaFieldsCollector,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
        protected readonly RecordFactory $recordFactory,
        protected readonly ComponentFactory $componentFactory,
    ) {
        $this->calcPerms = new Permission();
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    /**
     * Returns a list of all fields / columns including meta-columns such as
     * "_REF_" or "_PATH_" which should be rendered for the database table.
     */
    public function getColumnsToRender(string $table, bool $includeMetaColumns, string $selectedPreset = ''): array
    {
        $schema = $this->tcaSchemaFactory->get($table);
        $columnsToSelect = [];
        if ($schema->getCapability(TcaSchemaCapability::Label)->hasPrimaryField()) {
            $columnsToSelect[] = $schema->getCapability(TcaSchemaCapability::Label)->getPrimaryFieldName();
        }
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
        if ($schema->hasCapability(TcaSchemaCapability::InternalDescription) && empty($rowListArray)) {
            $rowListArray[] = $schema->getCapability(TcaSchemaCapability::InternalDescription)->getFieldName();
        }

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
            if ($schema->isLanguageAware()) {
                $columnsToSelect[] = '_LOCALIZATION_';
                // Do not show the "Localize to:" field when only translated records should be shown
                if (!$this->showOnlyTranslatedRecords) {
                    $columnsToSelect[] = '_LOCALIZATION_b';
                }
            }
        }

        return $this->applyPresetToColumns(
            $table,
            $selectedPreset,
            array_unique(array_merge($columnsToSelect, $rowListArray))
        );
    }

    /**
     * Checks if a preset exists that will modify the selected columns.
     * @internal
     */
    protected function applyPresetToColumns(string $table, string $selectedPreset, array $columnsToRender): array
    {
        if ($selectedPreset === '') {
            return $columnsToRender;
        }

        // To prevent client-side transmission of wanted column names,
        // we only evaluate the defined presets and take the definition from there.
        $presetRenderColumns = [];

        $presets = $this->eventDispatcher->dispatch(
            new BeforeRecordDownloadPresetsAreDisplayedEvent(
                $table,
                $this->modTSconfig['downloadPresets.'][$table . '.'] ?? [],
                $this->request,
                $this->id,
            )
        )->getPresets();

        foreach ($presets as $presetData) {
            if (($presetData->getIdentifier()) === $selectedPreset) {
                $presetRenderColumns = ($presetData->getColumns());
                break;
            }
        }

        // Evaluation yielded empty list
        if ($presetRenderColumns === []) {
            return $columnsToRender;
        }

        // Make sure no column is configured in a preset that is not actually allowed.
        foreach ($presetRenderColumns as $columnKey => $overlayColumnName) {
            if (!in_array($overlayColumnName, $columnsToRender, true)) {
                unset($presetRenderColumns[$columnKey]);
            }
        }

        // Evaluation yielded no valid column names.
        if ($presetRenderColumns === []) {
            return $columnsToRender;
        }

        return $presetRenderColumns;
    }

    /**
     * Based on the columns which should be rendered this method returns a list of actual
     * database fields to be selected from the query string.
     *
     * @return string[] a list of all database table fields
     * @internal: This method should be placed in another location in the future
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
        $schema = $this->tcaSchemaFactory->get($table);
        foreach ([TcaSchemaCapability::RestrictionDisabledField,
            TcaSchemaCapability::RestrictionEndTime,
            TcaSchemaCapability::RestrictionStartTime,
            TcaSchemaCapability::RestrictionUserGroup,
            TcaSchemaCapability::CreatedAt,
            TcaSchemaCapability::UpdatedAt,
            TcaSchemaCapability::SoftDelete,
            TcaSchemaCapability::SortByField,
            TcaSchemaCapability::InternalDescription,
            TcaSchemaCapability::EditLock] as $capability) {
            if ($schema->hasCapability($capability)) {
                $selectFields[] = $schema->getCapability($capability)->getFieldName();
            }
        }
        if ($schema->supportsSubSchema()) {
            $selectFields[] = $schema->getSubSchemaTypeInformation()->getFieldName();
        }
        if ($schema->getRawConfiguration()['typeicon_column'] ?? false) {
            $selectFields[] = $schema->getRawConfiguration()['typeicon_column'];
        }
        if ($schema->isWorkspaceAware()) {
            $selectFields[] = 't3ver_stage';
            $selectFields[] = 't3ver_state';
            $selectFields[] = 't3ver_wsid';
            $selectFields[] = 't3ver_oid';
        }
        if ($schema->isLanguageAware()) {
            $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
            $selectFields[] = $languageCapability->getLanguageField()->getName();
            $selectFields[] = $languageCapability->getTranslationOriginPointerField()->getName();
            if ($languageCapability->hasTranslationSourceField()) {
                $selectFields[] = $languageCapability->getTranslationSourceField()->getName();
            }
            if ($languageCapability->hasDiffSourceField()) {
                $selectFields[] = $languageCapability->getDiffSourceField()->getName();
            }
        }
        $labelCapability = $schema->getCapability(TcaSchemaCapability::Label);
        $selectFields = array_unique(array_merge($selectFields, $labelCapability->getAllLabelFieldNames()));
        $fieldListFields = BackendUtility::getAllowedFieldsForTable($table, false);
        // Making sure that the fields in the field-list ARE in the field-list from TCA!
        return array_intersect($selectFields, $fieldListFields);
    }

    /**
     * Creates the listing of records from a single table
     *
     * @param string $table Table name
     * @throws \UnexpectedValueException
     * @return string HTML table with the listing for the record.
     */
    public function getTable(string $table): string
    {
        // Finding the total amount of records on the page
        $queryBuilderTotalItems = $this->getQueryBuilder($table, ['*'], false, 0, 1);
        $totalItems = (int)$queryBuilderTotalItems
            ->count('*')
            ->resetOrderBy()
            ->executeQuery()
            ->fetchOne();
        if ($totalItems === 0) {
            return '';
        }
        $schema = $this->tcaSchemaFactory->get($table);
        // Setting the limits for the amount of records to be displayed in the list and single table view.
        // Using the default value and overwriting with page TSconfig. The limit is forced
        // to be in the range of 0 - 10000.
        // default 100 for single table view
        $itemsLimitSingleTable = MathUtility::forceIntegerInRange((int)(
            $this->modTSconfig['itemsLimitSingleTable'] ?? 100
        ), 0, 10000);

        // default 20 for list view
        $itemsLimitPerTable = MathUtility::forceIntegerInRange((int)(
            $this->modTSconfig['itemsLimitPerTable'] ?? 20
        ), 0, 10000);

        if ($this->showLimit) {
            // Set limit defined by calling code
            $itemsPerPage = $this->showLimit;
        } else {
            // Set limit depending on the view (single table vs. default)
            $itemsPerPage = $this->table ? $itemsLimitSingleTable : $itemsLimitPerTable;
        }

        // Init
        $labelCapability = $schema->getCapability(TcaSchemaCapability::Label);
        $titleCol = $labelCapability->getPrimaryFieldName() ?? '';
        $l10nEnabled = $schema->isLanguageAware();

        $this->fieldArray = $this->getColumnsToRender($table, true);
        // Creating the list of fields to include in the SQL query
        $selectFields = $this->getFieldsToSelect($table, $this->fieldArray);

        $firstElement = ($this->page - 1) * $itemsPerPage;
        if ($firstElement > 2 && $itemsPerPage > 0) {
            // Get the two previous rows for sorting if displaying page > 1
            $firstElement -= 2;
            $itemsPerPage += 2;
            $queryBuilder = $this->getQueryBuilder($table, $selectFields, true, $firstElement, $itemsPerPage);
            $firstElement += 2;
            $itemsPerPage -= 2;
        } else {
            $queryBuilder = $this->getQueryBuilder($table, $selectFields, true, $firstElement, $itemsPerPage);
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

        // Initialize RecordIdentityMap for this request
        $this->recordIdentityMap = GeneralUtility::makeInstance(RecordIdentityMap::class);

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
            $tableTitle = htmlspecialchars($schema->getTitle($lang->sL(...)));
            if ($tableTitle === '') {
                $tableTitle = $table;
            }
        }

        $backendUser = $this->getBackendUserAuthentication();
        $tableCollapsed = (bool)($this->moduleData?->get('collapsedTables')[$tableIdentifier] ?? false);

        // Header line is drawn
        $theData = [];
        if ($this->disableSingleTableView) {
            $theData[$titleCol] = $tableTitle . ' (<span>' . $totalItems . '</span>)';
        } else {
            $icon = $this->table // @todo separate table header from contract/expand link
                ? $this->iconFactory
                    ->getIcon('actions-view-table-collapse', IconSize::SMALL)
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:contractView'))
                    ->render()
                : $this->iconFactory
                    ->getIcon('actions-view-table-expand', IconSize::SMALL)
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:expandView'))
                    ->render();
            $theData[$titleCol] = $this->linkWrapTable($table, $tableTitle . ' (<span>' . $totalItems . '</span>) ' . $icon);
        }
        $tableActions = '';
        $tableHeader = $theData[$titleCol];
        if (!$onlyShowRecordsInSingleTableMode) {
            // Add the "new record" button
            $tableActions .= $this->createActionButtonNewRecord($table) ?? '';
            // Show the select box
            $tableActions .= $this->createActionButtonColumnSelector($table) ?? '';
            // Create the Download button
            $tableActions .= $this->createActionButtonDownload($table, $totalItems) ?? '';
            // Render collapse button if in multi table mode
            $tableActions .= $this->createActionButtonCollapse($table) ?? '';
        }
        $currentIdList = [];
        // Render table rows only if in multi table view or if in single table view
        $rowOutput = '';
        if (!$onlyShowRecordsInSingleTableMode || $this->table) {
            // Fixing an order table for sortby tables
            $this->currentTable = [];
            $allowManualSorting = $schema->hasCapability(TcaSchemaCapability::SortByField) && !$this->sortField;
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
            // Accumulate Record objects here
            while ($rowData = $queryResult->fetchAssociative()) {
                if (!$this->isRowListingConditionFulfilled($this->recordFactory->createResolvedRecordFromDatabaseRow(
                    $table,
                    $rowData,
                    null,
                    $this->recordIdentityMap
                ))) {
                    continue;
                }
                // In offline workspace, look for alternative record
                BackendUtility::workspaceOL($table, $rowData, $backendUser->workspace, true);
                if (is_array($rowData)) {
                    // Create Record object from database row
                    $record = $this->recordFactory->createResolvedRecordFromDatabaseRow(
                        $table,
                        $rowData,
                        null,
                        $this->recordIdentityMap
                    );

                    $accRows[] = $record;
                    $currentIdList[] = $record->getUid();
                    if ($allowManualSorting) {
                        if ($prevUid) {
                            $this->currentTable['prev'][$record->getUid()] = $prevPrevUid;
                            $this->currentTable['next'][$prevUid] = '-' . $record->getUid();
                            $this->currentTable['prevUid'][$record->getUid()] = $prevUid;
                        }
                        $prevPrevUid = isset($this->currentTable['prev'][$record->getUid()]) ? -$prevUid : $record->getPid();
                        $prevUid = $record->getUid();
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
            foreach ($accRows as $record) {
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
                    $languageFieldName = $schema->isLanguageAware() ? $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName() : '';
                    // Guard clause so we can quickly return if a record is localized to "all languages"
                    // It should only be possible to localize a record off default (uid 0)
                    $filterLanguage = (int)$this->moduleData?->get('language', -1);
                    $rowLanguage = ($languageFieldName !== '' ? ($record->getRawRecord()->toArray()[$languageFieldName] ?? false) : false);
                    if ($l10nEnabled && $rowLanguage !== -1 && $filterLanguage !== 0) {
                        $translationsRaw = $this->translateTools->translationInfo($table, $record->getUid(), $filterLanguage !== -1 ? $filterLanguage : 0, $record->getRawRecord()->toArray(), '*');
                        if (is_array($translationsRaw)) {
                            if ($filterLanguage < 1) {
                                $translationEnabled = true;
                            }
                            $translations = $translationsRaw['translations'] ?? [];
                        }
                        // Enable translation for default records not translated into selected language
                        if ($filterLanguage > 0 && $filterLanguage !== $rowLanguage && !array_key_exists($filterLanguage, $translations)) {
                            $translationEnabled = true;
                        }
                    }

                    $rowOutput .= $this->renderListRow($table, $record, 0, $translations, $translationEnabled, $filterLanguage !== -1 ? [$filterLanguage] : []);

                    if ($listTranslatedRecords) {
                        foreach ($translations ?? [] as $lRow) {
                            if (!$this->isRowListingConditionFulfilled(
                                $this->recordFactory->createResolvedRecordFromDatabaseRow(
                                    $table,
                                    $lRow,
                                    null,
                                    $this->recordIdentityMap
                                )
                            )) {
                                continue;
                            }
                            // In offline workspace, look for alternative record:
                            BackendUtility::workspaceOL($table, $lRow, $backendUser->workspace, true);
                            if (is_array($lRow) && $backendUser->checkLanguageAccess($lRow[$languageFieldName])) {
                                // Create Record object for translation
                                $translationRecord = $this->recordFactory->createResolvedRecordFromDatabaseRow(
                                    $table,
                                    $lRow,
                                    null,
                                    $this->recordIdentityMap
                                );

                                $currentIdList[] = $translationRecord->getUid();
                                $rowOutput .= $this->renderListRow($table, $translationRecord, 1, [], false);
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
                    <tr data-multi-record-selection-element="true">
                        <td colspan="' . (count($this->fieldArray)) . '">
                            <a href="' . htmlspecialchars((string)$this->listURL(null, $tableIdentifier)) . '" class="btn btn-sm btn-default">
                                ' . $this->iconFactory->getIcon('actions-caret-down', IconSize::SMALL)->render() . '
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
                <div class="recordlist-heading-row t3js-multi-record-selection-actions hidden">
                    <div class="recordlist-heading-title">
                        <strong>' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.selection')) . '</strong>
                    </div>
                    <div class="recordlist-heading-actions">
                        ' . $this->renderMultiRecordSelectionActions($table, $currentIdList) . '
                    </div>
                </div>
            ';
        }

        $recordListMessages = '';
        $recordlistMessageEntries = [];
        if ($backendUser->workspace > 0 && ExtensionManagementUtility::isLoaded('workspaces') && !$schema->hasCapability(TcaSchemaCapability::Workspace)) {
            // In case the table is not editable in workspace inform the user about the missing actions
            if ($backendUser->workspaceAllowsLiveEditingInTable($table)) {
                $recordlistMessageEntries[] = [
                    'message' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.editingLiveRecordsWarning'),
                    'severity' => ContextualFeedbackSeverity::WARNING,
                ];
            } else {
                $recordlistMessageEntries[] = [
                    'message' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.notEditableInWorkspace'),
                    'severity' => ContextualFeedbackSeverity::INFO,
                ];
            }
        }

        foreach ($recordlistMessageEntries as $messageEntry) {
            $recordListMessages .= '<div class="alert alert-' . $messageEntry['severity']->getCssClass() . '">';
            $recordListMessages .= $this->iconFactory->getIcon($messageEntry['severity']->getIconIdentifier(), IconSize::SMALL)->render();
            $recordListMessages .= ' ';
            $recordListMessages .= htmlspecialchars($messageEntry['message'], ENT_QUOTES | ENT_HTML5);
            $recordListMessages .= '</div>';
        }

        $collapseClass = $tableCollapsed && !$this->table ? 'collapse' : 'collapse show';
        $dataState = $tableCollapsed && !$this->table ? 'collapsed' : 'expanded';
        return '
            <div class="recordlist" id="t3-table-' . htmlspecialchars($tableIdentifier) . '" data-multi-record-selection-identifier="t3-table-' . htmlspecialchars($tableIdentifier) . '">
                <form action="' . htmlspecialchars((string)$this->listURL()) . '#t3-table-' . htmlspecialchars($tableIdentifier) . '" method="post" name="list-table-form-' . htmlspecialchars($tableIdentifier) . '">
                    <input type="hidden" name="cmd_table" value="' . htmlspecialchars($tableIdentifier) . '" />
                    <input type="hidden" name="cmd" />
                    <div class="recordlist-heading ' . ($multiRecordSelectionActions !== '' ? 'multi-record-selection-panel' : '') . '">
                        <div class="recordlist-heading-row">
                            <div class="recordlist-heading-title">' . $tableHeader . '</div>
                            <div class="recordlist-heading-actions">' . $tableActions . '</div>
                        </div>
                        ' . $multiRecordSelectionActions . '
                    </div>
                    ' . $recordListMessages . '
                    <div class="' . $collapseClass . '" data-state="' . $dataState . '" id="recordlist-' . htmlspecialchars($tableIdentifier) . '">
                        <div class="table-fit">
                            <table data-table="' . htmlspecialchars($tableIdentifier) . '" class="table table-striped table-hover">
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
     */
    protected function createActionButtonNewRecord(string $table): ?ButtonInterface
    {
        if (!$this->isEditable($table)) {
            return null;
        }
        if (!$this->showNewRecLink($table)) {
            return null;
        }
        $permsAdditional = ($table === 'pages' ? Permission::PAGE_NEW : Permission::CONTENT_EDIT);
        if (!$this->calcPerms->isGranted($permsAdditional)) {
            return null;
        }

        if ($table === 'tt_content') {
            // No button with tt_content table, content elements should be managed using page module.
            return null;
        }

        $schema = $this->tcaSchemaFactory->get($table);

        $tag = 'a';
        $iconIdentifier = 'actions-plus';
        $label = sprintf(
            $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:newRecordOfType'),
            $schema->getTitle($this->getLanguageService()->sL(...)),
        );
        $attributes = [
            'data-recordlist-action' => 'new',
        ];

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
                    'module' => $this->request->getAttribute('module')?->getIdentifier() ?? '',
                    'returnUrl' => $this->listURL(),
                ]
            );
        }

        $button = $this->componentFactory->createGenericButton();
        $button->setTag($tag);
        $button->setLabel($label);
        $button->setShowLabelText(true);
        $button->setIcon($this->iconFactory->getIcon($iconIdentifier, IconSize::SMALL));
        $button->setAttributes($attributes);

        return $button;
    }

    protected function createActionButtonDownload(string $table, int $totalItems): ?ButtonInterface
    {
        // Do not render the download button for page translations or in case it is generally disabled
        if (!$this->displayRecordDownload || $this->showOnlyTranslatedRecords) {
            return null;
        }

        $shouldRenderDownloadButton = true;
        // See if it is disabled in general
        if (isset($this->modTSconfig['displayRecordDownload'])) {
            $shouldRenderDownloadButton = (bool)$this->modTSconfig['displayRecordDownload'];
        }
        // Table override was explicitly set
        if (isset($this->tableTSconfigOverTCA[$table . '.']['displayRecordDownload'])) {
            $shouldRenderDownloadButton = (bool)$this->tableTSconfigOverTCA[$table . '.']['displayRecordDownload'];
        }
        // Do not render button if disabled
        if ($shouldRenderDownloadButton === false) {
            return null;
        }

        $schema = $this->tcaSchemaFactory->get($table);

        $downloadButtonLabel = $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_download.xlf:download');
        $downloadButtonTitle = sprintf($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_download.xlf:' . ($totalItems === 1 ? 'downloadRecord' : 'downloadRecords')), $totalItems);
        $downloadCancelTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cancel');
        $downloadSettingsUrl = (string)$this->uriBuilder->buildUriFromRoute(
            'ajax_record_download_settings',
            [
                'id' => $this->id,
                'table' => $table,
                'searchString' => $this->searchString,
                'searchLevels' => $this->searchLevels,
                'sortField' => $this->sortField,
                'sortRev' => $this->sortRev,
            ],
        );
        $downloadSettingsTitle = sprintf(
            $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_download.xlf:' . ($totalItems === 1 ? 'downloadRecordSettings' : 'downloadRecordsSettings')),
            $schema->getTitle($this->getLanguageService()->sL(...)) ?: $table,
            $totalItems
        );

        $button = $this->componentFactory->createGenericButton();
        $button->setTag('typo3-recordlist-record-download-button');
        $button->setLabel($downloadButtonLabel);
        $button->setShowLabelText(true);
        $button->setIcon($this->iconFactory->getIcon('actions-download', IconSize::SMALL));
        $button->setAttributes([
            'url' => $downloadSettingsUrl,
            'subject' => $downloadSettingsTitle,
            'ok' => $downloadButtonTitle,
            'close' => $downloadCancelTitle,
            'data-recordlist-action' => 'download',
        ]);

        return $button;
    }

    /**
     * Creates a button, which triggers a modal for the column selection
     */
    protected function createActionButtonColumnSelector(string $table): ?ButtonInterface
    {
        if ($this->displayColumnSelector === false) {
            // Early return in case column selector is disabled
            return null;
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
            return null;
        }

        $schema = $this->tcaSchemaFactory->get($table);

        $lang = $this->getLanguageService();
        $tableIdentifier = $table . (($table === 'pages' && $this->showOnlyTranslatedRecords) ? '_translated' : '');
        $columnSelectorUrl = (string)$this->uriBuilder->buildUriFromRoute(
            'ajax_show_columns_selector',
            ['id' => $this->id, 'table' => $table]
        );
        $columnSelectorTitle = sprintf(
            $lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_column_selector.xlf:showColumnsSelection'),
            $schema->getTitle($lang->sL(...)) ?: $table,
        );

        $button = $this->componentFactory->createGenericButton();
        $button->setTag('typo3-backend-column-selector-button');
        $button->setLabel($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_column_selector.xlf:showColumns'));
        $button->setShowLabelText(true);
        $button->setIcon($this->iconFactory->getIcon('actions-options', IconSize::SMALL));
        $button->setAttributes([
            'data-url' => $columnSelectorUrl,
            'data-target' => (string)($this->listURL()->withFragment('#t3-table-' . $tableIdentifier)),
            'data-title' => $columnSelectorTitle,
            'data-button-ok' => $lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_column_selector.xlf:updateColumnView'),
            'data-button-close' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cancel'),
            'data-error-message' => $lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_column_selector.xlf:updateColumnView.error'),
            'data-recordlist-action' => 'columns',
        ]);

        return $button;
    }

    protected function createActionButtonCollapse(string $table): ?ButtonInterface
    {
        if ($this->table !== '') {
            return null;
        }

        $schema = $this->tcaSchemaFactory->get($table);

        $tableIdentifier = $table . (($table === 'pages' && $this->showOnlyTranslatedRecords) ? '_translated' : '');
        $tableCollapsed = (bool)($this->moduleData?->get('collapsedTables')[$tableIdentifier] ?? false);

        $button = $this->componentFactory->createGenericButton();
        $button->setLabel(sprintf(
            $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:collapseExpandTable'),
            $schema->getTitle($this->getLanguageService()->sL(...))
        ));
        $button->setClasses('t3js-toggle-recordlist');
        $button->setIcon($this->iconFactory->getIcon(($tableCollapsed ? 'actions-view-list-expand' : 'actions-view-list-collapse'), IconSize::SMALL));
        $button->setAttributes([
            'aria-expanded' => ($tableCollapsed ? 'false' : 'true'),
            'data-table' => $tableIdentifier,
            'data-recordlist-action' => 'toggle',
            'data-bs-toggle' => 'collapse',
            'data-bs-target' => '#recordlist-' . $tableIdentifier,
        ]);

        return $button;
    }

    protected function getPreviewUriBuilder(string $table, RecordInterface $record): PreviewUriBuilder
    {
        return PreviewUriBuilder::createForRecordPreview(
            $table,
            $record,
            (int)($table === 'pages' ? $record->getUid() : ($this->pageRow['uid'] ?? 0))
        );
    }

    /**
     * Check if all row listing conditions are fulfilled.
     *
     * This function serves as a dummy method to be overridden in extending classes.
     *
     * @param RecordInterface $record Record
     * @return bool True, if all conditions are fulfilled.
     */
    protected function isRowListingConditionFulfilled(RecordInterface $record): bool
    {
        return true;
    }

    /**
     * Rendering a single row for the list
     *
     * @param string $table Table name
     * @param int $indent Indent from left.
     * @param array $translations Array of already existing translations for the current record
     * @param bool $translationEnabled Whether the record can be translated
     * @param int[] $possibleTranslations The possible translation language uids.
     * @return string Table row for the element
     * @internal
     * @see getTable()
     */
    public function renderListRow($table, RecordInterface $record, int $indent, array $translations, bool $translationEnabled, array $possibleTranslations = [])
    {
        $titleCol = '';
        $schema = $this->tcaSchemaFactory->get($table);
        if ($schema->hasCapability(TcaSchemaCapability::Label)) {
            $titleCol = $schema->getCapability(TcaSchemaCapability::Label)->getPrimaryFieldName();
        }
        $languageService = $this->getLanguageService();
        $rowOutput = '';
        $id_orig = $this->id;
        // If in search mode, make sure the preview will show the correct page
        if ($this->searchString !== '') {
            $this->id = $record->getPid();
        }

        $tagAttributes = [
            'class' => [],
            'data-table' => $table,
            'title' => 'id=' . $record->getUid(),
        ];

        // Add active class to record of current link
        if (
            isset($this->currentLink['tableNames'])
            && (int)$this->currentLink['uid'] === $record->getUid()
            && GeneralUtility::inList($this->currentLink['tableNames'], $table)
        ) {
            $tagAttributes['class'][] = 'active';
        }
        $tagAttributes['class'][] = 't3js-entity';

        // Preparing and getting the data-array
        $theData = [];
        $deletePlaceholderClass = '';
        foreach ($this->fieldArray as $fCol) {
            if ($fCol === $titleCol) {
                $recTitle = BackendUtility::getRecordTitle($table, $record);
                $warning = '';
                // If the record is edit-locked	by another user, we will show a little warning sign:
                $lockInfo = BackendUtility::isRecordLocked($table, $record->getUid());
                if ($lockInfo) {
                    $warning = '<span tabindex="0"'
                        . ' title="' . htmlspecialchars($lockInfo['msg']) . '"'
                        . ' aria-label="' . htmlspecialchars($lockInfo['msg']) . '">'
                        . $this->iconFactory->getIcon('status-user-backend', IconSize::SMALL, 'overlay-edit')->render()
                        . '</span>';
                }
                if ($this->isRecordDeletePlaceholder($record)) {
                    // Delete placeholder records do not link to formEngine edit and are rendered strike-through
                    $deletePlaceholderClass = ' deletePlaceholder';
                    $theData[$fCol] = $theData['__label'] =
                        $warning
                        . '<span title="' . htmlspecialchars($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:row.deletePlaceholder.title')) . '">'
                            . htmlspecialchars($recTitle)
                        . '</span>';
                } else {
                    $theData[$fCol] = $theData['__label'] = $warning . $this->linkWrapItems($table, $record->getUid(), $recTitle, $record);
                }
            } elseif ($fCol === 'pid') {
                $theData[$fCol] = $record->getPid();
            } elseif ($fCol === '_SELECTOR_') {
                if ($table !== 'pages' || !$this->showOnlyTranslatedRecords) {
                    // Add checkbox for all tables except the special page translations table
                    $theData[$fCol] = $this->makeCheckbox($table, $record);
                } else {
                    // Remove "_SELECTOR_", which is always the first item, from the field list
                    array_splice($this->fieldArray, 0, 1);
                }
            } elseif ($fCol === 'icon') {
                $rowArray = $record->getRawRecord()?->toArray() ?? [];
                $icon = $this->iconFactory
                    ->getIconForRecord($table, $rowArray, IconSize::SMALL)
                    ->setTitle(BackendUtility::getRecordIconAltText($record, $table, false))
                    ->render();
                $theData[$fCol] = ''
                    . ($indent ? '<span class="indent indent-inline-block" style="--indent-level: ' . $indent . '"></span> ' : '')
                    . (($this->clickMenuEnabled && !$this->isRecordDeletePlaceholder($record)) ? BackendUtility::wrapClickMenuOnIcon($icon, $table, $record->getUid()) : $icon);
            } elseif ($fCol === '_PATH_') {
                $theData[$fCol] = $this->recPath($record->getPid());
            } elseif ($fCol === '_REF_') {
                $theData[$fCol] = $this->generateReferenceToolTip($table, $record->getUid());
            } elseif ($fCol === '_CONTROL_') {
                /** @var Record $record */
                $theData[$fCol] = $this->makeControl($record);
            } elseif ($fCol === '_LOCALIZATION_') {
                // Language flag and title
                $theData[$fCol] = $this->languageFlag($table, $record);
                // Localize record
                $localizationPanel = $translationEnabled ? $this->makeLocalizationPanel((string)$table, $record, $translations, $possibleTranslations) : '';

                if ($localizationPanel !== '') {
                    $theData['_LOCALIZATION_b'] = '<div class="btn-group">' . $localizationPanel . '</div>';
                    $this->showLocalizeColumn[$table] = true;
                }
            } elseif ($fCol !== '_LOCALIZATION_b') {
                // default for all other columns, except "_LOCALIZATION_b"
                $pageId = $table === 'pages' ? $record->getUid() : $record->getPid();
                $fieldValue = $record->getRawRecord()?->has($fCol) ? $record->getRawRecord()->get($fCol) : '';
                $tmpProc = BackendUtility::getProcessedValueExtra($table, $fCol, $fieldValue, 100, $record->getUid(), true, $pageId, $record->getRawRecord()?->toArray());
                $theData[$fCol] = $this->linkUrlMail(htmlspecialchars((string)$tmpProc), (string)$fieldValue);
            }
        }
        // Reset the ID if it was overwritten
        if ($this->searchString !== '') {
            $this->id = $id_orig;
        }
        // Add classes to table cells
        $this->addElement_tdCssClass['_SELECTOR_'] = 'col-checkbox';
        $this->addElement_tdCssClass[$titleCol] = 'col-title col-responsive' . $deletePlaceholderClass;
        $this->addElement_tdCssClass['__label'] = $this->addElement_tdCssClass[$titleCol];
        $this->addElement_tdCssClass['icon'] = 'col-icon';
        $this->addElement_tdCssClass['_CONTROL_'] = 'col-control';
        $this->addElement_tdCssClass['_PATH_'] = 'col-path';
        $this->addElement_tdCssClass['_LOCALIZATION_'] = 'col-localizationa';
        $this->addElement_tdCssClass['_LOCALIZATION_b'] = 'col-localizationb';
        // Create element in table cells:
        $theData['uid'] = $record->getUid();
        if ($schema->isLanguageAware() && $record instanceof Record) {
            $theData['_l10nparent_'] = $record->getLanguageInfo()?->getTranslationParent();
        }

        $tagAttributes = array_map(
            static function (array|string $attributeValue): string {
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
        $lang = $this->getLanguageService();
        $currentIdList = is_array($currentIdList) ? $currentIdList : [];

        // Init:
        $theData = [];
        // Traverse the fields:
        foreach ($this->fieldArray as $field) {
            switch ((string)$field) {
                case '_SELECTOR_':
                    if ($table !== 'pages' || !$this->showOnlyTranslatedRecords) {
                        // Add checkbox actions for all tables except the special page translations table
                        $theData[$field] = $this->renderCheckboxActions();
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
                        $theData[$field] = '';
                    }
                    break;
                case '_CONTROL_':
                    $theData[$field] = '<i class="hidden">' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels._CONTROL_')) . '</i>';
                    // In single table view, add button to edit displayed fields of marked / listed records
                    if ($this->table && $this->canEditTable($table) && $currentIdList !== [] && $this->isEditable($table)) {
                        $label = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:editShownColumns'));
                        $theData[$field] = '<button type="button"'
                            . ' class="btn btn-default t3js-record-edit-multiple"'
                            . ' title="' . $label . '"'
                            . ' aria-label="' . $label . '"'
                            . ' data-return-url="' . htmlspecialchars((string)$this->listURL()) . '"'
                            . ' data-columns-only="' . GeneralUtility::jsonEncodeForHtmlAttribute(array_values($this->fieldArray)) . '">'
                            . $this->iconFactory->getIcon('actions-document-open', IconSize::SMALL)->render()
                            . '</button>';
                    }
                    break;
                case '_LOCALIZATION_b':
                    // Show translation options
                    if ($this->showLocalizeColumn[$table] ?? false) {
                        $theData[$field] = '<i>' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:Localize')) . '</i>';
                    }
                    break;
                default:
                    $theData[$field] = $this->renderListTableFieldHeader($table, $field, $currentIdList);
            }
        }

        $event = $this->eventDispatcher->dispatch(
            new ModifyRecordListHeaderColumnsEvent($theData, $table, $currentIdList, $this)
        );

        // Create and return header table row:
        return $this->addElement($event->getColumns(), GeneralUtility::implodeAttributes($event->getHeaderAttributes(), true), 'th');
    }

    protected function renderListTableFieldHeader(string $table, string $field, array $currentIdList): string
    {
        $schema = $this->tcaSchemaFactory->get($table);
        $label = $this->getFieldLabel($schema, $field);
        $sortField = $field;

        if (in_array($field, ['_SELECTOR_', '_CONTROL_', '_REF_', '_LOCALIZATION_', '_PATH_'])) {
            return '<i>' . $label . '</i>';
        }

        $dropdownExtraItems = [];
        if ($currentIdList !== []) {
            // If the numeric clipboard pads are selected, show duplicate sorting link:
            if ($this->table
                && $this->noControlPanels === false
                && $this->isClipboardFunctionalityEnabled($table)
                && $this->clipObj->current !== 'normal'
            ) {
                $title = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:clip_duplicates');
                $attributes = [
                    'class' => 'dropdown-item',
                    'href' => $this->listURL() . '&duplicateField=' . $field,
                    'title' => $title,
                    'aria-label' => $title,
                ];
                $dropdownExtraItems[] = '
                    <a ' . GeneralUtility::implodeAttributes($attributes, true) . '>
                        <span class="dropdown-item-columns">
                            <span class="dropdown-item-column dropdown-item-column-icon">
                                ' . $this->iconFactory->getIcon('actions-document-duplicates-select', IconSize::SMALL)->render() . '
                            </span>
                            <span class="dropdown-item-column dropdown-item-column-title">
                                ' . htmlspecialchars($title) . '
                            </span>
                        </span>
                    </a>
                ';
            }
            // If the table and field can be edited, add link for editing THIS field for all listed records:
            if ($this->isEditable($table, $field) && $this->canEditTable($table)) {
                $title = sprintf($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:editThisColumn'), $label);
                $attributes = [
                    'type' => 'button',
                    'class' => 'dropdown-item t3js-record-edit-multiple',
                    'title' => $title,
                    'aria-label' => $title,
                    'data-return-url' => (string)$this->listURL(),
                    'data-columns-only' => json_encode([$field]),
                ];
                $dropdownExtraItems[] = '
                    <button ' . GeneralUtility::implodeAttributes($attributes, true) . '>
                        <span class="dropdown-item-columns">
                            <span class="dropdown-item-column dropdown-item-column-icon">
                                ' . $this->iconFactory->getIcon('actions-document-open', IconSize::SMALL)->render() . '
                            </span>
                            <span class="dropdown-item-column dropdown-item-column-title">
                                ' . htmlspecialchars($title) . '
                            </span>
                        </span>
                    </button>
                ';
            }
        }

        $dropdownSortingItems = [];
        if (!$this->disableSingleTableView) {
            // Sort ascending
            $title = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.sorting.asc');
            $attributes = [
                'class' => 'dropdown-item',
                'href' => $this->listURL(null, $table, ['sortField', 'sortRev', 'table', 'pointer']) . '&sortField=' . $sortField . '&sortRev=0',
                'title' => $title,
                'aria-label' => $title,
            ];
            $dropdownSortingItems[] = '
                <a ' . GeneralUtility::implodeAttributes($attributes, true) . '>
                    <span class="dropdown-item-columns">
                        <span class="dropdown-item-column dropdown-item-column-icon text-primary">
                            ' . ($this->sortField === $sortField && !$this->sortRev ? $this->iconFactory->getIcon('actions-dot', IconSize::SMALL)->render() : '') . '
                        </span>
                        <span class="dropdown-item-column dropdown-item-column-title">
                            ' . htmlspecialchars($title) . '
                        </span>
                    </span>
                </a>
            ';

            // Sort decending
            $title = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.sorting.desc');
            $attributes = [
                'class' => 'dropdown-item',
                'href' => $this->listURL(null, $table, ['sortField', 'sortRev', 'table', 'pointer']) . '&sortField=' . $sortField . '&sortRev=1',
                'title' => $title,
                'aria-label' => $title,
            ];
            $dropdownSortingItems[] = '
                <a ' . GeneralUtility::implodeAttributes($attributes, true) . '>
                    <span class="dropdown-item-columns">
                        <span class="dropdown-item-column dropdown-item-column-icon text-primary">
                            ' . ($this->sortField === $sortField && $this->sortRev ? $this->iconFactory->getIcon('actions-dot', IconSize::SMALL)->render() : '') . '
                        </span>
                        <span class="dropdown-item-column dropdown-item-column-title">
                            ' . htmlspecialchars($title) . '
                        </span>
                    </span>
                </a>
            ';
        }

        $dropdownExtraHasItems = $dropdownExtraItems !== [];
        $dropdownSortingHasItems = $dropdownSortingItems !== [];
        if (!$dropdownExtraHasItems && !$dropdownSortingHasItems) {
            return $label;
        }

        $icon = '';
        if ($dropdownSortingHasItems) {
            $icon = $this->sortField === $sortField
                ? $this->iconFactory->getIcon('actions-sort-amount-' . ($this->sortRev ? 'down' : 'up'), IconSize::SMALL)->render()
                : $this->iconFactory->getIcon('empty-empty', IconSize::SMALL)->render();
        }

        return '
            <div class="dropdown dropdown-static">
                <button
                    class="dropdown-toggle dropdown-toggle-link"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >
                    ' . htmlspecialchars($label) . ' <div class="' . ($this->sortField === $sortField ? 'text-primary' : '') . '">' . $icon . '</div>
                </button>
                <ul class="dropdown-menu">
                    ' . implode('', array_map(static fn($item) => '<li>' . $item . '</li>', $dropdownSortingItems)) . '
                    ' . ($dropdownExtraHasItems && $dropdownSortingHasItems ? '<li><hr class="dropdown-divider" aria-hidden="true"></li>' : '') . '
                    ' . implode('', array_map(static fn($item) => '<li>' . $item . '</li>', $dropdownExtraItems)) . '
                </ul>
            </div>
        ';
    }

    /**
     * Creates a page browser for tables with many records
     *
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
        $view = $this->backendViewFactory->create($this->request);
        return $view->assignMultiple([
            'currentUrl' => $this->listURL(null, $table, ['pointer']),
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'firstElement' => ((($currentPage - 1) * $itemsPerPage) + 1),
            'lastElement' => $lastElementNumber,
            'colspan' => $paginationColumns,
        ])
            ->render('ListNavigation');
    }

    /*********************************
     *
     * Rendering of various elements
     *
     *********************************/

    /**
     * Creates the control panel for a single record in the listing.
     *
     * @throws \UnexpectedValueException
     * @return string HTML table with the control panel (unless disabled)
     */
    public function makeControl(RecordInterface $record): string
    {
        $table = $record->getMainType();
        $backendUser = $this->getBackendUserAuthentication();
        $schema = $this->tcaSchemaFactory->get($table);
        $userTsConfig = $backendUser->getTSConfig();
        $isDeletePlaceHolder = $this->isRecordDeletePlaceholder($record);
        $primary = new ComponentGroup('primary');
        $secondary = new ComponentGroup('secondary');
        $languageFieldName = $transOrigPointerFieldName = '';
        if ($schema->isLanguageAware()) {
            $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
            $languageFieldName = $languageCapability->getLanguageField()->getName();
            $transOrigPointerFieldName = $languageCapability->getTranslationOriginPointerField()->getName();
        }
        // Hide the move elements for localized records - doesn't make much sense to perform these options for them
        $isL10nOverlay = $record->getRawRecord()?->has($transOrigPointerFieldName) && $record->getRawRecord()->get($transOrigPointerFieldName) > 0;
        $localCalcPerms = $this->getPagePermissionsForRecord($record);
        if ($table === 'pages') {
            $permsEdit = $backendUser->checkLanguageAccess($record->getRawRecord()?->has($languageFieldName) ? $record->getRawRecord()->get($languageFieldName) : 0) && $localCalcPerms->editPagePermissionIsGranted();
        } else {
            $permsEdit = $localCalcPerms->editContentPermissionIsGranted() && $backendUser->recordEditAccessInternals($table, $record);
        }
        $permsEdit = $this->overlayEditLockPermissions($table, $record, $permsEdit);

        // "Show" link
        $attributes = $this->getPreviewUriBuilder($table, $record)->buildDispatcherAttributes();
        $viewButton = null;
        if ($attributes !== null) {
            $viewButton = $this->componentFactory->createGenericButton()
                ->setIcon($this->iconFactory->getIcon($table === 'pages' ? 'actions-view-page' : 'actions-view'))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                ->setAttributes($attributes);
        }
        $secondary->add('view', $viewButton);

        $editButton = null;
        // "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
        if ($permsEdit && !$isDeletePlaceHolder && $this->isEditable($table)) {
            $params = [
                'edit' => [
                    $table => [
                        $record->getUid() => 'edit',
                    ],
                ],
                'module' => $this->request->getAttribute('module')?->getIdentifier() ?? '',
            ];
            $iconIdentifier = 'actions-open';
            if ($table === 'pages') {
                // Disallow manual adjustment of the language field for pages
                $params['overrideVals']['pages']['sys_language_uid'] = $record->getRawRecord()?->has($languageFieldName) ? $record->getRawRecord()->get($languageFieldName) : 0;
                $iconIdentifier = 'actions-page-open';
            }
            $params['returnUrl'] = $this->listURL();
            $editLink = $this->uriBuilder->buildUriFromRoute('record_edit', $params);

            $editButton = $this->componentFactory->createLinkButton()
                ->setIcon($this->iconFactory->getIcon($iconIdentifier, IconSize::SMALL))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:edit'))
                ->setHref((string)$editLink);
        }
        $primary->add('edit', $editButton);

        // "Info"
        $viewBigAction = null;
        if (!$isDeletePlaceHolder) {
            $label = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:showInfo');
            $viewBigAction = $this->componentFactory->createGenericButton()
                ->setIcon($this->iconFactory->getIcon('actions-document-info', IconSize::SMALL))
                ->setTitle($label)
                ->setAttributes(array_merge(
                    $this->createShowItemTagAttributesArray($table . ',' . $record->getUid()),
                    ['aria-haspopup' => 'dialog']
                ));
        }
        $secondary->add('info', $viewBigAction);

        // "Move" wizard link for pages/tt_content elements:
        if ($permsEdit && ($table === 'tt_content' || $table === 'pages') && $this->isEditable($table)) {
            if ($isL10nOverlay || $isDeletePlaceHolder) {
                $moveButton = null;
            } else {
                if ($table === 'pages') {
                    $linkTitleLL = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:move_page');
                    $icon = 'actions-page-move';
                    $url = $this->uriBuilder->buildUriFromRoute('move_page', [
                        'uid' => $record->getUid(),
                        'table' => $table,
                        'expandPage' => $record->getPid(),
                    ]);
                } else {
                    $linkTitleLL = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:move_record');
                    $icon = 'actions-document-move';
                    $url = (string)$this->uriBuilder->buildUriFromRoute('move_element', [
                        'uid' => $record->getUid(),
                        'originalPid' => $record->getPid(),
                        'expandPage' => $record->getPid(),
                        'returnUrl' => $this->listURL(),
                    ]);
                }
                $moveButton = $this->componentFactory->createGenericButton()
                    ->setTag('typo3-backend-dispatch-modal-button')
                    ->setIcon($this->iconFactory->getIcon($icon, IconSize::SMALL))
                    ->setTitle($linkTitleLL)
                    ->setAttributes([
                        'subject' => $linkTitleLL,
                        'url' => (string)$url,
                        'aria-label' => $linkTitleLL,
                    ]);
            }
            $secondary->add('move', $moveButton);
        }

        // If the table is NOT a read-only table, then show these links:
        if ($this->isEditable($table)) {
            // "Revert" link (history/undo)
            if (trim($userTsConfig['options.']['showHistory.'][$table] ?? $userTsConfig['options.']['showHistory'] ?? '1')) {

                $historyButton = null;
                if (!$isDeletePlaceHolder) {
                    $moduleUrl = $this->uriBuilder->buildUriFromRoute('record_history', [
                        'element' => $table . ':' . $record->getUid(),
                        'returnUrl' => $this->listURL(),
                    ])->withFragment('#latest');
                    $historyButton = $this->componentFactory->createLinkButton()
                        ->setIcon($this->iconFactory->getIcon('actions-document-history-open', IconSize::SMALL))
                        ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:history'))
                        ->setHref((string)$moduleUrl);
                }
                $secondary->add('history', $historyButton);
            }

            // "Edit Perms" link:
            if ($table === 'pages' && $this->moduleProvider->accessGranted('permissions_pages', $backendUser)) {
                $permsButton = null;
                if (!$isL10nOverlay && !$isDeletePlaceHolder) {
                    $params = [
                        'id' => $record->getUid(),
                        'action' => 'edit',
                        'returnUrl' => $this->listURL(),
                    ];
                    $href = $this->uriBuilder->buildUriFromRoute('permissions_pages', $params);
                    $permsButton = $this->componentFactory->createLinkButton()
                        ->setIcon($this->iconFactory->getIcon('actions-document-history-open', IconSize::SMALL))
                        ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:permissions'))
                        ->setHref((string)$href);
                }
                $secondary->add('perms', $permsButton);
            }

            // "New record after" link (ONLY if the records in the table are sorted by a "sortby"-row
            // or if default values can depend on previous record):
            if (($schema->hasCapability(TcaSchemaCapability::SortByField) || ($schema->getRawConfiguration()['useColumnsForDefaultValues'] ?? false)) && $permsEdit) {
                $neededPermission = $table === 'pages' ? Permission::PAGE_NEW : Permission::CONTENT_EDIT;
                if ($this->calcPerms->isGranted($neededPermission)) {
                    $newButton = null;
                    if (!$isL10nOverlay && !$isDeletePlaceHolder && $this->showNewRecLink($table)) {
                        $params = [
                            'edit' => [
                                $table => [
                                    (0 - $record->getUid()) => 'new',
                                ],
                            ],
                            'module' => $this->request->getAttribute('module')?->getIdentifier() ?? '',
                            'returnUrl' => $this->listURL(),
                        ];
                        $icon = ($table === 'pages' ? $this->iconFactory->getIcon('actions-page-new', IconSize::SMALL) : $this->iconFactory->getIcon('actions-plus', IconSize::SMALL));
                        $titleLabel = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:new');
                        if ($schema->hasCapability(TcaSchemaCapability::SortByField)) {
                            $titleLabel = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:newRecord');
                            if ($table === 'pages') {
                                $titleLabel = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:newPage');
                            }
                        }
                        $newLink = $this->uriBuilder->buildUriFromRoute('record_edit', $params);
                        $newButton = $this->componentFactory->createLinkButton()
                            ->setIcon($icon)
                            ->setTitle($titleLabel)
                            ->setHref((string)$newLink);
                    }
                    $secondary->add('new', $newButton);
                }
            }

            // "Hide/Unhide" links:
            $hiddenField = $schema->hasCapability(TcaSchemaCapability::RestrictionDisabledField) ? $schema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName() : '';
            if (!empty($hiddenField)
                && ($schema->getField($hiddenField)->supportsAccessControl() || $backendUser->check('non_exclude_fields', $table . ':' . $hiddenField))
            ) {
                if (!$permsEdit || $isDeletePlaceHolder || $this->isRecordCurrentBackendUser($record)) {
                    $hideButton = null;
                } else {
                    $visibleTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:hide' . ($table === 'pages' ? 'Page' : ''));
                    $visibleIcon = 'actions-edit-hide';
                    $visibleValue = '0';
                    $hiddenTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:unHide' . ($table === 'pages' ? 'Page' : ''));
                    $hiddenIcon = 'actions-edit-unhide';
                    $hiddenValue = '1';
                    if (($rawRecord = $record->getRawRecord())?->has($hiddenField) && ($rawRecord->get($hiddenField) ?? false)) {
                        $titleLabel = $hiddenTitle;
                        $iconIdentifier = $hiddenIcon;
                        $status = 'hidden';
                    } else {
                        $titleLabel = $visibleTitle;
                        $iconIdentifier = $visibleIcon;
                        $status = 'visible';
                    }

                    $attributes = [
                        'data-datahandler-action' => 'visibility',
                        'data-datahandler-status' => $status,
                        'data-datahandler-visible-label' => $visibleTitle,
                        'data-datahandler-visible-value' => $visibleValue,
                        'data-datahandler-hidden-label' => $hiddenTitle,
                        'data-datahandler-hidden-value' => $hiddenValue,
                    ];
                    $hideButton = $this->componentFactory->createGenericButton()
                        ->setIcon($this->iconFactory->getIcon($iconIdentifier, IconSize::SMALL))
                        ->setTitle($titleLabel)
                        ->setAttributes($attributes);
                }
                $primary->add('hide', $hideButton);
            }

            // "Up/Down" links
            if ($permsEdit && $schema->hasCapability(TcaSchemaCapability::SortByField) && !$this->sortField && !$this->searchLevels) {

                $moveUpButton = null;
                if (!$isL10nOverlay && !$isDeletePlaceHolder && isset($this->currentTable['prev'][$record->getUid()])) {
                    // Up
                    $params = [];
                    $params['redirect'] = $this->listURL();
                    $params['cmd'][$table][$record->getUid()]['move'] = $this->currentTable['prev'][$record->getUid()];
                    $url = (string)$this->uriBuilder->buildUriFromRoute('tce_db', $params);
                    $moveUpButton = $this->componentFactory->createLinkButton()
                        ->setIcon($this->iconFactory->getIcon('actions-move-up', IconSize::SMALL))
                        ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:moveUp'))
                        ->setHref((string)$url);
                }
                $primary->add('moveUp', $moveUpButton);

                $moveDownButton = null;
                if (!$isL10nOverlay && !$isDeletePlaceHolder && !empty($this->currentTable['next'][$record->getUid()])) {
                    // Down
                    $params = [];
                    $params['redirect'] = $this->listURL();
                    $params['cmd'][$table][$record->getUid()]['move'] = $this->currentTable['next'][$record->getUid()];
                    $url = (string)$this->uriBuilder->buildUriFromRoute('tce_db', $params);
                    $moveDownButton = $this->componentFactory->createLinkButton()
                        ->setIcon($this->iconFactory->getIcon('actions-move-down', IconSize::SMALL))
                        ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:moveDown'))
                        ->setHref($url);
                }
                $primary->add('moveDown', $moveDownButton);
            }

            $deleteButton = null;
            // "Delete" link:
            $disableDelete = (bool)trim((string)($userTsConfig['options.']['disableDelete.'][$table] ?? $userTsConfig['options.']['disableDelete'] ?? ''));
            if ($permsEdit
                && !$disableDelete
                && (($table === 'pages' && $localCalcPerms->deletePagePermissionIsGranted()) || ($table !== 'pages' && $this->calcPerms->editContentPermissionIsGranted()))
                && !$this->isRecordCurrentBackendUser($record)
                && !$isDeletePlaceHolder
            ) {
                $actionName = 'delete';
                $recordInfo = BackendUtility::getRecordTitle($table, $record);
                if ($this->getBackendUserAuthentication()->shallDisplayDebugInformation()) {
                    $recordInfo .= ' [' . $table . ':' . $record->getUid() . ']';
                }
                $refCountMsg = BackendUtility::referenceCount(
                    $table,
                    $record->getUid(),
                    LF . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToRecord'),
                    (string)$this->getReferenceCount($table, $record->getUid())
                ) . BackendUtility::translationCount(
                    $table,
                    $record->getUid(),
                    LF . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.translationsOfRecord')
                );

                $warningText = sprintf($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:' . $actionName . 'Warning'), trim($recordInfo)) . $refCountMsg;
                $icon = 'actions-edit-' . $actionName;
                $linkTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:' . $actionName);
                $titleText = $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:label.confirm.delete_record.title');

                $attributes = [
                    'data-severity' => 'warning',
                    'aria-haspopup' => 'dialog',
                    'data-button-ok-text' => $linkTitle,
                    'data-button-close-text' => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:cancel'),
                    'data-content' => $warningText,
                    'data-uri' => (string)$this->uriBuilder->buildUriFromRoute('tce_db', [
                        'cmd' => [
                            $table => [
                                $record->getUid() => [
                                    'delete' => true,
                                ],
                            ],
                        ],
                        'redirect' => $this->listURL(),
                    ]),
                    'data-title' => $titleText,
                ];
                $deleteButton = $this->componentFactory->createGenericButton()
                    ->setIcon($this->iconFactory->getIcon($icon, IconSize::SMALL))
                    ->setTitle($linkTitle)
                    ->setClasses('btn btn-default t3js-modal-trigger')
                    ->setAttributes($attributes);
            }
            $primary->add('delete', $deleteButton);

            // "Levels" links: Moving pages into new levels...
            if ($permsEdit && $table === 'pages' && !$this->searchLevels) {
                // Up (Paste as the page right after the current parent page)
                if ($this->calcPerms->createPagePermissionIsGranted()) {
                    $moveLeftButton = null;
                    if (!$isDeletePlaceHolder && !$isL10nOverlay) {
                        $params = [];
                        $params['redirect'] = $this->listURL();
                        $params['cmd'][$table][$record->getUid()]['move'] = -$this->id;
                        $url = $this->uriBuilder->buildUriFromRoute('tce_db', $params);
                        $moveLeftButton = $this->componentFactory->createGenericButton()
                            ->setIcon($this->iconFactory->getIcon('actions-move-left', IconSize::SMALL))
                            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:prevLevel'))
                            ->setHref((string)$url);
                    }
                    $secondary->add('moveLeft', $moveLeftButton);
                }
                $moveRightAction = null;
                // Down (Paste as subpage to the page right above)
                if (!$isL10nOverlay && !$isDeletePlaceHolder && !empty($this->currentTable['prevUid'][$record->getUid()])) {
                    $localCalcPerms = $this->getPagePermissionsForRecord(
                        $this->recordFactory->createResolvedRecordFromDatabaseRow(
                            'pages',
                            BackendUtility::getRecord('pages', $this->currentTable['prevUid'][$record->getUid()]) ?? [],
                            null,
                            $this->recordIdentityMap
                        )
                    );
                    if ($localCalcPerms->createPagePermissionIsGranted()) {
                        $params = [];
                        $params['redirect'] = $this->listURL();
                        $params['cmd'][$table][$record->getUid()]['move'] = $this->currentTable['prevUid'][$record->getUid()];
                        $url = $this->uriBuilder->buildUriFromRoute('tce_db', $params);
                        $moveRightAction = $this->componentFactory->createGenericButton()
                            ->setIcon($this->iconFactory->getIcon('actions-move-right', IconSize::SMALL))
                            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:nextLevel'))
                            ->setHref((string)$url);
                    }
                }
                $secondary->add('moveRight', $moveRightAction);
            }
        }

        // Add clipboard related actions
        $clipboardActions = $this->makeClip($table, $record);
        // Add the clipboard actions to the secondary cell group
        if ($clipboardActions !== []) {
            $secondary->add('divider', $this->componentFactory->createDropDownDivider());
            foreach ($clipboardActions as $identifier => $action) {
                $secondary->add($identifier, $action);
            }
        }

        $event = $this->eventDispatcher->dispatch(
            new ModifyRecordListRecordActionsEvent($primary, $secondary, $record, $this, $this->request)
        );

        $emptyAction = $this->componentFactory->createGenericButton()
            ->setTag('span')
            ->setIcon($this->iconFactory->getIcon('empty-empty'))
            ->setClasses('disabled')
            ->setAttributes([
                'aria-hidden' => 'true',
            ]);
        $primary = $event->getActionGroup(ActionGroup::primary);
        $output = ' <div class="btn-group">';
        foreach ($primary->getItems($emptyAction) as $action) {
            if (method_exists($action, 'setSize')) {
                $action->setSize(ButtonSize::MEDIUM);
            }
            $output .= $action->render();
        }
        $output .= '</div>';

        $secondary = $event->getActionGroup(ActionGroup::secondary);
        $cellOutput = '';
        foreach ($secondary->getItems() as $action) {
            if ($action instanceof GenericButton) {
                $action = $this->componentFactory->createDropDownItem()
                    ->setTag($action->getTag())
                    ->setLabel($action->getLabel() ?: $action->getTitle())
                    ->setIcon($action->getIcon())
                    ->setHref($action->getHref())
                    ->setAttributes([
                        ...$action->getAttributes(),
                        'class' => $action->getClasses(),
                    ]);
                $cellOutput .= '<li>' . $action->render() . '</li>';
                continue;
            }
            if ($action instanceof LinkButton) {
                $attributes = [];
                foreach ($action->getDataAttributes() as $key => $value) {
                    $attributes['data-' . $key] = $value;
                }
                $action = $this->componentFactory->createDropDownItem()
                    ->setLabel($action->getTitle())
                    ->setIcon($action->getIcon())
                    ->setHref($action->getHref())
                    ->setAttributes([
                        ...$attributes,
                        'class' => $action->getClasses(),
                        'role' => $action->getRole(),
                    ]);
                $cellOutput .= '<li>' . $action->render() . '</li>';
                continue;
            }
            $cellOutput .= '<li>' . $action->render() . '</li>';
        }

        if ($cellOutput !== '') {
            $icon = $this->iconFactory->getIcon('actions-menu-alternative', IconSize::SMALL);
            $title = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.more');
            // @todo Maybe this grouping can also be moved into the ComponentFactory
            $output .= ' <div class="btn-group dropdown">' .
                        '<a title="' . htmlspecialchars($title) . '" href="#actions_' . $table . '_' . $record->getUid() . '" class="btn btn-default dropdown-toggle dropdown-toggle-no-chevron" data-bs-toggle="dropdown" data-bs-boundary="window" aria-expanded="false">' . $icon->render() . '</a>' .
                '<ul id="actions_' . $table . '_' . $record->getUid() . '" class="dropdown-menu">' . $cellOutput . '</ul>' .
                '</div>';
        } else {
            $output .= ' <div class="btn-group">' . $emptyAction . '</div>';
        }

        return $output;
    }

    /**
     * Creates the clipboard actions for a single record in the listing.
     *
     * @param string $table The table
     * @param RecordInterface $record The record for which to create the clipboard actions
     * @return array<string, ComponentInterface>
     */
    public function makeClip(string $table, RecordInterface $record): array
    {
        // Return, if disabled:
        if (!$this->isClipboardFunctionalityEnabled($table, $record)) {
            return [];
        }
        $clipboardButtons = [];
        $isEditable = $this->isEditable($table);
        $schema = $this->tcaSchemaFactory->get($table);

        if ($this->clipObj->current === 'normal') {
            $isSel = $this->clipObj->isSelected($table, $record->getUid());

            $copyTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.' . ($isSel === 'copy' ? 'copyrelease' : 'copy'));
            $copyUrl = $this->clipObj->selUrlDB($table, (int)$record->getUid(), true, $isSel === 'copy');
            $clipboardButtons['copy'] = $this->componentFactory->createGenericButton()
                ->setIcon($this->iconFactory->getIcon($isSel === 'copy' ? 'actions-edit-copy-release' : 'actions-edit-copy', IconSize::SMALL))
                ->setTitle($copyTitle)
                ->setHref($copyUrl);
            // Calculate permission to cut page or content
            if ($table === 'pages') {
                $localCalcPerms = $this->getPagePermissionsForRecord($record);
                $permsEdit = $localCalcPerms->editPagePermissionIsGranted();
            } else {
                $permsEdit = $this->calcPerms->editContentPermissionIsGranted() && $this->getBackendUserAuthentication()->recordEditAccessInternals($table, $record);
            }
            if (!$isEditable || !$this->overlayEditLockPermissions($table, $record, $permsEdit)) {
                $clipboardButtons['cut'] = null;
            } else {
                $cutTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.' . ($isSel === 'cut' ? 'cutrelease' : 'cut'));
                $cutUrl = $this->clipObj->selUrlDB($table, (int)$record->getUid(), false, $isSel === 'cut');
                $clipboardButtons['cut'] = $this->componentFactory->createGenericButton()
                    ->setIcon($this->iconFactory->getIcon($isSel === 'cut' ? 'actions-edit-cut-release' : 'actions-edit-cut', IconSize::SMALL))
                    ->setTitle($cutTitle)
                    ->setHref($cutUrl);
            }
        }

        // Now, looking for selected elements from the current table:
        if ($isEditable
            && $schema->hasCapability(TcaSchemaCapability::SortByField)
            && $this->clipObj->elFromTable($table) !== []
            && $this->overlayEditLockPermissions($table, $record)
        ) {
            $clipboardButtons['divider'] = $this->componentFactory->createDropDownDivider();
            $pasteAfterUrl = $this->clipObj->pasteUrl($table, -$record->getUid());
            $pasteAfterTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:clip_pasteAfter');
            $pasteAfterContent = $this->clipObj->confirmMsgText($table, $record->getRawRecord()?->toArray(), 'after');
            $clipboardButtons['pasteAfter'] = $this->componentFactory->createGenericButton()
                ->setTag('button')
                ->setIcon($this->iconFactory->getIcon('actions-document-paste-after', IconSize::SMALL))
                ->setTitle($pasteAfterTitle)
                ->setAttributes([
                    'data-severity' => 'warning',
                    'aria-haspopup' => 'dialog',
                    'data-uri' => $pasteAfterUrl,
                    'data-content' => $pasteAfterContent,
                    'class' => 'btn btn-default t3js-modal-trigger',
                ]);
        }

        // Now, looking for elements in general:
        if ($table === 'pages' && $isEditable && $this->clipObj->elFromTable() !== []) {
            $pasteIntoUrl = $this->clipObj->pasteUrl('', $record->getUid());
            $pasteIntoTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:clip_pasteInto');
            $pasteIntoContent = $this->clipObj->confirmMsgText($table, $record->getRawRecord()?->toArray(), 'into');
            $clipboardButtons['pasteInto'] = $this->componentFactory->createGenericButton()
                ->setIcon($this->iconFactory->getIcon('actions-document-paste-into', IconSize::SMALL))
                ->setTitle($pasteIntoTitle)
                ->setAttributes([
                    'data-severity' => 'warning',
                    'aria-haspopup' => 'dialog',
                    'data-uri' => $pasteIntoUrl,
                    'data-content' => $pasteIntoContent,
                    'class' => 'btn btn-default t3js-modal-trigger',
                ]);
        }

        return $clipboardButtons;
    }

    /**
     * Adds the checkbox to select a single record in the listing
     *
     * @param string $table The table
     * @return string The checkbox for the record
     */
    public function makeCheckbox(string $table, RecordInterface $record): string
    {
        // Early return if current record is a "delete placeholder"
        if ($this->isRecordDeletePlaceholder($record)) {
            return '';
        }

        $schema = $this->tcaSchemaFactory->get($table);
        // Early return if current record is a translation
        if ($schema->isLanguageAware()
            && ($transPointerField = $schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName())
            && $record->getRawRecord()?->has($transPointerField)
            && $record->getRawRecord()->get($transPointerField) !== 0
        ) {
            return '';
        }

        // In case clipObj is not set, just add a checkbox without any clipboard functionality
        if ($this->clipObj === null) {
            return '
                <span class="form-check form-check-type-toggle">
                    <input class="form-check-input t3js-multi-record-selection-check" type="checkbox" />
                </span>';
        }

        // For the numeric clipboard pads (showing checkboxes where one can select elements on/off)
        // Setting name of the element in ->CBnames array:
        $identifier = $table . '|' . $record->getUid();
        $this->CBnames[] = $identifier;
        $isSelected = false;
        // If the "duplicateField" value is set then select all elements which are duplicates...
        if ($this->duplicateField && $record->getRawRecord()?->has($this->duplicateField)) {
            $fieldValue = (string)$record->getRawRecord()->get($this->duplicateField);
            $isSelected = in_array($fieldValue, $this->duplicateStack, true);
            $this->duplicateStack[] = $fieldValue;
        }
        // Adding the checkbox to the panel:
        return '
            <span class="form-check form-check-type-toggle">
                <input class="form-check-input t3js-multi-record-selection-check" type="checkbox" name="CBC[' . $identifier . ']" value="1" ' . ($isSelected ? 'checked="checked" ' : '') . '/>
            </span>';
    }

    /**
     * Creates the localization panel
     */
    public function makeLocalizationPanel(string $table, RecordInterface $record, array $translations, array $possibleTranslations = []): string
    {
        $out = '';
        // All records excluding pages
        if (empty($possibleTranslations)) {
            $possibleTranslations = $this->possibleTranslations;
            if ($table === 'pages') {
                // Calculate possible translations for pages
                $possibleTranslations = array_map(static fn(SiteLanguage $siteLanguage): int => $siteLanguage->getLanguageId(), $this->languagesAllowedForUser);
                $possibleTranslations = array_filter($possibleTranslations, static fn(int $languageUid): bool => $languageUid > 0);
            }
        }

        // Traverse page translations and add icon for each language that does NOT yet exist and is included in site configuration:
        $pageId = $table === 'pages' ? $record->getUid() : $record->getPid();
        $languageInformation = $this->translateTools->getSystemLanguages($pageId);

        foreach ($possibleTranslations as $lUid_OnPage) {
            if ($this->isEditable($table)
                && !$this->isRecordDeletePlaceholder($record)
                && !isset($translations[$lUid_OnPage])
                && $this->getBackendUserAuthentication()->checkLanguageAccess($lUid_OnPage)
            ) {
                $redirectUrl = (string)$this->uriBuilder->buildUriFromRoute(
                    'record_edit',
                    [
                        'justLocalized' => $table . ':' . $record->getUid() . ':' . $lUid_OnPage,
                        'returnUrl' => $this->listURL(),
                    ]
                );
                $params = [];
                $params['redirect'] = $redirectUrl;
                $params['cmd'][$table][$record->getUid()]['localize'] = $lUid_OnPage;
                $href = (string)$this->uriBuilder->buildUriFromRoute('tce_db', $params);
                $title = htmlspecialchars($languageInformation[$lUid_OnPage]['title'] ?? '');

                $lC = ($languageInformation[$lUid_OnPage]['flagIcon'] ?? false)
                    ? $this->iconFactory->getIcon($languageInformation[$lUid_OnPage]['flagIcon'], IconSize::SMALL)->setTitle($title)->render()
                    : $title;

                $out .= '<a href="' . htmlspecialchars($href) . '"'
                    . ' class="btn btn-default t3js-action-localize"'
                    . ' title="' . $title . '">'
                    . $lC . '</a> ';
            }
        }
        return $out;
    }

    /*********************************
     *
     * Helper functions
     *
     *********************************/

    /**
     * Returns the path for a certain pid
     * The result is cached internally for the session, thus you can call
     * this function as much as you like without performance problems.
     *
     * @param int $pid The page id for which to get the path
     * @return mixed[] The path.
     */
    public function recPath(int $pid): mixed
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
    protected function getPagePermissionsForRecord(RecordInterface $record): Permission
    {
        $pageId = $record->getPid();
        $schema = $this->tcaSchemaFactory->get($record->getMainType());
        if ($schema->isLanguageAware()
            && $record->getMainType() === 'pages'
            && ($transOrigPointerField = $schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName())
            && $record->getRawRecord()?->has($transOrigPointerField)
        ) {
            $pageId = $record->getRawRecord()->get($transOrigPointerField) ?: $record->getUid();
        }

        // If the listed table is 'pages' we have to request the permission settings for each page.
        // If the listed table is not 'pages' we have to request the permission settings from the parent page
        if (!isset($this->pagePermsCache[$pageId])) {
            $this->pagePermsCache[$pageId] = new Permission($this->getBackendUserAuthentication()->calcPerms(BackendUtility::getRecord('pages', $pageId)));
        }
        return $this->pagePermsCache[$pageId];
    }

    /**
     * Returns TRUE if a link for creating new records should be displayed for $table
     *
     * @param string $table Table name
     * @return bool Returns TRUE if a link for creating new records should be displayed for $table
     */
    public function showNewRecLink(string $table): bool
    {
        // No deny/allow tables are set:
        if (empty($this->allowedNewTables) && empty($this->deniedNewTables)) {
            return true;
        }
        return !in_array($table, $this->deniedNewTables)
            && (empty($this->allowedNewTables) || in_array($table, $this->allowedNewTables));
    }

    /**
     * Check if the record represents the current backend user
     */
    protected function isRecordCurrentBackendUser(RecordInterface $record): bool
    {
        return $record->getMainType() === 'be_users' && $record->getUid() === (int)$this->getBackendUserAuthentication()->user['uid'];
    }

    /**
     * Check if user is in workspace and given record is a delete placeholder
     */
    protected function isRecordDeletePlaceholder(RecordInterface $record): bool
    {
        return $this->getBackendUserAuthentication()->workspace > 0
            && $record instanceof Record && $record->getVersionInfo()?->getState() === VersionState::DELETE_PLACEHOLDER;
    }

    public function setIsEditable(bool $isEditable): void
    {
        $this->editable = $isEditable;
    }

    /**
     * Check if the table (and field) is readonly or editable.
     */
    public function isEditable(string $table, string $field = ''): bool
    {
        $backendUser = $this->getBackendUserAuthentication();
        $schema = $this->tcaSchemaFactory->get($table);
        return !($schema->hasCapability(TcaSchemaCapability::AccessReadOnly))
            && $this->editable
            && $backendUser->check('tables_modify', $table)
            && ($schema->isWorkspaceAware() || $backendUser->workspaceAllowsLiveEditingInTable($table))
            && (
                $field === ''
                || (
                    $schema->hasField($field)
                    && !($schema->getField($field)->getConfiguration()['readOnly'] ?? false)
                )
            );
    }

    /**
     * Check if user can edit records in the table
     */
    protected function canEditTable(string $table): bool
    {
        if ($table === 'pages') {
            $permsEdit = $this->calcPerms->editPagePermissionIsGranted();
        } else {
            $permsEdit = $this->calcPerms->editContentPermissionIsGranted();
        }

        return $permsEdit && $this->overlayEditLockPermissions($table);
    }

    /**
     * Check if the current record is locked by editlock. Pages are locked if their editlock flag is set,
     * records are if they are locked themselves or if the page they are on is locked (a page’s editlock
     * is transitive for its content elements).
     */
    protected function overlayEditLockPermissions(string $table, ?RecordInterface $record = null, bool $editPermission = true): bool
    {
        if ($editPermission && !$this->getBackendUserAuthentication()->isAdmin()) {
            $pagesEditLockFieldName = $this->tcaSchemaFactory->get('pages')->hasCapability(TcaSchemaCapability::EditLock) ? $this->tcaSchemaFactory->get('pages')->getCapability(TcaSchemaCapability::EditLock)->getFieldName() : '';
            // If no $row is submitted we only check for general edit lock of current page (except for table "pages")
            $pageHasEditLock = (bool)($this->pageRow[$pagesEditLockFieldName] ?? false);
            if (empty($record)) {
                return ($table === 'pages') || !$pageHasEditLock;
            }
            if (($table === 'pages' && $pagesEditLockFieldName && $record->getRawRecord()?->has($pagesEditLockFieldName) && $record->getRawRecord()->get($pagesEditLockFieldName))
                || ($table !== 'pages' && $pageHasEditLock)
            ) {
                $editPermission = false;
            } else {
                $schema = $this->tcaSchemaFactory->get($table);
                $recordEditLockFieldName = $schema->hasCapability(TcaSchemaCapability::EditLock) ? $schema->getCapability(TcaSchemaCapability::EditLock)->getFieldName() : '';
                if ($record->getRawRecord()?->has($recordEditLockFieldName) && $record->getRawRecord()->get($recordEditLockFieldName)) {
                    $editPermission = false;
                }
            }
        }
        return $editPermission;
    }

    public function setModuleData(ModuleData $moduleData): void
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
    public function start(int $id, string $table, int $pointer, string $search = '', int $levels = 0, int $showLimit = 0): void
    {
        $backendUser = $this->getBackendUserAuthentication();
        // Setting internal variables:
        // sets the parent id
        $this->id = (int)$id;
        if ($this->tcaSchemaFactory->has($table)) {
            // Setting single table mode, if table exists:
            $this->table = $table;
        }
        // Resolve unique table identifier for page translations. See getTable()
        if ($table === 'pages_translated') {
            $this->table = 'pages';
            $this->showOnlyTranslatedRecords = true;
        }
        $this->page = MathUtility::forceIntegerInRange((int)$pointer, 1, 10000000);
        $this->showLimit = MathUtility::forceIntegerInRange((int)$showLimit, 0, 10000);
        $this->searchString = trim($search);
        $this->searchLevels = (int)$levels;
        $this->sortField = (string)($this->request->getParsedBody()['sortField'] ?? $this->request->getQueryParams()['sortField'] ?? '');
        $this->sortRev = (bool)($this->request->getParsedBody()['sortRev'] ?? $this->request->getQueryParams()['sortRev'] ?? false);
        $this->duplicateField = (string)($this->request->getParsedBody()['duplicateField'] ?? $this->request->getQueryParams()['duplicateField'] ?? '');

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
        $permsClause = $expressionBuilder->and($backendUser->getPagePermsClause(Permission::PAGE_SHOW));
        // This will hide records from display - it has nothing to do with user rights!!
        $pidList = GeneralUtility::intExplode(',', (string)($backendUser->getTSConfig()['options.']['hideRecords.']['pages'] ?? ''), true);
        if (!empty($pidList)) {
            $permsClause = $permsClause->with($expressionBuilder->notIn('pages.uid', $pidList));
        }
        $this->perms_clause = (string)$permsClause;

        $this->possibleTranslations = $this->getPossibleTranslations($this->id);
        $this->setFields = $this->getBackendUserAuthentication()->getModuleData('list/displayFields') ?? [];
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
            $output .= $this->getTable($tableName);
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
        $tableNames = array_flip($this->tcaSchemaFactory->all()->getNames());
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
                // Don't show table if hidden by page TSconfig mod.web_list.hideTables
                $schema = $this->tcaSchemaFactory->get($tableName);
                $hideTable = $schema->hasCapability(TcaSchemaCapability::HideInUi)
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
        try {
            $orderedTableNames = GeneralUtility::makeInstance(DependencyOrderingService::class)
                ->orderByDependencies($tableNames);
        } catch (\UnexpectedValueException $e) {
            // If you have circular dependencies we just keep the original order and give a notice
            // Example mod.web_list.tableDisplayOrder.pages.after = tt_content
            $lang = $this->getLanguageService();
            $header = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.tableDisplayOrder.title');
            $msg = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.tableDisplayOrder.message');
            $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $msg, $header, ContextualFeedbackSeverity::WARNING, true);
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
            $orderedTableNames = $tableNames;
        }
        return array_keys($orderedTableNames);
    }

    /**
     * Returns a QueryBuilder configured to select $fields from $table where the pid is restricted
     * depending on the current searchlevel setting.
     *
     * @param string $table Table name
     * @param string[] $fields Field list to select, * for all
     */
    public function getQueryBuilder(
        string $table,
        array $fields = ['*'],
        bool $addSorting = true,
        int $firstResult = 0,
        int $maxResult = 0
    ): QueryBuilder {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUserAuthentication()->workspace));
        $queryBuilder
            ->select(...$fields)
            ->from($table);
        $schema = $this->tcaSchemaFactory->get($table);

        // Additional constraints
        if ($schema->isLanguageAware()) {
            // Only restrict to the default language if no search request is in place
            // And if only translations should be shown
            if ($this->searchString === '' && !$this->showOnlyTranslatedRecords) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->lte($schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName(), 0),
                        $queryBuilder->expr()->eq($schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName(), 0)
                    )
                );
            }

            // Restrict to filtered language
            if ($this->moduleData?->has('language')) {
                $filterLanguage = (int)$this->moduleData->get('language', -1);

                if ($filterLanguage !== -1) {
                    // Build list of allowed languages: always include "all language" (-1) and filtered language
                    $allowedLanguages = [-1, $filterLanguage];
                    // When not searching, or when filtering default language, include default language (0) as well
                    if ($this->searchString === '' || $filterLanguage === 0) {
                        $allowedLanguages[] = 0;
                    }
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->in(
                            $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName(),
                            $allowedLanguages
                        )
                    );
                }
            }
        }
        if ($table === 'pages' && $this->showOnlyTranslatedRecords && $schema->isLanguageAware()) {
            $queryBuilder->andWhere($queryBuilder->expr()->in(
                $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName(),
                $queryBuilder->quoteArrayBasedValueListToIntegerList(
                    array_keys($this->languagesAllowedForUser)
                )
            ));
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
                if ($schema->hasCapability(TcaSchemaCapability::SortByField)) {
                    $orderBy = $schema->getCapability(TcaSchemaCapability::SortByField)->getFieldName();
                } elseif ($schema->hasCapability(TcaSchemaCapability::DefaultSorting)) {
                    $orderBy = (string)$schema->getCapability(TcaSchemaCapability::DefaultSorting)->getValue();
                } else {
                    $orderBy = '';
                }
                $orderBys = QueryHelper::parseOrderBy($orderBy);
                foreach ($orderBys as $orderBy) {
                    $queryBuilder->addOrderBy($orderBy[0], $orderBy[1]);
                }
            }
        }

        // Build the query constraints
        $queryBuilder = $this->addPageIdConstraint($table, $queryBuilder, $this->searchLevels);
        $searchWhere = $this->makeSearchString($table, $this->id, $queryBuilder);
        if (!empty($searchWhere)) {
            $queryBuilder->andWhere($searchWhere);
        }

        // Filtering on displayable pages (permissions):
        if ($table === 'pages' && $this->perms_clause) {
            $queryBuilder->andWhere($this->perms_clause);
        }

        // Filter out records that are translated, if TSconfig mod.web_list.hideTranslations is set
        if ($schema->isLanguageAware()
            && (GeneralUtility::inList($this->hideTranslations, $table) || $this->hideTranslations === '*')
        ) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    $schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName(),
                    0
                )
            );
        } elseif ($schema->isLanguageAware() && $this->showOnlyTranslatedRecords) {
            // When only translated records should be shown, it is necessary to use l10n_parent=pageId, instead of
            // a check to the PID
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    $schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName(),
                    $queryBuilder->createNamedParameter(
                        $this->id,
                        Connection::PARAM_INT
                    )
                )
            );
        }

        // @todo This event should contain the $addSorting value, so listener knows when to add ORDER-BY stuff.
        //       Additionally, having QueryBuilder order-by with `addSorting: false` should be deprecated along
        //       with the additional event flag.
        $event = new ModifyDatabaseQueryForRecordListingEvent(
            $queryBuilder,
            $table,
            $this->id,
            $fields,
            $firstResult,
            $maxResult,
            $this
        );
        $this->eventDispatcher->dispatch($event);
        return $event->getQueryBuilder();
    }

    /**
     * Creates part of query for searching after a word ($this->searchString)
     * fields in input table.
     *
     * @param string $table Table, in which the fields are being searched.
     * @param int $currentPid Page id for the possible search limit
     * @return string Returns part of WHERE-clause for searching, if applicable.
     */
    protected function makeSearchString(string $table, int $currentPid, QueryBuilder $queryBuilder)
    {
        $expressionBuilder = $queryBuilder->expr();
        $constraints = [];
        $tablePidField = $table === 'pages' ? 'uid' : 'pid';
        // Make query only if table is valid and a search string is actually defined
        if (empty($this->searchString)) {
            return '';
        }

        $searchableFields = $this->searchableSchemaFieldsCollector->getFields($table);
        [$subSchemaDivisorFieldName, $fieldsSubSchemaTypes] = $this->searchableSchemaFieldsCollector->getSchemaFieldSubSchemaTypes($table);
        // Get fields from ctrl section of TCA first
        if (MathUtility::canBeInterpretedAsInteger($this->searchString)) {
            $constraints[] = $expressionBuilder->eq('uid', (int)$this->searchString);
            foreach ($searchableFields as $field) {
                $searchConstraint = null;
                if ($field instanceof NumberFieldType || $field instanceof DateTimeFieldType) {
                    $searchConstraint = $expressionBuilder->and(
                        $expressionBuilder->eq($field->getName(), (int)$this->searchString),
                        $expressionBuilder->eq($tablePidField, $currentPid)
                    );
                } else {
                    $searchConstraint = $expressionBuilder->like(
                        $field->getName(),
                        $queryBuilder->quote('%' . $this->searchString . '%')
                    );
                }

                // If this table has subtypes (e.g. tt_content.CType), we want to ensure that only CType that contain
                // e.g. "bodytext" in their list of fields, to search through them. This is important when a field
                // is filled but its type has been changed.
                if ($subSchemaDivisorFieldName !== ''
                    && isset($fieldsSubSchemaTypes[$field->getName()])
                    && $fieldsSubSchemaTypes[$field->getName()] !== []
                ) {
                    // Using `IN()` with a string-value quoted list is fine for all database systems, even when
                    // used on integer-typed fields and no additional work required here to mitigate something.
                    $searchConstraint = $queryBuilder->expr()->and(
                        $searchConstraint,
                        $queryBuilder->expr()->in(
                            $subSchemaDivisorFieldName,
                            $queryBuilder->quoteArrayBasedValueListToStringList($fieldsSubSchemaTypes[$field->getName()])
                        ),
                    );
                }

                $constraints[] = $searchConstraint;
            }
        } elseif ($searchableFields->count() > 0) {
            $like = $queryBuilder->quote('%' . $queryBuilder->escapeLikeWildcards($this->searchString) . '%');
            foreach ($searchableFields as $field) {
                $searchConstraint = $expressionBuilder->comparison(
                    'LOWER(' . $queryBuilder->castFieldToTextType($field->getName()) . ')',
                    'LIKE',
                    'LOWER(' . $like . ')'
                );
                // If this table has subtypes (e.g. tt_content.CType), we want to ensure that only CType that contain
                // e.g. "bodytext" in their list of fields, to search through them. This is important when a field
                // is filled but its type has been changed.
                if ($subSchemaDivisorFieldName !== ''
                    && isset($fieldsSubSchemaTypes[$field->getName()])
                    && $fieldsSubSchemaTypes[$field->getName()] !== []
                ) {
                    // Using `IN()` with a string-value quoted list is fine for all database systems, even when
                    // used on integer-typed fields and no additional work required here to mitigate something.
                    $searchConstraint = $queryBuilder->expr()->and(
                        $searchConstraint,
                        $queryBuilder->expr()->in(
                            $subSchemaDivisorFieldName,
                            $queryBuilder->quoteArrayBasedValueListToStringList($fieldsSubSchemaTypes[$field->getName()])
                        ),
                    );
                }

                $constraints[] = $searchConstraint;
            }
        }
        // If no search field conditions have been built ensure no results are returned
        if (empty($constraints)) {
            return '0=1';
        }

        return (string)$expressionBuilder->or(...$constraints);
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
            $url = $this->listURL('', $table, ['pointer']);
        } else {
            $url = $this->listURL('', '', ['sortField', 'sortRev', 'table', 'pointer']);
        }
        return '<a href="' . htmlspecialchars((string)$url) . '">' . $label . '</a>';
    }

    /**
     * Returns the title (based on $code) of a record (from table $table) with the proper link around (that is for 'pages'-records a link to the level of that record...)
     *
     * @param string $code Item title (not htmlspecialchars()'ed yet)
     * @return string The item title. Ready for HTML output (is htmlspecialchars()'ed)
     */
    public function linkWrapItems(string $table, int $uid, string $code, RecordInterface $record): string
    {
        $lang = $this->getLanguageService();
        $origCode = $code;
        // If the title is blank, make a "no title" label:
        if ((string)$code === '') {
            $code = '<i>[' . htmlspecialchars(
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title')
            ) . ']</i> - '
                . htmlspecialchars(BackendUtility::getRecordTitle($table, $record));
        } else {
            $code = htmlspecialchars($code);
        }
        switch ($this->clickTitleMode) {
            case 'edit':
                // If the listed table is 'pages' we have to request the permission settings for each page:
                if ($table === 'pages') {
                    $localCalcPerms = $this->getPagePermissionsForRecord($record);
                    $permsEdit = $localCalcPerms->editPagePermissionIsGranted();
                } else {
                    $backendUser = $this->getBackendUserAuthentication();
                    $permsEdit = $this->calcPerms->editContentPermissionIsGranted() && $backendUser->recordEditAccessInternals($table, $record);
                }
                // "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
                if ($permsEdit && $this->isEditable($table)) {
                    $params = [
                        'edit' => [
                            $table => [
                                $record->getUid() => 'edit',
                            ],
                        ],
                        'module' => $this->request->getAttribute('module')?->getIdentifier() ?? '',
                        'returnUrl' => $this->listURL(),
                    ];
                    $editLink = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $params);
                    $label = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:edit'));
                    $code = '<a href="' . htmlspecialchars($editLink) . '"'
                        . ' title="' . $label . '"'
                        . ' aria-label="' . $label . '">'
                        . $code . '</a>';
                }
                break;
            case 'show':
                // "Show" link
                if (($attributes = $this->getPreviewUriBuilder($table, $record)->serializeDispatcherAttributes()) !== null) {
                    $title = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'));
                    $code = '<button ' . $attributes
                        . ' title="' . $title . '"'
                        . ' aria-label="' . $title . '">'
                        . $code . '</button>';
                }
                break;
            case 'info':
                // "Info": (All records)
                $label = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:showInfo'));
                $code = '<a href="#" role="button"' // @todo add handler that triggers click on space key
                    . $this->createShowItemTagAttributes($table . ',' . (int)$record->getUid())
                    . ' title="' . $label . '"'
                    . ' aria-label="' . $label . '"'
                    . ' aria-haspopup="dialog">'
                    . $code
                    . '</a>';
                break;
            default:
                // Output the label now:
                if ($table === 'pages') {
                    $code = '<a href="' . htmlspecialchars(
                        (string)$this->listURL($uid, null, ['pointer'])
                    ) . '">' . $code . '</a>';
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
    protected function linkUrlMail(string $code, string $testString): string
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
     * Fixed GPvars are id, table, returnUrl, searchTerm, and search_levels
     * The GPvars "sortField" and "sortRev" are also included UNLESS they are found in the $exclList variable.
     *
     * @param string|int|null $altId Alternative id value. Enter blank string for the current id ($this->id)
     * @param string|null $table Table name to display. Use null for the current table.
     * @param string|array|null $exclList List of fields NOT to include ("sortField", "sortRev" or "pointer")
     */
    public function listURL(string|int|null $altId = null, ?string $table = null, array|string|null $exclList = null): UriInterface
    {
        $urlParameters = [];
        if ((string)$altId !== '') {
            $urlParameters['id'] = $altId;
        } else {
            $urlParameters['id'] = $this->id;
        }
        $urlParameters['table'] = $table ?? $this->table;
        if ($this->returnUrl) {
            $urlParameters['returnUrl'] = $this->returnUrl;
        }
        if (!is_array($exclList)) {
            $exclList = GeneralUtility::trimExplode(',', (string)$exclList, true);
        }
        if (($exclList === [] || !in_array('searchTerm', $exclList, true)) && $this->searchString) {
            $urlParameters['searchTerm'] = $this->searchString;
        }
        if ($this->searchLevels) {
            $urlParameters['search_levels'] = $this->searchLevels;
        }
        if (($exclList === [] || !in_array('pointer', $exclList, true)) && $this->page) {
            $urlParameters['pointer'] = $this->page;
        }
        if (($exclList === [] || !in_array('sortField', $exclList, true)) && $this->sortField) {
            $urlParameters['sortField'] = $this->sortField;
        }
        if (($exclList === [] || !in_array('sortRev', $exclList, true)) && $this->sortRev) {
            $urlParameters['sortRev'] = $this->sortRev;
        }
        if (($exclList === [] || !in_array('language', $exclList, true)) && $this->moduleData->get('language')) {
            $urlParameters['language'] = (int)$this->moduleData->get('language');
        }

        return $this->uriBuilder->buildUriFromRequest(
            $this->request,
            array_replace($urlParameters, $this->overrideUrlParameters)
        );
    }

    /**
     * Set URL parameters to override or add in the listUrl() method.
     *
     * @param string[] $urlParameters
     */
    public function setOverrideUrlParameters(array $urlParameters, ServerRequestInterface $request): void
    {
        $currentUrlParameter = $request->getParsedBody()['curUrl'] ?? $request->getQueryParams()['curUrl'] ?? '';
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
    public function setTableDisplayOrder(array $orderInformation): void
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

    public function getOverridePageIdList(): array
    {
        return $this->overridePageIdList;
    }

    /**
     * @param int[]|array $overridePageIdList
     */
    public function setOverridePageIdList(array $overridePageIdList): void
    {
        $this->overridePageIdList = array_map(intval(...), $overridePageIdList);
    }

    /**
     * Get all allowed mount pages to be searched in.
     *
     * @param int $id Page id
     * @param int $depth Depth to go down
     * @return int[]
     */
    protected function getSearchableWebmounts(int $id, int $depth): array
    {
        $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
        $hash = 'webmounts_list' . md5($id . '-' . $depth . '-' . $this->perms_clause);
        $idList = $runtimeCache->get($hash);
        if ($idList === false) {
            $backendUser = $this->getBackendUserAuthentication();

            if (!$backendUser->isAdmin() && $id === 0) {
                $mountPoints = $backendUser->getWebmounts();
            } else {
                $mountPoints = [$id];
            }
            // Add the initial mount points to the pids
            $idList = $mountPoints;
            $repository = GeneralUtility::makeInstance(PageTreeRepository::class);
            $repository->setAdditionalWhereClause($this->perms_clause);
            $pages = $repository->getFlattenedPages($mountPoints, $depth);
            foreach ($pages as $page) {
                $idList[] = (int)$page['uid'];
            }
            $idList = array_unique($idList);
            $runtimeCache->set($hash, $idList);
        }

        return $idList;
    }

    /**
     * Add conditions to the QueryBuilder object ($queryBuilder) to limit a
     * query to a list of page IDs based on the current search level setting.
     *
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
                    $queryBuilder->createNamedParameter($pageRecord['pid'] ?? 0, Connection::PARAM_INT)
                )
            );
        } elseif ($searchLevels === 0) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    $tableName . '.pid',
                    $queryBuilder->createNamedParameter($this->id, Connection::PARAM_INT)
                )
            );
        } elseif ($searchLevels > 0) {
            $allowedMounts = $this->getSearchableWebmounts($this->id, $searchLevels);
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

    protected function getBackendUserAuthentication(): BackendUserAuthentication
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
        $out = '<tr ' . $rowParams . ' data-uid="' . $dataUid . '" data-l10nparent="' . $l10nParent . '" data-multi-record-selection-element="true">';

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
        $pagesSchema = $this->tcaSchemaFactory->get('pages');
        if (!$pagesSchema->isLanguageAware()) {
            return [];
        }
        // Look up page overlays:
        $localizationParentField = $pagesSchema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName();
        $languageField = $pagesSchema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUserAuthentication()->workspace));
        $result = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq($localizationParentField, $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT)),
                    $queryBuilder->expr()->in($languageField, $queryBuilder->createNamedParameter($availableSystemLanguageUids, Connection::PARAM_INT_ARRAY)),
                    $queryBuilder->expr()->gt(
                        $languageField,
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
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
     * @return string Language icon
     */
    protected function languageFlag(string $table, RecordInterface $record): string
    {
        $schema = $this->tcaSchemaFactory->get($table);
        $pageId = $record->getPid();
        $languageUid = 0;
        if ($schema->isLanguageAware()) {
            if ($table === 'pages'
                && ($transOrigPointerField = $schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName())
                && $record->getRawRecord()?->has($transOrigPointerField)
            ) {
                $pageId = $record->getRawRecord()->get($transOrigPointerField) ?: $record->getUid();
            }
            if (($languageField = $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName()) && $record->getRawRecord()?->has($languageField)) {
                $languageUid = $record->getRawRecord()->get($languageField);
            }
        }

        $languageInformation = $this->translateTools->getSystemLanguages($pageId);
        $title = htmlspecialchars($languageInformation[$languageUid]['title'] ?? '');
        $indent = !$this->showOnlyTranslatedRecords && $this->isLocalized($record) ? '<span class="indent indent-inline-block" style="--indent-level: 1"></span> ' : '';
        if ($languageInformation[$languageUid]['flagIcon'] ?? false) {
            return $indent . $this->iconFactory
                ->getIcon($languageInformation[$languageUid]['flagIcon'], IconSize::SMALL)
                ->setTitle($title)
                ->render() . ' ' . $title;
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
                . '<span class="visually-hidden">' . $showReferences . '</span>'
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
                <button type="button" class="dropdown-item" disabled data-multi-record-selection-check-action="check-all" title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.checkAll')) . '">
                    ' . $this->iconFactory->getIcon('actions-selection-elements-all', IconSize::SMALL)->render() . '
                    ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.checkAll')) . '
                </button>
            </li>';

        $dropdownItems['checkNone'] = '
            <li>
                <button type="button" class="dropdown-item" disabled data-multi-record-selection-check-action="check-none" title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.uncheckAll')) . '">
                    ' . $this->iconFactory->getIcon('actions-selection-elements-none', IconSize::SMALL)->render() . '
                    ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.uncheckAll')) . '
                </button>
            </li>';

        $dropdownItems['toggleSelection'] = '
            <li>
                <button type="button" class="dropdown-item" disabled data-multi-record-selection-check-action="toggle" title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.toggleSelection')) . '">
                    ' . $this->iconFactory->getIcon('actions-selection-elements-invert', IconSize::SMALL)->render() . '
                    ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.toggleSelection')) . '
                </button>
            </li>';

        return '
            <div class="btn-group dropdown">
                <button type="button" class="dropdown-toggle dropdown-toggle-link t3js-multi-record-selection-check-actions-toggle" data-bs-toggle="dropdown" data-bs-boundary="window" aria-expanded="false" aria-label="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.openSelectionOptions')) . '">
                    ' . $this->iconFactory->getIcon('actions-selection', IconSize::SMALL) . '
                </button>
                <ul class="dropdown-menu t3js-multi-record-selection-check-actions">
                    ' . implode(PHP_EOL, $dropdownItems) . '
                </ul>
            </div>';
    }

    /**
     * Render the multi record selection actions, which are shown as soon as one record is selected
     */
    protected function renderMultiRecordSelectionActions(string $table, array $currentIdList): string
    {
        $actions = [];
        $lang = $this->getLanguageService();
        $schema = $this->tcaSchemaFactory->get($table);
        $userTsConfig = $this->getBackendUserAuthentication()->getTSConfig();
        $addClipboardActions = $this->showClipboardActions && $this->isClipboardFunctionalityEnabled($table);
        $editPermission = (
            ($table === 'pages') ? $this->calcPerms->editPagePermissionIsGranted() : $this->calcPerms->editContentPermissionIsGranted()
        ) && $this->overlayEditLockPermissions($table);

        // Add actions in case table can be modified by the current user
        if ($editPermission && $this->isEditable($table)) {
            $editActionConfiguration = [
                'idField' => 'uid',
                'tableName' => $table,
                'returnUrl' => (string)$this->listURL(),
            ];
            $actions['edit'] = '
                <div class="btn-group">
                    <button
                        type="button"
                        title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.edit')) . '"
                        class="btn btn-sm btn-default"
                        data-multi-record-selection-action="edit"
                        data-multi-record-selection-action-config="' . GeneralUtility::jsonEncodeForHtmlAttribute($editActionConfiguration) . '"
                    >
                        ' . $this->iconFactory->getIcon('actions-document-open', IconSize::SMALL)->render() . '
                        ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.edit')) . '
                    </button>
                    <button
                        type="button"
                        title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editColumns')) . '"
                        class="btn btn-sm btn-default"
                        data-multi-record-selection-action="edit"
                        data-multi-record-selection-action-config="' . GeneralUtility::jsonEncodeForHtmlAttribute(array_merge($editActionConfiguration, ['columnsOnly' => array_values($this->getColumnsToRender($table, false))])) . '"
                    >
                        ' . $this->iconFactory->getIcon('actions-document-open', IconSize::SMALL)->render() . '
                        ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editColumns')) . '
                    </button>
                </div>';

            if (!(bool)trim((string)($userTsConfig['options.']['disableDelete.'][$table] ?? $userTsConfig['options.']['disableDelete'] ?? ''))) {
                $deleteActionConfiguration = GeneralUtility::jsonEncodeForHtmlAttribute([
                    'idField' => 'uid',
                    'tableName' => $table,
                    'ok' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:button.delete'),
                    'cancel' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:button.cancel'),
                    'title' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:clip_deleteMarked'),
                    'content' => sprintf($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:clip_deleteMarkedWarning'), $schema->getTitle($lang->sL(...))),
                ], true);
                $actions['delete'] = '
                    <button
                        type="button"
                        title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete')) . '"
                        class="btn btn-sm btn-default"
                        data-multi-record-selection-action="delete"
                        data-multi-record-selection-action-config="' . $deleteActionConfiguration . '"
                        aria-haspopup="dialog"
                    >
                        ' . $this->iconFactory->getIcon('actions-edit-delete', IconSize::SMALL)->render() . '
                        ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete')) . '
                    </button>';
            }
        }

        // Add clipboard actions in case they  are enabled and clipboard is not deactivated
        if ($addClipboardActions && (string)($this->modTSconfig['enableClipBoard'] ?? '') !== 'deactivated') {
            $copyMarked = '
                <button type="button"
                    class="btn btn-sm btn-default"
                    ' . ($this->clipObj->current === 'normal' ? 'disabled' : '') . '
                    title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.transferToClipboard')) . '"
                    data-multi-record-selection-action="copyMarked"
                >
                    ' . $this->iconFactory->getIcon('actions-edit-copy', IconSize::SMALL)->render() . '
                    ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.transferToClipboard')) . '
                </button>';
            $removeMarked = '
                <button type="button"
                    class="btn btn-sm btn-default"
                    ' . ($this->clipObj->current === 'normal' ? 'disabled' : '') . '
                    title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.removeFromClipboard')) . '"
                    data-multi-record-selection-action="removeMarked"
                >
                    ' . $this->iconFactory->getIcon('actions-minus', IconSize::SMALL)->render() . '
                    ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.removeFromClipboard')) . '
                </button>';
            // Add "copy marked" after "edit", or in case "edit" is not set, as first item
            if (!isset($actions['edit'])) {
                $actions = array_merge(['copyMarked' => $copyMarked], $actions);
            } else {
                $end = array_splice($actions, (int)(array_search('edit', array_keys($actions), true)) + 1);
                $actions = array_merge($actions, ['copyMarked' => $copyMarked, 'removeMarked' => $removeMarked], $end);
            }
        }

        $event = $this->eventDispatcher->dispatch(
            new ModifyRecordListTableActionsEvent($actions, $table, $currentIdList, $this)
        );
        /** @var array<string, string> $actions */
        $actions = $event->getActions();

        if ($actions === []) {
            // In case the user does not have permissions to execute on of the above
            // actions or a hook removed all remaining actions, inform the user about this.
            return '
                <span class="badge badge-info">
                    ' . htmlspecialchars($lang->sL($event->getNoActionLabel() ?: 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noActionAvailable')) . '
                </span>
                ';
        }

        return implode(LF, $actions);
    }

    /**
     * If enabled, only translations are shown (= only with l10n_parent)
     * See the use case in RecordList class, where a list of page translations is rendered before.
     */
    public function showOnlyTranslatedRecords(bool $showOnlyTranslatedRecords): void
    {
        $this->showOnlyTranslatedRecords = $showOnlyTranslatedRecords;
    }

    /**
     * Creates data attributes to be handles in moddule `TYPO3/CMS/Backend/ActionDispatcher`
     */
    protected function createShowItemTagAttributes(string $arguments): string
    {
        return GeneralUtility::implodeAttributes([
            'data-dispatch-action' => 'TYPO3.InfoWindow.showItem',
            'data-dispatch-args-list' => $arguments,
        ], true);
    }

    /**
     * Creates data attributes to be handles in moddule `TYPO3/CMS/Backend/ActionDispatcher`
     */
    protected function createShowItemTagAttributesArray(string $arguments): array
    {
        return [
            'data-dispatch-action' => 'TYPO3.InfoWindow.showItem',
            'data-dispatch-args-list' => $arguments,
        ];
    }

    protected function getFieldLabel(TcaSchema $schema, string $field): string
    {
        $table = $schema->getName();
        // Check if $field is really a field and get the label and remove the colons at the end
        if ($schema->hasField($field) && $schema->getField($field)->getLabel()) {
            $label = $schema->getField($field)->getLabel();
            $tsConfig = BackendUtility::getPagesTSconfig($this->id)['TCEFORM.'][$table . '.'] ?? null;
            $tsConfigForTable = is_array($tsConfig) ? $tsConfig : null;
            $tsConfigForField = isset($tsConfigForTable[$field . '.']) && is_array($tsConfigForTable[$field . '.'])
                ? $tsConfigForTable[$field . '.']
                : [];
            $label = $this->getLanguageService()->translateLabel(
                $tsConfigForField['label.'] ?? [],
                $tsConfigForField['label'] ?? $label
            );
            $label = htmlspecialchars(rtrim(trim($label), ':'));
        } elseif ($specialLabel = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.' . $field)) {
            // Special label exists for this field (Probably a management field, e.g. sorting)
            $label = htmlspecialchars($specialLabel);
        } else {
            // No TCA field, only output the $field variable with square brackets []
            $label = '[' . rtrim(trim(htmlspecialchars($field)), ':') . ']';
        }

        return $label;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    public function setLanguagesAllowedForUser(array $languagesAllowedForUser): DatabaseRecordList
    {
        $this->languagesAllowedForUser = $languagesAllowedForUser;
        return $this;
    }

    protected function isLocalized(RecordInterface $record): bool
    {
        if ($record instanceof Record) {
            return $record->getLanguageId() > 0 && $record->getLanguageInfo()?->getTranslationParent() > 0;
        }
        $schema = $this->tcaSchemaFactory->get($record->getMainType());
        if ($schema->isLanguageAware() && ($rawRecord = $record->getRawRecord()) !== null) {
            $languageFieldName = $schema->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName();
            $transPointerFieldName = $schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName();
            return $rawRecord->has($languageFieldName)
                && $rawRecord->get($languageFieldName) > 0
                && $rawRecord->has($transPointerFieldName)
                && $rawRecord->get($transPointerFieldName) > 0;
        }
        return false;
    }

    /**
     * Check whether the clipboard functionality is generally enabled.
     * In case a row is given, this checks if the record is neither
     * a "delete placeholder", nor a translation, nor a version
     */
    protected function isClipboardFunctionalityEnabled(string $table, ?RecordInterface $record = null): bool
    {
        $schema = $this->tcaSchemaFactory->get($table);
        $transOrigPointerFieldName = $schema->isLanguageAware() ? $schema->getCapability(TcaSchemaCapability::Language)->getTranslationOriginPointerField()->getName() : '';
        return $this->clipObj !== null
            && ($table !== 'pages' || !$this->showOnlyTranslatedRecords)
            && (
                $record === null
                || (
                    !$this->isRecordDeletePlaceholder($record)
                    && (!$transOrigPointerFieldName || ($record->getRawRecord()?->has($transOrigPointerFieldName) && (int)($record->getRawRecord()->get($transOrigPointerFieldName) === 0)))
                )
            )
            && ($schema->isWorkspaceAware() || $this->getBackendUserAuthentication()->workspaceAllowsLiveEditingInTable($table));
    }
}
