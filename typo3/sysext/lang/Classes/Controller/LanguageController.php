<?php
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
class Tx_Lang_Controller_LanguageController extends Tx_Extbase_MVC_Controller_ActionController {

	/**
	 * @var integer
	 */
	const TRANSLATION_CHECK_FOR_EXTENSION = 0;

	/**
	 * @var integer
	 */
	const TRANSLATION_UPDATE_FOR_EXTENSION = 1;

	/**
	 * @var Tx_Lang_Domain_Repository_LanguageRepository
	 */
	protected $languageRepository;

	/**
	 * @var Tx_Lang_Domain_Repository_ExtensionRepository
	 */
	protected $extensionRepository;

	/**
	 * @var Tx_Extensionmanager_Utility_Repository_Helper
	 */
	protected $repositoryHelper;

	/**
	 * @var array
	 */
	protected $icons = array();

	/**
	 * Inject the language repository
	 *
	 * @param Tx_Lang_Domain_Repository_LanguageRepository $repository
	 * @return void
	 */
	public function injectLanguageRepository(Tx_Lang_Domain_Repository_LanguageRepository $repository) {
		$this->languageRepository = $repository;
	}

	/**
	 * Inject the extension repository
	 *
	 * @param Tx_Lang_Domain_Repository_ExtensionRepository $repository
	 * @return void
	 */
	public function injectExtensionRepository(Tx_Lang_Domain_Repository_ExtensionRepository $repository) {
		$this->extensionRepository = $repository;
	}

	/**
	 * Inject the repository helper
	 *
	 * @param Tx_Extensionmanager_Utility_Repository_Helper $repositoryHelper
	 * @return void
	 */
	public function injectRepositoryHelper(Tx_Extensionmanager_Utility_Repository_Helper $repositoryHelper) {
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
	 * @param Tx_Lang_Domain_Model_LanguageSelectionForm $form
	 * @return void
	 */
	public function saveSelectedLocaleAction(Tx_Lang_Domain_Model_LanguageSelectionForm $form) {
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
			'ok' => t3lib_iconWorks::getSpriteIcon('status-status-checked'),
			'unavailable' => t3lib_iconWorks::getSpriteIcon('actions-document-info'),
			'failed' => t3lib_iconWorks::getSpriteIcon('status-status-permission-denied'),
		);
	}

	/**
	 * Update translation(s)
	 *
	 * @param Tx_Lang_Domain_Model_UpdateTranslationForm $form
	 * @return void
	 */
	public function updateTranslationAction(Tx_Lang_Domain_Model_UpdateTranslationForm $form) {
		$result = array();

		if (count($form->getSelectedLanguages())) {
			foreach ($form->getExtensions() as $extension) {
				$result[$extension] = $this->checkTranslationForExtension($form->getSelectedLanguages(), $extension);
			}
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

		/** @var $terConnection Tx_Lang_Utility_Connection_Ter */
		$terConnection = $this->objectManager->create('Tx_Lang_Utility_Connection_Ter');
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