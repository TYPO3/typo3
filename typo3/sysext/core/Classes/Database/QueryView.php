<?php
namespace TYPO3\CMS\Core\Database;

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

use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
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

/**
 * Class used in module tools/dbint (advanced search) and which may hold code specific for that module
 * However the class has a general principle in it which may be used in the web/export module.
 */
class QueryView
{
    /**
     * @var string
     */
    public $storeList = 'search_query_smallparts,search_result_labels,labels_noprefix,show_deleted,queryConfig,queryTable,queryFields,queryLimit,queryOrder,queryOrderDesc,queryOrder2,queryOrder2Desc,queryGroup,search_query_makeQuery';

    /**
     * @var string
     */
    public $downloadScript = 'index.php';

    /**
     * @var int
     */
    public $formW = 48;

    /**
     * @var int
     */
    public $noDownloadB = 0;

    /**
     * @var array
     */
    public $hookArray = [];

    /**
     * @var string
     */
    protected $formName = '';

    /**
     * @var \TYPO3\CMS\Core\Imaging\IconFactory
     */
    protected $iconFactory;

    /**
     * @var array
     */
    protected $tableArray = [];

    /**
     * @var LanguageService
     */
    protected $languageService;

    /**
     * @var BackendUserAuthentication
     */
    protected $backendUserAuthentication;

    /**
     * Settings, usually from the controller (previously known from $GLOBALS['SOBE']->MOD_SETTINGS
     * @var array
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
     * @param array $settings previously stored in $GLOBALS['SOBE']->MOD_SETTINGS
     * @param array $menuItems previously stored in $GLOBALS['SOBE']->MOD_MENU
     * @param string $moduleName previously stored in $GLOBALS['SOBE']->moduleName
     */
    public function __construct(array $settings = null, $menuItems = null, $moduleName = null)
    {
        $this->backendUserAuthentication = $GLOBALS['BE_USER'];
        $this->languageService = $GLOBALS['LANG'];
        $this->languageService->includeLLFile('EXT:core/Resources/Private/Language/locallang_t3lib_fullsearch.xlf');
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->settings = $settings ?: $GLOBALS['SOBE']->MOD_SETTINGS;
        $this->menuItems = $menuItems ?: $GLOBALS['SOBE']->MOD_MENU;
        $this->moduleName = $moduleName ?: $GLOBALS['SOBE']->moduleName;
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
            . htmlspecialchars($this->settings['sword']) . '">';
        $markup[] = '</div>';
        $markup[] = '<div class="form-group">';
        $markup[] = '<input class="btn btn-default" type="submit" name="submit" value="Search All Records">';
        $markup[] = '</div>';
        return implode(LF, $markup);
    }

    /**
     * Make store control
     *
     * @return string
     */
    public function makeStoreControl()
    {
        // Load/Save
        $storeArray = $this->initStoreArray();

        $opt = [];
        foreach ($storeArray as $k => $v) {
            $opt[] = '<option value="' . htmlspecialchars($k) . '">' . htmlspecialchars($v) . '</option>';
        }
        // Actions:
        if (ExtensionManagementUtility::isLoaded('sys_action') && $this->backendUserAuthentication->isAdmin()) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_action');
            $queryBuilder->getRestrictions()->removeAll();
            $statement = $queryBuilder->select('uid', 'title')
                ->from('sys_action')
                ->where($queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(2, \PDO::PARAM_INT)))
                ->orderBy('title')
                ->execute();
            $opt[] = '<option value="0">__Save to Action:__</option>';
            while ($row = $statement->fetch()) {
                $opt[] = '<option value="-' . (int)$row['uid'] . '">' . htmlspecialchars($row['title']
                        . ' [' . (int)$row['uid'] . ']') . '</option>';
            }
        }
        $markup = [];
        $markup[] = '<div class="load-queries">';
        $markup[] = '  <div class="form-group form-inline">';
        $markup[] = '    <div class="form-group">';
        $markup[] = '      <select class="form-control" name="storeControl[STORE]" onChange="document.forms[0]'
            . '[\'storeControl[title]\'].value= this.options[this.selectedIndex].value!=0 '
            . '? this.options[this.selectedIndex].text : \'\';">' . implode(LF, $opt) . '</select>';
        $markup[] = '      <input class="form-control" name="storeControl[title]" value="" type="text" max="80">';
        $markup[] = '      <input class="btn btn-default" type="submit" name="storeControl[LOAD]" value="Load">';
        $markup[] = '      <input class="btn btn-default" type="submit" name="storeControl[SAVE]" value="Save">';
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
    public function initStoreArray()
    {
        $storeArray = [
            '0' => '[New]'
        ];
        $savedStoreArray = unserialize($this->settings['storeArray'], ['allowed_classes' => false]);
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
    public function cleanStoreQueryConfigs($storeQueryConfigs, $storeArray)
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
    public function addToStoreQueryConfigs($storeQueryConfigs, $index)
    {
        $keyArr = explode(',', $this->storeList);
        $storeQueryConfigs[$index] = [];
        foreach ($keyArr as $k) {
            $storeQueryConfigs[$index][$k] = $this->settings[$k];
        }
        return $storeQueryConfigs;
    }

    /**
     * Save query in action
     *
     * @param int $uid
     * @return int
     */
    public function saveQueryInAction($uid)
    {
        if (ExtensionManagementUtility::isLoaded('sys_action')) {
            $keyArr = explode(',', $this->storeList);
            $saveArr = [];
            foreach ($keyArr as $k) {
                $saveArr[$k] = $this->settings[$k];
            }
            // Show query
            if ($saveArr['queryTable']) {
                /** @var $queryGenerator \TYPO3\CMS\Core\Database\QueryGenerator */
                $queryGenerator = GeneralUtility::makeInstance(QueryGenerator::class);
                $queryGenerator->init('queryConfig', $saveArr['queryTable']);
                $queryGenerator->makeSelectorTable($saveArr);
                $queryGenerator->enablePrefix = 1;
                $queryString = $queryGenerator->getQuery($queryGenerator->queryConfig);

                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($queryGenerator->table);
                $queryBuilder->getRestrictions()->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                $rowCount = $queryBuilder->count('*')
                    ->from($queryGenerator->table)
                    ->where(QueryHelper::stripLogicalOperatorPrefix($queryString))
                    ->execute()->fetchColumn(0);

                $t2DataValue = [
                    'qC' => $saveArr,
                    'qCount' => $rowCount,
                    'qSelect' => $queryGenerator->getSelectQuery($queryString),
                    'qString' => $queryString
                ];
                GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_action')
                    ->update(
                        'sys_action',
                        ['t2_data' => serialize($t2DataValue)],
                        ['uid' => (int)$uid],
                        ['t2_data' => Connection::PARAM_LOB]
                    );
            }
            return 1;
        }
        return null;
    }

    /**
     * Load store query configs
     *
     * @param array $storeQueryConfigs
     * @param int $storeIndex
     * @param array $writeArray
     * @return array
     */
    public function loadStoreQueryConfigs($storeQueryConfigs, $storeIndex, $writeArray)
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
    public function procesStoreControl()
    {
        $storeArray = $this->initStoreArray();
        $storeQueryConfigs = unserialize($this->settings['storeQueryConfigs'], ['allowed_classes' => false]);
        $storeControl = GeneralUtility::_GP('storeControl');
        $storeIndex = (int)$storeControl['STORE'];
        $saveStoreArray = 0;
        $writeArray = [];
        $msg = '';
        if (is_array($storeControl)) {
            if ($storeControl['LOAD']) {
                if ($storeIndex > 0) {
                    $writeArray = $this->loadStoreQueryConfigs($storeQueryConfigs, $storeIndex, $writeArray);
                    $saveStoreArray = 1;
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        sprintf($this->languageService->getLL('query_loaded'), $storeArray[$storeIndex])
                    );
                } elseif ($storeIndex < 0 && ExtensionManagementUtility::isLoaded('sys_action')) {
                    $actionRecord = BackendUtility::getRecord('sys_action', abs($storeIndex));
                    if (is_array($actionRecord)) {
                        $dA = unserialize($actionRecord['t2_data'], ['allowed_classes' => false]);
                        $dbSC = [];
                        if (is_array($dA['qC'])) {
                            $dbSC[0] = $dA['qC'];
                        }
                        $writeArray = $this->loadStoreQueryConfigs($dbSC, '0', $writeArray);
                        $saveStoreArray = 1;
                        $flashMessage = GeneralUtility::makeInstance(
                            FlashMessage::class,
                            sprintf($this->languageService->getLL('query_from_action_loaded'), $actionRecord['title'])
                        );
                    }
                }
            } elseif ($storeControl['SAVE']) {
                if ($storeIndex < 0) {
                    $qOK = $this->saveQueryInAction(abs($storeIndex));
                    if ($qOK) {
                        $flashMessage = GeneralUtility::makeInstance(
                            FlashMessage::class,
                            $this->languageService->getLL('query_saved')
                        );
                    } else {
                        $flashMessage = GeneralUtility::makeInstance(
                            FlashMessage::class,
                            $this->languageService->getLL('query_notsaved'),
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
                        $storeQueryConfigs = $this->addToStoreQueryConfigs($storeQueryConfigs, $storeIndex);
                        $saveStoreArray = 1;
                        $flashMessage = GeneralUtility::makeInstance(
                            FlashMessage::class,
                            $this->languageService->getLL('query_saved')
                        );
                    }
                }
            } elseif ($storeControl['REMOVE']) {
                if ($storeIndex > 0) {
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        sprintf($this->languageService->getLL('query_removed'), $storeArray[$storeControl['STORE']])
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
     * Query marker
     *
     * @return string
     */
    public function queryMaker()
    {
        $output = '';
        $this->hookArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3lib_fullsearch'] ?? [];
        $msg = $this->procesStoreControl();
        $userTsConfig = $this->backendUserAuthentication->getTSConfig();
        if (!$userTsConfig['mod.']['dbint.']['disableStoreControl']) {
            $output .= '<h2>Load/Save Query</h2>';
            $output .= '<div>' . $this->makeStoreControl() . '</div>';
            $output .= $msg;
        }
        // Query Maker:
        $queryGenerator = GeneralUtility::makeInstance(QueryGenerator::class);
        $queryGenerator->init('queryConfig', $this->settings['queryTable']);
        if ($this->formName) {
            $queryGenerator->setFormName($this->formName);
        }
        $tmpCode = $queryGenerator->makeSelectorTable($this->settings);
        $output .= '<div id="query"></div><h2>Make query</h2><div>' . $tmpCode . '</div>';
        $mQ = $this->settings['search_query_makeQuery'];
        // Make form elements:
        if ($queryGenerator->table && is_array($GLOBALS['TCA'][$queryGenerator->table])) {
            if ($mQ) {
                // Show query
                $queryGenerator->enablePrefix = 1;
                $queryString = $queryGenerator->getQuery($queryGenerator->queryConfig);
                $selectQueryString = $queryGenerator->getSelectQuery($queryString);
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($queryGenerator->table);

                $isConnectionMysql = strpos($connection->getServerVersion(), 'MySQL') === 0;
                $fullQueryString = '';
                try {
                    if ($mQ === 'explain' && $isConnectionMysql) {
                        // EXPLAIN is no ANSI SQL, for now this is only executed on mysql
                        // @todo: Move away from getSelectQuery() or model differently
                        $fullQueryString = 'EXPLAIN ' . $selectQueryString;
                        $dataRows = $connection->executeQuery('EXPLAIN ' . $selectQueryString)->fetchAll();
                    } elseif ($mQ === 'count') {
                        $queryBuilder = $connection->createQueryBuilder();
                        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                        $queryBuilder->count('*')
                            ->from($queryGenerator->table)
                            ->where(QueryHelper::stripLogicalOperatorPrefix($queryString));
                        $fullQueryString = $queryBuilder->getSQL();
                        $dataRows = [$queryBuilder->execute()->fetchColumn(0)];
                    } else {
                        $fullQueryString = $selectQueryString;
                        $dataRows = $connection->executeQuery($selectQueryString)->fetchAll();
                    }
                    if (!$userTsConfig['mod.']['dbint.']['disableShowSQLQuery']) {
                        $output .= '<h2>SQL query</h2><div><pre>' . htmlspecialchars($fullQueryString) . '</pre></div>';
                    }
                    $cPR = $this->getQueryResultCode($mQ, $dataRows, $queryGenerator->table);
                    $output .= '<h2>' . $cPR['header'] . '</h2><div>' . $cPR['content'] . '</div>';
                } catch (DBALException $e) {
                    if (!$userTsConfig['mod.']['dbint.']['disableShowSQLQuery']) {
                        $output .= '<h2>SQL query</h2><div><pre>' . htmlspecialchars($fullQueryString) . '</pre></div>';
                    }
                    $out = '<p><strong>Error: <span class="text-danger">'
                        . htmlspecialchars($e->getMessage())
                        . '</span></strong></p>';
                    $output .= '<h2>SQL error</h2><div>' . $out . '</div>';
                }
            }
        }
        return '<div class="query-builder">' . $output . '</div>';
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
    public function getQueryResultCode($type, array $dataRows, $table)
    {
        $out = '';
        $cPR = [];
        switch ($type) {
            case 'count':
                $cPR['header'] = 'Count';
                $cPR['content'] = '<BR><strong>' . (int)$dataRows[0] . '</strong> records selected.';
                break;
            case 'all':
                $rowArr = [];
                $dataRow = null;
                foreach ($dataRows as $dataRow) {
                    $rowArr[] = $this->resultRowDisplay($dataRow, $GLOBALS['TCA'][$table], $table);
                }
                if (is_array($this->hookArray['beforeResultTable'])) {
                    foreach ($this->hookArray['beforeResultTable'] as $_funcRef) {
                        $out .= GeneralUtility::callUserFunction($_funcRef, $this->settings, $this);
                    }
                }
                if (!empty($rowArr)) {
                    $cPR['header'] = 'Result';
                    $out .= '<table class="table table-striped table-hover">'
                        . $this->resultRowTitles($dataRow, $GLOBALS['TCA'][$table]) . implode(LF, $rowArr)
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
                        $rowArr[] = $this->csvValues(array_keys($dataRow), ',', '');
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
                            . 'value="Click to download file" onClick="window.location.href=' . htmlspecialchars(GeneralUtility::quoteJSvalue($this->downloadScript))
                            . ';">';
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
    public function csvValues($row, $delim = ',', $quote = '"', $conf = [], $table = '')
    {
        $valueArray = $row;
        if ($this->settings['search_result_labels'] && $table) {
            foreach ($valueArray as $key => $val) {
                $valueArray[$key] = $this->getProcessedValueExtra($table, $key, $val, $conf, ';');
            }
        }
        return CsvUtility::csvValues($valueArray, $delim, $quote);
    }

    /**
     * Search
     *
     * @return string
     */
    public function search()
    {
        $swords = $this->settings['sword'];
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
                $tableColumns = $connection->getSchemaManager()->listTableColumns($table);
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
                $count = $queryBuilder->orWhere(...$likes)->execute()->fetchColumn(0);

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
                    $statement = $queryBuilder->orWhere(...$likes)->execute();
                    $lastRow = null;
                    $rowArr = [];
                    while ($row = $statement->fetch()) {
                        $rowArr[] = $this->resultRowDisplay($row, $conf, $table);
                        $lastRow = $row;
                    }
                    $markup = [];
                    $markup[] = '<div class="panel panel-default">';
                    $markup[] = '  <div class="panel-heading">';
                    $markup[] = htmlspecialchars($this->languageService->sL($conf['ctrl']['title'])) . ' (' . $count . ')';
                    $markup[] = '  </div>';
                    $markup[] = '  <table class="table table-striped table-hover">';
                    $markup[] = $this->resultRowTitles($lastRow, $conf);
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
     * Result row display
     *
     * @param array $row
     * @param array $conf
     * @param string $table
     * @return string
     */
    public function resultRowDisplay($row, $conf, $table)
    {
        $out = '<tr>';
        foreach ($row as $fieldName => $fieldValue) {
            if (GeneralUtility::inList($this->settings['queryFields'], $fieldName)
                || !$this->settings['queryFields']
                && $fieldName !== 'pid'
                && $fieldName !== 'deleted'
            ) {
                if ($this->settings['search_result_labels']) {
                    $fVnew = $this->getProcessedValueExtra($table, $fieldName, $fieldValue, $conf, '<br />');
                } else {
                    $fVnew = htmlspecialchars($fieldValue);
                }
                $out .= '<td>' . $fVnew . '</td>';
            }
        }
        $out .= '<td>';
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);

        if (!$row['deleted']) {
            $out .= '<div class="btn-group" role="group">';
            $url = (string)$uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    $table => [
                        $row['uid'] => 'edit'
                    ]
                ],
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                    . HttpUtility::buildQueryString(['SET' => (array)GeneralUtility::_POST('SET')], '&')
            ]);
            $out .= '<a class="btn btn-default" href="' . htmlspecialchars($url) . '">'
                . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render() . '</a>';
            $out .= '</div><div class="btn-group" role="group">';
            $out .= '<a class="btn btn-default" href="#" onClick="top.TYPO3.InfoWindow.showItem(\'' . $table . '\',' . $row['uid']
                . ');return false;">' . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render()
                . '</a>';
            $out .= '</div>';
        } else {
            $out .= '<div class="btn-group" role="group">';
            $out .= '<a class="btn btn-default" href="' . htmlspecialchars((string)$uriBuilder->buildUriFromRoute('tce_db', [
                        'cmd' => [
                            $table => [
                                $row['uid'] => [
                                    'undelete' => 1
                                ]
                            ]
                        ],
                        'redirect' => GeneralUtility::linkThisScript()
                    ])) . '" title="' . htmlspecialchars($this->languageService->getLL('undelete_only')) . '">';
            $out .= $this->iconFactory->getIcon('actions-edit-restore', Icon::SIZE_SMALL)->render() . '</a>';
            $formEngineParameters = [
                'edit' => [
                    $table => [
                        $row['uid'] => 'edit'
                    ]
                ],
                'returnUrl' => GeneralUtility::linkThisScript()
            ];
            $redirectUrl = (string)$uriBuilder->buildUriFromRoute('record_edit', $formEngineParameters);
            $out .= '<a class="btn btn-default" href="' . htmlspecialchars((string)$uriBuilder->buildUriFromRoute('tce_db', [
                    'cmd' => [
                        $table => [
                            $row['uid'] => [
                                'undelete' => 1
                            ]
                        ]
                    ],
                    'redirect' => $redirectUrl
                ])) . '" title="' . htmlspecialchars($this->languageService->getLL('undelete_and_edit')) . '">';
            $out .= $this->iconFactory->getIcon('actions-edit-restore-edit', Icon::SIZE_SMALL)->render() . '</a>';
            $out .= '</div>';
        }
        $_params = [$table => $row];
        if (is_array($this->hookArray['additionalButtons'])) {
            foreach ($this->hookArray['additionalButtons'] as $_funcRef) {
                $out .= GeneralUtility::callUserFunction($_funcRef, $_params, $this);
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
    public function getProcessedValueExtra($table, $fieldName, $fieldValue, $conf, $splitString)
    {
        $out = '';
        $fields = [];
        // Analysing the fields in the table.
        if (is_array($GLOBALS['TCA'][$table])) {
            $fC = $GLOBALS['TCA'][$table]['columns'][$fieldName];
            $fields = $fC['config'];
            $fields['exclude'] = $fC['exclude'];
            if (is_array($fC) && $fC['label']) {
                $fields['label'] = preg_replace('/:$/', '', trim($this->languageService->sL($fC['label'])));
                switch ($fields['type']) {
                    case 'input':
                        if (preg_match('/int|year/i', $fields['eval'])) {
                            $fields['type'] = 'number';
                        } elseif (preg_match('/time/i', $fields['eval'])) {
                            $fields['type'] = 'time';
                        } elseif (preg_match('/date/i', $fields['eval'])) {
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
                        $fields['type'] = 'multiple';
                        if ($fields['foreign_table']) {
                            $fields['type'] = 'relation';
                        }
                        if ($fields['special']) {
                            $fields['type'] = 'text';
                        }
                        break;
                    case 'group':
                        $fields['type'] = 'files';
                        if ($fields['internal_type'] === 'db') {
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
                    $out = strftime('%d-%m-%Y', $fieldValue);
                }
                break;
            case 'time':
                if ($fieldValue != -1) {
                    if ($splitString === '<br />') {
                        $out = strftime('%H:%M' . $splitString . '%d-%m-%Y', $fieldValue);
                    } else {
                        $out = strftime('%H:%M %d-%m-%Y', $fieldValue);
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
            case 'files':
            default:
                $out = htmlspecialchars($fieldValue);
        }
        return $out;
    }

    /**
     * Get tree list
     *
     * @param int $id
     * @param int $depth
     * @param int $begin
     * @param string $permsClause
     *
     * @return string
     */
    public function getTreeList($id, $depth, $begin = 0, $permsClause = null)
    {
        $depth = (int)$depth;
        $begin = (int)$begin;
        $id = (int)$id;
        if ($id < 0) {
            $id = abs($id);
        }
        if ($begin == 0) {
            $theList = $id;
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
                    $queryBuilder->expr()->eq('sys_language_uid', 0),
                    QueryHelper::stripLogicalOperatorPrefix($permsClause)
                )
                ->execute();
            while ($row = $statement->fetch()) {
                if ($begin <= 0) {
                    $theList .= ',' . $row['uid'];
                }
                if ($depth > 1) {
                    $theList .= $this->getTreeList($row['uid'], $depth - 1, $begin - 1, $permsClause);
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
    public function makeValueList($fieldName, $fieldValue, $conf, $table, $splitString)
    {
        $fieldSetup = $conf;
        $out = '';
        if ($fieldSetup['type'] === 'files') {
            $d = dir(Environment::getPublicPath() . '/' . $fieldSetup['uploadfolder']);
            while (false !== ($entry = $d->read())) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $fileArray[] = $entry;
            }
            $d->close();
            natcasesort($fileArray);
            foreach ($fileArray as $fileName) {
                if (GeneralUtility::inList($fieldValue, $fileName) || $fieldValue == $fileName) {
                    if ($out !== '') {
                        $out .= $splitString;
                    }
                    $out .= htmlspecialchars($fileName);
                }
            }
        }
        if ($fieldSetup['type'] === 'multiple') {
            foreach ($fieldSetup['items'] as $key => $val) {
                if (strpos($val[0], 'LLL:') === 0) {
                    $value = $this->languageService->sL($val[0]);
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
                    $value = $this->languageService->sL($val[0]);
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
            if ($fieldSetup['items']) {
                foreach ($fieldSetup['items'] as $key => $val) {
                    if (strpos($val[0], 'LLL:') === 0) {
                        $value = $this->languageService->sL($val[0]);
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
            }
            if (stristr($fieldSetup['allowed'], ',')) {
                $from_table_Arr = explode(',', $fieldSetup['allowed']);
                $useTablePrefix = 1;
                if (!$fieldSetup['prepend_tname']) {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                    $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                    $statement = $queryBuilder->select($fieldName)->from($table)->execute();
                    while ($row = $statement->fetch()) {
                        if (stristr($row[$fieldName], ',')) {
                            $checkContent = explode(',', $row[$fieldName]);
                            foreach ($checkContent as $singleValue) {
                                if (!stristr($singleValue, '_')) {
                                    $dontPrefixFirstTable = 1;
                                }
                            }
                        } else {
                            $singleValue = $row[$fieldName];
                            if ($singleValue !== '' && !stristr($singleValue, '_')) {
                                $dontPrefixFirstTable = 1;
                            }
                        }
                    }
                }
            } else {
                $from_table_Arr[0] = $fieldSetup['allowed'];
            }
            if ($fieldSetup['prepend_tname']) {
                $useTablePrefix = 1;
            }
            if ($fieldSetup['foreign_table']) {
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
                if (is_array($GLOBALS['TCA'][$from_table])) {
                    $labelField = $GLOBALS['TCA'][$from_table]['ctrl']['label'];
                    $altLabelField = $GLOBALS['TCA'][$from_table]['ctrl']['label_alt'];
                    if ($GLOBALS['TCA'][$from_table]['columns'][$labelField]['config']['items']) {
                        $items = $GLOBALS['TCA'][$from_table]['columns'][$labelField]['config']['items'];
                        foreach ($items as $labelArray) {
                            if (strpos($labelArray[0], 'LLL:') === 0) {
                                $labelFieldSelect[$labelArray[1]] = $this->languageService->sL($labelArray[0]);
                            } else {
                                $labelFieldSelect[$labelArray[1]] = $labelArray[0];
                            }
                        }
                        $useSelectLabels = 1;
                    }
                    $altLabelFieldSelect = [];
                    if ($GLOBALS['TCA'][$from_table]['columns'][$altLabelField]['config']['items']) {
                        $items = $GLOBALS['TCA'][$from_table]['columns'][$altLabelField]['config']['items'];
                        foreach ($items as $altLabelArray) {
                            if (strpos($altLabelArray[0], 'LLL:') === 0) {
                                $altLabelFieldSelect[$altLabelArray[1]] = $this->languageService->sL($altLabelArray[0]);
                            } else {
                                $altLabelFieldSelect[$altLabelArray[1]] = $altLabelArray[0];
                            }
                        }
                        $useAltSelectLabels = 1;
                    }

                    if (!$this->tableArray[$from_table]) {
                        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($from_table);
                        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                        $selectFields = ['uid', $labelField];
                        if ($altLabelField) {
                            $selectFields[] = $altLabelField;
                        }
                        $queryBuilder->select(...$selectFields)
                            ->from($from_table)
                            ->orderBy('uid');
                        if (!$this->backendUserAuthentication->isAdmin() && $GLOBALS['TYPO3_CONF_VARS']['BE']['lockBeUserToDBmounts']) {
                            $webMounts = $this->backendUserAuthentication->returnWebmounts();
                            $perms_clause = $this->backendUserAuthentication->getPagePermsClause(Permission::PAGE_SHOW);
                            $webMountPageTree = '';
                            $webMountPageTreePrefix = '';
                            foreach ($webMounts as $webMount) {
                                if ($webMountPageTree) {
                                    $webMountPageTreePrefix = ',';
                                }
                                $webMountPageTree .= $webMountPageTreePrefix
                                    . $this->getTreeList($webMount, 999, $begin = 0, $perms_clause);
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
                        $statement = $queryBuilder->execute();
                        $this->tableArray[$from_table] = [];
                        while ($row = $statement->fetch()) {
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
    public function resultRowTitles($row, $conf)
    {
        $tableHeader = [];
        // Start header row
        $tableHeader[] = '<thead><tr>';
        // Iterate over given columns
        foreach ($row as $fieldName => $fieldValue) {
            if (GeneralUtility::inList($this->settings['queryFields'], $fieldName)
                || !$this->settings['queryFields']
                && $fieldName !== 'pid'
                && $fieldName !== 'deleted'
            ) {
                if ($this->settings['search_result_labels']) {
                    $title = $this->languageService->sL($conf['columns'][$fieldName]['label']
                        ? $conf['columns'][$fieldName]['label']
                        : $fieldName);
                } else {
                    $title = $this->languageService->sL($fieldName);
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
     * CSV row titles
     *
     * @param array $row
     * @param array $conf
     * @return string
     * @todo Unused?
     */
    public function csvRowTitles($row, $conf)
    {
        $out = '';
        foreach ($row as $fieldName => $fieldValue) {
            if (GeneralUtility::inList($this->settings['queryFields'], $fieldName)
                || !$this->settings['queryFields'] && $fieldName !== 'pid') {
                if ($out !== '') {
                    $out .= ',';
                }
                if ($this->settings['search_result_labels']) {
                    $out .= htmlspecialchars(
                        $this->languageService->sL(
                            $conf['columns'][$fieldName]['label']
                            ? $conf['columns'][$fieldName]['label']
                            : $fieldName
                        )
                    );
                } else {
                    $out .= htmlspecialchars($this->languageService->sL($fieldName));
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
}
