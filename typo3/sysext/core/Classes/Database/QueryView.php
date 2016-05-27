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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

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
    public $hookArray = array();

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
     * @var DatabaseConnection
     */
    protected $databaseConnection;

    /**
     * @var LanguageService
     */
    protected $languageService;

    /**
     * @var BackendUserAuthentication
     */
    protected $backendUserAuthentication;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->backendUserAuthentication = $GLOBALS['BE_USER'];
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];
        $this->languageService = $GLOBALS['LANG'];
        $this->languageService->includeLLFile('EXT:lang/locallang_t3lib_fullsearch.xlf');
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
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
            . htmlspecialchars($GLOBALS['SOBE']->MOD_SETTINGS['sword']) . '">';
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

        $opt = array();
        foreach ($storeArray as $k => $v) {
            $opt[] = '<option value="' . $k . '">' . htmlspecialchars($v) . '</option>';
        }
        // Actions:
        if (ExtensionManagementUtility::isLoaded('sys_action') && $this->backendUserAuthentication->isAdmin()) {
            $rows = $this->databaseConnection->exec_SELECTgetRows('*', 'sys_action', 'type=2', '', 'title');
            $opt[] = '<option value="0">__Save to Action:__</option>';
            foreach ($rows as $row) {
                $opt[] = '<option value="-' . (int)$row['uid'] . '">' . htmlspecialchars(($row['title']
                        . ' [' . (int)$row['uid'] . ']')) . '</option>';
            }
        }
        $markup = [];
        $markup[] = '<div class="load-queries">';
        $markup[] = '  <div class="form-inline">';
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
        $storeArray = array(
            '0' => '[New]'
        );
        $savedStoreArray = unserialize($GLOBALS['SOBE']->MOD_SETTINGS['storeArray']);
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
        $storeQueryConfigs[$index] = array();
        foreach ($keyArr as $k) {
            $storeQueryConfigs[$index][$k] = $GLOBALS['SOBE']->MOD_SETTINGS[$k];
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
            $saveArr = array();
            foreach ($keyArr as $k) {
                $saveArr[$k] = $GLOBALS['SOBE']->MOD_SETTINGS[$k];
            }
            $qOK = 0;
            // Show query
            if ($saveArr['queryTable']) {
                /** @var \TYPO3\CMS\Core\Database\QueryGenerator */
                $qGen = GeneralUtility::makeInstance(QueryGenerator::class);
                $qGen->init('queryConfig', $saveArr['queryTable']);
                $qGen->makeSelectorTable($saveArr);
                $qGen->enablePrefix = 1;
                $qString = $qGen->getQuery($qGen->queryConfig);
                $qCount = $this->databaseConnection->SELECTquery('count(*)', $qGen->table, $qString
                    . BackendUtility::deleteClause($qGen->table));
                $qSelect = $qGen->getSelectQuery($qString);
                $res = @$this->databaseConnection->sql_query($qCount);
                if (!$this->databaseConnection->sql_error()) {
                    $this->databaseConnection->sql_free_result($res);
                    $dA = array();
                    $dA['t2_data'] = serialize(array(
                        'qC' => $saveArr,
                        'qCount' => $qCount,
                        'qSelect' => $qSelect,
                        'qString' => $qString
                    ));
                    $this->databaseConnection->exec_UPDATEquery('sys_action', 'uid=' . (int)$uid, $dA);
                    $qOK = 1;
                }
            }
            return $qOK;
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
        $storeQueryConfigs = unserialize($GLOBALS['SOBE']->MOD_SETTINGS['storeQueryConfigs']);
        $storeControl = GeneralUtility::_GP('storeControl');
        $storeIndex = (int)$storeControl['STORE'];
        $saveStoreArray = 0;
        $writeArray = array();
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
                        $dA = unserialize($actionRecord['t2_data']);
                        $dbSC = array();
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
                $msg = $flashMessage->getMessageAsMarkup();
            }
        }
        if ($saveStoreArray) {
            // Making sure, index 0 is not set!
            unset($storeArray[0]);
            $writeArray['storeArray'] = serialize($storeArray);
            $writeArray['storeQueryConfigs'] =
                serialize($this->cleanStoreQueryConfigs($storeQueryConfigs, $storeArray));
            $GLOBALS['SOBE']->MOD_SETTINGS = BackendUtility::getModuleData(
                $GLOBALS['SOBE']->MOD_MENU,
                $writeArray,
                $GLOBALS['SOBE']->MCONF['name'],
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
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3lib_fullsearch'])) {
            $this->hookArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3lib_fullsearch'];
        }
        $msg = $this->procesStoreControl();
        if (!$this->backendUserAuthentication->userTS['mod.']['dbint.']['disableStoreControl']) {
            $output .= '<h2>Load/Save Query</h2>';
            $output .= '<div>' . $this->makeStoreControl() . '</div>';
            $output .= $msg;
        }
        // Query Maker:
        $qGen = GeneralUtility::makeInstance(QueryGenerator::class);
        $qGen->init('queryConfig', $GLOBALS['SOBE']->MOD_SETTINGS['queryTable']);
        if ($this->formName) {
            $qGen->setFormName($this->formName);
        }
        $tmpCode = $qGen->makeSelectorTable($GLOBALS['SOBE']->MOD_SETTINGS);
        $output .= '<div id="query"></div>' . '<h2>Make query</h2><div>' . $tmpCode . '</div>';
        $mQ = $GLOBALS['SOBE']->MOD_SETTINGS['search_query_makeQuery'];
        // Make form elements:
        if ($qGen->table && is_array($GLOBALS['TCA'][$qGen->table])) {
            if ($mQ) {
                // Show query
                $qGen->enablePrefix = 1;
                $qString = $qGen->getQuery($qGen->queryConfig);
                switch ($mQ) {
                    case 'count':
                        $qExplain = $this->databaseConnection->SELECTquery(
                            'count(*)',
                            $qGen->table,
                            $qString . BackendUtility::deleteClause($qGen->table)
                        );
                        break;
                    default:
                        $qExplain = $qGen->getSelectQuery($qString);
                        if ($mQ == 'explain') {
                            $qExplain = 'EXPLAIN ' . $qExplain;
                        }
                }
                if (!$this->backendUserAuthentication->userTS['mod.']['dbint.']['disableShowSQLQuery']) {
                    $output .= '<h2>SQL query</h2><div>' . $this->tableWrap(htmlspecialchars($qExplain)) . '</div>';
                }
                $res = @$this->databaseConnection->sql_query($qExplain);
                if ($this->databaseConnection->sql_error()) {
                    $out = '<p><strong>Error: <span class="text-danger">'
                        . $this->databaseConnection->sql_error()
                        . '</span></strong></p>';
                    $output .= '<h2>SQL error</h2><div>' . $out . '</div>';
                } else {
                    $cPR = $this->getQueryResultCode($mQ, $res, $qGen->table);
                    $this->databaseConnection->sql_free_result($res);
                    $output .= '<h2>' . $cPR['header'] . '</h2><div>' . $cPR['content'] . '</div>';
                }
            }
        }
        return '<div class="query-builder">' . $output . '</div>';
    }

    /**
     * Get query result code
     *
     * @param string $mQ
     * @param bool|\mysqli_result|object $res MySQLi result object / DBAL object
     * @param string $table
     * @return string
     */
    public function getQueryResultCode($mQ, $res, $table)
    {
        $out = '';
        $cPR = array();
        switch ($mQ) {
            case 'count':
                $row = $this->databaseConnection->sql_fetch_row($res);
                $cPR['header'] = 'Count';
                $cPR['content'] = '<BR><strong>' . $row[0] . '</strong> records selected.';
                break;
            case 'all':
                $rowArr = array();
                $lrow = null;
                while ($row = $this->databaseConnection->sql_fetch_assoc($res)) {
                    $rowArr[] = $this->resultRowDisplay($row, $GLOBALS['TCA'][$table], $table);
                    $lrow = $row;
                }
                if (is_array($this->hookArray['beforeResultTable'])) {
                    foreach ($this->hookArray['beforeResultTable'] as $_funcRef) {
                        $out .= GeneralUtility::callUserFunction($_funcRef, $GLOBALS['SOBE']->MOD_SETTINGS, $this);
                    }
                }
                if (!empty($rowArr)) {
                    $out .= '<table class="table table-striped table-hover">'
                        . $this->resultRowTitles($lrow, $GLOBALS['TCA'][$table], $table) . implode(LF, $rowArr)
                        . '</table>';
                }
                if (!$out) {
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        'No rows selected!',
                        '',
                        FlashMessage::INFO
                    );
                    $out = $flashMessage->getMessageAsMarkup();
                }
                $cPR['header'] = 'Result';
                $cPR['content'] = $out;
                break;
            case 'csv':
                $rowArr = array();
                $first = 1;
                while ($row = $this->databaseConnection->sql_fetch_assoc($res)) {
                    if ($first) {
                        $rowArr[] = $this->csvValues(array_keys($row), ',', '');
                        $first = 0;
                    }
                    $rowArr[] = $this->csvValues($row, ',', '"', $GLOBALS['TCA'][$table], $table);
                }
                if (!empty($rowArr)) {
                    $out .= '<textarea name="whatever" rows="20" class="text-monospace" style="width:100%">'
                        . htmlspecialchars(implode(LF, $rowArr))
                        . '</textarea>';
                    if (!$this->noDownloadB) {
                        $out .= '<br><input class="btn btn-default" type="submit" name="download_file" '
                            . 'value="Click to download file" onClick="window.location.href=\'' . $this->downloadScript
                            . '\';">';
                    }
                    // Downloads file:
                    if (GeneralUtility::_GP('download_file')) {
                        $filename = 'TYPO3_' . $table . '_export_' . date('dmy-Hi') . '.csv';
                        $mimeType = 'application/octet-stream';
                        header('Content-Type: ' . $mimeType);
                        header('Content-Disposition: attachment; filename=' . $filename);
                        echo implode(CRLF, $rowArr);
                        die;
                    }
                }
                if (!$out) {
                    $out = '<em>No rows selected!</em>';
                }
                $cPR['header'] = 'Result';
                $cPR['content'] = $out;
                break;
            case 'explain':
            default:
                while ($row = $this->databaseConnection->sql_fetch_assoc($res)) {
                    $out .= '<br />' . DebugUtility::viewArray($row);
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
    public function csvValues($row, $delim = ',', $quote = '"', $conf = array(), $table = '')
    {
        $valueArray = $row;
        if ($GLOBALS['SOBE']->MOD_SETTINGS['search_result_labels'] && $table) {
            foreach ($valueArray as $key => $val) {
                $valueArray[$key] = $this->getProcessedValueExtra($table, $key, $val, $conf, ';');
            }
        }
        return GeneralUtility::csvValues($valueArray, $delim, $quote);
    }

    /**
     * Table wrap
     *
     * @param string $str
     * @return string
     */
    public function tableWrap($str)
    {
        return '<pre>' . $str . '</pre>';
    }

    /**
     * Search
     *
     * @return string
     */
    public function search()
    {
        $SET = $GLOBALS['SOBE']->MOD_SETTINGS;
        $swords = $SET['sword'];
        $out = '';
        $limit = 200;
        if ($swords) {
            foreach ($GLOBALS['TCA'] as $table => $value) {
                // Get fields list
                $conf = $GLOBALS['TCA'][$table];
                // Avoid querying tables with no columns
                if (empty($conf['columns'])) {
                    continue;
                }
                $fieldsInDatabase = $this->databaseConnection->admin_get_fields($table);
                $list = array_intersect(array_keys($conf['columns']), array_keys($fieldsInDatabase));
                // Get query
                $qp = $this->databaseConnection->searchQuery(array($swords), $list, $table);
                // Count:
                $count = $this->databaseConnection->exec_SELECTcountRows(
                    '*',
                    $table,
                    $qp . BackendUtility::deleteClause($table)
                );
                if ($count) {
                    $rowArr = array();
                    $res = $this->databaseConnection->exec_SELECTquery(
                        'uid,' . $conf['ctrl']['label'],
                        $table,
                        $qp . BackendUtility::deleteClause($table),
                        '',
                        '',
                        $limit
                    );
                    $lrow = null;
                    while ($row = $this->databaseConnection->sql_fetch_assoc($res)) {
                        $rowArr[] = $this->resultRowDisplay($row, $conf, $table);
                        $lrow = $row;
                    }
                    $this->databaseConnection->sql_free_result($res);
                    $markup = [];
                    $markup[] = '<div class="panel panel-default">';
                    $markup[] = '  <div class="panel-heading">';
                    $markup[] = htmlspecialchars($this->languageService->sL($conf['ctrl']['title'])) . ' (' . $count . ')';
                    $markup[] = '  </div>';
                    $markup[] = '  <table class="table table-striped table-hover">';
                    $markup[] = $this->resultRowTitles($lrow, $conf, $table);
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
        $SET = $GLOBALS['SOBE']->MOD_SETTINGS;
        $out = '<tr>';
        foreach ($row as $fieldName => $fieldValue) {
            if (GeneralUtility::inList($SET['queryFields'], $fieldName)
                || !$SET['queryFields']
                && $fieldName != 'pid'
                && $fieldName != 'deleted'
            ) {
                if ($SET['search_result_labels']) {
                    $fVnew = $this->getProcessedValueExtra($table, $fieldName, $fieldValue, $conf, '<br />');
                } else {
                    $fVnew = htmlspecialchars($fieldValue);
                }
                $out .= '<td>' . $fVnew . '</td>';
            }
        }
        $out .= '<td><div class="btn-group">';
        if (!$row['deleted']) {
            $url = BackendUtility::getModuleUrl('record_edit', [
                'edit' => [
                    $table => [
                        $row['uid'] => 'edit'
                    ]
                ],
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
                    . GeneralUtility::implodeArrayForUrl('SET', (array)GeneralUtility::_POST('SET'))
            ]);
            $out .= '<a class="btn btn-default" href="#" onClick="top.launchView(\'' . $table . '\',' . $row['uid']
                . ');return false;">' . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render()
                . '</a>';
            $out .= '<a class="btn btn-default" href="' . htmlspecialchars($url) . '">'
                . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
        } else {
            $out .= '<a class="btn btn-default" href="' . htmlspecialchars(BackendUtility::getModuleUrl('tce_db', [
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
            $redirectUrl = BackendUtility::getModuleUrl('record_edit', $formEngineParameters);
            $out .= '<a class="btn btn-default" href="' . htmlspecialchars(BackendUtility::getModuleUrl('tce_db'), [
                    'cmd' => [
                        $table => [
                            $row['uid'] => [
                                'undelete' => 1
                            ]
                        ]
                    ],
                    'redirect' => $redirectUrl
                ]) . '" title="' . htmlspecialchars($this->languageService->getLL('undelete_and_edit')) . '">';
            $out .= $this->iconFactory->getIcon('actions-edit-restore-edit', Icon::SIZE_SMALL)->render() . '</a>';
        }
        $_params = array($table => $row);
        if (is_array($this->hookArray['additionalButtons'])) {
            foreach ($this->hookArray['additionalButtons'] as $_funcRef) {
                $out .= GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
        $out .= '</div></td></tr>';
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
                        if ($fields['internal_type'] == 'db') {
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
                    $out = strftime('%e-%m-%Y', $fieldValue);
                }
                break;
            case 'time':
                if ($fieldValue != -1) {
                    if ($splitString == '<br />') {
                        $out = strftime('%H:%M' . $splitString . '%e-%m-%Y', $fieldValue);
                    } else {
                        $out = strftime('%H:%M %e-%m-%Y', $fieldValue);
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
            $res = $this->databaseConnection->exec_SELECTquery(
                'uid',
                'pages',
                'pid=' . $id . ' ' . BackendUtility::deleteClause('pages') . ' AND ' .
                $permsClause
            );
            while ($row = $this->databaseConnection->sql_fetch_assoc($res)) {
                if ($begin <= 0) {
                    $theList .= ',' . $row['uid'];
                }
                if ($depth > 1) {
                    $theList .= $this->getTreeList($row['uid'], $depth - 1, $begin - 1, $permsClause);
                }
            }
            $this->databaseConnection->sql_free_result($res);
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
        if ($fieldSetup['type'] == 'files') {
            $d = dir(PATH_site . $fieldSetup['uploadfolder']);
            while (false !== ($entry = $d->read())) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                $fileArray[] = $entry;
            }
            $d->close();
            natcasesort($fileArray);
            foreach ($fileArray as $fileName) {
                if (GeneralUtility::inList($fieldValue, $fileName) || $fieldValue == $fileName) {
                    if (!$out) {
                        $out = htmlspecialchars($fileName);
                    } else {
                        $out .= $splitString . htmlspecialchars($fileName);
                    }
                }
            }
        }
        if ($fieldSetup['type'] == 'multiple') {
            foreach ($fieldSetup['items'] as $key => $val) {
                if (substr($val[0], 0, 4) == 'LLL:') {
                    $value = $this->languageService->sL($val[0]);
                } else {
                    $value = $val[0];
                }
                if (GeneralUtility::inList($fieldValue, $val[1]) || $fieldValue == $val[1]) {
                    if (!$out) {
                        $out = htmlspecialchars($value);
                    } else {
                        $out .= $splitString . htmlspecialchars($value);
                    }
                }
            }
        }
        if ($fieldSetup['type'] == 'binary') {
            foreach ($fieldSetup['items'] as $Key => $val) {
                if (substr($val[0], 0, 4) == 'LLL:') {
                    $value = $this->languageService->sL($val[0]);
                } else {
                    $value = $val[0];
                }
                if (!$out) {
                    $out = htmlspecialchars($value);
                } else {
                    $out .= $splitString . htmlspecialchars($value);
                }
            }
        }
        if ($fieldSetup['type'] == 'relation') {
            $dontPrefixFirstTable = 0;
            $useTablePrefix = 0;
            if ($fieldSetup['items']) {
                foreach ($fieldSetup['items'] as $key => $val) {
                    if (substr($val[0], 0, 4) == 'LLL:') {
                        $value = $this->languageService->sL($val[0]);
                    } else {
                        $value = $val[0];
                    }
                    if (GeneralUtility::inList($fieldValue, $value) || $fieldValue == $value) {
                        if (!$out) {
                            $out = htmlspecialchars($value);
                        } else {
                            $out .= $splitString . htmlspecialchars($value);
                        }
                    }
                }
            }
            if (stristr($fieldSetup['allowed'], ',')) {
                $from_table_Arr = explode(',', $fieldSetup['allowed']);
                $useTablePrefix = 1;
                if (!$fieldSetup['prepend_tname']) {
                    $checkres = $this->databaseConnection->exec_SELECTquery(
                        $fieldName,
                        $table,
                        'uid ' . BackendUtility::deleteClause($table)
                    );
                    if ($checkres) {
                        while ($row = $this->databaseConnection->sql_fetch_assoc($checkres)) {
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
                        $this->databaseConnection->sql_free_result($checkres);
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
                            if (substr($labelArray[0], 0, 4) == 'LLL:') {
                                $labelFieldSelect[$labelArray[1]] = $this->languageService->sL($labelArray[0]);
                            } else {
                                $labelFieldSelect[$labelArray[1]] = $labelArray[0];
                            }
                        }
                        $useSelectLabels = 1;
                    }
                    if ($GLOBALS['TCA'][$from_table]['columns'][$altLabelField]['config']['items']) {
                        $items = $GLOBALS['TCA'][$from_table]['columns'][$altLabelField]['config']['items'];
                        foreach ($items as $altLabelArray) {
                            if (substr($altLabelArray[0], 0, 4) == 'LLL:') {
                                $altLabelFieldSelect[$altLabelArray[1]] = $this->languageService->sL($altLabelArray[0]);
                            } else {
                                $altLabelFieldSelect[$altLabelArray[1]] = $altLabelArray[0];
                            }
                        }
                        $useAltSelectLabels = 1;
                    }
                    $altLabelFieldSelect = $altLabelField ? ',' . $altLabelField : '';
                    $select_fields = 'uid,' . $labelField . $altLabelFieldSelect;
                    if (!$this->backendUserAuthentication->isAdmin()
                        && $GLOBALS['TYPO3_CONF_VARS']['BE']['lockBeUserToDBmounts']) {
                        $webMounts = $this->backendUserAuthentication->returnWebmounts();
                        $perms_clause = $this->backendUserAuthentication->getPagePermsClause(1);
                        $webMountPageTree = '';
                        $webMountPageTreePrefix = '';
                        foreach ($webMounts as $key => $val) {
                            if ($webMountPageTree) {
                                $webMountPageTreePrefix = ',';
                            }
                            $webMountPageTree .= $webMountPageTreePrefix
                                . $this->getTreeList($val, 999, ($begin = 0), $perms_clause);
                        }
                        if ($from_table == 'pages') {
                            $where_clause = 'uid IN (' . $webMountPageTree . ') '
                                . BackendUtility::deleteClause($from_table) . ' AND ' . $perms_clause;
                        } else {
                            $where_clause = 'pid IN (' . $webMountPageTree . ') '
                                . BackendUtility::deleteClause($from_table);
                        }
                    } else {
                        $where_clause = 'uid' . BackendUtility::deleteClause($from_table);
                    }
                    $orderBy = 'uid';
                    $res = null;
                    if (!$this->tableArray[$from_table]) {
                        $res = $this->databaseConnection->exec_SELECTquery(
                            $select_fields,
                            $from_table,
                            $where_clause,
                            ($groupBy = ''),
                            $orderBy
                        );
                        $this->tableArray[$from_table] = array();
                    }
                    if ($res) {
                        while ($row = $this->databaseConnection->sql_fetch_assoc($res)) {
                            $this->tableArray[$from_table][] = $row;
                        }
                        $this->databaseConnection->sql_free_result($res);
                    }
                    foreach ($this->tableArray[$from_table] as $key => $val) {
                        $GLOBALS['SOBE']->MOD_SETTINGS['labels_noprefix'] =
                            $GLOBALS['SOBE']->MOD_SETTINGS['labels_noprefix'] == 1
                                ? 'on'
                                : $GLOBALS['SOBE']->MOD_SETTINGS['labels_noprefix'];
                        $prefixString =
                            $GLOBALS['SOBE']->MOD_SETTINGS['labels_noprefix'] == 'on'
                                ? ''
                                : ' [' . $tablePrefix . $val['uid'] . '] ';
                        if (GeneralUtility::inList($fieldValue, $tablePrefix . $val['uid'])
                            || $fieldValue == $tablePrefix . $val['uid']) {
                            if ($useSelectLabels) {
                                if (!$out) {
                                    $out = htmlspecialchars($prefixString . $labelFieldSelect[$val[$labelField]]);
                                } else {
                                    $out .= $splitString . htmlspecialchars(
                                        $prefixString . $labelFieldSelect[$val[$labelField]]
                                    );
                                }
                            } elseif ($val[$labelField]) {
                                if (!$out) {
                                    $out = htmlspecialchars($prefixString . $val[$labelField]);
                                } else {
                                    $out .= $splitString . htmlspecialchars(
                                        $prefixString . $val[$labelField]
                                    );
                                }
                            } elseif ($useAltSelectLabels) {
                                if (!$out) {
                                    $out = htmlspecialchars($prefixString . $altLabelFieldSelect[$val[$altLabelField]]);
                                } else {
                                    $out .= $splitString . htmlspecialchars(
                                        $prefixString . $altLabelFieldSelect[$val[$altLabelField]]
                                    );
                                }
                            } else {
                                if (!$out) {
                                    $out = htmlspecialchars($prefixString . $val[$altLabelField]);
                                } else {
                                    $out .= $splitString . htmlspecialchars(($prefixString . $val[$altLabelField]));
                                }
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
     * @param string $table Table name
     * @return string HTML of table header
     */
    public function resultRowTitles($row, $conf, $table)
    {
        $SET = $GLOBALS['SOBE']->MOD_SETTINGS;
        $tableHeader = array();
        // Start header row
        $tableHeader[] = '<thead><tr>';
        // Iterate over given columns
        foreach ($row as $fieldName => $fieldValue) {
            if (GeneralUtility::inList($SET['queryFields'], $fieldName)
                || !$SET['queryFields']
                && $fieldName != 'pid'
                && $fieldName != 'deleted'
            ) {
                if ($GLOBALS['SOBE']->MOD_SETTINGS['search_result_labels']) {
                    $title = $this->languageService->sL($conf['columns'][$fieldName]['label']
                        ? $conf['columns'][$fieldName]['label']
                        : $fieldName, true);
                } else {
                    $title = htmlspecialchars($this->languageService->sL($fieldName));
                }
                $tableHeader[] = '<th>' . $title . '</th>';
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
     * @param mixed $table Not used
     * @return string
     */
    public function csvRowTitles($row, $conf, $table)
    {
        $out = '';
        $SET = $GLOBALS['SOBE']->MOD_SETTINGS;
        foreach ($row as $fieldName => $fieldValue) {
            if (GeneralUtility::inList($SET['queryFields'], $fieldName)
                || !$SET['queryFields'] && $fieldName != 'pid') {
                if (!$out) {
                    if ($GLOBALS['SOBE']->MOD_SETTINGS['search_result_labels']) {
                        $out = $this->languageService->sL($conf['columns'][$fieldName]['label']
                            ? $conf['columns'][$fieldName]['label']
                            : $fieldName, true);
                    } else {
                        $out = htmlspecialchars($this->languageService->sL($fieldName));
                    }
                } else {
                    if ($GLOBALS['SOBE']->MOD_SETTINGS['search_result_labels']) {
                        $out .= ',' . $this->languageService->sL(($conf['columns'][$fieldName]['label']
                            ? $conf['columns'][$fieldName]['label']
                            : $fieldName), true);
                    } else {
                        $out .= ',' . htmlspecialchars($this->languageService->sL($fieldName));
                    }
                }
            }
        }
        return $out;
    }

    /**
     * Sets the current name of the input form.
     *
     * @param string $formName The name of the form.
     * @return void
     */
    public function setFormName($formName)
    {
        $this->formName = trim($formName);
    }
}
