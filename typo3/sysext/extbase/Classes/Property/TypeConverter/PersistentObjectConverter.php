<?php
namespace TYPO3\CMS\Extbase\Property\TypeConverter;

/*                                                                        *
 * This script belongs to the Extbase framework                           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
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
 *
 * @api
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
    protected $priority = 1;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     */
    protected $persistenceManager;

        /**
     * @param \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager
     */
    public function injectPersistenceManager(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * We can only convert if the $targetType is either tagged with entity or value object.
     *
     * @param mixed $source
     * @param string $targetType
     * @return bool
     */
    public function canConvertFrom($source, $targetType)
    {
        return is_subclass_of($targetType, \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject::class);
    }

    /**
     * All properties in the source array except __identity are sub-properties.
     *
     * @param mixed $source
     * @return array
     */
    public function getSourceChildPropertiesToBeConverted($source)
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
     */
    public function getTypeOfChildProperty($targetType, $propertyName, \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration)
    {
        $configuredTargetType = $configuration->getConfigurationFor($propertyName)->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, self::CONFIGURATION_TARGET_TYPE);
        if ($configuredTargetType !== null) {
            return $configuredTargetType;
        }

        $specificTargetType = $this->objectContainer->getImplementationClassName($targetType);
        $schema = $this->reflectionService->getClassSchema($specificTargetType);
        if (!$schema->hasProperty($propertyName)) {
            throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException('Property "' . $propertyName . '" was not found in target object of type "' . $specificTargetType . '".', 1297978366);
        }
        $propertyInformation = $schema->getProperty($propertyName);
        return $propertyInformation['type'] . ($propertyInformation['elementType'] !== null ? '<' . $propertyInformation['elementType'] . '>' : '');
    }

    /**
     * Convert an object from $source to an entity or a value object.
     *
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @throws \InvalidArgumentException
     * @return object the target type
     * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        if (is_array($source)) {
            if (
                class_exists($targetType)
                && is_subclass_of($targetType, \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject::class)
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
            throw new \InvalidArgumentException('Only integers, strings and arrays are accepted.', 1305630314);
        }
        foreach ($convertedChildProperties as $propertyName => $propertyValue) {
            $result = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($object, $propertyName, $propertyValue);
            if ($result === false) {
                $exceptionMessage = sprintf(
                    'Property "%s" having a value of type "%s" could not be set in target object of type "%s". Make sure that the property is accessible properly, for example via an appropriate setter method.',
                    $propertyName,
                    (is_object($propertyValue) ? get_class($propertyValue) : gettype($propertyValue)),
                    $targetType
                );
                throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException($exceptionMessage, 1297935345);
            }
        }

        return $object;
    }

    /**
     * Handle the case if $source is an array.
     *
     * @param array $source
     * @param string $targetType
     * @param array &$convertedChildProperties
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return object
     * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException
     */
    protected function handleArrayData(array $source, $targetType, array &$convertedChildProperties, \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        if (isset($source['__identity'])) {
            $object = $this->fetchObjectFromPersistence($source['__identity'], $targetType);

            if (count($source) > 1 && ($configuration === null || $configuration->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, self::CONFIGURATION_MODIFICATION_ALLOWED) !== true)) {
                throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException('Modification of persistent objects not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_MODIFICATION_ALLOWED" to TRUE.', 1297932028);
            }
        } else {
            if ($configuration === null || $configuration->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class, self::CONFIGURATION_CREATION_ALLOWED) !== true) {
                throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException('Creation of objects not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_CREATION_ALLOWED" to TRUE');
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
    protected function fetchObjectFromPersistence($identity, $targetType)
    {
        if (ctype_digit((string)$identity)) {
            $object = $this->persistenceManager->getObjectByIdentifier($identity, $targetType);
        } else {
            throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidSourceException('The identity property "' . $identity . '" is no UID.', 1297931020);
        }

        if ($object === null) {
            throw new \TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException(sprintf('Object of type %s with identity "%s" not found.', $targetType, print_r($identity, true)), 1297933823);
        }

        return $object;
    }
}
