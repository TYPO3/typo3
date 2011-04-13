<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Class used in module tools/dbint (advanced search) and which may hold code specific for that module
 * However the class has a general principle in it which may be used in the web/export module.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @coauthor	Jo Hasenau <info@cybercraft.de>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   88: class t3lib_fullsearch
 *  103:	 function form()
 *  117:	 function makeStoreControl()
 *  156:	 function initStoreArray()
 *  176:	 function cleanStoreQueryConfigs($storeQueryConfigs,$storeArray)
 *  193:	 function addToStoreQueryConfigs($storeQueryConfigs,$index)
 *  209:	 function saveQueryInAction($uid)
 *  256:	 function loadStoreQueryConfigs($storeQueryConfigs,$storeIndex,$writeArray)
 *  272:	 function procesStoreControl()
 *  344:	 function queryMaker()
 *  414:	 function getQueryResultCode($mQ,$res,$table)
 *  534:	 function csvValues($row, $delim=',', $quote='"', $conf=array(), $table='')
 *  550:	 function tableWrap($str)
 *  559:	 function search()
 *  614:	 function resultRowDisplay($row,$conf,$table)
 *  662:	 function getProcessedValueExtra($table, $fN, $fV, $conf, $splitString)
 *  781:	 function getTreeList($id, $depth, $begin = 0, $perms_clause)
 *  818:	 function makeValueList($fN, $fV, $conf, $table, $splitString)
 * 1028:	 function resultRowTitles($row,$conf,$table)
 * 1058:	 function csvRowTitles($row, $conf, $table)
 *
 * TOTAL FUNCTIONS: 19
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * Class used in module tools/dbint (advanced search) and which may hold code specific for that module
 * However the class has a general principle in it which may be used in the web/export module.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_fullsearch {
	var $storeList = 'search_query_smallparts,search_result_labels,labels_noprefix,show_deleted,queryConfig,queryTable,queryFields,queryLimit,queryOrder,queryOrderDesc,queryOrder2,queryOrder2Desc,queryGroup,search_query_makeQuery';
	var $downloadScript = 'index.php';
	var $formW = 48;
	var $noDownloadB = 0;

	protected $formName = '';

	/**
	 * constructor
	 */
	public function __construct() {
		$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_t3lib_fullsearch.xml');
	}


	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function form() {
		$out = '
		Search Word:<BR>
		<input type="text" name="SET[sword]" value="' . htmlspecialchars($GLOBALS['SOBE']->MOD_SETTINGS['sword']) . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(20) . '><input type="submit" name="submit" value="Search All Records">
		';

		return $out;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function makeStoreControl() {
			// Load/Save
		$storeArray = $this->initStoreArray();
		$cur = '';

			// Store Array:
		$opt = array();
		foreach ($storeArray as $k => $v) {
			$opt[] = '<option value="' . $k . '"' . (!strcmp($cur, $v) ? ' selected' : '') . '>' . htmlspecialchars($v) . '</option>';
		}

			// Actions:
		if (t3lib_extMgm::isLoaded('sys_action') && $GLOBALS['BE_USER']->isAdmin()) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_action', 'type=2', '', 'title');
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
				$opt[] = '<option value="0">__Save to Action:__</option>';
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$opt[] = '<option value="-' . $row['uid'] . '"' . (!strcmp($cur, '-' . $row['uid']) ? ' selected' : '') . '>' . htmlspecialchars($row['title'] . ' [' . $row['uid'] . ']') . '</option>';
				}
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}

		$TDparams = ' nowrap="nowrap" class="bgColor4"';
		$tmpCode = '
		<table border="0" cellpadding="3" cellspacing="1">
		<tr' . $TDparams . '><td><select name="storeControl[STORE]" onChange="document.forms[0][\'storeControl[title]\'].value= this.options[this.selectedIndex].value!=0 ? this.options[this.selectedIndex].text : \'\';">' . implode(LF, $opt) . '</select><input type="submit" name="storeControl[LOAD]" value="Load"></td></tr>
		<tr' . $TDparams . '><td nowrap><input name="storeControl[title]" value="" type="text" max="80"' . $GLOBALS['SOBE']->doc->formWidth() . '><input type="submit" name="storeControl[SAVE]" value="Save" onClick="if (document.forms[0][\'storeControl[STORE]\'].options[document.forms[0][\'storeControl[STORE]\'].selectedIndex].value<0) return confirm(\'Are you sure you want to overwrite the existing query in this action?\');"><input type="submit" name="storeControl[REMOVE]" value="Remove"></td></tr>
		</table>
		';
		return $tmpCode;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function initStoreArray() {
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
	 * [Describe function...]
	 *
	 * @param	[type]		$storeQueryConfigs: ...
	 * @param	[type]		$storeArray: ...
	 * @return	[type]		...
	 */
	function cleanStoreQueryConfigs($storeQueryConfigs, $storeArray) {
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
	 * [Describe function...]
	 *
	 * @param	[type]		$storeQueryConfigs: ...
	 * @param	[type]		$index: ...
	 * @return	[type]		...
	 */
	function addToStoreQueryConfigs($storeQueryConfigs, $index) {
		$keyArr = explode(',', $this->storeList);
		$storeQueryConfigs[$index] = array();
		foreach ($keyArr as $k) {
			$storeQueryConfigs[$index][$k] = $GLOBALS['SOBE']->MOD_SETTINGS[$k];
		}
		return $storeQueryConfigs;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
	function saveQueryInAction($uid) {
		if (t3lib_extMgm::isLoaded('sys_action')) {
			$keyArr = explode(',', $this->storeList);
			$saveArr = array();
			foreach ($keyArr as $k) {
				$saveArr[$k] = $GLOBALS['SOBE']->MOD_SETTINGS[$k];
			}

			$qOK = 0;
				// Show query
			if ($saveArr['queryTable']) {
				/* @var t3lib_queryGenerator */
				$qGen = t3lib_div::makeInstance('t3lib_queryGenerator');
				$qGen->init('queryConfig', $saveArr['queryTable']);
				$qGen->makeSelectorTable($saveArr);

				$qGen->enablePrefix = 1;
				$qString = $qGen->getQuery($qGen->queryConfig);
				$qCount = $GLOBALS['TYPO3_DB']->SELECTquery('count(*)', $qGen->table, $qString . t3lib_BEfunc::deleteClause($qGen->table));
				$qSelect = $qGen->getSelectQuery($qString);

				$res = @$GLOBALS['TYPO3_DB']->sql_query($qCount);
				if (!$GLOBALS['TYPO3_DB']->sql_error()) {
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
					$dA = array();
					$dA['t2_data'] = serialize(array(
						'qC' => $saveArr,
						'qCount' => $qCount,
						'qSelect' => $qSelect,
						'qString' => $qString
					));
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_action', 'uid=' . intval($uid), $dA);
					$qOK = 1;
				}
			}

			return $qOK;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$storeQueryConfigs: ...
	 * @param	[type]		$storeIndex: ...
	 * @param	[type]		$writeArray: ...
	 * @return	[type]		...
	 */
	function loadStoreQueryConfigs($storeQueryConfigs, $storeIndex, $writeArray) {
		if ($storeQueryConfigs[$storeIndex]) {
			$keyArr = explode(',', $this->storeList);
			foreach ($keyArr as $k) {
				$writeArray[$k] = $storeQueryConfigs[$storeIndex][$k];
			}
		}
		return $writeArray;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function procesStoreControl() {
		$storeArray = $this->initStoreArray();
		$storeQueryConfigs = unserialize($GLOBALS['SOBE']->MOD_SETTINGS['storeQueryConfigs']);

		$storeControl = t3lib_div::_GP('storeControl');
		$storeIndex = intval($storeControl['STORE']);
		$saveStoreArray = 0;
		$writeArray = array();
		if (is_array($storeControl)) {
			$msg = '';
			if ($storeControl['LOAD']) {
				if ($storeIndex > 0) {
					$writeArray = $this->loadStoreQueryConfigs($storeQueryConfigs, $storeIndex, $writeArray);
					$saveStoreArray = 1;
					$flashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						sprintf($GLOBALS['LANG']->getLL('query_loaded'), htmlspecialchars($storeArray[$storeIndex]))
					);
				} elseif ($storeIndex < 0 && t3lib_extMgm::isLoaded('sys_action')) {
					$actionRecord = t3lib_BEfunc::getRecord('sys_action', abs($storeIndex));
					if (is_array($actionRecord)) {
						$dA = unserialize($actionRecord['t2_data']);
						$dbSC = array();
						if (is_array($dA['qC'])) {
							$dbSC[0] = $dA['qC'];
						}
						$writeArray = $this->loadStoreQueryConfigs($dbSC, '0', $writeArray);
						$saveStoreArray = 1;

						$flashMessage = t3lib_div::makeInstance(
							't3lib_FlashMessage',
							sprintf($GLOBALS['LANG']->getLL('query_from_action_loaded'), htmlspecialchars($actionRecord['title']))
						);
					}
				}
			} elseif ($storeControl['SAVE']) {
				if ($storeIndex < 0) {
					$qOK = $this->saveQueryInAction(abs($storeIndex));
					if ($qOK) {
						$flashMessage = t3lib_div::makeInstance(
							't3lib_FlashMessage',
							$GLOBALS['LANG']->getLL('query_saved')
						);
					} else {
						$flashMessage = t3lib_div::makeInstance(
							't3lib_FlashMessage',
							$GLOBALS['LANG']->getLL('query_notsaved'),
							'',
							t3lib_FlashMessage::ERROR
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
						$flashMessage = t3lib_div::makeInstance(
							't3lib_FlashMessage',
							$GLOBALS['LANG']->getLL('query_saved')
						);
					}
				}
			} elseif ($storeControl['REMOVE']) {
				if ($storeIndex > 0) {
					$flashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						sprintf($GLOBALS['LANG']->getLL('query_removed'), htmlspecialchars($storeArray[$storeControl['STORE']]))
					);
					unset($storeArray[$storeControl['STORE']]); // Removing
					$saveStoreArray = 1;
				}
			}
			if ($flashMessage) {
				$msg = $flashMessage->render();
			}
		}
		if ($saveStoreArray) {
			unset($storeArray[0]); // making sure, index 0 is not set!
			$writeArray['storeArray'] = serialize($storeArray);
			$writeArray['storeQueryConfigs'] = serialize($this->cleanStoreQueryConfigs($storeQueryConfigs, $storeArray));
			$GLOBALS['SOBE']->MOD_SETTINGS = t3lib_BEfunc::getModuleData($GLOBALS['SOBE']->MOD_MENU, $writeArray, $GLOBALS['SOBE']->MCONF['name'], 'ses');
		}
		return $msg;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function queryMaker() {
		$output = '';
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3lib_fullsearch'])) {
			$this->hookArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3lib_fullsearch'];
		}
		$msg = $this->procesStoreControl();

		if (!$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableStoreControl']) {
			$output .= $GLOBALS['SOBE']->doc->section('Load/Save Query', $this->makeStoreControl(), 0, 1);
			if ($msg) {
				$output .= '<br />' . $msg;
			}
			$output .= $GLOBALS['SOBE']->doc->spacer(20);
		}


			// Query Maker:
		$qGen = t3lib_div::makeInstance('t3lib_queryGenerator');
		$qGen->init('queryConfig', $GLOBALS['SOBE']->MOD_SETTINGS['queryTable']);
		if ($this->formName) {
			$qGen->setFormName($this->formName);
		}
		$tmpCode = $qGen->makeSelectorTable($GLOBALS['SOBE']->MOD_SETTINGS);
		$output .= $GLOBALS['SOBE']->doc->section('Make query', $tmpCode, 0, 1);

		$mQ = $GLOBALS['SOBE']->MOD_SETTINGS['search_query_makeQuery'];

			// Make form elements:
		if ($qGen->table && is_array($GLOBALS['TCA'][$qGen->table])) {
			if ($mQ) {
					// Show query
				$qGen->enablePrefix = 1;
				$qString = $qGen->getQuery($qGen->queryConfig);
				//				debug($qGen->queryConfig);

				switch ($mQ) {
					case 'count':
						$qExplain = $GLOBALS['TYPO3_DB']->SELECTquery('count(*)', $qGen->table, $qString . t3lib_BEfunc::deleteClause($qGen->table));
					break;
					default:
						$qExplain = $qGen->getSelectQuery($qString);
						if ($mQ == 'explain') {
							$qExplain = 'EXPLAIN ' . $qExplain;
						}
					break;
				}

				if (!$GLOBALS['BE_USER']->userTS['mod.']['dbint.']['disableShowSQLQuery']) {
					$output .= $GLOBALS['SOBE']->doc->section('SQL query', $this->tableWrap(htmlspecialchars($qExplain)), 0, 1);
				}

				$res = @$GLOBALS['TYPO3_DB']->sql_query($qExplain);
				if ($GLOBALS['TYPO3_DB']->sql_error()) {
					$out = '<BR><strong>Error:</strong><BR><font color="red"><strong>' . $GLOBALS['TYPO3_DB']->sql_error() . '</strong></font>';
					$output .= $GLOBALS['SOBE']->doc->section('SQL error', $out, 0, 1);
				} else {
					$cPR = $this->getQueryResultCode($mQ, $res, $qGen->table);
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
					$output .= $GLOBALS['SOBE']->doc->section($cPR['header'], $cPR['content'], 0, 1);
				}
			}
		}
		return $output;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$mQ: ...
	 * @param	[type]		$res: ...
	 * @param	[type]		$table: ...
	 * @return	[type]		...
	 */
	function getQueryResultCode($mQ, $res, $table) {
		$out = '';
		$cPR = array();
		switch ($mQ) {
			case 'count':
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
				$cPR['header'] = 'Count';
				$cPR['content'] = '<BR><strong>' . $row[0] . '</strong> records selected.';
			break;
			case 'all':
				$rowArr = array();
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					$rowArr[] = $this->resultRowDisplay($row, $GLOBALS['TCA'][$table], $table);
					$lrow = $row;
				}
				if (is_array($this->hookArray['beforeResultTable'])) {
					foreach ($this->hookArray['beforeResultTable'] as $_funcRef) {
						$out .= t3lib_div::callUserFunction($_funcRef, $GLOBALS['SOBE']->MOD_SETTINGS, $this);
					}
				}
				if (count($rowArr)) {
					$out .= '<table border="0" cellpadding="2" cellspacing="1" width="100%">' .
							$this->resultRowTitles($lrow, $GLOBALS['TCA'][$table], $table) . implode(LF, $rowArr) . '</table>';
				}
				if (!$out) {
					$out = '<em>No rows selected!</em>';
				}
				$cPR['header'] = 'Result';
				$cPR['content'] = $out;
			break;
			case 'csv':
				$rowArr = array();
				$first = 1;
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					if ($first) {
						$rowArr[] = $this->csvValues(array_keys($row), ',', '');
						$first = 0;
					}
					$rowArr[] = $this->csvValues($row, ',', '"', $GLOBALS['TCA'][$table], $table);
				}
				if (count($rowArr)) {
					$out .= '<textarea name="whatever" rows="20" wrap="off"' . $GLOBALS['SOBE']->doc->formWidthText($this->formW, '', 'off') . ' class="fixed-font">' . t3lib_div::formatForTextarea(implode(LF, $rowArr)) . '</textarea>';
					if (!$this->noDownloadB) {
						$out .= '<BR><input type="submit" name="download_file" value="Click to download file" onClick="window.location.href=\'' . $this->downloadScript . '\';">'; // document.forms[0].target=\'_blank\';
					}
						// Downloads file:
					if (t3lib_div::_GP('download_file')) {
						$filename = 'TYPO3_' . $table . '_export_' . date('dmy-Hi') . '.csv';
						$mimeType = 'application/octet-stream';
						header('Content-Type: ' . $mimeType);
						header('Content-Disposition: attachment; filename=' . $filename);
						echo implode(CRLF, $rowArr);
						exit;
					}
				}
				if (!$out) {
					$out = '<em>No rows selected!</em>';
				}
				$cPR['header'] = 'Result';
				$cPR['content'] = $out;
			break;
			case 'xml':
				$xmlObj = t3lib_div::makeInstance('t3lib_xml', 'typo3_export');
				$xmlObj->includeNonEmptyValues = 1;
				$xmlObj->renderHeader();
				$first = 1;
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					if ($first) {
						$xmlObj->setRecFields($table, implode(',', array_keys($row)));
						$first = 0;
					}
					$valueArray = $row;
					if ($GLOBALS['SOBE']->MOD_SETTINGS['search_result_labels']) {
						foreach ($valueArray as $key => $val) {
							$valueArray[$key] = $this->getProcessedValueExtra($table, $key, $val, array(), ',');
						}
					}
					$xmlObj->addRecord($table, $valueArray);
				}
				$xmlObj->renderFooter();
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
					$xmlData = $xmlObj->getResult();
					$out .= '<textarea name="whatever" rows="20" wrap="off"' . $GLOBALS['SOBE']->doc->formWidthText($this->formW, '', 'off') . ' class="fixed-font">' . t3lib_div::formatForTextarea($xmlData) . '</textarea>';
					if (!$this->noDownloadB) {
						$out .= '<BR><input type="submit" name="download_file" value="Click to download file" onClick="window.location.href=\'' . $this->downloadScript . '\';">'; // document.forms[0].target=\'_blank\';
					}
						// Downloads file:
					if (t3lib_div::_GP('download_file')) {
						$filename = 'TYPO3_' . $table . '_export_' . date('dmy-Hi') . '.xml';
						$mimeType = 'application/octet-stream';
						header('Content-Type: ' . $mimeType);
						header('Content-Disposition: attachment; filename=' . $filename);
						echo $xmlData;
						exit;
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
					$out .= '<br />' . t3lib_utility_Debug::viewArray($row);
				}
				$cPR['header'] = 'Explain SQL query';
				$cPR['content'] = $out;
			break;
		}
		return $cPR;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$row: ...
	 * @param	[type]		$delim: ...
	 * @param	[type]		$quote: ...
	 * @param	[type]		$conf: ...
	 * @param	[type]		$table: ...
	 * @return	[type]		...
	 */
	function csvValues($row, $delim = ',', $quote = '"', $conf = array(), $table = '') {
		$valueArray = $row;
		if ($GLOBALS['SOBE']->MOD_SETTINGS['search_result_labels'] && $table) {
			foreach ($valueArray as $key => $val) {
				$valueArray[$key] = $this->getProcessedValueExtra($table, $key, $val, $conf, ';');
			}
		}
		return t3lib_div::csvValues($valueArray, $delim, $quote);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$str: ...
	 * @return	[type]		...
	 */
	function tableWrap($str) {
		return '<table border="0" cellpadding="10" cellspacing="0" class="bgColor4"><tr><td nowrap><pre>' . $str . '</pre></td></tr></table>';
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function search() {
		$SET = $GLOBALS['SOBE']->MOD_SETTINGS;
		$swords = $SET['sword'];

		$out = '';
		$limit = 200;
		$showAlways = 0;
		if ($swords) {
			foreach ($GLOBALS['TCA'] as $table => $value) {
					// Get fields list
				t3lib_div::loadTCA($table);
				$conf = $GLOBALS['TCA'][$table];

					// avoid querying tables with no columns
				if (empty($conf['columns'])) {
					continue;
				}

				$list = array_keys($conf['columns']);
					// Get query
				$qp = $GLOBALS['TYPO3_DB']->searchQuery(array($swords), $list, $table);

					// Count:
				$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', $table, $qp . t3lib_BEfunc::deleteClause($table));
				if ($count || $showAlways) {
						// Output header:
					$out .= '<strong>TABLE:</strong> ' . $GLOBALS['LANG']->sL($conf['ctrl']['title']) . '<BR>';
					$out .= '<strong>Results:</strong> ' . $count . '<BR>';

						// Show to limit
					if ($count) {
						$rowArr = array();
						$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,' . $conf['ctrl']['label'], $table, $qp . t3lib_BEfunc::deleteClause($table), '', '', $limit);
						while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
							$rowArr[] = $this->resultRowDisplay($row, $conf, $table);
							$lrow = $row;
						}
						$GLOBALS['TYPO3_DB']->sql_free_result($res);
						$out .= '<table border="0" cellpadding="2" cellspacing="1">' . $this->resultRowTitles($lrow, $conf, $table) . implode(LF, $rowArr) . '</table>';
					}
					$out .= '<HR>';
				}
			}
		}
		return $out;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$row: ...
	 * @param	[type]		$conf: ...
	 * @param	[type]		$table: ...
	 * @return	[type]		...
	 */
	function resultRowDisplay($row, $conf, $table) {
		static $even = FALSE;
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$SET = $GLOBALS['SOBE']->MOD_SETTINGS;
		$out = '<tr class="bgColor' . ($even ? '6' : '4') . '">';
		$even = !$even;
		foreach ($row as $fN => $fV) {
			if (t3lib_div::inList($SET['queryFields'], $fN) || (!$SET['queryFields'] && $fN != 'pid' && $fN != 'deleted')) {
				if ($SET['search_result_labels']) {
					$fVnew = $this->getProcessedValueExtra($table, $fN, $fV, $conf, '<br />');
				} else {
					$fVnew = htmlspecialchars($fV);
				}
				$out .= '<td>' . $fVnew . '</td>';
			}
		}
		$params = '&edit[' . $table . '][' . $row['uid'] . ']=edit';
		$out .= '<td nowrap>';
		if (!$row['deleted']) {
			$out .= '<a href="#" onClick="top.launchView(\'' . $table . '\',' . $row['uid'] . ',\'' . $GLOBALS['BACK_PATH'] . '\');return false;">' . t3lib_iconWorks::getSpriteIcon('status-dialog-information') . '</a>';
			$out .= '<a href="#" onClick="' . t3lib_BEfunc::editOnClick($params, $GLOBALS['BACK_PATH'], t3lib_div::getIndpEnv('REQUEST_URI') . t3lib_div::implodeArrayForUrl('SET', (array) t3lib_div::_POST('SET'))) . '">' . t3lib_iconWorks::getSpriteIcon('actions-document-open') . '</a>';
		} else {
			$out .= '<a href="' . t3lib_div::linkThisUrl($GLOBALS['BACK_PATH'] . 'tce_db.php',
					array(
						'cmd[' . $table . '][' . $row['uid'] . '][undelete]' => '1',
						'redirect' => t3lib_div::linkThisScript(array()))) . t3lib_BEfunc::getUrlToken('tceAction') . '">';
			$out .= t3lib_iconWorks::getSpriteIcon('actions-edit-restore', array('title' => 'undelete only')) . '</a>';
			$out .= '<a href="' . t3lib_div::linkThisUrl($GLOBALS['BACK_PATH'] . 'tce_db.php',
					array(
						'cmd[' . $table . '][' . $row['uid'] . '][undelete]' => '1',
						'redirect' => t3lib_div::linkThisUrl('alt_doc.php',
							array(
								'edit[' . $table . '][' . $row['uid'] . ']' => 'edit',
								'returnUrl' => t3lib_div::linkThisScript(array())
							)
						)
					)
				) . t3lib_BEfunc::getUrlToken('tceAction') . '">';
			$out .= t3lib_iconWorks::getSpriteIcon('actions-edit-restore-edit', array('title' => 'undelete and edit')) . '</a>';
		}
		$_params = array($table => $row);
		if (is_array($this->hookArray['additionalButtons'])) {
			foreach ($this->hookArray['additionalButtons'] as $_funcRef) {
				$out .= t3lib_div::callUserFunction($_funcRef, $_params, $this);
			}
		}
		$out .= '</td>
		</tr>
		';
		return $out;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$fN: ...
	 * @param	[type]		$fV: ...
	 * @param	[type]		$conf: ...
	 * @param	[type]		$splitString: ...
	 * @return	[type]		...
	 */
	function getProcessedValueExtra($table, $fN, $fV, $conf, $splitString) {
			// Analysing the fields in the table.
		if (is_array($GLOBALS['TCA'][$table])) {
			t3lib_div::loadTCA($table);
			$fC = $GLOBALS['TCA'][$table]['columns'][$fN];
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
					break;
				}
			} else {
				$fields['label'] = '[FIELD: ' . $fN . ']';
				switch ($fN) {
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
					break;
				}
			}
		}

		switch ($fields['type']) {
			case 'date':
				if ($fV != -1) {
					$out = strftime('%e-%m-%Y', $fV);
				}
			break;
			case 'time':
				if ($fV != -1) {
					if ($splitString == '<br />') {
						$out = strftime('%H:%M' . $splitString . '%e-%m-%Y', $fV);
					} else {
						$out = strftime('%H:%M %e-%m-%Y', $fV);
					}
				}
			break;
			case 'multiple':
			case 'binary':
			case 'relation':
				$out = $this->makeValueList($fN, $fV, $fields, $table, $splitString);
			break;
			case 'boolean':
				$out = $fV ? 'True' : 'False';
			break;
			case 'files':
			default:
				$out = htmlspecialchars($fV);
			break;
		}
		return $out;
	}

	/*
	* [Describe function...]
	*
	* @param	[type]		$qString: ...
	* @param	[type]		$depth: ...
	* @param	[type]		$begin: ...
	* @param	[type]		$perms_clause: ...
	* @return	[type]		...
	*/
	function getTreeList($id, $depth, $begin = 0, $perms_clause) {
		$depth = intval($depth);
		$begin = intval($begin);
		$id = intval($id);
		if ($begin == 0) {
			$theList = $id;
		} else {
			$theList = '';
		}
		if ($id && $depth > 0) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid',
				'pages',
					'pid=' . $id . ' ' . t3lib_BEfunc::deleteClause('pages') . ' AND ' . $perms_clause
			);
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
	 * [Describe function...]
	 *
	 * @param	[type]		$fN: ...
	 * @param	[type]		$fV: ...
	 * @param	[type]		$conf: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$splitString: ...
	 * @return	[type]		...
	 */
	function makeValueList($fN, $fV, $conf, $table, $splitString) {
		$fieldSetup = $conf;
		$out = '';
		if ($fieldSetup['type'] == 'files') {
			$d = dir(PATH_site . $fieldSetup['uploadfolder']);
			while (FALSE !== ($entry = $d->read())) {
				if ($entry == '.' || $entry == '..') {
					continue;
				}
				$fileArray[] = $entry;
			}
			$d->close();
			natcasesort($fileArray);
			while (list(, $fileName) = each($fileArray)) {
				if (t3lib_div::inList($fV, $fileName) || $fV == $fileName) {
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
				if (t3lib_div::inList($fV, $val[1]) || $fV == $val[1]) {
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
					if (t3lib_div::inList($fV, $value) || $fV == $value) {
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
					$checkres = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fN, $table, 'uid ' . t3lib_BEfunc::deleteClause($table), $groupBy = '', $orderBy = '', $limit = '');
					if ($checkres) {
						while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($checkres)) {
							if (stristr($row[$fN], ',')) {
								$checkContent = explode(',', $row[$fN]);
								foreach ($checkContent as $singleValue) {
									if (!stristr($singleValue, '_')) {
										$dontPrefixFirstTable = 1;
									}
								}
							} else {
								$singleValue = $row[$fN];
								if (strlen($singleValue) && !stristr($singleValue, '_')) {
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
				if (($useTablePrefix && !$dontPrefixFirstTable && $counter != 1) || $counter == 1) {
					$tablePrefix = $from_table . '_';
				}
				$counter = 1;
				if (is_array($GLOBALS['TCA'][$from_table])) {
					t3lib_div::loadTCA($from_table);
					$labelField = $GLOBALS['TCA'][$from_table]['ctrl']['label'];
					$altLabelField = $GLOBALS['TCA'][$from_table]['ctrl']['label_alt'];
					if ($GLOBALS['TCA'][$from_table]['columns'][$labelField]['config']['items']) {
						reset($GLOBALS['TCA'][$from_table]['columns'][$labelField]['config']['items']);
						while (list(, $labelArray) = each($GLOBALS['TCA'][$from_table]['columns'][$labelField]['config']['items'])) {
							if (substr($labelArray[0], 0, 4) == 'LLL:') {
								$labelFieldSelect[$labelArray[1]] = $GLOBALS['LANG']->sL($labelArray[0]);
							} else {
								$labelFieldSelect[$labelArray[1]] = $labelArray[0];
							}
						}
						$useSelectLabels = 1;
					}
					if ($GLOBALS['TCA'][$from_table]['columns'][$altLabelField]['config']['items']) {
						reset($GLOBALS['TCA'][$from_table]['columns'][$altLabelField]['config']['items']);
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
							$webMountPageTree .= $webMountPageTreePrefix . $this->getTreeList($val, 999, $begin = 0, $perms_clause);
						}
						if ($from_table == 'pages') {
							$where_clause = 'uid IN (' . $webMountPageTree . ') ' . t3lib_BEfunc::deleteClause($from_table) . ' AND ' . $perms_clause;
						} else {
							$where_clause = 'pid IN (' . $webMountPageTree . ') ' . t3lib_BEfunc::deleteClause($from_table);
						}
					} else {
						$where_clause = 'uid' . t3lib_BEfunc::deleteClause($from_table);
					}
					$orderBy = 'uid';
					if (!$this->tableArray[$from_table]) {
						$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select_fields, $from_table, $where_clause, $groupBy = '', $orderBy, $limit = '');
						$this->tableArray[$from_table] = array();
					}
					if ($res) {
						while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
							$this->tableArray[$from_table][] = $row;
						}
						$GLOBALS['TYPO3_DB']->sql_free_result($res);
					}
					reset($this->tableArray[$from_table]);
					foreach ($this->tableArray[$from_table] as $key => $val) {
						$GLOBALS['SOBE']->MOD_SETTINGS['labels_noprefix'] = $GLOBALS['SOBE']->MOD_SETTINGS['labels_noprefix'] == 1 ? 'on' :
								$GLOBALS['SOBE']->MOD_SETTINGS['labels_noprefix'];
						$prefixString = $GLOBALS['SOBE']->MOD_SETTINGS['labels_noprefix'] == 'on' ? '' : ' [' . $tablePrefix . $val['uid'] . '] ';
						if (t3lib_div::inList($fV, $tablePrefix . $val['uid']) || $fV == $tablePrefix . $val['uid']) {
							if ($useSelectLabels) {
								if (!$out) {
									$out = htmlspecialchars($prefixString . $labelFieldSelect[$val[$labelField]]);
								} else {
									$out .= $splitString . htmlspecialchars($prefixString . $labelFieldSelect[$val[$labelField]]);
								}
							} elseif ($val[$labelField]) {
								if (!$out) {
									$out = htmlspecialchars($prefixString . $val[$labelField]);
								} else {
									$out .= $splitString . htmlspecialchars($prefixString . $val[$labelField]);
								}
							} elseif ($useAltSelectLabels) {
								if (!$out) {
									$out = htmlspecialchars($prefixString . $altLabelFieldSelect[$val[$altLabelField]]);
								} else {
									$out .= $splitString . htmlspecialchars($prefixString . $altLabelFieldSelect[$val[$altLabelField]]);
								}
							} else {
								if (!$out) {
									$out = htmlspecialchars($prefixString . $val[$altLabelField]);
								} else {
									$out .= $splitString . htmlspecialchars($prefixString . $val[$altLabelField]);
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
	 * @param	array		row: Table columns
	 * @param	array		conf: Table TCA
	 * @param	string		table: Table name
	 * @return	string		HTML of table header
	 */
	function resultRowTitles($row, $conf, $table) {
		$SET = $GLOBALS['SOBE']->MOD_SETTINGS;

		$tableHeader = array();

			// Start header row
		$tableHeader[] = '<thead><tr class="bgColor5">';

			// Iterate over given columns
		foreach ($row as $fieldName => $fieldValue) {
			if (t3lib_div::inList($SET['queryFields'], $fieldName) || (!$SET['queryFields'] && $fieldName != 'pid' && $fieldName != 'deleted')) {
				$THparams = (strlen($fieldValue) < 50) ? ' style="white-space:nowrap;"' : '';

				if ($GLOBALS['SOBE']->MOD_SETTINGS['search_result_labels']) {
					$title = $GLOBALS['LANG']->sL($conf['columns'][$fieldName]['label'] ? $conf['columns'][$fieldName]['label'] : $fieldName, 1);
				} else {
					$title = $GLOBALS['LANG']->sL($fieldName, 1);
				}

				$tableHeader[] = '<th' . $THparams . '>' . $title . '</th>';
			}
		}

			// Add empty icon column
		$tableHeader[] = '<th style="white-space:nowrap;"></th>';
			// Close header row
		$tableHeader[] = '</tr></thead>';

		return implode($tableHeader, LF);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$row: ...
	 * @param	[type]		$conf: ...
	 * @param	[type]		$table: ...
	 * @return	[type]		...
	 */
	function csvRowTitles($row, $conf, $table) {
		$out = '';
		$SET = $GLOBALS['SOBE']->MOD_SETTINGS;
		foreach ($row as $fN => $fV) {
			if (t3lib_div::inList($SET['queryFields'], $fN) || (!$SET['queryFields'] && $fN != 'pid')) {
				if (!$out) {
					if ($GLOBALS['SOBE']->MOD_SETTINGS['search_result_labels']) {
						$out = $GLOBALS['LANG']->sL($conf['columns'][$fN]['label'] ? $conf['columns'][$fN]['label'] : $fN, 1);
					} else {
						$out = $GLOBALS['LANG']->sL($fN, 1);
					}
				} else {
					if ($GLOBALS['SOBE']->MOD_SETTINGS['search_result_labels']) {
						$out .= ',' . $GLOBALS['LANG']->sL($conf['columns'][$fN]['label'] ? $conf['columns'][$fN]['label'] : $fN, 1);
					} else {
						$out .= ',' . $GLOBALS['LANG']->sL($fN, 1);
					}
				}
			}
		}
		return $out;
	}

	/**
	 * Sets the current name of the input form.
	 *
	 * @param	string		$formName: The name of the form.
	 * @return	void
	 */
	public function setFormName($formName) {
		$this->formName = trim($formName);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_fullsearch.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_fullsearch.php']);
}
?>
