<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Import / Export module
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  137: class localPageTree extends t3lib_browseTree
 *  144:     function localPageTree()
 *  155:     function wrapTitle($title,$v)
 *  168:     function PM_ATagWrap($icon,$cmd,$bMark='')
 *  179:     function wrapIcon($icon,$row)
 *  188:     function permsC()
 *  199:     function ext_tree($pid, $clause='')
 *
 *
 *  280: class SC_mod_tools_log_index extends t3lib_SCbase
 *  289:     function main()
 *  359:     function printContent()
 *
 *              SECTION: EXPORT FUNCTIONS
 *  387:     function exportData($inData)
 *  661:     function addRecordsForPid($k, $tables, $maxNumber)
 *  687:     function exec_listQueryPid($table,$pid,$limit)
 *  717:     function makeConfigurationForm($inData, &$row)
 *  885:     function makeAdvancedOptionsForm($inData, &$row)
 *  933:     function makeSaveForm($inData, &$row)
 *
 *              SECTION: IMPORT FUNCTIONS
 * 1064:     function importData($inData)
 *
 *              SECTION: Preset functions
 * 1363:     function processPresets(&$inData)
 * 1458:     function getPreset($uid)
 *
 *              SECTION: Helper functions
 * 1484:     function userTempFolder()
 * 1500:     function userSaveFolder()
 * 1523:     function checkUpload()
 * 1553:     function renderSelectBox($prefix,$value,$optValues)
 * 1576:     function tableSelector($prefix,$value,$excludeList='')
 * 1612:     function extensionSelector($prefix,$value)
 * 1637:     function filterPageIds($exclude)
 *
 * TOTAL FUNCTIONS: 24
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
/**
 * 	IMPORTING DATA:
 *
 * 	Incoming array has syntax:
 * 		GETvar 'id' = import page id (must be readable)
 *
 * 		file = 	(pointing to filename relative to PATH_site)
 *
 *
 *
 * 		[all relation fields are clear, but not files]
 * 		- page-tree is written first
 * 		- then remaining pages (to the root of import)
 * 		- then all other records are written either to related included pages or if not found to import-root (should be a sysFolder in most cases)
 * 		- then all internal relations are set and non-existing relations removed, relations to static tables preserved.
 *
 * 	EXPORTING DATA:
 *
 * 	Incoming array has syntax:
 *
 * 		file[] = file
 * 		dir[] = dir
 * 		list[] = table:pid
 * 		record[] = table:uid
 *
 * 		pagetree[id] = (single id)
 * 		pagetree[levels]=1,2,3, -1 = currently unpacked tree, -2 = only tables on page
 * 		pagetree[tables][]=table/_ALL
 *
 * 		external_ref[tables][]=table/_ALL
 */

unset($MCONF);
require ('conf.php');
require_once ($BACK_PATH.'init.php');
require_once ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:impexp/app/locallang.php');
require_once (t3lib_extMgm::extPath('impexp').'class.tx_impexp.php');

t3lib_extMgm::isLoaded('impexp',1);





/**
 * Extension of the page tree class. Used to get the tree of pages to export.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_impexp
 */
class localPageTree extends t3lib_browseTree {

	/**
	 * Initialization
	 *
	 * @return	void
	 */
	function localPageTree() {
		$this->init();
	}

	/**
	 * Wrapping title from page tree.
	 *
	 * @param	string		Title to wrap
	 * @param	mixed		(See parent class)
	 * @return	string		Wrapped title
	 */
	function wrapTitle($title,$v)	{
		$title = (!strcmp(trim($title),'')) ? '<em>['.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.no_title',1).']</em>' : htmlspecialchars($title);
		return $title;
	}

	/**
	 * Wrapping Plus/Minus icon
	 *
	 * @param	string		Icon HTML
	 * @param	mixed		(See parent class)
	 * @param	mixed		(See parent class)
	 * @return	string		Icon HTML
	 */
	function PM_ATagWrap($icon,$cmd,$bMark='')	{
		return $icon;
	}

	/**
	 * Wrapping Icon
	 *
	 * @param	string		Icon HTML
	 * @param	array		Record row (page)
	 * @return	string		Icon HTML
	 */
	function wrapIcon($icon,$row)	{
		return $icon;
	}

	/**
	 * Select permissions
	 *
	 * @return	string		SQL where clause
	 */
	function permsC()	{
		return $this->BE_USER->getPagePermsClause(1);
	}

	/**
	 * Tree rendering
	 *
	 * @param	integer		PID value
	 * @param	string		Additional where clause
	 * @return	array		Array of tree elements
	 */
	function ext_tree($pid, $clause='')	{

			// Initialize:
		$this->init(' AND '.$this->permsC().$clause);

			// Get stored tree structure:
		$this->stored = unserialize($this->BE_USER->uc['browseTrees']['browsePages']);

			// PM action:
		$PM = t3lib_div::intExplode('_',t3lib_div::_GP('PM'));

			// traverse mounts:
		$titleLen = intval($this->BE_USER->uc['titleLen']);
		$treeArr = array();

		$idx = 0;

			// Set first:
		$this->bank = $idx;
		$isOpen = $this->stored[$idx][$pid] || $this->expandFirst;

		$curIds = $this->ids;	// save ids
		$this->reset();
		$this->ids = $curIds;

			// Set PM icon:
		$cmd = $this->bank.'_'.($isOpen?'0_':'1_').$pid;
		$icon = '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/ol/'.($isOpen?'minus':'plus').'only.gif','width="18" height="16"').' align="top" alt="" />';
		$firstHtml = $this->PM_ATagWrap($icon,$cmd);

		if ($pid>0)	{
			$rootRec = t3lib_befunc::getRecordWSOL('pages',$pid);
			$firstHtml.= $this->wrapIcon(t3lib_iconWorks::getIconImage('pages',$rootRec,$this->backPath,'align="top"'),$rootRec);
		} else {
			$rootRec = array(
				'title' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
				'uid' => 0
			);
			$firstHtml.= $this->wrapIcon('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/i/_icon_website.gif','width="18" height="16"').' align="top" alt="" />',$rootRec);
		}
		$this->tree[] = array('HTML'=>$firstHtml, 'row'=>$rootRec);
		if ($isOpen)	{
				// Set depth:
			$depthD = '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/ol/blank.gif','width="18" height="16"').' align="top" alt="" />';
			if ($this->addSelfId)	$this->ids[] = $pid;
			$this->getTree($pid,999,$depthD);

			$idH = array();
			$idH[$pid]['uid'] = $pid;
			if (count($this->buffer_idH))	$idH[$pid]['subrow'] = $this->buffer_idH;
			$this->buffer_idH = $idH;

		}

			// Add tree:
		$treeArr = array_merge($treeArr,$this->tree);

		return $treeArr;
	}
}














/**
 * Main script class for the Import / Export facility
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_impexp
 */
class SC_mod_tools_log_index extends t3lib_SCbase {

	var $pageinfo;			// array containing the current page.

	/**
	 * Main module function
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH;

			// Start document template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->bodyTagId = 'imp-exp-mod';
		$this->doc->setModuleTemplate(t3lib_extMgm::extRelPath('impexp') . '/app/template.html');


		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);

				// JavaScript
		$this->doc->JScode = $this->doc->wrapScriptTags('
			script_ended = 0;
			function jumpToUrl(URL)	{	//
				window.location.href = URL;
			}
		');

			// Set up JS for dynamic tab menu
		$this->doc->JScode .= $this->doc->getDynTabMenuJScode();

		// Setting up the context sensitive menu:
		$this->doc->getContextMenuCode();

		$this->doc->postCode = $this->doc->wrapScriptTags('
			script_ended = 1;
			if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
		');
		$this->doc->form = '<form action="'.htmlspecialchars($GLOBALS['MCONF']['_']).'" method="post" enctype="'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'].'"><input type="hidden" name="id" value="'.$this->id.'" />';

		$this->content.= $this->doc->header($LANG->getLL('title'));
		$this->content.= $this->doc->spacer(5);

			// Input data grabbed:
		$inData = t3lib_div::_GP('tx_impexp');

		$this->checkUpload();

		switch((string)$inData['action'])	{
			case 'export':

					// Finally: If upload went well, set the new file as the thumbnail in the $inData array:
				if (is_object($this->fileProcessor) && $this->fileProcessor->internalUploadMap[1])	{
					$inData['meta']['thumbnail'] = md5($this->fileProcessor->internalUploadMap[1]);
				}

					// Call export interface
				$this->exportData($inData);
			break;
			case 'import':

					// Finally: If upload went well, set the new file as the import file:
				if (is_object($this->fileProcessor) && $this->fileProcessor->internalUploadMap[1])	{
					$fI = pathinfo($this->fileProcessor->internalUploadMap[1]);
					if (t3lib_div::inList('t3d,xml',strtolower($fI['extension'])))	{	// Only allowed extensions....
						$inData['file'] = $this->fileProcessor->internalUploadMap[1];
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
		$this->content = $this->doc->startPage($LANG->getLL('title'));
		$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Print the content
	 *
	 * @return	void
	 */
	function printContent()	{
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
			'record_list' => '',
			'shortcut' => ''
		);

		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('tx_impexp', '', $this->MCONF['name']);
		}

		// Input data grabbed:
		$inData = t3lib_div::_GP('tx_impexp');
		if((string)$inData['action'] == 'import') {
			if (($this->id && is_array($this->pageinfo)) || ($GLOBALS['BE_USER']->user['admin'] && !$this->id))	{
				if (is_array($this->pageinfo) && $this->pageinfo['uid']) {
					// View
					$buttons['view'] = '<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::viewOnClick($this->pageinfo['uid'], $this->doc->backPath, t3lib_BEfunc::BEgetRootLine($this->pageinfo['uid']))) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showPage', TRUE) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-document-view') . 
				  '</a>';

					// Record list
					if ($GLOBALS['BE_USER']->check('modules', 'web_list')) {
						$href = $this->doc->backPath . 'db_list.php?id=' . $this->pageinfo['uid'] . '&returnUrl=' . rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'));
						$buttons['record_list'] = '<a href="' . htmlspecialchars($href) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showList', TRUE) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-system-list-open') . 
					  '</a>';
					}
				}
			}
		}

		return $buttons;
	}





	/**************************
	 *
	 * EXPORT FUNCTIONS
	 *
	 **************************/

	/**
	 * Export part of module
	 *
	 * @param	array		Content of POST VAR tx_impexp[]..
	 * @return	void		Setting content in $this->content
	 */
	function exportData($inData)	{
		global $TCA, $LANG;

			// BUILDING EXPORT DATA:

			// Processing of InData array values:
		$inData['pagetree']['maxNumber'] = t3lib_div::intInRange($inData['pagetree']['maxNumber'],1,10000,100);
		$inData['listCfg']['maxNumber'] = t3lib_div::intInRange($inData['listCfg']['maxNumber'],1,10000,100);
		$inData['maxFileSize'] = t3lib_div::intInRange($inData['maxFileSize'],1,10000,1000);
		$inData['filename'] = trim(preg_replace('/[^[:alnum:]._-]*/','',preg_replace('/\.(t3d|xml)$/','',$inData['filename'])));
		if (strlen($inData['filename']))	{
			$inData['filename'].= $inData['filetype']=='xml' ? '.xml' : '.t3d';
		}

			// Set exclude fields in export object:
		if (!is_array($inData['exclude']))	{
			$inData['exclude'] = array();
		}


			// Saving/Loading/Deleting presets:
		$this->processPresets($inData);

			// Create export object and configure it:
		$this->export = t3lib_div::makeInstance('tx_impexp');
		$this->export->init(0,'export');
		$this->export->setCharset($LANG->charSet);

		$this->export->maxFileSize = $inData['maxFileSize']*1024;
		$this->export->excludeMap = (array)$inData['exclude'];
		$this->export->softrefCfg = (array)$inData['softrefCfg'];
		$this->export->extensionDependencies = (array)$inData['extension_dep'];
		$this->export->showStaticRelations = $inData['showStaticRelations'];

		$this->export->includeExtFileResources = !$inData['excludeHTMLfileResources'];

			// Static tables:
		if (is_array($inData['external_static']['tables']))	{
			$this->export->relStaticTables = $inData['external_static']['tables'];
		}

			// Configure which tables external relations are included for:
		if (is_array($inData['external_ref']['tables']))	{
			$this->export->relOnlyTables = $inData['external_ref']['tables'];
		}
		$this->export->setHeaderBasics();

			// Meta data setting:
		$this->export->setMetaData(
			$inData['meta']['title'],
			$inData['meta']['description'],
			$inData['meta']['notes'],
			$GLOBALS['BE_USER']->user['username'],
			$GLOBALS['BE_USER']->user['realName'],
			$GLOBALS['BE_USER']->user['email']
		);
		if ($inData['meta']['thumbnail'])	{
			$tempDir = $this->userTempFolder();
			if ($tempDir)	{
				$thumbnails = t3lib_div::getFilesInDir($tempDir,'png,gif,jpg',1);
				$theThumb = $thumbnails[$inData['meta']['thumbnail']];
				if ($theThumb)	{
					$this->export->addThumbnail($theThumb);
				}
			}
		}


			// Configure which records to export
		if (is_array($inData['record']))	{
			foreach($inData['record'] as $ref)	{
				$rParts = explode(':',$ref);
				$this->export->export_addRecord($rParts[0],t3lib_BEfunc::getRecord($rParts[0],$rParts[1]));
			}
		}

			// Configure which tables to export
		if (is_array($inData['list']))	{
			foreach($inData['list'] as $ref)	{
				$rParts = explode(':',$ref);
				if ($GLOBALS['BE_USER']->check('tables_select',$rParts[0]))	{
					$res = $this->exec_listQueryPid($rParts[0],$rParts[1],t3lib_div::intInRange($inData['listCfg']['maxNumber'],1));
					while($subTrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
						$this->export->export_addRecord($rParts[0],$subTrow);
					}
				}
			}
		}

			// Pagetree
		if (isset($inData['pagetree']['id']))	{
			if ($inData['pagetree']['levels']==-1)	{	// Based on click-expandable tree
				$pagetree = t3lib_div::makeInstance('localPageTree');

				$tree = $pagetree->ext_tree($inData['pagetree']['id'],$this->filterPageIds($this->export->excludeMap));
				$this->treeHTML = $pagetree->printTree($tree);

				$idH = $pagetree->buffer_idH;
			} elseif ($inData['pagetree']['levels']==-2)	{	// Only tables on page
				$this->addRecordsForPid($inData['pagetree']['id'],$inData['pagetree']['tables'],$inData['pagetree']['maxNumber']);
			} else {	// Based on depth
					// Drawing tree:
					// If the ID is zero, export root
				if (!$inData['pagetree']['id'] && $GLOBALS['BE_USER']->isAdmin())	{
					$sPage = array(
						'uid' => 0,
						'title' => 'ROOT'
					);
				} else {
					$sPage = t3lib_BEfunc::getRecordWSOL('pages',$inData['pagetree']['id'],'*',' AND '.$this->perms_clause);
				}
				if (is_array($sPage))	{
					$pid = $inData['pagetree']['id'];
					$tree = t3lib_div::makeInstance('t3lib_pageTree');
					$tree->init('AND '.$this->perms_clause.$this->filterPageIds($this->export->excludeMap));

					$HTML = t3lib_iconWorks::getIconImage('pages',$sPage,$GLOBALS['BACK_PATH'],'align="top"');
					$tree->tree[] = Array('row'=>$sPage,'HTML'=>$HTML);
					$tree->buffer_idH = array();
					if ($inData['pagetree']['levels']>0)	{
						$tree->getTree($pid,$inData['pagetree']['levels'],'');
					}

					$idH = array();
					$idH[$pid]['uid'] = $pid;
					if (count($tree->buffer_idH))	{
						$idH[$pid]['subrow'] = $tree->buffer_idH;
					}

					$pagetree = t3lib_div::makeInstance('localPageTree');
					$this->treeHTML = $pagetree->printTree($tree->tree);
				}
			}
				// In any case we should have a multi-level array, $idH, with the page structure here (and the HTML-code loaded into memory for nice display...)
			if (is_array($idH))	{
				$flatList = $this->export->setPageTree($idH);	// Sets the pagetree and gets a 1-dim array in return with the pages (in correct submission order BTW...)
				foreach ($flatList as $k => $value) {
					$this->export->export_addRecord('pages',t3lib_BEfunc::getRecord('pages',$k));
					$this->addRecordsForPid($k,$inData['pagetree']['tables'],$inData['pagetree']['maxNumber']);
				}
			}
		}

			// After adding ALL records we set relations:
		for($a=0;$a<10;$a++)	{
			$addR = $this->export->export_addDBRelations($a);
			if (!count($addR)) {
				break;
			}
		}

			// Finally files are added:
		$this->export->export_addFilesFromRelations();	// MUST be after the DBrelations are set so that files from ALL added records are included!
			// If the download button is clicked, return file
		if ($inData['download_export'] || $inData['save_export'])	{
			switch((string)$inData['filetype'])	{
				case 'xml':
					$out = $this->export->compileMemoryToFileContent('xml');
					$fExt = '.xml';
				break;
				case 't3d':
					$this->export->dontCompress = 1;
				default:
					$out = $this->export->compileMemoryToFileContent();
					$fExt = ($this->export->doOutputCompress()?'-z':'').'.t3d';
				break;
			}

				// Filename:
			$dlFile = $inData['filename'] ? $inData['filename'] : 'T3D_'.substr(preg_replace('/[^[:alnum:]_]/','-',$inData['download_export_name']),0,20).'_'.date('d-m-H-i-s').$fExt;

				// Export for download:
			if ($inData['download_export'])	{
				$mimeType = 'application/octet-stream';
				Header('Content-Type: '.$mimeType);
				Header('Content-Length: '.strlen($out));
				Header('Content-Disposition: attachment; filename='.basename($dlFile));

				echo $out;
				exit;
			}

				// Export by saving:
			if ($inData['save_export'])	{
				$savePath = $this->userSaveFolder();
				$fullName = $savePath.$dlFile;

				if (t3lib_div::isAllowedAbsPath($savePath) && @is_dir(dirname($fullName)) && t3lib_div::isAllowedAbsPath($fullName))	{
					t3lib_div::writeFile($fullName, $out);
					$this->content.= $this->doc->section($LANG->getLL('exportdata_savedFile'),sprintf($LANG->getLL('exportdata_savedInSBytes',1), substr($savePath.$dlFile,strlen(PATH_site)), t3lib_div::formatSize(strlen($out))),0,1);
				} else {
					$this->content.= $this->doc->section($LANG->getLL('exportdata_problemsSavingFile'),sprintf($LANG->getLL('exportdata_badPathS',1),$fullName),0,1,2);
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
			'label' => $LANG->getLL('tableselec_configuration'),
			'content' => '
				<table border="0" cellpadding="1" cellspacing="1">
					'.implode('
					',$row).'
				</table>
			'
		);

			// File options
		$row = array();
		$this->makeSaveForm($inData, $row);
		$menuItems[] = array(
			'label' => $LANG->getLL('exportdata_filePreset'),
			'content' => '
				<table border="0" cellpadding="1" cellspacing="1">
					'.implode('
					',$row).'
				</table>
			'
		);

			// File options
		$row = array();
		$this->makeAdvancedOptionsForm($inData, $row);
		$menuItems[] = array(
			'label' => $LANG->getLL('exportdata_advancedOptions'),
			'content' => '
				<table border="0" cellpadding="1" cellspacing="1">
					'.implode('
					',$row).'
				</table>
			'
		);

			// Generate overview:
		$overViewContent = $this->export->displayContentOverview();

			// Print errors that might be:
		$errors = $this->export->printErrorLog();
		$menuItems[] = array(
			'label' => $LANG->getLL('exportdata_messages'),
			'content' => $errors,
			'stateIcon' => $errors ? 2 : 0
		);

			// Add hidden fields and create tabs:
		$content = $this->doc->getDynTabMenu($menuItems,'tx_impexp_export',-1);
		$content.= '<input type="hidden" name="tx_impexp[action]" value="export" />';
		$this->content.= $this->doc->section('',$content,0,1);

			// Output Overview:
		$this->content.= $this->doc->section($LANG->getLL('execlistqu_structureToBeExported'),$overViewContent,0,1);

	}

	/**
	 * Adds records to the export object for a specific page id.
	 *
	 * @param	integer		Page id for which to select records to add
	 * @param	array		Array of table names to select from
	 * @param	integer		Max amount of records to select
	 * @return	void
	 */
	function addRecordsForPid($k, $tables, $maxNumber)	{
		global $TCA;

		if (is_array($tables))	{
			foreach ($TCA as $table => $value) {
				if ($table!='pages' && (in_array($table,$tables) || in_array('_ALL',$tables)))	{
					if ($GLOBALS['BE_USER']->check('tables_select',$table) && !$TCA[$table]['ctrl']['is_static'])	{
						$res = $this->exec_listQueryPid($table,$k,t3lib_div::intInRange($maxNumber,1));
						while($subTrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
							$this->export->export_addRecord($table,$subTrow);
						}
					}
				}
			}
		}
	}

	/**
	 * Selects records from table / pid
	 *
	 * @param	string		Table to select from
	 * @param	integer		Page ID to select from
	 * @param	integer		Max number of records to select
	 * @return	pointer		SQL resource pointer
	 */
	function exec_listQueryPid($table,$pid,$limit)	{
		global $TCA, $LANG;

		$orderBy = $TCA[$table]['ctrl']['sortby'] ? 'ORDER BY '.$TCA[$table]['ctrl']['sortby'] : $TCA[$table]['ctrl']['default_sortby'];
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				$table,
				'pid='.intval($pid).
					t3lib_BEfunc::deleteClause($table).
					t3lib_BEfunc::versioningPlaceholderClause($table),
				'',
				$GLOBALS['TYPO3_DB']->stripOrderBy($orderBy),
				$limit
			);

			// Warning about hitting limit:
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) == $limit)	{
			$this->content.= $this->doc->section($LANG->getLL('execlistqu_maxNumberLimit'),sprintf($LANG->getLL('makeconfig_anSqlQueryReturned',1),$limit),0,1, 2);
		}

		return $res;
	}

	/**
	 * Create configuration form
	 *
	 * @param	array		Form configurat data
	 * @param	array		Table row accumulation variable. This is filled with table rows.
	 * @return	void		Sets content in $this->content
	 */
	function makeConfigurationForm($inData, &$row)	{
		global $LANG;

		$nameSuggestion = '';

			// Page tree export options:
		if (isset($inData['pagetree']['id']))	{

			$nameSuggestion.= 'tree_PID'.$inData['pagetree']['id'].'_L'.$inData['pagetree']['levels'];

			$row[] = '
				<tr class="tableheader bgColor5">
					<td colspan="2">'.$LANG->getLL('makeconfig_exportPagetreeConfiguration',1).t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'pageTreeCfg', $GLOBALS['BACK_PATH'],'').'</td>
				</tr>';

			$row[] = '
				<tr class="bgColor4">
					<td><strong>'.$LANG->getLL('makeconfig_pageId',1).'</strong></td>
					<td>'.htmlspecialchars($inData['pagetree']['id']).
						'<input type="hidden" value="'.htmlspecialchars($inData['pagetree']['id']).'" name="tx_impexp[pagetree][id]" /></td>
				</tr>';

			$row[] = '
				<tr class="bgColor4">
					<td><strong>'.$LANG->getLL('makeconfig_tree',1).'</strong>'.t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'pageTreeDisplay', $GLOBALS['BACK_PATH'],'').'</td>
					<td>'.($this->treeHTML ? $this->treeHTML : $LANG->getLL('makeconfig_noTreeExportedOnly',1)).'</td>
				</tr>';

			$opt = array(
				'-2' => $LANG->getLL('makeconfig_tablesOnThisPage'),
				'-1' => $LANG->getLL('makeconfig_expandedTree'),
				'0' => $LANG->getLL('makeconfig_onlyThisPage'),
				'1' => $LANG->getLL('makeconfig_1Level'),
				'2' => $LANG->getLL('makeconfig_2Levels'),
				'3' => $LANG->getLL('makeconfig_3Levels'),
				'4' => $LANG->getLL('makeconfig_4Levels'),
				'999' => $LANG->getLL('makeconfig_infinite')
			);
			$row[] = '
				<tr class="bgColor4">
					<td><strong>'.$LANG->getLL('makeconfig_levels',1).'</strong>'.t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'pageTreeMode', $GLOBALS['BACK_PATH'],'').'</td>
					<td>'.$this->renderSelectBox('tx_impexp[pagetree][levels]',$inData['pagetree']['levels'],$opt).'</td>
				</tr>';

			$row[] = '
				<tr class="bgColor4">
					<td><strong>'.$LANG->getLL('makeconfig_includeTables',1).'</strong>'.t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'pageTreeRecordLimit', $GLOBALS['BACK_PATH'],'').'</td>
					<td>'.$this->tableSelector('tx_impexp[pagetree][tables]',$inData['pagetree']['tables'],'pages').'<br/>
						'.$LANG->getLL('makeconfig_maxNumberOfRecords',1).'<br/>
						<input type="text" name="tx_impexp[pagetree][maxNumber]" value="'.htmlspecialchars($inData['pagetree']['maxNumber']).'"'.$this->doc->formWidth(10).' /><br/>
					</td>
				</tr>';
		}

			// Single record export:
		if (is_array($inData['record']))	{
			$row[] = '
				<tr class="tableheader bgColor5">
					<td colspan="2">'.$LANG->getLL('makeconfig_exportSingleRecord',1).t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'singleRecord', $GLOBALS['BACK_PATH'],'').'</td>
				</tr>';
			foreach($inData['record'] as $ref)	{
				$rParts = explode(':', $ref);
				$tName = $rParts[0];
				$rUid = $rParts[1];
				$nameSuggestion.= $tName.'_'.$rUid;
				$rec = t3lib_BEfunc::getRecordWSOL($tName,$rUid);

				$row[] = '
				<tr class="bgColor4">
					<td><strong>'.$LANG->getLL('makeconfig_record',1).'</strong></td>
					<td>'.t3lib_iconworks::getIconImage($tName,$rec,$GLOBALS['BACK_PATH'],' align="top"').
						t3lib_BEfunc::getRecordTitle($tName,$rec,TRUE).
						'<input type="hidden" name="tx_impexp[record][]" value="'.htmlspecialchars($tName.':'.$rUid).'" /></td>
				</tr>';
			}
		}

			// Single tables/pids:
		if (is_array($inData['list']))	{
			$row[] = '
				<tr class="tableheader bgColor5">
					<td colspan="2">'.$LANG->getLL('makeconfig_exportTablesFromPages',1).'</td>
				</tr>';

			$tblList = '';
			foreach($inData['list'] as $ref)	{
				$rParts = explode(':', $ref);
				$tName = $rParts[0];

				if ($GLOBALS['BE_USER']->check('tables_select',$tName))	{
					$rec = t3lib_BEfunc::getRecordWSOL('pages', $rParts[1]);
					$tblList.='Table "'.$tName.'" from '.t3lib_iconworks::getIconImage('pages',$rec,$GLOBALS['BACK_PATH'],' align="top"').
					t3lib_BEfunc::getRecordTitle('pages',$rec,TRUE).
					'<input type="hidden" name="tx_impexp[list][]" value="'.htmlspecialchars($ref).'" /><br/>';
				}
			}
			$row[] = '
			<tr class="bgColor4">
				<td><strong>'.$LANG->getLL('makeconfig_tablePids',1).'</strong>'.t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'tableList', $GLOBALS['BACK_PATH'],'').'</td>
				<td>'.$tblList.'</td>
			</tr>';

			$row[] = '
				<tr class="bgColor4">
					<td><strong>'.$LANG->getLL('makeconfig_maxNumberOfRecords',1).t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'tableListMaxNumber', $GLOBALS['BACK_PATH'],'').'</strong></td>
					<td>
						<input type="text" name="tx_impexp[listCfg][maxNumber]" value="'.htmlspecialchars($inData['listCfg']['maxNumber']).'"'.$this->doc->formWidth(10).' /><br/>
					</td>
				</tr>';
		}


		$row[] = '
			<tr class="tableheader bgColor5">
				<td colspan="2">'.$LANG->getLL('makeconfig_relationsAndExclusions',1).'</td>
			</tr>';

			// Add relation selector:
		$row[] = '
				<tr class="bgColor4">
					<td><strong>'.$LANG->getLL('makeconfig_includeRelationsToTables',1).'</strong>'.t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'inclRelations', $GLOBALS['BACK_PATH'],'').'</td>
					<td>'.$this->tableSelector('tx_impexp[external_ref][tables]',$inData['external_ref']['tables']).'</td>
				</tr>';

			// Add static relation selector:
		$row[] = '
				<tr class="bgColor4">
					<td><strong>'.$LANG->getLL('makeconfig_useStaticRelationsFor',1).'</strong>'.t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'staticRelations', $GLOBALS['BACK_PATH'],'').'</td>
					<td>'.$this->tableSelector('tx_impexp[external_static][tables]',$inData['external_static']['tables']).'<br/>
						<label for="checkShowStaticRelations">'.$LANG->getLL('makeconfig_showStaticRelations',1).'</label> <input type="checkbox" name="tx_impexp[showStaticRelations]" id="checkShowStaticRelations" value="1"'.($inData['showStaticRelations'] ? ' checked="checked"' : '').' />
						</td>
				</tr>';

			// Exclude:
		$excludeHiddenFields = '';
		if (is_array($inData['exclude']))	{
			foreach($inData['exclude'] as $key => $value)	{
				$excludeHiddenFields.= '<input type="hidden" name="tx_impexp[exclude]['.$key.']" value="1" />';
			}
		}
		$row[] = '
				<tr class="bgColor4">
					<td><strong>'.$LANG->getLL('makeconfig_excludeElements',1).'</strong>'.t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'excludedElements', $GLOBALS['BACK_PATH'],'').'</td>
					<td>'.$excludeHiddenFields.'
					'.(count($inData['exclude']) ? '<em>'.implode(', ',array_keys($inData['exclude'])).'</em><hr/><label for="checkExclude">'.$LANG->getLL('makeconfig_clearAllExclusions',1).'</label> <input type="checkbox" name="tx_impexp[exclude]" id="checkExclude" value="1" />' : $LANG->getLL('makeconfig_noExcludedElementsYet',1)).'
					</td>
				</tr>';


			// Add buttons:
		$row[] = '
				<tr class="bgColor4">
					<td>&nbsp;</td>
					<td>
						<input type="submit" value="'.$LANG->getLL('makeadvanc_update',1).'" />
						<input type="hidden" name="tx_impexp[download_export_name]" value="'.substr($nameSuggestion,0,30).'" />
					</td>
				</tr>';

	}

	/**
	 * Create advanced options form
	 *
	 * @param	array		Form configurat data
	 * @param	array		Table row accumulation variable. This is filled with table rows.
	 * @return	void		Sets content in $this->content
	 */
	function makeAdvancedOptionsForm($inData, &$row)	{
		global $LANG;

			// Soft references
		$row[] = '
			<tr class="tableheader bgColor5">
				<td colspan="2">'.$LANG->getLL('makeadvanc_softReferences',1).'</td>
			</tr>';
		$row[] = '
				<tr class="bgColor4">
					<td><label for="checkExcludeHTMLfileResources"><strong>'.$LANG->getLL('makeadvanc_excludeHtmlCssFile',1).'</strong></label>'.t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'htmlCssResources', $GLOBALS['BACK_PATH'],'').'</td>
					<td><input type="checkbox" name="tx_impexp[excludeHTMLfileResources]" id="checkExcludeHTMLfileResources" value="1"'.($inData['excludeHTMLfileResources'] ? ' checked="checked"' : '').' /></td>
				</tr>';


			// Extensions
		$row[] = '
			<tr class="tableheader bgColor5">
				<td colspan="2">'.$LANG->getLL('makeadvanc_extensionDependencies',1).'</td>
			</tr>';
		$row[] = '
				<tr class="bgColor4">
					<td><strong>'.$LANG->getLL('makeadvanc_selectExtensionsThatThe',1).'</strong>'.t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'extensionDependencies', $GLOBALS['BACK_PATH'],'').'</td>
					<td>'.$this->extensionSelector('tx_impexp[extension_dep]',$inData['extension_dep']).'</td>
				</tr>';



			// Add buttons:
		$row[] = '
				<tr class="bgColor4">
					<td>&nbsp;</td>
					<td>
						<input type="submit" value="'.$LANG->getLL('makesavefo_update',1).'" />
						<input type="hidden" name="tx_impexp[download_export_name]" value="'.substr($nameSuggestion,0,30).'" />
					</td>
				</tr>';


	}

	/**
	 * Create configuration form
	 *
	 * @param	array		Form configurat data
	 * @param	array		Table row accumulation variable. This is filled with table rows.
	 * @return	void		Sets content in $this->content
	 */
	function makeSaveForm($inData, &$row)	{
		global $LANG;

			// Presets:
		$row[] = '
			<tr class="tableheader bgColor5">
				<td colspan="2">'.$LANG->getLL('makesavefo_presets',1).'</td>
			</tr>';

		$opt = array('');
		$presets = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
						'*',
						'tx_impexp_presets',
						'(public>0 OR user_uid='.intval($GLOBALS['BE_USER']->user['uid']).')'.
							($inData['pagetree']['id'] ? ' AND (item_uid='.intval($inData['pagetree']['id']).' OR item_uid=0)' : '')

					);
		if (is_array($presets))	{
			foreach($presets as $presetCfg)	{
				$opt[$presetCfg['uid']] = $presetCfg['title'].' ['.$presetCfg['uid'].']'.
											($presetCfg['public'] ? ' [Public]' : '').
											($presetCfg['user_uid']===$GLOBALS['BE_USER']->user['uid'] ? ' [Own]' : '');
			}
		}

		$row[] = '
				<tr class="bgColor4">
					<td><strong>'.$LANG->getLL('makesavefo_presets',1).'</strong>'.t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'presets', $GLOBALS['BACK_PATH'],'').'</td>
					<td>
						'.$LANG->getLL('makesavefo_selectPreset',1).'<br/>
						'.$this->renderSelectBox('preset[select]','',$opt).'
						<br/>
						<input type="submit" value="'.$LANG->getLL('makesavefo_load',1).'" name="preset[load]" />
						<input type="submit" value="'.$LANG->getLL('makesavefo_save',1).'" name="preset[save]" onclick="return confirm(\''.$LANG->getLL('makesavefo_areYouSure',1).'\');" />
						<input type="submit" value="'.$LANG->getLL('makesavefo_delete',1).'" name="preset[delete]" onclick="return confirm(\''.$LANG->getLL('makesavefo_areYouSure',1).'\');" />
						<input type="submit" value="'.$LANG->getLL('makesavefo_merge',1).'" name="preset[merge]" onclick="return confirm(\''.$LANG->getLL('makesavefo_areYouSure',1).'\');" />
						<br/>
						'.$LANG->getLL('makesavefo_titleOfNewPreset',1).'
						<input type="text" name="tx_impexp[preset][title]" value="'.htmlspecialchars($inData['preset']['title']).'"'.$this->doc->formWidth(30).' /><br/>
						<label for="checkPresetPublic">'.$LANG->getLL('makesavefo_public',1).'</label>
						<input type="checkbox" name="tx_impexp[preset][public]" id="checkPresetPublic" value="1"'.($inData['preset']['public'] ? ' checked="checked"' : '').' /><br/>
					</td>
				</tr>';

			// Output options:
		$row[] = '
			<tr class="tableheader bgColor5">
				<td colspan="2">'.$LANG->getLL('makesavefo_outputOptions',1).'</td>
			</tr>';

			// Meta data:
		$tempDir = $this->userTempFolder();
		if ($tempDir)	{
			$thumbnails = t3lib_div::getFilesInDir($tempDir,'png,gif,jpg');
			array_unshift($thumbnails,'');
		} else $thumbnails = FALSE;
		$row[] = '
				<tr class="bgColor4">
					<td><strong>'.$LANG->getLL('makesavefo_metaData',1).'</strong>'.t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'metadata', $GLOBALS['BACK_PATH'],'').'</td>
					<td>
							'.$LANG->getLL('makesavefo_title',1).' <br/>
							<input type="text" name="tx_impexp[meta][title]" value="'.htmlspecialchars($inData['meta']['title']).'"'.$this->doc->formWidth(30).' /><br/>
							'.$LANG->getLL('makesavefo_description',1).' <br/>
							<input type="text" name="tx_impexp[meta][description]" value="'.htmlspecialchars($inData['meta']['description']).'"'.$this->doc->formWidth(30).' /><br/>
							'.$LANG->getLL('makesavefo_notes',1).' <br/>
							<textarea name="tx_impexp[meta][notes]"'.$this->doc->formWidth(30,1).'>'.t3lib_div::formatForTextarea($inData['meta']['notes']).'</textarea><br/>
							'.(is_array($thumbnails) ? '
							'.$LANG->getLL('makesavefo_thumbnail',1).'<br/>
							'.$this->renderSelectBox('tx_impexp[meta][thumbnail]',$inData['meta']['thumbnail'],$thumbnails).'<br/>
							'.($inData['meta']['thumbnail'] ? '<img src="'.$this->doc->backPath.'../'.substr($tempDir,strlen(PATH_site)).$thumbnails[$inData['meta']['thumbnail']].'" vspace="5" style="border: solid black 1px;" alt="" /><br/>' : '').'
							'.$LANG->getLL('makesavefo_uploadThumbnail',1).'<br/>
							<input type="file" name="upload_1" '.$this->doc->formWidth(30).' size="30" /><br/>
								<input type="hidden" name="file[upload][1][target]" value="'.htmlspecialchars($tempDir).'" />
								<input type="hidden" name="file[upload][1][data]" value="1" /><br />
							' : '').'
						</td>
				</tr>';

			// Add file options:
		$savePath = $this->userSaveFolder();
		$opt = array();
		if ($this->export->compress)	{
			$opt['t3d_compressed'] = $LANG->getLL('makesavefo_t3dFileCompressed');
		}
		$opt['t3d'] = $LANG->getLL('makesavefo_t3dFile');
		$opt['xml'] = $LANG->getLL('makesavefo_xml');
		$row[] = '
				<tr class="bgColor4">
					<td><strong>'.$LANG->getLL('makesavefo_fileFormat',1).'</strong>'.t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'fileFormat', $GLOBALS['BACK_PATH'],'').'</td>
					<td>'.$this->renderSelectBox('tx_impexp[filetype]',$inData['filetype'],$opt).'<br/>
						'.$LANG->getLL('makesavefo_maxSizeOfFiles',1).'<br/>
						<input type="text" name="tx_impexp[maxFileSize]" value="'.htmlspecialchars($inData['maxFileSize']).'"'.$this->doc->formWidth(10).' /><br/>
						'.($savePath ? sprintf($LANG->getLL('makesavefo_filenameSavedInS',1),substr($savePath,strlen(PATH_site))).'<br/>
						<input type="text" name="tx_impexp[filename]" value="'.htmlspecialchars($inData['filename']).'"'.$this->doc->formWidth(30).' /><br/>' : '').'
					</td>
				</tr>';


			// Add buttons:
		$row[] = '
				<tr class="bgColor4">
					<td>&nbsp;</td>
					<td><input type="submit" value="'.$LANG->getLL('makesavefo_update',1).'" /> - <input type="submit" value="'.$LANG->getLL('makesavefo_downloadExport',1).'" name="tx_impexp[download_export]" />'.
						($savePath ? ' - <input type="submit" value="'.$LANG->getLL('importdata_saveToFilename',1).'" name="tx_impexp[save_export]" />' : '').'</td>
				</tr>';
	}













	/**************************
	 *
	 * IMPORT FUNCTIONS
	 *
	 **************************/

	/**
	 * Import part of module
	 *
	 * @param	array		Content of POST VAR tx_impexp[]..
	 * @return	void		Setting content in $this->content
	 */
	function importData($inData)	{
		global $TCA,$LANG,$BE_USER;

		$access = is_array($this->pageinfo) ? 1 : 0;

		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{
			if ($BE_USER->user['admin'] && !$this->id)	{
				$this->pageinfo=array('title' => '[root-level]','uid'=>0,'pid'=>0);
			}

			if ($inData['new_import'])	{
				unset($inData['import_mode']);
			}

			$import = t3lib_div::makeInstance('tx_impexp');
			$import->init(0,'import');
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
			$path = $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'];	// must have trailing slash.
			$filesInDir = t3lib_div::getFilesInDir(PATH_site.$path, 't3d,xml', 1, 1);

			$userPath = $this->userSaveFolder(); //Files from User-Dir
			$filesInUserDir = t3lib_div::getFilesInDir($userPath, 't3d,xml', 1, 1);

			$filesInDir = array_merge($filesInUserDir, $filesInDir);

			if (is_dir(PATH_site.$path.'export/'))	{
				$filesInDir = array_merge($filesInDir, t3lib_div::getFilesInDir(PATH_site . $path . 'export/', 't3d,xml', 1, 1));
			}
			$tempFolder = $this->userTempFolder();
			if ($tempFolder)	{
				$temp_filesInDir = t3lib_div::getFilesInDir($tempFolder, 't3d,xml', 1, 1);
				$filesInDir = array_merge($filesInDir, $temp_filesInDir);
			}

				// Configuration
			$row = array();
			$opt = array('');
			foreach($filesInDir as $file)	{
				$opt[$file] = substr($file,strlen(PATH_site));
			}

			$row[] = '<tr class="bgColor5">
					<td colspan="2"><strong>'.$LANG->getLL('importdata_selectFileToImport',1).'</strong></td>
				</tr>';

			$row[] = '<tr class="bgColor4">
				<td><strong>'.$LANG->getLL('importdata_file',1).'</strong>'.t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'importFile', $GLOBALS['BACK_PATH'],'').'</td>
				<td>'.
					$this->renderSelectBox('tx_impexp[file]',$inData['file'],$opt).'<br />'.sprintf($LANG->getLL('importdata_fromPathS',1),$path).
					(!$import->compress ? '<br /><span class="typo3-red">'.$LANG->getLL('importdata_noteNoDecompressorAvailable',1).'</span>':'').
				'</td>
				</tr>';

			$row[] = '<tr class="bgColor5">
					<td colspan="2"><strong>'.$LANG->getLL('importdata_importOptions',1).'</strong></td>
				</tr>';

			$row[] = '<tr class="bgColor4">
				<td><strong>'.$LANG->getLL('importdata_update',1).'</strong>'.t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'update', $GLOBALS['BACK_PATH'],'').'</td>
				<td>
					<input type="checkbox" name="tx_impexp[do_update]" id="checkDo_update" value="1"'.($inData['do_update'] ? ' checked="checked"' : '').' />
					<label for="checkDo_update">'.$LANG->getLL('importdata_updateRecords',1).'</label><br/>
				<em>('.$LANG->getLL('importdata_thisOptionRequiresThat',1).')</em>'.
				($inData['do_update'] ?
				'	<hr/>
					<input type="checkbox" name="tx_impexp[global_ignore_pid]" id="checkGlobal_ignore_pid" value="1"'.($inData['global_ignore_pid'] ? ' checked="checked"' : '').' />
					<label for="checkGlobal_ignore_pid">'.$LANG->getLL('importdata_ignorePidDifferencesGlobally',1).'</label><br/>
					<em>('.$LANG->getLL('importdata_ifYouSetThis',1).')</em>
					' : ''
				).'</td>
				</tr>';

			$row[] = '<tr class="bgColor4">
				<td><strong>'.$LANG->getLL('importdata_options',1).'</strong>'.t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'options', $GLOBALS['BACK_PATH'],'').'</td>
				<td>
					<input type="checkbox" name="tx_impexp[notShowDiff]" id="checkNotShowDiff" value="1"'.($inData['notShowDiff'] ? ' checked="checked"' : '').' />
					<label for="checkNotShowDiff">'.$LANG->getLL('importdata_doNotShowDifferences',1).'</label><br/>
					<em>('.$LANG->getLL('importdata_greenValuesAreFrom',1).')</em>
					<br/><br/>

					'.($GLOBALS['BE_USER']->isAdmin() ? '
					<input type="checkbox" name="tx_impexp[allowPHPScripts]" id="checkAllowPHPScripts" value="1"'.($inData['allowPHPScripts'] ? ' checked="checked"' : '').' />
					<label for="checkAllowPHPScripts">'.$LANG->getLL('importdata_allowToWriteBanned',1).'</label><br/>' : '').

					(!$inData['do_update'] && $GLOBALS['BE_USER']->isAdmin() ? '
					<br/>
					<input type="checkbox" name="tx_impexp[force_all_UIDS]" id="checkForce_all_UIDS" value="1"'.($inData['force_all_UIDS'] ? ' checked="checked"' : '').' />
					<label for="checkForce_all_UIDS"><span class="typo3-red">'.$LANG->getLL('importdata_force_all_UIDS',1).'</span></label><br/>
					<em>('.$LANG->getLL('importdata_force_all_UIDS_descr',1).')</em>' : '').
					'
				</td>
				</tr>';

			$row[] = '<tr class="bgColor4">
				<td><strong>'.$LANG->getLL('importdata_action',1).'</strong>'.t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'action', $GLOBALS['BACK_PATH'],'').'</td>
				<td>'.
					(!$inData['import_file'] ? '<input type="submit" value="'.$LANG->getLL('importdata_preview',1).'" />'.($inData['file'] ? ' - <input type="submit" value="'.($inData['do_update']?$LANG->getLL('importdata_update_299e',1):$LANG->getLL('importdata_import',1)).'" name="tx_impexp[import_file]" onclick="return confirm(\''.$LANG->getLL('importdata_areYouSure',1).'\');" />':''):'<input type="submit" name="tx_impexp[new_import]" value="'.$LANG->getLL('importdata_newImport',1).'" />').'
					<input type="hidden" name="tx_impexp[action]" value="import" /></td>
				</tr>';

			$row[] = '<tr class="bgColor4">
				<td><strong>'.$LANG->getLL('importdata_enableLogging',1).'</strong>'.t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'enableLogging', $GLOBALS['BACK_PATH'],'').'</td>
				<td>
					<input type="checkbox" name="tx_impexp[enableLogging]" id="checkEnableLogging" value="1"'.($inData['enableLogging'] ? ' checked="checked"' : '').' />
					<label for="checkEnableLogging">'.$LANG->getLL('importdata_writeIndividualDbActions',1).'</label><br/>
					<em>('.$LANG->getLL('importdata_thisIsDisabledBy',1).')</em>
				</td>
				</tr>';

			$menuItems[] = array(
				'label' => $LANG->getLL('importdata_import',1),
				'content' => '
					<table border="0" cellpadding="1" cellspacing="1">
						'.implode('
						',$row).'
					</table>
				'
			);

				// Upload file:
			$tempFolder = $this->userTempFolder();
			if ($tempFolder)	{
				$row = array();

				$row[] = '<tr class="bgColor5">
						<td colspan="2"><strong>'.$LANG->getLL('importdata_uploadFileFromLocal',1).'</strong></td>
					</tr>';

				$row[] = '<tr class="bgColor4">
						<td>'.$LANG->getLL('importdata_browse',1).t3lib_BEfunc::cshItem('xMOD_tx_impexp', 'upload', $GLOBALS['BACK_PATH'],'').'</td>
						<td>

								<input type="file" name="upload_1"'.$this->doc->formWidth(35).' size="40" />
								<input type="hidden" name="file[upload][1][target]" value="'.htmlspecialchars($tempFolder).'" />
								<input type="hidden" name="file[upload][1][data]" value="1" /><br />

								<input type="submit" name="_upload" value="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_upload.php.submit',1).'" />
								<input type="checkbox" name="overwriteExistingFiles" id="checkOverwriteExistingFiles" value="1" checked="checked" /> <label for="checkOverwriteExistingFiles">'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.php:overwriteExistingFiles',1).'</label>
						</td>
					</tr>';

				if (t3lib_div::_POST('_upload'))	{
					$row[] = '<tr class="bgColor4">
							<td>'.$LANG->getLL('importdata_uploadStatus',1).'</td>
							<td>'.($this->fileProcessor->internalUploadMap[1] ? $LANG->getLL('importdata_success',1).' '.substr($this->fileProcessor->internalUploadMap[1],strlen(PATH_site)) : '<span class="typo3-red">'.$LANG->getLL('importdata_failureNoFileUploaded',1).'</span>').'</td>
						</tr>';
				}

				$menuItems[] = array(
					'label' => $LANG->getLL('importdata_upload'),
					'content' => '
						<table border="0" cellpadding="1" cellspacing="1">
							'.implode('
							',$row).'
						</table>
					'
				);
			}


				// Perform import or preview depending:
			$overviewContent = '';
			$extensionInstallationMessage = '';
			$emURL = '';
			$inFile = t3lib_div::getFileAbsFileName($inData['file']);
			if ($inFile && @is_file($inFile))	{
				$trow = array();
				if ($import->loadFile($inFile,1))	{

						// Check extension dependencies:
					$extKeysToInstall = array();
					if (is_array($import->dat['header']['extensionDependencies']))	{
						foreach($import->dat['header']['extensionDependencies'] as $extKey)	{
							if (!t3lib_extMgm::isLoaded($extKey))	{
								$extKeysToInstall[] = $extKey;
							}
						}
					}

					if (count($extKeysToInstall)) {
						$passParams = t3lib_div::_POST('tx_impexp');
						unset($passParams['import_mode']);
						unset($passParams['import_file']);

						$thisScriptUrl = t3lib_div::getIndpEnv('REQUEST_URI').'?M=xMOD_tximpexp&id='.$this->id.t3lib_div::implodeArrayForUrl('tx_impexp',$passParams);
						$emURL = $this->doc->backPath.'mod/tools/em/index.php?CMD[requestInstallExtensions]='.implode(',',$extKeysToInstall).'&returnUrl='.rawurlencode($thisScriptUrl);
						$extensionInstallationMessage = 'Before you can install this T3D file you need to install the extensions "'.implode('", "',$extKeysToInstall).'". Clicking Import will first take you to the Extension Manager so these dependencies can be resolved.';
					}

					if ($inData['import_file'])	{
						if (!count($extKeysToInstall))	{
							$import->importData($this->id);
							t3lib_BEfunc::setUpdateSignal('updatePageTree');
						} else {
							t3lib_utility_Http::redirect($emURL);
						}
					}

					$import->display_import_pid_record = $this->pageinfo;
					$overviewContent = $import->displayContentOverview();
				}

					// Meta data output:
				$trow[] = '<tr class="bgColor5">
						<td colspan="2"><strong>'.$LANG->getLL('importdata_metaData',1).'</strong></td>
					</tr>';

				$opt = array('');
				foreach($filesInDir as $file)	{
					$opt[$file] = substr($file,strlen(PATH_site));
				}

				$trow[] = '<tr class="bgColor4">
					<td><strong>'.$LANG->getLL('importdata_title',1).'</strong></td>
					<td width="95%">'.nl2br(htmlspecialchars($import->dat['header']['meta']['title'])).'</td>
					</tr>';

				$trow[] = '<tr class="bgColor4">
					<td><strong>'.$LANG->getLL('importdata_description',1).'</strong></td>
					<td width="95%">'.nl2br(htmlspecialchars($import->dat['header']['meta']['description'])).'</td>
					</tr>';

				$trow[] = '<tr class="bgColor4">
					<td><strong>'.$LANG->getLL('importdata_notes',1).'</strong></td>
					<td width="95%">'.nl2br(htmlspecialchars($import->dat['header']['meta']['notes'])).'</td>
					</tr>';

				$trow[] = '<tr class="bgColor4">
					<td><strong>'.$LANG->getLL('importdata_packager',1).'</strong></td>
					<td width="95%">'.nl2br(htmlspecialchars($import->dat['header']['meta']['packager_name'].' ('.$import->dat['header']['meta']['packager_username'].')')).'<br/>
						'.$LANG->getLL('importdata_email',1).' '.$import->dat['header']['meta']['packager_email'].'</td>
					</tr>';

					// Thumbnail icon:
				if (is_array($import->dat['header']['thumbnail']))	{
					$pI = pathinfo($import->dat['header']['thumbnail']['filename']);
					if (t3lib_div::inList('gif,jpg,png,jpeg',strtolower($pI['extension'])))	{

							// Construct filename and write it:
						$fileName = PATH_site.
									'typo3temp/importthumb.'.$pI['extension'];
						t3lib_div::writeFile($fileName, $import->dat['header']['thumbnail']['content']);

							// Check that the image really is an image and not a malicious PHP script...
						if (getimagesize($fileName))	{
								// Create icon tag:
							$iconTag = '<img src="'.$this->doc->backPath.'../'.substr($fileName,strlen(PATH_site)).'" '.$import->dat['header']['thumbnail']['imgInfo'][3].' vspace="5" style="border: solid black 1px;" alt="" />';

							$trow[] = '<tr class="bgColor4">
								<td><strong>'.$LANG->getLL('importdata_icon',1).'</strong></td>
								<td>'.$iconTag.'</td>
								</tr>';
						} else {
							t3lib_div::unlink_tempfile($fileName);
						}
					}
				}

				$menuItems[] = array(
					'label' => $LANG->getLL('importdata_metaData_1387'),
					'content' => '
						<table border="0" cellpadding="1" cellspacing="1">
							'.implode('
							',$trow).'
						</table>
					'
				);
			}

				// Print errors that might be:
			$errors = $import->printErrorLog();
			$menuItems[] = array(
				'label' => $LANG->getLL('importdata_messages'),
				'content' => $errors,
				'stateIcon' => $errors ? 2 : 0
			);

				// Output tabs:
			$content = $this->doc->getDynTabMenu($menuItems,'tx_impexp_import',-1);
			if ($extensionInstallationMessage)	{
				$content = '<div style="border: 1px black solid; margin: 10px 10px 10px 10px; padding: 10px 10px 10px 10px;">'.$this->doc->icons(1).htmlspecialchars($extensionInstallationMessage).'</div>'.$content;
			}
			$this->content.= $this->doc->section('',$content,0,1);


				// Print overview:
			if ($overviewContent) {
				$this->content.= $this->doc->section($inData['import_file'] ? $LANG->getLL('importdata_structureHasBeenImported',1) : $LANG->getLL('filterpage_structureToBeImported',1), $overviewContent, 0, 1);
			}
		}
	}













	/****************************
	 *
	 * Preset functions
	 *
	 ****************************/

	/**
	 * Manipulate presets
	 *
	 * @param	array		In data array, passed by reference!
	 * @return	void
	 */
	function processPresets(&$inData)	{

		$presetData = t3lib_div::_GP('preset');
		$err = FALSE;

			// Save preset
		if (isset($presetData['save']))	{
			$preset = $this->getPreset($presetData['select']);
			if (is_array($preset))	{	// Update existing
				if ($GLOBALS['BE_USER']->isAdmin() || $preset['user_uid'] === $GLOBALS['BE_USER']->user['uid'])	{
					$fields_values = array(
						'public' => $inData['preset']['public'],
						'title' => $inData['preset']['title'],
						'item_uid' => $inData['pagetree']['id'],
						'preset_data' => serialize($inData)
					);
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_impexp_presets','uid='.intval($preset['uid']),$fields_values);
					$msg = 'Preset #'.$preset['uid'].' saved!';
				} else {
					$msg = 'ERROR: The preset was not saved because you were not the owner of it!';
					$err = TRUE;
				}
			} else {	// Insert new:
				$fields_values = array(
					'user_uid' => $GLOBALS['BE_USER']->user['uid'],
					'public' => $inData['preset']['public'],
					'title' => $inData['preset']['title'],
					'item_uid' => $inData['pagetree']['id'],
					'preset_data' => serialize($inData)
				);
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_impexp_presets',$fields_values);
				$msg = 'New preset "'.$inData['preset']['title'].'" is created';
			}
		}

			// Delete preset:
		if (isset($presetData['delete']))	{
			$preset = $this->getPreset($presetData['select']);
			if (is_array($preset))	{	// Update existing
				if ($GLOBALS['BE_USER']->isAdmin() || $preset['user_uid'] === $GLOBALS['BE_USER']->user['uid'])	{
					$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_impexp_presets','uid='.intval($preset['uid']));
					$msg = 'Preset #'.$preset['uid'].' deleted!';
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
		if (isset($presetData['load']) || isset($presetData['merge']))	{
			$preset = $this->getPreset($presetData['select']);
			if (is_array($preset))	{	// Update existing
				$inData_temp = unserialize($preset['preset_data']);
				if (is_array($inData_temp))	{
					if (isset($presetData['merge']))	{

							// Merge records in:
						if (is_array($inData_temp['record']))	{
							$inData['record'] = array_merge((array)$inData['record'], $inData_temp['record']);
						}

							// Merge lists in:
						if (is_array($inData_temp['list']))	{
							$inData['list'] = array_merge((array)$inData['list'], $inData_temp['list']);
						}
					} else {
						$msg = 'Preset #'.$preset['uid'].' loaded!';
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
		if (strlen($msg))	{
			$this->content.= $this->doc->section('Presets',$msg,0,1,$err ? 3 : 1);
		}
	}

	/**
	 * Get single preset record
	 *
	 * @param	integer		Preset record
	 * @return	array		Preset record, if any (otherwise false)
	 */
	function getPreset($uid)	{
		list($preset) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*','tx_impexp_presets','uid='.intval($uid));
		return $preset;
	}











	/****************************
	 *
	 * Helper functions
	 *
	 ****************************/

	/**
	 * Returns first temporary folder of the user account (from $FILEMOUNTS)
	 *
	 * @return	string		Absolute path to first "_temp_" folder of the current user, otherwise blank.
	 */
	function userTempFolder()	{
		global $FILEMOUNTS;

		foreach($FILEMOUNTS as $filePathInfo)	{
			$tempFolder = $filePathInfo['path'].'_temp_/';
			if (@is_dir($tempFolder))	{
				return $tempFolder;
			}
		}
	}

	/**
	 * Returns folder where user can save export files.
	 *
	 * @return	string		Absolute path to folder where export files can be saved.
	 */
	function userSaveFolder()	{
		global $FILEMOUNTS;

		reset($FILEMOUNTS);
		$filePathInfo = current($FILEMOUNTS);

		if (is_array($filePathInfo))	{
			$tempFolder = $filePathInfo['path'].'export/';
			if (!@is_dir($tempFolder))	{
				$tempFolder = $filePathInfo['path'];
				if (!@is_dir($tempFolder))	{
					return FALSE;
				}
			}
			return $tempFolder;
		}
	}

	/**
	 * Check if a file has been uploaded
	 *
	 * @return	void
	 */
	function checkUpload()	{
		global $FILEMOUNTS,$TYPO3_CONF_VARS,$BE_USER;

		$file = t3lib_div::_GP('file');

			// Initializing:
		$this->fileProcessor = t3lib_div::makeInstance('t3lib_extFileFunctions');
		$this->fileProcessor->init($FILEMOUNTS, $TYPO3_CONF_VARS['BE']['fileExtensions']);
		$this->fileProcessor->init_actionPerms($GLOBALS['BE_USER']->getFileoperationPermissions());
		$this->fileProcessor->dontCheckForUnique = t3lib_div::_GP('overwriteExistingFiles') ? 1 : 0;

			// Checking referer / executing:
		$refInfo = parse_url(t3lib_div::getIndpEnv('HTTP_REFERER'));
		$httpHost = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');
		if ($httpHost!=$refInfo['host'] && $this->vC!=$BE_USER->veriCode() && !$TYPO3_CONF_VARS['SYS']['doNotCheckReferer'])	{
			$this->fileProcessor->writeLog(0,2,1,'Referer host "%s" and server host "%s" did not match!',array($refInfo['host'],$httpHost));
		} else {
			$this->fileProcessor->start($file);
			$this->fileProcessor->processData();
		}
	}

	/**
	 * Makes a selector-box from optValues
	 *
	 * @param	string		Form element name
	 * @param	string		Current value
	 * @param	array		Options to display (key/value pairs)
	 * @return	string		HTML select element
	 */
	function renderSelectBox($prefix,$value,$optValues)	{
		$opt = array();
		$isSelFlag = 0;
		foreach ($optValues as $k => $v) {
			$sel = (!strcmp($k,$value) ? ' selected="selected"' : '');
			if ($sel)	$isSelFlag++;
			$opt[] = '<option value="'.htmlspecialchars($k).'"'.$sel.'>'.htmlspecialchars($v).'</option>';
		}
		if (!$isSelFlag && strcmp('',$value))	{
			$opt[] = '<option value="'.htmlspecialchars($value).'" selected="selected">'.htmlspecialchars("['".$value."']").'</option>';
		}
		return '<select name="'.$prefix.'">'.implode('',$opt).'</select>';
	}

	/**
	 * Returns a selector-box with TCA tables
	 *
	 * @param	string		Form element name prefix
	 * @param	array		The current values selected
	 * @param	string		Table names (and the string "_ALL") to exclude. Comma list
	 * @return	string		HTML select element
	 */
	function tableSelector($prefix,$value,$excludeList='')	{
		global $TCA, $LANG;

		$optValues = array();

		if (!t3lib_div::inList($excludeList,'_ALL'))	{
			$optValues['_ALL'] = '['.$LANG->getLL('ALL_tables').']';
		}

		foreach ($TCA as $table => $value) {
			if ($GLOBALS['BE_USER']->check('tables_select',$table) && !t3lib_div::inList($excludeList,$table))	{
				$optValues[$table] = $table;
			}
		}

			// make box:
		$opt = array();
		$opt[] = '<option value=""></option>';
		foreach ($optValues as $k => $v) {
			if (is_array($value))	{
				$sel = in_array($k,$value)?' selected="selected"':'';
			}
			$opt[] = '<option value="'.htmlspecialchars($k).'"'.$sel.'>'.htmlspecialchars($v).'</option>';
		}
		return '<select name="'.$prefix.'[]" multiple="multiple" size="'.t3lib_div::intInRange(count($opt),5,10).'">'.implode('',$opt).'</select>';
	}

	/**
	 * Returns a selector-box with loaded extension keys
	 *
	 * @param	string		Form element name prefix
	 * @param	array		The current values selected
	 * @return	string		HTML select element
	 */
	function extensionSelector($prefix,$value)	{
		global $TYPO3_LOADED_EXT;

		$extTrav = array_keys($TYPO3_LOADED_EXT);

			// make box:
		$opt = array();
		$opt[] = '<option value=""></option>';
		foreach($extTrav as $v)	{
			if ($v!=='_CACHEFILE')	{
				if (is_array($value))	{
					$sel = in_array($v,$value)?' selected="selected"':'';
				}
				$opt[] = '<option value="'.htmlspecialchars($v).'"'.$sel.'>'.htmlspecialchars($v).'</option>';
			}
		}
		return '<select name="'.$prefix.'[]" multiple="multiple" size="'.t3lib_div::intInRange(count($opt),5,10).'">'.implode('',$opt).'</select>';
	}

	/**
	 * Filter page IDs by traversing exclude array, finding all excluded pages (if any) and making an AND NOT IN statement for the select clause.
	 *
	 * @param	array		Exclude array from import/export object.
	 * @return	string		AND where clause part to filter out page uids.
	 */
	function filterPageIds($exclude)	{

			// Get keys:
		$exclude = array_keys($exclude);

			// Traverse
		$pageIds = array();
		foreach($exclude as $element)	{
			list($table,$uid) = explode(':', $element);
			if ($table==='pages')	{
				$pageIds[] = intval($uid);
			}
		}

			// Add to clause:
		if (count($pageIds))	{
			return ' AND uid NOT IN ('.implode(',', $pageIds).')';
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/impexp/app/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/impexp/app/index.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_tools_log_index');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>