<?php
namespace TYPO3\CMS\Lang\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Sebastian Fischer <typo3@evoweb.de>
 *      2012-2013 Kai Vogel <kai.vogel@speedprogs.de>
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
 * Language controller handling the selection of available languages and update of extension translations
 *
 * @author Sebastian Fischer <typo3@evoweb.de>
 * @author Kai Vogel <kai.vogel@speedprogs.de>
 */
class LanguageController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var \TYPO3\CMS\Lang\Domain\Repository\LanguageRepository
	 * @inject
	 */
	protected $languageRepository;

	/**
	 * @var \TYPO3\CMS\Lang\Domain\Repository\ExtensionRepository
	 * @inject
	 */
	protected $extensionRepository;

	/**
	 * @var \TYPO3\CMS\Lang\Service\UpdateTranslationService
	 * @inject
	 */
	protected $updateTranslationService;

	/**
	 * JSON actions
	 * @var array
	 */
	protected $jsonActions = array('updateTranslation');

	/**
	 * Force JSON output for defined actions
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view The view to be initialized
	 * @return void
	 */
	protected function initializeView(\TYPO3\CMS\Extbase\Mvc\View\ViewInterface $view) {
		$actionName = $this->request->getControllerActionName();
		if (in_array($actionName, $this->jsonActions)) {
			$viewObjectName = 'TYPO3\\CMS\\Lang\\View\\Language\\' . ucfirst($actionName) . 'Json';
			$this->view = $this->objectManager->get($viewObjectName);
			$this->view->setControllerContext($this->controllerContext);
			$this->view->initializeView();
		}
	}

	/**
	 * Index action
	 *
	 * @param \TYPO3\CMS\Lang\Domain\Model\LanguageSelectionForm $languageSelectionForm
	 * @param mixed $extensions Extensions to show in form
	 * @return void
	 * @dontvalidate $languageSelectionForm
	 * @dontvalidate $extensions
	 */
	public function indexAction(\TYPO3\CMS\Lang\Domain\Model\LanguageSelectionForm $languageSelectionForm = NULL, $extensions = NULL) {
		if ($languageSelectionForm === NULL) {
			$languageSelectionForm = $this->objectManager->get('TYPO3\\CMS\\Lang\\Domain\\Model\\LanguageSelectionForm');
			$languageSelectionForm->setLanguages($this->languageRepository->findAll());
			$languageSelectionForm->setSelectedLanguages($this->languageRepository->findSelected());
		}

		if (empty($extensions)) {
			$extensions = $this->extensionRepository->findAll();
		}

		$this->view->assign('languageSelectionForm', $languageSelectionForm);
		$this->view->assign('extensions', $extensions);
	}

	/**
	 * Update the language selection form
	 *
	 * @param \TYPO3\CMS\Lang\Domain\Model\LanguageSelectionForm $languageSelectionForm
	 * @return void
	 * @dontvalidate $languageSelectionForm
	 */
	public function updateLanguageSelectionAction(\TYPO3\CMS\Lang\Domain\Model\LanguageSelectionForm $languageSelectionForm) {
		if ($languageSelectionForm !== NULL) {
			$this->languageRepository->updateSelectedLanguages($languageSelectionForm->getSelectedLanguages());
		}
		$this->redirect('index');
	}

	/**
	 * Update translation for one extension.
	 * The view of this action returns JSON!
	 *
	 * @param string $extension The extension key
	 * @param string $locales Comma separated list of locales to update
	 * @return void
	 */
	public function updateTranslationAction($extension, $locales) {
		$locales = $this->updateTranslationService->updateTranslation($extension, $locales);
		$this->view->assign('extension', $extension);
		$this->view->assign('locales', $locales);
	}

}
