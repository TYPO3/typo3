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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for showing information about an item.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ElementInformationController {

	/**
	 * Record table name
	 *
	 * @var string
	 */
	public $table;

	/**
	 * Record uid
	 *
	 * @var int
	 */
	public $uid;

	/**
	 * @var string
	 */
	protected $permsClause;

	/**
	 * @var boolean
	 */
	public $access = FALSE;

	/**
	 * Which type of element:
	 * - "file"
	 * - "db"
	 *
	 * @var string
	 */
	public $type = '';

	/**
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	public $doc;

	/**
	 * @var string
	 */
	protected $content = '';

	/**
	 * For type "db": Set to page record of the parent page of the item set
	 * (if type="db")
	 *
	 * @var array
	 * @todo Define visibility
	 */
	public $pageinfo;

	/**
	 * Database records identified by table/uid
	 *
	 * @var array
	 */
	protected $row;

	/**
	 * @var \TYPO3\CMS\Core\Resource\File
	 */
	protected $fileObject;

	/**
	 * @var \TYPO3\CMS\Core\Resource\Folder
	 */
	protected $folderObject;

	/**
	 * Constructor
	 */
	public function __construct() {
		$GLOBALS['BACK_PATH'] = '';
		$GLOBALS['SOBE'] = $this;

		$this->init();
	}

	/**
	 * Determines if table/uid point to database record or file and
	 * if user has access to view information
	 *
	 * @return void
	 */
	public function init() {
		$this->table = GeneralUtility::_GET('table');
		$this->uid = GeneralUtility::_GET('uid');

		$this->perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
		$this->doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');

		if (isset($GLOBALS['TCA'][$this->table])) {
			$this->initDatabaseRecord();
		} elseif ($this->table == '_FILE' || $this->table == '_FOLDER' || $this->table == 'sys_file') {
			$this->initFileOrFolderRecord();
		}
	}

	/**
	 * Init database records (table)
	 */
	protected function initDatabaseRecord() {
		$this->type = 'db';
		$this->uid = (int)$this->uid;

		// Check permissions and uid value:
		if ($this->uid && $GLOBALS['BE_USER']->check('tables_select', $this->table)) {
			if ((string) $this->table == 'pages') {
				$this->pageinfo = BackendUtility::readPageAccess($this->uid, $this->perms_clause);
				$this->access = is_array($this->pageinfo) ? 1 : 0;
				$this->row = $this->pageinfo;
			} else {
				$this->row = BackendUtility::getRecordWSOL($this->table, $this->uid);
				if ($this->row) {
					$this->pageinfo = BackendUtility::readPageAccess($this->row['pid'], $this->perms_clause);
					$this->access = is_array($this->pageinfo) ? 1 : 0;
				}
			}
			/** @var $treatData \TYPO3\CMS\Backend\Form\DataPreprocessor */
			$treatData = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\DataPreprocessor');
			$treatData->renderRecord($this->table, $this->uid, 0, $this->row);
		}
	}

	/**
	 * Init file/folder parameters
	 */
	protected function initFileOrFolderRecord() {
		$fileOrFolderObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->
				retrieveFileOrFolderObject($this->uid);

		if ($fileOrFolderObject instanceof \TYPO3\CMS\Core\Resource\Folder) {
			$this->folderObject = $fileOrFolderObject;
			$this->access = $this->folderObject->checkActionPermission('read');
			$this->type = 'folder';
		} else {
			$this->fileObject = $fileOrFolderObject;
			$this->access = $this->fileObject->checkActionPermission('read');
			$this->type = 'file';
			$this->table = 'sys_file_metadata';

			try {
				$metaData = $fileOrFolderObject->_getMetaData();
				$this->row = BackendUtility::getRecordWSOL($this->table, $metaData['uid']);
			} catch (\Exception $e) {
				$this->row = array();
			}
		}
	}

	/**
	 * @return void
	 */
	public function main() {
		if (!$this->access) {
			return;
		}

		// render type by user func
		$typeRendered = FALSE;
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/show_item.php']['typeRendering'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/show_item.php']['typeRendering'] as $classRef) {
				$typeRenderObj = GeneralUtility::getUserObj($classRef);
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

		if (!$typeRendered) {
			$this->content .= $this->renderPageTitle();
			$this->content .= $this->renderPreview();
			$this->content .= $this->renderPropertiesAsTable();
			$this->content .= $this->renderReferences();
		}
	}

	/**
	 * Render page title with icon, table title and record title
	 *
	 * @return string
	 */
	protected function renderPageTitle() {
		if ($this->type === 'folder') {
			$table = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:folder');
			$title = $this->doc->getResourceHeader($this->folderObject, array(' ', ''));
		} elseif ($this->type === 'file') {
			$table = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$this->table]['ctrl']['title']);
			$title = $this->doc->getResourceHeader($this->fileObject, array(' ', ''));
		} else {
			$table = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$this->table]['ctrl']['title']);
			$title = $this->doc->getHeader($this->table, $this->row, $this->pageinfo['_thePath'], 1, array(' ', ''));
		}

		return '<h1>' .
				($table ? '<small>' . $table . '</small><br />' : '') .
				$title .
				'</h1>';
	}

	/**
	 * Render preview for current record
	 *
	 * @return string
	 */
	protected function renderPreview() {
		// Perhaps @TODO in future: Also display preview for records - without fileObject
		if (!$this->fileObject) {
			return;
		}
		$imageTag = '';
		$downloadLink = '';

		// check if file is marked as missing
		if ($this->fileObject->isMissing()) {
			$flashMessage = \TYPO3\CMS\Core\Resource\Utility\BackendUtility::getFlashMessageForMissingFile($this->fileObject);
			$imageTag .= $flashMessage->render();

		} else {

			$fileExtension = $this->fileObject->getExtension();
			$thumbUrl = '';
			if (GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileExtension)) {
				$thumbUrl = $this->fileObject->process(
					ProcessedFile::CONTEXT_IMAGEPREVIEW,
					array(
						'width' => '400m',
						'height' => '400m'
					)
				)->getPublicUrl(TRUE);
			}

			// Create thumbnail image?
			if ($thumbUrl) {
				$imageTag .= '<img src="' . $thumbUrl . '" ' .
						'alt="' . htmlspecialchars(trim($this->fileObject->getName())) . '" ' .
						'title="' . htmlspecialchars(trim($this->fileObject->getName())) . '" />';
			}

			// Display download link?
			$url = $this->fileObject->getPublicUrl(TRUE);

			if ($url) {
				$downloadLink .= '<a href="' . htmlspecialchars($url) . '" target="_blank" class="t3-button">' .
						IconUtility::getSpriteIcon('actions-edit-download') . ' ' .
						$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:download', TRUE) .
						'</a>';
			}
		}

		return ($imageTag ? '<p>' . $imageTag . '</p>' : '') .
				($downloadLink ? '<p>' . $downloadLink . '</p>' : '');
	}

	/**
	 * Render property array as html table
	 *
	 * @return string
	 */
	protected function renderPropertiesAsTable() {
		$tableRows = array();
		$extraFields = array();

		if ($this->type !== 'folder') {
			$extraFields = array(
				'crdate' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_general.xlf:LGL.creationDate', TRUE),
				'cruser_id' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_general.xlf:LGL.creationUserId', TRUE),
				'tstamp' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_general.xlf:LGL.timestamp', TRUE)
			);
		}

		foreach ($extraFields as $name => $value) {
			$rowValue = BackendUtility::getProcessedValueExtra($this->table, $name, $this->row[$name]);
			if ($name === 'cruser_id' && $rowValue) {
				$userTemp = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('username, realName', 'be_users', 'uid = ' . (int)$rowValue);
				if ($userTemp[0]['username'] !== '') {
					$rowValue = $userTemp[0]['username'];
					if ($userTemp[0]['realName'] !== '') {
						$rowValue .= ' - ' . $userTemp[0]['realName'];
					}
				}
			}
			$tableRows[] = '
				<tr>
					<td><strong>' . rtrim($value, ':') . '</strong></td>
					<td>' . htmlspecialchars($rowValue) . '</td>
				</tr>';
		}

		// Traverse the list of fields to display for the record:
		$fieldList = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$this->table]['interface']['showRecordFieldList'], TRUE);
		foreach ($fieldList as $name) {
			$name = trim($name);
			$uid = $this->row['uid'];

			if (!isset($GLOBALS['TCA'][$this->table]['columns'][$name])) {
				continue;
			}

			$isExcluded = !(!$GLOBALS['TCA'][$this->table]['columns'][$name]['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields', $this->table . ':' . $name));
			if ($isExcluded) {
				continue;
			}

			$itemValue = BackendUtility::getProcessedValue($this->table, $name, $this->row[$name], 0, 0, FALSE, $uid);
			$itemLabel = $GLOBALS['LANG']->sL(BackendUtility::getItemLabel($this->table, $name), TRUE);
			$tableRows[] = '
				<tr>
					<td><strong>' . $itemLabel . '</strong></td>
					<td>' . htmlspecialchars($itemValue) . '</td>
				</tr>';
		}

		return '<table class="t3-table">' . implode('', $tableRows) . '</table>';
	}

	/*
	 * Render references section (references from and references to current record)
	 *
	 * @return string
	 */
	protected function renderReferences() {
		$content = '';

		switch ($this->type) {
			case 'db': {
				$references = $this->makeRef($this->table, $this->row['uid']);
				if (!empty($references)) {
					$content .= $this->doc->section($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.referencesToThisItem'), $references);
				}

				$referencesFrom = $this->makeRefFrom($this->table, $this->row['uid']);
				if (!empty($referencesFrom)) {
					$content .= $this->doc->section($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.referencesFromThisItem'), $referencesFrom);
				}
				break;
			}

			case 'file': {
				if ($this->fileObject && $this->fileObject->isIndexed()) {
					$references = $this->makeRef('_FILE', $this->fileObject);

					if (!empty($references)) {
						$header = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.referencesToThisItem');
						$content .= $this->doc->section($header, $references);
					}
				}
				break;
			}
		}

		return $content;
	}

	/**
	 * Renders file properties as html table
	 *
	 * @param array $fieldList
	 * @return string
	 */
	protected function renderFileInformationAsTable($fieldList) {
		$tableRows = array();
		foreach ($fieldList as $name) {
			if (!isset($GLOBALS['TCA'][$this->table]['columns'][$name])) {
				continue;
			}
			$isExcluded = !(!$GLOBALS['TCA'][$this->table]['columns'][$name]['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields', $this->table . ':' . $name));
			if ($isExcluded) {
				continue;
			}
			$uid = $this->row['uid'];
			$itemValue = BackendUtility::getProcessedValue($this->table, $name, $this->row[$name], 0, 0, FALSE, $uid);
			$itemLabel = $GLOBALS['LANG']->sL(BackendUtility::getItemLabel($this->table, $name), TRUE);
			$tableRows[] = '
				<tr>
					<td><strong>' . $itemLabel . '</strong></td>
					<td>' . htmlspecialchars($itemValue) . '</td>
				</tr>';
		}

		if (!$tableRows) {
			return '';
		}

		return '<table id="typo3-showitem" class="t3-table-info">' .
				implode('', $tableRows) .
				'</table>';
	}

	/**
	 * End page and print content
	 *
	 * @return void
	 */
	public function printContent() {
		echo $this->doc->startPage('') .
				$this->doc->insertStylesAndJS($this->content) .
				$this->doc->endPage();
	}

	/**
	 * Get field name for specified table/column name
	 *
	 * @param string $tableName Table name
	 * @param string $fieldName Column name
	 * @return string label
	 */
	public function getLabelForTableColumn($tableName, $fieldName) {
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

		$editOnClick = BackendUtility::editOnClick('&edit[' . $table . '][' . $uid . ']=edit', $GLOBALS['BACK_PATH']);
		$icon = IconUtility::getSpriteIcon('actions-document-open');
		$pageActionIcons = '<a href="#" onclick="' . htmlspecialchars($editOnClick) . '">' . $icon . '</a>';
		$historyOnClick = 'window.location.href=' .
			GeneralUtility::quoteJSvalue(
				BackendUtility::getModuleUrl(
					'record_history',
					array(
						'element' => $table . ':' . $uid,
						'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
					)
				)
			) . '; return false;';

		$icon = IconUtility::getSpriteIcon('actions-document-history-open');
		$pageActionIcons .= '<a href="#" onclick="' . htmlspecialchars($historyOnClick) . '">' . $icon . '</a>';
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
	 */
	protected function makeRef($table, $ref) {
		// Files reside in sys_file table
		if ($table === '_FILE') {
			$selectTable = 'sys_file';
			$selectUid = $ref->getUid();
		} else {
			$selectTable = $table;
			$selectUid = $ref;
		}
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'sys_refindex',
			'ref_table=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($selectTable, 'sys_refindex') . ' AND ref_uid=' . (int)$selectUid . ' AND deleted=0'
		);

		// Compile information for title tag:
		$infoData = array();
		if (count($rows)) {
			$infoDataHeader = '<tr>' . '<td>&nbsp;</td>' . '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.table') . '</td>' . '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.title') . '</td>' . '<td>[uid]</td>' . '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.field') . '</td>' . '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.flexpointer') . '</td>' . '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.softrefKey') . '</td>' . '<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.sorting') . '</td>' . '</tr>';
		}
		foreach ($rows as $row) {
			if ($row['tablename'] === 'sys_file_reference') {
				$row = $this->transformFileReferenceToRecordReference($row);
			}
			$record = BackendUtility::getRecord($row['tablename'], $row['recuid']);
			$parentRecord = BackendUtility::getRecord('pages', $record['pid']);
			$actions = $this->getRecordActions($row['tablename'], $row['recuid']);
			$infoData[] = '<tr class="db_list_normal">' .
					'<td style="white-space:nowrap;">' . $actions . '</td>' .
					'<td>' . $GLOBALS['LANG']->sL($GLOBALS['TCA'][$row['tablename']]['ctrl']['title'], TRUE) . '</td>' .
					'<td>' . BackendUtility::getRecordTitle($row['tablename'], $record, TRUE) . '</td>' .
					'<td><span title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:page') . ': ' .
							htmlspecialchars(BackendUtility::getRecordTitle('pages', $parentRecord)) . ' (uid=' . $record['pid'] . ')">' .
							$record['uid'] . '</span></td>' .
					'<td>' . htmlspecialchars($this->getLabelForTableColumn($row['tablename'], $row['field'])) . '</td>' .
					'<td>' . htmlspecialchars($row['flexpointer']) . '</td>' . '<td>' . htmlspecialchars($row['softref_key']) . '</td>' .
					'<td>' . htmlspecialchars($row['sorting']) . '</td>' .
					'</tr>';
		}
		$referenceLine = '';
		if (count($infoData)) {
			$referenceLine = '<table class="t3-table">' .
					'<thead>' . $infoDataHeader . '</thead>' .
					'<tbody>' .
					implode('', $infoData) .
					'</tbody></table>';
		}
		return $referenceLine;
	}

	/**
	 * Make reference display (what this elements points to)
	 *
	 * @param string $table Table name
	 * @param string $ref Filename or uid
	 * @return string HTML
	 */
	protected function makeRefFrom($table, $ref) {
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'sys_refindex',
			'tablename=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($table, 'sys_refindex') . ' AND recuid=' . (int)$ref
		);

		// Compile information for title tag:
		$infoData = array();
		if (count($rows)) {
			$infoDataHeader = '<tr class="t3-row-header">' .
					'<td>&nbsp;</td>' .
					'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.field') . '</td>' .
					'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.flexpointer') . '</td>' .
					'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.softrefKey') . '</td>' .
					'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.sorting') . '</td>' .
					'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.refTable') . '</td>' .
					'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.refUid') . '</td>' .
					'<td>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.refString') . '</td>' .
					'</tr>';
		}
		foreach ($rows as $row) {
			$actions = $this->getRecordActions($row['ref_table'], $row['ref_uid']);
			$infoData[] = '<tr class="db_list_normal">' .
					'<td style="white-space:nowrap;">' . $actions . '</td>' .
					'<td>' . htmlspecialchars($this->getLabelForTableColumn($table, $row['field'])) . '</td>' .
					'<td>' . htmlspecialchars($row['flexpointer']) . '</td>' .
					'<td>' . htmlspecialchars($row['softref_key']) . '</td>' .
					'<td>' . htmlspecialchars($row['sorting']) . '</td>' .
					'<td>' . $GLOBALS['LANG']->sL($GLOBALS['TCA'][$row['ref_table']]['ctrl']['title'], TRUE) . '</td>' .
					'<td>' . htmlspecialchars($row['ref_uid']) . '</td>' .
					'<td>' . htmlspecialchars($row['ref_string']) . '</td>' .
					'</tr>';
		}

		if (empty($infoData)) {
			return;
		}

		return '<table class="t3-table">' .
				'<thead>' . $infoDataHeader . '</thead>' .
				'<tbody>' .
				implode('', $infoData) .
				'</tbody></table>';
	}

	/**
	 * Convert FAL file reference (sys_file_reference) to reference index (sys_refindex) table format
	 *
	 * @param array $referenceRecord
	 * @return array
	 */
	protected function transformFileReferenceToRecordReference(array $referenceRecord) {
		$fileReference = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'*',
			'sys_file_reference',
			'uid=' . (int)$referenceRecord['recuid']
		);
		return array(
			'recuid' => $fileReference['uid_foreign'],
			'tablename' => $fileReference['tablenames'],
			'field' => $fileReference['fieldname'],
			'flexpointer' => '',
			'softref_key' => '',
			'sorting' => $fileReference['sorting_foreign']
		);
	}

}
