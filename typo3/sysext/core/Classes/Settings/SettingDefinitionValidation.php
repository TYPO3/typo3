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
final readonly class SettingDefinitionValidation
{
    public function __construct(
        private SettingsTypeRegistry $settingsTypeRegistry,
    ) {}

    /**
     * @throws InvalidSettingDefinitionException
     */
    public function validate(SettingDefinition $definition): void
    {
        if (!$this->settingsTypeRegistry->has($definition->type)) {
            throw new InvalidSettingDefinitionException('Invalid settings type "' . $definition->type . '" in setting "' . $definition->key . '"', 1732181103);
        }

        $type = $this->settingsTypeRegistry->get($definition->type);

        // Only validate options if type supports them
        if ($type instanceof SettingsTypeOptionAwareInterface) {
            $this->validateSettingsTypeOptions($definition, $type);
        }

        // Validate default value
        if (!$type->validate($definition->default, $definition)) {
            throw new InvalidSettingDefinitionException('Invalid default value in setting "' . $definition->key . '"', 1732181102);
        }
    }

    private function validateSettingsTypeOptions(
        SettingDefinition $definition,
        SettingsTypeInterface & SettingsTypeOptionAwareInterface $type
    ): void {
        $supportedOptions = $type->getSupportedOptions();

        $options = $definition->options;
        foreach ($supportedOptions as $optionName => $optionDefinition) {
            if (!array_key_exists($optionName, $options)) {
                if ($optionDefinition->required) {
                    throw new InvalidSettingDefinitionException(
                        'Required option "' . $optionName . '" missing for type "' . $definition->type . '" in setting "' . $definition->key . '"',
                        1732181110
                    );
                }
                continue;
            }

            $value = $definition->options[$optionName];
            $isValid = match ($optionDefinition->type) {
                'string' => is_string($value),
                'int' => is_int($value),
                'number' => is_int($value) || is_float($value),
                'bool' => is_bool($value),
                'array' => is_array($value),
                default => throw new InvalidSettingDefinitionException('Unsupported settings type option: ' . $optionDefinition->type, 1734513842),
            };

            if (!$isValid) {
                throw new InvalidSettingDefinitionException(
                    'Invalid value for option "' . $optionName . '" in setting "' . $definition->key . '": expected ' . $optionDefinition->type . ', got ' . gettype($value),
                    1732181109
                );
            }
            unset($options[$optionName]);
        }

        if ($options !== []) {
            throw new InvalidSettingDefinitionException(
                'Unsupported options [' . implode(', ', array_keys($options)) . '] for type "' . $definition->type . '" in setting "' . $definition->key . '". Supported options: [' . implode(', ', array_keys($supportedOptions)) . ']',
                1732181108
            );
        }

        if (!$type->validateOptions($definition)) {
            throw new InvalidSettingDefinitionException(
                'Setting definition options for type "' . $definition->type . '" in setting "' . $definition->key . '" could not be validated.',
                1752821671
            );
        }
    }
}
