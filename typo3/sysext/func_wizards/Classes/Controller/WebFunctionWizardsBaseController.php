<?php
namespace TYPO3\CMS\FuncWizards\Controller;

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
 * The Wizard function in the Web>Info module
 * Creates a framework for adding wizard sub-sub-modules under the Wizard function in Web>Info
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class WebFunctionWizardsBaseController extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule {

	/**
	 * @todo Define visibility
	 */
	public $function_key = 'wiz';

	/**
	 * Initialize.
	 * Calls parent init function and then the handleExternalFunctionValue() function from the parent class
	 *
	 * @param object $pObj A reference to the parent (calling) object (which is probably an instance of an extension class to \TYPO3\CMS\Backend\Module\BaseScriptClass)
	 * @param array $conf The configuration set for this module - from global array TBE_MODULES_EXT
	 * @return void
	 * @todo Define visibility
	 */
	public function init(&$pObj, $conf) {
		// OK, handles ordinary init. This includes setting up the menu array with ->modMenu
		parent::init($pObj, $conf);
		// Making sure that any further external classes are added to the include_once array.
		// Notice that inclusion happens twice in the main script because of this!!!
		$this->handleExternalFunctionValue();
	}

	/**
	 * Modifies parent objects internal MOD_MENU array, adding items this module needs.
	 *
	 * @return array Items merged with the parent objects.
	 * @todo Define visibility
	 */
	public function modMenu() {
		$GLOBALS['LANG']->includeLLFile('EXT:func_wizards/locallang.xlf');
		$modMenuAdd = array(
			$this->function_key => array()
		);
		$modMenuAdd[$this->function_key] = $this->pObj->mergeExternalItems($this->pObj->MCONF['name'], $this->function_key, $modMenuAdd[$this->function_key]);
		$modMenuAdd[$this->function_key] = \TYPO3\CMS\Backend\Utility\BackendUtility::unsetMenuItems(
			$this->pObj->modTSconfig['properties'],
			$modMenuAdd[$this->function_key],
			'menu.' . $this->function_key
		);
		return $modMenuAdd;
	}

	/**
	 * Creation of the main content. Calling extObjContent() to trigger content generation from the sub-sub modules
	 *
	 * @return string The content
	 * @todo Define visibility
	 */
	public function main() {
		global $SOBE, $LANG;
		$menu = $LANG->getLL('wiz_lWizards', 1) . ': ' . \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu(
			$this->pObj->id,
			'SET[wiz]',
			$this->pObj->MOD_SETTINGS['wiz'],
			$this->pObj->MOD_MENU['wiz']
		);
		$theOutput .= $this->pObj->doc->section('', '<span class="nobr">' . $menu . '</span>');
		$content = '';
		$content .= $theOutput;
		$content .= $this->pObj->doc->spacer(20);
		$content .= $this->extObjContent();
		return $content;
	}

}

?>