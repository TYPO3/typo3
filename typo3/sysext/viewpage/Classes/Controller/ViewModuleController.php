<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Viewpage\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\UnableToLinkToPageException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Controller for viewing the frontend
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class ViewModuleController
{
    protected ModuleTemplateFactory $moduleTemplateFactory;
    protected IconFactory $iconFactory;
    protected PageRenderer $pageRenderer;
    protected UriBuilder $uriBuilder;
    protected PageRepository $pageRepository;
    protected SiteFinder $siteFinder;

    protected ?ModuleTemplate $moduleTemplate = null;
    protected StandaloneView $view;

    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory,
        IconFactory $iconFactory,
        PageRenderer $pageRenderer,
        UriBuilder $uriBuilder,
        PageRepository $pageRepository,
        SiteFinder $siteFinder
    ) {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->iconFactory = $iconFactory;
        $this->pageRenderer = $pageRenderer;
        $this->uriBuilder = $uriBuilder;
        $this->pageRepository = $pageRepository;
        $this->siteFinder = $siteFinder;
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
    }

    /**
     * Initialize view
     *
     * @param string $templateName
     */
    protected function initializeView(string $templateName)
    {
        $this->view->getRequest()->setControllerExtensionName('Viewpage');
        $this->view->setTemplate($templateName);
        $this->view->setTemplateRootPaths(['EXT:viewpage/Resources/Private/Templates/ViewModule']);
        $this->view->setPartialRootPaths(['EXT:viewpage/Resources/Private/Partials']);
        $this->view->setLayoutRootPaths(['EXT:viewpage/Resources/Private/Layouts']);
    }

    /**
     * Register the doc header

     * @param int $pageId
     * @param int $languageId
     * @param string $targetUrl
     * @param string $route
     */
    protected function registerDocHeader(int $pageId, int $languageId, string $targetUrl, string $route)
    {
        $languages = $this->getPreviewLanguages($pageId);
        if (count($languages) > 1) {
            $languageMenu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
            $languageMenu->setIdentifier('_langSelector');
            foreach ($languages as $value => $label) {
                $href = (string)$this->uriBuilder->buildUriFromRoute(
                    'web_ViewpageView',
                    [
                        'id' => $pageId,
                        'language' => (int)$value,
                    ]
                );
                $menuItem = $languageMenu->makeMenuItem()
                    ->setTitle($label)
                    ->setHref($href);
                if ($languageId === (int)$value) {
                    $menuItem->setActive(true);
                }
                $languageMenu->addMenuItem($menuItem);
            }
            $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($languageMenu);
        }

        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $showButton = $buttonBar->makeLinkButton()
            ->setHref($targetUrl)
            ->setDataAttributes([
                'dispatch-action' => 'TYPO3.WindowManager.localOpen',
                'dispatch-args' => GeneralUtility::jsonEncodeForHtmlAttribute([
                    $targetUrl,
                    true, // switchFocus
                    'newTYPO3frontendWindow', // windowName,
                ]),
            ])
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
            ->setIcon($this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL));
        $buttonBar->addButton($showButton);

        $refreshButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setClasses('t3js-viewpage-refresh')
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:refreshPage'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT, 1);

        // Shortcut
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('web_ViewpageView')
            ->setDisplayName($this->getShortcutTitle($pageId))
            ->setArguments(['id' => $pageId]);
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * Show selected page from pagetree in iframe
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Core\Exception
     */
    public function showAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->getLanguageService()->includeLLFile('EXT:viewpage/Resources/Private/Language/locallang.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:viewpage/Resources/Private/Language/locallang.xlf');
        $pageId = (int)($request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0);

        $this->initializeView('show');
        $this->moduleTemplate->setBodyTag('<body class="typo3-module-viewpage">');
        $this->moduleTemplate->setModuleId('typo3-module-viewpage');

        $pageinfo = BackendUtility::readPageAccess($pageId, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));

        $this->moduleTemplate->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:viewpage/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
            $pageinfo['title'] ?? ''
        );

        if (!$this->isValidDoktype($pageId)) {
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $this->getLanguageService()->getLL('noValidPageSelected'),
                '',
                FlashMessage::INFO
            );
            return $this->renderFlashMessage($flashMessage);
        }

        $languageId = $this->getCurrentLanguage($pageId, $request->getParsedBody()['language'] ?? $request->getQueryParams()['language'] ?? null);
        try {
            $targetUrl = BackendUtility::getPreviewUrl(
                $pageId,
                '',
                null,
                '',
                '',
                $this->getTypeParameterIfSet($pageId) . '&L=' . $languageId
            );
        } catch (UnableToLinkToPageException $e) {
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $this->getLanguageService()->getLL('noSiteConfiguration'),
                '',
                FlashMessage::WARNING
            );
            return $this->renderFlashMessage($flashMessage);
        }

        $this->registerDocHeader($pageId, $languageId, $targetUrl, $request->getQueryParams()['route']);

        $backendUser = $this->getBackendUser();
        $icons = [];
        $icons['orientation'] = $this->iconFactory->getIcon('actions-device-orientation-change', Icon::SIZE_SMALL)->render('inline');
        $icons['fullscreen'] = $this->iconFactory->getIcon('actions-fullscreen', Icon::SIZE_SMALL)->render('inline');
        $icons['expand'] = $this->iconFactory->getIcon('actions-expand', Icon::SIZE_SMALL)->render('inline');
        $icons['desktop'] = $this->iconFactory->getIcon('actions-device-desktop', Icon::SIZE_SMALL)->render('inline');
        $icons['tablet'] = $this->iconFactory->getIcon('actions-device-tablet', Icon::SIZE_SMALL)->render('inline');
        $icons['mobile'] = $this->iconFactory->getIcon('actions-device-mobile', Icon::SIZE_SMALL)->render('inline');
        $icons['unidentified'] = $this->iconFactory->getIcon('actions-device-unidentified', Icon::SIZE_SMALL)->render('inline');

        $current = $backendUser->uc['moduleData']['web_view']['States']['current'] ?? [];
        $current['label'] = ($current['label'] ?? $this->getLanguageService()->sL('LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:custom'));
        $current['width'] = (isset($current['width']) && (int)$current['width'] >= 300 ? (int)$current['width'] : 320);
        $current['height'] = (isset($current['height']) && (int)$current['height'] >= 300 ? (int)$current['height'] : 480);

        $custom = $backendUser->uc['moduleData']['web_view']['States']['custom'] ?? [];
        $custom['width'] = (isset($current['custom']) && (int)$current['custom'] >= 300 ? (int)$current['custom'] : 320);
        $custom['height'] = (isset($current['custom']) && (int)$current['custom'] >= 300 ? (int)$current['custom'] : 480);

        $this->view->assign('icons', $icons);
        $this->view->assign('current', $current);
        $this->view->assign('custom', $custom);
        $this->view->assign('presetGroups', $this->getPreviewPresets($pageId));
        $this->view->assign('url', $targetUrl);

        $this->moduleTemplate->setContent($this->view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    protected function renderFlashMessage(FlashMessage $flashMessage): HtmlResponse
    {
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);

        $this->moduleTemplate->setContent($this->view->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * With page TS config it is possible to force a specific type id via mod.web_view.type
     * for a page id or a page tree.
     * The method checks if a type is set for the given id and returns the additional GET string.
     *
     * @param int $pageId
     * @return string
     */
    protected function getTypeParameterIfSet(int $pageId): string
    {
        $typeParameter = '';
        $typeId = (int)(BackendUtility::getPagesTSconfig($pageId)['mod.']['web_view.']['type'] ?? 0);
        if ($typeId > 0) {
            $typeParameter = '&type=' . $typeId;
        }
        return $typeParameter;
    }

    /**
     * Get available presets for page id
     *
     * @param int $pageId
     * @return array
     */
    protected function getPreviewPresets(int $pageId): array
    {
        $presetGroups = [
            'desktop' => [],
            'tablet' => [],
            'mobile' => [],
            'unidentified' => [],
        ];
        $previewFrameWidthConfig = BackendUtility::getPagesTSconfig($pageId)['mod.']['web_view.']['previewFrameWidths.'] ?? [];
        foreach ($previewFrameWidthConfig as $item => $conf) {
            $data = [
                'key' => substr($item, 0, -1),
                'label' => $conf['label'] ?? null,
                'type' => $conf['type'] ?? 'unknown',
                'width' => (isset($conf['width']) && (int)$conf['width'] > 0 && !str_contains($conf['width'], '%')) ? (int)$conf['width'] : null,
                'height' => (isset($conf['height']) && (int)$conf['height'] > 0 && !str_contains($conf['height'], '%')) ? (int)$conf['height'] : null,
            ];
            $width = (int)substr($item, 0, -1);
            if (!isset($data['width']) && $width > 0) {
                $data['width'] = $width;
            }
            if (!isset($data['label'])) {
                $data['label'] = $data['key'];
            } elseif (strpos($data['label'], 'LLL:') === 0) {
                $data['label'] = $this->getLanguageService()->sL(trim($data['label']));
            }

            if (array_key_exists($data['type'], $presetGroups)) {
                $presetGroups[$data['type']][$data['key']] = $data;
            } else {
                $presetGroups['unidentified'][$data['key']] = $data;
            }
        }

        return $presetGroups;
    }

    /**
     * Returns the preview languages
     *
     * @param int $pageId
     * @return array
     */
    protected function getPreviewLanguages(int $pageId): array
    {
        $languages = [];
        $modSharedTSconfig = BackendUtility::getPagesTSconfig($pageId)['mod.']['SHARED.'] ?? [];
        if (($modSharedTSconfig['view.']['disableLanguageSelector'] ?? false) === '1') {
            return $languages;
        }

        try {
            $site = $this->siteFinder->getSiteByPageId($pageId);
            $siteLanguages = $site->getAvailableLanguages($this->getBackendUser(), false, $pageId);

            foreach ($siteLanguages as $siteLanguage) {
                $languageAspectToTest = LanguageAspectFactory::createFromSiteLanguage($siteLanguage);
                $page = $this->pageRepository->getPageOverlay($this->pageRepository->getPage($pageId), $siteLanguage->getLanguageId());

                if ($this->pageRepository->isPageSuitableForLanguage($page, $languageAspectToTest)) {
                    $languages[$siteLanguage->getLanguageId()] = $siteLanguage->getTitle();
                }
            }
        } catch (SiteNotFoundException $e) {
            // do nothing
        }
        return $languages;
    }

    /**
     * Returns the current language
     *
     * @param int $pageId
     * @param string $languageParam
     * @return int
     */
    protected function getCurrentLanguage(int $pageId, string $languageParam = null): int
    {
        $languageId = (int)$languageParam;
        if ($languageParam === null) {
            $states = $this->getBackendUser()->uc['moduleData']['web_view']['States'] ?? [];
            $languages = $this->getPreviewLanguages($pageId);
            if (isset($states['languageSelectorValue']) && isset($languages[$states['languageSelectorValue']])) {
                $languageId = (int)$states['languageSelectorValue'];
            }
        } else {
            $this->getBackendUser()->uc['moduleData']['web_view']['States']['languageSelectorValue'] = $languageId;
            $this->getBackendUser()->writeUC();
        }
        return $languageId;
    }

    /**
     * Verifies if doktype of given page is valid
     *
     * @param int $pageId
     * @return bool
     */
    protected function isValidDoktype(int $pageId = 0): bool
    {
        if ($pageId === 0) {
            return false;
        }

        $page = BackendUtility::getRecord('pages', $pageId);
        $pageType = (int)($page['doktype'] ?? 0);

        return $pageType !== 0
            && !in_array($pageType, [
                PageRepository::DOKTYPE_SPACER,
                PageRepository::DOKTYPE_SYSFOLDER,
                PageRepository::DOKTYPE_RECYCLER,
            ], true);
    }

    /**
     * Returns the shortcut title for the current page
     *
     * @param int $pageId
     * @return string
     */
    protected function getShortcutTitle(int $pageId): string
    {
        $pageTitle = '';
        $pageRow = BackendUtility::getRecord('pages', $pageId) ?? [];
        if ($pageRow !== []) {
            $pageTitle = BackendUtility::getRecordTitle('pages', $pageRow);
        }
        return sprintf(
            '%s: %s [%d]',
            $this->getLanguageService()->sL('LLL:EXT:viewpage/Resources/Private/Language/locallang_mod.xlf:mlang_labels_tablabel'),
            $pageTitle,
            $pageId
        );
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
