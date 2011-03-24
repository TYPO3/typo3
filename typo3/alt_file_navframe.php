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
 * Folder tree in the File main module.
 *
 * Revised for TYPO3 3.6 2/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   72: class localFolderTree extends t3lib_folderTree
 *   81:     function localFolderTree()
 *   92:     function wrapIcon($icon,&$row)
 *  121:     function wrapTitle($title,$row,$bank=0)
 *
 *
 *  146: class SC_alt_file_navframe
 *  163:     function init()
 *  253:     function main()
 *  284:     function printContent()
 *
 * TOTAL FUNCTIONS: 6
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

$BACK_PATH = '';
require_once('init.php');
require('template.php');
require_once('class.filelistfoldertree.php');


/**
 * Main script class for rendering of the folder tree
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_alt_file_navframe {

		// Internal, dynamic:
	var $content;		// Content accumulates in this variable.
	var $foldertree;	// Folder tree object.

	/**
	 * document template object
	 *
	 * @var template
	 */
	var $doc;
	var $backPath;

		// Internal, static: GPvar:
	var $currentSubScript;
	var $cMR;


	/**
	 * Initialiation of the script class
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER, $BACK_PATH;

			// Setting backPath
		$this->backPath = $BACK_PATH;

			// Setting GPvars:
		$this->currentSubScript = t3lib_div::_GP('currentSubScript');
		$this->cMR = t3lib_div::_GP('cMR');

			// Create folder tree object:
		$this->foldertree = t3lib_div::makeInstance('filelistFolderTree');
		$this->foldertree->ext_IconMode = $BE_USER->getTSConfigVal('options.folderTree.disableIconLinkToContextmenu');
		$this->foldertree->thisScript = 'alt_file_navframe.php';
	}


	/**
	 * initialization for the visual parts of the class
	 * Use template rendering only if this is a non-AJAX call
	 *
	 * @return	void
	 */
	public function initPage() {
		global $BE_USER, $BACK_PATH, $CLIENT;

			// Setting highlight mode:
		$this->doHighlight = !$BE_USER->getTSConfigVal('options.pageTree.disableTitleHighlight');

			// Create template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->setModuleTemplate('templates/alt_file_navframe.html');
		$this->doc->showFlashMessages = FALSE;

			// Adding javascript code for AJAX (prototype), drag&drop and the filetree as well as the click menu code
		$this->doc->getDragDropCode('folders');
		$this->doc->getContextMenuCode();

			// Setting JavaScript for menu.
		$this->doc->JScode .= $this->doc->wrapScriptTags(

		($this->currentSubScript?'top.currentSubScript=unescape("'.rawurlencode($this->currentSubScript).'");':'').'

		function initFlashUploader(path) {
			path = decodeURIComponent(path);
			var flashUploadOptions = {
				uploadURL: top.TS.PATH_typo3 + "ajax.php",
				uploadFileSizeLimit: "' . t3lib_div::getMaxUploadFileSize() . '",
				uploadFileTypes: {
					allow:  "' . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']['webspace']['allow'] . '",
					deny: "' . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']['webspace']['deny'] . '"
				},
				uploadFilePostName: "upload_1",
				uploadPostParams: {
					"file[upload][1][target]": path,
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
				jumpTo (top.rawurlencode(path), "", "", "");
			});

				// this is the callback function that delivers the additional post parameter to the flash application
			top.setFlashPostOptionOverwriteExistingFiles = function(checkbox) {
				var uploader = top.TYPO3.getInstance("FileUploadWindow");
				if (uploader.isVisible()) {
					uploader.swf.addPostParam("overwriteExistingFiles", (checkbox.checked == true ? 1 : 0));
				}
			};
		}


		// setting prefs for foldertree
		Tree.ajaxID = "SC_alt_file_navframe::expandCollapse";

		// Function, loading the list frame from navigation tree:
		function jumpTo(id, linkObj, highlightID, bank)	{
			var theUrl = top.TS.PATH_typo3 + top.currentSubScript ;
			if (theUrl.indexOf("?") != -1) {
				theUrl += "&id=" + id
			} else {
				theUrl += "?id=" + id
			}
			top.fsMod.currentBank = bank;
			top.TYPO3.Backend.ContentContainer.setUrl(theUrl);

			'.($this->doHighlight ? 'Tree.highlightActiveItem("file", highlightID + "_" + bank);' : '').'
			'.(!$CLIENT['FORMSTYLE'] ? '' : 'if (linkObj) linkObj.blur(); ').'
			return false;
		}
		'.($this->cMR ? " jumpTo(top.fsMod.recentIds['file'],'');" : '')
		);
	}


	/**
	 * Main function, rendering the folder tree
	 *
	 * @return	void
	 */
	function main()	{
		global $LANG,$CLIENT;

			// Produce browse-tree:
		$tree = $this->foldertree->getBrowsableTree();

			// Outputting page tree:
		$this->content.= $tree;

			// Adding javascript for drag & drop activation and highlighting
		$this->content .=$this->doc->wrapScriptTags('
			'.($this->doHighlight ? 'Tree.highlightActiveItem("", top.fsMod.navFrameHighlightedID["file"]);' : '').'
			'.(!$this->doc->isCMlayers() ? 'Tree.activateDragDrop = false;' : 'Tree.registerDragDropHandlers();')
		);

			// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers = array(
			'IMG_RESET' => '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/close_gray.gif', ' width="16" height="16"') .
			' id="treeFilterReset" alt="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.resetFilter') . '" ' .
			'title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.resetFilter') . '" />',
			'CONTENT' => $this->content
		);

		$subparts = array();

			// Possible filter/search like in page tree
		$subparts['###SECOND_ROW###'] = '';

			// Build the <body> for the module
		$this->content = $this->doc->startPage('TYPO3 Folder Tree');
		$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers, $subparts);
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
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
	protected function getButtons()	{
		$buttons = array(
			'csh' => '',
			'refresh' => '',
		);

			// Refresh
		$buttons['refresh'] = '<a href="' . htmlspecialchars(t3lib_div::getIndpEnv('REQUEST_URI')) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-system-refresh') .
		'</a>';

			// CSH
		$buttons['csh'] = str_replace('typo3-csh-inline','typo3-csh-inline show-right',t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'filetree', $GLOBALS['BACK_PATH']));

		return $buttons;
	}

	/**********************************
	 *
	 * AJAX Calls
	 *
	 **********************************/

	/**
	 * Makes the AJAX call to expand or collapse the foldertree.
	 * Called by typo3/ajax.php
	 *
	 * @param	array		$params: additional parameters (not used here)
	 * @param	TYPO3AJAX	$ajaxObj: The TYPO3AJAX object of this request
	 * @return	void
	 */
	public function ajaxExpandCollapse($params, $ajaxObj) {
		global $LANG;

		$this->init();
		$tree = $this->foldertree->getBrowsableTree();
		if (!$this->foldertree->ajaxStatus)	{
			$ajaxObj->setError($tree);
		} else	{
			$ajaxObj->addContent('tree', $tree);
		}
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/alt_file_navframe.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/alt_file_navframe.php']);
}


// Make instance if it is not an AJAX call
if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)) {
	$SOBE = t3lib_div::makeInstance('SC_alt_file_navframe');
	$SOBE->init();
	$SOBE->initPage();
	$SOBE->main();
	$SOBE->printContent();
}

?>
