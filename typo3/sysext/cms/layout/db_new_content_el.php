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
 * New content elements wizard
 * (Part of the 'cms' extension)
 * 
 * $Id$
 * Revised for TYPO3 3.6 November/2003 by Kasper Skaarhoj
 * XHTML compatible.
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  100: class ext_posMap extends t3lib_positionMap 
 *  110:     function wrapRecordTitle($str,$row)	
 *  124:     function onClickInsertRecord($row,$vv,$moveUid,$pid,$sys_lang=0) 
 *
 *
 *  152: class SC_db_new_content_el 
 *  175:     function init()	
 *  211:     function main()	
 *  355:     function printContent()	
 *
 *              SECTION: OTHER FUNCTIONS:
 *  384:     function getWizardItems()	
 *  394:     function wizardArray()	
 *
 * TOTAL FUNCTIONS: 7
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
 
 
unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');

	// Unset MCONF/MLANG since all we wanted was back path etc. for this particular script.
unset($MCONF);
unset($MLANG);

	// Merging locallang files/arrays:
include ($BACK_PATH.'sysext/lang/locallang_misc.php');
$LOCAL_LANG_orig = $LOCAL_LANG;
include ('locallang_db_new_content_el.php');
$LOCAL_LANG = t3lib_div::array_merge_recursive_overrule($LOCAL_LANG_orig,$LOCAL_LANG);

	// Exits if 'cms' extension is not loaded:
t3lib_extMgm::isLoaded('cms',1);

	// Include needed libraries:
require_once (PATH_t3lib.'class.t3lib_page.php');
require_once (PATH_t3lib.'class.t3lib_positionmap.php');










/**
 * Local position map class
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class ext_posMap extends t3lib_positionMap {
	var $dontPrintPageInsertIcons = 1;
	
	/**
	 * Wrapping the title of the record - here we just return it.
	 * 
	 * @param	string		The title value.
	 * @param	array		The record row.
	 * @return	string		Wrapped title string.
	 */
	function wrapRecordTitle($str,$row)	{
		return $str;
	}

	/**
	 * Create on-click event value.
	 * 
	 * @param	array		The record.
	 * @param	string		Column position value.
	 * @param	integer		Move uid
	 * @param	integer		PID value.
	 * @param	integer		System language
	 * @return	string		
	 */
	function onClickInsertRecord($row,$vv,$moveUid,$pid,$sys_lang=0) {
		$table='tt_content';
		
		$location=$this->backPath.'alt_doc.php?edit[tt_content]['.(is_array($row)?-$row['uid']:$pid).']=new&defVals[tt_content][colPos]='.$vv.'&defVals[tt_content][sys_language_uid]='.$sys_lang.'&returnUrl='.rawurlencode($GLOBALS['SOBE']->R_URI);

		return 'document.location=\''.$location.'\'+document.editForm.defValues.value; return false;';
	}
}













/**
 * Script Class for the New Content element wizard
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_db_new_content_el {
	
		// Internal, static (from GPvars):
	var $id;					// Page id
	var $sys_language=0;		// Sys language
	var $R_URI='';				// Return URL.
	var $colPos;				// If set, the content is destined for a specific column.
	var $uid_pid;				// 

		// Internal, static:
	var $modTSconfig=array();	// Module TSconfig.
	var $doc;					// Internal backend template object

		// Internal, dynamic:
	var $include_once = array();	// Includes a list of files to include between init() and main() - see init()
	var $content;					// Used to accumulate the content of the module.
	var $access;				// Access boolean.
	
	/**
	 * Constructor, initializing internal variables.
	 * 
	 * @return	void		
	 */
	function init()	{
		global $BE_USER,$BACK_PATH,$TBE_MODULES_EXT;
		
			// Setting class files to include:
		if (is_array($TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']))	{
			$this->include_once = array_merge($this->include_once,$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']);
		}
		
			// Setting internal vars:
		$this->id = intval(t3lib_div::GPvar('id'));
		$this->sys_language = intval(t3lib_div::GPvar('sys_language_uid'));
		$this->R_URI = t3lib_div::GPvar('returnUrl');
		$this->colPos = t3lib_div::GPvar('colPos');
		$this->uid_pid = intval(t3lib_div::GPvar('uid_pid'));

		$this->MCONF['name'] = 'xMOD_db_new_content_el';
		$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->id,'mod.'.$this->MCONF['name']);

			// Starting the document template object:		
		$this->doc = t3lib_div::makeInstance('mediumDoc');
		$this->doc->docType= 'xhtml_trans';
		$this->doc->backPath = $BACK_PATH;
		$this->doc->JScode='';
		$this->doc->form='<form action="" name="editForm"><input type="hidden" name="defValues" value="" />';
		
			// Getting the current page and receiving access information (used in main())
		$perms_clause = $BE_USER->getPagePermsClause(1);
		$pageinfo = t3lib_BEfunc::readPageAccess($this->id,$perms_clause);
		$this->access = is_array($pageinfo) ? 1 : 0;
	}

	/**
	 * Creating the module output.
	 * 
	 * @return	void		
	 */
	function main()	{
		global $LANG,$BACK_PATH;

		if ($this->id && $this->access)	{
			
				// Init position map object:
			$posMap = t3lib_div::makeInstance('ext_posMap');
			$posMap->cur_sys_language = $this->sys_language;
			$posMap->backPath = $BACK_PATH;
		
			if ((string)$this->colPos!='')	{	// If a column is pre-set:
				if ($this->uid_pid<0)	{
					$row=array();
					$row['uid']=abs($this->uid_pid);
				} else {
					$row='';
				}
				$onClickEvent = $posMap->onClickInsertRecord($row,$this->colPos,'',$this->uid_pid,$this->sys_language);
			} else {
				$onClickEvent='';
			}
		
			$this->doc->JScode=$this->doc->wrapScriptTags('
				function goToalt_doc()	{	//
					'.$onClickEvent.'
				}	
			');
		
		
			// ***************************
			// Creating content
			// ***************************
			$this->content='';
			$this->content.=$this->doc->startPage($LANG->getLL('newContentElement'));
			$this->content.=$this->doc->header($LANG->getLL('newContentElement'));
			$this->content.=$this->doc->spacer(5);
		
			$elRow = t3lib_BEfunc::getRecord('pages',$this->id);
			$hline = t3lib_iconWorks::getIconImage('pages',$elRow,$BACK_PATH,' title="'.htmlspecialchars(t3lib_BEfunc::getRecordIconAltText($elRow,'pages')).'" align="top"');
			$hline.= t3lib_BEfunc::getRecordTitle('pages',$elRow,1);
			$this->content.=$this->doc->section('',$hline,0,1);
			$this->content.=$this->doc->spacer(10);
		
		
				// Wizard
			$code='';
			$lines=array();
			$wizardItems = $this->getWizardItems();

				// Traverse items for the wizard.
				// An item is either a header or an item rendered with a radio button and title/description and icon:			
			$cc=0;
			foreach($wizardItems as $k => $wInfo)	{
				if ($wInfo['header'])	{
					if ($cc>0) $lines[]='
						<tr>
							<td colspan="3"><br /></td>
						</tr>';
					$lines[]='
						<tr class="bgColor5">
							<td colspan="3"><strong>'.htmlspecialchars($wInfo['header']).'</strong></td>
						</tr>';
				} else {
					$tL=array();
					
						// Radio button:
					$oC = "document.editForm.defValues.value=unescape('".rawurlencode($wInfo['params'])."');goToalt_doc();".(!$onClickEvent?"document.location='#sel2';":'');
					$tL[]='<input type="radio" name="tempB" value="'.htmlspecialchars($k).'" onclick="'.htmlspecialchars($this->doc->thisBlur().$oC).'" />';

						// Onclick action for icon/title:			
					$aOnClick = 'document.editForm.tempB['.$cc.'].checked=1;'.$this->doc->thisBlur().$oC.'return false;';

						// Icon:
					$iInfo = @getimagesize($wInfo['icon']);
					$tL[]='<a href="#" onclick="'.htmlspecialchars($aOnClick).'"><img'.t3lib_iconWorks::skinImg($this->doc->backPath,$wInfo['icon'],'').' alt="" /></a>';

						// Title + description:					
					$tL[]='<a href="#" onclick="'.htmlspecialchars($aOnClick).'"><strong>'.htmlspecialchars($wInfo['title']).'</strong><br />'.nl2br(htmlspecialchars(trim($wInfo['description']))).'</a>';
			
						// Finally, put it together in a table row:
					$lines[]='
						<tr>
							<td valign="top">'.implode('</td>
							<td valign="top">',$tL).'</td>
						</tr>';
					$cc++;
				}
			}
				// Add the wizard table to the content:
			$code.=$LANG->getLL('sel1',1).'<br /><br />

		
			<!--
				Content Element wizard table:
			-->
				<table border="0" cellpadding="1" cellspacing="2" id="typo3-ceWizardTable">
					'.implode('',$lines).'
				</table>';
			$this->content.=$this->doc->section(!$onClickEvent?$LANG->getLL('1_selectType'):'',$code,0,1);
		
				

				// If the user must also select a column:
			if (!$onClickEvent)	{

					// Add anchor "sel2"
				$this->content.=$this->doc->section('','<a name="sel2"></a>');
				$this->content.=$this->doc->spacer(20);

					// Select position
				$code=$LANG->getLL('sel2',1).'<br /><br />';
			
					// Load SHARED page-TSconfig settings and retrieve column list from there, if applicable:
				$modTSconfig_SHARED = t3lib_BEfunc::getModTSconfig($this->id,'mod.SHARED');	
				$colPosList = strcmp(trim($modTSconfig_SHARED['properties']['colPos_list']),'') ? trim($modTSconfig_SHARED['properties']['colPos_list']) : '1,0,2,3';
				$colPosList = implode(',',array_unique(t3lib_div::intExplode(',',$colPosList)));		// Removing duplicates, if any
			
					// Finally, add the content of the column selector to the content:
				$code.=$posMap->printContentElementColumns($this->id,0,$colPosList,1,$this->R_URI);
				$this->content.=$this->doc->section($LANG->getLL('2_selectPosition'),$code,0,1);
			}

				// IF there is a return-url set, then print a go-back link:		
			if ($this->R_URI)	{
				$code='<br /><br /><a href="'.htmlspecialchars($this->R_URI).'" class="typo3-goBack"><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/goback.gif','width="14" height="14"').' alt="" />'.$LANG->getLL('goBack',1).'</a>';
				$this->content.=$this->doc->section('',$code,0,1);
			}
		
				// Add a very high clear-gif, 700 px (so that the link to the anchor "sel2" shows this part in top for sure...)
			$this->content.=$this->doc->section('','<img src="clear.gif" width="1" height="700" alt="" />',0,1);

		} else {		// In case of no access:
			$this->content='';
			$this->content.=$this->doc->startPage($LANG->getLL('newContentElement'));
			$this->content.=$this->doc->header($LANG->getLL('newContentElement'));
			$this->content.=$this->doc->spacer(5);
		}
	}

	/**
	 * Print out the accumulated content:
	 * 
	 * @return	void		
	 */
	function printContent()	{
		global $SOBE;

		$this->content.= $this->doc->endPage();
		echo $this->content;
	}
	










	/***************************
	 *
	 * OTHER FUNCTIONS:	
	 *
	 ***************************/


	/**
	 * Returns the content of wizardArray() function...
	 * 
	 * @return	array		Returns the content of wizardArray() function...
	 */
	function getWizardItems()	{
		return $this->wizardArray();
	}

	/**
	 * Returns the array of elements in the wizard display.
	 * For the plugin section there is support for adding elements there from a global variable.
	 * 
	 * @return	array		
	 */
	function wizardArray()	{
		global $LANG,$TBE_MODULES_EXT;
		
		$wizardItems = array(
			'common' => array('header'=>$LANG->getLL('common')),
			'common_1' => array(
				'icon'=>'gfx/c_wiz/regular_text.gif',
				'title'=>$LANG->getLL('common_1_title'),
				'description'=>$LANG->getLL('common_1_description'),
				'params'=>'&defVals[tt_content][CType]=text'
			),
			'common_2' => array(
				'icon'=>'gfx/c_wiz/text_image_below.gif',
				'title'=>$LANG->getLL('common_2_title'),
				'description'=>$LANG->getLL('common_2_description'),
				'params'=>'&defVals[tt_content][CType]=textpic&defVals[tt_content][imageorient]=8'
			),
			'common_3' => array(
				'icon'=>'gfx/c_wiz/text_image_right.gif',
				'title'=>$LANG->getLL('common_3_title'),
				'description'=>$LANG->getLL('common_3_description'),
				'params'=>'&defVals[tt_content][CType]=textpic&defVals[tt_content][imageorient]=17'
			),
			'common_4' => array(
				'icon'=>'gfx/c_wiz/images_only.gif',
				'title'=>$LANG->getLL('common_4_title'),
				'description'=>$LANG->getLL('common_4_description'),
				'params'=>'&defVals[tt_content][CType]=image&defVals[tt_content][imagecols]=2'
			),
			'common_5' => array(
				'icon'=>'gfx/c_wiz/bullet_list.gif',
				'title'=>$LANG->getLL('common_5_title'),
				'description'=>$LANG->getLL('common_5_description'),
				'params'=>'&defVals[tt_content][CType]=bullets'
			),
			'common_6' => array(
				'icon'=>'gfx/c_wiz/table.gif',
				'title'=>$LANG->getLL('common_6_title'),
				'description'=>$LANG->getLL('common_6_description'),
				'params'=>'&defVals[tt_content][CType]=table'
			),
			'special' => array('header'=>$LANG->getLL('special')),
			'special_1' => array(
				'icon'=>'gfx/c_wiz/filelinks.gif',
				'title'=>$LANG->getLL('special_1_title'),
				'description'=>$LANG->getLL('special_1_description'),
				'params'=>'&defVals[tt_content][CType]=uploads'
			),
			'special_2' => array(
				'icon'=>'gfx/c_wiz/multimedia.gif',
				'title'=>$LANG->getLL('special_2_title'),
				'description'=>$LANG->getLL('special_2_description'),
				'params'=>'&defVals[tt_content][CType]=multimedia'
			),
			'special_3' => array(
				'icon'=>'gfx/c_wiz/sitemap2.gif',
				'title'=>$LANG->getLL('special_3_title'),
				'description'=>$LANG->getLL('special_3_description'),
				'params'=>'&defVals[tt_content][CType]=menu&defVals[tt_content][menu_type]=2'
			),
			'special_4' => array(
				'icon'=>'gfx/c_wiz/html.gif',
				'title'=>$LANG->getLL('special_4_title'),
				'description'=>$LANG->getLL('special_4_description'),
				'params'=>'&defVals[tt_content][CType]=html'
			),
		
		
			'forms' => array('header'=>$LANG->getLL('forms')),
			'forms_1' => array(
				'icon'=>'gfx/c_wiz/mailform.gif',
				'title'=>$LANG->getLL('forms_1_title'),
				'description'=>$LANG->getLL('forms_1_description'),
				'params'=>'&defVals[tt_content][CType]=mailform&defVals[tt_content][bodytext]='.rawurlencode(trim('
# Example content:
Name: | *name = input,40 | Enter your name here
Email: | *email=input,40 |
Address: | address=textarea,40,5 |
Contact me: | tv=check | 1

|formtype_mail = submit | Send form!
|html_enabled=hidden | 1
|subject=hidden| This is the subject
				'))
			),
			'forms_2' => array(
				'icon'=>'gfx/c_wiz/searchform.gif',
				'title'=>$LANG->getLL('forms_2_title'),
				'description'=>$LANG->getLL('forms_2_description'),
				'params'=>'&defVals[tt_content][CType]=search'
			),
			'forms_3' => array(
				'icon'=>'gfx/c_wiz/login_form.gif',
				'title'=>$LANG->getLL('forms_3_title'),
				'description'=>$LANG->getLL('forms_3_description'),
				'params'=>'&defVals[tt_content][CType]=login'
			),
			'plugins' => array('header'=>$LANG->getLL('plugins')),
		);


			// PLUG-INS:
		if (is_array($TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']))	{
			reset($TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']);
			while(list($class,$path)=each($TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']))	{
				$modObj = t3lib_div::makeInstance($class);
				$wizardItems = $modObj->proc($wizardItems);
			}
		}

		return $wizardItems;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cms/layout/db_new_content_el.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cms/layout/db_new_content_el.php']);
}












// Make instance:
$SOBE = t3lib_div::makeInstance('SC_db_new_content_el');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();
?>