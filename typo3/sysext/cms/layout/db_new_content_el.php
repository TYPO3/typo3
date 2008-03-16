<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2008 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  101: class ext_posMap extends t3lib_positionMap
 *  111:     function wrapRecordTitle($str,$row)
 *  125:     function onClickInsertRecord($row,$vv,$moveUid,$pid,$sys_lang=0)
 *
 *
 *  153: class SC_db_new_content_el
 *  176:     function init()
 *  212:     function main()
 *  359:     function printContent()
 *
 *              SECTION: OTHER FUNCTIONS:
 *  388:     function getWizardItems()
 *  398:     function wizardArray()
 *  549:     function removeInvalidElements(&$wizardItems)
 *
 * TOTAL FUNCTIONS: 8
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


unset($MCONF);
require('conf.php');
require($BACK_PATH.'init.php');
require($BACK_PATH.'template.php');

	// Unset MCONF/MLANG since all we wanted was back path etc. for this particular script.
unset($MCONF);
unset($MLANG);

	// Merging locallang files/arrays:
$LANG->includeLLFile('EXT:lang/locallang_misc.xml');
$LOCAL_LANG_orig = $LOCAL_LANG;
$LANG->includeLLFile('EXT:cms/layout/locallang_db_new_content_el.xml');
$LOCAL_LANG = t3lib_div::array_merge_recursive_overrule($LOCAL_LANG_orig,$LOCAL_LANG);

	// Exits if 'cms' extension is not loaded:
t3lib_extMgm::isLoaded('cms',1);

	// Include needed libraries:
require_once(PATH_t3lib.'class.t3lib_page.php');
require_once(PATH_t3lib.'class.t3lib_positionmap.php');










/**
 * Local position map class
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
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

		return 'window.location.href=\''.$location.'\'+document.editForm.defValues.value; return false;';
	}
}













/**
 * Script Class for the New Content element wizard
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
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

	/**
	 * Internal backend template object
	 *
	 * @var mediumDoc
	 */
	var $doc;

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
		$this->id = intval(t3lib_div::_GP('id'));
		$this->sys_language = intval(t3lib_div::_GP('sys_language_uid'));
		$this->R_URI = t3lib_div::_GP('returnUrl');
		$this->colPos = t3lib_div::_GP('colPos');
		$this->uid_pid = intval(t3lib_div::_GP('uid_pid'));

		$this->MCONF['name'] = 'xMOD_db_new_content_el';
		$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->id,'mod.'.$this->MCONF['name']);

			// Starting the document template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->docType= 'xhtml_trans';
		$this->doc->backPath = $BACK_PATH;
		$this->doc->setModuleTemplate('templates/db_new_content_el.html');
		$this->doc->JScode='';
		$this->doc->form='<form action="" name="editForm"><input type="hidden" name="defValues" value="" />';

			// Setting up the context sensitive menu:
		$this->doc->getContextMenuCode();

			// Getting the current page and receiving access information (used in main())
		$perms_clause = $BE_USER->getPagePermsClause(1);
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$perms_clause);
		$this->access = is_array($this->pageinfo) ? 1 : 0;
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
			$this->content.=$this->doc->header($LANG->getLL('newContentElement'));
			$this->content.=$this->doc->spacer(5);

				// Wizard
			$code='';
			$lines=array();
			$wizardItems = $this->getWizardItems();

				// Traverse items for the wizard.
				// An item is either a header or an item rendered with a radio button and title/description and icon:
			$cc=0;
			foreach ($wizardItems as $k => $wInfo)	{
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
					$oC = "document.editForm.defValues.value=unescape('".rawurlencode($wInfo['params'])."');goToalt_doc();".(!$onClickEvent?"window.location.hash='#sel2';":'');
					$tL[]='<input type="radio" name="tempB" value="'.htmlspecialchars($k).'" onclick="'.htmlspecialchars($this->doc->thisBlur().$oC).'" />';

						// Onclick action for icon/title:
					$aOnClick = 'document.getElementsByName(\'tempB\')['.$cc.'].checked=1;'.$this->doc->thisBlur().$oC.'return false;';

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
			$this->content.= $this->doc->section(!$onClickEvent?$LANG->getLL('1_selectType'):'',$code,0,1);



				// If the user must also select a column:
			if (!$onClickEvent)	{

					// Add anchor "sel2"
				$this->content.= $this->doc->section('','<a name="sel2"></a>');
				$this->content.= $this->doc->spacer(20);

					// Select position
				$code = $LANG->getLL('sel2',1).'<br /><br />';

					// Load SHARED page-TSconfig settings and retrieve column list from there, if applicable:
				$modTSconfig_SHARED = t3lib_BEfunc::getModTSconfig($this->id,'mod.SHARED');
				$colPosList = strcmp(trim($modTSconfig_SHARED['properties']['colPos_list']),'') ? trim($modTSconfig_SHARED['properties']['colPos_list']) : '1,0,2,3';
				$colPosList = implode(',',array_unique(t3lib_div::intExplode(',',$colPosList)));		// Removing duplicates, if any

					// Finally, add the content of the column selector to the content:
				$code.= $posMap->printContentElementColumns($this->id,0,$colPosList,1,$this->R_URI);
				$this->content.= $this->doc->section($LANG->getLL('2_selectPosition'),$code,0,1);
			}
		} else {		// In case of no access:
			$this->content = '';
			$this->content.= $this->doc->header($LANG->getLL('newContentElement'));
			$this->content.= $this->doc->spacer(5);
		}

			// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers['CSH'] = $docHeaderButtons['csh'];
		$markers['CONTENT'] = $this->content;

			// Build the <body> for the module
		$this->content = $this->doc->startPage($LANG->getLL('newContentElement'));
		$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Print out the accumulated content:
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
	private function getButtons()	{
		global $LANG, $BACK_PATH;

		$buttons = array(
			'csh' => '',
			'back' => ''
		);


		if ($this->id && $this->access)	{
				// CSH
			$buttons['csh'] = t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'new_ce', $GLOBALS['BACK_PATH']);

				// Back
			if ($this->R_URI)	{
				$buttons['back'] = '<a href="' . htmlspecialchars($this->R_URI) . '" class="typo3-goBack">' .
					'<img' . t3lib_iconWorks::skinImg($this->doc->backPath, 'gfx/goback.gif') . ' alt="" title="' . $LANG->getLL('goBack', 1) . '" />' .
					'</a>';
			}
		}


		return $buttons;
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
			'common_regularText' => array(	// Regular text element
				'icon'=>'gfx/c_wiz/regular_text.gif',
				'title'=>$LANG->getLL('common_regularText_title'),
				'description'=>$LANG->getLL('common_regularText_description'),
				'tt_content_defValues' => array(
					'CType' => 'text'
				)
			),
			'common_textImage' => array(	// Text with image
				'icon'=>'gfx/c_wiz/text_image_right.gif',
				'title'=>$LANG->getLL('common_textImage_title'),
				'description'=>$LANG->getLL('common_textImage_description'),
				'tt_content_defValues' => array(
					'CType' => 'textpic',
					'imageorient' => 17
				)
			),
			'common_imagesOnly' => array(	// Images only
				'icon'=>'gfx/c_wiz/images_only.gif',
				'title'=>$LANG->getLL('common_imagesOnly_title'),
				'description'=>$LANG->getLL('common_imagesOnly_description'),
				'tt_content_defValues' => array(
					'CType' => 'image',
					'imagecols' => 2
				)
			),
			'common_bulletList' => array(	// Bullet list
				'icon'=>'gfx/c_wiz/bullet_list.gif',
				'title'=>$LANG->getLL('common_bulletList_title'),
				'description'=>$LANG->getLL('common_bulletList_description'),
				'tt_content_defValues' => array(
					'CType' => 'bullets',
				)
			),
			'common_table' => array(	// Table
				'icon'=>'gfx/c_wiz/table.gif',
				'title'=>$LANG->getLL('common_table_title'),
				'description'=>$LANG->getLL('common_table_description'),
				'tt_content_defValues' => array(
					'CType' => 'table',
				)
			),
			'special' => array('header'=>$LANG->getLL('special')),
			'special_filelinks' => array(	// Filelinks
				'icon'=>'gfx/c_wiz/filelinks.gif',
				'title'=>$LANG->getLL('special_filelinks_title'),
				'description'=>$LANG->getLL('special_filelinks_description'),
				'tt_content_defValues' => array(
					'CType' => 'uploads',
				)
			),
			'special_multimedia' => array(	// Multimedia
				'icon'=>'gfx/c_wiz/multimedia.gif',
				'title'=>$LANG->getLL('special_multimedia_title'),
				'description'=>$LANG->getLL('special_multimedia_description'),
				'tt_content_defValues' => array(
					'CType' => 'multimedia',
				)
			),
			'special_sitemap' => array(	// Sitemap
				'icon'=>'gfx/c_wiz/sitemap2.gif',
				'title'=>$LANG->getLL('special_sitemap_title'),
				'description'=>$LANG->getLL('special_sitemap_description'),
				'tt_content_defValues' => array(
					'CType' => 'menu',
					'menu_type' => 2
				)
			),
			'special_plainHTML' => array(	// Plain HTML
				'icon'=>'gfx/c_wiz/html.gif',
				'title'=>$LANG->getLL('special_plainHTML_title'),
				'description'=>$LANG->getLL('special_plainHTML_description'),
				'tt_content_defValues' => array(
					'CType' => 'html',
				)
			),
			'forms' => array('header'=>$LANG->getLL('forms')),
			'forms_mail' => array(	// Mail form
				'icon'=>'gfx/c_wiz/mailform.gif',
				'title'=>$LANG->getLL('forms_mail_title'),
				'description'=>$LANG->getLL('forms_mail_description'),
				'tt_content_defValues' => array(
					'CType' => 'mailform',
					'bodytext' => trim('
# Example content:
Name: | *name = input,40 | Enter your name here
Email: | *email=input,40 |
Address: | address=textarea,40,5 |
Contact me: | tv=check | 1

|formtype_mail = submit | Send form!
|html_enabled=hidden | 1
|subject=hidden| This is the subject
					')
				)
			),
			'forms_search' => array(	// Search form
				'icon'=>'gfx/c_wiz/searchform.gif',
				'title'=>$LANG->getLL('forms_search_title'),
				'description'=>$LANG->getLL('forms_search_description'),
				'tt_content_defValues' => array(
					'CType' => 'search',
				)
			),
			'forms_login' => array(	// Login form
				'icon'=>'gfx/c_wiz/login_form.gif',
				'title'=>$LANG->getLL('forms_login_title'),
				'description'=>$LANG->getLL('forms_login_description'),
				'tt_content_defValues' => array(
					'CType' => 'login',
				)
			),
			'plugins' => array('header'=>$LANG->getLL('plugins')),
			'plugins_general' => array(	// General plugin
				'icon'=>'gfx/c_wiz/user_defined.gif',
				'title'=>$LANG->getLL('plugins_general_title'),
				'description'=>$LANG->getLL('plugins_general_description'),
				'tt_content_defValues' => array(
					'CType' => 'list',
				)
			),
		);


			// PLUG-INS:
		if (is_array($TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']))	{
			reset($TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']);
			while(list($class,$path)=each($TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']))	{
				$modObj = t3lib_div::makeInstance($class);
				$wizardItems = $modObj->proc($wizardItems);
			}
		}

			// Remove elements where preset values are not allowed:
		$this->removeInvalidElements($wizardItems);

		return $wizardItems;
	}

	/**
	 * Checks the array for elements which might contain unallowed default values and will unset them!
	 * Looks for the "tt_content_defValues" key in each element and if found it will traverse that array as fieldname / value pairs and check. The values will be added to the "params" key of the array (which should probably be unset or empty by default).
	 *
	 * @param	array		Wizard items, passed by reference
	 * @return	void
	 */
	function removeInvalidElements(&$wizardItems)	{
		global $TCA;

			// Load full table definition:
		t3lib_div::loadTCA('tt_content');

			// Get TCEFORM from TSconfig of current page
		$row = array('pid'=>$this->id);
		$TCEFORM_TSconfig = t3lib_BEfunc::getTCEFORM_TSconfig('tt_content',$row);
		$removeItems = t3lib_div::trimExplode(',',$TCEFORM_TSconfig['CType']['removeItems'],1);

		$headersUsed = Array();
			// Traverse wizard items:
		foreach($wizardItems as $key => $cfg)	{

				// Exploding parameter string, if any (old style)
			if ($wizardItems[$key]['params'])	{
					// Explode GET vars recursively
				$tempGetVars = t3lib_div::explodeUrl2Array($wizardItems[$key]['params'],TRUE);
					// If tt_content values are set, merge them into the tt_content_defValues array, unset them from $tempGetVars and re-implode $tempGetVars into the param string (in case remaining parameters are around).
				if (is_array($tempGetVars['defVals']['tt_content']))	{
					$wizardItems[$key]['tt_content_defValues'] = array_merge(is_array($wizardItems[$key]['tt_content_defValues']) ? $wizardItems[$key]['tt_content_defValues'] : array(), $tempGetVars['defVals']['tt_content']);
					unset($tempGetVars['defVals']['tt_content']);
					$wizardItems[$key]['params'] = t3lib_div::implodeArrayForUrl('',$tempGetVars);
				}
			}

				// If tt_content_defValues are defined...:
			if (is_array($wizardItems[$key]['tt_content_defValues']))	{

					// Traverse field values:
				foreach($wizardItems[$key]['tt_content_defValues'] as $fN => $fV)	{
					if (is_array($TCA['tt_content']['columns'][$fN]))	{
							// Get information about if the field value is OK:
						$config = &$TCA['tt_content']['columns'][$fN]['config'];
						$authModeDeny = $config['type']=='select' && $config['authMode'] && !$GLOBALS['BE_USER']->checkAuthMode('tt_content',$fN,$fV,$config['authMode']);

						if ($authModeDeny || ($fN=='CType' && in_array($fV,$removeItems)))	{
								// Remove element all together:
							unset($wizardItems[$key]);
							break;
						} else {
								// Add the parameter:
							$wizardItems[$key]['params'].= '&defVals[tt_content]['.$fN.']='.rawurlencode($fV);
							$tmp = explode('_', $key);
							$headersUsed[$tmp[0]] = $tmp[0];
						}
					}
				}
			}
		}
			// remove headers without elements
		foreach ($wizardItems as $key => $cfg)	{
			$tmp = explode('_',$key);
			if ($tmp[0] && !$tmp[1] && !in_array($tmp[0], $headersUsed))	{
				unset($wizardItems[$key]);
			}
		}
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
