<?php
namespace TYPO3\CMS\Lowlevel\View;

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
 * Script class for the Config module
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ConfigurationView {

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
	 * Document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * @todo Define visibility
	 */
	public $doc;

	/**
	 * @todo Define visibility
	 */
	public $include_once = array();

	/**
	 * @todo Define visibility
	 */
	public $content;

	/**
	 * Initialization
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function init() {
		global $BACK_PATH;
		$this->MCONF = $GLOBALS['MCONF'];
		$this->menuConfig();
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->setModuleTemplate('templates/config.html');
		// JavaScript
		$this->doc->JScode = '
		<script language="javascript" type="text/javascript">
			script_ended = 0;
			function jumpToUrl(URL) {
				window.location.href = URL;
			}
		</script>
		';
		$this->doc->form = '<form action="" method="post">';
	}

	/**
	 * Menu Configuration
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function menuConfig() {
		global $TYPO3_CONF_VARS;
		// MENU-ITEMS:
		// If array, then it's a selector box menu
		// If empty string it's just a variable, that'll be saved.
		// Values NOT in this array will not be saved in the settings-array for the module.
		$this->MOD_MENU = array(
			'function' => array(
				0 => $GLOBALS['LANG']->getLL('typo3ConfVars', TRUE),
				1 => $GLOBALS['LANG']->getLL('tca', TRUE),
				2 => $GLOBALS['LANG']->getLL('tcaDescr', TRUE),
				3 => $GLOBALS['LANG']->getLL('loadedExt', TRUE),
				4 => $GLOBALS['LANG']->getLL('t3services', TRUE),
				5 => $GLOBALS['LANG']->getLL('tbemodules', TRUE),
				6 => $GLOBALS['LANG']->getLL('tbemodulesext', TRUE),
				7 => $GLOBALS['LANG']->getLL('tbeStyles', TRUE),
				8 => $GLOBALS['LANG']->getLL('beUser', TRUE),
				9 => $GLOBALS['LANG']->getLL('usersettings', TRUE)
			),
			'regexsearch' => '',
			'fixedLgd' => ''
		);
		// CLEANSE SETTINGS
		$this->MOD_SETTINGS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData($this->MOD_MENU, \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SET'), $this->MCONF['name']);
	}

	/**
	 * [Describe function...]
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		$arrayBrowser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Lowlevel\\Utility\\ArrayBrowser');
		$this->content = $this->doc->header($GLOBALS['LANG']->getLL('configuration', TRUE));
		$this->content .= '<div id="lowlevel-config">
						<label for="search_field">' . $GLOBALS['LANG']->getLL('enterSearchPhrase', TRUE) . '</label>
						<input type="text" id="search_field" name="search_field" value="' . htmlspecialchars($search_field) . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(20) . ' />
						<input type="submit" name="search" id="search" value="' . $GLOBALS['LANG']->getLL('search', TRUE) . '" />';
		$this->content .= \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck(0, 'SET[regexsearch]', $this->MOD_SETTINGS['regexsearch'], '', '', 'id="checkRegexsearch"') . '<label for="checkRegexsearch">' . $GLOBALS['LANG']->getLL('useRegExp', TRUE) . '</label>';
		$this->content .= \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck(0, 'SET[fixedLgd]', $this->MOD_SETTINGS['fixedLgd'], '', '', 'id="checkFixedLgd"') . '<label for="checkFixedLgd">' . $GLOBALS['LANG']->getLL('cropLines', TRUE) . '</label>
						</div>';
		$this->content .= $this->doc->spacer(5);
		switch ($this->MOD_SETTINGS['function']) {
		case 0:
			$theVar = $GLOBALS['TYPO3_CONF_VARS'];
			\TYPO3\CMS\Core\Utility\GeneralUtility::naturalKeySortRecursive($theVar);
			$arrayBrowser->varName = '$TYPO3_CONF_VARS';
			break;
		case 1:
			$theVar = $GLOBALS['TCA'];
			\TYPO3\CMS\Core\Utility\GeneralUtility::naturalKeySortRecursive($theVar);
			$arrayBrowser->varName = '$TCA';
			break;
		case 2:
			$theVar = $GLOBALS['TCA_DESCR'];
			\TYPO3\CMS\Core\Utility\GeneralUtility::naturalKeySortRecursive($theVar);
			$arrayBrowser->varName = '$TCA_DESCR';
			break;
		case 3:
			$theVar = $GLOBALS['TYPO3_LOADED_EXT'];
			\TYPO3\CMS\Core\Utility\GeneralUtility::naturalKeySortRecursive($theVar);
			$arrayBrowser->varName = '$TYPO3_LOADED_EXT';
			break;
		case 4:
			$theVar = $GLOBALS['T3_SERVICES'];
			\TYPO3\CMS\Core\Utility\GeneralUtility::naturalKeySortRecursive($theVar);
			$arrayBrowser->varName = '$T3_SERVICES';
			break;
		case 5:
			$theVar = $GLOBALS['TBE_MODULES'];
			\TYPO3\CMS\Core\Utility\GeneralUtility::naturalKeySortRecursive($theVar);
			$arrayBrowser->varName = '$TBE_MODULES';
			break;
		case 6:
			$theVar = $GLOBALS['TBE_MODULES_EXT'];
			\TYPO3\CMS\Core\Utility\GeneralUtility::naturalKeySortRecursive($theVar);
			$arrayBrowser->varName = '$TBE_MODULES_EXT';
			break;
		case 7:
			$theVar = $GLOBALS['TBE_STYLES'];
			\TYPO3\CMS\Core\Utility\GeneralUtility::naturalKeySortRecursive($theVar);
			$arrayBrowser->varName = '$TBE_STYLES';
			break;
		case 8:
			$theVar = $GLOBALS['BE_USER']->uc;
			\TYPO3\CMS\Core\Utility\GeneralUtility::naturalKeySortRecursive($theVar);
			$arrayBrowser->varName = '$BE_USER->uc';
			break;
		case 9:
			$theVar = $GLOBALS['TYPO3_USER_SETTINGS'];
			\TYPO3\CMS\Core\Utility\GeneralUtility::naturalKeySortRecursive($theVar);
			$arrayBrowser->varName = '$TYPO3_USER_SETTINGS';
			break;
		default:
			$theVar = array();
			break;
		}
		// Update node:
		$update = 0;
		$node = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('node');
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
		$search_field = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('search_field');
		// If any POST-vars are send, update the condition array
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('search') && trim($search_field)) {
			$arrayBrowser->depthKeys = $arrayBrowser->getSearchKeys($theVar, '', $search_field, array());
		}
		// mask the encryption key to not show it as plaintext in the configuration module
		if ($theVar == $GLOBALS['TYPO3_CONF_VARS']) {
			$theVar['SYS']['encryptionKey'] = '***** (length: ' . strlen($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']) . ' characters)';
		}
		$tree = $arrayBrowser->tree($theVar, '', '');
		$label = $this->MOD_MENU['function'][$this->MOD_SETTINGS['function']];
		$this->content .= $this->doc->sectionEnd();
		// Variable name:
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('varname')) {
			$line = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_') ? \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('_') : \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('varname');
			// Write the line to extTables.php
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('writetoexttables')) {
				// change value to $GLOBALS
				$length = strpos($line, '[');
				$var = substr($line, 0, $length);
				$changedLine = '$GLOBALS[\'' . substr($line, 1, ($length - 1)) . '\']' . substr($line, $length);
				// load current extTables.php
				$extTables = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl(PATH_typo3conf . TYPO3_extTableDef_script);
				if ($var === '$TCA') {
					// check if we are editing the TCA
					preg_match_all('/\\[\'([^\']+)\'\\]/', $line, $parts);
				}
				// insert line in extTables.php
				$extTables = preg_replace('/<\\?php|\\?>/is', '', $extTables);
				$extTables = '<?php' . (empty($extTables) ? LF : '') . $extTables . $changedLine . LF . '?>';
				$success = \TYPO3\CMS\Core\Utility\GeneralUtility::writeFile(PATH_typo3conf . TYPO3_extTableDef_script, $extTables);
				if ($success) {
					// show flash message
					$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
						'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
						'',
						sprintf(
							$GLOBALS['LANG']->getLL('writeMessage', TRUE),
							TYPO3_extTableDef_script,
							'<br />',
							'<strong>' . nl2br(htmlspecialchars($changedLine)) . '</strong>'
						),
						\TYPO3\CMS\Core\Messaging\FlashMessage::OK
					);
				} else {
					// Error: show flash message
					$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
						'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
						'',
						sprintf($GLOBALS['LANG']->getLL('writeMessageFailed', TRUE), TYPO3_extTableDef_script),
						\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
					);
				}
				$this->content .= $flashMessage->render();
			}
			$this->content .= '<div id="lowlevel-config-var">
				<strong>' . $GLOBALS['LANG']->getLL('variable', TRUE) . '</strong><br />
				<input type="text" name="_" value="' . trim(htmlspecialchars($line)) . '" size="120" /><br/>';
			if (TYPO3_extTableDef_script !== '' && ($this->MOD_SETTINGS['function'] === '1' || $this->MOD_SETTINGS['function'] === '4')) {
				// write only for $TCA and TBE_STYLES if  TYPO3_extTableDef_script is defined
				$this->content .= '<br /><input type="submit" name="writetoexttables" value="' . $GLOBALS['LANG']->getLL('writeValue', TRUE) . '" /></div>';
			} else {
				$this->content .= $GLOBALS['LANG']->getLL('copyPaste', TRUE) . LF . '</div>';
			}
		}
		$this->content .= '<br /><table border="0" cellpadding="0" cellspacing="0" class="t3-tree t3-tree-config">';
		$this->content .= '<tr>
					<th class="t3-row-header t3-tree-config-header">' . $label . '</th>
				</tr>
				<tr>
					<td>' . $tree . '</td>
				</tr>
			</table>
		';
		// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers = array(
			'CSH' => $docHeaderButtons['csh'],
			'FUNC_MENU' => $this->getFuncMenu(),
			'CONTENT' => $this->content
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
		$funcMenu = \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu(0, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
		return $funcMenu;
	}

}


?>