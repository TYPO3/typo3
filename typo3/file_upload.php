<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * $Id$
 * Revised for TYPO3 3.6 November/2003 by Kasper Skaarhoj
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   77: class SC_file_upload
 *  103:     function init()
 *  171:     function main()
 *  241:     function printContent()
 *
 * TOTAL FUNCTIONS: 3
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */



$BACK_PATH = '';
require('init.php');
require('template.php');
$LANG->includeLLFile('EXT:lang/locallang_misc.xml');
require_once(PATH_t3lib.'class.t3lib_basicfilefunc.php');













/**
 * Script Class for display up to 10 upload fields
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_file_upload {

		// External, static:
	var $uploadNumber=10;

		// Internal, static:
	var $doc;			// Template object.
	var $basicff;		// Instance of "t3lib_basicFileFunctions"
	var $icon;			// Will be set to the proper icon for the $target value.
	var $shortPath;		// Relative path to current found filemount
	var $title;			// Name of the filemount

		// Internal, static (GPVar):
	var $number;
	var $target;		// Set with the target path inputted in &target
	var $returnUrl;		// Return URL of list module.

		// Internal, dynamic:
	var $content;		// Accumulating content


	/**
	 * Constructor for initializing the class
	 *
	 * @return	void
	 */
	function init()	{
		global $LANG,$BACK_PATH,$TYPO3_CONF_VARS;

			// Initialize GPvars:
		$this->number = t3lib_div::_GP('number');
		$this->target = t3lib_div::_GP('target');
		$this->returnUrl = t3lib_div::sanitizeBackEndUrl(t3lib_div::_GP('returnUrl'));

		if (empty($this->number))	{
			$defaultFileUploads = $GLOBALS['BE_USER']->getTSConfigVal('options.defaultFileUploads');
			if ($defaultFileUploads)	{
				$this->number = t3lib_div::intInRange($defaultFileUploads,1,$this->uploadNumber);
			}
		}
			// Init basic-file-functions object:
		$this->basicff = t3lib_div::makeInstance('t3lib_basicFileFunctions');
		$this->basicff->init($GLOBALS['FILEMOUNTS'],$TYPO3_CONF_VARS['BE']['fileExtensions']);

			// Cleaning and checking target
		$this->target = $GLOBALS['LANG']->csConvObj->conv($this->target,'utf-8',$GLOBALS['LANG']->charSet);
		$this->target = $this->basicff->is_directory($this->target);
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

			// Relative path to filemount, $key:
		$this->shortPath = substr($this->target,strlen($GLOBALS['FILEMOUNTS'][$key]['path']));

			// Setting title:
		$this->title = $GLOBALS['FILEMOUNTS'][$key]['name'].': '.$this->shortPath;

			// Setting template object
		$this->doc = t3lib_div::makeInstance('smallDoc');
		$this->doc->docType = 'xhtml_trans';
		$this->doc->backPath = $BACK_PATH;
		$this->doc->form='<form action="tce_file.php" method="post" name="editform" enctype="'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'].'">';

		if($GLOBALS['BE_USER']->jsConfirmation(1))	{
			$confirm = ' && confirm('.$LANG->JScharCode($LANG->sL('LLL:EXT:lang/locallang_core.php:mess.redraw')).')';
		} else {
			$confirm = '';
		}
		$this->doc->JScode=$this->doc->wrapScriptTags('
			var path = "'.$this->target.'";

			function reload(a)	{	//
				if (!changed || (changed '.$confirm.'))	{
					var params = "&target="+encodeURIComponent(path)+"&number="+a+"&returnUrl='
							. urlencode($GLOBALS['LANG']->csConvObj->conv($this->returnUrl,$GLOBALS['LANG']->charSet,'utf-8'))
							. '";
					window.location.href = "file_upload.php?"+params;
				}
			}
			function backToList()	{	//
				top.goToModule("file_list");
			}
			var changed = 0;
		');
	}

	/**
	 * Main function, rendering the upload file form fields
	 *
	 * @return	void
	 */
	function main()	{
		global $LANG;

			// Make page header:
		$this->content='';
		$this->content.=$this->doc->startPage($LANG->sL('LLL:EXT:lang/locallang_core.php:file_upload.php.pagetitle'));
		$this->content.=$this->doc->header($LANG->sL('LLL:EXT:lang/locallang_core.php:file_upload.php.pagetitle'));
		$this->content.=$this->doc->spacer(5);
		$this->content.=$this->doc->section('',$this->doc->getFileheader($this->title,$this->shortPath,$this->icon));
		$this->content.=$this->doc->divider(5);


			// Making the selector box for the number of concurrent uploads
		$this->number = t3lib_div::intInRange($this->number,1,10);
		$code='
			<div id="c-select">
				<select name="number" onchange="reload(this.options[this.selectedIndex].value);">';
		for ($a=1;$a<=$this->uploadNumber;$a++)	{
			$code.='
					<option value="'.$a.'"'.($this->number==$a?' selected="selected"':'').'>'.$a.' '.$LANG->sL('LLL:EXT:lang/locallang_core.php:file_upload.php.files',1).'</option>';
		}
		$code.='
				</select>
			</div>
			';

			// Make checkbox for "overwrite"
		$code.='
			<div id="c-override">
				<input type="checkbox" name="overwriteExistingFiles" id="overwriteExistingFiles" value="1" /> <label for="overwriteExistingFiles">'.$LANG->getLL('overwriteExistingFiles',1).'</label>
			</div>
			';

			// Produce the number of upload-fields needed:
		$code.='
			<div id="c-upload">
		';
		for ($a=0;$a<$this->number;$a++)	{
				// Adding 'size="50" ' for the sake of Mozilla!
			$code.='
				<input type="file" name="upload_'.$a.'"'.$this->doc->formWidth(35).' size="50" onclick="changed=1;" />
				<input type="hidden" name="file[upload]['.$a.'][target]" value="'.htmlspecialchars($this->target).'" />
				<input type="hidden" name="file[upload]['.$a.'][data]" value="'.$a.'" /><br />
			';
		}
		$code.='
			</div>
		';

			// Submit button:
		$code.='
			<div id="c-submit">
				<input type="submit" value="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:file_upload.php.submit',1).'" />
				<input type="submit" value="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.cancel',1).'" onclick="backToList(); return false;" />
				<input type="hidden" name="redirect" value="'.htmlspecialchars($this->returnUrl).'" />
			</div>
		';

			// CSH:
		$code.= t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'file_upload', $GLOBALS['BACK_PATH'],'<br/>');

			// Add the HTML as a section:
		$this->content.= $this->doc->section('',$code);
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/file_upload.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/file_upload.php']);
}












// Make instance:
$SOBE = t3lib_div::makeInstance('SC_file_upload');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>