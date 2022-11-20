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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverterInterface;

/**
 * Type converter which provides sensible default implementations for most methods. If you extend this class
 * you only need to implement convertFrom()
 */
abstract class AbstractTypeConverter implements TypeConverterInterface, SingletonInterface
{
    /**
     * The source types this converter can convert.
     *
     * @var string[]
     * @deprecated will be removed in TYPO3 v13.0, as this is defined in Services.yaml.
     */
    protected $sourceTypes = [];

    /**
     * The target type this converter can convert to.
     *
     * @var string
     * @deprecated will be removed in TYPO3 v13.0, as this is defined in Services.yaml.
     */
    protected $targetType = '';

    /**
     * The priority for this converter.
     *
     * @var int
     * @deprecated will be removed in TYPO3 v13.0, as this is defined in Services.yaml.
     */
    protected $priority;

    /**
     * Returns the list of source types the TypeConverter can handle.
     * Must be PHP simple types, classes or object is not allowed.
     *
     * @return string[]
     * @deprecated will be removed in TYPO3 v13.0, as this is defined in Services.yaml.
     */
    public function getSupportedSourceTypes(): array
    {
        return $this->sourceTypes;
    }

    /**
     * Return the target type this TypeConverter converts to.
     * Can be a simple type or a class name.
     *
     * @deprecated will be removed in TYPO3 v13.0, as this is defined in Services.yaml.
     */
    public function getSupportedTargetType(): string
    {
        return $this->targetType;
    }

    /**
     * @todo The concept of this method is flawed because it enables the override of the target type depending on the
     *       structure of the source. So, technically we no longer convert type A to B but source of type A with
     *       structure X to type B defined by X. This makes a type converter non-deterministic.
     *
     * Returns the $originalTargetType unchanged in this implementation.
     *
     * @param mixed $source the source data
     * @param string $originalTargetType the type we originally want to convert to
     */
    public function getTargetTypeForSource($source, string $originalTargetType, PropertyMappingConfigurationInterface $configuration = null): string
    {
        return $originalTargetType;
    }

    /**
     * Return the priority of this TypeConverter. TypeConverters with a high priority are chosen before low priority.
     *
     * @deprecated will be removed in TYPO3 v13.0, as this is defined in Services.yaml.
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @param mixed $source the source data
     * @deprecated will be removed in TYPO3 v13.0
     */
    public function canConvertFrom($source, string $targetType): bool
    {
        return true;
    }

    /**
     * @todo this method is only used for converter sources that have children (i.e. objects). Introduce another
     *       ChildPropertyAwareTypeConverterInterface and drop this method from the main interface
     *
     * Returns an empty list of sub property names
     *
     * @param mixed $source
     */
    public function getSourceChildPropertiesToBeConverted($source): array
    {
        return [];
    }

    /**
     * @todo this method is only used for converter sources that have children (i.e. objects). Introduce another
     *       ChildPropertyAwareTypeConverterInterface and drop this method from the main interface
     *
     * This method is never called, as getSourceChildPropertiesToBeConverted() returns an empty array.
     *
     * @param string $targetType
     * @param string $propertyName
     */
    public function getTypeOfChildProperty(string $targetType, string $propertyName, PropertyMappingConfigurationInterface $configuration): string
    {
        return '';
    }
}
