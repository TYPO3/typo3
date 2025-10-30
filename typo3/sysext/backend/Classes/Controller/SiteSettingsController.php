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
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\SetupSettingsViewMode;
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * View, edit, save site settings. Part of Setup module.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
readonly class SiteSettingsController
{
    public function __construct(
        protected ComponentFactory $componentFactory,
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

    public function editAction(ServerRequestInterface $request): ResponseInterface
    {
        $moduleData = $request->getAttribute('moduleData');
        $mode = SetupSettingsViewMode::tryFrom($moduleData->get('settingsMode') ?? '') ?? SetupSettingsViewMode::BASIC;
        $moduleData->set('settingsMode', $mode->value);

        $identifier = $request->getQueryParams()['site'] ?? null;
        if ($identifier === null) {
            throw new \RuntimeException('Site identifier to edit must be set', 1713394528);
        }

        $returnUrl = GeneralUtility::sanitizeLocalUrl(
            (string)($request->getQueryParams()['returnUrl'] ?? '')
        ) ?: null;
        $overviewUrl = (string)$this->uriBuilder->buildUriFromRoute('site_configuration');

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

        $this->addDocHeaderBreadcrumb($view, $site);
        $this->addDocHeaderCloseAndSaveButtons($view, $returnUrl ?? $overviewUrl, $hasSettings);
        $this->addDocHeaderViewModeButton($view, $site, $mode);
        $this->addDocHeaderSiteConfigurationButton($view, $site);
        if ($hasSettings) {
            $this->addDocHeaderExportButton($view, $mode);
        }
        // Set shortcut context - reload button is added automatically
        $view->getDocHeaderComponent()->setShortcutContext(
            'site_configuration.editSettings',
            sprintf($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_sitesettings.xlf:labels.edit'), $site->getIdentifier()),
            ['site' => $site->getIdentifier()]
        );

        $this->pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_copytoclipboard.xlf');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_sitesettings.xlf');

        $view->assign('site', $site);
        $view->assign('siteTitle', $this->getSiteTitle($site));
        $view->assign('rootPageId', $site->getRootPageId());

        $view->assign('actionUrl', (string)$this->uriBuilder->buildUriFromRoute('site_configuration.saveSettings', array_filter([
            'site' => $site->getIdentifier(),
            'returnUrl' => $returnUrl,
        ])));
        $view->assign('returnUrl', $returnUrl);
        $view->assign('dumpUrl', (string)$this->uriBuilder->buildUriFromRoute('site_configuration.dumpSettings', ['site' => $site->getIdentifier()]));
        $view->assign('categories', $categories);
        $view->assign('mode', $mode);

        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        $view->assign('formToken', $formProtection->generateToken('site_configuration', 'saveSettings'));

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
        if (!$formProtection->validateToken((string)($parsedBody['formToken'] ?? ''), 'site_configuration', 'saveSettings')) {
            return $this->responseFactory
                ->createResponse(400, 'Invalid request token given')
                ->withHeader('Location', (string)$this->uriBuilder->buildUriFromRoute('site_configuration.editSettings', [
                    'site' => $site->getIdentifier(),
                ]));
        }

        $returnUrl = GeneralUtility::sanitizeLocalUrl(
            (string)($parsedBody['returnUrl'] ?? '')
        ) ?: null;
        $overviewUrl = $this->uriBuilder->buildUriFromRoute('site_configuration');
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
            $defaultFlashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }

        if ($isSaveClose) {
            return new RedirectResponse($returnUrl ?? $overviewUrl);
        }
        $editRoute = $this->uriBuilder->buildUriFromRoute('site_configuration.editSettings', array_filter([
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
        if ($specificSetting !== '' && isset($settings[$specificSetting])) {
            $settings = [
                $specificSetting => $settings[$specificSetting],
            ];
        }

        $yamlContents = Yaml::dump($settings, 99, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE | Yaml::DUMP_OBJECT_AS_MAP);

        return new JsonResponse([
            'yaml' => $yamlContents,
        ]);
    }

    protected function addDocHeaderBreadcrumb(ModuleTemplate $moduleTemplate, Site $site): void
    {
        $record = BackendUtility::getRecord('pages', $site->getRootPageId());
        $moduleTemplate->getDocHeaderComponent()->setPageBreadcrumb($record ?? []);
    }

    protected function addDocHeaderCloseAndSaveButtons(ModuleTemplate $moduleTemplate, string $closeUrl, bool $saveEnabled): void
    {
        $moduleTemplate->addButtonToButtonBar($this->componentFactory->createCloseButton($closeUrl));
        $saveButton = $this->componentFactory->createSaveButton('sitesettings_form')
            ->setName('CMD')
            ->setValue('save')
            ->setDisabled(!$saveEnabled);
        $moduleTemplate->addButtonToButtonBar($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
    }

    protected function addDocHeaderViewModeButton(ModuleTemplate $moduleTemplate, Site $site, SetupSettingsViewMode $mode): void
    {
        $languageService = $this->getLanguageService();
        $viewModeButton = $this->componentFactory->createDropDownButton()
            ->setLabel($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view'))
            ->setShowLabelText(true);

        $viewModeButton->addItem(
            $this->componentFactory->createDropDownRadio()
                ->setActive(($mode === SetupSettingsViewMode::BASIC))
                ->setHref(
                    (string)$this->uriBuilder->buildUriFromRoute(
                        'site_configuration.editSettings',
                        array_filter([
                            'site' => $site->getIdentifier(),
                            'settingsMode' => SetupSettingsViewMode::BASIC->value,
                        ])
                    )
                )
                ->setLabel($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_settingseditor.xlf:settingseditor.mode.basic'))
                ->setIcon($this->iconFactory->getIcon('actions-window', IconSize::SMALL))
        );

        $viewModeButton->addItem(
            $this->componentFactory->createDropDownRadio()
                ->setActive(($mode === SetupSettingsViewMode::ADVANCED))
                ->setHref(
                    (string)$this->uriBuilder->buildUriFromRoute(
                        'site_configuration.editSettings',
                        array_filter([
                            'site' => $site->getIdentifier(),
                            'settingsMode' => SetupSettingsViewMode::ADVANCED->value,
                        ])
                    )
                )
                ->setLabel($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_settingseditor.xlf:settingseditor.mode.advanced'))
                ->setIcon($this->iconFactory->getIcon('actions-window-cog', IconSize::SMALL))
        );

        $moduleTemplate->addButtonToButtonBar($viewModeButton, ButtonBar::BUTTON_POSITION_RIGHT, 2);
    }

    protected function addDocHeaderExportButton(ModuleTemplate $moduleTemplate, SetupSettingsViewMode $mode): void
    {
        if ($mode === SetupSettingsViewMode::ADVANCED) {
            $languageService = $this->getLanguageService();
            $exportButton = $this->componentFactory->createInputButton()
                ->setTitle($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_sitesettings.xlf:edit.yamlExport'))
                ->setIcon($this->iconFactory->getIcon('actions-database-export', IconSize::SMALL))
                ->setShowLabelText(true)
                ->setName('CMD')
                ->setValue('export')
                ->setForm('sitesettings_form');
            $moduleTemplate->addButtonToButtonBar($exportButton, ButtonBar::BUTTON_POSITION_RIGHT);
        }
    }

    protected function addDocHeaderSiteConfigurationButton(ModuleTemplate $moduleTemplate, Site $site): void
    {
        $languageService = $this->getLanguageService();
        $exportButton = $this->componentFactory->createLinkButton()
            ->setTitle($languageService->sL('LLL:EXT:backend/Resources/Private/Language/locallang_sitesettings.xlf:edit.editSiteConfiguration'))
            ->setIcon($this->iconFactory->getIcon('actions-open', IconSize::SMALL))
            ->setShowLabelText(true)
            ->setHref((string)$this->uriBuilder->buildUriFromRoute('site_configuration.edit', [
                'site' => $site->getIdentifier(),
                'returnUrl' => $this->uriBuilder->buildUriFromRoute('site_configuration.editSettings', [
                    'site' => $site->getIdentifier(),
                ]),
            ]));
        $moduleTemplate->addButtonToButtonBar($exportButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
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
