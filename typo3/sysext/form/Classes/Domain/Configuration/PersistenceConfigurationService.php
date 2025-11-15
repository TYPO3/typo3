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

namespace TYPO3\CMS\Form\Domain\Configuration;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface as ExtbaseConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as ExtFormConfigurationManagerInterface;

/**
 * Service for accessing form storage configuration (persistenceManager settings)
 *
 * This service provides a clean interface to access form storage related settings
 * from the YAML configuration without coupling every component to the configuration
 * loading mechanism.
 *
 * @internal
 */
#[Autoconfigure(public: true)]
final readonly class PersistenceConfigurationService
{
    public function __construct(
        #[Autowire(lazy: true)]
        private ExtbaseConfigurationManagerInterface $extbaseConfigurationManager,
        #[Autowire(lazy: ExtFormConfigurationManagerInterface::class)]
        private ExtFormConfigurationManagerInterface $extFormConfigurationManager,
    ) {}

    /**
     * Get all form settings
     */
    public function getFormSettings(): array
    {
        $isFrontend = $this->isFrontendRequest();
        $request = $this->getCurrentRequest();

        $typoScriptSettings = $this->extbaseConfigurationManager->getConfiguration(
            ExtbaseConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
            'form'
        );

        return $this->extFormConfigurationManager->getYamlConfiguration(
            $typoScriptSettings,
            $isFrontend,
            $isFrontend ? $request : null
        );
    }

    /**
     * Get persistence manager settings
     */
    public function getPersistenceManagerSettings(): array
    {
        $formSettings = $this->getFormSettings();
        return $formSettings['persistenceManager'] ?? [];
    }

    /**
     * Get allowed file mounts from form configuration
     *
     * @return string[] Array of allowed file mount paths (e.g., ["1:/forms/", "2:/user_forms/"])
     */
    public function getAllowedFileMounts(): array
    {
        $persistenceSettings = $this->getPersistenceManagerSettings();
        $allowedFileMounts = $persistenceSettings['allowedFileMounts'] ?? [];
        return is_array($allowedFileMounts) ? $allowedFileMounts : [];
    }

    /**
     * Get allowed extension paths from form configuration
     *
     * @return string[] Array of allowed extension paths (e.g., ["EXT:my_ext/Configuration/Forms/"])
     */
    public function getAllowedExtensionPaths(): array
    {
        $persistenceSettings = $this->getPersistenceManagerSettings();
        $allowedExtensionPaths = $persistenceSettings['allowedExtensionPaths'] ?? [];

        return is_array($allowedExtensionPaths) ? $allowedExtensionPaths : [];
    }

    /**
     * Check if saving to extension paths is allowed
     */
    public function isAllowedToSaveToExtensionPaths(): bool
    {
        $persistenceSettings = $this->getPersistenceManagerSettings();
        return (bool)($persistenceSettings['allowSaveToExtensionPaths'] ?? false);
    }

    /**
     * Check if deleting from extension paths is allowed
     */
    public function isAllowedToDeleteFromExtensionPaths(): bool
    {
        $persistenceSettings = $this->getPersistenceManagerSettings();
        return (bool)($persistenceSettings['allowDeleteFromExtensionPaths'] ?? false);
    }

    /**
     * Get sort configuration for form listing
     *
     * @return array{sortByKeys: string[], sortAscending: bool}
     */
    public function getSortConfiguration(): array
    {
        $persistenceSettings = $this->getPersistenceManagerSettings();

        return [
            'sortByKeys' => $persistenceSettings['sortByKeys'] ?? ['name', 'fileUid'],
            'sortAscending' => (bool)($persistenceSettings['sortAscending'] ?? true),
        ];
    }

    /**
     * Get default storage path for new forms
     */
    public function getDefaultStoragePath(): string
    {
        $allowedFileMounts = $this->getAllowedFileMounts();

        // Return first allowed file mount as default
        if (!empty($allowedFileMounts)) {
            return rtrim($allowedFileMounts[0], '/') . '/';
        }

        // Fallback
        return '1:/forms/';
    }

    /**
     * Get database storage settings
     */
    public function getDatabaseStorageSettings(): array
    {
        $persistenceSettings = $this->getPersistenceManagerSettings();

        return [
            'enabled' => (bool)($persistenceSettings['allowDatabaseStorage'] ?? true),
            'table' => $persistenceSettings['databaseStorageTable'] ?? 'tx_form_domain_model_formdefinition',
            'pid' => (int)($persistenceSettings['databaseStoragePid'] ?? 0),
        ];
    }

    /**
     * Get specific setting from persistence manager configuration
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $persistenceSettings = $this->getPersistenceManagerSettings();
        return $persistenceSettings[$key] ?? $default;
    }

    /**
     * Check if current request is a frontend request
     */
    private function isFrontendRequest(): bool
    {
        $request = $this->getCurrentRequest();

        if ($request !== null) {
            return ApplicationType::fromRequest($request)->isFrontend();
        }

        return false;
    }

    /**
     * Get current request from globals
     */
    private function getCurrentRequest(): ?ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? null;
    }
}
