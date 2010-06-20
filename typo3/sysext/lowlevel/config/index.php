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
 * Module: Config
 *
 * This module lets you view the config variables around TYPO3.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   72: class SC_mod_tools_config_index
 *   89:     function init()
 *  103:     function jumpToUrl(URL)
 *  117:     function menuConfig()
 *  144:     function main()
 *  268:     function printContent()
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$GLOBALS['LANG']->includeLLFile('EXT:lowlevel/config/locallang.xml');

$BE_USER->modAccess($MCONF,1);







/**
 * Script class for the Config module
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_lowlevel
 */
class SC_mod_tools_config_index {

	var $MCONF = array();
	var $MOD_MENU = array();
	var $MOD_SETTINGS = array();

	/**
	 * document template object
	 *
	 * @var noDoc
	 */
	var $doc;

	var $include_once = array();
	var $content;



	/**
	 * Initialization
	 *
	 * @return	void
	 */
	function init()	{
		global $BACK_PATH;

		$this->MCONF = $GLOBALS['MCONF'];

		$this->menuConfig();

		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->setModuleTemplate('templates/config.html');

				// JavaScript
		$this->doc->JScode = '
		<script language="javascript" type="text/javascript">
			script_ended = 0;
			function jumpToUrl(URL)	{
				window.location.href = URL;
			}
		</script>
		';

		$this->doc->form = '<form action="" method="post">';
	}

	/**
	 * Menu Configuration
	 *
	 * @return	void
	 */
	function menuConfig()	{
		global $TYPO3_CONF_VARS;

			// MENU-ITEMS:
			// If array, then it's a selector box menu
			// If empty string it's just a variable, that'll be saved.
			// Values NOT in this array will not be saved in the settings-array for the module.
		$this->MOD_MENU = array(
			'function' => array(
				0 => $GLOBALS['LANG']->getLL('typo3ConfVars', true),
				1 => $GLOBALS['LANG']->getLL('tca', true),
				2 => $GLOBALS['LANG']->getLL('tcaDescr', true),
				3 => $GLOBALS['LANG']->getLL('loadedExt', true),
				4 => $GLOBALS['LANG']->getLL('t3services', true),
				5 => $GLOBALS['LANG']->getLL('tbemodules', true),
				6 => $GLOBALS['LANG']->getLL('tbemodulesext', true),
				7 => $GLOBALS['LANG']->getLL('tbeStyles', true),
				8 => $GLOBALS['LANG']->getLL('beUser', true),
				9 => $GLOBALS['LANG']->getLL('usersettings', true),
			),
			'regexsearch' => '',
			'fixedLgd' => ''
		);

			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function main()	{

		$arrayBrowser = t3lib_div::makeInstance('t3lib_arrayBrowser');

		$this->content= $this->doc->header($GLOBALS['LANG']->getLL('configuration', true));
		$this->content.= $this->doc->spacer(5);

		$this->content .= '<div id="lowlevel-config">
						<label for="search_field">' . $GLOBALS['LANG']->getLL('enterSearchPhrase', true) . '</label>
						<input type="text" id="search_field" name="search_field" value="' . htmlspecialchars($search_field) . '"' . $GLOBALS['TBE_TEMPLATE']->formWidth(20) . ' />
						<input type="submit" name="search" id="search" value="' . $GLOBALS['LANG']->getLL('search', true) . '" />';
		$this->content .= t3lib_BEfunc::getFuncCheck(0,'SET[regexsearch]',$this->MOD_SETTINGS['regexsearch'],'','','id="checkRegexsearch"') .
						'<label for="checkRegexsearch">' . $GLOBALS['LANG']->getLL('useRegExp', true) . '</label>';

		$this->content.= t3lib_BEfunc::getFuncCheck(0, 'SET[fixedLgd]', $this->MOD_SETTINGS['fixedLgd'], '', '', 'id="checkFixedLgd"') .
						'<label for="checkFixedLgd">' . $GLOBALS['LANG']->getLL('cropLines', true) . '</label>
						</div>';

		$this->content.= $this->doc->spacer(5);

		switch($this->MOD_SETTINGS['function'])	{
			case 0:
				$theVar = $GLOBALS['TYPO3_CONF_VARS'];
				$arrayBrowser->varName = '$TYPO3_CONF_VARS';
			break;
			case 1:
				foreach ($GLOBALS['TCA'] as $table => $config)	{
					t3lib_div::loadTCA($table);
				}
				$theVar = $GLOBALS['TCA'];
				$arrayBrowser->varName = '$TCA';
			break;
			case 2:
				$theVar = $GLOBALS['TCA_DESCR'];
				$arrayBrowser->varName = '$TCA_DESCR';
			break;
			case 3:
				$theVar = $GLOBALS['TYPO3_LOADED_EXT'];
				$arrayBrowser->varName = '$TYPO3_LOADED_EXT';
			break;
			case 4:
				$theVar = $GLOBALS['T3_SERVICES'];
				$arrayBrowser->varName = '$T3_SERVICES';
			break;
			case 5:
				$theVar = $GLOBALS['TBE_MODULES'];
				$arrayBrowser->varName = '$TBE_MODULES';
			break;
			case 6:
				$theVar = $GLOBALS['TBE_MODULES_EXT'];
				$arrayBrowser->varName = '$TBE_MODULES_EXT';
			break;
			case 7:
				$theVar = $GLOBALS['TBE_STYLES'];
				$arrayBrowser->varName = '$TBE_STYLES';
			break;
			case 8:
				$theVar = $GLOBALS['BE_USER']->uc;
				$arrayBrowser->varName = '$BE_USER->uc';
			break;
			case 9:
				$theVar = $GLOBALS['TYPO3_USER_SETTINGS'];
				$arrayBrowser->varName = '$TYPO3_USER_SETTINGS';
			break;
			default:
				$theVar = array();
			break;
		}


			// Update node:
		$update = 0;
		$node = t3lib_div::_GET('node');
		if (is_array($node))	{		// If any plus-signs were clicked, it's registred.
			$this->MOD_SETTINGS['node_'.$this->MOD_SETTINGS['function']] = $arrayBrowser->depthKeys($node, $this->MOD_SETTINGS['node_'.$this->MOD_SETTINGS['function']]);
			$update = 1;
		}
		if ($update) {
			$GLOBALS['BE_USER']->pushModuleData($this->MCONF['name'],$this->MOD_SETTINGS);
		}

		$arrayBrowser->depthKeys = $this->MOD_SETTINGS['node_'.$this->MOD_SETTINGS['function']];
		$arrayBrowser->regexMode = $this->MOD_SETTINGS['regexsearch'];
		$arrayBrowser->fixedLgd = $this->MOD_SETTINGS['fixedLgd'];
		$arrayBrowser->searchKeysToo = TRUE;


		$search_field = t3lib_div::_GP('search_field');
		if (t3lib_div::_POST('search') && trim($search_field))	{		// If any POST-vars are send, update the condition array
			$arrayBrowser->depthKeys=$arrayBrowser->getSearchKeys($theVar, '',	$search_field, array());
		}

		$tree = $arrayBrowser->tree($theVar, '', '');

		$label = $this->MOD_MENU['function'][$this->MOD_SETTINGS['function']];
		$this->content.= $this->doc->sectionEnd();

			// Variable name:
		if (t3lib_div::_GP('varname'))	{
			$line = t3lib_div::_GP('_') ? t3lib_div::_GP('_') : t3lib_div::_GP('varname');
			if (t3lib_div::_GP('writetoexttables')) { // Write the line to extTables.php
					// change value to $GLOBALS
				$length = strpos($line, '[');
				$var = substr($line, 0, $length);
				$changedLine = '$GLOBALS[\'' . substr($line, 1, $length - 1) . '\']' . substr($line, $length);
					// insert line  in extTables.php
				$extTables = t3lib_div::getURL(PATH_typo3conf . TYPO3_extTableDef_script);
				$extTables = '<?php' . preg_replace('/<\?php|\?>/is', '', $extTables) . $changedLine . LF . '?>';
				$success = t3lib_div::writeFile(PATH_typo3conf . TYPO3_extTableDef_script, $extTables);
				if ($success) {
						// show flash message
					$flashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						'',
						sprintf($GLOBALS['LANG']->getLL('writeMessage', TRUE), TYPO3_extTableDef_script,  '<br />', '<strong>' . $changedLine . '</strong>'),
						t3lib_FlashMessage::OK
					);
				} else {
					// Error: show flash message
					$flashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						'',
						sprintf($GLOBALS['LANG']->getLL('writeMessageFailed', TRUE), TYPO3_extTableDef_script),
						t3lib_FlashMessage::ERROR
					);
				}
				$this->content .= $flashMessage->render();
			}
			$this->content .= '<div id="lowlevel-config-var">
				<strong>' . $GLOBALS['LANG']->getLL('variable', TRUE) . '</strong><br />
				<input type="text" name="_" value="'.trim(htmlspecialchars($line)).'" size="120" /><br/>';

			if (TYPO3_extTableDef_script !== '' && ($this->MOD_SETTINGS['function'] === '1' || $this->MOD_SETTINGS['function'] === '4')) {
					// write only for $TCA and TBE_STYLES if  TYPO3_extTableDef_script is defined
				$this->content .= '<br /><input type="submit" name="writetoexttables" value="' .
					$GLOBALS['LANG']->getLL('writeValue', TRUE) . '" /></div>';
			} else {
				$this->content .= $GLOBALS['LANG']->getLL('copyPaste', TRUE) . LF . '</div>';
			}

		}

		$this->content.= '<br /><table border="0" cellpadding="0" cellspacing="0" class="t3-tree t3-tree-config">';
		$this->content.= '<tr>
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
		$this->content = $this->doc->startPage('Configuration');

		$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Print output to browser
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
			'shortcut' => ''
		);
			// CSH
		//$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_func', '', $GLOBALS['BACK_PATH']);

			// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut())	{
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('','function',$this->MCONF['name']);
		}
		return $buttons;
	}

	/**
	 * Create the function menu
	 *
	 * @return	string	HTML of the function menu
	 */
	protected function getFuncMenu() {
		$funcMenu = t3lib_BEfunc::getFuncMenu(0, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
		return $funcMenu;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lowlevel/config/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lowlevel/config/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_tools_config_index');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
