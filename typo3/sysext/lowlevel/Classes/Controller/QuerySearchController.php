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
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Localization\DateFormatter;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\CsvUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * "Database > Advanced query" module.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[AsController]
class QuerySearchController
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
    protected string $moduleName = 'system_database_query';

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
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
        protected readonly Locales $locales,
        protected readonly FlashMessageService $flashMessageService,
        protected readonly ConnectionPool $connectionPool,
        protected readonly ResponseFactoryInterface $responseFactory,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $lang = $this->getLanguageService();

        $this->menuConfig($request);
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->makeDocHeaderModuleMenu();
        $moduleTemplate->getDocHeaderComponent()->setShortcutContext(
            $this->moduleName,
            $this->getLanguageService()->translate('fullSearch', 'lowlevel.messages'),
            [
                'SET' => [
                    'search_query_makeQuery' => $this->MOD_SETTINGS['search_query_makeQuery'] ?? '',
                ],
            ]
        );
        $this->showFieldAndTableNames = $this->getBackendUserAuthentication()->shallDisplayDebugInformation();

        $title = $lang->translate('title', 'lowlevel.modules.database_integrity');
        $moduleTemplate->setTitle($title, $lang->translate('title', 'lowlevel.modules.database_query'));

        $moduleTemplate->assignMultiple([
            'queryMaker' => $this->queryMaker($request),
            'queryTypes' => $this->MOD_MENU['search_query_makeQuery'],
            'settings' => $this->MOD_SETTINGS,
            'queryOptions' => [
                'search_query_smallparts' => 'showSQL',
                'search_result_labels' => 'useFormattedStrings',
                'labels_noprefix' => 'dontUseOrigValues',
                'options_sortlabel' => 'sortOptions',
                'show_deleted' => 'showDeleted',
            ],
            'moduleUrl' => (string)$this->uriBuilder->buildUriFromRequest($request),
        ]);

        return $moduleTemplate->renderResponse('SearchQuery');
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
                'all' => $lang->translate('selectRecords', 'lowlevel.messages'),
                'count' => $lang->translate('countResults', 'lowlevel.messages'),
                'explain' => $lang->translate('explainQuery', 'lowlevel.messages'),
                'csv' => $lang->translate('csvExport', 'lowlevel.messages'),
            ],
            'sword' => '',
        ];

        // EXPLAIN is no ANSI SQL, for now this is only executed on mysql
        $connection = $this->connectionPool->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
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

    protected function queryMaker(ServerRequestInterface $request): array
    {
        $message = $this->procesStoreControl($request);
        $userTsConfig = $this->getBackendUserAuthentication()->getTSConfig();
        $this->init('queryConfig', $this->MOD_SETTINGS['queryTable'] ?? '', '', $this->MOD_SETTINGS);
        $data = [
            'showStoreControl' => !($userTsConfig['mod.']['dbint.']['disableStoreControl'] ?? false),
            'storedQueries' => $this->initStoreArray(),
            'storeMessage' => $message,
            'selector' => $this->makeSelectorTable($this->MOD_SETTINGS, $request),
        ];
        $mQ = $this->MOD_SETTINGS['search_query_makeQuery'] ?? '';

        // Make form elements:
        if ($this->table && $this->tcaSchemaFactory->has($this->table)) {
            if ($mQ) {
                // Show query
                $this->enablePrefix = true;
                $queryString = $this->getQuery($this->queryConfig);
                $selectQueryString = $this->getSelectQuery($queryString);
                $connection = $this->connectionPool->getConnectionForTable($this->table);
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
                    $data['result'] = $this->getQueryResultData($mQ, $dataRows, $this->table, $request);
                } catch (DBALException $e) {
                    $data['error'] = $e->getMessage();
                }
                if (!($userTsConfig['mod.']['dbint.']['disableShowSQLQuery'] ?? false)) {
                    $data['sql'] = $fullQueryString;
                }
            }
        }

        return $data;
    }

    protected function getSelectQuery(string $qString = ''): string
    {
        $backendUserAuthentication = $this->getBackendUserAuthentication();
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($this->table);
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
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
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
     * @return array Query result data
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function getQueryResultData(string $type, array $dataRows, string $table, ServerRequestInterface $request): array
    {
        $result = ['type' => $type];
        switch ($type) {
            case 'count':
                $result['count'] = (int)$dataRows[0];
                break;
            case 'all':
                $result['rows'] = [];
                foreach ($dataRows as $dataRow) {
                    $result['rows'][] = $this->getResultRowData($dataRow, $table, $request);
                }
                if ($dataRows !== []) {
                    $result['titles'] = $this->getResultRowTitles($dataRows[0], $table);
                } else {
                    $this->renderNoResultsFoundMessage();
                }
                break;
            case 'csv':
                $rows = [];
                foreach ($dataRows as $dataRow) {
                    if ($rows === []) {
                        $rows[] = $this->csvValues(array_keys($dataRow));
                    }
                    $rows[] = $this->csvValues($dataRow, $table);
                }
                if ($rows !== []) {
                    $result['csv'] = implode(LF, $rows);
                    $result['download'] = !$this->noDownloadB;
                    if ($request->getParsedBody()['download_file'] ?? false) {
                        $filename = 'TYPO3_' . $table . '_export_' . date('dmy-Hi') . '.csv';
                        $response = $this->responseFactory->createResponse()
                            ->withHeader('Content-Type', 'application/octet-stream')
                            ->withHeader('Content-Disposition', 'attachment; filename=' . $filename);
                        $response->getBody()->write(implode(CRLF, $rows));
                        throw new PropagateResponseException($response, 1791542540);
                    }
                } else {
                    $this->renderNoResultsFoundMessage();
                }
                break;
            case 'explain':
            default:
                $result['explain'] = $dataRows;
        }
        return $result;
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
     * @param array $row Table columns
     */
    protected function getResultRowTitles(array $row, string $table): array
    {
        $languageService = $this->getLanguageService();
        $tableHeader = [];
        // Iterate over given columns
        $schema = $this->tcaSchemaFactory->get($table);
        foreach ($row as $fieldName => $fieldValue) {
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
                $tableHeader[] = $title;
            }
        }
        return $tableHeader;
    }

    protected function getResultRowData(array $row, string $table, ServerRequestInterface $request): array
    {
        $data = ['values' => [], 'table' => $table, 'uid' => $row['uid'], 'deleted' => (bool)($row['deleted'] ?? false)];
        foreach ($row as $fieldName => $fieldValue) {
            if (GeneralUtility::inList($this->MOD_SETTINGS['queryFields'] ?? '', $fieldName)
                || !($this->MOD_SETTINGS['queryFields'] ?? false)
                && $fieldName !== 'pid'
                && $fieldName !== 'deleted'
            ) {
                if ($this->MOD_SETTINGS['search_result_labels'] ?? false) {
                    $fVnew = $this->getProcessedValueExtra($table, $fieldName, (string)$fieldValue, LF);
                } else {
                    $fVnew = (string)$fieldValue;
                }
                $data['values'][] = ($this->MOD_SETTINGS['search_result_labels'] ?? false) ? explode(LF, $fVnew) : [$fVnew];
            }
        }

        if (!($row['deleted'] ?? false)) {
            // "Edit"
            $editActionUrl = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    $table => [
                        $row['uid'] => 'edit',
                    ],
                ],
                'module' => 'system_database',
                'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri()
                    . HttpUtility::buildQueryString(['SET' => $request->getParsedBody()['SET'] ?? []], '&'),
            ]);
            $data['editUrl'] = $editActionUrl;
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
            $data['undeleteUrl'] = $undeleteActionUrl;
        }

        return $data;
    }

    protected function getProcessedValueExtra(string $table, string $fieldName, string $fieldValue, string $splitString): string
    {
        $out = '';
        $fields = [];
        $user = $this->getBackendUserAuthentication();
        if ($user->user['lang'] ?? false) {
            $locale = $this->locales->createLocale($user->user['lang']);
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
                        $fields['type'] = 'text';
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
                    if ($splitString === LF) {
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
                $out = $fieldValue;
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
                    $out .= $value;
                }
            }
        }
        if ($fieldSetup['type'] === 'binary') {
            foreach ($fieldSetup['items'] as $val) {
                $value = $languageService->sL($val['label']);
                if ($out !== '') {
                    $out .= $splitString;
                }
                $out .= $value;
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
                    $out .= $value;
                }
            }
            if (str_contains($fieldSetup['allowed'] ?? '', ',')) {
                $from_table_Arr = explode(',', $fieldSetup['allowed']);
                $useTablePrefix = 1;
                if (!$fieldSetup['prepend_tname']) {
                    $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
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
            $tablePrefix = '';
            foreach ($from_table_Arr as $from_table) {
                if ($useTablePrefix && !$dontPrefixFirstTable && $counter !== 1 || $counter === 1) {
                    $tablePrefix = $from_table . '_';
                }
                $counter = 1;
                if (!$this->tcaSchemaFactory->has($from_table)) {
                    continue;
                }
                $selectFields = $this->getSelectFieldsForRecordTitle($from_table);

                if (empty($this->tableArray[$from_table])) {
                    $queryBuilder = $this->connectionPool->getQueryBuilderForTable($from_table);
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
                    $this->MOD_SETTINGS['labels_noprefix']
                        = ($this->MOD_SETTINGS['labels_noprefix'] ?? '') == 1
                            ? 'on'
                            : $this->MOD_SETTINGS['labels_noprefix'] ?? '';
                    $prefixString
                        = $this->MOD_SETTINGS['labels_noprefix'] === 'on'
                            ? ''
                            : ' [' . $tablePrefix . $val['uid'] . '] ';
                    if (GeneralUtility::inList($fieldValue, $tablePrefix . $val['uid'])
                        || $fieldValue == $tablePrefix . $val['uid']) {
                        // Multiple matching records are separated by a newline inside the same HTML cell
                        if ($out !== '') {
                            $out .= $splitString;
                        }

                        $out .= BackendUtility::getRecordTitle($from_table, $val, false, false);
                    }
                }
            }
        }

        return $out;
    }

    private function renderNoResultsFoundMessage(): void
    {
        $languageService = $this->getLanguageService();
        $flashMessageText = $languageService->translate('fullSearch.flashMessage.noResultsFoundMessage', 'lowlevel.messages');
        $flashMessage = new FlashMessage($flashMessageText, '', ContextualFeedbackSeverity::INFO);
        $defaultFlashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
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
        $queryBuilder = $this->connectionPool->getConnectionForTable($this->table);
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

    protected function makeSelectorTable(array $modSettings, ServerRequestInterface $request): array
    {
        $userTsConfig = $this->getBackendUserAuthentication()->getTSConfig();
        $data = [
            'table' => $this->table,
            'tables' => $this->getTableOptions(),
            'showTable' => !($userTsConfig['mod.']['dbint.']['disableSelectATable'] ?? false),
        ];

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
            $nextLimit = max(0, $limitBegin + ($limitLength ?: 100));
            $data += [
                'conditions' => $this->getFormElements(),
                'fields' => $this->getFieldOptions(),
                'fieldLists' => $this->extFieldLists,
                'orderBy' => explode(',', $this->extFieldLists['queryOrder']),
                'showQueryParts' => $this->enableQueryParts,
                'showFields' => !($userTsConfig['mod.']['dbint.']['disableSelectFields'] ?? false),
                'showQuery' => !($userTsConfig['mod.']['dbint.']['disableMakeQuery'] ?? false),
                'showGroup' => !($userTsConfig['mod.']['dbint.']['disableGroupBy'] ?? false),
                'showOrder' => !($userTsConfig['mod.']['dbint.']['disableOrderBy'] ?? false),
                'showLimit' => !($userTsConfig['mod.']['dbint.']['disableLimit'] ?? false),
                'previousLimit' => $limitBegin ? max(0, $limitBegin - $limitLength) . ',' . $limitLength : '',
                'previousLength' => $limitLength,
                'nextLimit' => $nextLimit ? $nextLimit . ',' . ($limitLength ?: 100) : '',
                'nextLength' => $limitLength ?: 100,
            ];
        }
        return $data;
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
        $elements = [];
        if (!is_array($queryConfig)) {
            $queryConfig = $this->queryConfig;
        }
        $loopCount = 0;
        foreach ($queryConfig as $key => $conf) {
            $subscript = $parent . '[' . $key . ']';
            $fieldName = '';
            if (str_starts_with(($conf['type'] ?? ''), 'FIELD_')) {
                $fieldName = substr($conf['type'], 6);
                $this->fieldName = $fieldName;
                $fieldType = $this->fields[$fieldName]['type'] ?? '';
                if ((int)($conf['comparison'] ?? 0) >> 5 !== (int)($this->comp_offsets[$fieldType] ?? 0)) {
                    $conf['comparison'] = (int)($this->comp_offsets[$fieldType] ?? 0) << 5;
                }
                $queryConfig[$key]['comparison'] += isset($conf['negate']) - $conf['comparison'] % 2;
            } elseif (($conf['type'] ?? '') === 'newlevel') {
                $fieldType = 'newlevel';
            } else {
                $loopCount++;
                continue;
            }
            $element = [
                'name' => $this->name . $subscript,
                'subscript' => $subscript,
                'fieldName' => $fieldName,
                'fieldType' => $fieldType,
                'operator' => $conf['operator'] ?? '',
                'showOperator' => $elements !== [],
                'submitOperator' => ($conf['type'] ?? '') !== 'FIELD_',
                'removable' => (bool)$loopCount,
                'negate' => (bool)($conf['negate'] ?? false),
                'comparison' => (int)($conf['comparison'] ?? 0),
                'comparisons' => $this->getComparisonOptions((int)($conf['comparison'] ?? 0), (int)(bool)($conf['negate'] ?? false)),
            ];
            if ($fieldType === 'newlevel') {
                if (!is_array($queryConfig[$key]['nl'] ?? null)) {
                    $queryConfig[$key]['nl'] = [['type' => 'FIELD_']];
                }
                $element['sub'] = $this->getFormElements($subLevel + 1, $queryConfig[$key]['nl'], $subscript . '[nl]');
            } elseif ($fieldType === 'date' || $fieldType === 'time') {
                $type = $fieldType === 'date' ? 'date' : 'datetime';
                $element['dateFields'][] = $this->getDateTimePickerData($element['name'] . '[inputValue]', (string)($conf['inputValue'] ?? ''), $type);
                if ($conf['comparison'] === 100 || $conf['comparison'] === 101) {
                    $element['dateFields'][] = $this->getDateTimePickerData($element['name'] . '[inputValue1]', (string)($conf['inputValue1'] ?? ''), $type);
                }
            } elseif (in_array($fieldType, ['multiple', 'binary', 'relation'], true)) {
                $comparison = $element['comparison'];
                $element['multiple'] = in_array($comparison, [68, 69, 162, 163], true);
                $element['textInput'] = $comparison === 66 || $comparison === 67;
                if ($element['textInput'] && is_array($conf['inputValue'] ?? null)) {
                    $conf['inputValue'] = implode(',', $conf['inputValue']);
                } elseif ($comparison === 64 && is_array($conf['inputValue'] ?? null)) {
                    $conf['inputValue'] = $conf['inputValue'][0];
                }
                $element['options'] = $element['textInput'] ? [] : $this->getValueOptions($fieldName, $conf, $this->table);
                $element['inputValue'] = $conf['inputValue'] ?? '';
            } else {
                $element['between'] = $conf['comparison'] === 36 || $conf['comparison'] === 37;
                $element['inputValue'] = is_array($conf['inputValue'] ?? null) ? '' : ($conf['inputValue'] ?? '');
                $element['inputValue1'] = $conf['inputValue1'] ?? '';
            }
            $element['query'] = $this->getQuerySingle($conf, $elements === []);
            $elements[] = $element;
            $loopCount++;
        }
        $this->queryConfig = $queryConfig;
        return $elements;
    }

    protected function getDateTimePickerData(string $name, string $timestamp, string $type): array
    {
        return [
            'name' => $name,
            'timestamp' => $timestamp,
            'type' => $type,
            'id' => StringUtility::getUniqueId('dt_'),
            'value' => strtotime($timestamp) ? date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], (int)strtotime($timestamp)) : '',
        ];
    }

    protected function getValueOptions(string $fieldName, array $conf, string $table): array
    {
        $backendUserAuthentication = $this->getBackendUserAuthentication();
        $from_table_Arr = [];
        $out = [];
        $fieldSetup = $this->fields[$fieldName];
        $languageService = $this->getLanguageService();
        $selectedValues = is_array($conf['inputValue'] ?? null)
            ? array_map(strval(...), $conf['inputValue'])
            : explode(',', (string)($conf['inputValue'] ?? ''));
        if ($fieldSetup['type'] === 'multiple') {
            $group = null;
            foreach (($fieldSetup['items'] ?? []) as $item) {
                $label = $languageService->sL($item['label']);
                if ($item['value'] === '--div--') {
                    $out[] = ['label' => $label, 'group' => true, 'options' => []];
                    $group = array_key_last($out);
                    continue;
                }
                $option = ['value' => (string)$item['value'], 'label' => $label, 'selected' => in_array((string)$item['value'], $selectedValues, true)];
                if ($group === null) {
                    $out[] = $option;
                } else {
                    $out[$group]['options'][] = $option;
                }
            }
        }
        if ($fieldSetup['type'] === 'binary') {
            foreach ($fieldSetup['items'] as $key => $item) {
                $value = (string)(2 ** $key);
                $out[] = ['value' => $value, 'label' => $languageService->sL($item['label']), 'selected' => in_array($value, $selectedValues, true)];
            }
        }
        if ($fieldSetup['type'] === 'relation') {
            $useTablePrefix = 0;
            $dontPrefixFirstTable = 0;
            foreach (($fieldSetup['items'] ?? []) as $item) {
                $value = (string)$item['value'];
                $out[] = ['value' => $value, 'label' => $languageService->sL($item['label']), 'selected' => in_array($value, $selectedValues, true)];
            }
            $allowedFields = $fieldSetup['allowed'] ?? '';
            if (str_contains($allowedFields, ',')) {
                $from_table_Arr = explode(',', $allowedFields);
                $useTablePrefix = 1;
                if (!$fieldSetup['prepend_tname']) {
                    $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
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
            foreach ($from_table_Arr as $from_table) {
                if ($useTablePrefix && !$dontPrefixFirstTable && $counter !== 1 || $counter === 1) {
                    $tablePrefix = $from_table . '_';
                }
                $counter = 1;
                if ($this->tcaSchemaFactory->has($from_table)) {
                    $selectFields = $this->getSelectFieldsForRecordTitle($from_table);

                    if (!($this->tableArray[$from_table] ?? false)) {
                        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($from_table);
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

                    foreach ($this->tableArray[$from_table] ?? [] as $val) {
                        $outArray[$tablePrefix . $val['uid']] = BackendUtility::getRecordTitle($from_table, $val, false, false);
                    }
                    if (isset($this->MOD_SETTINGS['options_sortlabel']) && $this->MOD_SETTINGS['options_sortlabel'] && $outArray !== []) {
                        natcasesort($outArray);
                    }
                }
            }
            foreach ($outArray as $key2 => $val2) {
                $key2 = (string)$key2;
                $val2 = (string)$val2;
                $out[] = ['value' => $key2, 'label' => '[' . $key2 . '] ' . $val2, 'selected' => in_array($key2, $selectedValues, true)];
            }
        }

        return $out;
    }

    /**
     * Returns the fields of the table, which are needed to resolve the record title
     *
     * @return string[]
     */
    protected function getSelectFieldsForRecordTitle(string $table): array
    {
        $schema = $this->tcaSchemaFactory->get($table);
        $selectFields = ['uid', 'pid', ...$schema->getCapability(TcaSchemaCapability::Label)->getLabelFieldNamesOfAllRecordTypes()];
        if ($schema->supportsSubSchema()) {
            $selectFields[] = $schema->getSubSchemaTypeInformation()->getFieldName();
        }
        return array_values(array_unique($selectFields));
    }

    protected function getComparisonOptions(int $comparison, int $negate): array
    {
        $offset = $comparison >> 5;
        $options = [];
        for ($i = 32 * $offset + $negate; $i < 32 * ($offset + 1); $i += 2) {
            if ($this->lang['comparison'][$i . '_'] ?? false) {
                $options[] = [
                    'value' => $i,
                    'label' => $this->lang['comparison'][$i . '_'],
                    'selected' => $i >> 1 === $comparison >> 1,
                ];
            }
        }
        return $options;
    }

    protected function getFieldOptions(): array
    {
        $options = [];
        foreach ($this->fields as $name => $field) {
            if (!($field['exclude'] ?? false) || $this->getBackendUserAuthentication()->check('non_exclude_fields', $this->table . ':' . $name)) {
                $options[$name] = $field['label'] . ($this->showFieldAndTableNames ? ' [' . $name . ']' : '');
            }
        }
        return $options;
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
        while (is_array($arr)) {
            reset($arr);
            $key = key($arr);
            $retArr[] = $key;
            if (isset($arr[$key ?? ''])) {
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
            return $schema->getCapability(TcaSchemaCapability::Label)->getPrimaryFieldName();
        }
        return '';
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

    protected function getTableOptions(): array
    {
        $tables = [];
        /** @var TcaSchema $schema */
        foreach ($this->tcaSchemaFactory->all() as $tableName => $schema) {
            $tableTitle = $schema->getTitle($this->getLanguageService()->sL(...));
            if (!$tableTitle || $this->showFieldAndTableNames) {
                $tableTitle .= ' [' . $tableName . ']';
            }
            $tables[$tableName] = trim($tableTitle);
        }
        asort($tables);

        return array_filter($tables, fn(string $tableName): bool => $this->getBackendUserAuthentication()->check('tables_select', $tableName), ARRAY_FILTER_USE_KEY);
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

    protected function procesStoreControl(ServerRequestInterface $request): ?FlashMessage
    {
        $languageService = $this->getLanguageService();
        $flashMessage = null;
        $storeArray = $this->initStoreArray();
        $storeQueryConfigs = (array)(unserialize($this->MOD_SETTINGS['storeQueryConfigs'] ?? '', ['allowed_classes' => false]));
        $storeControl = $request->getParsedBody()['storeControl'] ?? [];
        $storeIndex = (int)($storeControl['STORE'] ?? 0);
        $saveStoreArray = 0;
        $writeArray = [];
        if (is_array($storeControl)) {
            if ($storeControl['LOAD'] ?? false) {
                if ($storeIndex > 0) {
                    $writeArray = $this->loadStoreQueryConfigs($storeQueryConfigs, $storeIndex, $writeArray);
                    $saveStoreArray = 1;
                    $flashMessage = new FlashMessage(
                        sprintf($languageService->translate('query_loaded', 'lowlevel.modules.database_integrity'), $storeArray[$storeIndex])
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
                    $flashMessage = new FlashMessage(
                        $languageService->translate('query_saved', 'lowlevel.modules.database_integrity')
                    );
                }
            } elseif ($storeControl['REMOVE'] ?? false) {
                if ($storeIndex > 0) {
                    $flashMessage = new FlashMessage(
                        sprintf($languageService->translate('query_removed', 'lowlevel.modules.database_integrity'), $storeArray[$storeControl['STORE']])
                    );
                    // Removing
                    unset($storeArray[$storeControl['STORE']]);
                    $saveStoreArray = 1;
                }
            }
        }
        if ($saveStoreArray) {
            // Making sure, index 0 is not set!
            unset($storeArray[0]);
            $writeArray['storeArray'] = serialize($storeArray);
            $writeArray['storeQueryConfigs']
                = serialize($this->cleanStoreQueryConfigs($storeQueryConfigs, $storeArray));
            $this->MOD_SETTINGS = BackendUtility::getModuleData(
                $this->MOD_MENU,
                $writeArray,
                $this->moduleName,
                'ses'
            );
        }

        return $flashMessage;
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
            '0' => $languageService->translate('fullSearch.form.field.queryStore.storage.0', 'lowlevel.messages'),
        ];
        $savedStoreArray = unserialize($this->MOD_SETTINGS['storeArray'] ?? '', ['allowed_classes' => false]);
        if (is_array($savedStoreArray)) {
            $storeArray = array_merge($storeArray, $savedStoreArray);
        }

        return $storeArray;
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

}
