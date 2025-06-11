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
 * @internal
 */
final readonly class SettingsFactory
{
    public function __construct(
        private SettingsTypeRegistry $settingsTypeRegistry,
    ) {}

    public function resolveSettings(SettingsProviderInterface ...$providers): SettingsInterface
    {
        /** @var SettingDefinition[] $definitions */
        $definitions = [];
        /** @var SettingValue[] $settings */
        $settings = [];
        foreach ($providers as $provider) {
            foreach ($provider->getDefinitions() as $definition) {
                $definitions[$definition->key] = $definition;
            }
            $settings = [
                ...$settings,
                ...$provider->getProvidedSettings($definitions),
            ];
        }

        /** @var array<string, string|int|float|bool|array|null> $map */
        $map = [];
        foreach (array_reverse($settings) as $setting) {
            if (array_key_exists($setting->key, $map)) {
                continue;
            }

            $value = $setting->value;
            if ($setting->definition !== null && !$this->validateAndTransformValue($value, $setting->definition)) {
                continue;
            }
            $map[$setting->key] = $value;
        }

        return new Settings($map);
    }

    public function createSettingsFromFormData(array $settings, iterable $definitions): SettingsInterface
    {
        $definitionMap = [];
        foreach ($definitions as $definition) {
            $definitionMap[$definition->key] = $definition;
        }
        foreach ($settings as $key => $value) {
            $definition = $definitionMap[$key] ?? null;
            if ($definition === null) {
                throw new \RuntimeException('Unexpected setting ' . $key . ' is not defined', 1724067004);
            }
            // @todo We should collect invalid values (readonly-violation/validation-error) and report in the UI instead of ignoring them
            if ($definition->readonly || !$this->validateAndTransformValue($value, $definition)) {
                $value = $definition->default;
            }
            $settings[$key] = $value;
        }

        return new Settings($settings);
    }

    private function validateAndTransformValue(mixed &$value, SettingDefinition $definition): bool
    {
        if (!$this->settingsTypeRegistry->has($definition->type)) {
            throw new \RuntimeException('Setting type ' . $definition->type . ' is not defined.', 1712437727);
        }
        $type = $this->settingsTypeRegistry->get($definition->type);
        if (!$type->validate($value, $definition)) {
            return false;
        }

        $value = $type->transformValue($value, $definition);
        return true;
    }
}
