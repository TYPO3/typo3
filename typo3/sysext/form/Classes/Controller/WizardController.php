<?php
namespace TYPO3\CMS\Form\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Patrick Broens (patrick@patrickbroens.nl)
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

/**
 * The form wizard controller
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class WizardController {

	/**
	 * Dispatch on action
	 *
	 * Calls the requested action
	 *
	 * @return void
	 */
	public function dispatch() {
		switch (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('action')) {
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
		/** @var $view \TYPO3\CMS\Form\View\Wizard\WizardView */
		$view = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\View\\Wizard\\WizardView', $this->getRepository());
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
		/** @var $view \TYPO3\CMS\Form\View\Wizard\SaveWizardView */
		$view = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\View\\Wizard\\SaveWizardView', $this->getRepository());
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
		/** @var $view \TYPO3\CMS\Form\View\Wizard\LoadWizardView */
		$view = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\View\\Wizard\\LoadWizardView', $this->getRepository());
		$view->render();
	}

	/**
	 * Gets the repository object.
	 *
	 * @return \TYPO3\CMS\Form\Domain\Repository\ContentRepository
	 */
	protected function getRepository() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\Domain\\Repository\\ContentRepository');
	}

}

?>