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

use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Core\Settings\SettingsProviderInterface;
use TYPO3\CMS\Core\Settings\SettingValue;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * @internal
 */
final readonly class SiteSettingsProvider implements SettingsProviderInterface
{
    public function __construct(
        private array $settings,
        private array $definitions = [],
    ) {}

    /**
     * @return SettingDefinition[]
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    /**
     * @return SettingValue[]
     */
    public function getProvidedSettings(array $currentDefinitions): array
    {
        // Obtain default settings
        /** @var SettingValue[] $defaultSettings */
        $defaultSettings = [];
        foreach ($this->definitions as $definition) {
            $defaultSettings[] = new SettingValue(
                value: $definition->default,
                key: $definition->key,
                definition: $definition,
            );
        }

        // Obtain defined setting values from map presentation
        /** @var SettingValue[] $settings */
        $settings = [];
        $treeSettings = $this->settings;
        foreach ($this->settings as $key => $value) {
            $definition = $currentDefinitions[$key] ?? null;
            if ($definition !== null) {
                $settings[] = new SettingValue(
                    value: $value,
                    key: $key,
                    definition: $definition,
                );
                // A setting that is defined, is not to be interpreted as an anonymous legacy tree setting
                // (otherwise the key would be duplicated, but with dots being escaped)
                unset($treeSettings[$key]);
            }
        }

        // Obtain defined setting values from tree presentation
        /** @var SettingValue[] $legacySettings */
        $legacySettings = [];
        foreach ($currentDefinitions as $definition) {
            if (!ArrayUtility::isValidPath($treeSettings, $definition->key, '.')) {
                continue;
            }
            $value = ArrayUtility::getValueByPath($treeSettings, $definition->key, '.');
            $treeSettings = ArrayUtility::removeByPath($treeSettings, $definition->key, '.');
            $legacySettings[] = new SettingValue(
                value: $value,
                key: $definition->key,
                definition: $definition,
            );
        }

        // Derive anonymous setting values from tree by mapping tree nodes to dots
        $flatSettings = ArrayUtility::flattenPlain($treeSettings);
        foreach ($flatSettings as $key => $value) {
            $legacySettings[] = new SettingValue(
                value: $value,
                key: $key,
                definition: null,
            );
        }

        return [
            ...$defaultSettings,
            ...$legacySettings,
            ...$settings,
        ];
    }
}
