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
use TYPO3\CMS\Form\Domain\DTO\PersistenceManagerConfiguration;
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
     * Get persistence manager settings as a typed DTO
     */
    public function getPersistenceManagerConfiguration(): PersistenceManagerConfiguration
    {
        $formSettings = $this->getFormSettings();
        return PersistenceManagerConfiguration::fromArray($formSettings['persistenceManager'] ?? []);
    }

    /**
     * Get allowed extension paths from form configuration
     *
     * @return string[] Array of allowed extension paths (e.g., ["EXT:my_extension/Configuration/Forms/"])
     */
    public function getAllowedExtensionPaths(): array
    {
        return $this->getPersistenceManagerConfiguration()->allowedExtensionPaths;
    }

    /**
     * Get allowed pages for form storage.
     *
     * Forms are always stored on pid 0 (root level).
     *
     * @return array<int, array{uid: int, title: string}> Array of allowed page IDs
     */
    public function getAllowedPages(): array
    {
        return [
            0 => [
                'uid' => 0,
                'title' => 'Root',
            ],
        ];
    }

    /**
     * Check if saving to extension paths is allowed
     */
    public function isAllowedToSaveToExtensionPaths(): bool
    {
        return $this->getPersistenceManagerConfiguration()->allowSaveToExtensionPaths;
    }

    /**
     * Check if deleting from extension paths is allowed
     */
    public function isAllowedToDeleteFromExtensionPaths(): bool
    {
        return $this->getPersistenceManagerConfiguration()->allowDeleteFromExtensionPaths;
    }

    /**
     * Get sort configuration for form listing
     *
     * @return array{sortByKeys: string[], sortAscending: bool}
     */
    public function getSortConfiguration(): array
    {
        $configuration = $this->getPersistenceManagerConfiguration();

        return [
            'sortByKeys' => $configuration->sortByKeys,
            'sortAscending' => $configuration->sortAscending,
        ];
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
