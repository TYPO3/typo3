<?php
namespace TYPO3\CMS\Lowlevel\View;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\BaseScriptClass;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\QueryView;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Integrity\DatabaseIntegrityCheck;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Script class for the DB int module
 */
class DatabaseIntegrityView extends BaseScriptClass
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
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * ModuleTemplate Container
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->getLanguageService()->includeLLFile('EXT:lowlevel/Resources/Private/Language/locallang.xlf');
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->getRequest()->setControllerExtensionName('lowlevel');
    }

    /**
     * Initialization
     *
     * @return void
     */
    public function init()
    {
        $this->MCONF['name'] = $this->moduleName;
        $this->menuConfig();
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->moduleTemplate->addJavaScriptCode(
            'jumpToUrl',
            '
            function jumpToUrl(URL) {
                window.location.href = URL;
                return false;
            }
            '
        );
    }

    /**
     * Configure menu
     *
     * @return void
     */
    public function menuConfig()
    {
        $lang = $this->getLanguageService();
        // MENU-ITEMS:
        // If array, then it's a selector box menu
        // If empty string it's just a variable, that'll be saved.
        // Values NOT in this array will not be saved in the settings-array for the module.
        $this->MOD_MENU = [
            'function' => [
                0 => $lang->getLL('menuTitle', true),
                'records' => $lang->getLL('recordStatistics', true),
                'relations' => $lang->getLL('databaseRelations', true),
                'search' => $lang->getLL('fullSearch', true),
                'refindex' => $lang->getLL('manageRefIndex', true)
            ],
            'search' => [
                'raw' => $lang->getLL('rawSearch', true),
                'query' => $lang->getLL('advancedQuery', true)
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
                'all' => $lang->getLL('selectRecords', true),
                'count' => $lang->getLL('countResults', true),
                'explain' => $lang->getLL('explainQuery', true),
                'csv' => $lang->getLL('csvExport', true)
            ],
            'sword' => ''
        ];
        // CLEAN SETTINGS
        $OLD_MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, '', $this->moduleName, 'ses');
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), $this->moduleName, 'ses');
        if (GeneralUtility::_GP('queryConfig')) {
            $qA = GeneralUtility::_GP('queryConfig');
            $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, ['queryConfig' => serialize($qA)], $this->moduleName, 'ses');
        }
        $addConditionCheck = GeneralUtility::_GP('qG_ins');
        $setLimitToStart = false;
        foreach ($OLD_MOD_SETTINGS as $key => $val) {
            if (substr($key, 0, 5) == 'query' && $this->MOD_SETTINGS[$key] != $val && $key != 'queryLimit' && $key != 'use_listview') {
                $setLimitToStart = true;
                if ($key == 'queryTable' && !$addConditionCheck) {
                    $this->MOD_SETTINGS['queryConfig'] = '';
                }
            }
            if ($key == 'queryTable' && $this->MOD_SETTINGS[$key] != $val) {
                $this->MOD_SETTINGS['queryFields'] = '';
            }
        }
        if ($setLimitToStart) {
            $currentLimit = explode(',', $this->MOD_SETTINGS['queryLimit']);
            if ($currentLimit[1]) {
                $this->MOD_SETTINGS['queryLimit'] = '0,' . $currentLimit[1];
            } else {
                $this->MOD_SETTINGS['queryLimit'] = '0';
            }
            $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, $this->MOD_SETTINGS, $this->moduleName, 'ses');
        }
    }

    /**
     * Main functions, is rendering the content
     *
     * @return void
     */
    public function main()
    {
        switch ($this->MOD_SETTINGS['function']) {
            case 'search':
                $templateFilename = 'CustomSearch.html';
                $this->func_search();
                break;
            case 'records':
                $templateFilename = 'RecordStatistics.html';
                $this->func_records();
                break;
            case 'relations':
                $templateFilename = 'Relations.html';
                $this->func_relations();
                break;
            case 'refindex':
                $templateFilename = 'ReferenceIndex.html';
                $this->func_refindex();
                break;
            default:
                $templateFilename = 'IntegrityOverview.html';
                $this->func_default();
        }
        $this->view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($this->templatePath . $templateFilename));
        $this->content = '<form action="" method="post" id="DatabaseIntegrityView" name="' . $this->formName . '">';
        $this->content .= $this->view->render();
        $this->content .= '</form>';

        // Setting up the shortcut button for docheader
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        // Shortcut
        $shortCutButton = $buttonBar->makeShortcutButton()
            ->setModuleName($this->moduleName)
            ->setDisplayName($this->MOD_MENU['function'][$this->MOD_SETTINGS['function']])
            ->setSetVariables(['function', 'search', 'search_query_makeQuery']);
        $buttonBar->addButton($shortCutButton, ButtonBar::BUTTON_POSITION_RIGHT, 2);

        $this->getModuleMenu();
    }

    /**
     * Print content
     *
     * @return void
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public function printContent()
    {
        GeneralUtility::logDeprecatedFunction();
        echo $this->content;
    }

    /**
     * Injects the request object for the current request or subrequest
     * Simply calls main() and init() and outputs the content
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $GLOBALS['SOBE'] = $this;
        $this->init();
        $this->main();

        $this->moduleTemplate->setContent($this->content);
        $response->getBody()->write($this->moduleTemplate->renderContent());
        return $response;
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
                    BackendUtility::getModuleUrl(
                        $this->moduleName,
                        [
                            'id' => $this->id,
                            'SET' => [
                                'function' => $controller
                            ]
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
     *
     * @return void
     */
    protected function func_default()
    {
        $modules = [];
        $availableModFuncs = ['records', 'relations', 'search', 'refindex'];
        foreach ($availableModFuncs as $modFunc) {
            $modules[$modFunc] = BackendUtility::getModuleUrl('system_dbint') . '&SET[function]=' . $modFunc;
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
     *
     * @return void
     */
    public function func_refindex()
    {
        $this->view->assign('PATH_typo3', PATH_typo3);

        if (GeneralUtility::_GP('_update') || GeneralUtility::_GP('_check')) {
            $testOnly = (bool)GeneralUtility::_GP('_check');
            // Call the functionality
            $refIndexObj = GeneralUtility::makeInstance(ReferenceIndex::class);
            list(, $bodyContent) = $refIndexObj->updateIndex($testOnly);
            $this->view->assign('content', str_replace('##LF##', '<br />', $bodyContent));
        }
    }

    /**
     * Search (Full / Advanced)
     *
     * @return void
     */
    public function func_search()
    {
        $lang = $this->getLanguageService();
        $searchMode = $this->MOD_SETTINGS['search'];
        $fullsearch = GeneralUtility::makeInstance(QueryView::class);
        $fullsearch->setFormName($this->formName);
        $submenu = '<div class="form-inline form-inline-spaced">';
        $submenu .= BackendUtility::getDropdownMenu(0, 'SET[search]', $searchMode, $this->MOD_MENU['search']);
        if ($this->MOD_SETTINGS['search'] == 'query') {
            $submenu .= BackendUtility::getDropdownMenu(0, 'SET[search_query_makeQuery]', $this->MOD_SETTINGS['search_query_makeQuery'], $this->MOD_MENU['search_query_makeQuery']) . '<br />';
        }
        $submenu .= '</div>';
        if ($this->MOD_SETTINGS['search'] == 'query') {
            $submenu .= '<div class="checkbox"><label for="checkSearch_query_smallparts">' . BackendUtility::getFuncCheck($GLOBALS['SOBE']->id, 'SET[search_query_smallparts]', $this->MOD_SETTINGS['search_query_smallparts'], '', '', 'id="checkSearch_query_smallparts"') . $lang->getLL('showSQL') . '</label></div>';
            $submenu .= '<div class="checkbox"><label for="checkSearch_result_labels">' . BackendUtility::getFuncCheck($GLOBALS['SOBE']->id, 'SET[search_result_labels]', $this->MOD_SETTINGS['search_result_labels'], '', '', 'id="checkSearch_result_labels"') . $lang->getLL('useFormattedStrings') . '</label></div>';
            $submenu .= '<div class="checkbox"><label for="checkLabels_noprefix">' . BackendUtility::getFuncCheck($GLOBALS['SOBE']->id, 'SET[labels_noprefix]', $this->MOD_SETTINGS['labels_noprefix'], '', '', 'id="checkLabels_noprefix"') . $lang->getLL('dontUseOrigValues') . '</label></div>';
            $submenu .= '<div class="checkbox"><label for="checkOptions_sortlabel">' . BackendUtility::getFuncCheck($GLOBALS['SOBE']->id, 'SET[options_sortlabel]', $this->MOD_SETTINGS['options_sortlabel'], '', '', 'id="checkOptions_sortlabel"') . $lang->getLL('sortOptions') . '</label></div>';
            $submenu .= '<div class="checkbox"><label for="checkShow_deleted">' . BackendUtility::getFuncCheck($GLOBALS['SOBE']->id, 'SET[show_deleted]', $this->MOD_SETTINGS['show_deleted'], '', '', 'id="checkShow_deleted"') . $lang->getLL('showDeleted') . '</label></div>';
        }
        $this->view->assign('submenu', $submenu);
        $this->view->assign('searchMode', $searchMode);
        switch ($searchMode) {
            case 'query':
                $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Core/QueryGenerator');
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
     *
     * @return void
     */
    public function func_records()
    {
        /** @var $admin DatabaseIntegrityCheck */
        $admin = GeneralUtility::makeInstance(DatabaseIntegrityCheck::class);
        $admin->genTree(0);

        // Pages stat
        $pageStatistic = [
            'total_pages' => [
                'icon' => $this->iconFactory->getIconForRecord('pages', [], Icon::SIZE_SMALL)->render(),
                'count' => count($admin->page_idArray)
            ],
            'hidden_pages' => [
                'icon' => $this->iconFactory->getIconForRecord('pages', ['hidden' => 1], Icon::SIZE_SMALL)->render(),
                'count' => $admin->recStats['hidden']
            ],
            'deleted_pages' => [
                'icon' => $this->iconFactory->getIconForRecord('pages', ['deleted' => 1], Icon::SIZE_SMALL)->render(),
                'count' => count($admin->recStats['deleted']['pages'])
            ]
        ];

        $lang = $this->getLanguageService();

        // Doktype
        $doktypes = [];
        $doktype = $GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'];
        if (is_array($doktype)) {
            foreach ($doktype as $setup) {
                if ($setup[1] != '--div--') {
                    $doktypes[] = [
                        'icon' => $this->iconFactory->getIconForRecord('pages', ['doktype' => $setup[1]], Icon::SIZE_SMALL)->render(),
                        'title' => $lang->sL($setup[0]) . ' (' . $setup[1] . ')',
                        'count' => (int)$admin->recStats['doktype'][$setup[1]]
                    ];
                }
            }
        }

        // Tables and lost records
        $id_list = '-1,0,' . implode(',', array_keys($admin->page_idArray));
        $id_list = rtrim($id_list, ',');
        $admin->lostRecords($id_list);
        if ($admin->fixLostRecord(GeneralUtility::_GET('fixLostRecords_table'), GeneralUtility::_GET('fixLostRecords_uid'))) {
            $admin = GeneralUtility::makeInstance(DatabaseIntegrityCheck::class);
            $admin->genTree(0);
            $id_list = '-1,0,' . implode(',', array_keys($admin->page_idArray));
            $id_list = rtrim($id_list, ',');
            $admin->lostRecords($id_list);
        }
        $tableStatistic = [];
        $countArr = $admin->countRecords($id_list);
        if (is_array($GLOBALS['TCA'])) {
            foreach ($GLOBALS['TCA'] as $t => $value) {
                if ($GLOBALS['TCA'][$t]['ctrl']['hideTable']) {
                    continue;
                }
                if ($t === 'pages' && $admin->lostPagesList !== '') {
                    $lostRecordCount = count(explode(',', $admin->lostPagesList));
                } else {
                    $lostRecordCount = count($admin->lRecords[$t]);
                }
                if ($countArr['all'][$t]) {
                    $theNumberOfRe = (int)$countArr['non_deleted'][$t] . '/' . $lostRecordCount;
                } else {
                    $theNumberOfRe = '';
                }
                $lr = '';
                if (is_array($admin->lRecords[$t])) {
                    foreach ($admin->lRecords[$t] as $data) {
                        if (!GeneralUtility::inList($admin->lostPagesList, $data['pid'])) {
                            $lr .= '<div class="record"><a href="' . htmlspecialchars((BackendUtility::getModuleUrl('system_dbint') . '&SET[function]=records&fixLostRecords_table=' . $t . '&fixLostRecords_uid=' . $data['uid'])) . '" title="' . $lang->getLL('fixLostRecord', true) . '">' . $this->iconFactory->getIcon('status-dialog-error', Icon::SIZE_SMALL)->render() . '</a>uid:' . $data['uid'] . ', pid:' . $data['pid'] . ', ' . htmlspecialchars(GeneralUtility::fixed_lgd_cs(strip_tags($data['title']), 20)) . '</div>';
                        } else {
                            $lr .= '<div class="record-noicon">uid:' . $data['uid'] . ', pid:' . $data['pid'] . ', ' . htmlspecialchars(GeneralUtility::fixed_lgd_cs(strip_tags($data['title']), 20)) . '</div>';
                        }
                    }
                }
                $tableStatistic[$t] = [
                    'icon' => $this->iconFactory->getIconForRecord($t, [], Icon::SIZE_SMALL)->render(),
                    'title' => $lang->sL($GLOBALS['TCA'][$t]['ctrl']['title']),
                    'count' => $theNumberOfRe,
                    'lostRecords' => $lr
                ];
            }
        }

        $this->view->assignMultiple([
            'pages' => $pageStatistic,
            'doktypes' => $doktypes,
            'tables' => $tableStatistic
        ]);
    }

    /**
     * Show list references
     *
     * @return void
     */
    public function func_relations()
    {
        $admin = GeneralUtility::makeInstance(DatabaseIntegrityCheck::class);
        $fkey_arrays = $admin->getGroupFields('');
        $admin->selectNonEmptyRecordsWithFkeys($fkey_arrays);
        $fileTest = $admin->testFileRefs();

        if (is_array($fileTest['noFile'])) {
            ksort($fileTest['noFile']);
        }
        $this->view->assignMultiple([
            'files' =>  $fileTest,
            'select_db' => $admin->testDBRefs($admin->checkSelectDBRefs),
            'group_db' => $admin->testDBRefs($admin->checkGroupDBRefs)
        ]);
    }

    /**
     * Returns the ModuleTemplate container
     *
     * @return ModuleTemplate
     */
    public function getModuleTemplate()
    {
        return $this->moduleTemplate;
    }
}
