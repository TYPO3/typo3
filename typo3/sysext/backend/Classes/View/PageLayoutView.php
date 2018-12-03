<?php

namespace TYPO3\CMS\Backend\View;

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

use Doctrine\DBAL\Driver\Statement;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Controller\Page\LocalizationController;
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;

/**
 * Child class for the Web > Page module
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class PageLayoutView implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * If TRUE, users/groups are shown in the page info box.
     *
     * @var bool
     */
    public $pI_showUser = false;

    /**
     * The number of successive records to edit when showing content elements.
     *
     * @var int
     */
    public $nextThree = 3;

    /**
     * If TRUE, disables the edit-column icon for tt_content elements
     *
     * @var bool
     */
    public $pages_noEditColumns = false;

    /**
     * If TRUE, new-wizards are linked to rather than the regular new-element list.
     *
     * @var bool
     */
    public $option_newWizard = true;

    /**
     * If set to "1", will link a big button to content element wizard.
     *
     * @var int
     */
    public $ext_function = 0;

    /**
     * If TRUE, elements will have edit icons (probably this is whether the user has permission to edit the page content). Set externally.
     *
     * @var bool
     */
    public $doEdit = true;

    /**
     * Age prefixes for displaying times. May be set externally to localized values.
     *
     * @var string
     */
    public $agePrefixes = ' min| hrs| days| yrs| min| hour| day| year';

    /**
     * Array of tables to be listed by the Web > Page module in addition to the default tables.
     *
     * @var array
     */
    public $externalTables = [];

    /**
     * "Pseudo" Description -table name
     *
     * @var string
     */
    public $descrTable;

    /**
     * If set TRUE, the language mode of tt_content elements will be rendered with hard binding between
     * default language content elements and their translations!
     *
     * @var bool
     */
    public $defLangBinding = false;

    /**
     * External, static: Configuration of tt_content element display:
     *
     * @var array
     */
    public $tt_contentConfig = [
        // Boolean: Display info-marks or not
        'showInfo' => 1,
        // Boolean: Display up/down arrows and edit icons for tt_content records
        'showCommands' => 1,
        'languageCols' => 0,
        'languageMode' => 0,
        'languageColsPointer' => 0,
        'showHidden' => 1,
        // Displays hidden records as well
        'sys_language_uid' => 0,
        // Which language
        'cols' => '1,0,2,3',
        'activeCols' => '1,0,2,3'
        // Which columns can be accessed by current BE user
    ];

    /**
     * Contains icon/title of pages which are listed in the tables menu (see getTableMenu() function )
     *
     * @var array
     */
    public $activeTables = [];

    /**
     * @var array
     */
    public $tt_contentData = [
        'nextThree' => [],
        'prev' => [],
        'next' => []
    ];

    /**
     * Used to store labels for CTypes for tt_content elements
     *
     * @var array
     */
    public $CType_labels = [];

    /**
     * Used to store labels for the various fields in tt_content elements
     *
     * @var array
     */
    public $itemLabels = [];

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
     * Shared module configuration, used by localization features
     *
     * @var array
     */
    public $modSharedTSconfig = [];

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
     * Tables which should not list their translations
     *
     * @var string
     */
    public $hideTranslations = '';

    /**
     * If set, csvList is outputted.
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
     * Page select permissions
     *
     * @var string
     */
    public $perms_clause = '';

    /**
     * Page id
     *
     * @var int
     */
    public $id;

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
     * Used for tracking duplicate values of fields
     *
     * @var string[]
     */
    public $duplicateStack = [];

    /**
     * Fields to display for the current table
     *
     * @var string[]
     */
    public $setFields = [];

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
     * JavaScript code accumulation
     *
     * @var string
     */
    public $JScode = '';

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
     * default Max items shown per table in "single-table mode", may be overridden by tables.php
     *
     * @var int
     */
    public $itemsLimitSingleTable = 100;

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
     * HTML output
     *
     * @var string
     */
    public $HTMLcode = '';

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
     * OBSOLETE - NOT USED ANYMORE. leftMargin
     *
     * @var int
     */
    public $leftMargin = 0;

    /**
     * Decides the columns shown. Filled with values that refers to the keys of the data-array. $this->fieldArray[0] is the title column.
     *
     * @var array
     */
    public $fieldArray = [];

    /**
     * Set to zero, if you don't want a left-margin with addElement function
     *
     * @var int
     */
    public $setLMargin = 1;

    /**
     * Contains page translation languages
     *
     * @var array
     */
    public $pageOverlays = [];

    /**
     * Counter increased for each element. Used to index elements for the JavaScript-code that transfers to the clipboard
     *
     * @var int
     */
    public $counter = 0;

    /**
     * Contains sys language icons and titles
     *
     * @var array
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Use site languages instead.
     */
    public $languageIconTitles = [];

    /**
     * Contains site languages for this page ID
     *
     * @var SiteLanguage[]
     */
    protected $siteLanguages = [];

    /**
     * Script URL
     *
     * @var string
     */
    public $thisScript = '';

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
     * @var TranslationConfigurationProvider
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public $translateTools;

    /**
     * Keys are fieldnames and values are td-parameters to add in addElement(), please use $addElement_tdCSSClass for CSS-classes;
     *
     * @var array
     */
    public $addElement_tdParams = [];

    /**
     * @var int
     */
    public $no_noWrap = 0;

    /**
     * @var int
     */
    public $showIcon = 1;

    /**
     * Keys are fieldnames and values are td-css-classes to add in addElement();
     *
     * @var array
     */
    public $addElement_tdCssClass = [];

    /**
     * @var \TYPO3\CMS\Backend\Clipboard\Clipboard
     */
    protected $clipboard;

    /**
     * User permissions
     *
     * @var int
     */
    public $ext_CALC_PERMS;

    /**
     * Current ids page record
     *
     * @var array
     */
    protected $pageinfo;

    /**
     * Caches the available languages in a colPos
     *
     * @var array
     */
    protected $languagesInColumnCache = [];

    /**
     * Caches the amount of content elements as a matrix
     *
     * @var array
     * @internal
     */
    protected $contentElementCache = [];

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Stores whether a certain language has translations in it
     *
     * @var array
     */
    protected $languageHasTranslationsCache = [];

    /**
     * @var LocalizationController
     */
    protected $localizationController;

    /**
     * Override the page ids taken into account by getPageIdConstraint()
     *
     * @var array
     */
    protected $overridePageIdList = [];

    /**
     * Override/add urlparameters in listUrl() method
     *
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
     * Cache the number of references to a record
     *
     * @var array
     */
    protected $referenceCount = [];

    /**
     * Construct to initialize class variables.
     */
    public function __construct()
    {
        if (isset($GLOBALS['BE_USER']->uc['titleLen']) && $GLOBALS['BE_USER']->uc['titleLen'] > 0) {
            $this->fixedL = $GLOBALS['BE_USER']->uc['titleLen'];
        }
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Remove this instance along with the property.
        $this->translateTools = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
        $this->determineScriptUrl();
        $this->localizationController = GeneralUtility::makeInstance(LocalizationController::class);
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_layout.xlf');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Tooltip');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Localization');
    }

    /*****************************************
     *
     * Renderings
     *
     *****************************************/
    /**
     * Adds the code of a single table
     *
     * @param string $table Table name
     * @param int $id Current page id
     * @param string $fields
     * @return string HTML for listing.
     */
    public function getTable($table, $id, $fields = '')
    {
        if (isset($this->externalTables[$table])) {
            return $this->getExternalTables($id, $table);
        }
        // Branch out based on table name:
        switch ($table) {
                case 'pages':
                    return $this->getTable_pages($id);
                case 'tt_content':
                    return $this->getTable_tt_content($id);
                default:
                    return '';
            }
    }

    /**
     * Renders an external table from page id
     *
     * @param int $id Page id
     * @param string $table Name of the table
     * @return string HTML for the listing
     */
    public function getExternalTables($id, $table)
    {
        $this->pageinfo = BackendUtility::readPageAccess($id, '');
        $type = $this->getPageLayoutController()->MOD_SETTINGS[$table];
        if (!isset($type)) {
            $type = 0;
        }
        // eg. "name;title;email;company,image"
        $fList = $this->externalTables[$table][$type]['fList'];
        // The columns are separeted by comma ','.
        // Values separated by semicolon ';' are shown in the same column.
        $icon = $this->externalTables[$table][$type]['icon'];
        $addWhere = $this->externalTables[$table][$type]['addWhere'];
        // Create listing
        $out = $this->makeOrdinaryList($table, $id, $fList, $icon, $addWhere);
        return $out;
    }

    /**
     * Renders records from the pages table from page id
     * (Used to get information about the page tree content by "Web>Info"!)
     *
     * @param int $id Page id
     * @return string HTML for the listing
     */
    public function getTable_pages($id)
    {
        // Initializing:
        $out = '';
        $lang = $this->getLanguageService();
        // Select current page:
        if (!$id) {
            // The root has a pseudo record in pageinfo...
            $row = $this->getPageLayoutController()->pageinfo;
        } else {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $row = $queryBuilder
                ->select('*')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)),
                    $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)
                )
                ->execute()
                ->fetch();
            BackendUtility::workspaceOL('pages', $row);
        }
        // If there was found a page:
        if (is_array($row)) {
            // Getting select-depth:
            $depth = (int)$this->getPageLayoutController()->MOD_SETTINGS['pages_levels'];
            // Overriding a few things:
            $this->no_noWrap = 0;
            // Items
            $this->eCounter = $this->firstElementNumber;
            // Creating elements:
            list($flag, $code) = $this->fwd_rwd_nav();
            $out .= $code;
            $editUids = [];
            if ($flag) {
                // Getting children:
                $theRows = $this->getPageRecordsRecursive($row['uid'], $depth);
                if ($this->getBackendUser()->doesUserHaveAccess($row, 2) && $row['uid'] > 0) {
                    $editUids[] = $row['uid'];
                }
                $out .= $this->pages_drawItem($row, $this->fieldArray);
                // Traverse all pages selected:
                foreach ($theRows as $sRow) {
                    if ($this->getBackendUser()->doesUserHaveAccess($sRow, 2)) {
                        $editUids[] = $sRow['uid'];
                    }
                    $out .= $this->pages_drawItem($sRow, $this->fieldArray);
                }
                $this->eCounter++;
            }
            // Header line is drawn
            $theData = [];
            $editIdList = implode(',', $editUids);
            // Traverse fields (as set above) in order to create header values:
            foreach ($this->fieldArray as $field) {
                if ($editIdList
                    && isset($GLOBALS['TCA']['pages']['columns'][$field])
                    && $field !== 'uid'
                    && !$this->pages_noEditColumns
                ) {
                    $iTitle = sprintf(
                        $lang->getLL('editThisColumn'),
                        rtrim(trim($lang->sL(BackendUtility::getItemLabel('pages', $field))), ':')
                    );
                    $urlParameters = [
                        'edit' => [
                            'pages' => [
                                $editIdList => 'edit'
                            ]
                        ],
                        'columnsOnly' => $field,
                        'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                    ];
                    $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                    $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                    $eI = '<a class="btn btn-default" href="' . htmlspecialchars($url)
                        . '" title="' . htmlspecialchars($iTitle) . '">'
                        . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
                } else {
                    $eI = '';
                }
                switch ($field) {
                    case 'title':
                        $theData[$field] = $eI . '&nbsp;<strong>'
                            . $lang->sL($GLOBALS['TCA']['pages']['columns'][$field]['label'])
                            . '</strong>';
                        break;
                    case 'uid':
                        $theData[$field] = '';
                        break;
                    default:
                        if (strpos($field, 'table_') === 0) {
                            $f2 = substr($field, 6);
                            if ($GLOBALS['TCA'][$f2]) {
                                $theData[$field] = '&nbsp;' .
                                    '<span title="' .
                                    htmlspecialchars($lang->sL($GLOBALS['TCA'][$f2]['ctrl']['title'])) .
                                    '">' .
                                    $this->iconFactory->getIconForRecord($f2, [], Icon::SIZE_SMALL)->render() .
                                    '</span>';
                            }
                        } else {
                            $theData[$field] = $eI . '&nbsp;<strong>'
                                . htmlspecialchars($lang->sL($GLOBALS['TCA']['pages']['columns'][$field]['label']))
                                . '</strong>';
                        }
                }
            }
            $out = '<div class="table-fit">'
                . '<table class="table table-striped table-hover typo3-page-pages">'
                    . '<thead>'
                            . $this->addElement(1, '', $theData)
                    . '</thead>'
                    . '<tbody>'
                        . $out
                    . '</tbody>'
                . '</table>'
                . '</div>';
        }
        return $out;
    }

    /**
     * Renders Content Elements from the tt_content table from page id
     *
     * @param int $id Page id
     * @return string HTML for the listing
     */
    public function getTable_tt_content($id)
    {
        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content')
            ->getExpressionBuilder();
        $this->pageinfo = BackendUtility::readPageAccess($this->id, '');
        $this->initializeLanguages();
        $this->initializeClipboard();
        $pageTitleParamForAltDoc = '&recTitle=' . rawurlencode(BackendUtility::getRecordTitle('pages', BackendUtility::getRecordWSOL('pages', $id), true));
        /** @var PageRenderer $pageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/LayoutModule/DragDrop');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/LayoutModule/Paste');
        if ($this->isPageEditable()) {
            $languageOverlayId = 0;
            $pageLocalizationRecord = BackendUtility::getRecordLocalization('pages', $this->id, (int)$this->tt_contentConfig['sys_language_uid']);
            if (is_array($pageLocalizationRecord)) {
                $pageLocalizationRecord = reset($pageLocalizationRecord);
            }
            if (!empty($pageLocalizationRecord['uid'])) {
                $languageOverlayId = $pageLocalizationRecord['uid'];
            }
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/PageActions', 'function(PageActions) {
                PageActions.setPageId(' . (int)$this->id . ');
                PageActions.setLanguageOverlayId(' . $languageOverlayId . ');
            }');
        }
        // Get labels for CTypes and tt_content element fields in general:
        $this->CType_labels = [];
        foreach ($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] as $val) {
            $this->CType_labels[$val[1]] = $this->getLanguageService()->sL($val[0]);
        }

        $this->itemLabels = [];
        foreach ($GLOBALS['TCA']['tt_content']['columns'] as $name => $val) {
            $this->itemLabels[$name] = $this->getLanguageService()->sL($val['label']);
        }
        $languageColumn = [];
        $out = '';

        // Setting language list:
        $langList = $this->tt_contentConfig['sys_language_uid'];
        if ($this->tt_contentConfig['languageMode']) {
            if ($this->tt_contentConfig['languageColsPointer']) {
                $langList = '0,' . $this->tt_contentConfig['languageColsPointer'];
            } else {
                $langList = implode(',', array_keys($this->tt_contentConfig['languageCols']));
            }
            $languageColumn = [];
        }
        $langListArr = GeneralUtility::intExplode(',', $langList);
        $defaultLanguageElementsByColumn = [];
        $defLangBinding = [];
        // For each languages... :
        // If not languageMode, then we'll only be through this once.
        foreach ($langListArr as $lP) {
            $lP = (int)$lP;

            if (!isset($this->contentElementCache[$lP])) {
                $this->contentElementCache[$lP] = [];
            }

            if (count($langListArr) === 1 || $lP === 0) {
                $showLanguage = $expressionBuilder->in('sys_language_uid', [$lP, -1]);
            } else {
                $showLanguage = $expressionBuilder->eq('sys_language_uid', $lP);
            }
            $content = [];
            $head = [];

            $backendLayout = $this->getBackendLayoutView()->getSelectedBackendLayout($this->id);
            $columns = $backendLayout['__colPosList'];
            // Select content records per column
            $contentRecordsPerColumn = $this->getContentRecordsPerColumn('table', $id, $columns, $showLanguage);
            $cList = array_keys($contentRecordsPerColumn);
            // For each column, render the content into a variable:
            foreach ($cList as $columnId) {
                if (!isset($this->contentElementCache[$lP])) {
                    $this->contentElementCache[$lP] = [];
                }

                if (!$lP) {
                    $defaultLanguageElementsByColumn[$columnId] = [];
                }

                // Start wrapping div
                $content[$columnId] .= '<div data-colpos="' . $columnId . '" data-language-uid="' . $lP . '" class="t3js-sortable t3js-sortable-lang t3js-sortable-lang-' . $lP . ' t3-page-ce-wrapper';
                if (empty($contentRecordsPerColumn[$columnId])) {
                    $content[$columnId] .= ' t3-page-ce-empty';
                }
                $content[$columnId] .= '">';
                // Add new content at the top most position
                $link = '';
                if ($this->isContentEditable()
                    && (!$this->checkIfTranslationsExistInLanguage($contentRecordsPerColumn, $lP))
                ) {
                    if ($this->option_newWizard) {
                        $urlParameters = [
                            'id' => $id,
                            'sys_language_uid' => $lP,
                            'colPos' => $columnId,
                            'uid_pid' => $id,
                            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                        ];
                        $routeName = BackendUtility::getPagesTSconfig($id)['mod.']['newContentElementWizard.']['override']
                            ?? 'new_content_element_wizard';
                        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                        $url = (string)$uriBuilder->buildUriFromRoute($routeName, $urlParameters);
                    } else {
                        $urlParameters = [
                            'edit' => [
                                'tt_content' => [
                                    $id => 'new'
                                ]
                            ],
                            'defVals' => [
                                'tt_content' => [
                                    'colPos' => $columnId,
                                    'sys_language_uid' => $lP
                                ]
                            ],
                            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                        ];
                        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                        $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                    }
                    $title = htmlspecialchars($this->getLanguageService()->getLL('newContentElement'));
                    $link = '<a href="#" data-url="' . htmlspecialchars($url) . '" '
                        . 'title="' . $title . '"'
                        . 'data-title="' . $title . '"'
                        . 'class="btn btn-default btn-sm t3js-toggle-new-content-element-wizard">'
                        . $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL)->render()
                        . ' '
                        . htmlspecialchars($this->getLanguageService()->getLL('content')) . '</a>';
                }
                if ($this->getBackendUser()->checkLanguageAccess($lP) && $columnId !== 'unused') {
                    $content[$columnId] .= '
                    <div class="t3-page-ce t3js-page-ce" data-page="' . (int)$id . '" id="' . StringUtility::getUniqueId() . '">
                        <div class="t3js-page-new-ce t3-page-ce-wrapper-new-ce" id="colpos-' . $columnId . '-' . 'page-' . $id . '-' . StringUtility::getUniqueId() . '">'
                            . $link
                            . '</div>
                        <div class="t3-page-ce-dropzone-available t3js-page-ce-dropzone-available"></div>
                    </div>
                    ';
                }
                $editUidList = '';
                if (!isset($contentRecordsPerColumn[$columnId]) || !is_array($contentRecordsPerColumn[$columnId])) {
                    $message = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:error.invalidBackendLayout'),
                        '',
                        FlashMessage::WARNING
                    );
                    $service = GeneralUtility::makeInstance(FlashMessageService::class);
                    $queue = $service->getMessageQueueByIdentifier();
                    $queue->addMessage($message);
                } else {
                    $rowArr = $contentRecordsPerColumn[$columnId];
                    $this->generateTtContentDataArray($rowArr);

                    foreach ((array)$rowArr as $rKey => $row) {
                        $this->contentElementCache[$lP][$columnId][$row['uid']] = $row;
                        if ($this->tt_contentConfig['languageMode']) {
                            $languageColumn[$columnId][$lP] = $head[$columnId] . $content[$columnId];
                        }
                        if (is_array($row) && !VersionState::cast($row['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
                            $singleElementHTML = '<div class="t3-page-ce-dragitem" id="' . StringUtility::getUniqueId() . '">';
                            if (!$lP && ($this->defLangBinding || $row['sys_language_uid'] != -1)) {
                                $defaultLanguageElementsByColumn[$columnId][] = ($row['_ORIG_uid'] ?? $row['uid']);
                            }
                            $editUidList .= $row['uid'] . ',';
                            $disableMoveAndNewButtons = $this->defLangBinding && $lP > 0 && $this->checkIfTranslationsExistInLanguage($contentRecordsPerColumn, $lP);
                            $singleElementHTML .= $this->tt_content_drawHeader(
                                $row,
                                $this->tt_contentConfig['showInfo'] ? 15 : 5,
                                $disableMoveAndNewButtons,
                                true,
                                $this->getBackendUser()->doesUserHaveAccess($this->pageinfo, Permission::CONTENT_EDIT)
                            );
                            $innerContent = '<div ' . ($row['_ORIG_uid'] ? ' class="ver-element"' : '') . '>'
                                . $this->tt_content_drawItem($row) . '</div>';
                            $singleElementHTML .= '<div class="t3-page-ce-body-inner">' . $innerContent . '</div></div>'
                                . $this->tt_content_drawFooter($row);
                            $isDisabled = $this->isDisabled('tt_content', $row);
                            $statusHidden = $isDisabled ? ' t3-page-ce-hidden t3js-hidden-record' : '';
                            $displayNone = !$this->tt_contentConfig['showHidden'] && $isDisabled ? ' style="display: none;"' : '';
                            $highlightHeader = '';
                            if ($this->checkIfTranslationsExistInLanguage([], (int)$row['sys_language_uid']) && (int)$row['l18n_parent'] === 0) {
                                $highlightHeader = ' t3-page-ce-danger';
                            } elseif ($columnId === 'unused') {
                                $highlightHeader = ' t3-page-ce-warning';
                            }
                            $singleElementHTML = '<div class="t3-page-ce' . $highlightHeader . ' t3js-page-ce t3js-page-ce-sortable ' . $statusHidden . '" id="element-tt_content-'
                                . $row['uid'] . '" data-table="tt_content" data-uid="' . $row['uid'] . '"' . $displayNone . '>' . $singleElementHTML . '</div>';

                            $singleElementHTML .= '<div class="t3-page-ce" data-colpos="' . $columnId . '">';
                            $singleElementHTML .= '<div class="t3js-page-new-ce t3-page-ce-wrapper-new-ce" id="colpos-' . $columnId . '-' . 'page-' . $id .
                                '-' . StringUtility::getUniqueId() . '">';
                            // Add icon "new content element below"
                            if (!$disableMoveAndNewButtons
                                && $this->isContentEditable()
                                && $this->getBackendUser()->checkLanguageAccess($lP)
                                && (!$this->checkIfTranslationsExistInLanguage($contentRecordsPerColumn, $lP))
                                && $columnId !== 'unused'
                            ) {
                                // New content element:
                                if ($this->option_newWizard) {
                                    $urlParameters = [
                                        'id' => $row['pid'],
                                        'sys_language_uid' => $row['sys_language_uid'],
                                        'colPos' => $row['colPos'],
                                        'uid_pid' => -$row['uid'],
                                        'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                                    ];
                                    $routeName = BackendUtility::getPagesTSconfig($row['pid'])['mod.']['newContentElementWizard.']['override']
                                        ?? 'new_content_element_wizard';
                                    $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                                    $url = (string)$uriBuilder->buildUriFromRoute($routeName, $urlParameters);
                                } else {
                                    $urlParameters = [
                                        'edit' => [
                                            'tt_content' => [
                                                -$row['uid'] => 'new'
                                            ]
                                        ],
                                        'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                                    ];
                                    $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                                    $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                                }
                                $title = htmlspecialchars($this->getLanguageService()->getLL('newContentElement'));
                                $singleElementHTML .= '<a href="#" data-url="' . htmlspecialchars($url) . '" '
                                    . 'title="' . $title . '"'
                                    . 'data-title="' . $title . '"'
                                    . 'class="btn btn-default btn-sm t3js-toggle-new-content-element-wizard">'
                                    . $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL)->render()
                                    . ' '
                                    . htmlspecialchars($this->getLanguageService()->getLL('content')) . '</a>';
                            }
                            $singleElementHTML .= '</div></div><div class="t3-page-ce-dropzone-available t3js-page-ce-dropzone-available"></div></div>';
                            if ($this->defLangBinding && $this->tt_contentConfig['languageMode']) {
                                $defLangBinding[$columnId][$lP][$row[$lP ? 'l18n_parent' : 'uid'] ?: $row['uid']] = $singleElementHTML;
                            } else {
                                $content[$columnId] .= $singleElementHTML;
                            }
                        } else {
                            unset($rowArr[$rKey]);
                        }
                    }
                    $content[$columnId] .= '</div>';
                    $colTitle = BackendUtility::getProcessedValue('tt_content', 'colPos', $columnId);
                    $tcaItems = GeneralUtility::callUserFunction(\TYPO3\CMS\Backend\View\BackendLayoutView::class . '->getColPosListItemsParsed', $id, $this);
                    foreach ($tcaItems as $item) {
                        if ($item[1] == $columnId) {
                            $colTitle = $this->getLanguageService()->sL($item[0]);
                        }
                    }
                    if ($columnId === 'unused') {
                        if (empty($unusedElementsMessage)) {
                            $unusedElementsMessage = GeneralUtility::makeInstance(
                                FlashMessage::class,
                                $this->getLanguageService()->getLL('staleUnusedElementsWarning'),
                                $this->getLanguageService()->getLL('staleUnusedElementsWarningTitle'),
                                FlashMessage::WARNING
                            );
                            $service = GeneralUtility::makeInstance(FlashMessageService::class);
                            $queue = $service->getMessageQueueByIdentifier();
                            $queue->addMessage($unusedElementsMessage);
                        }
                        $colTitle = $this->getLanguageService()->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos.I.unused');
                        $editParam = '';
                    } else {
                        $editParam = $this->doEdit && !empty($rowArr)
                            ? '&edit[tt_content][' . $editUidList . ']=edit' . $pageTitleParamForAltDoc
                            : '';
                    }
                    $head[$columnId] .= $this->tt_content_drawColHeader($colTitle, $editParam);
                }
            }
            // For each column, fit the rendered content into a table cell:
            $out = '';
            if ($this->tt_contentConfig['languageMode']) {
                // in language mode process the content elements, but only fill $languageColumn. output will be generated later
                $sortedLanguageColumn = [];
                foreach ($cList as $columnId) {
                    if (GeneralUtility::inList($this->tt_contentConfig['activeCols'], $columnId) || $columnId === 'unused') {
                        $languageColumn[$columnId][$lP] = $head[$columnId] . $content[$columnId];

                        // We sort $languageColumn again according to $cList as it may contain data already from above.
                        $sortedLanguageColumn[$columnId] = $languageColumn[$columnId];
                    }
                }
                if (!empty($languageColumn['unused'])) {
                    $sortedLanguageColumn['unused'] = $languageColumn['unused'];
                }
                $languageColumn = $sortedLanguageColumn;
            } else {
                // GRID VIEW:
                $grid = '<div class="t3-grid-container"><table border="0" cellspacing="0" cellpadding="0" width="100%" class="t3-page-columns t3-grid-table t3js-page-columns">';
                // Add colgroups
                $colCount = (int)$backendLayout['__config']['backend_layout.']['colCount'];
                $rowCount = (int)$backendLayout['__config']['backend_layout.']['rowCount'];
                $grid .= '<colgroup>';
                for ($i = 0; $i < $colCount; $i++) {
                    $grid .= '<col />';
                }
                $grid .= '</colgroup>';

                // Check how to handle restricted columns
                $hideRestrictedCols = (bool)(BackendUtility::getPagesTSconfig($id)['mod.']['web_layout.']['hideRestrictedCols'] ?? false);

                // Cycle through rows
                for ($row = 1; $row <= $rowCount; $row++) {
                    $rowConfig = $backendLayout['__config']['backend_layout.']['rows.'][$row . '.'];
                    if (!isset($rowConfig)) {
                        continue;
                    }
                    $grid .= '<tr>';
                    for ($col = 1; $col <= $colCount; $col++) {
                        $columnConfig = $rowConfig['columns.'][$col . '.'];
                        if (!isset($columnConfig)) {
                            continue;
                        }
                        // Which tt_content colPos should be displayed inside this cell
                        $columnKey = (int)$columnConfig['colPos'];
                        // Render the grid cell
                        $colSpan = (int)$columnConfig['colspan'];
                        $rowSpan = (int)$columnConfig['rowspan'];
                        $grid .= '<td valign="top"' .
                            ($colSpan > 0 ? ' colspan="' . $colSpan . '"' : '') .
                            ($rowSpan > 0 ? ' rowspan="' . $rowSpan . '"' : '') .
                            ' data-colpos="' . (int)$columnConfig['colPos'] . '" data-language-uid="' . $lP . '" class="t3js-page-lang-column-' . $lP . ' t3js-page-column t3-grid-cell t3-page-column t3-page-column-' . $columnKey .
                            ((!isset($columnConfig['colPos']) || $columnConfig['colPos'] === '') ? ' t3-grid-cell-unassigned' : '') .
                            ((isset($columnConfig['colPos']) && $columnConfig['colPos'] !== '' && !$head[$columnKey]) || !GeneralUtility::inList($this->tt_contentConfig['activeCols'], $columnConfig['colPos']) ? ($hideRestrictedCols ? ' t3-grid-cell-restricted t3-grid-cell-hidden' : ' t3-grid-cell-restricted') : '') .
                            ($colSpan > 0 ? ' t3-gridCell-width' . $colSpan : '') .
                            ($rowSpan > 0 ? ' t3-gridCell-height' . $rowSpan : '') . '">';

                        // Draw the pre-generated header with edit and new buttons if a colPos is assigned.
                        // If not, a new header without any buttons will be generated.
                        if (isset($columnConfig['colPos']) && $columnConfig['colPos'] !== '' && $head[$columnKey]
                            && GeneralUtility::inList($this->tt_contentConfig['activeCols'], $columnConfig['colPos'])
                        ) {
                            $grid .= $head[$columnKey] . $content[$columnKey];
                        } elseif (isset($columnConfig['colPos']) && $columnConfig['colPos'] !== ''
                            && GeneralUtility::inList($this->tt_contentConfig['activeCols'], $columnConfig['colPos'])
                        ) {
                            if (!$hideRestrictedCols) {
                                $grid .= $this->tt_content_drawColHeader($this->getLanguageService()->getLL('noAccess'));
                            }
                        } elseif (isset($columnConfig['colPos']) && $columnConfig['colPos'] !== ''
                            && !GeneralUtility::inList($this->tt_contentConfig['activeCols'], $columnConfig['colPos'])
                        ) {
                            if (!$hideRestrictedCols) {
                                $grid .= $this->tt_content_drawColHeader($this->getLanguageService()->sL($columnConfig['name']) .
                                  ' (' . $this->getLanguageService()->getLL('noAccess') . ')');
                            }
                        } elseif (isset($columnConfig['name']) && $columnConfig['name'] !== '') {
                            $grid .= $this->tt_content_drawColHeader($this->getLanguageService()->sL($columnConfig['name'])
                                . ' (' . $this->getLanguageService()->getLL('notAssigned') . ')');
                        } else {
                            $grid .= $this->tt_content_drawColHeader($this->getLanguageService()->getLL('notAssigned'));
                        }

                        $grid .= '</td>';
                    }
                    $grid .= '</tr>';
                }
                if (!empty($content['unused'])) {
                    $grid .= '<tr>';
                    // Which tt_content colPos should be displayed inside this cell
                    $columnKey = 'unused';
                    // Render the grid cell
                    $colSpan = (int)$backendLayout['__config']['backend_layout.']['colCount'];
                    $grid .= '<td valign="top"' .
                        ($colSpan > 0 ? ' colspan="' . $colSpan . '"' : '') .
                        ($rowSpan > 0 ? ' rowspan="' . $rowSpan . '"' : '') .
                        ' data-colpos="unused" data-language-uid="' . $lP . '" class="t3js-page-lang-column-' . $lP . ' t3js-page-column t3-grid-cell t3-page-column t3-page-column-' . $columnKey .
                        ($colSpan > 0 ? ' t3-gridCell-width' . $colSpan : '') . '">';

                    // Draw the pre-generated header with edit and new buttons if a colPos is assigned.
                    // If not, a new header without any buttons will be generated.
                    $grid .= $head[$columnKey] . $content[$columnKey];
                    $grid .= '</td></tr>';
                }
                $out .= $grid . '</table></div>';
            }
        }
        $elFromTable = $this->clipboard->elFromTable('tt_content');
        if (!empty($elFromTable) && $this->isPageEditable()) {
            $pasteItem = substr(key($elFromTable), 11);
            $pasteRecord = BackendUtility::getRecord('tt_content', (int)$pasteItem);
            $pasteTitle = $pasteRecord['header'] ? $pasteRecord['header'] : $pasteItem;
            $copyMode = $this->clipboard->clipData['normal']['mode'] ? '-' . $this->clipboard->clipData['normal']['mode'] : '';
            $addExtOnReadyCode = '
                     top.pasteIntoLinkTemplate = '
                . $this->tt_content_drawPasteIcon($pasteItem, $pasteTitle, $copyMode, 't3js-paste-into', 'pasteIntoColumn')
                . ';
                    top.pasteAfterLinkTemplate = '
                . $this->tt_content_drawPasteIcon($pasteItem, $pasteTitle, $copyMode, 't3js-paste-after', 'pasteAfterRecord')
                . ';';
        } else {
            $addExtOnReadyCode = '
                top.pasteIntoLinkTemplate = \'\';
                top.pasteAfterLinkTemplate = \'\';';
        }
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addJsInlineCode('pasteLinkTemplates', $addExtOnReadyCode);
        // If language mode, then make another presentation:
        // Notice that THIS presentation will override the value of $out!
        // But it needs the code above to execute since $languageColumn is filled with content we need!
        if ($this->tt_contentConfig['languageMode']) {
            // Get language selector:
            $languageSelector = $this->languageSelector($id);
            // Reset out - we will make new content here:
            $out = '';
            // Traverse languages found on the page and build up the table displaying them side by side:
            $cCont = [];
            $sCont = [];
            foreach ($langListArr as $lP) {
                $languageMode = '';
                $labelClass = 'info';
                // Header:
                $lP = (int)$lP;
                // Determine language mode
                if ($lP > 0 && isset($this->languageHasTranslationsCache[$lP]['mode'])) {
                    switch ($this->languageHasTranslationsCache[$lP]['mode']) {
                        case 'mixed':
                            $languageMode = $this->getLanguageService()->getLL('languageModeMixed');
                            $labelClass = 'danger';
                            break;
                        case 'connected':
                            $languageMode = $this->getLanguageService()->getLL('languageModeConnected');
                            break;
                        case 'free':
                            $languageMode = $this->getLanguageService()->getLL('languageModeFree');
                            break;
                        default:
                            // we'll let opcode optimize this intentionally empty case
                    }
                }
                $cCont[$lP] = '
					<td valign="top" class="t3-page-column t3-page-column-lang-name" data-language-uid="' . $lP . '">
						<h2>' . htmlspecialchars($this->tt_contentConfig['languageCols'][$lP]) . '</h2>
						' . ($languageMode !== '' ? '<span class="label label-' . $labelClass . '">' . $languageMode . '</span>' : '') . '
					</td>';

                // "View page" icon is added:
                $viewLink = '';
                if (!VersionState::cast($this->getPageLayoutController()->pageinfo['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
                    $onClick = BackendUtility::viewOnClick(
                        $this->id,
                        '',
                        BackendUtility::BEgetRootLine($this->id),
                        '',
                        '',
                        '&L=' . $lP
                    );
                    $viewLink = '<a href="#" class="btn btn-default btn-sm" onclick="' . htmlspecialchars($onClick) . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage')) . '">' . $this->iconFactory->getIcon('actions-view', Icon::SIZE_SMALL)->render() . '</a>';
                }
                // Language overlay page header:
                if ($lP) {
                    $pageLocalizationRecord = BackendUtility::getRecordLocalization('pages', $id, $lP);
                    if (is_array($pageLocalizationRecord)) {
                        $pageLocalizationRecord = reset($pageLocalizationRecord);
                    }
                    BackendUtility::workspaceOL('pages', $pageLocalizationRecord);
                    $recordIcon = BackendUtility::wrapClickMenuOnIcon(
                        $this->iconFactory->getIconForRecord('pages', $pageLocalizationRecord, Icon::SIZE_SMALL)->render(),
                        'pages',
                        $pageLocalizationRecord['uid']
                    );
                    $urlParameters = [
                        'edit' => [
                            'pages' => [
                                $pageLocalizationRecord['uid'] => 'edit'
                            ]
                        ],
                        // Disallow manual adjustment of the language field for pages
                        'overrideVals' => [
                            'pages' => [
                                'sys_language_uid' => $lP
                            ]
                        ],
                        'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                    ];
                    $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                    $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                    $editLink = (
                        $this->getBackendUser()->check('tables_modify', 'pages')
                        ? '<a href="' . htmlspecialchars($url) . '" class="btn btn-default btn-sm"'
                        . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('edit')) . '">'
                        . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render() . '</a>'
                        : ''
                    );

                    $defaultLanguageElements = [];
                    array_walk($defaultLanguageElementsByColumn, function (array $columnContent) use (&$defaultLanguageElements) {
                        $defaultLanguageElements = array_merge($defaultLanguageElements, $columnContent);
                    });

                    $localizationButtons = [];
                    $localizationButtons[] = $this->newLanguageButton(
                        $this->getNonTranslatedTTcontentUids($defaultLanguageElements, $id, $lP),
                        $lP
                    );

                    $lPLabel =
                        '<div class="btn-group">'
                            . $viewLink
                            . $editLink
                            . (!empty($localizationButtons) ? implode(LF, $localizationButtons) : '')
                        . '</div>'
                        . ' ' . $recordIcon . ' ' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($pageLocalizationRecord['title'], 20))
                        ;
                } else {
                    $editLink = '';
                    $recordIcon = '';
                    if ($this->getBackendUser()->checkLanguageAccess(0)) {
                        $recordIcon = BackendUtility::wrapClickMenuOnIcon(
                            $this->iconFactory->getIconForRecord('pages', $this->pageRecord, Icon::SIZE_SMALL)->render(),
                            'pages',
                            $this->id
                        );
                        $urlParameters = [
                            'edit' => [
                                'pages' => [
                                    $this->id => 'edit'
                                ]
                            ],
                            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                        ];
                        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                        $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                        $editLink = (
                            $this->getBackendUser()->check('tables_modify', 'pages')
                            ? '<a href="' . htmlspecialchars($url) . '" class="btn btn-default btn-sm"'
                            . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('edit')) . '">'
                            . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render() . '</a>'
                            : ''
                        );
                    }

                    $lPLabel =
                        '<div class="btn-group">'
                            . $viewLink
                            . $editLink
                        . '</div>'
                        . ' ' . $recordIcon . ' ' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($this->pageRecord['title'], 20));
                }
                $sCont[$lP] = '
					<td class="t3-page-column t3-page-lang-label nowrap">' . $lPLabel . '</td>';
            }
            // Add headers:
            $out .= '<tr>' . implode($cCont) . '</tr>';
            $out .= '<tr>' . implode($sCont) . '</tr>';
            unset($cCont, $sCont);

            // Traverse previously built content for the columns:
            foreach ($languageColumn as $cKey => $cCont) {
                $out .= '<tr>';
                foreach ($cCont as $languageId => $columnContent) {
                    $out .= '<td valign="top" data-colpos="' . $cKey . '" class="t3-grid-cell t3-page-column t3js-page-column t3js-page-lang-column t3js-page-lang-column-' . $languageId . '">' . $columnContent . '</td>';
                }
                $out .= '</tr>';
                if ($this->defLangBinding && !empty($defLangBinding[$cKey])) {
                    $maxItemsCount = max(array_map('count', $defLangBinding[$cKey]));
                    for ($i = 0; $i < $maxItemsCount; $i++) {
                        $defUid = $defaultLanguageElementsByColumn[$cKey][$i] ?? 0;
                        $cCont = [];
                        foreach ($langListArr as $lP) {
                            if ($lP > 0
                                && is_array($defLangBinding[$cKey][$lP])
                                && !$this->checkIfTranslationsExistInLanguage($defaultLanguageElementsByColumn[$cKey], $lP)
                                && count($defLangBinding[$cKey][$lP]) > $i
                            ) {
                                $slice = array_slice($defLangBinding[$cKey][$lP], $i, 1);
                                $element = $slice[0] ?? '';
                            } else {
                                $element = $defLangBinding[$cKey][$lP][$defUid] ?? '';
                            }
                            $cCont[] = $element;
                        }
                        $out .= '
                        <tr>
							<td valign="top" class="t3-grid-cell">' . implode('</td>' . '
							<td valign="top" class="t3-grid-cell">', $cCont) . '</td>
						</tr>';
                    }
                }
            }
            // Finally, wrap it all in a table and add the language selector on top of it:
            $out = $languageSelector . '
                <div class="t3-grid-container">
                    <table cellpadding="0" cellspacing="0" class="t3-page-columns t3-grid-table t3js-page-columns">
						' . $out . '
                    </table>
				</div>';
        }

        return $out;
    }

    /**********************************
     *
     * Generic listing of items
     *
     **********************************/
    /**
     * Creates a standard list of elements from a table.
     *
     * @param string $table Table name
     * @param int $id Page id.
     * @param string $fList Comma list of fields to display
     * @param bool $icon If TRUE, icon is shown
     * @param string $addWhere Additional WHERE-clauses.
     * @return string HTML table
     */
    public function makeOrdinaryList($table, $id, $fList, $icon = false, $addWhere = '')
    {
        // Initialize
        $addWhere = empty($addWhere) ? [] : [QueryHelper::stripLogicalOperatorPrefix($addWhere)];
        $queryBuilder = $this->getQueryBuilder($table, $id, $addWhere);
        $this->setTotalItems($table, $id, $addWhere);
        $dbCount = 0;
        $result = false;
        // Make query for records if there were any records found in the count operation
        if ($this->totalItems) {
            $result = $queryBuilder->execute();
            // Will return FALSE, if $result is invalid
            $dbCount = $queryBuilder->count('uid')->execute()->fetchColumn(0);
        }
        // If records were found, render the list
        if (!$dbCount) {
            return '';
        }
        // Set fields
        $out = '';
        $this->fieldArray = GeneralUtility::trimExplode(',', '__cmds__,' . $fList . ',__editIconLink__', true);
        $theData = [];
        $theData = $this->headerFields($this->fieldArray, $table, $theData);
        // Title row
        $localizedTableTitle = htmlspecialchars($this->getLanguageService()->sL($GLOBALS['TCA'][$table]['ctrl']['title']));
        $out .= '<tr><th class="col-icon"></th>'
            . '<th colspan="' . (count($theData) - 2) . '"><span class="c-table">'
            . $localizedTableTitle . '</span> (' . $dbCount . ')</td>' . '<td class="col-icon"></td>'
            . '</tr>';
        // Column's titles
        if ($this->doEdit) {
            $urlParameters = [
                'edit' => [
                    $table => [
                        $this->id => 'new'
                    ]
                ],
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
            ];
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
            $title = htmlspecialchars($this->getLanguageService()->getLL('new'));
            $theData['__cmds__'] = '<a href="#" data-url="' . htmlspecialchars($url) . '" class="t3js-toggle-new-content-element-wizard" '
                . 'title="' . $title . '"'
                . 'data-title="' . $title . '">'
                . $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL)->render() . '</a>';
        }
        $out .= $this->addElement(1, '', $theData, ' class="c-headLine"', 15, '', 'th');
        // Render Items
        $this->eCounter = $this->firstElementNumber;
        while ($row = $result->fetch()) {
            BackendUtility::workspaceOL($table, $row);
            if (is_array($row)) {
                list($flag, $code) = $this->fwd_rwd_nav();
                $out .= $code;
                if ($flag) {
                    $Nrow = [];
                    // Setting icons links
                    if ($icon) {
                        $Nrow['__cmds__'] = $this->getIcon($table, $row);
                    }
                    // Get values:
                    $Nrow = $this->dataFields($this->fieldArray, $table, $row, $Nrow);
                    // Attach edit icon
                    if ($this->doEdit && $this->getBackendUser()->doesUserHaveAccess($this->pageinfo, Permission::CONTENT_EDIT)) {
                        $urlParameters = [
                            'edit' => [
                                $table => [
                                    $row['uid'] => 'edit'
                                ]
                            ],
                            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                        ];
                        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                        $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                        $Nrow['__editIconLink__'] = '<a class="btn btn-default" href="' . htmlspecialchars($url)
                            . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('edit')) . '">'
                            . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render() . '</a>';
                    } else {
                        $Nrow['__editIconLink__'] = $this->noEditIcon();
                    }
                    $out .= $this->addElement(1, '', $Nrow);
                }
                $this->eCounter++;
            }
        }
        // Wrap it all in a table:
        $out = '
			<!--
				Standard list of table "' . $table . '"
			-->
			<div class="table-fit"><table class="table table-hover table-striped">
				' . $out . '
			</table></div>';
        return $out;
    }

    /**
     * Adds content to all data fields in $out array
     *
     * Each field name in $fieldArr has a special feature which is that the field name can be specified as more field names.
     * Eg. "field1,field2;field3".
     * Field 2 and 3 will be shown in the same cell of the table separated by <br /> while field1 will have its own cell.
     *
     * @param array $fieldArr Array of fields to display
     * @param string $table Table name
     * @param array $row Record array
     * @param array $out Array to which the data is added
     * @return array $out array returned after processing.
     * @see makeOrdinaryList()
     */
    public function dataFields($fieldArr, $table, $row, $out = [])
    {
        // Check table validity
        if (!isset($GLOBALS['TCA'][$table])) {
            return $out;
        }

        $thumbsCol = $GLOBALS['TCA'][$table]['ctrl']['thumbnail'];
        // Traverse fields
        foreach ($fieldArr as $fieldName) {
            if ($GLOBALS['TCA'][$table]['columns'][$fieldName]) {
                // Each field has its own cell (if configured in TCA)
                // If the column is a thumbnail column:
                if ($fieldName == $thumbsCol) {
                    $out[$fieldName] = $this->thumbCode($row, $table, $fieldName);
                } else {
                    // ... otherwise just render the output:
                    $out[$fieldName] = nl2br(htmlspecialchars(trim(GeneralUtility::fixed_lgd_cs(
                        BackendUtility::getProcessedValue($table, $fieldName, $row[$fieldName], 0, 0, 0, $row['uid']),
                        250
                    ))));
                }
            } else {
                // Each field is separated by <br /> and shown in the same cell (If not a TCA field, then explode
                // the field name with ";" and check each value there as a TCA configured field)
                $theFields = explode(';', $fieldName);
                // Traverse fields, separated by ";" (displayed in a single cell).
                foreach ($theFields as $fName2) {
                    if ($GLOBALS['TCA'][$table]['columns'][$fName2]) {
                        $out[$fieldName] .= '<strong>' . htmlspecialchars($this->getLanguageService()->sL(
                            $GLOBALS['TCA'][$table]['columns'][$fName2]['label']
                        )) . '</strong>' . '&nbsp;&nbsp;' . htmlspecialchars(GeneralUtility::fixed_lgd_cs(
                            BackendUtility::getProcessedValue($table, $fName2, $row[$fName2], 0, 0, 0, $row['uid']),
                            25
                        )) . '<br />';
                    }
                }
            }
            // If no value, add a nbsp.
            if (!$out[$fieldName]) {
                $out[$fieldName] = '&nbsp;';
            }
            // Wrap in dimmed-span tags if record is "disabled"
            if ($this->isDisabled($table, $row)) {
                $out[$fieldName] = '<span class="text-muted">' . $out[$fieldName] . '</span>';
            }
        }
        return $out;
    }

    /**
     * Header fields made for the listing of records
     *
     * @param array $fieldArr Field names
     * @param string $table The table name
     * @param array $out Array to which the headers are added.
     * @return array $out returned after addition of the header fields.
     * @see makeOrdinaryList()
     */
    public function headerFields($fieldArr, $table, $out = [])
    {
        foreach ($fieldArr as $fieldName) {
            $ll = htmlspecialchars($this->getLanguageService()->sL($GLOBALS['TCA'][$table]['columns'][$fieldName]['label']));
            $out[$fieldName] = $ll ? $ll : '&nbsp;';
        }
        return $out;
    }

    /**
     * Gets content records per column.
     * This is required for correct workspace overlays.
     *
     * @param string $table UNUSED (will always be queried from tt_content)
     * @param int $id Page Id to be used (not used at all, but part of the API, see $this->pidSelect)
     * @param array $columns colPos values to be considered to be shown
     * @param string $additionalWhereClause Additional where clause for database select
     * @return array Associative array for each column (colPos)
     */
    protected function getContentRecordsPerColumn($table, $id, array $columns, $additionalWhereClause = '')
    {
        $contentRecordsPerColumn = array_fill_keys($columns, []);
        $columns = array_flip($columns);
        $queryBuilder = $this->getQueryBuilder(
            'tt_content',
            $id,
            [
                $additionalWhereClause
            ]
        );

        // Traverse any selected elements and render their display code:
        $results = $this->getResult($queryBuilder->execute());
        $unused = [];
        $hookArray = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['record_is_used'] ?? [];
        foreach ($results as $record) {
            $used = isset($columns[$record['colPos']]);
            foreach ($hookArray as $_funcRef) {
                $_params = ['columns' => $columns, 'record' => $record, 'used' => $used];
                $used = GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
            if ($used) {
                $columnValue = (string)$record['colPos'];
                $contentRecordsPerColumn[$columnValue][] = $record;
            } else {
                $unused[] = $record;
            }
        }
        if (!empty($unused)) {
            $contentRecordsPerColumn['unused'] = $unused;
        }
        return $contentRecordsPerColumn;
    }

    /**********************************
     *
     * Additional functions; Pages
     *
     **********************************/

    /**
     * Adds pages-rows to an array, selecting recursively in the page tree.
     *
     * @param int $pid Starting page id to select from
     * @param string $iconPrefix Prefix for icon code.
     * @param int $depth Depth (decreasing)
     * @param array $rows Array which will accumulate page rows
     * @return array $rows with added rows.
     */
    protected function getPageRecordsRecursive(int $pid, int $depth, string $iconPrefix = '', array $rows = []): array
    {
        $depth--;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

        $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
                $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW)
            );

        if (!empty($GLOBALS['TCA']['pages']['ctrl']['sortby'])) {
            $queryBuilder->orderBy($GLOBALS['TCA']['pages']['ctrl']['sortby']);
        }

        if ($depth >= 0) {
            $result = $queryBuilder->execute();
            $rowCount = $queryBuilder->count('uid')->execute()->fetchColumn(0);
            $count = 0;
            while ($row = $result->fetch()) {
                BackendUtility::workspaceOL('pages', $row);
                if (is_array($row)) {
                    $count++;
                    $row['treeIcons'] = $iconPrefix
                        . '<span class="treeline-icon treeline-icon-join'
                        . ($rowCount === $count ? 'bottom' : '')
                        . '"></span>';
                    $rows[] = $row;
                    // Get the branch
                    $spaceOutIcons = '<span class="treeline-icon treeline-icon-'
                        . ($rowCount === $count ? 'clear' : 'line')
                        . '"></span>';
                    $rows = $this->getPageRecordsRecursive(
                        $row['uid'],
                        $row['php_tree_stop'] ? 0 : $depth,
                        $iconPrefix . $spaceOutIcons,
                        $rows
                    );
                }
            }
        }

        return $rows;
    }

    /**
     * Adds a list item for the pages-rendering
     *
     * @param array $row Record array
     * @param array $fieldArr Field list
     * @return string HTML for the item
     */
    public function pages_drawItem($row, $fieldArr)
    {
        $userTsConfig = $this->getBackendUser()->getTSConfig();

        // Initialization
        $theIcon = $this->getIcon('pages', $row);
        // Preparing and getting the data-array
        $theData = [];
        foreach ($fieldArr as $field) {
            switch ($field) {
                case 'title':
                    $showPageId = !empty($userTsConfig['options.']['pageTree.']['showPageIdWithTitle']);
                    $pTitle = htmlspecialchars(BackendUtility::getProcessedValue('pages', $field, $row[$field], 20));
                    $theData[$field] = $row['treeIcons'] . $theIcon . ($showPageId ? '[' . $row['uid'] . '] ' : '') . $pTitle;
                    break;
                case 'php_tree_stop':
                    // Intended fall through
                case 'TSconfig':
                    $theData[$field] = $row[$field] ? '<strong>x</strong>' : '&nbsp;';
                    break;
                case 'uid':
                    if ($this->getBackendUser()->doesUserHaveAccess($row, 2) && $row['uid'] > 0) {
                        $urlParameters = [
                            'edit' => [
                                'pages' => [
                                    $row['uid'] => 'edit'
                                ]
                            ],
                            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                        ];
                        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                        $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                        $onClick = BackendUtility::viewOnClick($row['uid'], '', BackendUtility::BEgetRootLine($row['uid']));

                        $eI =
                            '<a href="#" onclick="' . htmlspecialchars($onClick) . '" class="btn btn-default" title="' .
                            $this->getLanguageService()->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_webinfo.xlf:lang_renderl10n_viewPage') . '">' .
                            $this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL)->render() .
                            '</a>';
                        $eI .=
                            '<a class="btn btn-default" href="' . htmlspecialchars($url) . '" title="' .
                            htmlspecialchars($this->getLanguageService()->getLL('editThisPage')) . '">' .
                            $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render() .
                            '</a>';
                    } else {
                        $eI = '';
                    }
                    $theData[$field] = '<div class="btn-group" role="group">' . $eI . '</div>';
                    break;
                case 'shortcut':
                case 'shortcut_mode':
                    if ((int)$row['doktype'] === \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SHORTCUT) {
                        $theData[$field] = $this->getPagesTableFieldValue($field, $row);
                    }
                    break;
                default:
                    if (strpos($field, 'table_') === 0) {
                        $f2 = substr($field, 6);
                        if ($GLOBALS['TCA'][$f2]) {
                            $c = $this->numberOfRecords($f2, $row['uid']);
                            $theData[$field] = ($c ? $c : '');
                        }
                    } else {
                        $theData[$field] = $this->getPagesTableFieldValue($field, $row);
                    }
            }
        }
        $this->addElement_tdParams['title'] = $row['_CSSCLASS'] ? ' class="' . $row['_CSSCLASS'] . '"' : '';
        return $this->addElement(1, '', $theData);
    }

    /**
     * Returns the HTML code for rendering a field in the pages table.
     * The row value is processed to a human readable form and the result is parsed through htmlspecialchars().
     *
     * @param string $field The name of the field of which the value should be rendered.
     * @param array $row The pages table row as an associative array.
     * @return string The rendered table field value.
     */
    protected function getPagesTableFieldValue($field, array $row)
    {
        return htmlspecialchars(BackendUtility::getProcessedValue('pages', $field, $row[$field]));
    }

    /**********************************
     *
     * Additional functions; Content Elements
     *
     **********************************/
    /**
     * Draw header for a content element column:
     *
     * @param string $colName Column name
     * @param string $editParams Edit params (Syntax: &edit[...] for FormEngine)
     * @return string HTML table
     */
    public function tt_content_drawColHeader($colName, $editParams = '')
    {
        $iconsArr = [];
        // Create command links:
        if ($this->tt_contentConfig['showCommands']) {
            // Edit whole of column:
            if ($editParams && $this->getBackendUser()->doesUserHaveAccess($this->pageinfo, Permission::CONTENT_EDIT) && $this->getBackendUser()->checkLanguageAccess(0)) {
                $iconsArr['edit'] = '<a href="#" onclick="'
                    . htmlspecialchars(BackendUtility::editOnClick($editParams)) . '" title="'
                    . htmlspecialchars($this->getLanguageService()->getLL('editColumn')) . '">'
                    . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
            }
        }
        $icons = '';
        if (!empty($iconsArr)) {
            $icons = '<div class="t3-page-column-header-icons">' . implode('', $iconsArr) . '</div>';
        }
        // Create header row:
        $out = '<div class="t3-page-column-header">
					' . $icons . '
					<div class="t3-page-column-header-label">' . htmlspecialchars($colName) . '</div>
				</div>';
        return $out;
    }

    /**
     * Draw a paste icon either for pasting into a column or for pasting after a record
     *
     * @param int $pasteItem ID of the item in the clipboard
     * @param string $pasteTitle Title for the JS modal
     * @param string $copyMode copy or cut
     * @param string $cssClass CSS class to determine if pasting is done into column or after record
     * @param string $title title attribute of the generated link
     *
     * @return string Generated HTML code with link and icon
     */
    protected function tt_content_drawPasteIcon($pasteItem, $pasteTitle, $copyMode, $cssClass, $title)
    {
        $pasteIcon = json_encode(
            ' <a data-content="' . htmlspecialchars($pasteItem) . '"'
            . ' data-title="' . htmlspecialchars($pasteTitle) . '"'
            . ' data-severity="warning"'
            . ' class="t3js-paste t3js-paste' . htmlspecialchars($copyMode) . ' ' . htmlspecialchars($cssClass) . ' btn btn-default btn-sm"'
            . ' title="' . htmlspecialchars($this->getLanguageService()->getLL($title)) . '">'
            . $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)->render()
            . '</a>'
        );
        return $pasteIcon;
    }

    /**
     * Draw the footer for a single tt_content element
     *
     * @param array $row Record array
     * @return string HTML of the footer
     * @throws \UnexpectedValueException
     */
    protected function tt_content_drawFooter(array $row)
    {
        $content = '';
        // Get processed values:
        $info = [];
        $this->getProcessedValue('tt_content', 'starttime,endtime,fe_group,space_before_class,space_after_class', $row, $info);

        // Content element annotation
        if (!empty($GLOBALS['TCA']['tt_content']['ctrl']['descriptionColumn']) && !empty($row[$GLOBALS['TCA']['tt_content']['ctrl']['descriptionColumn']])) {
            $info[] = htmlspecialchars($row[$GLOBALS['TCA']['tt_content']['ctrl']['descriptionColumn']]);
        }

        // Call drawFooter hooks
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawFooter'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof PageLayoutViewDrawFooterHookInterface) {
                throw new \UnexpectedValueException($className . ' must implement interface ' . PageLayoutViewDrawFooterHookInterface::class, 1404378171);
            }
            $hookObject->preProcess($this, $info, $row);
        }

        // Display info from records fields:
        if (!empty($info)) {
            $content = '<div class="t3-page-ce-info">
				' . implode('<br>', $info) . '
				</div>';
        }
        // Wrap it
        if (!empty($content)) {
            $content = '<div class="t3-page-ce-footer">' . $content . '</div>';
        }
        return $content;
    }

    /**
     * Draw the header for a single tt_content element
     *
     * @param array $row Record array
     * @param int $space Amount of pixel space above the header. UNUSED
     * @param bool $disableMoveAndNewButtons If set the buttons for creating new elements and moving up and down are not shown.
     * @param bool $langMode If set, we are in language mode and flags will be shown for languages
     * @param bool $dragDropEnabled If set the move button must be hidden
     * @return string HTML table with the record header.
     */
    public function tt_content_drawHeader($row, $space = 0, $disableMoveAndNewButtons = false, $langMode = false, $dragDropEnabled = false)
    {
        $backendUser = $this->getBackendUser();
        $out = '';
        // If show info is set...;
        if ($this->tt_contentConfig['showInfo'] && $backendUser->recordEditAccessInternals('tt_content', $row)) {
            // Render control panel for the element:
            if ($this->tt_contentConfig['showCommands'] && $this->doEdit) {
                // Edit content element:
                $urlParameters = [
                    'edit' => [
                        'tt_content' => [
                            $this->tt_contentData['nextThree'][$row['uid']] => 'edit'
                        ]
                    ],
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI') . '#element-tt_content-' . $row['uid'],
                ];
                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters) . '#element-tt_content-' . $row['uid'];

                $out .= '<a class="btn btn-default" href="' . htmlspecialchars($url)
                    . '" title="' . htmlspecialchars($this->nextThree > 1
                        ? sprintf($this->getLanguageService()->getLL('nextThree'), $this->nextThree)
                        : $this->getLanguageService()->getLL('edit'))
                    . '">' . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render() . '</a>';
                // Hide element:
                $hiddenField = $GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['disabled'];
                if ($hiddenField && $GLOBALS['TCA']['tt_content']['columns'][$hiddenField]
                    && (!$GLOBALS['TCA']['tt_content']['columns'][$hiddenField]['exclude']
                        || $backendUser->check('non_exclude_fields', 'tt_content:' . $hiddenField))
                ) {
                    if ($row[$hiddenField]) {
                        $value = 0;
                        $label = 'unHide';
                    } else {
                        $value = 1;
                        $label = 'hide';
                    }
                    $params = '&data[tt_content][' . ($row['_ORIG_uid'] ? $row['_ORIG_uid'] : $row['uid'])
                        . '][' . $hiddenField . ']=' . $value;
                    $out .= '<a class="btn btn-default" href="' . htmlspecialchars(BackendUtility::getLinkToDataHandlerAction($params))
                        . '#element-tt_content-' . $row['uid'] . '" title="' . htmlspecialchars($this->getLanguageService()->getLL($label)) . '">'
                        . $this->iconFactory->getIcon('actions-edit-' . strtolower($label), Icon::SIZE_SMALL)->render() . '</a>';
                }
                // Delete
                $disableDelete = (bool)\trim(
                    $backendUser->getTSConfig()['options.']['disableDelete.']['tt_content']
                    ?? $backendUser->getTSConfig()['options.']['disableDelete']
                    ?? '0'
                );
                if (!$disableDelete) {
                    $params = '&cmd[tt_content][' . $row['uid'] . '][delete]=1';
                    $refCountMsg = BackendUtility::referenceCount(
                            'tt_content',
                            $row['uid'],
                            ' ' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToRecord'),
                            $this->getReferenceCount('tt_content', $row['uid'])
                        ) . BackendUtility::translationCount(
                            'tt_content',
                            $row['uid'],
                            ' ' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.translationsOfRecord')
                        );
                    $confirm = $this->getLanguageService()->getLL('deleteWarning')
                        . $refCountMsg;
                    $out .= '<a class="btn btn-default t3js-modal-trigger" href="' . htmlspecialchars(BackendUtility::getLinkToDataHandlerAction($params)) . '"'
                        . ' data-severity="warning"'
                        . ' data-title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:label.confirm.delete_record.title')) . '"'
                        . ' data-content="' . htmlspecialchars($confirm) . '" '
                        . ' data-button-close-text="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:cancel')) . '"'
                        . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('deleteItem')) . '">'
                        . $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render() . '</a>';
                    if ($out && $backendUser->doesUserHaveAccess($this->pageinfo, Permission::CONTENT_EDIT)) {
                        $out = '<div class="btn-group btn-group-sm" role="group">' . $out . '</div>';
                    } else {
                        $out = '';
                    }
                }
                if (!$disableMoveAndNewButtons) {
                    $moveButtonContent = '';
                    $displayMoveButtons = false;
                    // Move element up:
                    if ($this->tt_contentData['prev'][$row['uid']]) {
                        $params = '&cmd[tt_content][' . $row['uid'] . '][move]=' . $this->tt_contentData['prev'][$row['uid']];
                        $moveButtonContent .= '<a class="btn btn-default" href="'
                            . htmlspecialchars(BackendUtility::getLinkToDataHandlerAction($params))
                            . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('moveUp')) . '">'
                            . $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render() . '</a>';
                        if (!$dragDropEnabled) {
                            $displayMoveButtons = true;
                        }
                    } else {
                        $moveButtonContent .= '<span class="btn btn-default disabled">' . $this->iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
                    }
                    // Move element down:
                    if ($this->tt_contentData['next'][$row['uid']]) {
                        $params = '&cmd[tt_content][' . $row['uid'] . '][move]= ' . $this->tt_contentData['next'][$row['uid']];
                        $moveButtonContent .= '<a class="btn btn-default" href="'
                            . htmlspecialchars(BackendUtility::getLinkToDataHandlerAction($params))
                            . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('moveDown')) . '">'
                            . $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render() . '</a>';
                        if (!$dragDropEnabled) {
                            $displayMoveButtons = true;
                        }
                    } else {
                        $moveButtonContent .= '<span class="btn btn-default disabled">' . $this->iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
                    }
                    if ($displayMoveButtons) {
                        $out .= '<div class="btn-group btn-group-sm" role="group">' . $moveButtonContent . '</div>';
                    }
                }
            }
        }
        $allowDragAndDrop = $this->isDragAndDropAllowed($row);
        $additionalIcons = [];
        $additionalIcons[] = $this->getIcon('tt_content', $row) . ' ';
        if ($langMode && isset($this->siteLanguages[(int)$row['sys_language_uid']])) {
            $additionalIcons[] = $this->renderLanguageFlag($this->siteLanguages[(int)$row['sys_language_uid']]);
        }
        // Get record locking status:
        if ($lockInfo = BackendUtility::isRecordLocked('tt_content', $row['uid'])) {
            $additionalIcons[] = '<a href="#" data-toggle="tooltip" data-title="' . htmlspecialchars($lockInfo['msg']) . '">'
                . $this->iconFactory->getIcon('warning-in-use', Icon::SIZE_SMALL)->render() . '</a>';
        }
        // Call stats information hook
        $_params = ['tt_content', $row['uid'], &$row];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] ?? [] as $_funcRef) {
            $additionalIcons[] = GeneralUtility::callUserFunction($_funcRef, $_params, $this);
        }

        // Wrap the whole header
        // NOTE: end-tag for <div class="t3-page-ce-body"> is in getTable_tt_content()
        return '<div class="t3-page-ce-header ' . ($allowDragAndDrop ? 't3-page-ce-header-draggable t3js-page-ce-draghandle' : '') . '">
					<div class="t3-page-ce-header-icons-left">' . implode('', $additionalIcons) . '</div>
					<div class="t3-page-ce-header-icons-right">' . ($out ? '<div class="btn-toolbar">' . $out . '</div>' : '') . '</div>
				</div>
				<div class="t3-page-ce-body">';
    }

    /**
     * Gets the number of records referencing the record with the UID $uid in
     * the table $tableName.
     *
     * @param string $tableName
     * @param int $uid
     * @return int The number of references to record $uid in table
     */
    protected function getReferenceCount(string $tableName, int $uid): int
    {
        if (!isset($this->referenceCount[$tableName][$uid])) {
            $referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
            $numberOfReferences = $referenceIndex->getNumberOfReferencedRecords($tableName, $uid);
            $this->referenceCount[$tableName][$uid] = $numberOfReferences;
        }
        return $this->referenceCount[$tableName][$uid];
    }

    /**
     * Determine whether Drag & Drop should be allowed
     *
     * @param array $row
     * @return bool
     */
    protected function isDragAndDropAllowed(array $row)
    {
        if ((int)$row['l18n_parent'] === 0 &&
            (
                $this->getBackendUser()->isAdmin()
                || ((int)$row['editlock'] === 0 && (int)$this->pageinfo['editlock'] === 0)
                && $this->getBackendUser()->doesUserHaveAccess($this->pageinfo, Permission::CONTENT_EDIT)
                && $this->getBackendUser()->checkAuthMode('tt_content', 'CType', $row['CType'], $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'])
            )
        ) {
            return true;
        }
        return false;
    }

    /**
     * Draws the preview content for a content element
     *
     * @param array $row Content element
     * @return string HTML
     * @throws \UnexpectedValueException
     */
    public function tt_content_drawItem($row)
    {
        $out = '';
        $outHeader = '';
        // Make header:

        if ($row['header']) {
            $infoArr = [];
            $this->getProcessedValue('tt_content', 'header_position,header_layout,header_link', $row, $infoArr);
            $hiddenHeaderNote = '';
            // If header layout is set to 'hidden', display an accordant note:
            if ($row['header_layout'] == 100) {
                $hiddenHeaderNote = ' <em>[' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.hidden')) . ']</em>';
            }
            $outHeader = $row['date']
                ? htmlspecialchars($this->itemLabels['date'] . ' ' . BackendUtility::date($row['date'])) . '<br />'
                : '';
            $outHeader .= '<strong>' . $this->linkEditContent($this->renderText($row['header']), $row)
                . $hiddenHeaderNote . '</strong><br />';
        }
        // Make content:
        $infoArr = [];
        $drawItem = true;
        // Hook: Render an own preview of a record
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof PageLayoutViewDrawItemHookInterface) {
                throw new \UnexpectedValueException($className . ' must implement interface ' . PageLayoutViewDrawItemHookInterface::class, 1218547409);
            }
            $hookObject->preProcess($this, $drawItem, $outHeader, $out, $row);
        }

        // If the previous hook did not render something,
        // then check if a Fluid-based preview template was defined for this CType
        // and render it via Fluid. Possible option:
        // mod.web_layout.tt_content.preview.media = EXT:site_mysite/Resources/Private/Templates/Preview/Media.html
        if ($drawItem) {
            $tsConfig = BackendUtility::getPagesTSconfig($row['pid'])['mod.']['web_layout.']['tt_content.']['preview.'] ?? [];
            $fluidTemplateFile = '';

            if ($row['CType'] === 'list' && !empty($row['list_type'])
                && !empty($tsConfig['list.'][$row['list_type']])
            ) {
                $fluidTemplateFile = $tsConfig['list.'][$row['list_type']];
            } elseif (!empty($tsConfig[$row['CType']])) {
                $fluidTemplateFile = $tsConfig[$row['CType']];
            }

            if ($fluidTemplateFile) {
                $fluidTemplateFile = GeneralUtility::getFileAbsFileName($fluidTemplateFile);
                if ($fluidTemplateFile) {
                    try {
                        $view = GeneralUtility::makeInstance(StandaloneView::class);
                        $view->setTemplatePathAndFilename($fluidTemplateFile);
                        $view->assignMultiple($row);
                        if (!empty($row['pi_flexform'])) {
                            $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
                            $view->assign('pi_flexform_transformed', $flexFormService->convertFlexFormContentToArray($row['pi_flexform']));
                        }
                        $out = $view->render();
                        $drawItem = false;
                    } catch (\Exception $e) {
                        $this->logger->warning(sprintf(
                            'The backend preview for content element %d can not be rendered using the Fluid template file "%s": %s',
                            $row['uid'],
                            $fluidTemplateFile,
                            $e->getMessage()
                        ));
                    }
                }
            }
        }

        // Draw preview of the item depending on its CType (if not disabled by previous hook):
        if ($drawItem) {
            switch ($row['CType']) {
                case 'header':
                    if ($row['subheader']) {
                        $out .= $this->linkEditContent($this->renderText($row['subheader']), $row) . '<br />';
                    }
                    break;
                case 'bullets':
                case 'table':
                    if ($row['bodytext']) {
                        $out .= $this->linkEditContent($this->renderText($row['bodytext']), $row) . '<br />';
                    }
                    break;
                case 'uploads':
                    if ($row['media']) {
                        $out .= $this->linkEditContent($this->getThumbCodeUnlinked($row, 'tt_content', 'media'), $row) . '<br />';
                    }
                    break;
                case 'menu':
                    $contentType = $this->CType_labels[$row['CType']];
                    $out .= $this->linkEditContent('<strong>' . htmlspecialchars($contentType) . '</strong>', $row) . '<br />';
                    // Add Menu Type
                    $menuTypeLabel = $this->getLanguageService()->sL(
                        BackendUtility::getLabelFromItemListMerged($row['pid'], 'tt_content', 'menu_type', $row['menu_type'])
                    );
                    $menuTypeLabel = $menuTypeLabel ?: 'invalid menu type';
                    $out .= $this->linkEditContent($menuTypeLabel, $row);
                    if ($row['menu_type'] !== '2' && ($row['pages'] || $row['selected_categories'])) {
                        // Show pages if menu type is not "Sitemap"
                        $out .= ':' . $this->linkEditContent($this->generateListForCTypeMenu($row), $row) . '<br />';
                    }
                    break;
                case 'shortcut':
                    if (!empty($row['records'])) {
                        $shortcutContent = [];
                        $recordList = explode(',', $row['records']);
                        foreach ($recordList as $recordIdentifier) {
                            $split = BackendUtility::splitTable_Uid($recordIdentifier);
                            $tableName = empty($split[0]) ? 'tt_content' : $split[0];
                            $shortcutRecord = BackendUtility::getRecord($tableName, $split[1]);
                            if (is_array($shortcutRecord)) {
                                $icon = $this->iconFactory->getIconForRecord($tableName, $shortcutRecord, Icon::SIZE_SMALL)->render();
                                $icon = BackendUtility::wrapClickMenuOnIcon(
                                    $icon,
                                    $tableName,
                                    $shortcutRecord['uid']
                                );
                                $shortcutContent[] = $icon
                                    . htmlspecialchars(BackendUtility::getRecordTitle($tableName, $shortcutRecord));
                            }
                        }
                        $out .= implode('<br />', $shortcutContent) . '<br />';
                    }
                    break;
                case 'list':
                    $hookOut = '';
                    $_params = ['pObj' => &$this, 'row' => $row, 'infoArr' => $infoArr];
                    foreach (
                        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$row['list_type']] ??
                        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['_DEFAULT'] ??
                        [] as $_funcRef
                    ) {
                        $hookOut .= GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                    }
                    if ((string)$hookOut !== '') {
                        $out .= $hookOut;
                    } elseif (!empty($row['list_type'])) {
                        $label = BackendUtility::getLabelFromItemListMerged($row['pid'], 'tt_content', 'list_type', $row['list_type']);
                        if (!empty($label)) {
                            $out .= $this->linkEditContent('<strong>' . htmlspecialchars($this->getLanguageService()->sL($label)) . '</strong>', $row) . '<br />';
                        } else {
                            $message = sprintf($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue'), $row['list_type']);
                            $out .= '<span class="label label-warning">' . htmlspecialchars($message) . '</span>';
                        }
                    } else {
                        $out .= '<strong>' . $this->getLanguageService()->getLL('noPluginSelected') . '</strong>';
                    }
                    $out .= htmlspecialchars($this->getLanguageService()->sL(
                            BackendUtility::getLabelFromItemlist('tt_content', 'pages', $row['pages'])
                        )) . '<br />';
                    break;
                default:
                    $contentType = $this->CType_labels[$row['CType']];
                    if (!isset($contentType)) {
                        $contentType =  BackendUtility::getLabelFromItemListMerged($row['pid'], 'tt_content', 'CType', $row['CType']);
                    }

                    if ($contentType) {
                        $out .= $this->linkEditContent('<strong>' . htmlspecialchars($contentType) . '</strong>', $row) . '<br />';
                        if ($row['bodytext']) {
                            $out .= $this->linkEditContent($this->renderText($row['bodytext']), $row) . '<br />';
                        }
                        if ($row['image']) {
                            $out .= $this->linkEditContent($this->getThumbCodeUnlinked($row, 'tt_content', 'image'), $row) . '<br />';
                        }
                    } else {
                        $message = sprintf(
                            $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue'),
                            $row['CType']
                        );
                        $out .= '<span class="label label-warning">' . htmlspecialchars($message) . '</span>';
                    }
            }
        }
        // Wrap span-tags:
        $out = '
			<span class="exampleContent">' . $out . '</span>';
        // Add header:
        $out = $outHeader . $out;
        // Return values:
        if ($this->isDisabled('tt_content', $row)) {
            return '<span class="text-muted">' . $out . '</span>';
        }
        return $out;
    }

    /**
     * Generates a list of selected pages or categories for the CType menu
     *
     * @param array $row row from pages
     * @return string
     */
    protected function generateListForCTypeMenu(array $row)
    {
        $table = 'pages';
        $field = 'pages';
        // get categories instead of pages
        if (strpos($row['menu_type'], 'categorized_') !== false) {
            $table = 'sys_category';
            $field = 'selected_categories';
        }
        if (trim($row[$field]) === '') {
            return '';
        }
        $content = '';
        $uidList = explode(',', $row[$field]);
        foreach ($uidList as $uid) {
            $uid = (int)$uid;
            $record = BackendUtility::getRecord($table, $uid, 'title');
            $content .= '<br>' . $record['title'] . ' (' . $uid . ')';
        }
        return $content;
    }

    /**
     * Filters out all tt_content uids which are already translated so only non-translated uids is left.
     * Selects across columns, but within in the same PID. Columns are expect to be the same
     * for translations and original but this may be a conceptual error (?)
     *
     * @param array $defaultLanguageUids Numeric array with uids of tt_content elements in the default language
     * @param int $id Page pid
     * @param int $lP Sys language UID
     * @return array Modified $defLanguageCount
     */
    public function getNonTranslatedTTcontentUids($defaultLanguageUids, $id, $lP)
    {
        if ($lP && !empty($defaultLanguageUids)) {
            // Select all translations here:
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('tt_content');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class, null, false));
            $queryBuilder
                ->select('*')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter($lP, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->in(
                        'l18n_parent',
                        $queryBuilder->createNamedParameter($defaultLanguageUids, Connection::PARAM_INT_ARRAY)
                    )
                );

            $result = $queryBuilder->execute();

            // Flip uids:
            $defaultLanguageUids = array_flip($defaultLanguageUids);
            // Traverse any selected elements and unset original UID if any:
            while ($row = $result->fetch()) {
                BackendUtility::workspaceOL('tt_content', $row);
                unset($defaultLanguageUids[$row['l18n_parent']]);
            }
            // Flip again:
            $defaultLanguageUids = array_keys($defaultLanguageUids);
        }
        return $defaultLanguageUids;
    }

    /**
     * Creates button which is used to create copies of records..
     *
     * @param array $defaultLanguageUids Numeric array with uids of tt_content elements in the default language
     * @param int $lP Sys language UID
     * @return string "Copy languages" button, if available.
     */
    public function newLanguageButton($defaultLanguageUids, $lP)
    {
        $lP = (int)$lP;
        if (!$this->doEdit || !$lP) {
            return '';
        }
        $theNewButton = '';

        $localizationTsConfig = BackendUtility::getPagesTSconfig($this->id)['mod.']['web_layout.']['localization.'] ?? [];
        $allowCopy = (bool)($localizationTsConfig['enableCopy'] ?? true);
        $allowTranslate = (bool)($localizationTsConfig['enableTranslate'] ?? true);
        if (!empty($this->languageHasTranslationsCache[$lP])) {
            if (isset($this->languageHasTranslationsCache[$lP]['hasStandAloneContent'])) {
                $allowTranslate = false;
            }
            if (isset($this->languageHasTranslationsCache[$lP]['hasTranslations'])) {
                $allowCopy = $allowCopy && !$this->languageHasTranslationsCache[$lP]['hasTranslations'];
            }
        }

        if (isset($this->contentElementCache[$lP]) && is_array($this->contentElementCache[$lP])) {
            foreach ($this->contentElementCache[$lP] as $column => $records) {
                foreach ($records as $record) {
                    $key = array_search($record['l10n_source'], $defaultLanguageUids);
                    if ($key !== false) {
                        unset($defaultLanguageUids[$key]);
                    }
                }
            }
        }

        if (!empty($defaultLanguageUids)) {
            $theNewButton =
                '<a'
                    . ' href="#"'
                    . ' class="btn btn-default btn-sm t3js-localize disabled"'
                    . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('newPageContent_translate')) . '"'
                    . ' data-page="' . htmlspecialchars($this->getLocalizedPageTitle()) . '"'
                    . ' data-has-elements="' . (int)!empty($this->contentElementCache[$lP]) . '"'
                    . ' data-allow-copy="' . (int)$allowCopy . '"'
                    . ' data-allow-translate="' . (int)$allowTranslate . '"'
                    . ' data-table="tt_content"'
                    . ' data-page-id="' . (int)GeneralUtility::_GP('id') . '"'
                    . ' data-language-id="' . $lP . '"'
                    . ' data-language-name="' . htmlspecialchars($this->tt_contentConfig['languageCols'][$lP]) . '"'
                . '>'
                . $this->iconFactory->getIcon('actions-localize', Icon::SIZE_SMALL)->render()
                . ' ' . htmlspecialchars($this->getLanguageService()->getLL('newPageContent_translate'))
                . '</a>';
        }

        return $theNewButton;
    }

    /**
     * Creates onclick-attribute content for a new content element
     *
     * @param int $id Page id where to create the element.
     * @param int $colPos Preset: Column position value
     * @param int $sys_language Preset: Sys language value
     * @return string String for onclick attribute.
     * @see getTable_tt_content()
     */
    public function newContentElementOnClick($id, $colPos, $sys_language)
    {
        if ($this->option_newWizard) {
            $routeName = BackendUtility::getPagesTSconfig($id)['mod.']['newContentElementWizard.']['override']
                ?? 'new_content_element_wizard';
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $url = $uriBuilder->buildUriFromRoute($routeName, [
                'id' => $id,
                'colPos' => $colPos,
                'sys_language_uid' => $sys_language,
                'uid_pid' => $id,
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
            ]);
            $onClick = 'window.location.href=' . GeneralUtility::quoteJSvalue((string)$url) . ';';
        } else {
            $onClick = BackendUtility::editOnClick('&edit[tt_content][' . $id . ']=new&defVals[tt_content][colPos]='
                . $colPos . '&defVals[tt_content][sys_language_uid]=' . $sys_language);
        }
        return $onClick;
    }

    /**
     * Will create a link on the input string and possibly a big button after the string which links to editing in the RTE.
     * Used for content element content displayed so the user can click the content / "Edit in Rich Text Editor" button
     *
     * @param string $str String to link. Must be prepared for HTML output.
     * @param array $row The row.
     * @return string If the whole thing was editable ($this->doEdit) $str is return with link around. Otherwise just $str.
     * @see getTable_tt_content()
     */
    public function linkEditContent($str, $row)
    {
        if ($this->doEdit && $this->getBackendUser()->recordEditAccessInternals('tt_content', $row)) {
            $urlParameters = [
                'edit' => [
                    'tt_content' => [
                        $row['uid'] => 'edit'
                    ]
                ],
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI') . '#element-tt_content-' . $row['uid']
            ];
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
            // Return link
            return '<a href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('edit')) . '">' . $str . '</a>';
        }
        return $str;
    }

    /**
     * Make selector box for creating new translation in a language
     * Displays only languages which are not yet present for the current page and
     * that are not disabled with page TS.
     *
     * @param int $id Page id for which to create a new translation record of pages
     * @return string <select> HTML element (if there were items for the box anyways...)
     * @see getTable_tt_content()
     */
    public function languageSelector($id)
    {
        if (!$this->getBackendUser()->check('tables_modify', 'pages')) {
            return '';
        }
        $id = (int)$id;

        // First, select all languages that are available for the current user
        $availableTranslations = [];
        foreach ($this->siteLanguages as $language) {
            if ($language->getLanguageId() === 0) {
                continue;
            }
            $availableTranslations[$language->getLanguageId()] = $language->getTitle();
        }

        // Then, subtract the languages which are already on the page:
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
        $queryBuilder->select('uid', $GLOBALS['TCA']['pages']['ctrl']['languageField'])
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
                )
            );
        $statement = $queryBuilder->execute();
        while ($row = $statement->fetch()) {
            unset($availableTranslations[(int)$row[$GLOBALS['TCA']['pages']['ctrl']['languageField']]]);
        }
        // If any languages are left, make selector:
        if (!empty($availableTranslations)) {
            $output = '<option value="">' . htmlspecialchars($this->getLanguageService()->getLL('new_language')) . '</option>';
            foreach ($availableTranslations as $languageUid => $languageTitle) {
                // Build localize command URL to DataHandler (tce_db)
                // which redirects to FormEngine (record_edit)
                // which, when finished editing should return back to the current page (returnUrl)
                $parameters = [
                    'justLocalized' => 'pages:' . $id . ':' . $languageUid,
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                ];
                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                $redirectUrl = (string)$uriBuilder->buildUriFromRoute('record_edit', $parameters);
                $targetUrl = BackendUtility::getLinkToDataHandlerAction(
                    '&cmd[pages][' . $id . '][localize]=' . $languageUid,
                    $redirectUrl
                );

                $output .= '<option value="' . htmlspecialchars($targetUrl) . '">' . htmlspecialchars($languageTitle) . '</option>';
            }

            return '<div class="form-inline form-inline-spaced">'
                . '<div class="form-group">'
                . '<select class="form-control input-sm" name="createNewLanguage" onchange="window.location.href=this.options[this.selectedIndex].value">'
                . $output
                . '</select></div></div>';
        }
        return '';
    }

    /**
     * Traverse the result pointer given, adding each record to array and setting some internal values at the same time.
     *
     * @param Statement $result DBAL Statement
     * @param string $table Table name defaulting to tt_content
     * @return array The selected rows returned in this array.
     */
    public function getResult(Statement $result, string $table = 'tt_content'): array
    {
        $output = [];
        // Traverse the result:
        while ($row = $result->fetch()) {
            BackendUtility::workspaceOL($table, $row, -99, true);
            if ($row) {
                // Add the row to the array:
                $output[] = $row;
            }
        }
        $this->generateTtContentDataArray($output);
        // Return selected records
        return $output;
    }

    /********************************
     *
     * Various helper functions
     *
     ********************************/

    /**
     * Initializes the clipboard for generating paste links
     *
     *
     * @see \TYPO3\CMS\Backend\Controller\ContextMenuController::clipboardAction()
     * @see \TYPO3\CMS\Filelist\Controller\FileListController::indexAction()
     */
    protected function initializeClipboard()
    {
        // Start clipboard
        $this->clipboard = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Clipboard\Clipboard::class);

        // Initialize - reads the clipboard content from the user session
        $this->clipboard->initializeClipboard();

        // This locks the clipboard to the Normal for this request.
        $this->clipboard->lockToNormal();

        // Clean up pad
        $this->clipboard->cleanCurrent();

        // Save the clipboard content
        $this->clipboard->endClipboard();
    }

    /**
     * Generates the data for previous and next elements which is needed for movements.
     *
     * @param array $rowArray
     */
    protected function generateTtContentDataArray(array $rowArray)
    {
        if (empty($this->tt_contentData)) {
            $this->tt_contentData = [
                'nextThree' => [],
                'next' => [],
                'prev' => [],
            ];
        }
        foreach ($rowArray as $key => $value) {
            // Create the list of the next three ids (for editing links...)
            for ($i = 0; $i < $this->nextThree; $i++) {
                if (isset($rowArray[$key - $i])
                    && !GeneralUtility::inList($this->tt_contentData['nextThree'][$rowArray[$key - $i]['uid']], $value['uid'])
                ) {
                    $this->tt_contentData['nextThree'][$rowArray[$key - $i]['uid']] .= $value['uid'] . ',';
                }
            }

            // Create information for next and previous content elements
            if (isset($rowArray[$key - 1])) {
                if (isset($rowArray[$key - 2])) {
                    $this->tt_contentData['prev'][$value['uid']] = -$rowArray[$key - 2]['uid'];
                } else {
                    $this->tt_contentData['prev'][$value['uid']] = $value['pid'];
                }
                $this->tt_contentData['next'][$rowArray[$key - 1]['uid']] = -$value['uid'];
            }
        }
    }

    /**
     * Counts and returns the number of records on the page with $pid
     *
     * @param string $table Table name
     * @param int $pid Page id
     * @return int Number of records.
     */
    public function numberOfRecords($table, $pid)
    {
        $count = 0;
        if ($GLOBALS['TCA'][$table]) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
            $count = (int)$queryBuilder->count('uid')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT))
                )
                ->execute()
                ->fetchColumn();
        }

        return $count;
    }

    /**
     * Processing of larger amounts of text (usually from RTE/bodytext fields) with word wrapping etc.
     *
     * @param string $input Input string
     * @return string Output string
     */
    public function renderText($input)
    {
        $input = strip_tags($input);
        $input = GeneralUtility::fixed_lgd_cs($input, 1500);
        return nl2br(htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8', false));
    }

    /**
     * Creates the icon image tag for record from table and wraps it in a link which will trigger the click menu.
     *
     * @param string $table Table name
     * @param array $row Record array
     * @param string $enabledClickMenuItems Passthrough to wrapClickMenuOnIcon
     * @return string HTML for the icon
     */
    public function getIcon($table, $row, $enabledClickMenuItems = '')
    {
        // Initialization
        $toolTip = BackendUtility::getRecordToolTip($row, 'tt_content');
        $icon = '<span ' . $toolTip . '>' . $this->iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render() . '</span>';
        $this->counter++;
        // The icon with link
        if ($this->getBackendUser()->recordEditAccessInternals($table, $row)) {
            $icon = BackendUtility::wrapClickMenuOnIcon($icon, $table, $row['uid']);
        }
        return $icon;
    }

    /**
     * Creates processed values for all field names in $fieldList based on values from $row array.
     * The result is 'returned' through $info which is passed as a reference
     *
     * @param string $table Table name
     * @param string $fieldList Comma separated list of fields.
     * @param array $row Record from which to take values for processing.
     * @param array $info Array to which the processed values are added.
     */
    public function getProcessedValue($table, $fieldList, array $row, array &$info)
    {
        // Splitting values from $fieldList
        $fieldArr = explode(',', $fieldList);
        // Traverse fields from $fieldList
        foreach ($fieldArr as $field) {
            if ($row[$field]) {
                $info[] = '<strong>' . htmlspecialchars($this->itemLabels[$field]) . '</strong> '
                    . htmlspecialchars(BackendUtility::getProcessedValue($table, $field, $row[$field]));
            }
        }
    }

    /**
     * Returns TRUE, if the record given as parameters is NOT visible based on hidden/starttime/endtime (if available)
     *
     * @param string $table Tablename of table to test
     * @param array $row Record row.
     * @return bool Returns TRUE, if disabled.
     */
    public function isDisabled($table, $row)
    {
        $enableCols = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns'];
        return $enableCols['disabled'] && $row[$enableCols['disabled']]
            || $enableCols['starttime'] && $row[$enableCols['starttime']] > $GLOBALS['EXEC_TIME']
            || $enableCols['endtime'] && $row[$enableCols['endtime']] && $row[$enableCols['endtime']] < $GLOBALS['EXEC_TIME'];
    }

    /**
     * Returns icon for "no-edit" of a record.
     * Basically, the point is to signal that this record could have had an edit link if
     * the circumstances were right. A placeholder for the regular edit icon...
     *
     * @param string $label Label key from LOCAL_LANG
     * @return string IMG tag for icon.
     */
    public function noEditIcon($label = 'noEditItems')
    {
        $title = htmlspecialchars($this->getLanguageService()->getLL($label));
        return '<span title="' . $title . '">' . $this->iconFactory->getIcon('status-edit-read-only', Icon::SIZE_SMALL)->render() . '</span>';
    }

    /*****************************************
     *
     * External renderings
     *
     *****************************************/

    /**
     * Creates a menu of the tables that can be listed by this function
     * Only tables which has records on the page will be included.
     * Notice: The function also fills in the internal variable $this->activeTables with icon/titles.
     *
     * @param int $id Page id from which we are listing records (the function will look up if there are records on the page)
     * @return string HTML output.
     */
    public function getTableMenu($id)
    {
        // Initialize:
        $this->activeTables = [];
        $theTables = ['tt_content'];
        // External tables:
        if (is_array($this->externalTables)) {
            $theTables = array_unique(array_merge($theTables, array_keys($this->externalTables)));
        }
        $out = '';
        // Traverse tables to check:
        foreach ($theTables as $tName) {
            // Check access and whether the proper extensions are loaded:
            if ($this->getBackendUser()->check('tables_select', $tName)
                && (
                    isset($this->externalTables[$tName])
                    || $tName === 'fe_users' || $tName === 'tt_content'
                    || \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($tName)
                )
            ) {
                // Make query to count records from page:
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($tName);
                $queryBuilder->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                    ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
                $count = $queryBuilder->count('uid')
                    ->from($tName)
                    ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)))
                    ->execute()
                    ->fetchColumn();
                // If records were found (or if "tt_content" is the table...):
                if ($count || $tName === 'tt_content') {
                    // Add row to menu:
                    $out .= '
					<td><a href="#' . $tName . '" title="' . htmlspecialchars($this->getLanguageService()->sL($GLOBALS['TCA'][$tName]['ctrl']['title'])) . '"></a>'
                        . $this->iconFactory->getIconForRecord($tName, [], Icon::SIZE_SMALL)->render()
                        . '</td>';
                    // ... and to the internal array, activeTables we also add table icon and title (for use elsewhere)
                    $title = htmlspecialchars($this->getLanguageService()->sL($GLOBALS['TCA'][$tName]['ctrl']['title']))
                        . ': ' . $count . ' ' . htmlspecialchars($this->getLanguageService()->getLL('records'));
                    $this->activeTables[$tName] = '<span title="' . $title . '">'
                        . $this->iconFactory->getIconForRecord($tName, [], Icon::SIZE_SMALL)->render()
                        . '</span>'
                        . '&nbsp;' . htmlspecialchars($this->getLanguageService()->sL($GLOBALS['TCA'][$tName]['ctrl']['title']));
                }
            }
        }
        // Wrap cells in table tags:
        $out = '
            <!--
                Menu of tables on the page (table menu)
            -->
            <table border="0" cellpadding="0" cellspacing="0" id="typo3-page-tblMenu">
				<tr>' . $out . '
                </tr>
			</table>';
        // Return the content:
        return $out;
    }

    /**
     * Create thumbnail code for record/field but not linked
     *
     * @param mixed[] $row Record array
     * @param string $table Table (record is from)
     * @param string $field Field name for which thumbnail are to be rendered.
     * @return string HTML for thumbnails, if any.
     */
    public function getThumbCodeUnlinked($row, $table, $field)
    {
        return BackendUtility::thumbCode($row, $table, $field, '', '', null, 0, '', '', false);
    }

    /**
     * Checks whether translated Content Elements exist in the desired language
     * If so, deny creating new ones via the UI
     *
     * @param array $contentElements
     * @param int $language
     * @return bool
     */
    protected function checkIfTranslationsExistInLanguage(array $contentElements, int $language)
    {
        // If in default language, you may always create new entries
        // Also, you may override this strict behavior via user TS Config
        // If you do so, you're on your own and cannot rely on any support by the TYPO3 core
        // We jump out here since we don't need to do the expensive loop operations
        $allowInconsistentLanguageHandling = (bool)(BackendUtility::getPagesTSconfig($this->id)['mod.']['web_layout.']['allowInconsistentLanguageHandling'] ?? false);
        if ($language === 0 || $allowInconsistentLanguageHandling) {
            return false;
        }
        /**
         * Build up caches
         */
        if (!isset($this->languageHasTranslationsCache[$language])) {
            foreach ($contentElements as $columns) {
                foreach ($columns as $contentElement) {
                    if ((int)$contentElement['l18n_parent'] === 0) {
                        $this->languageHasTranslationsCache[$language]['hasStandAloneContent'] = true;
                        $this->languageHasTranslationsCache[$language]['mode'] = 'free';
                    }
                    if ((int)$contentElement['l18n_parent'] > 0) {
                        $this->languageHasTranslationsCache[$language]['hasTranslations'] = true;
                        $this->languageHasTranslationsCache[$language]['mode'] = 'connected';
                    }
                }
            }
            if (!isset($this->languageHasTranslationsCache[$language])) {
                $this->languageHasTranslationsCache[$language]['hasTranslations'] = false;
            }
            // Check whether we have a mix of both
            if (isset($this->languageHasTranslationsCache[$language]['hasStandAloneContent'])
                && $this->languageHasTranslationsCache[$language]['hasTranslations']
            ) {
                $this->languageHasTranslationsCache[$language]['mode'] = 'mixed';
                $siteLanguage = $this->siteLanguages[$language];
                $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    sprintf($this->getLanguageService()->getLL('staleTranslationWarning'), $siteLanguage->getTitle()),
                    sprintf($this->getLanguageService()->getLL('staleTranslationWarningTitle'), $siteLanguage->getTitle()),
                    FlashMessage::WARNING
                );
                $service = GeneralUtility::makeInstance(FlashMessageService::class);
                $queue = $service->getMessageQueueByIdentifier();
                $queue->addMessage($message);
            }
        }

        return $this->languageHasTranslationsCache[$language]['hasTranslations'];
    }

    /**
     * @return BackendLayoutView
     */
    protected function getBackendLayoutView()
    {
        return GeneralUtility::makeInstance(BackendLayoutView::class);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return PageLayoutController
     */
    protected function getPageLayoutController()
    {
        return $GLOBALS['SOBE'];
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
     * @throws SiteNotFoundException
     */
    public function start($id, $table, $pointer, $search = '', $levels = 0, $showLimit = 0)
    {
        $this->resolveSiteLanguages((int)$id);
        $backendUser = $this->getBackendUser();
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
     */
    public function generateList()
    {
        // Set page record in header
        $this->pageRecord = BackendUtility::getRecordWSOL('pages', $this->id);
        $hideTablesArray = GeneralUtility::trimExplode(',', $this->hideTables);

        $backendUser = $this->getBackendUser();

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
     * Creates the search box
     *
     * @param bool $formFields If TRUE, the search box is wrapped in its own form-tags
     * @return string HTML for the search box
     */
    public function getSearchBox($formFields = true)
    {
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
                                <div class="form-group col-xs-12">
                                    <label for="search_field">' . htmlspecialchars(
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.label.searchString')
            ) . ': </label>
									<input class="form-control" type="search" placeholder="' . htmlspecialchars(
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.enterSearchString')
            ) . '" title="' . htmlspecialchars(
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.title.searchString')
            ) . '" name="search_field" id="search_field" value="' . htmlspecialchars($this->searchString) . '" />
                                </div>
                                <div class="form-group col-xs-12 col-sm-6">
									<label for="search_levels">' . htmlspecialchars(
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.label.search_levels')
            ) . ': </label>
									' . $lMenu . '
                                </div>
                                <div class="form-group col-xs-12 col-sm-6">
									<label for="showLimit">' . htmlspecialchars(
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.label.limit')
            ) . ': </label>
									<input class="form-control" type="number" min="0" max="10000" placeholder="10" title="' . htmlspecialchars(
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.title.limit')
            ) . '" name="showLimit" id="showLimit" value="' . htmlspecialchars(
                ($this->showLimit ? $this->showLimit : '')
            ) . '" />
                                </div>
                                <div class="form-group col-xs-12">
                                    <div class="form-control-wrap">
                                        <button type="submit" class="btn btn-default" name="search" title="' . htmlspecialchars(
                $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.title.search')
            ) . '">
                                            ' . $this->iconFactory->getIcon('actions-search', Icon::SIZE_SMALL)->render(
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
        $backendUser = $this->getBackendUser();
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
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'],
                    0
                )
            );
        }

        $hookName = DatabaseRecordList::class;
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$hookName]['buildQueryParameters'] ?? [] as $className) {
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, the modifyQuery hook should be used instead.
            trigger_error('The hook ($GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][' . $hookName . '][\'buildQueryParameters\']) will be removed in TYPO3 v10.0, the modifyQuery hook should be used instead.', E_USER_DEPRECATED);
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
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][PageLayoutView::class]['modifyQuery'] ?? [] as $className) {
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
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['mod_list']['getSearchFieldList'] ?? [] as $hookFunction) {
            $hookParameters = [
                'tableHasSearchConfiguration' => $fieldListWasSet,
                'tableName' => $tableName,
                'searchFields' => &$fieldArray,
                'searchString' => $this->searchString
            ];
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
            }
        }
        switch ((string)$this->clickTitleMode) {
            case 'edit':
                // If the listed table is 'pages' we have to request the permission settings for each page:
                if ($table === 'pages') {
                    $localCalcPerms = $this->getBackendUser()->calcPerms(
                        BackendUtility::getRecord('pages', $row['uid'])
                    );
                    $permsEdit = $localCalcPerms & Permission::PAGE_EDIT;
                } else {
                    $permsEdit = $this->calcPerms & Permission::CONTENT_EDIT;
                }
                // "Edit" link: ( Only if permissions to edit the page-record of the content of the parent page ($this->id)
                if ($permsEdit) {
                    $params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
                    $code = '<a href="#" onclick="' . htmlspecialchars(
                            BackendUtility::editOnClick($params, '', -1)
                        ) . '" title="' . htmlspecialchars($lang->getLL('edit')) . '">' . $code . '</a>';
                }
                break;
            case 'show':
                // "Show" link (only pages and tt_content elements)
                if ($table === 'pages' || $table === 'tt_content') {
                    $code = '<a href="#" onclick="' . htmlspecialchars(
                            BackendUtility::viewOnClick(
                                ($table === 'tt_content' ? $this->id . '#' . $row['uid'] : $row['uid'])
                            )
                        ) . '" title="' . htmlspecialchars(
                            $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage')
                        ) . '">' . $code . '</a>';
                }
                break;
            case 'info':
                // "Info": (All records)
                $code = '<a href="#" onclick="' . htmlspecialchars(
                        'top.TYPO3.InfoWindow.showItem(\'' . $table . '\', \'' . $row['uid'] . '\'); return false;'
                    ) . '" title="' . htmlspecialchars($lang->getLL('showInfo')) . '">' . $code . '</a>';
                break;
            default:
                // Output the label now:
                if ($table === 'pages') {
                    $code = '<a href="' . htmlspecialchars(
                            $this->listURL($uid, '', 'firstElementNumber')
                        ) . '" onclick="setHighlight(' . $uid . ')">' . $code . '</a>';
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
        $backendUser = $this->getBackendUser();
        // Init fieldlist array:
        $fieldListArr = [];
        // Check table:
        if (is_array($GLOBALS['TCA'][$table]) && isset($GLOBALS['TCA'][$table]['columns']) && is_array(
                $GLOBALS['TCA'][$table]['columns']
            )) {
            if (isset($GLOBALS['TCA'][$table]['columns']) && is_array($GLOBALS['TCA'][$table]['columns'])) {
                // Traverse configured columns and add them to field array, if available for user.
                foreach ($GLOBALS['TCA'][$table]['columns'] as $fN => $fieldValue) {
                    if ($dontCheckUser || (!$fieldValue['exclude'] || $backendUser->check(
                                'non_exclude_fields',
                                $table . ':' . $fN
                            )) && $fieldValue['config']['type'] !== 'passthrough') {
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
                    if (ExtensionManagementUtility::isLoaded('workspaces')
                        && $GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
                        $fieldListArr[] = 't3ver_id';
                        $fieldListArr[] = 't3ver_state';
                        $fieldListArr[] = 't3ver_wsid';
                    }
                }
            } else {
                $this->logger->error('TCA is broken for the table "' . $table . '": no required "columns" entry in TCA.');
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
                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
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
                        1504870805
                    );
                }
            }
            if (isset($configuration['after'])) {
                if (is_string($configuration['after'])) {
                    $configuration['after'] = GeneralUtility::trimExplode(',', $configuration['after'], true);
                } elseif (!is_array($configuration['after'])) {
                    throw new \UnexpectedValueException(
                        'The specified "after" order configuration for table "' . $tableName . '" is invalid.',
                        1504870806
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
        $backendUser = $this->getBackendUser();
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

        if ($searchLevels === 0) {
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
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected function logDeprecation(string $index)
    {
        trigger_error(
            '[index: ' . $index . '] $parameters in "buildQueryParameters"-Hook will be removed in TYPO3 v10.0, use $queryBuilder instead',
            E_USER_DEPRECATED
        );
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
                // Reverse
                $theData = [];
                $titleCol = $this->fieldArray[0];
                $theData[$titleCol] = $this->fwd_rwd_HTML('fwd', $this->eCounter, $table);
                $code = $this->addElement(1, '', $theData, 'class="fwd_rwd_nav"');
            }
            return [1, $code];
        }
        if ($this->eCounter == $this->firstElementNumber + $this->iLimit) {
            // Forward
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
     * @return string
     */
    protected function getThisScript()
    {
        return strpos($this->thisScript, '?') === false ? $this->thisScript . '?' : $this->thisScript . '&';
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
     * Initializes page languages
     */
    public function initializeLanguages()
    {
        // Look up page overlays:
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
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                        $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->gt(
                        $GLOBALS['TCA']['pages']['ctrl']['languageField'],
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                )
            )
            ->execute();

        $this->pageOverlays = [];
        while ($row = $result->fetch()) {
            $this->pageOverlays[$row[$GLOBALS['TCA']['pages']['ctrl']['languageField']]] = $row;
        }
        // @deprecated $this->languageIconTitles can be removed in TYPO3 v10.0.
        foreach ($this->siteLanguages as $language) {
            $this->languageIconTitles[$language->getLanguageId()] = [
                'title' => $language->getTitle(),
                'flagIcon' => $language->getFlagIdentifier()
            ];
        }
    }

    /**
     * Return the icon for the language
     *
     * @param int $sys_language_uid Sys language uid
     * @param bool $addAsAdditionalText If set to true, only the flag is returned
     * @return string Language icon
     * @deprecated since TYPO3 v9.4, will be removed in TYPO3 v10.0. Use Site Languages instead.
     */
    public function languageFlag($sys_language_uid, $addAsAdditionalText = true)
    {
        trigger_error('This method will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
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
     * Renders the language flag and language title, but only if a icon is given, otherwise just the language
     *
     * @param SiteLanguage $language
     * @return string
     */
    protected function renderLanguageFlag(SiteLanguage $language)
    {
        $title = htmlspecialchars($language->getTitle());
        if ($language->getFlagIdentifier()) {
            $icon = $this->iconFactory->getIcon(
                $language->getFlagIdentifier(),
                Icon::SIZE_SMALL
            )->render();
            return '<span title="' . $title . '">' . $icon . '</span>&nbsp;' . $title;
        }
        return $title;
    }

    /**
     * Fetch the site language objects for the given $pageId and store it in $this->siteLanguages
     *
     * @param int $pageId
     * @throws SiteNotFoundException
     */
    protected function resolveSiteLanguages(int $pageId)
    {
        $site = GeneralUtility::makeInstance(SiteMatcher::class)->matchByPageId($pageId);
        $this->siteLanguages = $site->getAvailableLanguages($this->getBackendUser(), false, $pageId);
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
     * @return string $title
     */
    protected function getLocalizedPageTitle(): string
    {
        if ($this->tt_contentConfig['sys_language_uid'] ?? 0 > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
            $localizedPage = $queryBuilder
                ->select('*')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                        $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA']['pages']['ctrl']['languageField'],
                        $queryBuilder->createNamedParameter($this->tt_contentConfig['sys_language_uid'], \PDO::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->execute()
                ->fetch();
            BackendUtility::workspaceOL('pages', $localizedPage);
            return $localizedPage['title'];
        }
        return $this->pageinfo['title'];
    }

    /**
     * Check if page can be edited by current user
     *
     * @return bool
     */
    protected function isPageEditable()
    {
        if ($this->getBackendUser()->isAdmin()) {
            return true;
        }
        return !$this->pageinfo['editlock'] && $this->getBackendUser()->doesUserHaveAccess($this->pageinfo, Permission::PAGE_EDIT);
    }

    /**
     * Check if content can be edited by current user
     *
     * @return bool
     */
    protected function isContentEditable()
    {
        if ($this->getBackendUser()->isAdmin()) {
            return true;
        }
        return !$this->pageinfo['editlock'] && $this->getBackendUser()->doesUserHaveAccess($this->pageinfo, Permission::CONTENT_EDIT);
    }

    /**
     * Returns the language service
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
