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
	 * @return void
	 */
	public function indexAction() {
		$languages = $this->languageRepository->findAll();
		$selectedLanguages = $this->languageRepository->findSelected();
		$extensions = $this->extensionRepository->findAll();

		if ($this->request->hasArgument('updateResult')) {
			$extensions = $this->mergeUpdateResult($extensions, $this->request->getArgument('updateResult'));
		}

		$this->view->assign('languages', $languages);
		$this->view->assign('selectedLanguages', $selectedLanguages);
		$this->view->assign('extensions', $extensions);
	}

	/**
	 * Merge update results after translation update into extension models for rendering
	 *
	 * @param array $extensions
	 * @param array $updateResult
	 * @return array
	 */
	protected function mergeUpdateResult(array $extensions, array $updateResult) {
		foreach ($updateResult as $key => $messages) {
			$extensions[$key]->setUpdateResult($messages);
		}

		return $extensions;
	}

	/**
	 * Save selected locale(s)
	 *
	 * @param \TYPO3\CMS\Lang\Domain\Model\LanguageSelectionForm $form
	 * @return void
	 */
	public function saveSelectedLocaleAction(\TYPO3\CMS\Lang\Domain\Model\LanguageSelectionForm $form) {
		$selectedLanguages = array();
		foreach ($form->getLocale() as $locale => $value) {
			if ($value) {
				$selectedLanguages[] = $locale;
			}
		}

		$this->languageRepository->updateSelectedLanguages($selectedLanguages);

		$this->forward('index');
	}

	/**
	 * Initializes icons used in the update translation prozess
	 *
	 * @return void
	 */
	protected function initializeUpdateTranslationAction() {
		$this->icons = array(
			'ok' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-status-checked'),
			'unavailable' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-info'),
			'failed' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-status-permission-denied'),
		);
	}

	/**
	 * Update translation(s)
	 *
	 * @param \TYPO3\CMS\Lang\Domain\Model\UpdateTranslationForm $form
	 * @return void
	 */
	public function updateTranslationAction(\TYPO3\CMS\Lang\Domain\Model\UpdateTranslationForm $form) {
		$result = array();

		try {
			if (count($form->getSelectedLanguages())) {
				foreach ($form->getExtensions() as $extension) {
					$result[$extension] = $this->checkTranslationForExtension($form->getSelectedLanguages(), $extension);
				}
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

		$this->forward('index', NULL, NULL, array('updateResult' => $result));
	}

	/**
	 * Check translation(s) for extension
	 *
	 * @param array $languages
	 * @param string $extensionKey
	 * @return array
	 */
	public function checkTranslationForExtension($languages, $extensionKey) {
		$result = array();

		/** @var $terConnection \TYPO3\CMS\Lang\Utility\Connection\Ter */
		$terConnection = $this->objectManager->create('TYPO3\CMS\Lang\Utility\Connection\Ter');
		$mirrorUrl = $this->repositoryHelper->getMirrors()->getMirrorUrl();

		$fetch = $terConnection->fetchTranslationStatus($extensionKey, $mirrorUrl);
		foreach ($languages as $lang) {
			if (!isset($fetch[$lang])) {
					// No translation available
				$result[$lang] = array(
					'icon' => $this->icons['unavailable'],
					'message' => 'translation_n_a'
				);
			} else {
				$zip = PATH_site . 'typo3temp' . DIRECTORY_SEPARATOR . $extensionKey . '-l10n-' . $lang . '.zip';
				$md5OfTranslationFile = '';
				if (is_file($zip)) {
					$md5OfTranslationFile = md5_file($zip);
				}

				if ($md5OfTranslationFile !== $fetch[$lang]['md5']) {
					$update = $terConnection->updateTranslation($extensionKey, $lang, $mirrorUrl);

					$result[$lang] = $update ?
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
					$result[$lang] = array(
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