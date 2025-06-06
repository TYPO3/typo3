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

namespace TYPO3\CMS\Dashboard\Factory;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Core\Settings\SettingDefinitionValidation;
use TYPO3\CMS\Core\Settings\SettingsFactory;
use TYPO3\CMS\Core\Settings\SettingsInterface;
use TYPO3\CMS\Core\Settings\SettingsProvider;

/**
 * Factory for creating widget settings instances.
 *
 * This factory creates Settings objects for dashboard widgets, combining default
 * values from setting definitions with instance-specific values. It handles
 * validation of setting definitions and provides utilities for creating settings
 * from form data submitted through the widget configuration interface.
 *
 * The factory supports creating settings with various options:
 * - Respecting readonly settings
 * - Omitting default values when needed
 * - Processing form data for widget configuration updates
 */
#[Autoconfigure(public: true)]
readonly class WidgetSettingsFactory
{
    public function __construct(
        protected SettingsFactory $settingsFactory,
        protected SettingDefinitionValidation $settingDefinitionValidation,
    ) {}

    /**
     * @param SettingDefinition[] $definitions
     */
    public function createSettings(
        string $name,
        array $settings,
        array $definitions,
        bool $respectReadonly = false,
        bool $omitDefaults = false,
    ): SettingsInterface {
        $defaultSettings = [];
        foreach ($definitions as $definition) {
            $this->settingDefinitionValidation->validate($definition);
            if (!$omitDefaults) {
                $defaultSettings[$definition->key] = $definition->default;
            }
        }
        return $this->settingsFactory->resolveSettings(
            new SettingsProvider($name, $defaultSettings, $definitions),
            new SettingsProvider($name . ':instance', $settings, []),
        );
    }

    public function createSettingsFromFormData(array $settings, array $definitions): SettingsInterface
    {
        return $this->settingsFactory->createSettingsFromFormData($settings, $definitions);
    }
}
