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
use TYPO3\CMS\Core\Settings\SettingsTypeRegistry;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[Autoconfigure(public: true)]
readonly class SiteSettingsFactory
{
    public function __construct(
        #[Autowire('%env(TYPO3:configPath)%/sites')]
        protected string $configPath,
        protected SetRegistry $setRegistry,
        protected SettingsTypeRegistry $settingsTypeRegistry,
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
     *
     * @internal
     */
    public function loadLocalSettingsTree(string $siteIdentifier): ?array
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
     * @internal
     */
    public function createSettings(array $sets = [], ?string $siteIdentifier = null, array $inlineSettings = []): SiteSettings
    {
        $settingsTree = [];
        if ($siteIdentifier !== null) {
            $settingsTree = $this->loadLocalSettingsTree($siteIdentifier) ?? $inlineSettings;
        }

        return $this->composeSettings($settingsTree, $sets);
    }

    /**
     * @internal
     */
    public function composeSettings(array $settingsTree, array $sets): SiteSettings
    {
        $settings = [];
        $definitions = [];
        $activeSets = [];
        if (is_array($sets) && $sets !== []) {
            $activeSets = $this->setRegistry->getSets(...$sets);
        }

        foreach ($activeSets as $set) {
            foreach ($set->settingsDefinitions as $settingDefinition) {
                $definitions[] = $settingDefinition;
            }
        }

        foreach ($definitions as $settingDefinition) {
            $settings = ArrayUtility::setValueByPath($settings, $settingDefinition->key, $settingDefinition->default, '.');
        }

        foreach ($activeSets as $set) {
            ArrayUtility::mergeRecursiveWithOverrule($settings, $this->validateSettings($set->settings, $definitions));
        }

        if ($settingsTree !== []) {
            ArrayUtility::mergeRecursiveWithOverrule($settings, $this->validateSettings($settingsTree, $definitions));
        }

        /** @var array<string, string|int|float|bool|array|null> $settingsMap */
        $settingsMap = [];
        foreach ($definitions as $settingDefinition) {
            $settingsMap[$settingDefinition->key] = ArrayUtility::getValueByPath($settings, $settingDefinition->key, '.');
        }

        return SiteSettings::create(
            settingsMap: $settingsMap,
            settingsTree: $settings,
        );
    }

    /**
     * @internal
     */
    public function createSettingsForKeys(array $settingKeys, string $siteIdentifier, array $inlineSettings = []): SiteSettings
    {
        $settingsTree = $this->loadLocalSettingsTree($siteIdentifier) ?? $inlineSettings;

        /** @var array<string, string|int|float|bool|array|null> $settingsMap */
        $settingsMap = [];
        foreach ($settingKeys as $key) {
            if (!ArrayUtility::isValidPath($settingsTree, $key, '.')) {
                continue;
            }
            $settingsMap[$key] = ArrayUtility::getValueByPath($settingsTree, $key, '.');
        }

        return SiteSettings::create(
            settingsMap: $settingsMap,
            settingsTree: $settingsTree,
        );
    }

    /**
     * @internal
     */
    public function createSettingsFromFormData(array $settingsMap, array $definitions): SiteSettings
    {
        $settingsTree = [];
        foreach ($settingsMap as $key => $value) {
            $definition = $definitions[$key] ?? null;
            if ($definition === null) {
                throw new \RuntimeException('Unexpected setting ' . $key . ' is not defined', 1724067004);
            }
            $type = $this->settingsTypeRegistry->get($definition->type);
            // @todo We should collect invalid values (readonly-violation/validation-error) and report in the UI instead of ignoring them
            if ($definition->readonly || !$type->validate($value, $definition)) {
                $value = $definition->default;
            }
            $value = $type->transformValue($value, $definition);
            $settingsMap[$key] = $value;
            $settingsTree = ArrayUtility::setValueByPath($settingsTree, $key, $value, '.');
        }

        return SiteSettings::create(
            settingsMap: $settingsMap,
            settingsTree: $settingsTree,
        );
    }

    protected function validateSettings(array $settings, array $definitions): array
    {
        foreach ($definitions as $definition) {
            try {
                $value = ArrayUtility::getValueByPath($settings, $definition->key, '.');
            } catch (MissingArrayPathException) {
                continue;
            }
            if (!$this->settingsTypeRegistry->has($definition->type)) {
                throw new \RuntimeException('Setting type ' . $definition->type . ' is not defined.', 1712437727);
            }
            $type = $this->settingsTypeRegistry->get($definition->type);
            if (!$type->validate($value, $definition)) {
                $settings = ArrayUtility::removeByPath($settings, $definition->key, '.');
            }

            $newValue = $type->transformValue($value, $definition);
            if ($newValue !== $value) {
                ArrayUtility::setValueByPath($settings, $definition->key, $newValue, '.');
            }
        }

        return $settings;
    }

}
