<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Web>File: File listing
 *
 * $Id$
 * Revised for TYPO3 3.6 2/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   77: class SC_file_list
 *  103:     function init()
 *  130:     function menuConfig()
 *  151:     function main()
 *  325:     function printContent()
 *
 * TOTAL FUNCTIONS: 4
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


unset($MCONF);
require ('conf.php');
require ($BACK_PATH . 'init.php');
require ($BACK_PATH . 'template.php');
$LANG->includeLLFile('EXT:lang/locallang_mod_file_list.xml');
$LANG->includeLLFile('EXT:lang/locallang_misc.xml');
require_once ($BACK_PATH . 'class.file_list.inc');
$BE_USER->modAccess($MCONF,1);







/**
 * Script Class for creating the list of files in the File > Filelist module
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_file_list {
	var $MCONF=array();			// Module configuration
	var $MOD_MENU=array();
	var $MOD_SETTINGS=array();


		// Internal:
	var $content;	// Accumulated HTML output

	/**
	 * File processing object
	 *
	 * @var t3lib_basicFileFunctions
	 */
	var $basicFF;

	/**
	 * Document template object
	 *
	 * @var template
	 */
	var $doc;

		// Internal, static: GPvars:
	var $id;		// "id" -> the path to list.
	var $pointer;	// Pointer to listing
	var $table;		// "Table"
	var $imagemode;	// Thumbnail mode.
	var $cmd;
	var $overwriteExistingFiles;


	/**
	 * Initialize variables, file object
	 * Incoming GET vars include id, pointer, table, imagemode
	 *
	 * @return	void
	 */
	function init()	{
		global $TYPO3_CONF_VARS,$FILEMOUNTS;

			// Setting GPvars:
		$this->id = t3lib_div::_GP('id');
		$this->pointer = t3lib_div::_GP('pointer');
		$this->table = t3lib_div::_GP('table');
		$this->imagemode = t3lib_div::_GP('imagemode');
		$this->cmd = t3lib_div::_GP('cmd');
		$this->overwriteExistingFiles = t3lib_div::_GP('overwriteExistingFiles');

			// Setting module name:
		$this->MCONF = $GLOBALS['MCONF'];

			// File operation object:
		$this->basicFF = t3lib_div::makeInstance('t3lib_basicFileFunctions');
		$this->basicFF->init($FILEMOUNTS,$TYPO3_CONF_VARS['BE']['fileExtensions']);

			// Configure the "menu" - which is used internally to save the values of sorting, displayThumbs etc.
		$this->menuConfig();
	}

	/**
	 * Setting the menu/session variables
	 *
	 * @return	void
	 */
	function menuConfig()	{
			// MENU-ITEMS:
			// If array, then it's a selector box menu
			// If empty string it's just a variable, that'll be saved.
			// Values NOT in this array will not be saved in the settings-array for the module.
		$this->MOD_MENU = array(
			'sort' => '',
			'reverse' => '',
			'displayThumbs' => '',
			'clipBoard' => '',
			'bigControlPanel' => ''
		);

			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);
	}

	/**
	 * Main function, creating the listing
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TYPO3_CONF_VARS,$FILEMOUNTS;

			// Initialize the template object
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->setModuleTemplate('templates/file_list.html');
		$this->doc->getPageRenderer()->loadPrototype();

			// Validating the input "id" (the path, directory!) and checking it against the mounts of the user.
		$this->id = $this->basicFF->is_directory($this->id);
		$access = $this->id && $this->basicFF->checkPathAgainstMounts($this->id.'/');

			// There there was access to this file path, continue, make the list
		if ($access)	{
				// include the initialization for the flash uploader
			if ($GLOBALS['BE_USER']->uc['enableFlashUploader']) {

				$this->doc->JScodeArray['flashUploader'] = '
					if (top.TYPO3.FileUploadWindow.isFlashAvailable()) {
						document.observe("dom:loaded", function() {
								// monitor the button
							$("button-upload").observe("click", initFlashUploader);

							function initFlashUploader(event) {
									// set the page specific options for the flashUploader
								var flashUploadOptions = {
									uploadURL:           top.TS.PATH_typo3 + "ajax.php",
									uploadFileSizeLimit: "' . t3lib_div::getMaxUploadFileSize() . '",
									uploadFileTypes: {
										allow:  "' . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']['webspace']['allow'] . '",
										deny: "' . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']['webspace']['deny'] . '"
									},
									uploadFilePostName:  "upload_1",
									uploadPostParams: {
										"file[upload][1][target]": "' . $this->id . '",
										"file[upload][1][data]": 1,
										"file[upload][1][charset]": "utf-8",
										"ajaxID": "TYPO3_tcefile::process"
									}
								};

									// get the flashUploaderWindow instance from the parent frame
								var flashUploader = top.TYPO3.FileUploadWindow.getInstance(flashUploadOptions);
								// add an additional function inside the container to show the checkbox option
								var infoComponent = new top.Ext.Panel({
									autoEl: { tag: "div" },
									height: "auto",
									bodyBorder: false,
									border: false,
									hideBorders: true,
									cls: "t3-upload-window-infopanel",
									id: "t3-upload-window-infopanel-addition",
									html: \'<label for="overrideExistingFilesCheckbox"><input id="overrideExistingFilesCheckbox" type="checkbox" onclick="setFlashPostOptionOverwriteExistingFiles(this);" />\' + top.String.format(top.TYPO3.LLL.fileUpload.infoComponentOverrideFiles) + \'</label>\'
								});
								flashUploader.add(infoComponent);

									// do a reload of this frame once all uploads are done
								flashUploader.on("totalcomplete", function() {
									window.location.reload();
								});

									// this is the callback function that delivers the additional post parameter to the flash application
								top.setFlashPostOptionOverwriteExistingFiles = function(checkbox) {
									var uploader = top.TYPO3.getInstance("FileUploadWindow");
									if (uploader.isVisible()) {
										uploader.swf.addPostParam("overwriteExistingFiles", (checkbox.checked == true ? 1 : 0));
									}
								};

								event.stop();
							};
						});
					}
				';
			}
				// Create filelisting object
			$this->filelist = t3lib_div::makeInstance('fileList');
			$this->filelist->backPath = $BACK_PATH;

				// Apply predefined values for hidden checkboxes
				// Set predefined value for DisplayBigControlPanel:
			if ($GLOBALS['BE_USER']->getTSConfigVal('options.file_list.enableDisplayBigControlPanel') === 'activated') {
				$this->MOD_SETTINGS['bigControlPanel'] = TRUE;
			} elseif ($GLOBALS['BE_USER']->getTSConfigVal('options.file_list.enableDisplayBigControlPanel') === 'deactivated') {
				$this->MOD_SETTINGS['bigControlPanel'] = FALSE;
			}

				// Set predefined value for DisplayThumbnails:
			if ($GLOBALS['BE_USER']->getTSConfigVal('options.file_list.enableDisplayThumbnails') === 'activated') {
				$this->MOD_SETTINGS['displayThumbs'] = TRUE;
			} elseif ($GLOBALS['BE_USER']->getTSConfigVal('options.file_list.enableDisplayThumbnails') === 'deactivated') {
				$this->MOD_SETTINGS['displayThumbs'] = FALSE;
			}

				// Set predefined value for Clipboard:
			if ($GLOBALS['BE_USER']->getTSConfigVal('options.file_list.enableClipBoard') === 'activated') {
				$this->MOD_SETTINGS['clipBoard'] = TRUE;
			} elseif ($GLOBALS['BE_USER']->getTSConfigVal('options.file_list.enableClipBoard') === 'deactivated') {
				$this->MOD_SETTINGS['clipBoard'] = FALSE;
			}

				// if user never opened the list module, set the value for displayThumbs
			if (!isset($this->MOD_SETTINGS['displayThumbs'])) {
				$this->MOD_SETTINGS['displayThumbs'] = $BE_USER->uc['thumbnailsByDefault'];
			}
			$this->filelist->thumbs = $this->MOD_SETTINGS['displayThumbs'];

				// Create clipboard object and initialize that
			$this->filelist->clipObj = t3lib_div::makeInstance('t3lib_clipboard');
			$this->filelist->clipObj->fileMode=1;
			$this->filelist->clipObj->initializeClipboard();

			$CB = t3lib_div::_GET('CB');
			if ($this->cmd=='setCB') $CB['el'] = $this->filelist->clipObj->cleanUpCBC(array_merge(t3lib_div::_POST('CBH'),t3lib_div::_POST('CBC')),'_FILE');
			if (!$this->MOD_SETTINGS['clipBoard'])	$CB['setP']='normal';
			$this->filelist->clipObj->setCmd($CB);
			$this->filelist->clipObj->cleanCurrent();
			$this->filelist->clipObj->endClipboard();	// Saves

				// If the "cmd" was to delete files from the list (clipboard thing), do that:
			if ($this->cmd=='delete')	{
				$items = $this->filelist->clipObj->cleanUpCBC(t3lib_div::_POST('CBC'),'_FILE',1);
				if (count($items))	{
						// Make command array:
					$FILE=array();
					foreach ($items as $v) {
						$FILE['delete'][]=array('data'=>$v);
					}

						// Init file processing object for deleting and pass the cmd array.
					$fileProcessor = t3lib_div::makeInstance('t3lib_extFileFunctions');
					$fileProcessor->init($FILEMOUNTS, $TYPO3_CONF_VARS['BE']['fileExtensions']);
					$fileProcessor->init_actionPerms($GLOBALS['BE_USER']->getFileoperationPermissions());
					$fileProcessor->dontCheckForUnique = $this->overwriteExistingFiles ? 1 : 0;
					$fileProcessor->start($FILE);
					$fileProcessor->processData();

					$fileProcessor->printLogErrorMessages();
				}
			}

			if (!isset($this->MOD_SETTINGS['sort']))	{
					// Set default sorting
				$this->MOD_SETTINGS['sort'] = 'file';
				$this->MOD_SETTINGS['reverse'] = 0;
			}

				// Start up filelisting object, include settings.
			$this->pointer = t3lib_div::intInRange($this->pointer,0,100000);
			$this->filelist->start($this->id, $this->pointer, $this->MOD_SETTINGS['sort'], $this->MOD_SETTINGS['reverse'], $this->MOD_SETTINGS['clipBoard'], $this->MOD_SETTINGS['bigControlPanel']);

				// Generate the list
			$this->filelist->generateList();

				// Write the footer
			$this->filelist->writeBottom();

				// Set top JavaScript:
			$this->doc->JScode=$this->doc->wrapScriptTags('

			if (top.fsMod) top.fsMod.recentIds["file"] = unescape("'.rawurlencode($this->id).'");
			function jumpToUrl(URL)	{	//
				window.location.href = URL;
			}

			'.$this->filelist->CBfunctions()	// ... and add clipboard JavaScript functions
			);

				// This will return content necessary for the context sensitive clickmenus to work: bodytag events, JavaScript functions and DIV-layers.
			$this->doc->getContextMenuCode();

				// Setting up the buttons and markers for docheader
			list($buttons, $otherMarkers) = $this->filelist->getButtonsAndOtherMarkers($this->id);

				// add the folder info to the marker array
			$otherMarkers['FOLDER_INFO'] = $this->filelist->getFolderInfo();

			$docHeaderButtons = array_merge($this->getButtons(), $buttons);

				// Build the <body> for the module

				// Create output
			$pageContent='';
			$pageContent.= '<form action="'.htmlspecialchars($this->filelist->listURL()).'" method="post" name="dblistForm">';
			$pageContent.= $this->filelist->HTMLcode;
			$pageContent.= '<input type="hidden" name="cmd" /></form>';


			if ($this->filelist->HTMLcode)	{	// Making listing options:

				$pageContent.='

					<!--
						Listing options for extended view, clipboard and thumbnails
					-->
					<div id="typo3-listOptions">
				';

			   		// Add "display bigControlPanel" checkbox:
				if ($GLOBALS['BE_USER']->getTSConfigVal('options.file_list.enableDisplayBigControlPanel') === 'selectable') {
					$pageContent .= t3lib_BEfunc::getFuncCheck(
						$this->id,
						'SET[bigControlPanel]',
						$this->MOD_SETTINGS['bigControlPanel'],
						'file_list.php',
						'',
						'id="bigControlPanel"'
					) . '<label for="bigControlPanel"> ' . $GLOBALS['LANG']->getLL('bigControlPanel', TRUE) . '</label><br />';
				}

					// Add "display thumbnails" checkbox:
				if ($GLOBALS['BE_USER']->getTSConfigVal('options.file_list.enableDisplayThumbnails') === 'selectable') {
					$pageContent .= t3lib_BEfunc::getFuncCheck(
						$this->id,
						'SET[displayThumbs]',
						$this->MOD_SETTINGS['displayThumbs'],
						'file_list.php',
						'',
						'id="checkDisplayThumbs"'
					) . ' <label for="checkDisplayThumbs">' . $GLOBALS['LANG']->getLL('displayThumbs', TRUE) . '</label><br />';
				}

					// Add "clipboard" checkbox:
				if ($GLOBALS['BE_USER']->getTSConfigVal('options.file_list.enableClipBoard') === 'selectable') {
					$pageContent .= t3lib_BEfunc::getFuncCheck(
						$this->id,
						'SET[clipBoard]',
						$this->MOD_SETTINGS['clipBoard'],
						'file_list.php',
						'',
						'id="checkClipBoard"'
					) . ' <label for="checkClipBoard">' . $GLOBALS['LANG']->getLL('clipBoard', TRUE) . '</label>';
				}

				$pageContent.='
					</div>
				';


					// Set clipboard:
				if ($this->MOD_SETTINGS['clipBoard'])	{
					$pageContent.=$this->filelist->clipObj->printClipboard();
					$pageContent.= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'filelist_clipboard', $GLOBALS['BACK_PATH']);
				}
			}

			$markerArray = array(
				'CSH' => $docHeaderButtons['csh'],
				'FUNC_MENU' => t3lib_BEfunc::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']),
				'CONTENT' => $pageContent
			);

			$this->content = $this->doc->moduleBody(array(), $docHeaderButtons, array_merge($markerArray, $otherMarkers));
				// Renders the module page
			$this->content = $this->doc->render(
				$LANG->getLL('files'),
				$this->content
			);

		} else {
				// Create output - no access (no warning though)
			$this->content = $this->doc->render(
				$LANG->getLL('files'),
				''
			);
		}


	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	function getButtons()	{
		global $TCA, $LANG, $BACK_PATH, $BE_USER;

		$buttons = array(
			'csh' => '',
			'shortcut' => '',
			'upload' => '',
			'new' => '',
		);

			// Add shortcut
		if ($BE_USER->mayMakeShortcut())	{
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('pointer,id,target,table',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']);
		}

			// FileList Module CSH:
		$buttons['csh'] = t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'filelist_module', $GLOBALS['BACK_PATH'], '', TRUE);

			// upload button
		$buttons['upload'] = '<a href="' . $BACK_PATH . 'file_upload.php?target=' . rawurlencode($this->id) . '&amp;returnUrl=' . rawurlencode($this->filelist->listURL()) . '" id="button-upload" title="'.$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:cm.upload',1)).'">' .
			t3lib_iconWorks::getSpriteIcon('actions-edit-upload') .
		'</a>';

		$buttons['new'] = '<a href="' . $BACK_PATH . 'file_newfolder.php?target=' . rawurlencode($this->id) . '&amp;returnUrl=' . rawurlencode($this->filelist->listURL()) . '" title="'.$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:cm.new',1)).'">' .
			t3lib_iconWorks::getSpriteIcon('actions-document-new') .
		'</a>';

		return $buttons;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/file_list.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/file_list.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_file_list');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>