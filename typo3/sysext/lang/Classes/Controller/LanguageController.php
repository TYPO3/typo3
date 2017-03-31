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

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Menu\Menu;
use TYPO3\CMS\Backend\Template\Components\Menu\MenuItem;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Lang\Domain\Model\Extension;
use TYPO3\CMS\Lang\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Lang\Domain\Repository\LanguageRepository;
use TYPO3\CMS\Lang\Service\RegistryService;
use TYPO3\CMS\Lang\Service\TranslationService;

/**
 * Language controller
 */
class LanguageController extends ActionController
{
    /**
     * @var string
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @var BackendTemplateView
     */
    protected $view;

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
    public function injectLanguageRepository(LanguageRepository $languageRepository)
    {
        $this->languageRepository = $languageRepository;
    }

    /**
     * @param \TYPO3\CMS\Lang\Domain\Repository\ExtensionRepository $extensionRepository
     */
    public function injectExtensionRepository(ExtensionRepository $extensionRepository)
    {
        $this->extensionRepository = $extensionRepository;
    }

    /**
     * @param \TYPO3\CMS\Lang\Service\TranslationService $translationService
     */
    public function injectTranslationService(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * @param \TYPO3\CMS\Lang\Service\RegistryService $registryService
     */
    public function injectRegistryService(RegistryService $registryService)
    {
        $this->registryService = $registryService;
    }

    /**
     * List languages
     */
    public function listLanguagesAction()
    {
        $this->prepareDocHeaderMenu();
        $this->prepareDocHeaderButtons();

        $languages = $this->languageRepository->findAll();
        $this->view->assign('languages', $languages);
    }

    /**
     * List translations
     */
    public function listTranslationsAction()
    {
        $this->prepareDocHeaderMenu();

        $languages = $this->languageRepository->findSelected();
        $this->view->assign('languages', $languages);
    }

    /**
     * Returns the translations
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
     */
    public function updateLanguageAction(array $data)
    {
        $numberOfExtensionsToUpdate = 10;
        $response = [
            'success' => false,
            'progress' => 0,
        ];
        $progress = 0;
        if (!empty($data['locale'])) {
            $allCount = 0;
            for ($i = 0; $i < $numberOfExtensionsToUpdate; $i++) {
                $offset = (int)$data['count'] * $numberOfExtensionsToUpdate + $i;
                /** @var Extension $extension */
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
                if (empty($result[$data['locale']]['error'])) {
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
        // Flush language cache
        GeneralUtility::makeInstance(CacheManager::class)->getCache('l10n')->flush();
    }

    /**
     * Fetch the translation for given extension and locale
     *
     * @param array $data The request data
     */
    public function updateTranslationAction(array $data)
    {
        $response = ['success' => false];
        if (!empty($data['extension']) && !empty($data['locale'])) {
            $result = $this->translationService->updateTranslation($data['extension'], $data['locale']);
            if (empty($result[$data['extension']][$data['locale']]['error'])) {
                $response = [
                    'success' => true,
                    'result' => $result,
                ];
            }
        }
        $this->view->assign('response', $response);
        // Flush language cache
        GeneralUtility::makeInstance(CacheManager::class)->getCache('l10n')->flush();
    }

    /**
     * Activate a language
     *
     * @param array $data The request data
     */
    public function activateLanguageAction(array $data)
    {
        $response = ['success' => false];
        if (!empty($data['locale'])) {
            $response = $this->languageRepository->activateByLocale($data['locale']);
        }
        $this->view->assign('response', $response);
    }

    /**
     * Deactivate a language
     *
     * @param array $data The request data
     */
    public function deactivateLanguageAction(array $data)
    {
        $response = ['success' => false];
        if (!empty($data['locale'])) {
            $response = $this->languageRepository->deactivateByLocale($data['locale']);
        }
        $this->view->assign('response', $response);
    }

    /**
     * Remove a language
     *
     * @param array $data The request data
     */
    public function removeLanguageAction(array $data)
    {
        $response = ['success' => false];
        if (!empty($data['locale'])) {
            $response = $this->languageRepository->deactivateByLocale($data['locale']);
            $absoluteLanguagePath = GeneralUtility::getFileAbsFileName(PATH_typo3conf . 'l10n/' . $data['locale']);
            GeneralUtility::rmdir($absoluteLanguagePath, true);
        }
        $this->view->assign('response', $response);
    }

    /**
     * DocHeaderMenu
     */
    protected function prepareDocHeaderMenu()
    {
        $this->view->getModuleTemplate()->setModuleName('typo3-module-lang');
        $this->view->getModuleTemplate()->setModuleId('typo3-module-lang');

        $this->view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Lang/LanguageModule');

        $extensionKey = 'lang';
        $addJsInlineLabels = [
            'flashmessage.error',
            'flashmessage.information',
            'flashmessage.success',
            'flashmessage.multipleErrors',
            'flashmessage.updateComplete',
            'flashmessage.canceled',
            'flashmessage.languageActivated',
            'flashmessage.languageDeactivated',
            'flashmessage.languageRemoved',
            'flashmessage.noLanguageActivated',
            'flashmessage.errorOccurred',
            'table.processing',
            'table.search',
            'table.loadingRecords',
            'table.zeroRecords',
            'table.emptyTable',
            'table.dateFormat',
        ];
        foreach ($addJsInlineLabels as $key) {
            $label = LocalizationUtility::translate($key, $extensionKey);
            $this->view->getModuleTemplate()->getPageRenderer()->addInlineLanguageLabel($key, $label);
        }

        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);

        /** @var Menu $menu */
        $menu = GeneralUtility::makeInstance(Menu::class);
        $menu->setIdentifier('_languageMenu');
        $menu->setLabel($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.language'));

        /** @var MenuItem $languageListMenuItem */
        $languageListMenuItem = GeneralUtility::makeInstance(MenuItem::class);
        $action = 'listLanguages';
        $isActive = $this->request->getControllerActionName() === $action ? true : false;
        $languageListMenuItem->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang.xlf:header.languages'));
        $uri = $uriBuilder->reset()->uriFor('listLanguages', [], 'Language');
        $languageListMenuItem->setHref($uri)->setActive($isActive);

        /** @var MenuItem $translationMenuItem */
        $translationMenuItem = GeneralUtility::makeInstance(MenuItem::class);
        $action = 'listTranslations';
        $isActive = $this->request->getControllerActionName() === $action ? true : false;
        $translationMenuItem->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang.xlf:header.translations'));
        $uri = $uriBuilder->reset()->uriFor('listTranslations', [], 'Language');
        $translationMenuItem->setHref($uri)->setActive($isActive);

        $menu->addMenuItem($languageListMenuItem);
        $menu->addMenuItem($translationMenuItem);
        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * DocHeaderButtons
     */
    protected function prepareDocHeaderButtons()
    {
        // @todo: the html structure needed to operate the buttons correctly is broken now.
        // @todo: LanguageModule.js and backend.css -> div.typo3-module-lang div.menuItems

        $downloadAllButton = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
            ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-system-extension-download', Icon::SIZE_SMALL))
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang.xlf:button.downloadAll'))
            ->setClasses('menuItem updateItem t3js-button-update')
            ->setDataAttributes(['action' => 'updateActiveLanguages'])
            ->setHref('#');
        $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar()->addButton($downloadAllButton, ButtonBar::BUTTON_POSITION_LEFT);

        $cancelButton = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
            ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-close', Icon::SIZE_SMALL))
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang.xlf:button.cancel'))
            ->setClasses('menuItem cancelItem disabled t3js-button-cancel')
            ->setDataAttributes(['action' => 'cancelLanguageUpdate'])
            ->setHref('#');

        $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar()->addButton($cancelButton, ButtonBar::BUTTON_POSITION_LEFT);
    }
}
