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
 * Web>File: Editing documents
 *
 * Revised for TYPO3 3.6 2/2003 by Kasper Skårhøj
 * XHTML compliant (except textarea field)
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */

$GLOBALS['BACK_PATH'] = '';
require('init.php');
require('template.php');


/**
 * Script Class for rendering the file editing screen
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_file_edit {
	var $content;		// Module content accumulated.

	/**
	 * File processing object
	 *
	 * @var t3lib_basicFileFunctions
	 */
	var $basicff;
	var $shortPath;
	var $title;
	var $icon;

	/**
	 * Document template object
	 *
	 * @var template
	 */
	var $doc;

		// Internal, static: GPvar
	var $origTarget;		// Original input target
	var $target;			// The original target, but validated.
	var $returnUrl;		// Return URL of list module.


	/**
	 * Initialize script class
	 *
	 * @return	void
	 */
	function init()	{
			// Setting target, which must be a file reference to a file within the mounts.
		$this->target = $this->origTarget = t3lib_div::_GP('target');
		$this->returnUrl = t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('returnUrl'));

			// Creating file management object:
		$this->basicff = t3lib_div::makeInstance('t3lib_basicFileFunctions');
		$this->basicff->init($GLOBALS['FILEMOUNTS'],$GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);


		if (file_exists($this->target))	{
			$this->target=$this->basicff->cleanDirectoryName($this->target);		// Cleaning and checking target (file or dir)
		} else {
			$this->target='';
		}
		$key=$this->basicff->checkPathAgainstMounts($this->target.'/');
		if (!$this->target || !$key) {
			$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.xml:paramError', TRUE);
			$message = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.xml:targetNoDir', TRUE);
			throw new RuntimeException($title . ': ' . $message, 1294586841);
		}
			// Finding the icon
		switch($GLOBALS['FILEMOUNTS'][$key]['type']) {
			case 'user':
				$this->icon = 'gfx/i/_icon_ftp_user.gif';
				break;
			case 'group':
				$this->icon = 'gfx/i/_icon_ftp_group.gif';
				break;
			default:
				$this->icon = 'gfx/i/_icon_ftp.gif';
		}

		$this->icon = '<img'.t3lib_iconWorks::skinImg($this->backPath,$this->icon,'width="18" height="16"').' title="" alt="" />';

			// Relative path to filemount, $key:
		$this->shortPath = substr($this->target,strlen($GLOBALS['FILEMOUNTS'][$key]['path']));

			// Setting title:
		$this->title = $this->icon.$GLOBALS['FILEMOUNTS'][$key]['name'].': '.$this->shortPath;

		// ***************************
		// Setting template object
		// ***************************
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->setModuleTemplate('templates/file_edit.html');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->JScode=$this->doc->wrapScriptTags('
			function backToList()	{	//
				top.goToModule("file_list");
			}
		');
		$this->doc->form='<form action="tce_file.php" method="post" name="editform">';
	}

	/**
	 * Main function, redering the actual content of the editing page
	 *
	 * @return	void
	 */
	function main()	{
		//TODO: change locallang*.php to locallang*.xml
		$docHeaderButtons = $this->getButtons();

		$this->content = $this->doc->startPage($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_edit.php.pagetitle'));

			// hook	before compiling the output
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['preOutputProcessingHook'])) {
			$preOutputProcessingHook =& $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['preOutputProcessingHook'];
			if (is_array($preOutputProcessingHook)) {
				$hookParameters = array(
					'content' => &$this->content,
					'target' => &$this->target,
				);
				foreach ($preOutputProcessingHook as $hookFunction)	{
					t3lib_div::callUserFunction($hookFunction, $hookParameters, $this);
				}
			}
		}

		$pageContent = $this->doc->header($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_edit.php.pagetitle'));
		$pageContent .= $this->doc->spacer(2);

		$fI = pathinfo($this->target);
		$extList = $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'];

		if ($extList && t3lib_div::inList($extList,strtolower($fI['extension'])))		{
				// Read file content to edit:
			$fileContent = t3lib_div::getUrl($this->target);

				// making the formfields
			$hValue = 'file_edit.php?target='.rawurlencode($this->origTarget).'&returnUrl='.rawurlencode($this->returnUrl);

				// Edit textarea:
			$code.='
				<div id="c-edit">
					<textarea rows="30" name="file[editfile][0][data]" wrap="off"'.$this->doc->formWidthText(48,'width:98%;height:80%','off').' class="fixed-font enable-tab">'.
					t3lib_div::formatForTextarea($fileContent).
					'</textarea>
					<input type="hidden" name="file[editfile][0][target]" value="'.$this->target.'" />
					<input type="hidden" name="redirect" value="'.htmlspecialchars($hValue).'" />
				</div>
				<br />';

				// Make shortcut:
			if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
				$this->MCONF['name']='xMOD_file_edit.php';
				$docHeaderButtons['shortcut'] = $this->doc->makeShortcutIcon('target','',$this->MCONF['name'],1);
			} else {
				$docHeaderButtons['shortcut'] = '';
			}
		} else {
			$code .= sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_edit.php.coundNot'), $extList);
		}

			// Ending of section and outputting editing form:
		$pageContent.= $this->doc->sectionEnd();
		$pageContent.=$code;

				// hook	after compiling the output
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['postOutputProcessingHook'])) {
			$postOutputProcessingHook =& $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['postOutputProcessingHook'];
			if (is_array($postOutputProcessingHook)) {
				$hookParameters = array(
					'pageContent' => &$pageContent,
					'target' => &$this->target,
				);
				foreach ($postOutputProcessingHook as $hookFunction)	{
					t3lib_div::callUserFunction($hookFunction, $hookParameters, $this);
				}
			}
		}

			// Add the HTML as a section:
		$markerArray = array(
			'CSH' => $docHeaderButtons['csh'],
			'FUNC_MENU' => t3lib_BEfunc::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']),
			'BUTTONS' => $docHeaderButtons,
			'PATH' => $this->title,
			'CONTENT' => $pageContent,
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

	/**
	 * Builds the buttons for the docheader and returns them as an array
	 *
	 * @return array
	 **/
	function getButtons() {

		$buttons = array();

			// CSH button
		$buttons['csh'] = t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'file_edit', $GLOBALS['BACK_PATH'], '', TRUE);

			// Save button
		$theIcon = t3lib_iconWorks::getSpriteIcon('actions-document-save');
		$buttons['SAVE'] = '<a href="#" onclick="document.editform.submit();" title="'.$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_edit.php.submit', TRUE)).'">' . $theIcon . '</a>';

			// Save and Close button
		$theIcon = t3lib_iconWorks::getSpriteIcon('actions-document-save-close');
		$buttons['SAVE_CLOSE'] = '<a href="#" onclick="document.editform.redirect.value=\''.htmlspecialchars($this->returnUrl).'\'; document.editform.submit();" title="'.$GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_edit.php.saveAndClose', TRUE)).'">' . $theIcon . '</a>';

			// Cancel button
		$theIcon = t3lib_iconWorks::getSpriteIcon('actions-document-close');
		$buttons['CANCEL'] = '<a href="#" onclick="backToList(); return false;" title="' . $GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.cancel', TRUE)) . '">' . $theIcon . '</a>';

		return $buttons;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/file_edit.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/file_edit.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_file_edit');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>