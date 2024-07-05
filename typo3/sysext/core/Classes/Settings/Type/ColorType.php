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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * @todo Extract color parsing into utility including a registry for
 *       custom color spaces.
 */
#[AsTaggedItem(index: 'color')]
readonly class ColorType implements SettingsTypeInterface
{
    public function __construct(
        protected LoggerInterface $logger,
    ) {}

    public function validate(mixed $value, SettingDefinition $definition): bool
    {
        $stringType = new StringType($this->logger);
        if (!$stringType->validate($value, $definition)) {
            return false;
        }

        $value = $stringType->transformValue($value, $definition);
        return $this->doColorNormalization($value) !== null;
    }

    public function transformValue(mixed $value, SettingDefinition $definition): string
    {
        $stringType = new StringType($this->logger);
        if (!$stringType->validate($value, $definition)) {
            $this->logger->warning('Setting validation field, reverting to default: {key}', ['key' => $definition->key]);
            return $definition->default;
        }

        $value = $stringType->transformValue($value, $definition);
        return $this->doColorNormalization($value) ?? $definition->default;
    }

    private function doColorNormalization(string $value): ?string
    {
        if (str_starts_with($value, 'rgb(') && str_ends_with($value, ')')) {
            $values = GeneralUtility::trimExplode(',', substr(substr($value, 0, -1), 4));
            return $this->normalizeRgb($values);
        }
        if (str_starts_with($value, 'rgba(') && str_ends_with($value, ')')) {
            $values = GeneralUtility::trimExplode(',', substr(substr($value, 0, -1), 5));
            return $this->normalizeRgba($values);
        }

        if (str_starts_with($value, '#')) {
            return $this->normalizeHex(substr($value, 1));
        }

        return null;
    }

    private function normalizeRgb(array $values): ?string
    {
        if (count($values) === 1) {
            $values = GeneralUtility::trimExplode('/', $values[0]);
            if (count($values) === 2) {
                return $this->normalizeRgba([...GeneralUtility::trimExplode(' ', $values[0]), $values[1]]);
            }
            $values = GeneralUtility::trimExplode(' ', $values[0]);
        }
        if (count($values) !== 3) {
            return null;
        }
        foreach ($values as $value) {
            if (!MathUtility::canBeInterpretedAsInteger($value)) {
                return null;
            }
            $value = (int)$value;
            if ($value < 0 || $value > 255) {
                return null;
            }
        }
        return 'rgb(' . implode(',', $values) . ')';
    }

    private function normalizeRgba(array $values): ?string
    {
        if (count($values) !== 4) {
            return null;
        }

        $a = array_pop($values);
        if (!MathUtility::canBeInterpretedAsFloat($a)) {
            return null;
        }

        if ((float)$a < 0 || (float)$a > 1) {
            return null;
        }

        foreach ($values as $value) {
            if (!MathUtility::canBeInterpretedAsInteger($value)) {
                return null;
            }
            $value = (int)$value;
            if ($value < 0 || $value > 255) {
                return null;
            }
        }
        $values[] = $a;
        return 'rgba(' . implode(',', $values) . ')';
    }

    private function normalizeHex(string $values): ?string
    {
        $len = strlen($values);
        if ($len !== 3 && $len !== 6 && $len !== 8) {
            return null;
        }

        if (!preg_match('/^[0-9a-f]+$/', $values)) {
            return null;
        }

        return '#' . $values;
    }

    public function getJavaScriptModule(): string
    {
        return '@typo3/backend/settings/type/color.js';
    }
}
