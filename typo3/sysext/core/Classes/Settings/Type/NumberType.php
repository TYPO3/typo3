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
use TYPO3\CMS\Core\Utility\MathUtility;

#[AsTaggedItem(index: 'number')]
readonly class NumberType implements SettingsTypeInterface
{
    public function __construct(
        protected LoggerInterface $logger,
    ) {}

    public function validate(mixed $value, SettingDefinition $definition): bool
    {
        if (is_int($value)) {
            return true;
        }
        if (is_float($value)) {
            return true;
        }

        if (is_string($value) && (
            MathUtility::canBeInterpretedAsInteger($value) ||
            MathUtility::canBeInterpretedAsFloat($value)
        )) {
            return true;
        }

        return false;
    }

    public function transformValue(mixed $value, SettingDefinition $definition): int|float
    {
        if (!$this->validate($value, $definition)) {
            $this->logger->warning('Setting validation field, reverting to default: {key}', ['key' => $definition->key]);
            return $definition->default;
        }

        if (is_string($value)) {
            if (MathUtility::canBeInterpretedAsInteger($value)) {
                return (int)$value;
            }
            return (float)$value;
        }
        return $value;
    }

    public function getJavaScriptModule(): string
    {
        return '@typo3/backend/settings/type/number.js';
    }
}
