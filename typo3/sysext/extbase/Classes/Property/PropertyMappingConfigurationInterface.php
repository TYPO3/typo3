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

namespace TYPO3\CMS\Extbase\Property;

/**
 * Configuration object for the property mapper.
 */
interface PropertyMappingConfigurationInterface
{
    /**
     * returns TRUE if the given propertyName should be mapped, FALSE otherwise.
     *
     * @param non-empty-string $propertyName
     */
    public function shouldSkip(string $propertyName): bool;

    /**
     * Whether unknown (unconfigured) properties should be skipped during
     * mapping, instead if causing an error.
     */
    public function shouldSkipUnknownProperties(): bool;

    /**
     * Returns the sub-configuration for the passed $propertyName. Must ALWAYS return a valid configuration object!
     *
     * @param non-empty-string $propertyName
     * @return PropertyMappingConfigurationInterface the property mapping configuration for the given $propertyName.
     */
    public function getConfigurationFor(string $propertyName): PropertyMappingConfigurationInterface;

    /**
     * Maps the given $sourcePropertyName to a target property name.
     * Can be used to rename properties from source to target.
     *
     * @param non-empty-string $sourcePropertyName
     * @return non-empty-string property name of target
     */
    public function getTargetPropertyName(string $sourcePropertyName): string;

    /**
     * @param class-string<TypeConverterInterface> $typeConverterClassName
     * @param non-empty-string|int $key
     * @return mixed configuration value for the specific $typeConverterClassName. Can be used by Type Converters to fetch converter-specific configuration
     */
    public function getConfigurationValue(string $typeConverterClassName, string|int $key): mixed;

    /**
     * This method can be used to explicitly force a TypeConverter to be used for this Configuration.
     *
     * @return TypeConverterInterface|null The type converter to be used for this particular PropertyMappingConfiguration, or NULL if the system-wide configured type converter should be used.
     */
    public function getTypeConverter(): ?TypeConverterInterface;

    public function allowAllProperties(): PropertyMappingConfigurationInterface;

    /**
     * @param class-string<TypeConverterInterface> $typeConverter
     */
    public function setTypeConverterOption(string $typeConverter, string|int $optionKey, mixed $optionValue): PropertyMappingConfigurationInterface;

    /**
     * @param non-empty-string $propertyName
     */
    public function shouldMap(string $propertyName): bool;

    /**
     * @param class-string<TypeConverterInterface> $typeConverter
     */
    public function setTypeConverterOptions(string $typeConverter, array $options): PropertyMappingConfigurationInterface;

    /**
     * @param non-empty-string $propertyPath
     */
    public function forProperty(string $propertyPath): PropertyMappingConfigurationInterface;

    /**
     * @param non-empty-string ...$propertyNames
     */
    public function allowProperties(string ...$propertyNames): PropertyMappingConfigurationInterface;

    /**
     * @param array $splitPropertyPath
     */
    public function traverseProperties(array $splitPropertyPath): PropertyMappingConfigurationInterface;
}
