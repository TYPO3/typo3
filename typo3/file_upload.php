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
 * Web>File: Upload of files
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */

$BACK_PATH = '';
require('init.php');
require('template.php');
$LANG->includeLLFile('EXT:lang/locallang_misc.xml');

/**
 * Script Class for display up to 10 upload fields
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_file_upload {

		// Internal, static:
	/**
	 * Document template object
	 *
	 * @var smallDoc
	 */
	var $doc;
		// Name of the filemount
	var $title;

		// Internal, static (GPVar):
		// Set with the target path inputted in &target
	var $target;
		// Return URL of list module.
	var $returnUrl;

		// Internal, dynamic:
		// Accumulating content
	var $content;

	/**
	 * The folder object which is the target directory for the upload
	 *
	 * @var t3lib_file_Folder $folderObject
	 */
	protected $folderObject;

	/**
	 * Constructor for initializing the class
	 *
	 * @return	void
	 */
	function init() {
			// Initialize GPvars:
		$this->target = t3lib_div::_GP('target');
		$this->returnUrl = t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('returnUrl'));
		if (!$this->returnUrl) {
			$this->returnUrl = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir . t3lib_extMgm::extRelPath('filelist') . 'mod1/file_list.php?id=' . rawurlencode($this->target);
		}

			// Create the folder object
		if ($this->target) {
			$this->folderObject = t3lib_file_Factory::getInstance()->retrieveFileOrFolderObject($this->target);
		}

			// Cleaning and checking target directory
		if (!$this->folderObject) {
			$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.xml:paramError', TRUE);
			$message = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.xml:targetNoDir', TRUE);
			throw new RuntimeException($title . ': ' . $message, 1294586843);
		}

			// Setting the title and the icon
		$icon = t3lib_iconWorks::getSpriteIcon('apps-filetree-root');
		$this->title = $icon . htmlspecialchars($this->folderObject->getStorage()->getName()) . ': ' . htmlspecialchars($this->folderObject->getIdentifier());


			// Setting template object
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->setModuleTemplate('templates/file_upload.html');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->form = '<form action="tce_file.php" method="post" name="editform" enctype="' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'] . '">';
	}

	/**
	 * Main function, rendering the upload file form fields
	 *
	 * @return void
	 */
	function main() {
			// Make page header:
		$this->content = $this->doc->startPage($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_upload.php.pagetitle'));

		$form = $this->renderUploadForm();

		$pageContent =
			$this->doc->header($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_upload.php.pagetitle')) .
			$this->doc->section('', $form);

			// Header Buttons
		$docHeaderButtons = array(
			'csh' => t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'file_upload', $GLOBALS['BACK_PATH'])
		);

		$markerArray = array(
			'CSH'       => $docHeaderButtons['csh'],
			'FUNC_MENU' => t3lib_BEfunc::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']),
			'CONTENT'  => $pageContent,
			'PATH'     => $this->title,
		);

		$this->content .= $this->doc->moduleBody(array(), $docHeaderButtons, $markerArray);
		$this->content .= $this->doc->endPage();
		$this->content  = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * This function renders the upload form
	 *
	 * @return	string	the HTML form as a string, ready for outputting
	 */
	function renderUploadForm() {

			// Make checkbox for "overwrite"
		$content = '
			<div id="c-override">
				<p><label for="overwriteExistingFiles"><input type="checkbox" class="checkbox" name="overwriteExistingFiles" id="overwriteExistingFiles" value="1" /> ' . $GLOBALS['LANG']->getLL('overwriteExistingFiles', 1) . '</label></p>
				<p>&nbsp;</p>
				<p>' . $GLOBALS['LANG']->getLL('uploadMultipleFilesInfo', TRUE) . '</p>
			</div>
			';


			// Produce the number of upload-fields needed:
		$content .= '
			<div id="c-upload">
		';
				// Adding 'size="50" ' for the sake of Mozilla!
			$content .= '
				<input type="file" multiple="true" name="upload_1[]" />
				<input type="hidden" name="file[upload][1][target]" value="' . htmlspecialchars($this->folderObject->getCombinedIdentifier()) . '" />
				<input type="hidden" name="file[upload][1][data]" value="1" /><br />
			';

		$content .= '
			</div>
		';

			// Submit button:
		$content .= '
			<div id="c-submit">
				<input type="hidden" name="redirect" value="' . $this->returnUrl . '" /><br />
				<input type="submit" value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_upload.php.submit', 1) . '" />
			</div>
		';

		return $content;
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 */
	function printContent() {
		echo $this->content;
	}
}

	// Make instance:
$SOBE = t3lib_div::makeInstance('SC_file_upload');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>