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

namespace TYPO3\CMS\Lowlevel\Database;

use Doctrine\DBAL\Exception as DBALException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageRendererResolver;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\CsvUtility;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Class used in module tools/dbint (advanced search) and which may hold code specific for that module
 *
 * @internal This class is a specific implementation for the lowlevel extension and is not part of the TYPO3's Core API.
 */
class QueryGenerator
{
    /**
     * @var string
     */
    protected $storeList = 'search_query_smallparts,search_result_labels,labels_noprefix,show_deleted,queryConfig,queryTable,queryFields,queryLimit,queryOrder,queryOrderDesc,queryOrder2,queryOrder2Desc,queryGroup,search_query_makeQuery';

    /**
     * @var int
     */
    protected $noDownloadB = 0;

    /**
     * @var array
     */
    protected $hookArray = [];

    /**
     * @var string
     */
    protected $formName = '';

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var array
     */
    protected $tableArray = [];

    /**
     * @var array Settings, usually from the controller
     */
    protected $settings = [];

    /**
     * @var array information on the menu of this module
     */
    protected $menuItems = [];

    /**
     * @var string
     */
    protected $moduleName;

    /**
     * @var array
     */
    protected $lang = [
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

    /**
     * @var array
     */
    protected $compSQL = [
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

    /**
     * @var array
     */
    protected $comp_offsets = [
        'text' => 0,
        'number' => 1,
        'multiple' => 2,
        'relation' => 2,
        'date' => 3,
        'time' => 3,
        'boolean' => 4,
        'binary' => 5,
    ];

    /**
     * Form data name prefix
     *
     * @var string
     */
    protected $name;

    /**
     * Table for the query
     *
     * @var string
     */
    protected $table;

    /**
     * Field list
     *
     * @var string
     */
    protected $fieldList;

    /**
     * Array of the fields possible
     *
     * @var array
     */
    protected $fields = [];

    /**
     * @var array
     */
    protected $extFieldLists = [];

    /**
     * The query config
     *
     * @var array
     */
    protected $queryConfig = [];

    /**
     * @var bool
     */
    protected $enablePrefix = false;

    /**
     * @var bool
     */
    protected $enableQueryParts = false;

    /**
     * @var string
     */
    protected $fieldName;

    /**
     * If the current user is an admin and $GLOBALS['TYPO3_CONF_VARS']['BE']['debug']
     * is set to true, the names of fields and tables are displayed.
     *
     * @var bool
     */
    protected $showFieldAndTableNames = false;

    public function __construct(array $settings, array $menuItems, string $moduleName)
    {
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_t3lib_fullsearch.xlf');
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->settings = $settings;
        $this->menuItems = $menuItems;
        $this->moduleName = $moduleName;
        $this->showFieldAndTableNames = ($GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] ?? false)
            && $this->getBackendUserAuthentication()->isAdmin();
    }

    /**
     * Get form
     *
     * @return string
     */
    public function form()
    {
        $markup = [];
        $markup[] = '<div class="form-group">';
        $markup[] = '<input placeholder="Search Word" class="form-control" type="search" name="SET[sword]" value="'
            . htmlspecialchars($this->settings['sword'] ?? '') . '">';
        $markup[] = '</div>';
        $markup[] = '<div class="form-group">';
        $markup[] = '<input class="btn btn-default" type="submit" name="submit" value="Search All Records">';
        $markup[] = '</div>';
        return implode(LF, $markup);
    }

    /**
     * Query marker
     *
     * @return string
     */
    public function queryMaker()
    {
        $output = '';
        $this->hookArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3lib_fullsearch'] ?? [];
        $msg = $this->procesStoreControl();
        $userTsConfig = $this->getBackendUserAuthentication()->getTSConfig();
        if (!($userTsConfig['mod.']['dbint.']['disableStoreControl'] ?? false)) {
            $output .= '<h2>Load/Save Query</h2>';
            $output .= '<div>' . $this->makeStoreControl() . '</div>';
            $output .= $msg;
        }
        // Query Maker:
        $this->init('queryConfig', $this->settings['queryTable'] ?? '', '', $this->settings);
        if ($this->formName) {
            $this->setFormName($this->formName);
        }
        $tmpCode = $this->makeSelectorTable($this->settings);
        $output .= '<div id="query"></div><h2>Make query</h2><div>' . $tmpCode . '</div>';
        $mQ = $this->settings['search_query_makeQuery'];
        // Make form elements:
        if ($this->table && is_array($GLOBALS['TCA'][$this->table])) {
            if ($mQ) {
                // Show query
                $this->enablePrefix = true;
                $queryString = $this->getQuery($this->queryConfig);
                $selectQueryString = $this->getSelectQuery($queryString);
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->table);

                $isConnectionMysql = strpos($connection->getServerVersion(), 'MySQL') === 0;
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
                        if (empty($this->settings['show_deleted'])) {
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
                        $output .= '<h2>SQL query</h2><div><code>' . htmlspecialchars($fullQueryString) . '</code></div>';
                    }
                    $cPR = $this->getQueryResultCode($mQ, $dataRows, $this->table);
                    $output .= '<h2>' . ($cPR['header'] ?? '') . '</h2><div>' . $cPR['content'] . '</div>';
                } catch (DBALException $e) {
                    if (!($userTsConfig['mod.']['dbint.']['disableShowSQLQuery'] ?? false)) {
                        $output .= '<h2>SQL query</h2><div><code>' . htmlspecialchars($fullQueryString) . '</code></div>';
                    }
                    $out = '<p><strong>Error: <span class="text-danger">'
                        . htmlspecialchars($e->getMessage())
                        . '</span></strong></p>';
                    $output .= '<h2>SQL error</h2><div>' . $out . '</div>';
                }
            }
        }
        return '<div class="database-query-builder">' . $output . '</div>';
    }

    /**
     * Search
     *
     * @return string
     */
    public function search()
    {
        $swords = $this->settings['sword'] ?? '';
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
                $fieldsInDatabase = [];
                foreach ($tableColumns as $column) {
                    $fieldsInDatabase[] = $column->getName();
                }
                $fields = array_intersect(array_keys($conf['columns']), $fieldsInDatabase);

                $queryBuilder = $connection->createQueryBuilder();
                $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                $queryBuilder->count('*')->from($table);
                $likes = [];
                $escapedLikeString = '%' . $queryBuilder->escapeLikeWildcards($swords) . '%';
                foreach ($fields as $field) {
                    $likes[] = $queryBuilder->expr()->like(
                        $field,
                        $queryBuilder->createNamedParameter($escapedLikeString, \PDO::PARAM_STR)
                    );
                }
                $count = $queryBuilder->orWhere(...$likes)->executeQuery()->fetchOne();

                if ($count > 0) {
                    $queryBuilder = $connection->createQueryBuilder();
                    $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                    $queryBuilder->select('uid', $conf['ctrl']['label'])
                        ->from($table)
                        ->setMaxResults(200);
                    $likes = [];
                    foreach ($fields as $field) {
                        $likes[] = $queryBuilder->expr()->like(
                            $field,
                            $queryBuilder->createNamedParameter($escapedLikeString, \PDO::PARAM_STR)
                        );
                    }
                    $statement = $queryBuilder->orWhere(...$likes)->executeQuery();
                    $lastRow = null;
                    $rowArr = [];
                    while ($row = $statement->fetchAssociative()) {
                        $rowArr[] = $this->resultRowDisplay($row, $conf, $table);
                        $lastRow = $row;
                    }
                    $markup = [];
                    $markup[] = '<div class="panel panel-default">';
                    $markup[] = '  <div class="panel-heading">';
                    $markup[] = htmlspecialchars($this->getLanguageService()->sL($conf['ctrl']['title'])) . ' (' . $count . ')';
                    $markup[] = '  </div>';
                    $markup[] = '  <table class="table table-striped table-hover">';
                    $markup[] = $this->resultRowTitles((array)$lastRow, $conf);
                    $markup[] = implode(LF, $rowArr);
                    $markup[] = '  </table>';
                    $markup[] = '</div>';

                    $out .= implode(LF, $markup);
                }
            }
        }
        return $out;
    }

    /**
     * Sets the current name of the input form.
     *
     * @param string $formName The name of the form.
     */
    public function setFormName($formName)
    {
        $this->formName = trim($formName);
    }

    /**
     * Make store control
     *
     * @return string
     */
    protected function makeStoreControl()
    {
        // Load/Save
        $storeArray = $this->initStoreArray();

        $opt = [];
        foreach ($storeArray as $k => $v) {
            $opt[] = '<option value="' . htmlspecialchars($k) . '">' . htmlspecialchars($v) . '</option>';
        }
        // Actions:
        if (ExtensionManagementUtility::isLoaded('sys_action') && $this->getBackendUserAuthentication()->isAdmin()) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_action');
            $queryBuilder->getRestrictions()->removeAll();
            $statement = $queryBuilder->select('uid', 'title')
                ->from('sys_action')
                ->where($queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)))
                ->orderBy('title')
                ->executeQuery();
            $opt[] = '<option value="0">__Save to Action:__</option>';
            while ($row = $statement->fetchAssociative()) {
                $opt[] = '<option value="-' . (int)$row['uid'] . '">' . htmlspecialchars($row['title']
                        . ' [' . (int)$row['uid'] . ']') . '</option>';
            }
        }
        $markup = [];
        $markup[] = '<div class="load-queries">';
        $markup[] = '  <div class="row row-cols-auto">';
        $markup[] = '    <div class="col">';
        $markup[] = '      <select class="form-select" name="storeControl[STORE]" data-assign-store-control-title>' . implode(LF, $opt) . '</select>';
        $markup[] = '    </div>';
        $markup[] = '    <div class="col">';
        $markup[] = '      <input class="form-control" name="storeControl[title]" value="" type="text" max="80">';
        $markup[] = '    </div>';
        $markup[] = '    <div class="col">';
        $markup[] = '      <input class="btn btn-default" type="submit" name="storeControl[LOAD]" value="Load">';
        $markup[] = '    </div>';
        $markup[] = '    <div class="col">';
        $markup[] = '      <input class="btn btn-default" type="submit" name="storeControl[SAVE]" value="Save">';
        $markup[] = '    </div>';
        $markup[] = '    <div class="col">';
        $markup[] = '      <input class="btn btn-default" type="submit" name="storeControl[REMOVE]" value="Remove">';
        $markup[] = '    </div>';
        $markup[] = '  </div>';
        $markup[] = '</div>';

        return implode(LF, $markup);
    }

    /**
     * Init store array
     *
     * @return array
     */
    protected function initStoreArray()
    {
        $storeArray = [
            '0' => '[New]',
        ];
        $savedStoreArray = unserialize($this->settings['storeArray'] ?? '', ['allowed_classes' => false]);
        if (is_array($savedStoreArray)) {
            $storeArray = array_merge($storeArray, $savedStoreArray);
        }
        return $storeArray;
    }

    /**
     * Clean store query configs
     *
     * @param array $storeQueryConfigs
     * @param array $storeArray
     * @return array
     */
    protected function cleanStoreQueryConfigs($storeQueryConfigs, $storeArray)
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

    /**
     * Add to store query configs
     *
     * @param array $storeQueryConfigs
     * @param int $index
     * @return array
     */
    protected function addToStoreQueryConfigs($storeQueryConfigs, $index)
    {
        $keyArr = explode(',', $this->storeList);
        $storeQueryConfigs[$index] = [];
        foreach ($keyArr as $k) {
            $storeQueryConfigs[$index][$k] = $this->settings[$k] ?? null;
        }
        return $storeQueryConfigs;
    }

    /**
     * Save query in action
     *
     * @param int $uid
     * @return bool
     */
    protected function saveQueryInAction($uid)
    {
        if (ExtensionManagementUtility::isLoaded('sys_action')) {
            $keyArr = explode(',', $this->storeList);
            $saveArr = [];
            foreach ($keyArr as $k) {
                $saveArr[$k] = $this->settings[$k];
            }
            // Show query
            if ($saveArr['queryTable']) {
                $this->init('queryConfig', $saveArr['queryTable'], '', $this->settings);
                $this->makeSelectorTable($saveArr);
                $this->enablePrefix = true;
                $queryString = $this->getQuery($this->queryConfig);

                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($this->table);
                $queryBuilder->getRestrictions()->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                $rowCount = $queryBuilder->count('*')
                    ->from($this->table)
                    ->where(QueryHelper::stripLogicalOperatorPrefix($queryString))
                    ->executeQuery()
                    ->fetchOne();

                $t2DataValue = [
                    'qC' => $saveArr,
                    'qCount' => $rowCount,
                    'qSelect' => $this->getSelectQuery($queryString),
                    'qString' => $queryString,
                ];
                GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_action')
                    ->update(
                        'sys_action',
                        ['t2_data' => serialize($t2DataValue)],
                        ['uid' => (int)$uid],
                        ['t2_data' => Connection::PARAM_LOB]
                    );
            }
            return true;
        }
        return false;
    }
    /**
     * Load store query configs
     *
     * @param array $storeQueryConfigs
     * @param int $storeIndex
     * @param array $writeArray
     * @return array
     */
    protected function loadStoreQueryConfigs($storeQueryConfigs, $storeIndex, $writeArray)
    {
        if ($storeQueryConfigs[$storeIndex]) {
            $keyArr = explode(',', $this->storeList);
            foreach ($keyArr as $k) {
                $writeArray[$k] = $storeQueryConfigs[$storeIndex][$k];
            }
        }
        return $writeArray;
    }

    /**
     * Process store control
     *
     * @return string
     */
    protected function procesStoreControl()
    {
        $languageService = $this->getLanguageService();
        $flashMessage = null;
        $storeArray = $this->initStoreArray();
        $storeQueryConfigs = unserialize($this->settings['storeQueryConfigs'] ?? '', ['allowed_classes' => false]);
        $storeControl = GeneralUtility::_GP('storeControl');
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
                        sprintf($languageService->getLL('query_loaded'), $storeArray[$storeIndex])
                    );
                } elseif ($storeIndex < 0 && ExtensionManagementUtility::isLoaded('sys_action')) {
                    $actionRecord = BackendUtility::getRecord('sys_action', abs($storeIndex));
                    if (is_array($actionRecord)) {
                        $dA = unserialize($actionRecord['t2_data'], ['allowed_classes' => false]);
                        $dbSC = [];
                        if (is_array($dA['qC'])) {
                            $dbSC[0] = $dA['qC'];
                        }
                        $writeArray = $this->loadStoreQueryConfigs($dbSC, 0, $writeArray);
                        $saveStoreArray = 1;
                        $flashMessage = GeneralUtility::makeInstance(
                            FlashMessage::class,
                            sprintf($languageService->getLL('query_from_action_loaded'), $actionRecord['title'])
                        );
                    }
                }
            } elseif ($storeControl['SAVE'] ?? false) {
                if ($storeIndex < 0) {
                    $qOK = $this->saveQueryInAction(abs($storeIndex));
                    if ($qOK) {
                        $flashMessage = GeneralUtility::makeInstance(
                            FlashMessage::class,
                            $languageService->getLL('query_saved')
                        );
                    } else {
                        $flashMessage = GeneralUtility::makeInstance(
                            FlashMessage::class,
                            $languageService->getLL('query_notsaved'),
                            '',
                            FlashMessage::ERROR
                        );
                    }
                } else {
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
                            $languageService->getLL('query_saved')
                        );
                    }
                }
            } elseif ($storeControl['REMOVE'] ?? false) {
                if ($storeIndex > 0) {
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        sprintf($languageService->getLL('query_removed'), $storeArray[$storeControl['STORE']])
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
            $this->settings = BackendUtility::getModuleData(
                $this->menuItems,
                $writeArray,
                $this->moduleName,
                'ses'
            );
        }
        return $msg;
    }

    /**
     * Get query result code
     *
     * @param string $type
     * @param array $dataRows Rows to display
     * @param string $table
     * @return array HTML-code for "header" and "content"
     * @throws \TYPO3\CMS\Core\Exception
     */
    protected function getQueryResultCode($type, array $dataRows, $table)
    {
        $out = '';
        $cPR = [];
        switch ($type) {
            case 'count':
                $cPR['header'] = 'Count';
                $cPR['content'] = '<br><strong>' . (int)$dataRows[0] . '</strong> records selected.';
                break;
            case 'all':
                $rowArr = [];
                $dataRow = null;
                foreach ($dataRows as $dataRow) {
                    $rowArr[] = $this->resultRowDisplay($dataRow, $GLOBALS['TCA'][$table], $table);
                }
                if (is_array($this->hookArray['beforeResultTable'] ?? false)) {
                    foreach ($this->hookArray['beforeResultTable'] as $_funcRef) {
                        $out .= GeneralUtility::callUserFunction($_funcRef, $this->settings);
                    }
                }
                if (!empty($rowArr)) {
                    $cPR['header'] = 'Result';
                    $out .= '<table class="table table-striped table-hover">'
                        . $this->resultRowTitles((array)$dataRow, $GLOBALS['TCA'][$table]) . implode(LF, $rowArr)
                        . '</table>';
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
                    $out .= '<textarea name="whatever" rows="20" class="text-monospace" style="width:100%">'
                        . htmlspecialchars(implode(LF, $rowArr))
                        . '</textarea>';
                    if (!$this->noDownloadB) {
                        $out .= '<br><input class="btn btn-default" type="submit" name="download_file" '
                            . 'value="Click to download file">';
                    }
                    // Downloads file:
                    // @todo: args. routing anyone?
                    if (GeneralUtility::_GP('download_file')) {
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
                    $out .= '<br />' . DebugUtility::viewArray($dataRow);
                }
                $cPR['header'] = 'Explain SQL query';
                $cPR['content'] = $out;
        }
        return $cPR;
    }
    /**
     * CSV values
     *
     * @param array $row
     * @param string $delim
     * @param string $quote
     * @param array $conf
     * @param string $table
     * @return string A single line of CSV
     */
    protected function csvValues($row, $delim = ',', $quote = '"', $conf = [], $table = '')
    {
        $valueArray = $row;
        if (($this->settings['search_result_labels'] ?? false) && $table) {
            foreach ($valueArray as $key => $val) {
                $valueArray[$key] = $this->getProcessedValueExtra($table, $key, $val, $conf, ';');
            }
        }
        return CsvUtility::csvValues($valueArray, $delim, $quote);
    }

    /**
     * Result row display
     *
     * @param array $row
     * @param array $conf
     * @param string $table
     * @return string
     */
    protected function resultRowDisplay($row, $conf, $table)
    {
        $languageService = $this->getLanguageService();
        $out = '<tr>';
        foreach ($row as $fieldName => $fieldValue) {
            if (GeneralUtility::inList($this->settings['queryFields'] ?? '', $fieldName)
                || !($this->settings['queryFields'] ?? false)
                && $fieldName !== 'pid'
                && $fieldName !== 'deleted'
            ) {
                if ($this->settings['search_result_labels'] ?? false) {
                    $fVnew = $this->getProcessedValueExtra($table, $fieldName, $fieldValue, $conf, '<br />');
                } else {
                    $fVnew = htmlspecialchars($fieldValue);
                }
                $out .= '<td>' . $fVnew . '</td>';
            }
        }
        $out .= '<td>';
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        if (!($row['deleted'] ?? false)) {
            $out .= '<div class="btn-group" role="group">';
            $url = (string)$uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    $table => [
                        $row['uid'] => 'edit',
                    ],
                ],
                'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri()
                    . HttpUtility::buildQueryString(['SET' => (array)GeneralUtility::_POST('SET')], '&'),
            ]);
            $out .= '<a class="btn btn-default" href="' . htmlspecialchars($url) . '">'
                . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render() . '</a>';
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
                        'redirect' => GeneralUtility::linkThisScript(),
                    ])) . '" title="' . htmlspecialchars($languageService->getLL('undelete_only')) . '">';
            $out .= $this->iconFactory->getIcon('actions-edit-restore', Icon::SIZE_SMALL)->render() . '</a>';
            $formEngineParameters = [
                'edit' => [
                    $table => [
                        $row['uid'] => 'edit',
                    ],
                ],
                'returnUrl' => GeneralUtility::linkThisScript(),
            ];
            $redirectUrl = (string)$uriBuilder->buildUriFromRoute('record_edit', $formEngineParameters);
            $out .= '<a class="btn btn-default" href="' . htmlspecialchars((string)$uriBuilder->buildUriFromRoute('tce_db', [
                    'cmd' => [
                        $table => [
                            $row['uid'] => [
                                'undelete' => 1,
                            ],
                        ],
                    ],
                    'redirect' => $redirectUrl,
                ])) . '" title="' . htmlspecialchars($languageService->getLL('undelete_and_edit')) . '">';
            $out .= $this->iconFactory->getIcon('actions-delete-edit', Icon::SIZE_SMALL)->render() . '</a>';
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

    /**
     * Get processed value extra
     *
     * @param string $table
     * @param string $fieldName
     * @param string $fieldValue
     * @param array $conf Not used
     * @param string $splitString
     * @return string
     */
    protected function getProcessedValueExtra($table, $fieldName, $fieldValue, $conf, $splitString)
    {
        $out = '';
        $fields = [];
        // Analysing the fields in the table.
        if (is_array($GLOBALS['TCA'][$table] ?? null)) {
            $fC = $GLOBALS['TCA'][$table]['columns'][$fieldName] ?? null;
            $fields = $fC['config'] ?? [];
            $fields['exclude'] = $fC['exclude'] ?? '';
            if (is_array($fC) && $fC['label']) {
                $fields['label'] = preg_replace('/:$/', '', trim($this->getLanguageService()->sL($fC['label'])));
                switch ($fields['type']) {
                    case 'input':
                        if (preg_match('/int|year/i', $fields['eval'] ?? '')) {
                            $fields['type'] = 'number';
                        } elseif (preg_match('/time/i', $fields['eval'] ?? '')) {
                            $fields['type'] = 'time';
                        } elseif (preg_match('/date/i', $fields['eval'] ?? '')) {
                            $fields['type'] = 'date';
                        } else {
                            $fields['type'] = 'text';
                        }
                        break;
                    case 'check':
                        if (!$fields['items']) {
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
                        if ($fields['foreign_table']) {
                            $fields['type'] = 'relation';
                        }
                        if ($fields['special']) {
                            $fields['type'] = 'text';
                        }
                        break;
                    case 'group':
                        if (($fields['internal_type'] ?? '') !== 'folder') {
                            $fields['type'] = 'relation';
                        }
                        break;
                    case 'user':
                    case 'flex':
                    case 'passthrough':
                    case 'none':
                    case 'text':
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
                    case 'cruser_id':
                        $fields['type'] = 'relation';
                        $fields['allowed'] = 'be_users';
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
                    // @todo Replace deprecated strftime in php 8.1. Suppress warning in v11.
                    $out = (string)@strftime('%d-%m-%Y', (int)$fieldValue);
                }
                break;
            case 'time':
                if ($fieldValue != -1) {
                    if ($splitString === '<br />') {
                        // @todo Replace deprecated strftime in php 8.1. Suppress warning in v11.
                        $out = (string)@strftime('%H:%M' . $splitString . '%d-%m-%Y', (int)$fieldValue);
                    } else {
                        // @todo Replace deprecated strftime in php 8.1. Suppress warning in v11.
                        $out = (string)@strftime('%H:%M %d-%m-%Y', (int)$fieldValue);
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

    /**
     * Recursively fetch all descendants of a given page
     *
     * @param int $id uid of the page
     * @param int $depth
     * @param int $begin
     * @param string $permsClause
     * @return string comma separated list of descendant pages
     */
    protected function getTreeList($id, $depth, $begin = 0, $permsClause = '')
    {
        $depth = (int)$depth;
        $begin = (int)$begin;
        $id = (int)$id;
        if ($id < 0) {
            $id = abs($id);
        }
        if ($begin == 0) {
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
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)),
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
     * Make value list
     *
     * @param string $fieldName
     * @param string $fieldValue
     * @param array $conf
     * @param string $table
     * @param string $splitString
     * @return string
     */
    protected function makeValueList($fieldName, $fieldValue, $conf, $table, $splitString)
    {
        $backendUserAuthentication = $this->getBackendUserAuthentication();
        $languageService = $this->getLanguageService();
        $from_table_Arr = [];
        $fieldSetup = $conf;
        $out = '';
        if ($fieldSetup['type'] === 'multiple') {
            foreach ($fieldSetup['items'] as $key => $val) {
                if (strpos($val[0], 'LLL:') === 0) {
                    $value = $languageService->sL($val[0]);
                } else {
                    $value = $val[0];
                }
                if (GeneralUtility::inList($fieldValue, $val[1]) || $fieldValue == $val[1]) {
                    if ($out !== '') {
                        $out .= $splitString;
                    }
                    $out .= htmlspecialchars($value);
                }
            }
        }
        if ($fieldSetup['type'] === 'binary') {
            foreach ($fieldSetup['items'] as $Key => $val) {
                if (strpos($val[0], 'LLL:') === 0) {
                    $value = $languageService->sL($val[0]);
                } else {
                    $value = $val[0];
                }
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
                if (strpos($val[0], 'LLL:') === 0) {
                    $value = $languageService->sL($val[0]);
                } else {
                    $value = $val[0];
                }
                if (GeneralUtility::inList($fieldValue, $value) || $fieldValue == $value) {
                    if ($out !== '') {
                        $out .= $splitString;
                    }
                    $out .= htmlspecialchars($value);
                }
            }
            if (str_contains($fieldSetup['allowed'], ',')) {
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
                $from_table_Arr[0] = $fieldSetup['allowed'];
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
                if ($useTablePrefix && !$dontPrefixFirstTable && $counter != 1 || $counter == 1) {
                    $tablePrefix = $from_table . '_';
                }
                $counter = 1;
                if (is_array($GLOBALS['TCA'][$from_table] ?? null)) {
                    $labelField = $GLOBALS['TCA'][$from_table]['ctrl']['label'] ?? '';
                    $altLabelField = $GLOBALS['TCA'][$from_table]['ctrl']['label_alt'] ?? '';
                    if (is_array($GLOBALS['TCA'][$from_table]['columns'][$labelField]['config']['items'] ?? false)) {
                        $items = $GLOBALS['TCA'][$from_table]['columns'][$labelField]['config']['items'];
                        foreach ($items as $labelArray) {
                            if (str_starts_with($labelArray[0], 'LLL:')) {
                                $labelFieldSelect[$labelArray[1]] = $languageService->sL($labelArray[0]);
                            } else {
                                $labelFieldSelect[$labelArray[1]] = $labelArray[0];
                            }
                        }
                        $useSelectLabels = 1;
                    }
                    $altLabelFieldSelect = [];
                    if (is_array($GLOBALS['TCA'][$from_table]['columns'][$altLabelField]['config']['items'] ?? false)) {
                        $items = $GLOBALS['TCA'][$from_table]['columns'][$altLabelField]['config']['items'];
                        foreach ($items as $altLabelArray) {
                            if (str_starts_with($altLabelArray[0], 'LLL:')) {
                                $altLabelFieldSelect[$altLabelArray[1]] = $languageService->sL($altLabelArray[0]);
                            } else {
                                $altLabelFieldSelect[$altLabelArray[1]] = $altLabelArray[0];
                            }
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
                        $this->settings['labels_noprefix'] =
                            $this->settings['labels_noprefix'] == 1
                                ? 'on'
                                : $this->settings['labels_noprefix'];
                        $prefixString =
                            $this->settings['labels_noprefix'] === 'on'
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
     * Render table header
     *
     * @param array $row Table columns
     * @param array $conf Table TCA
     * @return string HTML of table header
     */
    protected function resultRowTitles($row, $conf)
    {
        $languageService = $this->getLanguageService();
        $tableHeader = [];
        // Start header row
        $tableHeader[] = '<thead><tr>';
        // Iterate over given columns
        foreach ($row as $fieldName => $fieldValue) {
            if (GeneralUtility::inList($this->settings['queryFields'] ?? '', $fieldName)
                || !($this->settings['queryFields'] ?? false)
                && $fieldName !== 'pid'
                && $fieldName !== 'deleted'
            ) {
                if ($this->settings['search_result_labels'] ?? false) {
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
    /**
     * @throws \InvalidArgumentException
     * @throws \TYPO3\CMS\Core\Exception
     */
    private function renderNoResultsFoundMessage()
    {
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, 'No rows selected!', '', FlashMessage::INFO);
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * Make a list of fields for current table
     *
     * @return string Separated list of fields
     */
    protected function makeFieldList()
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
            if ($GLOBALS['TCA'][$this->table]['ctrl']['cruser_id'] ?? false) {
                $fieldListArr[] = $GLOBALS['TCA'][$this->table]['ctrl']['cruser_id'];
            }
            if ($GLOBALS['TCA'][$this->table]['ctrl']['sortby'] ?? false) {
                $fieldListArr[] = $GLOBALS['TCA'][$this->table]['ctrl']['sortby'];
            }
        }
        return implode(',', $fieldListArr);
    }

    /**
     * Init function
     *
     * @param string $name The name
     * @param string $table The table name
     * @param string $fieldList The field list
     * @param array $settings Module settings like checkboxes in the interface
     */
    protected function init($name, $table, $fieldList = '', array $settings = [])
    {
        // Analysing the fields in the table.
        if (is_array($GLOBALS['TCA'][$table] ?? false)) {
            $this->name = $name;
            $this->table = $table;
            $this->fieldList = $fieldList ?: $this->makeFieldList();
            $this->settings = $settings;
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
                            } elseif (preg_match('/time/i', ($this->fields[$fieldName]['eval'] ?? ''))) {
                                $this->fields[$fieldName]['type'] = 'time';
                            } elseif (preg_match('/date/i', ($this->fields[$fieldName]['eval'] ?? ''))) {
                                $this->fields[$fieldName]['type'] = 'date';
                            } else {
                                $this->fields[$fieldName]['type'] = 'text';
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
                            if (($this->fields[$fieldName]['internal_type'] ?? '') !== 'folder') {
                                $this->fields[$fieldName]['type'] = 'relation';
                            }
                            break;
                        case 'user':
                        case 'flex':
                        case 'passthrough':
                        case 'none':
                        case 'text':
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
                        case 'cruser_id':
                            $this->fields[$fieldName]['type'] = 'relation';
                            $this->fields[$fieldName]['allowed'] = 'be_users';
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

    /**
     * Set and clean up external lists
     *
     * @param string $name The name
     * @param string $list The list
     * @param string $force
     */
    protected function setAndCleanUpExternalLists($name, $list, $force = '')
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

    /**
     * Process data
     *
     * @param array $qC Query config
     */
    protected function procesData($qC = [])
    {
        $this->queryConfig = $qC;
        $POST = GeneralUtility::_POST();
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

    /**
     * Clean up query config
     *
     * @param array $queryConfig Query config
     * @return array
     */
    protected function cleanUpQueryConfig($queryConfig)
    {
        // Since we don't traverse the array using numeric keys in the upcoming while-loop make sure it's fresh and clean before displaying
        if (!empty($queryConfig) && is_array($queryConfig)) {
            ksort($queryConfig);
        } else {
            // queryConfig should never be empty!
            if (!isset($queryConfig[0]) || empty($queryConfig[0]['type'])) {
                // Make sure queryConfig is an array
                $queryConfig = [];
                $queryConfig[0] = ['type' => 'FIELD_'];
            }
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
                    $queryConfig[$key]['comparison'] = $this->verifyComparison($conf['comparison'] ?? '0', ($conf['negate'] ?? null) ? 1 : 0);
                    $queryConfig[$key]['inputValue'] = $this->cleanInputVal($queryConfig[$key]);
                    $queryConfig[$key]['inputValue1'] = $this->cleanInputVal($queryConfig[$key], '1');
            }
        }
        return $queryConfig;
    }

    /**
     * Get form elements
     *
     * @param int $subLevel
     * @param string $queryConfig
     * @param string $parent
     * @return array
     */
    protected function getFormElements($subLevel = 0, $queryConfig = '', $parent = '')
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
                    $lineHTML[] = '<div class="row row-cols-auto mb-2 mb-sm-0">';
                    $lineHTML[] = $this->makeComparisonSelector($subscript, $fieldName, $conf);
                    if ($conf['comparison'] === 100 || $conf['comparison'] === 101) {
                        // between
                        $lineHTML[] = $this->getDateTimePickerField($fieldPrefix . '[inputValue]', $conf['inputValue'], 'date');
                        $lineHTML[] = $this->getDateTimePickerField($fieldPrefix . '[inputValue1]', $conf['inputValue1'], 'date');
                    } else {
                        $lineHTML[] = $this->getDateTimePickerField($fieldPrefix . '[inputValue]', $conf['inputValue'], 'date');
                    }
                    $lineHTML[] = '</div>';
                    break;
                case 'time':
                    $lineHTML[] = '<div class="row row-cols-auto mb-2 mb-sm-0">';
                    $lineHTML[] = $this->makeComparisonSelector($subscript, $fieldName, $conf);
                    if ($conf['comparison'] === 100 || $conf['comparison'] === 101) {
                        // between:
                        $lineHTML[] = $this->getDateTimePickerField($fieldPrefix . '[inputValue]', $conf['inputValue'], 'datetime');
                        $lineHTML[] = $this->getDateTimePickerField($fieldPrefix . '[inputValue1]', $conf['inputValue1'], 'datetime');
                    } else {
                        $lineHTML[] = $this->getDateTimePickerField($fieldPrefix . '[inputValue]', $conf['inputValue'], 'datetime');
                    }
                    $lineHTML[] = '</div>';
                    break;
                case 'multiple':
                case 'binary':
                case 'relation':
                    $lineHTML[] = '<div class="row row-cols-auto mb-2 mb-sm-0">';
                    $lineHTML[] = $this->makeComparisonSelector($subscript, $fieldName, $conf);
                    $lineHTML[] = '<div class="col mb-sm-2">';
                    if ($conf['comparison'] === 68 || $conf['comparison'] === 69 || $conf['comparison'] === 162 || $conf['comparison'] === 163) {
                        $lineHTML[] = '<select class="form-select" name="' . $fieldPrefix . '[inputValue][]" multiple="multiple">';
                    } elseif ($conf['comparison'] === 66 || $conf['comparison'] === 67) {
                        if (is_array($conf['inputValue'])) {
                            $conf['inputValue'] = implode(',', $conf['inputValue']);
                        }
                        $lineHTML[] = '<input class="form-control t3js-clearable" type="text" value="' . htmlspecialchars($conf['inputValue']) . '" name="' . $fieldPrefix . '[inputValue]">';
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
                    $lineHTML[] = '<div class="row row-cols-auto mb-2 mb-sm-0">';
                    $lineHTML[] = $this->makeComparisonSelector($subscript, $fieldName, $conf);
                    $lineHTML[] = '<input type="hidden" value="1" name="' . $fieldPrefix . '[inputValue]">';
                    $lineHTML[] = '</div>';
                    break;
                default:
                    $lineHTML[] = '<div class="row row-cols-auto mb-2 mb-sm-0">';
                    $lineHTML[] = $this->makeComparisonSelector($subscript, $fieldName, $conf);
                    $lineHTML[] = '<div class="col mb-sm-2">';
                    if ($conf['comparison'] === 37 || $conf['comparison'] === 36) {
                        // between:
                        $lineHTML[] = '<input class="form-control t3js-clearable" type="text" value="' . htmlspecialchars($conf['inputValue']) . '" name="' . $fieldPrefix . '[inputValue]">';
                        $lineHTML[] = '<input class="form-control t3js-clearable" type="text" value="' . htmlspecialchars($conf['inputValue1']) . '" name="' . $fieldPrefix . '[inputValue1]">';
                    } else {
                        $lineHTML[] = '<input class="form-control t3js-clearable" type="text" value="' . htmlspecialchars($conf['inputValue']) . '" name="' . $fieldPrefix . '[inputValue]">';
                    }
                    $lineHTML[] = '</div>';
                    $lineHTML[] = '</div>';
            }
            if ($fieldType !== 'ignore') {
                $lineHTML[] = '<div class="row row-cols-auto mb-2">';
                $lineHTML[] = '<div class="btn-group">';
                $lineHTML[] = $this->updateIcon();
                if ($loopCount) {
                    $lineHTML[] = '<button class="btn btn-default" title="Remove condition" name="qG_del' . htmlspecialchars($subscript) . '"><i class="fa fa-trash fa-fw"></i></button>';
                }
                $lineHTML[] = '<button class="btn btn-default" title="Add condition" name="qG_ins' . htmlspecialchars($subscript) . '"><i class="fa fa-plus fa-fw"></i></button>';
                if ($c != 0) {
                    $lineHTML[] = '<button class="btn btn-default" title="Move up" name="qG_up' . htmlspecialchars($subscript) . '"><i class="fa fa-chevron-up fa-fw"></i></button>';
                }
                if ($c != 0 && $fieldType !== 'newlevel') {
                    $lineHTML[] = '<button class="btn btn-default" title="New level" name="qG_nl' . htmlspecialchars($subscript) . '"><i class="fa fa-chevron-right fa-fw"></i></button>';
                }
                if ($fieldType === 'newlevel') {
                    $lineHTML[] = '<button class="btn btn-default" title="Collapse new level" name="qG_remnl' . htmlspecialchars($subscript) . '"><i class="fa fa-chevron-left fa-fw"></i></button>';
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

    /**
     * @param string $subscript
     * @param string $fieldName
     * @param array $conf
     *
     * @return string
     */
    protected function makeComparisonSelector($subscript, $fieldName, $conf)
    {
        $fieldPrefix = $this->name . $subscript;
        $lineHTML = [];
        $lineHTML[] = '<div class="col mb-sm-2">';
        $lineHTML[] =     $this->mkTypeSelect($fieldPrefix . '[type]', $fieldName);
        $lineHTML[] = '</div>';
        $lineHTML[] = '<div class="col mb-sm-2">';
        $lineHTML[] = '	 <div class="input-group">';
        $lineHTML[] =      $this->mkCompSelect($fieldPrefix . '[comparison]', $conf['comparison'], ($conf['negate'] ?? null) ? 1 : 0);
        $lineHTML[] = '	   <span class="input-group-addon">';
        $lineHTML[] = '		 <input type="checkbox" class="checkbox t3js-submit-click"' . (($conf['negate'] ?? null) ? ' checked' : '') . ' name="' . htmlspecialchars($fieldPrefix) . '[negate]">';
        $lineHTML[] = '	   </span>';
        $lineHTML[] = '  </div>';
        $lineHTML[] = '	</div>';
        return implode(LF, $lineHTML);
    }

    /**
     * Make option list
     *
     * @param string $fieldName
     * @param array $conf
     * @param string $table
     * @return string
     */
    protected function makeOptionList($fieldName, $conf, $table)
    {
        $backendUserAuthentication = $this->getBackendUserAuthentication();
        $from_table_Arr = [];
        $out = [];
        $fieldSetup = $this->fields[$fieldName];
        $languageService = $this->getLanguageService();
        if ($fieldSetup['type'] === 'multiple') {
            $optGroupOpen = false;
            foreach (($fieldSetup['items'] ?? []) as $val) {
                if (strpos($val[0], 'LLL:') === 0) {
                    $value = $languageService->sL($val[0]);
                } else {
                    $value = $val[0];
                }
                if ($val[1] === '--div--') {
                    if ($optGroupOpen) {
                        $out[] = '</optgroup>';
                    }
                    $optGroupOpen = true;
                    $out[] = '<optgroup label="' . htmlspecialchars($value) . '">';
                } elseif (GeneralUtility::inList($conf['inputValue'], $val[1])) {
                    $out[] = '<option value="' . htmlspecialchars($val[1]) . '" selected>' . htmlspecialchars($value) . '</option>';
                } else {
                    $out[] = '<option value="' . htmlspecialchars($val[1]) . '">' . htmlspecialchars($value) . '</option>';
                }
            }
            if ($optGroupOpen) {
                $out[] = '</optgroup>';
            }
        }
        if ($fieldSetup['type'] === 'binary') {
            foreach ($fieldSetup['items'] as $key => $val) {
                if (strpos($val[0], 'LLL:') === 0) {
                    $value = $languageService->sL($val[0]);
                } else {
                    $value = $val[0];
                }
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
                if (strpos($val[0], 'LLL:') === 0) {
                    $value = $languageService->sL($val[0]);
                } else {
                    $value = $val[0];
                }
                if (GeneralUtility::inList($conf['inputValue'], $val[1])) {
                    $out[] = '<option value="' . htmlspecialchars($val[1]) . '" selected>' . htmlspecialchars($value) . '</option>';
                } else {
                    $out[] = '<option value="' . htmlspecialchars($val[1]) . '">' . htmlspecialchars($value) . '</option>';
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
                if ($useTablePrefix && !$dontPrefixFirstTable && $counter != 1 || $counter === 1) {
                    $tablePrefix = $from_table . '_';
                }
                $counter = 1;
                if (is_array($GLOBALS['TCA'][$from_table])) {
                    $labelField = $GLOBALS['TCA'][$from_table]['ctrl']['label'] ?? '';
                    $altLabelField = $GLOBALS['TCA'][$from_table]['ctrl']['label_alt'] ?? '';
                    if ($GLOBALS['TCA'][$from_table]['columns'][$labelField]['config']['items'] ?? false) {
                        foreach ($GLOBALS['TCA'][$from_table]['columns'][$labelField]['config']['items'] as $labelArray) {
                            if (strpos($labelArray[0], 'LLL:') === 0) {
                                $labelFieldSelect[$labelArray[1]] = $languageService->sL($labelArray[0]);
                            } else {
                                $labelFieldSelect[$labelArray[1]] = $labelArray[0];
                            }
                        }
                        $useSelectLabels = true;
                    }
                    $altLabelFieldSelect = [];
                    if ($GLOBALS['TCA'][$from_table]['columns'][$altLabelField]['config']['items'] ?? false) {
                        foreach ($GLOBALS['TCA'][$from_table]['columns'][$altLabelField]['config']['items'] as $altLabelArray) {
                            if (strpos($altLabelArray[0], 'LLL:') === 0) {
                                $altLabelFieldSelect[$altLabelArray[1]] = $languageService->sL($altLabelArray[0]);
                            } else {
                                $altLabelFieldSelect[$altLabelArray[1]] = $altLabelArray[0];
                            }
                        }
                        $useAltSelectLabels = true;
                    }

                    if (!($this->tableArray[$from_table] ?? false)) {
                        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($from_table);
                        $queryBuilder->getRestrictions()->removeAll();
                        if (empty($this->settings['show_deleted'])) {
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
                    if (isset($this->settings['options_sortlabel']) && $this->settings['options_sortlabel'] && is_array($outArray)) {
                        natcasesort($outArray);
                    }
                }
            }
            foreach ($outArray as $key2 => $val2) {
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
     * Print code array
     *
     * @param array $codeArr
     * @param int $recursionLevel
     * @return string
     */
    protected function printCodeArray($codeArr, $recursionLevel = 0)
    {
        $out = [];
        foreach (array_values($codeArr) as $queryComponent) {
            $out[] = '<div class="card">';
            $out[] =     '<div class="card-body pb-2">';
            $out[] =         $queryComponent['html'];

            if ($this->enableQueryParts) {
                $out[] = '<div class="row row-cols-auto mb-2">';
                $out[] =     '<div class="col">';
                $out[] =         '<code class="m-0">';
                $out[] =             htmlspecialchars($queryComponent['query']);
                $out[] =         '</code>';
                $out[] =     '</div>';
                $out[] = '</div>';
            }
            if (is_array($queryComponent['sub'] ?? null)) {
                $out[] = '<div class="mb-2">';
                $out[] =     $this->printCodeArray($queryComponent['sub'], $recursionLevel + 1);
                $out[] = '</div>';
            }
            $out[] =     '</div>';
            $out[] = '</div>';
        }
        return implode(LF, $out);
    }

    /**
     * Make operator select
     *
     * @param string $name
     * @param string $op
     * @param bool $draw
     * @param bool $submit
     * @return string
     */
    protected function mkOperatorSelect($name, $op, $draw, $submit)
    {
        $out = [];
        if ($draw) {
            $out[] = '<div class="row row-cols-auto mb-2">';
            $out[] = '	<div class="col">';
            $out[] = '    <select class="form-select' . ($submit ? ' t3js-submit-change' : '') . '" name="' . htmlspecialchars($name) . '[operator]">';
            $out[] = '	    <option value="AND"' . (!$op || $op === 'AND' ? ' selected' : '') . '>' . htmlspecialchars($this->lang['AND']) . '</option>';
            $out[] = '	    <option value="OR"' . ($op === 'OR' ? ' selected' : '') . '>' . htmlspecialchars($this->lang['OR']) . '</option>';
            $out[] = '    </select>';
            $out[] = '	</div>';
            $out[] = '</div>';
        } else {
            $out[] = '<input type="hidden" value="' . htmlspecialchars($op) . '" name="' . htmlspecialchars($name) . '[operator]">';
        }
        return implode(LF, $out);
    }

    /**
     * Make type select
     *
     * @param string $name
     * @param string $fieldName
     * @param string $prepend
     * @return string
     */
    protected function mkTypeSelect($name, $fieldName, $prepend = 'FIELD_')
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

    /**
     * Verify type
     *
     * @param string $fieldName
     * @return string
     */
    protected function verifyType($fieldName)
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
     * Verify comparison
     *
     * @param string $comparison
     * @param int $neg
     * @return int
     */
    protected function verifyComparison($comparison, $neg)
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
     * Make field to input select
     *
     * @param string $name
     * @param string $fieldName
     * @return string
     */
    protected function mkFieldToInputSelect($name, $fieldName)
    {
        $out = [];
        $out[] = '<div class="input-group mb-2">';
        $out[] = '	<span class="input-group-btn">';
        $out[] = $this->updateIcon();
        $out[] = ' 	</span>';
        $out[] = '	<input type="text" class="form-control t3js-clearable" value="' . htmlspecialchars($fieldName) . '" name="' . htmlspecialchars($name) . '">';
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

    /**
     * Make table select
     *
     * @param string $name
     * @param string $cur
     * @return string
     */
    protected function mkTableSelect($name, $cur)
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
     * Make comparison select
     *
     * @param string $name
     * @param string $comparison
     * @param int $neg
     * @return string
     */
    protected function mkCompSelect($name, $comparison, $neg)
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

    /**
     * Get subscript
     *
     * @param array $arr
     * @return array
     */
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

    /**
     * Get query
     *
     * @param array $queryConfig
     * @param string $pad
     * @return string
     */
    protected function getQuery($queryConfig, $pad = '')
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

    /**
     * Convert ISO-8601 timestamp (string) into unix timestamp (int)
     *
     * @param array $conf
     * @return array
     */
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
     *
     * @param mixed $date
     * @return bool
     */
    protected function isDateOfIso8601Format($date): bool
    {
        if (!is_int($date) && !is_string($date)) {
            return false;
        }
        $format = 'Y-m-d\\TH:i:s\\Z';
        $formattedDate = \DateTime::createFromFormat($format, (string)$date);
        return $formattedDate && $formattedDate->format($format) === $date;
    }

    /**
     * Get single query
     *
     * @param array $conf
     * @param bool $first
     * @return string
     */
    protected function getQuerySingle($conf, $first)
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
     * Clean input value
     *
     * @param array $conf
     * @param string $suffix
     * @return string|int|float|null
     */
    protected function cleanInputVal($conf, $suffix = '')
    {
        $comparison = (int)($conf['comparison'] ?? 0);
        if ($comparison >> 5 === 0 || ($comparison === 32 || $comparison === 33 || $comparison === 64 || $comparison === 65 || $comparison === 66 || $comparison === 67 || $comparison === 96 || $comparison === 97)) {
            $inputVal = $conf['inputValue' . $suffix] ?? null;
        } elseif ($comparison === 39 || $comparison === 38) {
            // in list:
            $inputVal = implode(',', GeneralUtility::intExplode(',', ($conf['inputValue' . $suffix] ?? '')));
        } elseif ($comparison === 68 || $comparison === 69 || $comparison === 162 || $comparison === 163) {
            // in list:
            if (is_array($conf['inputValue' . $suffix] ?? false)) {
                $inputVal = implode(',', $conf['inputValue' . $suffix]);
            } elseif ($conf['inputValue' . $suffix] ?? false) {
                $inputVal = $conf['inputValue' . $suffix];
            } else {
                $inputVal = 0;
            }
        } elseif (!is_array($conf['inputValue' . $suffix] ?? null) && strtotime($conf['inputValue' . $suffix] ?? '')) {
            $inputVal = $conf['inputValue' . $suffix];
        } elseif (!is_array($conf['inputValue' . $suffix] ?? null) && MathUtility::canBeInterpretedAsInteger($conf['inputValue' . $suffix] ?? null)) {
            $inputVal = (int)$conf['inputValue' . $suffix];
        } else {
            // TODO: Six eyes looked at this code and nobody understood completely what is going on here and why we
            // fallback to float casting, the whole class smells like it needs a refactoring.
            $inputVal = (float)($conf['inputValue' . $suffix] ?? 0.0);
        }
        return $inputVal;
    }

    /**
     * Update icon
     *
     * @return string
     */
    protected function updateIcon()
    {
        return '<button class="btn btn-default" title="Update" name="just_update"><i class="fa fa-refresh fa-fw"></i></button>';
    }

    /**
     * Get label column
     *
     * @return string
     */
    protected function getLabelCol()
    {
        return $GLOBALS['TCA'][$this->table]['ctrl']['label'];
    }

    /**
     * Make selector table
     *
     * @param array $modSettings
     * @param string $enableList
     * @return string
     */
    protected function makeSelectorTable($modSettings, $enableList = 'table,fields,query,group,order,limit')
    {
        $out = [];
        $enableArr = explode(',', $enableList);
        $userTsConfig = $this->getBackendUserAuthentication()->getTSConfig();

        // Make output
        if (in_array('table', $enableArr) && !($userTsConfig['mod.']['dbint.']['disableSelectATable'] ?? false)) {
            $out[] = '<div class="form-group">';
            $out[] =     '<label for="SET[queryTable]">Select a table:</label>';
            $out[] =     '<div class="row row-cols-auto">';
            $out[] =         '<div class="col">';
            $out[] =             $this->mkTableSelect('SET[queryTable]', $this->table);
            $out[] =         '</div>';
            $out[] =     '</div>';
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
            $parts = GeneralUtility::intExplode(',', $this->extFieldLists['queryLimit']);
            $limitBegin = 0;
            $limitLength = (int)($this->extFieldLists['queryLimit'] ?? 0);
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
            $this->procesData(($modSettings['queryConfig'] ?? false) ? unserialize($modSettings['queryConfig'] ?? '', ['allowed_classes' => false]) : []);
            $this->queryConfig = $this->cleanUpQueryConfig($this->queryConfig);
            $this->enableQueryParts = (bool)($modSettings['search_query_smallparts'] ?? false);
            $codeArr = $this->getFormElements();
            $queryCode = $this->printCodeArray($codeArr);
            if (in_array('fields', $enableArr) && !($userTsConfig['mod.']['dbint.']['disableSelectFields'] ?? false)) {
                $out[] = '<div class="form-group form-group-with-button-addon">';
                $out[] = '	<label for="SET[queryFields]">Select fields:</label>';
                $out[] =    $this->mkFieldToInputSelect('SET[queryFields]', $this->extFieldLists['queryFields']);
                $out[] = '</div>';
            }
            if (in_array('query', $enableArr) && !($userTsConfig['mod.']['dbint.']['disableMakeQuery'] ?? false)) {
                $out[] = '<div class="form-group">';
                $out[] = '	<label>Make Query:</label>';
                $out[] =    $queryCode;
                $out[] = '</div>';
            }
            if (in_array('group', $enableArr) && !($userTsConfig['mod.']['dbint.']['disableGroupBy'] ?? false)) {
                $out[] = '<div class="form-group">';
                $out[] =    '<label for="SET[queryGroup]">Group By:</label>';
                $out[] =     '<div class="row row-cols-auto">';
                $out[] =         '<div class="col">';
                $out[] =             $this->mkTypeSelect('SET[queryGroup]', $this->extFieldLists['queryGroup'], '');
                $out[] =         '</div>';
                $out[] =     '</div>';
                $out[] = '</div>';
            }
            if (in_array('order', $enableArr) && !($userTsConfig['mod.']['dbint.']['disableOrderBy'] ?? false)) {
                $orderByArr = explode(',', $this->extFieldLists['queryOrder']);
                $orderBy = [];
                $orderBy[] = '<div class="row row-cols-auto align-items-center">';
                $orderBy[] =     '<div class="col">';
                $orderBy[] =         $this->mkTypeSelect('SET[queryOrder]', $orderByArr[0], '');
                $orderBy[] =     '</div>';
                $orderBy[] =     '<div class="col mt-2">';
                $orderBy[] =         '<div class="form-check">';
                $orderBy[] =              BackendUtility::getFuncCheck(0, 'SET[queryOrderDesc]', $modSettings['queryOrderDesc'] ?? '', '', '', 'id="checkQueryOrderDesc"');
                $orderBy[] =              '<label class="form-check-label" for="checkQueryOrderDesc">Descending</label>';
                $orderBy[] =         '</div>';
                $orderBy[] =     '</div>';
                $orderBy[] = '</div>';

                if ($orderByArr[0]) {
                    $orderBy[] = '<div class="row row-cols-auto align-items-center mt-2">';
                    $orderBy[] =     '<div class="col">';
                    $orderBy[] =         '<div class="input-group">';
                    $orderBy[] =             $this->mkTypeSelect('SET[queryOrder2]', $orderByArr[1] ?? '', '');
                    $orderBy[] =         '</div>';
                    $orderBy[] =     '</div>';
                    $orderBy[] =     '<div class="col mt-2">';
                    $orderBy[] =         '<div class="form-check">';
                    $orderBy[] =             BackendUtility::getFuncCheck(0, 'SET[queryOrder2Desc]', $modSettings['queryOrder2Desc'] ?? false, '', '', 'id="checkQueryOrder2Desc"');
                    $orderBy[] =             '<label class="form-check-label" for="checkQueryOrder2Desc">Descending</label>';
                    $orderBy[] =         '</div>';
                    $orderBy[] =     '</div>';
                    $orderBy[] = '</div>';
                }
                $out[] = '<div class="form-group">';
                $out[] = '	<label>Order By:</label>';
                $out[] =     implode(LF, $orderBy);
                $out[] = '</div>';
            }
            if (in_array('limit', $enableArr) && !($userTsConfig['mod.']['dbint.']['disableLimit'] ?? false)) {
                $limit = [];
                $limit[] = '<div class="input-group">';
                $limit[] = '	<span class="input-group-btn">';
                $limit[] = $this->updateIcon();
                $limit[] = '	</span>';
                $limit[] = '	<input type="text" class="form-control" value="' . htmlspecialchars($this->extFieldLists['queryLimit']) . '" name="SET[queryLimit]" id="queryLimit">';
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
                $out[] = '	<label>Limit:</label>';
                $out[] = '	<div class="row row-cols-auto">';
                $out[] = '   <div class="col">';
                $out[] =        implode(LF, $limit);
                $out[] = '   </div>';
                $out[] = '   <div class="col">';
                $out[] = '		<div class="btn-group t3js-limit-submit">';
                $out[] =            $prevButton;
                $out[] =            $nextButton;
                $out[] = '		</div>';
                $out[] = '   </div>';
                $out[] = '   <div class="col">';
                $out[] = '		<div class="btn-group t3js-limit-submit">';
                $out[] = '			<input type="button" class="btn btn-default" data-value="10" value="10">';
                $out[] = '			<input type="button" class="btn btn-default" data-value="20" value="20">';
                $out[] = '			<input type="button" class="btn btn-default" data-value="50" value="50">';
                $out[] = '			<input type="button" class="btn btn-default" data-value="100" value="100">';
                $out[] = '		</div>';
                $out[] = '   </div>';
                $out[] = '	</div>';
                $out[] = '</div>';
            }
        }
        return implode(LF, $out);
    }

    /**
     * Get select query
     *
     * @param string $qString
     * @return string
     */
    protected function getSelectQuery($qString = ''): string
    {
        $backendUserAuthentication = $this->getBackendUserAuthentication();
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);
        $queryBuilder->getRestrictions()->removeAll();
        if (empty($this->settings['show_deleted'])) {
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
        if ($this->extFieldLists['queryLimit']) {
            // Explode queryLimit to fetch the limit and a possible offset
            $parts = GeneralUtility::intExplode(',', $this->extFieldLists['queryLimit']);
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
     * @param string $name the field name
     * @param string $timestamp ISO-8601 timestamp
     * @param string $type [datetime, date, time, timesec, year]
     *
     * @return string
     */
    protected function getDateTimePickerField($name, $timestamp, $type)
    {
        $value = strtotime($timestamp) ? date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], (int)strtotime($timestamp)) : '';
        $id = StringUtility::getUniqueId('dt_');
        $html = [];
        $html[] = '<div class="col mb-sm-2">';
        $html[] = '  <div class="input-group" id="' . $id . '-wrapper">';
        $html[] = '	   <input data-formengine-input-name="' . htmlspecialchars($name) . '" value="' . $value . '" class="form-control t3js-datetimepicker t3js-clearable" data-date-type="' . htmlspecialchars($type) . '" type="text" id="' . $id . '">';
        $html[] = '	   <input name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($timestamp) . '" type="hidden">';
        $html[] = '	   <span class="input-group-btn">';
        $html[] = '	     <label class="btn btn-default" for="' . $id . '">';
        $html[] = '		   <span class="fa fa-calendar"></span>';
        $html[] = '		 </label>';
        $html[] = '    </span>';
        $html[] = '  </div>';
        $html[] = '</div>';
        return implode(LF, $html);
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
