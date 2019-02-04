<?php
declare(strict_types = 1);

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
 * Converter which transforms simple types to an ObjectStorage.
 *
 * @todo Implement functionality for converting collection properties.
 */
class ObjectStorageConverter extends AbstractTypeConverter
{
    /**
     * @var string[]
     */
    protected $sourceTypes = ['string', 'array'];

    /**
     * @var string
     */
    protected $targetType = \TYPO3\CMS\Extbase\Persistence\ObjectStorage::class;

    /**
     * @var int
     */
    protected $priority = 10;

    /**
     * Actually convert from $source to $targetType, taking into account the fully
     * built $convertedChildProperties and $configuration.
     *
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function convertFrom($source, string $targetType, array $convertedChildProperties = [], \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = null): \TYPO3\CMS\Extbase\Persistence\ObjectStorage
    {
        $objectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        foreach ($convertedChildProperties as $subProperty) {
            $objectStorage->attach($subProperty);
        }
        return $objectStorage;
    }

    /**
     * Returns the source, if it is an array, otherwise an empty array.
     *
     * @param mixed $source
     * @return array
     */
    public function getSourceChildPropertiesToBeConverted($source): array
    {
        if (is_array($source)) {
            return $source;
        }
        return [];
    }

    /**
     * Return the type of a given sub-property inside the $targetType
     *
     * @param string $targetType
     * @param string $propertyName
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return string
     */
    public function getTypeOfChildProperty($targetType, string $propertyName, \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration): string
    {
        $parsedTargetType = \TYPO3\CMS\Extbase\Utility\TypeHandlingUtility::parseType($targetType);
        return $parsedTargetType['elementType'];
    }
}
