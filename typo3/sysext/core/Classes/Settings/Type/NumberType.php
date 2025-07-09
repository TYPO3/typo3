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
use TYPO3\CMS\Core\Utility\MathUtility;

#[AsTaggedItem(index: 'number')]
readonly class NumberType implements SettingsTypeInterface, SettingsTypeOptionAwareInterface
{
    public function __construct(
        protected LoggerInterface $logger,
    ) {}

    public function validate(mixed $value, SettingDefinition $definition): bool
    {
        // Normalize value
        if (is_int($value) || is_float($value)) {
            $numericValue = (float)$value;
        } elseif (is_string($value) && (
            MathUtility::canBeInterpretedAsInteger($value) ||
            MathUtility::canBeInterpretedAsFloat($value)
        )) {
            $numericValue = (float)$value;
        } else {
            return false;
        }

        // Check optional constraints
        if (array_key_exists('min', $definition->options) &&
            $numericValue < $definition->options['min']
        ) {
            return false;
        }
        if (array_key_exists('max', $definition->options) &&
            $numericValue > $definition->options['max']
        ) {
            return false;
        }
        if (array_key_exists('step', $definition->options)) {
            $stepBase = array_key_exists('min', $definition->options) ? $definition->options['min'] : 0.0;
            $offset = ($stepBase - $numericValue) / $definition->options['step'];
            if ((string)(int)$offset !== (string)$offset) {
                return false;
            }
        }

        return true;
    }

    public function transformValue(mixed $value, SettingDefinition $definition): int|float
    {
        if (!$this->validate($value, $definition)) {
            $this->logger->warning('Setting validation field, reverting to default: {key}', ['key' => $definition->key]);
            return $definition->default;
        }

        if (is_string($value)) {
            return MathUtility::canBeInterpretedAsInteger($value)
                ? (int)$value
                : (float)$value;
        }

        return $value;
    }

    public function getSupportedOptions(): array
    {
        return [
            'min' => new SettingsTypeOption(
                type: 'number',
                description: 'Minimum value allowed',
                required: false,
            ),
            'max' => new SettingsTypeOption(
                type: 'number',
                description: 'Maximum value allowed',
                required: false,
            ),
            'step' => new SettingsTypeOption(
                type: 'number',
                description: 'Step size',
                required: false,
            ),
        ];
    }

    public function validateOptions(SettingDefinition $definition): bool
    {
        $min = $definition->options['min'] ?? null;
        $max = $definition->options['max'] ?? null;
        $step = $definition->options['step'] ?? null;
        if ($min !== null && $max !== null && $min > $max) {
            return false;
        }
        if ($min !== null && $max !== null && $step !== null) {
            if ($max - $min < $step) {
                return false;
            }
            $steps = ($max - $min) / $step;
            if ((string)(int)$steps !== (string)$steps) {
                return false;
            }
        }
        return true;
    }

    public function getJavaScriptModule(): string
    {
        return '@typo3/backend/settings/type/number.js';
    }
}
