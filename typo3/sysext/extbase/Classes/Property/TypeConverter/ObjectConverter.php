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

use TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception\NoSuchMethodException;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception\NoSuchMethodParameterException;

/**
 * This converter transforms arrays to simple objects (POPO) by setting properties.
 */
class ObjectConverter extends AbstractTypeConverter
{
    /**
     * @var int
     */
    const CONFIGURATION_TARGET_TYPE = 3;

    /**
     * @var int
     */
    const CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED = 4;

    /**
     * @var array
     */
    protected $sourceTypes = ['array'];

    /**
     * @var string
     */
    protected $targetType = 'object';

    /**
     * @var int
     */
    protected $priority = 10;

    /**
     * @var \TYPO3\CMS\Extbase\Object\Container\Container
     */
    protected $objectContainer;

    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
     */
    protected $reflectionService;

    /**
     * @param \TYPO3\CMS\Extbase\Object\Container\Container $objectContainer
     */
    public function injectObjectContainer(\TYPO3\CMS\Extbase\Object\Container\Container $objectContainer): void
    {
        $this->objectContainer = $objectContainer;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
     */
    public function injectReflectionService(\TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService): void
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * Only convert non-persistent types
     *
     * @param mixed $source
     * @param string $targetType
     * @return bool
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function canConvertFrom($source, string $targetType): bool
    {
        return !is_subclass_of($targetType, \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject::class);
    }

    /**
     * Convert all properties in the source array
     *
     * @param mixed $source
     * @return array
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getSourceChildPropertiesToBeConverted($source): array
    {
        if (isset($source['__type'])) {
            unset($source['__type']);
        }
        return $source;
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
    public function getTypeOfChildProperty(string $targetType, string $propertyName, \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration): string
    {
        $configuredTargetType = $configuration->getConfigurationFor($propertyName)->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\ObjectConverter::class, self::CONFIGURATION_TARGET_TYPE);
        if ($configuredTargetType !== null) {
            return $configuredTargetType;
        }

        $specificTargetType = $this->objectContainer->getImplementationClassName($targetType);
        $classSchema = $this->reflectionService->getClassSchema($specificTargetType);

        $methodName = 'set' . ucfirst($propertyName);
        if ($classSchema->hasMethod($methodName)) {
            $methodParameters = $classSchema->getMethod($methodName)->getParameters() ?? [];
            $methodParameter = current($methodParameters);
            if ($methodParameter->getType() === null) {
                throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException('Setter for property "' . $propertyName . '" had no type hint or documentation in target object of type "' . $specificTargetType . '".', 1303379158);
            }
            return $methodParameter->getType();
        }
        try {
            $parameterType = $classSchema->getMethod('__construct')->getParameter($propertyName)->getType();
            if ($parameterType !== null) {
                return $parameterType;
            }
        } catch (NoSuchMethodException|NoSuchMethodParameterException $e) {
            throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException('Property "' . $propertyName . '" had no setter or constructor argument in target object of type "' . $specificTargetType . '".', 1303379126);
        }
    }

    /**
     * Convert an object from $source to an object.
     *
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return object|null the target type
     * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function convertFrom($source, string $targetType, array $convertedChildProperties = [], \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = null): ?object
    {
        $object = $this->buildObject($convertedChildProperties, $targetType);
        foreach ($convertedChildProperties as $propertyName => $propertyValue) {
            $result = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($object, $propertyName, $propertyValue);
            if ($result === false) {
                $exceptionMessage = sprintf(
                    'Property "%s" having a value of type "%s" could not be set in target object of type "%s". Make sure that the property is accessible properly, for example via an appropriate setter method.',
                    $propertyName,
                    (is_object($propertyValue) ? get_class($propertyValue) : gettype($propertyValue)),
                    $targetType
                );
                throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException($exceptionMessage, 1304538165);
            }
        }

        return $object;
    }

    /**
     * Determines the target type based on the source's (optional) __type key.
     *
     * @param mixed $source
     * @param string $originalTargetType
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return string
     * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidDataTypeException
     * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException
     * @throws \InvalidArgumentException
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getTargetTypeForSource($source, string $originalTargetType, \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = null): string
    {
        $targetType = $originalTargetType;

        if (is_array($source) && array_key_exists('__type', $source)) {
            $targetType = $source['__type'];

            if ($configuration === null) {
                throw new \InvalidArgumentException('A property mapping configuration must be given, not NULL.', 1326277369);
            }
            if ($configuration->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\ObjectConverter::class, self::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED) !== true) {
                throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException('Override of target type not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED" to TRUE.', 1317050430);
            }

            if ($targetType !== $originalTargetType && is_a($targetType, $originalTargetType, true) === false) {
                throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidDataTypeException('The given type "' . $targetType . '" is not a subtype of "' . $originalTargetType . '".', 1317048056);
            }
        }

        return $targetType;
    }

    /**
     * Builds a new instance of $objectType with the given $possibleConstructorArgumentValues. If
     * constructor argument values are missing from the given array the method
     * looks for a default value in the constructor signature. Furthermore, the constructor arguments are removed from $possibleConstructorArgumentValues
     *
     * @param array &$possibleConstructorArgumentValues
     * @param string $objectType
     * @return object The created instance
     * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException if a required constructor argument is missing
     */
    protected function buildObject(array &$possibleConstructorArgumentValues, string $objectType): object
    {
        $specificObjectType = $this->objectContainer->getImplementationClassName($objectType);
        $classSchema = $this->reflectionService->getClassSchema($specificObjectType);

        if ($classSchema->hasConstructor()) {
            $constructor = $classSchema->getMethod('__construct');
            $constructorArguments = [];
            foreach ($constructor->getParameters() as $parameterName => $parameter) {
                if (array_key_exists($parameterName, $possibleConstructorArgumentValues)) {
                    $constructorArguments[] = $possibleConstructorArgumentValues[$parameterName];
                    unset($possibleConstructorArgumentValues[$parameterName]);
                } elseif ($parameter->isOptional()) {
                    $constructorArguments[] = $parameter->getDefaultValue();
                } else {
                    throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException('Missing constructor argument "' . $parameterName . '" for object of type "' . $objectType . '".', 1268734872);
                }
            }
            return call_user_func_array([$this->objectManager, 'get'], array_merge([$objectType], $constructorArguments));
        }
        return $this->objectManager->get($objectType);
    }
}
