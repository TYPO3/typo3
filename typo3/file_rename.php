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
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   74: class SC_file_rename
 *   96:     function init()
 *  149:     function main()
 *  192:     function printContent()
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
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

	/**
	 * File processing object
	 *
	 * @var t3lib_basicFileFunctions
	 */
	var $basicff;
	var $icon;			// Will be set to the proper icon for the $target value.
	var $shortPath;		// Relative path to current found filemount
	var $title;			// Name of the filemount

		// Internal, static (GPVar):
	var $target;		// Set with the target path inputted in &target
	var $returnUrl;		// Return URL of list module.

		// Internal, dynamic:
	var $content;		// Accumulating content


	/**
	 * Constructor function for class
	 *
	 * @return	void
	 */
	function init()	{
		//TODO remove global
		global $LANG,$BACK_PATH,$TYPO3_CONF_VARS;

			// Initialize GPvars:
		$this->target = t3lib_div::_GP('target');
		$this->returnUrl = t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('returnUrl'));

			// Init basic-file-functions object:
		$this->basicff = t3lib_div::makeInstance('t3lib_basicFileFunctions');
		$this->basicff->init($GLOBALS['FILEMOUNTS'],$TYPO3_CONF_VARS['BE']['fileExtensions']);

			// Cleaning and checking target
		if (file_exists($this->target))	{
			$this->target=$this->basicff->cleanDirectoryName($this->target);		// Cleaning and checking target (file or dir)
		} else {
			$this->target='';
		}
		$key=$this->basicff->checkPathAgainstMounts($this->target.'/');
		if (!$this->target || !$key) {
			$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.xml:paramError', TRUE);
			$message = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.xml:targetNoDir', TRUE);
			throw new RuntimeException($title . ': ' . $message, 1294586844);
		}

			// Finding the icon
		switch($GLOBALS['FILEMOUNTS'][$key]['type'])	{
			case 'user':	$this->icon = 'gfx/i/_icon_ftp_user.gif';	break;
			case 'group':	$this->icon = 'gfx/i/_icon_ftp_group.gif';	break;
			default:		$this->icon = 'gfx/i/_icon_ftp.gif';	break;
		}

		$this->icon = '<img'.t3lib_iconWorks::skinImg($this->backPath,$this->icon,'width="18" height="16"').' title="" alt="" />';

			// Relative path to filemount, $key:
		$this->shortPath = substr($this->target,strlen($GLOBALS['FILEMOUNTS'][$key]['path']));

			// Setting title:
		$this->title = $this->icon . htmlspecialchars($GLOBALS['FILEMOUNTS'][$key]['name']) . ': ' . $this->shortPath;

			// Setting template object
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->setModuleTemplate('templates/file_rename.html');
		$this->doc->backPath = $BACK_PATH;
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
		//TODO remove global, change $LANG into $GLOBALS['LANG'], change locallang*.php to locallang*.xml

		global $LANG;

			// Make page header:
		$this->content = $this->doc->startPage($LANG->sL('LLL:EXT:lang/locallang_core.php:file_rename.php.pagetitle'));

		$pageContent = $this->doc->header($LANG->sL('LLL:EXT:lang/locallang_core.php:file_rename.php.pagetitle'));
		$pageContent .= $this->doc->spacer(5);
		$pageContent .= $this->doc->divider(5);


		$code = '<form action="tce_file.php" method="post" name="editform">';
			// Making the formfields for renaming:
		$code .= '

			<div id="c-rename">
				<input type="text" name="file[rename][0][data]" value="'.htmlspecialchars(basename($this->shortPath)).'"'.$GLOBALS['TBE_TEMPLATE']->formWidth(20).' />
				<input type="hidden" name="file[rename][0][target]" value="'.htmlspecialchars($this->target).'" />
			</div>
		';

			// Making submit button:
		$code.='
			<div id="c-submit">
				<input type="submit" value="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:file_rename.php.submit',1).'" />
				<input type="submit" value="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.cancel',1).'" onclick="backToList(); return false;" />
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


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/file_rename.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/file_rename.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_file_rename');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>
