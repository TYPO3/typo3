<?php
namespace TYPO3\CMS\Extbase\Property\TypeConverter;

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

/**
 * Type converter which provides sensible default implementations for most methods. If you extend this class
 * you only need to do the following:
 * - set $sourceTypes
 * - set $targetType
 * - set $priority
 * - implement convertFrom()
 */
abstract class AbstractTypeConverter implements \TYPO3\CMS\Extbase\Property\TypeConverterInterface, \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * The source types this converter can convert.
     *
     * @var array<string>
     */
    protected $sourceTypes = [];

    /**
     * The target type this converter can convert to.
     *
     * @var string
     */
    protected $targetType = '';

    /**
     * The priority for this converter.
     *
     * @var int
     */
    protected $priority;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Returns the list of source types the TypeConverter can handle.
     * Must be PHP simple types, classes or object is not allowed.
     *
     * @return array<string>
     */
    public function getSupportedSourceTypes()
    {
        return $this->sourceTypes;
    }

    /**
     * Return the target type this TypeConverter converts to.
     * Can be a simple type or a class name.
     *
     * @return string
     */
    public function getSupportedTargetType()
    {
        return $this->targetType;
    }

    /**
     * Returns the $originalTargetType unchanged in this implementation.
     *
     * @param mixed $source the source data
     * @param string $originalTargetType the type we originally want to convert to
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return string
     */
    public function getTargetTypeForSource($source, $originalTargetType, \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        return $originalTargetType;
    }

    /**
     * Return the priority of this TypeConverter. TypeConverters with a high priority are chosen before low priority.
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * This implementation always returns TRUE for this method.
     *
     * @param mixed $source the source data
     * @param string $targetType the type to convert to.
     * @return bool TRUE if this TypeConverter can convert from $source to $targetType, FALSE otherwise.
     */
    public function canConvertFrom($source, $targetType)
    {
        return true;
    }

    /**
     * Returns an empty list of sub property names
     *
     * @param mixed $source
     * @return array
     */
    public function getSourceChildPropertiesToBeConverted($source)
    {
        return [];
    }

    /**
     * This method is never called, as getSourceChildPropertiesToBeConverted() returns an empty array.
     *
     * @param string $targetType
     * @param string $propertyName
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     */
    public function getTypeOfChildProperty($targetType, $propertyName, \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration)
    {
    }
}
