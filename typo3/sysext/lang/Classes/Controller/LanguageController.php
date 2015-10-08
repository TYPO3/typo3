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
class LanguageController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
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
    public function injectLanguageRepository(\TYPO3\CMS\Lang\Domain\Repository\LanguageRepository $languageRepository)
    {
        $this->languageRepository = $languageRepository;
    }

    /**
     * @param \TYPO3\CMS\Lang\Domain\Repository\ExtensionRepository $extensionRepository
     */
    public function injectExtensionRepository(\TYPO3\CMS\Lang\Domain\Repository\ExtensionRepository $extensionRepository)
    {
        $this->extensionRepository = $extensionRepository;
    }

    /**
     * @param \TYPO3\CMS\Lang\Service\TranslationService $translationService
     */
    public function injectTranslationService(\TYPO3\CMS\Lang\Service\TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * @param \TYPO3\CMS\Lang\Service\RegistryService $registryService
     */
    public function injectRegistryService(\TYPO3\CMS\Lang\Service\RegistryService $registryService)
    {
        $this->registryService = $registryService;
    }

    /**
     * List languages
     *
     * @return void
     */
    public function listLanguagesAction()
    {
        $languages = $this->languageRepository->findAll();
        $this->view->assign('languages', $languages);
    }

    /**
     * List translations
     *
     * @return void
     */
    public function listTranslationsAction()
    {
        $languages = $this->languageRepository->findSelected();
        $this->view->assign('languages', $languages);
    }

    /**
     * Returns the translations
     *
     * @return void
     */
    public function getTranslationsAction()
    {
        $this->view->assign('extensions', $this->extensionRepository->findAll());
        $this->view->assign('languages', $this->languageRepository->findSelected());
    }

    /**
     * Fetch all translations for given locale
     *
     * @param array $data The request data
     * @return void
     */
    public function updateLanguageAction(array $data)
    {
        $numberOfExtensionsToUpdate = 10;
        $response = array(
            'success'  => false,
            'progress' => 0,
        );
        if (!empty($data['locale'])) {
            $allCount = 0;
            for ($i = 0; $i < $numberOfExtensionsToUpdate; $i++) {
                $offset = (int)$data['count'] * $numberOfExtensionsToUpdate + $i;
                $extension = $this->extensionRepository->findOneByOffset($offset);
                if (empty($extension)) {
                    // No more extensions to update
                    break;
                }
                if ($allCount === 0) {
                    $allCount = (int)$this->extensionRepository->countAll();
                }
                $extensionKey = $extension->getKey();
                $result = $this->translationService->updateTranslation($extensionKey, $data['locale']);
                $progress = round((($offset + 1) * 100) / $allCount, 2);
                $response['result'][$data['locale']][$extensionKey] = $result[$data['locale']];
                if (empty($result[$extensionKey][$data['locale']]['error'])) {
                    $response['success'] = true;
                } else {
                    // Could not update an extension, stop here!
                    $response['success'] = false;
                    break;
                }
            }
        }
        if ($response['success']) {
            $this->registryService->set($data['locale'], $GLOBALS['EXEC_TIME']);
            $response['timestamp'] = $GLOBALS['EXEC_TIME'];
            $response['progress'] = $progress > 100 ? 100 : $progress;
        }
        $this->view->assign('response', $response);
    }

    /**
     * Fetch the translation for given extension and locale
     *
     * @param array $data The request data
     * @return void
     */
    public function updateTranslationAction(array $data)
    {
        $response = array('success' => false);
        if (!empty($data['extension']) && !empty($data['locale'])) {
            $result = $this->translationService->updateTranslation($data['extension'], $data['locale']);
            if (empty($result[$data['extension']][$data['locale']]['error'])) {
                $response = array(
                    'success' => true,
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
    public function activateLanguageAction(array $data)
    {
        $response = array('success' => false);
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
    public function deactivateLanguageAction(array $data)
    {
        $response = array('success' => false);
        if (!empty($data['locale'])) {
            $response = $this->languageRepository->deactivateByLocale($data['locale']);
        }
        $this->view->assign('response', $response);
    }
}
