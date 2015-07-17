<?php
namespace TYPO3\CMS\Backend\Controller\ContentElement;

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
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Backend\Avatar\Avatar;

/**
 * Script Class for showing information about an item.
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
	 * @var bool
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
	 */
	public $pageInfo;

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
	 * @var Folder
	 */
	protected $folderObject;

	/**
	 * The HTML title tag
	 *
	 * @var string
	 */
	protected $titleTag;

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

		$this->permsClause = $this->getBackendUser()->getPagePermsClause(1);
		$this->doc = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);
		$this->doc->divClass = 'container';

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
		if ($this->uid && $this->getBackendUser()->check('tables_select', $this->table)) {
			if ((string)$this->table == 'pages') {
				$this->pageInfo = BackendUtility::readPageAccess($this->uid, $this->permsClause);
				$this->access = is_array($this->pageInfo) ? 1 : 0;
				$this->row = $this->pageInfo;
			} else {
				$this->row = BackendUtility::getRecordWSOL($this->table, $this->uid);
				if ($this->row) {
					$this->pageInfo = BackendUtility::readPageAccess($this->row['pid'], $this->permsClause);
					$this->access = is_array($this->pageInfo) ? 1 : 0;
				}
			}
			/** @var $treatData \TYPO3\CMS\Backend\Form\DataPreprocessor */
			$treatData = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Form\DataPreprocessor::class);
			$treatData->renderRecord($this->table, $this->uid, 0, $this->row);
		}
	}

	/**
	 * Init file/folder parameters
	 */
	protected function initFileOrFolderRecord() {
		$fileOrFolderObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject($this->uid);

		if ($fileOrFolderObject instanceof Folder) {
			$this->folderObject = $fileOrFolderObject;
			$this->access = $this->folderObject->checkActionPermission('read');
			$this->type = 'folder';
		} else {
			$this->fileObject = $fileOrFolderObject;
			$this->access = $this->fileObject->checkActionPermission('read');
			$this->type = 'file';
			$this->table = 'sys_file';

			try {
				$this->row = BackendUtility::getRecordWSOL($this->table, $fileOrFolderObject->getUid());
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
				// @todo should have an interface
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
			$this->content .= $this->renderBackButton();
		}
	}

	/**
	 * Render page title with icon, table title and record title
	 *
	 * @return string
	 */
	protected function renderPageTitle() {
		if ($this->type === 'folder') {
			$table = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_common.xlf:folder');
			$title = $this->doc->getResourceHeader($this->folderObject, array(' ', ''), FALSE);
		} elseif ($this->type === 'file') {
			$table = $this->getLanguageService()->sL($GLOBALS['TCA'][$this->table]['ctrl']['title']);
			$title = $this->doc->getResourceHeader($this->fileObject, array(' ', ''), FALSE);
		} else {
			$table = $this->getLanguageService()->sL($GLOBALS['TCA'][$this->table]['ctrl']['title']);
			$title = $this->doc->getHeader($this->table, $this->row, $this->pageInfo['_thePath'], 1, array(' ', ''), FALSE);
		}
		// Set HTML title tag
		$this->titleTag = $table . ': ' . strip_tags(BackendUtility::getRecordTitle($this->table, $this->row));
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
		// Perhaps @todo in future: Also display preview for records - without fileObject
		if (!$this->fileObject) {
			return '';
		}

		$previewTag = '';
		$downloadLink = '';

		// check if file is marked as missing
		if ($this->fileObject->isMissing()) {
			$flashMessage = \TYPO3\CMS\Core\Resource\Utility\BackendUtility::getFlashMessageForMissingFile($this->fileObject);
			$previewTag .= $flashMessage->render();

		} else {

			/** @var \TYPO3\CMS\Core\Resource\Rendering\RendererRegistry $rendererRegistry */
			$rendererRegistry = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Rendering\RendererRegistry::class);
			$fileRenderer = $rendererRegistry->getRenderer($this->fileObject);
			$fileExtension = $this->fileObject->getExtension();
			$url = $this->fileObject->getPublicUrl(TRUE);

			// Check if there is a FileRenderer
			if ($fileRenderer !== NULL) {
				$previewTag = $fileRenderer->render(
					$this->fileObject,
					'590m',
					'400m',
					array(),
					TRUE
				);

			// else check if we can create an Image preview
			} elseif (GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileExtension)) {
				$processedFile = $this->fileObject->process(
					ProcessedFile::CONTEXT_IMAGEPREVIEW,
					array(
						'width' => '590m',
						'height' => '400m'
					)
				);
				// Create thumbnail image?
				if ($processedFile) {
					$thumbUrl = $processedFile->getPublicUrl(TRUE);
					$previewTag .= '<img class="img-responsive img-thumbnail" src="' . $thumbUrl . '" ' .
						'width="' . $processedFile->getProperty('width') . '" ' .
						'height="' . $processedFile->getProperty('height') . '" ' .
						'alt="' . htmlspecialchars(trim($this->fileObject->getName())) . '" ' .
						'title="' . htmlspecialchars(trim($this->fileObject->getName())) . '" />';
				}
			}

			// Download
			if ($url) {
				$downloadLink .= '
					<a class="btn btn-primary" href="' . htmlspecialchars($url) . '" target="_blank">
						' . IconUtility::getSpriteIcon('actions-edit-download') . '
						' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_common.xlf:download', TRUE) . '
					</a>';
			}
		}

		return ($previewTag ? '<p>' . $previewTag . '</p>' : '') .
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

		$lang = $this->getLanguageService();
		if (in_array($this->type, array('folder', 'file'), TRUE)) {
			if ($this->type === 'file') {
				$extraFields['creation_date'] = $lang->sL('LLL:EXT:lang/locallang_general.xlf:LGL.creationDate', TRUE);
				$extraFields['modification_date'] = $lang->sL('LLL:EXT:lang/locallang_general.xlf:LGL.timestamp', TRUE);
			}
			$extraFields['storage'] = $lang->sL('LLL:EXT:lang/locallang_tca.xlf:sys_file.storage', TRUE);
			$extraFields['folder'] = $lang->sL('LLL:EXT:lang/locallang_common.xlf:folder', TRUE);
		} else {
			$extraFields['crdate'] = $lang->sL('LLL:EXT:lang/locallang_general.xlf:LGL.creationDate', TRUE);
			$extraFields['cruser_id'] = $lang->sL('LLL:EXT:lang/locallang_general.xlf:LGL.creationUserId', TRUE);
			$extraFields['tstamp'] = $lang->sL('LLL:EXT:lang/locallang_general.xlf:LGL.timestamp', TRUE);

			// check if the special fields are defined in the TCA ctrl section of the table
			foreach ($extraFields as $fieldName => $fieldLabel) {
				if (isset($GLOBALS['TCA'][$this->table]['ctrl'][$fieldName])) {
					$extraFields[$GLOBALS['TCA'][$this->table]['ctrl'][$fieldName]] = $fieldLabel;
				} else {
					unset($extraFields[$fieldName]);
				}
			}
		}

		foreach ($extraFields as $name => $fieldLabel) {
			$rowValue = '';
			if (!isset($this->row[$name])) {
				$resourceObject = $this->fileObject ?: $this->folderObject;
				if ($name === 'storage') {
					$rowValue = $resourceObject->getStorage()->getName();
				} elseif ($name === 'folder') {
					$rowValue = $resourceObject->getParentFolder()->getReadablePath();
				}
			} elseif (in_array($name, array('creation_date', 'modification_date'), TRUE)) {
				$rowValue = BackendUtility::datetime($this->row[$name]);
			} else {
				$rowValue = BackendUtility::getProcessedValueExtra($this->table, $name, $this->row[$name]);
			}
			// show the backend username who created the issue
			if ($name === 'cruser_id' && $rowValue) {
				$creatorRecord = BackendUtility::getRecord('be_users', $rowValue);
				if ($creatorRecord) {
					/** @var Avatar $avatar */
					$avatar = GeneralUtility::makeInstance(Avatar::class);
					$icon = $avatar->render($creatorRecord);

					$rowValue = '<span class="pull-left">' . $icon . '</span>' .
					'<strong>' . htmlspecialchars($GLOBALS['BE_USER']->user['username']) . '</strong><br />'
					. ($GLOBALS['BE_USER']->user['realName']) ? $GLOBALS['BE_USER']->user['realName'] : '';
				}
			}

			$tableRows[] = '
				<tr>
					<th>' . rtrim($fieldLabel, ':') . '</th>
					<td>' . ($name === 'cruser_id' ? $rowValue : htmlspecialchars($rowValue)) . '</td>
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

			// Storage is already handled above
			if ($this->type === 'file' && $name === 'storage') {
				continue;
			}

			$isExcluded = !(!$GLOBALS['TCA'][$this->table]['columns'][$name]['exclude'] || $this->getBackendUser()->check('non_exclude_fields', $this->table . ':' . $name));
			if ($isExcluded) {
				continue;
			}

			$itemValue = BackendUtility::getProcessedValue($this->table, $name, $this->row[$name], 0, 0, FALSE, $uid);
			$itemLabel = $lang->sL(BackendUtility::getItemLabel($this->table, $name), TRUE);
			$tableRows[] = '
				<tr>
					<th>' . $itemLabel . '</th>
					<td>' . htmlspecialchars($itemValue) . '</td>
				</tr>';
		}

		return '
			<div class="table-fit table-fit-wrap">
				<table class="table table-striped table-hover">
					' . implode('', $tableRows) . '
				</table>
			</div>';
	}

	/**
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
					$content .= $this->doc->section($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.referencesToThisItem'), $references);
				}

				$referencesFrom = $this->makeRefFrom($this->table, $this->row['uid']);
				if (!empty($referencesFrom)) {
					$content .= $this->doc->section($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.referencesFromThisItem'), $referencesFrom);
				}
				break;
			}

			case 'file': {
				if ($this->fileObject && $this->fileObject->isIndexed()) {
					$references = $this->makeRef('_FILE', $this->fileObject);

					if (!empty($references)) {
						$header = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.referencesToThisItem');
						$content .= $this->doc->section($header, $references);
					}
				}
				break;
			}
		}

		return $content;
	}

	/**
	 * Render a back button, if a returnUrl was provided
	 *
	 * @return string
	 */
	protected function renderBackButton() {
		$backLink = '';
		$returnUrl = GeneralUtility::_GET('returnUrl');
		if ($returnUrl) {
			$backLink .= '
				<a class="btn btn-primary" href="' . htmlspecialchars($returnUrl) . '>
					' . IconUtility::getSpriteIcon('actions-view-go-back') . '
					' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_common.xlf:back', TRUE) . '
				</a>';
		}
		return $backLink;
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
			$isExcluded = !(!$GLOBALS['TCA'][$this->table]['columns'][$name]['exclude'] || $this->getBackendUser()->check('non_exclude_fields', $this->table . ':' . $name));
			if ($isExcluded) {
				continue;
			}
			$uid = $this->row['uid'];
			$itemValue = BackendUtility::getProcessedValue($this->table, $name, $this->row[$name], 0, 0, FALSE, $uid);
			$itemLabel = $this->getLanguageService()->sL(BackendUtility::getItemLabel($this->table, $name), TRUE);
			$tableRows[] = '
				<tr>
					<th>' . $itemLabel . '</th>
					<td>' . htmlspecialchars($itemValue) . '</td>
				</tr>';
		}

		if (!$tableRows) {
			return '';
		}

		return '
			<div class="table-fit table-fit-wrap">
				<table class="table table-striped table-hover">
					' . implode('', $tableRows) . '
				</table>
			</div>';
	}

	/**
	 * End page and print content
	 *
	 * @return void
	 */
	public function printContent() {
		echo $this->doc->startPage($this->titleTag) .
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
			$field = $this->getLanguageService()->sL($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['label']);
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
	 * @param int $uid
	 * @return string
	 */
	protected function getRecordActions($table, $uid) {
		if ($table === '' || $uid < 0) {
			return '';
		}

		// Edit button
		$editOnClick = BackendUtility::editOnClick('&edit[' . $table . '][' . $uid . ']=edit');
		$pageActionIcons = '
			<a class="btn btn-default btn-sm" href="#" onclick="' . htmlspecialchars($editOnClick) . '">
				' . IconUtility::getSpriteIcon('actions-document-open') . '
			</a>';

		// History button
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
		$pageActionIcons .= '
			<a class="btn btn-default btn-sm" href="#" onclick="' . htmlspecialchars($historyOnClick) . '">
				' . IconUtility::getSpriteIcon('actions-document-history-open') . '
			</a>';

		if ($table === 'pages') {
			// Recordlist button
			$url = BackendUtility::getModuleUrl('web_list', array('id' => $uid, 'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')));
			$pageActionIcons .= '
				<a class="btn btn-default btn-sm" href="' . htmlspecialchars($url) . '" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.showList') . '">
					' . IconUtility::getSpriteIcon('actions-system-list-open') . '
				</a>';

			// View page button
			$viewOnClick = BackendUtility::viewOnClick($uid, '', BackendUtility::BEgetRootLine($uid));
			$pageActionIcons .= '
				<a class="btn btn-default btn-sm" href="#" onclick="' . htmlspecialchars($viewOnClick) . '" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage', TRUE) . '">
					' . IconUtility::getSpriteIcon('actions-document-view') . '
				</a>';
		}

		return '
			<div class="btn-group" role="group">
				' . $pageActionIcons . '
			</div>';
	}

	/**
	 * Make reference display
	 *
	 * @param string $table Table name
	 * @param string|\TYPO3\CMS\Core\Resource\File $ref Filename or uid
	 * @return string HTML
	 */
	protected function makeRef($table, $ref) {
		$lang = $this->getLanguageService();
		// Files reside in sys_file table
		if ($table === '_FILE') {
			$selectTable = 'sys_file';
			$selectUid = $ref->getUid();
		} else {
			$selectTable = $table;
			$selectUid = $ref;
		}
		$rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
			'*',
			'sys_refindex',
			'ref_table=' . $this->getDatabaseConnection()->fullQuoteStr($selectTable, 'sys_refindex') . ' AND ref_uid=' . (int)$selectUid . ' AND deleted=0'
		);

		// Compile information for title tag:
		$infoData = array();
		$infoDataHeader = '';
		if (!empty($rows)) {
			$infoDataHeader = '
				<tr>
					<th class="col-icon"></th>
					<th class="col-title">' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.title') . '</th>
					<th>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.table') . '</th>
					<th>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.uid') . '</th>
					<th>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.field') . '</th>
					<th>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.flexpointer') . '</th>
					<th>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.softrefKey') . '</th>
					<th>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.sorting') . '</th>
					<th class="col-control"></th>
				</tr>';
		}
		foreach ($rows as $row) {
			if ($row['tablename'] === 'sys_file_reference') {
				$row = $this->transformFileReferenceToRecordReference($row);
				if ($row['tablename'] === NULL || $row['recuid'] === NULL) {
					return '';
				}
			}
			$record = BackendUtility::getRecord($row['tablename'], $row['recuid']);
			if ($record) {
				$parentRecord = BackendUtility::getRecord('pages', $record['pid']);
				$parentRecordTitle = is_array($parentRecord)
					? BackendUtility::getRecordTitle('pages', $parentRecord)
					: '';
				$icon = IconUtility::getSpriteIconForRecord($row['tablename'], $record);
				$actions = $this->getRecordActions($row['tablename'], $row['recuid']);
				$editOnClick = BackendUtility::editOnClick('&edit[' . $row['tablename'] . '][' . $row['recuid'] . ']=edit');
				$infoData[] = '
				<tr>
					<td class="col-icon">
						<a href="#" onclick="' . htmlspecialchars($editOnClick) . '" title="id=' . $record['uid'] . '">
							' . $icon . '
						</a>
					</td>
					<td class="col-title">
						<a href="#" onclick="' . htmlspecialchars($editOnClick) . '" title="id=' . $record['uid'] . '" >
							' . BackendUtility::getRecordTitle($row['tablename'], $record, TRUE) . '
						</a>
					</td>
					<td>' . $lang->sL($GLOBALS['TCA'][$row['tablename']]['ctrl']['title'], TRUE) . '</td>
					<td>
						<span title="' . $lang->sL('LLL:EXT:lang/locallang_common.xlf:page') . ': '
							. htmlspecialchars($parentRecordTitle) . ' (uid=' . $record['pid'] . ')">
							' . $record['uid'] . '
						</span>
					</td>
					<td>' . htmlspecialchars($this->getLabelForTableColumn($row['tablename'], $row['field'])) . '</td>
					<td>' . htmlspecialchars($row['flexpointer']) . '</td>
					<td>' . htmlspecialchars($row['softref_key']) . '</td>
					<td>' . htmlspecialchars($row['sorting']) . '</td>
					<td class="col-control">' . $actions . '</td>
				</tr>';
			} else {
				$infoData[] = '
				<tr>
					<td class="col-icon"></td>
					<td class="col-title">' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.missing_record') . ' (uid=' . $row['recuid'] . ')</td>
					<td>' . htmlspecialchars($lang->sL($GLOBALS['TCA'][$row['tablename']]['ctrl']['title']) ?: $row['tablename']) . '</td>
					<td></td>
					<td>' . htmlspecialchars($this->getLabelForTableColumn($row['tablename'], $row['field'])) . '</td>
					<td>' . htmlspecialchars($row['flexpointer']) . '</td>
					<td>' . htmlspecialchars($row['softref_key']) . '</td>
					<td>' . htmlspecialchars($row['sorting']) . '</td>
					<td class="col-control"></td>
				</tr>';
			}
		}
		$referenceLine = '';
		if (!empty($infoData)) {
			$referenceLine = '
				<div class="table-fit">
					<table class="table table-striped table-hover">
						<thead>' . $infoDataHeader . '</thead>
						<tbody>' . implode('', $infoData) .	'</tbody>
					</table>
				</div>';
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
		$lang = $this->getLanguageService();
		$rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
			'*',
			'sys_refindex',
			'tablename=' . $this->getDatabaseConnection()->fullQuoteStr($table, 'sys_refindex') . ' AND recuid=' . (int)$ref
		);

		// Compile information for title tag:
		$infoData = array();
		$infoDataHeader = '';
		if (!empty($rows)) {
			$infoDataHeader = '
				<tr>
					<th class="col-icon"></th>
					<th class="col-title">' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.title') . '</th>
					<th>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.table') . '</th>
					<th>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.uid') . '</th>
					<th>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.field') . '</th>
					<th>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.flexpointer') . '</th>
					<th>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.softrefKey') . '</th>
					<th>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.sorting') . '</th>
					<th>' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:show_item.php.refString') . '</th>
					<th class="col-control"></th>
				</tr>';
		}
		foreach ($rows as $row) {
			$record = BackendUtility::getRecord($row['ref_table'], $row['ref_uid']);
			$icon = IconUtility::getSpriteIconForRecord($row['tablename'], $record);
			$actions = $this->getRecordActions($row['ref_table'], $row['ref_uid']);
			$editOnClick = BackendUtility::editOnClick('&edit[' . $row['ref_table'] . '][' . $row['ref_uid'] . ']=edit');
			$infoData[] = '
				<tr>
					<td class="col-icon">
						<a href="#" onclick="' . htmlspecialchars($editOnClick) . '" title="id=' . $record['uid'] . '">
							' . $icon . '
						</a>
					</td>
					<td class="col-title">
						<a href="#" onclick="' . htmlspecialchars($editOnClick) . '" title="id=' . $record['uid'] . '" >
							' . BackendUtility::getRecordTitle($row['ref_table'], $record, TRUE) . '
						</a>
					</td>
					<td>' . $lang->sL($GLOBALS['TCA'][$row['ref_table']]['ctrl']['title'], TRUE) . '</td>
					<td>' . htmlspecialchars($row['ref_uid']) . '</td>
					<td>' . htmlspecialchars($this->getLabelForTableColumn($table, $row['field'])) . '</td>
					<td>' . htmlspecialchars($row['flexpointer']) . '</td>
					<td>' . htmlspecialchars($row['softref_key']) . '</td>
					<td>' . htmlspecialchars($row['sorting']) . '</td>
					<td>' . htmlspecialchars($row['ref_string']) . '</td>
					<td class="col-control">' . $actions . '</td>
				</tr>';
		}

		if (empty($infoData)) {
			return '';
		}

		return '
			<div class="table-fit">
				<table class="table table-striped table-hover">
					<thead>' . $infoDataHeader . '</thead>
					<tbody>' . implode('', $infoData) . '</tbody>
				</table>
			</div>';
	}

	/**
	 * Convert FAL file reference (sys_file_reference) to reference index (sys_refindex) table format
	 *
	 * @param array $referenceRecord
	 * @return array
	 */
	protected function transformFileReferenceToRecordReference(array $referenceRecord) {
		$fileReference = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
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

	/**
	 * Returns LanguageService
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * Returns the current BE user.
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * Returns the database connection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
