<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2012 Kasper Skårhøj (kasperYYYY@typo3.com)
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

$GLOBALS['BACK_PATH'] = '';
require_once('init.php');

/**
 * Extension of transfer data class
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class transferData extends t3lib_transferData {
	/**
	 * @var string
	 */
	var $formname = 'loadform';

	/**
	 * @var boolean
	 */
	var $loading = 1;

	/**
	 * Extra for show_item.php:
	 *
	 * @var array
	 */
	var $theRecord = array();

	/**
	 * Register item function.
	 *
	 * @param string $table Table name
	 * @param integer $id Record uid
	 * @param string $field Field name
	 * @param string $content Content string.
	 * @return void
	 */
	function regItem($table, $id, $field, $content) {
		t3lib_div::loadTCA($table);
		$config = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
		switch($config['type']) {
			case 'input':
				if (isset($config['checkbox']) && $content == $config['checkbox']) {
					$content = '';
					break;
				}
				if (t3lib_div::inList($config['eval'], 'date')) {
					$content = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $content);
				}
				break;

			case 'group':
			case 'select':
				break;
		}

		$this->theRecord[$field] = $content;
	}
}

/**
 * Script Class for showing information about an item.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_show_item {
	/**
	 * GET vars:
	 * Record table (or filename)
	 *
	 * @var string
	 */
	var $table;

	/**
	 * Record uid  (or '' when filename)
	 *
	 * @var int
	 */
	var $uid;

	/**
	 * Internal, static:
	 * Page select clause
	 *
	 * @var string
	 */
	var $perms_clause;

	/**
	 * If TRUE, access to element is granted
	 *
	 * @var boolean
	 */
	var $access;

	/**
	 * Which type of element: "file" or "db"
	 *
	 * @var string
	 */
	var $type;

	/**
	 * Document Template Object
	 *
	 * @var template
	 */
	var $doc;

	/**
	 * Internal, dynamic:
	 * Content Accumulation
	 *
	 * @var string
	 */
	var $content;

	/**
	 * For type "db": Set to page record of the parent page of the item set
	 * (if type="db")
	 *
	 * @var array
	 */
	var $pageinfo;

	/**
	 * For type "db": The database record row.
	 *
	 * @var array
	 */
	var $row;

	/**
	 * The fileObject if present
	 *
	 * @var t3lib_file_File
	 */
	protected $fileObject;

	/**
	 * The folder obejct if present
	 *
	 * @var t3lib_file_Folder
	 */
	protected $folderObject;

	/**
	 * Initialization of the class
	 * Will determine if table/uid GET vars are database record or a file and if
	 * the user has access to view information about the item.
	 *
	 * @return void
	 */
	function init() {
			// Setting input variables.
		$this->table = t3lib_div::_GET('table');
		$this->uid = t3lib_div::_GET('uid');

			// Initialize:
		$this->perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
			// Set to TRUE if there is access to the record / file.
		$this->access = FALSE;
			// Sets the type, "db" or "file". If blank, nothing can be shown.
		$this->type = '';

			// Checking if the $table value is really a table and if the user has
			// access to it.
		if (isset($GLOBALS['TCA'][$this->table])) {
			t3lib_div::loadTCA($this->table);
			$this->type = 'db';
			$this->uid = intval($this->uid);

				// Check permissions and uid value:
			if ($this->uid && $GLOBALS['BE_USER']->check('tables_select', $this->table)) {
				if ((string)$this->table == 'pages') {
					$this->pageinfo = t3lib_BEfunc::readPageAccess($this->uid, $this->perms_clause);
					$this->access = (is_array($this->pageinfo) ? 1 : 0);
					$this->row = $this->pageinfo;
				} else {
					$this->row = t3lib_BEfunc::getRecordWSOL($this->table, $this->uid);
					if ($this->row) {
						$this->pageinfo = t3lib_BEfunc::readPageAccess($this->row['pid'], $this->perms_clause);
						$this->access = (is_array($this->pageinfo) ? 1 : 0);
					}
				}

				/** @var $treatData t3lib_transferData */
				$treatData = t3lib_div::makeInstance('t3lib_transferData');
				$treatData->renderRecord($this->table, $this->uid, 0, $this->row);
			}

		} elseif ($this->table == '_FILE' || $this->table == '_FOLDER' || $this->table == 'sys_file') {
			$fileOrFolderObject = t3lib_file_Factory::getInstance()->retrieveFileOrFolderObject($this->uid);

			if ($fileOrFolderObject instanceof t3lib_file_Folder) {
				$this->folderObject = $fileOrFolderObject;
				$this->access = $this->folderObject->checkActionPermission('read');
				$this->type = 'folder';
			} else {
				$this->fileObject = $fileOrFolderObject;
				$this->access = $this->fileObject->checkActionPermission('read');
				$this->type = 'file';
				$this->table = 'sys_file';
				t3lib_div::loadTCA($this->table);

				try {
					$this->row = t3lib_BEfunc::getRecordWSOL($this->table, $this->fileObject->getUid());
				} catch (Exception $e) {
					$this->row = array();
				}
			}
		}

			// Initialize document template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];

			// Starting the page by creating page header stuff:
		$this->content .= $this->doc->startPage(
			$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.viewItem')
		);

		$this->content .= '<h3 class="t3-row-header">' .
			$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.viewItem') . '</h3>';
		$this->content .= $this->doc->spacer(5);
	}

	/**
	 * Main function. Will generate the information to display for the item
	 * set internally.
	 *
	 * @return void
	 */
	function main() {
		if (!$this->access) {
			return;
		}

		$returnLink = t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('returnUrl'));
		$returnLinkTag = $returnLink ? '<a href="' . $returnLink .
			'" class="typo3-goBack">' : '<a href="#" onclick="window.close();">';

			// render type by user func
		$typeRendered = FALSE;
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/show_item.php']['typeRendering'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/show_item.php']['typeRendering'] as $classRef) {
				$typeRenderObj = t3lib_div::getUserObj($classRef);
					// @TODO should have an interface
				if (is_object($typeRenderObj) && method_exists($typeRenderObj, 'isValid')
					&& method_exists($typeRenderObj, 'render')
				) {
					if ($typeRenderObj->isValid($this->type, $this)) {
						$this->content .=  $typeRenderObj->render($this->type, $this);
						$typeRendered = TRUE;
						break;
					}
				}
			}
		}

			// If type was not rendered use default rendering functions
		if (!$typeRendered) {
				// Branch out based on type:
			switch ($this->type) {
				case 'db':
					$this->renderDBInfo();
				break;
				case 'file':
					$this->renderFileInfo($returnLinkTag);
				break;
				case 'folder':
					// @todo: implement an info about a folder
				break;
			}
		}

			// If return Url is set, output link to go back:
		if (t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('returnUrl'))) {
			$this->content = $this->doc->section(
				'',
				$returnLinkTag .
				'<strong>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.goBack', 1) .
				'</strong></a><br /><br />'
			) . $this->content;

			$this->content .= $this->doc->section(
				'',
				'<br />' . $returnLinkTag .
				'<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.goBack', 1) .
				'</strong></a>'
			);
		}
	}

	/**
	 * Main function. Will generate the information to display for the item
	 * set internally.
	 *
	 * @return	void
	 */
	function renderDBInfo() {
			// Print header, path etc:
			// @TODO invalid context menu code in the output
		$code = $this->doc->getHeader($this->table, $this->row, $this->pageinfo['_thePath'], 1) . '<br />';
		$this->content .= $this->doc->section('', $code);
		$tableRows = array();

		$extraFields = array(
			'crdate' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_general.xml:LGL.creationDate', 1),
			'cruser_id' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_general.xml:LGL.creationUserId', 1),
			'tstamp' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_general.xml:LGL.timestamp', 1)
		);

		foreach ($extraFields as $name => $value) {
			$rowValue = t3lib_BEfunc::getProcessedValueExtra($this->table, $name, $this->row[$name]);

			if ($name === 'cruser_id' && $rowValue) {
				$userTemp = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'username, realName',
					'be_users',
					'uid = ' . intval($rowValue)
				);

				if ($userTemp[0]['username'] !== '') {
					$rowValue = $userTemp[0]['username'];
					if ($userTemp[0]['realName'] !== '') {
						$rowValue .= ' - '.$userTemp[0]['realName'];
					}
				}
			}

			$tableRows[] = '
				<tr>
					<td class="t3-col-header">' . $value . '</td>
					<td>' . htmlspecialchars($rowValue) . '</td>
				</tr>';
		}

			// Traverse the list of fields to display for the record:
		$fieldList = t3lib_div::trimExplode(',', $GLOBALS['TCA'][$this->table]['interface']['showRecordFieldList'], 1);
		foreach ($fieldList as $name) {
			$name = trim($name);
			if (!isset($GLOBALS['TCA'][$this->table]['columns'][$name])) {
				continue;
			}

			$isExcluded = !(!$GLOBALS['TCA'][$this->table]['columns'][$name]['exclude'] ||
				$GLOBALS['BE_USER']->check('non_exclude_fields', $this->table . ':' . $name));
			if ($isExcluded) {
				continue;
			}

			$uid = $this->row['uid'];
			$itemValue = t3lib_BEfunc::getProcessedValue($this->table, $name, $this->row[$name], 0, 0, FALSE, $uid);
			$itemLabel = $GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel($this->table, $name), 1);
			$tableRows[] = '
				<tr>
					<td class="t3-col-header">' . $itemLabel . '</td>
					<td>' . htmlspecialchars($itemValue) . '</td>
				</tr>';
		}

			// Create table from the information:
		$tableCode = '
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-showitem" class="t3-table-info">
				'.implode('', $tableRows).'
			</table>';
		$this->content .= $this->doc->section('', $tableCode);

			// Add path and table information in the bottom:
		$code = '';
		$code .= $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.path') . ': ' .
			t3lib_div::fixed_lgd_cs($this->pageinfo['_thePath'], -48) . '<br />';
		$code .= $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.table') . ': ' .
			$GLOBALS['LANG']->sL($GLOBALS['TCA'][$this->table]['ctrl']['title']) .
			' (' . $this->table . ') - UID: ' . $this->uid . '<br />';
		$this->content .= $this->doc->section('', $code);

			// References:
		$this->content .= $this->doc->section(
			$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.referencesToThisItem'),
			$this->makeRef($this->table, $this->row['uid'])
		);

		$this->content .= $this->doc->section(
			$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.referencesFromThisItem'),
			$this->makeRefFrom($this->table, $this->row['uid'])
		);
	}

	/**
	 * Main function. Will generate the information to display for the item
	 * set internally.
	 *
	 * @param string $returnLinkTag <a> tag closing/returning.
	 * @return void
	 */
	function renderFileInfo($returnLinkTag) {
		$fileExtension = $this->fileObject->getExtension();
		$code = '<div class="fileInfoContainer">' .
			t3lib_iconWorks::getSpriteIconForFile($fileExtension) .
			'<strong>' .
				$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.file', TRUE) .
			':</strong> ' . $this->fileObject->getName() .
			'&nbsp;&nbsp;' .
			'<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.filesize') .
			':</strong> ' .
			t3lib_div::formatSize($this->fileObject->getSize()) .
			'</div>
			';
		$this->content .= $this->doc->section('', $code);
		$this->content .= $this->doc->divider(2);

			// If the file was an image...
			// @todo: add this check in the domain model, or in the processing folder
		if (t3lib_div::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileExtension)) {
				// @todo: find a way to make getimagesize part of the t3lib_file object
			$imgInfo = @getimagesize($this->fileObject->getForLocalProcessing(FALSE));

			$thumbUrl = $this->fileObject->process(
				t3lib_file_ProcessedFile::CONTEXT_IMAGEPREVIEW,
				array('width' => '150m', 'height' => '150m')
			)->getPublicUrl(TRUE);

			$code = '<div class="fileInfoContainer fileDimensions">' .
				'<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.dimensions') .
				':</strong> ' . $imgInfo[0] . 'x' . $imgInfo[1] . ' ' .
				$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.pixels') . '</div>';

			$code .= '<br />
				<div align="center">' . $returnLinkTag . '<img src="' . $thumbUrl . '" alt="' .
				htmlspecialchars(trim($this->fileObject->getName())) . '" title="' .
				htmlspecialchars(trim($this->fileObject->getName())) . '" /></a></div>';
			$this->content .= $this->doc->section('', $code);

		} elseif ($fileExtension == 'ttf') {
			$thumbUrl = $this->fileObject->process(
				t3lib_file_ProcessedFile::CONTEXT_IMAGEPREVIEW,
				array('width' => '530m', 'height' => '600m')
			)->getPublicUrl(TRUE);

			$thumb = '<br />
				<div align="center">' . $returnLinkTag . '<img src="' . $thumbUrl . '" border="0" title="' .
				htmlspecialchars(trim($this->fileObject->getName())) . '" alt="" /></a></div>';
			$this->content .= $this->doc->section('', $thumb);
		}

			// Traverse the list of fields to display for the record:
		$tableRows = array();
		$showRecordFieldList = $GLOBALS['TCA'][$this->table]['interface']['showRecordFieldList'];
		$fieldList = t3lib_div::trimExplode(',', $showRecordFieldList, TRUE);
		foreach ($fieldList as $name) {
			$name = trim($name);
			if (!isset($GLOBALS['TCA'][$this->table]['columns'][$name])) {
				continue;
			}

			$isExcluded = !(!$GLOBALS['TCA'][$this->table]['columns'][$name]['exclude'] ||
				$GLOBALS['BE_USER']->check('non_exclude_fields', $this->table . ':' . $name));
			if ($isExcluded) {
				continue;
			}

			$uid = $this->row['uid'];
			$itemValue = t3lib_BEfunc::getProcessedValue($this->table, $name, $this->row[$name], 0, 0, FALSE, $uid);
			$itemLabel = $GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel($this->table, $name), 1);
			$tableRows[] = '
				<tr>
					<td class="t3-col-header">' . $itemLabel . '</td>
					<td>' . htmlspecialchars($itemValue) . '</td>
				</tr>';
		}

			// Create table from the information:
		$tableCode = '
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-showitem" class="t3-table-info">
				' . implode('', $tableRows) . '
			</table>';
		$this->content .= $this->doc->section('', $tableCode);

			// References:
		if ($this->fileObject->isIndexed()) {
			$header = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.referencesToThisItem');
			$this->content .= $this->doc->section($header, $this->makeRef('_FILE', $this->fileObject));
		}
	}

	/**
	 * End page and print content
	 *
	 * @return void
	 */
	function printContent() {
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}

	/**
	 * Get table field name
	 *
	 * @param string $tableName Table name
	 * @param string $fieldName Field name
	 * @return string Field name
	 */
	public function getFieldName($tableName, $fieldName) {
		t3lib_div::loadTCA($tableName);
		if ($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['label'] !== NULL) {
			$field = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['label']);
			if (trim($field) === '') {
				$field = $fieldName;
			}
		} else {
			$field = $fieldName;
		}

		return $field;
	}

	/**
	 * Returns the rendered record actions
	 *
	 * @param string $table
	 * @param integer $uid
	 * @return string
	 */
	protected function getRecordActions($table, $uid) {
		if ($table === '' || $uid < 0) {
			return '';
		}

		$editOnClick = t3lib_BEfunc::editOnClick('&edit[' . $table . '][' . $uid . ']=edit', $GLOBALS['BACK_PATH']);
		$icon = t3lib_iconWorks::getSpriteIcon('actions-document-open');
		$pageActionIcons = '<a href="#" onclick="' . htmlspecialchars($editOnClick) . '">' . $icon . '</a>';

		$historyOnClick = 'window.location.href=\'show_rechis.php?element=' . $table . '%3A' . $uid .
			'&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI')) . '\'; return false;';
		$icon = t3lib_iconWorks::getSpriteIcon('actions-document-history-open');
		$pageActionIcons .= '<a href="#" onclick="' . $historyOnClick . '">' . $icon . '</a>';

		if ($table === 'pages') {
			$pageActionIcons .= $this->doc->viewPageIcon($uid, '');
		}

		return $pageActionIcons;
	}

	/**
	 * Make reference display
	 *
	 * @param string $table Table name
	 * @param string|t3lib_file_File $ref Filename or uid
	 * @return string HTML
	 */
	function makeRef($table, $ref) {
			// Look up the path:
		if ($table === '_FILE') {
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*',
				'sys_file_reference',
				'uid_local=' . $ref->getUid()
			);
		} else {
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*',
				'sys_refindex',
				'ref_table=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($table, 'sys_refindex') .
					' AND ref_uid=' . intval($ref) . ' AND deleted=0'
			);
		}
			// Compile information for title tag:
		$infoData = array();
		if (count($rows)) {
			$infoData[] = '<tr class="t3-row-header">' .
				'<td>&nbsp;</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.table') . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.title') . '</td>' .
				'<td>[uid]</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.field') . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.flexpointer') . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.softrefKey') . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.sorting') . '</td>' .
				'</tr>';
		}

		foreach ($rows as $row) {
			if($table === '_FILE') {
				$row = $this->mapFileReferenceOnRefIndex($row);
			}

			$record = t3lib_BEfunc::getRecord($row['tablename'], $row['recuid']);
			$parentRecord = t3lib_BEfunc::getRecord('pages', $record['pid']);
			$actions = $this->getRecordActions($row['tablename'], $row['recuid']);
			$infoData[] = '<tr class="bgColor4">' .
				'<td style="white-space:nowrap;">' . $actions . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL($GLOBALS['TCA'][$row['tablename']]['ctrl']['title'], TRUE) . '</td>' .
				'<td>' . t3lib_BEfunc::getRecordTitle($row['tablename'], $record, TRUE) . '</td>' .
				'<td><span title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:page') . ': ' .
				htmlspecialchars(t3lib_BEfunc::getRecordTitle('pages', $parentRecord)) .
				' (uid=' . $record['pid'] . ')">' . $record['uid'] . '</span></td>' .
				'<td>' . htmlspecialchars($this->getFieldName($row['tablename'], $row['field'])) . '</td>' .
				'<td>' . htmlspecialchars($row['flexpointer']) . '</td>' .
				'<td>' . htmlspecialchars($row['softref_key']) . '</td>' .
				'<td>' . htmlspecialchars($row['sorting']) . '</td>' .
				'</tr>';
		}

		$referenceLine = '';
		if (count($infoData)) {
			$referenceLine = '<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">' .
				implode('', $infoData) . '</table>';
		}

		return $referenceLine;
	}

	/**
	 * Maps results from the fal file reference table on the
	 * structure of  the normal reference index table.
	 *
	 * @param array $fileReference
	 * @return array
	 */
	protected function mapFileReferenceOnRefIndex(array $fileReference) {
		return array(
			'recuid' => $fileReference['uid_foreign'],
			'tablename' => $fileReference['tablenames'],
			'field' => $fileReference['fieldname'],
			'flexpointer' => '',
			'softref_key' => '',
			'sorting' => $fileReference['sorting_foreign']
		);
	}

	/**
	 * Make reference display (what this elements points to)
	 *
	 * @param string $table Table name
	 * @param string $ref Filename or uid
	 * @return string HTML
	 */
	function makeRefFrom($table, $ref) {
			// Look up the path:
			// @TODO files not respected (see makeRef)
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'sys_refindex',
			'tablename=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($table, 'sys_refindex') .
				' AND recuid=' . intval($ref)
		);

			// Compile information for title tag:
		$infoData = array();
		if (count($rows)) {
			$infoData[] = '<tr class="t3-row-header">' .
				'<td>&nbsp;</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.field') . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.flexpointer') . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.softrefKey') . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.sorting') . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.refTable') . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.refUid') . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.refString') . '</td>' .
				'</tr>';
		}

		foreach ($rows as $row) {
			$actions = $this->getRecordActions($row['ref_table'], $row['ref_uid']);
			$infoData[] = '<tr class="bgColor4">' .
				'<td style="white-space:nowrap;">' . $actions . '</td>' .
				'<td>' . htmlspecialchars($this->getFieldName($table, $row['field'])) . '</td>' .
				'<td>' . htmlspecialchars($row['flexpointer']) . '</td>' .
				'<td>' . htmlspecialchars($row['softref_key']) . '</td>' .
				'<td>' . htmlspecialchars($row['sorting']) . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL($GLOBALS['TCA'][$row['ref_table']]['ctrl']['title'], TRUE) . '</td>' .
				'<td>' . htmlspecialchars($row['ref_uid']) . '</td>' .
				'<td>' . htmlspecialchars($row['ref_string']) . '</td>' .
				'</tr>';
		}

		$referenceLine = '';
		if (count($infoData)) {
			$referenceLine = '<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">' .
				implode('', $infoData) . '</table>';
		}

		return $referenceLine;
	}
}

/** @var $SOBE SC_show_item */
$SOBE = t3lib_div::makeInstance('SC_show_item');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>