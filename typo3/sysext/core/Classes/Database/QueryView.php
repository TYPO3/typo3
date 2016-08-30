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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * constructor
     */
    public function __construct()
    {
        $GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_t3lib_fullsearch.xlf');
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Get form
     *
     * @return string
     */
    public function form()
    {
        return '
		<div class="form-group">
			<input placeholder="Search Word" class="form-control" type="search" name="SET[sword]" value="' . htmlspecialchars($GLOBALS['SOBE']->MOD_SETTINGS['sword']) . '">
		</div>
		<div class="form-group">
			<input class="btn btn-default" type="submit" name="submit" value="Search All Records">
		</div>
		';
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
            $opt[] = '<option value="' . $k . '">' . htmlspecialchars($v) . '</option>';
        }
        // Actions:
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('sys_action') && $GLOBALS['BE_USER']->isAdmin()) {
            $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_action', 'type=2', '', 'title');
            $opt[] = '<option value="0">__Save to Action:__</option>';
            foreach ($rows as $row) {
                $opt[] = '<option value="-' . $row['uid'] . '">' . htmlspecialchars(($row['title'] . ' [' . $row['uid'] . ']')) . '</option>';
            }
        }
        return '<div class="load-queries">
			<div class="form-inline">
				<div class="form-group">
					<select class="form-control" name="storeControl[STORE]" onChange="document.forms[0][\'storeControl[title]\'].value= this.options[this.selectedIndex].value!=0 ? this.options[this.selectedIndex].text : \'\';">' . implode(LF, $opt) . '</select>
					<input class="btn btn-default" type="submit" name="storeControl[LOAD]" value="Load">
				</div>
			</div>
			<div class="form-inline">
				<div class="form-group">
					<input name="storeControl[title]" value="" type="text" max="80" class="form-control">
					<input class="btn btn-default" type="submit" name="storeControl[SAVE]" value="Save" onClick="if (document.forms[0][\'storeControl[STORE]\'].options[document.forms[0][\'storeControl[STORE]\'].selectedIndex].value<0) return confirm(\'Are you sure you want to overwrite the existing query in this action?\');">
					<input class="btn btn-default" type="submit" name="storeControl[REMOVE]" value="Remove">
				</div>
			</div>
		</div>';
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
        $storeQueryConfigs[$index] = [];
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
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('sys_action')) {
            $keyArr = explode(',', $this->storeList);
            $saveArr = [];
            foreach ($keyArr as $k) {
                $saveArr[$k] = $GLOBALS['SOBE']->MOD_SETTINGS[$k];
            }
            $qOK = 0;
            // Show query
            if ($saveArr['queryTable']) {
                /** @var \TYPO3\CMS\Core\Database\QueryGenerator */
                $qGen = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\QueryGenerator::class);
                $qGen->init('queryConfig', $saveArr['queryTable']);
                $qGen->makeSelectorTable($saveArr);
                $qGen->enablePrefix = 1;
                $qString = $qGen->getQuery($qGen->queryConfig);
                $qCount = $GLOBALS['TYPO3_DB']->SELECTquery('count(*)', $qGen->table, $qString . BackendUtility::deleteClause($qGen->table));
                $qSelect = $qGen->getSelectQuery($qString);
                $res = @$GLOBALS['TYPO3_DB']->sql_query($qCount);
                if (!$GLOBALS['TYPO3_DB']->sql_error()) {
                    $GLOBALS['TYPO3_DB']->sql_free_result($res);
                    $dA = [];
                    $dA['t2_data'] = serialize([
                        'qC' => $saveArr,
                        'qCount' => $qCount,
                        'qSelect' => $qSelect,
                        'qString' => $qString
                    ]);
                    $GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_action', 'uid=' . (int)$uid, $dA);
                    $qOK = 1;
                }
            }
            return $qOK;
        }
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
        $writeArray = [];
        if (is_array($storeControl)) {
            $msg = '';
            if ($storeControl['LOAD']) {
                if ($storeIndex > 0) {
                    $writeArray = $this->loadStoreQueryConfigs($storeQueryConfigs, $storeIndex, $writeArray);
                    $saveStoreArray = 1;
                    $flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, sprintf($GLOBALS['LANG']->getLL('query_loaded'), htmlspecialchars($storeArray[$storeIndex])));
                } elseif ($storeIndex < 0 && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('sys_action')) {
                    $actionRecord = BackendUtility::getRecord('sys_action', abs($storeIndex));
                    if (is_array($actionRecord)) {
                        $dA = unserialize($actionRecord['t2_data']);
                        $dbSC = [];
                        if (is_array($dA['qC'])) {
                            $dbSC[0] = $dA['qC'];
                        }
                        $writeArray = $this->loadStoreQueryConfigs($dbSC, '0', $writeArray);
                        $saveStoreArray = 1;
                        $flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, sprintf($GLOBALS['LANG']->getLL('query_from_action_loaded'), htmlspecialchars($actionRecord['title'])));
                    }
                }
            } elseif ($storeControl['SAVE']) {
                if ($storeIndex < 0) {
                    $qOK = $this->saveQueryInAction(abs($storeIndex));
                    if ($qOK) {
                        $flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $GLOBALS['LANG']->getLL('query_saved'));
                    } else {
                        $flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $GLOBALS['LANG']->getLL('query_notsaved'), '', \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
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
                        $flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $GLOBALS['LANG']->getLL('query_saved'));
                    }
                }
            } elseif ($storeControl['REMOVE']) {
                if ($storeIndex > 0) {
                    $flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, sprintf($GLOBALS['LANG']->getLL('query_removed'), htmlspecialchars($storeArray[$storeControl['STORE']])));
                    // Removing
                    unset($storeArray[$storeControl['STORE']]);
                    $saveStoreArray = 1;
                }
            }
            if ($flashMessage) {
                $msg = $flashMessage->render();
            }
        }
        if ($saveStoreArray) {
            // Making sure, index 0 is not set!
            unset($storeArray[0]);
            $writeArray['storeArray'] = serialize($storeArray);
            $writeArray['storeQueryConfigs'] = serialize($this->cleanStoreQueryConfigs($storeQueryConfigs, $storeArray));
            $GLOBALS['SOBE']->MOD_SETTINGS = BackendUtility::getModuleData($GLOBALS['SOBE']->MOD_MENU, $writeArray, $GLOBALS['SOBE']->MCONF['name'], 'ses');
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
        if (!$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableStoreControl']) {
            $output .= '<h2>Load/Save Query</h2><div>' . $this->makeStoreControl() . '</div>';
            if ($msg) {
                $output .= '<br />' . $msg;
            }
            $output .= '<div style="padding-top: 20px;"></div>';
        }
        // Query Maker:
        $qGen = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\QueryGenerator::class);
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
                        $qExplain = $GLOBALS['TYPO3_DB']->SELECTquery('count(*)', $qGen->table, $qString . BackendUtility::deleteClause($qGen->table));
                        break;
                    default:
                        $qExplain = $qGen->getSelectQuery($qString);
                        if ($mQ == 'explain') {
                            $qExplain = 'EXPLAIN ' . $qExplain;
                        }
                }
                if (!$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableShowSQLQuery']) {
                    $output .= '<h2>SQL query</h2><div>' . $this->tableWrap(htmlspecialchars($qExplain)) . '</div>';
                }
                $res = @$GLOBALS['TYPO3_DB']->sql_query($qExplain);
                if ($GLOBALS['TYPO3_DB']->sql_error()) {
                    $out = '<BR><strong>Error:</strong><BR><font color="red"><strong>' . $GLOBALS['TYPO3_DB']->sql_error() . '</strong></font>';
                    $output .= '<h2>SQL error</h2><div>' . $out . '</div>';
                } else {
                    $cPR = $this->getQueryResultCode($mQ, $res, $qGen->table);
                    $GLOBALS['TYPO3_DB']->sql_free_result($res);
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
        $cPR = [];
        switch ($mQ) {
            case 'count':
                $row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
                $cPR['header'] = 'Count';
                $cPR['content'] = '<BR><strong>' . $row[0] . '</strong> records selected.';
                break;
            case 'all':
                $rowArr = [];
                while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                    $rowArr[] = $this->resultRowDisplay($row, $GLOBALS['TCA'][$table], $table);
                    $lrow = $row;
                }
                if (is_array($this->hookArray['beforeResultTable'])) {
                    foreach ($this->hookArray['beforeResultTable'] as $_funcRef) {
                        $out .= GeneralUtility::callUserFunction($_funcRef, $GLOBALS['SOBE']->MOD_SETTINGS, $this);
                    }
                }
                if (!empty($rowArr)) {
                    $out .= '<table class="table table-striped table-hover">' . $this->resultRowTitles($lrow, $GLOBALS['TCA'][$table], $table) . implode(LF, $rowArr) . '</table>';
                }
                if (!$out) {
                    $out = '<div class="alert-info">No rows selected!</div>';
                }
                $cPR['header'] = 'Result';
                $cPR['content'] = $out;
                break;
            case 'csv':
                $rowArr = [];
                $first = 1;
                while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
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
                        $out .= '<br><input class="btn btn-default" type="submit" name="download_file" value="Click to download file" onClick="window.location.href=\'' . $this->downloadScript . '\';">';
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
                while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                    $out .= '<br />' . \TYPO3\CMS\Core\Utility\DebugUtility::viewArray($row);
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
                $fieldsInDatabase = $GLOBALS['TYPO3_DB']->admin_get_fields($table);
                $list = array_intersect(array_keys($conf['columns']), array_keys($fieldsInDatabase));
                // Get query
                $qp = $GLOBALS['TYPO3_DB']->searchQuery([$swords], $list, $table);
                // Count:
                $count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', $table, $qp . BackendUtility::deleteClause($table));
                if ($count) {
                    $rowArr = [];
                    $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,' . $conf['ctrl']['label'], $table, $qp . BackendUtility::deleteClause($table), '', '', $limit);
                    while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                        $rowArr[] = $this->resultRowDisplay($row, $conf, $table);
                        $lrow = $row;
                    }
                    $GLOBALS['TYPO3_DB']->sql_free_result($res);
                    $out .= '<div class="panel panel-default">
								<div class="panel-heading">' . $GLOBALS['LANG']->sL($conf['ctrl']['title'], true) . ' (' . $count . ')</div>
								<table class="table table-striped table-hover">' .
                        $this->resultRowTitles($lrow, $conf, $table) .
                        implode(LF, $rowArr) .
                        '</table>
							</div>';
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
            if (GeneralUtility::inList($SET['queryFields'], $fieldName) || !$SET['queryFields'] && $fieldName != 'pid' && $fieldName != 'deleted') {
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
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI') . GeneralUtility::implodeArrayForUrl('SET', (array)GeneralUtility::_POST('SET'))
            ]);
            $out .= '<a class="btn btn-default" href="#" onClick="top.launchView(\'' . $table . '\',' . $row['uid'] . ',\'' . $GLOBALS['BACK_PATH'] . '\');return false;">' . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render() . '</a>';
            $out .= '<a class="btn btn-default" href="' . htmlspecialchars($url) . '">' . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '</a>';
        } else {
            $out .= '<a class="btn btn-default" href="' . GeneralUtility::linkThisUrl(BackendUtility::getModuleUrl('tce_db'), [
                    ('cmd[' . $table . '][' . $row['uid'] . '][undelete]') => '1',
                    'redirect' => GeneralUtility::linkThisScript([])
                ]) . '" title="' . $GLOBALS['LANG']->getLL('undelete_only', true) . '">';
            $out .= $this->iconFactory->getIcon('actions-edit-restore', Icon::SIZE_SMALL)->render() . '</a>';
            $formEngineParameters = [
                'edit[' . $table . '][' . $row['uid'] . ']' => 'edit',
                'returnUrl' => GeneralUtility::linkThisScript([])
            ];
            $redirectUrl = BackendUtility::getModuleUrl('record_edit', $formEngineParameters);
            $out .= '<a class="btn btn-default" href="' . GeneralUtility::linkThisUrl(BackendUtility::getModuleUrl('tce_db'), [
                    ('cmd[' . $table . '][' . $row['uid'] . '][undelete]') => '1',
                    'redirect' => $redirectUrl
                ]) . '" title="' . $GLOBALS['LANG']->getLL('undelete_and_edit', true) . '">';
            $out .= $this->iconFactory->getIcon('actions-edit-restore-edit', Icon::SIZE_SMALL)->render() . '</a>';
        }
        $_params = [$table => $row];
        if (is_array($this->hookArray['additionalButtons'])) {
            foreach ($this->hookArray['additionalButtons'] as $_funcRef) {
                $out .= GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
        $out .= '</div></td>
		</tr>
		';
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
        // Analysing the fields in the table.
        if (is_array($GLOBALS['TCA'][$table])) {
            $fC = $GLOBALS['TCA'][$table]['columns'][$fieldName];
            $fields = $fC['config'];
            $fields['exclude'] = $fC['exclude'];
            if (is_array($fC) && $fC['label']) {
                $fields['label'] = preg_replace('/:$/', '', trim($GLOBALS['LANG']->sL($fC['label'])));
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
     * @param string $perms_clause
     * @return string
     */
    public function getTreeList($id, $depth, $begin = 0, $perms_clause)
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
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages', 'pid=' . $id . ' ' . BackendUtility::deleteClause('pages') . ' AND ' . $perms_clause);
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                if ($begin <= 0) {
                    $theList .= ',' . $row['uid'];
                }
                if ($depth > 1) {
                    $theList .= $this->getTreeList($row['uid'], $depth - 1, $begin - 1, $perms_clause);
                }
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($res);
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
                    $value = $GLOBALS['LANG']->sL($val[0]);
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
                    $value = $GLOBALS['LANG']->sL($val[0]);
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
            if ($fieldSetup['items']) {
                foreach ($fieldSetup['items'] as $key => $val) {
                    if (substr($val[0], 0, 4) == 'LLL:') {
                        $value = $GLOBALS['LANG']->sL($val[0]);
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
                    $checkres = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fieldName, $table, 'uid ' . BackendUtility::deleteClause($table), ($groupBy = ''), ($orderBy = ''), ($limit = ''));
                    if ($checkres) {
                        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($checkres)) {
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
                        $GLOBALS['TYPO3_DB']->sql_free_result($checkres);
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
            foreach ($from_table_Arr as $from_table) {
                if ($useTablePrefix && !$dontPrefixFirstTable && $counter != 1 || $counter == 1) {
                    $tablePrefix = $from_table . '_';
                }
                $counter = 1;
                if (is_array($GLOBALS['TCA'][$from_table])) {
                    $labelField = $GLOBALS['TCA'][$from_table]['ctrl']['label'];
                    $altLabelField = $GLOBALS['TCA'][$from_table]['ctrl']['label_alt'];
                    if ($GLOBALS['TCA'][$from_table]['columns'][$labelField]['config']['items']) {
                        foreach ($GLOBALS['TCA'][$from_table]['columns'][$labelField]['config']['items'] as $labelArray) {
                            if (substr($labelArray[0], 0, 4) == 'LLL:') {
                                $labelFieldSelect[$labelArray[1]] = $GLOBALS['LANG']->sL($labelArray[0]);
                            } else {
                                $labelFieldSelect[$labelArray[1]] = $labelArray[0];
                            }
                        }
                        $useSelectLabels = 1;
                    }
                    if ($GLOBALS['TCA'][$from_table]['columns'][$altLabelField]['config']['items']) {
                        foreach ($GLOBALS['TCA'][$from_table]['columns'][$altLabelField]['config']['items'] as $altLabelArray) {
                            if (substr($altLabelArray[0], 0, 4) == 'LLL:') {
                                $altLabelFieldSelect[$altLabelArray[1]] = $GLOBALS['LANG']->sL($altLabelArray[0]);
                            } else {
                                $altLabelFieldSelect[$altLabelArray[1]] = $altLabelArray[0];
                            }
                        }
                        $useAltSelectLabels = 1;
                    }
                    $altLabelFieldSelect = $altLabelField ? ',' . $altLabelField : '';
                    $select_fields = 'uid,' . $labelField . $altLabelFieldSelect;
                    if (!$GLOBALS['BE_USER']->isAdmin() && $GLOBALS['TYPO3_CONF_VARS']['BE']['lockBeUserToDBmounts']) {
                        $webMounts = $GLOBALS['BE_USER']->returnWebmounts();
                        $perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
                        $webMountPageTree = '';
                        foreach ($webMounts as $key => $val) {
                            if ($webMountPageTree) {
                                $webMountPageTreePrefix = ',';
                            }
                            $webMountPageTree .= $webMountPageTreePrefix . $this->getTreeList($val, 999, ($begin = 0), $perms_clause);
                        }
                        if ($from_table == 'pages') {
                            $where_clause = 'uid IN (' . $webMountPageTree . ') ' . BackendUtility::deleteClause($from_table) . ' AND ' . $perms_clause;
                        } else {
                            $where_clause = 'pid IN (' . $webMountPageTree . ') ' . BackendUtility::deleteClause($from_table);
                        }
                    } else {
                        $where_clause = 'uid' . BackendUtility::deleteClause($from_table);
                    }
                    $orderBy = 'uid';
                    if (!$this->tableArray[$from_table]) {
                        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select_fields, $from_table, $where_clause, ($groupBy = ''), $orderBy, ($limit = ''));
                        $this->tableArray[$from_table] = [];
                    }
                    if ($res) {
                        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                            $this->tableArray[$from_table][] = $row;
                        }
                        $GLOBALS['TYPO3_DB']->sql_free_result($res);
                    }
                    foreach ($this->tableArray[$from_table] as $key => $val) {
                        $GLOBALS['SOBE']->MOD_SETTINGS['labels_noprefix'] = $GLOBALS['SOBE']->MOD_SETTINGS['labels_noprefix'] == 1 ? 'on' : $GLOBALS['SOBE']->MOD_SETTINGS['labels_noprefix'];
                        $prefixString = $GLOBALS['SOBE']->MOD_SETTINGS['labels_noprefix'] == 'on' ? '' : ' [' . $tablePrefix . $val['uid'] . '] ';
                        if (GeneralUtility::inList($fieldValue, $tablePrefix . $val['uid']) || $fieldValue == $tablePrefix . $val['uid']) {
                            if ($useSelectLabels) {
                                if (!$out) {
                                    $out = htmlspecialchars($prefixString . $labelFieldSelect[$val[$labelField]]);
                                } else {
                                    $out .= $splitString . htmlspecialchars(($prefixString . $labelFieldSelect[$val[$labelField]]));
                                }
                            } elseif ($val[$labelField]) {
                                if (!$out) {
                                    $out = htmlspecialchars($prefixString . $val[$labelField]);
                                } else {
                                    $out .= $splitString . htmlspecialchars(($prefixString . $val[$labelField]));
                                }
                            } elseif ($useAltSelectLabels) {
                                if (!$out) {
                                    $out = htmlspecialchars($prefixString . $altLabelFieldSelect[$val[$altLabelField]]);
                                } else {
                                    $out .= $splitString . htmlspecialchars(($prefixString . $altLabelFieldSelect[$val[$altLabelField]]));
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
        $tableHeader = [];
        // Start header row
        $tableHeader[] = '<thead><tr>';
        // Iterate over given columns
        foreach ($row as $fieldName => $fieldValue) {
            if (GeneralUtility::inList($SET['queryFields'], $fieldName) || !$SET['queryFields'] && $fieldName != 'pid' && $fieldName != 'deleted') {
                if ($GLOBALS['SOBE']->MOD_SETTINGS['search_result_labels']) {
                    $title = $GLOBALS['LANG']->sL($conf['columns'][$fieldName]['label'] ? $conf['columns'][$fieldName]['label'] : $fieldName, true);
                } else {
                    $title = $GLOBALS['LANG']->sL($fieldName, true);
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
            if (GeneralUtility::inList($SET['queryFields'], $fieldName) || !$SET['queryFields'] && $fieldName != 'pid') {
                if (!$out) {
                    if ($GLOBALS['SOBE']->MOD_SETTINGS['search_result_labels']) {
                        $out = $GLOBALS['LANG']->sL($conf['columns'][$fieldName]['label'] ? $conf['columns'][$fieldName]['label'] : $fieldName, true);
                    } else {
                        $out = $GLOBALS['LANG']->sL($fieldName, true);
                    }
                } else {
                    if ($GLOBALS['SOBE']->MOD_SETTINGS['search_result_labels']) {
                        $out .= ',' . $GLOBALS['LANG']->sL(($conf['columns'][$fieldName]['label'] ? $conf['columns'][$fieldName]['label'] : $fieldName), true);
                    } else {
                        $out .= ',' . $GLOBALS['LANG']->sL($fieldName, true);
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
