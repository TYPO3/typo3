<?php
namespace TYPO3\CMS\Lowlevel\View;

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
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Script class for the Config module
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ConfigurationView {

	/**
	 * @var \TYPO3\CMS\Fluid\View\StandaloneView
	 */
	protected $view;

	/**
	 * @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue
	 */
	protected $flashMessageQueue;

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
	 * Document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	public $doc;

	/**
	 * @var array
	 */
	public $include_once = array();

	/**
	 * @var string
	 */
	public $content;

	/**
	 * Constructor
	 */
	public function __construct() {
		$GLOBALS['BE_USER']->modAccess($GLOBALS['MCONF'], 1);
		$this->view = GeneralUtility::makeInstance('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
		$this->view->getRequest()->setControllerExtensionName('lowlevel');
	}

	/**
	 * Initialization
	 *
	 * @return void
	 */
	public function init() {
		$this->MCONF = $GLOBALS['MCONF'];
		$this->menuConfig();
		$this->doc = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('EXT:lowlevel/Resources/Private/Templates/config.html');
		$this->doc->form = '<form action="" method="post">';
	}

	/**
	 * Menu Configuration
	 *
	 * @return void
	 */
	public function menuConfig() {
		// MENU-ITEMS:
		// If array, then it's a selector box menu
		// If empty string it's just a variable, that'll be saved.
		// Values NOT in this array will not be saved in the settings-array for the module.
		$this->MOD_MENU = array(
			'function' => array(
				0 => LocalizationUtility::translate('typo3ConfVars', 'lowlevel'),
				1 => LocalizationUtility::translate('tca', 'lowlevel'),
				2 => LocalizationUtility::translate('tcaDescr', 'lowlevel'),
				3 => LocalizationUtility::translate('loadedExt', 'lowlevel'),
				4 => LocalizationUtility::translate('t3services', 'lowlevel'),
				5 => LocalizationUtility::translate('tbemodules', 'lowlevel'),
				6 => LocalizationUtility::translate('tbemodulesext', 'lowlevel'),
				7 => LocalizationUtility::translate('tbeStyles', 'lowlevel'),
				8 => LocalizationUtility::translate('beUser', 'lowlevel'),
				9 => LocalizationUtility::translate('usersettings', 'lowlevel')
			),
			'regexsearch' => '',
			'fixedLgd' => ''
		);
		// CLEANSE SETTINGS
		$this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, GeneralUtility::_GP('SET'), $this->MCONF['name']);
	}

	/**
	 * [Describe function...]
	 *
	 * @return void
	 */
	public function main() {
		$arrayBrowser = GeneralUtility::makeInstance('TYPO3\\CMS\\Lowlevel\\Utility\\ArrayBrowser');
		$label = $this->MOD_MENU['function'][$this->MOD_SETTINGS['function']];
		$search_field = GeneralUtility::_GP('search_field');

		$templatePathAndFilename = GeneralUtility::getFileAbsFileName('EXT:lowlevel/Resources/Private/Templates/Backend/Configuration.html');
		$this->view->setTemplatePathAndFilename($templatePathAndFilename);
		$this->view->assign('label', $label);
		$this->view->assign('search_field', $search_field);
		$this->view->assign('checkbox_checkRegexsearch', BackendUtility::getFuncCheck(0, 'SET[regexsearch]', $this->MOD_SETTINGS['regexsearch'], '', '', 'id="checkRegexsearch"'));
		$this->view->assign('checkbox_checkFixedLgd', BackendUtility::getFuncCheck(0, 'SET[fixedLgd]', $this->MOD_SETTINGS['fixedLgd'], '', '', 'id="checkFixedLgd"'));

		switch ($this->MOD_SETTINGS['function']) {
			case 0:
				$theVar = $GLOBALS['TYPO3_CONF_VARS'];
				GeneralUtility::naturalKeySortRecursive($theVar);
				$arrayBrowser->varName = '$TYPO3_CONF_VARS';
				break;
			case 1:
				$theVar = $GLOBALS['TCA'];
				GeneralUtility::naturalKeySortRecursive($theVar);
				$arrayBrowser->varName = '$TCA';
				break;
			case 2:
				$theVar = $GLOBALS['TCA_DESCR'];
				GeneralUtility::naturalKeySortRecursive($theVar);
				$arrayBrowser->varName = '$TCA_DESCR';
				break;
			case 3:
				$theVar = $GLOBALS['TYPO3_LOADED_EXT'];
				GeneralUtility::naturalKeySortRecursive($theVar);
				$arrayBrowser->varName = '$TYPO3_LOADED_EXT';
				break;
			case 4:
				$theVar = $GLOBALS['T3_SERVICES'];
				GeneralUtility::naturalKeySortRecursive($theVar);
				$arrayBrowser->varName = '$T3_SERVICES';
				break;
			case 5:
				$theVar = $GLOBALS['TBE_MODULES'];
				GeneralUtility::naturalKeySortRecursive($theVar);
				$arrayBrowser->varName = '$TBE_MODULES';
				break;
			case 6:
				$theVar = $GLOBALS['TBE_MODULES_EXT'];
				GeneralUtility::naturalKeySortRecursive($theVar);
				$arrayBrowser->varName = '$TBE_MODULES_EXT';
				break;
			case 7:
				$theVar = $GLOBALS['TBE_STYLES'];
				GeneralUtility::naturalKeySortRecursive($theVar);
				$arrayBrowser->varName = '$TBE_STYLES';
				break;
			case 8:
				$theVar = $GLOBALS['BE_USER']->uc;
				GeneralUtility::naturalKeySortRecursive($theVar);
				$arrayBrowser->varName = '$BE_USER->uc';
				break;
			case 9:
				$theVar = $GLOBALS['TYPO3_USER_SETTINGS'];
				GeneralUtility::naturalKeySortRecursive($theVar);
				$arrayBrowser->varName = '$TYPO3_USER_SETTINGS';
				break;
			default:
				$theVar = array();
		}
		// Update node:
		$update = 0;
		$node = GeneralUtility::_GET('node');
		// If any plus-signs were clicked, it's registred.
		if (is_array($node)) {
			$this->MOD_SETTINGS['node_' . $this->MOD_SETTINGS['function']] = $arrayBrowser->depthKeys($node, $this->MOD_SETTINGS['node_' . $this->MOD_SETTINGS['function']]);
			$update = 1;
		}
		if ($update) {
			$GLOBALS['BE_USER']->pushModuleData($this->MCONF['name'], $this->MOD_SETTINGS);
		}
		$arrayBrowser->depthKeys = $this->MOD_SETTINGS['node_' . $this->MOD_SETTINGS['function']];
		$arrayBrowser->regexMode = $this->MOD_SETTINGS['regexsearch'];
		$arrayBrowser->fixedLgd = $this->MOD_SETTINGS['fixedLgd'];
		$arrayBrowser->searchKeysToo = TRUE;

		// If any POST-vars are send, update the condition array
		if (GeneralUtility::_POST('search') && trim($search_field)) {
			$arrayBrowser->depthKeys = $arrayBrowser->getSearchKeys($theVar, '', $search_field, array());
		}
		// mask the encryption key to not show it as plaintext in the configuration module
		if ($theVar == $GLOBALS['TYPO3_CONF_VARS']) {
			$theVar['SYS']['encryptionKey'] = '***** (length: ' . strlen($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']) . ' characters)';
		}
		$tree = $arrayBrowser->tree($theVar, '', '');
		$this->view->assign('tree', $tree);
		// Variable name:
		if (GeneralUtility::_GP('varname')) {
			$this->view->assign('varname', TRUE);
			$line = GeneralUtility::_GP('_') ? GeneralUtility::_GP('_') : GeneralUtility::_GP('varname');
			// Write the line to extTables.php
			if (GeneralUtility::_GP('writetoexttables')) {
				// change value to $GLOBALS
				$length = strpos($line, '[');
				$var = substr($line, 0, $length);
				$changedLine = '$GLOBALS[\'' . substr($line, 1, ($length - 1)) . '\']' . substr($line, $length);
				// load current extTables.php
				$extTables = GeneralUtility::getUrl(PATH_typo3conf . TYPO3_extTableDef_script);
				if ($var === '$TCA') {
					// check if we are editing the TCA
					preg_match_all('/\\[\'([^\']+)\'\\]/', $line, $parts);
				}
				// insert line in extTables.php
				$extTables = preg_replace('/<\\?php|\\?>/is', '', $extTables);
				$extTables = '<?php' . (empty($extTables) ? LF : '') . $extTables . $changedLine . LF . '?>';
				$success = GeneralUtility::writeFile(PATH_typo3conf . TYPO3_extTableDef_script, $extTables);
				if ($success) {
					// show flash message
					$flashMessage = GeneralUtility::makeInstance(
						'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
						'',
						sprintf(
							LocalizationUtility::translate('writeMessage', 'lowlevel'),
							TYPO3_extTableDef_script,
							'<br />',
							'<strong>' . nl2br(htmlspecialchars($changedLine)) . '</strong>'
						),
						\TYPO3\CMS\Core\Messaging\FlashMessage::OK
					);
				} else {
					// Error: show flash message
					$flashMessage = GeneralUtility::makeInstance(
						'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
						'',
						sprintf(LocalizationUtility::translate('writeMessageFailed', 'lowlevel'), TYPO3_extTableDef_script),
						\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
					);
				}
				$this->getFlashMessageQueue()->enqueue($flashMessage);
			}

			$this->view->assign('line', trim($line));

			if (TYPO3_extTableDef_script !== '' && ($this->MOD_SETTINGS['function'] === '1' || $this->MOD_SETTINGS['function'] === '4')) {
				// write only for $TCA and TBE_STYLES if  TYPO3_extTableDef_script is defined
				$this->view->assign('showSaveButton', TRUE);
			} else {
				$this->view->assign('showSaveButton', FALSE);
			}
		} else {
			$this->view->assign('varname', FALSE);
		}

		// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers = array(
			'CSH' => $docHeaderButtons['csh'],
			'FUNC_MENU' => $this->getFuncMenu(),
			'CONTENT' => $this->view->render(),
		);
		// Build the <body> for the module
		$this->content = $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		// Renders the module page
		$this->content = $this->doc->render('Configuration', $this->content);
	}

	/**
	 * Print output to browser
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
			'shortcut' => ''
		);
		// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('', 'function', $this->MCONF['name']);
		}
		return $buttons;
	}

	/**
	 * Create the function menu
	 *
	 * @return string HTML of the function menu
	 */
	protected function getFuncMenu() {
		$funcMenu = BackendUtility::getFuncMenu(0, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
		return $funcMenu;
	}

	/**
	 * @return \TYPO3\CMS\Core\Messaging\FlashMessageQueue
	 */
	protected function getFlashMessageQueue() {
		if (!$this->flashMessageQueue instanceof \TYPO3\CMS\Core\Messaging\FlashMessageQueue) {
			/** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
			$flashMessageService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
			$this->flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
		}

		return $this->flashMessageQueue;
	}
}
