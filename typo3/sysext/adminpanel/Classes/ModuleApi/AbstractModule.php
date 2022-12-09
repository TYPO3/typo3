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

namespace TYPO3\CMS\Adminpanel\ModuleApi;

use TYPO3\CMS\Adminpanel\Service\ConfigurationService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Abstract base class for Admin Panel Modules containing helper methods and default interface implementations
 * Extend this class when writing own admin panel modules (or implement the Interface directly)
 */
abstract class AbstractModule implements ModuleInterface, ConfigurableInterface, SubmoduleProviderInterface
{
    /** @var ModuleInterface[] */
    protected array $subModules = [];
    /** Main Configuration (from UserTSConfig, admPanel) */
    protected array $mainConfiguration;
    protected ConfigurationService $configurationService;
    protected ModuleDataStorageCollection $moduleData;

    public function injectConfigurationService(ConfigurationService $configurationService): void
    {
        $this->configurationService = $configurationService;
        $this->mainConfiguration = $this->configurationService->getMainConfiguration();
    }

    /**
     * Returns true if the module is
     * -> either enabled via TSConfig "admPanel.enable"
     * -> or any setting is overridden.
     * Override is a way to use functionality of the admin panel without displaying the admin panel to users
     * for example: hidden records or pages can be displayed by default
     */
    public function isEnabled(): bool
    {
        $identifier = $this->getIdentifier();
        $result = $this->isEnabledViaTsConfig();
        if ($this->mainConfiguration['override.'][$identifier] ?? false) {
            $result = (bool)$this->mainConfiguration['override.'][$identifier];
        }
        return $result;
    }

    public function setSubModules(array $subModules): void
    {
        $this->subModules = $subModules;
    }

    public function getSubModules(): array
    {
        return $this->subModules;
    }

    public function hasSubmoduleSettings(): bool
    {
        $hasSettings = false;
        foreach ($this->subModules as $subModule) {
            if ($subModule instanceof ModuleSettingsProviderInterface) {
                $hasSettings = true;
                break;
            }
            if ($subModule instanceof SubmoduleProviderInterface) {
                $hasSettings = $subModule->hasSubmoduleSettings();
            }
        }
        return $hasSettings;
    }

    public function setModuleData(ModuleDataStorageCollection $moduleData): void
    {
        $this->moduleData = $moduleData;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns true if TSConfig "admPanel.enable" is set for this module (or all modules)
     */
    protected function isEnabledViaTsConfig(): bool
    {
        $result = false;
        $identifier = $this->getIdentifier();
        if (!empty($this->mainConfiguration['enable.']['all'])) {
            $result = true;
        } elseif (!empty($this->mainConfiguration['enable.'][$identifier])) {
            $result = true;
        }
        return $result;
    }
}
