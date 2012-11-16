<?php
namespace TYPO3\CMS\Form\Controller;

/**
 * The form wizard controller
 *
 * @category Controller
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