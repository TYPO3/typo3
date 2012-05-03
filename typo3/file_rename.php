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
 * Web>File: Renaming files and folders
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */



$BACK_PATH = '';
require('init.php');
require('template.php');



/**
 * Script Class for the rename-file form.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_file_rename {

		// Internal, static:
	/**
	 * Document template object
	 *
	 * @var smallDoc
	 */
	var $doc;
	var $title;			// Name of the filemount

		// Internal, static (GPVar):
	var $target;		// Set with the target path inputted in &target

	/**
	 * the file or folder object that should be renamed
	 *
	 * @var t3lib_file_ResourceInterface $fileOrFolderObject
	 */
	protected $fileOrFolderObject;

	var $returnUrl;		// Return URL of list module.

		// Internal, dynamic:
	var $content;		// Accumulating content


	/**
	 * Constructor function for class
	 *
	 * @return	void
	 */
	function init()	{
			// Initialize GPvars:
		$this->target = t3lib_div::_GP('target');
		$this->returnUrl = t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('returnUrl'));

			// Cleaning and checking target
		if ($this->target) {
			$this->fileOrFolderObject = t3lib_file_Factory::getInstance()->retrieveFileOrFolderObject($this->target);
		}

		if (!$this->fileOrFolderObject) {
			$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.xml:paramError', TRUE);
			$message = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.xml:targetNoDir', TRUE);
			throw new RuntimeException($title . ': ' . $message, 1294586844);
		}

			// if a folder should be renamed, AND the returnURL should go to the old directory name, the redirect is forced
			// so the redirect will NOT end in a error message
			// this case only happens if you select the folder itself in the foldertree and then use the clickmenu to
			// rename the folder
		if ($this->fileOrFolderObject instanceof t3lib_file_Folder) {
			$parsedUrl = parse_url($this->returnUrl);
			$queryParts = t3lib_div::explodeUrl2Array(urldecode($parsedUrl['query']));
			if ($queryParts['id'] === $this->fileOrFolderObject->getCombinedIdentifier()) {
				$this->returnUrl = str_replace(urlencode($queryParts['id']), urlencode($this->fileOrFolderObject->getStorage()->getRootLevelFolder()->getCombinedIdentifier()), $this->returnUrl);
			}
		}



			// Setting icon and title
		$icon = t3lib_iconWorks::getSpriteIcon('apps-filetree-root');
		$this->title = $icon . htmlspecialchars($this->fileOrFolderObject->getStorage()->getName()) . ': ' . htmlspecialchars($this->fileOrFolderObject->getIdentifier());

			// Setting template object
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->setModuleTemplate('templates/file_rename.html');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->JScode=$this->doc->wrapScriptTags('
			function backToList()	{	//
				top.goToModule("file_list");
			}
		');
	}

	/**
	 * Main function, rendering the content of the rename form
	 *
	 * @return	void
	 */
	function main()	{
		//TODO: change locallang*.php to locallang*.xml

			// Make page header:
		$this->content = $this->doc->startPage($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_rename.php.pagetitle'));

		$pageContent = $this->doc->header($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_rename.php.pagetitle'));
		$pageContent .= $this->doc->spacer(5);
		$pageContent .= $this->doc->divider(5);

		if ($this->fileOrFolderObject instanceof t3lib_file_Folder) {
			$fileIdentifier = $this->fileOrFolderObject->getCombinedIdentifier();
		} else {
			$fileIdentifier = $this->fileOrFolderObject->getUid();
		}

		$code = '<form action="tce_file.php" method="post" name="editform">';
			// Making the formfields for renaming:
		$code .= '

			<div id="c-rename">
				<input type="text" name="file[rename][0][target]" value="' . htmlspecialchars($this->fileOrFolderObject->getName()) . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(40) . ' />
				<input type="hidden" name="file[rename][0][data]" value="' . htmlspecialchars($fileIdentifier) . '" />
			</div>
		';

			// Making submit button:
		$code.='
			<div id="c-submit">
				<input type="submit" value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_rename.php.submit', 1) . '" />
				<input type="submit" value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.cancel', 1) . '" onclick="backToList(); return false;" />
				<input type="hidden" name="redirect" value="'.htmlspecialchars($this->returnUrl).'" />
			</div>
		';

		$code .= '</form>';

			// Add the HTML as a section:
		$pageContent .= $code;

		$docHeaderButtons = array();
		$docHeaderButtons['csh'] = t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'file_rename', $GLOBALS['BACK_PATH']);

			// Add the HTML as a section:
		$markerArray = array(
			'CSH' => $docHeaderButtons['csh'],
			'FUNC_MENU' => t3lib_BEfunc::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']),
			'CONTENT' => $pageContent,
			'PATH' => $this->title,
		);

		$this->content.= $this->doc->moduleBody(array(), $docHeaderButtons, $markerArray);
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
}

// Make instance:
$SOBE = t3lib_div::makeInstance('SC_file_rename');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>