<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skaarhoj (kasper@typo3.com)
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
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 * Revised for TYPO3 3.6 2/2003 by Kasper Skaarhoj
 * XHTML compliant (except textarea field)
 */


$BACK_PATH='';
require ('init.php');
require ('template.php');
require_once (PATH_t3lib.'class.t3lib_basicfilefunc.php');


// ***************************
// Script Classes
// ***************************
class SC_file_edit {
	var $content;
	var $basicff;
	var $shortPath;
	var $title;
	var $icon;
	var $target;
	var $doc;	

	/**
	 * Initialize
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		
			// Setting target, which must be a file reference to a file within the mounts.
		$this->target = t3lib_div::GPvar('target');

			// Creating file management object:
		$this->basicff = t3lib_div::makeInstance('t3lib_basicFileFunctions');
		$this->basicff->init($GLOBALS['FILEMOUNTS'],$TYPO3_CONF_VARS['BE']['fileExtensions']);
		
			
		if (@file_exists($this->target))	{
			$this->target=$this->basicff->cleanDirectoryName($this->target);		// Cleaning and checking target (file or dir)
		} else {
			$this->target='';
		}
		$key=$this->basicff->checkPathAgainstMounts($this->target.'/');
		if (!$this->target || !$key)	{
			t3lib_BEfunc::typo3PrintError ('Parameter Error','Target was not a directory!','');
			exit;
		}
			// Finding the icon
		switch($GLOBALS['FILEMOUNTS'][$key]['type'])	{
			case 'user':	$this->icon = 'gfx/i/_icon_ftp_user.gif';	break;
			case 'group':	$this->icon = 'gfx/i/_icon_ftp_group.gif';	break;
			default:		$this->icon = 'gfx/i/_icon_ftp.gif';	break;
		}
		$this->shortPath = substr($this->target,strlen($GLOBALS['FILEMOUNTS'][$key]['path']));
		$this->title = $GLOBALS['FILEMOUNTS'][$key]['name'].': '.$this->shortPath;
		
		// ***************************
		// Setting template object
		// ***************************
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->docType = 'xhtml_trans';
		$this->doc->backPath = $BACK_PATH;
		$this->doc->inDocStyles.= 'DIV.typo3-def {width:98%; height:100%}';
		$this->doc->JScode=$this->doc->wrapScriptTags('
			function backToList()	{
				top.goToModule("file_list");
			}
		');
		$this->doc->form='<form action="tce_file.php" method="post" name="editform">';
	}

	/**
	 * Main
	 */
	function main()	{
		global $BE_USER, $LANG, $TYPO3_CONF_VARS;

		$this->content='';
		$this->content.=$this->doc->startPage($LANG->sL('LLL:EXT:lang/locallang_core.php:file_edit.php.pagetitle'));
		$this->content.=$this->doc->header($LANG->sL('LLL:EXT:lang/locallang_core.php:file_edit.php.pagetitle'));
		$this->content.=$this->doc->spacer(5);
		$this->content.=$this->doc->section('',$this->doc->getFileheader($this->title,$this->shortPath,$this->icon));
		$this->content.=$this->doc->divider(5);
		
		$fI = pathinfo($this->target);
		$extList=$TYPO3_CONF_VARS['SYS']['textfile_ext'];
		
		if ($extList && t3lib_div::inList($extList,strtolower($fI['extension'])))		{
				// Read file content to edit:
			$fileContent = t3lib_div::getUrl($this->target);
			
				// making the formfields
			$hValue = 'file_edit.php?target='.rawurlencode(t3lib_div::GPvar("target"));
			$code='';
			$code.='<input type="hidden" name="redirect" value="'.htmlspecialchars($hValue).'" />'.
					'<input type="submit" value="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:file_edit.php.submit',1).'" />&nbsp;&nbsp;'.
					'<input type="submit" value="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:file_edit.php.saveAndClose',1).'" onclick="document.editform.redirect.value=\'\';" />&nbsp;&nbsp;'.
					'<input type="submit" value="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.cancel',1).'" onclick="backToList(); return false;" />';

				// Edit textarea:
			$code.='<br />
				<textarea rows="30" name="file[editfile][0][data]" wrap="off"'.$this->doc->formWidthText(48,'width:98%;height:80%','off').'>'.
				t3lib_div::formatForTextarea($fileContent).
				'</textarea>
				<input type="hidden" name="file[editfile][0][target]" value="'.$this->target.'" />
				<br />';

				// Make shortcut:
			if ($BE_USER->mayMakeShortcut())	{
				$this->MCONF['name']='xMOD_file_edit.php';
				$code.= '<br />'.$this->doc->makeShortcutIcon('target','',$this->MCONF['name'],1);
			}
		} else {
			$code.=sprintf($LANG->sL('LLL:EXT:lang/locallang_core.php:file_edit.php.coundNot'), $extList);
		}
		
			// Ending of section and outputting editing form:
		$this->content.= $this->doc->sectionEnd();
		$this->content.=$code;
	}

	/**
	 * Ends page and outputs content
	 */
	function printContent()	{
		global $SOBE;

		$this->content.=$this->doc->endPage();
		echo $this->content;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/file_edit.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/file_edit.php']);
}












// Make instance:
$SOBE = t3lib_div::makeInstance('SC_file_edit');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>