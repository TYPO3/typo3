<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * Revised for TYPO3 3.7 6/2004 by Kasper Skaarhoj
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
 *  216:     function init()
 *  248:     function main()
 *  403:     function printContent()
 *
 *              SECTION: Helper functions
 *  432:     function getRealScriptUserObj()
 *  442:     function simulateUser()
 *  488:     function setLabel($str,$key='')
 *
 * TOTAL FUNCTIONS: 7
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

unset($MCONF);
require('conf.php');
require($BACK_PATH.'init.php');














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

	/**
	 * document template object
	 *
	 * @var mediumDoc
	 */
	var $doc;

	var $content;
	var $overrideConf;

	/**
	 * backend user object, set during simulate-user operation
	 *
	 * @var t3lib_beUserAuth
	 */
	var $OLD_BE_USER;
	var $languageUpdate;

	protected $isAdmin;
	protected $dividers2tabs;

	protected $tsFieldConf;

	protected $saveData = FALSE;
	protected $passwordIsUpdated = FALSE;
	protected $passwordIsSubmitted = FALSE;
	protected $setupIsUpdated = FALSE;
	protected $tempDataIsCleared = FALSE;


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
		/* @var $BE_USER t3lib_beUserAuth */
		global $BE_USER;

			// First check if something is submittet in the data-array from POST vars
		$d = t3lib_div::_POST('data');
		$columns = $GLOBALS['TYPO3_USER_SETTINGS']['columns'];
		$beUserId = $BE_USER->user['uid'];
		$storeRec = array();
		$fieldList = $this->getFieldsFromShowItem();

		if (is_array($d))	{

				// UC hashed before applying changes
			$save_before = md5(serialize($BE_USER->uc));

				// PUT SETTINGS into the ->uc array:

				// reload left frame when switching BE language
			if (isset($d['lang']) && ($d['lang'] != $BE_USER->uc['lang'])) {
				$this->languageUpdate = true;
			}

			if ($d['setValuesToDefault']) {
					// If every value should be default
				$BE_USER->resetUC();
			} elseif ($d['clearSessionVars']) {
				foreach ($BE_USER->uc as $key => $value) {
					if (!isset($columns[$key])) {
						unset ($BE_USER->uc[$key]);
					}
				}
				$this->tempDataIsCleared = TRUE;
			} elseif ($d['save']) {
					// save all submitted values if they are no array (arrays are with table=be_users) and exists in $GLOBALS['TYPO3_USER_SETTINGS'][columns]

				foreach($columns as $field => $config) {
					if (!in_array($field, $fieldList)) {
						continue;
					}
					if ($config['table']) {
						if ($config['table'] == 'be_users' && !in_array($field, array('password', 'password2', 'email', 'realName', 'admin'))) {
							if (!isset($config['access']) || $this->checkAccess($config) && $BE_USER->user[$field] !== $d['be_users'][$field]) {
								$storeRec['be_users'][$beUserId][$field] = $d['be_users'][$field];
								$BE_USER->user[$field] = $d['be_users'][$field];
							}
						}
					}
					if ($config['type'] == 'check') {
						$BE_USER->uc[$field] = isset($d[$field]) ? 1 : 0;
					} else {
						$BE_USER->uc[$field] = htmlspecialchars($d[$field]);
					}
				}

					// Personal data for the users be_user-record (email, name, password...)
					// If email and name is changed, set it in the users record:
				$be_user_data = $d['be_users'];

				$this->passwordIsSubmitted = (strlen($be_user_data['password']) > 0);
				$passwordIsConfirmed = ($this->passwordIsSubmitted && $be_user_data['password'] === $be_user_data['password2']);

					// Update the real name:
				if ($be_user_data['realName'] !== $BE_USER->user['realName']) {
					$BE_USER->user['realName'] = $storeRec['be_users'][$beUserId]['realName'] = substr($be_user_data['realName'], 0, 80);
				}
					// Update the email address:
				if ($be_user_data['email'] !== $BE_USER->user['email']) {
					$BE_USER->user['email'] = $storeRec['be_users'][$beUserId]['email'] = substr($be_user_data['email'], 0, 80);
				}
					// Update the password:
				if ($passwordIsConfirmed) {
					$storeRec['be_users'][$beUserId]['password'] = $be_user_data['password2'];
					$this->passwordIsUpdated = TRUE;
				}

				$this->saveData = TRUE;
			}

			$BE_USER->overrideUC();	// Inserts the overriding values.

			$save_after = md5(serialize($BE_USER->uc));
			if ($save_before!=$save_after)	{	// If something in the uc-array of the user has changed, we save the array...
				$BE_USER->writeUC($BE_USER->uc);
				$BE_USER->writelog(254, 1, 0, 1, 'Personal settings changed', array());
				$this->setupIsUpdated = TRUE;
			}
				// If the temporary data has been cleared, lets make a log note about it
			if ($this->tempDataIsCleared) {
				$BE_USER->writelog(254, 1, 0, 1, $GLOBALS['LANG']->getLL('tempDataClearedLog'), array());
			}

				// Persist data if something has changed:
			if (count($storeRec) && $this->saveData) {
					// Make instance of TCE for storing the changes.
				$tce = t3lib_div::makeInstance('t3lib_TCEmain');
				$tce->stripslashes_values=0;
				$tce->start($storeRec,Array(),$BE_USER);
				$tce->admin = 1;	// This is so the user can actually update his user record.
				$tce->bypassWorkspaceRestrictions = TRUE;	// This is to make sure that the users record can be updated even if in another workspace. This is tolerated.
				$tce->process_datamap();
				unset($tce);

				if (!$this->passwordIsUpdated || count($storeRec['be_users'][$beUserId]) > 1) {
					$this->setupIsUpdated = TRUE;
				}
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
		$this->MCONF = $GLOBALS['MCONF'];


			// Returns the script user - that is the REAL logged in user! ($GLOBALS[BE_USER] might be another user due to simulation!)
		$scriptUser = $this->getRealScriptUserObj();
			// ... and checking module access for the logged in user.
		$scriptUser->modAccess($this->MCONF, 1);

		$this->isAdmin = $scriptUser->isAdmin();

			// Getting the 'override' values as set might be set in User TSconfig
		$this->overrideConf = $GLOBALS['BE_USER']->getTSConfigProp('setup.override');
			// Getting the disabled fields might be set in User TSconfig (eg setup.fields.password.disabled=1)
		$this->tsFieldConf = $GLOBALS['BE_USER']->getTSConfigProp('setup.fields');

			// Create instance of object for output of data
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('templates/setup.html');
		$this->doc->JScodeLibArray['dyntabmenu'] = $this->doc->getDynTabMenuJScode();
		$this->doc->form = '<form action="index.php" method="post" name="usersetup" enctype="application/x-www-form-urlencoded">';
		$this->doc->tableLayout = array(
			'defRow' => array(
				'0' => array('<td class="td-label">','</td>'),
				'defCol' => array('<td valign="top">','</td>')
			)
		);
		$this->doc->table_TR = '<tr>';
		$this->doc->table_TABLE = '<table border="0" cellspacing="1" cellpadding="2" class="typo3-usersettings">';
	}

	/**
	 * Generate the main settings formular:
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TBE_MODULES;

			// file creation / delete
		if ($this->isAdmin) {
			if (t3lib_div::_POST('deleteInstallToolEnableFile')) {
				unlink(PATH_typo3conf . 'ENABLE_INSTALL_TOOL');
				$installToolEnableFileExists = is_file(PATH_typo3conf . 'ENABLE_INSTALL_TOOL');
				if ($installToolEnableFileExists) {
					$flashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$LANG->getLL('enableInstallTool.fileDelete_failed'),
						$LANG->getLL('enableInstallTool.file'),
						t3lib_FlashMessage::ERROR
					);
				} else {
					$flashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$LANG->getLL('enableInstallTool.fileDelete_ok'),
						$LANG->getLL('enableInstallTool.file'),
						t3lib_FlashMessage::OK
					);
			}
				$this->content .= $flashMessage->render();
			}
			if (t3lib_div::_POST('createInstallToolEnableFile')) {
				touch(PATH_typo3conf . 'ENABLE_INSTALL_TOOL');
				t3lib_div::fixPermissions(PATH_typo3conf . 'ENABLE_INSTALL_TOOL');
				$installToolEnableFileExists = is_file(PATH_typo3conf . 'ENABLE_INSTALL_TOOL');
				if ($installToolEnableFileExists) {
					$flashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$LANG->getLL('enableInstallTool.fileCreate_ok'),
						$LANG->getLL('enableInstallTool.file'),
						t3lib_FlashMessage::OK
					);
				} else {
					$flashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$LANG->getLL('enableInstallTool.fileCreate_failed'),
						$LANG->getLL('enableInstallTool.file'),
						t3lib_FlashMessage::ERROR
					);
			}
				$this->content .= $flashMessage->render();
		}
		}

		if ($this->languageUpdate) {
			$this->doc->JScodeArray['languageUpdate'] .=  '
				if (top.refreshMenu) {
					top.refreshMenu();
				} else {
					top.TYPO3ModuleMenu.refreshMenu();
				}
			';
		}

			// Start page:
		$this->doc->loadJavascriptLib('md5.js');

			// use a wrapper div
		$this->content .= '<div id="user-setup-wrapper">';

			// Load available backend modules
		$this->loadModules = t3lib_div::makeInstance('t3lib_loadModules');
		$this->loadModules->observeWorkspaces = true;
		$this->loadModules->load($TBE_MODULES);

		$this->content .= $this->doc->header($LANG->getLL('UserSettings').' - '.$BE_USER->user['realName'].' ['.$BE_USER->user['username'].']');

			// show if setup was saved
		if ($this->setupIsUpdated) {
			$flashMessage = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$LANG->getLL('setupWasUpdated'),
				$LANG->getLL('UserSettings')
			);
			$this->content .= $flashMessage->render();
		}
			// Show if temporary data was cleared
		if ($this->tempDataIsCleared) {
			$flashMessage = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$LANG->getLL('tempDataClearedFlashMessage'),
				$LANG->getLL('tempDataCleared')
			);
			$this->content .= $flashMessage->render();
		}
			// If password is updated, output whether it failed or was OK.
		if ($this->passwordIsSubmitted) {
			if ($this->passwordIsUpdated) {
				$flashMessage = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$LANG->getLL('newPassword_ok'),
					$LANG->getLL('newPassword')
				);
			} else {
				$flashMessage = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$LANG->getLL('newPassword_failed'),
					$LANG->getLL('newPassword'),
					t3lib_FlashMessage::ERROR
				);
			}
			$this->content .= $flashMessage->render();
		}


			// render the menu items
		$menuItems = $this->renderUserSetup();

		$this->content .= $this->doc->spacer(20) . $this->doc->getDynTabMenu($menuItems, 'user-setup', false, false, 100, 1, false, 1, $this->dividers2tabs);


			// Submit and reset buttons
		$this->content .= $this->doc->spacer(20);
		$this->content .= $this->doc->section('',
			t3lib_BEfunc::cshItem('_MOD_user_setup', 'reset', $BACK_PATH) . '
			<input type="hidden" name="simUser" value="'.$this->simUser.'" />
			<input type="submit" name="data[save]" value="'.$LANG->getLL('save').'" />
			<input type="submit" name="data[setValuesToDefault]" value="'.$LANG->getLL('resetConfiguration').'" onclick="return confirm(\''.$LANG->getLL('setToStandardQuestion').'\');" />
			<input type="submit" name="data[clearSessionVars]" value="' . $LANG->getLL('clearSessionVars') . '"  onclick="return confirm(\'' . $LANG->getLL('clearSessionVarsQuestion') . '\');" />'
		);



			// Notice
		$this->content .= $this->doc->spacer(30);
		$flashMessage = t3lib_div::makeInstance(
			't3lib_FlashMessage',
			$LANG->getLL('activateChanges'),
			'',
			t3lib_FlashMessage::INFO
		);
		$this->content .= $flashMessage->render();

			// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers['CSH'] = $docHeaderButtons['csh'];
		$markers['CONTENT'] = $this->content;

			// Build the <body> for the module
		$this->content = $this->doc->startPage($LANG->getLL('UserSettings'));
		$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
			// end of wrapper div
		$this->content .= '</div>';
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);

	}

	/**
	 * Prints the content / ends page
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
	protected function getButtons()	{
		$buttons = array(
			'csh' => '',
			'save' => '',
			'shortcut' => '',
		);

		$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_user_setup', '', $GLOBALS['BACK_PATH'], '|', true);

		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('','',$this->MCONF['name']);
		}

		return $buttons;
	}




	/******************************
	 *
	 * Render module
	 *
	 ******************************/


	 /**
	 * renders the data for all tabs in the user setup and returns
	 * everything that is needed with tabs and dyntab menu
	 *
	 * @return	ready to use for the dyntabmenu itemarray
	 */
	protected function renderUserSetup() {
		$result = array();
		$firstTabLabel = '';
		$code = array();
		$i = 0;

		$fieldArray = $this->getFieldsFromShowItem();

		$this->dividers2tabs = isset($GLOBALS['TYPO3_USER_SETTINGS']['ctrl']['dividers2tabs']) ? intval($GLOBALS['TYPO3_USER_SETTINGS']['ctrl']['dividers2tabs']) : 0;


		// "display full help" is active?
		$displayFullText = ($GLOBALS['BE_USER']->uc['edit_showFieldHelp'] == 'text');
		if ($displayFullText) {
			$this->doc->tableLayout['defRowEven'] = array('defCol' => array ('<td valign="top" colspan="3">','</td>'));
		}

		foreach ($fieldArray as $fieldName) {
			$more = '';

			if (substr($fieldName, 0, 8) == '--div--;') {
				if ($firstTabLabel == '') {
					// first tab
					$tabLabel = $this->getLabel(substr($fieldName, 8), '', false);
					$firstTabLabel = $tabLabel;
				} else {
					if ($this->dividers2tabs) {
						$result[] = array(
							'label'   => $tabLabel,
							'content' => count($code) ? $this->doc->spacer(20) . $this->doc->table($code) : ''
						);
						$tabLabel = $this->getLabel(substr($fieldName, 8), '', false);
						$i = 0;
						$code = array();
					}
				}
				continue;
			}

			$config = $GLOBALS['TYPO3_USER_SETTINGS']['columns'][$fieldName];

				// field my be disabled in setup.fields
			if (isset($this->tsFieldConf[$fieldName . '.']['disabled']) && $this->tsFieldConf[$fieldName . '.']['disabled'] == 1) {
				continue;
			}
			if (isset($config['access']) && !$this->checkAccess($config)) {
				continue;
			}

			$label = $this->getLabel($config['label'], $fieldName);
			$csh = $this->getCSH($config['csh'] ? $config['csh'] : $fieldName);
			if (!$csh) {
				$csh = '<img class="csh-dummy" src="' . $this->doc->backPath . 'clear.gif" width="16" height="16" />';
			}
			$type = $config['type'];
			$eval = $config['eval'];
			$class = $config['class'];
			$style = $config['style'];

			if ($class) {
				$more .= ' class="' . $class . '"';
			}
			if ($style) {
				$more .= ' style="' . $style . '"';
			}
			if ($this->overrideConf[$fieldName]) {
				$more .= ' disabled="disabled"';
			}

			$value = $config['table'] == 'be_users' ? $GLOBALS['BE_USER']->user[$fieldName] : $GLOBALS['BE_USER']->uc[$fieldName];
			if (!$value && isset($config['default'])) {
				$value = $config['default'];
			}

			switch ($type) {
				case 'text':
				case 'password':
					$dataAdd = '';
					if ($config['table'] == 'be_users') {
						$dataAdd = '[be_users]';
					}
					if ($eval == 'md5') {
						$more .= ' onchange="this.value=this.value?MD5(this.value):\'\';"';
					}

					if ($type == 'password') {
						$value = '';
					}

					$noAutocomplete = ($type == 'password' ? 'autocomplete="off" ' : '');
					$html = '<input id="field_' . $fieldName . '"
							type="' . $type . '"
							name="data' . $dataAdd . '[' . $fieldName . ']" ' .
							$noAutocomplete .
							'value="' . htmlspecialchars($value) . '" ' . $GLOBALS['TBE_TEMPLATE']->formWidth(20) . $more . ' />';
				break;
				case 'check':
					if (!$class) {
						$more .= ' class="check"';
					}
					$html = '<input id="field_' . $fieldName . '"
									type="checkbox"
									name="data[' . $fieldName . ']"' .
									($value ? ' checked="checked"' : '') . $more . ' />';
				break;
				case 'select':
					if (!$class) {
						$more .= ' class="select"';
					}

					if ($config['itemsProcFunc']) {
						$html = t3lib_div::callUserFunction($config['itemsProcFunc'], $config, $this, '');
					} else {
						$html = '<select id="field_' . $fieldName . '" name="data[' . $fieldName . ']"' . $more . '>' . LF;
						foreach ($config['items'] as $key => $optionLabel) {
							$html .= '<option value="' . $key . '"' .
								($value == $key ? ' selected="selected"' : '') .
								'>' . $this->getLabel($optionLabel, '', false) . '</option>' . LF;
						}
						$html .= '</select>';
					}

				break;
				case 'user':
					$html = t3lib_div::callUserFunction($config['userFunc'], $config, $this, '');
				break;
				default:
					$html = '';
			}


				// add another table row with the full text help if needed
			if ($displayFullText) {
				$code[$i++][1] = $csh;
				$csh = '';
			}

			$code[$i][1] = $csh . $label;
			$code[$i++][2]   = $html;



		}

		if ($this->dividers2tabs == 0) {
			$tabLabel = $firstTabLabel;
		}

		$result[] = array(
			'label'   => $tabLabel,
			'content' => count($code) ? $this->doc->spacer(20) . $this->doc->table($code) : ''
		);


		return $result;
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
	protected function getRealScriptUserObj()	{
		return is_object($this->OLD_BE_USER) ? $this->OLD_BE_USER : $GLOBALS['BE_USER'];
	}


	/**
	* Return a select with available languages
	 *
	* @return	string		complete select as HTML string or warning box if something went wrong.
	 */
	public function renderLanguageSelect($params, $pObj) {

			// compile the languages dropdown
		$languageOptions = array(
			'000000000' => LF . '<option value="">' . $GLOBALS['LANG']->getLL('lang_default', 1) . '</option>'
		);
			// traverse the number of languages
		$theLanguages = t3lib_div::trimExplode('|', TYPO3_languages);
		foreach ($theLanguages as $language) {
			if ($language != 'default') {
				$languageValue = $GLOBALS['LOCAL_LANG']['default']['lang_' . $language];
				$localLabel = '  -  ['.htmlspecialchars($languageValue) . ']';
				$unavailable = (is_dir(PATH_typo3conf . 'l10n/' . $language) ? false : true);
				if (!$unavailable) {
					$languageOptions[$languageValue . '--' . $language] = '
					<option value="'.$language.'"'.($GLOBALS['BE_USER']->uc['lang'] == $language ? ' selected="selected"' : '') . ($unavailable ? ' class="c-na"' : '').'>'.$GLOBALS['LANG']->getLL('lang_' . $language, 1) . $localLabel . '</option>';
				}
			}
		}
		ksort($languageOptions);
		$languageCode = '
				<select id="field_lang" name="data[lang]" class="select">' .
					implode('', $languageOptions) . '
				</select>';
		if ( $GLOBALS['BE_USER']->uc['lang'] && !@is_dir(PATH_typo3conf . 'l10n/' . $GLOBALS['BE_USER']->uc['lang'])) {
			$languageUnavailableWarning = 'The selected language "'
				. $GLOBALS['LANG']->getLL('lang_' . $GLOBALS['BE_USER']->uc['lang'], 1)
				. '" is not available before the language pack is installed.<br />'
				. ($GLOBALS['BE_USER']->isAdmin() ?
					'You can use the Extension Manager to easily download and install new language packs.'
				:	'Please ask your system administrator to do this.');


			$languageUnavailableMessage = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$languageUnavailableWarning,
				'',
				t3lib_FlashMessage::WARNING
			);

			$languageCode = $languageUnavailableMessage->render() . $languageCode;
		}

		return $languageCode;
	}

	/**
	* Returns a select with all modules for startup
	*
	* @return	string		complete select as HTML string
	*/
	public function renderStartModuleSelect($params, $pObj) {
			// start module select
		if (empty($GLOBALS['BE_USER']->uc['startModule']))	{
			$GLOBALS['BE_USER']->uc['startModule'] = $GLOBALS['BE_USER']->uc_default['startModule'];
		}
		$startModuleSelect .= '<option value=""></option>';
		foreach ($pObj->loadModules->modules as $mainMod => $modData) {
			if (isset($modData['sub']) && is_array($modData['sub'])) {
				$startModuleSelect .= '<option disabled="disabled">'.$GLOBALS['LANG']->moduleLabels['tabs'][$mainMod.'_tab'].'</option>';
				foreach ($modData['sub'] as $subKey => $subData) {
					$modName = $subData['name'];
					$startModuleSelect .= '<option value="' . $modName . '"' . ($GLOBALS['BE_USER']->uc['startModule'] == $modName ? ' selected="selected"' : '') . '>';
					$startModuleSelect .= ' - ' . $GLOBALS['LANG']->moduleLabels['tabs'][$modName.'_tab'] . '</option>';
				}
			}
		}


		return '<select id="field_startModule" name="data[startModule]" class="select">' . $startModuleSelect . '</select>';
		}

 	/**
	 *
	 * @param array $params                    config of the field
	 * @param SC_mod_user_setup_index $parent  this class as reference
	 * @return string	                       html with description and button
	 */
	public function renderInstallToolEnableFileButton(array $params, SC_mod_user_setup_index $parent) {
		// Install Tool access file
		$installToolEnableFile = PATH_typo3conf . 'ENABLE_INSTALL_TOOL';
		$installToolEnableFileExists = is_file($installToolEnableFile);
		if ($installToolEnableFileExists && (time() - filemtime($installToolEnableFile) > 3600)) {
			$content = file_get_contents($installToolEnableFile);
			$verifyString = 'KEEP_FILE';

			if (trim($content) !== $verifyString) {
					// Delete the file if it is older than 3600s (1 hour)
				unlink($installToolEnableFile);
				$installToolEnableFileExists = is_file($installToolEnableFile);
			}
		}

		if ($installToolEnableFileExists) {
			return '<input type="submit" name="deleteInstallToolEnableFile" value="' . $GLOBALS['LANG']->sL('LLL:EXT:setup/mod/locallang.xml:enableInstallTool.deleteFile') . '" />';
		} else {
			return '<input type="submit" name="createInstallToolEnableFile" value="' . $GLOBALS['LANG']->sL('LLL:EXT:setup/mod/locallang.xml:enableInstallTool.createFile') . '" />';
		}
	}

	/**
	 * Will make the simulate-user selector if the logged in user is administrator.
	 * It will also set the GLOBAL(!) BE_USER to the simulated user selected if any (and set $this->OLD_BE_USER to logged in user)
	 *
	 * @return	void
	 */
	public function simulateUser()	{
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
			$users = t3lib_BEfunc::getUserNames('username,usergroup,usergroup_cached_list,uid,realName', t3lib_BEfunc::BEenableFields('be_users'));
			$opt = array();
			foreach ($users as $rr) {
				if ($rr['uid'] != $BE_USER->user['uid']) {
					$opt[] = '<option value="'.$rr['uid'].'"'.($this->simUser==$rr['uid']?' selected="selected"':'').'>'.htmlspecialchars($rr['username'].' ('.$rr['realName'].')').'</option>';
				}
			}
			if (count($opt)) {
				$this->simulateSelector = '<select id="field_simulate" name="simulateUser" onchange="window.location.href=\'index.php?simUser=\'+this.options[this.selectedIndex].value;"><option></option>'.implode('',$opt).'</select>';
			}
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
	* Returns a select with simulate users
	*
	* @return	string		complete select as HTML string
	*/
	public function renderSimulateUserSelect($params, $pObj) {
		return $pObj->simulateSelector;
	}

	/**
	* Returns access check (currently only "admin" is supported)
	*
	* @param	array		$config: Configuration of the field, access mode is defined in key 'access'
	* @return	boolean		Whether it is allowed to modify the given field
	*/
	protected function checkAccess(array $config) {
		$access = $config['access'];
			// check for hook
		if (strpos($access, 'tx_') === 0) {
			$accessObject = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['setup']['accessLevelCheck'][$access] . ':&' . $access);
			if (is_object($accessObject) && method_exists($accessObject, 'accessLevelCheck'))	{
					// initialize vars. If method fails, $set will be set to false
				return $accessObject->accessLevelCheck($config);
			}
		} elseif ($access == 'admin') {
			return $this->isAdmin;
		}
	}


	/**
	 * Returns the label $str from getLL() and grays out the value if the $str/$key is found in $this->overrideConf array
	 *
	 * @param	string		Locallang key
	 * @param	string		Alternative override-config key
	 * @param	boolean		Defines whether the string should be wrapped in a <label> tag.
	 * @param	string		Alternative id for use in "for" attribute of <label> tag. By default the $str key is used prepended with "field_".
	 * @return	string		HTML output.
	 */
	protected function getLabel($str, $key='', $addLabelTag=true, $altLabelTagId='')	{
		if (substr($str, 0, 4) == 'LLL:') {
			$out = $GLOBALS['LANG']->sL($str);
		} else {
			$out = htmlspecialchars($str);
 		}


		if (isset($this->overrideConf[($key?$key:$str)]))	{
			$out = '<span style="color:#999999">'.$out.'</span>';
		}

		if($addLabelTag) {
			$out = '<label for="' . ($altLabelTagId ? $altLabelTagId : 'field_' . $key) . '">' . $out . '</label>';
		}
		return $out;
	}

	/**
	 * Returns the CSH Icon for given string
	 *
	 * @param	string		Locallang key
	 * @return	string		HTML output.
	 */
	protected function getCSH($str) {
		if (!t3lib_div::inList('language,simuser', $str)) {
			$str = 'option_' . $str;
		}
		return t3lib_BEfunc::cshItem('_MOD_user_setup', $str, $this->doc->backPath, '|', false, 'margin-bottom:0px;');
	}
	
	/**
	 * Returns array with fields defined in $GLOBALS['TYPO3_USER_SETTINGS']['showitem']
	 * 
	 * @param	void
	 * @return	array	array with fieldnames visible in form
	 */
	protected function getFieldsFromShowItem() {
		$fieldList = $GLOBALS['TYPO3_USER_SETTINGS']['showitem'];

			// disable fields depended on settings
		if (!$GLOBALS['TYPO3_CONF_VARS']['BE']['RTEenabled']) {
			$fieldList = t3lib_div::rmFromList('edit_RTE', $fieldList);
		}

		$fieldArray = t3lib_div::trimExplode(',', $fieldList, TRUE);
		return $fieldArray;
	}
}


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