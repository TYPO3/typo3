<?php
namespace TYPO3\CMS\IndexedSearch\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Backend module providing boring statistics of the index-tables.
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ModuleController {

	/**
	 * @todo Define visibility
	 */
	public $MCONF = array();

	/**
	 * @todo Define visibility
	 */
	public $MOD_MENU = array();

	/**
	 * @todo Define visibility
	 */
	public $MOD_SETTINGS = array();

	/**
	 * document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * @todo Define visibility
	 */
	public $doc;

	/**
	 * @todo Define visibility
	 */
	public $include_once = array();

	/**
	 * @todo Define visibility
	 */
	public $content;

	/**
	 * Initialization
	 *
	 * @return 	void
	 * @todo Define visibility
	 */
	public function init() {
		$this->MCONF = $GLOBALS['MCONF'];
		$this->menuConfig();
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->form = '<form action="" method="post">';
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('indexed_search') . '/mod/mod_template.html');
		// JavaScript
		$this->doc->JScodeArray['indexed_search'] = '
			script_ended = 0;
			function jumpToUrl(URL) {
				window.location.href = URL;
			}';
		$this->doc->tableLayout = array(
			'defRow' => array(
				'0' => array('<td valign="top" nowrap>', '</td>'),
				'defCol' => array('<td><img src="' . $this->doc->backPath . 'clear.gif" width=10 height=1></td><td valign="top" nowrap>', '</td>')
			)
		);
		$indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\IndexedSearch\\Indexer');
		$indexer->initializeExternalParsers();
	}

	/**
	 * MENU-ITEMS:
	 * If array, then it's a selector box menu
	 * If empty string it's just a variable, that'll be saved.
	 * Values NOT in this array will not be saved in the settings-array for the module.
	 *
	 * @return 	void
	 * @todo Define visibility
	 */
	public function menuConfig() {
		$this->MOD_MENU = array(
			'function' => array(
				'stat' => 'General statistics',
				'typo3pages' => 'List: TYPO3 Pages',
				'externalDocs' => 'List: External documents'
			)
		);
		// cleanse settings
		$this->MOD_SETTINGS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData($this->MOD_MENU, \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SET'), $this->MCONF['name'], 'ses');
	}

	/**
	 * Main function to generate the content
	 *
	 * @return 	void
	 * @todo Define visibility
	 */
	public function main() {
		$this->content = $this->doc->header('Indexing Engine Statistics');
		$this->content .= $this->doc->spacer(5);
		switch ($this->MOD_SETTINGS['function']) {
		case 'stat':
			$this->content .= $this->doc->section('Records', $this->doc->table($this->getRecordsNumbers()), 0, 1);
			$this->content .= $this->doc->spacer(15);
			$this->content .= $this->doc->section('index_phash TYPES', $this->doc->table($this->getPhashTypes()), 1);
			$this->content .= $this->doc->spacer(15);
			break;
		case 'externalDocs':
			$this->content .= $this->doc->section('External documents', $this->doc->table($this->getPhashExternalDocs()), 0, 1);
			$this->content .= $this->doc->spacer(15);
			break;
		case 'typo3pages':
			$this->content .= $this->doc->section('TYPO3 Pages', $this->doc->table($this->getPhashT3pages()), 0, 1);
			$this->content .= $this->doc->spacer(15);
			break;
		}
		$docHeaderButtons = $this->getButtons();
		$markers = array(
			'CSH' => $docHeaderButtons['csh'],
			'FUNC_MENU' => \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu(0, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']),
			'CONTENT' => $this->content
		);
		$this->content = $this->doc->startPage('Indexing Engine Statistics');
		$this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Print content
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array all available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'csh' => '',
			'shortcut' => ''
		);
		// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('', 'function', $this->MCONF['name']);
		}
		return $buttons;
	}

	/***************************
	 *
	 * OTHER FUNCTIONS:
	 *
	 ***************************/
	/**
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function getRecordsNumbers() {
		$tables = explode(',', 'index_phash,index_words,index_rel,index_grlist,index_section,index_fulltext');
		$recList = array();
		foreach ($tables as $t) {
			$recList[] = array(
				$this->tableHead($t),
				$GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', $t)
			);
		}
		return $recList;
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$str: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function tableHead($str) {
		return '<strong>' . $str . ':&nbsp;&nbsp;&nbsp;</strong>';
	}

	/**
	 * [Describe function...]
	 *
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function getPhashStat() {
		$recList = array();
		// TYPO3 pages, unique
		$items = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*),phash', 'index_phash', 'data_page_id<>0', 'phash_grouping,pcount,phash');
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res)) {
			$items[] = $row;
		}
		$recList[] = array($this->tableHead('TYPO3 pages'), count($items));
		// TYPO3 pages:
		$recList[] = array(
			$this->tableHead('TYPO3 pages, raw'),
			$GLOBALS['TYPO3_DB']->exec_SELECTcountRows('phash', 'index_phash', 'data_page_id<>0')
		);
		// External files, unique
		$items = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*),phash', 'index_phash', 'data_filename<>\'\'', 'phash_grouping');
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		$recList[] = array($this->tableHead('External files'), $row[0]);
		// External files
		$recList[] = array(
			$this->tableHead('External files, raw'),
			$GLOBALS['TYPO3_DB']->exec_SELECTcountRows('phash', 'index_phash', 'data_filename<>\'\'')
		);
		return $recList;
	}

	/**
	 * [Describe function...]
	 *
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function getPhashT3pages() {
		$recList[] = array(
			$this->tableHead('id/type'),
			$this->tableHead('Title'),
			$this->tableHead('Size'),
			$this->tableHead('Words'),
			$this->tableHead('mtime'),
			$this->tableHead('Indexed'),
			$this->tableHead('Updated'),
			$this->tableHead('Parsetime'),
			$this->tableHead('#sec/gr/full'),
			$this->tableHead('#sub'),
			$this->tableHead('Lang'),
			$this->tableHead('cHash'),
			$this->tableHead('phash')
		);
		// TYPO3 pages, unique
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*) AS pcount,index_phash.*', 'index_phash', 'data_page_id<>0', 'phash_grouping,phash,cHashParams,data_filename,data_page_id,data_page_reg1,data_page_type,data_page_mp,gr_list,item_type,item_title,item_description,item_mtime,tstamp,item_size,contentHash,crdate,parsetime,sys_language_uid,item_crdate,externalUrl,recordUid,freeIndexUid,freeIndexSetId', 'data_page_id');
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$cHash = count(unserialize($row['cHashParams'])) ? $this->formatCHash(unserialize($row['cHashParams'])) : '';
			$grListRec = $this->getGrlistRecord($row['phash']);
			$recList[] = array(
				$row['data_page_id'] . ($row['data_page_type'] ? '/' . $row['data_page_type'] : ''),
				htmlentities(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($row['item_title'], 30)),
				\TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($row['item_size']),
				$this->getNumberOfWords($row['phash']),
				\TYPO3\CMS\Backend\Utility\BackendUtility::datetime($row['item_mtime']),
				\TYPO3\CMS\Backend\Utility\BackendUtility::datetime($row['crdate']),
				$row['tstamp'] != $row['crdate'] ? \TYPO3\CMS\Backend\Utility\BackendUtility::datetime($row['tstamp']) : '',
				$row['parsetime'],
				$this->getNumberOfSections($row['phash']) . '/' . $grListRec[0]['pcount'] . '/' . $this->getNumberOfFulltext($row['phash']),
				$row['pcount'] . '/' . $this->formatFeGroup($grListRec),
				$row['sys_language_uid'],
				$cHash,
				$row['phash']
			);
			if ($row['pcount'] > 1) {
				$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('index_phash.*', 'index_phash', 'phash_grouping=' . intval($row['phash_grouping']) . ' AND phash<>' . intval($row['phash']));
				while ($row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2)) {
					$grListRec = $this->getGrlistRecord($row2['phash']);
					$recList[] = array(
						'',
						'',
						\TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($row2['item_size']),
						$this->getNumberOfWords($row2['phash']),
						\TYPO3\CMS\Backend\Utility\BackendUtility::datetime($row2['item_mtime']),
						\TYPO3\CMS\Backend\Utility\BackendUtility::datetime($row2['crdate']),
						$row2['tstamp'] != $row2['crdate'] ? \TYPO3\CMS\Backend\Utility\BackendUtility::datetime($row2['tstamp']) : '',
						$row2['parsetime'],
						$this->getNumberOfSections($row2['phash']) . '/' . $grListRec[0]['pcount'] . '/' . $this->getNumberOfFulltext($row2['phash']),
						'-/' . $this->formatFeGroup($grListRec),
						'',
						'',
						$row2['phash']
					);
				}
			}
		}
		return $recList;
	}

	/**
	 * [Describe function...]
	 *
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function getPhashExternalDocs() {
		$recList[] = array(
			$this->tableHead('Filename'),
			$this->tableHead('Size'),
			$this->tableHead('Words'),
			$this->tableHead('mtime'),
			$this->tableHead('Indexed'),
			$this->tableHead('Updated'),
			$this->tableHead('Parsetime'),
			$this->tableHead('#sec/gr/full'),
			$this->tableHead('#sub'),
			$this->tableHead('cHash'),
			$this->tableHead('phash'),
			$this->tableHead('Path')
		);
		// TYPO3 pages, unique
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*) AS pcount,index_phash.*', 'index_phash', 'item_type<>\'0\'', 'phash_grouping,phash,cHashParams,data_filename,data_page_id,data_page_reg1,data_page_type,data_page_mp,gr_list,item_type,item_title,item_description,item_mtime,tstamp,item_size,contentHash,crdate,parsetime,sys_language_uid,item_crdate,externalUrl,recordUid,freeIndexUid,freeIndexSetId', 'item_type');
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$cHash = count(unserialize($row['cHashParams'])) ? $this->formatCHash(unserialize($row['cHashParams'])) : '';
			$grListRec = $this->getGrlistRecord($row['phash']);
			$recList[] = array(
				htmlentities(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($row['item_title'], 30)),
				\TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($row['item_size']),
				$this->getNumberOfWords($row['phash']),
				\TYPO3\CMS\Backend\Utility\BackendUtility::datetime($row['item_mtime']),
				\TYPO3\CMS\Backend\Utility\BackendUtility::datetime($row['crdate']),
				$row['tstamp'] != $row['crdate'] ? \TYPO3\CMS\Backend\Utility\BackendUtility::datetime($row['tstamp']) : '',
				$row['parsetime'],
				$this->getNumberOfSections($row['phash']) . '/' . $grListRec[0]['pcount'] . '/' . $this->getNumberOfFulltext($row['phash']),
				$row['pcount'],
				$cHash,
				$row['phash'],
				htmlentities(\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($row['data_filename'], 100))
			);
			if ($row['pcount'] > 1) {
				$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('index_phash.*', 'index_phash', 'phash_grouping=' . intval($row['phash_grouping']) . ' AND phash<>' . intval($row['phash']));
				while ($row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2)) {
					$cHash = count(unserialize($row2['cHashParams'])) ? $this->formatCHash(unserialize($row2['cHashParams'])) : '';
					$grListRec = $this->getGrlistRecord($row2['phash']);
					$recList[] = array(
						'',
						'',
						$this->getNumberOfWords($row2['phash']),
						'',
						\TYPO3\CMS\Backend\Utility\BackendUtility::datetime($row2['crdate']),
						$row2['tstamp'] != $row2['crdate'] ? \TYPO3\CMS\Backend\Utility\BackendUtility::datetime($row2['tstamp']) : '',
						$row2['parsetime'],
						$this->getNumberOfSections($row2['phash']) . '/' . $grListRec[0]['pcount'] . '/' . $this->getNumberOfFulltext($row2['phash']),
						'',
						$cHash,
						$row2['phash'],
						''
					);
				}
			}
		}
		return $recList;
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$fegroup_recs: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function formatFeGroup($fegroup_recs) {
		$str = array();
		foreach ($fegroup_recs as $row) {
			$str[] = $row['gr_list'] == '0,-1' ? 'NL' : $row['gr_list'];
		}
		arsort($str);
		return implode('|', $str);
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$arr: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function formatCHash($arr) {
		$list = array();
		foreach ($arr as $k => $v) {
			$list[] = htmlspecialchars($k) . '=' . htmlspecialchars($v);
		}
		return implode('<br />', $list);
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$phash: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function getNumberOfSections($phash) {
		return $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('phash', 'index_section', 'phash=' . intval($phash));
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$phash: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function getNumberOfWords($phash) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)', 'index_rel', 'phash=' . intval($phash));
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		return $row[0];
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$phash: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function getGrlistRecord($phash) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('index_grlist.*', 'index_grlist', 'phash=' . intval($phash));
		$allRows = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$row['pcount'] = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
			$allRows[] = $row;
		}
		return $allRows;
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$phash: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function getNumberOfFulltext($phash) {
		return $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('phash', 'index_fulltext', 'phash=' . intval($phash));
	}

	/**
	 * [Describe function...]
	 *
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function getPhashTypes() {
		$recList = array();
		// Types:
		$Itypes = array(
			'html' => 1,
			'htm' => 1,
			'pdf' => 2,
			'doc' => 3,
			'txt' => 4
		);
		$revTypes = array_flip($Itypes);
		$revTypes[0] = 'TYPO3 page';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*),item_type', 'index_phash', '', 'item_type', 'item_type');
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res)) {
			$iT = $row[1];
			$recList[] = array($this->tableHead($revTypes[$iT] . ' (' . $iT . ')'), $this->countUniqueTypes($iT) . '/' . $row[0]);
		}
		return $recList;
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$item_type: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function countUniqueTypes($item_type) {
		// TYPO3 pages, unique
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)', 'index_phash', 'item_type=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($item_type, 'index_phash'), 'phash_grouping');
		$items = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res)) {
			$items[] = $row;
		}
		return count($items);
	}

}


?>