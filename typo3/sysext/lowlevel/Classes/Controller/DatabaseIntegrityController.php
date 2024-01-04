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

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\Types;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\DateFormatter;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageRendererResolver;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\CsvUtility;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
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

    protected string $formName = '';
    protected string $moduleName = '';

    /**
     * If the current user is an admin and $GLOBALS['TYPO3_CONF_VARS']['BE']['debug']
     * is set to true, the names of fields and tables are displayed.
     */
    protected bool $showFieldAndTableNames = false;
    protected array $hookArray = [];
    protected string $table = '';
    protected bool $enablePrefix = false;
    protected int $noDownloadB = 0;
    protected array $tableArray = [];
    protected array $queryConfig = [];
    protected array $extFieldLists = [];
    protected array $fields = [];
    protected string $storeList = 'search_query_smallparts,search_result_labels,labels_noprefix,show_deleted,queryConfig,queryTable,queryFields,queryLimit,queryOrder,queryOrderDesc,queryOrder2,queryOrder2Desc,queryGroup,search_query_makeQuery';
    protected bool $enableQueryParts = false;
    protected array $lang = [
        'OR' => 'or',
        'AND' => 'and',
        'comparison' => [
            // Type = text	offset = 0
            '0_' => 'contains',
            '1_' => 'does not contain',
            '2_' => 'starts with',
            '3_' => 'does not start with',
            '4_' => 'ends with',
            '5_' => 'does not end with',
            '6_' => 'equals',
            '7_' => 'does not equal',
            // Type = number , offset = 32
            '32_' => 'equals',
            '33_' => 'does not equal',
            '34_' => 'is greater than',
            '35_' => 'is less than',
            '36_' => 'is between',
            '37_' => 'is not between',
            '38_' => 'is in list',
            '39_' => 'is not in list',
            '40_' => 'binary AND equals',
            '41_' => 'binary AND does not equal',
            '42_' => 'binary OR equals',
            '43_' => 'binary OR does not equal',
            // Type = multiple, relation, offset = 64
            '64_' => 'equals',
            '65_' => 'does not equal',
            '66_' => 'contains',
            '67_' => 'does not contain',
            '68_' => 'is in list',
            '69_' => 'is not in list',
            '70_' => 'binary AND equals',
            '71_' => 'binary AND does not equal',
            '72_' => 'binary OR equals',
            '73_' => 'binary OR does not equal',
            // Type = date,time  offset = 96
            '96_' => 'equals',
            '97_' => 'does not equal',
            '98_' => 'is greater than',
            '99_' => 'is less than',
            '100_' => 'is between',
            '101_' => 'is not between',
            '102_' => 'binary AND equals',
            '103_' => 'binary AND does not equal',
            '104_' => 'binary OR equals',
            '105_' => 'binary OR does not equal',
            // Type = boolean,  offset = 128
            '128_' => 'is True',
            '129_' => 'is False',
            // Type = binary , offset = 160
            '160_' => 'equals',
            '161_' => 'does not equal',
            '162_' => 'contains',
            '163_' => 'does not contain',
        ],
    ];
    protected string $fieldName = '';
    protected string $name = '';
    protected string $fieldList;
    protected array $comp_offsets = [
        'text' => 0,
        'number' => 1,
        'multiple' => 2,
        'relation' => 2,
        'date' => 3,
        'time' => 3,
        'boolean' => 4,
        'binary' => 5,
    ];
    protected array $compSQL = [
        // Type = text	offset = 0
        '0' => '#FIELD# LIKE \'%#VALUE#%\'',
        '1' => '#FIELD# NOT LIKE \'%#VALUE#%\'',
        '2' => '#FIELD# LIKE \'#VALUE#%\'',
        '3' => '#FIELD# NOT LIKE \'#VALUE#%\'',
        '4' => '#FIELD# LIKE \'%#VALUE#\'',
        '5' => '#FIELD# NOT LIKE \'%#VALUE#\'',
        '6' => '#FIELD# = \'#VALUE#\'',
        '7' => '#FIELD# != \'#VALUE#\'',
        // Type = number, offset = 32
        '32' => '#FIELD# = \'#VALUE#\'',
        '33' => '#FIELD# != \'#VALUE#\'',
        '34' => '#FIELD# > #VALUE#',
        '35' => '#FIELD# < #VALUE#',
        '36' => '#FIELD# >= #VALUE# AND #FIELD# <= #VALUE1#',
        '37' => 'NOT (#FIELD# >= #VALUE# AND #FIELD# <= #VALUE1#)',
        '38' => '#FIELD# IN (#VALUE#)',
        '39' => '#FIELD# NOT IN (#VALUE#)',
        '40' => '(#FIELD# & #VALUE#)=#VALUE#',
        '41' => '(#FIELD# & #VALUE#)!=#VALUE#',
        '42' => '(#FIELD# | #VALUE#)=#VALUE#',
        '43' => '(#FIELD# | #VALUE#)!=#VALUE#',
        // Type = multiple, relation, offset = 64
        '64' => '#FIELD# = \'#VALUE#\'',
        '65' => '#FIELD# != \'#VALUE#\'',
        '66' => '#FIELD# LIKE \'%#VALUE#%\' AND #FIELD# LIKE \'%#VALUE1#%\'',
        '67' => '(#FIELD# NOT LIKE \'%#VALUE#%\' OR #FIELD# NOT LIKE \'%#VALUE1#%\')',
        '68' => '#FIELD# IN (#VALUE#)',
        '69' => '#FIELD# NOT IN (#VALUE#)',
        '70' => '(#FIELD# & #VALUE#)=#VALUE#',
        '71' => '(#FIELD# & #VALUE#)!=#VALUE#',
        '72' => '(#FIELD# | #VALUE#)=#VALUE#',
        '73' => '(#FIELD# | #VALUE#)!=#VALUE#',
        // Type = date, offset = 32
        '96' => '#FIELD# = \'#VALUE#\'',
        '97' => '#FIELD# != \'#VALUE#\'',
        '98' => '#FIELD# > #VALUE#',
        '99' => '#FIELD# < #VALUE#',
        '100' => '#FIELD# >= #VALUE# AND #FIELD# <= #VALUE1#',
        '101' => 'NOT (#FIELD# >= #VALUE# AND #FIELD# <= #VALUE1#)',
        '102' => '(#FIELD# & #VALUE#)=#VALUE#',
        '103' => '(#FIELD# & #VALUE#)!=#VALUE#',
        '104' => '(#FIELD# | #VALUE#)=#VALUE#',
        '105' => '(#FIELD# | #VALUE#)!=#VALUE#',
        // Type = boolean, offset = 128
        '128' => '#FIELD# = \'1\'',
        '129' => '#FIELD# != \'1\'',
        // Type = binary = 160
        '160' => '#FIELD# = \'#VALUE#\'',
        '161' => '#FIELD# != \'#VALUE#\'',
        '162' => '(#FIELD# & #VALUE#)=#VALUE#',
        '163' => '(#FIELD# & #VALUE#)=0',
    ];

    public function __construct(
        protected IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->moduleName = 'system_dbint';
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();

        $this->menuConfig($request);
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->setUpDocHeader($moduleTemplate);

        $title = $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:module.dbint.title');
        switch ($this->MOD_SETTINGS['function']) {
            case 'search':
                $moduleTemplate->setTitle($title, $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch'));
                return $this->searchAction($moduleTemplate, $request);
            case 'records':
                $moduleTemplate->setTitle($title, $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:recordStatistics'));
                return $this->recordStatisticsAction($moduleTemplate, $request);
            case 'relations':
                $moduleTemplate->setTitle($title, $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:databaseRelations'));
                return $this->relationsAction($moduleTemplate);
            default:
                $moduleTemplate->setTitle($title, $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:manageRefIndex'));
                return $this->referenceIndexAction($moduleTemplate, $request);
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
                'refindex' => htmlspecialchars($lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:manageRefIndex')),
                'records' => htmlspecialchars($lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:recordStatistics')),
                'relations' => htmlspecialchars($lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:databaseRelations')),
                'search' => htmlspecialchars($lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch')),
            ],
            'search' => [
                'raw' => htmlspecialchars($lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:rawSearch')),
                'query' => htmlspecialchars($lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:advancedQuery')),
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
                'all' => htmlspecialchars($lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:selectRecords')),
                'count' => htmlspecialchars($lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:countResults')),
                'explain' => htmlspecialchars($lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:explainQuery')),
                'csv' => htmlspecialchars($lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:csvExport')),
            ],
            'sword' => '',
        ];

        // EXPLAIN is no ANSI SQL, for now this is only executed on mysql
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        if (!$connection->getDatabasePlatform() instanceof AbstractMySQLPlatform) {
            unset($this->MOD_MENU['search_query_makeQuery']['explain']);
        }

        // CLEAN SETTINGS
        $OLD_MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, [], 'system_dbint', 'ses');
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, $parsedBody['SET'] ?? $queryParams['SET'] ?? [], 'system_dbint', 'ses');
        $queryConfig = $parsedBody['queryConfig'] ?? $queryParams['queryConfig'] ?? false;
        if ($queryConfig) {
            $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, ['queryConfig' => serialize($queryConfig)], 'system_dbint', 'ses');
        }
        $setLimitToStart = false;
        foreach ($OLD_MOD_SETTINGS as $key => $val) {
            if (str_starts_with($key, 'query') && $this->MOD_SETTINGS[$key] != $val && $key !== 'queryLimit' && $key !== 'use_listview') {
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
            $currentLimit = explode(',', $this->MOD_SETTINGS['queryLimit'] ??  '');
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
    protected function searchAction(ModuleTemplate $view, ServerRequestInterface $request): ResponseInterface
    {
        $lang = $this->getLanguageService();
        $this->showFieldAndTableNames = $this->getBackendUserAuthentication()->shallDisplayDebugInformation();
        $searchMode = $this->MOD_SETTINGS['search'];
        $this->setFormName('queryform');
        $submenu = '';
        $submenu .= '<div class="form-row">';
        $submenu .= '<div class="form-group">' . self::getDropdownMenu(0, 'SET[search]', $searchMode, $this->MOD_MENU['search'], $request) . '</div>';
        if ($this->MOD_SETTINGS['search'] === 'query') {
            $submenu .= '<div class="form-group">' . self::getDropdownMenu(0, 'SET[search_query_makeQuery]', $this->MOD_SETTINGS['search_query_makeQuery'], $this->MOD_MENU['search_query_makeQuery'], $request) . '</div>';
        }
        $submenu .= '</div>';
        if ($this->MOD_SETTINGS['search'] === 'query') {
            $submenu .= '<div class="form-group">';
            $submenu .= '<div class="form-check">' . self::getFuncCheck(0, 'SET[search_query_smallparts]', $this->MOD_SETTINGS['search_query_smallparts'] ?? '', $request, '', '', 'id="checkSearch_query_smallparts"') . '<label class="form-check-label" for="checkSearch_query_smallparts">' . $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:showSQL') . '</label></div>';
            $submenu .= '<div class="form-check">' . self::getFuncCheck(0, 'SET[search_result_labels]', $this->MOD_SETTINGS['search_result_labels'] ?? '', $request, '', '', 'id="checkSearch_result_labels"') . '<label class="form-check-label" for="checkSearch_result_labels">' . $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:useFormattedStrings') . '</label></div>';
            $submenu .= '<div class="form-check">' . self::getFuncCheck(0, 'SET[labels_noprefix]', $this->MOD_SETTINGS['labels_noprefix'] ?? '', $request, '', '', 'id="checkLabels_noprefix"') . '<label class="form-check-label" for="checkLabels_noprefix">' . $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:dontUseOrigValues') . '</label></div>';
            $submenu .= '<div class="form-check">' . self::getFuncCheck(0, 'SET[options_sortlabel]', $this->MOD_SETTINGS['options_sortlabel'] ?? '', $request, '', '', 'id="checkOptions_sortlabel"') . '<label class="form-check-label" for="checkOptions_sortlabel">' . $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:sortOptions') . '</label></div>';
            $submenu .= '<div class="form-check">' . self::getFuncCheck(0, 'SET[show_deleted]', $this->MOD_SETTINGS['show_deleted'] ?? 0, $request, '', '', 'id="checkShow_deleted"') . '<label class="form-check-label" for="checkShow_deleted">' . $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:showDeleted') . '</label></div>';
            $submenu .= '</div>';
        }
        $view->assign('submenu', $submenu);
        $view->assign('searchMode', $searchMode);
        switch ($searchMode) {
            case 'query':
                $view->assign('queryMaker', $this->queryMaker($request));
                break;
            case 'raw':
            default:
                $view->assign('sword', (string)($this->MOD_SETTINGS['sword'] ?? ''));
                $view->assign('searchOptions', $this->form());
                $view->assign('results', $this->search($request));
                $view->assign('isSearching', $request->getMethod() === 'POST');
        }

        return $view->renderResponse('CustomSearch');
    }

    protected function setFormName(string $formName): void
    {
        $this->formName = trim($formName);
    }

    protected function queryMaker(ServerRequestInterface $request): string
    {
        $output = '';
        $this->hookArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3lib_fullsearch'] ?? [];
        $msg = $this->procesStoreControl($request);
        $userTsConfig = $this->getBackendUserAuthentication()->getTSConfig();
        if (!($userTsConfig['mod.']['dbint.']['disableStoreControl'] ?? false)) {
            $output .= '<h2 class="headline-spaced">Load/Save Query</h2>';
            $output .= $this->makeStoreControl();
            $output .= $msg;
        }
        // Query Maker:
        $this->init('queryConfig', $this->MOD_SETTINGS['queryTable'] ?? '', '', $this->MOD_SETTINGS);
        if ($this->formName) {
            $this->setFormName($this->formName);
        }
        $output .= '<h2>Make query</h2>';
        $output .= $this->makeSelectorTable($this->MOD_SETTINGS, $request);
        $mQ = $this->MOD_SETTINGS['search_query_makeQuery'] ?? '';
        // Make form elements:
        if ($this->table && is_array($GLOBALS['TCA'][$this->table])) {
            if ($mQ) {
                // Show query
                $this->enablePrefix = true;
                $queryString = $this->getQuery($this->queryConfig);
                $selectQueryString = $this->getSelectQuery($queryString);
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);

                $isConnectionMysql = str_starts_with($connection->getServerVersion(), 'MySQL');
                $fullQueryString = '';
                try {
                    if ($mQ === 'explain' && $isConnectionMysql) {
                        // EXPLAIN is no ANSI SQL, for now this is only executed on mysql
                        // @todo: Move away from getSelectQuery() or model differently
                        $fullQueryString = 'EXPLAIN ' . $selectQueryString;
                        $dataRows = $connection->executeQuery('EXPLAIN ' . $selectQueryString)->fetchAllAssociative();
                    } elseif ($mQ === 'count') {
                        $queryBuilder = $connection->createQueryBuilder();
                        $queryBuilder->getRestrictions()->removeAll();
                        if (empty($this->MOD_SETTINGS['show_deleted'])) {
                            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                        }
                        $queryBuilder->count('*')
                            ->from($this->table)
                            ->where(QueryHelper::stripLogicalOperatorPrefix($queryString));
                        $fullQueryString = $queryBuilder->getSQL();
                        $dataRows = [$queryBuilder->executeQuery()->fetchOne()];
                    } else {
                        $fullQueryString = $selectQueryString;
                        $dataRows = $connection->executeQuery($selectQueryString)->fetchAllAssociative();
                    }
                    if (!($userTsConfig['mod.']['dbint.']['disableShowSQLQuery'] ?? false)) {
                        $output .= '<h2>SQL query</h2>';
                        $output .= '<pre class="language-sql">';
                        $output .=   '<code class="language-sql">';
                        $output .=     htmlspecialchars($fullQueryString);
                        $output .=   '</code>';
                        $output .= '</pre>';
                    }
                    $cPR = $this->getQueryResultCode($mQ, $dataRows, $this->table, $request);
                    if ($cPR['header'] ?? null) {
                        $output .= '<h2>' . $cPR['header'] . '</h2>';
                    }
                    if ($cPR['content'] ?? null) {
                        $output .= $cPR['content'];
                    }
                } catch (DBALException $e) {
                    if (!($userTsConfig['mod.']['dbint.']['disableShowSQLQuery'] ?? false)) {
                        $output .= '<h2>SQL query</h2>';
                        $output .= '<pre class="language-sql">';
                        $output .=   '<code class="language-sql">';
                        $output .=     htmlspecialchars($fullQueryString);
                        $output .=   '</code>';
                        $output .= '</pre>';
                    }
                    $output .= '<h2>SQL error</h2>';
                    $output .= '<div class="alert alert-danger">';
                    $output .= '<p class="alert-message"><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
                    $output .= '</div>';
                }
            }
        }

        return $output;
    }

    protected function getSelectQuery(string $qString = ''): string
    {
        $backendUserAuthentication = $this->getBackendUserAuthentication();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);
        $queryBuilder->getRestrictions()->removeAll();
        if (empty($this->MOD_SETTINGS['show_deleted'])) {
            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        }
        $deleteField = $GLOBALS['TCA'][$this->table]['ctrl']['delete'] ?? '';
        $fieldList = GeneralUtility::trimExplode(
            ',',
            $this->extFieldLists['queryFields']
            . ',pid'
            . ($deleteField ? ',' . $deleteField : '')
        );
        $queryBuilder->select(...$fieldList)
            ->from($this->table);

        if ($this->extFieldLists['queryGroup']) {
            $queryBuilder->groupBy(...QueryHelper::parseGroupBy($this->extFieldLists['queryGroup']));
        }
        if ($this->extFieldLists['queryOrder']) {
            foreach (QueryHelper::parseOrderBy($this->extFieldLists['queryOrder_SQL']) as $orderPair) {
                [$fieldName, $order] = $orderPair;
                $queryBuilder->addOrderBy($fieldName, $order);
            }
        }
        $queryLimit = (string)($this->extFieldLists['queryLimit'] ?? '');
        if ($queryLimit) {
            // Explode queryLimit to fetch the limit and a possible offset
            $parts = GeneralUtility::intExplode(',', $queryLimit);
            if ($parts[1] ?? null) {
                // Offset and limit are given
                $queryBuilder->setFirstResult($parts[0]);
                $queryBuilder->setMaxResults($parts[1]);
            } else {
                // Only the limit is given
                $queryBuilder->setMaxResults($parts[0]);
            }
        }

        if (!$backendUserAuthentication->isAdmin()) {
            $webMounts = $backendUserAuthentication->returnWebmounts();
            $perms_clause = $backendUserAuthentication->getPagePermsClause(Permission::PAGE_SHOW);
            $webMountPageTree = '';
            $webMountPageTreePrefix = '';
            foreach ($webMounts as $webMount) {
                if ($webMountPageTree) {
                    $webMountPageTreePrefix = ',';
                }
                $webMountPageTree .= $webMountPageTreePrefix
                    . $this->getTreeList($webMount, 999, 0, $perms_clause);
            }
            // createNamedParameter() is not used here because the SQL fragment will only include
            // the :dcValueX placeholder when the query is returned as a string. The value for the
            // placeholder would be lost in the process.
            if ($this->table === 'pages') {
                $queryBuilder->where(
                    QueryHelper::stripLogicalOperatorPrefix($perms_clause),
                    $queryBuilder->expr()->in(
                        'uid',
                        GeneralUtility::intExplode(',', $webMountPageTree)
                    )
                );
            } else {
                $queryBuilder->where(
                    $queryBuilder->expr()->in(
                        'pid',
                        GeneralUtility::intExplode(',', $webMountPageTree)
                    )
                );
            }
        }
        if (!$qString) {
            $qString = $this->getQuery($this->queryConfig);
        }
        $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($qString));

        return $queryBuilder->getSQL();
    }

    /**
     * Recursively fetch all descendants of a given page
     *
     * @return string comma separated list of descendant pages
     */
    protected function getTreeList(int $id, int $depth, int $begin = 0, string $permsClause = ''): string
    {
        if ($id < 0) {
            $id = abs($id);
        }
        if ($begin === 0) {
            $theList = (string)$id;
        } else {
            $theList = '';
        }
        if ($id && $depth > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $statement = $queryBuilder->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('sys_language_uid', 0)
                )
                ->orderBy('uid');
            if ($permsClause !== '') {
                $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($permsClause));
            }
            $statement = $queryBuilder->executeQuery();
            while ($row = $statement->fetchAssociative()) {
                if ($begin <= 0) {
                    $theList .= ',' . $row['uid'];
                }
                if ($depth > 1) {
                    $theSubList = $this->getTreeList($row['uid'], $depth - 1, $begin - 1, $permsClause);
                    if (!empty($theList) && !empty($theSubList) && ($theSubList[0] !== ',')) {
                        $theList .= ',';
                    }
                    $theList .= $theSubList;
                }
            }
        }

        return $theList;
    }

    /**
     * @return array HTML-code for "header" and "content"
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function getQueryResultCode(string $type, array $dataRows, string $table, ServerRequestInterface $request): array
    {
        $out = '';
        $cPR = [];
        switch ($type) {
            case 'count':
                $cPR['header'] = 'Count';
                $cPR['content'] = '<p><strong>' . (int)$dataRows[0] . '</strong> records selected.</p>';
                break;
            case 'all':
                $rowArr = [];
                $dataRow = null;
                foreach ($dataRows as $dataRow) {
                    $rowArr[] = $this->resultRowDisplay($dataRow, $GLOBALS['TCA'][$table], $table, $request);
                }
                if (is_array($this->hookArray['beforeResultTable'] ?? false)) {
                    foreach ($this->hookArray['beforeResultTable'] as $_funcRef) {
                        $out .= GeneralUtility::callUserFunction($_funcRef, $this->MOD_SETTINGS);
                    }
                }
                if (!empty($rowArr)) {
                    $cPR['header'] = 'Result';
                    $out .= '<div class="table-fit">';
                    $out .= '<table class="table table-striped table-hover">';
                    $out .= $this->resultRowTitles((array)$dataRow, $GLOBALS['TCA'][$table]) . implode(LF, $rowArr);
                    $out .= '</table>';
                    $out .= '</div>';
                } else {
                    $this->renderNoResultsFoundMessage();
                }

                $cPR['content'] = $out;
                break;
            case 'csv':
                $rowArr = [];
                $first = 1;
                foreach ($dataRows as $dataRow) {
                    if ($first) {
                        $rowArr[] = $this->csvValues(array_keys($dataRow));
                        $first = 0;
                    }
                    $rowArr[] = $this->csvValues($dataRow, ',', '"', $GLOBALS['TCA'][$table], $table);
                }
                if (!empty($rowArr)) {
                    $cPR['header'] = 'Result';
                    $out .= '<div class="form-group">';
                    $out .= '<textarea class="form-control" name="whatever" rows="20" class="font-monospace" style="width:100%">';
                    $out .= htmlspecialchars(implode(LF, $rowArr));
                    $out .= '</textarea>';
                    $out .= '</div>';
                    if (!$this->noDownloadB) {
                        $out .= '<button class="btn btn-default" type="submit" name="download_file" value="Click to download file">';
                        $out .=    $this->iconFactory->getIcon('actions-file-csv-download', Icon::SIZE_SMALL)->render();
                        $out .= '  Click to download file';
                        $out .= '</button>';
                    }
                    // Downloads file:
                    // @todo: args. routing anyone?
                    if ($request->getParsedBody()['download_file'] ?? false) {
                        $filename = 'TYPO3_' . $table . '_export_' . date('dmy-Hi') . '.csv';
                        $mimeType = 'application/octet-stream';
                        header('Content-Type: ' . $mimeType);
                        header('Content-Disposition: attachment; filename=' . $filename);
                        echo implode(CRLF, $rowArr);
                        die;
                    }
                } else {
                    $this->renderNoResultsFoundMessage();
                }
                $cPR['content'] = $out;
                break;
            case 'explain':
            default:
                foreach ($dataRows as $dataRow) {
                    $out .= DebugUtility::viewArray($dataRow);
                }
                $cPR['header'] = 'Explain SQL query';
                $cPR['content'] = $out;
        }

        return $cPR;
    }

    protected function csvValues(array $row, string $delim = ',', string $quote = '"', array $conf = [], string $table = ''): string
    {
        $valueArray = $row;
        if (($this->MOD_SETTINGS['search_result_labels'] ?? false) && $table) {
            foreach ($valueArray as $key => $val) {
                $valueArray[$key] = $this->getProcessedValueExtra($table, $key, (string)$val, $conf, ';');
            }
        }

        return CsvUtility::csvValues($valueArray, $delim, $quote);
    }

    /**
     * @param array|null $row Table columns
     */
    protected function resultRowTitles(?array $row, array $conf): string
    {
        $languageService = $this->getLanguageService();
        $tableHeader = [];
        // Start header row
        $tableHeader[] = '<thead><tr>';
        // Iterate over given columns
        foreach ($row ?? [] as $fieldName => $fieldValue) {
            if (GeneralUtility::inList($this->MOD_SETTINGS['queryFields'] ?? '', $fieldName)
                || !($this->MOD_SETTINGS['queryFields'] ?? false)
                && $fieldName !== 'pid'
                && $fieldName !== 'deleted'
            ) {
                if ($this->MOD_SETTINGS['search_result_labels'] ?? false) {
                    $title = $languageService->sL(($conf['columns'][$fieldName]['label'] ?? false) ?: $fieldName);
                } else {
                    $title = $languageService->sL($fieldName);
                }
                $tableHeader[] = '<th>' . htmlspecialchars($title) . '</th>';
            }
        }
        // Add empty icon column
        $tableHeader[] = '<th></th>';
        // Close header row
        $tableHeader[] = '</tr></thead>';

        return implode(LF, $tableHeader);
    }

    protected function resultRowDisplay(array $row, array $conf, string $table, ServerRequestInterface $request): string
    {
        $languageService = $this->getLanguageService();
        $out = '<tr>';
        foreach ($row as $fieldName => $fieldValue) {
            if (GeneralUtility::inList($this->MOD_SETTINGS['queryFields'] ?? '', $fieldName)
                || !($this->MOD_SETTINGS['queryFields'] ?? false)
                && $fieldName !== 'pid'
                && $fieldName !== 'deleted'
            ) {
                if ($this->MOD_SETTINGS['search_result_labels'] ?? false) {
                    $fVnew = $this->getProcessedValueExtra($table, $fieldName, (string)$fieldValue, $conf, '<br />');
                } else {
                    $fVnew = htmlspecialchars((string)$fieldValue);
                }
                $out .= '<td>' . $fVnew . '</td>';
            }
        }
        $out .= '<td class="col-control">';
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        if (!($row['deleted'] ?? false)) {
            $out .= '<div class="btn-group" role="group">';
            $url = (string)$uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    $table => [
                        $row['uid'] => 'edit',
                    ],
                ],
                'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri()
                    . HttpUtility::buildQueryString(['SET' => $request->getParsedBody()['SET'] ?? []], '&'),
            ]);
            $out .= '<a class="btn btn-default" href="' . htmlspecialchars($url) . '">'
                . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render()
                . '</a>';
            $out .= '</div><div class="btn-group" role="group">';
            $out .= sprintf(
                '<a class="btn btn-default" href="#" data-dispatch-action="%s" data-dispatch-args-list="%s">%s</a>',
                'TYPO3.InfoWindow.showItem',
                htmlspecialchars($table . ',' . $row['uid']),
                $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render()
            );
            $out .= '</div>';
        } else {
            $out .= '<div class="btn-group" role="group">';
            $out .= '<a class="btn btn-default" href="' . htmlspecialchars((string)$uriBuilder->buildUriFromRoute('tce_db', [
                'cmd' => [
                    $table => [
                        $row['uid'] => [
                            'undelete' => 1,
                        ],
                    ],
                ],
                'redirect' => (string)$uriBuilder->buildUriFromRoute('system_dbint'),
            ])) . '" title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_t3lib_fullsearch.xlf:undelete_only')) . '">';
            $out .= $this->iconFactory->getIcon('actions-edit-restore', Icon::SIZE_SMALL)->render() . '</a>';
            $out .= '</div>';
        }
        $_params = [$table => $row];
        if (is_array($this->hookArray['additionalButtons'] ?? false)) {
            foreach ($this->hookArray['additionalButtons'] as $_funcRef) {
                $out .= GeneralUtility::callUserFunction($_funcRef, $_params);
            }
        }
        $out .= '</td></tr>';

        return $out;
    }

    protected function getProcessedValueExtra(string $table, string $fieldName, string $fieldValue, array $conf, string $splitString): string
    {
        $out = '';
        $fields = [];
        $user = $this->getBackendUserAuthentication();
        if ($user->user['lang'] ?? false) {
            $locale = GeneralUtility::makeInstance(Locales::class)->createLocale($user->user['lang']);
        } else {
            $locale = new Locale();
        }
        // Analysing the fields in the table.
        if (is_array($GLOBALS['TCA'][$table] ?? null)) {
            $fC = $GLOBALS['TCA'][$table]['columns'][$fieldName] ?? null;
            $fields = $fC['config'] ?? [];
            $fields['exclude'] = $fC['exclude'] ?? '';
            if (is_array($fC) && ($fC['label'] ?? false)) {
                $fields['label'] = preg_replace('/:$/', '', trim($this->getLanguageService()->sL($fC['label'])));
                switch ($fields['type']) {
                    case 'input':
                        if (GeneralUtility::inList($fields['eval'] ?? '', 'year')) {
                            $fields['type'] = 'number';
                        } else {
                            $fields['type'] = 'text';
                        }
                        break;
                    case 'number':
                        // Empty on purpose, we have to keep the type "number".
                        // Falling back to the "default" case would set the type to "text"
                        break;
                    case 'datetime':
                        if (!in_array($fields['dbType']  ?? '', QueryHelper::getDateTimeTypes(), true)) {
                            $fields['type'] = 'number';
                        } elseif ($fields['dbType'] === 'time') {
                            $fields['type'] = 'time';
                        } else {
                            $fields['type'] = 'date';
                        }
                        break;
                    case 'check':
                        if (!($fields['items'] ?? false)) {
                            $fields['type'] = 'boolean';
                        } else {
                            $fields['type'] = 'binary';
                        }
                        break;
                    case 'radio':
                        $fields['type'] = 'multiple';
                        break;
                    case 'select':
                    case 'category':
                        $fields['type'] = 'multiple';
                        if ($fields['foreign_table'] ?? false) {
                            $fields['type'] = 'relation';
                        }
                        if ($fields['special'] ?? false) {
                            $fields['type'] = 'text';
                        }
                        break;
                    case 'group':
                        $fields['type'] = 'relation';
                        break;
                    case 'user':
                    case 'flex':
                    case 'passthrough':
                    case 'none':
                    case 'text':
                    case 'email':
                    case 'link':
                    case 'password':
                    case 'color':
                    case 'json':
                    case 'uuid':
                    default:
                        $fields['type'] = 'text';
                }
            } else {
                $fields['label'] = '[FIELD: ' . $fieldName . ']';
                switch ($fieldName) {
                    case 'pid':
                        $fields['type'] = 'relation';
                        $fields['allowed'] = 'pages';
                        break;
                    case 'tstamp':
                    case 'crdate':
                        $fields['type'] = 'time';
                        break;
                    default:
                        $fields['type'] = 'number';
                }
            }
        }
        switch ($fields['type']) {
            case 'date':
                if ($fieldValue != -1) {
                    $formatter = new DateFormatter();
                    $out = $formatter->format((int)$fieldValue, 'SHORTDATE', $locale);
                }
                break;
            case 'time':
                if ($fieldValue != -1) {
                    $formatter = new DateFormatter();
                    if ($splitString === '<br />') {
                        $out = $formatter->format((int)$fieldValue, 'HH:mm\'' . $splitString . '\'dd-MM-yyyy', $locale);
                    } else {
                        $out = $formatter->format((int)$fieldValue, 'HH:mm dd-MM-yyyy', $locale);
                    }
                }
                break;
            case 'multiple':
            case 'binary':
            case 'relation':
                $out = $this->makeValueList($fieldName, $fieldValue, $fields, $table, $splitString);
                break;
            case 'boolean':
                $out = $fieldValue ? 'True' : 'False';
                break;
            default:
                $out = htmlspecialchars($fieldValue);
        }

        return $out;
    }

    protected function makeValueList(string $fieldName, string $fieldValue, array $conf, string $table, string $splitString): string
    {
        $backendUserAuthentication = $this->getBackendUserAuthentication();
        $languageService = $this->getLanguageService();
        $from_table_Arr = [];
        $fieldSetup = $conf;
        $out = '';
        if ($fieldSetup['type'] === 'multiple') {
            foreach (($fieldSetup['items'] ?? []) as $val) {
                $value = $languageService->sL($val['label']);
                if (GeneralUtility::inList($fieldValue, $val['value']) || $fieldValue == $val['value']) {
                    if ($out !== '') {
                        $out .= $splitString;
                    }
                    $out .= htmlspecialchars($value);
                }
            }
        }
        if ($fieldSetup['type'] === 'binary') {
            foreach ($fieldSetup['items'] as $val) {
                $value = $languageService->sL($val['label']);
                if ($out !== '') {
                    $out .= $splitString;
                }
                $out .= htmlspecialchars($value);
            }
        }
        if ($fieldSetup['type'] === 'relation') {
            $dontPrefixFirstTable = 0;
            $useTablePrefix = 0;
            foreach (($fieldSetup['items'] ?? []) as $val) {
                if (str_starts_with($val['label'], 'LLL:')) {
                    $value = $languageService->sL($val['label']);
                } else {
                    $value = $val['label'];
                }
                if (GeneralUtility::inList($fieldValue, $value) || $fieldValue == $value) {
                    if ($out !== '') {
                        $out .= $splitString;
                    }
                    $out .= htmlspecialchars($value);
                }
            }
            if (str_contains($fieldSetup['allowed'] ?? '', ',')) {
                $from_table_Arr = explode(',', $fieldSetup['allowed']);
                $useTablePrefix = 1;
                if (!$fieldSetup['prepend_tname']) {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                    $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                    $statement = $queryBuilder->select($fieldName)->from($table)->executeQuery();
                    while ($row = $statement->fetchAssociative()) {
                        if (str_contains($row[$fieldName], ',')) {
                            $checkContent = explode(',', $row[$fieldName]);
                            foreach ($checkContent as $singleValue) {
                                if (!str_contains($singleValue, '_')) {
                                    $dontPrefixFirstTable = 1;
                                }
                            }
                        } else {
                            $singleValue = $row[$fieldName];
                            if ($singleValue !== '' && !str_contains($singleValue, '_')) {
                                $dontPrefixFirstTable = 1;
                            }
                        }
                    }
                }
            } else {
                $from_table_Arr[0] = $fieldSetup['allowed'] ?? null;
            }
            if (!empty($fieldSetup['prepend_tname'])) {
                $useTablePrefix = 1;
            }
            if (!empty($fieldSetup['foreign_table'])) {
                $from_table_Arr[0] = $fieldSetup['foreign_table'];
            }
            $counter = 0;
            $useSelectLabels = 0;
            $useAltSelectLabels = 0;
            $tablePrefix = '';
            $labelFieldSelect = [];
            foreach ($from_table_Arr as $from_table) {
                if ($useTablePrefix && !$dontPrefixFirstTable && $counter !== 1 || $counter === 1) {
                    $tablePrefix = $from_table . '_';
                }
                $counter = 1;
                if (is_array($GLOBALS['TCA'][$from_table] ?? null)) {
                    $labelField = $GLOBALS['TCA'][$from_table]['ctrl']['label'] ?? '';
                    $altLabelField = $GLOBALS['TCA'][$from_table]['ctrl']['label_alt'] ?? '';
                    if (is_array($GLOBALS['TCA'][$from_table]['columns'][$labelField]['config']['items'] ?? false)) {
                        $items = $GLOBALS['TCA'][$from_table]['columns'][$labelField]['config']['items'];
                        foreach ($items as $labelArray) {
                            $labelFieldSelect[$labelArray['value']] = $languageService->sL($labelArray['label']);
                        }
                        $useSelectLabels = 1;
                    }
                    $altLabelFieldSelect = [];
                    if (is_array($GLOBALS['TCA'][$from_table]['columns'][$altLabelField]['config']['items'] ?? false)) {
                        $items = $GLOBALS['TCA'][$from_table]['columns'][$altLabelField]['config']['items'];
                        foreach ($items as $altLabelArray) {
                            $altLabelFieldSelect[$altLabelArray['value']] = $languageService->sL($altLabelArray['label']);
                        }
                        $useAltSelectLabels = 1;
                    }

                    if (empty($this->tableArray[$from_table])) {
                        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($from_table);
                        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                        $selectFields = ['uid', $labelField];
                        if ($altLabelField) {
                            $selectFields = array_merge($selectFields, GeneralUtility::trimExplode(',', $altLabelField, true));
                        }
                        $queryBuilder->select(...$selectFields)
                            ->from($from_table)
                            ->orderBy('uid');
                        if (!$backendUserAuthentication->isAdmin()) {
                            $webMounts = $backendUserAuthentication->returnWebmounts();
                            $perms_clause = $backendUserAuthentication->getPagePermsClause(Permission::PAGE_SHOW);
                            $webMountPageTree = '';
                            $webMountPageTreePrefix = '';
                            foreach ($webMounts as $webMount) {
                                if ($webMountPageTree) {
                                    $webMountPageTreePrefix = ',';
                                }
                                $webMountPageTree .= $webMountPageTreePrefix
                                    . $this->getTreeList($webMount, 999, 0, $perms_clause);
                            }
                            if ($from_table === 'pages') {
                                $queryBuilder->where(
                                    QueryHelper::stripLogicalOperatorPrefix($perms_clause),
                                    $queryBuilder->expr()->in(
                                        'uid',
                                        $queryBuilder->createNamedParameter(
                                            GeneralUtility::intExplode(',', $webMountPageTree),
                                            Connection::PARAM_INT_ARRAY
                                        )
                                    )
                                );
                            } else {
                                $queryBuilder->where(
                                    $queryBuilder->expr()->in(
                                        'pid',
                                        $queryBuilder->createNamedParameter(
                                            GeneralUtility::intExplode(',', $webMountPageTree),
                                            Connection::PARAM_INT_ARRAY
                                        )
                                    )
                                );
                            }
                        }
                        $statement = $queryBuilder->executeQuery();
                        $this->tableArray[$from_table] = [];
                        while ($row = $statement->fetchAssociative()) {
                            $this->tableArray[$from_table][] = $row;
                        }
                    }

                    foreach ($this->tableArray[$from_table] as $key => $val) {
                        $this->MOD_SETTINGS['labels_noprefix'] =
                            ($this->MOD_SETTINGS['labels_noprefix'] ?? '') == 1
                                ? 'on'
                                : $this->MOD_SETTINGS['labels_noprefix'];
                        $prefixString =
                            $this->MOD_SETTINGS['labels_noprefix'] === 'on'
                                ? ''
                                : ' [' . $tablePrefix . $val['uid'] . '] ';
                        if ($out !== '') {
                            $out .= $splitString;
                        }
                        if (GeneralUtility::inList($fieldValue, $tablePrefix . $val['uid'])
                            || $fieldValue == $tablePrefix . $val['uid']) {
                            if ($useSelectLabels) {
                                $out .= htmlspecialchars($prefixString . $labelFieldSelect[$val[$labelField]]);
                            } elseif ($val[$labelField]) {
                                $out .= htmlspecialchars($prefixString . $val[$labelField]);
                            } elseif ($useAltSelectLabels) {
                                $out .= htmlspecialchars($prefixString . $altLabelFieldSelect[$val[$altLabelField]]);
                            } else {
                                $out .= htmlspecialchars($prefixString . $val[$altLabelField]);
                            }
                        }
                    }
                }
            }
        }

        return $out;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Core\Exception
     */
    private function renderNoResultsFoundMessage(): void
    {
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, 'No rows selected!', '', ContextualFeedbackSeverity::INFO);
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    protected function getQuery(array $queryConfig, string $pad = ''): string
    {
        $qs = '';
        // Since we don't traverse the array using numeric keys in the upcoming whileloop make sure it's fresh and clean
        ksort($queryConfig);
        $first = true;
        foreach ($queryConfig as $key => $conf) {
            $conf = $this->convertIso8601DatetimeStringToUnixTimestamp($conf);
            switch ($conf['type']) {
                case 'newlevel':
                    $qs .= LF . $pad . trim($conf['operator']) . ' (' . $this->getQuery(
                        $queryConfig[$key]['nl'],
                        $pad . '   '
                    ) . LF . $pad . ')';
                    break;
                default:
                    $qs .= LF . $pad . $this->getQuerySingle($conf, $first);
            }
            $first = false;
        }

        return $qs;
    }

    protected function getQuerySingle(array $conf, bool $first): string
    {
        $comparison = (int)($conf['comparison'] ?? 0);
        $qs = '';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
        $prefix = $this->enablePrefix ? $this->table . '.' : '';
        if (!$first) {
            // Is it OK to insert the AND operator if none is set?
            $operator = strtoupper(trim($conf['operator'] ?? ''));
            if (!in_array($operator, ['AND', 'OR'], true)) {
                $operator = 'AND';
            }
            $qs .= $operator . ' ';
        }
        $qsTmp = str_replace('#FIELD#', $prefix . trim(substr($conf['type'], 6)), $this->compSQL[$comparison] ?? '');
        $inputVal = $this->cleanInputVal($conf);
        if ($comparison === 68 || $comparison === 69) {
            $inputVal = explode(',', (string)$inputVal);
            foreach ($inputVal as $key => $fileName) {
                $inputVal[$key] = $queryBuilder->quote($fileName);
            }
            $inputVal = implode(',', $inputVal);
            $qsTmp = str_replace('#VALUE#', $inputVal, $qsTmp);
        } elseif ($comparison === 162 || $comparison === 163) {
            $inputValArray = explode(',', (string)$inputVal);
            $inputVal = 0;
            foreach ($inputValArray as $fileName) {
                $inputVal += (int)$fileName;
            }
            $qsTmp = str_replace('#VALUE#', (string)$inputVal, $qsTmp);
        } else {
            if (is_array($inputVal)) {
                $inputVal = $inputVal[0];
            }
            // @todo This is weired, as it seems that it quotes the value as string and remove
            //       quotings using the trim() method. Should be investagated/refactored.
            $qsTmp = str_replace('#VALUE#', trim($queryBuilder->quote((string)$inputVal), '\''), $qsTmp);
        }
        if ($comparison === 37 || $comparison === 36 || $comparison === 66 || $comparison === 67 || $comparison === 100 || $comparison === 101) {
            // between:
            $inputVal = $this->cleanInputVal($conf, '1');
            // @todo This is weired, as it seems that it quotes the value as string and remove
            //       quotings using the trim() method. Should be investagated/refactored.
            $qsTmp = str_replace('#VALUE1#', trim($queryBuilder->quote((string)$inputVal), '\''), $qsTmp);
        }
        $qs .= trim((string)$qsTmp);

        return $qs;
    }

    /**
     * @return mixed
     */
    protected function cleanInputVal(array $conf, string $suffix = '')
    {
        $comparison = (int)($conf['comparison'] ?? 0);
        $var = $conf['inputValue' . $suffix] ?? '';
        if ($comparison >> 5 === 0 || ($comparison === 32 || $comparison === 33 || $comparison === 64 || $comparison === 65 || $comparison === 66 || $comparison === 67 || $comparison === 96 || $comparison === 97)) {
            $inputVal = $var ?? null;
        } elseif ($comparison === 39 || $comparison === 38) {
            // in list:
            $inputVal = implode(',', GeneralUtility::intExplode(',', (string)($var ?? '')));
        } elseif ($comparison === 68 || $comparison === 69 || $comparison === 162 || $comparison === 163) {
            // in list:
            if (is_array($var ?? false)) {
                $inputVal = implode(',', $var);
            } elseif ($var ?? false) {
                $inputVal = $var;
            } else {
                $inputVal = 0;
            }
        } elseif (!is_array($var) && strtotime((string)$var)) {
            $inputVal = $var;
        } elseif (!is_array($var) && MathUtility::canBeInterpretedAsInteger($var)) {
            $inputVal = (int)$var;
        } else {
            // TODO: Six eyes looked at this code and nobody understood completely what is going on here and why we
            // fallback to float casting, the whole class smells like it needs a refactoring.
            $inputVal = (float)($var ?? 0.0);
        }

        return $inputVal;
    }

    protected function convertIso8601DatetimeStringToUnixTimestamp(array $conf): array
    {
        if ($this->isDateOfIso8601Format($conf['inputValue'] ?? '')) {
            $conf['inputValue'] = strtotime($conf['inputValue']);
            if ($this->isDateOfIso8601Format($conf['inputValue1'] ?? '')) {
                $conf['inputValue1'] = strtotime($conf['inputValue1']);
            }
        }

        return $conf;
    }

    /**
     * Checks if the given value is of the ISO 8601 format.
     */
    protected function isDateOfIso8601Format(mixed $date): bool
    {
        if (!is_int($date) && !is_string($date)) {
            return false;
        }
        $format = 'Y-m-d\\TH:i:s\\Z';
        $formattedDate = \DateTime::createFromFormat($format, (string)$date);

        return $formattedDate && $formattedDate->format($format) === $date;
    }

    protected function makeSelectorTable(array $modSettings, ServerRequestInterface $request, string $enableList = 'table,fields,query,group,order,limit'): string
    {
        $out = [];
        $enableArr = explode(',', $enableList);
        $userTsConfig = $this->getBackendUserAuthentication()->getTSConfig();

        // Make output
        if (in_array('table', $enableArr) && !($userTsConfig['mod.']['dbint.']['disableSelectATable'] ?? false)) {
            $out[] = '<div class="form-group">';
            $out[] =     '<label class="form-label" for="SET[queryTable]">Select a table:</label>';
            $out[] =     $this->mkTableSelect('SET[queryTable]', $this->table);
            $out[] = '</div>';
        }
        if ($this->table) {
            // Init fields:
            $this->setAndCleanUpExternalLists('queryFields', $modSettings['queryFields'] ?? '', 'uid,' . $this->getLabelCol());
            $this->setAndCleanUpExternalLists('queryGroup', $modSettings['queryGroup'] ?? '');
            $this->setAndCleanUpExternalLists('queryOrder', ($modSettings['queryOrder'] ?? '') . ',' . ($modSettings['queryOrder2'] ?? ''));
            // Limit:
            $this->extFieldLists['queryLimit'] = $modSettings['queryLimit'] ?? '';
            if (!$this->extFieldLists['queryLimit']) {
                $this->extFieldLists['queryLimit'] = 100;
            }
            $parts = GeneralUtility::intExplode(',', (string)$this->extFieldLists['queryLimit']);
            $limitBegin = 0;
            $limitLength = (int)($this->extFieldLists['queryLimit']);
            if ($parts[1] ?? null) {
                $limitBegin = (int)$parts[0];
                $limitLength = (int)$parts[1];
            }
            $this->extFieldLists['queryLimit'] = implode(',', array_slice($parts, 0, 2));
            // Insert Descending parts
            if ($this->extFieldLists['queryOrder']) {
                $descParts = explode(',', ($modSettings['queryOrderDesc'] ?? '') . ',' . ($modSettings['queryOrder2Desc'] ?? ''));
                $orderParts = explode(',', $this->extFieldLists['queryOrder']);
                $reList = [];
                foreach ($orderParts as $kk => $vv) {
                    $reList[] = $vv . ($descParts[$kk] ? ' DESC' : '');
                }
                $this->extFieldLists['queryOrder_SQL'] = implode(',', $reList);
            }
            // Query Generator:
            $this->procesData($request, ($modSettings['queryConfig'] ?? '') ? unserialize((string)$modSettings['queryConfig'], ['allowed_classes' => false]) : []);
            $this->queryConfig = $this->cleanUpQueryConfig($this->queryConfig);
            $this->enableQueryParts = (bool)($modSettings['search_query_smallparts'] ?? false);
            $codeArr = $this->getFormElements();
            $queryCode = $this->printCodeArray($codeArr);
            if (in_array('fields', $enableArr) && !($userTsConfig['mod.']['dbint.']['disableSelectFields'] ?? false)) {
                $out[] = '<div class="form-group">';
                $out[] =   '<label class="form-label" for="SET[queryFields]">Select fields:</label>';
                $out[] =    $this->mkFieldToInputSelect('SET[queryFields]', $this->extFieldLists['queryFields']);
                $out[] = '</div>';
            }
            if (in_array('query', $enableArr) && !($userTsConfig['mod.']['dbint.']['disableMakeQuery'] ?? false)) {
                $out[] = '<div class="form-group">';
                $out[] =   '<label class="form-label">Make Query:</label>';
                $out[] =    $queryCode;
                $out[] = '</div>';
            }
            if (in_array('group', $enableArr) && !($userTsConfig['mod.']['dbint.']['disableGroupBy'] ?? false)) {
                $out[] = '<div class="form-group">';
                $out[] =   '<label class="form-label" for="SET[queryGroup]">Group By:</label>';
                $out[] =   $this->mkTypeSelect('SET[queryGroup]', $this->extFieldLists['queryGroup'], '');
                $out[] = '</div>';
            }
            if (in_array('order', $enableArr) && !($userTsConfig['mod.']['dbint.']['disableOrderBy'] ?? false)) {
                $orderByArr = explode(',', $this->extFieldLists['queryOrder']);
                $orderBy = [];
                $orderBy[] = '<div class="form-group">';
                $orderBy[] =   '<div class="input-group">';
                $orderBy[] =     $this->mkTypeSelect('SET[queryOrder]', $orderByArr[0], '');
                $orderBy[] =     '<div class="input-group-text">';
                $orderBy[] =       '<div class="form-check form-check-type-toggle">';
                $orderBy[] =         self::getFuncCheck(0, 'SET[queryOrderDesc]', $modSettings['queryOrderDesc'] ?? '', $request, '', '', 'id="checkQueryOrderDesc"');
                $orderBy[] =         '<label class="form-check-label" for="checkQueryOrderDesc">Descending</label>';
                $orderBy[] =       '</div>';
                $orderBy[] =     '</div>';
                $orderBy[] =   '</div>';
                $orderBy[] = '</div>';

                if ($orderByArr[0]) {
                    $orderBy[] = '<div class="form-group">';
                    $orderBy[] =   '<div class="input-group">';
                    $orderBy[] =     $this->mkTypeSelect('SET[queryOrder2]', $orderByArr[1] ?? '', '');
                    $orderBy[] =     '<div class="input-group-text">';
                    $orderBy[] =       '<div class="form-check form-check-type-toggle">';
                    $orderBy[] =         self::getFuncCheck(0, 'SET[queryOrder2Desc]', $modSettings['queryOrder2Desc'] ?? false, $request, '', '', 'id="checkQueryOrder2Desc"');
                    $orderBy[] =         '<label class="form-check-label" for="checkQueryOrder2Desc">Descending</label>';
                    $orderBy[] =       '</div>';
                    $orderBy[] =     '</div>';
                    $orderBy[] =   '</div>';
                    $orderBy[] = '</div>';
                }
                $out[] = '<div class="form-group">';
                $out[] = '  <label class="form-label">Order By:</label>';
                $out[] =    implode(LF, $orderBy);
                $out[] = '</div>';
            }
            if (in_array('limit', $enableArr) && !($userTsConfig['mod.']['dbint.']['disableLimit'] ?? false)) {
                $limit = [];
                $limit[] = '<div class="input-group">';
                $limit[] =   $this->updateIcon();
                $limit[] =   '<input type="text" class="form-control" value="' . htmlspecialchars($this->extFieldLists['queryLimit']) . '" name="SET[queryLimit]" id="queryLimit">';
                $limit[] = '</div>';

                $prevLimit = $limitBegin - $limitLength < 0 ? 0 : $limitBegin - $limitLength;
                $prevButton = '';
                $nextButton = '';

                if ($limitBegin) {
                    $prevButton = '<input type="button" class="btn btn-default" value="previous ' . htmlspecialchars((string)$limitLength) . '" data-value="' . htmlspecialchars($prevLimit . ',' . $limitLength) . '">';
                }
                if (!$limitLength) {
                    $limitLength = 100;
                }

                $nextLimit = $limitBegin + $limitLength;
                if ($nextLimit < 0) {
                    $nextLimit = 0;
                }
                if ($nextLimit) {
                    $nextButton = '<input type="button" class="btn btn-default" value="next ' . htmlspecialchars((string)$limitLength) . '" data-value="' . htmlspecialchars($nextLimit . ',' . $limitLength) . '">';
                }

                $out[] = '<div class="form-group">';
                $out[] = '  <label class="form-label">Limit:</label>';
                $out[] = '  <div class="form-row">';
                $out[] = '    <div class="form-group">';
                $out[] =        implode(LF, $limit);
                $out[] = '    </div>';
                $out[] = '    <div class="form-group">';
                $out[] = '      <div class="btn-group t3js-limit-submit">';
                $out[] =          $prevButton;
                $out[] =          $nextButton;
                $out[] = '      </div>';
                $out[] = '    </div>';
                $out[] = '    <div class="form-group">';
                $out[] = '      <div class="btn-group t3js-limit-submit">';
                $out[] = '        <input type="button" class="btn btn-default" data-value="10" value="10">';
                $out[] = '        <input type="button" class="btn btn-default" data-value="20" value="20">';
                $out[] = '        <input type="button" class="btn btn-default" data-value="50" value="50">';
                $out[] = '        <input type="button" class="btn btn-default" data-value="100" value="100">';
                $out[] = '      </div>';
                $out[] = '    </div>';
                $out[] = '  </div>';
                $out[] = '</div>';
            }
        }

        return implode(LF, $out);
    }

    protected function cleanUpQueryConfig(array $queryConfig): array
    {
        // Since we don't traverse the array using numeric keys in the upcoming while-loop make sure it's fresh and clean before displaying
        if (!empty($queryConfig) && is_array($queryConfig)) {
            ksort($queryConfig);
        } elseif (empty($queryConfig[0]['type'])) {
            // Make sure queryConfig is an array
            $queryConfig = [];
            $queryConfig[0] = ['type' => 'FIELD_'];
        }
        // Traverse:
        foreach ($queryConfig as $key => $conf) {
            $fieldName = '';
            if (str_starts_with(($conf['type'] ?? ''), 'FIELD_')) {
                $fieldName = substr($conf['type'], 6);
                $fieldType = $this->fields[$fieldName]['type'] ?? '';
            } elseif (($conf['type'] ?? '') === 'newlevel') {
                $fieldType = $conf['type'];
            } else {
                $fieldType = 'ignore';
            }
            switch ($fieldType) {
                case 'newlevel':
                    if (!$queryConfig[$key]['nl']) {
                        $queryConfig[$key]['nl'][0]['type'] = 'FIELD_';
                    }
                    $queryConfig[$key]['nl'] = $this->cleanUpQueryConfig($queryConfig[$key]['nl']);
                    break;
                case 'userdef':
                    break;
                case 'ignore':
                default:
                    $verifiedName = $this->verifyType($fieldName);
                    $queryConfig[$key]['type'] = 'FIELD_' . $this->verifyType($verifiedName);
                    if ((int)($conf['comparison'] ?? 0) >> 5 !== (int)($this->comp_offsets[$fieldType] ?? 0)) {
                        $conf['comparison'] = (int)($this->comp_offsets[$fieldType] ?? 0) << 5;
                    }
                    $queryConfig[$key]['comparison'] = $this->verifyComparison($conf['comparison'] ?? '' ? (string)$conf['comparison'] : '0', ($conf['negate'] ?? null) ? 1 : 0);
                    $queryConfig[$key]['inputValue'] = $this->cleanInputVal($queryConfig[$key]);
                    $queryConfig[$key]['inputValue1'] = $this->cleanInputVal($queryConfig[$key], '1');
            }
        }

        return $queryConfig;
    }

    protected function verifyType(string $fieldName): string
    {
        $first = '';
        foreach ($this->fields as $key => $value) {
            if (!$first) {
                $first = $key;
            }
            if ($key === $fieldName) {
                return $key;
            }
        }

        return $first;
    }

    /**
     * @param string $comparison
     */
    protected function verifyComparison($comparison, int $neg): int
    {
        $compOffSet = $comparison >> 5;
        $first = -1;
        for ($i = 32 * $compOffSet + $neg; $i < 32 * ($compOffSet + 1); $i += 2) {
            if ($first === -1) {
                $first = $i;
            }
            if ($i >> 1 === $comparison >> 1) {
                return $i;
            }
        }

        return $first;
    }

    /**
     * @param string $queryConfig
     */
    protected function getFormElements(int $subLevel = 0, $queryConfig = '', string $parent = ''): array
    {
        $codeArr = [];
        if (!is_array($queryConfig)) {
            $queryConfig = $this->queryConfig;
        }
        $c = 0;
        $arrCount = 0;
        $loopCount = 0;
        foreach ($queryConfig as $key => $conf) {
            $fieldName = '';
            $subscript = $parent . '[' . $key . ']';
            $lineHTML = [];
            $lineHTML[] = $this->mkOperatorSelect($this->name . $subscript, ($conf['operator'] ?? ''), (bool)$c, ($conf['type'] ?? '') !== 'FIELD_');
            if (str_starts_with(($conf['type'] ?? ''), 'FIELD_')) {
                $fieldName = substr($conf['type'], 6);
                $this->fieldName = $fieldName;
                $fieldType = $this->fields[$fieldName]['type'] ?? '';
                if ((int)($conf['comparison'] ?? 0) >> 5 !== (int)($this->comp_offsets[$fieldType] ?? 0)) {
                    $conf['comparison'] = (int)($this->comp_offsets[$fieldType] ?? 0) << 5;
                }
                //nasty nasty...
                //make sure queryConfig contains _actual_ comparevalue.
                //mkCompSelect don't care, but getQuery does.
                $queryConfig[$key]['comparison'] += isset($conf['negate']) - $conf['comparison'] % 2;
            } elseif (($conf['type'] ?? '') === 'newlevel') {
                $fieldType = $conf['type'];
            } else {
                $fieldType = 'ignore';
            }
            $fieldPrefix = htmlspecialchars($this->name . $subscript);
            switch ($fieldType) {
                case 'ignore':
                    break;
                case 'newlevel':
                    if (!$queryConfig[$key]['nl']) {
                        $queryConfig[$key]['nl'][0]['type'] = 'FIELD_';
                    }
                    $lineHTML[] = '<input type="hidden" name="' . $fieldPrefix . '[type]" value="newlevel">';
                    $codeArr[$arrCount]['sub'] = $this->getFormElements($subLevel + 1, $queryConfig[$key]['nl'], $subscript . '[nl]');
                    break;
                case 'userdef':
                    $lineHTML[] = '';
                    break;
                case 'date':
                    $lineHTML[] = '<div class="form-row">';
                    $lineHTML[] = $this->makeComparisonSelector($subscript, $fieldName, $conf);
                    if ($conf['comparison'] === 100 || $conf['comparison'] === 101) {
                        // between
                        $lineHTML[] = $this->getDateTimePickerField($fieldPrefix . '[inputValue]', (string)$conf['inputValue'], 'date');
                        $lineHTML[] = $this->getDateTimePickerField($fieldPrefix . '[inputValue1]', (string)$conf['inputValue1'], 'date');
                    } else {
                        $lineHTML[] = $this->getDateTimePickerField($fieldPrefix . '[inputValue]', (string)$conf['inputValue'], 'date');
                    }
                    $lineHTML[] = '</div>';
                    break;
                case 'time':
                    $lineHTML[] = '<div class="form-row">';
                    $lineHTML[] = $this->makeComparisonSelector($subscript, $fieldName, $conf);
                    if ($conf['comparison'] === 100 || $conf['comparison'] === 101) {
                        // between:
                        $lineHTML[] = $this->getDateTimePickerField($fieldPrefix . '[inputValue]', (string)$conf['inputValue'], 'datetime');
                        $lineHTML[] = $this->getDateTimePickerField($fieldPrefix . '[inputValue1]', (string)$conf['inputValue1'], 'datetime');
                    } else {
                        $lineHTML[] = $this->getDateTimePickerField($fieldPrefix . '[inputValue]', (string)$conf['inputValue'], 'datetime');
                    }
                    $lineHTML[] = '</div>';
                    break;
                case 'multiple':
                case 'binary':
                case 'relation':
                    $lineHTML[] = '<div class="form-row">';
                    $lineHTML[] = $this->makeComparisonSelector($subscript, $fieldName, $conf);
                    $lineHTML[] = '<div class="form-group">';
                    if ($conf['comparison'] === 68 || $conf['comparison'] === 69 || $conf['comparison'] === 162 || $conf['comparison'] === 163) {
                        $lineHTML[] = '<select class="form-select" name="' . $fieldPrefix . '[inputValue][]" multiple="multiple">';
                    } elseif ($conf['comparison'] === 66 || $conf['comparison'] === 67) {
                        if (is_array($conf['inputValue'])) {
                            $conf['inputValue'] = implode(',', $conf['inputValue']);
                        }
                        $lineHTML[] = '<input class="form-control form-control-clearable t3js-clearable" type="text" value="' . htmlspecialchars($conf['inputValue'] ?? '') . '" name="' . $fieldPrefix . '[inputValue]">';
                    } elseif ($conf['comparison'] === 64) {
                        if (is_array($conf['inputValue'])) {
                            $conf['inputValue'] = $conf['inputValue'][0];
                        }
                        $lineHTML[] = '<select class="form-select t3js-submit-change" name="' . $fieldPrefix . '[inputValue]">';
                    } else {
                        $lineHTML[] = '<select class="form-select t3js-submit-change" name="' . $fieldPrefix . '[inputValue]">';
                    }
                    if ($conf['comparison'] != 66 && $conf['comparison'] != 67) {
                        $lineHTML[] = $this->makeOptionList($fieldName, $conf, $this->table);
                        $lineHTML[] = '</select>';
                    }
                    $lineHTML[] = '</div>';
                    $lineHTML[] = '</div>';
                    break;
                case 'boolean':
                    $lineHTML[] = '<div class="form-row">';
                    $lineHTML[] = $this->makeComparisonSelector($subscript, $fieldName, $conf);
                    $lineHTML[] = '<input type="hidden" value="1" name="' . $fieldPrefix . '[inputValue]">';
                    $lineHTML[] = '</div>';
                    break;
                default:
                    $lineHTML[] = '<div class="form-row">';
                    $lineHTML[] = $this->makeComparisonSelector($subscript, $fieldName, $conf);
                    if ($conf['comparison'] === 37 || $conf['comparison'] === 36) {
                        // between:
                        $lineHTML[] = '<div class="form-group">';
                        $lineHTML[] = '  <input class="form-control form-control-clearable t3js-clearable" type="text" value="' . htmlspecialchars($conf['inputValue'] ?? '') . '" name="' . $fieldPrefix . '[inputValue]">';
                        $lineHTML[] = '</div>';
                        $lineHTML[] = '<div class="form-group">';
                        $lineHTML[] = '  <input class="form-control form-control-clearable t3js-clearable" type="text" value="' . htmlspecialchars($conf['inputValue1'] ?? '') . '" name="' . $fieldPrefix . '[inputValue1]">';
                        $lineHTML[] = '</div>';
                    } else {
                        $lineHTML[] = '<div class="form-group">';
                        $lineHTML[] = '  <input class="form-control form-control-clearable t3js-clearable" type="text" value="' . htmlspecialchars($conf['inputValue'] ?? '') . '" name="' . $fieldPrefix . '[inputValue]">';
                        $lineHTML[] = '</div>';
                    }
                    $lineHTML[] = '</div>';
            }
            if ($fieldType !== 'ignore') {
                $lineHTML[] = '<div class="form-row">';
                $lineHTML[] = '<div class="btn-group">';
                $lineHTML[] = $this->updateIcon();
                if ($loopCount) {
                    $lineHTML[] = ''
                        . '<button class="btn btn-default" title="Remove condition" name="qG_del' . htmlspecialchars($subscript) . '">'
                        . $this->iconFactory->getIcon('actions-delete', Icon::SIZE_SMALL)->render()
                        . '</button>';
                }
                $lineHTML[] = ''
                    . '<button class="btn btn-default" title="Add condition" name="qG_ins' . htmlspecialchars($subscript) . '">'
                    . $this->iconFactory->getIcon('actions-plus', Icon::SIZE_SMALL)->render()
                    . '</button>';
                if ($c != 0) {
                    $lineHTML[] = ''
                        . '<button class="btn btn-default" title="Move up" name="qG_up' . htmlspecialchars($subscript) . '">'
                        . $this->iconFactory->getIcon('actions-chevron-up', Icon::SIZE_SMALL)->render()
                        . '</button>';
                }
                if ($c != 0 && $fieldType !== 'newlevel') {
                    $lineHTML[] = ''
                        . '<button class="btn btn-default" title="New level" name="qG_nl' . htmlspecialchars($subscript) . '">'
                        . $this->iconFactory->getIcon('actions-chevron-right', Icon::SIZE_SMALL)->render()
                        . '</button>';
                }
                if ($fieldType === 'newlevel') {
                    $lineHTML[] = ''
                        . '<button class="btn btn-default" title="Collapse new level" name="qG_remnl' . htmlspecialchars($subscript) . '">'
                        . $this->iconFactory->getIcon('actions-chevron-left', Icon::SIZE_SMALL)->render()
                        . '</button>';
                }
                $lineHTML[] = '</div>';
                $lineHTML[] = '</div>';
                $codeArr[$arrCount]['html'] = implode(LF, $lineHTML);
                $codeArr[$arrCount]['query'] = $this->getQuerySingle($conf, $c === 0);
                $arrCount++;
                $c++;
            }
            $loopCount = 1;
        }
        $this->queryConfig = $queryConfig;

        return $codeArr;
    }

    protected function getDateTimePickerField(string $name, string $timestamp, string $type): string
    {
        $value = strtotime($timestamp) ? date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], (int)strtotime($timestamp)) : '';
        $id = StringUtility::getUniqueId('dt_');
        $html = [];
        $html[] = '<div class="form-group">';
        $html[] = '  <div class="input-group" id="' . $id . '-wrapper">';
        $html[] = '	   <input data-formengine-input-name="' . htmlspecialchars($name) . '" value="' . $value . '" class="form-control form-control-clearable t3js-datetimepicker t3js-clearable" data-date-type="' . htmlspecialchars($type) . '" type="text" id="' . $id . '">';
        $html[] = '	   <input name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($timestamp) . '" type="hidden">';
        $html[] = '	   <button class="btn btn-default" type="button" data-global-event="click" data-action-focus="#' . $id . '">';
        $html[] =          $this->iconFactory->getIcon('actions-calendar-alternative', Icon::SIZE_SMALL)->render();
        $html[] = '    </button>';
        $html[] = '  </div>';
        $html[] = '</div>';

        return implode(LF, $html);
    }

    protected function makeOptionList(string $fieldName, array $conf, string $table): string
    {
        $backendUserAuthentication = $this->getBackendUserAuthentication();
        $from_table_Arr = [];
        $out = [];
        $fieldSetup = $this->fields[$fieldName];
        $languageService = $this->getLanguageService();
        if ($fieldSetup['type'] === 'multiple') {
            $optGroupOpen = false;
            foreach (($fieldSetup['items'] ?? []) as $val) {
                $value = $languageService->sL($val['label']);
                if ($val['value'] === '--div--') {
                    if ($optGroupOpen) {
                        $out[] = '</optgroup>';
                    }
                    $optGroupOpen = true;
                    $out[] = '<optgroup label="' . htmlspecialchars($value) . '">';
                } elseif (GeneralUtility::inList($conf['inputValue'], (string)$val['value'])) {
                    $out[] = '<option value="' . htmlspecialchars((string)$val['value']) . '" selected>' . htmlspecialchars($value) . '</option>';
                } else {
                    $out[] = '<option value="' . htmlspecialchars((string)$val['value']) . '">' . htmlspecialchars($value) . '</option>';
                }
            }
            if ($optGroupOpen) {
                $out[] = '</optgroup>';
            }
        }
        if ($fieldSetup['type'] === 'binary') {
            foreach ($fieldSetup['items'] as $key => $val) {
                $value = $languageService->sL($val['label']);
                if (GeneralUtility::inList($conf['inputValue'], (string)(2 ** $key))) {
                    $out[] = '<option value="' . 2 ** $key . '" selected>' . htmlspecialchars($value) . '</option>';
                } else {
                    $out[] = '<option value="' . 2 ** $key . '">' . htmlspecialchars($value) . '</option>';
                }
            }
        }
        if ($fieldSetup['type'] === 'relation') {
            $useTablePrefix = 0;
            $dontPrefixFirstTable = 0;
            foreach (($fieldSetup['items'] ?? []) as $val) {
                $value = $languageService->sL($val['label']);
                if (GeneralUtility::inList($conf['inputValue'], (string)$val['value'])) {
                    $out[] = '<option value="' . htmlspecialchars((string)$val['value']) . '" selected>' . htmlspecialchars($value) . '</option>';
                } else {
                    $out[] = '<option value="' . htmlspecialchars((string)$val['value']) . '">' . htmlspecialchars($value) . '</option>';
                }
            }
            $allowedFields = $fieldSetup['allowed'] ?? '';
            if (str_contains($allowedFields, ',')) {
                $from_table_Arr = explode(',', $allowedFields);
                $useTablePrefix = 1;
                if (!$fieldSetup['prepend_tname']) {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                    $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                    $statement = $queryBuilder->select($fieldName)
                        ->from($table)
                        ->executeQuery();
                    while ($row = $statement->fetchAssociative()) {
                        if (str_contains($row[$fieldName], ',')) {
                            $checkContent = explode(',', $row[$fieldName]);
                            foreach ($checkContent as $singleValue) {
                                if (!str_contains($singleValue, '_')) {
                                    $dontPrefixFirstTable = 1;
                                }
                            }
                        } else {
                            $singleValue = $row[$fieldName];
                            if ($singleValue !== '' && !str_contains($singleValue, '_')) {
                                $dontPrefixFirstTable = 1;
                            }
                        }
                    }
                }
            } else {
                $from_table_Arr[0] = $allowedFields;
            }
            if (!empty($fieldSetup['prepend_tname'])) {
                $useTablePrefix = 1;
            }
            if (!empty($fieldSetup['foreign_table'])) {
                $from_table_Arr[0] = $fieldSetup['foreign_table'];
            }
            $counter = 0;
            $tablePrefix = '';
            $outArray = [];
            $labelFieldSelect = [];
            foreach ($from_table_Arr as $from_table) {
                $useSelectLabels = false;
                $useAltSelectLabels = false;
                if ($useTablePrefix && !$dontPrefixFirstTable && $counter !== 1 || $counter === 1) {
                    $tablePrefix = $from_table . '_';
                }
                $counter = 1;
                if (is_array($GLOBALS['TCA'][$from_table])) {
                    $labelField = $GLOBALS['TCA'][$from_table]['ctrl']['label'] ?? '';
                    $altLabelField = $GLOBALS['TCA'][$from_table]['ctrl']['label_alt'] ?? '';
                    if ($GLOBALS['TCA'][$from_table]['columns'][$labelField]['config']['items'] ?? false) {
                        foreach ($GLOBALS['TCA'][$from_table]['columns'][$labelField]['config']['items'] as $labelArray) {
                            $labelFieldSelect[$labelArray[1]] = $languageService->sL($labelArray[0]);
                        }
                        $useSelectLabels = true;
                    }
                    $altLabelFieldSelect = [];
                    if ($GLOBALS['TCA'][$from_table]['columns'][$altLabelField]['config']['items'] ?? false) {
                        foreach ($GLOBALS['TCA'][$from_table]['columns'][$altLabelField]['config']['items'] as $altLabelArray) {
                            $altLabelFieldSelect[$altLabelArray[1]] = $languageService->sL($altLabelArray[0]);
                        }
                        $useAltSelectLabels = true;
                    }

                    if (!($this->tableArray[$from_table] ?? false)) {
                        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($from_table);
                        $queryBuilder->getRestrictions()->removeAll();
                        if (empty($this->MOD_SETTINGS['show_deleted'])) {
                            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                        }
                        $selectFields = ['uid', $labelField];
                        if ($altLabelField) {
                            $selectFields = array_merge($selectFields, GeneralUtility::trimExplode(',', $altLabelField, true));
                        }
                        $queryBuilder->select(...$selectFields)
                            ->from($from_table)
                            ->orderBy('uid');
                        if (!$backendUserAuthentication->isAdmin()) {
                            $webMounts = $backendUserAuthentication->returnWebmounts();
                            $perms_clause = $backendUserAuthentication->getPagePermsClause(Permission::PAGE_SHOW);
                            $webMountPageTree = '';
                            $webMountPageTreePrefix = '';
                            foreach ($webMounts as $webMount) {
                                if ($webMountPageTree) {
                                    $webMountPageTreePrefix = ',';
                                }
                                $webMountPageTree .= $webMountPageTreePrefix
                                    . $this->getTreeList($webMount, 999, 0, $perms_clause);
                            }
                            if ($from_table === 'pages') {
                                $queryBuilder->where(
                                    QueryHelper::stripLogicalOperatorPrefix($perms_clause),
                                    $queryBuilder->expr()->in(
                                        'uid',
                                        $queryBuilder->createNamedParameter(
                                            GeneralUtility::intExplode(',', $webMountPageTree),
                                            Connection::PARAM_INT_ARRAY
                                        )
                                    )
                                );
                            } else {
                                $queryBuilder->where(
                                    $queryBuilder->expr()->in(
                                        'pid',
                                        $queryBuilder->createNamedParameter(
                                            GeneralUtility::intExplode(',', $webMountPageTree),
                                            Connection::PARAM_INT_ARRAY
                                        )
                                    )
                                );
                            }
                        }
                        $statement = $queryBuilder->executeQuery();
                        $this->tableArray[$from_table] = $statement->fetchAllAssociative();
                    }

                    foreach (($this->tableArray[$from_table] ?? []) as $val) {
                        if ($useSelectLabels) {
                            $outArray[$tablePrefix . $val['uid']] = htmlspecialchars($labelFieldSelect[$val[$labelField]]);
                        } elseif ($val[$labelField]) {
                            $outArray[$tablePrefix . $val['uid']] = htmlspecialchars($val[$labelField]);
                        } elseif ($useAltSelectLabels) {
                            $outArray[$tablePrefix . $val['uid']] = htmlspecialchars($altLabelFieldSelect[$val[$altLabelField]]);
                        } else {
                            $outArray[$tablePrefix . $val['uid']] = htmlspecialchars($val[$altLabelField]);
                        }
                    }
                    if (isset($this->MOD_SETTINGS['options_sortlabel']) && $this->MOD_SETTINGS['options_sortlabel'] && is_array($outArray)) {
                        natcasesort($outArray);
                    }
                }
            }
            foreach ($outArray as $key2 => $val2) {
                $key2 = (string)$key2;
                $val2 = (string)$val2;
                if (GeneralUtility::inList($conf['inputValue'], $key2)) {
                    $out[] = '<option value="' . htmlspecialchars($key2) . '" selected>[' . htmlspecialchars($key2) . '] ' . htmlspecialchars($val2) . '</option>';
                } else {
                    $out[] = '<option value="' . htmlspecialchars($key2) . '">[' . htmlspecialchars($key2) . '] ' . htmlspecialchars($val2) . '</option>';
                }
            }
        }

        return implode(LF, $out);
    }

    protected function mkOperatorSelect(string $name, string $op, bool $draw, bool $submit): string
    {
        $out = [];
        if ($draw) {
            $out[] = '<div class="form-group">';
            $out[] = '  <select class="form-select' . ($submit ? ' t3js-submit-change' : '') . '" name="' . htmlspecialchars($name) . '[operator]">';
            $out[] = '    <option value="AND"' . (!$op || $op === 'AND' ? ' selected' : '') . '>' . htmlspecialchars($this->lang['AND']) . '</option>';
            $out[] = '    <option value="OR"' . ($op === 'OR' ? ' selected' : '') . '>' . htmlspecialchars($this->lang['OR']) . '</option>';
            $out[] = '  </select>';
            $out[] = '</div>';
        } else {
            $out[] = '<input type="hidden" value="' . htmlspecialchars($op) . '" name="' . htmlspecialchars($name) . '[operator]">';
        }

        return implode(LF, $out);
    }

    protected function makeComparisonSelector(string $subscript, string $fieldName, array $conf): string
    {
        $fieldPrefix = $this->name . $subscript;
        $lineHTML = [];
        $lineHTML[] = '<div class="form-group">';
        $lineHTML[] =    $this->mkTypeSelect($fieldPrefix . '[type]', $fieldName);
        $lineHTML[] = '</div>';
        $lineHTML[] = '<div class="form-group">';
        $lineHTML[] = '  <div class="input-group">';
        $lineHTML[] =      $this->mkCompSelect($fieldPrefix . '[comparison]', (string)$conf['comparison'], ($conf['negate'] ?? null) ? 1 : 0);
        $lineHTML[] = '    <span class="input-group-addon">';
        $lineHTML[] = '      <div class="form-check form-check-type-toggle">';
        $lineHTML[] = '        <input type="checkbox" class="form-check-input t3js-submit-click"' . (($conf['negate'] ?? null) ? ' checked' : '') . ' name="' . htmlspecialchars($fieldPrefix) . '[negate]">';
        $lineHTML[] = '      </div>';
        $lineHTML[] = '    </span>';
        $lineHTML[] = '  </div>';
        $lineHTML[] = '</div>';

        return implode(LF, $lineHTML);
    }

    protected function mkCompSelect(string $name, string $comparison, int $neg): string
    {
        $compOffSet = $comparison >> 5;
        $out = [];
        $out[] = '<select class="form-select t3js-submit-change" name="' . $name . '">';
        for ($i = 32 * $compOffSet + $neg; $i < 32 * ($compOffSet + 1); $i += 2) {
            if ($this->lang['comparison'][$i . '_'] ?? false) {
                $out[] = '<option value="' . $i . '"' . ($i >> 1 === $comparison >> 1 ? ' selected' : '') . '>' . htmlspecialchars($this->lang['comparison'][$i . '_']) . '</option>';
            }
        }
        $out[] = '</select>';

        return implode(LF, $out);
    }

    protected function printCodeArray(array $codeArr, int $recursionLevel = 0): string
    {
        $out = [];
        foreach (array_values($codeArr) as $queryComponent) {
            $out[] = '<div class="card">';
            $out[] =     '<div class="card-body">';
            $out[] =         $queryComponent['html'];

            if ($this->enableQueryParts) {
                $out[] = '<pre class="language-sql">';
                $out[] =   '<code class="language-sql">';
                $out[] =     htmlspecialchars($queryComponent['query']);
                $out[] =   '</code>';
                $out[] = '</pre>';
            }
            if (is_array($queryComponent['sub'] ?? null)) {
                $out[] = $this->printCodeArray($queryComponent['sub'], $recursionLevel + 1);
            }
            $out[] =     '</div>';
            $out[] = '</div>';
        }

        return implode(LF, $out);
    }

    protected function mkFieldToInputSelect(string $name, string $fieldName): string
    {
        $out = [];
        $out[] = '<div class="input-group mb-1">';
        $out[] =   $this->updateIcon();
        $out[] =   '<input type="text" class="form-control form-control-clearable t3js-clearable" value="' . htmlspecialchars($fieldName) . '" name="' . htmlspecialchars($name) . '">';
        $out[] = '</div>';
        $out[] = '<select class="form-select t3js-addfield" name="_fieldListDummy" size="5" data-field="' . htmlspecialchars($name) . '">';
        foreach ($this->fields as $key => $value) {
            if (!$value['exclude'] || $this->getBackendUserAuthentication()->check('non_exclude_fields', $this->table . ':' . $key)) {
                $label = $this->fields[$key]['label'];
                if ($this->showFieldAndTableNames) {
                    $label .= ' [' . $key . ']';
                }
                $out[] = '<option value="' . htmlspecialchars($key) . '"' . ($key === $fieldName ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
            }
        }
        $out[] = '</select>';

        return implode(LF, $out);
    }

    protected function procesData(ServerRequestInterface $request, array $qC = []): void
    {
        $this->queryConfig = $qC;
        $POST = $request->getParsedBody();
        // If delete...
        if ($POST['qG_del'] ?? false) {
            // Initialize array to work on, save special parameters
            $ssArr = $this->getSubscript($POST['qG_del']);
            $workArr = &$this->queryConfig;
            $ssArrSize = count($ssArr) - 1;
            $i = 0;
            for (; $i < $ssArrSize; $i++) {
                $workArr = &$workArr[$ssArr[$i]];
            }
            // Delete the entry and move the other entries
            unset($workArr[$ssArr[$i]]);
            $workArrSize = count((array)$workArr);
            for ($j = $ssArr[$i]; $j < $workArrSize; $j++) {
                $workArr[$j] = $workArr[$j + 1];
                unset($workArr[$j + 1]);
            }
        }
        // If insert...
        if ($POST['qG_ins'] ?? false) {
            // Initialize array to work on, save special parameters
            $ssArr = $this->getSubscript($POST['qG_ins']);
            $workArr = &$this->queryConfig;
            $ssArrSize = count($ssArr) - 1;
            $i = 0;
            for (; $i < $ssArrSize; $i++) {
                $workArr = &$workArr[$ssArr[$i]];
            }
            // Move all entries above position where new entry is to be inserted
            $workArrSize = count((array)$workArr);
            for ($j = $workArrSize; $j > $ssArr[$i]; $j--) {
                $workArr[$j] = $workArr[$j - 1];
            }
            // Clear new entry position
            unset($workArr[$ssArr[$i] + 1]);
            $workArr[$ssArr[$i] + 1]['type'] = 'FIELD_';
        }
        // If move up...
        if ($POST['qG_up'] ?? false) {
            // Initialize array to work on
            $ssArr = $this->getSubscript($POST['qG_up']);
            $workArr = &$this->queryConfig;
            $ssArrSize = count($ssArr) - 1;
            $i = 0;
            for (; $i < $ssArrSize; $i++) {
                $workArr = &$workArr[$ssArr[$i]];
            }
            // Swap entries
            $qG_tmp = $workArr[$ssArr[$i]];
            $workArr[$ssArr[$i]] = $workArr[$ssArr[$i] - 1];
            $workArr[$ssArr[$i] - 1] = $qG_tmp;
        }
        // If new level...
        if ($POST['qG_nl'] ?? false) {
            // Initialize array to work on
            $ssArr = $this->getSubscript($POST['qG_nl']);
            $workArr = &$this->queryConfig;
            $ssArraySize = count($ssArr) - 1;
            $i = 0;
            for (; $i < $ssArraySize; $i++) {
                $workArr = &$workArr[$ssArr[$i]];
            }
            // Do stuff:
            $tempEl = $workArr[$ssArr[$i]];
            if (is_array($tempEl)) {
                if ($tempEl['type'] !== 'newlevel') {
                    $workArr[$ssArr[$i]] = [
                        'type' => 'newlevel',
                        'operator' => $tempEl['operator'],
                        'nl' => [$tempEl],
                    ];
                }
            }
        }
        // If collapse level...
        if ($POST['qG_remnl'] ?? false) {
            // Initialize array to work on
            $ssArr = $this->getSubscript($POST['qG_remnl']);
            $workArr = &$this->queryConfig;
            $ssArrSize = count($ssArr) - 1;
            $i = 0;
            for (; $i < $ssArrSize; $i++) {
                $workArr = &$workArr[$ssArr[$i]];
            }
            // Do stuff:
            $tempEl = $workArr[$ssArr[$i]];
            if (is_array($tempEl)) {
                if ($tempEl['type'] === 'newlevel' && is_array($workArr)) {
                    $a1 = array_slice($workArr, 0, $ssArr[$i]);
                    $a2 = array_slice($workArr, $ssArr[$i]);
                    array_shift($a2);
                    $a3 = $tempEl['nl'];
                    $a3[0]['operator'] = $tempEl['operator'];
                    $workArr = array_merge($a1, $a3, $a2);
                }
            }
        }
    }

    protected function getSubscript($arr): array
    {
        $retArr = [];
        while (\is_array($arr)) {
            reset($arr);
            $key = key($arr);
            $retArr[] = $key;
            if (isset($arr[$key])) {
                $arr = $arr[$key];
            } else {
                break;
            }
        }

        return $retArr;
    }

    protected function getLabelCol(): string
    {
        return $GLOBALS['TCA'][$this->table]['ctrl']['label'];
    }

    protected function mkTypeSelect(string $name, string $fieldName, string $prepend = 'FIELD_'): string
    {
        $out = [];
        $out[] = '<select class="form-select t3js-submit-change" name="' . htmlspecialchars($name) . '">';
        $out[] = '<option value=""></option>';
        foreach ($this->fields as $key => $value) {
            if (!($value['exclude'] ?? false) || $this->getBackendUserAuthentication()->check('non_exclude_fields', $this->table . ':' . $key)) {
                $label = $this->fields[$key]['label'];
                if ($this->showFieldAndTableNames) {
                    $label .= ' [' . $key . ']';
                }
                $out[] = '<option value="' . htmlspecialchars($prepend . $key) . '"' . ($key === $fieldName ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
            }
        }
        $out[] = '</select>';

        return implode(LF, $out);
    }

    protected function updateIcon(): string
    {
        return '<button class="btn btn-default" title="Update" name="just_update">' . $this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL)->render() . '</button>';
    }

    protected function setAndCleanUpExternalLists(string $name, string $list, string $force = ''): void
    {
        $fields = array_unique(GeneralUtility::trimExplode(',', $list . ',' . $force, true));
        $reList = [];
        foreach ($fields as $fieldName) {
            if (isset($this->fields[$fieldName])) {
                $reList[] = $fieldName;
            }
        }
        $this->extFieldLists[$name] = implode(',', $reList);
    }

    protected function mkTableSelect(string $name, string $cur): string
    {
        $out = [];
        $out[] = '<select class="form-select t3js-submit-change" name="' . $name . '">';
        $out[] = '<option value=""></option>';
        foreach ($GLOBALS['TCA'] as $tN => $value) {
            if ($this->getBackendUserAuthentication()->check('tables_select', $tN)) {
                $label = $this->getLanguageService()->sL($GLOBALS['TCA'][$tN]['ctrl']['title']);
                if ($this->showFieldAndTableNames) {
                    $label .= ' [' . $tN . ']';
                }
                $out[] = '<option value="' . htmlspecialchars($tN) . '"' . ($tN === $cur ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
            }
        }
        $out[] = '</select>';

        return implode(LF, $out);
    }

    /**
     * @param array $settings Module settings like checkboxes in the interface
     */
    protected function init(string $name, string $table, string $fieldList = '', array $settings = []): void
    {
        // Analysing the fields in the table.
        if (is_array($GLOBALS['TCA'][$table] ?? false)) {
            $this->name = $name;
            $this->table = $table;
            $this->fieldList = $fieldList ?: $this->makeFieldList();
            $this->MOD_SETTINGS = $settings;
            $fieldArr = GeneralUtility::trimExplode(',', $this->fieldList, true);
            foreach ($fieldArr as $fieldName) {
                $fC = $GLOBALS['TCA'][$this->table]['columns'][$fieldName] ?? [];
                $this->fields[$fieldName] = $fC['config'] ?? [];
                $this->fields[$fieldName]['exclude'] = $fC['exclude'] ?? '';
                if (($this->fields[$fieldName]['type'] ?? '') === 'user' && !isset($this->fields[$fieldName]['type']['userFunc'])
                    || ($this->fields[$fieldName]['type'] ?? '') === 'none'
                ) {
                    // Do not list type=none "virtual" fields or query them from db,
                    // and if type is user without defined userFunc
                    unset($this->fields[$fieldName]);
                    continue;
                }
                if (is_array($fC) && ($fC['label'] ?? false)) {
                    $this->fields[$fieldName]['label'] = rtrim(trim($this->getLanguageService()->sL($fC['label'])), ':');
                    switch ($this->fields[$fieldName]['type']) {
                        case 'input':
                            if (preg_match('/int|year/i', ($this->fields[$fieldName]['eval'] ?? ''))) {
                                $this->fields[$fieldName]['type'] = 'number';
                            } else {
                                $this->fields[$fieldName]['type'] = 'text';
                            }
                            break;
                        case 'number':
                            // Empty on purpose, we have to keep the type "number".
                            // Falling back to the "default" case would set the type to "text"
                            break;
                        case 'datetime':
                            if (!in_array($this->fields[$fieldName]['dbType'] ?? '', QueryHelper::getDateTimeTypes(), true)) {
                                $this->fields[$fieldName]['type'] = 'number';
                            } elseif ($this->fields[$fieldName]['dbType'] === 'time') {
                                $this->fields[$fieldName]['type'] = 'time';
                            } else {
                                $this->fields[$fieldName]['type'] = 'date';
                            }
                            break;
                        case 'check':
                            if (count($this->fields[$fieldName]['items'] ?? []) <= 1) {
                                $this->fields[$fieldName]['type'] = 'boolean';
                            } else {
                                $this->fields[$fieldName]['type'] = 'binary';
                            }
                            break;
                        case 'radio':
                            $this->fields[$fieldName]['type'] = 'multiple';
                            break;
                        case 'select':
                        case 'category':
                            $this->fields[$fieldName]['type'] = 'multiple';
                            if ($this->fields[$fieldName]['foreign_table'] ?? false) {
                                $this->fields[$fieldName]['type'] = 'relation';
                            }
                            if ($this->fields[$fieldName]['special'] ?? false) {
                                $this->fields[$fieldName]['type'] = 'text';
                            }
                            break;
                        case 'group':
                            $this->fields[$fieldName]['type'] = 'relation';
                            break;
                        case 'user':
                        case 'flex':
                        case 'passthrough':
                        case 'none':
                        case 'text':
                        case 'email':
                        case 'link':
                        case 'password':
                        case 'color':
                        case 'json':
                        case 'uuid':
                        default:
                            $this->fields[$fieldName]['type'] = 'text';
                    }
                } else {
                    $this->fields[$fieldName]['label'] = '[FIELD: ' . $fieldName . ']';
                    switch ($fieldName) {
                        case 'pid':
                            $this->fields[$fieldName]['type'] = 'relation';
                            $this->fields[$fieldName]['allowed'] = 'pages';
                            break;
                        case 'tstamp':
                        case 'crdate':
                            $this->fields[$fieldName]['type'] = 'time';
                            break;
                        case 'deleted':
                            $this->fields[$fieldName]['type'] = 'boolean';
                            break;
                        default:
                            $this->fields[$fieldName]['type'] = 'number';
                    }
                }
            }
        }
        /*	// EXAMPLE:
        $this->queryConfig = array(
        array(
        'operator' => 'AND',
        'type' => 'FIELD_space_before_class',
        ),
        array(
        'operator' => 'AND',
        'type' => 'FIELD_records',
        'negate' => 1,
        'inputValue' => 'foo foo'
        ),
        array(
        'type' => 'newlevel',
        'nl' => array(
        array(
        'operator' => 'AND',
        'type' => 'FIELD_space_before_class',
        'negate' => 1,
        'inputValue' => 'foo foo'
        ),
        array(
        'operator' => 'AND',
        'type' => 'FIELD_records',
        'negate' => 1,
        'inputValue' => 'foo foo'
        )
        )
        ),
        array(
        'operator' => 'OR',
        'type' => 'FIELD_maillist',
        )
        );
         */
    }

    protected function makeFieldList(): string
    {
        $fieldListArr = [];
        if (is_array($GLOBALS['TCA'][$this->table])) {
            $fieldListArr = array_keys($GLOBALS['TCA'][$this->table]['columns'] ?? []);
            $fieldListArr[] = 'uid';
            $fieldListArr[] = 'pid';
            $fieldListArr[] = 'deleted';
            if ($GLOBALS['TCA'][$this->table]['ctrl']['tstamp'] ?? false) {
                $fieldListArr[] = $GLOBALS['TCA'][$this->table]['ctrl']['tstamp'];
            }
            if ($GLOBALS['TCA'][$this->table]['ctrl']['crdate'] ?? false) {
                $fieldListArr[] = $GLOBALS['TCA'][$this->table]['ctrl']['crdate'];
            }
            if ($GLOBALS['TCA'][$this->table]['ctrl']['sortby'] ?? false) {
                $fieldListArr[] = $GLOBALS['TCA'][$this->table]['ctrl']['sortby'];
            }
        }

        return implode(',', $fieldListArr);
    }

    protected function makeStoreControl(): string
    {
        // Load/Save
        $storeArray = $this->initStoreArray();

        $opt = [];
        foreach ($storeArray as $k => $v) {
            $opt[] = '<option value="' . htmlspecialchars((string)$k) . '">' . htmlspecialchars((string)$v) . '</option>';
        }

        $markup = [];
        $markup[] = '<div class="form-row">';
        $markup[] = '  <div class="form-group">';
        $markup[] = '    <select class="form-select" name="storeControl[STORE]" data-assign-store-control-title>' . implode(LF, $opt) . '</select>';
        $markup[] = '  </div>';
        $markup[] = '  <div class="form-group">';
        $markup[] = '    <input class="form-control" name="storeControl[title]" value="" type="text" max="80">';
        $markup[] = '  </div>';
        $markup[] = '  <div class="form-group">';
        $markup[] = '    <button class="btn btn-default" type="submit" name="storeControl[LOAD]" value="Load">';
        $markup[] =        $this->iconFactory->getIcon('actions-upload', Icon::SIZE_SMALL)->render();
        $markup[] = '      Load';
        $markup[] = '    </button>';
        $markup[] = '    <button class="btn btn-default" type="submit" name="storeControl[SAVE]" value="Save">';
        $markup[] =        $this->iconFactory->getIcon('actions-save', Icon::SIZE_SMALL)->render();
        $markup[] = '      Save';
        $markup[] = '    </button>';
        $markup[] = '    <button class="btn btn-default" type="submit" name="storeControl[REMOVE]" value="Remove">';
        $markup[] =        $this->iconFactory->getIcon('actions-delete', Icon::SIZE_SMALL)->render();
        $markup[] = '      Remove';
        $markup[] = '    </button>';
        $markup[] = '  </div>';
        $markup[] = '</div>';

        return implode(LF, $markup);
    }

    protected function procesStoreControl(ServerRequestInterface $request): string
    {
        $languageService = $this->getLanguageService();
        $flashMessage = null;
        $storeArray = $this->initStoreArray();
        $storeQueryConfigs = (array)(unserialize($this->MOD_SETTINGS['storeQueryConfigs'] ?? '', ['allowed_classes' => false]));
        $storeControl = $request->getParsedBody()['storeControl'] ?? [];
        $storeIndex = (int)($storeControl['STORE'] ?? 0);
        $saveStoreArray = 0;
        $writeArray = [];
        $msg = '';
        if (is_array($storeControl)) {
            if ($storeControl['LOAD'] ?? false) {
                if ($storeIndex > 0) {
                    $writeArray = $this->loadStoreQueryConfigs($storeQueryConfigs, $storeIndex, $writeArray);
                    $saveStoreArray = 1;
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        sprintf($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_t3lib_fullsearch.xlf:query_loaded'), $storeArray[$storeIndex])
                    );
                }
            } elseif ($storeControl['SAVE'] ?? false) {
                if (trim($storeControl['title'])) {
                    if ($storeIndex > 0) {
                        $storeArray[$storeIndex] = $storeControl['title'];
                    } else {
                        $storeArray[] = $storeControl['title'];
                        end($storeArray);
                        $storeIndex = key($storeArray);
                    }
                    $storeQueryConfigs = $this->addToStoreQueryConfigs($storeQueryConfigs, (int)$storeIndex);
                    $saveStoreArray = 1;
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_t3lib_fullsearch.xlf:query_saved')
                    );
                }
            } elseif ($storeControl['REMOVE'] ?? false) {
                if ($storeIndex > 0) {
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        sprintf($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_t3lib_fullsearch.xlf:query_removed'), $storeArray[$storeControl['STORE']])
                    );
                    // Removing
                    unset($storeArray[$storeControl['STORE']]);
                    $saveStoreArray = 1;
                }
            }
            if (!empty($flashMessage)) {
                $msg = GeneralUtility::makeInstance(FlashMessageRendererResolver::class)
                    ->resolve()
                    ->render([$flashMessage]);
            }
        }
        if ($saveStoreArray) {
            // Making sure, index 0 is not set!
            unset($storeArray[0]);
            $writeArray['storeArray'] = serialize($storeArray);
            $writeArray['storeQueryConfigs'] =
                serialize($this->cleanStoreQueryConfigs($storeQueryConfigs, $storeArray));
            $this->MOD_SETTINGS = BackendUtility::getModuleData(
                $this->MOD_MENU,
                $writeArray,
                $this->moduleName,
                'ses'
            );
        }

        return $msg;
    }

    protected function cleanStoreQueryConfigs(array $storeQueryConfigs, array $storeArray): array
    {
        if (is_array($storeQueryConfigs)) {
            foreach ($storeQueryConfigs as $k => $v) {
                if (!isset($storeArray[$k])) {
                    unset($storeQueryConfigs[$k]);
                }
            }
        }

        return $storeQueryConfigs;
    }

    protected function addToStoreQueryConfigs(array $storeQueryConfigs, int $index): array
    {
        $keyArr = explode(',', $this->storeList);
        $storeQueryConfigs[$index] = [];
        foreach ($keyArr as $k) {
            $storeQueryConfigs[$index][$k] = $this->MOD_SETTINGS[$k] ?? null;
        }

        return $storeQueryConfigs;
    }

    protected function loadStoreQueryConfigs(array $storeQueryConfigs, int $storeIndex, array $writeArray): array
    {
        if ($storeQueryConfigs[$storeIndex]) {
            $keyArr = explode(',', $this->storeList);
            foreach ($keyArr as $k) {
                $writeArray[$k] = $storeQueryConfigs[$storeIndex][$k];
            }
        }

        return $writeArray;
    }

    protected function initStoreArray(): array
    {
        $storeArray = [
            '0' => '[New]',
        ];
        $savedStoreArray = unserialize($this->MOD_SETTINGS['storeArray'] ?? '', ['allowed_classes' => false]);
        if (is_array($savedStoreArray)) {
            $storeArray = array_merge($storeArray, $savedStoreArray);
        }

        return $storeArray;
    }

    protected function form(): string
    {
        $languageService = $this->getLanguageService();
        $markup = [];
        $markup[] = '<h2 id="search-options">' . htmlspecialchars($languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:searchOptions')) . '</h2>';
        $markup[] = '<div class="form-group">';
        $markup[] =   '<div class="input-group">';
        $markup[] =     '<input aria-labelledby="search-options" placeholder="' . htmlspecialchars($languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:search.placeholder')) . '" class="form-control" type="search" id="searchField" name="SET[sword]" value="' . htmlspecialchars($this->MOD_SETTINGS['sword'] ?? '') . '">';
        $markup[] =     '<button class="btn btn-default" disabled type="submit" name="submitSearch" id="submitSearch" title="' . htmlspecialchars($languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:search.submit')) . '">';
        $markup[] =       $this->iconFactory->getIcon('actions-search', Icon::SIZE_SMALL)->render();
        $markup[] =     '</button>';
        $markup[] =   '</div>';
        $markup[] = '</div>';

        return implode(LF, $markup);
    }

    protected function search(ServerRequestInterface $request): string
    {
        $swords = $this->MOD_SETTINGS['sword'] ?? '';
        $out = '';
        if ($swords) {
            foreach ($GLOBALS['TCA'] as $table => $value) {
                // Get fields list
                $conf = $GLOBALS['TCA'][$table];
                // Avoid querying tables with no columns
                if (empty($conf['columns'])) {
                    continue;
                }
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
                $tableColumns = $connection->createSchemaManager()->listTableColumns($table);
                $normalizedTableColumns = [];
                $fieldsInDatabase = [];
                foreach ($tableColumns as $column) {
                    $fieldsInDatabase[] = $column->getName();
                    $normalizedTableColumns[trim($column->getName(), $connection->getDatabasePlatform()->getIdentifierQuoteCharacter())] = $column;
                }
                $fields = array_intersect(array_keys($conf['columns']), $fieldsInDatabase);

                $queryBuilder = $connection->createQueryBuilder();
                $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                $queryBuilder->count('*')->from($table);
                $likes = [];
                $escapedLikeString = '%' . $queryBuilder->escapeLikeWildcards($swords) . '%';
                foreach ($fields as $field) {
                    $field = trim($field, $connection->getDatabasePlatform()->getIdentifierQuoteCharacter());
                    $quotedField = $queryBuilder->quoteIdentifier($field);
                    $column = $normalizedTableColumns[$field] ?? $normalizedTableColumns[$quotedField] ?? null;
                    if ($column !== null
                        && $connection->getDatabasePlatform() instanceof PostgreSQLPlatform
                        && !in_array($column->getType()->getName(), [Types::STRING, Types::ASCII_STRING, Types::JSON], true)
                    ) {
                        if ($column->getType()->getName() === Types::SMALLINT) {
                            // we need to cast smallint to int first, otherwise text case below won't work
                            $quotedField .= '::int';
                        }
                        $quotedField .= '::text';
                    }
                    $likes[] = $queryBuilder->expr()->comparison(
                        $quotedField,
                        'LIKE',
                        $queryBuilder->createNamedParameter($escapedLikeString)
                    );
                }
                $queryBuilder->orWhere(...$likes);
                $count = $queryBuilder->executeQuery()->fetchOne();

                if ($count > 0) {
                    $queryBuilder = $connection->createQueryBuilder();
                    $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                    $queryBuilder->select('uid', $conf['ctrl']['label'])
                        ->from($table)
                        ->setMaxResults(200);
                    $likes = [];
                    foreach ($fields as $field) {
                        $field = trim($field, $connection->getDatabasePlatform()->getIdentifierQuoteCharacter());
                        $quotedField = $queryBuilder->quoteIdentifier($field);
                        $column = $normalizedTableColumns[$field] ?? $normalizedTableColumns[$quotedField] ?? null;
                        if ($column !== null
                            && $connection->getDatabasePlatform() instanceof PostgreSQLPlatform
                            && !in_array($column->getType()->getName(), [Types::STRING, Types::ASCII_STRING, Types::JSON], true)
                        ) {
                            if ($column->getType()->getName() === Types::SMALLINT) {
                                // we need to cast smallint to int first, otherwise text case below won't work
                                $quotedField .= '::int';
                            }
                            $quotedField .= '::text';
                        }
                        $likes[] = $queryBuilder->expr()->comparison(
                            $quotedField,
                            'LIKE',
                            $queryBuilder->createNamedParameter($escapedLikeString)
                        );
                    }
                    $statement = $queryBuilder->orWhere(...$likes)->executeQuery();
                    $lastRow = null;
                    $rowArr = [];
                    while ($row = $statement->fetchAssociative()) {
                        $rowArr[] = $this->resultRowDisplay($row, $conf, $table, $request);
                        $lastRow = $row;
                    }
                    $markup = [];
                    $markup[] = '<div class="panel panel-default">';
                    $markup[] = '  <div class="panel-heading">';
                    $markup[] = htmlspecialchars($this->getLanguageService()->sL($conf['ctrl']['title'])) . ' (' . $count . ')';
                    $markup[] = '  </div>';
                    $markup[] = '  <div class="table-fit">';
                    $markup[] = '    <table class="table table-striped table-hover">';
                    $markup[] = $this->resultRowTitles((array)$lastRow, $conf);
                    $markup[] = implode(LF, $rowArr);
                    $markup[] = '    </table>';
                    $markup[] = '  </div>';
                    $markup[] = '</div>';

                    $out .= implode(LF, $markup);
                }
            }
        }

        return $out;
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
                if ($setup['value'] !== '--div--') {
                    $doktypes[] = [
                        'icon' => $this->iconFactory->getIconForRecord('pages', ['doktype' => $setup['value']], Icon::SIZE_SMALL)->render(),
                        'title' => $languageService->sL($setup['label']) . ' (' . $setup['value'] . ')',
                        'count' => (int)($databaseIntegrityCheck->getRecStats()['doktype'][$setup['value']] ?? 0),
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
                                    '<a href="' . htmlspecialchars($fixLink) . '" title="' . htmlspecialchars($languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fixLostRecord')) . '">' .
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

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    //################################
    // copied over from BackendUtility to enable deprecation of the original method
    // @todo finish fluidification of template and remove HTML generation from controller
    //################################

    /**
     * Returns a selector box to switch the view
     * Based on BackendUtility::getFuncMenu() but done as new function because it has another purpose.
     * Mingling with getFuncMenu would harm the docHeader Menu.
     *
     * @param mixed $mainParams The "&id=" parameter value to be sent to the module, but it can be also a parameter array which will be passed instead of the &id=...
     * @param string $elementName The form elements name, probably something like "SET[...]
     * @param string|int $currentValue The value to be selected currently.
     * @param array $menuItems An array with the menu items for the selector box
     * @param string $script The script to send the &id to, if empty it's automatically found
     * @param string $addParams Additional parameters to pass to the script.
     * @param array $additionalAttributes Additional attributes for the select element
     * @return string HTML code for selector box
     */
    protected static function getDropdownMenu(
        $mainParams,
        $elementName,
        $currentValue,
        $menuItems,
        ServerRequestInterface $request,
        $script = '',
        $addParams = '',
        array $additionalAttributes = []
    ) {
        if (!is_array($menuItems) || count($menuItems) <= 1) {
            return '';
        }
        $scriptUrl = self::buildScriptUrl($mainParams, $addParams, $request, $script);
        $options = [];
        foreach ($menuItems as $value => $label) {
            $options[] = '<option value="'
                . htmlspecialchars($value) . '"'
                . ((string)$currentValue === (string)$value ? ' selected="selected"' : '') . '>'
                . htmlspecialchars($label, ENT_COMPAT, 'UTF-8', false) . '</option>';
        }
        $dataMenuIdentifier = str_replace(['SET[', ']'], '', $elementName);
        $dataMenuIdentifier = GeneralUtility::camelCaseToLowerCaseUnderscored($dataMenuIdentifier);
        $dataMenuIdentifier = str_replace('_', '-', $dataMenuIdentifier);
        // relies on module 'TYPO3/CMS/Backend/ActionDispatcher'
        $attributes = GeneralUtility::implodeAttributes(array_merge([
            'name' => $elementName,
            'data-menu-identifier' => $dataMenuIdentifier,
            'data-global-event' => 'change',
            'data-action-navigate' => '$data=~s/$value/',
            'data-navigate-value' => $scriptUrl . '&' . $elementName . '=${value}',
        ], $additionalAttributes), true);

        return '
            <select class="form-select" ' . $attributes . '>
                ' . implode(LF, $options) . '
            </select>';
    }

    /**
     * Checkbox function menu.
     * Works like ->getFuncMenu() but takes no $menuItem array since this is a simple checkbox.
     *
     * @param mixed $mainParams $id is the "&id=" parameter value to be sent to the module, but it can be also a parameter array which will be passed instead of the &id=...
     * @param string $elementName The form elements name, probably something like "SET[...]
     * @param string|bool $currentValue The value to be selected currently.
     * @param string $script The script to send the &id to, if empty it's automatically found
     * @param string $addParams Additional parameters to pass to the script.
     * @param string $tagParams Additional attributes for the checkbox input tag
     * @return string HTML code for checkbox
     * @see getFuncMenu()
     */
    protected static function getFuncCheck(
        $mainParams,
        $elementName,
        $currentValue,
        ServerRequestInterface $request,
        $script = '',
        $addParams = '',
        $tagParams = ''
    ) {
        // relies on module 'TYPO3/CMS/Backend/ActionDispatcher'
        $scriptUrl = self::buildScriptUrl($mainParams, $addParams, $request, $script);
        $attributes = GeneralUtility::implodeAttributes([
            'type' => 'checkbox',
            'class' => 'form-check-input',
            'name' => $elementName,
            'value' => '1',
            'data-global-event' => 'change',
            'data-action-navigate' => '$data=~s/$value/',
            'data-navigate-value' => sprintf('%s&%s=${value}', $scriptUrl, $elementName),
            'data-empty-value' => '0',
        ], true);

        return
            '<input ' . $attributes .
            ($currentValue ? ' checked="checked"' : '') .
            ($tagParams ? ' ' . $tagParams : '') .
            ' />';
    }

    /**
     * Builds the URL to the current script with given arguments
     *
     * @param mixed $mainParams $id is the "&id=" parameter value to be sent to the module, but it can be also a parameter array which will be passed instead of the &id=...
     * @param string $addParams Additional parameters to pass to the script.
     * @param string $script The script to send the &id to, if empty it's automatically found
     * @return string The complete script URL
     * @todo Check if this can be removed or replaced by routing
     */
    protected static function buildScriptUrl($mainParams, string $addParams, ServerRequestInterface $request, string $script = '')
    {
        if (!is_array($mainParams)) {
            $mainParams = ['id' => $mainParams];
        }

        $route = $request->getAttribute('route');
        if ($route instanceof Route) {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $scriptUrl = (string)$uriBuilder->buildUriFromRoute($route->getOption('_identifier'), $mainParams);
            $scriptUrl .= $addParams;
        } else {
            if (!$script) {
                $script = PathUtility::basename(Environment::getCurrentScript());
            }
            $scriptUrl = $script . HttpUtility::buildQueryString($mainParams, '?') . $addParams;
        }

        return $scriptUrl;
    }
}
