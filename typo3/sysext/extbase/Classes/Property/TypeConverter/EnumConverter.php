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

namespace TYPO3\CMS\Extbase\Property\TypeConverter;

use TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use UnitEnum;

/**
 * Converter which transforms strings/integers/floats to Enum Instance.
 */
class EnumConverter extends AbstractTypeConverter
{
    /**
     * Only convert if target is enum
     *
     * @template T of UnitEnum
     * @param class-string<T> $targetType
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function canConvertFrom(mixed $source, string $targetType): bool
    {
        return enum_exists($targetType) && (is_int($source) || is_float($source) || is_string($source)) && $this->getEnumElement($source, $targetType);
    }

    /**
     * Convert an enum from $source to an enum.
     *
     * @template T of UnitEnum
     * @param class-string<T> $targetType
     * @param PropertyMappingConfigurationInterface|null $configuration
     * @return T|null
     * @throws InvalidTargetException
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function convertFrom(mixed $source, string $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null): ?\UnitEnum
    {
        return $this->getEnumElement($source, $targetType);
    }

    /**
     * @template T of UnitEnum
     * @param class-string<T> $targetType
     * @return T|null
     * @throws InvalidTargetException
     */
    protected function getEnumElement(float|int|string $source, string $targetType): ?\UnitEnum
    {
        if (!enum_exists($targetType)) {
            throw new InvalidTargetException('TargetType "' . $targetType . '" is not an enum.', 1660834545);
        }
        foreach ($targetType::cases() as $enum) {
            if (property_exists($enum, 'value') && $enum->value == $source) {
                return $enum;
            }
        }

        foreach ($targetType::cases() as $enum) {
            if ($enum->name == $source) {
                return $enum;
            }
        }
        return null;
    }
}
