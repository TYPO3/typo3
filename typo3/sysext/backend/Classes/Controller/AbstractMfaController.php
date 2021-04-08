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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract class for mfa controllers (configuration and authentication)
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
abstract class AbstractMfaController
{
    protected UriBuilder $uriBuilder;
    protected MfaProviderRegistry $mfaProviderRegistry;
    protected ModuleTemplateFactory $moduleTemplateFactory;
    protected ?ModuleTemplate $moduleTemplate;
    protected array $mfaTsConfig;
    protected bool $mfaRequired;
    protected array $allowedProviders;
    protected array $allowedActions = [];

    public function __construct(
        UriBuilder $uriBuilder,
        MfaProviderRegistry $mfaProviderRegistry,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->uriBuilder = $uriBuilder;
        $this->mfaProviderRegistry = $mfaProviderRegistry;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->initializeMfaConfiguration();
    }

    /**
     * Main action for handling the request and returning the response
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    abstract public function handleRequest(ServerRequestInterface $request): ResponseInterface;

    protected function isActionAllowed(string $action): bool
    {
        return in_array($action, $this->allowedActions, true);
    }

    protected function isProviderAllowed(string $identifier): bool
    {
        return isset($this->allowedProviders[$identifier]);
    }

    protected function isValidIdentifier(string $identifier): bool
    {
        return $identifier !== ''
            && $this->isProviderAllowed($identifier)
            && $this->mfaProviderRegistry->hasProvider($identifier);
    }

    /**
     * Initialize MFA configuration based on TSconfig and global configuration
     */
    protected function initializeMfaConfiguration(): void
    {
        $backendUser = $this->getBackendUser();
        $this->mfaTsConfig = $backendUser->getTSConfig()['auth.']['mfa.'] ?? [];

        // Set up required state based on user TSconfig and global configuration
        if (isset($this->mfaTsConfig['required'])) {
            // user TSconfig overrules global configuration
            $this->mfaRequired = (bool)$this->mfaTsConfig['required'];
        } else {
            $globalConfig = (int)($GLOBALS['TYPO3_CONF_VARS']['BE']['requireMfa'] ?? 0);
            if ($globalConfig <= 1) {
                // 0 and 1 can directly be used by type-casting to boolean
                $this->mfaRequired = (bool)$globalConfig;
            } else {
                // check the system maintainer / admin / non-admin options
                $isAdmin = $backendUser->isAdmin();
                $this->mfaRequired = ($globalConfig === 2 && !$isAdmin)
                    || ($globalConfig === 3 && $isAdmin)
                    || ($globalConfig === 4 && $backendUser->isSystemMaintainer());
            }
        }

        // Set up allowed providers based on user TSconfig and user groupData
        $this->allowedProviders = array_filter($this->mfaProviderRegistry->getProviders(), function ($identifier) use ($backendUser) {
            return $backendUser->check('mfa_providers', $identifier)
                && !GeneralUtility::inList(($this->mfaTsConfig['disableProviders'] ?? ''), $identifier);
        }, ARRAY_FILTER_USE_KEY);
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
