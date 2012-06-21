<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Patrick Broens (patrick@patrickbroens.nl)
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
define('TYPO3_MOD_PATH', 'sysext/form/Classes/Controller/');
$BACK_PATH = '../../../../';
require($BACK_PATH . 'init.php');
require($BACK_PATH . 'template.php');

/**
 * The form wizard controller
 *
 * @category Controller
 * @package TYPO3
 * @subpackage form
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class tx_form_Controller_Wizard {
	/**
	 * Dispatch on action
	 *
	 * Calls the requested action
	 *
	 * @return void
	 */
	public function dispatch() {
		switch(t3lib_div::_GP('action')) {
			case 'save':
				$this->saveAction();
				break;
			case 'load':
				$this->loadAction();
				break;
			default:
				$this->indexAction();
		}
	}

	/**
	 * The index action
	 *
	 * The action which should be taken when the wizard is loaded
	 *
	 * @return void
	 */
	protected function indexAction() {
		/** @var $view tx_form_View_Wizard_Wizard */
		$view = t3lib_div::makeInstance('tx_form_View_Wizard_Wizard', $this->getRepository());
		$view->render();
	}

	/**
	 * The save action
	 *
	 * The action which should be taken when the form in the wizard is saved
	 *
	 * @return void
	 */
	protected function saveAction() {
		/** @var $view tx_form_View_Wizard_Save */
		$view = t3lib_div::makeInstance('tx_form_View_Wizard_Save', $this->getRepository());
		$view->render();
	}

	/**
	 * The load action
	 *
	 * The action which should be taken when the form in the wizard is loaded
	 *
	 * @return void
	 */
	protected function loadAction() {
		/** @var $view tx_form_View_Wizard_Load */
		$view = t3lib_div::makeInstance('tx_form_View_Wizard_Load', $this->getRepository());
		$view->render();
	}

	/**
	 * Gets the repository object.
	 *
	 * @return tx_form_Domain_Repository_Content
	 */
	protected function getRepository() {
		return t3lib_div::makeInstance('tx_form_Domain_Repository_Content');
	}
}

/** @var $wizard tx_form_Controller_Wizard */
$wizard = t3lib_div::makeInstance('tx_form_Controller_Wizard');
$wizard->dispatch();
?>