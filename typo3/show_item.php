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
 * Shows information about a database or file item
 *
 * Revised for TYPO3 3.7 May/2004 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */


$GLOBALS['BACK_PATH'] = '';
require_once('init.php');
require_once('template.php');












/**
 * Extension of transfer data class
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class transferData extends t3lib_transferData	{

	var $formname = 'loadform';
	var $loading = 1;

		// Extra for show_item.php:
	var $theRecord = Array();

	/**
	 * Register item function.
	 *
	 * @param	string		Table name
	 * @param	integer		Record uid
	 * @param	string		Field name
	 * @param	string		Content string.
	 * @return	void
	 */
	function regItem($table, $id, $field, $content)	{
		t3lib_div::loadTCA($table);
		$config = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
		switch($config['type'])	{
			case 'input':
				if (isset($config['checkbox']) && $content == $config['checkbox']) {
					$content = '';
					break;
				}
				if (t3lib_div::inList($config['eval'],'date')) {
					$content = Date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $content);
				}
			break;
			case 'group':
			break;
			case 'select':
			break;
		}
		$this->theRecord[$field]=$content;
	}
}











/**
 * Script Class for showing information about an item.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_show_item {

		// GET vars:
	var $table;			// Record table (or filename)
	var $uid;			// Record uid  (or '' when filename)

		// Internal, static:
	var $perms_clause;	// Page select clause
	var $access;		// If TRUE, access to element is granted
	var $type;			// Which type of element: "file" or "db"
	var $doc;			// Document Template Object

		// Internal, dynamic:
	var $content;		// Content Accumulation
	var $pageinfo;		// For type "db": Set to page record of the parent page of the item set (if type="db")
	var $row;			// For type "db": The database record row.

	/**
	 * The fileObject if present
	 *
	 * @var t3lib_file_AbstractFile
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
	 * Will determine if table/uid GET vars are database record or a file and if the user has access to view information about the item.
	 *
	 * @return	void
	 */
	function init() {
			// Setting input variables.
		$this->table = t3lib_div::_GET('table');
		$this->uid = t3lib_div::_GET('uid');

			// Initialize:
		$this->perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
		$this->access = FALSE;	// Set to TRUE if there is access to the record / file.
		$this->type = '';	// Sets the type, "db" or "file". If blank, nothing can be shown.

			// Checking if the $table value is really a table and if the user has access to it.
		if (isset($GLOBALS['TCA'][$this->table])) {
			t3lib_div::loadTCA($this->table);
			$this->type = 'db';
			$this->uid = intval($this->uid);

				// Check permissions and uid value:
			if ($this->uid && $GLOBALS['BE_USER']->check('tables_select',$this->table)) {
				if ((string)$this->table == 'pages') {
					$this->pageinfo = t3lib_BEfunc::readPageAccess($this->uid,$this->perms_clause);
					$this->access = is_array($this->pageinfo) ? 1 : 0;
					$this->row = $this->pageinfo;
				} else {
					$this->row = t3lib_BEfunc::getRecordWSOL($this->table, $this->uid);
					if ($this->row)	{
						$this->pageinfo = t3lib_BEfunc::readPageAccess($this->row['pid'],$this->perms_clause);
						$this->access = is_array($this->pageinfo) ? 1 : 0;
					}
				}

				$treatData = t3lib_div::makeInstance('t3lib_transferData');
				$treatData->renderRecord($this->table, $this->uid, 0, $this->row);
				$cRow = $treatData->theRecord;
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
		$this->content.=$this->doc->startPage($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.viewItem'));
		$this->content.='<h3 class="t3-row-header">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.viewItem') . '</h3>';
		$this->content.=$this->doc->spacer(5);
	}

	/**
	 * Main function. Will generate the information to display for the item set internally.
	 *
	 * @return	void
	 */
	function main() {

		if ($this->access) {
			$returnLink =  t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('returnUrl'));
			$returnLinkTag = $returnLink ? '<a href="' . $returnLink . '" class="typo3-goBack">' : '<a href="#" onclick="window.close();">';
				// render type by user func
			$typeRendered = FALSE;
			if (is_array ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/show_item.php']['typeRendering'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/show_item.php']['typeRendering'] as $classRef) {
					$typeRenderObj = t3lib_div::getUserObj($classRef);
					if (is_object($typeRenderObj) && method_exists($typeRenderObj, 'isValid') && method_exists($typeRenderObj, 'render')) {
						if ($typeRenderObj->isValid($this->type, $this)) {
							$this->content .=  $typeRenderObj->render($this->type, $this);
							$typeRendered = TRUE;
							break;
						}
					}
				}
			}

				// if type was not rendered use default rendering functions
			if(!$typeRendered) {
					// Branch out based on type:
				switch ($this->type) {
					case 'db':
						$this->renderDBInfo();
					break;
					case 'file':
						$this->renderFileInfo($returnLinkTag);
					break;
					case 'folder':
						// @todo: implement a info about a folder
					break;
				}
			}

				// If return Url is set, output link to go back:
			if (t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('returnUrl')))	{
				$this->content = $this->doc->section('',$returnLinkTag.'<strong>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.goBack',1).'</strong></a><br /><br />').$this->content;

				$this->content .= $this->doc->section('','<br />'.$returnLinkTag.'<strong>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.goBack',1).'</strong></a>');
			}
		}
	}

	/**
	 * Main function. Will generate the information to display for the item set internally.
	 *
	 * @return	void
	 */
	function renderDBInfo() {

			// Print header, path etc:
		$code = $this->doc->getHeader($this->table,$this->row,$this->pageinfo['_thePath'],1).'<br />';
		$this->content.= $this->doc->section('',$code);

			// Initialize variables:
		$tableRows = Array();
		$i = 0;

			// Traverse the list of fields to display for the record:
		$fieldList = t3lib_div::trimExplode(',', $GLOBALS['TCA'][$this->table]['interface']['showRecordFieldList'], 1);
		foreach ($fieldList as $name) {
			$name = trim($name);
			if ($GLOBALS['TCA'][$this->table]['columns'][$name]) {
				if (!$GLOBALS['TCA'][$this->table]['columns'][$name]['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields', $this->table . ':' . $name)) {
					$i++;
					$tableRows[] = '
						<tr>
							<td class="t3-col-header">' . $GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel($this->table, $name), 1) . '</td>
							<td>' . htmlspecialchars(t3lib_BEfunc::getProcessedValue($this->table, $name, $this->row[$name], 0, 0, FALSE, $this->row['uid'])) . '</td>
						</tr>';
				}
			}
		}

			// Create table from the information:
		$tableCode = '
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-showitem" class="t3-table-info">
						'.implode('',$tableRows).'
					</table>';
		$this->content.=$this->doc->section('',$tableCode);

			// Add path and table information in the bottom:
		$code = '';
		$code .= $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.path') . ': ' . t3lib_div::fixed_lgd_cs($this->pageinfo['_thePath'], -48) . '<br />';
		$code .= $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.table') . ': ' . $GLOBALS['LANG']->sL($GLOBALS['TCA'][$this->table]['ctrl']['title']) . ' (' . $this->table . ') - UID: ' . $this->uid . '<br />';
		$this->content.= $this->doc->section('', $code);

			// References:
		$this->content.= $this->doc->section($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.referencesToThisItem'),$this->makeRef($this->table,$this->row['uid']));

			// References:
		$this->content.= $this->doc->section($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.referencesFromThisItem'),$this->makeRefFrom($this->table,$this->row['uid']));
	}

	/**
	 * Main function. Will generate the information to display for the item set internally.
	 *
	 * @param string $returnLinkTag <a> tag closing/returning.
	 * @return void
	 */
	function renderFileInfo($returnLinkTag) {
		$fileExtension = $this->fileObject->getExtension();
			// Setting header:
		$code = '<div class="fileInfoContainer">'
			. t3lib_iconWorks::getSpriteIconForFile($fileExtension) . '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.file', TRUE) . ':</strong> ' . $this->fileObject->getName()
			. '&nbsp;&nbsp;'
			. '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.filesize') . ':</strong> '
			. t3lib_div::formatSize($this->fileObject->getSize())
			. '</div>
			';
		$this->content .= $this->doc->section('', $code);
		$this->content .= $this->doc->divider(2);

			// If the file was an image...
			// @todo: add this check in the domain model in some kind of way, or in the processing folder
		if (t3lib_div::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileExtension)) {
				// @todo: find a way to make getimagesize part of the t3lib_file object
			$imgInfo = @getimagesize($this->fileObject->getForLocalProcessing(FALSE));

			$thumbUrl = $this->fileObject->process(
				t3lib_file_ProcessedFile::CONTEXT_IMAGEPREVIEW,
				array('width' => '150m', 'height' => '150m')
			)->getPublicUrl(TRUE);
			$code = '<div class="fileInfoContainer fileDimensions">'
					. '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.dimensions')
					. ':</strong> ' . $imgInfo[0] . 'x' . $imgInfo[1] . ' '
					. $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.pixels') . '</div>';
			$code .= '<br />
				<div align="center">' . $returnLinkTag . '<img src="' . $thumbUrl . '" alt="' . htmlspecialchars(trim($this->fileObject->getName())) . '" title="' . htmlspecialchars(trim($this->fileObject->getName())) . '" /></a></div>';
			$this->content .= $this->doc->section('', $code);
		} elseif ($fileExtension == 'ttf') {
			$thumbUrl = $this->fileObject->process(
				t3lib_file_ProcessedFile::CONTEXT_IMAGEPREVIEW,
				array('width' => '530m', 'height' => '600m')
			)->getPublicUrl(TRUE);
			$thumb = '<br />
				<div align="center">' . $returnLinkTag . '<img src="' . $thumbUrl . '" border="0" title="' . htmlspecialchars(trim($this->fileObject->getName())) . '" alt="" /></a></div>';
			$this->content .= $this->doc->section('', $thumb);
		}

					// Initialize variables:
		$tableRows = array();
		$i = 0;

			// Traverse the list of fields to display for the record:
		$fieldList = t3lib_div::trimExplode(',', $GLOBALS['TCA'][$this->table]['interface']['showRecordFieldList'], TRUE);
		foreach ($fieldList as $name) {
			$name = trim($name);
			if ($GLOBALS['TCA'][$this->table]['columns'][$name]) {
				if (!$GLOBALS['TCA'][$this->table]['columns'][$name]['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields', $this->table . ':' . $name)) {
					$i++;
					$tableRows[] = '
						<tr>
							<td class="t3-col-header">' . $GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel($this->table, $name), 1) . '</td>
							<td>' . htmlspecialchars(t3lib_BEfunc::getProcessedValue($this->table, $name, $this->row[$name], 0, 0, FALSE, $this->row['uid'])) . '</td>
						</tr>';
				}
			}
		}

					// Create table from the information:
		$tableCode = '
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-showitem" class="t3-table-info">
						' . implode('', $tableRows) . '
					</table>';
		$this->content .= $this->doc->section('', $tableCode);


		if ($this->fileObject->isIndexed()) {
				// References:
			$this->content .= $this->doc->section($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.referencesToThisItem'), $this->makeRef('_FILE', $this->fileObject));
		}
	}

	/**
	 * End page and print content
	 *
	 * @return	void
	 */
	function printContent() {
		$this->content.= $this->doc->endPage();
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
	 * Make reference display
	 *
	 * @param string $table Table name
	 * @param string $ref Filename or uid
	 * @return string HTML
	 */
	function makeRef($table, $ref) {

		if ($table === '_FILE') {
				// Look up the path:
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*',
				'sys_file_reference',
				'uid_local=' . $ref->getUid()
			);
		} else {
				// Look up the path:
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*',
				'sys_refindex',
				'ref_table=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($table,'sys_refindex') . ' AND ref_uid=' . intval($ref) .
					' AND deleted=0'
			);
		}
			// Compile information for title tag:
		$infoData = array();
		if (count($rows)) {
			$infoData[] = '<tr class="t3-row-header">' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.table') . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.title') . '</td>' .
				'<td>[uid]</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.field') . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.flexpointer') . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.softrefKey') . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.sorting') . '</td>' .
				'</tr>';
		}
		foreach($rows as $row) {
			if($table === '_FILE') {
				$row = $this->mapFileReferenceOnRefIndex($row);
			}
			$record = t3lib_BEfunc::getRecord($row['tablename'], $row['recuid']);
			$infoData[] = '<tr class="bgColor4">' .
				'<td>' . $GLOBALS['LANG']->sL($GLOBALS['TCA'][$row['tablename']]['ctrl']['title'], TRUE) . '</td>' .
				'<td>' . t3lib_BEfunc::getRecordTitle($row['tablename'], $record, TRUE) . '</td>' .
				'<td><span title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:page') . ': ' .
				htmlspecialchars(t3lib_BEfunc::getRecordTitle('pages', t3lib_BEfunc::getRecord('pages', $record['pid']))) .
				" (uid=" . $record['pid'] . ')">' . $record['uid'] . '</span></td>' .
				'<td>' . htmlspecialchars($this->getFieldName($row['tablename'], $row['field'])) . '</td>' .
				'<td>' . htmlspecialchars($row['flexpointer']) . '</td>' .
				'<td>' . htmlspecialchars($row['softref_key']) . '</td>' .
				'<td>' . htmlspecialchars($row['sorting']) . '</td>' .
				'</tr>';
		}

		return count($infoData)
			? '<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">' . implode('', $infoData) . '</table>'
			: '';
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
	 * @param $table string Table name
	 * @param $ref string Filename or uid
	 * @return string HTML
	 */
	function makeRefFrom($table, $ref) {

			// Look up the path:
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
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.field') . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.flexpointer') . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.softrefKey') . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.sorting') . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.refTable') . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.refUid') . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.refString') . '</td>' .
				'</tr>';
		}
		foreach($rows as $row) {
			$infoData[] = '<tr class="bgColor4">' .
				'<td>' . htmlspecialchars($this->getFieldName($table, $row['field'])) . '</td>' .
				'<td>' . htmlspecialchars($row['flexpointer']) . '</td>' .
				'<td>' . htmlspecialchars($row['softref_key']) . '</td>' .
				'<td>' . htmlspecialchars($row['sorting']) . '</td>' .
				'<td>' . $GLOBALS['LANG']->sL($GLOBALS['TCA'][$row['ref_table']]['ctrl']['title'], TRUE) . '</td>' .
				'<td>' . htmlspecialchars($row['ref_uid']) . '</td>' .
				'<td>' . htmlspecialchars($row['ref_string']) . '</td>' .
				'</tr>';
		}

		return count($infoData)
			? '<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">' . implode('', $infoData) . '</table>'
			: '';
	}
}

// Make instance:
$SOBE = t3lib_div::makeInstance('SC_show_item');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>