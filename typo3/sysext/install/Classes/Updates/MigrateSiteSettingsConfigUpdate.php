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

namespace TYPO3\CMS\Install\Updates;

use TYPO3\CMS\Core\Configuration\Exception\SiteConfigurationWriteException;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;

/**
 * @internal
 *
 * The upgrade wizard cuts the settings part of the config.yaml and moves it into settings.yaml.
 */
#[UpgradeWizard('migrateSiteSettings')]
class MigrateSiteSettingsConfigUpdate implements UpgradeWizardInterface
{
    protected const SETTINGS_FILENAME = 'settings.yaml';

    protected ?SiteConfiguration $siteConfiguration = null;
    protected array $sitePathsToMigrate = [];

    public function __construct()
    {
        $this->siteConfiguration = GeneralUtility::makeInstance(SiteConfiguration::class);
        $this->sitePathsToMigrate = $this->getSitePathsToMigrate();
    }

    public function getTitle(): string
    {
        return 'Migrate site settings to separate file';
    }

    public function getDescription(): string
    {
        return
            'If site settings exist in a config.yaml file, this wizard migrates them to a dedicated settings.yaml file. ' .
            'Please note that you should remove them from your existing config manually.';
    }

    public function executeUpdate(): bool
    {
        try {
            foreach ($this->sitePathsToMigrate as $siteIdentifier => $settings) {
                $this->siteConfiguration->writeSettings($siteIdentifier, $settings);
            }
        } catch (SiteConfigurationWriteException $e) {
            return false;
        }
        return true;
    }

    /**
     * if the settings file does not exist an update is considered as necessary
     */
    public function updateNecessary(): bool
    {
        return $this->sitePathsToMigrate !== [];
    }

    public function getPrerequisites(): array
    {
        return [];
    }

    /**
     * returns an array of siteconfigs, if they don't have a corresponding settings file
     */
    protected function getSitePathsToMigrate(): array
    {
        $settingsCollection = [];
        foreach ($this->siteConfiguration->getAllSiteConfigurationPaths() as $siteIdentifier => $configurationPath) {
            // Ensure site identifier is a string, even if it only consists of digits
            $siteIdentifier = (string)$siteIdentifier;
            // settings.yaml already exists, skip
            if (file_exists($configurationPath . '/' . self::SETTINGS_FILENAME)) {
                continue;
            }
            // Check if the site has any settings
            $siteConfiguration = $this->siteConfiguration->load($siteIdentifier);
            if (!isset($siteConfiguration['settings'])) {
                continue;
            }
            $settingsCollection[$siteIdentifier] = $siteConfiguration['settings'];
        }
        return $settingsCollection;
    }
}
