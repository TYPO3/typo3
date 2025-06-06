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

namespace TYPO3\CMS\Core\Settings;

/**
 * Settings provider implementation for providing settings definitions and values.
 *
 * This provider is used internally by the Settings API to manage setting definitions
 * and their corresponding values. It combines default values from definitions with
 * runtime values provided through the settings array.
 *
 * @internal
 */
final readonly class SettingsProvider implements SettingsProviderInterface
{
    public function __construct(
        public string $name,
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
    public function getProvidedSettings(array $globalDefinitions): array
    {
        /** @var SettingValue[] $settings */
        $settings = [];
        foreach ($this->definitions as $definition) {
            $settings[] = new SettingValue(
                value: $definition->default,
                key: $definition->key,
                definition: $definition,
            );
        }

        foreach ($this->settings as $key => $value) {
            $definition = $globalDefinitions[$key] ?? null;
            if ($definition !== null) {
                $settings[] = new SettingValue(
                    value: $value,
                    key: $key,
                    definition: $definition,
                );
            }
        }

        return $settings;
    }
}
