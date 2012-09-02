<?php
namespace TYPO3\CMS\Lang\Controller;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Sebastian Fischer <typo3@evoweb.de>
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
 * @package lang
 * @subpackage LanguageController
 */
class LanguageController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var integer
	 */
	const TRANSLATION_CHECK_FOR_EXTENSION = 0;

	/**
	 * @var integer
	 */
	const TRANSLATION_UPDATE_FOR_EXTENSION = 1;

	/**
	 * @var \TYPO3\CMS\Lang\Domain\Repository\LanguageRepository
	 */
	protected $languageRepository;

	/**
	 * @var \TYPO3\CMS\Lang\Domain\Repository\ExtensionRepository
	 */
	protected $extensionRepository;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper
	 */
	protected $repositoryHelper;

	/**
	 * @var array
	 */
	protected $icons = array();

	/**
	 * Inject the language repository
	 *
	 * @param \TYPO3\CMS\Lang\Domain\Repository\LanguageRepository $repository
	 * @return void
	 */
	public function injectLanguageRepository(\TYPO3\CMS\Lang\Domain\Repository\LanguageRepository $repository) {
		$this->languageRepository = $repository;
	}

	/**
	 * Inject the extension repository
	 *
	 * @param \TYPO3\CMS\Lang\Domain\Repository\ExtensionRepository $repository
	 * @return void
	 */
	public function injectExtensionRepository(\TYPO3\CMS\Lang\Domain\Repository\ExtensionRepository $repository) {
		$this->extensionRepository = $repository;
	}

	/**
	 * Inject the repository helper
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper $repositoryHelper
	 * @return void
	 */
	public function injectRepositoryHelper(\TYPO3\CMS\Extensionmanager\Utility\Repository\Helper $repositoryHelper) {
		$this->repositoryHelper = $repositoryHelper;
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
			$languageSelectionForm = $this->objectManager->create('TYPO3\\CMS\\Lang\\Domain\\Model\\LanguageSelectionForm');
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
	 * Initializes icons used in the update translation prozess
	 *
	 * @return void
	 */
	public function initializeUpdateTranslationAction() {
		$this->icons = array(
			'ok' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-status-checked'),
			'unavailable' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-info'),
			'failed' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-status-permission-denied'),
		);
	}

	/**
	 * Update translations
	 *
	 * @return void
	 */
	public function updateTranslationAction() {
		$selectedLanguages = $this->languageRepository->findSelected();
		$extensions = $this->extensionRepository->findAll();

		if (empty($selectedLanguages)) {
			$this->forward('index');
		}

		try {
			foreach ($extensions as $key => $extension) {
				$updateResult = $this->checkTranslationForExtension($selectedLanguages, $key);
				$extensions[$key]->setUpdateResult($updateResult);
			}
		} catch (\Exception $exception) {
			$flashMessage = $this->objectManager->create(
				'TYPO3\CMS\Core\Messaging\FlashMessage',
				htmlspecialchars($exception->getMessage()),
				'',
				\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
			);
			\TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($flashMessage);
		}

		$this->forward('index', NULL, NULL, array('extensions' => $extensions));
	}

	/**
	 * Check translation(s) for extension
	 *
	 * @param array $languages
	 * @param string $extensionKey
	 * @return array
	 */
	protected function checkTranslationForExtension($languages, $extensionKey) {
		$result = array();

		/** @var $terConnection \TYPO3\CMS\Lang\Utility\Connection\Ter */
		$terConnection = $this->objectManager->create('TYPO3\CMS\Lang\Utility\Connection\Ter');
		$mirrorUrl = $this->repositoryHelper->getMirrors()->getMirrorUrl();

		$fetch = $terConnection->fetchTranslationStatus($extensionKey, $mirrorUrl);
		foreach ($languages as $language) {
			$locale = $language->getLocale();

			if (!isset($fetch[$locale])) {
					// No translation available
				$result[$locale] = array(
					'icon' => $this->icons['unavailable'],
					'message' => 'translation_n_a'
				);
			} else {
				$zip = PATH_site . 'typo3temp' . DIRECTORY_SEPARATOR . $extensionKey . '-l10n-' . $locale . '.zip';
				$md5OfTranslationFile = '';
				if (is_file($zip)) {
					$md5OfTranslationFile = md5_file($zip);
				}

				if ($md5OfTranslationFile !== $fetch[$locale]['md5']) {
					$update = $terConnection->updateTranslation($extensionKey, $locale, $mirrorUrl);

					$result[$locale] = $update ?
						array(
							'icon' => $this->icons['ok'],
							'message' => 'translation_msg_updated'
						) :
						array(
							'icon' => $this->icons['failed'],
							'message' => 'translation_msg_failed'
						);
				} else {
						// Translation is up to date
					$result[$locale] = array(
						'icon' => $this->icons['ok'],
						'message' => 'translation_status_uptodate'
					);
				}
			}
		}

		return $result;
	}

}
?>