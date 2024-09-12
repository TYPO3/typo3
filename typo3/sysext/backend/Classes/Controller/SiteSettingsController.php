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

namespace TYPO3\CMS\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Dto\Settings\EditableSetting;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Settings\Category;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Core\Settings\SettingsTypeRegistry;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Set\CategoryRegistry;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Site\SiteSettingsService;
use TYPO3\CMS\Core\SysLog\Action\Setting as SettingAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Backend controller: The "Site settings" module
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
readonly class SiteSettingsController
{
    public function __construct(
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected SiteFinder $siteFinder,
        protected SiteSettingsService $siteSettingsService,
        protected SettingsTypeRegistry $settingsTypeRegistry,
        protected CategoryRegistry $categoryRegistry,
        protected UriBuilder $uriBuilder,
        protected PageRenderer $pageRenderer,
        protected FlashMessageService $flashMessageService,
        protected IconFactory $iconFactory,
        protected ResponseFactory $responseFactory,
        protected FormProtectionFactory $formProtectionFactory,
    ) {}

    public function overviewAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $view->assign('sites', array_map(
            fn(Site $site): array => [
                'site' => $site,
                'siteTitle' => $this->getSiteTitle($site),
                'hasSettingsDefinitions' => $this->siteSettingsService->hasSettingsDefinitions($site),
                'localSettings' => $this->siteSettingsService->getLocalSettings($site),
            ],
            array_filter(
                $this->siteFinder->getAllSites(),
                static fn(Site $site): bool => $site->getSets() !== []
            )
        ));

        return $view->renderResponse('SiteSettings/Overview');
    }

    public function editAction(ServerRequestInterface $request): ResponseInterface
    {
        $identifier = $request->getQueryParams()['site'] ?? null;
        if ($identifier === null) {
            throw new \RuntimeException('Site identifier to edit must be set', 1713394528);
        }

        $returnUrl = GeneralUtility::sanitizeLocalUrl(
            (string)($request->getQueryParams()['returnUrl'] ?? '')
        ) ?: null;
        $overviewUrl = (string)$this->uriBuilder->buildUriFromRoute('site_settings');

        $site = $this->siteFinder->getSiteByIdentifier($identifier);
        $view = $this->moduleTemplateFactory->create($request);

        $settings = $this->siteSettingsService->getUncachedSettings($site);
        $setSettings = $this->siteSettingsService->getSetSettings($site);

        $categoryEnhancer = function (Category $category) use (&$categoryEnhancer, $settings, $setSettings): Category {
            return new Category(...[
                ...get_object_vars($category),
                'label' => $this->getLanguageService()->sL($category->label),
                'description' => $category->description !== null ? $this->getLanguageService()->sL($category->description) : $category->description,
                'categories' => array_map($categoryEnhancer, $category->categories),
                'settings' => array_map(
                    fn(SettingDefinition $definition): EditableSetting => new EditableSetting(
                        definition: $this->resolveSettingLabels($definition),
                        value: $settings->get($definition->key),
                        systemDefault: $setSettings->get($definition->key),
                        typeImplementation: $this->settingsTypeRegistry->get($definition->type)->getJavaScriptModule(),
                    ),
                    $category->settings
                ),
            ]);
        };

        $categories = array_map(
            $categoryEnhancer,
            $this->categoryRegistry->getCategories(...$site->getSets())
        );
        $hasSettings = count($categories) > 0;

        $this->addDocHeaderCloseAndSaveButtons($view, $site, $returnUrl ?? $overviewUrl, $hasSettings);
        if ($hasSettings) {
            $this->addDocHeaderExportButton($view, $site);
        }

        $this->addDocHeaderSiteConfigurationButton($view, $site);
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_copytoclipboard.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_sitesettings.xlf');

        $view->assign('site', $site);
        $view->assign('siteTitle', $this->getSiteTitle($site));
        $view->assign('rootPageId', $site->getRootPageId());

        $view->assign('actionUrl', (string)$this->uriBuilder->buildUriFromRoute('site_settings.save', array_filter([
            'site' => $site->getIdentifier(),
            'returnUrl' => $returnUrl,
        ], static fn(?string $v): bool => $v !== null)));
        $view->assign('returnUrl', $returnUrl);
        $view->assign('dumpUrl', (string)$this->uriBuilder->buildUriFromRoute('site_settings.dump', ['site' => $site->getIdentifier()]));
        $view->assign('categories', $categories);
        $view->assign('debug', $this->getBackendUser()->shallDisplayDebugInformation());

        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        $view->assign('formToken', $formProtection->generateToken('site_settings', 'save'));

        return $view->renderResponse('SiteSettings/Edit');
    }

    private function resolveSettingLabels(SettingDefinition $definition): SettingDefinition
    {
        $languageService = $this->getLanguageService();
        return new SettingDefinition(...[
            ...get_object_vars($definition),
            'label' => $languageService->sL($definition->label),
            'description' => $definition->description !== null ? $languageService->sL($definition->description) : null,
        ]);
    }

    public function saveAction(ServerRequestInterface $request): ResponseInterface
    {
        $identifier = $request->getQueryParams()['site'] ?? null;
        if ($identifier === null) {
            throw new \RuntimeException('Site identifier to edit must be set', 1713394529);
        }

        $site = $this->siteFinder->getSiteByIdentifier($identifier);

        $parsedBody = $request->getParsedBody();
        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        if (!$formProtection->validateToken((string)($parsedBody['formToken'] ?? ''), 'site_settings', 'save')) {
            return $this->responseFactory
                ->createResponse(400, 'Invalid request token given')
                ->withHeader('Location', (string)$this->uriBuilder->buildUriFromRoute('site_settings.edit', [
                    'site' => $site->getIdentifier(),
                ]));
        }

        $view = $this->moduleTemplateFactory->create($request);

        $returnUrl = GeneralUtility::sanitizeLocalUrl(
            (string)($parsedBody['returnUrl'] ?? '')
        ) ?: null;
        $overviewUrl = $this->uriBuilder->buildUriFromRoute('site_settings');
        $CMD = $parsedBody['CMD'] ?? '';
        $isSave = $CMD === 'save' || $CMD === 'saveclose';
        $isSaveClose = $parsedBody['CMD'] === 'saveclose';
        if (!$isSave) {
            return new RedirectResponse($returnUrl ?? $overviewUrl);
        }

        $newSettings = $this->siteSettingsService->createSettingsFromFormData($site, $parsedBody['settings'] ?? []);
        $settingsDiff = $this->siteSettingsService->computeSettingsDiff($site, $newSettings);
        $this->siteSettingsService->writeSettings($site, $settingsDiff->asArray());

        if ($settingsDiff->changes !== [] || $settingsDiff->deletions !== []) {
            $this->getBackendUser()->writelog(
                Type::SITE,
                SettingAction::CHANGE,
                SystemLogErrorClassification::MESSAGE,
                null,
                'Site settings changed for \'%s\': %s',
                [$site->getIdentifier(), json_encode($settingsDiff)],
                'site'
            );

            $languageService = $this->getLanguageService();
            $message = $languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_sitesettings.xlf:save.message.updated');
            $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, '', ContextualFeedbackSeverity::OK, true);
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $defaultFlashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }

        if ($isSaveClose) {
            return new RedirectResponse($returnUrl ?? $overviewUrl);
        }
        $editRoute = $this->uriBuilder->buildUriFromRoute('site_settings.edit', array_filter([
            'site' => $site->getIdentifier(),
            'returnUrl' => $returnUrl,
        ], static fn(?string $v): bool => $v !== null));
        return new RedirectResponse($editRoute);
    }

    public function dumpAction(ServerRequestInterface $request): ResponseInterface
    {
        $identifier = $request->getQueryParams()['site'] ?? null;
        if ($identifier === null) {
            throw new \RuntimeException('Site identifier to edit must be set', 1724772561);
        }

        $site = $this->siteFinder->getSiteByIdentifier($identifier);
        $parsedBody = $request->getParsedBody();
        $specificSetting = (string)($parsedBody['specificSetting'] ?? '');

        $minify = $specificSetting !== '' ? false : true;

        $newSettings = $this->siteSettingsService->createSettingsFromFormData($site, $parsedBody['settings'] ?? []);
        $settingsDiff = $this->siteSettingsService->computeSettingsDiff($site, $newSettings, $minify);
        $settings = $settingsDiff->asArray();
        if ($specificSetting !== '') {
            $value = ArrayUtility::getValueByPath($settings, $specificSetting, '.');
            $settings = ArrayUtility::setValueByPath([], $specificSetting, $value, '.');
        }

        $yamlContents = Yaml::dump($settings, 99, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_OBJECT_AS_MAP);

        return new JsonResponse([
            'yaml' => $yamlContents,
        ]);
    }

    protected function addDocHeaderCloseAndSaveButtons(ModuleTemplate $moduleTemplate, Site $site, string $closeUrl, bool $saveEnabled): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $closeButton = $buttonBar->makeLinkButton()
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:close'))
            ->setIcon($this->iconFactory->getIcon('actions-close', IconSize::SMALL))
            ->setShowLabelText(true)
            ->setHref($closeUrl);
        $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
        $saveButton = $buttonBar->makeInputButton()
            ->setName('CMD')
            ->setValue('save')
            ->setForm('sitesettings_form')
            ->setIcon($this->iconFactory->getIcon('actions-document-save', IconSize::SMALL))
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:save'))
            ->setShowLabelText(true)
            ->setDisabled(!$saveEnabled);
        $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 4);
    }

    protected function addDocHeaderExportButton(ModuleTemplate $moduleTemplate, Site $site): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $exportButton = $buttonBar->makeInputButton()
            ->setTitle($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_sitesettings.xlf:edit.yamlExport'))
            ->setIcon($this->iconFactory->getIcon('actions-database-export', IconSize::SMALL))
            ->setShowLabelText(true)
            ->setName('CMD')
            ->setValue('export')
            ->setForm('sitesettings_form');
        $buttonBar->addButton($exportButton, ButtonBar::BUTTON_POSITION_RIGHT, 2);
    }

    protected function addDocHeaderSiteConfigurationButton(ModuleTemplate $moduleTemplate, Site $site): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $exportButton = $buttonBar->makeLinkButton()
            ->setTitle($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_sitesettings.xlf:edit.editSiteConfiguration'))
            ->setIcon($this->iconFactory->getIcon('actions-open', IconSize::SMALL))
            ->setShowLabelText(true)
            ->setHref((string)$this->uriBuilder->buildUriFromRoute('site_configuration.edit', [
                'site' => $site->getIdentifier(),
                'returnUrl' => $this->uriBuilder->buildUriFromRoute('site_settings.edit', [
                    'site' => $site->getIdentifier(),
                ]),
            ]));
        $buttonBar->addButton($exportButton, ButtonBar::BUTTON_POSITION_RIGHT, 3);
    }

    protected function getSiteTitle(Site $site): string
    {
        $websiteTitle = $site->getConfiguration()['websiteTitle'] ?? '';
        if ($websiteTitle !== '') {
            return $websiteTitle;
        }
        $rootPage = BackendUtility::getRecord('pages', $site->getRootPageId());
        $title = $rootPage['title'] ?? '';
        if ($title !== '') {
            return $title;
        }

        return '(unknown)';
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
