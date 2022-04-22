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

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Object\Container\Container;
use TYPO3\CMS\Extbase\Property\Exception\InvalidDataTypeException;
use TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException;
use TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception\NoSuchMethodException;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception\NoSuchMethodParameterException;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;

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
     * @deprecated since v11, will be removed in v12.
     */
    protected $objectContainer;

    protected ContainerInterface $container;

    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
     */
    protected $reflectionService;

    /**
     * @param \TYPO3\CMS\Extbase\Object\Container\Container $objectContainer
     * @deprecated since v11, will be removed in v12.
     */
    public function injectObjectContainer(Container $objectContainer): void
    {
        $this->objectContainer = $objectContainer;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
     */
    public function injectReflectionService(ReflectionService $reflectionService): void
    {
        $this->reflectionService = $reflectionService;
    }

    public function injectContainer(ContainerInterface $container)
    {
        $this->container = $container;
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
        return !is_subclass_of($targetType, AbstractDomainObject::class);
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
     * @throws InvalidTargetException
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getTypeOfChildProperty(string $targetType, string $propertyName, PropertyMappingConfigurationInterface $configuration): string
    {
        $configuredTargetType = $configuration->getConfigurationFor($propertyName)->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\ObjectConverter::class, self::CONFIGURATION_TARGET_TYPE);
        if ($configuredTargetType !== null) {
            return $configuredTargetType;
        }

        // @deprecated since v11, will be removed in v12: ContainerInterface resolves class names. v12: drop next line.
        $specificTargetType = $this->objectContainer->getImplementationClassName($targetType);
        $classSchema = $this->reflectionService->getClassSchema($specificTargetType);

        $methodName = 'set' . ucfirst($propertyName);
        if ($classSchema->hasMethod($methodName)) {
            $methodParameters = $classSchema->getMethod($methodName)->getParameters();
            $methodParameter = current($methodParameters);
            if ($methodParameter->getType() === null) {
                throw new InvalidTargetException('Setter for property "' . $propertyName . '" had no type hint or documentation in target object of type "' . $specificTargetType . '".', 1303379158);
            }
            $property = $classSchema->getProperty($propertyName);
            if ($property->getElementType() !== null) {
                return $methodParameter->getType() . '<' . $property->getElementType() . '>';
            }
            return $methodParameter->getType();
        }
        try {
            $parameterType = $classSchema->getMethod('__construct')->getParameter($propertyName)->getType();
        } catch (NoSuchMethodException $e) {
            $exceptionMessage = sprintf('Type of child property "%s" of class "%s" could not be '
                . 'derived from constructor arguments as said class does not have a constructor '
                . 'defined.', $propertyName, $specificTargetType);
            throw new InvalidTargetException($exceptionMessage, 1582385098);
        } catch (NoSuchMethodParameterException $e) {
            $exceptionMessage = sprintf('Type of child property "%1$s" of class "%2$s" could not be '
                . 'derived from constructor arguments as the constructor of said class does not '
                . 'have a parameter with property name "%1$s".', $propertyName, $specificTargetType);
            throw new InvalidTargetException($exceptionMessage, 1303379126);
        }

        if ($parameterType === null) {
            $exceptionMessage = sprintf('Type of child property "%1$s" of class "%2$s" could not be '
                . 'derived from constructor argument "%1$s". This usually happens if the argument '
                . 'misses a type hint.', $propertyName, $specificTargetType);
            throw new InvalidTargetException($exceptionMessage, 1582385619);
        }
        return $parameterType;
    }

    /**
     * Convert an object from $source to an object.
     *
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return object|null the target type
     * @throws InvalidTargetException
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function convertFrom($source, string $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null): ?object
    {
        $object = $this->buildObject($convertedChildProperties, $targetType);
        foreach ($convertedChildProperties as $propertyName => $propertyValue) {
            $result = ObjectAccess::setProperty($object, $propertyName, $propertyValue);
            if ($result === false) {
                $exceptionMessage = sprintf(
                    'Property "%s" having a value of type "%s" could not be set in target object of type "%s". Make sure that the property is accessible properly, for example via an appropriate setter method.',
                    $propertyName,
                    (is_object($propertyValue) ? get_class($propertyValue) : gettype($propertyValue)),
                    $targetType
                );
                throw new InvalidTargetException($exceptionMessage, 1304538165);
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
    public function getTargetTypeForSource($source, string $originalTargetType, PropertyMappingConfigurationInterface $configuration = null): string
    {
        $targetType = $originalTargetType;

        if (is_array($source) && array_key_exists('__type', $source)) {
            $targetType = $source['__type'];

            if ($configuration === null) {
                // todo: this is impossible to achieve since this methods is always called via (convert -> doMapping -> getTargetTypeForSource) and convert and doMapping create configuration objects if missing.
                throw new \InvalidArgumentException('A property mapping configuration must be given, not NULL.', 1326277369);
            }
            if ($configuration->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\ObjectConverter::class, self::CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED) !== true) {
                throw new InvalidPropertyMappingConfigurationException('Override of target type not allowed. To enable this, you need to set the PropertyMappingConfiguration Value "CONFIGURATION_OVERRIDE_TARGET_TYPE_ALLOWED" to TRUE.', 1317050430);
            }

            if ($targetType !== $originalTargetType && is_a($targetType, $originalTargetType, true) === false) {
                throw new InvalidDataTypeException('The given type "' . $targetType . '" is not a subtype of "' . $originalTargetType . '".', 1317048056);
            }
        }

        return $targetType;
    }

    /**
     * Builds a new instance of $objectType with the given $possibleConstructorArgumentValues. If
     * constructor argument values are missing from the given array the method looks for a default
     * value in the constructor signature. Furthermore, the constructor arguments are removed from
     * $possibleConstructorArgumentValues: They are considered "handled" by __construct and will
     * not be mapped calling setters later.
     *
     * @param array $possibleConstructorArgumentValues
     * @param string $objectType
     * @return object The created instance
     * @throws InvalidTargetException if a required constructor argument is missing
     */
    protected function buildObject(array &$possibleConstructorArgumentValues, string $objectType): object
    {
        // The ObjectConverter typically kicks in, if request arguments are to be mapped to
        // a domain model. An example is ext:belog:Domain/Model/Demand.
        // Domain models are data objects and should thus be fetched via makeInstance(), should
        // not be registered as service, and should thus not be DI aware.
        // However, historically, ObjectManager->get() made *all* classes DI aware.
        // Additionally, all to-be-mapped arguments are hand over as "possible constructor arguments" here,
        // and extbase is able to use single arguments as constructor arguments to domain models,
        // if a __construct() with an argument having the same name as a to-be-mapped argument exists.
        // This is the reason that &$possibleConstructorArgumentValues is hand over as reference here:
        // If an argument can be hand over as constructor argument, it is considered "already mapped" and
        // is not manually mapped calling setters later.
        // To be as backwards compatible as possible, without using ObjectManager, the following logic
        // is applied for now:
        // * If the class is registered as service (container->has()=true), and if there are no
        //   $possibleConstructorArgumentValues, instantiate the class via container->get(). Easy
        //   scenario - the target class is DI aware and will get dependencies injected. A different target
        //   class can be specified using service configuration if needed.
        // * If the class is registered as service, and if there are $possibleConstructorArgumentValues,
        //   the class is instantiated via container->get(). $possibleConstructorArgumentValues are *not* hand
        //   over to the constructor. The target class can then use constructor injection and inject* methods
        //   for DI. A different target class can be specified using service configuration if needed. Mapping
        //   of arguments is done using setters by follow-up code.
        // * If the class is *not* registered as service, makeInstance() is used for object retrieval.
        // * @todo delete in v12: As compat layer, if a different implementation has been registered
        //   for the ObjectManager (extbase Container->registerImplementation()), the ObjectManager target class is
        //   still used in v11, but this is marked deprecated, with makeInstance(), a different implementation should
        //   be registered as XCLASS if really needed.
        // * If there are no $possibleConstructorArgumentValues, makeInstance() is used right away.
        // * If there are $possibleConstructorArgumentValues and __construct() does not exist, makeInstance()
        //   is used without constructor arguments. Mapping of argument values via setters is done by follow-up code.
        // * If there are $possibleConstructorArgumentValues and if __construct() exists, extbase reflection
        //   is used to map single arguments to constructor arguments with the same name and
        //   makeInstance() is used to instantiate the class. Mapping remaining arguments is done by follow-up code.
        if ($this->container->has($objectType)) {
            // @todo: consider dropping container->get() to prevent domain models being treated as services in >=v12.
            return $this->container->get($objectType);
        }

        $specificObjectType = $this->objectContainer->getImplementationClassName($objectType);
        if ($specificObjectType !== $objectType) {
            // @deprecated since v11, will be removed in v12: makeInstance() overrides should be done as XCLASS
            trigger_error(
                'Container->registerImplemenation() for class ' . $objectType . ' is deprecated. Use XCLASS instead.',
                E_USER_DEPRECATED
            );
        }

        if (empty($possibleConstructorArgumentValues) || !method_exists($specificObjectType, '__construct')) {
            return GeneralUtility::makeInstance($specificObjectType);
        }

        $classSchema = $this->reflectionService->getClassSchema($specificObjectType);
        $constructor = $classSchema->getMethod('__construct');
        $constructorArguments = [];
        foreach ($constructor->getParameters() as $parameterName => $parameter) {
            if (array_key_exists($parameterName, $possibleConstructorArgumentValues)) {
                $constructorArguments[] = $possibleConstructorArgumentValues[$parameterName];
                unset($possibleConstructorArgumentValues[$parameterName]);
            } elseif ($parameter->isOptional()) {
                $constructorArguments[] = $parameter->getDefaultValue();
            } else {
                throw new InvalidTargetException('Missing constructor argument "' . $parameterName . '" for object of type "' . $objectType . '".', 1268734872);
            }
        }
        return GeneralUtility::makeInstance(...[$specificObjectType, ...$constructorArguments]);
    }
}
