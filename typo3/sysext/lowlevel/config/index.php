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
require_once (PATH_t3lib.'class.t3lib_arraybrowser.php');

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
		$this->doc->docType='xhtml_trans';

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
				0 => '$TYPO3_CONF_VARS',
				1 => '$TCA (tables.php)',
				3 => '$TYPO3_LOADED_EXT',
				4 => '$TBE_STYLES',
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
		global $BE_USER,$LANG,$TCA,$TYPO3_CONF_VARS;

		$this->content.= $this->doc->header('Configuration');
		$this->content.= $this->doc->spacer(5);

		$arrayBrowser = t3lib_div::makeInstance('t3lib_arrayBrowser');

		$this->content.= '<label for="checkFixedLgd">Crop lines:</label>&nbsp;&nbsp;' . t3lib_BEfunc::getFuncCheck(0, 'SET[fixedLgd]', $this->MOD_SETTINGS['fixedLgd'], '', '', 'id="checkFixedLgd"');
		$this->content.= $this->doc->spacer(5);

		switch($this->MOD_SETTINGS['function'])	{
			case 0:
				$theVar = $TYPO3_CONF_VARS;
				$arrayBrowser->varName = '$TYPO3_CONF_VARS';
			break;
			case 1:
				reset($TCA);
				while(list($ttable) =each($TCA))	{
					t3lib_div::loadTCA($ttable);
				}
				$theVar = $TCA;
				$arrayBrowser->varName = '$TCA';
			break;
			case 3:
				$theVar = $GLOBALS['TYPO3_LOADED_EXT'];
				$arrayBrowser->varName = '$TYPO3_LOADED_EXT';
			break;
			case 4:
				$theVar = $GLOBALS['TBE_STYLES'];
				$arrayBrowser->varName = '$TBE_STYLES';
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
			$this->content.= '<div style="margin: 10px 10px 10px 10px; padding: 10px 10px 10px 10px; background-color: #eeeeee; border: 1px solid black;">Variable: <br/>
				<input type="text" name="_" value="'.trim(htmlspecialchars(t3lib_div::_GP('varname'))).'" size="80" /><br/>
				(Now, copy/paste this value into the configuration file where you can set it. This is all you can do from here...)
				</div>
			';
		}

		$this->content.= '<br/><table border="0" cellpadding="1" cellspacing="0"">';
		$this->content.= '<tr>
					<td><img src="clear.gif" width="1" height="1" alt="" /></td>
					<td class="bgColor2">
						<table border="0" cellpadding="0" cellspacing="0" class="bgColor5" width="100%"><tr><td nowrap="nowrap"><b>'.$label.'</b></td></tr></table>
					</td>
				</tr>';
		$this->content.='<tr>
					<td></td>
					<td class="bgColor2">
						<table border="0" cellpadding="0" cellspacing="0" bgcolor="#D9D5C9" width="100%"><tr><td nowrap="nowrap">'.$tree.'</td></tr></table>' .
								'<img src="clear.gif" width="465" height="1" alt="" /></td>
				</tr>
			</table>
		';


			// Search:
		$this->content.='<br>
			<table border="0" cellpadding="1" cellspacing="0"">
				<tr>
					<td><img src="clear.gif" width="1" height="1" alt="" /></td>
					<td class="bgColor2">
						<table border="0" cellpadding="0" cellspacing="0" bgcolor="#D9D5C9">
						<tr>
							<td>&nbsp;Enter search phrase:&nbsp;&nbsp;<input type="text" name="search_field" value="'.htmlspecialchars($search_field).'"'.$GLOBALS['TBE_TEMPLATE']->formWidth(20).'></td>
							<td><input type="submit" name="search" value="Search" /></td>
						</tr>
						<tr>
							<td>&nbsp;<label for="checkRegexsearch">Use ereg(), not stristr():</label>&nbsp;&nbsp;'.t3lib_BEfunc::getFuncCheck(0,'SET[regexsearch]',$this->MOD_SETTINGS['regexsearch'],'','','id="checkRegexsearch"').'</td>
							<td>&nbsp;</td>
						</tr>
						</table>
					</td>
				</tr>
			</table>
		<br/>
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

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lowlevel/config/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/lowlevel/config/index.php']);
}












// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_tools_config_index');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
