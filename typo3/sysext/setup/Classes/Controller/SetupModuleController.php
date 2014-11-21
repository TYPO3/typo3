<?php
namespace TYPO3\CMS\Setup\Controller;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script class for the Setup module
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class SetupModuleController {

	/**
	 * @var array
	 */
	public $MCONF = array();

	/**
	 * @var array
	 */
	public $MOD_MENU = array();

	/**
	 * @var array
	 */
	public $MOD_SETTINGS = array();

	/**
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	public $doc;

	/**
	 * @var string
	 */
	public $content;

	/**
	 * @var array
	 */
	public $overrideConf;

	/**
	 * backend user object, set during simulate-user operation
	 *
	 * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	public $OLD_BE_USER;

	/**
	 * @var bool
	 */
	public $languageUpdate;

	/**
	 * @var bool
	 */
	protected $pagetreeNeedsRefresh = FALSE;

	/**
	 * @var bool
	 */
	protected $isAdmin;

	/**
	 * @var array
	 */
	protected $tsFieldConf;

	/**
	 * @var bool
	 */
	protected $saveData = FALSE;

	/**
	 * @var bool
	 */
	protected $passwordIsUpdated = FALSE;

	/**
	 * @var bool
	 */
	protected $passwordIsSubmitted = FALSE;

	/**
	 * @var bool
	 */
	protected $setupIsUpdated = FALSE;

	/**
	 * @var bool
	 */
	protected $tempDataIsCleared = FALSE;

	/**
	 * @var bool
	 */
	protected $settingsAreResetToDefault = FALSE;

	/**
	 * Form protection instance
	 *
	 * @var \TYPO3\CMS\Core\FormProtection\BackendFormProtection
	 */
	protected $formProtection;

	/**
	 * @var string
	 */
	protected $simulateSelector = '';

	/**
	 * @var string
	 */
	protected $simUser = '';

	/**
	 * Instantiate the form protection before a simulated user is initialized.
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
	 * NOTICE: This method is called before the \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * is included. See bottom of document.
	 *
	 * @see \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	public function storeIncomingData() {
		// First check if something is submitted in the data-array from POST vars
		$d = GeneralUtility::_POST('data');
		$columns = $GLOBALS['TYPO3_USER_SETTINGS']['columns'];
		$beUserId = $GLOBALS['BE_USER']->user['uid'];
		$storeRec = array();
		$fieldList = $this->getFieldsFromShowItem();
		if (is_array($d) && $this->formProtection->validateToken((string)GeneralUtility::_POST('formToken'), 'BE user setup', 'edit')) {
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
						if ($config['table'] === 'be_users' && !in_array($field, array('password', 'password2', 'email', 'realName', 'admin'))) {
							if (!isset($config['access']) || $this->checkAccess($config) && $GLOBALS['BE_USER']->user[$field] !== $d['be_users'][$field]) {
								if ($config['type'] === 'check') {
									$fieldValue = isset($d['be_users'][$field]) ? 1 : 0;
								} else {
									$fieldValue = $d['be_users'][$field];
								}
								$storeRec['be_users'][$beUserId][$field] = $fieldValue;
								$GLOBALS['BE_USER']->user[$field] = $fieldValue;
							}
						}
					}
					if ($config['type'] === 'check') {
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
						GeneralUtility::callUserFunction($function, $params, $this);
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
				$tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
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
	 */
	public function init() {
		$GLOBALS['LANG']->includeLLFile('EXT:setup/mod/locallang.xlf');
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
		$this->doc = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('EXT:setup/Resources/Private/Templates/setup.html');
		$this->doc->form = '<form action="' . BackendUtility::getModuleUrl('user_setup') . '" method="post" name="usersetup" enctype="application/x-www-form-urlencoded">';
		$this->doc->addStyleSheet('module', 'sysext/setup/Resources/Public/Styles/styles.css');
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
				$javaScript .= GeneralUtility::callUserFunction($function, $params, $this);
			}
		}
		return $javaScript;
	}

	/**
	 * Generate the main settings form:
	 *
	 * @return void
	 */
	public function main() {
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
			BackendUtility::setUpdateSignal('updatePageTree');
		}
		// Start page:
		$this->doc->loadJavascriptLib('sysext/backend/Resources/Public/JavaScript/md5.js');
		// Use a wrapper div
		$this->content .= '<div id="user-setup-wrapper">';
		// Load available backend modules
		$this->loadModules = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Module\ModuleLoader::class);
		$this->loadModules->observeWorkspaces = TRUE;
		$this->loadModules->load($GLOBALS['TBE_MODULES']);
		$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('UserSettings'));
		// Show if setup was saved
		if ($this->setupIsUpdated && !$this->tempDataIsCleared && !$this->settingsAreResetToDefault) {
			$flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $GLOBALS['LANG']->getLL('setupWasUpdated'), $GLOBALS['LANG']->getLL('UserSettings'));
			$this->content .= $flashMessage->render();
		}
		// Show if temporary data was cleared
		if ($this->tempDataIsCleared) {
			$flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $GLOBALS['LANG']->getLL('tempDataClearedFlashMessage'), $GLOBALS['LANG']->getLL('tempDataCleared'));
			$this->content .= $flashMessage->render();
		}
		// Show if temporary data was cleared
		if ($this->settingsAreResetToDefault) {
			$flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $GLOBALS['LANG']->getLL('settingsAreReset'), $GLOBALS['LANG']->getLL('resetConfiguration'));
			$this->content .= $flashMessage->render();
		}
		// Notice
		if ($this->setupIsUpdated || $this->settingsAreResetToDefault) {
			$flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $GLOBALS['LANG']->getLL('activateChanges'), '', \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);
			$this->content .= $flashMessage->render();
		}
		// If password is updated, output whether it failed or was OK.
		if ($this->passwordIsSubmitted) {
			if ($this->passwordIsUpdated) {
				$flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $GLOBALS['LANG']->getLL('newPassword_ok'), $GLOBALS['LANG']->getLL('newPassword'));
			} else {
				$flashMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $GLOBALS['LANG']->getLL('newPassword_failed'), $GLOBALS['LANG']->getLL('newPassword'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
			}
			$this->content .= $flashMessage->render();
		}

		// Render user switch
		$this->content .= $this->renderSimulateUserSelectAndLabel();

		// Render the menu items
		$menuItems = $this->renderUserSetup();
		$this->content .= $this->doc->getDynTabMenu($menuItems, 'user-setup', FALSE, FALSE, 1, FALSE, 1, 1);
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
		$this->content = $this->doc->render($GLOBALS['LANG']->getLL('UserSettings'), $this->content);
	}

	/**
	 * Prints the content / ends page
	 *
	 * @return void
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
		$buttons['csh'] = BackendUtility::cshItem('_MOD_user_setup', '');
		$buttons['save'] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-save', array('html' => '<input type="image" name="data[save]" class="c-inputButton" src="clear.gif" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc', TRUE) . '" />'));
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
		$tabLabel = '';
		foreach ($fieldArray as $fieldName) {
			$more = '';
			if (substr($fieldName, 0, 8) === '--div--;') {
				if ($firstTabLabel === '') {
					// First tab
					$tabLabel = $this->getLabel(substr($fieldName, 8), '', FALSE);
					$firstTabLabel = $tabLabel;
				} else {
					$result[] = array(
						'label' => $tabLabel,
						'content' => count($code) ? implode(LF, $code) : ''
					);
					$tabLabel = $this->getLabel(substr($fieldName, 8), '', FALSE);
					$i = 0;
					$code = array();
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
			$label = $this->getCSH($config['csh'] ?: $fieldName, $label);
			$type = $config['type'];
			$class = $config['class'];

			if ($type !== 'check') {
				$class .= ' form-control';
			}

			$style = $config['style'];
			if ($class) {
				$more .= ' class="' . $class . '"';
			}
			if ($style) {
				$more .= ' style="' . $style . '"';
			}
			if (isset($this->overrideConf[$fieldName])) {
				$more .= ' disabled="disabled"';
			}
			$value = $config['table'] === 'be_users' ? $GLOBALS['BE_USER']->user[$fieldName] : $GLOBALS['BE_USER']->uc[$fieldName];
			if (!$value && isset($config['default'])) {
				$value = $config['default'];
			}
			$dataAdd = '';
			if ($config['table'] === 'be_users') {
				$dataAdd = '[be_users]';
			}

			switch ($type) {
				case 'text':
				case 'email':
				case 'password': {
					$noAutocomplete = '';
					if ($type === 'password') {
						$value = '';
						$noAutocomplete = 'autocomplete="off" ';
					}
					$html = '<input id="field_' . $fieldName . '"
						type="' . $type . '"
						name="data' . $dataAdd . '[' . $fieldName . ']" ' .
						$noAutocomplete .
						'value="' . htmlspecialchars($value) . '" ' .
						$more .
						' />';
					break;
				}
				case 'check': {
					$html = $label . '<div class="checkbox"><label><input id="field_' . $fieldName . '"
						type="checkbox"
						name="data' . $dataAdd . '[' . $fieldName . ']"' .
						($value ? ' checked="checked"' : '') .
						$more .
						' /></label></div>';
					$label = '';
					break;
				}
				case 'select': {
					if ($config['itemsProcFunc']) {
						$html = GeneralUtility::callUserFunction($config['itemsProcFunc'], $config, $this, '');
					} else {
						$html = '<select id="field_' . $fieldName . '"
							name="data' . $dataAdd . '[' . $fieldName . ']"' .
							$more . '>' . LF;
						foreach ($config['items'] as $key => $optionLabel) {
							$html .= '<option value="' . $key . '"' . ($value == $key ? ' selected="selected"' : '') . '>' . $this->getLabel($optionLabel, '', FALSE) . '</option>' . LF;
						}
						$html .= '</select>';
					}
					break;
				}
				case 'user': {
					$html = GeneralUtility::callUserFunction($config['userFunc'], $config, $this, '');
					break;
				}
				case 'button': {
					if ($config['onClick']) {
						$onClick = $config['onClick'];
						if ($config['onClickLabels']) {
							foreach ($config['onClickLabels'] as $key => $labelclick) {
								$config['onClickLabels'][$key] = $this->getLabel($labelclick, '', FALSE);
							}
							$onClick = vsprintf($onClick, $config['onClickLabels']);
						}
						$html = '<br><input type="button"
							value="' . $this->getLabel($config['buttonlabel'], '', FALSE) . '"
							onclick="' . $onClick . '" />';
					}
					break;
				}
				default:
					$html = '';
			}

			$code[] = '<div class="form-group">' .
				$label .
				$html .
				'</div>';
		}

		$result[] = array(
			'label' => $tabLabel,
			'content' => count($code) ? implode(LF, $code) : ''
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
		$langDefault = $GLOBALS['LANG']->getLL('lang_default', TRUE);
		$languageOptions[$langDefault] = '<option value=""' . ($GLOBALS['BE_USER']->uc['lang'] === '' ? ' selected="selected"' : '') . '>' . $langDefault . '</option>';
		// Traverse the number of languages
		/** @var $locales \TYPO3\CMS\Core\Localization\Locales */
		$locales = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Localization\Locales::class);
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
				<select id="field_lang" name="data[lang]" class="form-control">' . implode('', $languageOptions) . '
				</select>';
		if ($GLOBALS['BE_USER']->uc['lang'] && !@is_dir((PATH_typo3conf . 'l10n/' . $GLOBALS['BE_USER']->uc['lang']))) {
			$languageUnavailableWarning = 'The selected language "' . $GLOBALS['LANG']->getLL(('lang_' . $GLOBALS['BE_USER']->uc['lang']), TRUE) . '" is not available before the language pack is installed.<br />' . ($GLOBALS['BE_USER']->isAdmin() ? 'You can use the Extension Manager to easily download and install new language packs.' : 'Please ask your system administrator to do this.');
			$languageUnavailableMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class, $languageUnavailableWarning, '', \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING);
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
		return '<select id="field_startModule" name="data[startModule]" class="form-control">' . $startModuleSelect . '</select>';
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
			$this->simUser = (int)GeneralUtility::_GP('simUser');
			// Make user-selector:
			$users = BackendUtility::getUserNames('username,usergroup,usergroup_cached_list,uid,realName', BackendUtility::BEenableFields('be_users'));
			$opt = array();
			foreach ($users as $rr) {
				if ($rr['uid'] != $GLOBALS['BE_USER']->user['uid']) {
					$label = htmlspecialchars(($rr['username'] . ($rr['realName'] ? ' (' . $rr['realName'] . ')' : '')));
					$opt[] = '<option value="' . $rr['uid'] . '"' . ($this->simUser == $rr['uid'] ? ' selected="selected"' : '') . '>' . $label . '</option>';
				}
			}
			if (count($opt)) {
				$this->simulateSelector = '<select id="field_simulate" name="simulateUser" onchange="window.location.href=\'' . BackendUtility::getModuleUrl('user_setup') . '&simUser=\'+this.options[this.selectedIndex].value;"><option></option>' . implode('', $opt) . '</select>';
			}
		}
		// This can only be set if the previous code was executed.
		if ($this->simUser > 0) {
			// Save old user...
			$this->OLD_BE_USER = $GLOBALS['BE_USER'];
			unset($GLOBALS['BE_USER']);
			// Unset current
			// New backend user object
			$BE_USER = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class);
			$BE_USER->OS = TYPO3_OS;
			$BE_USER->setBeUserByUid($this->simUser);
			$BE_USER->fetchGroupData();
			$BE_USER->backendSetUC();
			// Must do this, because unsetting $BE_USER before apparently unsets the reference to the global variable by this name!
			$GLOBALS['BE_USER'] = $BE_USER;
		}
	}

	/**
	 * Render simulate user select and label
	 *
	 * @return string
	 */
	protected function renderSimulateUserSelectAndLabel() {
		if ($this->simulateSelector === '') {
			return '';
		}

		return '<p>' .
			'<label for="field_simulate" style="margin-right: 20px;">' .
			$GLOBALS['LANG']->sL('LLL:EXT:setup/mod/locallang.xlf:simulate') .
			'</label>' .
			$this->simulateSelector .
			'</p>';
	}

	/**
	 * Returns access check (currently only "admin" is supported)
	 *
	 * @param array $config Configuration of the field, access mode is defined in key 'access'
	 * @return bool Whether it is allowed to modify the given field
	 */
	protected function checkAccess(array $config) {
		$access = $config['access'];
		// Check for hook
		$accessObject = GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['setup']['accessLevelCheck'][$access] . ':&' . $access);
		if (is_object($accessObject) && method_exists($accessObject, 'accessLevelCheck')) {
			// Initialize vars. If method fails, $set will be set to FALSE
			return $accessObject->accessLevelCheck($config);
		} elseif ($access == 'admin') {
			return $this->isAdmin;
		}

		return FALSE;
	}

	/**
	 * Returns the label $str from getLL() and grays out the value if the $str/$key is found in $this->overrideConf array
	 *
	 * @param string $str Locallang key
	 * @param string $key Alternative override-config key
	 * @param bool $addLabelTag Defines whether the string should be wrapped in a <label> tag.
	 * @param string $altLabelTagId Alternative id for use in "for" attribute of <label> tag. By default the $str key is used prepended with "field_".
	 * @return string HTML output.
	 */
	protected function getLabel($str, $key = '', $addLabelTag = TRUE, $altLabelTagId = '') {
		if (substr($str, 0, 4) === 'LLL:') {
			$out = $GLOBALS['LANG']->sL($str);
		} else {
			$out = htmlspecialchars($str);
		}
		if (isset($this->overrideConf[$key ?: $str])) {
			$out = '<span style="color:#999999">' . $out . '</span>';
		}
		if ($addLabelTag) {
			$out = '<label for="' . ($altLabelTagId ?: 'field_' . $key) . '">' . $out . '</label>';
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
		} elseif (!GeneralUtility::inList('language,simuser,reset', $str)) {
			$field = 'option_' . $str;
		}
		return BackendUtility::wrapInHelp($context, $field, $label);
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
			$fieldList = GeneralUtility::rmFromList('edit_RTE', $fieldList);
		}
		$fieldArray = GeneralUtility::trimExplode(',', $fieldList, TRUE);
		return $fieldArray;
	}

}
