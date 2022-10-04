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

namespace TYPO3\CMS\Backend\View;

use Doctrine\DBAL\Result;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Controller\Page\LocalizationController;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Child class for the Web > Page module
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 * @deprecated Will be removed in TYPO3 11
 */
class PageLayoutView implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * If TRUE, new-wizards are linked to rather than the regular new-element list.
     *
     * @var bool
     */
    public $option_newWizard = true;

    /**
     * If TRUE, elements will have edit icons (probably this is whether the user has permission to edit the page content). Set externally.
     *
     * @var bool
     */
    public $doEdit = true;

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
        'languageCols' => 0,
        'languageMode' => 0,
        'languageColsPointer' => 0,
        // Displays hidden records as well
        'showHidden' => 1,
        // Which language
        'sys_language_uid' => 0,
        'cols' => '1,0,2,3',
        // Which columns can be accessed by current BE user
        'activeCols' => '1,0,2,3',
    ];

    /**
     * Used to move content up / down
     * @var array
     */
    public $tt_contentData = [
        'prev' => [],
        'next' => [],
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
     * Page id
     *
     * @var int
     */
    public $id;

    /**
     * Loaded with page record with version overlay if any.
     *
     * @var string[]
     */
    public $pageRecord = [];

    /**
     * Contains site languages for this page ID
     *
     * @var SiteLanguage[]
     */
    protected $siteLanguages = [];

    /**
     * Current ids page record
     *
     * @var array
     */
    protected $pageinfo;

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
     * Cache the number of references to a record
     *
     * @var array
     */
    protected $referenceCount = [];

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->localizationController = GeneralUtility::makeInstance(LocalizationController::class);
    }

    /**
     * @param PageLayoutContext $context
     * @return PageLayoutView
     * @internal
     */
    public static function createFromPageLayoutContext(PageLayoutContext $context): PageLayoutView
    {
        $drawingConfiguration = $context->getDrawingConfiguration();
        $languageId = $drawingConfiguration->getSelectedLanguageId();
        $pageLayoutView = GeneralUtility::makeInstance(self::class);
        $pageLayoutView->id = $context->getPageId();
        $pageLayoutView->pageinfo = BackendUtility::readPageAccess($pageLayoutView->id, '') ?: [];
        $pageLayoutView->pageRecord = $context->getPageRecord();
        $pageLayoutView->option_newWizard = $drawingConfiguration->getShowNewContentWizard();
        $pageLayoutView->defLangBinding = $drawingConfiguration->getDefaultLanguageBinding();
        $pageLayoutView->tt_contentConfig['cols'] = implode(',', $drawingConfiguration->getActiveColumns());
        $pageLayoutView->tt_contentConfig['activeCols'] = implode(',', $drawingConfiguration->getActiveColumns());
        $pageLayoutView->tt_contentConfig['showHidden'] = $drawingConfiguration->getShowHidden();
        $pageLayoutView->tt_contentConfig['sys_language_uid'] = $languageId;
        if ($drawingConfiguration->getLanguageMode()) {
            $pageLayoutView->tt_contentConfig['languageMode'] = 1;
            $pageLayoutView->tt_contentConfig['languageCols'] = $drawingConfiguration->getLanguageColumns();
            $pageLayoutView->tt_contentConfig['languageColsPointer'] = $languageId;
        }
        $pageLayoutView->doEdit = $pageLayoutView->isContentEditable($languageId);
        $pageLayoutView->CType_labels = $context->getContentTypeLabels();
        $pageLayoutView->itemLabels = $context->getItemLabels();
        return $pageLayoutView;
    }

    protected function initialize()
    {
        $this->resolveSiteLanguages($this->id);
        $this->pageRecord = BackendUtility::getRecordWSOL('pages', $this->id);
        $this->pageinfo = BackendUtility::readPageAccess($this->id, '') ?: [];
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);

        $pageActionsInstruction = JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/Backend/PageActions');
        if ($this->isPageEditable()) {
            $languageOverlayId = 0;
            $pageLocalizationRecord = BackendUtility::getRecordLocalization('pages', $this->id, (int)$this->tt_contentConfig['sys_language_uid']);
            if (is_array($pageLocalizationRecord)) {
                $pageLocalizationRecord = reset($pageLocalizationRecord);
            }
            if (!empty($pageLocalizationRecord['uid'])) {
                $languageOverlayId = $pageLocalizationRecord['uid'];
            }
            $pageActionsInstruction
                ->invoke('setPageId', (int)$this->id)
                ->invoke('setLanguageOverlayId', $languageOverlayId);
        }
        $pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction($pageActionsInstruction);
        // Get labels for CTypes and tt_content element fields in general:
        $this->CType_labels = [];
        foreach ($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] as $val) {
            $this->CType_labels[$val[1]] = $this->getLanguageService()->sL($val[0]);
        }

        $this->itemLabels = [];
        foreach ($GLOBALS['TCA']['tt_content']['columns'] as $name => $val) {
            $this->itemLabels[$name] = $this->getLanguageService()->sL($val['label']);
        }
    }

    /**
     * Build a list of language IDs that should be rendered in this view
     * @return int[]
     */
    protected function getSelectedLanguages(): array
    {
        $langList = $this->tt_contentConfig['sys_language_uid'];
        if ($this->tt_contentConfig['languageMode']) {
            if ($this->tt_contentConfig['languageColsPointer']) {
                $langList = '0,' . $this->tt_contentConfig['languageColsPointer'];
            } else {
                $langList = implode(',', array_keys($this->tt_contentConfig['languageCols']));
            }
        }
        return GeneralUtility::intExplode(',', $langList);
    }

    /**
     * Renders Content Elements from the tt_content table from page id
     *
     * @param int $id Page id
     * @return string HTML for the listing
     */
    public function getTable_tt_content($id)
    {
        $this->id = (int)$id;
        $this->initialize();
        $expressionBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content')
            ->getExpressionBuilder();

        $languageColumn = [];
        $out = '';
        $tcaItems = GeneralUtility::makeInstance(BackendLayoutView::class)->getColPosListItemsParsed($this->id);
        $languageIds = $this->getSelectedLanguages();
        $defaultLanguageElementsByColumn = [];
        $defLangBinding = [];
        // For each languages...
        // If not languageMode, then we'll only be through this once.
        foreach ($languageIds as $lP) {
            if (!isset($this->contentElementCache[$lP])) {
                $this->contentElementCache[$lP] = [];
            }

            if (count($languageIds) === 1 || $lP === 0) {
                $showLanguage = $expressionBuilder->in('sys_language_uid', [$lP, -1]);
            } else {
                $showLanguage = $expressionBuilder->eq('sys_language_uid', $lP);
            }
            $content = [];
            $head = [];

            $backendLayout = $this->getBackendLayoutView()->getSelectedBackendLayout($this->id);
            $columns = $backendLayout['__colPosList'];
            // Select content records per column
            $contentRecordsPerColumn = $this->getContentRecordsPerColumn('tt_content', $id, $columns, $showLanguage);
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
                            'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
                        ];
                        $routeName = BackendUtility::getPagesTSconfig($id)['mod.']['newContentElementWizard.']['override']
                            ?? 'new_content_element_wizard';
                        $url = (string)$this->uriBuilder->buildUriFromRoute($routeName, $urlParameters);
                    } else {
                        $urlParameters = [
                            'edit' => [
                                'tt_content' => [
                                    $id => 'new',
                                ],
                            ],
                            'defVals' => [
                                'tt_content' => [
                                    'colPos' => $columnId,
                                    'sys_language_uid' => $lP,
                                ],
                            ],
                            'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
                        ];
                        $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                    }
                    $title = htmlspecialchars($this->getLanguageService()->getLL('newContentElement'));
                    $link = '<a href="' . htmlspecialchars($url) . '" '
                        . 'title="' . $title . '"'
                        . 'data-title="' . $title . '"'
                        . 'class="btn btn-default btn-sm ' . ($this->option_newWizard ? 't3js-toggle-new-content-element-wizard disabled' : '') . '">'
                        . $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL)->render()
                        . ' '
                        . htmlspecialchars($this->getLanguageService()->getLL('content')) . '</a>';
                }
                if ($this->getBackendUser()->checkLanguageAccess($lP) && $columnId !== 'unused') {
                    $content[$columnId] .= '
                    <div class="t3-page-ce t3js-page-ce" data-page="' . (int)$id . '" id="' . StringUtility::getUniqueId() . '">
                        <div class="t3js-page-new-ce t3-page-ce-wrapper-new-ce" id="colpos-' . $columnId . '-page-' . $id . '-' . StringUtility::getUniqueId() . '">'
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
                                0,
                                $disableMoveAndNewButtons,
                                true,
                                $this->hasContentModificationAndAccessPermissions()
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
                                . $row['uid'] . '" data-table="tt_content" data-uid="' . $row['uid'] . '" data-language-uid="'
                                . $row['sys_language_uid'] . '"' . $displayNone . '>' . $singleElementHTML . '</div>';

                            $singleElementHTML .= '<div class="t3-page-ce" data-colpos="' . $columnId . '">';
                            $singleElementHTML .= '<div class="t3js-page-new-ce t3-page-ce-wrapper-new-ce" id="colpos-' . $columnId . '-page-' . $id .
                                '-' . StringUtility::getUniqueId() . '">';
                            // Add icon "new content element below"
                            if (!$disableMoveAndNewButtons
                                && $this->isContentEditable($lP)
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
                                        'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
                                    ];
                                    $routeName = BackendUtility::getPagesTSconfig($row['pid'])['mod.']['newContentElementWizard.']['override']
                                        ?? 'new_content_element_wizard';
                                    $url = (string)$this->uriBuilder->buildUriFromRoute($routeName, $urlParameters);
                                } else {
                                    $urlParameters = [
                                        'edit' => [
                                            'tt_content' => [
                                                -$row['uid'] => 'new',
                                            ],
                                        ],
                                        'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
                                    ];
                                    $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                                }
                                $title = htmlspecialchars($this->getLanguageService()->getLL('newContentElement'));
                                $singleElementHTML .= '<a href="' . htmlspecialchars($url) . '" '
                                    . 'title="' . $title . '"'
                                    . 'data-title="' . $title . '"'
                                    . 'class="btn btn-default btn-sm ' . ($this->option_newWizard ? 't3js-toggle-new-content-element-wizard disabled' : '') . '">'
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
                        $colTitle = $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:unusedColPos');
                        $editParam = '';
                    } else {
                        $colTitle = '';
                        foreach ($tcaItems as $item) {
                            if ($item[1] == $columnId) {
                                $colTitle = $this->getLanguageService()->sL($item[0]);
                            }
                        }
                        if (empty($colTitle)) {
                            $colTitle = BackendUtility::getProcessedValue('tt_content', 'colPos', (string)$columnId) ?? '';
                        }
                        $editParam = $this->doEdit && !empty($rowArr)
                            ? '&edit[tt_content][' . $editUidList . ']=edit&recTitle=' . rawurlencode(BackendUtility::getRecordTitle('pages', $this->pageRecord, true))
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
                $colSpan = 0;
                $rowSpan = 0;
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
                            $grid .= $head[$columnKey];
                            $grid .= $content[$columnKey];
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
        // If language mode, then make another presentation:
        // Notice that THIS presentation will override the value of $out!
        // But it needs the code above to execute since $languageColumn is filled with content we need!
        if ($this->tt_contentConfig['languageMode']) {
            return $this->generateLanguageView($languageIds, $defaultLanguageElementsByColumn, $languageColumn, $defLangBinding);
        }
        return $out;
    }

    /**
     * Shows the content elements of the selected languages in each column.
     * @param array $languageIds languages to render
     * @param array $defaultLanguageElementsByColumn
     * @param array $languageColumn
     * @param array $defLangBinding
     * @return string the compiled content
     */
    protected function generateLanguageView(
        array $languageIds,
        array $defaultLanguageElementsByColumn,
        array $languageColumn,
        array $defLangBinding
    ): string {
        // Get language selector:
        $languageSelector = $this->languageSelector($this->id);
        // Reset out - we will make new content here:
        $out = '';
        // Traverse languages found on the page and build up the table displaying them side by side:
        $cCont = [];
        $sCont = [];
        foreach ($languageIds as $languageId) {
            $languageMode = '';
            $labelClass = 'info';
            // Header:
            $languageId = (int)$languageId;
            // Determine language mode
            if ($languageId > 0 && isset($this->languageHasTranslationsCache[$languageId]['mode'])) {
                switch ($this->languageHasTranslationsCache[$languageId]['mode']) {
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
            $columnAttributes = [
                'valign' => 'top',
                'class' => 't3-page-column t3-page-column-lang-name',
                'data-language-uid' => (string)$languageId,
                'data-language-title' => $this->siteLanguages[$languageId]->getTitle(),
                'data-flag-identifier' => $this->siteLanguages[$languageId]->getFlagIdentifier(),
            ];

            $cCont[$languageId] = '
					<td ' . GeneralUtility::implodeAttributes($columnAttributes, true) . '>
						<h2>' . htmlspecialchars($this->tt_contentConfig['languageCols'][$languageId]) . '</h2>
						' . ($languageMode !== '' ? '<span class="label label-' . $labelClass . '">' . $languageMode . '</span>' : '') . '
					</td>';

            $editLink = '';
            $recordIcon = '';
            $viewLink = '';
            // "View page" icon is added:
            if (!VersionState::cast($this->pageinfo['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
                $attributes = PreviewUriBuilder::create($this->id)
                    ->withRootLine(BackendUtility::BEgetRootLine($this->id))
                    ->withAdditionalQueryParameters('&L=' . $languageId)
                    ->serializeDispatcherAttributes();
                $viewLink = '<a href="#" class="btn btn-default btn-sm" ' . $attributes . ' title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage')) . '">' . $this->iconFactory->getIcon('actions-view', Icon::SIZE_SMALL)->render() . '</a>';
            }
            // Language overlay page header:
            if ($languageId) {
                $pageLocalizationRecord = BackendUtility::getRecordLocalization('pages', $this->id, $languageId);
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
                            $pageLocalizationRecord['uid'] => 'edit',
                        ],
                    ],
                    // Disallow manual adjustment of the language field for pages
                    'overrideVals' => [
                        'pages' => [
                            'sys_language_uid' => $languageId,
                        ],
                    ],
                    'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
                ];
                $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                if ($this->getBackendUser()->check('tables_modify', 'pages')) {
                    $editLink = '<a href="' . htmlspecialchars($url) . '" class="btn btn-default btn-sm"'
                        . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('edit')) . '">'
                        . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render() . '</a>';
                }

                $defaultLanguageElements = [];
                array_walk($defaultLanguageElementsByColumn, static function (array $columnContent) use (&$defaultLanguageElements) {
                    $defaultLanguageElements = array_merge($defaultLanguageElements, $columnContent);
                });

                $localizationButtons = [];
                $localizationButtons[] = $this->newLanguageButton(
                    $this->getNonTranslatedTTcontentUids($defaultLanguageElements, $this->id, $languageId),
                    $languageId
                );

                $languageLabel =
                    '<div class="btn-group">'
                    . $viewLink
                    . $editLink
                    . (!empty($localizationButtons) ? implode(LF, $localizationButtons) : '')
                    . '</div>'
                    . ' ' . $recordIcon . ' ' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($pageLocalizationRecord['title'], 20))
                ;
            } else {
                if ($this->getBackendUser()->checkLanguageAccess(0)) {
                    $recordIcon = BackendUtility::wrapClickMenuOnIcon(
                        $this->iconFactory->getIconForRecord('pages', $this->pageRecord, Icon::SIZE_SMALL)->render(),
                        'pages',
                        $this->id
                    );
                    $urlParameters = [
                        'edit' => [
                            'pages' => [
                                $this->id => 'edit',
                            ],
                        ],
                        'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
                    ];
                    $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                    if ($this->getBackendUser()->check('tables_modify', 'pages')) {
                        $editLink = '<a href="' . htmlspecialchars($url) . '" class="btn btn-default btn-sm"'
                            . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('edit')) . '">'
                            . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render() . '</a>';
                    }
                }

                $languageLabel =
                    '<div class="btn-group">'
                    . $viewLink
                    . $editLink
                    . '</div>'
                    . ' ' . $recordIcon . ' ' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($this->pageRecord['title'], 20));
            }
            $sCont[$languageId] = '
					<td class="t3-page-column t3-page-lang-label nowrap">' . $languageLabel . '</td>';
        }
        // Add headers:
        $out .= '<tr>' . implode('', $cCont) . '</tr>';
        $out .= '<tr>' . implode('', $sCont) . '</tr>';
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
                    foreach ($languageIds as $languageId) {
                        if ($languageId > 0
                            && is_array($defLangBinding[$cKey][$languageId])
                            && !$this->checkIfTranslationsExistInLanguage($defaultLanguageElementsByColumn[$cKey], $languageId)
                            && count($defLangBinding[$cKey][$languageId]) > $i
                        ) {
                            $slice = array_slice($defLangBinding[$cKey][$languageId], $i, 1);
                            $element = $slice[0] ?? '';
                        } else {
                            $element = $defLangBinding[$cKey][$languageId][$defUid] ?? '';
                        }
                        $cCont[] = $element;
                    }
                    $out .= '
                        <tr>
							<td valign="top" class="t3-grid-cell" data-colpos="' . $cKey . '">' . implode('</td>
                            <td valign="top" class="t3-grid-cell" data-colpos="' . $cKey . '">', $cCont) . '</td>
						</tr>';
                }
            }
        }
        // Finally, wrap it all in a table and add the language selector on top of it:
        return $languageSelector . '
                <div class="t3-grid-container">
                    <table cellpadding="0" cellspacing="0" class="t3-page-columns t3-grid-table t3js-page-columns">
						' . $out . '
                    </table>
				</div>';
    }

    /**
     * Gets content records per column.
     * This is required for correct workspace overlays.
     *
     * @param string $table Name of table storing content records, by default tt_content
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
            $table,
            $id,
            [
                $additionalWhereClause,
            ]
        );

        // Traverse any selected elements and render their display code:
        $result = $queryBuilder->executeQuery();
        $results = $this->getResult($result);
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

    /**
     * Draw header for a content element column:
     *
     * @param string $colName Column name
     * @param string $editParams Edit params (Syntax: &edit[...] for FormEngine)
     * @return string HTML table
     */
    public function tt_content_drawColHeader($colName, $editParams = '')
    {
        $icons = '';
        // Edit whole of column:
        if ($editParams && $this->hasContentModificationAndAccessPermissions() && $this->getBackendUser()->checkLanguageAccess(0)) {
            $link = $this->uriBuilder->buildUriFromRoute('record_edit') . $editParams . '&returnUrl=' . rawurlencode($GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri());
            $icons = '<a href="' . htmlspecialchars($link) . '"  title="'
                . htmlspecialchars($this->getLanguageService()->getLL('editColumn')) . '">'
                . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
            $icons = '<div class="t3-page-column-header-icons">' . $icons . '</div>';
        }
        return '<div class="t3-page-column-header">
					' . $icons . '
					<div class="t3-page-column-header-label">' . htmlspecialchars($colName) . '</div>
				</div>';
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
        // Render control panel for the element
        if ($backendUser->recordEditAccessInternals('tt_content', $row) && $this->isContentEditable($row['sys_language_uid'])) {
            // Edit content element:
            $urlParameters = [
                'edit' => [
                    'tt_content' => [
                        $row['uid'] => 'edit',
                    ],
                ],
                'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri() . '#element-tt_content-' . $row['uid'],
            ];
            $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters) . '#element-tt_content-' . $row['uid'];

            $out .= '<a class="btn btn-default" href="' . htmlspecialchars($url)
                . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('edit'))
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
                $params = '&data[tt_content][' . ($row['_ORIG_uid'] ?: $row['uid'])
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
                    (string)$this->getReferenceCount('tt_content', $row['uid'])
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
                    . ' data-bs-content="' . htmlspecialchars($confirm) . '" '
                    . ' data-button-close-text="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:cancel')) . '"'
                    . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('deleteItem')) . '">'
                    . $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render() . '</a>';
                if ($this->hasContentModificationAndAccessPermissions()) {
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
        $allowDragAndDrop = $this->isDragAndDropAllowed($row);
        $additionalIcons = [];
        $additionalIcons[] = $this->getIcon('tt_content', $row) . ' ';
        if ($langMode && isset($this->siteLanguages[(int)$row['sys_language_uid']])) {
            $additionalIcons[] = $this->renderLanguageFlag($this->siteLanguages[(int)$row['sys_language_uid']]);
        }
        // Get record locking status:
        if ($lockInfo = BackendUtility::isRecordLocked('tt_content', $row['uid'])) {
            $additionalIcons[] = '<a href="#" data-bs-toggle="tooltip" title="' . htmlspecialchars($lockInfo['msg']) . '">'
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
                && $this->hasContentModificationAndAccessPermissions()
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
        $outHeader = $this->renderContentElementHeader($row);
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
            $fluidPreview = $this->renderContentElementPreviewFromFluidTemplate($row);
            if ($fluidPreview !== null) {
                $out .= $fluidPreview;
                $drawItem = false;
            }
        }

        // Draw preview of the item depending on its CType (if not disabled by previous hook)
        if ($drawItem) {
            $out .= $this->renderContentElementPreview($row);
        }
        $out = $outHeader . '<span class="exampleContent">' . $out . '</span>';
        if ($this->isDisabled('tt_content', $row)) {
            return '<span class="text-muted">' . $out . '</span>';
        }
        return $out;
    }

    public function renderContentElementHeader(array $row): string
    {
        $outHeader = '';
        // Make header:
        if ($row['header']) {
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
        return $outHeader;
    }

    public function renderContentElementPreviewFromFluidTemplate(array $row): ?string
    {
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
                    return $view->render();
                } catch (\Exception $e) {
                    $this->logger->warning('The backend preview for content element {uid} can not be rendered using the Fluid template file "{file}"', [
                        'uid' => $row['uid'],
                        'file' => $fluidTemplateFile,
                        $e->getMessage(),
                    ]);

                    if ($this->getBackendUser()->shallDisplayDebugInformation()) {
                        $view = GeneralUtility::makeInstance(StandaloneView::class);
                        $view->assign('error', [
                            'message' => str_replace(Environment::getProjectPath(), '', $e->getMessage()),
                            'title' => 'Error while rendering FluidTemplate preview using ' . str_replace(Environment::getProjectPath(), '', $fluidTemplateFile),
                        ]);
                        $view->setTemplateSource('<f:be.infobox title="{error.title}" state="2">{error.message}</f:be.infobox>');
                        return $view->render();
                    }
                }
            }
        }
        return null;
    }

    /**
     * Renders the preview part of a content element
     * @param array $row given tt_content database record
     * @return string
     */
    public function renderContentElementPreview(array $row): string
    {
        $previewHtml = '';
        switch ($row['CType']) {
            case 'header':
                if ($row['subheader']) {
                    $previewHtml = $this->linkEditContent($this->renderText($row['subheader']), $row) . '<br />';
                }
                break;
            case 'bullets':
            case 'table':
                if ($row['bodytext']) {
                    $previewHtml = $this->linkEditContent($this->renderText($row['bodytext']), $row) . '<br />';
                }
                break;
            case 'uploads':
                if ($row['media']) {
                    $previewHtml = $this->linkEditContent($this->getThumbCodeUnlinked($row, 'tt_content', 'media'), $row) . '<br />';
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
                    $previewHtml = implode('<br />', $shortcutContent) . '<br />';
                }
                break;
            case 'list':
                $hookOut = '';
                $_params = ['pObj' => &$this, 'row' => $row];
                foreach (
                    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$row['list_type']] ??
                    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['_DEFAULT'] ??
                    [] as $_funcRef
                ) {
                    $hookOut .= GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                }
                if ((string)$hookOut !== '') {
                    $previewHtml = $hookOut;
                } elseif (!empty($row['list_type'])) {
                    $label = BackendUtility::getLabelFromItemListMerged($row['pid'], 'tt_content', 'list_type', $row['list_type']);
                    if (!empty($label)) {
                        $previewHtml = $this->linkEditContent('<strong>' . htmlspecialchars($this->getLanguageService()->sL($label)) . '</strong>', $row) . '<br />';
                    } else {
                        $message = sprintf($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue'), $row['list_type']);
                        $previewHtml = '<span class="label label-warning">' . htmlspecialchars($message) . '</span>';
                    }
                } else {
                    $previewHtml = '<strong>' . $this->getLanguageService()->getLL('noPluginSelected') . '</strong>';
                }
                $previewHtml .= htmlspecialchars($this->getLanguageService()->sL(
                    BackendUtility::getLabelFromItemlist('tt_content', 'pages', $row['pages'])
                )) . '<br />';
                break;
            default:
                $contentType = $this->CType_labels[$row['CType']];
                if (!isset($contentType)) {
                    $contentType = BackendUtility::getLabelFromItemListMerged($row['pid'], 'tt_content', 'CType', $row['CType']);
                }

                if ($contentType) {
                    $previewHtml = $this->linkEditContent('<strong>' . htmlspecialchars($contentType) . '</strong>', $row) . '<br />';
                    if ($row['bodytext']) {
                        $previewHtml .= $this->linkEditContent($this->renderText($row['bodytext']), $row) . '<br />';
                    }
                    if ($row['image']) {
                        $previewHtml .= $this->linkEditContent($this->getThumbCodeUnlinked($row, 'tt_content', 'image'), $row) . '<br />';
                    }
                } else {
                    $message = sprintf(
                        $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue'),
                        $row['CType']
                    );
                    $previewHtml = '<span class="label label-warning">' . htmlspecialchars($message) . '</span>';
                }
        }
        return $previewHtml;
    }

    /**
     * Filters out all tt_content uids which are already translated so only non-translated uids is left.
     * Selects across columns, but within in the same PID. Columns are expect to be the same
     * for translations and original but this may be a conceptual error (?)
     *
     * @param array $defaultLanguageUids Numeric array with uids of tt_content elements in the default language
     * @param int $id The page UID from which to fetch untranslated records (unused, remains in place for compatibility)
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
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, 0));
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

            $result = $queryBuilder->executeQuery();

            // Flip uids:
            $defaultLanguageUids = array_flip($defaultLanguageUids);
            // Traverse any selected elements and unset original UID if any:
            while ($row = $result->fetchAssociative()) {
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
        if (!$this->doEdit || !$lP || !$this->hasContentModificationAndAccessPermissions()) {
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
        if ($this->doEdit
            && $this->hasContentModificationAndAccessPermissions()
            && $this->getBackendUser()->recordEditAccessInternals('tt_content', $row)
        ) {
            $urlParameters = [
                'edit' => [
                    'tt_content' => [
                        $row['uid'] => 'edit',
                    ],
                ],
                'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri() . '#element-tt_content-' . $row['uid'],
            ];
            $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
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
     * @return string HTML <select> element (if there were items for the box anyways...)
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
            if ($language->getLanguageId() <= 0) {
                continue;
            }
            $availableTranslations[$language->getLanguageId()] = $language->getTitle();
        }

        // Then, subtract the languages which are already on the page:
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->getBackendUser()->workspace));
        $queryBuilder->select('uid', $GLOBALS['TCA']['pages']['ctrl']['languageField'])
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
                )
            );
        $statement = $queryBuilder->executeQuery();
        while ($row = $statement->fetchAssociative()) {
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
                    'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
                ];
                $redirectUrl = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $parameters);
                $targetUrl = BackendUtility::getLinkToDataHandlerAction(
                    '&cmd[pages][' . $id . '][localize]=' . $languageUid,
                    $redirectUrl
                );

                $output .= '<option value="' . htmlspecialchars($targetUrl) . '">' . htmlspecialchars($languageTitle) . '</option>';
            }

            return '<div class="row row-cols-auto align-items-end g-3 mb-3">'
                . '<div class="col">'
                . '<select class="form-select" name="createNewLanguage" data-global-event="change" data-action-navigate="$value">'
                . $output
                . '</select></div></div>';
        }
        return '';
    }

    /**
     * Traverse the result pointer given, adding each record to array and setting some internal values at the same time.
     *
     * @param Result $result DBAL Result
     * @return array The selected rows returned in this array.
     */
    public function getResult($result): array
    {
        $output = [];
        // Traverse the result:
        while ($row = $result->fetchAssociative()) {
            BackendUtility::workspaceOL('tt_content', $row, -99, true);
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
     * Generates the data for previous and next elements which is needed for movements.
     *
     * @param array $rowArray
     */
    protected function generateTtContentDataArray(array $rowArray)
    {
        if (empty($this->tt_contentData)) {
            $this->tt_contentData = [
                'next' => [],
                'prev' => [],
            ];
        }
        foreach ($rowArray as $key => $value) {
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
     * @return string HTML for the icon
     */
    public function getIcon($table, $row)
    {
        // Initialization
        $toolTip = BackendUtility::getRecordToolTip($row, $table);
        $icon = '<span ' . $toolTip . '>' . $this->iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render() . '</span>';
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
                    . htmlspecialchars(BackendUtility::getProcessedValue($table, $field, $row[$field]) ?? '');
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

    /*****************************************
     *
     * External renderings
     *
     *****************************************/

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
                    $this->getLanguageService()->getLL('staleTranslationWarning'),
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
     * Returns a QueryBuilder configured to select $fields from $table where the pid is restricted.
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
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->getBackendUser()->workspace));
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
     * @return QueryBuilder
     */
    protected function prepareQueryBuilder(
        string $table,
        int $pageId,
        array $fieldList,
        array $additionalConstraints,
        QueryBuilder $queryBuilder
    ): QueryBuilder {
        $parameters = [
            'table' => $table,
            'fields' => $fieldList,
            'groupBy' => null,
            'orderBy' => null,
        ];

        $sortBy = (string)($GLOBALS['TCA'][$table]['ctrl']['sortby'] ?: $GLOBALS['TCA'][$table]['ctrl']['default_sortby']);
        foreach (QueryHelper::parseOrderBy($sortBy) as $orderBy) {
            $queryBuilder->addOrderBy($orderBy[0], $orderBy[1]);
        }

        // Build the query constraints
        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                $table . '.pid',
                $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)
            )
        );

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

        return $queryBuilder;
    }

    /**
     * Renders the language flag and language title, but only if an icon is given, otherwise just the language
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
            return '<span title="' . $title . '" class="t3js-flag">' . $icon . '</span>&nbsp;<span class="t3js-language-title">' . $title . '</span>';
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
        try {
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($pageId);
        } catch (SiteNotFoundException $e) {
            $site = new NullSite();
        }
        $this->siteLanguages = $site->getAvailableLanguages($this->getBackendUser(), true, $pageId);
    }

    /**
     * @return string $title
     */
    protected function getLocalizedPageTitle(): string
    {
        if (($this->tt_contentConfig['sys_language_uid'] ?? 0) > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->getBackendUser()->workspace));
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
                ->executeQuery()
                ->fetchAssociative();
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
     * @param int|null $languageId
     * @return bool
     */
    protected function isContentEditable(?int $languageId = null)
    {
        if ($this->getBackendUser()->isAdmin()) {
            return true;
        }
        return !$this->pageinfo['editlock']
            && $this->hasContentModificationAndAccessPermissions()
            && ($languageId === null || $this->getBackendUser()->checkLanguageAccess($languageId));
    }

    /**
     * Check if current user has modification and access permissions for content set
     *
     * @return bool
     */
    protected function hasContentModificationAndAccessPermissions(): bool
    {
        return $this->getBackendUser()->check('tables_modify', 'tt_content')
            && $this->getBackendUser()->doesUserHaveAccess($this->pageinfo, Permission::CONTENT_EDIT);
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
