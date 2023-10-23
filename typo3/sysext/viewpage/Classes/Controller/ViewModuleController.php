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
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyRegistry;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Controller to show a frontend page in the backend. Backend "View" module.
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class ViewModuleController
{
    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly PageRepository $pageRepository,
        protected readonly SiteFinder $siteFinder,
        protected readonly PolicyRegistry $policyRegistry,
    ) {}

    /**
     * Show selected page.
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $pageId = (int)($request->getQueryParams()['id'] ?? 0);
        $moduleData = $request->getAttribute('moduleData');
        $pageInfo = BackendUtility::readPageAccess($pageId, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));

        $view = $this->moduleTemplateFactory->create($request);
        $view->setBodyTag('<body class="typo3-module-viewpage">');
        $view->setModuleId('typo3-module-viewpage');
        $view->setTitle(
            $languageService->sL('LLL:EXT:viewpage/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
            $pageInfo['title'] ?? ''
        );

        if (!$this->isValidDoktype($pageId)) {
            $view->addFlashMessage(
                $languageService->sL('LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:noValidPageSelected'),
                '',
                ContextualFeedbackSeverity::INFO
            );
            return $view->renderResponse('Empty');
        }

        $previewLanguages = $this->getPreviewLanguages($pageId);
        if ($previewLanguages !== [] && $moduleData->clean('language', array_keys($previewLanguages))) {
            $this->getBackendUser()->pushModuleData($moduleData->getModuleIdentifier(), $moduleData->toArray());
        }
        $languageId = (int)$moduleData->get('language');
        $targetUri = PreviewUriBuilder::create($pageId)
            ->withAdditionalQueryParameters($this->getTypeParameterIfSet($pageId))
            ->withLanguage($languageId)
            ->buildUri();
        $targetUrl = (string)$targetUri;
        if ($targetUri === null || $targetUrl === '') {
            $view->addFlashMessage(
                $languageService->sL('LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:noSiteConfiguration'),
                '',
                ContextualFeedbackSeverity::WARNING
            );
            return $view->renderResponse('Empty');
        }

        $this->registerDocHeader($view, $pageId, $languageId, $targetUrl);
        $current = $moduleData->get('States')['current'] ?? [];
        $current['label'] = ($current['label'] ?? $languageService->sL('LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:custom'));
        $current['width'] = MathUtility::forceIntegerInRange($current['width'] ?? 320, 300);
        $current['height'] = MathUtility::forceIntegerInRange($current['height'] ?? 480, 300);

        $custom = $moduleData->get('States')['custom'] ?? [];
        $custom['width'] = MathUtility::forceIntegerInRange($custom['width'] ?? 320, 300);
        $custom['height'] = MathUtility::forceIntegerInRange($custom['height'] ?? 480, 300);

        $view->assignMultiple([
            'current' => $current,
            'custom' => $custom,
            'presetGroups' => $this->getPreviewPresets($pageId),
            'url' => $targetUrl,
        ]);

        if ($targetUri->getScheme() !== '' && $targetUri->getHost() !== '') {
            // temporarily(!) extend the CSP `frame-src` directive with the URL to be shown in the `<iframe>`
            $mutation = new Mutation(MutationMode::Extend, Directive::FrameSrc, UriValue::fromUri($targetUri));
            $this->policyRegistry->appendMutationCollection(new MutationCollection($mutation));
        }
        return $view->renderResponse('Show');
    }

    protected function registerDocHeader(ModuleTemplate $view, int $pageId, int $languageId, string $targetUrl)
    {
        $languageService = $this->getLanguageService();
        $languages = $this->getPreviewLanguages($pageId);
        if (count($languages) > 1) {
            $languageMenu = $view->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
            $languageMenu->setIdentifier('_langSelector');
            foreach ($languages as $value => $label) {
                $href = (string)$this->uriBuilder->buildUriFromRoute(
                    'page_preview',
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
            $view->getDocHeaderComponent()->getMenuRegistry()->addMenu($languageMenu);
        }

        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
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
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL));
        $buttonBar->addButton($showButton);

        $refreshButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setClasses('t3js-viewpage-refresh')
            ->setTitle($languageService->sL('LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:refreshPage'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT);

        // Shortcut
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('page_preview')
            ->setDisplayName($this->getShortcutTitle($pageId))
            ->setArguments(['id' => $pageId]);
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    /**
     * With page TS config it is possible to force a specific type id via mod.web_view.type for a page id or a page tree.
     * The method checks if a type is set for the given id and returns the additional GET string.
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
     * Get available presets for page id.
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
            } else {
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
     * Verifies if doktype of given page is valid - not a folder / recycler / ...
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
     * Returns the shortcut title for the current page.
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

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
