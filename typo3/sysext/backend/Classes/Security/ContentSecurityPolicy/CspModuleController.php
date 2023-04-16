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

namespace TYPO3\CMS\Backend\Security\ContentSecurityPolicy;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ScopeRepository;

/**
 * Content-Security-Policy backend module view, loading the CSP lit-element and providing the current context.
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
class CspModuleController
{
    public function __construct(
        protected readonly Features $features,
        protected readonly UriBuilder $uriBuilder,
        protected readonly PageRenderer $pageRenderer,
        protected readonly ScopeRepository $scopeRepository,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly IconFactory $iconFactory,
    ) {}

    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->pageRenderer->addInlineLanguageLabelFile(
            'EXT:backend/Resources/Private/Language/Modules/content-security-policy.xlf',
            'module.',
            'module.'
        );
        $view = $this->moduleTemplateFactory->create($request);
        $this->registerDocHeaderButtons($view, $request->getAttribute('module'));
        $view->assignMultiple([
            'configurationStatus' => $this->getConfigurationStatus(),
            'scopes' => array_map(strval(...), $this->scopeRepository->findAll()),
            'controlUri' => $this->uriBuilder->buildUriFromRoutePath('/ajax/security/csp/control'),
        ]);
        return $view->renderResponse('Security/CspModule');
    }

    protected function registerDocHeaderButtons(ModuleTemplate $view, ModuleInterface $currentModule): void
    {
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier($currentModule->getIdentifier())
            ->setDisplayName($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/Modules/content-security-policy.xlf:mlang_tabs_tab'));
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        $reloadButton = $buttonBar->makeLinkButton()
            ->setDataAttributes(['csp-reports-handler' => 'refresh'])
            ->setHref((string)$this->uriBuilder->buildUriFromRoute($currentModule->getIdentifier()))
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', IconSize::SMALL));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    protected function getConfigurationStatus(): array
    {
        return [
            'featureDisabled' => array_filter([
                'backend' => [],
                'frontend' => !$this->features->isFeatureEnabled('security.frontend.enforceContentSecurityPolicy')
                    && !$this->features->isFeatureEnabled('security.frontend.reportContentSecurityPolicy')
                        ? ['enforce', 'report']
                        : [],
            ]),
            'customReporting' => array_filter([
                'BE' => $GLOBALS['TYPO3_CONF_VARS']['BE']['contentSecurityPolicyReportingUrl'] ?? '',
                'FE' => $GLOBALS['TYPO3_CONF_VARS']['FE']['contentSecurityPolicyReportingUrl'] ?? '',
            ]),
        ];
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
