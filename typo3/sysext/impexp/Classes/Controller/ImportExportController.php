<?php
namespace TYPO3\CMS\Impexp\Controller;

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

use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Main script class for the Import / Export facility
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ImportExportController extends \TYPO3\CMS\Backend\Module\BaseScriptClass {

	/**
	 * @var array|\TYPO3\CMS\Core\Resource\File[]
	 */
	protected $uploadedFiles = array();

	/**
	 * Array containing the current page.
	 *
	 * @todo Define visibility
	 */
	public $pageinfo;

	/**
	 * @var \TYPO3\CMS\Impexp\ImportExport
	 */
	protected $export;

	/**
	 * @var \TYPO3\CMS\Impexp\ImportExport
	 */
	protected $import;

	/**
	 * @var \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility
	 */
	protected $fileProcessor;

	/**
	 * @var string
	 */
	protected $vC = '';

	/**
	 * @var LanguageService
	 */
	protected $lang = NULL;

	/**
	 * @var string
	 */
	protected $treeHTML = '';

	/**
	 * @return void
	 */
	public function init() {
		parent::init();
		$this->vC = GeneralUtility::_GP('vC');
		$this->lang = $this->getLanguageService();
	}

	/**
	 * Main module function
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		$this->lang->includeLLFile('EXT:impexp/app/locallang.xlf');
		// Start document template object:
		$this->doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->bodyTagId = 'imp-exp-mod';
		$this->doc->setModuleTemplate(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('impexp') . '/app/template.html');
		$this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
		// Setting up the context sensitive menu:
		$this->doc->getContextMenuCode();
		$this->doc->postCode = $this->doc->wrapScriptTags('if (top.fsMod) top.fsMod.recentIds["web"] = ' . (int)$this->id . ';');
		$this->doc->form = '<form action="' . htmlspecialchars($GLOBALS['MCONF']['_']) . '" method="post" enctype="'
			. $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'] . '"><input type="hidden" name="id" value="' . $this->id . '" />';
		$this->content .= $this->doc->header($this->lang->getLL('title'));
		$this->content .= $this->doc->spacer(5);
		// Input data grabbed:
		$inData = GeneralUtility::_GP('tx_impexp');
		$this->checkUpload();
		switch ((string) $inData['action']) {
			case 'export':
				// Finally: If upload went well, set the new file as the thumbnail in the $inData array:
				if (!empty($this->uploadedFiles[0])) {
					$inData['meta']['thumbnail'] = $this->uploadedFiles[0]->getCombinedIdentifier();
				}
				// Call export interface
				$this->exportData($inData);
				break;
			case 'import':
				// Finally: If upload went well, set the new file as the import file:
				if (!empty($this->uploadedFiles[0])) {
					// Only allowed extensions....
					if (GeneralUtility::inList('t3d,xml', $this->uploadedFiles[0]->getExtension())) {
						$inData['file'] = $this->uploadedFiles[0]->getCombinedIdentifier();
					}
				}
				// Call import interface:
				$this->importData($inData);
				break;
		}
		// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers['CONTENT'] = $this->content;
		// Build the <body> for the module
		$this->content = $this->doc->startPage($this->lang->getLL('title'));
		$this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Print the content
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
	 * @return array all available buttons as an associated array
	 */
	protected function getButtons() {
		$buttons = array(
			'view' => '',
			'shortcut' => ''
		);
		if ($this->getBackendUser()->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('tx_impexp', '', $this->MCONF['name']);
		}
		// Input data grabbed:
		$inData = GeneralUtility::_GP('tx_impexp');
		if ((string) $inData['action'] == 'import') {
			if ($this->id && is_array($this->pageinfo) || $this->getBackendUser()->user['admin'] && !$this->id) {
				if (is_array($this->pageinfo) && $this->pageinfo['uid']) {
					// View
					$onClick = BackendUtility::viewOnClick(
						$this->pageinfo['uid'],
						$this->doc->backPath,
						BackendUtility::BEgetRootLine($this->pageinfo['uid'])
					);
					$title = $this->lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage', TRUE);
					$buttons['view'] = '<a href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $title . '">'
						. IconUtility::getSpriteIcon('actions-document-view') . '</a>';
				}
			}
		}
		return $buttons;
	}

	/**************************
	 * EXPORT FUNCTIONS
	 **************************/

	/**
	 * Export part of module
	 *
	 * @param array $inData Content of POST VAR tx_impexp[]..
	 * @return void Setting content in $this->content
	 * @todo Define visibility
	 */
	public function exportData($inData) {
		// BUILDING EXPORT DATA:
		// Processing of InData array values:
		$inData['pagetree']['maxNumber'] = MathUtility::forceIntegerInRange($inData['pagetree']['maxNumber'], 1, 10000, 100);
		$inData['listCfg']['maxNumber'] = MathUtility::forceIntegerInRange($inData['listCfg']['maxNumber'], 1, 10000, 100);
		$inData['maxFileSize'] = MathUtility::forceIntegerInRange($inData['maxFileSize'], 1, 10000, 1000);
		$inData['filename'] = trim(preg_replace('/[^[:alnum:]._-]*/', '', preg_replace('/\\.(t3d|xml)$/', '', $inData['filename'])));
		if (strlen($inData['filename'])) {
			$inData['filename'] .= $inData['filetype'] == 'xml' ? '.xml' : '.t3d';
		}
		// Set exclude fields in export object:
		if (!is_array($inData['exclude'])) {
			$inData['exclude'] = array();
		}
		// Saving/Loading/Deleting presets:
		$this->processPresets($inData);
		// Create export object and configure it:
		$this->export = GeneralUtility::makeInstance('TYPO3\\CMS\\Impexp\\ImportExport');
		$this->export->init(0, 'export');
		$this->export->setCharset($this->lang->charSet);
		$this->export->maxFileSize = $inData['maxFileSize'] * 1024;
		$this->export->excludeMap = (array) $inData['exclude'];
		$this->export->softrefCfg = (array) $inData['softrefCfg'];
		$this->export->extensionDependencies = (array) $inData['extension_dep'];
		$this->export->showStaticRelations = $inData['showStaticRelations'];
		$this->export->includeExtFileResources = !$inData['excludeHTMLfileResources'];
		// Static tables:
		if (is_array($inData['external_static']['tables'])) {
			$this->export->relStaticTables = $inData['external_static']['tables'];
		}
		// Configure which tables external relations are included for:
		if (is_array($inData['external_ref']['tables'])) {
			$this->export->relOnlyTables = $inData['external_ref']['tables'];
		}
		$this->export->setHeaderBasics();
		// Meta data setting:

		$beUser = $this->getBackendUser();
		$this->export->setMetaData(
			$inData['meta']['title'],
			$inData['meta']['description'],
			$inData['meta']['notes'],
			$beUser->user['username'],
			$beUser->user['realName'],
			$beUser->user['email']
		);
		if ($inData['meta']['thumbnail']) {
			$theThumb = $this->getFile($inData['meta']['thumbnail']);
			if ($theThumb !== NULL && $theThumb->exists()) {
				$this->export->addThumbnail($theThumb->getForLocalProcessing(FALSE));
			}
		}
		// Configure which records to export
		if (is_array($inData['record'])) {
			foreach ($inData['record'] as $ref) {
				$rParts = explode(':', $ref);
				$this->export->export_addRecord($rParts[0], BackendUtility::getRecord($rParts[0], $rParts[1]));
			}
		}
		// Configure which tables to export
		if (is_array($inData['list'])) {
			$db = $this->getDatabaseConnection();
			foreach ($inData['list'] as $ref) {
				$rParts = explode(':', $ref);
				if ($beUser->check('tables_select', $rParts[0])) {
					$res = $this->exec_listQueryPid($rParts[0], $rParts[1], MathUtility::forceIntegerInRange($inData['listCfg']['maxNumber'], 1));
					while ($subTrow = $db->sql_fetch_assoc($res)) {
						$this->export->export_addRecord($rParts[0], $subTrow);
					}
					$db->sql_free_result($res);
				}
			}
		}
		// Pagetree
		if (isset($inData['pagetree']['id'])) {
			// Based on click-expandable tree
			$idH = NULL;
			if ($inData['pagetree']['levels'] == -1) {
				$pagetree = GeneralUtility::makeInstance('TYPO3\\CMS\\Impexp\\LocalPageTree');
				$tree = $pagetree->ext_tree($inData['pagetree']['id'], $this->filterPageIds($this->export->excludeMap));
				$this->treeHTML = $pagetree->printTree($tree);
				$idH = $pagetree->buffer_idH;
			} elseif ($inData['pagetree']['levels'] == -2) {
				$this->addRecordsForPid($inData['pagetree']['id'], $inData['pagetree']['tables'], $inData['pagetree']['maxNumber']);
			} else {
				// Based on depth
				// Drawing tree:
				// If the ID is zero, export root
				if (!$inData['pagetree']['id'] && $beUser->isAdmin()) {
					$sPage = array(
						'uid' => 0,
						'title' => 'ROOT'
					);
				} else {
					$sPage = BackendUtility::getRecordWSOL('pages', $inData['pagetree']['id'], '*', ' AND ' . $this->perms_clause);
				}
				if (is_array($sPage)) {
					$pid = $inData['pagetree']['id'];
					$tree = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\View\\PageTreeView');
					$tree->init('AND ' . $this->perms_clause . $this->filterPageIds($this->export->excludeMap));
					$HTML = IconUtility::getSpriteIconForRecord('pages', $sPage);
					$tree->tree[] = array('row' => $sPage, 'HTML' => $HTML);
					$tree->buffer_idH = array();
					if ($inData['pagetree']['levels'] > 0) {
						$tree->getTree($pid, $inData['pagetree']['levels'], '');
					}
					$idH = array();
					$idH[$pid]['uid'] = $pid;
					if (count($tree->buffer_idH)) {
						$idH[$pid]['subrow'] = $tree->buffer_idH;
					}
					$pagetree = GeneralUtility::makeInstance('TYPO3\\CMS\\Impexp\\LocalPageTree');
					$this->treeHTML = $pagetree->printTree($tree->tree);
				}
			}
			// In any case we should have a multi-level array, $idH, with the page structure
			// here (and the HTML-code loaded into memory for nice display...)
			if (is_array($idH)) {
				// Sets the pagetree and gets a 1-dim array in return with the pages (in correct submission order BTW...)
				$flatList = $this->export->setPageTree($idH);
				foreach ($flatList as $k => $value) {
					$this->export->export_addRecord('pages', BackendUtility::getRecord('pages', $k));
					$this->addRecordsForPid($k, $inData['pagetree']['tables'], $inData['pagetree']['maxNumber']);
				}
			}
		}
		// After adding ALL records we set relations:
		for ($a = 0; $a < 10; $a++) {
			$addR = $this->export->export_addDBRelations($a);
			if (!count($addR)) {
				break;
			}
		}
		// Finally files are added:
		// MUST be after the DBrelations are set so that files from ALL added records are included!
		$this->export->export_addFilesFromRelations();

		$this->export->export_addFilesFromSysFilesRecords();

		// If the download button is clicked, return file
		if ($inData['download_export'] || $inData['save_export']) {
			switch ((string) $inData['filetype']) {
				case 'xml':
					$out = $this->export->compileMemoryToFileContent('xml');
					$fExt = '.xml';
					break;
				case 't3d':
					$this->export->dontCompress = 1;
					// intentional fall-through
				default:
					$out = $this->export->compileMemoryToFileContent();
					$fExt = ($this->export->doOutputCompress() ? '-z' : '') . '.t3d';
			}
			// Filename:
			$dlFile = $inData['filename'];
			if (!$dlFile) {
				$exportName = substr(preg_replace('/[^[:alnum:]_]/', '-', $inData['download_export_name']), 0, 20);
				$dlFile = 'T3D_' . $exportName . '_' . date('Y-m-d_H-i') . $fExt;
			}

			// Export for download:
			if ($inData['download_export']) {
				$mimeType = 'application/octet-stream';
				Header('Content-Type: ' . $mimeType);
				Header('Content-Length: ' . strlen($out));
				Header('Content-Disposition: attachment; filename=' . basename($dlFile));
				echo $out;
				die;
			}
			// Export by saving:
			if ($inData['save_export']) {
				$saveFolder = $this->getDefaultImportExportFolder();
				if ($saveFolder !== FALSE && $saveFolder->checkActionPermission('write')) {
					$temporaryFileName = GeneralUtility::tempnam('export');
					file_put_contents($temporaryFileName, $out);
					$file = $saveFolder->addFile($temporaryFileName, $dlFile, 'replace');
					$file = $this->getIndexerService()->indexFile($file);
					$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('exportdata_savedFile'), sprintf($GLOBALS['LANG']->getLL('exportdata_savedInSBytes', TRUE), $file->getPublicUrl(), GeneralUtility::formatSize(strlen($out))), 0, 1);
				} else {
					$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('exportdata_problemsSavingFile'), sprintf($GLOBALS['LANG']->getLL('exportdata_badPathS', TRUE), $this->getTemporaryFolderPath()), 0, 1, 2);
				}
			}
		}
		// OUTPUT to BROWSER:
		// Now, if we didn't make download file, show configuration form based on export:
		$menuItems = array();
		// Export configuration
		$row = array();
		$this->makeConfigurationForm($inData, $row);
		$menuItems[] = array(
			'label' => $this->lang->getLL('tableselec_configuration'),
			'content' => '
				<table border="0" cellpadding="1" cellspacing="1">
					' . implode('
					', $row) . '
				</table>
			'
		);
		// File options
		$row = array();
		$this->makeSaveForm($inData, $row);
		$menuItems[] = array(
			'label' => $this->lang->getLL('exportdata_filePreset'),
			'content' => '
				<table border="0" cellpadding="1" cellspacing="1">
					' . implode('
					', $row) . '
				</table>
			'
		);
		// File options
		$row = array();
		$this->makeAdvancedOptionsForm($inData, $row);
		$menuItems[] = array(
			'label' => $this->lang->getLL('exportdata_advancedOptions'),
			'content' => '
				<table border="0" cellpadding="1" cellspacing="1">
					' . implode('
					', $row) . '
				</table>
			'
		);
		// Generate overview:
		$overViewContent = $this->export->displayContentOverview();
		// Print errors that might be:
		$errors = $this->export->printErrorLog();
		$menuItems[] = array(
			'label' => $this->lang->getLL('exportdata_messages'),
			'content' => $errors,
			'stateIcon' => $errors ? 2 : 0
		);
		// Add hidden fields and create tabs:
		$content = $this->doc->getDynTabMenu($menuItems, 'tx_impexp_export', -1);
		$content .= '<input type="hidden" name="tx_impexp[action]" value="export" />';
		$this->content .= $this->doc->section('', $content, 0, 1);
		// Output Overview:
		$this->content .= $this->doc->section($this->lang->getLL('execlistqu_structureToBeExported'), $overViewContent, 0, 1);
	}

	/**
	 * Adds records to the export object for a specific page id.
	 *
	 * @param integer $k Page id for which to select records to add
	 * @param array $tables Array of table names to select from
	 * @param integer $maxNumber Max amount of records to select
	 * @return void
	 * @todo Define visibility
	 */
	public function addRecordsForPid($k, $tables, $maxNumber) {
		if (!is_array($tables)) {
			return;
		}
		$db = $this->getDatabaseConnection();
		foreach ($GLOBALS['TCA'] as $table => $value) {
			if ($table != 'pages' && (in_array($table, $tables) || in_array('_ALL', $tables))) {
				if ($this->getBackendUser()->check('tables_select', $table) && !$GLOBALS['TCA'][$table]['ctrl']['is_static']) {
					$res = $this->exec_listQueryPid($table, $k, MathUtility::forceIntegerInRange($maxNumber, 1));
					while ($subTrow = $db->sql_fetch_assoc($res)) {
						$this->export->export_addRecord($table, $subTrow);
					}
					$db->sql_free_result($res);
				}
			}
		}
	}

	/**
	 * Selects records from table / pid
	 *
	 * @param string $table Table to select from
	 * @param integer $pid Page ID to select from
	 * @param integer $limit Max number of records to select
	 * @return \mysqli_result|object Database resource
	 * @todo Define visibility
	 */
	public function exec_listQueryPid($table, $pid, $limit) {
		$db = $this->getDatabaseConnection();
		$orderBy = $GLOBALS['TCA'][$table]['ctrl']['sortby']
			? 'ORDER BY ' . $GLOBALS['TCA'][$table]['ctrl']['sortby']
			: $GLOBALS['TCA'][$table]['ctrl']['default_sortby'];
		$res = $db->exec_SELECTquery(
			'*',
			$table,
			'pid=' . (int)$pid . BackendUtility::deleteClause($table) . BackendUtility::versioningPlaceholderClause($table),
			'',
			$db->stripOrderBy($orderBy),
			$limit
		);
		// Warning about hitting limit:
		if ($db->sql_num_rows($res) == $limit) {
			$limitWarning = sprintf($this->lang->getLL('makeconfig_anSqlQueryReturned', TRUE), $limit);
			$this->content .= $this->doc->section($this->lang->getLL('execlistqu_maxNumberLimit'), $limitWarning, 0, 1, 2);
		}
		return $res;
	}

	/**
	 * Create configuration form
	 *
	 * @param array $inData Form configurat data
	 * @param array $row Table row accumulation variable. This is filled with table rows.
	 * @return void Sets content in $this->content
	 * @todo Define visibility
	 */
	public function makeConfigurationForm($inData, &$row) {
		$nameSuggestion = '';
		// Page tree export options:
		if (isset($inData['pagetree']['id'])) {
			$nameSuggestion .= 'tree_PID' . $inData['pagetree']['id'] . '_L' . $inData['pagetree']['levels'];
			$row[] = '
				<tr class="tableheader bgColor5">
					<td colspan="2">' . $this->lang->getLL('makeconfig_exportPagetreeConfiguration', TRUE)
						. BackendUtility::cshItem('xMOD_tx_impexp', 'pageTreeCfg', $GLOBALS['BACK_PATH'], '') . '</td>
				</tr>';
			$row[] = '
				<tr class="bgColor4">
					<td><strong>' . $this->lang->getLL('makeconfig_pageId', TRUE) . '</strong></td>
					<td>' . htmlspecialchars($inData['pagetree']['id']) . '<input type="hidden" value="'
						. htmlspecialchars($inData['pagetree']['id']) . '" name="tx_impexp[pagetree][id]" /></td>
				</tr>';
			$row[] = '
				<tr class="bgColor4">
					<td><strong>' . $this->lang->getLL('makeconfig_tree', TRUE) . '</strong>'
						. BackendUtility::cshItem('xMOD_tx_impexp', 'pageTreeDisplay', $GLOBALS['BACK_PATH'], '') . '</td>
					<td>' . ($this->treeHTML ?: $this->lang->getLL('makeconfig_noTreeExportedOnly', TRUE)) . '</td>
				</tr>';
			$opt = array(
				'-2' => $this->lang->getLL('makeconfig_tablesOnThisPage'),
				'-1' => $this->lang->getLL('makeconfig_expandedTree'),
				'0' => $this->lang->getLL('makeconfig_onlyThisPage'),
				'1' => $this->lang->getLL('makeconfig_1Level'),
				'2' => $this->lang->getLL('makeconfig_2Levels'),
				'3' => $this->lang->getLL('makeconfig_3Levels'),
				'4' => $this->lang->getLL('makeconfig_4Levels'),
				'999' => $this->lang->getLL('makeconfig_infinite')
			);
			$row[] = '
				<tr class="bgColor4">
					<td><strong>' . $this->lang->getLL('makeconfig_levels', TRUE) . '</strong>'
						. BackendUtility::cshItem('xMOD_tx_impexp', 'pageTreeMode', $GLOBALS['BACK_PATH'], '') . '</td>
					<td>' . $this->renderSelectBox('tx_impexp[pagetree][levels]', $inData['pagetree']['levels'], $opt) . '</td>
				</tr>';
			$row[] = '
				<tr class="bgColor4">
					<td><strong>' . $this->lang->getLL('makeconfig_includeTables', TRUE) . '</strong>'
						. BackendUtility::cshItem('xMOD_tx_impexp', 'pageTreeRecordLimit', $GLOBALS['BACK_PATH'], '') . '</td>
					<td>' . $this->tableSelector('tx_impexp[pagetree][tables]', $inData['pagetree']['tables'], 'pages') . '<br/>
						' . $this->lang->getLL('makeconfig_maxNumberOfRecords', TRUE) . '<br/>
						<input type="text" name="tx_impexp[pagetree][maxNumber]" value="'
						. htmlspecialchars($inData['pagetree']['maxNumber']) . '"' . $this->doc->formWidth(10) . ' /><br/>
					</td>
				</tr>';
		}
		// Single record export:
		if (is_array($inData['record'])) {
			$row[] = '
				<tr class="tableheader bgColor5">
					<td colspan="2">' . $this->lang->getLL('makeconfig_exportSingleRecord', TRUE)
						. BackendUtility::cshItem('xMOD_tx_impexp', 'singleRecord', $GLOBALS['BACK_PATH'], '') . '</td>
				</tr>';
			foreach ($inData['record'] as $ref) {
				$rParts = explode(':', $ref);
				$tName = $rParts[0];
				$rUid = $rParts[1];
				$nameSuggestion .= $tName . '_' . $rUid;
				$rec = BackendUtility::getRecordWSOL($tName, $rUid);
				$row[] = '
				<tr class="bgColor4">
					<td><strong>' . $this->lang->getLL('makeconfig_record', TRUE) . '</strong></td>
					<td>' . IconUtility::getSpriteIconForRecord($tName, $rec) . BackendUtility::getRecordTitle($tName, $rec, TRUE)
						. '<input type="hidden" name="tx_impexp[record][]" value="' . htmlspecialchars(($tName . ':' . $rUid)) . '" /></td>
				</tr>';
			}
		}
		// Single tables/pids:
		if (is_array($inData['list'])) {
			$row[] = '
				<tr class="tableheader bgColor5">
					<td colspan="2">' . $this->lang->getLL('makeconfig_exportTablesFromPages', TRUE) . '</td>
				</tr>';
			// Display information about pages from which the export takes place
			$tblList = '';
			foreach ($inData['list'] as $reference) {
				$referenceParts = explode(':', $reference);
				$tableName = $referenceParts[0];
				if ($this->getBackendUser()->check('tables_select', $tableName)) {
					// If the page is actually the root, handle it differently
					// NOTE: we don't compare integers, because the number actually comes from the split string above
					if ($referenceParts[1] === '0') {
						$iconAndTitle = IconUtility::getSpriteIcon('apps-pagetree-root') . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
					} else {
						$record = BackendUtility::getRecordWSOL('pages', $referenceParts[1]);
						$iconAndTitle = IconUtility::getSpriteIconForRecord('pages', $record)
							. BackendUtility::getRecordTitle('pages', $record, TRUE);
					}
					$tblList .= 'Table "' . $tableName . '" from ' . $iconAndTitle
						. '<input type="hidden" name="tx_impexp[list][]" value="' . htmlspecialchars($reference) . '" /><br/>';
				}
			}
			$row[] = '
			<tr class="bgColor4">
				<td><strong>' . $this->lang->getLL('makeconfig_tablePids', TRUE) . '</strong>'
					. BackendUtility::cshItem('xMOD_tx_impexp', 'tableList', $GLOBALS['BACK_PATH'], '') . '</td>
				<td>' . $tblList . '</td>
			</tr>';
			$row[] = '
				<tr class="bgColor4">
					<td><strong>' . $this->lang->getLL('makeconfig_maxNumberOfRecords', TRUE)
						. BackendUtility::cshItem('xMOD_tx_impexp', 'tableListMaxNumber', $GLOBALS['BACK_PATH'], '') . '</strong></td>
					<td>
						<input type="text" name="tx_impexp[listCfg][maxNumber]" value="'
						. htmlspecialchars($inData['listCfg']['maxNumber']) . '"' . $this->doc->formWidth(10) . ' /><br/>
					</td>
				</tr>';
		}
		$row[] = '
			<tr class="tableheader bgColor5">
				<td colspan="2">' . $this->lang->getLL('makeconfig_relationsAndExclusions', TRUE) . '</td>
			</tr>';
		// Add relation selector:
		$row[] = '
				<tr class="bgColor4">
					<td><strong>' . $this->lang->getLL('makeconfig_includeRelationsToTables', TRUE) . '</strong>'
						. BackendUtility::cshItem('xMOD_tx_impexp', 'inclRelations', $GLOBALS['BACK_PATH'], '') . '</td>
					<td>' . $this->tableSelector('tx_impexp[external_ref][tables]', $inData['external_ref']['tables']) . '</td>
				</tr>';
		// Add static relation selector:
		$row[] = '
				<tr class="bgColor4">
					<td><strong>' . $this->lang->getLL('makeconfig_useStaticRelationsFor', TRUE) . '</strong>'
						. BackendUtility::cshItem('xMOD_tx_impexp', 'staticRelations', $GLOBALS['BACK_PATH'], '') . '</td>
					<td>' . $this->tableSelector('tx_impexp[external_static][tables]', $inData['external_static']['tables']) . '<br/>
						<label for="checkShowStaticRelations">' . $this->lang->getLL('makeconfig_showStaticRelations', TRUE)
							. '</label> <input type="checkbox" name="tx_impexp[showStaticRelations]" id="checkShowStaticRelations" value="1"'
							. ($inData['showStaticRelations'] ? ' checked="checked"' : '') . ' />
						</td>
				</tr>';
		// Exclude:
		$excludeHiddenFields = '';
		if (is_array($inData['exclude'])) {
			foreach ($inData['exclude'] as $key => $value) {
				$excludeHiddenFields .= '<input type="hidden" name="tx_impexp[exclude][' . $key . ']" value="1" />';
			}
		}
		if (count($inData['exclude'])) {
			$excludedElements = '<em>' . implode(', ', array_keys($inData['exclude'])). '</em><hr/><label for="checkExclude">'
				. $this->lang->getLL('makeconfig_clearAllExclusions', TRUE)
				. '</label> <input type="checkbox" name="tx_impexp[exclude]" id="checkExclude" value="1" />';
		} else {
			$excludedElements = $this->lang->getLL('makeconfig_noExcludedElementsYet', TRUE);
		}
		$row[] = '
				<tr class="bgColor4">
					<td><strong>' . $this->lang->getLL('makeconfig_excludeElements', TRUE) . '</strong>'
						. BackendUtility::cshItem('xMOD_tx_impexp', 'excludedElements', $GLOBALS['BACK_PATH'], '') . '</td>
					<td>' . $excludeHiddenFields . '
					' . $excludedElements . '
					</td>
				</tr>';
		// Add buttons:
		$row[] = '
				<tr class="bgColor4">
					<td>&nbsp;</td>
					<td>
						<input type="submit" value="' . $this->lang->getLL('makeadvanc_update', TRUE) . '" />
						<input type="hidden" name="tx_impexp[download_export_name]" value="' . substr($nameSuggestion, 0, 30) . '" />
					</td>
				</tr>';
	}

	/**
	 * Create advanced options form
	 *
	 * @param array $inData Form configurat data
	 * @param array $row Table row accumulation variable. This is filled with table rows.
	 * @return void Sets content in $this->content
	 * @todo Define visibility
	 */
	public function makeAdvancedOptionsForm($inData, &$row) {
		// Soft references
		$row[] = '
			<tr class="tableheader bgColor5">
				<td colspan="2">' . $this->lang->getLL('makeadvanc_softReferences', TRUE) . '</td>
			</tr>';
		$row[] = '
				<tr class="bgColor4">
					<td><label for="checkExcludeHTMLfileResources"><strong>'
						. $this->lang->getLL('makeadvanc_excludeHtmlCssFile', TRUE)	. '</strong></label>'
						. BackendUtility::cshItem('xMOD_tx_impexp', 'htmlCssResources', $GLOBALS['BACK_PATH'], '') . '</td>
					<td><input type="checkbox" name="tx_impexp[excludeHTMLfileResources]" id="checkExcludeHTMLfileResources" value="1"'
						. ($inData['excludeHTMLfileResources'] ? ' checked="checked"' : '') . ' /></td>
				</tr>';
		// Extensions
		$row[] = '
			<tr class="tableheader bgColor5">
				<td colspan="2">' . $this->lang->getLL('makeadvanc_extensionDependencies', TRUE) . '</td>
			</tr>';
		$row[] = '
				<tr class="bgColor4">
					<td><strong>' . $this->lang->getLL('makeadvanc_selectExtensionsThatThe', TRUE) . '</strong>'
						. BackendUtility::cshItem('xMOD_tx_impexp', 'extensionDependencies', $GLOBALS['BACK_PATH'], '') . '</td>
					<td>' . $this->extensionSelector('tx_impexp[extension_dep]', $inData['extension_dep']) . '</td>
				</tr>';
		// Add buttons:
		$row[] = '
				<tr class="bgColor4">
					<td>&nbsp;</td>
					<td>
						<input type="submit" value="' . $this->lang->getLL('makesavefo_update', TRUE) . '" />
						<input type="hidden" name="tx_impexp[download_export_name]" value="" />
					</td>
				</tr>';
	}

	/**
	 * Create configuration form
	 *
	 * @param array $inData Form configurat data
	 * @param array $row Table row accumulation variable. This is filled with table rows.
	 * @return void Sets content in $this->content
	 * @todo Define visibility
	 */
	public function makeSaveForm($inData, &$row) {
		// Presets:
		$row[] = '
			<tr class="tableheader bgColor5">
				<td colspan="2">' . $this->lang->getLL('makesavefo_presets', TRUE) . '</td>
			</tr>';
		$opt = array('');
		$where = '(public>0 OR user_uid=' . (int)$this->getBackendUser()->user['uid'] . ')'
			. ($inData['pagetree']['id'] ? ' AND (item_uid=' . (int)$inData['pagetree']['id'] . ' OR item_uid=0)' : '');
		$presets = $this->getDatabaseConnection()->exec_SELECTgetRows('*', 'tx_impexp_presets', $where);
		if (is_array($presets)) {
			foreach ($presets as $presetCfg) {
				$opt[$presetCfg['uid']] = $presetCfg['title'] . ' [' . $presetCfg['uid'] . ']'
					. ($presetCfg['public'] ? ' [Public]' : '')
					. ($presetCfg['user_uid'] === $this->getBackendUser()->user['uid'] ? ' [Own]' : '');
			}
		}
		$row[] = '
				<tr class="bgColor4">
					<td><strong>' . $this->lang->getLL('makesavefo_presets', TRUE) . '</strong>'
						. BackendUtility::cshItem('xMOD_tx_impexp', 'presets', $GLOBALS['BACK_PATH'], '') . '</td>
					<td>
						' . $this->lang->getLL('makesavefo_selectPreset', TRUE) . '<br/>
						' . $this->renderSelectBox('preset[select]', '', $opt) . '
						<br/>
						<input type="submit" value="' . $this->lang->getLL('makesavefo_load', TRUE) . '" name="preset[load]" />
						<input type="submit" value="' . $this->lang->getLL('makesavefo_save', TRUE) . '" name="preset[save]" onclick="return confirm(\'' . $this->lang->getLL('makesavefo_areYouSure', TRUE) . '\');" />
						<input type="submit" value="' . $this->lang->getLL('makesavefo_delete', TRUE) . '" name="preset[delete]" onclick="return confirm(\'' . $this->lang->getLL('makesavefo_areYouSure', TRUE) . '\');" />
						<input type="submit" value="' . $this->lang->getLL('makesavefo_merge', TRUE) . '" name="preset[merge]" onclick="return confirm(\'' . $this->lang->getLL('makesavefo_areYouSure', TRUE) . '\');" />
						<br/>
						' . $this->lang->getLL('makesavefo_titleOfNewPreset', TRUE) . '
						<input type="text" name="tx_impexp[preset][title]" value="'
							. htmlspecialchars($inData['preset']['title']) . '"' . $this->doc->formWidth(30) . ' /><br/>
						<label for="checkPresetPublic">' . $this->lang->getLL('makesavefo_public', TRUE) . '</label>
						<input type="checkbox" name="tx_impexp[preset][public]" id="checkPresetPublic" value="1"'
							. ($inData['preset']['public'] ? ' checked="checked"' : '') . ' /><br/>
					</td>
				</tr>';
		// Output options:
		$row[] = '
			<tr class="tableheader bgColor5">
				<td colspan="2">' . $this->lang->getLL('makesavefo_outputOptions', TRUE) . '</td>
			</tr>';
		// Meta data:
		$thumbnailFiles = array();
		foreach ($this->getThumbnailFiles() as $thumbnailFile) {
			$thumbnailFiles[$thumbnailFile->getCombinedIdentifier()] = $thumbnailFile->getName();
		}
		if (!empty($thumbnailFiles)) {
			array_unshift($thumbnailFiles, '');
		}
		$thumbnail = $this->getFile($inData['meta']['thumbnail']);
		$saveFolder = $this->getDefaultImportExportFolder();

		$row[] = '
				<tr class="bgColor4">
					<td><strong>' . $this->lang->getLL('makesavefo_metaData', TRUE) . '</strong>'
						. BackendUtility::cshItem('xMOD_tx_impexp', 'metadata', $GLOBALS['BACK_PATH'], '') . '</td>
					<td>
							' . $this->lang->getLL('makesavefo_title', TRUE) . ' <br/>
							<input type="text" name="tx_impexp[meta][title]" value="' . htmlspecialchars($inData['meta']['title']) . '"' . $this->doc->formWidth(30) . ' /><br/>
							' . $this->lang->getLL('makesavefo_description', TRUE) . ' <br/>
							<input type="text" name="tx_impexp[meta][description]" value="' . htmlspecialchars($inData['meta']['description']) . '"' . $this->doc->formWidth(30) . ' /><br/>
							' . $this->lang->getLL('makesavefo_notes', TRUE) . ' <br/>
							<textarea name="tx_impexp[meta][notes]"' . $this->doc->formWidth(30, 1) . '>' . GeneralUtility::formatForTextarea($inData['meta']['notes']) . '</textarea><br/>
							' . (!empty($thumbnailFiles) ? '
							' . $this->lang->getLL('makesavefo_thumbnail', TRUE) . '<br/>
							' . $this->renderSelectBox('tx_impexp[meta][thumbnail]', $inData['meta']['thumbnail'], $thumbnailFiles) : '') . '<br/>
							' . ($thumbnail ? '<img src="' . htmlspecialchars($thumbnail->getPublicUrl(TRUE)) . '" vspace="5" style="border: solid black 1px;" alt="" /><br/>' : '') . '
							' . $this->lang->getLL('makesavefo_uploadThumbnail', TRUE) . '<br/>
							' . ($saveFolder ? '<input type="file" name="upload_1" ' . $this->doc->formWidth(30) . ' size="30" /><br/>
								<input type="hidden" name="file[upload][1][target]" value="' . htmlspecialchars($saveFolder->getCombinedIdentifier()) . '" />
								<input type="hidden" name="file[upload][1][data]" value="1" /><br />' : '') . '
						</td>
				</tr>';
		// Add file options:
		$opt = array();
		if ($this->export->compress) {
			$opt['t3d_compressed'] = $this->lang->getLL('makesavefo_t3dFileCompressed');
		}
		$opt['t3d'] = $this->lang->getLL('makesavefo_t3dFile');
		$opt['xml'] = $this->lang->getLL('makesavefo_xml');
		$fileName = '';
		if ($saveFolder) {
			$fileName = sprintf($this->lang->getLL('makesavefo_filenameSavedInS', TRUE), $saveFolder->getCombinedIdentifier())
				. '<br/>
						<input type="text" name="tx_impexp[filename]" value="'
				. htmlspecialchars($inData['filename']) . '"' . $this->doc->formWidth(30) . ' /><br/>';
		}
		$row[] = '
				<tr class="bgColor4">
					<td><strong>' . $this->lang->getLL('makesavefo_fileFormat', TRUE) . '</strong>'
						. BackendUtility::cshItem('xMOD_tx_impexp', 'fileFormat', $GLOBALS['BACK_PATH'], '') . '</td>
					<td>' . $this->renderSelectBox('tx_impexp[filetype]', $inData['filetype'], $opt) . '<br/>
						' . $this->lang->getLL('makesavefo_maxSizeOfFiles', TRUE) . '<br/>
						<input type="text" name="tx_impexp[maxFileSize]" value="'
				. htmlspecialchars($inData['maxFileSize']) . '"' . $this->doc->formWidth(10) . ' /><br/>
						' . $fileName . '
					</td>
				</tr>';
		// Add buttons:
		$row[] = '
				<tr class="bgColor4">
					<td>&nbsp;</td>
					<td><input type="submit" value="' . $this->lang->getLL('makesavefo_update', TRUE)
						. '" /> - <input type="submit" value="' . $this->lang->getLL('makesavefo_downloadExport', TRUE)
						. '" name="tx_impexp[download_export]" />' . ($saveFolder ? ' - <input type="submit" value="'
						. $this->lang->getLL('importdata_saveToFilename', TRUE) . '" name="tx_impexp[save_export]" />' : '') . '</td>
				</tr>';
	}

	/**************************
	 * IMPORT FUNCTIONS
	 **************************/

	/**
	 * Import part of module
	 *
	 * @param array $inData Content of POST VAR tx_impexp[]..
	 * @return void Setting content in $this->content
	 * @todo Define visibility
	 */
	public function importData($inData) {
		$access = is_array($this->pageinfo) ? 1 : 0;
		$beUser = $this->getBackendUser();
		if ($this->id && $access || $beUser->user['admin'] && !$this->id) {
			if ($beUser->user['admin'] && !$this->id) {
				$this->pageinfo = array('title' => '[root-level]', 'uid' => 0, 'pid' => 0);
			}
			if ($inData['new_import']) {
				unset($inData['import_mode']);
			}
			/** @var $import \TYPO3\CMS\Impexp\ImportExport */
			$import = GeneralUtility::makeInstance('TYPO3\\CMS\\Impexp\\ImportExport');
			$import->init(0, 'import');
			$import->update = $inData['do_update'];
			$import->import_mode = $inData['import_mode'];
			$import->enableLogging = $inData['enableLogging'];
			$import->global_ignore_pid = $inData['global_ignore_pid'];
			$import->force_all_UIDS = $inData['force_all_UIDS'];
			$import->showDiff = !$inData['notShowDiff'];
			$import->allowPHPScripts = $inData['allowPHPScripts'];
			$import->softrefInputValues = $inData['softrefInputValues'];
			// OUTPUT creation:
			$menuItems = array();
			// Make input selector:
			// must have trailing slash.
			$path = $this->getDefaultImportExportFolder();
			$exportFiles = $this->getExportFiles();
			// Configuration
			$row = array();
			$selectOptions = array('');
			foreach ($exportFiles as $file) {
				$selectOptions[$file->getCombinedIdentifier()] = $file->getPublicUrl();
			}
			$row[] = '<tr class="bgColor5">
					<td colspan="2"><strong>' . $this->lang->getLL('importdata_selectFileToImport', TRUE) . '</strong></td>
				</tr>';
			$noCompressorAvailable = !$import->compress
				? '<br /><span class="typo3-red">' . $this->lang->getLL('importdata_noteNoDecompressorAvailable', TRUE) . '</span>'
				: '';
			$row[] = '<tr class="bgColor4">
				<td><strong>' . $this->lang->getLL('importdata_file', TRUE) . '</strong>'
					. BackendUtility::cshItem('xMOD_tx_impexp', 'importFile', $GLOBALS['BACK_PATH'], '') . '</td>
				<td>' . $this->renderSelectBox('tx_impexp[file]', $inData['file'], $selectOptions) . '<br />'
					. sprintf($this->lang->getLL('importdata_fromPathS', TRUE), $path ? $path->getCombinedIdentifier() : $this->lang->getLL('importdata_no_accessible_file_mount', TRUE)) .
				$noCompressorAvailable . '</td>
				</tr>';
			$row[] = '<tr class="bgColor5">
					<td colspan="2"><strong>' . $this->lang->getLL('importdata_importOptions', TRUE) . '</strong></td>
				</tr>';
			$row[] = '<tr class="bgColor4">
				<td><strong>' . $this->lang->getLL('importdata_update', TRUE) . '</strong>'
					. BackendUtility::cshItem('xMOD_tx_impexp', 'update', $GLOBALS['BACK_PATH'], '') . '</td>
				<td>
					<input type="checkbox" name="tx_impexp[do_update]" id="checkDo_update" value="1"'
						. ($inData['do_update'] ? ' checked="checked"' : '') . ' />
					<label for="checkDo_update">' . $this->lang->getLL('importdata_updateRecords', TRUE) . '</label><br/>
				<em>(' . $this->lang->getLL('importdata_thisOptionRequiresThat', TRUE) . ')</em>' . ($inData['do_update'] ? '	<hr/>
					<input type="checkbox" name="tx_impexp[global_ignore_pid]" id="checkGlobal_ignore_pid" value="1"'
						. ($inData['global_ignore_pid'] ? ' checked="checked"' : '') . ' />
					<label for="checkGlobal_ignore_pid">' . $this->lang->getLL('importdata_ignorePidDifferencesGlobally', TRUE) . '</label><br/>
					<em>(' . $this->lang->getLL('importdata_ifYouSetThis', TRUE) . ')</em>
					' : '') . '</td>
				</tr>';
			$allowPhpScripts = $beUser->isAdmin()
				? '
					<input type="checkbox" name="tx_impexp[allowPHPScripts]" id="checkAllowPHPScripts" value="1"'
						. ($inData['allowPHPScripts'] ? ' checked="checked"' : '') . ' />
					<label for="checkAllowPHPScripts">' . $this->lang->getLL('importdata_allowToWriteBanned', TRUE) . '</label><br/>'
				: '';
			$doUpdate = !$inData['do_update'] && $beUser->isAdmin()
				? '
					<br/>
					<input type="checkbox" name="tx_impexp[force_all_UIDS]" id="checkForce_all_UIDS" value="1"'
						. ($inData['force_all_UIDS'] ? ' checked="checked"' : '') . ' />
					<label for="checkForce_all_UIDS"><span class="typo3-red">'
						. $this->lang->getLL('importdata_force_all_UIDS', TRUE) . '</span></label><br/>
					<em>(' . $this->lang->getLL('importdata_force_all_UIDS_descr', TRUE) . ')</em>'
				: '';
			$row[] = '<tr class="bgColor4">
				<td><strong>' . $this->lang->getLL('importdata_options', TRUE) . '</strong>'
					. BackendUtility::cshItem('xMOD_tx_impexp', 'options', $GLOBALS['BACK_PATH'], '') . '</td>
				<td>
					<input type="checkbox" name="tx_impexp[notShowDiff]" id="checkNotShowDiff" value="1"'
						. ($inData['notShowDiff'] ? ' checked="checked"' : '') . ' />
					<label for="checkNotShowDiff">' . $this->lang->getLL('importdata_doNotShowDifferences', TRUE) . '</label><br/>
					<em>(' . $this->lang->getLL('importdata_greenValuesAreFrom', TRUE) . ')</em>
					<br/><br/>

					' . $allowPhpScripts . $doUpdate . '
				</td>
				</tr>';
			$newImport = !$inData['import_file']
				? '<input type="submit" value="' . $this->lang->getLL('importdata_preview', TRUE) . '" />' . ($inData['file']
					? ' - <input type="submit" value="' . ($inData['do_update']
						? $this->lang->getLL('importdata_update_299e', TRUE)
						: $this->lang->getLL('importdata_import', TRUE)) . '" name="tx_impexp[import_file]" onclick="return confirm(\''
							. $this->lang->getLL('importdata_areYouSure', TRUE) . '\');" />'
					: '')
				: '<input type="submit" name="tx_impexp[new_import]" value="' . $this->lang->getLL('importdata_newImport', TRUE) . '" />';
			$row[] = '<tr class="bgColor4">
				<td><strong>' . $this->lang->getLL('importdata_action', TRUE) . '</strong>'
					. BackendUtility::cshItem('xMOD_tx_impexp', 'action', $GLOBALS['BACK_PATH'], '') . '</td>
				<td>' . $newImport . '
					<input type="hidden" name="tx_impexp[action]" value="import" /></td>
				</tr>';
			$row[] = '<tr class="bgColor4">
				<td><strong>' . $this->lang->getLL('importdata_enableLogging', TRUE) . '</strong>'
					. BackendUtility::cshItem('xMOD_tx_impexp', 'enableLogging', $GLOBALS['BACK_PATH'], '') . '</td>
				<td>
					<input type="checkbox" name="tx_impexp[enableLogging]" id="checkEnableLogging" value="1"'
						. ($inData['enableLogging'] ? ' checked="checked"' : '') . ' />
					<label for="checkEnableLogging">' . $this->lang->getLL('importdata_writeIndividualDbActions', TRUE) . '</label><br/>
					<em>(' . $this->lang->getLL('importdata_thisIsDisabledBy', TRUE) . ')</em>
				</td>
				</tr>';
			$menuItems[] = array(
				'label' => $this->lang->getLL('importdata_import', TRUE),
				'content' => '
					<table border="0" cellpadding="1" cellspacing="1">
						' . implode('
						', $row) . '
					</table>
				'
			);
			// Upload file:
			$tempFolder = $this->getDefaultImportExportFolder();
			if ($tempFolder) {
				$row = array();
				$row[] = '<tr class="bgColor5">
						<td colspan="2"><strong>' . $this->lang->getLL('importdata_uploadFileFromLocal', TRUE) . '</strong></td>
					</tr>';
				$row[] = '<tr class="bgColor4">
						<td>' . $this->lang->getLL('importdata_browse', TRUE)
							. BackendUtility::cshItem('xMOD_tx_impexp', 'upload', $GLOBALS['BACK_PATH'], '') . '</td>
						<td>

								<input type="file" name="upload_1"' . $this->doc->formWidth(35) . ' size="40" />
								<input type="hidden" name="file[upload][1][target]" value="' . htmlspecialchars($tempFolder->getCombinedIdentifier()) . '" />
								<input type="hidden" name="file[upload][1][data]" value="1" /><br />

								<input type="submit" name="_upload" value="' . $this->lang->sL('LLL:EXT:lang/locallang_core.xlf:file_upload.php.submit', TRUE) . '" />
								<input type="checkbox" name="overwriteExistingFiles" id="checkOverwriteExistingFiles" value="1" checked="checked" /> <label for="checkOverwriteExistingFiles">'
									. $this->lang->sL('LLL:EXT:lang/locallang_misc.xlf:overwriteExistingFiles', TRUE) . '</label>
						</td>
					</tr>';
				if (GeneralUtility::_POST('_upload')) {
					$noFileUploaded = $this->fileProcessor->internalUploadMap[1]
						? $this->lang->getLL('importdata_success', TRUE) . ' ' . $this->uploadedFiles[0]->getName()
						: '<span class="typo3-red">' . $this->lang->getLL('importdata_failureNoFileUploaded', TRUE) . '</span>';
					$row[] = '<tr class="bgColor4">
							<td>' . $this->lang->getLL('importdata_uploadStatus', TRUE) . '</td>
							<td>' . $noFileUploaded . '</td>
						</tr>';
				}
				$menuItems[] = array(
					'label' => $this->lang->getLL('importdata_upload'),
					'content' => '
						<table border="0" cellpadding="1" cellspacing="1">
							' . implode('
							', $row) . '
						</table>
					'
				);
			}
			// Perform import or preview depending:
			$overviewContent = '';
			$extensionInstallationMessage = '';
			$inFile = $this->getFile($inData['file']);
			if ($inFile !== NULL && $inFile->exists()) {
				$trow = array();
				if ($import->loadFile($inFile->getForLocalProcessing(FALSE), 1)) {
					// Check extension dependencies:
					$extKeysToInstall = array();
					if (is_array($import->dat['header']['extensionDependencies'])) {
						foreach ($import->dat['header']['extensionDependencies'] as $extKey) {
							if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extKey)) {
								$extKeysToInstall[] = $extKey;
							}
						}
					}
					if (count($extKeysToInstall)) {
						$extensionInstallationMessage = 'Before you can install this T3D file you need to install the extensions "'
							. implode('", "', $extKeysToInstall) . '".';
					}
					if ($inData['import_file']) {
						if (!count($extKeysToInstall)) {
							$import->importData($this->id);
							BackendUtility::setUpdateSignal('updatePageTree');
						}
					}
					$import->display_import_pid_record = $this->pageinfo;
					$overviewContent = $import->displayContentOverview();
				}
				// Meta data output:
				$trow[] = '<tr class="bgColor5">
						<td colspan="2"><strong>' . $this->lang->getLL('importdata_metaData', TRUE) . '</strong></td>
					</tr>';
				$trow[] = '<tr class="bgColor4">
					<td><strong>' . $this->lang->getLL('importdata_title', TRUE) . '</strong></td>
					<td width="95%">' . nl2br(htmlspecialchars($import->dat['header']['meta']['title'])) . '</td>
					</tr>';
				$trow[] = '<tr class="bgColor4">
					<td><strong>' . $this->lang->getLL('importdata_description', TRUE) . '</strong></td>
					<td width="95%">' . nl2br(htmlspecialchars($import->dat['header']['meta']['description'])) . '</td>
					</tr>';
				$trow[] = '<tr class="bgColor4">
					<td><strong>' . $this->lang->getLL('importdata_notes', TRUE) . '</strong></td>
					<td width="95%">' . nl2br(htmlspecialchars($import->dat['header']['meta']['notes'])) . '</td>
					</tr>';
				$trow[] = '<tr class="bgColor4">
					<td><strong>' . $this->lang->getLL('importdata_packager', TRUE) . '</strong></td>
					<td width="95%">' . nl2br(htmlspecialchars(($import->dat['header']['meta']['packager_name']
						. ' (' . $import->dat['header']['meta']['packager_username'] . ')'))) . '<br/>
						' . $this->lang->getLL('importdata_email', TRUE) . ' '
						. $import->dat['header']['meta']['packager_email'] . '</td>
					</tr>';
				// Thumbnail icon:
				if (is_array($import->dat['header']['thumbnail'])) {
					$pI = pathinfo($import->dat['header']['thumbnail']['filename']);
					if (GeneralUtility::inList('gif,jpg,png,jpeg', strtolower($pI['extension']))) {
						// Construct filename and write it:
						$fileName = PATH_site . 'typo3temp/importthumb.' . $pI['extension'];
						GeneralUtility::writeFile($fileName, $import->dat['header']['thumbnail']['content']);
						// Check that the image really is an image and not a malicious PHP script...
						if (getimagesize($fileName)) {
							// Create icon tag:
							$iconTag = '<img src="' . $this->doc->backPath . '../' . PathUtility::stripPathSitePrefix($fileName)
								. '" ' . $import->dat['header']['thumbnail']['imgInfo'][3]
								. ' vspace="5" style="border: solid black 1px;" alt="" />';
							$trow[] = '<tr class="bgColor4">
								<td><strong>' . $this->lang->getLL('importdata_icon', TRUE) . '</strong></td>
								<td>' . $iconTag . '</td>
								</tr>';
						} else {
							GeneralUtility::unlink_tempfile($fileName);
						}
					}
				}
				$menuItems[] = array(
					'label' => $this->lang->getLL('importdata_metaData_1387'),
					'content' => '
						<table border="0" cellpadding="1" cellspacing="1">
							' . implode('
							', $trow) . '
						</table>
					'
				);
			}
			// Print errors that might be:
			$errors = $import->printErrorLog();
			$menuItems[] = array(
				'label' => $this->lang->getLL('importdata_messages'),
				'content' => $errors,
				'stateIcon' => $errors ? 2 : 0
			);
			// Output tabs:
			$content = $this->doc->getDynTabMenu($menuItems, 'tx_impexp_import', -1);
			if ($extensionInstallationMessage) {
				$content = '<div style="border: 1px black solid; margin: 10px 10px 10px 10px; padding: 10px 10px 10px 10px;">'
					. $this->doc->icons(1) . htmlspecialchars($extensionInstallationMessage) . '</div>' . $content;
			}
			$this->content .= $this->doc->section('', $content, 0, 1);
			// Print overview:
			if ($overviewContent) {
				$this->content .= $this->doc->section($inData['import_file']
					? $this->lang->getLL('importdata_structureHasBeenImported', TRUE)
					: $this->lang->getLL('filterpage_structureToBeImported', TRUE), $overviewContent, 0, 1);
			}
		}
	}

	/****************************
	 * Preset functions
	 ****************************/

	/**
	 * Manipulate presets
	 *
	 * @param array $inData In data array, passed by reference!
	 * @return void
	 * @todo Define visibility
	 */
	public function processPresets(&$inData) {
		$presetData = GeneralUtility::_GP('preset');
		$err = FALSE;
		$msg = '';
		// Save preset
		$beUser = $this->getBackendUser();
		if (isset($presetData['save'])) {
			$preset = $this->getPreset($presetData['select']);
			// Update existing
			if (is_array($preset)) {
				if ($beUser->isAdmin() || $preset['user_uid'] === $beUser->user['uid']) {
					$fields_values = array(
						'public' => $inData['preset']['public'],
						'title' => $inData['preset']['title'],
						'item_uid' => $inData['pagetree']['id'],
						'preset_data' => serialize($inData)
					);
					$this->getDatabaseConnection()->exec_UPDATEquery('tx_impexp_presets', 'uid=' . (int)$preset['uid'], $fields_values);
					$msg = 'Preset #' . $preset['uid'] . ' saved!';
				} else {
					$msg = 'ERROR: The preset was not saved because you were not the owner of it!';
					$err = TRUE;
				}
			} else {
				// Insert new:
				$fields_values = array(
					'user_uid' => $beUser->user['uid'],
					'public' => $inData['preset']['public'],
					'title' => $inData['preset']['title'],
					'item_uid' => $inData['pagetree']['id'],
					'preset_data' => serialize($inData)
				);
				$this->getDatabaseConnection()->exec_INSERTquery('tx_impexp_presets', $fields_values);
				$msg = 'New preset "' . htmlspecialchars($inData['preset']['title']) . '" is created';
			}
		}
		// Delete preset:
		if (isset($presetData['delete'])) {
			$preset = $this->getPreset($presetData['select']);
			if (is_array($preset)) {
				// Update existing
				if ($beUser->isAdmin() || $preset['user_uid'] === $beUser->user['uid']) {
					$this->getDatabaseConnection()->exec_DELETEquery('tx_impexp_presets', 'uid=' . (int)$preset['uid']);
					$msg = 'Preset #' . $preset['uid'] . ' deleted!';
				} else {
					$msg = 'ERROR: You were not the owner of the preset so you could not delete it.';
					$err = TRUE;
				}
			} else {
				$msg = 'ERROR: No preset selected for deletion.';
				$err = TRUE;
			}
		}
		// Load preset
		if (isset($presetData['load']) || isset($presetData['merge'])) {
			$preset = $this->getPreset($presetData['select']);
			if (is_array($preset)) {
				// Update existing
				$inData_temp = unserialize($preset['preset_data']);
				if (is_array($inData_temp)) {
					if (isset($presetData['merge'])) {
						// Merge records in:
						if (is_array($inData_temp['record'])) {
							$inData['record'] = array_merge((array) $inData['record'], $inData_temp['record']);
						}
						// Merge lists in:
						if (is_array($inData_temp['list'])) {
							$inData['list'] = array_merge((array) $inData['list'], $inData_temp['list']);
						}
					} else {
						$msg = 'Preset #' . $preset['uid'] . ' loaded!';
						$inData = $inData_temp;
					}
				} else {
					$msg = 'ERROR: No configuratio data found in preset record!';
					$err = TRUE;
				}
			} else {
				$msg = 'ERROR: No preset selected for loading.';
				$err = TRUE;
			}
		}
		// Show message:
		if ($msg !== '') {
			$this->content .= $this->doc->section('Presets', $msg, 0, 1, $err ? 3 : 1);
		}
	}

	/**
	 * Get single preset record
	 *
	 * @param integer $uid Preset record
	 * @return array Preset record, if any (otherwise FALSE)
	 * @todo Define visibility
	 */
	public function getPreset($uid) {
		return $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'tx_impexp_presets', 'uid=' . (int)$uid);
	}

	/****************************
	 * Helper functions
	 ****************************/

	/**
	 * Gets the default folder path for temporary uploads,
	 * e.g. 'fileadmin/user_uploads/_temp_/importexport/'
	 *
	 * @return boolean|string Path or FALSE otherwise
	 * @deprecated since TYPO3 CMS 6.2, will be removed two versions later - use getDefaultImportExportFolder() instead
	 */
	public function userTempFolder() {
		GeneralUtility::logDeprecatedFunction();
		return $this->getDefaultImportExportFolder()->getPublicUrl();
	}

	/**
	 * Gets the default folder path for temporary uploads,
	 * e.g. 'fileadmin/user_uploads/_temp_/importexport/'
	 *
	 * @return string Absolute path to folder where export files can be saved.
	 * @deprecated since TYPO3 CMS 6.2, will be removed two versions later - use getDefaultImportExportFolder() instead
	 */
	public function userSaveFolder() {
		GeneralUtility::logDeprecatedFunction();
		return $this->getDefaultImportExportFolder()->getPublicUrl();
	}

	/**
	 * Returns a \TYPO3\CMS\Core\Resource\Folder object for saving export files
	 * to the server and is also used for uploading import files.
	 *
	 * @return NULL|\TYPO3\CMS\Core\Resource\Folder
	 */
	protected function getDefaultImportExportFolder() {
		$defaultImportExportFolder = NULL;

		$defaultTemporaryFolder = $this->getBackendUser()->getDefaultUploadTemporaryFolder();
		if ($defaultTemporaryFolder !== NULL) {

			$importExportFolderName = 'importexport';
			$createFolder = !$defaultTemporaryFolder->hasFolder($importExportFolderName);
			if ($createFolder === TRUE) {
				try {
					$defaultImportExportFolder = $defaultTemporaryFolder->createFolder($importExportFolderName);
				} catch (\TYPO3\CMS\Core\Resource\Exception $folderAccessException) {}
			} else {
				$defaultImportExportFolder = $defaultTemporaryFolder->getSubfolder($importExportFolderName);
			}
		}

		return $defaultImportExportFolder;
	}



	/**
	 * Check if a file has been uploaded
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function checkUpload() {
		$file = GeneralUtility::_GP('file');
		// Initializing:
		$this->fileProcessor = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Utility\\File\\ExtendedFileUtility');
		$this->fileProcessor->init(array(), $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
		$this->fileProcessor->setActionPermissions();
		$this->fileProcessor->dontCheckForUnique = GeneralUtility::_GP('overwriteExistingFiles') ? 1 : 0;
		// Checking referer / executing:
		$refInfo = parse_url(GeneralUtility::getIndpEnv('HTTP_REFERER'));
		$httpHost = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
		if (
			$httpHost != $refInfo['host']
			&& !$GLOBALS['$TYPO3_CONF_VARS']['SYS']['doNotCheckReferer']
			&& $this->vC != $this->getBackendUser()->veriCode()
		) {
			$this->fileProcessor->writeLog(0, 2, 1, 'Referer host "%s" and server host "%s" did not match!', array($refInfo['host'], $httpHost));
		} else {
			$this->fileProcessor->start($file);
			$result = $this->fileProcessor->processData();
			if (!empty($result['upload'])) {
				foreach ($result['upload'] as $uploadedFiles) {
					$this->uploadedFiles += $uploadedFiles;
				}
			}
		}
	}

	/**
	 * Makes a selector-box from optValues
	 *
	 * @param string $prefix Form element name
	 * @param string $value Current value
	 * @param array $optValues Options to display (key/value pairs)
	 * @return string HTML select element
	 * @todo Define visibility
	 */
	public function renderSelectBox($prefix, $value, $optValues) {
		$opt = array();
		$isSelFlag = 0;
		foreach ($optValues as $k => $v) {
			$sel = (string)$k === (string)$value ? ' selected="selected"' : '';
			if ($sel) {
				$isSelFlag++;
			}
			$opt[] = '<option value="' . htmlspecialchars($k) . '"' . $sel . '>' . htmlspecialchars($v) . '</option>';
		}
		if (!$isSelFlag && (string)$value !== '') {
			$opt[] = '<option value="' . htmlspecialchars($value) . '" selected="selected">'
				. htmlspecialchars(('[\'' . $value . '\']')) . '</option>';
		}
		return '<select name="' . $prefix . '">' . implode('', $opt) . '</select>';
	}

	/**
	 * Returns a selector-box with TCA tables
	 *
	 * @param string $prefix Form element name prefix
	 * @param array $value The current values selected
	 * @param string $excludeList Table names (and the string "_ALL") to exclude. Comma list
	 * @return string HTML select element
	 * @todo Define visibility
	 */
	public function tableSelector($prefix, $value, $excludeList = '') {
		$optValues = array();
		if (!GeneralUtility::inList($excludeList, '_ALL')) {
			$optValues['_ALL'] = '[' . $this->lang->getLL('ALL_tables') . ']';
		}
		foreach ($GLOBALS['TCA'] as $table => $_) {
			if ($this->getBackendUser()->check('tables_select', $table) && !GeneralUtility::inList($excludeList, $table)) {
				$optValues[$table] = $table;
			}
		}
		// make box:
		$opt = array();
		$opt[] = '<option value=""></option>';
		$sel = '';
		foreach ($optValues as $k => $v) {
			if (is_array($value)) {
				$sel = in_array($k, $value) ? ' selected="selected"' : '';
			}
			$opt[] = '<option value="' . htmlspecialchars($k) . '"' . $sel . '>' . htmlspecialchars($v) . '</option>';
		}
		return '<select name="' . $prefix . '[]" multiple="multiple" size="'
			. MathUtility::forceIntegerInRange(count($opt), 5, 10) . '">' . implode('', $opt) . '</select>';
	}

	/**
	 * Returns a selector-box with loaded extension keys
	 *
	 * @param string $prefix Form element name prefix
	 * @param array $value The current values selected
	 * @return string HTML select element
	 * @todo Define visibility
	 */
	public function extensionSelector($prefix, $value) {
		$loadedExtensions = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getLoadedExtensionListArray();

		// make box:
		$opt = array();
		$opt[] = '<option value=""></option>';
		foreach ($loadedExtensions as $extensionKey) {
			$sel = '';
			if (is_array($value)) {
				$sel = in_array($extensionKey, $value) ? ' selected="selected"' : '';
			}
			$opt[] = '<option value="' . htmlspecialchars($extensionKey) . '"' . $sel . '>'
				. htmlspecialchars($extensionKey) . '</option>';
		}
		return '<select name="' . $prefix . '[]" multiple="multiple" size="'
			. MathUtility::forceIntegerInRange(count($opt), 5, 10) . '">' . implode('', $opt) . '</select>';
	}

	/**
	 * Filter page IDs by traversing exclude array, finding all
	 * excluded pages (if any) and making an AND NOT IN statement for the select clause.
	 *
	 * @param array $exclude Exclude array from import/export object.
	 * @return string AND where clause part to filter out page uids.
	 * @todo Define visibility
	 */
	public function filterPageIds($exclude) {
		// Get keys:
		$exclude = array_keys($exclude);
		// Traverse
		$pageIds = array();
		foreach ($exclude as $element) {
			list($table, $uid) = explode(':', $element);
			if ($table === 'pages') {
				$pageIds[] = (int)$uid;
			}
		}
		// Add to clause:
		if (count($pageIds)) {
			return ' AND uid NOT IN (' . implode(',', $pageIds) . ')';
		}
		return '';
	}

	/**
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * @return DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * Gets thumbnail files.
	 *
	 * @return array|\TYPO3\CMS\Core\Resource\File[]
	 */
	protected function getThumbnailFiles() {
		$thumbnailFiles = array();
		$defaultTemporaryFolder = $this->getDefaultImportExportFolder();

		if ($defaultTemporaryFolder === FALSE) {
			return $thumbnailFiles;
		}

		/** @var $filter \TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter */
		$filter = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Filter\\FileExtensionFilter');
		$filter->setAllowedFileExtensions(array('png', 'gif', 'jpg'));
		$defaultTemporaryFolder->getStorage()->addFileAndFolderNameFilter(array($filter, 'filterFileList'));
		$thumbnailFiles = $defaultTemporaryFolder->getFiles();

		return $thumbnailFiles;
	}

	/**
	 * Gets all export files.
	 *
	 * @return array|\TYPO3\CMS\Core\Resource\File[]
	 */
	protected function getExportFiles() {
		$exportFiles = array();

		$folder = $this->getDefaultImportExportFolder();
		if ($folder !== FALSE) {

			/** @var $filter \TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter */
			$filter = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Filter\\FileExtensionFilter');
			$filter->setAllowedFileExtensions(array('t3d', 'xml'));
			$folder->getStorage()->addFileAndFolderNameFilter(array($filter, 'filterFileList'));

			$exportFiles = $folder->getFiles();
		}

		return $exportFiles;
	}

	/**
	 * Gets a file by combined identifier.
	 *
	 * @param string $combinedIdentifier
	 * @return NULL|\TYPO3\CMS\Core\Resource\File
	 */
	protected function getFile($combinedIdentifier) {
		try {
			$file = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFileObjectFromCombinedIdentifier($combinedIdentifier);
		} catch (\Exception $exception) {
			$file = NULL;
		}

		return $file;
	}

	/**
	 * Internal function to retrieve the indexer service,
	 * if it does not exist, an instance will be created
	 *
	 * @return \TYPO3\CMS\Core\Resource\Service\IndexerService
	 */
	protected function getIndexerService() {
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Service\\IndexerService');
	}


}
