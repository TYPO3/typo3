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

use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\DomainObject\AbstractValueObject;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException;
use TYPO3\CMS\Extbase\Property\Exception\InvalidSourceException;
use TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException;
use TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * This converter transforms arrays or strings to persistent objects. It does the following:
 *
 * - If the input is string, it is assumed to be a UID. Then, the object is fetched from persistence.
 * - If the input is array, we check if it has an identity property.
 *
 * - If the input has an identity property and NO additional properties, we fetch the object from persistence.
 * - If the input has an identity property AND additional properties, we fetch the object from persistence,
 *   and set the sub-properties. We only do this if the configuration option "CONFIGURATION_MODIFICATION_ALLOWED" is TRUE.
 * - If the input has NO identity property, but additional properties, we create a new object and return it.
 *   However, we only do this if the configuration option "CONFIGURATION_CREATION_ALLOWED" is TRUE.
 */
class PersistentObjectConverter extends ObjectConverter
{
    /**
     * @var int
     */
    const CONFIGURATION_MODIFICATION_ALLOWED = 1;

    /**
     * @var int
     */
    const CONFIGURATION_CREATION_ALLOWED = 2;

    /**
     * @var array
     */
    protected $sourceTypes = ['integer', 'string', 'array'];

    /**
     * @var string
     */
    protected $targetType = 'object';

    /**
     * @var int
     */
    protected $priority = 20;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function injectPersistenceManager(PersistenceManagerInterface $persistenceManager): void
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * We can only convert if the $targetType is either tagged with entity or value object.
     *
     * @param mixed $source
     * @param string $targetType
     * @return bool
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function canConvertFrom($source, string $targetType): bool
    {
        return is_subclass_of($targetType, AbstractDomainObject::class);
    }

    /**
     * All properties in the source array except __identity are sub-properties.
     *
     * @param mixed $source
     * @return array
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getSourceChildPropertiesToBeConverted($source): array
    {
        if (is_string($source) || is_int($source)) {
            return [];
        }
        if (isset($source['__identity'])) {
            unset($source['__identity']);
        }
        return parent::getSourceChildPropertiesToBeConverted($source);
    }

    /**
     * The type of a property is determined by the reflection service.
     *
     * @param string $targetType
     * @param string $propertyName
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return string
     * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getTypeOfChildProperty($targetType, string $propertyName, PropertyMappingConfigurationInterface $configuration): string
    {
        $configuredTargetType = $configuration->getConfigurationFor($propertyName)->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, self::CONFIGURATION_TARGET_TYPE);
        if ($configuredTargetType !== null) {
            return $configuredTargetType;
        }

        $specificTargetType = $this->objectContainer->getImplementationClassName($targetType);
        $schema = $this->reflectionService->getClassSchema($specificTargetType);
        if (!$schema->hasProperty($propertyName)) {
            throw new InvalidTargetException('Property "' . $propertyName . '" was not found in target object of type "' . $specificTargetType . '".', 1297978366);
        }
        $property = $schema->getProperty($propertyName);
        return $property->getType() . ($property->getElementType() !== null ? '<' . $property->getElementType() . '>' : '');
    }

    /**
     * Convert an object from $source to an entity or a value object.
     *
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @throws \InvalidArgumentException
     * @return object|null the target type
     * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function convertFrom($source, string $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null): ?object
    {
        if (is_array($source)) {
            if (
                class_exists($targetType)
                && is_subclass_of($targetType, AbstractValueObject::class)
            ) {
                // Unset identity for valueobject to use constructor mapping, since the identity is determined from
                // constructor arguments
                unset($source['__identity']);
            }
            $object = $this->handleArrayData($source, $targetType, $convertedChildProperties, $configuration);
        } elseif (is_string($source) || is_int($source)) {
            if (empty($source)) {
                return null;
            }
            $object = $this->fetchObjectFromPersistence($source, $targetType);
        } else {
            // todo: this case is impossible as this converter is never called with a source that is not an integer, a string or an array
            throw new \InvalidArgumentException('Only integers, strings and arrays are accepted.', 1305630314);
        }
        foreach ($convertedChildProperties as $propertyName => $propertyValue) {
            $result = ObjectAccess::setProperty($object, $propertyName, $propertyValue);
            if ($result === false) {
                $exceptionMessage = sprintf(
                    'Property "%s" having a value of type "%s" could not be set in target object of type "%s". Make sure that the property is accessible properly, for example via an appropriate setter method.',
                    $propertyName,
                    (is_object($propertyValue) ? get_class($propertyValue) : gettype($propertyValue)),
                    $targetType
                );
                throw new InvalidTargetException($exceptionMessage, 1297935345);
            }
        }

        return $object;
    }

    /**
     * Handle the case if $source is an array.
     *
     * @param array $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return object
     * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException
     */
    protected function handleArrayData(array $source, string $targetType, array &$convertedChildProperties, PropertyMappingConfigurationInterface $configuration = null): object
    {
        if (isset($source['__identity'])) {
            $object = $this->fetchObjectFromPersistence($source['__identity'], $targetType);

            if (count($source) > 1 && ($configuration === null || $configuration->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, self::CONFIGURATION_MODIFICATION_ALLOWED) !== true)) {
                throw new InvalidPropertyMappingConfigurationException('Modification of persistent objects not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_MODIFICATION_ALLOWED" to TRUE.', 1297932028);
            }
        } else {
            if ($configuration === null || $configuration->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, self::CONFIGURATION_CREATION_ALLOWED) !== true) {
                throw new InvalidPropertyMappingConfigurationException(
                    'Creation of objects not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_CREATION_ALLOWED" to TRUE',
                    1476044961
                );
            }
            $object = $this->buildObject($convertedChildProperties, $targetType);
        }
        return $object;
    }

    /**
     * Fetch an object from persistence layer.
     *
     * @param mixed $identity
     * @param string $targetType
     * @throws \TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException
     * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidSourceException
     * @return object
     */
    protected function fetchObjectFromPersistence($identity, string $targetType): object
    {
        if (ctype_digit((string)$identity)) {
            $object = $this->persistenceManager->getObjectByIdentifier($identity, $targetType);
        } else {
            throw new InvalidSourceException('The identity property "' . $identity . '" is no UID.', 1297931020);
        }

        if ($object === null) {
            throw new TargetNotFoundException(sprintf('Object of type %s with identity "%s" not found.', $targetType, print_r($identity, true)), 1297933823);
        }

        return $object;
    }
}
