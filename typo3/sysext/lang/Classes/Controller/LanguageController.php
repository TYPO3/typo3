<?php
namespace TYPO3\CMS\Lang\Controller;

/*
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
 * Language controller
 */
class LanguageController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var \TYPO3\CMS\Lang\Domain\Repository\LanguageRepository
	 */
	protected $languageRepository;

	/**
	 * @var \TYPO3\CMS\Lang\Domain\Repository\ExtensionRepository
	 */
	protected $extensionRepository;

	/**
	 * @var \TYPO3\CMS\Lang\Service\TranslationService
	 */
	protected $translationService;

	/**
	 * @var \TYPO3\CMS\Lang\Service\RegistryService
	 */
	protected $registryService;

	/**
	 * @param \TYPO3\CMS\Lang\Domain\Repository\LanguageRepository $languageRepository
	 */
	public function injectLanguageRepository(\TYPO3\CMS\Lang\Domain\Repository\LanguageRepository $languageRepository) {
		$this->languageRepository = $languageRepository;
	}

	/**
	 * @param \TYPO3\CMS\Lang\Domain\Repository\ExtensionRepository $extensionRepository
	 */
	public function injectExtensionRepository(\TYPO3\CMS\Lang\Domain\Repository\ExtensionRepository $extensionRepository) {
		$this->extensionRepository = $extensionRepository;
	}

	/**
	 * @param \TYPO3\CMS\Lang\Service\TranslationService $translationService
	 */
	public function injectTranslationService(\TYPO3\CMS\Lang\Service\TranslationService $translationService) {
		$this->translationService = $translationService;
	}

	/**
	 * @param \TYPO3\CMS\Lang\Service\RegistryService $registryService
	 */
	public function injectRegistryService(\TYPO3\CMS\Lang\Service\RegistryService $registryService) {
		$this->registryService = $registryService;
	}

	/**
	 * List languages
	 *
	 * @return void
	 */
	public function listLanguagesAction() {
		$languages = $this->languageRepository->findAll();
		$this->view->assign('languages', $languages);
	}

	/**
	 * List translations
	 *
	 * @return void
	 */
	public function listTranslationsAction() {
		$languages = $this->languageRepository->findSelected();
		$this->view->assign('languages', $languages);
	}

	/**
	 * Returns the translations
	 *
	 * @return void
	 */
	public function getTranslationsAction() {
		$this->view->assign('extensions', $this->extensionRepository->findAll());
		$this->view->assign('languages', $this->languageRepository->findSelected());
	}

	/**
	 * Fetch all translations for given locale
	 *
	 * @param array $data The request data
	 * @return void
	 */
	public function updateLanguageAction(array $data) {
		$response = array(
			'success'  => FALSE,
			'progress' => 0,
		);
		if (!empty($data['locale'])) {
			$extension = $this->extensionRepository->findOneByOffset((int)$data['count']);
			if (!empty($extension)) {
				$allCount = (int)$this->extensionRepository->countAll();
				$offset = (int)$data['count'];
				$extensionKey = $extension->getKey();
				$result = $this->translationService->updateTranslation($extensionKey, $data['locale']);
				$progress = round((($offset + 1) * 100) / $allCount, 2);
				if (empty($result[$extensionKey][$data['locale']]['error'])) {
					$this->registryService->set($data['locale'], $GLOBALS['EXEC_TIME']);
					$response = array(
						'success'   => TRUE,
						'result'    => $result,
						'timestamp' => $GLOBALS['EXEC_TIME'],
						'progress'  => $progress > 100 ? 100 : $progress,
					);
				}
			}
		}
		$this->view->assign('response', $response);
	}

	/**
	 * Fetch the translation for given extension and locale
	 *
	 * @param array $data The request data
	 * @return void
	 */
	public function updateTranslationAction(array $data) {
		$response = array('success' => FALSE);
		if (!empty($data['extension']) && !empty($data['locale'])) {
			$result = $this->translationService->updateTranslation($data['extension'], $data['locale']);
			if (empty($result[$data['extension']][$data['locale']]['error'])) {
				$response = array(
					'success' => TRUE,
					'result'  => $result,
				);
			}
		}
		$this->view->assign('response', $response);
	}

	/**
	 * Activate a language
	 *
	 * @param array $data The request data
	 * @return void
	 */
	public function activateLanguageAction(array $data) {
		$response = array('success' => FALSE);
		if (!empty($data['locale'])) {
			$response = $this->languageRepository->activateByLocale($data['locale']);
		}
		$this->view->assign('response', $response);
	}

	/**
	 * Deactivate a language
	 *
	 * @param array $data The request data
	 * @return void
	 */
	public function deactivateLanguageAction(array $data) {
		$response = array('success' => FALSE);
		if (!empty($data['locale'])) {
			$response = $this->languageRepository->deactivateByLocale($data['locale']);
		}
		$this->view->assign('response', $response);
	}

}
