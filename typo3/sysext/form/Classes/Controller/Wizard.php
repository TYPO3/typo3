<?php
declare(encoding = 'utf-8');

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
define('TYPO3_MOD_PATH', '../typo3conf/ext/form/Classes/Controller/');
$BACK_PATH = '../../../../../typo3/';
$backPathAbsolute = substr(
	$_SERVER['SCRIPT_FILENAME'],
	0,
	-strlen('/typo3conf/ext/form/Classes/Controller/Wizard.php')
) . '/typo3/';
require($backPathAbsolute . 'init.php');
require($backPathAbsolute . 'template.php');

/**
 * The form wizard controller
 *
 * @category Controller
 * @package TYPO3
 * @subpackage form
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @license http://www.gnu.org/copyleft/gpl.html
 * @version $Id$
 */
class tx_form_controller_wizard {
	/**
	 * Constructs this controller
	 *
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct() {
	}

	/**
	 * Dispatch on action
	 *
	 * Calls the requested action
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
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
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	private function indexAction() {
		/** @var $repository tx_form_domain_repository_content */
		$repository = t3lib_div::makeInstance('tx_form_domain_repository_content');

		/** @var $view tx_form_view_wizard_wizard */
		$view = t3lib_div::makeInstance('tx_form_view_wizard_wizard');
		$view->setRepository($repository);
		$view->render();
	}

	/**
	 * The save action
	 *
	 * The action which should be taken when the form in the wizard is saved
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	private function saveAction() {
		/** @var $repository tx_form_domain_repository_content */
		$repository = t3lib_div::makeInstance('tx_form_domain_repository_content');

		/** @var $view tx_form_view_wizard_save */
		$view = t3lib_div::makeInstance('tx_form_view_wizard_save');
		$view->setRepository($repository);
		$view->render();
	}

	/**
	 * The load action
	 *
	 * The action which should be taken when the form in the wizard is loaded
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	private function loadAction() {
		/** @var $repository tx_form_domain_repository_content */
		$repository = t3lib_div::makeInstance('tx_form_domain_repository_content');

		/** @var $view tx_form_view_wizard_load */
		$view = t3lib_div::makeInstance('tx_form_view_wizard_load');
		$view->setRepository($repository);
		$view->render();
	}
}

/** @var $wizard tx_form_controller_wizard */
$wizard = t3lib_div::makeInstance('tx_form_controller_wizard');
$wizard->dispatch();
?>