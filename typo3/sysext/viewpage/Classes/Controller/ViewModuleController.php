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
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Context\PageContext;
use TYPO3\CMS\Backend\Context\PageContextFactory;
use TYPO3\CMS\Backend\Domain\Model\Language\LanguageItem;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyRegistry;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Controller to show a frontend page in the backend. Backend "Content > Preview" module.
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
final class ViewModuleController
{
    protected PageContext $pageContext;

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly PageRepository $pageRepository,
        protected readonly PolicyRegistry $policyRegistry,
        protected readonly ComponentFactory $componentFactory,
        protected readonly PageContextFactory $pageContextFactory,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $pageContext = $request->getAttribute('pageContext');
        if (!$pageContext instanceof PageContext) {
            throw new \RuntimeException('Required PageContext not available', 1763630591);
        }
        $this->pageContext = $pageContext;

        $languageService = $this->getLanguageService();
        $view = $this->moduleTemplateFactory->create($request);
        $view->setModuleId('typo3-module-viewpage');
        $view->setTitle(
            $languageService->translate('title', 'viewpage.module'),
            $this->pageContext->getPageTitle()
        );

        if ($this->pageContext->isAccessible()) {
            $view->getDocHeaderComponent()->setPageBreadcrumb($this->pageContext->pageRecord);
        }

        if (!$this->isValidPage()) {
            $view->getDocHeaderComponent()->disableAutomaticReloadButton();
            return $view->assign('info', $languageService->sL('LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:noValidPageSelected'))->renderResponse('Empty');
        }

        $previewLanguages = $this->getPreviewLanguages();
        $languageId = $this->pageContext->getPrimaryLanguageId();
        if (!isset($previewLanguages[$languageId])) {
            // Fall back to 0 in case currently selected language is not allowed
            $languageId = 0;
            $this->pageContext = $this->pageContextFactory->createWithLanguages(
                $request,
                $this->pageContext->pageId,
                [$languageId],
                $this->getBackendUser()
            );
        }

        $targetUri = PreviewUriBuilder::create($this->pageContext->pageId)
            ->withAdditionalQueryParameters((($typeId = (int)($this->pageContext->getModuleTsConfig('web_view')['type'] ?? 0)) > 0) ? '&type=' . $typeId : '')
            ->withLanguage($languageId)
            ->buildUri();
        $targetUrl = (string)$targetUri;
        if ($targetUri === null || $targetUrl === '') {
            return $view->assign('info', $languageService->sL('LLL:EXT:viewpage/Resources/Private/Language/locallang.xlf:noValidPageSelected'))->renderResponse('Empty');
        }

        $this->registerDocHeader($view, $previewLanguages, $languageId, $targetUrl);
        $moduleData = $request->getAttribute('moduleData');
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
            'presetGroups' => $this->getPreviewPresets(),
            'url' => $targetUrl,
        ]);

        if ($targetUri->getScheme() !== '' && $targetUri->getHost() !== '') {
            // temporarily(!) extend the CSP `frame-src` directive with the URL to be shown in the `<iframe>`
            $mutation = new Mutation(MutationMode::Extend, Directive::FrameSrc, UriValue::fromUri($targetUri));
            $this->policyRegistry->appendMutationCollection(new MutationCollection($mutation));
        }
        return $view->renderResponse('Show');
    }

    protected function registerDocHeader(ModuleTemplate $view, array $previewLanguages, int $languageId, string $targetUrl): void
    {
        $languageService = $this->getLanguageService();
        if (count($previewLanguages) > 1) {
            $languageDropDownButton = $this->componentFactory->createDropDownButton()
                ->setLabel($languageService->sL('core.core:labels.language'))
                ->setShowActiveLabelText(true)
                ->setShowLabelText(true);

            foreach ($previewLanguages as $value => $language) {
                $href = (string)$this->uriBuilder->buildUriFromRoute(
                    'page_preview',
                    [
                        'id' => $this->pageContext->pageId,
                        'languages' => [(int)$value],
                    ]
                );
                $languageItem = $this->componentFactory->createDropDownRadio()
                    ->setLabel($language['title'])
                    ->setHref($href)
                    ->setActive($languageId === (int)$value);
                if (!empty($language['flagIcon'])) {
                    $languageItem->setIcon($this->iconFactory->getIcon($language['flagIcon']));
                }
                $languageDropDownButton->addItem($languageItem);
            }
            $view->getDocHeaderComponent()->setLanguageSelector($languageDropDownButton);
        }
        $showButton = $this->componentFactory->createLinkButton()
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
            ->setIcon($this->iconFactory->getIcon('actions-view-page', IconSize::SMALL));
        $view->addButtonToButtonBar($showButton);

        // Shortcut
        $view->getDocHeaderComponent()->setShortcutContext(
            routeIdentifier: 'page_preview',
            displayName: sprintf(
                '%s: %s [%d]',
                $this->getLanguageService()->translate('short_description', 'viewpage.module'),
                $this->pageContext->getPageTitle(),
                $this->pageContext->pageId
            ),
            arguments: ['id' => $this->pageContext->pageId, 'languages' => [$languageId]],
        );
    }

    protected function getPreviewPresets(): array
    {
        $presetGroups = [
            'desktop' => [],
            'tablet' => [],
            'mobile' => [],
            'unidentified' => [],
        ];
        $previewFrameWidthConfig = $this->pageContext->getModuleTsConfig('web_view')['previewFrameWidths'] ?? [];
        foreach ($previewFrameWidthConfig as $item => $conf) {
            $data = [
                'key' => (string)$item,
                'label' => $conf['label'] ?? null,
                'type' => $conf['type'] ?? 'unknown',
                'width' => (isset($conf['width']) && (int)$conf['width'] > 0 && !str_contains($conf['width'], '%')) ? (int)$conf['width'] : null,
                'height' => (isset($conf['height']) && (int)$conf['height'] > 0 && !str_contains($conf['height'], '%')) ? (int)$conf['height'] : null,
            ];
            if (!isset($data['width']) && ($width = (int)$item) > 0) {
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

    protected function getPreviewLanguages(): array
    {
        $languages = [];
        $modSharedTSconfig = $this->pageContext->getModuleTsConfig('SHARED');
        if (($modSharedTSconfig['view']['disableLanguageSelector'] ?? false) === '1') {
            return $languages;
        }
        $languageItems = array_filter($this->pageContext->languageInformation->languageItems, static fn(LanguageItem $languageItem): bool => $languageItem->isAvailable());
        foreach ($languageItems as $languageItem) {
            $languageAspectToTest = LanguageAspectFactory::createFromSiteLanguage($languageItem->siteLanguage);
            $page = $this->pageRepository->getPageOverlay($this->pageRepository->getPage($this->pageContext->pageId), $languageItem->getLanguageId());
            if ($this->pageRepository->isPageSuitableForLanguage($page, $languageAspectToTest)) {
                $languages[$languageItem->getLanguageId()] = [
                    'title' => $languageItem->getTitle(),
                    'flagIcon' => $languageItem->getFlagIdentifier(),
                ];
            }
        }
        return $languages;
    }

    /**
     * Verifies if page itself and also the doktype is valid - not a folder / spacer / ...
     */
    protected function isValidPage(): bool
    {
        if (!$this->pageContext->isAccessible() || $this->pageContext->pageId === 0) {
            return false;
        }
        $pageType = (int)($this->pageContext->pageRecord['doktype'] ?? 0);
        return $pageType !== 0
            && !in_array($pageType, [
                PageRepository::DOKTYPE_SPACER,
                PageRepository::DOKTYPE_SYSFOLDER,
            ], true);
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
