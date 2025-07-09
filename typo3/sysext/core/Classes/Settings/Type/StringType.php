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

namespace TYPO3\CMS\Core\Settings\Type;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Core\Settings\SettingsTypeInterface;
use TYPO3\CMS\Core\Settings\SettingsTypeOption;
use TYPO3\CMS\Core\Settings\SettingsTypeOptionAwareInterface;

#[AsTaggedItem(index: 'string')]
readonly class StringType implements SettingsTypeInterface, SettingsTypeOptionAwareInterface
{
    public function __construct(
        protected LoggerInterface $logger,
    ) {}

    public function validate(mixed $value, SettingDefinition $definition): bool
    {
        if (is_object($value) && !$value instanceof \Stringable) {
            return false;
        }

        // Normalize value
        $stringValue = (string)$value;

        // Check optional constraints
        if (array_key_exists('min', $definition->options) &&
            mb_strlen($stringValue) < (int)$definition->options['min']
        ) {
            return false;
        }
        if (array_key_exists('max', $definition->options) &&
            mb_strlen($stringValue) > (int)$definition->options['max']
        ) {
            return false;
        }

        return true;
    }

    public function transformValue(mixed $value, SettingDefinition $definition): string
    {
        if (!$this->validate($value, $definition)) {
            $this->logger->warning('Setting validation field, reverting to default: {key}', ['key' => $definition->key]);
            return $definition->default;
        }
        if (is_bool($value)) {
            if ($value) {
                return 'true';
            }
            return 'false';
        }

        return (string)$value;
    }

    public function getSupportedOptions(): array
    {
        return [
            'min' => new SettingsTypeOption(
                type: 'int',
                description: 'Minimum character count allowed',
                required: false,
            ),
            'max' => new SettingsTypeOption(
                type: 'int',
                description: 'Maximum character count allowed',
                required: false,
            ),
        ];
    }

    public function validateOptions(SettingDefinition $definition): bool
    {
        $min = $definition->options['min'] ?? null;
        $max = $definition->options['max'] ?? null;
        if ($min !== null && $min < 0) {
            return false;
        }
        if ($max !== null && $max < 1) {
            return false;
        }
        if ($min !== null && $max !== null && $min > $max) {
            return false;
        }
        return true;
    }

    public function getJavaScriptModule(): string
    {
        return '@typo3/backend/settings/type/string.js';
    }
}
