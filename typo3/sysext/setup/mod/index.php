<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Module: User configuration
 *
 * This module lets users viev and change their individual settings
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * Revised for TYPO3 3.7 6/2004 by Kasper Skårhøj
 * XHTML compatible.
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   86: class SC_mod_user_setup_index
 *
 *              SECTION: Saving data
 *  114:     function storeIncomingData()
 *
 *              SECTION: Rendering module
 *  215:     function init()
 *  247:     function main()
 *  397:     function printContent()
 *
 *              SECTION: Helper functions
 *  426:     function getRealScriptUserObj()
 *  436:     function simulateUser()
 *  482:     function setLabel($str,$key='')
 *
 * TOTAL FUNCTIONS: 7
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

unset($MCONF);
require('conf.php');
require($BACK_PATH.'init.php');
require_once(PATH_t3lib.'class.t3lib_tcemain.php');














/**
 * Script class for the Setup module
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_setup
 */
class SC_mod_user_setup_index {

		// Internal variables:
	var $MCONF = array();
	var $MOD_MENU = array();
	var $MOD_SETTINGS = array();
	var $doc;

	var $content;
	var $overrideConf;
	var $OLD_BE_USER;





	/******************************
	 *
	 * Saving data
	 *
	 ******************************/

	/**
	 * If settings are submitted to _POST[DATA], store them
	 * NOTICE: This method is called before the template.php is included. See buttom of document
	 *
	 * @return	void
	 */
	function storeIncomingData()	{
		global $BE_USER;


			// First check if something is submittet in the data-array from POST vars
		$d = t3lib_div::_POST('data');
		if (is_array($d))	{

				// UC hashed before applying changes
			$save_before = md5(serialize($BE_USER->uc));

				// PUT SETTINGS into the ->uc array:

				// Language
			$BE_USER->uc['lang'] = $d['lang'];

				// Startup
			$BE_USER->uc['condensedMode'] = $d['condensedMode'];
			$BE_USER->uc['noMenuMode'] = $d['noMenuMode'];
			if (t3lib_extMgm::isLoaded('taskcenter'))	$BE_USER->uc['startInTaskCenter'] = $d['startInTaskCenter'];
			$BE_USER->uc['thumbnailsByDefault'] = $d['thumbnailsByDefault'];
			$BE_USER->uc['helpText'] = $d['helpText'];
			$BE_USER->uc['titleLen'] = intval($d['titleLen']);

				// Advanced functions:
			$BE_USER->uc['copyLevels'] = t3lib_div::intInRange($d['copyLevels'],0,100);
			$BE_USER->uc['recursiveDelete'] = $d['recursiveDelete'];

				// Edit
			$BE_USER->uc['edit_wideDocument'] = $d['edit_wideDocument'];
			if ($GLOBALS['TYPO3_CONF_VARS']['BE']['RTEenabled'])	{ $BE_USER->uc['edit_RTE'] = $d['edit_RTE']; }
			$BE_USER->uc['edit_docModuleUpload'] = $d['edit_docModuleUpload'];
			$BE_USER->uc['edit_showFieldHelp'] = $d['edit_showFieldHelp'];
			$BE_USER->uc['disableCMlayers'] = $d['disableCMlayers'];

				// Personal:
			$BE_USER->uc['emailMeAtLogin'] = $d['emailMeAtLogin'];


			if ($d['setValuesToDefault'])	{	// If every value should be default
				$BE_USER->resetUC();
			}
			$BE_USER->overrideUC();	// Inserts the overriding values.

			$save_after = md5(serialize($BE_USER->uc));
			if ($save_before!=$save_after)	{	// If something in the uc-array of the user has changed, we save the array...
				$BE_USER->writeUC($BE_USER->uc);
				$BE_USER->writelog(254,1,0,1,'Personal settings changed',Array());
			}


				// Personal data for the users be_user-record (email, name, password...)
				// If email and name is changed, set it in the users record:
			$be_user_data = t3lib_div::_GP('ext_beuser');
			$this->PASSWORD_UPDATED = strlen($be_user_data['password1'].$be_user_data['password2'])>0 ? -1 : 0;
			if ($be_user_data['email']!=$BE_USER->user['email']
					|| $be_user_data['realName']!=$BE_USER->user['realName']
					|| (strlen($be_user_data['password1'])==32
							&& !strcmp($be_user_data['password1'],$be_user_data['password2']))
					)	{
				$storeRec = array();
				$BE_USER->user['realName'] = $storeRec['be_users'][$BE_USER->user['uid']]['realName'] = substr($be_user_data['realName'],0,80);
				$BE_USER->user['email'] = $storeRec['be_users'][$BE_USER->user['uid']]['email'] = substr($be_user_data['email'],0,80);
				if (strlen($be_user_data['password1'])==32 && !strcmp($be_user_data['password1'],$be_user_data['password2']))	{
					$BE_USER->user['password'] = $storeRec['be_users'][$BE_USER->user['uid']]['password'] = $be_user_data['password1'];
					$this->PASSWORD_UPDATED = 1;
				}

					// Make instance of TCE for storing the changes.
				$tce = t3lib_div::makeInstance('t3lib_TCEmain');
				$tce->stripslashes_values=0;
				$tce->start($storeRec,Array(),$BE_USER);
				$tce->admin = 1;
				$tce->process_datamap();
				unset($tce);
			}
		}
	}












	/******************************
	 *
	 * Rendering module
	 *
	 ******************************/

	/**
	 * Initializes the module for display of the settings form.
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$BACK_PATH;
		$this->MCONF = $GLOBALS['MCONF'];

			// Returns the script user - that is the REAL logged in user! ($GLOBALS[BE_USER] might be another user due to simulation!)
		$scriptUser = $this->getRealScriptUserObj();
		$scriptUser->modAccess($this->MCONF,1);	// ... and checking module access for the logged in user.

			// Getting the 'override' values as set might be set in User TSconfig
		$this->overrideConf = $BE_USER->getTSConfigProp('setup.override');

			// Create instance of object for output of data
		$this->doc = t3lib_div::makeInstance('mediumDoc');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->docType = 'xhtml_trans';

		$this->doc->form = '<form action="index.php" method="post" enctype="application/x-www-form-urlencoded">';
		$this->doc->tableLayout = Array (
			'defRow' => Array (
				'0' => Array('<td align="left" width="300">','</td>'),
				'defCol' => Array('<td valign="top">','</td>')
			)
		);
		$this->doc->table_TR = '<tr class="bgColor4">';
		$this->doc->table_TABLE = '<table border="0" cellspacing="1" cellpadding="2">';
	}

	/**
	 * Generate the main settings formular:
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH;

			// Start page:
		$this->doc->JScode.= '<script language="javascript" type="text/javascript" src="'.$BACK_PATH.'md5.js"></script>';
		$this->content.= $this->doc->startPage($LANG->getLL('UserSettings'));
		$this->content.= $this->doc->header($LANG->getLL('UserSettings').' - ['.$BE_USER->user['username'].']');

			// CSH general:
		$this->content.= t3lib_BEfunc::cshItem('_MOD_user_setup', '', $GLOBALS['BACK_PATH'],'|');

			// If password is updated, output whether it failed or was OK.
		if ($this->PASSWORD_UPDATED)	{
			if ($this->PASSWORD_UPDATED>0)	{
				$this->content.=$this->doc->section($LANG->getLL('newPassword').':',$LANG->getLL('newPassword_ok'),1,0,1);
			} else {
				$this->content.=$this->doc->section($LANG->getLL('newPassword').':',$LANG->getLL('newPassword_failed'),1,0,2);
			}
			$this->content.=$this->doc->spacer(25);
		}

			// Simulate selector box:
		if ($this->simulateSelector)	{
			$this->content.=$this->doc->section($LANG->getLL('simulate').':',$this->simulateSelector.t3lib_BEfunc::cshItem('_MOD_user_setup', 'simuser', $GLOBALS['BACK_PATH'],'|'),1,0,($this->simUser?2:0));
		}


			// Languages:
		$opt = array();
		$opt['000000000']='
					<option value="">'.$LANG->getLL('lang_default',1).'</option>';
		$theLanguages = t3lib_div::trimExplode('|',TYPO3_languages);

			// Character set conversion object:
		$csConvObj = t3lib_div::makeInstance('t3lib_cs');

			// traverse the number of languages:
		foreach($theLanguages as $val)	{
			if ($val!='default')	{
				if ($BE_USER->uc['lang']!='default')	{
					$localLabel = '  -  ['.htmlspecialchars($GLOBALS['LOCAL_LANG']['default']['lang_'.$val]).']';
				} else $localLabel='';

				$opt[$GLOBALS['LOCAL_LANG']['default']['lang_'.$val].'--'.$val]='
					<option value="'.$val.'"'.($BE_USER->uc['lang']==$val?' selected="selected"':'').'>'.$LANG->getLL('lang_'.$val,1).$localLabel.'</option>';
			}
		}
		ksort($opt);
		$code='
				<select name="data[lang]">'.
					implode('',$opt).'
				</select>'.
				t3lib_BEfunc::cshItem('_MOD_user_setup', 'language', $GLOBALS['BACK_PATH'],'|');
		$this->content.=$this->doc->section($LANG->getLL('language').':',$code,0,1);


			// 'Startup' section:
		$code = Array();

		$code[2][1] = $this->setLabel('condensedMode','condensedMode').':';
		$code[2][2] = '<input type="checkbox" name="data[condensedMode]"'.($BE_USER->uc['condensedMode']?' checked="checked"':'').' />';
		$code[3][1] = $this->setLabel('noMenuMode','noMenuMode').':';
		$code[3][2] = '<select name="data[noMenuMode]">
			<option value=""'.(!$BE_USER->uc['noMenuMode']?' selected="selected"':'').'>'.$this->setLabel('noMenuMode_def').'</option>
			<option value="1"'.($BE_USER->uc['noMenuMode'] && (string)$BE_USER->uc['noMenuMode']!="icons"?' selected="selected"':'').'>'.$this->setLabel('noMenuMode_sel').'</option>
			<option value="icons"'.((string)$BE_USER->uc['noMenuMode']=='icons'?' selected="selected"':'').'>'.$this->setLabel('noMenuMode_icons').'</option>
		</select>';
		if (t3lib_extMgm::isLoaded('taskcenter'))	{
			$code[4][1] = $this->setLabel('startInTaskCenter','startInTaskCenter').':';
			$code[4][2] = '<input type="checkbox" name="data[startInTaskCenter]"'.($BE_USER->uc['startInTaskCenter']?' checked="checked"':'').' />';
		}
		$code[5][1] = $this->setLabel('showThumbs','thumbnailsByDefault').':';
		$code[5][2] = '<input type="checkbox" name="data[thumbnailsByDefault]"'.($BE_USER->uc['thumbnailsByDefault']?' checked="checked"':'').' />';
		$code[6][1] = $this->setLabel('helpText').':';
		$code[6][2] = '<input type="checkbox" name="data[helpText]"'.($BE_USER->uc['helpText']?' checked="checked"':'').' />';
		$code[7][1] = $this->setLabel('maxTitleLen','titleLen').':';
		$code[7][2] = '<input type="text" name="data[titleLen]" value="'.$BE_USER->uc['titleLen'].'"'.$GLOBALS['TBE_TEMPLATE']->formWidth(5).' maxlength="5" />';

		$this->content.=$this->doc->section($LANG->getLL('opening').':',$this->doc->table($code),0,1);


			// Advanced Operations:
		$code = Array();
		$code[1][1] = $this->setLabel('copyLevels').':';
		$code[1][2] = '<input type="text" name="data[copyLevels]" value="'.$BE_USER->uc['copyLevels'].'"'.$GLOBALS['TBE_TEMPLATE']->formWidth(5).' maxlength="5" /> '.$this->setLabel('levels','copyLevels');
		$code[2][1] = $this->setLabel('recursiveDelete').':';
		$code[2][2] = '<input type="checkbox" name="data[recursiveDelete]"'.($BE_USER->uc['recursiveDelete']?' checked="checked"':'').' />';

		$this->content.=$this->doc->section($LANG->getLL('functions').":",$this->doc->table($code),0,1);


			// Edit
		$code = Array();
		$code[2][1] = $this->setLabel('edit_wideDocument').':';
		$code[2][2] = '<input type="checkbox" name="data[edit_wideDocument]"'.($BE_USER->uc['edit_wideDocument']?' checked="checked"':'').' />';
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['RTEenabled'])	{
			$code[3][1] = $this->setLabel('edit_RTE').':';
			$code[3][2] = '<input type="checkbox" name="data[edit_RTE]"'.($BE_USER->uc['edit_RTE']?' checked="checked"':'').' />';
		}
		$code[4][1] = $this->setLabel('edit_docModuleUpload').':';
		$code[4][2] = '<input type="checkbox" name="data[edit_docModuleUpload]"'.($BE_USER->uc['edit_docModuleUpload']?' checked="checked"':'').' />';

		$code[6][1] = $this->setLabel('edit_showFieldHelp').':';
		$code[6][2] = '<select name="data[edit_showFieldHelp]">
			<option value=""></option>
			<option value="icon"'.($BE_USER->uc['edit_showFieldHelp']=='icon'?' selected="selected"':'').'>'.$this->setLabel('edit_showFieldHelp_icon').'</option>
			<option value="text"'.($BE_USER->uc['edit_showFieldHelp']=='text'?' selected="selected"':'').'>'.$this->setLabel('edit_showFieldHelp_message').'</option>
		</select>';

		$code[7][1] = $this->setLabel('disableCMlayers').':';
		$code[7][2] = '<input type="checkbox" name="data[disableCMlayers]"'.($BE_USER->uc['disableCMlayers']?' checked="checked"':'').' />';

		$this->content.=$this->doc->section($LANG->getLL('edit_functions').":",$this->doc->table($code),0,1);


			// Personal data
		$code = Array();
		$code[1][1] = $this->setLabel('beUser_realName').':';
		$code[1][2] = '<input type="text" name="ext_beuser[realName]" value="'.htmlspecialchars($BE_USER->user['realName']).'"'.$GLOBALS['TBE_TEMPLATE']->formWidth(20).' />';
		$code[2][1] = $this->setLabel('beUser_email').':';
		$code[2][2] = '<input type="text" name="ext_beuser[email]" value="'.htmlspecialchars($BE_USER->user['email']).'"'.$GLOBALS['TBE_TEMPLATE']->formWidth(20).' />';
		$code[3][1] = $this->setLabel('emailMeAtLogin').' ('.$GLOBALS['BE_USER']->user['email'].'):';
		$code[3][2] = '<input type="checkbox" name="data[emailMeAtLogin]"'.($BE_USER->uc['emailMeAtLogin']?' checked="checked"':'').' />';
		$code[4][1] = $this->setLabel('newPassword').':';
		$code[4][2] = '<input type="password" name="ext_beuser[password1]" value=""'.$GLOBALS['TBE_TEMPLATE']->formWidth(20).' onchange="this.value=this.value?MD5(this.value):\'\';" />';
		$code[5][1] = $this->setLabel('newPasswordAgain').':';
		$code[5][2] = '<input type="password" name="ext_beuser[password2]" value=""'.$GLOBALS['TBE_TEMPLATE']->formWidth(20).' onchange="this.value=this.value?MD5(this.value):\'\'" />';

		$this->content.=$this->doc->section($LANG->getLL('personal_data').":",$this->doc->table($code),0,1);


			// Submit:
		$this->content.=$this->doc->spacer(20);
		$this->content.=$this->doc->section('','
			<input type="submit" name="submit" value="'.$LANG->getLL('save').'" />
			 &nbsp; <b>'.$LANG->getLL('setToStandard').': </b><input type="checkbox" name="data[setValuesToDefault]" />'.
			 t3lib_BEfunc::cshItem('_MOD_user_setup', 'reset', $GLOBALS['BACK_PATH'],'|').'
			<input type="hidden" name="simUser" value="'.$this->simUser.'" />');


			// Notice
		$this->content.=$this->doc->spacer(5);
		$this->content.=$this->doc->section('',$LANG->getLL('activateChanges'));
	}

	/**
	 * Prints the content / ends page
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content.= $this->doc->endPage();
		echo $this->content;
		exit;
	}












	/******************************
	 *
	 * Helper functions
	 *
	 ******************************/

	/**
	 * Returns the backend user object, either the global OR the $this->OLD_BE_USER which is set during simulate-user operation.
	 * Anyway: The REAL user is returned - the one logged in.
	 *
	 * @return	object		The REAL user is returned - the one logged in.
	 */
	function getRealScriptUserObj()	{
		return is_object($this->OLD_BE_USER) ? $this->OLD_BE_USER : $GLOBALS['BE_USER'];
	}

	/**
	 * Will make the simulate-user selector if the logged in user is administrator.
	 * It will also set the GLOBAL(!) BE_USER to the simulated user selected if any (and set $this->OLD_BE_USER to logged in user)
	 *
	 * @return	void
	 */
	function simulateUser()	{
		global $BE_USER,$LANG,$BACK_PATH;

		// *******************************************************************************
		// If admin, allow simulation of another user
		// *******************************************************************************
		$this->simUser = 0;
		$this->simulateSelector = '';
		unset($this->OLD_BE_USER);
		if ($BE_USER->isAdmin())	{
			$this->simUser = t3lib_div::_GP('simUser');

				// Make user-selector:
			$users = t3lib_BEfunc::getUserNames('username,usergroup,usergroup_cached_list,uid,realName');
			$opt = array();
			reset($users);
			$opt[] = '<option></option>';
			while(list(,$rr)=each($users))	{
				if ($rr['uid']!=$BE_USER->user['uid'])	{
					$opt[] = '<option value="'.$rr['uid'].'"'.($this->simUser==$rr['uid']?' selected="selected"':'').'>'.htmlspecialchars($rr['username'].' ('.$rr['realName'].')').'</option>';
				}
			}
			$this->simulateSelector = '<select name="simulateUser" onchange="document.location=\'index.php?simUser=\'+this.options[this.selectedIndex].value;">'.implode('',$opt).'</select>';
		}

		if ($this->simUser>0)	{	// This can only be set if the previous code was executed.
			$this->OLD_BE_USER = $BE_USER;	// Save old user...
			unset($BE_USER);	// Unset current

			$BE_USER = t3lib_div::makeInstance('t3lib_beUserAuth');	// New backend user object
			$BE_USER->OS = TYPO3_OS;
			$BE_USER->setBeUserByUid($this->simUser);
			$BE_USER->fetchGroupData();
			$BE_USER->backendSetUC();
			$GLOBALS['BE_USER'] = $BE_USER;	// Must do this, because unsetting $BE_USER before apparently unsets the reference to the global variable by this name!
		}
	}

	/**
	 * Returns the label $str from getLL() and grays out the value if the $str/$key is found in $this->overrideConf array
	 * Adds CSH as well if applicable.
	 *
	 * @param	string		Locallang key
	 * @param	string		Alternative override-config key
	 * @return	string		HTML output.
	 */
	function setLabel($str,$key='')	{
		$out = $GLOBALS['LANG']->getLL($str);
		if (isset($this->overrideConf[($key?$key:$str)]))	{
			$out = '<span style="color:#999999">'.$out.'</span>';
		}

			// CSH:
		$out.= t3lib_BEfunc::cshItem('_MOD_user_setup', 'option_'.$str, $GLOBALS['BACK_PATH'],'|',FALSE,'margin-bottom:0px;');

			// Return value:
		return $out;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/setup/mod/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/setup/mod/index.php']);
}












// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_user_setup_index');
$SOBE->simulateUser();
$SOBE->storeIncomingData();

// These includes MUST be afterwards the settings are saved...!
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:setup/mod/locallang.xml');

$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>