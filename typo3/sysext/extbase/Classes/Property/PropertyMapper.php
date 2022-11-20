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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException;
use TYPO3\CMS\Extbase\Property\Exception\TargetNotFoundException;
use TYPO3\CMS\Extbase\Utility\TypeHandlingUtility;

/**
 * The Property Mapper transforms simple types (arrays, strings, integers, floats, booleans) to objects or other simple types.
 * It is used most prominently to map incoming HTTP arguments to objects.
 */
class PropertyMapper implements SingletonInterface
{
    /**
     * A list of property mapping messages (errors, warnings) which have occurred on last mapping.
     */
    protected Result $messages;

    public function __construct(
        protected TypeConverterRegistry $typeConverterRegistry,
        protected PropertyMappingConfigurationBuilder $configurationBuilder
    ) {
        $this->resetMessages();
    }

    /**
     * Map $source to $targetType, and return the result
     *
     * @param mixed $source the source data to map. MUST be a simple type, NO object allowed!
     * @param string $targetType The type of the target; can be either a class name or a simple type.
     * @param PropertyMappingConfigurationInterface|null $configuration Configuration for the property mapping. If NULL, the PropertyMappingConfigurationBuilder will create a default configuration.
     * @throws Exception
     * @return mixed an instance of $targetType
     */
    public function convert($source, string $targetType, ?PropertyMappingConfigurationInterface $configuration = null)
    {
        $configuration ??= $this->configurationBuilder->build();
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
     */
    public function getMessages(): Result
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
     * @return mixed an instance of $targetType
     *
     * @throws Exception\TypeConverterException
     * @throws Exception\InvalidPropertyMappingConfigurationException
     * @throws Exception\DuplicateTypeConverterException
     * @throws Exception\InvalidPropertyMappingConfigurationException
     * @throws Exception\InvalidSourceException
     * @throws Exception\InvalidTargetException
     * @throws Exception\TypeConverterException
     *
     * @internal since TYPO3 v12.0
     */
    protected function doMapping($source, string $targetType, PropertyMappingConfigurationInterface $configuration, array &$currentPropertyPath)
    {
        if (is_object($source)) {
            $targetType = $this->parseCompositeType($targetType);
            if ($source instanceof $targetType) {
                return $source;
            }
        }

        $source ??= '';

        $typeConverter = $this->findTypeConverter($source, $targetType, $configuration);
        $targetType = $typeConverter->getTargetTypeForSource($source, $targetType, $configuration);

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
     * @return TypeConverterInterface Type Converter which should be used to convert between $source and $targetType.
     *
     * @throws Exception\TypeConverterException
     * @throws Exception\InvalidTargetException
     * @throws Exception\DuplicateTypeConverterException
     * @throws Exception\InvalidSourceException
     *
     * @internal since TYPO3 v12.0
     */
    protected function findTypeConverter($source, string $targetType, PropertyMappingConfigurationInterface $configuration): TypeConverterInterface
    {
        if ($configuration->getTypeConverter() !== null) {
            return $configuration->getTypeConverter();
        }

        $sourceType = $this->determineSourceType($source);

        $targetType = $this->parseCompositeType($targetType);
        $targetType = TypeHandlingUtility::normalizeType($targetType);

        return $this->typeConverterRegistry->findTypeConverter($sourceType, $targetType);
    }

    /**
     * Determine the type of the source data, or throw an exception if source was an unsupported format.
     *
     * @param mixed $source
     * @throws Exception\InvalidSourceException
     *
     * @internal since TYPO3 v12.0
     */
    protected function determineSourceType($source): string
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
        throw new Exception\InvalidSourceException('The source is not of type string, array, float, integer or boolean, but of type "' . gettype($source) . '"', 1297773150);
    }

    /**
     * Parse a composite type like \Foo\Collection<\Bar\Entity> into
     * \Foo\Collection
     *
     * @internal since TYPO3 v12.0
     */
    protected function parseCompositeType(string $compositeType): string
    {
        if (str_contains($compositeType, '<')) {
            $compositeType = substr($compositeType, 0, (int)strpos($compositeType, '<'));
        }
        return $compositeType;
    }
}
