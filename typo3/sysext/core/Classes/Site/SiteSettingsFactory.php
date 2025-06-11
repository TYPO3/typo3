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

namespace TYPO3\CMS\Core\Site;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Settings\Settings;
use TYPO3\CMS\Core\Settings\SettingsFactory;
use TYPO3\CMS\Core\Settings\SettingsTypeRegistry;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
#[Autoconfigure(public: true)]
readonly class SiteSettingsFactory
{
    public function __construct(
        #[Autowire('%env(TYPO3:configPath)%/sites')]
        protected string $configPath,
        protected SetRegistry $setRegistry,
        protected SettingsTypeRegistry $settingsTypeRegistry,
        protected SettingsFactory $settingsFactory,
        protected YamlFileLoader $yamlFileLoader,
        #[Autowire(service: 'cache.core')]
        protected PhpFrontend $cache,
        #[Autowire(expression: 'service("package-dependent-cache-identifier").withPrefix("SiteSettings")')]
        protected PackageDependentCacheIdentifier $cacheIdentifier,
        protected string $settingsFileName = 'settings.yaml',
    ) {}

    public function getSettings(string $siteIdentifier, array $siteConfiguration): SiteSettings
    {
        $cacheIdentifier = $this->cacheIdentifier->withAdditionalHashedIdentifier(
            $siteIdentifier . '_' . json_encode($siteConfiguration)
        )->toString();

        try {
            $settings = $this->cache->require($cacheIdentifier);
            if ($settings instanceof SiteSettings) {
                return $settings;
            }
        } catch (\Error) {
        }

        $settings = $this->createSettings(
            $siteConfiguration['dependencies'] ?? [],
            $siteIdentifier,
            $siteConfiguration['settings'] ?? [],
        );
        $this->cache->set($cacheIdentifier, 'return ' . var_export($settings, true) . ';');
        return $settings;
    }

    /**
     * Load settings from config/sites/{$siteIdentifier}/settings.yaml.
     */
    public function loadLocalSettings(string $siteIdentifier): ?array
    {
        $fileName = $this->configPath . '/' . $siteIdentifier . '/' . $this->settingsFileName;
        if (!file_exists($fileName)) {
            return null;
        }

        return $this->yamlFileLoader->load(GeneralUtility::fixWindowsFilePath($fileName));
    }

    /**
     * Fetch the settings for a specific site and return the parsed Site Settings object.
     *
     * @todo This method resolves placeholders during the loading, which is okay as this is only used in context where
     *       the replacement is needed. However, this may change in the future, for example if loading is needed for
     *       implementing a GUI for the settings - which should either get a dedicated method or a flag to control if
     *       placeholder should be resolved during yaml file loading or not. The SiteConfiguration save action currently
     *       avoid calling this method.
     */
    public function createSettings(array $sets = [], ?string $siteIdentifier = null, array $inlineSettings = []): SiteSettings
    {
        $rawSettings = [];
        if ($siteIdentifier !== null) {
            $rawSettings = $this->loadLocalSettings($siteIdentifier) ?? $inlineSettings;
        }

        return $this->composeSettings($rawSettings, $sets);
    }

    public function composeSettings(array $rawSettings, array $sets): SiteSettings
    {
        return SiteSettings::create(
            $this->settingsFactory->resolveSettings(
                ...$this->getSettingsProviders($rawSettings, $sets)
            )
        );
    }

    /**
     * @return SiteSettingsProvider[]
     */
    protected function getSettingsProviders(array $settings, array $sets): array
    {
        $activeSets = [];
        if (is_array($sets) && $sets !== []) {
            $activeSets = $this->setRegistry->getSets(...$sets);
        }

        /** @var SiteSettingsProvider[] $providers */
        $providers = [];
        foreach ($activeSets as $set) {
            $providers[] = new SiteSettingsProvider($set->settings, $set->settingsDefinitions);
        }

        $providers[] = new SiteSettingsProvider($settings);

        return $providers;
    }
}
