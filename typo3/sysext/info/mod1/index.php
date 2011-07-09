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
 * Module: Web>Info
 * Presents various page related information from extensions
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */

$LANG->includeLLFile('EXT:lang/locallang_mod_web_info.xml');
$BE_USER->modAccess($MCONF,1);



/**
 * Script Class for the Web > Info module
 * This class creates the framework to which other extensions can connect their sub-modules
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_mod_web_info_index extends t3lib_SCbase {

		// Internal, dynamic:
	var $be_user_Array;
	var $CALC_PERMS;
	var $pageinfo;

	/**
	 * Document Template Object
	 *
	 * @var mediumDoc
	 */
	var $doc;

	/**
	 * Initialize module header etc and call extObjContent function
	 *
	 * @return	void
	 */
	function main()	{
		// Access check...
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		if (($this->id && $access) || ($GLOBALS['BE_USER']->user['admin'] && !$this->id)) {
			$this->CALC_PERMS = $GLOBALS['BE_USER']->calcPerms($this->pageinfo);
			if ($GLOBALS['BE_USER']->user['admin'] && !$this->id) {
				$this->pageinfo=array('title' => '[root-level]','uid'=>0,'pid'=>0);
			}

			$this->doc = t3lib_div::makeInstance('template');
			$this->doc->backPath = $GLOBALS['BACK_PATH'];
			$this->doc->setModuleTemplate('templates/info.html');
			$this->doc->tableLayout = Array (
				'0' => Array (
					'0' => Array('<td valign="top"><strong>','</strong></td>'),
					"defCol" => Array('<td><img src="'.$this->doc->backPath.'clear.gif" width="10" height="1" alt="" /></td><td valign="top"><strong>','</strong></td>')
				),
				"defRow" => Array (
					"0" => Array('<td valign="top">','</td>'),
					"defCol" => Array('<td><img src="'.$this->doc->backPath.'clear.gif" width="10" height="1" alt="" /></td><td valign="top">','</td>')
				)
			);

				// JavaScript
			$this->doc->JScode = $this->doc->wrapScriptTags('
				script_ended = 0;
				function jumpToUrl(URL)	{	//
					window.location.href = URL;
				}
			');
			$this->doc->postCode = $this->doc->wrapScriptTags('
				script_ended = 1;
				if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
			');

				// Setting up the context sensitive menu:
			$this->doc->getContextMenuCode();
			$this->doc->form = '<form action="mod.php?M=' . htmlspecialchars(t3lib_div::_GP('M')) . '" method="post" name="webinfoForm">';

			$vContent = $this->doc->getVersionSelector($this->id,1);
			if ($vContent)	{
				$this->content.=$this->doc->section('',$vContent);
			}

			$this->extObjContent();

				// Setting up the buttons and markers for docheader
			$docHeaderButtons = $this->getButtons();
			$markers = array(
				'CSH' => $docHeaderButtons['csh'],
				'FUNC_MENU' => t3lib_BEfunc::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']),
				'CONTENT' => $this->content
			);

				// Build the <body> for the module
			$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		} else {
				// If no access or if ID == zero
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $GLOBALS['BACK_PATH'];

			$this->content = $this->doc->header($GLOBALS['LANG']->getLL('title'));
			$this->content .= $this->doc->spacer(5);
			$this->content .= $this->doc->spacer(10);
		}
		// Renders the module page
		$this->content = $this->doc->render(
			$GLOBALS['LANG']->getLL('title'),
			$this->content
		);
	}

	/**
	 * Print module content (from $this->content)
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content = $this->doc->insertStylesAndJS($this->content);
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
			'view' => '',
			'record_list' => '',
			'shortcut' => '',
		);
			// CSH
		$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_info', '', $GLOBALS['BACK_PATH'], '', TRUE);

			// View page
		$buttons['view'] = '<a href="#" onclick="' . htmlspecialchars(
			t3lib_BEfunc::viewOnClick(
				$this->pageinfo['uid'],
				$GLOBALS['BACK_PATH'],
				t3lib_BEfunc::BEgetRootLine($this->pageinfo['uid'])
			)) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showPage', 1) . '">' .
			t3lib_iconWorks::getSpriteIcon('actions-document-view') .
			'</a>';

			// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('id, edit_record, pointer, new_unique_uid, search_field, search_levels, showLimit', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
		}

			// If access to Web>List for user, then link to that module.
		$buttons['record_list'] = t3lib_BEfunc::getListViewLink(
			array(
				'id' => $this->pageinfo['uid'],
				'returnUrl' => t3lib_div::getIndpEnv('REQUEST_URI'),
			),
			$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.showList')
		);

		return $buttons;
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/mod/web/info/index.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/mod/web/info/index.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_web_info_index');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);
$SOBE->checkExtObj();	// Checking for first level external objects

// Repeat Include files! - if any files has been added by second-level extensions
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);
$SOBE->checkSubExtObj();	// Checking second level external objects

$SOBE->main();
$SOBE->printContent();

?>