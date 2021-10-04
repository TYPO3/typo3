<?php

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

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Core\ClassLoadingInformation;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Property\Exception\DuplicateTypeConverterException;
use TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException;
use TYPO3\CMS\Extbase\Property\Exception\InvalidSourceException;
use TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException;
use TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException;
use TYPO3\CMS\Extbase\Property\Exception\TypeConverterException;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\CMS\Extbase\Utility\TypeHandlingUtility;

/**
 * The Property Mapper transforms simple types (arrays, strings, integers, floats, booleans) to objects or other simple types.
 * It is used most prominently to map incoming HTTP arguments to objects.
 */
class PropertyMapper implements SingletonInterface
{
    protected ContainerInterface $container;
    protected PropertyMappingConfigurationBuilder $configurationBuilder;

    /**
     * A multi-dimensional array which stores the Type Converters available in the system.
     * It has the following structure:
     * 1. Dimension: Source Type
     * 2. Dimension: Target Type
     * 3. Dimension: Priority
     * Value: Type Converter instance
     *
     * @var array
     */
    protected $typeConverters = [];

    /**
     * A list of property mapping messages (errors, warnings) which have occurred on last mapping.
     *
     * @var \TYPO3\CMS\Extbase\Error\Result
     */
    protected $messages;

    public function __construct(
        ContainerInterface $container,
        PropertyMappingConfigurationBuilder $configurationBuilder
    ) {
        $this->container = $container;
        $this->configurationBuilder = $configurationBuilder;
    }

    /**
     * Lifecycle method, called after all dependencies have been injected.
     * Here, the typeConverter array gets initialized.
     *
     * @throws Exception\DuplicateTypeConverterException
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function initializeObject()
    {
        $this->resetMessages();
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['typeConverters'] as $typeConverterClassName) {
            if ($this->container->has($typeConverterClassName)) {
                $typeConverter = $this->container->get($typeConverterClassName);
            } else {
                // @deprecated since v11, will be removed in v12.
                $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
                $typeConverter = $objectManager->get($typeConverterClassName);
            }
            foreach ($typeConverter->getSupportedSourceTypes() as $supportedSourceType) {
                if (isset($this->typeConverters[$supportedSourceType][$typeConverter->getSupportedTargetType()][$typeConverter->getPriority()])) {
                    throw new DuplicateTypeConverterException('There exist at least two converters which handle the conversion from "' . $supportedSourceType . '" to "' . $typeConverter->getSupportedTargetType() . '" with priority "' . $typeConverter->getPriority() . '": ' . get_class($this->typeConverters[$supportedSourceType][$typeConverter->getSupportedTargetType()][$typeConverter->getPriority()]) . ' and ' . get_class($typeConverter), 1297951378);
                }
                $this->typeConverters[$supportedSourceType][$typeConverter->getSupportedTargetType()][$typeConverter->getPriority()] = $typeConverter;
            }
        }
    }

    /**
     * Map $source to $targetType, and return the result
     *
     * @param mixed $source the source data to map. MUST be a simple type, NO object allowed!
     * @param string $targetType The type of the target; can be either a class name or a simple type.
     * @param PropertyMappingConfigurationInterface $configuration Configuration for the property mapping. If NULL, the PropertyMappingConfigurationBuilder will create a default configuration.
     * @throws Exception
     * @return mixed an instance of $targetType
     */
    public function convert($source, $targetType, PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($configuration === null) {
            $configuration = $this->configurationBuilder->build();
        }
        $currentPropertyPath = [];
        try {
            $result = $this->doMapping($source, $targetType, $configuration, $currentPropertyPath);
            if ($result instanceof Error) {
                return null;
            }

            return $result;
        } catch (TargetNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new Exception('Exception while property mapping at property path "' . implode('.', $currentPropertyPath) . '": ' . $e->getMessage(), 1297759968, $e);
        }
    }

    /**
     * Get the messages of the last Property Mapping.
     *
     * @return \TYPO3\CMS\Extbase\Error\Result
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Resets the messages of the last Property Mapping.
     */
    public function resetMessages(): void
    {
        $this->messages = new Result();
    }

    /**
     * Internal function which actually does the property mapping.
     *
     * @param mixed $source the source data to map. MUST be a simple type, NO object allowed!
     * @param string $targetType The type of the target; can be either a class name or a simple type.
     * @param PropertyMappingConfigurationInterface $configuration Configuration for the property mapping.
     * @param array $currentPropertyPath The property path currently being mapped; used for knowing the context in case an exception is thrown.
     * @throws Exception\TypeConverterException
     * @throws Exception\InvalidPropertyMappingConfigurationException
     * @return mixed an instance of $targetType
     */
    protected function doMapping($source, $targetType, PropertyMappingConfigurationInterface $configuration, &$currentPropertyPath)
    {
        if (is_object($source)) {
            $targetType = $this->parseCompositeType($targetType);
            if ($source instanceof $targetType) {
                return $source;
            }
        }

        if ($source === null) {
            $source = '';
        }

        $typeConverter = $this->findTypeConverter($source, $targetType, $configuration);
        $targetType = $typeConverter->getTargetTypeForSource($source, $targetType, $configuration);

        if (!is_object($typeConverter) || !$typeConverter instanceof TypeConverterInterface) {
            // todo: this Exception is never thrown as findTypeConverter returns an object or throws an Exception.
            throw new TypeConverterException(
                'Type converter for "' . $source . '" -> "' . $targetType . '" not found.',
                1476045062
            );
        }

        $convertedChildProperties = [];
        foreach ($typeConverter->getSourceChildPropertiesToBeConverted($source) as $sourcePropertyName => $sourcePropertyValue) {
            $targetPropertyName = $configuration->getTargetPropertyName($sourcePropertyName);
            if ($configuration->shouldSkip($targetPropertyName)) {
                continue;
            }

            if (!$configuration->shouldMap($targetPropertyName)) {
                if ($configuration->shouldSkipUnknownProperties()) {
                    continue;
                }
                throw new InvalidPropertyMappingConfigurationException('It is not allowed to map property "' . $targetPropertyName . '". You need to use $propertyMappingConfiguration->allowProperties(\'' . $targetPropertyName . '\') to enable mapping of this property.', 1355155913);
            }

            $targetPropertyType = $typeConverter->getTypeOfChildProperty($targetType, $targetPropertyName, $configuration);

            $subConfiguration = $configuration->getConfigurationFor($targetPropertyName);

            $currentPropertyPath[] = $targetPropertyName;
            $targetPropertyValue = $this->doMapping($sourcePropertyValue, $targetPropertyType, $subConfiguration, $currentPropertyPath);
            array_pop($currentPropertyPath);
            if (!($targetPropertyValue instanceof Error)) {
                $convertedChildProperties[$targetPropertyName] = $targetPropertyValue;
            }
        }
        $result = $typeConverter->convertFrom($source, $targetType, $convertedChildProperties, $configuration);

        if ($result instanceof Error) {
            $this->messages->forProperty(implode('.', $currentPropertyPath))->addError($result);
        }

        return $result;
    }

    /**
     * Determine the type converter to be used. If no converter has been found, an exception is raised.
     *
     * @param mixed $source
     * @param string $targetType
     * @param PropertyMappingConfigurationInterface $configuration
     * @throws Exception\TypeConverterException
     * @throws Exception\InvalidTargetException
     * @return \TYPO3\CMS\Extbase\Property\TypeConverterInterface Type Converter which should be used to convert between $source and $targetType.
     */
    protected function findTypeConverter($source, $targetType, PropertyMappingConfigurationInterface $configuration)
    {
        if ($configuration->getTypeConverter() !== null) {
            return $configuration->getTypeConverter();
        }

        $sourceType = $this->determineSourceType($source);

        if (!is_string($targetType)) {
            throw new InvalidTargetException('The target type was no string, but of type "' . gettype($targetType) . '"', 1297941727);
        }

        $targetType = $this->parseCompositeType($targetType);
        // This is needed to correctly convert old class names to new ones
        // This compatibility layer will be removed with 7.0
        $targetType = ClassLoadingInformation::getClassNameForAlias($targetType);

        $targetType = TypeHandlingUtility::normalizeType($targetType);

        $converter = null;

        if (TypeHandlingUtility::isSimpleType($targetType)) {
            if (isset($this->typeConverters[$sourceType][$targetType])) {
                $converter = $this->findEligibleConverterWithHighestPriority($this->typeConverters[$sourceType][$targetType], $source, $targetType);
            }
        } else {
            $converter = $this->findFirstEligibleTypeConverterInObjectHierarchy($source, $sourceType, $targetType);
        }

        if ($converter === null) {
            throw new TypeConverterException(
                'No converter found which can be used to convert from "' . $sourceType . '" to "' . $targetType . '".',
                1476044883
            );
        }

        return $converter;
    }

    /**
     * Tries to find a suitable type converter for the given source and target type.
     *
     * @param string $source The actual source value
     * @param string $sourceType Type of the source to convert from
     * @param string $targetClass Name of the target class to find a type converter for
     * @return mixed Either the matching object converter or NULL
     * @throws Exception\InvalidTargetException
     */
    protected function findFirstEligibleTypeConverterInObjectHierarchy($source, $sourceType, $targetClass)
    {
        if (!class_exists($targetClass) && !interface_exists($targetClass)) {
            throw new InvalidTargetException('Could not find a suitable type converter for "' . $targetClass . '" because no such class or interface exists.', 1297948764);
        }

        if (!isset($this->typeConverters[$sourceType])) {
            return null;
        }

        $convertersForSource = $this->typeConverters[$sourceType];
        if (isset($convertersForSource[$targetClass])) {
            $converter = $this->findEligibleConverterWithHighestPriority($convertersForSource[$targetClass], $source, $targetClass);
            if ($converter !== null) {
                return $converter;
            }
        }

        foreach (class_parents($targetClass) as $parentClass) {
            if (!isset($convertersForSource[$parentClass])) {
                continue;
            }

            $converter = $this->findEligibleConverterWithHighestPriority($convertersForSource[$parentClass], $source, $targetClass);
            if ($converter !== null) {
                return $converter;
            }
        }

        $converters = $this->getConvertersForInterfaces($convertersForSource, class_implements($targetClass) ?: []);
        $converter = $this->findEligibleConverterWithHighestPriority($converters, $source, $targetClass);

        if ($converter !== null) {
            return $converter;
        }
        if (isset($convertersForSource['object'])) {
            return $this->findEligibleConverterWithHighestPriority($convertersForSource['object'], $source, $targetClass);
        }

        // todo: this case is impossible because at this point there must be an ObjectConverter
        // todo: which allowed the processing up to this point.
        return null;
    }

    /**
     * @param mixed $converters
     * @param mixed $source
     * @param string $targetType
     * @return mixed Either the matching object converter or NULL
     */
    protected function findEligibleConverterWithHighestPriority($converters, $source, $targetType)
    {
        if (!is_array($converters)) {
            // todo: this case is impossible as initializeObject always defines an array.
            return null;
        }
        krsort($converters, SORT_NUMERIC);
        reset($converters);
        /** @var AbstractTypeConverter $converter */
        foreach ($converters as $converter) {
            if ($converter->canConvertFrom($source, $targetType)) {
                return $converter;
            }
        }
        return null;
    }

    /**
     * @param array $convertersForSource
     * @param array $interfaceNames
     * @return array
     * @throws Exception\DuplicateTypeConverterException
     */
    protected function getConvertersForInterfaces(array $convertersForSource, array $interfaceNames)
    {
        $convertersForInterface = [];
        foreach ($interfaceNames as $implementedInterface) {
            if (isset($convertersForSource[$implementedInterface])) {
                foreach ($convertersForSource[$implementedInterface] as $priority => $converter) {
                    if (isset($convertersForInterface[$priority])) {
                        throw new DuplicateTypeConverterException('There exist at least two converters which handle the conversion to an interface with priority "' . $priority . '". ' . get_class($convertersForInterface[$priority]) . ' and ' . get_class($converter), 1297951338);
                    }
                    $convertersForInterface[$priority] = $converter;
                }
            }
        }
        return $convertersForInterface;
    }

    /**
     * Determine the type of the source data, or throw an exception if source was an unsupported format.
     *
     * @param mixed $source
     * @throws Exception\InvalidSourceException
     * @return string the type of $source
     */
    protected function determineSourceType($source)
    {
        if (is_string($source)) {
            return 'string';
        }
        if (is_array($source)) {
            return 'array';
        }
        if (is_float($source)) {
            return 'float';
        }
        if (is_int($source)) {
            return 'integer';
        }
        if (is_bool($source)) {
            return 'boolean';
        }
        throw new InvalidSourceException('The source is not of type string, array, float, integer or boolean, but of type "' . gettype($source) . '"', 1297773150);
    }

    /**
     * Parse a composite type like \Foo\Collection<\Bar\Entity> into
     * \Foo\Collection
     *
     * @param string $compositeType
     * @return string
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function parseCompositeType($compositeType)
    {
        if (str_contains($compositeType, '<')) {
            $compositeType = substr($compositeType, 0, (int)strpos($compositeType, '<'));
        }
        return $compositeType;
    }
}
