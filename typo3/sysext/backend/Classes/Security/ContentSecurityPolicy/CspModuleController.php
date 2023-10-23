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
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ScopeRepository;

/**
 * Content-Security-Policy backend module view, loading the CSP lit-element and providing the current context.
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
#[Controller]
class CspModuleController
{
    public function __construct(
        protected readonly Features $features,
        protected readonly UriBuilder $uriBuilder,
        protected readonly PageRenderer $pageRenderer,
        protected readonly ScopeRepository $scopeRepository,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {}

    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->pageRenderer->addInlineLanguageLabelFile(
            'EXT:backend/Resources/Private/Language/Modules/content-security-policy.xlf',
            'module.',
            'module.'
        );
        $view = $this->moduleTemplateFactory->create($request);
        $view->assignMultiple([
            'configurationStatus' => $this->getConfigurationStatus(),
            'scopes' => array_map(strval(...), $this->scopeRepository->findAll()),
            'controlUri' => $this->uriBuilder->buildUriFromRoutePath('/ajax/security/csp/control'),
        ]);
        return $view->renderResponse('Security/CspModule');
    }

    protected function getConfigurationStatus(): array
    {
        return [
            'featureDisabled' => array_filter([
                'backend' => !$this->features->isFeatureEnabled('security.backend.enforceContentSecurityPolicy'),
                'frontend' => !$this->features->isFeatureEnabled('security.frontend.enforceContentSecurityPolicy'),
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
}
