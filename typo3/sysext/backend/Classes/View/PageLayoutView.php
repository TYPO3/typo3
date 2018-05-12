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
use TYPO3\CMS\Backend\Controller\Page\LocalizationController;
use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
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
        }
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
                    $this->getBackendUser()->getPagePermsClause(1)
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
                    $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
                    $eI = '<a class="btn btn-default" href="' . htmlspecialchars($url)
                        . '" title="' . htmlspecialchars($iTitle) . '">'
                        . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
                } else {
                    $eI = '';
                }
                switch ($field) {
                    case 'title':
                        $theData[$field] = '&nbsp;' . $eI . '<strong>'
                            . $lang->sL($GLOBALS['TCA']['pages']['columns'][$field]['label'])
                            . '</strong>';
                        break;
                    case 'uid':
                        $theData[$field] = '&nbsp;<strong>ID:</strong>';
                        break;
                    default:
                        if (substr($field, 0, 6) === 'table_') {
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
                            $theData[$field] = '&nbsp;&nbsp;' . $eI . '<strong>'
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
        $backendUser = $this->getBackendUser();
        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content')
            ->getExpressionBuilder();
        $this->pageinfo = BackendUtility::readPageAccess($this->id, '');
        $this->initializeLanguages();
        $this->initializeClipboard();
        $pageTitleParamForAltDoc = '&recTitle=' . rawurlencode(BackendUtility::getRecordTitle('pages', BackendUtility::getRecordWSOL('pages', $id), true));
        /** @var $pageRenderer PageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/LayoutModule/DragDrop');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/LayoutModule/Paste');
        $userCanEditPage = $this->ext_CALC_PERMS & Permission::PAGE_EDIT && !empty($this->id) && ($backendUser->isAdmin() || (int)$this->pageinfo['editlock'] === 0);
        if ($this->tt_contentConfig['languageColsPointer'] > 0) {
            $userCanEditPage = $this->getBackendUser()->check('tables_modify', 'pages_language_overlay');
        }
        if ($userCanEditPage) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages_language_overlay');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

            $queryBuilder->select('uid')
                ->from('pages_language_overlay')
                ->where(
                    $queryBuilder->expr()->eq(
                        'pid',
                        $queryBuilder->createNamedParameter((int)$this->id, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'sys_language_uid',
                        $queryBuilder->createNamedParameter(
                            $this->tt_contentConfig['sys_language_uid'],
                            \PDO::PARAM_INT
                        )
                    )
                )
                ->setMaxResults(1);

            $languageOverlayId = (int)$queryBuilder->execute()->fetchColumn(0);

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
            $cList = explode(',', $this->tt_contentConfig['cols']);
            $content = [];
            $head = [];

            // Select content records per column
            $contentRecordsPerColumn = $this->getContentRecordsPerColumn('table', $id, array_values($cList), $showLanguage);
            // For each column, render the content into a variable:
            foreach ($cList as $columnId) {
                if (!isset($this->contentElementCache[$lP][$columnId])) {
                    $this->contentElementCache[$lP][$columnId] = [];
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
                if ($this->getPageLayoutController()->contentIsNotLockedForEditors()
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
                                    'colPos' => $columnId,
                                    'sys_language_uid' => $lP
                                ]
                            ],
                            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                        ];
                        $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
                    }

                    $link = '<a href="' . htmlspecialchars($url) . '" title="'
                        . htmlspecialchars($this->getLanguageService()->getLL('newContentElement')) . '" class="btn btn-default btn-sm">'
                        . $this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL)->render()
                        . ' '
                        . htmlspecialchars($this->getLanguageService()->getLL('content')) . '</a>';
                }
                if ($this->getBackendUser()->checkLanguageAccess($lP)) {
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
                            if (!$this->defLangBinding) {
                                $languageColumn[$columnId][$lP] .= $this->newLanguageButton(
                                    $this->getNonTranslatedTTcontentUids($defaultLanguageElementsByColumn[$columnId], $id, $lP),
                                    $lP,
                                    $columnId
                                );
                            }
                        }
                        if (is_array($row) && !VersionState::cast($row['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
                            $singleElementHTML = '';
                            if (!$lP && ($this->defLangBinding || $row['sys_language_uid'] != -1)) {
                                $defaultLanguageElementsByColumn[$columnId][] = (isset($row['_ORIG_uid']) ? $row['_ORIG_uid'] : $row['uid']);
                            }
                            $editUidList .= $row['uid'] . ',';
                            $disableMoveAndNewButtons = $this->defLangBinding && $lP > 0 && $this->checkIfTranslationsExistInLanguage($contentRecordsPerColumn, $lP);
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
                            $singleElementHTML .= '<div class="t3js-page-new-ce t3-page-ce-wrapper-new-ce" id="colpos-' . $columnId . '-' . 'page-' . $id .
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
                                    . htmlspecialchars($this->getLanguageService()->getLL('newContentElement')) . '" class="btn btn-default btn-sm">'
                                    . $this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL)->render()
                                    . ' '
                                    . htmlspecialchars($this->getLanguageService()->getLL('content')) . '</a>
							';
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
                    $editParam = $this->doEdit && !empty($rowArr)
                        ? '&edit[tt_content][' . $editUidList . ']=edit' . $pageTitleParamForAltDoc
                        : '';
                    $head[$columnId] .= $this->tt_content_drawColHeader($colTitle, $editParam);
                }
            }
            // For each column, fit the rendered content into a table cell:
            $out = '';
            if ($this->tt_contentConfig['languageMode']) {
                // in language mode process the content elements, but only fill $languageColumn. output will be generated later
                $sortedLanguageColumn = [];
                foreach ($cList as $columnId) {
                    if (GeneralUtility::inList($this->tt_contentConfig['activeCols'], $columnId)) {
                        $languageColumn[$columnId][$lP] = $head[$columnId] . $content[$columnId];
                        if (!$this->defLangBinding) {
                            $languageColumn[$columnId][$lP] .= $this->newLanguageButton(
                                $this->getNonTranslatedTTcontentUids($defaultLanguageElementsByColumn[$columnId], $id, $lP),
                                $lP,
                                $columnId
                            );
                        }
                        // We sort $languageColumn again according to $cList as it may contain data already from above.
                        $sortedLanguageColumn[$columnId] = $languageColumn[$columnId];
                    }
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
                            $grid .= $this->tt_content_drawColHeader($this->getLanguageService()->getLL('noAccess'));
                        } elseif (isset($columnConfig['colPos']) && $columnConfig['colPos'] !== ''
                            && !GeneralUtility::inList($this->tt_contentConfig['activeCols'], $columnConfig['colPos'])
                        ) {
                            $grid .= $this->tt_content_drawColHeader($this->getLanguageService()->sL($columnConfig['name']) .
                                ' (' . $this->getLanguageService()->getLL('noAccess') . ')');
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
                $out .= $grid . '</table></div>';
            }
            // CSH:
            $out .= BackendUtility::cshItem($this->descrTable, 'columns_multi', null, '<span class="btn btn-default btn-sm">|</span>');
        }
        $elFromTable = $this->clipboard->elFromTable('tt_content');
        if (!empty($elFromTable) && $this->getPageLayoutController()->pageIsNotLockedForEditors()) {
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
                    $onClick = BackendUtility::viewOnClick($this->id, '', BackendUtility::BEgetRootLine($this->id), '', '', ('&L=' . $lP));
                    $viewLink = '<a href="#" class="btn btn-default btn-sm" onclick="' . htmlspecialchars($onClick) . '" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.showPage')) . '">' . $this->iconFactory->getIcon('actions-view', Icon::SIZE_SMALL)->render() . '</a>';
                }
                // Language overlay page header:
                if ($lP) {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getQueryBuilderForTable('pages_language_overlay');
                    $queryBuilder->getRestrictions()
                        ->removeAll()
                        ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                        ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));

                    $lpRecord = $queryBuilder->select('*')
                        ->from('pages_language_overlay')
                        ->where(
                            $queryBuilder->expr()->eq(
                                'pid',
                                $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
                            ),
                            $queryBuilder->expr()->eq(
                                'sys_language_uid',
                                $queryBuilder->createNamedParameter($lP, \PDO::PARAM_INT)
                            )
                        )
                        ->setMaxResults(1)
                        ->execute()
                        ->fetch();

                    BackendUtility::workspaceOL('pages_language_overlay', $lpRecord);
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
                    $editLink = (
                        $this->getBackendUser()->check('tables_modify', 'pages_language_overlay')
                        ? '<a href="' . htmlspecialchars($url) . '" class="btn btn-default btn-sm"'
                        . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('edit')) . '">'
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
                        $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
                        $editLink = (
                            $this->getBackendUser()->check('tables_modify', 'pages_language_overlay')
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
                    $out .= '<td valign="top" class="t3-grid-cell t3-page-column t3js-page-column t3js-page-lang-column t3js-page-lang-column-' . $languageId . '">' . $columnContent . '</td>';
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
                            $cCont[] = $element . $this->newLanguageButton(
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
        $addWhere = empty($addWhere) ? [] : [QueryHelper::stripLogicalOperatorPrefix($addWhere)];
        $queryBuilder = $this->getQueryBuilder($table, $id, $addWhere);
        $this->setTotalItems($table, $id, $addWhere);
        $dbCount = 0;
        $result = false;
        // Make query for records if there were any records found in the count operation
        if ($this->totalItems) {
            $result = $queryBuilder->execute();
            // Will return FALSE, if $result is invalid
            $dbCount = $result->rowCount();
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
            $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
            $theData['__cmds__'] = '<a href="' . htmlspecialchars($url) . '" '
                . 'title="' . htmlspecialchars($this->getLanguageService()->getLL('new')) . '">'
                . $this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL)->render() . '</a>';
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
                        $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
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
        $columns = array_map('intval', $columns);
        $contentRecordsPerColumn = array_fill_keys($columns, []);

        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content')
            ->expr();

        $queryBuilder = $this->getQueryBuilder(
            'tt_content',
            $id,
            [
                $expressionBuilder->in('colPos', $columns),
                $additionalWhereClause
            ]
        );

        // Traverse any selected elements and render their display code:
        $results = $this->getResult($queryBuilder->execute());

        foreach ($results as $record) {
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
                $this->getBackendUser()->getPagePermsClause(1)
            );

        if (!empty($GLOBALS['TCA']['pages']['ctrl']['sortby'])) {
            $queryBuilder->orderBy($GLOBALS['TCA']['pages']['ctrl']['sortby']);
        }

        if ($depth >= 0) {
            $result = $queryBuilder->execute();
            $rowCount = $result->rowCount();
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
     * Adds pages-rows to an array, selecting recursively in the page tree.
     *
     * @param array $theRows Array which will accumulate page rows
     * @param int $pid Pid to select from
     * @param string $qWhere Query-where clause
     * @param string $treeIcons Prefixed icon code.
     * @param int $depth Depth (decreasing)
     * @return array $theRows, but with added rows.
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    public function pages_getTree($theRows, $pid, $qWhere, $treeIcons, $depth)
    {
        GeneralUtility::logDeprecatedFunction();
        $depth--;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll();
        $queryBuilder
            ->select('*')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)));

        if (!empty($GLOBALS['TCA']['pages']['ctrl']['sortby'])) {
            $queryBuilder->orderBy($GLOBALS['TCA']['pages']['ctrl']['sortby']);
        }

        if (!empty($qWhere)) {
            $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($qWhere));
        }

        if ($depth >= 0) {
            $result = $queryBuilder->execute();
            $rc = $result->rowCount();
            $c = 0;
            while ($row = $result->fetch()) {
                BackendUtility::workspaceOL('pages', $row);
                if (is_array($row)) {
                    $c++;
                    $row['treeIcons'] = $treeIcons . '<span class="treeline-icon treeline-icon-join' . ($rc === $c ? 'bottom' : '') . '"></span>';
                    $theRows[] = $row;
                    // Get the branch
                    $spaceOutIcons = '<span class="treeline-icon treeline-icon-' . ($rc === $c ? 'clear' : 'line') . '"></span>';
                    $theRows = $this->pages_getTree(
                        $theRows,
                        $row['uid'],
                        $qWhere,
                        $treeIcons . $spaceOutIcons,
                        $row['php_tree_stop'] ? 0 : $depth
                    );
                }
            }
        } else {
            $count = (int)$queryBuilder->count('uid')->execute()->fetchColumn(0);
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
                    $pTitle = htmlspecialchars(BackendUtility::getProcessedValue('pages', $field, $row[$field], 20));
                    $theData[$field] = $row['treeIcons'] . $theIcon . $pTitle . '&nbsp;&nbsp;';
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
                        $eI = '<a class="btn btn-default" href="' . htmlspecialchars($url)
                            . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('editThisPage')) . '">'
                            . $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render() . '</a>';
                    } else {
                        $eI = '';
                    }
                    $theData[$field] = $eI . '<span align="right">' . $row['uid'] . '</span>';
                    break;
                case 'shortcut':
                case 'shortcut_mode':
                    if ((int)$row['doktype'] === \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SHORTCUT) {
                        $theData[$field] = $this->getPagesTableFieldValue($field, $row);
                    }
                    break;
                default:
                    if (substr($field, 0, 6) === 'table_') {
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
                    . '">' . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render() . '</a>';
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
                        . '#element-tt_content-' . $row['uid'] . '" title="' . htmlspecialchars($this->getLanguageService()->getLL($label)) . '">'
                        . $this->iconFactory->getIcon('actions-edit-' . strtolower($label), Icon::SIZE_SMALL)->render() . '</a>';
                }
                // Delete
                $disableDeleteTS = $this->getBackendUser()->getTSConfig('options.disableDelete');
                $disableDelete = (bool)trim(isset($disableDeleteTS['properties']['tt_content']) ? $disableDeleteTS['properties']['tt_content'] : $disableDeleteTS['value']);
                if (!$disableDelete) {
                    $params = '&cmd[tt_content][' . $row['uid'] . '][delete]=1';
                    $confirm = $this->getLanguageService()->getLL('deleteWarning')
                        . BackendUtility::translationCount('tt_content', $row['uid'], (' '
                                                                                       . $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.translationsOfRecord')));
                    $out .= '<a class="btn btn-default t3js-modal-trigger" href="' . htmlspecialchars(BackendUtility::getLinkToDataHandlerAction($params)) . '"'
                        . ' data-severity="warning"'
                        . ' data-title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_alt_doc.xlf:label.confirm.delete_record.title')) . '"'
                        . ' data-content="' . htmlspecialchars($confirm) . '" '
                        . ' data-button-close-text="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_common.xlf:cancel')) . '"'
                        . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('deleteItem')) . '">'
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
        $additionalIcons[] = $langMode ? $this->languageFlag($row['sys_language_uid'], false) : '';
        // Get record locking status:
        if ($lockInfo = BackendUtility::isRecordLocked('tt_content', $row['uid'])) {
            $additionalIcons[] = '<a href="#" data-toggle="tooltip" data-title="' . htmlspecialchars($lockInfo['msg']) . '">'
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
                $hiddenHeaderNote = ' <em>[' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.hidden')) . ']</em>';
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
            $fluidTemplateFile = '';

            if (
                $row['CType'] === 'list' && !empty($row['list_type'])
                && !empty($tsConfig['properties']['list.'][$row['list_type']])
            ) {
                $fluidTemplateFile = $tsConfig['properties']['list.'][$row['list_type']];
            } elseif (!empty($tsConfig['properties'][$row['CType']])) {
                $fluidTemplateFile = $tsConfig['properties'][$row['CType']];
            }

            if ($fluidTemplateFile) {
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
                            $out .= $this->linkEditContent('<strong>' . htmlspecialchars($this->getLanguageService()->sL($label)) . '</strong>', $row) . '<br />';
                        } else {
                            $message = sprintf($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue'), $row['list_type']);
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
                            $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue'),
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
     * @param int $colPos Column position
     * @return string "Copy languages" button, if available.
     */
    public function newLanguageButton($defaultLanguageUids, $lP, $colPos = 0)
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
                $allowCopy = !$this->languageHasTranslationsCache[$lP]['hasTranslations'];
            }
        }

        if (isset($this->contentElementCache[$lP][$colPos]) && is_array($this->contentElementCache[$lP][$colPos])) {
            foreach ($this->contentElementCache[$lP][$colPos] as $record) {
                $key = array_search($record['l10n_source'], $defaultLanguageUids);
                if ($key !== false) {
                    unset($defaultLanguageUids[$key]);
                }
            }
        }

        if (!empty($defaultLanguageUids)) {
            $theNewButton =
                '<input'
                    . ' class="btn btn-default t3js-localize"'
                    . ' type="button"'
                    . ' disabled'
                    . ' value="' . htmlspecialchars($this->getLanguageService()->getLL('newPageContent_translate')) . '"'
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
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI') . '#element-tt_content-' . $row['uid']
            ];
            $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
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
     * @param int $id Page id for which to create a new language (pages_language_overlay record)
     * @return string <select> HTML element (if there were items for the box anyways...)
     * @see getTable_tt_content()
     */
    public function languageSelector($id)
    {
        if ($this->getBackendUser()->check('tables_modify', 'pages_language_overlay')) {
            // First, select all
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_language');
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(HiddenRestriction::class));
            $statement = $queryBuilder->select('uid', 'title')
                ->from('sys_language')
                ->orderBy('sorting')
                ->execute();
            $availableTranslations = [];
            while ($row = $statement->fetch()) {
                if ($this->getBackendUser()->checkLanguageAccess($row['uid'])) {
                    $availableTranslations[(int)$row['uid']] = $row['title'];
                }
            }
            // Then, subtract the languages which are already on the page:
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_language');
            $queryBuilder->getRestrictions()->removeAll();
            $queryBuilder->select('sys_language.uid AS uid', 'sys_language.title AS title')
                ->from('sys_language')
                ->join(
                    'sys_language',
                    'pages_language_overlay',
                    'pages_language_overlay',
                    $queryBuilder->expr()->eq('sys_language.uid', $queryBuilder->quoteIdentifier('pages_language_overlay.sys_language_uid'))
                )
                ->where(
                    $queryBuilder->expr()->eq(
                        'pages_language_overlay.deleted',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'pages_language_overlay.pid',
                        $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->gte(
                            'pages_language_overlay.t3ver_state',
                            $queryBuilder->createNamedParameter(
                                (string)new VersionState(VersionState::DEFAULT_STATE),
                                \PDO::PARAM_INT
                            )
                        ),
                        $queryBuilder->expr()->eq(
                            'pages_language_overlay.t3ver_wsid',
                            $queryBuilder->createNamedParameter($this->getBackendUser()->workspace, \PDO::PARAM_INT)
                        )
                    )
                )
                ->groupBy(
                    'pages_language_overlay.sys_language_uid',
                    'sys_language.uid',
                    'sys_language.pid',
                    'sys_language.tstamp',
                    'sys_language.hidden',
                    'sys_language.title',
                    'sys_language.language_isocode',
                    'sys_language.static_lang_isocode',
                    'sys_language.flag',
                    'sys_language.sorting'
                )
                ->orderBy('sys_language.sorting');
            if (!$this->getBackendUser()->isAdmin()) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->eq(
                        'sys_language.hidden',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                );
            }
            $statement = $queryBuilder->execute();
            while ($row = $statement->fetch()) {
                unset($availableTranslations[(int)$row['uid']]);
            }
            // Remove disallowed languages
            if (!empty($availableTranslations)
                && !$this->getBackendUser()->user['admin']
                && $this->getBackendUser()->groupData['allowed_languages'] !== ''
            ) {
                $allowed_languages = array_flip(explode(',', $this->getBackendUser()->groupData['allowed_languages']));
                if (!empty($allowed_languages)) {
                    foreach ($availableTranslations as $key => $value) {
                        if (!isset($allowed_languages[$key]) && $key != 0) {
                            unset($availableTranslations[$key]);
                        }
                    }
                }
            }
            // Remove disabled languages
            $modSharedTSconfig = BackendUtility::getModTSconfig($id, 'mod.SHARED');
            $disableLanguages = isset($modSharedTSconfig['properties']['disableLanguages'])
                ? GeneralUtility::trimExplode(',', $modSharedTSconfig['properties']['disableLanguages'], true)
                : [];
            if (!empty($availableTranslations) && !empty($disableLanguages)) {
                foreach ($disableLanguages as $language) {
                    if ($language != 0 && isset($availableTranslations[$language])) {
                        unset($availableTranslations[$language]);
                    }
                }
            }
            // If any languages are left, make selector:
            if (!empty($availableTranslations)) {
                $output = '<option value=""></option>';
                foreach ($availableTranslations as $languageUid => $languageTitle) {
                    // Build localize command URL to DataHandler (tce_db)
                    // which redirects to FormEngine (record_edit)
                    // which, when finished editing should return back to the current page (returnUrl)
                    $parameters = [
                        'justLocalized' => 'pages:' . $id . ':' . $languageUid,
                        'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                    ];
                    $redirectUrl = BackendUtility::getModuleUrl('record_edit', $parameters);
                    $targetUrl = BackendUtility::getLinkToDataHandlerAction(
                        '&cmd[pages][' . $id . '][localize]=' . $languageUid,
                        $redirectUrl
                    );

                    $output .= '<option value="' . htmlspecialchars($targetUrl) . '">' . htmlspecialchars($languageTitle) . '</option>';
                }

                return '<div class="form-inline form-inline-spaced">'
                    . '<div class="form-group">'
                    . '<label for="createNewLanguage">'
                    . htmlspecialchars($this->getLanguageService()->getLL('new_language'))
                    . '</label>'
                    . '<select class="form-control input-sm" name="createNewLanguage" onchange="window.location.href=this.options[this.selectedIndex].value">'
                    . $output
                    . '</select></div></div>';
            }
        }
        return '';
    }

    /**
     * Traverse the result pointer given, adding each record to array and setting some internal values at the same time.
     *
     * @param Statement|\mysqli_result $result DBAL Statement or MySQLi result object
     * @param string $table Table name defaulting to tt_content
     * @return array The selected rows returned in this array.
     */
    public function getResult($result, string $table = 'tt_content'): array
    {
        if ($result instanceof \mysqli_result) {
            GeneralUtility::deprecationLog('Using \TYPO3\CMS\Backend\View\PageLayoutView::getResult with a mysqli_result object is deprecated since TYPO3 CMS 8 and will be removed in TYPO3 CMS 9');
        }
        $output = [];
        // Traverse the result:
        while ($row = ($result instanceof Statement ? $result->fetch() : $result->fetch_assoc())) {
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
     * @see \TYPO3\CMS\Recordlist\RecordList::main()
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
        return '<span title="' . $title . '">' . $this->iconFactory->getIcon('status-status-edit-read-only', Icon::SIZE_SMALL)->render() . '</span>';
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
}
