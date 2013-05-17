<?php
namespace TYPO3\CMS\Backend\Controller\File;

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
 * Script Class for the create-new script; Displays a form for creating up to 10 folders or one new text file
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class CreateFolderController {

	// External, static:
	/**
	 * @todo Define visibility
	 */
	public $folderNumber = 10;

	// Internal, static:
	/**
	 * document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * @todo Define visibility
	 */
	public $doc;

	// Name of the filemount
	/**
	 * @todo Define visibility
	 */
	public $title;

	// Internal, static (GPVar):
	/**
	 * @todo Define visibility
	 */
	public $number;

	// Set with the target path inputted in &target
	/**
	 * @todo Define visibility
	 */
	public $target;

	/**
	 * The folder object which is  the target directory
	 *
	 * @var \TYPO3\CMS\Core\Resource\Folder $folderObject
	 */
	protected $folderObject;

	// Return URL of list module.
	/**
	 * @todo Define visibility
	 */
	public $returnUrl;

	// Internal, dynamic:
	// Accumulating content
	/**
	 * @todo Define visibility
	 */
	public $content;

	/**
	 * Constructor function for class
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function init() {
		// Initialize GPvars:
		$this->number = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('number');
		$this->target = ($combinedIdentifier = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('target'));
		$this->returnUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('returnUrl'));
		// create the folder object
		if ($combinedIdentifier) {
			$this->folderObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFolderObjectFromCombinedIdentifier($combinedIdentifier);
		}
		// Cleaning and checking target directory
		if (!$this->folderObject) {
			$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:paramError', TRUE);
			$message = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:targetNoDir', TRUE);
			throw new \RuntimeException($title . ': ' . $message, 1294586843);
		}
		// Setting the title and the icon
		$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('apps-filetree-root');
		$this->title = $icon . htmlspecialchars($this->folderObject->getStorage()->getName()) . ': ' . htmlspecialchars($this->folderObject->getIdentifier());
		// Setting template object
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->setModuleTemplate('templates/file_newfolder.html');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->JScode = $this->doc->wrapScriptTags('
			var path = "' . $this->target . '";

			function reload(a) {	//
				if (!changed || (changed && confirm(' . $GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.redraw')) . '))) {
					var params = "&target="+encodeURIComponent(path)+"&number="+a+"&returnUrl=' . rawurlencode($this->returnUrl) . '";
					window.location.href = "file_newfolder.php?"+params;
				}
			}
			function backToList() {	//
				top.goToModule("file_list");
			}

			var changed = 0;
		');
	}

	/**
	 * Main function, rendering the main module content
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		// Start content compilation
		$this->content .= $this->doc->startPage($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:file_newfolder.php.pagetitle'));
		// Make page header:
		$pageContent = '';
		$pageContent .= $this->doc->header($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:file_newfolder.php.pagetitle'));
		$pageContent .= $this->doc->spacer(5);
		$pageContent .= $this->doc->divider(5);
		$code = '<form action="tce_file.php" method="post" name="editform">';
		// Making the selector box for the number of concurrent folder-creations
		$this->number = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->number, 1, 10);
		$code .= '
			<div id="c-select">
				<label for="number-of-new-folders">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:file_newfolder.php.number_of_folders') . '</label>
				<select name="number" id="number-of-new-folders" onchange="reload(this.options[this.selectedIndex].value);">';
		for ($a = 1; $a <= $this->folderNumber; $a++) {
			$code .= '<option value="' . $a . '"' . ($this->number == $a ? ' selected="selected"' : '') . '>' . $a . '</option>';
		}
		$code .= '
				</select>
			</div>
			';
		// Making the number of new-folder boxes needed:
		$code .= '
			<div id="c-createFolders">
		';
		for ($a = 0; $a < $this->number; $a++) {
			$code .= '
					<input' . $this->doc->formWidth(20) . ' type="text" name="file[newfolder][' . $a . '][data]" onchange="changed=true;" />
					<input type="hidden" name="file[newfolder][' . $a . '][target]" value="' . htmlspecialchars($this->target) . '" /><br />
				';
		}
		$code .= '
			</div>
		';
		// Making submit button for folder creation:
		$code .= '
			<div id="c-submitFolders">
				<input type="submit" value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:file_newfolder.php.submit', 1) . '" />
				<input type="submit" value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.cancel', 1) . '" onclick="backToList(); return false;" />
				<input type="hidden" name="redirect" value="' . htmlspecialchars($this->returnUrl) . '" />
			</div>
			';
		// CSH:
		$code .= \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('xMOD_csh_corebe', 'file_newfolder', $GLOBALS['BACK_PATH'], '<br />');
		$pageContent .= $code;
		// Add spacer:
		$pageContent .= $this->doc->spacer(10);
		// Switching form tags:
		$pageContent .= $this->doc->sectionEnd();
		$pageContent .= '</form><form action="tce_file.php" method="post" name="editform2">';
		// Create a list of allowed file extensions with the nice format "*.jpg, *.gif" etc.
		$fileExtList = array();
		$textfileExt = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'], TRUE);
		foreach ($textfileExt as $fileExt) {
			if (!preg_match(('/' . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] . '/i'), ('.' . $fileExt))) {
				$fileExtList[] = '*.' . $fileExt;
			}
		}
		// Add form fields for creation of a new, blank text file:
		$code = '
			<div id="c-newFile">
				<p>[' . htmlspecialchars(implode(', ', $fileExtList)) . ']</p>
				<input' . $this->doc->formWidth(20) . ' type="text" name="file[newfile][0][data]" onchange="changed=true;" />
				<input type="hidden" name="file[newfile][0][target]" value="' . htmlspecialchars($this->target) . '" />
			</div>
			';
		// Submit button for creation of a new file:
		$code .= '
			<div id="c-submitFiles">
				<input type="submit" value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:file_newfolder.php.newfile_submit', 1) . '" />
				<input type="submit" value="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.cancel', 1) . '" onclick="backToList(); return false;" />
				<input type="hidden" name="redirect" value="' . htmlspecialchars($this->returnUrl) . '" />
			</div>
			';
		// CSH:
		$code .= \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('xMOD_csh_corebe', 'file_newfile', $GLOBALS['BACK_PATH'], '<br />');
		$pageContent .= $this->doc->section($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:file_newfolder.php.newfile'), $code);
		$pageContent .= $this->doc->sectionEnd();
		$pageContent .= '</form>';
		$docHeaderButtons = array(
			'back' => ''
		);
		// Back
		if ($this->returnUrl) {
			$docHeaderButtons['back'] = '<a href="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::linkThisUrl($this->returnUrl)) . '" class="typo3-goBack" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.goBack', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-view-go-back') . '</a>';
		}
		// Add the HTML as a section:
		$markerArray = array(
			'CSH' => $docHeaderButtons['csh'],
			'FUNC_MENU' => \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']),
			'CONTENT' => $pageContent,
			'PATH' => $this->title
		);
		$this->content .= $this->doc->moduleBody(array(), $docHeaderButtons, $markerArray);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printContent() {
		echo $this->content;
	}

}


?>