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

use TYPO3\CMS\Backend\Controller\Page\LocalizationController;
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Extbase\Service\FlexFormService;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Child class for the Web > Page module
 */
class PageLayoutView extends \TYPO3\CMS\Recordlist\RecordList\AbstractDatabaseRecordList
{
    /**
     * If TRUE, users/groups are shown in the page info box.
     *
     * @var int
     */
    public $pI_showUser = 0;

    /**
     * The number of successive records to edit when showing content elements.
     *
     * @var int
     */
    public $nextThree = 3;

    /**
     * If TRUE, disables the edit-column icon for tt_content elements
     *
     * @var int
     */
    public $pages_noEditColumns = 0;

    /**
     * If TRUE, new-wizards are linked to rather than the regular new-element list.
     *
     * @var int
     */
    public $option_newWizard = 1;

    /**
     * If set to "1", will link a big button to content element wizard.
     *
     * @var int
     */
    public $ext_function = 0;

    /**
     * If TRUE, elements will have edit icons (probably this is whether the user has permission to edit the page content). Set externally.
     *
     * @var int
     */
    public $doEdit = 1;

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
     * @var \TYPO3\CMS\Backend\Clipboard\Clipboard
     */
    protected $clipboard;

    /**
     * @var array
     */
    protected $plusPages = [];

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
     * Construct to initialize class variables.
     */
    public function __construct()
    {
        parent::__construct();
        $this->localizationController = GeneralUtility::makeInstance(LocalizationController::class);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
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
        } else {
            // Branch out based on table name:
            switch ($table) {
                case 'pages':
                    return $this->getTable_pages($id);
                    break;
                case 'tt_content':
                    return $this->getTable_tt_content($id);
                    break;
                default:
                    return '';
            }
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
        // Select clause for pages:
        $delClause = BackendUtility::deleteClause('pages') . ' AND ' . $this->getBackendUser()->getPagePermsClause(1);
        // Select current page:
        if (!$id) {
            // The root has a pseudo record in pageinfo...
            $row = $this->getPageLayoutController()->pageinfo;
        } else {
            $result = $this->getDatabase()->exec_SELECTquery('*', 'pages', 'uid=' . (int)$id . $delClause);
            $row = $this->getDatabase()->sql_fetch_assoc($result);
            BackendUtility::workspaceOL('pages', $row);
        }
        // If there was found a page:
        if (is_array($row)) {
            // Select which fields to show:
            $pKey = $this->getPageLayoutController()->MOD_SETTINGS['pages'];
            switch ($pKey) {
                case 1:
                    $this->fieldArray = ['title', 'uid'] + array_keys($this->cleanTableNames());
                    break;
                case 2:
                    $this->fieldArray = [
                        'title',
                        'uid',
                        'lastUpdated',
                        'newUntil',
                        'no_cache',
                        'cache_timeout',
                        'php_tree_stop',
                        'TSconfig',
                        'is_siteroot',
                        'fe_login_mode'
                    ];
                    break;
                default:
                    $this->fieldArray = [
                        'title',
                        'uid',
                        'alias',
                        'starttime',
                        'endtime',
                        'fe_group',
                        'target',
                        'url',
                        'shortcut',
                        'shortcut_mode'
                    ];
            }
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
                $theRows = [];
                $theRows = $this->pages_getTree($theRows, $row['uid'], $delClause . BackendUtility::versioningPlaceholderClause('pages'), '', $depth);
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
                if ($editIdList && isset($GLOBALS['TCA']['pages']['columns'][$field]) && $field != 'uid' && !$this->pages_noEditColumns) {
                    $iTitle = sprintf(
                        $this->getLanguageService()->getLL('editThisColumn'),
                        rtrim(trim($this->getLanguageService()->sL(BackendUtility::getItemLabel('pages', $field))), ':')
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
                    $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
                    $eI = '<a href="' . htmlspecialchars($url)
                        . '" title="' . htmlspecialchars($iTitle) . '">'
                        . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
                } else {
                    $eI = '';
                }
                switch ($field) {
                    case 'title':
                        $theData[$field] = '&nbsp;<strong>'
                            . $this->getLanguageService()->sL($GLOBALS['TCA']['pages']['columns'][$field]['label'])
                            . '</strong>' . $eI;
                        break;
                    case 'uid':
                        $theData[$field] = '&nbsp;<strong>ID:</strong>';
                        break;
                    default:
                        if (substr($field, 0, 6) == 'table_') {
                            $f2 = substr($field, 6);
                            if ($GLOBALS['TCA'][$f2]) {
                                $theData[$field] = '&nbsp;' . '<span title="' . $this->getLanguageService()->sL($GLOBALS['TCA'][$f2]['ctrl']['title'], true) . '">' . $this->iconFactory->getIconForRecord($f2, [], Icon::SIZE_SMALL)->render() . '</span>';
                            }
                        } else {
                            $theData[$field] = '&nbsp;&nbsp;<strong>'
                                . $this->getLanguageService()->sL($GLOBALS['TCA']['pages']['columns'][$field]['label'], true)
                                . '</strong>' . $eI;
                        }
                }
            }
            // CSH:
            $out = BackendUtility::cshItem($this->descrTable, ('func_' . $pKey), null, '<span class="btn btn-default btn-sm">|</span>') . '
                <div class="table-fit">
					<table class="table table-striped table-hover typo3-page-pages">' .
                        '<thead>' .
                            $this->addElement(1, '', $theData) .
                        '</thead>' .
                        '<tbody>' .
                            $out .
                        '</tbody>' .
                    '</table>
				</div>';
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
        $backendUser = $this->getBackendUser();
        $this->pageinfo = BackendUtility::readPageAccess($this->id, '');
        $this->initializeLanguages();
        $this->initializeClipboard();
        $pageTitleParamForAltDoc = '&recTitle=' . rawurlencode(BackendUtility::getRecordTitle('pages', BackendUtility::getRecordWSOL('pages', $id), true));
        /** @var $pageRenderer PageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/LayoutModule/DragDrop');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        $userCanEditPage = $this->ext_CALC_PERMS & Permission::PAGE_EDIT && !empty($this->id) && ($backendUser->isAdmin() || (int)$this->pageinfo['editlock'] === 0);
        if ($this->tt_contentConfig['languageColsPointer'] > 0) {
            $userCanEditPage = $this->getBackendUser()->check('tables_modify', 'pages_language_overlay');
        }
        if ($userCanEditPage) {
            $languageOverlayId = 0;
            $pageOverlayRecord = BackendUtility::getRecordsByField(
                'pages_language_overlay',
                'pid',
                (int)$this->id,
                'AND sys_language_uid=' . (int)$this->tt_contentConfig['sys_language_uid']
            );
            if (!empty($pageOverlayRecord[0]['uid'])) {
                $languageOverlayId = $pageOverlayRecord[0]['uid'];
            }
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/PageActions', 'function(PageActions) {
                PageActions.setPageId(' . (int)$this->id . ');
                PageActions.setLanguageOverlayId(' . $languageOverlayId . ');
                PageActions.initializePageTitleRenaming();
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
        $defLanguageCount = [];
        $defLangBinding = [];
        // For each languages... :
        // If not languageMode, then we'll only be through this once.
        foreach ($langListArr as $lP) {
            $lP = (int)$lP;

            if (!isset($this->contentElementCache[$lP])) {
                $this->contentElementCache[$lP] = [];
            }

            if (count($langListArr) === 1 || $lP === 0) {
                $showLanguage = ' AND sys_language_uid IN (' . $lP . ',-1)';
            } else {
                $showLanguage = ' AND sys_language_uid=' . $lP;
            }
            $cList = explode(',', $this->tt_contentConfig['cols']);
            $content = [];
            $head = [];

            // Select content records per column
            $contentRecordsPerColumn = $this->getContentRecordsPerColumn('table', $id, array_values($cList), $showLanguage);
            // For each column, render the content into a variable:
            foreach ($cList as $key) {
                if (!isset($this->contentElementCache[$lP][$key])) {
                    $this->contentElementCache[$lP][$key] = [];
                }

                if (!$lP) {
                    $defLanguageCount[$key] = [];
                }
                // Start wrapping div
                $content[$key] .= '<div data-colpos="' . $key . '" data-language-uid="' . $lP . '" class="t3js-sortable t3js-sortable-lang t3js-sortable-lang-' . $lP . ' t3-page-ce-wrapper';
                if (empty($contentRecordsPerColumn[$key])) {
                    $content[$key] .= ' t3-page-ce-empty';
                }
                $content[$key] .= '">';
                // Add new content at the top most position
                $link = '';
                if ($this->getPageLayoutController()->contentIsNotLockedForEditors()
                    && (!$this->checkIfTranslationsExistInLanguage($contentRecordsPerColumn, $lP))
                ) {
                    if ($this->option_newWizard) {
                        $urlParameters = [
                            'id' => $id,
                            'sys_language_uid' => $lP,
                            'colPos' => $key,
                            'uid_pid' => $id,
                            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                        ];
                        $tsConfig = BackendUtility::getModTSconfig($id, 'mod');
                        $moduleName = isset($tsConfig['properties']['newContentElementWizard.']['override'])
                            ? $tsConfig['properties']['newContentElementWizard.']['override']
                            : 'new_content_element';
                        $url = BackendUtility::getModuleUrl($moduleName, $urlParameters);
                    } else {
                        $urlParameters = [
                            'edit' => [
                                'tt_content' => [
                                    $id => 'new'
                                ]
                            ],
                            'defVals' => [
                                'tt_content' => [
                                    'colPos' => $key,
                                    'sys_language_uid' => $lP
                                ]
                            ],
                            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                        ];
                        $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
                    }

                    $link = '<a href="' . htmlspecialchars($url) . '" title="'
                        . $this->getLanguageService()->getLL('newContentElement', true) . '" class="btn btn-default btn-sm">'
                        . $this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL)->render()
                        . ' '
                        . $this->getLanguageService()->getLL('content', true) . '</a>';
                }
                if ($this->getBackendUser()->checkLanguageAccess($lP)) {
                    $content[$key] .= '
                    <div class="t3-page-ce t3js-page-ce" data-page="' . (int)$id . '" id="' . StringUtility::getUniqueId() . '">
                        <div class="t3js-page-new-ce t3-page-ce-wrapper-new-ce" id="colpos-' . $key . '-' . 'page-' . $id . '-' . StringUtility::getUniqueId() . '">'
                            . $link
                            . '</div>
                        <div class="t3-page-ce-dropzone-available t3js-page-ce-dropzone-available"></div>
                    </div>
                    ';
                }
                $editUidList = '';
                if (!isset($contentRecordsPerColumn[$key]) || !is_array($contentRecordsPerColumn[$key])) {
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
                    $rowArr = $contentRecordsPerColumn[$key];
                    $this->generateTtContentDataArray($rowArr);

                    foreach ((array)$rowArr as $rKey => $row) {
                        $this->contentElementCache[$lP][$key][$row['uid']] = $row;
                        if ($this->tt_contentConfig['languageMode']) {
                            $languageColumn[$key][$lP] = $head[$key] . $content[$key];
                            if (!$this->defLangBinding) {
                                $languageColumn[$key][$lP] .= $this->newLanguageButton(
                                    $this->getNonTranslatedTTcontentUids($defLanguageCount[$key], $id, $lP),
                                    $lP,
                                    $key
                                );
                            }
                        }
                        if (is_array($row) && !VersionState::cast($row['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
                            $singleElementHTML = '';
                            if (!$lP && ($this->defLangBinding || $row['sys_language_uid'] != -1)) {
                                $defLanguageCount[$key][] = (isset($row['_ORIG_uid']) ? $row['_ORIG_uid'] : $row['uid']);
                            }
                            $editUidList .= $row['uid'] . ',';
                            $disableMoveAndNewButtons = $this->defLangBinding && $lP > 0;
                            if (!$this->tt_contentConfig['languageMode']) {
                                $singleElementHTML .= '<div class="t3-page-ce-dragitem" id="' . StringUtility::getUniqueId() . '">';
                            }
                            $singleElementHTML .= $this->tt_content_drawHeader(
                                $row,
                                $this->tt_contentConfig['showInfo'] ? 15 : 5,
                                $disableMoveAndNewButtons,
                                true,
                                $this->getBackendUser()->doesUserHaveAccess($this->pageinfo, Permission::CONTENT_EDIT)
                            );
                            $innerContent = '<div ' . ($row['_ORIG_uid'] ? ' class="ver-element"' : '') . '>'
                                . $this->tt_content_drawItem($row) . '</div>';
                            $singleElementHTML .= '<div class="t3-page-ce-body-inner">' . $innerContent . '</div>'
                                . $this->tt_content_drawFooter($row);
                            $isDisabled = $this->isDisabled('tt_content', $row);
                            $statusHidden = $isDisabled ? ' t3-page-ce-hidden t3js-hidden-record' : '';
                            $displayNone = !$this->tt_contentConfig['showHidden'] && $isDisabled ? ' style="display: none;"' : '';
                            $highlightHeader = false;
                            if ($this->checkIfTranslationsExistInLanguage([], (int)$row['sys_language_uid']) && (int)$row['l18n_parent'] === 0) {
                                $highlightHeader = true;
                            }
                            $singleElementHTML = '<div class="t3-page-ce ' . ($highlightHeader ? 't3-page-ce-danger' : '') . ' t3js-page-ce t3js-page-ce-sortable ' . $statusHidden . '" id="element-tt_content-'
                                . $row['uid'] . '" data-table="tt_content" data-uid="' . $row['uid'] . '"' . $displayNone . '>' . $singleElementHTML . '</div>';

                            if ($this->tt_contentConfig['languageMode']) {
                                $singleElementHTML .= '<div class="t3-page-ce t3js-page-ce">';
                            }
                            $singleElementHTML .= '<div class="t3js-page-new-ce t3-page-ce-wrapper-new-ce" id="colpos-' . $key . '-' . 'page-' . $id .
                                '-' . StringUtility::getUniqueId() . '">';
                            // Add icon "new content element below"
                            if (!$disableMoveAndNewButtons
                                && $this->getPageLayoutController()->contentIsNotLockedForEditors()
                                && $this->getBackendUser()->checkLanguageAccess($lP)
                                && (!$this->checkIfTranslationsExistInLanguage($contentRecordsPerColumn, $lP))
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
                                    $tsConfig = BackendUtility::getModTSconfig($row['pid'], 'mod');
                                    $moduleName = isset($tsConfig['properties']['newContentElementWizard.']['override'])
                                        ? $tsConfig['properties']['newContentElementWizard.']['override']
                                        : 'new_content_element';
                                    $url = BackendUtility::getModuleUrl($moduleName, $urlParameters);
                                } else {
                                    $urlParameters = [
                                        'edit' => [
                                            'tt_content' => [
                                                -$row['uid'] => 'new'
                                            ]
                                        ],
                                        'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                                    ];
                                    $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
                                }
                                $singleElementHTML .= '
								<a href="' . htmlspecialchars($url) . '" title="'
                                    . $this->getLanguageService()->getLL('newContentElement', true) . '" class="btn btn-default btn-sm">'
                                    . $this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL)->render()
                                    . ' '
                                    . $this->getLanguageService()->getLL('content', true) . '</a>
							';
                            }
                            $singleElementHTML .= '</div></div><div class="t3-page-ce-dropzone-available t3js-page-ce-dropzone-available"></div></div>';
                            if ($this->defLangBinding && $this->tt_contentConfig['languageMode']) {
                                $defLangBinding[$key][$lP][$row[$lP ? 'l18n_parent' : 'uid']] = $singleElementHTML;
                            } else {
                                $content[$key] .= $singleElementHTML;
                            }
                        } else {
                            unset($rowArr[$rKey]);
                        }
                    }
                    $content[$key] .= '</div>';
                    $colTitle = BackendUtility::getProcessedValue('tt_content', 'colPos', $key);
                    $tcaItems = GeneralUtility::callUserFunction(\TYPO3\CMS\Backend\View\BackendLayoutView::class . '->getColPosListItemsParsed', $id, $this);
                    foreach ($tcaItems as $item) {
                        if ($item[1] == $key) {
                            $colTitle = $this->getLanguageService()->sL($item[0]);
                        }
                    }

                    $pasteP = ['colPos' => $key, 'sys_language_uid' => $lP];
                    $editParam = $this->doEdit && !empty($rowArr)
                        ? '&edit[tt_content][' . $editUidList . ']=edit' . $pageTitleParamForAltDoc
                        : '';
                    $head[$key] .= $this->tt_content_drawColHeader($colTitle, $editParam, '', $pasteP);
                }
            }
            // For each column, fit the rendered content into a table cell:
            $out = '';
            if ($this->tt_contentConfig['languageMode']) {
                // in language mode process the content elements, but only fill $languageColumn. output will be generated later
                $sortedLanguageColumn = [];
                foreach ($cList as $key) {
                    $languageColumn[$key][$lP] = $head[$key] . $content[$key];
                    if (!$this->defLangBinding) {
                        $languageColumn[$key][$lP] .= $this->newLanguageButton(
                            $this->getNonTranslatedTTcontentUids($defLanguageCount[$key], $id, $lP),
                            $lP,
                            $key
                        );
                    }
                    // We sort $languageColumn again according to $cList as it may contain data already from above.
                    $sortedLanguageColumn[$key] = $languageColumn[$key];
                }
                $languageColumn = $sortedLanguageColumn;
            } else {
                $backendLayout = $this->getBackendLayoutView()->getSelectedBackendLayout($this->id);
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
                            ((isset($columnConfig['colPos']) && $columnConfig['colPos'] !== '' && !$head[$columnKey]) || !GeneralUtility::inList($this->tt_contentConfig['activeCols'], $columnConfig['colPos']) ? ' t3-grid-cell-restricted' : '') .
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
                            $grid .= $this->tt_content_drawColHeader($this->getLanguageService()->getLL('noAccess'), '', '');
                        } elseif (isset($columnConfig['colPos']) && $columnConfig['colPos'] !== ''
                            && !GeneralUtility::inList($this->tt_contentConfig['activeCols'], $columnConfig['colPos'])
                        ) {
                            $grid .= $this->tt_content_drawColHeader($this->getLanguageService()->sL($columnConfig['name']) .
                                ' (' . $this->getLanguageService()->getLL('noAccess') . ')', '', '');
                        } elseif (isset($columnConfig['name']) && $columnConfig['name'] !== '') {
                            $grid .= $this->tt_content_drawColHeader($this->getLanguageService()->sL($columnConfig['name'])
                                . ' (' . $this->getLanguageService()->getLL('notAssigned') . ')', '', '');
                        } else {
                            $grid .= $this->tt_content_drawColHeader($this->getLanguageService()->getLL('notAssigned'), '', '');
                        }

                        $grid .= '</td>';
                    }
                    $grid .= '</tr>';
                }
                $out .= $grid . '</table></div>';
            }
            // CSH:
            $out .= BackendUtility::cshItem($this->descrTable, 'columns_multi', null, '<span class="btn btn-default btn-sm">|</span>');
        }
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
                // Header:
                $lP = (int)$lP;
                $cCont[$lP] = '
					<td valign="top" class="t3-page-column" data-language-uid="' . $lP . '">
						<h2>' . htmlspecialchars($this->tt_contentConfig['languageCols'][$lP]) . '</h2>
					</td>';

                // "View page" icon is added:
                $viewLink = '';
                if (!VersionState::cast($this->getPageLayoutController()->pageinfo['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
                    $onClick = BackendUtility::viewOnClick($this->id, '', BackendUtility::BEgetRootLine($this->id), '', '', ('&L=' . $lP));
                    $viewLink = '<a href="#" class="btn btn-default btn-sm" onclick="' . htmlspecialchars($onClick) . '" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage', true) . '">' . $this->iconFactory->getIcon('actions-view', Icon::SIZE_SMALL)->render() . '</a>';
                }
                // Language overlay page header:
                if ($lP) {
                    list($lpRecord) = BackendUtility::getRecordsByField('pages_language_overlay', 'pid', $id, 'AND sys_language_uid=' . $lP);
                    BackendUtility::workspaceOL('pages_language_overlay', $lpRecord);
                    $params = '&edit[pages_language_overlay][' . $lpRecord['uid'] . ']=edit&overrideVals[pages_language_overlay][sys_language_uid]=' . $lP;
                    $recordIcon = BackendUtility::wrapClickMenuOnIcon(
                        $this->iconFactory->getIconForRecord('pages_language_overlay', $lpRecord, Icon::SIZE_SMALL)->render(),
                        'pages_language_overlay',
                        $lpRecord['uid']
                    );
                    $urlParameters = [
                        'edit' => [
                            'pages_language_overlay' => [
                                $lpRecord['uid'] => 'edit'
                            ]
                        ],
                        'overrideVals' => [
                            'pages_language_overlay' => [
                                'sys_language_uid' => $lP
                            ]
                        ],
                        'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                    ];
                    $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
                    $editLink = ($this->getBackendUser()->check('tables_modify', 'pages_language_overlay')
                        ? '<a href="' . htmlspecialchars($url) . '" class="btn btn-default btn-sm"'
                        . ' title="' . $this->getLanguageService()->getLL('edit', true) . '">'
                        . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render() . '</a>'
                        : ''
                    );

                    $lPLabel =
                        '<div class="btn-group">'
                            . $viewLink
                            . $editLink
                        . '</div>'
                        . ' ' . $recordIcon . ' ' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($lpRecord['title'], 20));
                } else {
                    $editLink = '';
                    $recordIcon = '';
                    if ($this->getBackendUser()->checkLanguageAccess(0)) {
                        $recordIcon = BackendUtility::wrapClickMenuOnIcon(
                            $this->iconFactory->getIconForRecord('pages', $this->pageRecord,
                                Icon::SIZE_SMALL)->render(),
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
                        $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
                        $editLink = ($this->getBackendUser()->check('tables_modify', 'pages_language_overlay')
                            ? '<a href="' . htmlspecialchars($url) . '" class="btn btn-default btn-sm"'
                            . ' title="' . $this->getLanguageService()->getLL('edit', true) . '">'
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
					<td nowrap="nowrap" class="t3-page-column t3-page-lang-label">' . $lPLabel . '</td>';
            }
            // Add headers:
            $out .= '<tr>' . implode($cCont) . '</tr>';
            $out .= '<tr>' . implode($sCont) . '</tr>';
            unset($cCont, $sCont);

            // Traverse previously built content for the columns:
            foreach ($languageColumn as $cKey => $cCont) {
                $out .= '<tr>';
                foreach ($cCont as $languageId => $columnContent) {
                    $out .= '<td valign="top" class="t3-grid-cell t3-page-column t3js-page-column t3js-page-lang-column t3js-page-lang-column-' . $languageId . '">' . $columnContent . '</td>';
                }
                $out .= '</tr>';
                if ($this->defLangBinding) {
                    // "defLangBinding" mode
                    foreach ($defLanguageCount[$cKey] as $defUid) {
                        $cCont = [];
                        foreach ($langListArr as $lP) {
                            $cCont[] = $defLangBinding[$cKey][$lP][$defUid] . $this->newLanguageButton(
                                $this->getNonTranslatedTTcontentUids([$defUid], $id, $lP),
                                $lP,
                                $cKey
                            );
                        }
                        $out .= '
                        <tr>
							<td valign="top" class="t3-grid-cell">' . implode(('</td>' . '
							<td valign="top" class="t3-grid-cell">'), $cCont) . '</td>
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
            // CSH:
            $out .= BackendUtility::cshItem($this->descrTable, 'language_list', null, '<span class="btn btn-default btn-sm">|</span>');
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
        $queryParts = $this->makeQueryArray($table, $id, $addWhere);
        $this->setTotalItems($queryParts);
        $dbCount = 0;
        $result = false;
        // Make query for records if there were any records found in the count operation
        if ($this->totalItems) {
            $result = $this->getDatabase()->exec_SELECT_queryArray($queryParts);
            // Will return FALSE, if $result is invalid
            $dbCount = $this->getDatabase()->sql_num_rows($result);
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
        $localizedTableTitle = $this->getLanguageService()->sL($GLOBALS['TCA'][$table]['ctrl']['title'], true);
        $out .= '<tr class="t3-row-header">' . '<th class="col-icon"></th>'
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
            $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
            $theData['__cmds__'] = '<a href="' . htmlspecialchars($url) . '" '
                . 'title="' . $this->getLanguageService()->getLL('new', true) . '">'
                . $this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL)->render() . '</a>';
        }
        $out .= $this->addElement(1, '', $theData, ' class="c-headLine"', 15, '', 'th');
        // Render Items
        $this->eCounter = $this->firstElementNumber;
        while ($row = $this->getDatabase()->sql_fetch_assoc($result)) {
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
                        $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
                        $Nrow['__editIconLink__'] = '<a href="' . htmlspecialchars($url)
                            . '" title="' . $this->getLanguageService()->getLL('edit', true) . '">'
                            . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
                    } else {
                        $Nrow['__editIconLink__'] = $this->noEditIcon();
                    }
                    $out .= $this->addElement(1, '', $Nrow);
                }
                $this->eCounter++;
            }
        }
        $this->getDatabase()->sql_free_result($result);
        // Wrap it all in a table:
        $out = '
			<!--
				Standard list of table "' . $table . '"
			-->
			<div class="table-fit"><table class="table table-striped">
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
                        $out[$fieldName] .= '<strong>' . $this->getLanguageService()->sL(
                            $GLOBALS['TCA'][$table]['columns'][$fName2]['label'],
                            true
                        ) . '</strong>' . '&nbsp;&nbsp;' . htmlspecialchars(GeneralUtility::fixed_lgd_cs(
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
            $ll = $this->getLanguageService()->sL($GLOBALS['TCA'][$table]['columns'][$fieldName]['label'], true);
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
        $columns = array_map('intval', $columns);
        $contentRecordsPerColumn = array_fill_keys($columns, []);

        $queryParts = $this->makeQueryArray('tt_content', $id, 'AND colPos IN (' . implode(',', $columns) . ')' . $additionalWhereClause);
        $result = $this->getDatabase()->exec_SELECT_queryArray($queryParts);
        // Traverse any selected elements and render their display code:
        $rowArr = $this->getResult($result);

        foreach ($rowArr as $record) {
            $columnValue = $record['colPos'];
            $contentRecordsPerColumn[$columnValue][] = $record;
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
     * @param array $theRows Array which will accumulate page rows
     * @param int $pid Pid to select from
     * @param string $qWhere Query-where clause
     * @param string $treeIcons Prefixed icon code.
     * @param int $depth Depth (decreasing)
     * @return array $theRows, but with added rows.
     */
    public function pages_getTree($theRows, $pid, $qWhere, $treeIcons, $depth)
    {
        $depth--;
        if ($depth >= 0) {
            $res = $this->getDatabase()->exec_SELECTquery('*', 'pages', 'pid=' . (int)$pid . $qWhere, '', 'sorting');
            $c = 0;
            $rc = $this->getDatabase()->sql_num_rows($res);
            while ($row = $this->getDatabase()->sql_fetch_assoc($res)) {
                BackendUtility::workspaceOL('pages', $row);
                if (is_array($row)) {
                    $c++;
                    $row['treeIcons'] = $treeIcons . '<span class="treeline-icon treeline-icon-join' . ($rc === $c ? 'bottom' : '') . '"></span>';
                    $theRows[] = $row;
                    // Get the branch
                    $spaceOutIcons = '<span class="treeline-icon treeline-icon-' . ($rc === $c ? 'clear' : 'line') . '"></span>';
                    $theRows = $this->pages_getTree($theRows, $row['uid'], $qWhere, $treeIcons . $spaceOutIcons, $row['php_tree_stop'] ? 0 : $depth);
                }
            }
        } else {
            $count = $this->getDatabase()->exec_SELECTcountRows('uid', 'pages', 'pid=' . (int)$pid . $qWhere);
            if ($count) {
                $this->plusPages[$pid] = $count;
            }
        }
        return $theRows;
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
        // Initialization
        $theIcon = $this->getIcon('pages', $row);
        // Preparing and getting the data-array
        $theData = [];
        foreach ($fieldArr as $field) {
            switch ($field) {
                case 'title':
                    $red = $this->plusPages[$row['uid']] ? '<span class="text-danger"><strong>+</strong></span>' : '';
                    $pTitle = htmlspecialchars(BackendUtility::getProcessedValue('pages', $field, $row[$field], 20));
                    if ($red) {
                        $pTitle = '<a href="'
                            . htmlspecialchars($this->script . ((strpos($this->script, '?') !== false) ? '&' : '?')
                            . 'id=' . $row['uid']) . '">' . $pTitle . '</a>';
                    }
                    $theData[$field] = $row['treeIcons'] . $theIcon . $red . $pTitle . '&nbsp;&nbsp;';
                    break;
                case 'php_tree_stop':
                    // Intended fall through
                case 'TSconfig':
                    $theData[$field] = $row[$field] ? '&nbsp;<strong>x</strong>' : '&nbsp;';
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
                        $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
                        $eI = '<a href="' . htmlspecialchars($url)
                            . '" title="' . $this->getLanguageService()->getLL('editThisPage', true) . '">'
                            . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
                    } else {
                        $eI = '';
                    }
                    $theData[$field] = '<span align="right">' . $row['uid'] . $eI . '</span>';
                    break;
                case 'shortcut':
                case 'shortcut_mode':
                    if ((int)$row['doktype'] === \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SHORTCUT) {
                        $theData[$field] = $this->getPagesTableFieldValue($field, $row);
                    }
                    break;
                default:
                    if (substr($field, 0, 6) == 'table_') {
                        $f2 = substr($field, 6);
                        if ($GLOBALS['TCA'][$f2]) {
                            $c = $this->numberOfRecords($f2, $row['uid']);
                            $theData[$field] = '&nbsp;&nbsp;' . ($c ? $c : '');
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
        return '&nbsp;&nbsp;' . htmlspecialchars(BackendUtility::getProcessedValue('pages', $field, $row[$field]));
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
     * @param string $newParams New element params (Syntax: &edit[...] for FormEngine) OBSOLETE
     * @param array|NULL $pasteParams Paste element params (i.e. array(colPos => 1, sys_language_uid => 2))
     * @return string HTML table
     */
    public function tt_content_drawColHeader($colName, $editParams, $newParams, array $pasteParams = null)
    {
        $iconsArr = [];
        // Create command links:
        if ($this->tt_contentConfig['showCommands']) {
            // Edit whole of column:
            if ($editParams && $this->getBackendUser()->doesUserHaveAccess($this->pageinfo, Permission::CONTENT_EDIT) && $this->getBackendUser()->checkLanguageAccess(0)) {
                $iconsArr['edit'] = '<a href="#" onclick="'
                    . htmlspecialchars(BackendUtility::editOnClick($editParams)) . '" title="'
                    . $this->getLanguageService()->getLL('editColumn', true) . '">'
                    . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
            }
            if ($pasteParams) {
                $elFromTable = $this->clipboard->elFromTable('tt_content');
                if (!empty($elFromTable) && $this->getPageLayoutController()->pageIsNotLockedForEditors()) {
                    $iconsArr['paste'] =
                        '<a href="' . htmlspecialchars($this->clipboard->pasteUrl('tt_content', $this->id, true, $pasteParams)) . '"'
                        . ' class="t3js-modal-trigger"'
                        . ' data-severity="warning"'
                        . ' data-title="' . $this->getLanguageService()->getLL('pasteIntoColumn', true) . '"'
                        . ' data-content="' . htmlspecialchars($this->clipboard->confirmMsgText('pages', $this->pageRecord, 'into', $elFromTable, $colName)) . '"'
                        . ' title="' . $this->getLanguageService()->getLL('pasteIntoColumn', true) . '">'
                        . $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)->render()
                        . '</a>';
                }
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
        $this->getProcessedValue('tt_content', 'starttime,endtime,fe_group,spaceBefore,spaceAfter', $row, $info);

        // Content element annotation
        if (!empty($GLOBALS['TCA']['tt_content']['ctrl']['descriptionColumn'])) {
            $info[] = htmlspecialchars($row[$GLOBALS['TCA']['tt_content']['ctrl']['descriptionColumn']]);
        }

            // Call drawFooter hooks
        $drawFooterHooks = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawFooter'];
        if (is_array($drawFooterHooks)) {
            foreach ($drawFooterHooks as $hookClass) {
                $hookObject = GeneralUtility::getUserObj($hookClass);
                if (!$hookObject instanceof PageLayoutViewDrawFooterHookInterface) {
                    throw new \UnexpectedValueException($hookClass . ' must implement interface ' . PageLayoutViewDrawFooterHookInterface::class, 1404378171);
                }
                $hookObject->preProcess($this, $info, $row);
            }
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
        $out = '';
        // If show info is set...;
        if ($this->tt_contentConfig['showInfo'] && $this->getBackendUser()->recordEditAccessInternals('tt_content', $row)) {
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
                $url = BackendUtility::getModuleUrl('record_edit', $urlParameters) . '#element-tt_content-' . $row['uid'];

                $out .= '<a class="btn btn-default" href="' . htmlspecialchars($url)
                    . '" title="' . htmlspecialchars($this->nextThree > 1
                        ? sprintf($this->getLanguageService()->getLL('nextThree'), $this->nextThree)
                        : $this->getLanguageService()->getLL('edit'))
                    . '">' . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
                // Hide element:
                $hiddenField = $GLOBALS['TCA']['tt_content']['ctrl']['enablecolumns']['disabled'];
                if ($hiddenField && $GLOBALS['TCA']['tt_content']['columns'][$hiddenField]
                    && (!$GLOBALS['TCA']['tt_content']['columns'][$hiddenField]['exclude']
                        || $this->getBackendUser()->check('non_exclude_fields', 'tt_content:' . $hiddenField))
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
                        . '" title="' . $this->getLanguageService()->getLL($label, true) . '">'
                        . $this->iconFactory->getIcon('actions-edit-' . strtolower($label), Icon::SIZE_SMALL)->render() . '</a>';
                }
                // Delete
                $disableDeleteTS = $this->getBackendUser()->getTSConfig('options.disableDelete');
                $disableDelete = (bool) trim(isset($disableDeleteTS['properties']['tt_content']) ? $disableDeleteTS['properties']['tt_content'] : $disableDeleteTS['value']);
                if (!$disableDelete) {
                    $params = '&cmd[tt_content][' . $row['uid'] . '][delete]=1';
                    $confirm = $this->getLanguageService()->getLL('deleteWarning')
                        . BackendUtility::translationCount('tt_content', $row['uid'], (' '
                        . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.translationsOfRecord')));
                    $out .= '<a class="btn btn-default t3js-modal-trigger" href="' . htmlspecialchars(BackendUtility::getLinkToDataHandlerAction($params)) . '"'
                        . ' data-severity="warning"'
                        . ' data-title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/locallang_alt_doc.xlf:label.confirm.delete_record.title')) . '"'
                        . ' data-content="' . htmlspecialchars($confirm) . '" '
                        . ' data-button-close-text="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/locallang_common.xlf:cancel')) . '"'
                        . ' title="' . $this->getLanguageService()->getLL('deleteItem', true) . '">'
                        . $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render() . '</a>';
                    if ($out && $this->getBackendUser()->doesUserHaveAccess($this->pageinfo, Permission::CONTENT_EDIT)) {
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
                            . '" title="' . $this->getLanguageService()->getLL('moveUp', true) . '">'
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
                            . '" title="' . $this->getLanguageService()->getLL('moveDown', true) . '">'
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
        if ($row['sys_language_uid'] > 0 && $this->checkIfTranslationsExistInLanguage([], (int)$row['sys_language_uid'])) {
            $disabledClickMenuItems = 'new,move';
            $allowDragAndDrop = false;
        }
        $additionalIcons[] = $this->getIcon('tt_content', $row, $disabledClickMenuItems) . ' ';
        $additionalIcons[] = $langMode ? $this->languageFlag($row['sys_language_uid'], false) : '';
        // Get record locking status:
        if ($lockInfo = BackendUtility::isRecordLocked('tt_content', $row['uid'])) {
            $additionalIcons[] = '<a href="#" onclick="alert(' . GeneralUtility::quoteJSvalue($lockInfo['msg'])
                . ');return false;" title="' . htmlspecialchars($lockInfo['msg']) . '">'
                . $this->iconFactory->getIcon('status-warning-in-use', Icon::SIZE_SMALL)->render() . '</a>';
        }
        // Call stats information hook
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'])) {
            $_params = ['tt_content', $row['uid'], &$row];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['recStatInfoHooks'] as $_funcRef) {
                $additionalIcons[] = GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
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
     * Determine whether Drag & Drop should be allowed
     *
     * @param array $row
     * @return bool
     */
    protected function isDragAndDropAllowed(array $row)
    {
        if ($this->getBackendUser()->user['admin']
            || ((int)$row['editlock'] === 0 && (int)$this->pageinfo['editlock'] === 0)
            && $this->getBackendUser()->doesUserHaveAccess($this->pageinfo, Permission::CONTENT_EDIT)
            && $this->getBackendUser()->checkAuthMode('tt_content', 'CType', $row['CType'], $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'])
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
                $hiddenHeaderNote = ' <em>[' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.hidden', true) . ']</em>';
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
        $drawItemHooks = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem'];
        if (is_array($drawItemHooks)) {
            foreach ($drawItemHooks as $hookClass) {
                $hookObject = GeneralUtility::getUserObj($hookClass);
                if (!$hookObject instanceof PageLayoutViewDrawItemHookInterface) {
                    throw new \UnexpectedValueException($hookClass . ' must implement interface ' . PageLayoutViewDrawItemHookInterface::class, 1218547409);
                }
                $hookObject->preProcess($this, $drawItem, $outHeader, $out, $row);
            }
        }

        // If the previous hook did not render something,
        // then check if a Fluid-based preview template was defined for this CType
        // and render it via Fluid. Possible option:
        // mod.web_layout.tt_content.preview.media = EXT:site_mysite/Resources/Private/Templates/Preview/Media.html
        if ($drawItem) {
            $tsConfig = BackendUtility::getModTSconfig($row['pid'], 'mod.web_layout.tt_content.preview');
            if (!empty($tsConfig['properties'][$row['CType']])) {
                $fluidTemplateFile = $tsConfig['properties'][$row['CType']];
                $fluidTemplateFile = GeneralUtility::getFileAbsFileName($fluidTemplateFile);
                if ($fluidTemplateFile) {
                    try {
                        /** @var StandaloneView $view */
                        $view = GeneralUtility::makeInstance(StandaloneView::class);
                        $view->setTemplatePathAndFilename($fluidTemplateFile);
                        $view->assignMultiple($row);
                        if (!empty($row['pi_flexform'])) {
                            /** @var FlexFormService $flexFormService */
                            $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
                            $view->assign('pi_flexform_transformed', $flexFormService->convertFlexFormContentToArray($row['pi_flexform']));
                        }
                        $out = $view->render();
                        $drawItem = false;
                    } catch (\Exception $e) {
                        // Catch any exception to avoid breaking the view
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
                                    $shortcutRecord['uid'],
                                    1,
                                    '',
                                    '+copy,info,edit,view'
                                );
                                $shortcutContent[] = $icon
                                    . htmlspecialchars(BackendUtility::getRecordTitle($tableName, $shortcutRecord));
                            }
                        }
                        $out .= implode('<br />', $shortcutContent) . '<br />';
                    }
                    break;
                case 'list':
                    $hookArr = [];
                    $hookOut = '';
                    if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$row['list_type']])) {
                        $hookArr = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$row['list_type']];
                    } elseif (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['_DEFAULT'])) {
                        $hookArr = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['_DEFAULT'];
                    }
                    if (!empty($hookArr)) {
                        $_params = ['pObj' => &$this, 'row' => $row, 'infoArr' => $infoArr];
                        foreach ($hookArr as $_funcRef) {
                            $hookOut .= GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                        }
                    }
                    if ((string)$hookOut !== '') {
                        $out .= $hookOut;
                    } elseif (!empty($row['list_type'])) {
                        $label = BackendUtility::getLabelFromItemListMerged($row['pid'], 'tt_content', 'list_type', $row['list_type']);
                        if (!empty($label)) {
                            $out .=  $this->linkEditContent('<strong>' . $this->getLanguageService()->sL($label, true) . '</strong>', $row) . '<br />';
                        } else {
                            $message = sprintf($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue'), $row['list_type']);
                            $out .= '<span class="label label-warning">' . htmlspecialchars($message) . '</span>';
                        }
                    } elseif (!empty($row['select_key'])) {
                        $out .= $this->getLanguageService()->sL(BackendUtility::getItemLabel('tt_content', 'select_key'), true)
                            . ' ' . htmlspecialchars($row['select_key']) . '<br />';
                    } else {
                        $out .= '<strong>' . $this->getLanguageService()->getLL('noPluginSelected') . '</strong>';
                    }
                    $out .= $this->getLanguageService()->sL(
                        BackendUtility::getLabelFromItemlist('tt_content', 'pages', $row['pages']),
                        true
                    ) . '<br />';
                    break;
                default:
                    $contentType = $this->CType_labels[$row['CType']];

                    if (isset($contentType)) {
                        $out .= $this->linkEditContent('<strong>' . htmlspecialchars($contentType) . '</strong>', $row) . '<br />';
                        if ($row['bodytext']) {
                            $out .= $this->linkEditContent($this->renderText($row['bodytext']), $row) . '<br />';
                        }
                        if ($row['image']) {
                            $out .= $this->linkEditContent($this->getThumbCodeUnlinked($row, 'tt_content', 'image'), $row) . '<br />';
                        }
                    } else {
                        $message = sprintf(
                            $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue'),
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
        } else {
            return $out;
        }
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
     * @param array $defLanguageCount Numeric array with uids of tt_content elements in the default language
     * @param int $id Page pid
     * @param int $lP Sys language UID
     * @return array Modified $defLanguageCount
     */
    public function getNonTranslatedTTcontentUids($defLanguageCount, $id, $lP)
    {
        if ($lP && !empty($defLanguageCount)) {
            // Select all translations here:
            $where = 'sys_language_uid=' . intval($lP) . ' AND l18n_parent IN ('
                . implode(',', $defLanguageCount) . ')'
                . BackendUtility::deleteClause('tt_content');
            $rowArr = $this->getDatabase()->exec_SELECTgetRows('*', 'tt_content', $where);

            // Flip uids:
            $defLanguageCount = array_flip($defLanguageCount);
            // Traverse any selected elements and unset original UID if any:
            foreach ($rowArr as $row) {
                BackendUtility::workspaceOL('tt_content', $row);
                unset($defLanguageCount[$row['l18n_parent']]);
            }
            // Flip again:
            $defLanguageCount = array_keys($defLanguageCount);
        }
        return $defLanguageCount;
    }

    /**
     * Creates button which is used to create copies of records..
     *
     * @param array $defLanguageCount Numeric array with uids of tt_content elements in the default language
     * @param int $lP Sys language UID
     * @param int $colPos Column position
     * @return string "Copy languages" button, if available.
     */
    public function newLanguageButton($defLanguageCount, $lP, $colPos = 0)
    {
        $lP = (int)$lP;
        if (!$this->doEdit || !$lP) {
            return '';
        }
        $theNewButton = '';

        $allowCopy = true;
        $allowTranslate = true;
        if (!empty($this->languageHasTranslationsCache[$lP])) {
            if (isset($this->languageHasTranslationsCache[$lP]['hasStandAloneContent'])) {
                $allowTranslate = false;
            }
            if (isset($this->languageHasTranslationsCache[$lP]['hasTranslations'])) {
                $allowCopy = false;
            }
        }

        if (isset($this->contentElementCache[$lP][$colPos]) && is_array($this->contentElementCache[$lP][$colPos])) {
            foreach ($this->contentElementCache[$lP][$colPos] as $record) {
                $key = array_search($record['t3_origuid'], $defLanguageCount);
                if ($key !== false) {
                    unset($defLanguageCount[$key]);
                }
            }
        }

        if (!empty($defLanguageCount)) {
            $theNewButton =
                '<input'
                    . ' class="btn btn-default t3js-localize"'
                    . ' type="button"'
                    . ' disabled'
                    . ' value="' . htmlspecialchars($this->getLanguageService()->getLL('newPageContent_translate', true)) . '"'
                    . ' data-has-elements="' . (int)!empty($this->contentElementCache[$lP][$colPos]) . '"'
                    . ' data-allow-copy="' . (int)$allowCopy . '"'
                    . ' data-allow-translate="' . (int)$allowTranslate . '"'
                    . ' data-table="tt_content"'
                    . ' data-page-id="' . (int)GeneralUtility::_GP('id') . '"'
                    . ' data-language-id="' . $lP . '"'
                    . ' data-language-name="' . htmlspecialchars($this->tt_contentConfig['languageCols'][$lP]) . '"'
                    . ' data-colpos-id="' . $colPos . '"'
                    . ' data-colpos-name="' . BackendUtility::getProcessedValue('tt_content', 'colPos', $colPos) . '"'
                . '/>';
        }

        return '<div class="t3-page-lang-copyce">' . $theNewButton . '</div>';
    }

    /**
     * Creates onclick-attribute content for a new content element
     *
     * @param int $id Page id where to create the element.
     * @param int $colPos Preset: Column position value
     * @param int $sys_language Preset: Sys langauge value
     * @return string String for onclick attribute.
     * @see getTable_tt_content()
     */
    public function newContentElementOnClick($id, $colPos, $sys_language)
    {
        if ($this->option_newWizard) {
            $tsConfig = BackendUtility::getModTSconfig($id, 'mod');
            $moduleName = isset($tsConfig['properties']['newContentElementWizard.']['override'])
                ? $tsConfig['properties']['newContentElementWizard.']['override']
                : 'new_content_element';
            $onClick = 'window.location.href=' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl($moduleName) . '&id=' . $id . '&colPos=' . $colPos
                . '&sys_language_uid=' . $sys_language . '&uid_pid=' . $id
                . '&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'))) . ';';
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
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
            ];
            $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
            // Return link
            return '<a href="' . htmlspecialchars($url) . '" title="' . $this->getLanguageService()->getLL('edit', true) . '">' . $str . '</a>';
        } else {
            return $str;
        }
    }

    /**
     * Make selector box for creating new translation in a language
     * Displays only languages which are not yet present for the current page and
     * that are not disabled with page TS.
     *
     * @param int $id Page id for which to create a new language (pages_language_overlay record)
     * @return string <select> HTML element (if there were items for the box anyways...)
     * @see getTable_tt_content()
     */
    public function languageSelector($id)
    {
        if ($this->getBackendUser()->check('tables_modify', 'pages_language_overlay')) {
            // First, select all
            $res = $this->getPageLayoutController()->exec_languageQuery(0);
            $langSelItems = [];
            $langSelItems[0] = '
						<option value="0"></option>';
            while ($row = $this->getDatabase()->sql_fetch_assoc($res)) {
                if ($this->getBackendUser()->checkLanguageAccess($row['uid'])) {
                    $langSelItems[$row['uid']] = '
							<option value="' . $row['uid'] . '">' . htmlspecialchars($row['title']) . '</option>';
                }
            }
            // Then, subtract the languages which are already on the page:
            $res = $this->getPageLayoutController()->exec_languageQuery($id);
            while ($row = $this->getDatabase()->sql_fetch_assoc($res)) {
                unset($langSelItems[$row['uid']]);
            }
            // Remove disallowed languages
            if (count($langSelItems) > 1
                && !$this->getBackendUser()->user['admin']
                && $this->getBackendUser()->groupData['allowed_languages'] !== ''
            ) {
                $allowed_languages = array_flip(explode(',', $this->getBackendUser()->groupData['allowed_languages']));
                if (!empty($allowed_languages)) {
                    foreach ($langSelItems as $key => $value) {
                        if (!isset($allowed_languages[$key]) && $key != 0) {
                            unset($langSelItems[$key]);
                        }
                    }
                }
            }
            // Remove disabled languages
            $modSharedTSconfig = BackendUtility::getModTSconfig($id, 'mod.SHARED');
            $disableLanguages = isset($modSharedTSconfig['properties']['disableLanguages'])
                ? GeneralUtility::trimExplode(',', $modSharedTSconfig['properties']['disableLanguages'], true)
                : [];
            if (!empty($langSelItems) && !empty($disableLanguages)) {
                foreach ($disableLanguages as $language) {
                    if ($language != 0 && isset($langSelItems[$language])) {
                        unset($langSelItems[$language]);
                    }
                }
            }
            // If any languages are left, make selector:
            if (count($langSelItems) > 1) {
                $url = BackendUtility::getModuleUrl('record_edit', [
                    'edit[pages_language_overlay][' . $id . ']' => 'new',
                    'overrideVals[pages_language_overlay][doktype]' => (int)$this->pageRecord['doktype'],
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                ]);
                $onChangeContent = 'window.location.href=' . GeneralUtility::quoteJSvalue($url . '&overrideVals[pages_language_overlay][sys_language_uid]=') . '+this.options[this.selectedIndex].value';
                return '<div class="form-inline form-inline-spaced">'
                . '<div class="form-group">'
                . '<label for="createNewLanguage">'
                . $this->getLanguageService()->getLL('new_language', true)
                . '</label>'
                . '<select class="form-control input-sm" name="createNewLanguage" onchange="' . htmlspecialchars($onChangeContent) . '">'
                . implode('', $langSelItems)
                . '</select></div></div>';
            }
        }
        return '';
    }

    /**
     * Traverse the result pointer given, adding each record to array and setting some internal values at the same time.
     *
     * @param bool|\mysqli_result|object $result MySQLi result object / DBAL object
     * @param string $table Table name defaulting to tt_content
     * @return array The selected rows returned in this array.
     */
    public function getResult($result, $table = 'tt_content')
    {
        $output = [];
        // Traverse the result:
        while ($row = $this->getDatabase()->sql_fetch_assoc($result)) {
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
     * @return void
     *
     * @see \TYPO3\CMS\Recordlist\RecordList::main()
     * @see \TYPO3\CMS\Backend\Controller\ClickMenuController::main()
     * @see \TYPO3\CMS\Filelist\Controller\FileListController::main()
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
     * @return void
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
            $where = 'pid=' . (int)$pid . BackendUtility::deleteClause($table) . BackendUtility::versioningPlaceholderClause($table);
            $count = $this->getDatabase()->exec_SELECTcountRows('uid', $table, $where);
        }
        return (int)$count;
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
            $icon = BackendUtility::wrapClickMenuOnIcon($icon, $table, $row['uid'], true, '', $enabledClickMenuItems);
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
     * @return void
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
        $title = $this->getLanguageService()->getLL($label, true);
        return '<span title="' . $title . '">' . $this->iconFactory->getIcon('status-status-edit-read-only', Icon::SIZE_SMALL)->render() . '</span>';
    }

    /**
     * Function, which fills in the internal array, $this->allowedTableNames with all tables to
     * which the user has access. Also a set of standard tables (pages, static_template, sys_filemounts, etc...)
     * are filtered out. So what is left is basically all tables which makes sense to list content from.
     *
     * @return array
     */
    protected function cleanTableNames()
    {
        // Get all table names:
        $tableNames = array_flip(array_keys($GLOBALS['TCA']));
        // Unset common names:
        unset($tableNames['pages']);
        unset($tableNames['static_template']);
        unset($tableNames['sys_filemounts']);
        unset($tableNames['sys_action']);
        unset($tableNames['sys_workflows']);
        unset($tableNames['be_users']);
        unset($tableNames['be_groups']);
        $allowedTableNames = [];
        // Traverse table names and set them in allowedTableNames array IF they can be read-accessed by the user.
        if (is_array($tableNames)) {
            foreach ($tableNames as $k => $v) {
                if (!$GLOBALS['TCA'][$k]['ctrl']['hideTable'] && $this->getBackendUser()->check('tables_select', $k)) {
                    $allowedTableNames['table_' . $k] = $k;
                }
            }
        }
        return $allowedTableNames;
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
                && (isset($this->externalTables[$tName])
                    || $tName === 'fe_users' || $tName === 'tt_content'
                    || \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($tName)
                )
            ) {
                // Make query to count records from page:
                $c = $this->getDatabase()->exec_SELECTcountRows('uid', $tName, 'pid=' . (int)$id
                    . BackendUtility::deleteClause($tName) . BackendUtility::versioningPlaceholderClause($tName));
                // If records were found (or if "tt_content" is the table...):
                if ($c || $tName === 'tt_content') {
                    // Add row to menu:
                    $out .= '
					<td><a href="#' . $tName . '" title="' . $this->getLanguageService()->sL($GLOBALS['TCA'][$tName]['ctrl']['title'], true) . '"></a>'
                        . $this->iconFactory->getIconForRecord($tName, [], Icon::SIZE_SMALL)->render()
                        . '</td>';
                    // ... and to the internal array, activeTables we also add table icon and title (for use elsewhere)
                    $title = $this->getLanguageService()->sL($GLOBALS['TCA'][$tName]['ctrl']['title'], true)
                        . ': ' . $c . ' ' . $this->getLanguageService()->getLL('records', true);
                    $this->activeTables[$tName] = '<span title="' . $title . '">'
                        . $this->iconFactory->getIconForRecord($tName, [], Icon::SIZE_SMALL)->render()
                        . '</span>'
                        . '&nbsp;' . $this->getLanguageService()->sL($GLOBALS['TCA'][$tName]['ctrl']['title'], true);
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
    protected function checkIfTranslationsExistInLanguage(array $contentElements, $language)
    {
        // If in default language, you may always create new entries
        // Also, you may override this strict behavior via user TS Config
        // If you do so, you're on your own and cannot rely on any support by the TYPO3 core
        // We jump out here since we don't need to do the expensive loop operations
        $allowInconsistentLanguageHandling = BackendUtility::getModTSconfig($this->id, 'mod.web_layout.allowInconsistentLanguageHandling');
        if ($language === 0 || $allowInconsistentLanguageHandling['value'] === '1') {
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
                    }
                    if ((int)$contentElement['l18n_parent'] > 0) {
                        $this->languageHasTranslationsCache[$language]['hasTranslations'] = true;
                    }
                }
            }
            // Check whether we have a mix of both
            if ($this->languageHasTranslationsCache[$language]['hasStandAloneContent']
                && $this->languageHasTranslationsCache[$language]['hasTranslations']
            ) {
                $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    sprintf($this->getLanguageService()->getLL('staleTranslationWarning'), $this->languageIconTitles[$language]['title']),
                    sprintf($this->getLanguageService()->getLL('staleTranslationWarningTitle'), $this->languageIconTitles[$language]['title']),
                    FlashMessage::WARNING
                );
                $service = GeneralUtility::makeInstance(FlashMessageService::class);
                $queue = $service->getMessageQueueByIdentifier();
                $queue->addMessage($message);
            }
        }
        if ($this->languageHasTranslationsCache[$language]['hasTranslations']) {
            return true;
        }
        return false;
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
     * @return DatabaseConnection
     */
    protected function getDatabase()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return PageLayoutController
     */
    protected function getPageLayoutController()
    {
        return $GLOBALS['SOBE'];
    }
}
