<?php
namespace TYPO3\CMS\Backend\Controller\ContentElement;

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
 * Script Class for showing information about an item.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ElementInformationController {

	/**
	 * GET vars:
	 * Record table (or filename)
	 *
	 * @var string
	 * @todo Define visibility
	 */
	public $table;

	/**
	 * Record uid  (or '' when filename)
	 *
	 * @var int
	 * @todo Define visibility
	 */
	public $uid;

	/**
	 * Internal, static:
	 * Page select clause
	 *
	 * @var string
	 * @todo Define visibility
	 */
	public $perms_clause;

	/**
	 * If TRUE, access to element is granted
	 *
	 * @var boolean
	 * @todo Define visibility
	 */
	public $access;

	/**
	 * Which type of element: "file" or "db"
	 *
	 * @var string
	 * @todo Define visibility
	 */
	public $type;

	/**
	 * Document Template Object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * @todo Define visibility
	 */
	public $doc;

	/**
	 * Internal, dynamic:
	 * Content Accumulation
	 *
	 * @var string
	 * @todo Define visibility
	 */
	public $content;

	/**
	 * For type "db": Set to page record of the parent page of the item set
	 * (if type="db")
	 *
	 * @var array
	 * @todo Define visibility
	 */
	public $pageinfo;

	/**
	 * For type "db": The database record row.
	 *
	 * @var array
	 * @todo Define visibility
	 */
	public $row;

	/**
	 * The fileObject if present
	 *
	 * @var \TYPO3\CMS\Core\Resource\File
	 */
	protected $fileObject;

	/**
	 * The folder obejct if present
	 *
	 * @var \TYPO3\CMS\Core\Resource\Folder
	 */
	protected $folderObject;

	/**
	 * Initialization of the class
	 * Will determine if table/uid GET vars are database record or a file and if
	 * the user has access to view information about the item.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function init() {
		// Setting input variables.
		$this->table = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('table');
		$this->uid = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('uid');
		// Initialize:
		$this->perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
		// Set to TRUE if there is access to the record / file.
		$this->access = FALSE;
		// Sets the type, "db" or "file". If blank, nothing can be shown.
		$this->type = '';
		// Checking if the $table value is really a table and if the user has
		// access to it.
		if (isset($GLOBALS['TCA'][$this->table])) {
			$this->type = 'db';
			$this->uid = intval($this->uid);
			// Check permissions and uid value:
			if ($this->uid && $GLOBALS['BE_USER']->check('tables_select', $this->table)) {
				if ((string) $this->table == 'pages') {
					$this->pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->uid, $this->perms_clause);
					$this->access = is_array($this->pageinfo) ? 1 : 0;
					$this->row = $this->pageinfo;
				} else {
					$this->row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($this->table, $this->uid);
					if ($this->row) {
						$this->pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->row['pid'], $this->perms_clause);
						$this->access = is_array($this->pageinfo) ? 1 : 0;
					}
				}
				/** @var $treatData \TYPO3\CMS\Backend\Form\DataPreprocessor */
				$treatData = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\DataPreprocessor');
				$treatData->renderRecord($this->table, $this->uid, 0, $this->row);
			}
		} elseif ($this->table == '_FILE' || $this->table == '_FOLDER' || $this->table == 'sys_file') {
			$fileOrFolderObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($this->uid);
			if ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
				$this->folderObject = $fileOrFolderObject;
				$this->access = $this->folderObject->checkActionPermission('read');
				$this->type = 'folder';
			} else {
				$this->fileObject = $fileOrFolderObject;
				$this->access = $this->fileObject->checkActionPermission('read');
				$this->type = 'file';
				$this->table = 'sys_file';
				try {
					$this->row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($this->table, $this->fileObject->getUid());
				} catch (\Exception $e) {
					$this->row = array();
				}
			}
		}
		// Initialize document template object:
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		// Starting the page by creating page header stuff:
		$this->content .= $this->doc->startPage($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.viewItem'));
		$this->content .= '<h3 class="t3-row-header">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.viewItem') . '</h3>';
		$this->content .= $this->doc->spacer(5);
	}

	/**
	 * Main function. Will generate the information to display for the item
	 * set internally.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		if (!$this->access) {
			return;
		}
		$returnLink = \TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('returnUrl'));
		$returnLinkTag = $returnLink ? '<a href="' . $returnLink . '" class="typo3-goBack">' : '<a href="#" onclick="window.close();">';
		// render type by user func
		$typeRendered = FALSE;
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/show_item.php']['typeRendering'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/show_item.php']['typeRendering'] as $classRef) {
				$typeRenderObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				// @TODO should have an interface
				if (is_object($typeRenderObj) && method_exists($typeRenderObj, 'isValid') && method_exists($typeRenderObj, 'render')) {
					if ($typeRenderObj->isValid($this->type, $this)) {
						$this->content .= $typeRenderObj->render($this->type, $this);
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
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('returnUrl'))) {
			$this->content = $this->doc->section('', ($returnLinkTag . '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.goBack', 1) . '</strong></a><br /><br />')) . $this->content;
			$this->content .= $this->doc->section('', '<br />' . $returnLinkTag . '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.goBack', 1) . '</strong></a>');
		}
	}

	/**
	 * Main function. Will generate the information to display for the item
	 * set internally.
	 *
	 * @return 	void
	 * @todo Define visibility
	 */
	public function renderDBInfo() {
		// Print header, path etc:
		// @TODO invalid context menu code in the output
		$code = $this->doc->getHeader($this->table, $this->row, $this->pageinfo['_thePath'], 1) . '<br />';
		$this->content .= $this->doc->section('', $code);
		$tableRows = array();
		$extraFields = array(
			'crdate' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_general.xlf:LGL.creationDate', 1),
			'cruser_id' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_general.xlf:LGL.creationUserId', 1),
			'tstamp' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_general.xlf:LGL.timestamp', 1)
		);
		foreach ($extraFields as $name => $value) {
			$rowValue = \TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValueExtra($this->table, $name, $this->row[$name]);
			if ($name === 'cruser_id' && $rowValue) {
				$userTemp = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('username, realName', 'be_users', 'uid = ' . intval($rowValue));
				if ($userTemp[0]['username'] !== '') {
					$rowValue = $userTemp[0]['username'];
					if ($userTemp[0]['realName'] !== '') {
						$rowValue .= ' - ' . $userTemp[0]['realName'];
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
		$fieldList = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$this->table]['interface']['showRecordFieldList'], 1);
		foreach ($fieldList as $name) {
			$name = trim($name);
			if (!isset($GLOBALS['TCA'][$this->table]['columns'][$name])) {
				continue;
			}
			$isExcluded = !(!$GLOBALS['TCA'][$this->table]['columns'][$name]['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields', $this->table . ':' . $name));
			if ($isExcluded) {
				continue;
			}
			$uid = $this->row['uid'];
			$itemValue = \TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValue($this->table, $name, $this->row[$name], 0, 0, FALSE, $uid);
			$itemLabel = $GLOBALS['LANG']->sL(\TYPO3\CMS\Backend\Utility\BackendUtility::getItemLabel($this->table, $name), 1);
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
		// Add path and table information in the bottom:
		$code = '';
		$code .= $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.path') . ': ' . \TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($this->pageinfo['_thePath'], -48) . '<br />';
		$code .= $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.table') . ': ' . $GLOBALS['LANG']->sL($GLOBALS['TCA'][$this->table]['ctrl']['title']) . ' (' . $this->table . ') - UID: ' . $this->uid . '<br />';
		$this->content .= $this->doc->section('', $code);
		// References:
		$references = $this->makeRef($this->table, $this->row['uid']);
		if (!empty($references)) {
			$this->content .= $this->doc->section($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.referencesToThisItem'), $references);
		}
		$referencesFrom = $this->makeRefFrom($this->table, $this->row['uid']);
		if (!empty($referencesFrom)) {
			$this->content .= $this->doc->section($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.referencesFromThisItem'), $referencesFrom);
		}
	}

	/**
	 * Main function. Will generate the information to display for the item
	 * set internally.
	 *
	 * @param string $returnLinkTag <a> tag closing/returning.
	 * @return void
	 * @todo Define visibility
	 */
	public function renderFileInfo($returnLinkTag) {
		$fileExtension = $this->fileObject->getExtension();
		$code = '<div class="fileInfoContainer">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile($fileExtension) . '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.file', TRUE) . ':</strong> ' . $this->fileObject->getName() . '&nbsp;&nbsp;' . '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.filesize') . ':</strong> ' . \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($this->fileObject->getSize()) . '</div>
			';
		$this->content .= $this->doc->section('', $code);
		$this->content .= $this->doc->divider(2);
		// If the file was an image...
		// @todo: add this check in the domain model, or in the processing folder
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileExtension)) {
			// @todo: find a way to make getimagesize part of the \TYPO3\CMS\Core\Resource\File object
			$imgInfo = @getimagesize($this->fileObject->getForLocalProcessing(FALSE));
			$thumbUrl = $this->fileObject->process(\TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGEPREVIEW, array('width' => '150m', 'height' => '150m'))->getPublicUrl(TRUE);
			$code = '<div class="fileInfoContainer fileDimensions">' . '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.dimensions') . ':</strong> ' . $imgInfo[0] . 'x' . $imgInfo[1] . ' ' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.pixels') . '</div>';
			$code .= '<br />
				<div align="center">' . $returnLinkTag . '<img src="' . $thumbUrl . '" alt="' . htmlspecialchars(trim($this->fileObject->getName())) . '" title="' . htmlspecialchars(trim($this->fileObject->getName())) . '" /></a></div>';
			$this->content .= $this->doc->section('', $code);
		} elseif ($fileExtension == 'ttf') {
			$thumbUrl = $this->fileObject->process(\TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGEPREVIEW, array('width' => '530m', 'height' => '600m'))->getPublicUrl(TRUE);
			$thumb = '<br />
				<div align="center">' . $returnLinkTag . '<img src="' . $thumbUrl . '" border="0" title="' . htmlspecialchars(trim($this->fileObject->getName())) . '" alt="" /></a></div>';
			$this->content .= $this->doc->section('', $thumb);
		}
		// Traverse the list of fields to display for the record:
		$tableRows = array();
		$showRecordFieldList = $GLOBALS['TCA'][$this->table]['interface']['showRecordFieldList'];
		$fieldList = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $showRecordFieldList, TRUE);

		foreach ($fieldList as $name) {
			// Ignored fields
			if ($name === 'size') {
				continue;
			}
			if (!isset($GLOBALS['TCA'][$this->table]['columns'][$name])) {
				continue;
			}
			$isExcluded = !(!$GLOBALS['TCA'][$this->table]['columns'][$name]['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields', $this->table . ':' . $name));
			if ($isExcluded) {
				continue;
			}
			$uid = $this->row['uid'];
			$itemValue = \TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValue($this->table, $name, $this->row[$name], 0, 0, FALSE, $uid);
			$itemLabel = $GLOBALS['LANG']->sL(\TYPO3\CMS\Backend\Utility\BackendUtility::getItemLabel($this->table, $name), 1);
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
			$references = $this->makeRef('_FILE', $this->fileObject);

			if (!empty($references)) {
				$header = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.referencesToThisItem');
				$this->content .= $this->doc->section($header, $references);
			}
		}
	}

	/**
	 * End page and print content
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printContent() {
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
		$editOnClick = \TYPO3\CMS\Backend\Utility\BackendUtility::editOnClick('&edit[' . $table . '][' . $uid . ']=edit', $GLOBALS['BACK_PATH']);
		$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open');
		$pageActionIcons = '<a href="#" onclick="' . htmlspecialchars($editOnClick) . '">' . $icon . '</a>';
		$historyOnClick = 'window.location.href=\'show_rechis.php?element=' . $table . '%3A' . $uid . '&returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '\'; return false;';
		$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-history-open');
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
	 * @param string|\TYPO3\CMS\Core\Resource\File $ref Filename or uid
	 * @return string HTML
	 * @todo Define visibility
	 */
	public function makeRef($table, $ref) {
		// Files reside in sys_file table
		if ($table === '_FILE') {
			$selectTable = 'sys_file';
			$selectUid = $ref->getUid();
		} else {
			$selectTable = $table;
			$selectUid = $ref;
		}
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_refindex', 'ref_table=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($selectTable, 'sys_refindex') . ' AND ref_uid=' . intval($selectUid) . ' AND deleted=0');
		// Compile information for title tag:
		$infoData = array();
		if (count($rows)) {
			$infoData[] = '<tr class="t3-row-header">' . '<td>&nbsp;</td>' . '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.table') . '</td>' . '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.title') . '</td>' . '<td>[uid]</td>' . '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.field') . '</td>' . '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.flexpointer') . '</td>' . '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.softrefKey') . '</td>' . '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.sorting') . '</td>' . '</tr>';
		}
		foreach ($rows as $row) {
			if ($row['tablename'] === 'sys_file_reference') {
				$row = $this->transformFileReferenceToRecordReference($row);
			}
			$record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($row['tablename'], $row['recuid']);
			$parentRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $record['pid']);
			$actions = $this->getRecordActions($row['tablename'], $row['recuid']);
			$infoData[] = '<tr class="bgColor4">' . '<td style="white-space:nowrap;">' . $actions . '</td>' . '<td>' . $GLOBALS['LANG']->sL($GLOBALS['TCA'][$row['tablename']]['ctrl']['title'], TRUE) . '</td>' . '<td>' . \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($row['tablename'], $record, TRUE) . '</td>' . '<td><span title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:page') . ': ' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle('pages', $parentRecord)) . ' (uid=' . $record['pid'] . ')">' . $record['uid'] . '</span></td>' . '<td>' . htmlspecialchars($this->getFieldName($row['tablename'], $row['field'])) . '</td>' . '<td>' . htmlspecialchars($row['flexpointer']) . '</td>' . '<td>' . htmlspecialchars($row['softref_key']) . '</td>' . '<td>' . htmlspecialchars($row['sorting']) . '</td>' . '</tr>';
		}
		$referenceLine = '';
		if (count($infoData)) {
			$referenceLine = '<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">' . implode('', $infoData) . '</table>';
		}
		return $referenceLine;
	}

	/**
	 * Maps results from the fal file reference table on the
	 * structure of  the normal reference index table.
	 *
	 * @param array $referenceRecord
	 * @return array
	 */
	protected function transformFileReferenceToRecordReference(array $referenceRecord) {
		$fileReference = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'sys_file_reference', 'uid=' . (int)$referenceRecord['recuid']);
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
	 * @todo Define visibility
	 */
	public function makeRefFrom($table, $ref) {
		// Look up the path:
		// @TODO files not respected (see makeRef)
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_refindex', 'tablename=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($table, 'sys_refindex') . ' AND recuid=' . intval($ref));
		// Compile information for title tag:
		$infoData = array();
		if (count($rows)) {
			$infoData[] = '<tr class="t3-row-header">' . '<td>&nbsp;</td>' . '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.field') . '</td>' . '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.flexpointer') . '</td>' . '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.softrefKey') . '</td>' . '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.sorting') . '</td>' . '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.refTable') . '</td>' . '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.refUid') . '</td>' . '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.refString') . '</td>' . '</tr>';
		}
		foreach ($rows as $row) {
			$actions = $this->getRecordActions($row['ref_table'], $row['ref_uid']);
			$infoData[] = '<tr class="bgColor4">' . '<td style="white-space:nowrap;">' . $actions . '</td>' . '<td>' . htmlspecialchars($this->getFieldName($table, $row['field'])) . '</td>' . '<td>' . htmlspecialchars($row['flexpointer']) . '</td>' . '<td>' . htmlspecialchars($row['softref_key']) . '</td>' . '<td>' . htmlspecialchars($row['sorting']) . '</td>' . '<td>' . $GLOBALS['LANG']->sL($GLOBALS['TCA'][$row['ref_table']]['ctrl']['title'], TRUE) . '</td>' . '<td>' . htmlspecialchars($row['ref_uid']) . '</td>' . '<td>' . htmlspecialchars($row['ref_string']) . '</td>' . '</tr>';
		}
		$referenceLine = '';
		if (count($infoData)) {
			$referenceLine = '<table border="0" cellpadding="0" cellspacing="0" class="typo3-dblist">' . implode('', $infoData) . '</table>';
		}
		return $referenceLine;
	}

}


?>