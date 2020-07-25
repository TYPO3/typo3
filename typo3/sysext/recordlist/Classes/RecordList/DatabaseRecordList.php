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

use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Module\BaseScriptClass;
use TYPO3\CMS\Backend\RecordList\RecordListGetTableHookInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\CsvUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class for rendering of Web>List module
 * @internal This class is a specific TYPO3 Backend implementation and is not part of the TYPO3's Core API.
 */
class DatabaseRecordList
{
    use PublicPropertyDeprecationTrait;

    /**
     * @var array
     */
    protected $deprecatedPublicProperties = [
        'newWizards' => 'Using $newWizards of class DatabaseRecordList from outside is discouraged, property will be removed in TYPO3 v10.0.',
    ];

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
     * If TRUE, the control panel will contain links to the create-new wizards for
     * pages and tt_content elements (normally, the link goes to just creating a new
     * element without the wizards!).
     *
     * @var bool
     * @deprecated and unused since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public $newWizards = false;

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
     * Count of record rows in view
     *
     * @var int
     */
    public $totalRowCount;

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
     * Shared module configuration, used by localization features
     *
     * @var array
     */
    public $modSharedTSconfig = [];

    /**
     * Contains page translation languages
     *
     * @var array
     */
    public $pageOverlays = [];

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
     * default Max items shown per table in "multi-table mode", may be overridden by tables.php
     *
     * @var int
     */
    public $itemsLimitPerTable = 20;

    /**
     * Keys are fieldnames and values are td-parameters to add in addElement(), please use $addElement_tdCSSClass for CSS-classes;
     *
     * @var array
     */
    public $addElement_tdParams = [];

    /**
     * Page id
     *
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $no_noWrap = 0;

    /**
     * Set to zero, if you don't want a left-margin with addElement function
     *
     * @var int
     */
    public $setLMargin = 1;

    /**
     * Used for tracking duplicate values of fields
     *
     * @var string[]
     */
    public $duplicateStack = [];

    /**
     * Current script name
     *
     * @var string
     */
    public $script = 'index.php';

    /**
     * If TRUE, records are listed only if a specific table is selected.
     *
     * @var bool
     */
    public $listOnlyInSingleTableMode = false;

    /**
     * Script URL
     *
     * @var string
     */
    public $thisScript = '';

    /**
     * JavaScript code accumulation
     *
     * @var string
     */
    public $JScode = '';

    /**
     * @var TranslationConfigurationProvider
     */
    public $translateTools;

    /**
     * default Max items shown per table in "single-table mode", may be overridden by tables.php
     *
     * @var int
     */
    public $itemsLimitSingleTable = 100;

    /**
     * Array of collapsed / uncollapsed tables in multi table view
     *
     * @var int[][]
     */
    public $tablesCollapsed = [];

    /**
     * @var array[] Module configuration
     */
    public $modTSconfig;

    /**
     * String with accumulated HTML content
     *
     * @var string
     */
    public $HTMLcode = '';

    /**
     * Keys are fieldnames and values are td-css-classes to add in addElement();
     *
     * @var array
     */
    public $addElement_tdCssClass = [];

    /**
     * Thumbnails on records containing files (pictures)
     *
     * @var bool
     */
    public $thumbs = 0;

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
     * If set this is <td> CSS-classname for odd columns in addElement. Used with db_layout / pages section
     *
     * @var string
     */
    public $oddColumnsCssClass = '';

    /**
     * Not used in this class - but maybe extension classes...
     * Max length of strings
     *
     * @var int
     */
    public $fixedL = 30;

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
     * @var int
     */
    public $showIcon = 1;

    /**
     * Levels to search down.
     *
     * @var int
     */
    public $searchLevels = '';

    /**
     * "LIMIT " in SQL...
     *
     * @var int
     */
    public $iLimit = 0;

    /**
     * Set to the total number of items for a table when selecting.
     *
     * @var string
     */
    public $totalItems = '';

    /**
     * OBSOLETE - NOT USED ANYMORE. leftMargin
     *
     * @var int
     */
    public $leftMargin = 0;

    /**
     * TSconfig which overwrites TCA-Settings
     *
     * @var mixed[][]
     */
    public $tableTSconfigOverTCA = [];

    /**
     * Loaded with page record with version overlay if any.
     *
     * @var string[]
     */
    public $pageRecord = [];

    /**
     * Fields to display for the current table
     *
     * @var string[]
     */
    public $setFields = [];

    /**
     * Counter increased for each element. Used to index elements for the JavaScript-code that transfers to the clipboard
     *
     * @var int
     */
    public $counter = 0;

    /**
     * Pointer for browsing list
     *
     * @var int
     */
    public $firstElementNumber = 0;

    /**
     * Counting the elements no matter what...
     *
     * @var int
     */
    public $eCounter = 0;

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
     * Translations of the current record
     *
     * @var string[]
     */
    public $translations;

    /**
     * select fields for the query which fetches the translations of the current
     * record
     *
     * @var string
     */
    public $selFieldList;

    /**
     * @var mixed[]
     */
    public $pageinfo;

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
     * All languages that are included in the site configuration
     * for the current page. New records can only be created in those
     * languages.
     *
     * @var array
     */
    protected $systemLanguagesOnPage;

    /**
     * All languages that are allowed by the user
     *
     * @var array
     */
    protected $languagesAllowedForUser = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        if (isset($GLOBALS['BE_USER']->uc['titleLen']) && $GLOBALS['BE_USER']->uc['titleLen'] > 0) {
            $this->fixedL = $GLOBALS['BE_USER']->uc['titleLen'];
        }
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->getTranslateTools();
        $this->determineScriptUrl();

        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform
     * operations.
     *
     * @return string[] All available buttons as an assoc. array
     */
    public function getButtons()
    {
        $module = $this->getModule();
        $backendUser = $this->getBackendUserAuthentication();
        $lang = $this->getLanguageService();
        $buttons = [
            'csh' => '',
            'view' => '',
            'edit' => '',
            'hide_unhide' => '',
            'move' => '',
            'new_record' => '',
            'paste' => '',
            'level_up' => '',
            'cache' => '',
            'reload' => '',
            'shortcut' => '',
            'back' => '',
            'csv' => '',
            'export' => ''
        ];
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        // Get users permissions for this page record:
        $localCalcPerms = $backendUser->calcPerms($this->pageRow);
        // CSH
        if ((string)$this->id === '') {
            $buttons['csh'] = BackendUtility::cshItem('xMOD_csh_corebe', 'list_module_noId');
        } elseif (!$this->id) {
            $buttons['csh'] = BackendUtility::cshItem('xMOD_csh_corebe', 'list_module_root');
        } else {
            $buttons['csh'] = BackendUtility::cshItem('xMOD_csh_corebe', 'list_module');
        }
        if (isset($this->id)) {
            // View Exclude doktypes 254,255 Configuration:
            // mod.web_list.noViewWithDokTypes = 254,255
            if (isset($module->modTSconfig['properties']['noViewWithDokTypes'])) {
                $noViewDokTypes = GeneralUtility::trimExplode(',', $module->modTSconfig['properties']['noViewWithDokTypes'], true);
            } else {
                //default exclusion: doktype 254 (folder), 255 (recycler)
                $noViewDokTypes = [
                    PageRepository::DOKTYPE_SYSFOLDER,
                    PageRepository::DOKTYPE_RECYCLER
                ];
            }
            if (!in_array($this->pageRow['doktype'], $noViewDokTypes)) {
                $onClick = htmlspecialchars(BackendUtility::viewOnClick($this->id, '', BackendUtility::BEgetRootLine($this->id)));
                $buttons['view'] = '<a href="#" onclick="' . $onClick . '" title="'
                    . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage')) . '">'
                    . $this->iconFactory->getIcon('actions-document-view', Icon::SIZE_SMALL)->render() . '</a>';
            }
            // New record on pages that are not locked by editlock
            if (!$module->modTSconfig['properties']['noCreateRecordsLink'] && $this->editLockPermissions()) {
                $onClick = htmlspecialchars('return jumpExt(' . GeneralUtility::quoteJSvalue((string)$uriBuilder->buildUriFromRoute('db_new', ['id' => $this->id])) . ');');
                $buttons['new_record'] = '<a href="#" onclick="' . $onClick . '" title="'
                    . htmlspecialchars($lang->getLL('newRecordGeneral')) . '">'
                    . $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL)->render() . '</a>';
            }
            // If edit permissions are set, see
            // \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
            if ($localCalcPerms & Permission::PAGE_EDIT && !empty($this->id) && $this->editLockPermissions() && $this->getBackendUserAuthentication()->checkLanguageAccess(0)) {
                // Edit
                $params = '&edit[pages][' . $this->pageRow['uid'] . ']=edit';
                $onClick = htmlspecialchars(BackendUtility::editOnClick($params, '', -1));
                $buttons['edit'] = '<a href="#" onclick="' . $onClick . '" title="' . htmlspecialchars($lang->getLL('editPage')) . '">'
                    . $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render()
                    . '</a>';
            }
            // Paste
            if (($localCalcPerms & Permission::PAGE_NEW || $localCalcPerms & Permission::CONTENT_EDIT) && $this->editLockPermissions()) {
                $elFromTable = $this->clipObj->elFromTable('');
                if (!empty($elFromTable)) {
                    $confirmText = $this->clipObj->confirmMsgText('pages', $this->pageRow, 'into', $elFromTable);
                    $buttons['paste'] = '<a'
                        . ' href="' . htmlspecialchars($this->clipObj->pasteUrl('', $this->id)) . '"'
                        . ' title="' . htmlspecialchars($lang->getLL('clip_paste')) . '"'
                        . ' class="t3js-modal-trigger"'
                        . ' data-severity="warning"'
                        . ' data-title="' . htmlspecialchars($lang->getLL('clip_paste')) . '"'
                        . ' data-content="' . htmlspecialchars($confirmText) . '"'
                        . '>'
                        . $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)->render()
                        . '</a>';
                }
            }
            // Cache
            $buttons['cache'] = '<a href="' . htmlspecialchars($this->listURL() . '&clear_cache=1') . '" title="'
                . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.clear_cache')) . '">'
                . $this->iconFactory->getIcon('actions-system-cache-clear', Icon::SIZE_SMALL)->render() . '</a>';
            if ($this->table && (!isset($module->modTSconfig['properties']['noExportRecordsLinks'])
                || (isset($module->modTSconfig['properties']['noExportRecordsLinks'])
                    && !$module->modTSconfig['properties']['noExportRecordsLinks']))
            ) {
                // CSV
                $buttons['csv'] = '<a href="' . htmlspecialchars($this->listURL() . '&csv=1') . '" title="'
                    . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.csv')) . '">'
                    . $this->iconFactory->getIcon('actions-document-export-csv', Icon::SIZE_SMALL)->render() . '</a>';
                // Export
                if (ExtensionManagementUtility::isLoaded('impexp')) {
                    $url = (string)$uriBuilder->buildUriFromRoute('xMOD_tximpexp', ['tx_impexp[action]' => 'export']);
                    $buttons['export'] = '<a href="' . htmlspecialchars($url . '&tx_impexp[list][]='
                            . rawurlencode($this->table . ':' . $this->id)) . '" title="'
                        . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.export')) . '">'
                        . $this->iconFactory->getIcon('actions-document-export-t3d', Icon::SIZE_SMALL)->render() . '</a>';
                }
            }
            // Reload
            $buttons['reload'] = '<a href="' . htmlspecialchars($this->listURL()) . '" title="'
                . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload')) . '">'
                . $this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL)->render() . '</a>';
            // Shortcut
            if ($backendUser->mayMakeShortcut()) {
                $buttons['shortcut'] = $this->getDocumentTemplate()->makeShortcutIcon(
                    'id, M, imagemode, pointer, table, search_field, search_levels, showLimit, sortField, sortRev',
                    implode(',', array_keys($this->MOD_MENU)),
                    'web_list'
                );
            }
            // Back
            if ($this->returnUrl) {
                $href = htmlspecialchars(GeneralUtility::linkThisUrl($this->returnUrl, ['id' => $this->id]));
                $buttons['back'] = '<a href="' . $href . '" class="typo3-goBack" title="'
                    . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack')) . '">'
                    . $this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL)->render() . '</a>';
            }
        }
        return $buttons;
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform
     * operations.
     *
     * @param ModuleTemplate $moduleTemplate
     */
    public function getDocHeaderButtons(ModuleTemplate $moduleTemplate)
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $module = $this->getModule();
        $backendUser = $this->getBackendUserAuthentication();
        $lang = $this->getLanguageService();
        // Get users permissions for this page record:
        $localCalcPerms = $backendUser->calcPerms($this->pageRow);
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
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
            // View Exclude doktypes 254,255 Configuration:
            // mod.web_list.noViewWithDokTypes = 254,255
            if (isset($module->modTSconfig['properties']['noViewWithDokTypes'])) {
                $noViewDokTypes = GeneralUtility::trimExplode(',', $module->modTSconfig['properties']['noViewWithDokTypes'], true);
            } else {
                //default exclusion: doktype 254 (folder), 255 (recycler)
                $noViewDokTypes = [
                    PageRepository::DOKTYPE_SYSFOLDER,
                    PageRepository::DOKTYPE_RECYCLER
                ];
            }
            // New record on pages that are not locked by editlock
            if (!$module->modTSconfig['properties']['noCreateRecordsLink'] && $this->editLockPermissions()) {
                $onClick = 'return jumpExt(' . GeneralUtility::quoteJSvalue((string)$uriBuilder->buildUriFromRoute('db_new', ['id' => $this->id])) . ');';
                $newRecordButton = $buttonBar->makeLinkButton()
                    ->setHref('#')
                    ->setOnClick($onClick)
                    ->setTitle($lang->getLL('newRecordGeneral'))
                    ->setIcon($this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL));
                $buttonBar->addButton($newRecordButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
            }
            if (!in_array($this->pageRow['doktype'], $noViewDokTypes)) {
                $onClick = BackendUtility::viewOnClick($this->id, '', BackendUtility::BEgetRootLine($this->id));
                $viewButton = $buttonBar->makeLinkButton()
                    ->setHref('#')
                    ->setOnClick($onClick)
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                    ->setIcon($this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL));
                $buttonBar->addButton($viewButton, ButtonBar::BUTTON_POSITION_LEFT, 20);
            }
            // If edit permissions are set, see
            // \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
            if ($localCalcPerms & Permission::PAGE_EDIT && !empty($this->id) && $this->editLockPermissions() && $backendUser->checkLanguageAccess(0)) {
                // Edit
                $params = '&edit[pages][' . $this->pageRow['uid'] . ']=edit';
                $onClick = BackendUtility::editOnClick($params, '', -1);
                $editButton = $buttonBar->makeLinkButton()
                    ->setHref('#')
                    ->setOnClick($onClick)
                    ->setTitle($lang->getLL('editPage'))
                    ->setIcon($this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL));
                $buttonBar->addButton($editButton, ButtonBar::BUTTON_POSITION_LEFT, 20);
            }
            // Paste
            if ($this->showClipboard && ($localCalcPerms & Permission::PAGE_NEW || $localCalcPerms & Permission::CONTENT_EDIT) && $this->editLockPermissions()) {
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
            $clearCacheButton = $buttonBar->makeLinkButton()
                ->setHref($this->listURL() . '&clear_cache=1')
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.clear_cache'))
                ->setIcon($this->iconFactory->getIcon('actions-system-cache-clear', Icon::SIZE_SMALL));
            $buttonBar->addButton($clearCacheButton, ButtonBar::BUTTON_POSITION_RIGHT);
            if ($this->table && (!isset($module->modTSconfig['properties']['noExportRecordsLinks'])
                || (isset($module->modTSconfig['properties']['noExportRecordsLinks'])
                    && !$module->modTSconfig['properties']['noExportRecordsLinks']))
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
                    $url = (string)$uriBuilder->buildUriFromRoute('xMOD_tximpexp', ['tx_impexp[action]' => 'export']);
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
            if ($backendUser->mayMakeShortcut()) {
                $shortCutButton = $buttonBar->makeShortcutButton()
                    ->setModuleName('web_list')
                    ->setGetVariables([
                        'id',
                        'route',
                        'imagemode',
                        'pointer',
                        'table',
                        'search_field',
                        'search_levels',
                        'showLimit',
                        'sortField',
                        'sortRev'
                    ])
                    ->setSetVariables(array_keys($this->MOD_MENU));
                $buttonBar->addButton($shortCutButton, ButtonBar::BUTTON_POSITION_RIGHT);
            }
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
     * @param string $rowList List of fields to show in the listing. Pseudo fields will be added including the record header.
     * @throws \UnexpectedValueException
     * @return string HTML table with the listing for the record.
     */
    public function getTable($table, $id, $rowList = '')
    {
        $rowListArray = GeneralUtility::trimExplode(',', $rowList, true);
        // if no columns have been specified, show description (if configured)
        if (!empty($GLOBALS['TCA'][$table]['ctrl']['descriptionColumn']) && empty($rowListArray)) {
            $rowListArray[] = $GLOBALS['TCA'][$table]['ctrl']['descriptionColumn'];
        }
        $backendUser = $this->getBackendUserAuthentication();
        $lang = $this->getLanguageService();
        // Init
        $addWhere = '';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $titleCol = $GLOBALS['TCA'][$table]['ctrl']['label'];
        $thumbsCol = $GLOBALS['TCA'][$table]['ctrl']['thumbnail'];
        $l10nEnabled = $GLOBALS['TCA'][$table]['ctrl']['languageField']
                     && $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
        $tableCollapsed = (bool)$this->tablesCollapsed[$table];
        // prepare space icon
        $this->spaceIcon = '<span class="btn btn-default disabled">' . $this->iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
        // Cleaning rowlist for duplicates and place the $titleCol as the first column always!
        $this->fieldArray = [];
        // title Column
        // Add title column
        $this->fieldArray[] = $titleCol;
        // Control-Panel
        if (!GeneralUtility::inList($rowList, '_CONTROL_')) {
            $this->fieldArray[] = '_CONTROL_';
        }
        // Clipboard
        if ($this->showClipboard) {
            $this->fieldArray[] = '_CLIPBOARD_';
        }
        // Ref
        if (!$this->dontShowClipControlPanels) {
            $this->fieldArray[] = '_REF_';
        }
        // Path
        if ($this->searchLevels) {
            $this->fieldArray[] = '_PATH_';
        }
        // Localization
        if ($l10nEnabled) {
            $this->fieldArray[] = '_LOCALIZATION_';
            // Do not show the "Localize to:" field when only translated records should be shown
            if (!$this->showOnlyTranslatedRecords) {
                $this->fieldArray[] = '_LOCALIZATION_b';
            }
            // Only restrict to the default language if no search request is in place
            // And if only translations should be shown
            if ($this->searchString === '' && !$this->showOnlyTranslatedRecords) {
                $addWhere = (string)$queryBuilder->expr()->orX(
                    $queryBuilder->expr()->lte($GLOBALS['TCA'][$table]['ctrl']['languageField'], 0),
                    $queryBuilder->expr()->eq($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'], 0)
                );
            }
        }
        // Cleaning up:
        $this->fieldArray = array_unique(array_merge($this->fieldArray, $rowListArray));
        if ($this->noControlPanels) {
            $tempArray = array_flip($this->fieldArray);
            unset($tempArray['_CONTROL_']);
            unset($tempArray['_CLIPBOARD_']);
            $this->fieldArray = array_keys($tempArray);
        }
        // Creating the list of fields to include in the SQL query:
        $selectFields = $this->fieldArray;
        $selectFields[] = 'uid';
        $selectFields[] = 'pid';
        // adding column for thumbnails
        if ($thumbsCol) {
            $selectFields[] = $thumbsCol;
        }
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
        if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
            $selectFields[] = 't3ver_id';
            $selectFields[] = 't3ver_state';
            $selectFields[] = 't3ver_wsid';
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
        $fieldListFields = $this->makeFieldList($table, 1);
        if (empty($fieldListFields) && $GLOBALS['TYPO3_CONF_VARS']['BE']['debug']) {
            $message = sprintf($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:missingTcaColumnsMessage'), $table, $table);
            $messageTitle = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:missingTcaColumnsMessageTitle');
            /** @var FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $message,
                $messageTitle,
                FlashMessage::WARNING,
                true
            );
            /** @var FlashMessageService $flashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue $defaultFlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
        // Making sure that the fields in the field-list ARE in the field-list from TCA!
        $selectFields = array_intersect($selectFields, $fieldListFields);
        // Implode it into a list of fields for the SQL-statement.
        $selFieldList = implode(',', $selectFields);
        $this->selFieldList = $selFieldList;
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof RecordListGetTableHookInterface) {
                throw new \UnexpectedValueException($className . ' must implement interface ' . RecordListGetTableHookInterface::class, 1195114460);
            }
            $hookObject->getDBlistQuery($table, $id, $addWhere, $selFieldList, $this);
        }

        if ($table == 'pages' && $this->showOnlyTranslatedRecords) {
            $addWhere .= ' AND ' . $GLOBALS['TCA']['pages']['ctrl']['languageField'] . ' IN(' . implode(',', array_keys($this->languagesAllowedForUser)) . ')';
        }

        $additionalConstraints = empty($addWhere) ? [] : [QueryHelper::stripLogicalOperatorPrefix($addWhere)];
        $selFieldList = GeneralUtility::trimExplode(',', $selFieldList, true);

        // Create the SQL query for selecting the elements in the listing:
        // do not do paging when outputting as CSV
        if ($this->csvOutput) {
            $this->iLimit = 0;
        }
        if ($this->firstElementNumber > 2 && $this->iLimit > 0) {
            // Get the two previous rows for sorting if displaying page > 1
            $this->firstElementNumber -= 2;
            $this->iLimit += 2;
            // (API function from TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList)
            $queryBuilder = $this->getQueryBuilder($table, $id, $additionalConstraints);
            $this->firstElementNumber += 2;
            $this->iLimit -= 2;
        } else {
            // (API function from TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList)
            $queryBuilder = $this->getQueryBuilder($table, $id, $additionalConstraints);
        }

        // Finding the total amount of records on the page
        // (API function from TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList)
        $this->setTotalItems($table, $id, $additionalConstraints);

        // Init:
        $queryResult = $queryBuilder->execute();
        $dbCount = 0;
        $out = '';
        $tableHeader = '';
        $listOnlyInSingleTableMode = $this->listOnlyInSingleTableMode && !$this->table;
        // If the count query returned any number of records, we perform the real query,
        // selecting records.
        if ($this->totalItems) {
            // Fetch records only if not in single table mode
            if ($listOnlyInSingleTableMode) {
                $dbCount = $this->totalItems;
            } else {
                // Set the showLimit to the number of records when outputting as CSV
                if ($this->csvOutput) {
                    $this->showLimit = $this->totalItems;
                    $this->iLimit = $this->totalItems;
                    $dbCount = $this->totalItems;
                } else {
                    if ($this->firstElementNumber + $this->showLimit <= $this->totalItems) {
                        $dbCount = $this->showLimit + 2;
                    } else {
                        $dbCount = $this->totalItems - $this->firstElementNumber + 2;
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
                    . '</span> (<span class="t3js-table-total-items">' . $this->totalItems . '</span>)';
            } else {
                $icon = $this->table
                    ? '<span title="' . htmlspecialchars($lang->getLL('contractView')) . '">' . $this->iconFactory->getIcon('actions-view-table-collapse', Icon::SIZE_SMALL)->render() . '</span>'
                    : '<span title="' . htmlspecialchars($lang->getLL('expandView')) . '">' . $this->iconFactory->getIcon('actions-view-table-expand', Icon::SIZE_SMALL)->render() . '</span>';
                $theData[$titleCol] = $this->linkWrapTable($table, $tableTitle . ' (<span class="t3js-table-total-items">' . $this->totalItems . '</span>) ' . $icon);
            }
            if ($listOnlyInSingleTableMode) {
                $tableHeader .= BackendUtility::wrapInHelp($table, '', $theData[$titleCol]);
            } else {
                // Render collapse button if in multi table mode
                $collapseIcon = '';
                if (!$this->table) {
                    $href = htmlspecialchars($this->listURL() . '&collapse[' . $table . ']=' . ($tableCollapsed ? '0' : '1'));
                    $title = $tableCollapsed
                        ? htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.expandTable'))
                        : htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.collapseTable'));
                    $icon = '<span class="collapseIcon">' . $this->iconFactory->getIcon(($tableCollapsed ? 'actions-view-list-expand' : 'actions-view-list-collapse'), Icon::SIZE_SMALL)->render() . '</span>';
                    $collapseIcon = '<a href="' . $href . '" title="' . $title . '" class="pull-right t3js-toggle-recordlist" data-table="' . htmlspecialchars($tableIdentifier) . '" data-toggle="collapse" data-target="#recordlist-' . htmlspecialchars($tableIdentifier) . '">' . $icon . '</a>';
                }
                $tableHeader .= $theData[$titleCol] . $collapseIcon;
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
                if ($this->firstElementNumber > 2 && $this->iLimit > 0) {
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
                $this->totalRowCount = count($accRows);
                // CSV initiated
                if ($this->csvOutput) {
                    $this->initCSV();
                }
                // Render items:
                $this->CBnames = [];
                $this->duplicateStack = [];
                $this->eCounter = $this->firstElementNumber;
                $cc = 0;
                foreach ($accRows as $row) {
                    // Render item row if counter < limit
                    if ($cc < $this->iLimit) {
                        $cc++;
                        $this->translations = false;
                        $rowOutput .= $this->renderListRow($table, $row, $cc, $titleCol, $thumbsCol);
                        // If no search happened it means that the selected
                        // records are either default or All language and here we will not select translations
                        // which point to the main record:
                        if ($l10nEnabled && $this->searchString === '' && !($this->hideTranslations === '*' || GeneralUtility::inList($this->hideTranslations, $table))) {
                            // For each available translation, render the record:
                            if (is_array($this->translations)) {
                                foreach ($this->translations as $lRow) {
                                    // $lRow isn't always what we want - if record was moved we've to work with the
                                    // placeholder records otherwise the list is messed up a bit
                                    if ($row['_MOVE_PLH_uid'] && $row['_MOVE_PLH_pid']) {
                                        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                                            ->getQueryBuilderForTable($table);
                                        $queryBuilder->getRestrictions()
                                            ->removeAll()
                                            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                                        $predicates = [
                                            $queryBuilder->expr()->eq(
                                                't3ver_move_id',
                                                $queryBuilder->createNamedParameter((int)$lRow['uid'], \PDO::PARAM_INT)
                                            ),
                                            $queryBuilder->expr()->eq(
                                                'pid',
                                                $queryBuilder->createNamedParameter((int)$row['_MOVE_PLH_pid'], \PDO::PARAM_INT)
                                            ),
                                            $queryBuilder->expr()->eq(
                                                't3ver_wsid',
                                                $queryBuilder->createNamedParameter((int)$row['t3ver_wsid'], \PDO::PARAM_INT)
                                            ),
                                        ];

                                        $tmpRow = $queryBuilder
                                            ->select(...$selFieldList)
                                            ->from($table)
                                            ->andWhere(...$predicates)
                                            ->execute()
                                            ->fetch();

                                        $lRow = is_array($tmpRow) ? $tmpRow : $lRow;
                                    }
                                    if (!$this->isRowListingConditionFulfilled($table, $lRow)) {
                                        continue;
                                    }
                                    // In offline workspace, look for alternative record:
                                    BackendUtility::workspaceOL($table, $lRow, $backendUser->workspace, true);
                                    if (is_array($lRow) && $backendUser->checkLanguageAccess($lRow[$GLOBALS['TCA'][$table]['ctrl']['languageField']])) {
                                        $currentIdList[] = $lRow['uid'];
                                        $rowOutput .= $this->renderListRow($table, $lRow, $cc, $titleCol, $thumbsCol, 18);
                                    }
                                }
                            }
                        }
                    }
                    // Counter of total rows incremented:
                    $this->eCounter++;
                }
                // Record navigation is added to the beginning and end of the table if in single
                // table mode
                if ($this->table) {
                    $rowOutput = $this->renderListNavigation('top') . $rowOutput . $this->renderListNavigation('bottom');
                } else {
                    // Show that there are more records than shown
                    if ($this->totalItems > $this->itemsLimitPerTable) {
                        $countOnFirstPage = $this->totalItems > $this->itemsLimitSingleTable ? $this->itemsLimitSingleTable : $this->totalItems;
                        $hasMore = $this->totalItems > $this->itemsLimitSingleTable;
                        $colspan = $this->showIcon ? count($this->fieldArray) + 1 : count($this->fieldArray);
                        $rowOutput .= '<tr><td colspan="' . $colspan . '">
								<a href="' . htmlspecialchars($this->listURL() . '&table=' . rawurlencode($tableIdentifier)) . '" class="btn btn-default">'
                            . '<span class="t3-icon fa fa-chevron-down"></span> <i>[1 - ' . $countOnFirstPage . ($hasMore ? '+' : '') . ']</i></a>
								</td></tr>';
                    }
                }
                // The header row for the table is now created:
                $out .= $this->renderListHeader($table, $currentIdList);
            }

            $collapseClass = $tableCollapsed && !$this->table ? 'collapse' : 'collapse in';
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
     * @return string
     */
    protected function getOnClickForRow(string $table, array $row): string
    {
        if ($table === 'tt_content') {
            // Link to a content element, possibly translated and with anchor
            $additionalParams = '';
            $language = (int)$row[$GLOBALS['TCA']['tt_content']['ctrl']['languageField']];
            if ($language > 0) {
                $additionalParams = '&L=' . $language;
            }
            $onClick = BackendUtility::viewOnClick(
                $this->id,
                '',
                null,
                '#c' . $row['uid'],
                '',
                $additionalParams
            );
        } elseif ($table === 'pages' && $row[$GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField']] > 0) {
            // Link to a page translation needs uid of default language page as id
            $onClick = BackendUtility::viewOnClick(
                $row[$GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField']],
                '',
                null,
                '',
                '',
                '&L=' . (int)$row[$GLOBALS['TCA']['pages']['ctrl']['languageField']]
            );
        } else {
            // Link to a page in the default language
            $onClick = BackendUtility::viewOnClick($row['uid']);
        }
        return $onClick;
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
     * @return string Table row for the element
     * @internal
     * @see getTable()
     */
    public function renderListRow($table, $row, $cc, $titleCol, $thumbsCol, $indent = 0)
    {
        if (!is_array($row)) {
            return '';
        }
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
        // Add special classes for first and last row
        if ($cc == 1 && $indent == 0) {
            $tagAttributes['class'][] = 'firstcol';
        }
        if ($cc == $this->totalRowCount || $cc == $this->iLimit) {
            $tagAttributes['class'][] = 'lastcol';
        }
        // Overriding with versions background color if any:
        if (!empty($row['_CSSCLASS'])) {
            $tagAttributes['class'] = [$row['_CSSCLASS']];
        }

        $tagAttributes['class'][] = 't3js-entity';

        // Incr. counter.
        $this->counter++;
        // The icon with link
        $toolTip = BackendUtility::getRecordToolTip($row, $table);
        $additionalStyle = $indent ? ' style="margin-left: ' . $indent . 'px;"' : '';
        $iconImg = '<span ' . $toolTip . ' ' . $additionalStyle . '>'
            . $this->iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render()
            . '</span>';
        $theIcon = $this->clickMenuEnabled ? BackendUtility::wrapClickMenuOnIcon($iconImg, $table, $row['uid']) : $iconImg;
        // Preparing and getting the data-array
        $theData = [];
        $localizationMarkerClass = '';
        foreach ($this->fieldArray as $fCol) {
            if ($fCol == $titleCol) {
                $recTitle = BackendUtility::getRecordTitle($table, $row, false, true);
                $warning = '';
                // If the record is edit-locked	by another user, we will show a little warning sign:
                $lockInfo = BackendUtility::isRecordLocked($table, $row['uid']);
                if ($lockInfo) {
                    $warning = '<span data-toggle="tooltip" data-placement="right" data-title="' . htmlspecialchars($lockInfo['msg']) . '">'
                        . $this->iconFactory->getIcon('warning-in-use', Icon::SIZE_SMALL)->render() . '</span>';
                }
                $theData[$fCol] = $theData['__label'] = $warning . $this->linkWrapItems($table, $row['uid'], $recTitle, $row);
                // Render thumbnails, if:
                // - a thumbnail column exists
                // - there is content in it
                // - the thumbnail column is visible for the current type
                $type = 0;
                if (isset($GLOBALS['TCA'][$table]['ctrl']['type'])) {
                    $typeColumn = $GLOBALS['TCA'][$table]['ctrl']['type'];
                    $type = $row[$typeColumn];
                }
                // If current type doesn't exist, set it to 0 (or to 1 for historical reasons,
                // if 0 doesn't exist)
                if (!isset($GLOBALS['TCA'][$table]['types'][$type])) {
                    $type = isset($GLOBALS['TCA'][$table]['types'][0]) ? 0 : 1;
                }

                $visibleColumns = $this->getVisibleColumns($GLOBALS['TCA'][$table], $type);

                if ($this->thumbs &&
                    trim($row[$thumbsCol]) &&
                    preg_match('/(^|(.*(;|,)?))' . $thumbsCol . '(((;|,).*)|$)/', $visibleColumns) === 1
                ) {
                    $thumbCode = '<br />' . $this->thumbCode($row, $table, $thumbsCol);
                    $theData[$fCol] .= $thumbCode;
                    $theData['__label'] .= $thumbCode;
                }
                if (isset($GLOBALS['TCA'][$table]['ctrl']['languageField'])
                    && $row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] != 0
                    && $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] != 0
                ) {
                    // It's a translated record with a language parent
                    $localizationMarkerClass = ' localization';
                }
            } elseif ($fCol === 'pid') {
                $theData[$fCol] = $row[$fCol];
            } elseif ($fCol === '_PATH_') {
                $theData[$fCol] = $this->recPath($row['pid']);
            } elseif ($fCol === '_REF_') {
                $theData[$fCol] = $this->createReferenceHtml($table, $row['uid']);
            } elseif ($fCol === '_CONTROL_') {
                $theData[$fCol] = $this->makeControl($table, $row);
            } elseif ($fCol === '_CLIPBOARD_') {
                $theData[$fCol] = $this->makeClip($table, $row);
            } elseif ($fCol === '_LOCALIZATION_') {
                [$lC1, $lC2] = $this->makeLocalizationPanel($table, $row);
                $theData[$fCol] = $lC1;
                $theData[$fCol . 'b'] = '<div class="btn-group">' . $lC2 . '</div>';
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
            $this->addToCSV($row);
        }
        // Add classes to table cells
        $this->addElement_tdCssClass[$titleCol] = 'col-title col-responsive' . $localizationMarkerClass;
        $this->addElement_tdCssClass['__label'] = $this->addElement_tdCssClass[$titleCol];
        $this->addElement_tdCssClass['_CONTROL_'] = 'col-control';
        if ($this->getModule()->MOD_SETTINGS['clipBoard']) {
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
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        // Traverse the fields:
        foreach ($this->fieldArray as $fCol) {
            // Calculate users permissions to edit records in the table:
            $permsEdit = $this->calcPerms & ($table === 'pages' ? 2 : 16) && $this->overlayEditLockPermissions($table);
            switch ((string)$fCol) {
                case '_PATH_':
                    // Path
                    $theData[$fCol] = '<i>[' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels._PATH_')) . ']</i>';
                    break;
                case '_REF_':
                    // References
                    $theData[$fCol] = '<i>[' . htmlspecialchars($lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:c__REF_')) . ']</i>';
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
                    if (!$this->getModule()->MOD_SETTINGS['clipBoard']) {
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
                            . ' data-content="' . htmlspecialchars($confirmMessage) . '"'
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
                        $editUri = (string)$uriBuilder->buildUriFromRoute('record_edit')
                            . '&edit[' . $table . '][{entityIdentifiers:editList}]=edit'
                            . '&returnUrl={T3_THIS_LOCATION}';
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
                        $onClick = htmlspecialchars('checkOffCB(' . GeneralUtility::quoteJSvalue(implode(',', $this->CBnames)) . ', this); return false;');
                        $cells['markAll'] = '<a class="btn btn-default" rel="" href="#" onclick="' . $onClick . '" title="'
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
                        $permsAdditional = ($table === 'pages' ? 8 : 16);
                        if ($this->calcPerms & $permsAdditional && $this->showNewRecLink($table)) {
                            $spriteIcon = $table === 'pages'
                                ? $this->iconFactory->getIcon('actions-page-new', Icon::SIZE_SMALL)
                                : $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL);
                            if ($table === 'tt_content') {
                                // If mod.newContentElementWizard.override is set, use that extension's create new content wizard instead:
                                $newContentElementWizard = BackendUtility::getPagesTSconfig($this->pageinfo['uid'])['mod.']['newContentElementWizard.']['override']
                                    ?? 'new_content_element_wizard';
                                $url = (string)$uriBuilder->buildUriFromRoute(
                                    $newContentElementWizard,
                                    [
                                        'id' => $this->id,
                                        'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                                    ]
                                );
                                $icon = '<a href="' . htmlspecialchars($url) . '" '
                                    . 'data-title="' . htmlspecialchars($lang->getLL('new')) . '"'
                                    . 'class="btn btn-default t3js-toggle-new-content-element-wizard disabled"">'
                                    . $spriteIcon->render()
                                    . '</a>';
                            } elseif ($table === 'pages') {
                                $parameters = ['id' => $this->id, 'pagesOnly' => 1, 'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')];
                                $href = (string)$uriBuilder->buildUriFromRoute('db_new', $parameters);
                                $icon = '<a class="btn btn-default" href="' . htmlspecialchars($href) . '" title="' . htmlspecialchars($lang->getLL('new')) . '">'
                                    . $spriteIcon->render() . '</a>';
                            } else {
                                $params = '&edit[' . $table . '][' . $this->id . ']=new';
                                if ($table === 'pages') {
                                    $params .= '&overrideVals[pages][doktype]=' . (int)$this->pageRow['doktype'];
                                }
                                $icon = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, '', -1))
                                    . '" title="' . htmlspecialchars($lang->getLL('new')) . '">' . $spriteIcon->render() . '</a>';
                            }
                        }
                        // If the table can be edited, add link for editing ALL SHOWN fields for all listed records:
                        if ($permsEdit && $this->table && is_array($currentIdList)) {
                            $entityIdentifiers = 'entityIdentifiers';
                            if ($this->clipNumPane()) {
                                $entityIdentifiers .= ':editList';
                            }
                            $editUri = (string)$uriBuilder->buildUriFromRoute('record_edit')
                                . '&edit[' . $table . '][{' . $entityIdentifiers . '}]=edit'
                                . '&columnsOnly=' . implode(',', $this->fieldArray)
                                . '&returnUrl={T3_THIS_LOCATION}';
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
                            $editUri = (string)$uriBuilder->buildUriFromRoute('record_edit')
                                . '&edit[' . $table . '][{' . $entityIdentifiers . '}]=edit'
                                . '&columnsOnly=' . $fCol
                                . '&returnUrl={T3_THIS_LOCATION}';
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
     * @return int Pointer to first element on the page (starting with 0)
     */
    protected function getPointerForPage($page)
    {
        return ($page - 1) * $this->iLimit;
    }

    /**
     * Creates a page browser for tables with many records
     *
     * @param string $renderPart Distinguish between 'top' and 'bottom' part of the navigation (above or below the records)
     * @return string Navigation HTML
     */
    protected function renderListNavigation($renderPart = 'top')
    {
        $totalPages = ceil($this->totalItems / $this->iLimit);
        // Show page selector if not all records fit into one page
        if ($totalPages <= 1) {
            return '';
        }
        $content = '';
        $listURL = $this->listURL('', $this->table, 'firstElementNumber');
        // 1 = first page
        // 0 = first element
        $currentPage = floor($this->firstElementNumber / $this->iLimit) + 1;
        // Compile first, previous, next, last and refresh buttons
        if ($currentPage > 1) {
            $labelFirst = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:first'));
            $labelPrevious = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:previous'));
            $first = '<li><a href="' . $listURL . '&pointer=' . $this->getPointerForPage(1) . '" title="' . $labelFirst . '">'
                . $this->iconFactory->getIcon('actions-view-paging-first', Icon::SIZE_SMALL)->render() . '</a></li>';
            $previous = '<li><a href="' . $listURL . '&pointer=' . $this->getPointerForPage($currentPage - 1) . '" title="' . $labelPrevious . '">'
                . $this->iconFactory->getIcon('actions-view-paging-previous', Icon::SIZE_SMALL)->render() . '</a></li>';
        } else {
            $first = '<li class="disabled"><span>' . $this->iconFactory->getIcon('actions-view-paging-first', Icon::SIZE_SMALL)->render() . '</span></li>';
            $previous = '<li class="disabled"><span>' . $this->iconFactory->getIcon('actions-view-paging-previous', Icon::SIZE_SMALL)->render() . '</span></li>';
        }
        if ($currentPage < $totalPages) {
            $labelNext = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:next'));
            $labelLast = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:last'));
            $next = '<li><a href="' . $listURL . '&pointer=' . $this->getPointerForPage($currentPage + 1) . '" title="' . $labelNext . '">'
                . $this->iconFactory->getIcon('actions-view-paging-next', Icon::SIZE_SMALL)->render() . '</a></li>';
            $last = '<li><a href="' . $listURL . '&pointer=' . $this->getPointerForPage($totalPages) . '" title="' . $labelLast . '">'
                . $this->iconFactory->getIcon('actions-view-paging-last', Icon::SIZE_SMALL)->render() . '</a></li>';
        } else {
            $next = '<li class="disabled"><span>' . $this->iconFactory->getIcon('actions-view-paging-next', Icon::SIZE_SMALL)->render() . '</span></li>';
            $last = '<li class="disabled"><span>' . $this->iconFactory->getIcon('actions-view-paging-last', Icon::SIZE_SMALL)->render() . '</span></li>';
        }
        $reload = '<li><a href="#" onclick="document.dblistForm.action=' . GeneralUtility::quoteJSvalue($listURL
            . '&pointer=') . '+calculatePointer(document.getElementById(' . GeneralUtility::quoteJSvalue('jumpPage-' . $renderPart)
            . ').value); document.dblistForm.submit(); return true;" title="'
            . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:reload')) . '">'
            . $this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL)->render() . '</a></li>';
        if ($renderPart === 'top') {
            // Add js to traverse a page select input to a pointer value
            $content = '
<script type="text/javascript">
/*<![CDATA[*/
	function calculatePointer(page) {
		if (page > ' . $totalPages . ') {
			page = ' . $totalPages . ';
		}
		if (page < 1) {
			page = 1;
		}
		return (page - 1) * ' . $this->iLimit . ';
	}
/*]]>*/
</script>
';
        }
        $pageNumberInput = '
			<input type="number" min="1" max="' . $totalPages . '" value="' . $currentPage . '" size="3" class="form-control input-sm paginator-input" id="jumpPage-' . $renderPart . '" name="jumpPage-'
            . $renderPart . '" onkeyup="if (event.keyCode == 13) { document.dblistForm.action=' . htmlspecialchars(GeneralUtility::quoteJSvalue($listURL . '&pointer='))
            . '+calculatePointer(this.value); document.dblistForm.submit(); } return true;" />
			';
        $pageIndicatorText = sprintf(
            $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:pageIndicator'),
            $pageNumberInput,
            $totalPages
        );
        $pageIndicator = '<li><span>' . $pageIndicatorText . '</span></li>';
        if ($this->totalItems > $this->firstElementNumber + $this->iLimit) {
            $lastElementNumber = $this->firstElementNumber + $this->iLimit;
        } else {
            $lastElementNumber = $this->totalItems;
        }
        $rangeIndicator = '<li><span>' . sprintf(
            $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:rangeIndicator'),
            $this->firstElementNumber + 1,
            $lastElementNumber
        ) . '</span></li>';

        $titleColumn = $this->fieldArray[0];
        $data = [
            $titleColumn => $content . '
				<nav class="pagination-wrap">
					<ul class="pagination pagination-block">
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
        $module = $this->getModule();
        $rowUid = $row['uid'];
        if (ExtensionManagementUtility::isLoaded('workspaces') && isset($row['_ORIG_uid'])) {
            $rowUid = $row['_ORIG_uid'];
        }
        $cells = [
            'primary' => [],
            'secondary' => []
        ];
        // Enables to hide the move elements for localized records - doesn't make much sense to perform these options for them
        // For page translations these icons should never be shown
        $isL10nOverlay = $table === 'pages' && $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] != 0;
        if ($table === 'pages') {
            // If the listed table is 'pages' we have to request the permission settings for each page.
            $localCalcPerms = $backendUser->calcPerms(BackendUtility::getRecord('pages', $row['uid']));
        } else {
            // If the listed table is not 'pages' we have to request the permission settings from the parent page
            $localCalcPerms = $backendUser->calcPerms(BackendUtility::getRecord('pages', $row['pid']));
        }
        $permsEdit = $table === 'pages'
                     && $backendUser->checkLanguageAccess((int)$row[$GLOBALS['TCA']['pages']['ctrl']['languageField']])
                     && $localCalcPerms & Permission::PAGE_EDIT
                     || $table !== 'pages'
                        && $localCalcPerms & Permission::CONTENT_EDIT
                        && $backendUser->recordEditAccessInternals($table, $row);
        $permsEdit = $this->overlayEditLockPermissions($table, $row, $permsEdit);
        // "Show" link (only pages and tt_content elements)
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);

        if ($table === 'pages' || $table === 'tt_content') {
            $onClick = $this->getOnClickForRow($table, $row);
            $viewAction = '<a class="btn btn-default" href="#" onclick="'
                            . htmlspecialchars(
                                $onClick
                            ) . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage')) . '">';
            if ($table === 'pages') {
                $viewAction .= $this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL)->render();
            } else {
                $viewAction .= $this->iconFactory->getIcon('actions-view', Icon::SIZE_SMALL)->render();
            }
            $viewAction .= '</a>';
            $this->addActionToCellGroup($cells, $viewAction, 'view');
        }
        // "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
        if ($permsEdit && $this->isEditable($table)) {
            $params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
            $iconIdentifier = 'actions-open';
            if ($table === 'pages') {
                // Disallow manual adjustment of the language field for pages
                $params .= '&overrideVals[pages][sys_language_uid]=' . (int)$row[$GLOBALS['TCA']['pages']['ctrl']['languageField']];
                $iconIdentifier = 'actions-page-open';
            }
            $editAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, '', -1))
                . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('edit')) . '">' . $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render() . '</a>';
        } else {
            $editAction = $this->spaceIcon;
        }
        $this->addActionToCellGroup($cells, $editAction, 'edit');
        // "Info": (All records)
        $onClick = 'top.TYPO3.InfoWindow.showItem(' . GeneralUtility::quoteJSvalue($table) . ', ' . (int)$row['uid'] . '); return false;';
        $viewBigAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('showInfo')) . '">'
            . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render() . '</a>';
        $this->addActionToCellGroup($cells, $viewBigAction, 'viewBig');
        // "Move" wizard link for pages/tt_content elements:
        if ($permsEdit && ($table === 'tt_content' || $table === 'pages') && $this->isEditable($table)) {
            if ($isL10nOverlay) {
                $moveAction = $this->spaceIcon;
            } else {
                $onClick = 'return jumpExt(' . GeneralUtility::quoteJSvalue((string)$uriBuilder->buildUriFromRoute('move_element') . '&table=' . $table . '&uid=' . $row['uid']) . ');';
                $linkTitleLL = htmlspecialchars($this->getLanguageService()->getLL('move_' . ($table === 'tt_content' ? 'record' : 'page')));
                $icon = ($table === 'pages' ? $this->iconFactory->getIcon('actions-page-move', Icon::SIZE_SMALL) : $this->iconFactory->getIcon('actions-document-move', Icon::SIZE_SMALL));
                $moveAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $linkTitleLL . '">' . $icon->render() . '</a>';
            }
            $this->addActionToCellGroup($cells, $moveAction, 'move');
        }
        // If the table is NOT a read-only table, then show these links:
        if ($this->isEditable($table)) {
            // "Revert" link (history/undo)
            if ((bool)\trim($userTsConfig['options.']['showHistory.'][$table] ?? $userTsConfig['options.']['showHistory'] ?? '1')) {
                $moduleUrl = (string)$uriBuilder->buildUriFromRoute('record_history', ['element' => $table . ':' . $row['uid']]);
                $onClick = 'return jumpExt(' . GeneralUtility::quoteJSvalue($moduleUrl) . ',\'#latest\');';
                $historyAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . '" title="'
                    . htmlspecialchars($this->getLanguageService()->getLL('history')) . '">'
                    . $this->iconFactory->getIcon('actions-document-history-open', Icon::SIZE_SMALL)->render() . '</a>';
                $this->addActionToCellGroup($cells, $historyAction, 'history');
            }
            // "Edit Perms" link:
            if ($table === 'pages' && $backendUser->check('modules', 'system_BeuserTxPermission') && ExtensionManagementUtility::isLoaded('beuser')) {
                if ($isL10nOverlay) {
                    $permsAction = $this->spaceIcon;
                } else {
                    $href = (string)$uriBuilder->buildUriFromRoute('system_BeuserTxPermission') . '&id=' . $row['uid'] . '&tx_beuser_system_beusertxpermission[action]=edit' . $this->makeReturnUrl();
                    $permsAction = '<a class="btn btn-default" href="' . htmlspecialchars($href) . '" title="'
                        . htmlspecialchars($this->getLanguageService()->getLL('permissions')) . '">'
                        . $this->iconFactory->getIcon('actions-lock', Icon::SIZE_SMALL)->render() . '</a>';
                }
                $this->addActionToCellGroup($cells, $permsAction, 'perms');
            }
            // "New record after" link (ONLY if the records in the table are sorted by a "sortby"-row
            // or if default values can depend on previous record):
            if (($GLOBALS['TCA'][$table]['ctrl']['sortby'] || $GLOBALS['TCA'][$table]['ctrl']['useColumnsForDefaultValues']) && $permsEdit) {
                if ($table !== 'pages' && $this->calcPerms & Permission::CONTENT_EDIT || $table === 'pages' && $this->calcPerms & Permission::PAGE_NEW) {
                    if ($table === 'pages' && $isL10nOverlay) {
                        $this->addActionToCellGroup($cells, $this->spaceIcon, 'new');
                    } elseif ($this->showNewRecLink($table)) {
                        $params = '&edit[' . $table . '][' . -($row['_MOVE_PLH'] ? $row['_MOVE_PLH_uid'] : $row['uid']) . ']=new';
                        $icon = ($table === 'pages' ? $this->iconFactory->getIcon('actions-page-new', Icon::SIZE_SMALL) : $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL));
                        $titleLabel = 'new';
                        if ($GLOBALS['TCA'][$table]['ctrl']['sortby']) {
                            $titleLabel .= ($table === 'pages' ? 'Page' : 'Record');
                        }
                        $newAction = '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars(BackendUtility::editOnClick($params, '', -1))
                            . '" title="' . htmlspecialchars($this->getLanguageService()->getLL($titleLabel)) . '">'
                            . $icon->render() . '</a>';
                        $this->addActionToCellGroup($cells, $newAction, 'new');
                    }
                }
            }
            // "Up/Down" links
            if ($permsEdit && $GLOBALS['TCA'][$table]['ctrl']['sortby'] && !$this->sortField && !$this->searchLevels) {
                if (!$isL10nOverlay && isset($this->currentTable['prev'][$row['uid']])) {
                    // Up
                    $params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . $this->currentTable['prev'][$row['uid']];
                    $moveUpAction = '<a class="btn btn-default" href="#" onclick="'
                        . htmlspecialchars('return jumpToUrl(' . BackendUtility::getLinkToDataHandlerAction($params, -1) . ');')
                        . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('moveUp')) . '">'
                        . $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render() . '</a>';
                } else {
                    $moveUpAction = $this->spaceIcon;
                }
                $this->addActionToCellGroup($cells, $moveUpAction, 'moveUp');

                if (!$isL10nOverlay && $this->currentTable['next'][$row['uid']]) {
                    // Down
                    $params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . $this->currentTable['next'][$row['uid']];
                    $moveDownAction = '<a class="btn btn-default" href="#" onclick="'
                        . htmlspecialchars('return jumpToUrl(' . BackendUtility::getLinkToDataHandlerAction($params, -1) . ');')
                        . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('moveDown')) . '">'
                        . $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render() . '</a>';
                } else {
                    $moveDownAction = $this->spaceIcon;
                }
                $this->addActionToCellGroup($cells, $moveDownAction, 'moveDown');
            }
            // "Hide/Unhide" links:
            $hiddenField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];

            if (
                !empty($GLOBALS['TCA'][$table]['columns'][$hiddenField])
                && (empty($GLOBALS['TCA'][$table]['columns'][$hiddenField]['exclude'])
                    || $backendUser->check('non_exclude_fields', $table . ':' . $hiddenField))
            ) {
                if (!$permsEdit || $this->isRecordCurrentBackendUser($table, $row)) {
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
            if ($permsEdit && !$disableDelete && ($table === 'pages' && $localCalcPerms & Permission::PAGE_DELETE || $table !== 'pages' && $this->calcPerms & Permission::CONTENT_EDIT)) {
                // Check if the record version is in "deleted" state, because that will switch the action to "restore"
                if ($backendUser->workspace > 0 && isset($row['t3ver_state']) && VersionState::cast($row['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
                    $actionName = 'restore';
                    $refCountMsg = '';
                } else {
                    $actionName = 'delete';
                    $refCountMsg = BackendUtility::referenceCount(
                        $table,
                        $row['uid'],
                        ' ' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToRecord'),
                        $this->getReferenceCount($table, $row['uid'])
                    ) . BackendUtility::translationCount(
                        $table,
                        $row['uid'],
                        ' ' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.translationsOfRecord')
                    );
                }

                if ($this->isRecordCurrentBackendUser($table, $row)) {
                    $deleteAction = $this->spaceIcon;
                } else {
                    $title = BackendUtility::getRecordTitle($table, $row);
                    $warningText = $this->getLanguageService()->getLL($actionName . 'Warning') . ' "' . $title . '" ' . '[' . $table . ':' . $row['uid'] . ']' . $refCountMsg;

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
                }
            } else {
                $deleteAction = $this->spaceIcon;
            }
            $this->addActionToCellGroup($cells, $deleteAction, 'delete');
            // "Levels" links: Moving pages into new levels...
            if ($permsEdit && $table === 'pages' && !$this->searchLevels) {
                // Up (Paste as the page right after the current parent page)
                if ($this->calcPerms & Permission::PAGE_NEW) {
                    $params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . -$this->id;
                    $moveLeftAction = '<a class="btn btn-default" href="#" onclick="'
                        . htmlspecialchars('return jumpToUrl(' . BackendUtility::getLinkToDataHandlerAction($params, -1) . ');')
                        . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('prevLevel')) . '">'
                        . $this->iconFactory->getIcon('actions-move-left', Icon::SIZE_SMALL)->render() . '</a>';
                    $this->addActionToCellGroup($cells, $isL10nOverlay ? $this->spaceIcon : $moveLeftAction, 'moveLeft');
                }
                // Down (Paste as subpage to the page right above)
                if (!$isL10nOverlay && $this->currentTable['prevUid'][$row['uid']]) {
                    $localCalcPerms = $backendUser->calcPerms(BackendUtility::getRecord('pages', $this->currentTable['prevUid'][$row['uid']]));
                    if ($localCalcPerms & Permission::PAGE_NEW) {
                        $params = '&cmd[' . $table . '][' . $row['uid'] . '][move]=' . $this->currentTable['prevUid'][$row['uid']];
                        $moveRightAction = '<a class="btn btn-default" href="#" onclick="'
                            . htmlspecialchars('return jumpToUrl(' . BackendUtility::getLinkToDataHandlerAction($params, -1) . ');')
                            . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('nextLevel')) . '">'
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
            $visibilityClass = ($classification !== 'primary' && !$module->MOD_SETTINGS['bigControlPanel'] ? 'collapsed' : 'expanded');
            if ($visibilityClass === 'collapsed') {
                $cellOutput = '';
                foreach ($actions as $action) {
                    $cellOutput .= $action;
                }
                $output .= ' <div class="btn-group">' .
                    '<span id="actions_' . $table . '_' . $row['uid'] . '" class="btn-group collapse collapse-horizontal width">' . $cellOutput . '</span>' .
                    '<a href="#actions_' . $table . '_' . $row['uid'] . '" class="btn btn-default collapsed" data-toggle="collapse" aria-expanded="false"><span class="t3-icon fa fa-ellipsis-h"></span></a>' .
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
        if (!$this->getModule()->MOD_SETTINGS['clipBoard']) {
            return '';
        }
        if (!$this->isEditable($table)) {
            return '';
        }
        $cells = [];
        $cells['pasteAfter'] = ($cells['pasteInto'] = $this->spaceIcon);
        // Enables to hide the copy, cut and paste icons for localized records - doesn't make much sense to perform these options for them
        // For page translations these icons should never be shown
        $isL10nOverlay = $table === 'pages' && $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] != 0;
        // Return blank, if disabled:
        // Whether a numeric clipboard pad is active or the normal pad we will see different content of the panel:
        // For the "Normal" pad:
        if ($this->clipObj->current === 'normal') {
            // Show copy/cut icons:
            $isSel = (string)$this->clipObj->isSelected($table, $row['uid']);
            if ($isL10nOverlay || !$this->overlayEditLockPermissions($table, $row)) {
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

                $cells['copy'] = '<a class="btn btn-default" href="#" onclick="'
                    . htmlspecialchars('return jumpSelf(' . GeneralUtility::quoteJSvalue($this->clipObj->selUrlDB(
                        $table,
                        $row['uid'],
                        1,
                        $isSel === 'copy',
                        ['returnUrl' => '']
                    )) . ');')
                    . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.copy')) . '">'
                    . $copyIcon->render() . '</a>';

                // Check permission to cut page or content
                if ($table === 'pages') {
                    $localCalcPerms = $this->getBackendUserAuthentication()->calcPerms(BackendUtility::getRecord('pages', $row['uid']));
                    $permsEdit = $localCalcPerms & Permission::PAGE_EDIT;
                } else {
                    $permsEdit = $this->calcPerms & Permission::CONTENT_EDIT;
                }
                $permsEdit = $this->overlayEditLockPermissions($table, $row, $permsEdit);

                // If the listed table is 'pages' we have to request the permission settings for each page:
                if ($table === 'pages') {
                    if ($permsEdit) {
                        $cells['cut'] = '<a class="btn btn-default" href="#" onclick="'
                        . htmlspecialchars('return jumpSelf(' . GeneralUtility::quoteJSvalue($this->clipObj->selUrlDB(
                            $table,
                            $row['uid'],
                            0,
                            $isSel === 'cut',
                            ['returnUrl' => '']
                        )) . ');')
                        . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.cut')) . '">'
                        . $cutIcon->render() . '</a>';
                    } else {
                        $cells['cut'] = $this->spaceIcon;
                    }
                } else {
                    if ($this->calcPerms & Permission::CONTENT_EDIT) {
                        $cells['cut'] = '<a class="btn btn-default" href="#" onclick="'
                        . htmlspecialchars('return jumpSelf(' . GeneralUtility::quoteJSvalue($this->clipObj->selUrlDB(
                            $table,
                            $row['uid'],
                            0,
                            $isSel === 'cut',
                            ['returnUrl' => '']
                        )) . ');')
                        . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.cut')) . '">'
                        . $cutIcon->render() . '</a>';
                    } else {
                        $cells['cut'] = $this->spaceIcon;
                    }
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
        if (!empty($elFromTable) && $GLOBALS['TCA'][$table]['ctrl']['sortby']) {
            // IF elements are found, they can be individually ordered and are not locked by editlock, then add a "paste after" icon:
            $cells['pasteAfter'] = $isL10nOverlay || !$this->overlayEditLockPermissions($table, $row)
                ? $this->spaceIcon
                : '<a class="btn btn-default t3js-modal-trigger"'
                    . ' href="' . htmlspecialchars($this->clipObj->pasteUrl($table, -$row['uid'])) . '"'
                    . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_pasteAfter')) . '"'
                    . ' data-title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_pasteAfter')) . '"'
                    . ' data-content="' . htmlspecialchars($this->clipObj->confirmMsgText($table, $row, 'after', $elFromTable)) . '"'
                    . ' data-severity="warning">'
                    . $this->iconFactory->getIcon('actions-document-paste-after', Icon::SIZE_SMALL)->render() . '</a>';
        }
        // Now, looking for elements in general:
        $elFromTable = $this->clipObj->elFromTable('');
        if ($table === 'pages' && !$isL10nOverlay && !empty($elFromTable)) {
            $cells['pasteInto'] = '<a class="btn btn-default t3js-modal-trigger"'
                . ' href="' . htmlspecialchars($this->clipObj->pasteUrl('', $row['uid'])) . '"'
                . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_pasteInto')) . '"'
                . ' data-title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_pasteInto')) . '"'
                . ' data-content="' . htmlspecialchars($this->clipObj->confirmMsgText($table, $row, 'into', $elFromTable)) . '"'
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
     * Creates the HTML for a reference count for the record with the UID $uid
     * in the table $tableName.
     *
     * @param string $tableName
     * @param int $uid
     * @return string HTML of reference a link, will be empty if there are no
     */
    protected function createReferenceHtml($tableName, $uid)
    {
        $referenceCount = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_refindex')
            ->count(
                '*',
                'sys_refindex',
                [
                    'ref_table' => $tableName,
                    'ref_uid' => (int)$uid,
                    'deleted' => 0,
                ]
            );

        return $this->generateReferenceToolTip(
            $referenceCount,
            GeneralUtility::quoteJSvalue($tableName) . ', ' . GeneralUtility::quoteJSvalue($uid)
        );
    }

    /**
     * Creates the localization panel
     *
     * @param string $table The table
     * @param mixed[] $row The record for which to make the localization panel.
     * @return string[] Array with key 0/1 with content for column 1 and 2
     */
    public function makeLocalizationPanel($table, $row)
    {
        $out = [
            0 => '',
            1 => ''
        ];
        // Reset translations
        $this->translations = [];

        // Language title and icon:
        $out[0] = $this->languageFlag($row[$GLOBALS['TCA'][$table]['ctrl']['languageField']]);
        // Guard clause so we can quickly return if a record is localized to "all languages"
        // It should only be possible to localize a record off default (uid 0)
        // Reasoning: The Parent is for ALL languages... why overlay with a localization?
        if ((int)$row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] === -1) {
            return $out;
        }

        $translations = $this->translateTools->translationInfo($table, $row['uid'], 0, $row, $this->selFieldList);
        if (is_array($translations)) {
            $this->translations = $translations['translations'];
            // Traverse page translations and add icon for each language that does NOT yet exist and is included in site configuration:
            $lNew = '';
            foreach ($this->pageOverlays as $lUid_OnPage => $lsysRec) {
                if (isset($this->systemLanguagesOnPage[$lUid_OnPage]) && $this->isEditable($table) && !isset($translations['translations'][$lUid_OnPage]) && $this->getBackendUserAuthentication()->checkLanguageAccess($lUid_OnPage)) {
                    $url = $this->listURL();
                    $href = BackendUtility::getLinkToDataHandlerAction(
                        '&cmd[' . $table . '][' . $row['uid'] . '][localize]=' . $lUid_OnPage,
                        $url . '&justLocalized=' . rawurlencode($table . ':' . $row['uid'] . ':' . $lUid_OnPage)
                    );
                    $language = BackendUtility::getRecord('sys_language', $lUid_OnPage, 'title');
                    if ($this->languageIconTitles[$lUid_OnPage]['flagIcon']) {
                        $lC = $this->iconFactory->getIcon($this->languageIconTitles[$lUid_OnPage]['flagIcon'], Icon::SIZE_SMALL)->render();
                    } else {
                        $lC = $this->languageIconTitles[$lUid_OnPage]['title'];
                    }
                    $lC = '<a href="' . htmlspecialchars($href) . '" title="'
                        . htmlspecialchars($language['title']) . '" class="btn btn-default t3js-action-localize">'
                        . $lC . '</a> ';
                    $lNew .= $lC;
                }
            }
            if ($lNew) {
                $out[1] .= $lNew;
            }
        } elseif ($row['l18n_parent']) {
            $out[0] = '&nbsp;&nbsp;&nbsp;&nbsp;' . $out[0];
        }
        return $out;
    }

    /**
     * Creates a checkbox list for selecting fields to display from a table:
     *
     * @param string $table Table name
     * @param bool $formFields If TRUE, form-fields will be wrapped around the table.
     * @return string HTML table with the selector check box (name: displayFields['.$table.'][])
     */
    public function fieldSelectBox($table, $formFields = true)
    {
        $lang = $this->getLanguageService();
        // Init:
        $formElements = ['', ''];
        if ($formFields) {
            $formElements = ['<form action="' . htmlspecialchars($this->listURL()) . '" method="post" name="fieldSelectBox">', '</form>'];
        }
        // Load already selected fields, if any:
        $setFields = is_array($this->setFields[$table]) ? $this->setFields[$table] : [];
        // Request fields from table:
        $fields = $this->makeFieldList($table, false, true);
        // Add pseudo "control" fields
        $fields[] = '_PATH_';
        $fields[] = '_REF_';
        $fields[] = '_LOCALIZATION_';
        $fields[] = '_CONTROL_';
        $fields[] = '_CLIPBOARD_';
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
            if (in_array($fieldName, $setFields, true) || $fieldName === $this->fieldArray[0]) {
                $checked = ' checked="checked"';
            } else {
                $checkAllChecked = false;
                $checked = '';
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

            $checkboxes[] = '<tr><td class="col-checkbox"><input type="checkbox" id="check-' . $fieldName . '" name="displayFields['
                . $table . '][]" value="' . $fieldName . '" ' . $checked
                . ($fieldName === $this->fieldArray[0] ? ' disabled="disabled"' : '') . '></td><td class="col-title">'
                . '<label class="label-block" for="check-' . $fieldName . '">' . htmlspecialchars($lang->sL($fieldLabel)) . ' <span class="text-muted text-monospace">[' . htmlspecialchars($fieldName) . ']</span></label></td></tr>';
        }
        // Table with the field selector::
        $content = $formElements[0] . '
			<input type="hidden" name="displayFields[' . $table . '][]" value="">
			<div class="table-fit table-scrollable">
				<table border="0" cellpadding="0" cellspacing="0" class="table table-transparent table-hover">
					<thead>
						<tr>
							<th class="col-checkbox checkbox" colspan="2">
								<label><input type="checkbox" class="checkbox checkAll" ' . ($checkAllChecked ? ' checked="checked"' : '') . '>
								' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.toggleall')) . '</label>
							</th>
						</tr>
					</thead>
					<tbody>
					' . implode('', $checkboxes) . '
					</tbody>
				</table>
			</div>
			<input type="submit" name="search" class="btn btn-default" value="'
            . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.setFields')) . '"/>
			' . $formElements[1];
        return '<div class="fieldSelectBox">' . $content . '</div>';
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
     * @return string <a> tag wrapped link.
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
            $attributes['data-content'] = $warning;
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
        return in_array('_CLIPBOARD_', $this->fieldArray) && $this->clipObj->current !== 'normal';
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
     * @see \TYPO3\CMS\Backend\Controller\NewRecordController::showNewRecLink
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
     * Creates the "&returnUrl" parameter for links - this is used when the script links
     * to other scripts and passes its own URL with the link so other scripts can return to the listing again.
     * Uses REQUEST_URI as value.
     *
     * @return string
     */
    public function makeReturnUrl()
    {
        return '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'));
    }

    /************************************
     *
     * CSV related functions
     *
     ************************************/
    /**
     * Initializes internal csvLines array with the header of field names
     */
    protected function initCSV()
    {
        $this->addHeaderRowToCSV();
    }

    /**
     * Add header line with field names as CSV line
     */
    protected function addHeaderRowToCSV()
    {
        $fieldArray = array_combine($this->fieldArray, $this->fieldArray);
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][__CLASS__]['customizeCsvHeader'] ?? [];
        if (!empty($hooks)) {
            $hookParameters = [
                'fields' => &$fieldArray
            ];
            foreach ($hooks as $hookFunction) {
                GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
            }
        }
        // Add header row, control fields will be reduced inside addToCSV()
        $this->addToCSV($fieldArray);
    }

    /**
     * Adds selected columns of one table row as CSV line.
     *
     * @param mixed[] $row Record array, from which the values of fields found in $this->fieldArray will be listed in the CSV output.
     */
    protected function addToCSV(array $row = [])
    {
        $rowReducedByControlFields = self::removeControlFieldsFromFieldRow($row);
        // Get an field array without control fields but in the expected order
        $fieldArray = array_intersect_key(array_flip($this->fieldArray), $rowReducedByControlFields);
        // Overwrite fieldArray to keep the order with an array of needed fields
        $rowReducedToSelectedColumns = array_replace($fieldArray, array_intersect_key($rowReducedByControlFields, $fieldArray));
        $this->setCsvRow($rowReducedToSelectedColumns);
    }

    /**
     * Remove control fields from row for CSV export
     *
     * @param mixed[] $row fieldNames => fieldValues
     * @return mixed[] Input array reduces by control fields
     */
    protected static function removeControlFieldsFromFieldRow(array $row = [])
    {
        // Possible control fields in a list row
        $controlFields = [
            '_PATH_',
            '_REF_',
            '_CONTROL_',
            '_CLIPBOARD_',
            '_LOCALIZATION_',
            '_LOCALIZATION_b'
        ];
        return array_diff_key($row, array_flip($controlFields));
    }

    /**
     * Adds input row of values to the internal csvLines array as a CSV formatted line
     *
     * @param mixed[] $csvRow Array with values to be listed.
     */
    public function setCsvRow($csvRow)
    {
        $csvDelimiter = $this->modTSconfig['properties']['csvDelimiter'] ?? ',';
        $csvQuote = $this->modTSconfig['properties']['csvQuote'] ?? '"';

        $this->csvLines[] = CsvUtility::csvValues($csvRow, $csvDelimiter, $csvQuote);
    }

    /**
     * Compiles the internal csvLines array to a csv-string and outputs it to the browser.
     * This function exits!
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
     * @param bool $isEditable
     */
    public function setIsEditable($isEditable)
    {
        $this->editable = $isEditable;
    }

    /**
     * Check if the table is readonly or editable
     * @param string $table
     * @return bool
     */
    public function isEditable($table)
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
     * @return BaseScriptClass
     */
    protected function getModule()
    {
        return $GLOBALS['SOBE'];
    }

    /**
     * @return DocumentTemplate
     */
    protected function getDocumentTemplate()
    {
        return $GLOBALS['TBE_TEMPLATE'];
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
        // Store languages that are included in the site configuration for the current page.
        $this->systemLanguagesOnPage = $this->translateTools->getSystemLanguages($this->id);
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
                    ->setMaxResults(1)
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
     * Creates the search box
     *
     * @param bool $formFields If TRUE, the search box is wrapped in its own form-tags
     * @return string HTML for the search box
     */
    public function getSearchBox($formFields = true)
    {
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $lang = $this->getLanguageService();
        // Setting form-elements, if applicable:
        $formElements = ['', ''];
        if ($formFields) {
            $formElements = [
                '<form action="' . htmlspecialchars(
                    $this->listURL('', '-1', 'firstElementNumber,search_field')
                ) . '" method="post">',
                '</form>'
            ];
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
            $opt[] = '<option value="' . $kv . '"' . ($kv === $this->searchLevels ? ' selected="selected"' : '') . '>' . htmlspecialchars(
                $label
            ) . '</option>';
        }
        $lMenu = '<select class="form-control" name="search_levels" title="' . htmlspecialchars(
            $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.title.search_levels')
        ) . '" id="search_levels">' . implode('', $opt) . '</select>';
        // Table with the search box:
        $content = '<div class="db_list-searchbox-form db_list-searchbox-toolbar module-docheader-bar module-docheader-bar-search t3js-module-docheader-bar t3js-module-docheader-bar-search" id="db_list-searchbox-toolbar" style="display: ' . ($this->searchString == '' ? 'none' : 'block') . ';">
			' . $formElements[0] . '
                <div id="typo3-dblist-search">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-sm-6 col-xs-12">
                                    <label for="search_field">' . htmlspecialchars(
            $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.label.searchString')
        ) . '</label>
									<input class="form-control" type="search" placeholder="' . htmlspecialchars(
            $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enterSearchString')
        ) . '" title="' . htmlspecialchars(
            $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.title.searchString')
        ) . '" name="search_field" id="search_field" value="' . htmlspecialchars($this->searchString) . '" />
                                </div>
                                <div class="col-xs-12 col-sm-3">
									<label for="search_levels">' . htmlspecialchars(
            $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.label.search_levels')
        ) . '</label>
									' . $lMenu . '
                                </div>
                                <div class="col-xs-12 col-sm-3">
									<label for="showLimit">' . htmlspecialchars(
            $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.label.limit')
        ) . '</label>
									<input class="form-control" type="number" min="0" max="10000" placeholder="10" title="' . htmlspecialchars(
            $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.title.limit')
        ) . '" name="showLimit" id="showLimit" value="' . htmlspecialchars(
            ($this->showLimit ? $this->showLimit : '')
        ) . '" />
                                </div>
                                <div class="col-xs-12">
                                    <div class="form-control-wrap">
                                        <button type="submit" class="btn btn-default" name="search" title="' . htmlspecialchars(
            $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.title.search')
        ) . '">
                                            ' . $iconFactory->getIcon('actions-search', Icon::SIZE_SMALL)->render(
            ) . ' ' . htmlspecialchars(
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.search')
            ) . '
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
     * Returns a QueryBuilder configured to select $fields from $table where the pid is restricted
     * depending on the current searchlevel setting.
     *
     * @param string $table Table name
     * @param int $pageId Page id Only used to build the search constraints, getPageIdConstraint() used for restrictions
     * @param string[] $additionalConstraints Additional part for where clause
     * @param string[] $fields Field list to select, * for all
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
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

        if ($this->firstElementNumber > 0) {
            $queryBuilder->setFirstResult($this->firstElementNumber);
        }

        if ($addSorting) {
            if ($this->sortField && in_array($this->sortField, $this->makeFieldList($table, 1))) {
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
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$hookName]['modifyQuery'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (method_exists($hookObject, 'modifyQuery')) {
                $hookObject->modifyQuery(
                    $parameters,
                    $table,
                    $pageId,
                    $additionalConstraints,
                    $fieldList,
                    $queryBuilder
                );
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
                [$fieldName, $sorting] = $fieldNameAndSorting;
                $queryBuilder->addOrderBy($fieldName, $sorting);
            }
        }
        if (!empty($parameters['firstResult']) && $parameters['firstResult'] !== $this->firstElementNumber) {
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
                    $localCalcPerms = $this->getBackendUserAuthentication()->calcPerms(
                        BackendUtility::getRecord('pages', $row['uid'])
                    );
                    $permsEdit = $localCalcPerms & Permission::PAGE_EDIT;
                } else {
                    $backendUser = $this->getBackendUserAuthentication();
                    $permsEdit = $this->calcPerms & Permission::CONTENT_EDIT && $backendUser->recordEditAccessInternals($table, $row);
                }
                // "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
                if ($permsEdit && $this->isEditable($table)) {
                    $params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
                    $code = '<a href="#" onclick="' . htmlspecialchars(
                        BackendUtility::editOnClick($params, '', -1)
                    ) . '" title="' . htmlspecialchars($lang->getLL('edit')) . '">' . $code . '</a>';
                }
                break;
            case 'show':
                // "Show" link (only pages and tt_content elements)
                if ($table === 'pages' || $table === 'tt_content') {
                    $onClick = $this->getOnClickForRow($table, $row);
                    $code = '<a href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . htmlspecialchars(
                        $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage')
                    ) . '">' . $code . '</a>';
                }
                break;
            case 'info':
                // "Info": (All records)
                $code = '<a href="#" onclick="' . htmlspecialchars(
                    'top.TYPO3.InfoWindow.showItem(' . GeneralUtility::quoteJSvalue($table) . ', ' . (int)$row['uid'] . '); return false;'
                ) . '" title="' . htmlspecialchars($lang->getLL('showInfo')) . '">' . $code . '</a>';
                break;
            default:
                // Output the label now:
                if ($table === 'pages') {
                    $code = '<a href="' . htmlspecialchars(
                        $this->listURL($uid, '', 'firstElementNumber')
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
                    if (ExtensionManagementUtility::isLoaded(
                        'workspaces'
                    ) && $GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
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
     * Redirects to FormEngine if a record is just localized.
     *
     * @param string $justLocalized String with table, orig uid and language separated by ":
     */
    public function localizationRedirect($justLocalized)
    {
        [$table, $orig_uid, $language] = explode(':', $justLocalized);
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
        $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime');
        $hash = 'webmounts_list' . md5($id . '-' . $depth . '-' . $perms_clause);
        if ($runtimeCache->has($hash)) {
            $idList = $runtimeCache->get($hash);
        } else {
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
     * @return QueryBuilder Modified QueryBuilder object
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

        // When querying translated pages, the PID of the translated pages should be the same as the
        // the PID of the current page
        if ($tableName === 'pages' && $this->showOnlyTranslatedRecords) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    $tableName . '.pid',
                    $queryBuilder->createNamedParameter($this->pageRecord['pid'], \PDO::PARAM_INT)
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
     * Method used to log deprecated usage of old buildQueryParametersPostProcess hook arguments
     *
     * @param string $index
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0 - see method usages
     */
    protected function logDeprecation(string $index)
    {
        trigger_error('[index: ' . $index . '] $parameters in "buildQueryParameters"-Hook will be removed in TYPO3 v10.0, use $queryBuilder instead.', E_USER_DEPRECATED);
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
     * @param int $h Is an integer >=0 and denotes how tall an element is. Set to '0' makes a halv line, -1 = full line, set to 1 makes a 'join' and above makes 'line'
     * @param string $icon Is the <img>+<a> of the record. If not supplied the first 'join'-icon will be a 'line' instead
     * @param array $data Is the dataarray, record with the fields. Notice: These fields are (currently) NOT htmlspecialchar'ed before being wrapped in <td>-tags
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
        $noWrap = $this->no_noWrap ? '' : ' nowrap';
        // Start up:
        $l10nParent = isset($data['_l10nparent_']) ? (int)$data['_l10nparent_'] : 0;
        $out = '
		<!-- Element, begin: -->
		<tr ' . $rowParams . ' data-uid="' . (int)$data['uid'] . '" data-l10nparent="' . $l10nParent . '">';
        // Show icon and lines
        if ($this->showIcon) {
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
            $out .= '</' . $colType . '>
			';
        }
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
                    if ($this->oddColumnsCssClass && $ccount % 2 == 0) {
                        $cssClass = implode(' ', [$this->addElement_tdCssClass[$lastKey], $this->oddColumnsCssClass]);
                    }
                    $out .= '
						<' . $colType . ' class="' . $cssClass . $noWrap . '"' . $colsp . $this->addElement_tdParams[$lastKey] . '>' . $data[$lastKey] . '</' . $colType . '>';
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
            if ($this->oddColumnsCssClass) {
                $cssClass = implode(' ', [$this->addElement_tdCssClass[$lastKey], $this->oddColumnsCssClass]);
            }
            $out .= '
				<' . $colType . ' class="' . $cssClass . $noWrap . '"' . $colsp . $this->addElement_tdParams[$lastKey] . '>' . $data[$lastKey] . '</' . $colType . '>';
        }
        // End row
        $out .= '
		</tr>';
        // Return row.
        return $out;
    }

    /**
     * Dummy function, used to write the top of a table listing.
     */
    public function writeTop()
    {
    }

    /**
     * Creates a forward/reverse button based on the status of ->eCounter, ->firstElementNumber, ->iLimit
     *
     * @param string $table Table name
     * @return array array([boolean], [HTML]) where [boolean] is 1 for reverse element, [HTML] is the table-row code for the element
     */
    public function fwd_rwd_nav($table = '')
    {
        $code = '';
        if ($this->eCounter >= $this->firstElementNumber && $this->eCounter < $this->firstElementNumber + $this->iLimit) {
            if ($this->firstElementNumber && $this->eCounter == $this->firstElementNumber) {
                // 	Reverse
                $theData = [];
                $titleCol = $this->fieldArray[0];
                $theData[$titleCol] = $this->fwd_rwd_HTML('fwd', $this->eCounter, $table);
                $code = $this->addElement(1, '', $theData, 'class="fwd_rwd_nav"');
            }
            return [1, $code];
        }
        if ($this->eCounter == $this->firstElementNumber + $this->iLimit) {
            // 	Forward
            $theData = [];
            $titleCol = $this->fieldArray[0];
            $theData[$titleCol] = $this->fwd_rwd_HTML('rwd', $this->eCounter, $table);
            $code = $this->addElement(1, '', $theData, 'class="fwd_rwd_nav"');
        }
        return [0, $code];
    }

    /**
     * Creates the button with link to either forward or reverse
     *
     * @param string $type Type: "fwd" or "rwd
     * @param int $pointer Pointer
     * @param string $table Table name
     * @return string
     * @internal
     */
    public function fwd_rwd_HTML($type, $pointer, $table = '')
    {
        $content = '';
        $tParam = $table ? '&table=' . rawurlencode($table) : '';
        switch ($type) {
            case 'fwd':
                $href = $this->listURL() . '&pointer=' . ($pointer - $this->iLimit) . $tParam;
                $content = '<a href="' . htmlspecialchars($href) . '">' . $this->iconFactory->getIcon(
                    'actions-move-up',
                    Icon::SIZE_SMALL
                )->render() . '</a> <i>[' . (max(0, $pointer - $this->iLimit) + 1) . ' - ' . $pointer . ']</i>';
                break;
            case 'rwd':
                $href = $this->listURL() . '&pointer=' . $pointer . $tParam;
                $content = '<a href="' . htmlspecialchars($href) . '">' . $this->iconFactory->getIcon(
                    'actions-move-down',
                    Icon::SIZE_SMALL
                )->render() . '</a> <i>[' . ($pointer + 1) . ' - ' . $this->totalItems . ']</i>';
                break;
        }
        return $content;
    }

    /**
     * Returning JavaScript for ClipBoard functionality.
     *
     * @return string
     */
    public function CBfunctions()
    {
        return '
		// checkOffCB()
	function checkOffCB(listOfCBnames, link) {	//
		var checkBoxes, flag, i;
		var checkBoxes = listOfCBnames.split(",");
		if (link.rel === "") {
			link.rel = "allChecked";
			flag = true;
		} else {
			link.rel = "";
			flag = false;
		}
		for (i = 0; i < checkBoxes.length; i++) {
			setcbValue(checkBoxes[i], flag);
		}
	}
		// cbValue()
	function cbValue(CBname) {	//
		var CBfullName = "CBC["+CBname+"]";
		return (document.dblistForm[CBfullName] && document.dblistForm[CBfullName].checked ? 1 : 0);
	}
		// setcbValue()
	function setcbValue(CBname,flag) {	//
		CBfullName = "CBC["+CBname+"]";
		if(document.dblistForm[CBfullName]) {
			document.dblistForm[CBfullName].checked = flag ? "on" : 0;
		}
	}

		';
    }

    /**
     * Initializes page languages and icons
     */
    public function initializeLanguages()
    {
        // Look up page overlays:
        $localizationParentField = $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'];
        $languageField = $GLOBALS['TCA']['pages']['ctrl']['languageField'];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
        $result = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq($localizationParentField, $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)),
                    $queryBuilder->expr()->gt(
                        $languageField,
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                )
            )
            ->execute();

        $this->pageOverlays = [];
        while ($row = $result->fetch()) {
            $this->pageOverlays[$row[$languageField]] = $row;
        }

        $this->languageIconTitles = $this->getTranslateTools()->getSystemLanguages($this->id);
    }

    /**
     * Return the icon for the language
     *
     * @param int $sys_language_uid Sys language uid
     * @param bool $addAsAdditionalText If set to true, only the flag is returned
     * @return string Language icon
     */
    public function languageFlag($sys_language_uid, $addAsAdditionalText = true)
    {
        $out = '';
        $title = htmlspecialchars($this->languageIconTitles[$sys_language_uid]['title']);
        if ($this->languageIconTitles[$sys_language_uid]['flagIcon']) {
            $out .= '<span title="' . $title . '">' . $this->iconFactory->getIcon(
                $this->languageIconTitles[$sys_language_uid]['flagIcon'],
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
     * Sets the script url depending on being a module or script request
     */
    protected function determineScriptUrl()
    {
        if ($routePath = GeneralUtility::_GP('route')) {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $this->thisScript = (string)$uriBuilder->buildUriFromRoutePath($routePath);
        } else {
            $this->thisScript = GeneralUtility::getIndpEnv('SCRIPT_NAME');
        }
    }

    /**
     * @return string
     */
    protected function getThisScript()
    {
        return strpos($this->thisScript, '?') === false ? $this->thisScript . '?' : $this->thisScript . '&';
    }

    /**
     * Gets an instance of TranslationConfigurationProvider
     *
     * @return TranslationConfigurationProvider
     */
    protected function getTranslateTools()
    {
        if (!isset($this->translateTools)) {
            $this->translateTools = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
        }
        return $this->translateTools;
    }

    /**
     * Generates HTML code for a Reference tooltip out of
     * sys_refindex records you hand over
     *
     * @param int $references number of records from sys_refindex table
     * @param string $launchViewParameter JavaScript String, which will be passed as parameters to top.TYPO3.InfoWindow.showItem
     * @return string
     */
    protected function generateReferenceToolTip($references, $launchViewParameter = '')
    {
        if (!$references) {
            $htmlCode = '-';
        } else {
            $htmlCode = '<a href="#"';
            if ($launchViewParameter !== '') {
                $htmlCode .= ' onclick="' . htmlspecialchars(
                    'top.TYPO3.InfoWindow.showItem(' . $launchViewParameter . '); return false;'
                ) . '"';
            }
            $htmlCode .= ' title="' . htmlspecialchars(
                $this->getLanguageService()->sL(
                    'LLL:EXT:backend/Resources/Private/Language/locallang.xlf:show_references'
                ) . ' (' . $references . ')'
            ) . '">';
            $htmlCode .= $references;
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
     * Flatten palettes into types showitem
     *
     * By replacing the palettes in showitem of a TCA type with each palette content, the fields within a palette
     * can be considered as visible database columns for a backend form.
     *
     * @param array $tableTCA
     * @param string $type
     * @return string
     */
    protected function getVisibleColumns(array $tableTCA, string $type)
    {
        $visibleColumns = $tableTCA['types'][$type]['showitem'] ?? '';

        if (strpos($visibleColumns, '--palette--') !== false) {
            $matches = [];
            preg_match_all('/--palette--\s*;[^;]*;\s*(\w+)/', $visibleColumns, $matches, PREG_SET_ORDER);
            if (!empty($matches)) {
                foreach ($matches as $palette) {
                    $paletteColumns = $tableTCA['palettes'][$palette[1]]['showitem'] ?? '';
                    $paletteColumns = rtrim($paletteColumns, ", \t\r\n");
                    $visibleColumns = str_replace($palette[0], $paletteColumns, $visibleColumns);
                }
            }
        }

        return $visibleColumns;
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
}
