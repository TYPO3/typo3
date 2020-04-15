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
 * Interface for type converters, which can convert from a simple type to an object or another simple type.
 *
 * All Type Converters should have NO INTERNAL STATE, such that they can be used as singletons and multiple times in succession (as this improves performance dramatically).
 */
interface TypeConverterInterface
{
    /**
     * Returns the list of source types the TypeConverter can handle.
     * Must be PHP simple types, classes or object is not allowed.
     *
     * @return string[]
     */
    public function getSupportedSourceTypes(): array;

    /**
     * Return the target type this TypeConverter converts to.
     * Can be a simple type or a class name.
     *
     * @return string
     */
    public function getSupportedTargetType(): string;

    /**
     * Returns the type for a given source, depending on e.g. the __type setting or other properties.
     *
     * @param mixed $source the source data
     * @param string $originalTargetType the type we originally want to convert to
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return string
     */
    public function getTargetTypeForSource($source, string $originalTargetType, PropertyMappingConfigurationInterface $configuration = null): string;

    /**
     * Return the priority of this TypeConverter. TypeConverters with a high priority are chosen before low priority.
     *
     * @return int
     */
    public function getPriority(): int;

    /**
     * Here, the TypeConverter can do some additional runtime checks to see whether
     * it can handle the given source data and the given target type.
     *
     * @param mixed $source the source data
     * @param string $targetType the type to convert to.
     * @return bool TRUE if this TypeConverter can convert from $source to $targetType, FALSE otherwise.
     */
    public function canConvertFrom($source, string $targetType): bool;

    /**
     * Return a list of sub-properties inside the source object.
     * The "key" is the sub-property name, and the "value" is the value of the sub-property.
     *
     * @param mixed $source
     * @return array
     */
    public function getSourceChildPropertiesToBeConverted($source): array;

    /**
     * Return the type of a given sub-property inside the $targetType
     *
     * @param string $targetType
     * @param string $propertyName
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return string the type of $propertyName in $targetType
     */
    public function getTypeOfChildProperty(string $targetType, string $propertyName, PropertyMappingConfigurationInterface $configuration): string;

    /**
     * Actually convert from $source to $targetType, taking into account the fully
     * built $convertedChildProperties and $configuration.
     *
     * The return value can be one of three types:
     * - an arbitrary object, or a simple type (which has been created while mapping).
     *   This is the normal case.
     * - NULL, indicating that this object should *not* be mapped (i.e. a "File Upload" Converter could return NULL if no file has been uploaded, and a silent failure should occur.
     * - An instance of \TYPO3\CMS\Extbase\Error\Error -- This will be a user-visible error message later on.
     * Furthermore, it should throw an Exception if an unexpected failure (like a security error) occurred or a configuration issue happened.
     *
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return mixed|\TYPO3\CMS\Extbase\Error\Error the target type, or an error object if a user-error occurred
     * @throws \TYPO3\CMS\Extbase\Property\Exception\TypeConverterException thrown in case a developer error occurred
     */
    public function convertFrom($source, string $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null);
}
