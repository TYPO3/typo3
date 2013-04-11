<?php
namespace TYPO3\CMS\Setup\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Script class for the Setup module
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class SetupModuleController {

	// Internal variables:
	/**
	 * @todo Define visibility
	 */
	public $MCONF = array();

	/**
	 * @todo Define visibility
	 */
	public $MOD_MENU = array();

	/**
	 * @todo Define visibility
	 */
	public $MOD_SETTINGS = array();

	/**
	 * document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * @todo Define visibility
	 */
	public $doc;

	/**
	 * @todo Define visibility
	 */
	public $content;

	/**
	 * @todo Define visibility
	 */
	public $overrideConf;

	/**
	 * backend user object, set during simulate-user operation
	 *
	 * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 * @todo Define visibility
	 */
	public $OLD_BE_USER;

	/**
	 * @todo Define visibility
	 */
	public $languageUpdate;

	protected $pagetreeNeedsRefresh = FALSE;

	protected $isAdmin;

	protected $dividers2tabs;

	protected $tsFieldConf;

	protected $saveData = FALSE;

	protected $passwordIsUpdated = FALSE;

	protected $passwordIsSubmitted = FALSE;

	protected $setupIsUpdated = FALSE;

	protected $tempDataIsCleared = FALSE;

	protected $settingsAreResetToDefault = FALSE;

	/**
	 * Form protection instance
	 *
	 * @var \TYPO3\CMS\Core\FormProtection\BackendFormProtection
	 */
	protected $formProtection;

	/******************************
	 *
	 * Saving data
	 *
	 ******************************/
	/**
	 * Instanciate the form protection before a simulated user is initialized.
	 */
	public function __construct() {
		$this->formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
	}

	/**
	 * Getter for the form protection instance.
	 *
	 * @return \TYPO3\CMS\Core\FormProtection\BackendFormProtection
	 */
	public function getFormProtection() {
		return $this->formProtection;
	}

	/**
	 * If settings are submitted to _POST[DATA], store them
	 * NOTICE: This method is called before the template.php is included. See
	 * bottom of document.
	 */
	public function storeIncomingData() {
		// First check if something is submitted in the data-array from POST vars
		$d = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('data');
		$columns = $GLOBALS['TYPO3_USER_SETTINGS']['columns'];
		$beUserId = $GLOBALS['BE_USER']->user['uid'];
		$storeRec = array();
		$fieldList = $this->getFieldsFromShowItem();
		if (is_array($d) && $this->formProtection->validateToken((string) \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('formToken'), 'BE user setup', 'edit')) {
			// UC hashed before applying changes
			$save_before = md5(serialize($GLOBALS['BE_USER']->uc));
			// PUT SETTINGS into the ->uc array:
			// Reload left frame when switching BE language
			if (isset($d['lang']) && $d['lang'] != $GLOBALS['BE_USER']->uc['lang']) {
				$this->languageUpdate = TRUE;
			}
			// Reload pagetree if the title length is changed
			if (isset($d['titleLen']) && $d['titleLen'] !== $GLOBALS['BE_USER']->uc['titleLen']) {
				$this->pagetreeNeedsRefresh = TRUE;
			}
			if ($d['setValuesToDefault']) {
				// If every value should be default
				$GLOBALS['BE_USER']->resetUC();
				$this->settingsAreResetToDefault = TRUE;
			} elseif ($d['clearSessionVars']) {
				foreach ($GLOBALS['BE_USER']->uc as $key => $value) {
					if (!isset($columns[$key])) {
						unset($GLOBALS['BE_USER']->uc[$key]);
					}
				}
				$this->tempDataIsCleared = TRUE;
			} elseif ($d['save']) {
				// Save all submitted values if they are no array (arrays are with table=be_users) and exists in $GLOBALS['TYPO3_USER_SETTINGS'][columns]
				foreach ($columns as $field => $config) {
					if (!in_array($field, $fieldList)) {
						continue;
					}
					if ($config['table']) {
						if ($config['table'] == 'be_users' && !in_array($field, array('password', 'password2', 'email', 'realName', 'admin'))) {
							if (!isset($config['access']) || $this->checkAccess($config) && $GLOBALS['BE_USER']->user[$field] !== $d['be_users'][$field]) {
								$storeRec['be_users'][$beUserId][$field] = $d['be_users'][$field];
								$GLOBALS['BE_USER']->user[$field] = $d['be_users'][$field];
							}
						}
					}
					if ($config['type'] == 'check') {
						$GLOBALS['BE_USER']->uc[$field] = isset($d[$field]) ? 1 : 0;
					} else {
						$GLOBALS['BE_USER']->uc[$field] = htmlspecialchars($d[$field]);
					}
				}
				// Personal data for the users be_user-record (email, name, password...)
				// If email and name is changed, set it in the users record:
				$be_user_data = $d['be_users'];
				// Possibility to modify the transmitted values. Useful to do transformations, like RSA password decryption
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/setup/mod/index.php']['modifyUserDataBeforeSave'])) {
					foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/setup/mod/index.php']['modifyUserDataBeforeSave'] as $function) {
						$params = array('be_user_data' => &$be_user_data);
						\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($function, $params, $this);
					}
				}
				$this->passwordIsSubmitted = strlen($be_user_data['password']) > 0;
				$passwordIsConfirmed = $this->passwordIsSubmitted && $be_user_data['password'] === $be_user_data['password2'];
				// Update the real name:
				if ($be_user_data['realName'] !== $GLOBALS['BE_USER']->user['realName']) {
					$GLOBALS['BE_USER']->user['realName'] = ($storeRec['be_users'][$beUserId]['realName'] = substr($be_user_data['realName'], 0, 80));
				}
				// Update the email address:
				if ($be_user_data['email'] !== $GLOBALS['BE_USER']->user['email']) {
					$GLOBALS['BE_USER']->user['email'] = ($storeRec['be_users'][$beUserId]['email'] = substr($be_user_data['email'], 0, 80));
				}
				// Update the password:
				if ($passwordIsConfirmed) {
					$storeRec['be_users'][$beUserId]['password'] = $be_user_data['password2'];
					$this->passwordIsUpdated = TRUE;
				}
				$this->saveData = TRUE;
			}
			// Inserts the overriding values.
			$GLOBALS['BE_USER']->overrideUC();
			$save_after = md5(serialize($GLOBALS['BE_USER']->uc));
			// If something in the uc-array of the user has changed, we save the array...
			if ($save_before != $save_after) {
				$GLOBALS['BE_USER']->writeUC($GLOBALS['BE_USER']->uc);
				$GLOBALS['BE_USER']->writelog(254, 1, 0, 1, 'Personal settings changed', array());
				$this->setupIsUpdated = TRUE;
			}
			// If the temporary data has been cleared, lets make a log note about it
			if ($this->tempDataIsCleared) {
				$GLOBALS['BE_USER']->writelog(254, 1, 0, 1, $GLOBALS['LANG']->getLL('tempDataClearedLog'), array());
			}
			// Persist data if something has changed:
			if (count($storeRec) && $this->saveData) {
				// Make instance of TCE for storing the changes.
				$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
				$tce->stripslashes_values = 0;
				$tce->start($storeRec, array(), $GLOBALS['BE_USER']);
				// This is so the user can actually update his user record.
				$tce->admin = 1;
				// This is to make sure that the users record can be updated even if in another workspace. This is tolerated.
				$tce->bypassWorkspaceRestrictions = TRUE;
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
	 * @return void
	 * @todo Define visibility
	 */
	public function init() {
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
		// id password is disabled, disable repeat of password too (password2)
		if (isset($this->tsFieldConf['password.']) && $this->tsFieldConf['password.']['disabled']) {
			$this->tsFieldConf['password2.']['disabled'] = 1;
		}
		// Create instance of object for output of data
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('templates/setup.html');
		$this->doc->form = '<form action="' . \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('user_setup') . '" method="post" name="usersetup" enctype="application/x-www-form-urlencoded">';
		$this->doc->tableLayout = array(
			'defRow' => array(
				'0' => array('<td class="td-label">', '</td>'),
				'defCol' => array('<td valign="top">', '</td>')
			)
		);
		$this->doc->table_TR = '<tr>';
		$this->doc->table_TABLE = '<table border="0" cellspacing="1" cellpadding="2" class="typo3-usersettings">';
		$this->doc->JScode .= $this->getJavaScript();
	}

	/**
	 * Generate necessary JavaScript
	 *
	 * @return string
	 */
	protected function getJavaScript() {
		$javaScript = '';
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/setup/mod/index.php']['setupScriptHook'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/setup/mod/index.php']['setupScriptHook'] as $function) {
				$params = array();
				$javaScript .= \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($function, $params, $this);
			}
		}
		return $javaScript;
	}

	/**
	 * Generate the main settings formular:
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		global $LANG;
		if ($this->languageUpdate) {
			$this->doc->JScodeArray['languageUpdate'] .= '
				if (top.refreshMenu) {
					top.refreshMenu();
				} else {
					top.TYPO3ModuleMenu.refreshMenu();
				}
			';
		}
		if ($this->pagetreeNeedsRefresh) {
			\TYPO3\CMS\Backend\Utility\BackendUtility::setUpdateSignal('updatePageTree');
		}
		// Start page:
		$this->doc->loadJavascriptLib('md5.js');
		// Use a wrapper div
		$this->content .= '<div id="user-setup-wrapper">';
		// Load available backend modules
		$this->loadModules = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Module\\ModuleLoader');
		$this->loadModules->observeWorkspaces = TRUE;
		$this->loadModules->load($GLOBALS['TBE_MODULES']);
		$this->content .= $this->doc->header($LANG->getLL('UserSettings'));
		// Show if setup was saved
		if ($this->setupIsUpdated && !$this->tempDataIsCleared && !$this->settingsAreResetToDefault) {
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $LANG->getLL('setupWasUpdated'), $LANG->getLL('UserSettings'));
			$this->content .= $flashMessage->render();
		}
		// Show if temporary data was cleared
		if ($this->tempDataIsCleared) {
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $LANG->getLL('tempDataClearedFlashMessage'), $LANG->getLL('tempDataCleared'));
			$this->content .= $flashMessage->render();
		}
		// Show if temporary data was cleared
		if ($this->settingsAreResetToDefault) {
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $LANG->getLL('settingsAreReset'), $LANG->getLL('resetConfiguration'));
			$this->content .= $flashMessage->render();
		}
		// Notice
		if ($this->setupIsUpdated || $this->settingsAreResetToDefault) {
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $LANG->getLL('activateChanges'), '', \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);
			$this->content .= $flashMessage->render();
		}
		// If password is updated, output whether it failed or was OK.
		if ($this->passwordIsSubmitted) {
			if ($this->passwordIsUpdated) {
				$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $LANG->getLL('newPassword_ok'), $LANG->getLL('newPassword'));
			} else {
				$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $LANG->getLL('newPassword_failed'), $LANG->getLL('newPassword'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
			}
			$this->content .= $flashMessage->render();
		}
		// Render the menu items
		$menuItems = $this->renderUserSetup();
		$this->content .= $this->doc->getDynTabMenu($menuItems, 'user-setup', FALSE, FALSE, 1, FALSE, 1, $this->dividers2tabs);
		$formToken = $this->formProtection->generateToken('BE user setup', 'edit');
		$this->content .= $this->doc->section('', '<input type="hidden" name="simUser" value="' . $this->simUser . '" />
			<input type="hidden" name="formToken" value="' . $formToken . '" />
			<input type="hidden" value="1" name="data[save]" />
			<input type="hidden" name="data[setValuesToDefault]" value="0" id="setValuesToDefault" />
			<input type="hidden" name="data[clearSessionVars]" value="0" id="clearSessionVars" />');
		// End of wrapper div
		$this->content .= '</div>';
		// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers['CSH'] = $docHeaderButtons['csh'];
		$markers['CONTENT'] = $this->content;
		// Build the <body> for the module
		$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		// Renders the module page
		$this->content = $this->doc->render($LANG->getLL('UserSettings'), $this->content);
	}

	/**
	 * Prints the content / ends page
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array All available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'csh' => '',
			'save' => '',
			'shortcut' => ''
		);
		$buttons['csh'] = \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('_MOD_user_setup', '', $GLOBALS['BACK_PATH'], '|', TRUE);
		$buttons['save'] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-save', array('html' => '<input type="image" name="data[save]" class="c-inputButton" src="clear.gif" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc', 1) . '" />'));
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('', '', $this->MCONF['name']);
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
	 * @return array Ready to use for the dyntabmenu itemarray
	 */
	protected function renderUserSetup() {
		$result = array();
		$firstTabLabel = '';
		$code = array();
		$i = 0;
		$fieldArray = $this->getFieldsFromShowItem();
		$this->dividers2tabs = isset($GLOBALS['TYPO3_USER_SETTINGS']['ctrl']['dividers2tabs']) ? intval($GLOBALS['TYPO3_USER_SETTINGS']['ctrl']['dividers2tabs']) : 0;
		$tabLabel = '';
		foreach ($fieldArray as $fieldName) {
			$more = '';
			if (substr($fieldName, 0, 8) == '--div--;') {
				if ($firstTabLabel == '') {
					// First tab
					$tabLabel = $this->getLabel(substr($fieldName, 8), '', FALSE);
					$firstTabLabel = $tabLabel;
				} else {
					if ($this->dividers2tabs) {
						$result[] = array(
							'label' => $tabLabel,
							'content' => count($code) ? $this->doc->spacer(20) . $this->doc->table($code) : ''
						);
						$tabLabel = $this->getLabel(substr($fieldName, 8), '', FALSE);
						$i = 0;
						$code = array();
					}
				}
				continue;
			}
			$config = $GLOBALS['TYPO3_USER_SETTINGS']['columns'][$fieldName];
			// Field my be disabled in setup.fields
			if (isset($this->tsFieldConf[$fieldName . '.']['disabled']) && $this->tsFieldConf[$fieldName . '.']['disabled'] == 1) {
				continue;
			}
			if (isset($config['access']) && !$this->checkAccess($config)) {
				continue;
			}
			$label = $this->getLabel($config['label'], $fieldName);
			$label = $this->getCSH($config['csh'] ? $config['csh'] : $fieldName, $label);
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
				$noAutocomplete = $type == 'password' ? 'autocomplete="off" ' : '';
				$html = '<input id="field_' . $fieldName . '"
							type="' . $type . '"
							name="data' . $dataAdd . '[' . $fieldName . ']" ' . $noAutocomplete . 'value="' . htmlspecialchars($value) . '" ' . $GLOBALS['TBE_TEMPLATE']->formWidth(20) . $more . ' />';
				break;
			case 'check':
				if (!$class) {
					$more .= ' class="check"';
				}
				$html = '<input id="field_' . $fieldName . '"
									type="checkbox"
									name="data[' . $fieldName . ']"' . ($value ? ' checked="checked"' : '') . $more . ' />';
				break;
			case 'select':
				if (!$class) {
					$more .= ' class="select"';
				}
				if ($config['itemsProcFunc']) {
					$html = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($config['itemsProcFunc'], $config, $this, '');
				} else {
					$html = '<select ' . $GLOBALS['TBE_TEMPLATE']->formWidth(20) . ' id="field_' . $fieldName . '" name="data[' . $fieldName . ']"' . $more . '>' . LF;
					foreach ($config['items'] as $key => $optionLabel) {
						$html .= '<option value="' . $key . '"' . ($value == $key ? ' selected="selected"' : '') . '>' . $this->getLabel($optionLabel, '', FALSE) . '</option>' . LF;
					}
					$html .= '</select>';
				}
				break;
			case 'user':
				$html = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($config['userFunc'], $config, $this, '');
				break;
			case 'button':
				if ($config['onClick']) {
					$onClick = $config['onClick'];
					if ($config['onClickLabels']) {
						foreach ($config['onClickLabels'] as $key => $labelclick) {
							$config['onClickLabels'][$key] = $this->getLabel($labelclick, '', FALSE);
						}
						$onClick = vsprintf($onClick, $config['onClickLabels']);
					}
					$html = '<input ' . $GLOBALS['TBE_TEMPLATE']->formWidth(20) . ' type="button" value="' . $this->getLabel($config['buttonlabel'], '', FALSE) . '" onclick="' . $onClick . '" />';
				}
				break;
			default:
				$html = '';
			}
			$code[$i][1] = $label;
			$code[$i++][2] = $html;
		}
		if ($this->dividers2tabs == 0) {
			$tabLabel = $firstTabLabel;
		}
		$result[] = array(
			'label' => $tabLabel,
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
	 * @return object The REAL user is returned - the one logged in.
	 */
	protected function getRealScriptUserObj() {
		return is_object($this->OLD_BE_USER) ? $this->OLD_BE_USER : $GLOBALS['BE_USER'];
	}

	/**
	 * Return a select with available languages
	 *
	 * @return string Complete select as HTML string or warning box if something went wrong.
	 */
	public function renderLanguageSelect($params, $pObj) {
		$languageOptions = array();
		// Compile the languages dropdown
		$langDefault = $GLOBALS['LANG']->getLL('lang_default', 1);
		$languageOptions[$langDefault] = '<option value=""' . ($GLOBALS['BE_USER']->uc['lang'] === '' ? ' selected="selected"' : '') . '>' . $langDefault . '</option>';
		// Traverse the number of languages
		/** @var $locales \TYPO3\CMS\Core\Localization\Locales */
		$locales = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Localization\\Locales');
		$languages = $locales->getLanguages();
		foreach ($languages as $locale => $name) {
			if ($locale !== 'default') {
				$defaultName = isset($GLOBALS['LOCAL_LANG']['default']['lang_' . $locale]) ? $GLOBALS['LOCAL_LANG']['default']['lang_' . $locale][0]['source'] : $name;
				$localizedName = $GLOBALS['LANG']->getLL('lang_' . $locale, TRUE);
				if ($localizedName === '') {
					$localizedName = htmlspecialchars($name);
				}
				$localLabel = '  -  [' . htmlspecialchars($defaultName) . ']';
				$available = is_dir(PATH_typo3conf . 'l10n/' . $locale) ? TRUE : FALSE;
				if ($available) {
					$languageOptions[$defaultName] = '<option value="' . $locale . '"' . ($GLOBALS['BE_USER']->uc['lang'] === $locale ? ' selected="selected"' : '') . '>' . $localizedName . $localLabel . '</option>';
				}
			}
		}
		ksort($languageOptions);
		$languageCode = '
				<select ' . $GLOBALS['TBE_TEMPLATE']->formWidth(20) . ' id="field_lang" name="data[lang]" class="select">' . implode('', $languageOptions) . '
				</select>';
		if ($GLOBALS['BE_USER']->uc['lang'] && !@is_dir((PATH_typo3conf . 'l10n/' . $GLOBALS['BE_USER']->uc['lang']))) {
			$languageUnavailableWarning = 'The selected language "' . $GLOBALS['LANG']->getLL(('lang_' . $GLOBALS['BE_USER']->uc['lang']), 1) . '" is not available before the language pack is installed.<br />' . ($GLOBALS['BE_USER']->isAdmin() ? 'You can use the Extension Manager to easily download and install new language packs.' : 'Please ask your system administrator to do this.');
			$languageUnavailableMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $languageUnavailableWarning, '', \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING);
			$languageCode = $languageUnavailableMessage->render() . $languageCode;
		}
		return $languageCode;
	}

	/**
	 * Returns a select with all modules for startup
	 *
	 * @return string Complete select as HTML string
	 */
	public function renderStartModuleSelect($params, $pObj) {
		// Start module select
		if (empty($GLOBALS['BE_USER']->uc['startModule'])) {
			$GLOBALS['BE_USER']->uc['startModule'] = $GLOBALS['BE_USER']->uc_default['startModule'];
		}
		$startModuleSelect = '<option value=""></option>';
		foreach ($pObj->loadModules->modules as $mainMod => $modData) {
			if (isset($modData['sub']) && is_array($modData['sub'])) {
				$startModuleSelect .= '<option disabled="disabled">' . $GLOBALS['LANG']->moduleLabels['tabs'][($mainMod . '_tab')] . '</option>';
				foreach ($modData['sub'] as $subKey => $subData) {
					$modName = $subData['name'];
					$startModuleSelect .= '<option value="' . $modName . '"' . ($GLOBALS['BE_USER']->uc['startModule'] == $modName ? ' selected="selected"' : '') . '>';
					$startModuleSelect .= ' - ' . $GLOBALS['LANG']->moduleLabels['tabs'][($modName . '_tab')] . '</option>';
				}
			}
		}
		return '<select ' . $GLOBALS['TBE_TEMPLATE']->formWidth(20) . 'id="field_startModule" name="data[startModule]" class="select">' . $startModuleSelect . '</select>';
	}

	/**
	 * Will make the simulate-user selector if the logged in user is administrator.
	 * It will also set the GLOBAL(!) BE_USER to the simulated user selected if any (and set $this->OLD_BE_USER to logged in user)
	 *
	 * @return void
	 */
	public function simulateUser() {
		// If admin, allow simulation of another user
		$this->simUser = 0;
		$this->simulateSelector = '';
		unset($this->OLD_BE_USER);
		if ($GLOBALS['BE_USER']->isAdmin()) {
			$this->simUser = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('simUser'));
			// Make user-selector:
			$users = \TYPO3\CMS\Backend\Utility\BackendUtility::getUserNames('username,usergroup,usergroup_cached_list,uid,realName', \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('be_users'));
			$opt = array();
			foreach ($users as $rr) {
				if ($rr['uid'] != $GLOBALS['BE_USER']->user['uid']) {
					$opt[] = '<option value="' . $rr['uid'] . '"' . ($this->simUser == $rr['uid'] ? ' selected="selected"' : '') . '>' . htmlspecialchars(($rr['username'] . ' (' . $rr['realName'] . ')')) . '</option>';
				}
			}
			if (count($opt)) {
				$this->simulateSelector = '<select ' . $GLOBALS['TBE_TEMPLATE']->formWidth(20) . ' id="field_simulate" name="simulateUser" onchange="window.location.href=\'' . \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('user_setup') . '&simUser=\'+this.options[this.selectedIndex].value;"><option></option>' . implode('', $opt) . '</select>';
			}
		}
		// This can only be set if the previous code was executed.
		if ($this->simUser > 0) {
			// Save old user...
			$this->OLD_BE_USER = $GLOBALS['BE_USER'];
			unset($GLOBALS['BE_USER']);
			// Unset current
			// New backend user object
			$BE_USER = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
			$BE_USER->OS = TYPO3_OS;
			$BE_USER->setBeUserByUid($this->simUser);
			$BE_USER->fetchGroupData();
			$BE_USER->backendSetUC();
			// Must do this, because unsetting $BE_USER before apparently unsets the reference to the global variable by this name!
			$GLOBALS['BE_USER'] = $BE_USER;
		}
	}

	/**
	 * Returns a select with simulate users
	 *
	 * @return string Complete select as HTML string
	 */
	public function renderSimulateUserSelect($params, $pObj) {
		return $pObj->simulateSelector;
	}

	/**
	 * Returns access check (currently only "admin" is supported)
	 *
	 * @param array $config Configuration of the field, access mode is defined in key 'access'
	 * @return boolean Whether it is allowed to modify the given field
	 */
	protected function checkAccess(array $config) {
		$access = $config['access'];
		// Check for hook
		$accessObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['setup']['accessLevelCheck'][$access] . ':&' . $access);
		if (is_object($accessObject) && method_exists($accessObject, 'accessLevelCheck')) {
			// Initialize vars. If method fails, $set will be set to FALSE
			return $accessObject->accessLevelCheck($config);
		} elseif ($access == 'admin') {
			return $this->isAdmin;
		}
	}

	/**
	 * Returns the label $str from getLL() and grays out the value if the $str/$key is found in $this->overrideConf array
	 *
	 * @param string $str Locallang key
	 * @param string $key Alternative override-config key
	 * @param boolean $addLabelTag Defines whether the string should be wrapped in a <label> tag.
	 * @param string $altLabelTagId Alternative id for use in "for" attribute of <label> tag. By default the $str key is used prepended with "field_".
	 * @return string HTML output.
	 */
	protected function getLabel($str, $key = '', $addLabelTag = TRUE, $altLabelTagId = '') {
		if (substr($str, 0, 4) == 'LLL:') {
			$out = $GLOBALS['LANG']->sL($str);
		} else {
			$out = htmlspecialchars($str);
		}
		if (isset($this->overrideConf[$key ? $key : $str])) {
			$out = '<span style="color:#999999">' . $out . '</span>';
		}
		if ($addLabelTag) {
			$out = '<label for="' . ($altLabelTagId ? $altLabelTagId : 'field_' . $key) . '">' . $out . '</label>';
		}
		return $out;
	}

	/**
	 * Returns the CSH Icon for given string
	 *
	 * @param string $str Locallang key
	 * @param string $label The label to be used, that should be wrapped in help
	 * @return string HTML output.
	 */
	protected function getCSH($str, $label) {
		$context = '_MOD_user_setup';
		$field = $str;
		$strParts = explode(':', $str);
		if (count($strParts) > 1) {
			// Setting comes from another extension
			$context = $strParts[0];
			$field = $strParts[1];
		} elseif (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList('language,simuser,reset', $str)) {
			$field = 'option_' . $str;
		}
		return \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp($context, $field, $label);
	}

	/**
	 * Returns array with fields defined in $GLOBALS['TYPO3_USER_SETTINGS']['showitem']
	 *
	 * @return array Array with fieldnames visible in form
	 */
	protected function getFieldsFromShowItem() {
		$fieldList = $GLOBALS['TYPO3_USER_SETTINGS']['showitem'];
		// Disable fields depended on settings
		if (!$GLOBALS['TYPO3_CONF_VARS']['BE']['RTEenabled']) {
			$fieldList = \TYPO3\CMS\Core\Utility\GeneralUtility::rmFromList('edit_RTE', $fieldList);
		}
		$fieldArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $fieldList, TRUE);
		return $fieldArray;
	}

}


?>