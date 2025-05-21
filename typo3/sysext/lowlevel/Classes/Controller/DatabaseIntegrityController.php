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
use Doctrine\DBAL\Platforms\MariaDBPlatform as DoctrineMariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform as DoctrineMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform as DoctrinePostgreSQLPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Platform\PlatformHelper;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\DateFormatter;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageRendererResolver;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Schema\Capability\LabelCapability;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
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
#[AsController]
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
    protected string $moduleName = 'system_dbint';

    /**
     * If the current user is an admin and $GLOBALS['TYPO3_CONF_VARS']['BE']['debug']
     * is set to true, the names of fields and tables are displayed.
     */
    protected bool $showFieldAndTableNames = false;
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
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly PlatformHelper $platformHelper,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
        protected readonly FlashMessageRendererResolver $flashMessageRendererResolver,
        protected readonly PageDoktypeRegistry $pageDoktypeRegistry,
    ) {}

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
                'refindex' => $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:manageRefIndex'),
                'records' => $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:recordStatistics'),
                'search' => $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch'),
            ],
            'search' => [
                'raw' => $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:rawSearch'),
                'query' => $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:advancedQuery'),
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
                'all' => $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:selectRecords'),
                'count' => $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:countResults'),
                'explain' => $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:explainQuery'),
                'csv' => $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:csvExport'),
            ],
            'sword' => '',
        ];

        // EXPLAIN is no ANSI SQL, for now this is only executed on mysql
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        $platform = $connection->getDatabasePlatform();
        if (!($platform instanceof DoctrineMariaDBPlatform || $platform instanceof DoctrineMySQLPlatform)) {
            unset($this->MOD_MENU['search_query_makeQuery']['explain']);
        }

        // CLEAN SETTINGS
        $OLD_MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, [], $this->moduleName, 'ses');
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, $parsedBody['SET'] ?? $queryParams['SET'] ?? [], $this->moduleName, 'ses');
        $queryConfig = $parsedBody['queryConfig'] ?? $queryParams['queryConfig'] ?? false;
        if ($queryConfig) {
            $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, ['queryConfig' => serialize($queryConfig)], $this->moduleName, 'ses');
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
            $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, $this->MOD_SETTINGS, $this->moduleName, 'ses');
        }
    }

    /**
     * Generate doc header drop-down and shortcut button.
     */
    protected function setUpDocHeader(ModuleTemplate $moduleTemplate): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
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

        $menu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('DatabaseJumpMenu');
        $menu->setLabel(
            $this->getLanguageService()->sL(
                'LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:module.dbint.docheader.viewmode'
            )
        );
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
        $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Check and update reference index.
     */
    protected function referenceIndexAction(ModuleTemplate $view, ServerRequestInterface $request): ResponseInterface
    {
        $isUpdate = $request->getParsedBody()['update'] ?? false;
        $isCheckOnly = (bool)($request->getParsedBody()['checkOnly'] ?? false);
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

        $searchTypeSelect = '';
        $searchTypeSelect .= '<div class="form-group">';
        $searchTypeSelect .=   '<label for="search" class="form-label">' . $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.form.field.searchType.label') . '</label>';
        $searchTypeSelect .=   '<div class="input-group">' . $this->getDropdownMenu('SET[search]', $searchMode, $this->MOD_MENU['search'], $request) . '</div>';
        $searchTypeSelect .= '</div>';

        $queryTypeSelect = '';
        $queryOptions = '';
        if ($searchMode === 'query') {
            $queryTypeSelect .= '<div class="form-group">';
            $queryTypeSelect .=   '<label for="search-search-query-make-query" class="form-label">' . $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.form.field.makeQuery.label') . '</label>';
            $queryTypeSelect .=   '<div class="input-group">' . $this->getDropdownMenu('SET[search_query_makeQuery]', $this->MOD_SETTINGS['search_query_makeQuery'], $this->MOD_MENU['search_query_makeQuery'], $request) . '</div>';
            $queryTypeSelect .= '</div>';

            $queryOptions .= '<div class="form-row">';
            $queryOptions .=   '<div class="form-group">';
            $queryOptions .=     '<fieldset>';
            $queryOptions .=       '<legend class="form-label">' . $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.section.queryOptions') . '</legend>';
            $queryOptions .=       '<div class="form-check form-switch form-check-size-input">' . $this->getFuncCheck('SET[search_query_smallparts]', $this->MOD_SETTINGS['search_query_smallparts'] ?? '', $request, 'id="checkSearch_query_smallparts"')
                . '<label class="form-check-label" for="checkSearch_query_smallparts">' . $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:showSQL') . '</label></div>';
            $queryOptions .=       '<div class="form-check form-switch form-check-size-input">' . $this->getFuncCheck('SET[search_result_labels]', $this->MOD_SETTINGS['search_result_labels'] ?? '', $request, 'id="checkSearch_result_labels"')
                . '<label class="form-check-label" for="checkSearch_result_labels">' . $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:useFormattedStrings') . '</label></div>';
            $queryOptions .=       '<div class="form-check form-switch form-check-size-input">' . $this->getFuncCheck('SET[labels_noprefix]', $this->MOD_SETTINGS['labels_noprefix'] ?? '', $request, 'id="checkLabels_noprefix"')
                . '<label class="form-check-label" for="checkLabels_noprefix">' . $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:dontUseOrigValues') . '</label></div>';
            $queryOptions .=       '<div class="form-check form-switch form-check-size-input">' . $this->getFuncCheck('SET[options_sortlabel]', $this->MOD_SETTINGS['options_sortlabel'] ?? '', $request, 'id="checkOptions_sortlabel"')
                . '<label class="form-check-label" for="checkOptions_sortlabel">' . $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:sortOptions') . '</label></div>';
            $queryOptions .=       '<div class="form-check form-switch form-check-size-input">' . $this->getFuncCheck('SET[show_deleted]', $this->MOD_SETTINGS['show_deleted'] ?? 0, $request, 'id="checkShow_deleted"')
                . '<label class="form-check-label" for="checkShow_deleted">' . $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:showDeleted') . '</label></div>';
            $queryOptions .=     '</fieldset>';
            $queryOptions .=   '</div>';
            $queryOptions .= '</div>';
        }

        $view->assignMultiple([
            'queryOptions' => $queryOptions,
            'queryTypeSelect' => $queryTypeSelect,
            'searchMode' => $searchMode,
            'searchTypeSelect' => $searchTypeSelect,
        ]);

        switch ($searchMode) {
            case 'query':
                $view->assign('queryMaker', $this->queryMaker($request));
                break;
            case 'raw':
            default:
                $view->assign('sword', (string)($this->MOD_SETTINGS['sword'] ?? ''));
                $view->assign('results', $this->search($request));
                $view->assign('isSearching', $request->getMethod() === 'POST');
        }

        return $view->renderResponse('CustomSearch');
    }

    protected function queryMaker(ServerRequestInterface $request): string
    {
        $lang = $this->getLanguageService();
        $output = '';
        $msg = $this->procesStoreControl($request);
        $userTsConfig = $this->getBackendUserAuthentication()->getTSConfig();
        if (!($userTsConfig['mod.']['dbint.']['disableStoreControl'] ?? false)) {
            $output .= '<div class="card">';
            $output .=   '<div class="card-body">';
            $output .=     '<h2 class="card-title">' . $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.section.queryStorage') . '</h2>';
            $output .=       $this->makeStoreControl();
            $output .=     '<div class="card-text">' . $msg . '</div>';
            $output .=   '</div>';
            $output .= '</div>';
        }

        // Query Maker:
        $this->init('queryConfig', $this->MOD_SETTINGS['queryTable'] ?? '', '', $this->MOD_SETTINGS);

        $output .=  '<h2>' . $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.section.querySettings') . '</h2>';
        $output .=   '<fieldset class="form-section">';
        $output .=     $this->makeSelectorTable($this->MOD_SETTINGS, $request);
        $output .=   '</fieldset>';
        $mQ = $this->MOD_SETTINGS['search_query_makeQuery'] ?? '';

        // Make form elements:
        if ($this->table && $this->tcaSchemaFactory->has($this->table)) {
            if ($mQ) {
                // Show query
                $this->enablePrefix = true;
                $queryString = $this->getQuery($this->queryConfig);
                $selectQueryString = $this->getSelectQuery($queryString);
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);
                $platform = $connection->getDatabasePlatform();

                $isConnectionMysql = ($platform instanceof DoctrineMariaDBPlatform || $platform instanceof DoctrineMySQLPlatform);
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
                        $output .= '<h2>' . $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.section.querySQL') . '</h2>';
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
                        $output .= '<h2>' . $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.section.querySQL') . '</h2>';
                        $output .= '<pre class="language-sql">';
                        $output .=   '<code class="language-sql">';
                        $output .=     htmlspecialchars($fullQueryString);
                        $output .=   '</code>';
                        $output .= '</pre>';
                    }
                    $output .= '<h2>' . $lang->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.section.querySQL.error') . '</h2>';
                    $output .= '<div class="alert alert-danger">';
                    $output .=   '<p class="alert-message"><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
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
        $deleteField = '';
        $schema = $this->tcaSchemaFactory->get($this->table);
        if ($schema->hasCapability(TcaSchemaCapability::SoftDelete)) {
            $deleteField = $schema->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName();
        }
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
            $webMounts = $backendUserAuthentication->getWebmounts();
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
            $queryBuilder->select('uid')
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
        $languageService = $this->getLanguageService();
        $out = '';
        $cPR = [];
        $cPR['header'] = $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.section.result');
        switch ($type) {
            case 'count':
                $cPR['content'] = '<p><strong>' . (int)$dataRows[0] . '</strong> ' . $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.type.count.resultsFound') . '</p>';
                break;
            case 'all':
                $rowArr = [];
                $dataRow = null;
                foreach ($dataRows as $dataRow) {
                    $rowArr[] = $this->resultRowDisplay($dataRow, $table, $request);
                }
                if (!empty($rowArr)) {
                    $out .= '<div class="table-fit">';
                    $out .= '<table class="table table-striped table-hover">';
                    $out .= $this->resultRowTitles((array)$dataRow, $table) . implode(LF, $rowArr);
                    $out .= '</table>';
                    $out .= '</div>';
                } else {
                    $out .= '<p>' . $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.type.all.noResultsFound') . '</p>';
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
                    $rowArr[] = $this->csvValues($dataRow, $table);
                }
                if (!empty($rowArr)) {
                    $out .= '<div class="form-group">';
                    $out .= '<textarea class="form-control" name="whatever" rows="20" class="font-monospace" style="width:100%">';
                    $out .= htmlspecialchars(implode(LF, $rowArr));
                    $out .= '</textarea>';
                    $out .= '</div>';
                    if (!$this->noDownloadB) {
                        $out .= '<button class="btn btn-default" type="submit" name="download_file" value="Click to download file">';
                        $out .=    $this->iconFactory->getIcon('actions-file-csv-download', IconSize::SMALL)->render();
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
                    $out .= '<p>' . $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.type.all.noResultsFound') . '</p>';
                    $this->renderNoResultsFoundMessage();
                }
                $cPR['content'] = $out;
                break;
            case 'explain':
            default:
                foreach ($dataRows as $dataRow) {
                    $out .= DebugUtility::viewArray($dataRow);
                }
                $cPR['content'] = $out;
        }

        return $cPR;
    }

    protected function csvValues(array $row, string $table = ''): string
    {
        $valueArray = $row;
        if (($this->MOD_SETTINGS['search_result_labels'] ?? false) && $table) {
            foreach ($valueArray as $key => $val) {
                $valueArray[$key] = $this->getProcessedValueExtra($table, $key, (string)$val, ';');
            }
        }

        return CsvUtility::csvValues($valueArray);
    }

    /**
     * @param array|null $row Table columns
     */
    protected function resultRowTitles(?array $row, string $table): string
    {
        $languageService = $this->getLanguageService();
        $tableHeader = [];
        // Start header row
        $tableHeader[] = '<thead><tr>';
        // Iterate over given columns
        $schema = $this->tcaSchemaFactory->get($table);
        foreach ($row ?? [] as $fieldName => $fieldValue) {
            if (GeneralUtility::inList($this->MOD_SETTINGS['queryFields'] ?? '', $fieldName)
                || !($this->MOD_SETTINGS['queryFields'] ?? false)
                && $fieldName !== 'pid'
                && $fieldName !== 'deleted'
            ) {
                if ($this->MOD_SETTINGS['search_result_labels'] ?? false) {
                    $title  = null;
                    // Note: "uid" is not part of the regular schema definition. In this case we fallback to the $fieldName.
                    if ($schema->hasField($fieldName)) {
                        $title = $schema->getField($fieldName)->getLabel();
                        $title = $languageService->sL($title);
                    }
                    $title = $title ?: $fieldName;
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

    protected function resultRowDisplay(array $row, string $table, ServerRequestInterface $request): string
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
                    $fVnew = $this->getProcessedValueExtra($table, $fieldName, (string)$fieldValue, '<br>');
                } else {
                    $fVnew = htmlspecialchars((string)$fieldValue);
                }
                $out .= '<td>' . $fVnew . '</td>';
            }
        }
        $out .= '<td class="col-control">';

        if (!($row['deleted'] ?? false)) {
            // "Edit"
            $editActionUrl = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    $table => [
                        $row['uid'] => 'edit',
                    ],
                ],
                'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri()
                    . HttpUtility::buildQueryString(['SET' => $request->getParsedBody()['SET'] ?? []], '&'),
            ]);
            $editAction = '<a class="btn btn-default" href="' . htmlspecialchars($editActionUrl) . '"'
                . ' title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:edit')) . '">'
                . $this->iconFactory->getIcon('actions-open', IconSize::SMALL)->render()
                . '</a>';

            // "Info"
            $infoActionTitle = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:showInfo'));
            $infoAction = sprintf(
                '<a class="btn btn-default" href="#" title="' . $infoActionTitle . '" data-dispatch-action="%s" data-dispatch-args-list="%s">%s</a>',
                'TYPO3.InfoWindow.showItem',
                htmlspecialchars($table . ',' . $row['uid']),
                $this->iconFactory->getIcon('actions-document-info', IconSize::SMALL)->render()
            );

            $out .= '<div class="btn-group" role="group">' . $editAction . $infoAction . '</div>';
        } else {
            $undeleteActionUrl = (string)$this->uriBuilder->buildUriFromRoute('tce_db', [
                'cmd' => [
                    $table => [
                        $row['uid'] => [
                            'undelete' => 1,
                        ],
                    ],
                ],
                'redirect' => (string)$this->uriBuilder->buildUriFromRoute($this->moduleName),
            ]);
            $undeleteAction = '<a class="btn btn-default" href="' . htmlspecialchars($undeleteActionUrl) . '"'
                . ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_t3lib_fullsearch.xlf:undelete_only')) . '">'
                . $this->iconFactory->getIcon('actions-edit-restore', IconSize::SMALL)->render()
                . '</a>';
            $out .= '<div class="btn-group" role="group">' . $undeleteAction . '</div>';
        }
        $out .= '</td></tr>';

        return $out;
    }

    protected function getProcessedValueExtra(string $table, string $fieldName, string $fieldValue, string $splitString): string
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
        if ($this->tcaSchemaFactory->has($table)) {
            $schema = $this->tcaSchemaFactory->get($table);

            if (!$schema->hasField($fieldName)) {
                // happens for "uid", "pid", ... fields
                // We can shortcut this for these fields and jump
                // straight to the fallback case of an undefined fieldLabel.
                // @todo: Again, this is so wrong.
                $fieldLabel = null;
            } else {
                $fieldType = $schema->getField($fieldName);
                $fieldLabel = $fieldType->getLabel();
                $fields = $fieldType->getConfiguration();
                $fields['exclude'] = $fields['exclude'] ?? false;
                if ($fieldLabel) {
                    $fields['label'] = preg_replace('/:$/', '', trim($this->getLanguageService()->sL($fieldType->getLabel())));
                }
            }

            if ($fieldLabel) {
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
                    case 'country':
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
                    if ($splitString === '<br>') {
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
            $useSelectLabels = false;
            $useAltSelectLabels = false;
            $tablePrefix = '';
            $labelFieldSelect = [];
            foreach ($from_table_Arr as $from_table) {
                if ($useTablePrefix && !$dontPrefixFirstTable && $counter !== 1 || $counter === 1) {
                    $tablePrefix = $from_table . '_';
                }
                $counter = 1;
                if (!$this->tcaSchemaFactory->has($from_table)) {
                    continue;
                }
                $selectFields = ['uid'];
                $schema = $this->tcaSchemaFactory->get($from_table);
                $labelCapability = $schema->getCapability(TcaSchemaCapability::Label);
                $labelFieldName = null;
                $altLabelFieldName = null;

                if ($labelCapability->getPrimaryField()) {
                    $labelField = $labelCapability->getPrimaryField();
                    $labelFieldName = $labelField->getName();
                    $selectFields[] = $labelFieldName;
                    if ($labelField->isType(TableColumnType::SELECT)) {
                        foreach ($labelField->getConfiguration()['items'] ?? [] as $item) {
                            $item = SelectItem::fromTcaItemArray($item);
                            if ($item->isDivider()) {
                                continue;
                            }
                            $labelFieldSelect[$item->getValue()] = $languageService->sL($item->getLabel());
                        }
                        $useSelectLabels = true;
                    }
                }
                $altLabelFieldSelect = [];
                foreach ($labelCapability->getAdditionalFields() as $altLabelField) {
                    $selectFields[] = $altLabelField->getName();
                    if ($altLabelField->isType(TableColumnType::SELECT)) {
                        foreach ($altLabelField->getConfiguration()['items'] ?? [] as $item) {
                            $item = SelectItem::fromTcaItemArray($item);
                            if ($item->isDivider()) {
                                continue;
                            }
                            $altLabelFieldSelect[$item->getValue()] = $languageService->sL($item->getLabel());
                        }
                        $altLabelFieldName = $altLabelField->getName();
                        // We only take the first alt-label field
                        $useAltSelectLabels = true;
                        break;
                    }
                }

                if (empty($this->tableArray[$from_table])) {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($from_table);
                    $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                    $queryBuilder->select(...$selectFields)
                        ->from($from_table)
                        ->orderBy('uid');
                    if (!$backendUserAuthentication->isAdmin()) {
                        $webMounts = $backendUserAuthentication->getWebmounts();
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

                foreach ($this->tableArray[$from_table] as $val) {
                    $this->MOD_SETTINGS['labels_noprefix'] =
                        ($this->MOD_SETTINGS['labels_noprefix'] ?? '') == 1
                            ? 'on'
                            : $this->MOD_SETTINGS['labels_noprefix'] ?? '';
                    $prefixString =
                        $this->MOD_SETTINGS['labels_noprefix'] === 'on'
                            ? ''
                            : ' [' . $tablePrefix . $val['uid'] . '] ';
                    if (GeneralUtility::inList($fieldValue, $tablePrefix . $val['uid'])
                        || $fieldValue == $tablePrefix . $val['uid']) {
                        // Multiple matching records are separated by a newline inside the same HTML cell
                        if ($out !== '') {
                            $out .= $splitString;
                        }

                        $out .= $this->evaluateRelationDisplayWithLabels($useSelectLabels, $useAltSelectLabels, $labelCapability, $altLabelFieldName, $val, $labelFieldSelect, $altLabelFieldSelect, $labelFieldName);
                    }
                }
            }
        }

        return $out;
    }

    private function renderNoResultsFoundMessage(): void
    {
        $languageService = $this->getLanguageService();
        $flashMessageText = $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.flashMessage.noResultsFoundMessage');
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $flashMessageText, '', ContextualFeedbackSeverity::INFO);
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

    protected function cleanInputVal(array $conf, string $suffix = ''): mixed
    {
        $comparison = (int)($conf['comparison'] ?? 0);
        $var = $conf['inputValue' . $suffix] ?? '';
        if ($comparison >> 5 === 0 || ($comparison === 32 || $comparison === 33 || $comparison === 64 || $comparison === 65 || $comparison === 66 || $comparison === 67 || $comparison === 96 || $comparison === 97)) {
            $inputVal = $var;
        } elseif ($comparison === 39 || $comparison === 38) {
            // in list:
            $inputVal = implode(',', GeneralUtility::intExplode(',', (string)$var));
        } elseif ($comparison === 68 || $comparison === 69 || $comparison === 162 || $comparison === 163) {
            // in list:
            if (is_array($var)) {
                $inputVal = implode(',', $var);
            } elseif ($var) {
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
            $inputVal = (float)$var;
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

    protected function makeSelectorTable(array $modSettings, ServerRequestInterface $request): string
    {
        $languageService = $this->getLanguageService();
        $out = [];
        $enableArr = ['table', 'fields', 'query', 'group', 'order', 'limit'];
        $userTsConfig = $this->getBackendUserAuthentication()->getTSConfig();

        // Make output

        // Open form row
        $out[] = '<div class="row">';
        if (in_array('table', $enableArr) && !($userTsConfig['mod.']['dbint.']['disableSelectATable'] ?? false)) {
            $out[] = '<div class="form-group">';
            $out[] =   '<label class="form-label" for="select-table">' . $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.form.field.queryTable.label') . '</label>';
            $out[] =   $this->mkTableSelect('SET[queryTable]', $this->table);
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
                $out[] =   '<label class="form-label" for="select-queryFields">' . $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.form.field.queryFields.label') . '</label>';
                $out[] =    $this->mkFieldToInputSelect('SET[queryFields]', $this->extFieldLists['queryFields']);
                $out[] = '</div>';
            }
            if (in_array('query', $enableArr) && !($userTsConfig['mod.']['dbint.']['disableMakeQuery'] ?? false)) {
                $out[] = '<div class="form-group">';
                $out[] =   '<label class="form-label">' . $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.form.field.query.label') . '</label>';
                $out[] =    $queryCode;
                $out[] = '</div>';
            }

            // 'Group by'
            if (in_array('group', $enableArr) && !($userTsConfig['mod.']['dbint.']['disableGroupBy'] ?? false)) {
                $out[] = '<div class="form-group col-sm-6">';
                $out[] =   '<label class="form-label" for="SET[queryGroup]">' . $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.form.field.groupBy.label') . '</label>';
                $out[] =   $this->mkTypeSelect('SET[queryGroup]', $this->extFieldLists['queryGroup'], '');
                $out[] = '</div>';
            }

            // 'Order by'
            if (in_array('order', $enableArr) && !($userTsConfig['mod.']['dbint.']['disableOrderBy'] ?? false)) {
                $orderByArr = explode(',', $this->extFieldLists['queryOrder']);
                $orderBy = [];
                $orderBy[] = '<div class="form-group">';
                $orderBy[] =   '<div class="input-group">';
                $orderBy[] =     $this->mkTypeSelect('SET[queryOrder]', $orderByArr[0], '');
                $orderBy[] =     '<div class="input-group-text">';
                $orderBy[] =       '<div class="form-check form-check-type-toggle">';
                $orderBy[] =         $this->getFuncCheck('SET[queryOrderDesc]', $modSettings['queryOrderDesc'] ?? '', $request, 'id="checkQueryOrderDesc"');
                $orderBy[] =         '<label class="form-check-label" for="checkQueryOrderDesc">' . $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.form.field.orderBy.descending') . '</label>';
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
                    $orderBy[] =         $this->getFuncCheck('SET[queryOrder2Desc]', $modSettings['queryOrder2Desc'] ?? false, $request, 'id="checkQueryOrder2Desc"');
                    $orderBy[] =         '<label class="form-check-label" for="checkQueryOrder2Desc">' . $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.form.field.orderBy.descending') . '</label>';
                    $orderBy[] =       '</div>';
                    $orderBy[] =     '</div>';
                    $orderBy[] =   '</div>';
                    $orderBy[] = '</div>';
                }

                $out[] = '<div class="form-group col-sm-6">';
                $out[] =   '<label class="form-label">' . $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.form.field.orderBy.label') . '</label>';
                $out[] =   implode(LF, $orderBy);
                $out[] = '</div>';
            }

            // 'Limit'
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

                $out[] = '  <div class="form-group">';
                $out[] = '    <label for="queryLimit" class="form-label">' . $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.form.field.limit.label') . '</label>';
                $out[] = '    <div class="form-row">';
                $out[] = '      <div class="form-group">';
                $out[] =          implode(LF, $limit);
                $out[] = '      </div>';
                $out[] = '      <div class="form-group">';
                $out[] = '        <div class="btn-group t3js-limit-submit">';
                $out[] =            $prevButton;
                $out[] =            $nextButton;
                $out[] = '        </div>';
                $out[] = '      </div>';
                $out[] = '      <div class="form-group">';
                $out[] = '        <div class="btn-group t3js-limit-submit">';
                $out[] = '          <input type="button" class="btn btn-default" data-value="10" value="10">';
                $out[] = '          <input type="button" class="btn btn-default" data-value="20" value="20">';
                $out[] = '          <input type="button" class="btn btn-default" data-value="50" value="50">';
                $out[] = '          <input type="button" class="btn btn-default" data-value="100" value="100">';
                $out[] = '        </div>';
                $out[] = '      </div>';
                $out[] = '    </div>';
                $out[] = '  </div>';
            }
        }
        $out[] = '</div>';

        return implode(LF, $out);
    }

    protected function cleanUpQueryConfig(array $queryConfig): array
    {
        // Since we don't traverse the array using numeric keys in the upcoming while-loop make sure it's fresh and clean before displaying
        if (!empty($queryConfig)) {
            ksort($queryConfig);
        } else {
            $queryConfig = [['type' => 'FIELD_']];
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
                    if (!isset($conf['nl'])) {
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
                    $queryConfig[$key]['comparison'] = $this->verifyComparison($conf['comparison'] ?? 0 ? (int)$conf['comparison'] : 0, (bool)($conf['negate'] ?? null));
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

    protected function verifyComparison(int $comparison, bool $neg): int
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

    protected function getFormElements(int $subLevel = 0, string|array|null $queryConfig = null, string $parent = ''): array
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
                    if (!is_array($queryConfig[$key]['nl'] ?? null)) {
                        $queryConfig[$key]['nl'] = [];
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
                    $lineHTML[] =   '<div class="form-group col col-sm-4">';
                    if ($conf['comparison'] === 68 || $conf['comparison'] === 69 || $conf['comparison'] === 162 || $conf['comparison'] === 163) {
                        $lineHTML[] = '<select class="form-select" name="' . $fieldPrefix . '[inputValue][]" multiple="multiple">';
                    } elseif ($conf['comparison'] === 66 || $conf['comparison'] === 67) {
                        if (is_array($conf['inputValue'] ?? null)) {
                            $conf['inputValue'] = implode(',', $conf['inputValue']);
                        }
                        $lineHTML[] = '<input class="form-control form-control-clearable t3js-clearable" type="text" value="' . htmlspecialchars($conf['inputValue'] ?? '') . '" name="' . $fieldPrefix . '[inputValue]">';
                    } elseif ($conf['comparison'] === 64) {
                        if (is_array($conf['inputValue'] ?? null)) {
                            $conf['inputValue'] = $conf['inputValue'][0];
                        }
                        $lineHTML[] = '<select class="form-select t3js-submit-change" name="' . $fieldPrefix . '[inputValue]">';
                    } else {
                        $lineHTML[] = '<select class="form-select t3js-submit-change" name="' . $fieldPrefix . '[inputValue]">';
                    }
                    if ($conf['comparison'] != 66 && $conf['comparison'] != 67) {
                        $lineHTML[] =   $this->makeOptionList($fieldName, $conf, $this->table);
                        $lineHTML[] = '</select>';
                    }
                    $lineHTML[] =   '</div>';
                    $lineHTML[] = '</div>';
                    break;
                case 'boolean':
                    $lineHTML[] = '<div class="form-row">';
                    $lineHTML[] =   $this->makeComparisonSelector($subscript, $fieldName, $conf);
                    $lineHTML[] =   '<input type="hidden" value="1" name="' . $fieldPrefix . '[inputValue]">';
                    $lineHTML[] = '</div>';
                    break;
                default:
                    $lineHTML[] = '<div class="form-row">';
                    $lineHTML[] = $this->makeComparisonSelector($subscript, $fieldName, $conf);
                    if ($conf['comparison'] === 37 || $conf['comparison'] === 36) {
                        // between:
                        $lineHTML[] = '<div class="form-group col col-sm-2">';
                        $lineHTML[] = '  <input class="form-control form-control-clearable t3js-clearable" type="text" value="' . htmlspecialchars((string)($conf['inputValue'] ?? '')) . '" name="' . $fieldPrefix . '[inputValue]">';
                        $lineHTML[] = '</div>';
                        $lineHTML[] = '<div class="form-group col col-sm-2">';
                        $lineHTML[] = '  <input class="form-control form-control-clearable t3js-clearable" type="text" value="' . htmlspecialchars((string)($conf['inputValue1'] ?? '')) . '" name="' . $fieldPrefix . '[inputValue1]">';
                        $lineHTML[] = '</div>';
                    } else {
                        if (is_array($conf['inputValue'] ?? null)) {
                            $conf['inputValue'] = '';
                        }
                        $lineHTML[] = '<div class="form-group col col-sm-4">';
                        $lineHTML[] = '  <input class="form-control form-control-clearable t3js-clearable" type="text" value="' . htmlspecialchars((string)$conf['inputValue']) . '" name="' . $fieldPrefix . '[inputValue]">';
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
                        . $this->iconFactory->getIcon('actions-delete', IconSize::SMALL)->render()
                        . '</button>';
                }
                $lineHTML[] = ''
                    . '<button class="btn btn-default" title="Add condition" name="qG_ins' . htmlspecialchars($subscript) . '">'
                    . $this->iconFactory->getIcon('actions-plus', IconSize::SMALL)->render()
                    . '</button>';
                if ($c != 0) {
                    $lineHTML[] = ''
                        . '<button class="btn btn-default" title="Move up" name="qG_up' . htmlspecialchars($subscript) . '">'
                        . $this->iconFactory->getIcon('actions-chevron-up', IconSize::SMALL)->render()
                        . '</button>';
                }
                if ($c != 0 && $fieldType !== 'newlevel') {
                    $lineHTML[] = ''
                        . '<button class="btn btn-default" title="New level" name="qG_nl' . htmlspecialchars($subscript) . '">'
                        . $this->iconFactory->getIcon('actions-chevron-right', IconSize::SMALL)->render()
                        . '</button>';
                }
                if ($fieldType === 'newlevel') {
                    $lineHTML[] = ''
                        . '<button class="btn btn-default" title="Collapse new level" name="qG_remnl' . htmlspecialchars($subscript) . '">'
                        . $this->iconFactory->getIcon('actions-chevron-left', IconSize::SMALL)->render()
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
        $html[] =          $this->iconFactory->getIcon('actions-calendar-alternative', IconSize::SMALL)->render();
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
                if ($this->tcaSchemaFactory->has($from_table)) {
                    $schema = $this->tcaSchemaFactory->get($from_table);
                    $labelCapability = $schema->getCapability(TcaSchemaCapability::Label);
                    $labelFieldName = null;
                    $altLabelFieldName = null;
                    $selectFields = ['uid'];

                    if ($labelCapability->getPrimaryField()) {
                        $labelField = $labelCapability->getPrimaryField();
                        $labelFieldName = $labelField->getName();
                        $selectFields[] = $labelFieldName;
                        if ($labelField->isType(TableColumnType::SELECT)) {
                            foreach ($labelField->getConfiguration()['items'] ?? [] as $item) {
                                $item = SelectItem::fromTcaItemArray($item);
                                if ($item->isDivider()) {
                                    continue;
                                }
                                $labelFieldSelect[$item->getValue()] = $languageService->sL($item->getLabel());
                            }
                            $useSelectLabels = true;
                        }
                    }
                    $altLabelFieldSelect = [];
                    foreach ($labelCapability->getAdditionalFields() as $altLabelField) {
                        $selectFields[] = $altLabelField->getName();
                        if ($altLabelField->isType(TableColumnType::SELECT)) {
                            foreach ($altLabelField->getConfiguration()['items'] ?? [] as $item) {
                                $item = SelectItem::fromTcaItemArray($item);
                                if ($item->isDivider()) {
                                    continue;
                                }
                                $altLabelFieldSelect[$item->getValue()] = $languageService->sL($item->getLabel());
                            }
                            $altLabelFieldName = $altLabelField->getName();
                            // We only take the first alt-label field
                            $useAltSelectLabels = true;
                            break;
                        }
                    }

                    if (!($this->tableArray[$from_table] ?? false)) {
                        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($from_table);
                        $queryBuilder->getRestrictions()->removeAll();
                        if (empty($this->MOD_SETTINGS['show_deleted'])) {
                            $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                        }
                        $queryBuilder->select(...$selectFields)
                            ->from($from_table)
                            ->orderBy('uid');
                        if (!$backendUserAuthentication->isAdmin()) {
                            $webMounts = $backendUserAuthentication->getWebmounts();
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
                        $outArray[$tablePrefix . $val['uid']] = $this->evaluateRelationDisplayWithLabels($useSelectLabels, $useAltSelectLabels, $labelCapability, $altLabelFieldName, $val, $labelFieldSelect, $altLabelFieldSelect, $labelFieldName);
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

    /**
     * Helper method to evaluate a specific field configuration and decide which label to return.
     * This is used for both the dropdown when choosing a WHERE condition, but also for the record list itself,
     * when inline relations are resolved in case the option "[search_result_labels]" is set.
     * @param bool $useSelectLabels - Whether foreign resolving of a primary TCA 'label' field is required
     * @param bool $useAltSelectLabels - Whether foreign resolving of the FIRST TCA 'label_alt' relation field is required
     * @param LabelCapability $labelCapability - Schema capability information, used here for the table's label/label_alt evaluation
     * @param string|null $altLabelFieldName - The name of the matched first TCA 'label_alt' relation field
     * @param array $val - The DB SQL result row array
     * @param array $labelFieldSelect - An array holding the possible select values of a 'label' relation
     * @param array $altLabelFieldSelect - An array holding the possible select values of a 'label_alt' relation
     * @param string|null $labelFieldName - The name of the primary TCA column used for the label
     * @return string
     * @todo Please refactor me.
     */
    protected function evaluateRelationDisplayWithLabels(bool $useSelectLabels, bool $useAltSelectLabels, LabelCapability $labelCapability, ?string $altLabelFieldName, array $val, array $labelFieldSelect, array $altLabelFieldSelect, ?string $labelFieldName): string
    {
        // Several checks here to decide whether:
        // 1. the primary label field contains resolved selectable values,
        // 2. or a straight field value (no relation) for the primary label field is set,
        // 3. or a fallback to the FIRST label_alt relation exists (guaranteed that ONE select relation exists!)
        // 4. or a label_alt configuration is used where NO relations exist (final fallback)
        // (this piece of code is similar (but not identical) in makeOptionList() AND makeValueList()!
        if ($useSelectLabels) {
            return htmlspecialchars($labelFieldSelect[$val[$labelFieldName]]);
        }
        if ($val[$labelFieldName] ?? false) {
            return htmlspecialchars($val[$labelFieldName]);
        }
        if ($useAltSelectLabels) {
            if (isset($altLabelFieldSelect[$val[$altLabelFieldName]])) {
                // For example, altLabelFieldName=CType (for tt_content) and the row's CType is set to "text", this would return a string like "Regular Text element"
                // Resolved labels are already html-encoded.
                return $altLabelFieldSelect[$val[$altLabelFieldName]];
            }

            // For old/invalid item associations (like CType=list), display the hardcoded value here instead the resolved item
            return '[' . htmlspecialchars($val[$altLabelFieldName]) . ']';
        }
        // This case happens when NO relations exist. Iterate existing label_alt configuration and
        // take the first non-empty value.
        foreach ($labelCapability->getAdditionalFields() as $altLabelField) {
            if ($val[$altLabelField->getName()]) {
                // First altLabelField that matches concludes the output.
                return htmlspecialchars($val[$altLabelField->getName()]);
            }
        }
        // This happens when NONE of the label_alt fields contained an entry. We still need to be able to
        // match this field, so we put in a special empty indicator ('').
        return '';
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
        $languageService = $this->getLanguageService();
        $fieldPrefix = $this->name . $subscript;
        $lineHTML = [];
        $lineHTML[] = '<div class="form-group col col-sm-4">';
        $lineHTML[] =    $this->mkTypeSelect($fieldPrefix . '[type]', $fieldName);
        $lineHTML[] = '</div>';
        $lineHTML[] = '<div class="form-group col">';
        $lineHTML[] = '  <div class="input-group">';
        $lineHTML[] =      $this->mkCompSelect($fieldPrefix . '[comparison]', (int)$conf['comparison'], ($conf['negate'] ?? null) ? 1 : 0);
        $lineHTML[] = '    <span class="input-group-text">';
        $lineHTML[] = '      <div class="form-check form-check-type-toggle">';
        $lineHTML[] = '        <input type="checkbox" id="negateComparison" class="form-check-input t3js-submit-click"' . (($conf['negate'] ?? null) ? ' checked' : '') . ' name="' . htmlspecialchars($fieldPrefix) . '[negate]">';
        $lineHTML[] = '        <label class="form-check-label" for="negateComparison">' . $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.form.field.queryConfig.comparison.negate') . '</label>';
        $lineHTML[] = '      </div>';
        $lineHTML[] = '    </span>';
        $lineHTML[] = '  </div>';
        $lineHTML[] = '</div>';

        return implode(LF, $lineHTML);
    }

    protected function mkCompSelect(string $name, int $comparison, int $neg): string
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
        foreach ($codeArr as $queryComponent) {
            $out[] = '<div class="card">';
            $out[] =     '<div class="card-body">';
            $out[] =         $queryComponent['html'];

            if ($this->enableQueryParts) {
                $out[] = '<pre class="language-sql">';
                $out[] =   '<code class="language-sql">' . htmlspecialchars($queryComponent['query']) . '</code>';
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
        $out[] =   '<input type="text" class="form-control form-control-clearable t3js-clearable" value="' . htmlspecialchars($fieldName) . '" name="' . htmlspecialchars($name) . '" id="select-queryFields">';
        $out[] = '</div>';
        $out[] = '<select class="form-select t3js-addfield" name="_fieldListDummy" size="5" data-field="' . htmlspecialchars($name) . '">';
        foreach ($this->fields as $key => $value) {
            if (!($value['exclude'] ?? false) || $this->getBackendUserAuthentication()->check('non_exclude_fields', $this->table . ':' . $key)) {
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
        $schema = $this->tcaSchemaFactory->get($this->table);
        if ($schema->hasCapability(TcaSchemaCapability::Label) && $schema->getCapability(TcaSchemaCapability::Label)->hasPrimaryField()) {
            return $schema->getCapability(TcaSchemaCapability::Label)->getPrimaryField()->getName();
        }
        return '';
    }

    protected function mkTypeSelect(string $name, string $fieldName, string $prepend = 'FIELD_'): string
    {
        $out = [];
        $out[] = '<select class="form-select t3js-submit-change" name="' . htmlspecialchars($name) . '" id="' . htmlspecialchars($name) . '">';
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
        return '<button class="btn btn-default" title="Update" name="just_update">' . $this->iconFactory->getIcon('actions-refresh', IconSize::SMALL)->render() . '</button>';
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
        $tables = [];
        /** @var TcaSchema $schema */
        foreach ($this->tcaSchemaFactory->all() as $tableName => $schema) {
            $tableTitle = $this->getLanguageService()->sL($schema->getRawConfiguration()['title'] ?? '');
            if (!$tableTitle || $this->showFieldAndTableNames) {
                $tableTitle .= ' [' . $tableName . ']';
            }
            $tables[$tableName] = trim($tableTitle);
        }
        asort($tables);

        $out = [];
        $out[] = '<select class="form-select t3js-submit-change" name="' . $name . '" id="select-table">';
        $out[] = '<option value=""></option>';
        foreach ($tables as $tableName => $label) {
            if ($this->getBackendUserAuthentication()->check('tables_select', $tableName)) {
                $out[] = '<option value="' . htmlspecialchars($tableName) . '"' . ($tableName === $cur ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
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
        if ($this->tcaSchemaFactory->has($table)) {
            $this->name = $name;
            $this->table = $table;
            $this->fieldList = $fieldList ?: $this->makeFieldList();
            $this->MOD_SETTINGS = $settings;
            $schema = $this->tcaSchemaFactory->get($this->table);
            $fieldArr = GeneralUtility::trimExplode(',', $this->fieldList, true);
            foreach ($fieldArr as $fieldName) {
                if (!$schema->hasField($fieldName)) {
                    $this->fields[$fieldName]['label'] = '[FIELD: ' . $fieldName . ']';
                    switch ($fieldName) {
                        case 'pid':
                            $this->fields[$fieldName]['type'] = 'relation';
                            $this->fields[$fieldName]['allowed'] = 'pages';
                            break;
                            // @todo: this is so wrong
                        case 'tstamp':
                            // @todo: this is so wrong
                        case 'crdate':
                            $this->fields[$fieldName]['type'] = 'time';
                            break;
                            // @todo: this is so wrong
                        case 'deleted':
                            $this->fields[$fieldName]['type'] = 'boolean';
                            break;
                        default:
                            // @todo: this is so wrong
                            $this->fields[$fieldName]['type'] = 'number';
                    }
                    continue;
                }
                $fieldType = $schema->getField($fieldName);
                // Do not list type=none "virtual" fields or query them from db,
                // and if type=user without defined renderType
                if ($fieldType->isType(TableColumnType::NONE)) {
                    continue;
                }
                if ($fieldType->isType(TableColumnType::USER) && !isset($fieldType->getConfiguration()['renderType'])) {
                    continue;
                }
                // @todo: catch this in SchemaFactory / TcaMigration all the time.
                if ($fieldType->getLabel() === '') {
                    continue;
                }
                $this->fields[$fieldName] = $fieldType->getConfiguration();
                $this->fields[$fieldName]['exclude'] = $fieldType->supportsAccessControl();
                $this->fields[$fieldName]['label'] = rtrim(trim($this->getLanguageService()->sL($fieldType->getLabel())), ':');
                switch ($fieldType->getType()) {
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
                    default:
                        // includes other field types like: user, flex, passthrough, none, text, email, link, password, color, json, uuid, country
                        $this->fields[$fieldName]['type'] = 'text';
                }
            }
            uasort($this->fields, static fn($fieldA, $fieldB) => strcmp($fieldA['label'], $fieldB['label']));
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
        if ($this->tcaSchemaFactory->has($this->table)) {
            $schema = $this->tcaSchemaFactory->get($this->table);
            foreach ($schema->getFields() as $field) {
                $fieldListArr[] = $field->getName();
            }
            $fieldListArr[] = 'uid';
            $fieldListArr[] = 'pid';
            // @todo: this should never be hard-coded (note by benni in 2025)
            $fieldListArr[] = 'deleted';
            if ($schema->hasCapability(TcaSchemaCapability::UpdatedAt)) {
                $fieldListArr[] = $schema->getCapability(TcaSchemaCapability::UpdatedAt)->getFieldName();
            }
            if ($schema->hasCapability(TcaSchemaCapability::CreatedAt)) {
                $fieldListArr[] = $schema->getCapability(TcaSchemaCapability::CreatedAt)->getFieldName();
            }
            if ($schema->hasCapability(TcaSchemaCapability::SortByField)) {
                $fieldListArr[] = $schema->getCapability(TcaSchemaCapability::SortByField)->getFieldName();
            }
        }

        return implode(',', $fieldListArr);
    }

    protected function makeStoreControl(): string
    {
        $languageService = $this->getLanguageService();

        // Load/Save
        $storeArray = $this->initStoreArray();

        $opt = [];
        foreach ($storeArray as $k => $v) {
            $opt[] = '<option value="' . htmlspecialchars((string)$k) . '">' . htmlspecialchars((string)$v) . '</option>';
        }

        $markup = [];
        $markup[] = '<div class="form-row">';
        $markup[] = '  <div class="form-group">';
        $markup[] = '    <label for="query-store" class="form-label">' . $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.form.field.queryStore.storage.label') . '</label>';
        $markup[] = '    <div class="input-group">';
        $markup[] = '      <select class="form-select" name="storeControl[STORE]" id="query-store" data-assign-store-control-title>' . implode(LF, $opt) . '</select>';
        $markup[] = '    </div>';
        $markup[] = '  </div>';
        $markup[] = '  <div class="form-group">';
        $markup[] = '    <label for="query-title" class="form-label">' . $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.form.field.queryStore.title.label') . '</label>';
        $markup[] = '    <input class="form-control" name="storeControl[title]" id="query-title" value="" type="text" max="80">';
        $markup[] = '  </div>';
        $markup[] = '  <div class="form-group">';
        $markup[] = '    <button class="btn btn-default" type="submit" name="storeControl[LOAD]" value="' . $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.form.btn.load.label') . '">';
        $markup[] =        $this->iconFactory->getIcon('actions-upload', IconSize::SMALL)->render();
        $markup[] =        $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.form.btn.load.label');
        $markup[] = '    </button>';
        $markup[] = '    <button class="btn btn-default" type="submit" name="storeControl[SAVE]" value="' . $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.form.btn.save.label') . '">';
        $markup[] =        $this->iconFactory->getIcon('actions-save', IconSize::SMALL)->render();
        $markup[] =        $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.form.btn.save.label');
        $markup[] = '    </button>';
        $markup[] = '    <button class="btn btn-default" type="submit" name="storeControl[REMOVE]" value="' . $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.form.btn.delete.label') . '">';
        $markup[] =        $this->iconFactory->getIcon('actions-delete', IconSize::SMALL)->render();
        $markup[] =        $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.form.btn.delete.label');
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
                $msg = $this->flashMessageRendererResolver
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
        foreach ($storeQueryConfigs as $k => $v) {
            if (!isset($storeArray[$k])) {
                unset($storeQueryConfigs[$k]);
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
        $languageService = $this->getLanguageService();
        $storeArray = [
            '0' => $languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fullSearch.form.field.queryStore.storage.0'),
        ];
        $savedStoreArray = unserialize($this->MOD_SETTINGS['storeArray'] ?? '', ['allowed_classes' => false]);
        if (is_array($savedStoreArray)) {
            $storeArray = array_merge($storeArray, $savedStoreArray);
        }

        return $storeArray;
    }

    protected function search(ServerRequestInterface $request): string
    {
        $swords = $this->MOD_SETTINGS['sword'] ?? '';
        if ($swords === '') {
            return '';
        }
        $out = '';
        /** @var TcaSchema $schema */
        foreach ($this->tcaSchemaFactory->all() as $table => $schema) {
            // Avoid querying tables with no columns
            if ($schema->getFields()->count() === 0) {
                continue;
            }
            // Get fields list
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
            $identifierQuoteCharacter = $this->platformHelper->getIdentifierQuoteCharacter($connection->getDatabasePlatform());
            $tableColumns = $connection->createSchemaManager()->listTableColumns($table);
            $normalizedTableColumns = [];
            $fields = [];
            foreach ($tableColumns as $column) {
                if (!$schema->hasField($column->getName())) {
                    continue;
                }
                $fields[] = $column->getName();
                $normalizedTableColumns[trim($column->getName(), $identifierQuoteCharacter)] = $column;
            }
            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $queryBuilder->count('*')->from($table);
            $likes = [];
            $escapedLikeString = '%' . $queryBuilder->escapeLikeWildcards($swords) . '%';
            foreach ($fields as $field) {
                $field = trim($field, $identifierQuoteCharacter);
                $quotedField = $queryBuilder->quoteIdentifier($field);
                $column = $normalizedTableColumns[$field] ?? $normalizedTableColumns[$quotedField] ?? null;
                if ($column !== null
                    && $connection->getDatabasePlatform() instanceof DoctrinePostgreSQLPlatform
                    && !in_array(Type::getTypeRegistry()->lookupName($column->getType()), [Types::STRING, Types::ASCII_STRING], true)
                ) {
                    if (Type::getTypeRegistry()->lookupName($column->getType()) === Types::SMALLINT) {
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
                $queryBuilder
                    ->select('uid')
                    ->from($table)
                    ->setMaxResults(200);
                if ($schema->hasCapability(TcaSchemaCapability::Label) && $schema->getCapability(TcaSchemaCapability::Label)->hasPrimaryField()) {
                    $queryBuilder->addSelect($schema->getCapability(TcaSchemaCapability::Label)->getPrimaryField()->getName());
                }
                $likes = [];
                foreach ($fields as $field) {
                    $field = trim($field, $identifierQuoteCharacter);
                    $quotedField = $queryBuilder->quoteIdentifier($field);
                    $column = $normalizedTableColumns[$field] ?? $normalizedTableColumns[$quotedField] ?? null;
                    if ($column !== null
                        && $connection->getDatabasePlatform() instanceof DoctrinePostgreSQLPlatform
                        && !in_array(Type::getTypeRegistry()->lookupName($column->getType()), [Types::STRING, Types::ASCII_STRING], true)
                    ) {
                        if (Type::getTypeRegistry()->lookupName($column->getType()) === Types::SMALLINT) {
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
                    $rowArr[] = $this->resultRowDisplay($row, $table, $request);
                    $lastRow = $row;
                }
                $markup = [];
                $markup[] = '<div class="panel panel-default">';
                $markup[] = '  <div class="panel-heading">';
                $markup[] = htmlspecialchars($this->getLanguageService()->sL($schema->getRawConfiguration()['title'] ?? '')) . ' (' . $count . ')';
                $markup[] = '  </div>';
                $markup[] = '  <div class="table-fit">';
                $markup[] = '    <table class="table table-striped table-hover">';
                $markup[] = $this->resultRowTitles((array)$lastRow, $table);
                $markup[] = implode(LF, $rowArr);
                $markup[] = '    </table>';
                $markup[] = '  </div>';
                $markup[] = '</div>';

                $out .= implode(LF, $markup);
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
                'icon' => $this->iconFactory->getIconForRecord('pages', [], IconSize::SMALL)->render(),
                'count' => count($databaseIntegrityCheck->getPageIdArray()),
            ],
            'translated_pages' => [
                'icon' => $this->iconFactory->getIconForRecord('pages', [], IconSize::SMALL)->render(),
                'count' => count($databaseIntegrityCheck->getPageTranslatedPageIDArray()),
            ],
            'hidden_pages' => [
                'icon' => $this->iconFactory->getIconForRecord('pages', ['hidden' => 1], IconSize::SMALL)->render(),
                'count' => $databaseIntegrityCheck->getRecStats()['hidden'] ?? 0,
            ],
            'deleted_pages' => [
                'icon' => $this->iconFactory->getIconForRecord('pages', ['deleted' => 1], IconSize::SMALL)->render(),
                'count' => isset($databaseIntegrityCheck->getRecStats()['deleted']['pages']) ? count($databaseIntegrityCheck->getRecStats()['deleted']['pages']) : 0,
            ],
        ];

        // doktypes stats
        $doktypes = [];
        foreach ($this->pageDoktypeRegistry->getAllDoktypes() as $doktype) {
            if ($doktype->isDivider()) {
                continue;
            }
            $doktypes[] = [
                'icon' => $this->iconFactory->getIconForRecord('pages', ['doktype' => $doktype->getValue()], IconSize::SMALL)->render(),
                'title' => $languageService->sL($doktype->getLabel()) . ' (' . $doktype->getValue() . ')',
                'count' => (int)($databaseIntegrityCheck->getRecStats()['doktype'][$doktype->getValue()] ?? 0),
            ];
        }

        // Tables and lost records
        $id_list = implode(',', array_merge([0], array_keys($databaseIntegrityCheck->getPageIdArray())));
        $databaseIntegrityCheck->lostRecords($id_list);

        // Fix a lost record if requested
        $fixSingleLostRecordTableName = (string)($request->getQueryParams()['fixLostRecords_table'] ?? '');
        $fixSingleLostRecordUid = (int)($request->getQueryParams()['fixLostRecords_uid'] ?? 0);
        if (!empty($fixSingleLostRecordTableName) && $fixSingleLostRecordUid
            && $databaseIntegrityCheck->fixLostRecord($fixSingleLostRecordTableName, $fixSingleLostRecordUid)
        ) {
            $databaseIntegrityCheck = GeneralUtility::makeInstance(DatabaseIntegrityCheck::class);
            $databaseIntegrityCheck->genTree(0);
            $id_list = implode(',', array_merge([0], array_keys($databaseIntegrityCheck->getPageIdArray())));
            $databaseIntegrityCheck->lostRecords($id_list);
        }

        $tableStatistic = [];
        $countArr = $databaseIntegrityCheck->countRecords($id_list);
        /** @var TcaSchema $schema */
        foreach ($this->tcaSchemaFactory->all() as $table => $schema) {
            if ($schema->getRawConfiguration()['hideTable'] ?? false) {
                continue;
            }
            if ($table === 'pages' && $databaseIntegrityCheck->getLostPagesList() !== '') {
                $lostRecordCount = count(explode(',', $databaseIntegrityCheck->getLostPagesList()));
            } else {
                $lostRecordCount = isset($databaseIntegrityCheck->getLRecords()[$table]) ? count($databaseIntegrityCheck->getLRecords()[$table]) : 0;
            }
            $recordCount = 0;
            if ($countArr['all'][$table] ?? false) {
                $recordCount = (int)($countArr['non_deleted'][$table] ?? 0) . '/' . $lostRecordCount;
            }
            $lostRecordList = [];
            foreach ($databaseIntegrityCheck->getLRecords()[$table] ?? [] as $data) {
                if (!GeneralUtility::inList($databaseIntegrityCheck->getLostPagesList(), $data['pid'])) {
                    $fixLink = (string)$this->uriBuilder->buildUriFromRoute(
                        $this->moduleName,
                        ['SET' => ['function' => 'records'], 'fixLostRecords_table' => $table, 'fixLostRecords_uid' => $data['uid']]
                    );
                    $lostRecordList[] =
                        '<div class="record">' .
                            '<a href="' . htmlspecialchars($fixLink) . '" title="' . htmlspecialchars($languageService->sL('LLL:EXT:lowlevel/Resources/Private/Language/locallang.xlf:fixLostRecord')) . '">' .
                                $this->iconFactory->getIcon('status-dialog-error', IconSize::SMALL)->render() .
                            '</a>uid:' . $data['uid'] . ', pid:' . $data['pid'] . ', ' . htmlspecialchars(GeneralUtility::fixed_lgd_cs(strip_tags($data['title']), 20)) .
                        '</div>';
                } else {
                    $lostRecordList[] =
                        '<div class="record-noicon">' .
                            'uid:' . $data['uid'] . ', pid:' . $data['pid'] . ', ' . htmlspecialchars(GeneralUtility::fixed_lgd_cs(strip_tags($data['title']), 20)) .
                        '</div>';
                }
            }
            $tableStatistic[$table] = [
                'icon' => $this->iconFactory->getIconForRecord($table, [], IconSize::SMALL)->render(),
                'title' => $languageService->sL($schema->getRawConfiguration()['title']),
                'count' => $recordCount,
                'lostRecords' => implode(LF, $lostRecordList),
            ];
        }

        $view->assignMultiple([
            'pages' => $pageStatistic,
            'doktypes' => $doktypes,
            'tables' => $tableStatistic,
        ]);

        return $view->renderResponse('RecordStatistics');
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
     * Returns a selector box to switch the view.
     *
     * @param string $elementName The form elements name, probably something like "SET[...]
     * @param string|int $currentValue The value to be selected currently.
     * @param mixed $menuItems An array with the menu items for the selector box
     * @return string HTML code for selector box
     */
    protected function getDropdownMenu(
        string $elementName,
        string|int $currentValue,
        mixed $menuItems,
        ServerRequestInterface $request
    ): string {
        if (!is_array($menuItems) || count($menuItems) <= 1) {
            return '';
        }
        $scriptUrl = $this->uriBuilder->buildUriFromRequest($request);
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
        $attributes = GeneralUtility::implodeAttributes([
            'name' => $elementName,
            'id' => $dataMenuIdentifier,
            'class' => 'form-select',
            'data-menu-identifier' => $dataMenuIdentifier,
            'data-global-event' => 'change',
            'data-action-navigate' => '$data=~s/$value/',
            'data-navigate-value' => $scriptUrl . '&' . $elementName . '=${value}',
        ], true);

        return '
            <select class="form-select" ' . $attributes . '>
                ' . implode(LF, $options) . '
            </select>';
    }

    /**
     * Checkbox function menu.
     *
     * @param string $elementName The form elements name, probably something like "SET[...]
     * @param string|bool|int $currentValue The value to be selected currently.
     * @param string $tagParams Additional attributes for the checkbox input tag
     * @return string HTML code for checkbox
     */
    protected function getFuncCheck(
        string $elementName,
        string|bool|int $currentValue,
        ServerRequestInterface $request,
        string $tagParams = ''
    ): string {
        // relies on module 'TYPO3/CMS/Backend/ActionDispatcher'
        $scriptUrl = $this->uriBuilder->buildUriFromRequest($request);
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
}
