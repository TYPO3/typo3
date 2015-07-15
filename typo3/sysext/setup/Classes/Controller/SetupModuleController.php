<?php
namespace TYPO3\CMS\Setup\Controller;

/*
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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;

/**
 * Script class for the Setup module
 */
class SetupModuleController {

	const PASSWORD_NOT_UPDATED = 0;
	const PASSWORD_UPDATED = 1;
	const PASSWORD_NOT_THE_SAME = 2;
	const PASSWORD_OLD_WRONG = 3;

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
	 * @var int
	 */
	protected $passwordIsUpdated = self::PASSWORD_NOT_UPDATED;

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
	 * The name of the module
	 *
	 * @var string
	 */
	protected $moduleName = 'user_setup';

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
		$beUser = $this->getBackendUser();
		$beUserId = $beUser->user['uid'];
		$storeRec = array();
		$fieldList = $this->getFieldsFromShowItem();
		if (is_array($d) && $this->formProtection->validateToken((string)GeneralUtility::_POST('formToken'), 'BE user setup', 'edit')) {
			// UC hashed before applying changes
			$save_before = md5(serialize($beUser->uc));
			// PUT SETTINGS into the ->uc array:
			// Reload left frame when switching BE language
			if (isset($d['lang']) && $d['lang'] != $beUser->uc['lang']) {
				$this->languageUpdate = TRUE;
			}
			// Reload pagetree if the title length is changed
			if (isset($d['titleLen']) && $d['titleLen'] !== $beUser->uc['titleLen']) {
				$this->pagetreeNeedsRefresh = TRUE;
			}
			if ($d['setValuesToDefault']) {
				// If every value should be default
				$beUser->resetUC();
				$this->settingsAreResetToDefault = TRUE;
			} elseif ($d['clearSessionVars']) {
				foreach ($beUser->uc as $key => $value) {
					if (!isset($columns[$key])) {
						unset($beUser->uc[$key]);
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
						if ($config['table'] === 'be_users' && !in_array($field, array('password', 'password2', 'passwordCurrent', 'email', 'realName', 'admin'))) {
							if (!isset($config['access']) || $this->checkAccess($config) && $beUser->user[$field] !== $d['be_users'][$field]) {
								if ($config['type'] === 'check') {
									$fieldValue = isset($d['be_users'][$field]) ? 1 : 0;
								} else {
									$fieldValue = $d['be_users'][$field];
								}
								$storeRec['be_users'][$beUserId][$field] = $fieldValue;
								$beUser->user[$field] = $fieldValue;
							}
						}
					}
					if ($config['type'] === 'check') {
						$beUser->uc[$field] = isset($d[$field]) ? 1 : 0;
					} else {
						$beUser->uc[$field] = htmlspecialchars($d[$field]);
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
				$this->passwordIsSubmitted = (string)$be_user_data['password'] !== '';
				$passwordIsConfirmed = $this->passwordIsSubmitted && $be_user_data['password'] === $be_user_data['password2'];
				// Update the real name:
				if ($be_user_data['realName'] !== $beUser->user['realName']) {
					$beUser->user['realName'] = ($storeRec['be_users'][$beUserId]['realName'] = substr($be_user_data['realName'], 0, 80));
				}
				// Update the email address:
				if ($be_user_data['email'] !== $beUser->user['email']) {
					$beUser->user['email'] = ($storeRec['be_users'][$beUserId]['email'] = substr($be_user_data['email'], 0, 80));
				}
				// Update the password:
				if ($passwordIsConfirmed) {
					$currentPasswordHashed = $GLOBALS['BE_USER']->user['password'];
					$saltFactory = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance($currentPasswordHashed);
					if ($saltFactory->checkPassword($be_user_data['passwordCurrent'], $currentPasswordHashed)) {
						$this->passwordIsUpdated = self::PASSWORD_UPDATED;
						$storeRec['be_users'][$beUserId]['password'] = $be_user_data['password'];
					} else {
						$this->passwordIsUpdated = self::PASSWORD_OLD_WRONG;
					}
				} else {
					$this->passwordIsUpdated = self::PASSWORD_NOT_THE_SAME;
				}
				$this->saveData = TRUE;
			}
			// Inserts the overriding values.
			$beUser->overrideUC();
			$save_after = md5(serialize($beUser->uc));
			// If something in the uc-array of the user has changed, we save the array...
			if ($save_before != $save_after) {
				$beUser->writeUC($beUser->uc);
				$beUser->writelog(254, 1, 0, 1, 'Personal settings changed', array());
				$this->setupIsUpdated = TRUE;
			}
			// If the temporary data has been cleared, lets make a log note about it
			if ($this->tempDataIsCleared) {
				$beUser->writelog(254, 1, 0, 1, $this->getLanguageService()->getLL('tempDataClearedLog'), array());
			}
			// Persist data if something has changed:
			if (count($storeRec) && $this->saveData) {
				// Make instance of TCE for storing the changes.
				$tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
				$tce->stripslashes_values = 0;
				// This is so the user can actually update his user record.
				$isAdmin = $beUser->user['admin'];
				$beUser->user['admin'] = 1;
				$tce->start($storeRec, array(), $beUser);
				// This is to make sure that the users record can be updated even if in another workspace. This is tolerated.
				$tce->bypassWorkspaceRestrictions = TRUE;
				$tce->process_datamap();
				unset($tce);
				if ($this->passwordIsUpdated === self::PASSWORD_NOT_UPDATED || count($storeRec['be_users'][$beUserId]) > 1) {
					$this->setupIsUpdated = TRUE;
				}
				// Restore admin status after processing
				$beUser->user['admin'] = $isAdmin;
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
		$this->getLanguageService()->includeLLFile('EXT:setup/mod/locallang.xlf');

		// Returns the script user - that is the REAL logged in user! ($GLOBALS[BE_USER] might be another user due to simulation!)
		$scriptUser = $this->getRealScriptUserObj();

		$this->isAdmin = $scriptUser->isAdmin();
		// Getting the 'override' values as set might be set in User TSconfig
		$this->overrideConf = $this->getBackendUser()->getTSConfigProp('setup.override');
		// Getting the disabled fields might be set in User TSconfig (eg setup.fields.password.disabled=1)
		$this->tsFieldConf = $this->getBackendUser()->getTSConfigProp('setup.fields');
		// id password is disabled, disable repeat of password too (password2)
		if (isset($this->tsFieldConf['password.']) && $this->tsFieldConf['password.']['disabled']) {
			$this->tsFieldConf['password2.']['disabled'] = 1;
			$this->tsFieldConf['passwordCurrent.']['disabled'] = 1;
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
		$this->content .= $this->doc->header($this->getLanguageService()->getLL('UserSettings'));
		// Show if setup was saved
		if ($this->setupIsUpdated && !$this->tempDataIsCleared && !$this->settingsAreResetToDefault) {
			$flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $this->getLanguageService()->getLL('setupWasUpdated'), $this->getLanguageService()->getLL('UserSettings'));
			$this->content .= $flashMessage->render();
		}
		// Show if temporary data was cleared
		if ($this->tempDataIsCleared) {
			$flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $this->getLanguageService()->getLL('tempDataClearedFlashMessage'), $this->getLanguageService()->getLL('tempDataCleared'));
			$this->content .= $flashMessage->render();
		}
		// Show if temporary data was cleared
		if ($this->settingsAreResetToDefault) {
			$flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $this->getLanguageService()->getLL('settingsAreReset'), $this->getLanguageService()->getLL('resetConfiguration'));
			$this->content .= $flashMessage->render();
		}
		// Notice
		if ($this->setupIsUpdated || $this->settingsAreResetToDefault) {
			$flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $this->getLanguageService()->getLL('activateChanges'), '', FlashMessage::INFO);
			$this->content .= $flashMessage->render();
		}
		// If password is updated, output whether it failed or was OK.
		if ($this->passwordIsSubmitted) {
			$flashMessage = NULL;
			switch ($this->passwordIsUpdated) {
				case self::PASSWORD_OLD_WRONG:
					$flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $this->getLanguageService()->getLL('oldPassword_failed'), $this->getLanguageService()->getLL('newPassword'), FlashMessage::ERROR);
					break;
				case self::PASSWORD_NOT_THE_SAME:
					$flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $this->getLanguageService()->getLL('newPassword_failed'), $this->getLanguageService()->getLL('newPassword'), FlashMessage::ERROR);
					break;
				case self::PASSWORD_UPDATED:
					$flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $this->getLanguageService()->getLL('newPassword_ok'), $this->getLanguageService()->getLL('newPassword'));
					break;
			}
			if ($flashMessage) {
				$this->content .= $flashMessage->render();
			}
		}

		// Render user switch
		$this->content .= $this->renderSimulateUserSelectAndLabel();

		// Render the menu items
		$menuItems = $this->renderUserSetup();
		$this->content .= $this->doc->getDynamicTabMenu($menuItems, 'user-setup', 1, FALSE, FALSE);
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
		$this->content = $this->doc->render($this->getLanguageService()->getLL('UserSettings'), $this->content);
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
		$buttons['save'] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-save', array('html' => '<input type="image" name="data[save]" class="c-inputButton" src="clear.gif" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc', TRUE) . '" />'));
		if ($this->getBackendUser()->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('', '', $this->moduleName);
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
			$value = $config['table'] === 'be_users' ? $this->getBackendUser()->user[$fieldName] : $this->getBackendUser()->uc[$fieldName];
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
				case 'password':
					$noAutocomplete = '';
					if ($type === 'password') {
						$value = '';
						$noAutocomplete = 'autocomplete="off" ';
						$more .= ' data-rsa-encryption=""';
					}
					$html = '<input id="field_' . $fieldName . '"
						type="' . $type . '"
						name="data' . $dataAdd . '[' . $fieldName . ']" ' .
						$noAutocomplete .
						'value="' . htmlspecialchars($value) . '" ' .
						$more .
						' />';
					break;
				case 'check':
					$html = $label . '<div class="checkbox"><label><input id="field_' . $fieldName . '"
						type="checkbox"
						name="data' . $dataAdd . '[' . $fieldName . ']"' .
						($value ? ' checked="checked"' : '') .
						$more .
						' /></label></div>';
					$label = '';
					break;
				case 'select':
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
				case 'user':
					$html = GeneralUtility::callUserFunction($config['userFunc'], $config, $this, '');
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
						$html = '<br><input class="btn btn-default" type="button"
							value="' . $this->getLabel($config['buttonlabel'], '', FALSE) . '"
							onclick="' . $onClick . '" />';
					}
					break;
				default:
					$html = '';
			}

			$code[] = '<div class="form-section"><div class="form-group">' .
				$label .
				$html .
				'</div></div>';
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
	 * @return BackendUserAuthentication The REAL user is returned - the one logged in.
	 */
	protected function getRealScriptUserObj() {
		return is_object($this->OLD_BE_USER) ? $this->OLD_BE_USER : $this->getBackendUser();
	}

	/**
	 * Return a select with available languages
	 *
	 * @return string Complete select as HTML string or warning box if something went wrong.
	 */
	public function renderLanguageSelect($params, $pObj) {
		$languageOptions = array();
		// Compile the languages dropdown
		$langDefault = $this->getLanguageService()->getLL('lang_default', TRUE);
		$languageOptions[$langDefault] = '<option value=""' . ($this->getBackendUser()->uc['lang'] === '' ? ' selected="selected"' : '') . '>' . $langDefault . '</option>';
		// Traverse the number of languages
		/** @var $locales \TYPO3\CMS\Core\Localization\Locales */
		$locales = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Localization\Locales::class);
		$languages = $locales->getLanguages();
		foreach ($languages as $locale => $name) {
			if ($locale !== 'default') {
				$defaultName = isset($GLOBALS['LOCAL_LANG']['default']['lang_' . $locale]) ? $GLOBALS['LOCAL_LANG']['default']['lang_' . $locale][0]['source'] : $name;
				$localizedName = $this->getLanguageService()->getLL('lang_' . $locale, TRUE);
				if ($localizedName === '') {
					$localizedName = htmlspecialchars($name);
				}
				$localLabel = '  -  [' . htmlspecialchars($defaultName) . ']';
				$available = is_dir(PATH_typo3conf . 'l10n/' . $locale);
				if ($available) {
					$languageOptions[$defaultName] = '<option value="' . $locale . '"' . ($this->getBackendUser()->uc['lang'] === $locale ? ' selected="selected"' : '') . '>' . $localizedName . $localLabel . '</option>';
				}
			}
		}
		ksort($languageOptions);
		$languageCode = '
				<select id="field_lang" name="data[lang]" class="form-control">' . implode('', $languageOptions) . '
				</select>';
		if ($this->getBackendUser()->uc['lang'] && !@is_dir((PATH_typo3conf . 'l10n/' . $this->getBackendUser()->uc['lang']))) {
			$languageUnavailableWarning = 'The selected language "' . $this->getLanguageService()->getLL(('lang_' . $this->getBackendUser()->uc['lang']), TRUE) . '" is not available before the language files are installed.<br />' . ($this->getBackendUser()->isAdmin() ? 'You can use the Language module to easily download new language files.' : 'Please ask your system administrator to do this.');
			$languageUnavailableMessage = GeneralUtility::makeInstance(FlashMessage::class, $languageUnavailableWarning, '', FlashMessage::WARNING);
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
		$startModuleSelect = '<option value="">' . $this->getLanguageService()->getLL('startModule.firstInMenu', TRUE) . '</option>';
		foreach ($pObj->loadModules->modules as $mainMod => $modData) {
			if (!empty($modData['sub']) && is_array($modData['sub'])) {
				$modules = '';
				foreach ($modData['sub'] as $subData) {
					$modName = $subData['name'];
					$modules .= '<option value="' . htmlspecialchars($modName) . '"';
					$modules .= $this->getBackendUser()->uc['startModule'] === $modName ? ' selected="selected"' : '';
					$modules .=  '>' . $this->getLanguageService()->moduleLabels['tabs'][$modName . '_tab'] . '</option>';
				}
				$groupLabel = $this->getLanguageService()->moduleLabels['tabs'][$mainMod . '_tab'];
				$startModuleSelect .= '<optgroup label="' . htmlspecialchars($groupLabel) . '">' . $modules . '</optgroup>';
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
		if ($this->getBackendUser()->isAdmin()) {
			$this->simUser = (int)GeneralUtility::_GP('simUser');
			// Make user-selector:
			$db = $this->getDatabaseConnection();
			$where = 'AND username NOT LIKE ' . $db->fullQuoteStr($db->escapeStrForLike('_cli_', 'be_users') . '%', 'be_users');
			$where .= ' AND uid <> ' . (int)$this->getBackendUser()->user['uid'] . BackendUtility::BEenableFields('be_users');
			$users = BackendUtility::getUserNames('username,usergroup,usergroup_cached_list,uid,realName', $where);
			$opt = array();
			foreach ($users as $rr) {
				$label = htmlspecialchars(($rr['username'] . ($rr['realName'] ? ' (' . $rr['realName'] . ')' : '')));
				$opt[] = '<option value="' . $rr['uid'] . '"' . ($this->simUser == $rr['uid'] ? ' selected="selected"' : '') . '>' . $label . '</option>';
			}
			if (!empty($opt)) {
				$this->simulateSelector = '<select id="field_simulate" name="simulateUser" onchange="window.location.href=' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('user_setup') . '&simUser=') . '+this.options[this.selectedIndex].value;"><option></option>' . implode('', $opt) . '</select>';
			}
		}
		// This can only be set if the previous code was executed.
		if ($this->simUser > 0) {
			// Save old user...
			$this->OLD_BE_USER = $this->getBackendUser();
			unset($GLOBALS['BE_USER']);
			// Unset current
			// New backend user object
			$BE_USER = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class);
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
			$this->getLanguageService()->sL('LLL:EXT:setup/mod/locallang.xlf:simulate') .
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

		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['setup']['accessLevelCheck'][$access])) {
			if (class_exists($access)) {
				$accessObject = GeneralUtility::makeInstance($access);
				if (method_exists($accessObject, 'accessLevelCheck')) {
					// Initialize vars. If method fails, $set will be set to FALSE
					return $accessObject->accessLevelCheck($config);
				}
			}
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
			$out = $this->getLanguageService()->sL($str);
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
		return GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_USER_SETTINGS']['showitem'], TRUE);
	}

	/**
	 * Returns the current BE user.
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * Returns LanguageService
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * @return DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
