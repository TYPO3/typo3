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
	 * Status codes for AJAX response
	 */
	const TRANSLATION_NOT_AVAILABLE = 0;
	const TRANSLATION_AVAILABLE = 1;
	const TRANSLATION_FAILED = 2;
	const TRANSLATION_OK = 3;
	const TRANSLATION_INVALID = 4;
	const TRANSLATION_UPDATED = 5;

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
	 * @var \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper
	 * @inject
	 */
	protected $repositoryHelper;

	/**
	 * @var \TYPO3\CMS\Lang\Utility\Connection\Ter
	 * @inject
	 */
	protected $terConnection;

	/**
	 * @var array
	 */
	protected $translationStates = array();

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
		if (is_string($locales)) {
			$locales = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $locales);
		}
		$locales = array_flip((array) $locales);

		foreach ($locales as $locale => $key) {
			$state = static::TRANSLATION_INVALID;
			try {
				$state = $this->getTranslationStateForExtension($extension, $locale);
				if ($state === static::TRANSLATION_AVAILABLE) {
					$state = $this->updateTranslationForExtension($extension, $locale);
				}
			} catch (\Exception $exception) {
				$error = $exception->getMessage();
			}
			$locales[$locale] = array(
				'state'  => $state,
				'error'  => $error,
			);
		}

		$this->view->assign('extension', $extension);
		$this->view->assign('locales', $locales);
	}

	/**
	 * Returns the translation state for an extension
	 *
	 * @param string $extensionKey The extension key
	 * @param string $locale Locale to return
	 * @return integer Translation state
	 */
	protected function getTranslationStateForExtension($extensionKey, $locale) {
		if (empty($extensionKey) || empty($locale)) {
			return static::TRANSLATION_INVALID;
		}

		$identifier = $extensionKey . '-' . $locale;
		if (isset($this->translationStates[$identifier])) {
			return $this->translationStates[$identifier];
		}

		$selectedLanguages = $this->languageRepository->findSelected();
		if (empty($selectedLanguages) || !is_array($selectedLanguages)) {
			return static::TRANSLATION_INVALID;
		}

		$mirrorUrl = $this->repositoryHelper->getMirrors()->getMirrorUrl();
		$status = $this->terConnection->fetchTranslationStatus($extensionKey, $mirrorUrl);

		foreach ($selectedLanguages as $language) {
			$stateLocale = $language->getLocale();
			$stateIdentifier = $extensionKey . '-' . $stateLocale;
			$this->translationStates[$stateIdentifier] = static::TRANSLATION_INVALID;

			if (empty($status[$stateLocale]) || !is_array($status[$stateLocale])) {
				$this->translationStates[$stateIdentifier] = static::TRANSLATION_NOT_AVAILABLE;
				continue;
			}

			$md5 = $this->getTranslationFileMd5($extensionKey, $stateLocale);
			if ($md5 !== $status[$stateLocale]['md5']) {
				$this->translationStates[$stateIdentifier] = static::TRANSLATION_AVAILABLE;
				continue;
			}

			$this->translationStates[$stateIdentifier] = static::TRANSLATION_OK;
		}

		return $this->translationStates[$identifier];
	}

	/**
	 * Returns the md5 of a translation file
	 *
	 * @param string $extensionKey The extension key
	 * @param string $locale The locale
	 * @return string The md5 value
	 */
	protected function getTranslationFileMd5($extensionKey, $locale) {
		if (empty($extensionKey) || empty($locale)) {
			return '';
		}
		$fileName = PATH_site . 'typo3temp' . DIRECTORY_SEPARATOR . $extensionKey . '-l10n-' . $locale . '.zip';
		if (is_file($fileName)) {
			return md5_file($fileName);
		}
		return '';
	}

	/**
	 * Update the translation for an extension
	 *
	 * @param string $extensionKey The extension key
	 * @param string $locale Locale to update
	 * @return integer Translation state
	 */
	protected function updateTranslationForExtension($extensionKey, $locale) {
		if (empty($extensionKey) || empty($locale)) {
			return static::TRANSLATION_INVALID;
		}

		$state = static::TRANSLATION_FAILED;
		$mirrorUrl = $this->repositoryHelper->getMirrors()->getMirrorUrl();
		$updateResult = $this->terConnection->updateTranslation($extensionKey, $locale, $mirrorUrl);
		if ($updateResult === TRUE) {
			$state = static::TRANSLATION_UPDATED;
		}

		return $state;
	}

}
?>