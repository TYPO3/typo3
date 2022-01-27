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

namespace TYPO3\CMS\Lowlevel\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Lowlevel\Database\QueryGenerator;
use TYPO3\CMS\Lowlevel\Integrity\DatabaseIntegrityCheck;

/**
 * "DB Check" module.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class DatabaseIntegrityController
{
    /**
     * The module menu items array.
     */
    protected array $MOD_MENU = [];

    /**
     * Current settings for the keys of the MOD_MENU array.
     */
    protected array $MOD_SETTINGS = [];

    protected IconFactory $iconFactory;
    protected UriBuilder $uriBuilder;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(
        IconFactory $iconFactory,
        UriBuilder $uriBuilder,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->iconFactory = $iconFactory;
        $this->uriBuilder = $uriBuilder;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $languageService->includeLLFile('EXT:lowlevel/Resources/Private/Language/locallang.xlf');

        $this->menuConfig($request);
        $moduleTemplate = $this->moduleTemplateFactory->create($request, 'typo3/cms-lowlevel');
        $this->setUpDocHeader($moduleTemplate);

        $title = $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab');
        switch ($this->MOD_SETTINGS['function']) {
            case 'search':
                $moduleTemplate->setTitle($title, $languageService->getLL('fullSearch'));
                return $this->searchAction($moduleTemplate);
            case 'records':
                $moduleTemplate->setTitle($title, $languageService->getLL('recordStatistics'));
                return $this->recordStatisticsAction($moduleTemplate, $request);
            case 'relations':
                $moduleTemplate->setTitle($title, $languageService->getLL('databaseRelations'));
                return $this->relationsAction($moduleTemplate);
            case 'refindex':
                $moduleTemplate->setTitle($title, $languageService->getLL('manageRefIndex'));
                return $this->referenceIndexAction($moduleTemplate, $request);
            default:
                $moduleTemplate->setTitle($title, $languageService->getLL('menuTitle'));
                return $this->overviewAction($moduleTemplate);
        }
    }

    /**
     * Configure menu
     */
    protected function menuConfig(ServerRequestInterface $request): void
    {
        $lang = $this->getLanguageService();
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        // MENU-ITEMS:
        // If array, then it's a selector box menu
        // If empty string it's just a variable, that'll be saved.
        // Values NOT in this array will not be saved in the settings-array for the module.
        $this->MOD_MENU = [
            'function' => [
                0 => htmlspecialchars($lang->getLL('menuTitle')),
                'records' => htmlspecialchars($lang->getLL('recordStatistics')),
                'relations' => htmlspecialchars($lang->getLL('databaseRelations')),
                'search' => htmlspecialchars($lang->getLL('fullSearch')),
                'refindex' => htmlspecialchars($lang->getLL('manageRefIndex')),
            ],
            'search' => [
                'raw' => htmlspecialchars($lang->getLL('rawSearch')),
                'query' => htmlspecialchars($lang->getLL('advancedQuery')),
            ],
            'search_query_smallparts' => '',
            'search_result_labels' => '',
            'labels_noprefix' => '',
            'options_sortlabel' => '',
            'show_deleted' => '',
            'queryConfig' => '',
            // Current query
            'queryTable' => '',
            // Current table
            'queryFields' => '',
            // Current tableFields
            'queryLimit' => '',
            // Current limit
            'queryOrder' => '',
            // Current Order field
            'queryOrderDesc' => '',
            // Current Order field descending flag
            'queryOrder2' => '',
            // Current Order2 field
            'queryOrder2Desc' => '',
            // Current Order2 field descending flag
            'queryGroup' => '',
            // Current Group field
            'storeArray' => '',
            // Used to store the available Query config memory banks
            'storeQueryConfigs' => '',
            // Used to store the available Query configs in memory
            'search_query_makeQuery' => [
                'all' => htmlspecialchars($lang->getLL('selectRecords')),
                'count' => htmlspecialchars($lang->getLL('countResults')),
                'explain' => htmlspecialchars($lang->getLL('explainQuery')),
                'csv' => htmlspecialchars($lang->getLL('csvExport')),
            ],
            'sword' => '',
        ];
        // CLEAN SETTINGS
        $OLD_MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, [], 'system_dbint', 'ses');
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, $parsedBody['SET'] ?? $queryParams['SET'] ?? [], 'system_dbint', 'ses');
        $queryConfig = $parsedBody['queryConfig'] ?? $queryParams['queryConfig'] ?? false;
        if ($queryConfig) {
            $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, ['queryConfig' => serialize($queryConfig)], 'system_dbint', 'ses');
        }
        $setLimitToStart = false;
        foreach ($OLD_MOD_SETTINGS as $key => $val) {
            if (strpos($key, 'query') === 0 && $this->MOD_SETTINGS[$key] != $val && $key !== 'queryLimit' && $key !== 'use_listview') {
                $setLimitToStart = true;
                $addConditionCheck = (bool)($parsedBody['qG_ins'] ?? $queryParams['qG_ins'] ?? false);
                if ($key === 'queryTable' && !$addConditionCheck) {
                    $this->MOD_SETTINGS['queryConfig'] = '';
                }
            }
            if ($key === 'queryTable' && $this->MOD_SETTINGS[$key] != $val) {
                $this->MOD_SETTINGS['queryFields'] = '';
            }
        }
        if ($setLimitToStart) {
            $currentLimit = explode(',', $this->MOD_SETTINGS['queryLimit']);
            if (!empty($currentLimit[1] ?? 0)) {
                $this->MOD_SETTINGS['queryLimit'] = '0,' . $currentLimit[1];
            } else {
                $this->MOD_SETTINGS['queryLimit'] = '0';
            }
            $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, $this->MOD_SETTINGS, 'system_dbint', 'ses');
        }
    }

    /**
     * Generate doc header drop-down and shortcut button.
     */
    protected function setUpDocHeader(ModuleTemplate $moduleTemplate): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $shortCutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('system_dbint')
            ->setDisplayName($this->MOD_MENU['function'][$this->MOD_SETTINGS['function']])
            ->setArguments([
                'SET' => [
                    'function' => $this->MOD_SETTINGS['function'] ?? '',
                    'search' => $this->MOD_SETTINGS['search'] ?? 'raw',
                    'search_query_makeQuery' => $this->MOD_SETTINGS['search_query_makeQuery'] ?? '',
                ],
            ]);
        $buttonBar->addButton($shortCutButton, ButtonBar::BUTTON_POSITION_RIGHT, 2);

        $menu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('DatabaseJumpMenu');
        foreach ($this->MOD_MENU['function'] as $controller => $title) {
            $item = $menu
                ->makeMenuItem()
                ->setHref(
                    (string)$this->uriBuilder->buildUriFromRoute(
                        'system_dbint',
                        [
                            'id' => 0,
                            'SET' => [
                                'function' => $controller,
                            ],
                        ]
                    )
                )
                ->setTitle($title);
            if ($controller === $this->MOD_SETTINGS['function']) {
                $item->setActive(true);
            }
            $menu->addMenuItem($item);
        }
        $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Creates the overview menu.
     */
    protected function overviewAction(ModuleTemplate $view): ResponseInterface
    {
        $view->assign(
            'availableFunctions',
            [
                'records' => (string)$this->uriBuilder->buildUriFromRoute('system_dbint', ['SET' => ['function' => 'records']]),
                'relations' => (string)$this->uriBuilder->buildUriFromRoute('system_dbint', ['SET' => ['function' => 'relations']]),
                'search' => (string)$this->uriBuilder->buildUriFromRoute('system_dbint', ['SET' => ['function' => 'search']]),
                'refindex' => (string)$this->uriBuilder->buildUriFromRoute('system_dbint', ['SET' => ['function' => 'refindex']]),
            ]
        );
        return $view->renderResponse('IntegrityOverview');
    }

    /**
     * Check and update reference index.
     */
    protected function referenceIndexAction(ModuleTemplate $view, ServerRequestInterface $request): ResponseInterface
    {
        $isUpdate = $request->getParsedBody()['update'] ?? false;
        $isCheckOnly = $request->getParsedBody()['checkOnly'] ?? false;
        $referenceIndexResult = [];
        if ($isUpdate || $isCheckOnly) {
            $referenceIndexResult = GeneralUtility::makeInstance(ReferenceIndex::class)->updateIndex($isCheckOnly);
        }
        $readmeLocation = ExtensionManagementUtility::extPath('lowlevel', 'README.rst');
        $view->assignMultiple([
            'ReadmeLink' => PathUtility::getAbsoluteWebPath($readmeLocation),
            'ReadmeLocation' => $readmeLocation,
            'binaryPath' => ExtensionManagementUtility::extPath('core', 'bin/typo3'),
            'referenceIndexResult' => $referenceIndexResult,
        ]);
        return $view->renderResponse('ReferenceIndex');
    }

    /**
     * Search (Full / Advanced)
     */
    protected function searchAction(ModuleTemplate $view): ResponseInterface
    {
        $lang = $this->getLanguageService();
        $searchMode = $this->MOD_SETTINGS['search'];
        $fullSearch = GeneralUtility::makeInstance(QueryGenerator::class, $this->MOD_SETTINGS, $this->MOD_MENU, 'system_dbint');
        $fullSearch->setFormName('queryform');
        $submenu = '<div class="row row-cols-auto align-items-end g-3 mb-3">';
        $submenu .= '<div class="col">' . BackendUtility::getDropdownMenu(0, 'SET[search]', $searchMode, $this->MOD_MENU['search']) . '</div>';
        if ($this->MOD_SETTINGS['search'] === 'query') {
            $submenu .= '<div class="col">' . BackendUtility::getDropdownMenu(0, 'SET[search_query_makeQuery]', $this->MOD_SETTINGS['search_query_makeQuery'], $this->MOD_MENU['search_query_makeQuery']) . '</div>';
        }
        $submenu .= '</div>';
        if ($this->MOD_SETTINGS['search'] === 'query') {
            $submenu .= '<div class="form-check">' . BackendUtility::getFuncCheck(0, 'SET[search_query_smallparts]', $this->MOD_SETTINGS['search_query_smallparts'] ?? '', '', '', 'id="checkSearch_query_smallparts"') . '<label class="form-check-label" for="checkSearch_query_smallparts">' . $lang->getLL('showSQL') . '</label></div>';
            $submenu .= '<div class="form-check">' . BackendUtility::getFuncCheck(0, 'SET[search_result_labels]', $this->MOD_SETTINGS['search_result_labels'] ?? '', '', '', 'id="checkSearch_result_labels"') . '<label class="form-check-label" for="checkSearch_result_labels">' . $lang->getLL('useFormattedStrings') . '</label></div>';
            $submenu .= '<div class="form-check">' . BackendUtility::getFuncCheck(0, 'SET[labels_noprefix]', $this->MOD_SETTINGS['labels_noprefix'] ?? '', '', '', 'id="checkLabels_noprefix"') . '<label class="form-check-label" for="checkLabels_noprefix">' . $lang->getLL('dontUseOrigValues') . '</label></div>';
            $submenu .= '<div class="form-check">' . BackendUtility::getFuncCheck(0, 'SET[options_sortlabel]', $this->MOD_SETTINGS['options_sortlabel'] ?? '', '', '', 'id="checkOptions_sortlabel"') . '<label class="form-check-label" for="checkOptions_sortlabel">' . $lang->getLL('sortOptions') . '</label></div>';
            $submenu .= '<div class="form-check">' . BackendUtility::getFuncCheck(0, 'SET[show_deleted]', $this->MOD_SETTINGS['show_deleted'] ?? 0, '', '', 'id="checkShow_deleted"') . '<label class="form-check-label" for="checkShow_deleted">' . $lang->getLL('showDeleted') . '</label></div>';
        }
        $view->assign('submenu', $submenu);
        $view->assign('searchMode', $searchMode);
        switch ($searchMode) {
            case 'query':
                $view->assign('queryMaker', $fullSearch->queryMaker());
                break;
            case 'raw':
            default:
                $view->assign('searchOptions', $fullSearch->form());
                $view->assign('results', $fullSearch->search());
        }
        return $view->renderResponse('CustomSearch');
    }

    /**
     * Records overview
     */
    protected function recordStatisticsAction(ModuleTemplate $view, ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $databaseIntegrityCheck = GeneralUtility::makeInstance(DatabaseIntegrityCheck::class);
        $databaseIntegrityCheck->genTree(0);

        // Page stats
        $pageStatistic = [
            'total_pages' => [
                'icon' => $this->iconFactory->getIconForRecord('pages', [], Icon::SIZE_SMALL)->render(),
                'count' => count($databaseIntegrityCheck->getPageIdArray()),
            ],
            'translated_pages' => [
                'icon' => $this->iconFactory->getIconForRecord('pages', [], Icon::SIZE_SMALL)->render(),
                'count' => count($databaseIntegrityCheck->getPageTranslatedPageIDArray()),
            ],
            'hidden_pages' => [
                'icon' => $this->iconFactory->getIconForRecord('pages', ['hidden' => 1], Icon::SIZE_SMALL)->render(),
                'count' => $databaseIntegrityCheck->getRecStats()['hidden'] ?? 0,
            ],
            'deleted_pages' => [
                'icon' => $this->iconFactory->getIconForRecord('pages', ['deleted' => 1], Icon::SIZE_SMALL)->render(),
                'count' => isset($databaseIntegrityCheck->getRecStats()['deleted']['pages']) ? count($databaseIntegrityCheck->getRecStats()['deleted']['pages']) : 0,
            ],
        ];

        // doktypes stats
        $doktypes = [];
        $doktype = $GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'];
        if (is_array($doktype)) {
            foreach ($doktype as $setup) {
                if ($setup[1] !== '--div--') {
                    $doktypes[] = [
                        'icon' => $this->iconFactory->getIconForRecord('pages', ['doktype' => $setup[1]], Icon::SIZE_SMALL)->render(),
                        'title' => $languageService->sL($setup[0]) . ' (' . $setup[1] . ')',
                        'count' => (int)($databaseIntegrityCheck->getRecStats()['doktype'][$setup[1]] ?? 0),
                    ];
                }
            }
        }

        // Tables and lost records
        $id_list = '-1,0,' . implode(',', array_keys($databaseIntegrityCheck->getPageIdArray()));
        $id_list = rtrim($id_list, ',');
        $databaseIntegrityCheck->lostRecords($id_list);

        // Fix a lost record if requested
        $fixSingleLostRecordTableName = (string)($request->getQueryParams()['fixLostRecords_table'] ?? '');
        $fixSingleLostRecordUid = (int)($request->getQueryParams()['fixLostRecords_uid'] ?? 0);
        if (!empty($fixSingleLostRecordTableName) && $fixSingleLostRecordUid
            && $databaseIntegrityCheck->fixLostRecord($fixSingleLostRecordTableName, $fixSingleLostRecordUid)
        ) {
            $databaseIntegrityCheck = GeneralUtility::makeInstance(DatabaseIntegrityCheck::class);
            $databaseIntegrityCheck->genTree(0);
            $id_list = '-1,0,' . implode(',', array_keys($databaseIntegrityCheck->getPageIdArray()));
            $id_list = rtrim($id_list, ',');
            $databaseIntegrityCheck->lostRecords($id_list);
        }

        $tableStatistic = [];
        $countArr = $databaseIntegrityCheck->countRecords($id_list);
        if (is_array($GLOBALS['TCA'])) {
            foreach ($GLOBALS['TCA'] as $t => $value) {
                if ($GLOBALS['TCA'][$t]['ctrl']['hideTable'] ?? false) {
                    continue;
                }
                if ($t === 'pages' && $databaseIntegrityCheck->getLostPagesList() !== '') {
                    $lostRecordCount = count(explode(',', $databaseIntegrityCheck->getLostPagesList()));
                } else {
                    $lostRecordCount = isset($databaseIntegrityCheck->getLRecords()[$t]) ? count($databaseIntegrityCheck->getLRecords()[$t]) : 0;
                }
                $recordCount = 0;
                if ($countArr['all'][$t] ?? false) {
                    $recordCount = (int)($countArr['non_deleted'][$t] ?? 0) . '/' . $lostRecordCount;
                }
                $lostRecordList = [];
                if (is_array($databaseIntegrityCheck->getLRecords()[$t] ?? false)) {
                    foreach ($databaseIntegrityCheck->getLRecords()[$t] as $data) {
                        if (!GeneralUtility::inList($databaseIntegrityCheck->getLostPagesList(), $data['pid'])) {
                            $fixLink = (string)$this->uriBuilder->buildUriFromRoute(
                                'system_dbint',
                                ['SET' => ['function' => 'records'], 'fixLostRecords_table' => $t, 'fixLostRecords_uid' => $data['uid']]
                            );
                            $lostRecordList[] =
                                '<div class="record">' .
                                    '<a href="' . htmlspecialchars($fixLink) . '" title="' . htmlspecialchars($languageService->getLL('fixLostRecord')) . '">' .
                                        $this->iconFactory->getIcon('status-dialog-error', Icon::SIZE_SMALL)->render() .
                                    '</a>uid:' . $data['uid'] . ', pid:' . $data['pid'] . ', ' . htmlspecialchars(GeneralUtility::fixed_lgd_cs(strip_tags($data['title']), 20)) .
                                '</div>';
                        } else {
                            $lostRecordList[] =
                                '<div class="record-noicon">' .
                                    'uid:' . $data['uid'] . ', pid:' . $data['pid'] . ', ' . htmlspecialchars(GeneralUtility::fixed_lgd_cs(strip_tags($data['title']), 20)) .
                                '</div>';
                        }
                    }
                }
                $tableStatistic[$t] = [
                    'icon' => $this->iconFactory->getIconForRecord($t, [], Icon::SIZE_SMALL)->render(),
                    'title' => $languageService->sL($GLOBALS['TCA'][$t]['ctrl']['title']),
                    'count' => $recordCount,
                    'lostRecords' => implode(LF, $lostRecordList),
                ];
            }
        }

        $view->assignMultiple([
            'pages' => $pageStatistic,
            'doktypes' => $doktypes,
            'tables' => $tableStatistic,
        ]);
        return $view->renderResponse('RecordStatistics');
    }

    /**
     * Show reference list
     */
    protected function relationsAction(ModuleTemplate $view): ResponseInterface
    {
        $databaseIntegrityCheck = GeneralUtility::makeInstance(DatabaseIntegrityCheck::class);
        $databaseIntegrityCheck->selectNonEmptyRecordsWithFkeys();
        $view->assignMultiple([
            'select_db' => $databaseIntegrityCheck->testDBRefs($databaseIntegrityCheck->getCheckSelectDBRefs()),
            'group_db' => $databaseIntegrityCheck->testDBRefs($databaseIntegrityCheck->getCheckGroupDBRefs()),
        ]);
        return $view->renderResponse('Relations');
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
