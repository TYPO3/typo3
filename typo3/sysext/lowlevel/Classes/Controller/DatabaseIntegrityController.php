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

namespace TYPO3\CMS\Lowlevel\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageRendererResolver;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Lowlevel\Database\QueryGenerator;
use TYPO3\CMS\Lowlevel\Integrity\DatabaseIntegrityCheck;

/**
 * Script class for the DB int module
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class DatabaseIntegrityController
{
    /**
     * @var string
     */
    protected $formName = 'queryform';

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'system_dbint';

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * @var string
     */
    protected $templatePath = 'EXT:lowlevel/Resources/Private/Templates/Backend/';

    /**
     * ModuleTemplate Container
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * The module menu items array. Each key represents a key for which values can range between the items in the array of that key.
     *
     * @see init()
     * @var array
     */
    protected $MOD_MENU = [
        'function' => [],
    ];

    /**
     * Current settings for the keys of the MOD_MENU array
     *
     * @var array
     */
    protected $MOD_SETTINGS = [];

    protected IconFactory $iconFactory;
    protected PageRenderer $pageRenderer;
    protected UriBuilder $uriBuilder;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(
        IconFactory $iconFactory,
        PageRenderer $pageRenderer,
        UriBuilder $uriBuilder,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->iconFactory = $iconFactory;
        $this->pageRenderer = $pageRenderer;
        $this->uriBuilder = $uriBuilder;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * Injects the request object for the current request or subrequest
     * Simply calls main() and init() and outputs the content
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->getLanguageService()->includeLLFile('EXT:lowlevel/Resources/Private/Language/locallang.xlf');
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->getRequest()->setControllerExtensionName('lowlevel');

        $this->menuConfig();
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);

        switch ($this->MOD_SETTINGS['function']) {
            case 'search':
                $title = $this->getLanguageService()->getLL('fullSearch');
                $templateFilename = 'CustomSearch.html';
                $this->func_search();
                break;
            case 'records':
                $title = $this->getLanguageService()->getLL('recordStatistics');
                $templateFilename = 'RecordStatistics.html';
                $this->func_records();
                break;
            case 'relations':
                $title = $this->getLanguageService()->getLL('databaseRelations');
                $templateFilename = 'Relations.html';
                $this->func_relations();
                break;
            case 'refindex':
                $title = $this->getLanguageService()->getLL('manageRefIndex');
                $templateFilename = 'ReferenceIndex.html';
                $this->func_refindex();
                break;
            default:
                $title = $this->getLanguageService()->getLL('menuTitle');
                $templateFilename = 'IntegrityOverview.html';
                $this->func_default();
        }
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($this->templatePath . $templateFilename));
        $content = '<form action="" method="post" id="DatabaseIntegrityView" name="' . $this->formName . '">';
        $content .= $this->view->render();
        $content .= '</form>';

        // Setting up the shortcut button for docheader
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        // Shortcut
        $shortCutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier($this->moduleName)
            ->setDisplayName($this->MOD_MENU['function'][$this->MOD_SETTINGS['function']])
            ->setArguments([
                'SET' => [
                    'function' => $this->MOD_SETTINGS['function'] ?? '',
                    'search' => $this->MOD_SETTINGS['search'] ?? 'raw',
                    'search_query_makeQuery' => $this->MOD_SETTINGS['search_query_makeQuery'] ?? '',
                ],
            ]);
        $buttonBar->addButton($shortCutButton, ButtonBar::BUTTON_POSITION_RIGHT, 2);

        $this->getModuleMenu();

        $this->moduleTemplate->setContent($content);
        $this->moduleTemplate->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
            $title
        );
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Configure menu
     */
    protected function menuConfig()
    {
        $lang = $this->getLanguageService();
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
        $OLD_MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, [], $this->moduleName, 'ses');
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), $this->moduleName, 'ses');
        if (GeneralUtility::_GP('queryConfig')) {
            $qA = GeneralUtility::_GP('queryConfig');
            $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, ['queryConfig' => serialize($qA)], $this->moduleName, 'ses');
        }
        $addConditionCheck = GeneralUtility::_GP('qG_ins');
        $setLimitToStart = false;
        foreach ($OLD_MOD_SETTINGS as $key => $val) {
            if (strpos($key, 'query') === 0 && $this->MOD_SETTINGS[$key] != $val && $key !== 'queryLimit' && $key !== 'use_listview') {
                $setLimitToStart = true;
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
            $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, $this->MOD_SETTINGS, $this->moduleName, 'ses');
        }
    }

    /**
     * Generates the action menu
     */
    protected function getModuleMenu()
    {
        $menu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('DatabaseJumpMenu');
        foreach ($this->MOD_MENU['function'] as $controller => $title) {
            $item = $menu
                ->makeMenuItem()
                ->setHref(
                    (string)$this->uriBuilder->buildUriFromRoute(
                        $this->moduleName,
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
        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Creates the overview menu.
     */
    protected function func_default()
    {
        $modules = [];
        $availableModFuncs = ['records', 'relations', 'search', 'refindex'];
        foreach ($availableModFuncs as $modFunc) {
            $modules[$modFunc] = (string)$this->uriBuilder->buildUriFromRoute('system_dbint', ['SET' => ['function' => $modFunc]]);
        }
        $this->view->assign('availableFunctions', $modules);
    }

    /****************************
     *
     * Functionality implementation
     *
     ****************************/
    /**
     * Check and update reference index!
     */
    protected function func_refindex()
    {
        $readmeLocation = ExtensionManagementUtility::extPath('lowlevel', 'README.rst');
        $this->view->assign('ReadmeLink', PathUtility::getAbsoluteWebPath($readmeLocation));
        $this->view->assign('ReadmeLocation', $readmeLocation);
        $this->view->assign('binaryPath', ExtensionManagementUtility::extPath('core', 'bin/typo3'));
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Lowlevel/ReferenceIndex');

        if (GeneralUtility::_GP('_update') || GeneralUtility::_GP('_check')) {
            $testOnly = (bool)GeneralUtility::_GP('_check');
            $refIndexObj = GeneralUtility::makeInstance(ReferenceIndex::class);
            $result = $refIndexObj->updateIndex($testOnly);
            $recordsCheckedString = $result['resultText'];
            $errors = $result['errors'];
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                !empty($errors) ? implode("\n", $errors) : 'Index Integrity was perfect!',
                $recordsCheckedString,
                !empty($errors) ? FlashMessage::ERROR : FlashMessage::OK
            );

            $flashMessageRenderer = GeneralUtility::makeInstance(FlashMessageRendererResolver::class)->resolve();
            $bodyContent = $flashMessageRenderer->render([$flashMessage]);

            $this->view->assign('content', nl2br($bodyContent));
        }
    }

    /**
     * Search (Full / Advanced)
     */
    protected function func_search()
    {
        $lang = $this->getLanguageService();
        $searchMode = $this->MOD_SETTINGS['search'];
        $fullsearch = GeneralUtility::makeInstance(QueryGenerator::class, $this->MOD_SETTINGS, $this->MOD_MENU, $this->moduleName);
        $fullsearch->setFormName($this->formName);
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
        $this->view->assign('submenu', $submenu);
        $this->view->assign('searchMode', $searchMode);
        switch ($searchMode) {
            case 'query':
                $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Lowlevel/QueryGenerator');
                $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/DateTimePicker');
                $this->view->assign('queryMaker', $fullsearch->queryMaker());
                break;
            case 'raw':
            default:
                $this->view->assign('searchOptions', $fullsearch->form());
                $this->view->assign('results', $fullsearch->search());
        }
    }

    /**
     * Records overview
     */
    protected function func_records()
    {
        $admin = GeneralUtility::makeInstance(DatabaseIntegrityCheck::class);
        $admin->genTree(0);

        // Pages stat
        $pageStatistic = [
            'total_pages' => [
                'icon' => $this->iconFactory->getIconForRecord('pages', [], Icon::SIZE_SMALL)->render(),
                'count' => count($admin->getPageIdArray()),
            ],
            'translated_pages' => [
                'icon' => $this->iconFactory->getIconForRecord('pages', [], Icon::SIZE_SMALL)->render(),
                'count' => count($admin->getPageTranslatedPageIDArray()),
            ],
            'hidden_pages' => [
                'icon' => $this->iconFactory->getIconForRecord('pages', ['hidden' => 1], Icon::SIZE_SMALL)->render(),
                'count' => $admin->getRecStats()['hidden'] ?? 0,
            ],
            'deleted_pages' => [
                'icon' => $this->iconFactory->getIconForRecord('pages', ['deleted' => 1], Icon::SIZE_SMALL)->render(),
                'count' => isset($admin->getRecStats()['deleted']['pages']) ? count($admin->getRecStats()['deleted']['pages']) : 0,
            ],
        ];

        $lang = $this->getLanguageService();

        // Doktype
        $doktypes = [];
        $doktype = $GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'];
        if (is_array($doktype)) {
            foreach ($doktype as $setup) {
                if ($setup[1] !== '--div--') {
                    $doktypes[] = [
                        'icon' => $this->iconFactory->getIconForRecord('pages', ['doktype' => $setup[1]], Icon::SIZE_SMALL)->render(),
                        'title' => $lang->sL($setup[0]) . ' (' . $setup[1] . ')',
                        'count' => (int)($admin->getRecStats()['doktype'][$setup[1]] ?? 0),
                    ];
                }
            }
        }

        // Tables and lost records
        $id_list = '-1,0,' . implode(',', array_keys($admin->getPageIdArray()));
        $id_list = rtrim($id_list, ',');
        $admin->lostRecords($id_list);
        if ($admin->fixLostRecord(GeneralUtility::_GET('fixLostRecords_table'), GeneralUtility::_GET('fixLostRecords_uid'))) {
            $admin = GeneralUtility::makeInstance(DatabaseIntegrityCheck::class);
            $admin->genTree(0);
            $id_list = '-1,0,' . implode(',', array_keys($admin->getPageIdArray()));
            $id_list = rtrim($id_list, ',');
            $admin->lostRecords($id_list);
        }
        $tableStatistic = [];
        $countArr = $admin->countRecords($id_list);
        if (is_array($GLOBALS['TCA'])) {
            foreach ($GLOBALS['TCA'] as $t => $value) {
                if ($GLOBALS['TCA'][$t]['ctrl']['hideTable'] ?? false) {
                    continue;
                }
                if ($t === 'pages' && $admin->getLostPagesList() !== '') {
                    $lostRecordCount = count(explode(',', $admin->getLostPagesList()));
                } else {
                    $lostRecordCount = isset($admin->getLRecords()[$t]) ? count($admin->getLRecords()[$t]) : 0;
                }
                if ($countArr['all'][$t] ?? false) {
                    $theNumberOfRe = (int)($countArr['non_deleted'][$t] ?? 0) . '/' . $lostRecordCount;
                } else {
                    $theNumberOfRe = '';
                }
                $lr = '';
                if (is_array($admin->getLRecords()[$t] ?? false)) {
                    foreach ($admin->getLRecords()[$t] as $data) {
                        if (!GeneralUtility::inList($admin->getLostPagesList(), $data['pid'])) {
                            $lr .= '<div class="record"><a href="' . htmlspecialchars((string)$this->uriBuilder->buildUriFromRoute('system_dbint', ['SET' => ['function' => 'records'], 'fixLostRecords_table' => $t, 'fixLostRecords_uid' => $data['uid']])) . '" title="' . htmlspecialchars($lang->getLL('fixLostRecord')) . '">' . $this->iconFactory->getIcon('status-dialog-error', Icon::SIZE_SMALL)->render() . '</a>uid:' . $data['uid'] . ', pid:' . $data['pid'] . ', ' . htmlspecialchars(GeneralUtility::fixed_lgd_cs(strip_tags($data['title']), 20)) . '</div>';
                        } else {
                            $lr .= '<div class="record-noicon">uid:' . $data['uid'] . ', pid:' . $data['pid'] . ', ' . htmlspecialchars(GeneralUtility::fixed_lgd_cs(strip_tags($data['title']), 20)) . '</div>';
                        }
                    }
                }
                $tableStatistic[$t] = [
                    'icon' => $this->iconFactory->getIconForRecord($t, [], Icon::SIZE_SMALL)->render(),
                    'title' => $lang->sL($GLOBALS['TCA'][$t]['ctrl']['title']),
                    'count' => $theNumberOfRe,
                    'lostRecords' => $lr,
                ];
            }
        }

        $this->view->assignMultiple([
            'pages' => $pageStatistic,
            'doktypes' => $doktypes,
            'tables' => $tableStatistic,
        ]);
    }

    /**
     * Show list references
     */
    protected function func_relations()
    {
        $admin = GeneralUtility::makeInstance(DatabaseIntegrityCheck::class);
        $admin->selectNonEmptyRecordsWithFkeys();

        $this->view->assignMultiple([
            'select_db' => $admin->testDBRefs($admin->getCheckSelectDBRefs()),
            'group_db' => $admin->testDBRefs($admin->getCheckGroupDBRefs()),
        ]);
    }

    /**
     * Returns the Language Service
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
