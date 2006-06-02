<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2006 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Class for TYPO3 backend user authentication in the TSFE frontend
 *
 * $Id$
 * Revised for TYPO3 3.6 July/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  103: class t3lib_tsfeBeUserAuth extends t3lib_beUserAuth
 *  129:     function extInitFeAdmin()
 *  154:     function extPrintFeAdminDialog()
 *
 *              SECTION: Creating sections of the Admin Panel
 *  250:     function extGetCategory_preview($out='')
 *  283:     function extGetCategory_cache($out='')
 *  321:     function extGetCategory_publish($out='')
 *  356:     function extGetCategory_edit($out='')
 *  400:     function extGetCategory_tsdebug($out='')
 *  433:     function extGetCategory_info($out='')
 *
 *              SECTION: Admin Panel Layout Helper functions
 *  506:     function extGetHead($pre)
 *  526:     function extItemLink($pre,$str)
 *  542:     function extGetItem($pre,$element)
 *  559:     function extFw($str)
 *  568:     function ext_makeToolBar()
 *
 *              SECTION: TSFE BE user Access Functions
 *  637:     function checkBackendAccessSettingsFromInitPhp()
 *  682:     function extPageReadAccess($pageRec)
 *  693:     function extAdmModuleEnabled($key)
 *  709:     function extSaveFeAdminConfig()
 *  741:     function extGetFeAdminValue($pre,$val='')
 *  783:     function extIsAdmMenuOpen($pre)
 *
 *              SECTION: TSFE BE user Access Functions
 *  818:     function extGetTreeList($id,$depth,$begin=0,$perms_clause)
 *  849:     function extGetNumberOfCachedPages($page_id)
 *
 *              SECTION: Localization handling
 *  888:     function extGetLL($key)
 *
 *              SECTION: Frontend Editing
 *  932:     function extIsEditAction()
 *  954:     function extIsFormShown()
 *  970:     function extEditAction()
 *
 * TOTAL FUNCTIONS: 25
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */











/**
 * TYPO3 backend user authentication in the TSFE frontend.
 * This includes mainly functions related to the Admin Panel
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_tsfeBeUserAuth extends t3lib_beUserAuth {
	var $formfield_uname = ''; 			// formfield with login-name
	var $formfield_uident = ''; 		// formfield with password
	var $formfield_chalvalue = '';		// formfield with a unique value which is used to encrypt the password and username
	var $security_level = '';				// sets the level of security. *'normal' = clear-text. 'challenged' = hashed password/username from form in $formfield_uident. 'superchallenged' = hashed password hashed again with username.
	var $writeStdLog = 0;					// Decides if the writelog() function is called at login and logout
	var $writeAttemptLog = 0;				// If the writelog() functions is called if a login-attempt has be tried without success
	var $auth_include = '';						// this is the name of the include-file containing the login form. If not set, login CAN be anonymous. If set login IS needed.

	var $extNeedUpdate=0;
	var $extPublishList='';
	var $extPageInTreeInfo=array();
	var $ext_forcePreview=0;
	var $langSplitIndex=0;
	var $extAdmEnabled = 0;	// General flag which is set if the adminpanel should be displayed at all..




	/**
	 * Initialize the usage of Admin Panel.
	 * Called from index_ts.php if a backend users is correctly logged in.
	 * Sets $this->extAdminConfig to the "admPanel" config for the user and $this->extAdmEnabled = 1 IF access is enabled.
	 *
	 * @return	void
	 */
	function extInitFeAdmin()	{
		$this->extAdminConfig = $this->getTSConfigProp('admPanel');
		if (is_array($this->extAdminConfig['enable.']))	{
			reset($this->extAdminConfig['enable.']);
			while(list($k,$v)=each($this->extAdminConfig['enable.']))	{
				if ($v)	{
					$this->extAdmEnabled=1;	// Enable panel
					break;
				}
			}
		}

			// Init TSFE_EDIT variables if either the admPanel is enabled or if forceDisplayIcons is set
		if($this->extAdmEnabled || $this->extGetFeAdminValue('edit', 'displayIcons'))	{
			$this->TSFE_EDIT = t3lib_div::_POST('TSFE_EDIT');
		}
	}

	/**
	 * Creates and returns the HTML code for the Admin Panel in the TSFE frontend.
	 * Called from index_ts.php - in the end of the script
	 *
	 * @return	string		HTML for the Admin Panel
	 * @see index_ts.php
	 */
	function extPrintFeAdminDialog()	{
		$out='';
		if ($this->uc['TSFE_adminConfig']['display_top'])	{
			if ($this->extAdmModuleEnabled('preview'))	$out.= $this->extGetCategory_preview();
			if ($this->extAdmModuleEnabled('cache'))	$out.= $this->extGetCategory_cache();
			if ($this->extAdmModuleEnabled('publish'))	$out.= $this->extGetCategory_publish();
			if ($this->extAdmModuleEnabled('edit'))		$out.= $this->extGetCategory_edit();
			if ($this->extAdmModuleEnabled('tsdebug'))	$out.= $this->extGetCategory_tsdebug();
			if ($this->extAdmModuleEnabled('info'))		$out.= $this->extGetCategory_info();
		}

		$header='
			<tr class="typo3-adminPanel-hRow" style="background-color:#9ba1a8;">
				<td colspan="4" nowrap="nowrap">'.
					$this->extItemLink('top','<img src="'.TYPO3_mainDir.'gfx/ol/'.($this->uc['TSFE_adminConfig']['display_top']?'minus':'plus').'bullet.gif" width="18" height="16" align="absmiddle" border="0" alt="" /><strong>'.$this->extFw($this->extGetLL('adminOptions')).'</strong>').
					$this->extFw(': '.$this->user['username']).'
					<img src="clear.gif" width="40" height="1" alt="" />
					<input type="hidden" name="TSFE_ADMIN_PANEL[display_top]" value="'.$this->uc['TSFE_adminConfig']['display_top'].'" />'.($this->extNeedUpdate?'<input type="submit" value="'.$this->extGetLL('update').'" />':'').'</td>
			</tr>';

		$query = !t3lib_div::_GET('id') ? ('<input type="hidden" name="id" value="'.$GLOBALS['TSFE']->id.'" />'.chr(10)) : '';
			// the dummy field is needed for Firefix: to force a page reload on submit with must change the form value with Javascript (see "onsubmit" attribute of the "form" element")
		$query .= '<input type="hidden" name="TSFE_ADMIN_PANEL[DUMMY]" value="">';
		foreach(t3lib_div::_GET() as $k => $v)	{
			if ($k != 'TSFE_ADMIN_PANEL')	{
				if (is_array($v))	{
					$query.=$this->extPrintFeAdminDialogHiddenFields($k,$v);
				} else {
					$query.='<input type="hidden" name="'.$k.'" value="'.htmlspecialchars($v).'">'.chr(10);
				}
			}
		}

		$out='
<!--
	ADMIN PANEL
-->
<a name="TSFE_ADMIN"></a>
<form name="TSFE_ADMIN_PANEL_FORM" action="'.htmlspecialchars(t3lib_div::getIndpEnv('SCRIPT_NAME')).'#TSFE_ADMIN" method="get" style="margin:0;" onsubmit="document.forms.TSFE_ADMIN_PANEL_FORM[\'TSFE_ADMIN_PANEL[DUMMY]\'].value=Math.random().toString().substring(2,8)">'.
$query.
'	<table border="0" cellpadding="0" cellspacing="0" class="typo3-adminPanel" style="background-color:#f6f2e6; border: 1px solid black; z-index:0; position:absolute;">'.$header.$out.'
	</table>
</form>';

		if ($this->uc['TSFE_adminConfig']['display_top'])	{
			$out.='<script type="text/javascript" src="t3lib/jsfunc.evalfield.js"></script>';
			$out.='
			<script type="text/javascript">
					/*<![CDATA[*/
				var evalFunc = new evalFunc();
					// TSFEtypo3FormFieldSet()
				function TSFEtypo3FormFieldSet(theField, evallist, is_in, checkbox, checkboxValue)	{	//
					var theFObj = new evalFunc_dummy (evallist,is_in, checkbox, checkboxValue);
					var theValue = document.TSFE_ADMIN_PANEL_FORM[theField].value;
					if (checkbox && theValue==checkboxValue)	{
						document.TSFE_ADMIN_PANEL_FORM[theField+"_hr"].value="";
						document.TSFE_ADMIN_PANEL_FORM[theField+"_cb"].checked = "";
					} else {
						document.TSFE_ADMIN_PANEL_FORM[theField+"_hr"].value = evalFunc.outputObjValue(theFObj, theValue);
						document.TSFE_ADMIN_PANEL_FORM[theField+"_cb"].checked = "on";
					}
				}
					// TSFEtypo3FormFieldGet()
				function TSFEtypo3FormFieldGet(theField, evallist, is_in, checkbox, checkboxValue, checkbox_off)	{	//
					var theFObj = new evalFunc_dummy (evallist,is_in, checkbox, checkboxValue);
					if (checkbox_off)	{
						document.TSFE_ADMIN_PANEL_FORM[theField].value=checkboxValue;
					}else{
						document.TSFE_ADMIN_PANEL_FORM[theField].value = evalFunc.evalObjValue(theFObj, document.TSFE_ADMIN_PANEL_FORM[theField+"_hr"].value);
					}
					TSFEtypo3FormFieldSet(theField, evallist, is_in, checkbox, checkboxValue);
				}
					/*]]>*/
			</script>
			<script language="javascript" type="text/javascript">'.$this->extJSCODE.'</script>';
		}
		return "\n\n\n\n".$out.'<br />';
	}

	/**
	 * Fetches recursively all GET parameters as hidden fields.
	 * Called from extPrintFeAdminDialog.
	 *
	 * @param	string		current key
	 * @param	mixed		current value
	 * @return	string		hidden fields
	 * @see extPrintFeAdminDialog()
	 */
	function extPrintFeAdminDialogHiddenFields($key,&$val)	{
		$out='';
		foreach($val as $k => $v)	{
			if (is_array($v))	{
				$out.=$this->extPrintFeAdminDialogHiddenFields($key.'['.$k.']',$v);
			} else {
				$out.='<input type="hidden" name="'.$key.'['.$k.']" value="'.htmlspecialchars($v).'">'.chr(10);
			}
		}
		return $out;
	}
















	/*****************************************************
	 *
	 * Creating sections of the Admin Panel
	 *
	 ****************************************************/

	/**
	 * Creates the content for the "preview" section ("module") of the Admin Panel
	 *
	 * @param	string		Optional start-value; The generated content is added to this variable.
	 * @return	string		HTML content for the section. Consists of a string with table-rows with four columns.
	 * @see extPrintFeAdminDialog()
	 */
	function extGetCategory_preview($out='')	{
		$out.=$this->extGetHead('preview');
		if ($this->uc['TSFE_adminConfig']['display_preview'])	{
			$this->extNeedUpdate = 1;
			$out.= $this->extGetItem('preview_showHiddenPages', '<input type="hidden" name="TSFE_ADMIN_PANEL[preview_showHiddenPages]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[preview_showHiddenPages]" value="1"'.($this->uc['TSFE_adminConfig']['preview_showHiddenPages']?' checked="checked"':'').' />');
			$out.= $this->extGetItem('preview_showHiddenRecords', '<input type="hidden" name="TSFE_ADMIN_PANEL[preview_showHiddenRecords]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[preview_showHiddenRecords]" value="1"'.($this->uc['TSFE_adminConfig']['preview_showHiddenRecords']?' checked="checked"':'').' />');

				// Simulate date
			$out.= $this->extGetItem('preview_simulateDate', '<input type="checkbox" name="TSFE_ADMIN_PANEL[preview_simulateDate]_cb" onclick="TSFEtypo3FormFieldGet(\'TSFE_ADMIN_PANEL[preview_simulateDate]\', \'datetime\', \'\',1,0,1);" /><input type="text" name="TSFE_ADMIN_PANEL[preview_simulateDate]_hr" onchange="TSFEtypo3FormFieldGet(\'TSFE_ADMIN_PANEL[preview_simulateDate]\', \'datetime\', \'\', 1,0);" /><input type="hidden" name="TSFE_ADMIN_PANEL[preview_simulateDate]" value="'.$this->uc['TSFE_adminConfig']['preview_simulateDate'].'" />');
			$this->extJSCODE.= 'TSFEtypo3FormFieldSet("TSFE_ADMIN_PANEL[preview_simulateDate]", "datetime", "", 1,0);';

				// Simulate fe_user:
			$options = '<option value="0"></option>';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'fe_groups.uid, fe_groups.title',
						'fe_groups,pages',
						'pages.uid=fe_groups.pid AND pages.deleted=0 '.t3lib_BEfunc::deleteClause('fe_groups').' AND '.$this->getPagePermsClause(1)
					);
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				$options.= '<option value="'.$row['uid'].'"'.($this->uc['TSFE_adminConfig']['preview_simulateUserGroup']==$row['uid']?' selected="selected"':'').'>'.htmlspecialchars('['.$row['uid'].'] '.$row['title']).'</option>';
			}
			$out.= $this->extGetItem('preview_simulateUserGroup', '<select name="TSFE_ADMIN_PANEL[preview_simulateUserGroup]">'.$options.'</select>');
		}
		return $out;
	}

	/**
	 * Creates the content for the "cache" section ("module") of the Admin Panel
	 *
	 * @param	string		Optional start-value; The generated content is added to this variable.
	 * @return	string		HTML content for the section. Consists of a string with table-rows with four columns.
	 * @see extPrintFeAdminDialog()
	 */
	function extGetCategory_cache($out='')	{
		$out.=$this->extGetHead('cache');
		if ($this->uc['TSFE_adminConfig']['display_cache'])	{
			$this->extNeedUpdate=1;
			$out.=$this->extGetItem('cache_noCache', '<input type="hidden" name="TSFE_ADMIN_PANEL[cache_noCache]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[cache_noCache]" value="1"'.($this->uc['TSFE_adminConfig']['cache_noCache']?' checked="checked"':'').' />');

			$options='';
			$options.='<option value="0"'.($this->uc['TSFE_adminConfig']['cache_clearCacheLevels']==0?' selected="selected"':'').'>'.$this->extGetLL('div_Levels_0').'</option>';
			$options.='<option value="1"'.($this->uc['TSFE_adminConfig']['cache_clearCacheLevels']==1?' selected="selected"':'').'>'.$this->extGetLL('div_Levels_1').'</option>';
			$options.='<option value="2"'.($this->uc['TSFE_adminConfig']['cache_clearCacheLevels']==2?' selected="selected"':'').'>'.$this->extGetLL('div_Levels_2').'</option>';
			$out.=$this->extGetItem('cache_clearLevels', '<select name="TSFE_ADMIN_PANEL[cache_clearCacheLevels]">'.$options.'</select>'.
					'<input type="hidden" name="TSFE_ADMIN_PANEL[cache_clearCacheId]" value="'.$GLOBALS['TSFE']->id.'" /><input type="submit" value="'.$this->extGetLL('update').'" />');

				// Generating tree:
			$depth=$this->extGetFeAdminValue('cache','clearCacheLevels');
			$outTable='';
			$this->extPageInTreeInfo=array();
			$this->extPageInTreeInfo[]=array($GLOBALS['TSFE']->page['uid'],$GLOBALS['TSFE']->page['title'],$depth+1);
			$this->extGetTreeList($GLOBALS['TSFE']->id, $depth,0,$this->getPagePermsClause(1));
			reset($this->extPageInTreeInfo);
			while(list(,$row)=each($this->extPageInTreeInfo))	{
				$outTable.='<tr><td nowrap="nowrap"><img src="clear.gif" width="'.(($depth+1-$row[2])*18).'" height="1" alt="" /><img src="'.TYPO3_mainDir.'gfx/i/pages.gif" width="18" height="16" align="absmiddle" border="0" alt="" />'.$this->extFw($row[1]).'</td><td><img src="clear.gif" width="10" height="1" alt="" /></td><td>'.$this->extFw($this->extGetNumberOfCachedPages($row[0])).'</td></tr>';
			}
			$outTable='<br /><table border="0" cellpadding="0" cellspacing="0">'.$outTable.'</table>';
			$outTable.='<input type="submit" name="TSFE_ADMIN_PANEL[action][clearCache]" value="'.$this->extGetLL('cache_doit').'" />';
			$out.=$this->extGetItem('cache_cacheEntries', $outTable);

		}
		return $out;
	}

	/**
	 * Creates the content for the "publish" section ("module") of the Admin Panel
	 *
	 * @param	string		Optional start-value; The generated content is added to this variable.
	 * @return	string		HTML content for the section. Consists of a string with table-rows with four columns.
	 * @see extPrintFeAdminDialog()
	 */
	function extGetCategory_publish($out='')	{
		$out.=$this->extGetHead('publish');
		if ($this->uc['TSFE_adminConfig']['display_publish'])	{
			$this->extNeedUpdate=1;
			$options='';
			$options.='<option value="0"'.($this->uc['TSFE_adminConfig']['publish_levels']==0?' selected="selected"':'').'>'.$this->extGetLL('div_Levels_0').'</option>';
			$options.='<option value="1"'.($this->uc['TSFE_adminConfig']['publish_levels']==1?' selected="selected"':'').'>'.$this->extGetLL('div_Levels_1').'</option>';
			$options.='<option value="2"'.($this->uc['TSFE_adminConfig']['publish_levels']==2?' selected="selected"':'').'>'.$this->extGetLL('div_Levels_2').'</option>';
			$out.=$this->extGetItem('publish_levels', '<select name="TSFE_ADMIN_PANEL[publish_levels]">'.$options.'</select>'.
					'<input type="hidden" name="TSFE_ADMIN_PANEL[publish_id]" value="'.$GLOBALS['TSFE']->id.'" /><input type="submit" value="'.$this->extGetLL('update').'" />');

				// Generating tree:
			$depth=$this->extGetFeAdminValue('publish','levels');
			$outTable='';
			$this->extPageInTreeInfo=array();
			$this->extPageInTreeInfo[]=array($GLOBALS['TSFE']->page['uid'],$GLOBALS['TSFE']->page['title'],$depth+1);
			$this->extGetTreeList($GLOBALS['TSFE']->id, $depth,0,$this->getPagePermsClause(1));
			reset($this->extPageInTreeInfo);
			while(list(,$row)=each($this->extPageInTreeInfo))	{
				$outTable.='<tr><td nowrap="nowrap"><img src="clear.gif" width="'.(($depth+1-$row[2])*18).'" height="1" alt="" /><img src="'.TYPO3_mainDir.'gfx/i/pages.gif" width="18" height="16" align="absmiddle" border="0" alt="" />'.$this->extFw($row[1]).'</td><td><img src="clear.gif" width="10" height="1" alt="" /></td><td>'.$this->extFw('...').'</td></tr>';
			}
			$outTable='<br /><table border="0" cellpadding="0" cellspacing="0">'.$outTable.'</table>';
			$outTable.='<input type="submit" name="TSFE_ADMIN_PANEL[action][publish]" value="'.$this->extGetLL('publish_doit').'" />';
			$out.=$this->extGetItem('publish_tree', $outTable);
		}
		return $out;
	}

	/**
	 * Creates the content for the "edit" section ("module") of the Admin Panel
	 *
	 * @param	string		Optional start-value; The generated content is added to this variable.
	 * @return	string		HTML content for the section. Consists of a string with table-rows with four columns.
	 * @see extPrintFeAdminDialog()
	 */
	function extGetCategory_edit($out='')	{
		$out.=$this->extGetHead('edit');
		if ($this->uc['TSFE_adminConfig']['display_edit'])	{

				// If another page module was specified, replace the default Page module with the new one
			$newPageModule = trim($GLOBALS['BE_USER']->getTSConfigVal('options.overridePageModule'));
			$pageModule = t3lib_BEfunc::isModuleSetInTBE_MODULES($newPageModule) ? $newPageModule : 'web_layout';

			$this->extNeedUpdate=1;
			$out.=$this->extGetItem('edit_displayFieldIcons', '<input type="hidden" name="TSFE_ADMIN_PANEL[edit_displayFieldIcons]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[edit_displayFieldIcons]" value="1"'.($this->uc['TSFE_adminConfig']['edit_displayFieldIcons']?' checked="checked"':'').' />');
			$out.=$this->extGetItem('edit_displayIcons', '<input type="hidden" name="TSFE_ADMIN_PANEL[edit_displayIcons]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[edit_displayIcons]" value="1"'.($this->uc['TSFE_adminConfig']['edit_displayIcons']?' checked="checked"':'').' />');
			$out.=$this->extGetItem('edit_editFormsOnPage', '<input type="hidden" name="TSFE_ADMIN_PANEL[edit_editFormsOnPage]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[edit_editFormsOnPage]" value="1"'.($this->uc['TSFE_adminConfig']['edit_editFormsOnPage']?' checked="checked"':'').' />');
			$out.=$this->extGetItem('edit_editNoPopup', '<input type="hidden" name="TSFE_ADMIN_PANEL[edit_editNoPopup]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[edit_editNoPopup]" value="1"'.($this->uc['TSFE_adminConfig']['edit_editNoPopup']?' checked="checked"':'').' />');

			$out.=$this->extGetItem('', $this->ext_makeToolBar());
			if (!t3lib_div::_GP('ADMCMD_view'))	{
				$out.=$this->extGetItem('', '<a href="#" onclick="'.
					htmlspecialchars('
						if (parent.opener && parent.opener.top && parent.opener.top.TS)	{
							parent.opener.top.fsMod.recentIds["web"]='.intval($GLOBALS['TSFE']->page['uid']).';
							if (parent.opener.top.content && parent.opener.top.content.nav_frame && parent.opener.top.content.nav_frame.refresh_nav)	{
								parent.opener.top.content.nav_frame.refresh_nav();
							}
							parent.opener.top.goToModule("'.$pageModule.'");
							parent.opener.top.focus();
						} else {
							vHWin=window.open(\''.TYPO3_mainDir.'alt_main.php\',\''.md5('Typo3Backend-'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']).'\',\'status=1,menubar=1,scrollbars=1,resizable=1\');
							vHWin.focus();
						}
						return false;
						').
					'">'.$this->extFw($this->extGetLL('edit_openAB')).'</a>');
			}
		}
		return $out;
	}

	/**
	 * Creates the content for the "tsdebug" section ("module") of the Admin Panel
	 *
	 * @param	string		Optional start-value; The generated content is added to this variable.
	 * @return	string		HTML content for the section. Consists of a string with table-rows with four columns.
	 * @see extPrintFeAdminDialog()
	 */
	function extGetCategory_tsdebug($out='')	{
		$out.=$this->extGetHead('tsdebug');
		if ($this->uc['TSFE_adminConfig']['display_tsdebug'])	{
			$this->extNeedUpdate=1;

			$content='';
			$content.=$this->extGetItem('tsdebug_tree', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_tree]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_tree]" value="1"'.($this->uc['TSFE_adminConfig']['tsdebug_tree']?' checked="checked"':'').' />');
			$content.=$this->extGetItem('tsdebug_displayTimes', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayTimes]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_displayTimes]" value="1"'.($this->uc['TSFE_adminConfig']['tsdebug_displayTimes']?' checked="checked"':'').' />');
			$content.=$this->extGetItem('tsdebug_displayMessages', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayMessages]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_displayMessages]" value="1"'.($this->uc['TSFE_adminConfig']['tsdebug_displayMessages']?' checked="checked"':'').' />');
			$content.=$this->extGetItem('tsdebug_LR', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_LR]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_LR]" value="1"'.($this->uc['TSFE_adminConfig']['tsdebug_LR']?' checked="checked"':'').' />');
			$content.=$this->extGetItem('tsdebug_displayContent', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayContent]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_displayContent]" value="1"'.($this->uc['TSFE_adminConfig']['tsdebug_displayContent']?' checked="checked"':'').' />');
			$content.=$this->extGetItem('tsdebug_displayQueries', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_displayQueries]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_displayQueries]" value="1"'.($this->uc['TSFE_adminConfig']['tsdebug_displayQueries']?' checked="checked"':'').' />');
			$content.=$this->extGetItem('tsdebug_forceTemplateParsing', '<input type="hidden" name="TSFE_ADMIN_PANEL[tsdebug_forceTemplateParsing]" value="0" /><input type="checkbox" name="TSFE_ADMIN_PANEL[tsdebug_forceTemplateParsing]" value="1"'.($this->uc['TSFE_adminConfig']['tsdebug_forceTemplateParsing']?' checked="checked"':'').' />');

			$out.='<tr><td colspan="4" nowrap="nowrap"><table border="0" cellpadding="0" cellspacing="0">'.$content.'</table></td></tr>';

			$GLOBALS['TT']->printConf['flag_tree'] = $this->extGetFeAdminValue('tsdebug','tree');
			$GLOBALS['TT']->printConf['allTime'] = $this->extGetFeAdminValue('tsdebug','displayTimes');
			$GLOBALS['TT']->printConf['flag_messages'] = $this->extGetFeAdminValue('tsdebug','displayMessages');
			$GLOBALS['TT']->printConf['flag_content'] = $this->extGetFeAdminValue('tsdebug','displayContent');
			$GLOBALS['TT']->printConf['flag_queries'] = $this->extGetFeAdminValue('tsdebug','displayQueries');
			$out.='<tr><td><img src="clear.gif" width="50" height="1" alt="" /></td><td colspan="3">'.$GLOBALS['TT']->printTSlog().'</td></tr>';
		}
		return $out;
	}

	/**
	 * Creates the content for the "info" section ("module") of the Admin Panel
	 *
	 * @param	string		Optional start-value; The generated content is added to this variable.
	 * @return	string		HTML content for the section. Consists of a string with table-rows with four columns.
	 * @see extPrintFeAdminDialog()
	 */
	function extGetCategory_info($out='')	{
		$out.=$this->extGetHead('info');
		if ($this->uc['TSFE_adminConfig']['display_info'])	{

			if (is_array($GLOBALS['TSFE']->imagesOnPage) && $this->extGetFeAdminValue('cache','noCache'))	{
				reset($GLOBALS['TSFE']->imagesOnPage);
				$theBytes=0;
				$count=0;
				$fileTable='';
				while(list(,$file)=each($GLOBALS['TSFE']->imagesOnPage))	{
					$fs=@filesize($file);
					$fileTable.='<tr><td>'.$this->extFw($file).'</td><td align="right">'.$this->extFw(t3lib_div::formatSize($fs)).'</td></tr>';
					$theBytes+=$fs;
					$count++;
				}
				$fileTable.='<tr><td><strong>'.$this->extFw('Total number of images:').'</strong></td><td>'.$this->extFw($count).'</td></tr>';
				$fileTable.='<tr><td><strong>'.$this->extFw('Total image file sizes:').'</strong></td><td align="right">'.$this->extFw(t3lib_div::formatSize($theBytes)).'</td></tr>';
				$fileTable.='<tr><td><strong>'.$this->extFw('Document size:').'</strong></td><td align="right">'.$this->extFw(t3lib_div::formatSize(strlen($GLOBALS['TSFE']->content))).'</td></tr>';
				$fileTable.='<tr><td><strong>'.$this->extFw('Total page load:').'</strong></td><td align="right">'.$this->extFw(t3lib_div::formatSize(strlen($GLOBALS['TSFE']->content)+$theBytes)).'</td></tr>';
				$fileTable.='<tr><td>&nbsp;</td></tr>';
			}

			$fileTable.='<tr><td>'.$this->extFw('id:').'</td><td>'.$this->extFw($GLOBALS['TSFE']->id).'</td></tr>';
			$fileTable.='<tr><td>'.$this->extFw('type:').'</td><td>'.$this->extFw($GLOBALS['TSFE']->type).'</td></tr>';
			$fileTable.='<tr><td>'.$this->extFw('gr_list:').'</td><td>'.$this->extFw($GLOBALS['TSFE']->gr_list).'</td></tr>';
			$fileTable.='<tr><td>'.$this->extFw('no_cache:').'</td><td>'.$this->extFw($GLOBALS['TSFE']->no_cache).'</td></tr>';
			$fileTable.='<tr><td>'.$this->extFw('fe_user, name:').'</td><td>'.$this->extFw($GLOBALS['TSFE']->fe_user->user['username']).'</td></tr>';
			$fileTable.='<tr><td>'.$this->extFw('fe_user, uid:').'</td><td>'.$this->extFw($GLOBALS['TSFE']->fe_user->user['uid']).'</td></tr>';
			$fileTable.='<tr><td>&nbsp;</td></tr>';

				// parsetime:
			$fileTable.='<tr><td>'.$this->extFw('Total parsetime:').'</td><td>'.$this->extFw($GLOBALS['TSFE']->scriptParseTime.' ms').'</td></tr>';

			$fileTable='<table border="0" cellpadding="0" cellspacing="0">'.$fileTable.'</table>';

			$out.='<tr><td><img src="clear.gif" width="50" height="1" alt="" /></td><td colspan="3">'.$fileTable.'</td></tr>';
		}
		return $out;
	}


















	/*****************************************************
	 *
	 * Admin Panel Layout Helper functions
	 *
	 ****************************************************/

	/**
	 * Returns a row (with colspan=4) which is a header for a section in the Admin Panel.
	 * It will have a plus/minus icon and a label which is linked so that it submits the form which surrounds the whole Admin Panel when clicked, alterting the TSFE_ADMIN_PANEL[display_'.$pre.'] value
	 * See the functions extGetCategory_*
	 *
	 * @param	string		The suffix to the display_ label. Also selects the label from the LOCAL_LANG array.
	 * @return	string		HTML table row.
	 * @access private
	 * @see extGetItem()
	 */
	function extGetHead($pre)	{
		$out.='<img src="'.TYPO3_mainDir.'gfx/ol/blank.gif" width="18" height="16" align="absmiddle" border="0" alt="" />';
		$out.='<img src="'.TYPO3_mainDir.'gfx/ol/'.($this->uc['TSFE_adminConfig']['display_'.$pre]?'minus':'plus').'bullet.gif" width="18" height="16" align="absmiddle" border="0" alt="" />';
		$out.=$this->extFw($this->extGetLL($pre));
		$out=$this->extItemLink($pre,$out);
		return '
				<tr class="typo3-adminPanel-itemHRow" style="background-color:#abbbb4;">
					<td colspan="4" nowrap="nowrap" style="border-top:dashed 1px #007a8c;">'.$out.'<input type="hidden" name="TSFE_ADMIN_PANEL[display_'.$pre.']" value="'.$this->uc['TSFE_adminConfig']['display_'.$pre].'" /></td>
				</tr>';
	}

	/**
	 * Wraps a string in a link which will open/close a certain part of the Admin Panel
	 *
	 * @param	string		The code for the display_ label/key
	 * @param	string		Input string
	 * @return	string		Linked input string
	 * @access private
	 * @see extGetHead()
	 */
	function extItemLink($pre,$str)	{
		return '<a href="#" style="text-decoration:none;" onclick="'.
			htmlspecialchars('document.TSFE_ADMIN_PANEL_FORM[\'TSFE_ADMIN_PANEL[display_'.$pre.']\'].value='.($this->uc['TSFE_adminConfig']['display_'.$pre]?'0':'1').'; document.TSFE_ADMIN_PANEL_FORM.submit(); return false;').
			'">'.$str.'</a>';
	}

	/**
	 * Returns a row (with 4 columns) for content in a section of the Admin Panel.
	 * It will take $pre as a key to a label to display and $element as the content to put into the forth cell.
	 *
	 * @param	string		Key to label
	 * @param	string		The HTML content for the forth table cell.
	 * @return	string		HTML table row.
	 * @access private
	 * @see extGetHead()
	 */
	function extGetItem($pre,$element)	{
		return '
					<tr class="typo3-adminPanel-itemRow">
						<td><img src="clear.gif" width="50" height="1" alt="" /></td>
						<td nowrap="nowrap">'.($pre ? $this->extFw($this->extGetLL($pre)) : '&nbsp;').'</td>
						<td><img src="clear.gif" width="40" height="1" alt="" /></td>
						<td>'.$element.'</td>
					</tr>';

	}

	/**
	 * Wraps a string in a font-tag with verdana, size 1 and black
	 *
	 * @param	string		The string to wrap
	 * @return	string
	 */
	function extFw($str)	{
		return '<font face="verdana,arial" size="1" color="black">'.$str.'</font>';
	}

	/**
	 * Creates the tool bar links for the "edit" section of the Admin Panel.
	 *
	 * @return	string		A string containing images wrapped in <a>-tags linking them to proper functions.
	 */
	function ext_makeToolBar()	{
			//  If mod.web_list.newContentWiz.overrideWithExtension is set, use that extension's create new content wizard instead:
		$tmpTSc = t3lib_BEfunc::getModTSconfig($this->pageinfo['uid'],'mod.web_list');
		$tmpTSc = $tmpTSc ['properties']['newContentWiz.']['overrideWithExtension'];
		$newContentWizScriptPath = t3lib_extMgm::isLoaded($tmpTSc) ? (t3lib_extMgm::extRelPath($tmpTSc).'mod1/db_new_content_el.php') : (TYPO3_mainDir.'sysext/cms/layout/db_new_content_el.php');


		$perms = $GLOBALS['BE_USER']->calcPerms($GLOBALS['TSFE']->page);
		$langAllowed = $GLOBALS['BE_USER']->checkLanguageAccess($GLOBALS['TSFE']->sys_language_uid);

		$toolBar='';
		$id = $GLOBALS['TSFE']->id;
		$toolBar.='<a href="'.htmlspecialchars(TYPO3_mainDir.'show_rechis.php?element='.rawurlencode('pages:'.$id).'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))).'#latest">'.
					'<img src="'.TYPO3_mainDir.'gfx/history2.gif" width="13" height="12" hspace="2" border="0" align="top" title="'.$this->extGetLL('edit_recordHistory').'" alt="" /></a>';

		if ($perms&16 && $langAllowed)	{
			$params = '';
			if ($GLOBALS['TSFE']->sys_language_uid)	$params = '&sys_language_uid='.$GLOBALS['TSFE']->sys_language_uid;
			$toolBar.='<a href="'.htmlspecialchars($newContentWizScriptPath.'?id='.$id.$params.'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))).'">'.
						'<img src="'.TYPO3_mainDir.'gfx/new_record.gif" width="16" height="12" hspace="1" border="0" align="top" title="'.$this->extGetLL('edit_newContentElement').'" alt="" /></a>';
		}
		if ($perms&2)	{
			$toolBar.='<a href="'.htmlspecialchars(TYPO3_mainDir.'move_el.php?table=pages&uid='.$id.'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))).'">'.
					'<img src="'.TYPO3_mainDir.'gfx/move_page.gif" width="11" height="12" hspace="2" border="0" align="top" title="'.$this->extGetLL('edit_move_page').'" alt="" /></a>';
		}
		if ($perms&8)	{
			$toolBar.='<a href="'.htmlspecialchars(TYPO3_mainDir.'db_new.php?id='.$id.'&pagesOnly=1&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))).'">'.
					'<img src="'.TYPO3_mainDir.'gfx/new_page.gif" width="13" height="12" hspace="0" border="0" align="top" title="'.$this->extGetLL('edit_newPage').'" alt="" /></a>';
		}
		if ($perms&2)	{
			$params='&edit[pages]['.$id.']=edit';
			$toolBar.='<a href="'.htmlspecialchars(TYPO3_mainDir.'alt_doc.php?'.$params.'&noView=1&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))).'">'.
					'<img src="'.TYPO3_mainDir.'gfx/edit2.gif" width="11" height="12" hspace="2" border="0" align="top" title="'.$this->extGetLL('edit_editPageProperties').'" alt="" /></a>';
		}
		if ($this->check('modules','web_list'))	{
			$toolBar.='<a href="'.htmlspecialchars(TYPO3_mainDir.'db_list.php?id='.$id.'&returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))).'">'.
					'<img src="'.TYPO3_mainDir.'gfx/list.gif" width="11" height="11" hspace="2" border="0" align="top" title="'.$this->extGetLL('edit_db_list').'" alt="" /></a>';
		}
		return $toolBar;
	}



















	/*****************************************************
	 *
	 * TSFE BE user Access Functions
	 *
	 ****************************************************/

	/**
	 * Implementing the access checks that the typo3/init.php script does before a user is ever logged in.
	 * Used in the frontend.
	 *
	 * @return	boolean		Returns true if access is OK
	 * @see typo3/init.php, t3lib_beuserauth::backendCheckLogin()
	 */
	function checkBackendAccessSettingsFromInitPhp()	{
		global $TYPO3_CONF_VARS;

		// **********************
		// Check Hardcoded lock on BE:
		// **********************
		if ($TYPO3_CONF_VARS['BE']['adminOnly'] < 0)	{
			return FALSE;
		}

		// **********************
		// Check IP
		// **********************
		if (trim($TYPO3_CONF_VARS['BE']['IPmaskList']))	{
			if (!t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $TYPO3_CONF_VARS['BE']['IPmaskList']))	{
				return FALSE;
			}
		}


		// **********************
		// Check SSL (https)
		// **********************
		if (intval($TYPO3_CONF_VARS['BE']['lockSSL']) && $TYPO3_CONF_VARS['BE']['lockSSL'] != 3)	{
			if (!t3lib_div::getIndpEnv('TYPO3_SSL'))	{
				return FALSE;
			}
		}

			// Finally a check from t3lib_beuserauth::backendCheckLogin()
		if (!$TYPO3_CONF_VARS['BE']['adminOnly'] || $this->isAdmin())	{
			return TRUE;
		} else return FALSE;
	 }


	/**
	 * Evaluates if the Backend User has read access to the input page record.
	 * The evaluation is based on both read-permission and whether the page is found in one of the users webmounts. Only if both conditions are true will the function return true.
	 * Read access means that previewing is allowed etc.
	 * Used in index_ts.php
	 *
	 * @param	array		The page record to evaluate for
	 * @return	boolean		True if read access
	 */
	function extPageReadAccess($pageRec)	{
		return $this->isInWebMount($pageRec['uid']) && $this->doesUserHaveAccess($pageRec,1);
	}

	/**
	 * Checks if a Admin Panel section ("module") is available for the user. If so, true is returned.
	 *
	 * @param	string		The module key, eg. "edit", "preview", "info" etc.
	 * @return	boolean
	 * @see extPrintFeAdminDialog()
	 */
	function extAdmModuleEnabled($key)	{
			// Returns true if the module checked is "preview" and the forcePreview flag is set.
		if ($key=="preview" && $this->ext_forcePreview)	return true;
			// If key is not set, only "all" is checked
		if ($this->extAdminConfig['enable.']['all'])	return true;
		if ($this->extAdminConfig['enable.'][$key])	{
			return true;
		}
	}

	/**
	 * Saves any change in settings made in the Admin Panel.
	 * Called from index_ts.php right after access check for the Admin Panel
	 *
	 * @return	void
	 */
	function extSaveFeAdminConfig()	{
		$input = t3lib_div::_GET('TSFE_ADMIN_PANEL');
		if (is_array($input))	{
				// Setting
			$this->uc['TSFE_adminConfig'] = array_merge(!is_array($this->uc['TSFE_adminConfig'])?array():$this->uc['TSFE_adminConfig'], $input);			// Candidate for t3lib_div::array_merge() if integer-keys will some day make trouble...
			unset($this->uc['TSFE_adminConfig']['action']);

				// Actions:
			if ($input['action']['clearCache'] && $this->extAdmModuleEnabled('cache'))	{
				$this->extPageInTreeInfo=array();
				$theStartId = intval($input['cache_clearCacheId']);
				$GLOBALS['TSFE']->clearPageCacheContent_pidList($this->extGetTreeList($theStartId, $this->extGetFeAdminValue('cache','clearCacheLevels'),0,$this->getPagePermsClause(1)).$theStartId);
			}
			if ($input['action']['publish'] && $this->extAdmModuleEnabled('publish'))	{
				$theStartId = intval($input['publish_id']);
				$this->extPublishList = $this->extGetTreeList($theStartId, $this->extGetFeAdminValue('publish','levels'),0,$this->getPagePermsClause(1)).$theStartId;
			}

				// Saving
			$this->writeUC();
		}
		$GLOBALS['TT']->LR = $this->extGetFeAdminValue('tsdebug','LR');
		if ($this->extGetFeAdminValue('cache','noCache'))	{$GLOBALS['TSFE']->set_no_cache();}
	}

	/**
	 * Returns the value for a Admin Panel setting. You must specify both the module-key and the internal setting key.
	 *
	 * @param	string		Module key
	 * @param	string		Setting key
	 * @return	string		The setting value
	 */
	function extGetFeAdminValue($pre,$val='')	{
		if ($this->extAdmModuleEnabled($pre))	{	// Check if module is enabled.
				// Exceptions where the values can be overridden from backend:
				// deprecated
			if ($pre.'_'.$val == 'edit_displayIcons' && $this->extAdminConfig['module.']['edit.']['forceDisplayIcons'])	{
				return true;
			}
			if ($pre.'_'.$val == 'edit_displayFieldIcons' && $this->extAdminConfig['module.']['edit.']['forceDisplayFieldIcons'])	{
				return true;
			}

				// override all settings with user TSconfig
			if ($this->extAdminConfig['override.'][$pre.'.'][$val] && $val)	{
				return $this->extAdminConfig['override.'][$pre.'.'][$val];
			}
			if ($this->extAdminConfig['override.'][$pre])	{
				return $this->extAdminConfig['override.'][$pre];
			}

			$retVal = $val ? $this->uc['TSFE_adminConfig'][$pre.'_'.$val] : 1;

			if ($pre=='preview' && $this->ext_forcePreview)	{
				if (!$val)	{
					return true;
				} else {
					return $retVal;
				}
			}

				// regular check:
			if ($this->extIsAdmMenuOpen($pre))	{	// See if the menu is expanded!
				return $retVal;
			}
		}
	}

	/**
	 * Returns true if admin panel module is open
	 *
	 * @param	string		Module key
	 * @return	boolean		True, if the admin panel is open for the specified admin panel module key.
	 */
	function extIsAdmMenuOpen($pre)	{
		return $this->uc['TSFE_adminConfig']['display_top'] && $this->uc['TSFE_adminConfig']['display_'.$pre];
	}
















	/*****************************************************
	 *
	 * TSFE BE user Access Functions
	 *
	 ****************************************************/

	/**
	 * Generates a list of Page-uid's from $id. List does not include $id itself
	 * The only pages excluded from the list are deleted pages.
	 *
	 * @param	integer		Start page id
	 * @param	integer		Depth to traverse down the page tree.
	 * @param	integer		$begin is an optional integer that determines at which level in the tree to start collecting uid's. Zero means 'start right away', 1 = 'next level and out'
	 * @param	string		Perms clause
	 * @return	string		Returns the list with a comma in the end (if any pages selected!)
	 */
	function extGetTreeList($id,$depth,$begin=0,$perms_clause)	{
		$depth=intval($depth);
		$begin=intval($begin);
		$id=intval($id);
		$theList='';

		if ($id && $depth>0)	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'uid,title',
						'pages',
						'pid='.$id.' AND doktype IN ('.$GLOBALS['TYPO3_CONF_VARS']['FE']['content_doktypes'].') AND deleted=0 AND '.$perms_clause
					);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				if ($begin<=0)	{
					$theList.=$row['uid'].',';
					$this->extPageInTreeInfo[]=array($row['uid'],$row['title'],$depth);
				}
				if ($depth>1)	{
					$theList.=$this->extGetTreeList($row['uid'], $depth-1,$begin-1,$perms_clause);
				}
			}
		}
		return $theList;
	}

	/**
	 * Returns the number of cached pages for a page id.
	 *
	 * @param	integer		The page id.
	 * @return	integer		The number of pages for this page in the table "cache_pages"
	 */
	function extGetNumberOfCachedPages($page_id)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)', 'cache_pages', 'page_id='.intval($page_id));
		list($num) = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		return $num;
	}





















	/*****************************************************
	 *
	 * Localization handling
	 *
	 ****************************************************/

	/**
	 * Returns the label for key, $key. If a translation for the language set in $this->uc['lang'] is found that is returned, otherwise the default value.
	 * IF the global variable $LOCAL_LANG is NOT an array (yet) then this function loads the global $LOCAL_LANG array with the content of "sysext/lang/locallang_tsfe.php" so that the values therein can be used for labels in the Admin Panel
	 *
	 * @param	string		Key for a label in the $LOCAL_LANG array of "sysext/lang/locallang_tsfe.php"
	 * @return	string		The value for the $key
	 */
	function extGetLL($key)	{
		global $LOCAL_LANG;
		if (!is_array($LOCAL_LANG))	{
			$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_tsfe.php');
			#include('./'.TYPO3_mainDir.'sysext/lang/locallang_tsfe.php');
			if (!is_array($LOCAL_LANG))		$LOCAL_LANG=array();
		}

		$labelStr = htmlspecialchars($GLOBALS['LANG']->getLL($key));	// Label string in the default backend output charset.

			// Convert to utf-8, then to entities:
		if ($GLOBALS['LANG']->charSet!='utf-8')	{
			$labelStr = $GLOBALS['LANG']->csConvObj->utf8_encode($labelStr,$GLOBALS['LANG']->charSet);
		}
		$labelStr = $GLOBALS['LANG']->csConvObj->utf8_to_entities($labelStr);

			// Return the result:
		return $labelStr;
	}













	/*****************************************************
	 *
	 * Frontend Editing
	 *
	 ****************************************************/

	/**
	 * Returns true in an edit-action is sent from the Admin Panel
	 *
	 * @return	boolean
	 * @see index_ts.php
	 */
	function extIsEditAction()	{
		if (is_array($this->TSFE_EDIT))	{
			if ($this->TSFE_EDIT['cancel'])	{
				unset($this->TSFE_EDIT['cmd']);
			} else {
				$cmd = (string)$this->TSFE_EDIT['cmd'];
				if (($cmd!='edit' || (is_array($this->TSFE_EDIT['data']) && ($this->TSFE_EDIT['update'] || $this->TSFE_EDIT['update_close']))) && $cmd!='new')	{
						// $cmd can be a command like "hide" or "move". If $cmd is "edit" or "new" it's an indication to show the formfields. But if data is sent with update-flag then $cmd = edit is accepted because edit may be sendt because of .keepGoing flag.
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Returns true if an edit form is shown on the page.
	 * Used from index_ts.php where a true return-value will result in classes etc. being included.
	 *
	 * @return	boolean
	 * @see index_ts.php
	 */
	function extIsFormShown()	{
		if (is_array($this->TSFE_EDIT))	{
			$cmd=(string)$this->TSFE_EDIT['cmd'];
			if ($cmd=='edit' || $cmd=='new')	{
				return true;
			}
		}
	}

	/**
	 * Management of the on-page frontend editing forms and edit panels.
	 * Basically taking in the data and commands and passes them on to the proper classes as they should be.
	 *
	 * @return	void
	 * @see index_ts.php
	 */
	function extEditAction()	{
		global $TCA, $TYPO3_CONF_VARS;
			// Commands:
		list($table,$uid) = explode(':',$this->TSFE_EDIT['record']);
		if ($this->TSFE_EDIT['cmd'] && $table && $uid && isset($TCA[$table]))	{
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values=0;
			$recData=array();
			$cmdData=array();
			$cmd=$this->TSFE_EDIT['cmd'];

			// ****************
			// extEditAction HOOK
			// ****************
			if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tsfebeuserauth.php']['extEditAction'])) {
				$_params = array();
				foreach($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tsfebeuserauth.php']['extEditAction'] as $_funcRef) {
								t3lib_div::callUserFunction($_funcRef,$_params,$this);
				}
			}

			switch($cmd)	{
				case 'hide':
				case 'unhide':
					$hideField = $TCA[$table]['ctrl']['enablecolumns']['disabled'];
					if ($hideField)	{
						$recData[$table][$uid][$hideField]=($cmd=='hide'?1:0);
						$tce->start($recData,Array());
						$tce->process_datamap();
					}
				break;
				case 'up':
				case 'down':
					$sortField = $TCA[$table]['ctrl']['sortby'];
					if ($sortField)	{
						if ($cmd=='up')	{
							$op= '<';
							$desc=' DESC';
						} else {
							$op= '>';
							$desc='';
						}
							// Get self:
						$fields = array_unique(t3lib_div::trimExplode(',',$TCA[$table]['ctrl']['copyAfterDuplFields'].',uid,pid,'.$sortField,1));
						$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(implode(',',$fields), $table, 'uid='.$uid);
						if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
								// record before or after
							$preview = $this->extGetFeAdminValue('preview');
							$copyAfterFieldsQuery = '';
							if ($preview)	{$ignore = array('starttime'=>1, 'endtime'=>1, 'disabled'=>1, 'fe_group'=>1);}
							if ($TCA[$table]['ctrl']['copyAfterDuplFields'])	{
								$cAFields = t3lib_div::trimExplode(',',$TCA[$table]['ctrl']['copyAfterDuplFields'],1);
								while(list(,$fN)=each($cAFields))	{
									$copyAfterFieldsQuery.=' AND '.$fN.'="'.$row[$fN].'"';
								}
							}

							$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
										'uid,pid',
										$table,
										'pid='.intval($row['pid']).
											' AND '.$sortField.$op.intval($row[$sortField]).
											$copyAfterFieldsQuery.
											t3lib_pageSelect::enableFields($table,'',$ignore),
										'',
										$sortField.$desc,
										'2'
									);
							if ($row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
								if($cmd=='down')	{
									$cmdData[$table][$uid]['move']= -$row2['uid'];
								} elseif ($row3 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {	// Must take the second record above...
									$cmdData[$table][$uid]['move']= -$row3['uid'];
								} else {	// ... and if that does not exist, use pid
									$cmdData[$table][$uid]['move']= $row['pid'];
								}
							} elseif ($cmd=='up') {
								$cmdData[$table][$uid]['move']= $row['pid'];
							}
						}
						if (count($cmdData))	{
							$tce->start(Array(),$cmdData);
							$tce->process_cmdmap();
						}
					}
				break;
				case 'delete':
					$cmdData[$table][$uid]['delete']= 1;
					if (count($cmdData))	{
						$tce->start(Array(),$cmdData);
						$tce->process_cmdmap();
					}
				break;
			}
		}
			// Data:
		if (($this->TSFE_EDIT['doSave'] || $this->TSFE_EDIT['update'] || $this->TSFE_EDIT['update_close']) && is_array($this->TSFE_EDIT['data']))	{
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values=0;
			$tce->start($this->TSFE_EDIT['data'],Array());
			$tce->process_uploads($_FILES);
			$tce->process_datamap();
		}
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tsfebeuserauth.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tsfebeuserauth.php']);
}
?>