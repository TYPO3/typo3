<?php
namespace TYPO3\CMS\FuncWizards\Controller;

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
		$menu = $LANG->getLL('wiz_lWizards', TRUE) . ': ' . \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu(
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
