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
 * Concrete configuration object for the PropertyMapper.
 */
class PropertyMappingConfiguration implements PropertyMappingConfigurationInterface
{
    /**
     * Placeholder in property paths for multi-valued types
     */
    public const PROPERTY_PATH_PLACEHOLDER = '*';

    /**
     * multi-dimensional array which stores type-converter specific configuration:
     * 1. Dimension: Fully qualified class name of the type converter
     * 2. Dimension: Configuration Key
     * Value: Configuration Value
     *
     * @var array<class-string<TypeConverterInterface>, non-empty-string|int>
     */
    protected array $configuration = [];

    /**
     * Stores the configuration for specific child properties.
     *
     * @var array<self::PROPERTY_PATH_PLACEHOLDER|non-empty-string,PropertyMappingConfigurationInterface>
     */
    protected array $subConfigurationForProperty = [];

    /**
     * Keys which should be renamed
     *
     * @var array<non-empty-string, non-empty-string>
     */
    protected array $mapping = [];

    protected ?TypeConverterInterface $typeConverter = null;

    /**
     * List of allowed property names to be converted
     *
     * @var array<non-empty-string, non-empty-string>
     */
    protected array $propertiesToBeMapped = [];

    /**
     * List of property names to be skipped during property mapping
     *
     * @var array<non-empty-string, non-empty-string>
     */
    protected array $propertiesToSkip = [];

    /**
     * List of disallowed property names which will be ignored while property mapping
     *
     * @var array<non-empty-string, non-empty-string>
     */
    protected array $propertiesNotToBeMapped = [];

    /**
     * If TRUE, unknown properties will be skipped during property mapping
     */
    protected bool $skipUnknownProperties = false;

    /**
     * If TRUE, unknown properties will be mapped.
     */
    protected bool $mapUnknownProperties = false;

    /**
     * The behavior is as follows:
     *
     * - if a property has been explicitly forbidden using allowAllPropertiesExcept(...), it is directly rejected
     * - if a property has been allowed using allowProperties(...), it is directly allowed.
     * - if allowAllProperties* has been called, we allow unknown properties
     * - else, return FALSE.
     *
     * @param non-empty-string $propertyName
     * @return bool TRUE if the given propertyName should be mapped, FALSE otherwise.
     */
    public function shouldMap(string $propertyName): bool
    {
        if (isset($this->propertiesNotToBeMapped[$propertyName])) {
            return false;
        }

        if (isset($this->propertiesToBeMapped[$propertyName])) {
            return true;
        }

        if (isset($this->subConfigurationForProperty[self::PROPERTY_PATH_PLACEHOLDER])) {
            return true;
        }

        return $this->mapUnknownProperties;
    }

    /**
     * Check if the given $propertyName should be skipped during mapping.
     *
     * @param non-empty-string $propertyName
     */
    public function shouldSkip(string $propertyName): bool
    {
        return isset($this->propertiesToSkip[$propertyName]);
    }

    /**
     * Allow all properties in property mapping, even unknown ones.
     *
     * @return $this
     */
    public function allowAllProperties(): self
    {
        $this->mapUnknownProperties = true;
        return $this;
    }

    /**
     * Allow a list of specific properties. All arguments of
     * allowProperties are used here (varargs).
     *
     * Example: allowProperties('title', 'content', 'author')
     *
     * @param non-empty-string ...$propertyNames
     * @return $this
     */
    public function allowProperties(string ...$propertyNames): self
    {
        foreach ($propertyNames as $propertyName) {
            $this->propertiesToBeMapped[$propertyName] = $propertyName;
        }
        return $this;
    }

    /**
     * Skip a list of specific properties. All arguments of
     * skipProperties are used here (varargs).
     *
     * Example: skipProperties('unused', 'dummy')
     *
     * @param non-empty-string ...$propertyNames
     * @return $this
     */
    public function skipProperties(string ...$propertyNames): self
    {
        foreach ($propertyNames as $propertyName) {
            $this->propertiesToSkip[$propertyName] = $propertyName;
        }
        return $this;
    }

    /**
     * Allow all properties during property mapping, but reject a few
     * selected ones (blacklist).
     *
     * Example: allowAllPropertiesExcept('password', 'userGroup')
     *
     * @param non-empty-string ...$propertyNames
     * @return $this
     */
    public function allowAllPropertiesExcept(string ...$propertyNames): self
    {
        $this->mapUnknownProperties = true;

        foreach ($propertyNames as $propertyName) {
            $this->propertiesNotToBeMapped[$propertyName] = $propertyName;
        }
        return $this;
    }

    /**
     * When this is enabled, properties that are disallowed will be skipped
     * instead of triggering an error during mapping.
     *
     * @return $this
     */
    public function skipUnknownProperties(): self
    {
        $this->skipUnknownProperties = true;
        return $this;
    }

    /**
     * Whether unknown (non configured) properties should be skipped during
     * mapping, instead if causing an error.
     */
    public function shouldSkipUnknownProperties(): bool
    {
        return $this->skipUnknownProperties;
    }

    /**
     * Returns the sub-configuration for the passed $propertyName. Must ALWAYS return a valid configuration object!
     *
     * @param non-empty-string $propertyName
     * @return PropertyMappingConfigurationInterface the property mapping configuration for the given $propertyName.
     */
    public function getConfigurationFor(string $propertyName): PropertyMappingConfigurationInterface
    {
        if (isset($this->subConfigurationForProperty[$propertyName])) {
            return $this->subConfigurationForProperty[$propertyName];
        }
        if (isset($this->subConfigurationForProperty[self::PROPERTY_PATH_PLACEHOLDER])) {
            return $this->subConfigurationForProperty[self::PROPERTY_PATH_PLACEHOLDER];
        }

        return new self();
    }

    /**
     * Maps the given $sourcePropertyName to a target property name.
     *
     * @param non-empty-string $sourcePropertyName
     * @return non-empty-string property name of target
     */
    public function getTargetPropertyName(string $sourcePropertyName): string
    {
        if (isset($this->mapping[$sourcePropertyName])) {
            return $this->mapping[$sourcePropertyName];
        }
        return $sourcePropertyName;
    }

    /**
     * @param class-string<TypeConverterInterface> $typeConverterClassName
     * @param non-empty-string|int $key
     * @return mixed configuration value for the specific $typeConverterClassName. Can be used by Type Converters to fetch converter-specific configuration.
     */
    public function getConfigurationValue(string $typeConverterClassName, string|int $key): mixed
    {
        if (!isset($this->configuration[$typeConverterClassName][$key])) {
            return null;
        }

        return $this->configuration[$typeConverterClassName][$key];
    }

    /**
     * Define renaming from Source to Target property.
     *
     * @param non-empty-string $sourcePropertyName
     * @param non-empty-string $targetPropertyName
     * @return $this
     */
    public function setMapping(string $sourcePropertyName, string $targetPropertyName): self
    {
        $this->mapping[$sourcePropertyName] = $targetPropertyName;
        return $this;
    }

    /**
     * Set all options for the given $typeConverter.
     *
     * @param class-string<TypeConverterInterface> $typeConverter class name of type converter
     * @return $this
     */
    public function setTypeConverterOptions(string $typeConverter, array $options): self
    {
        foreach ($this->getTypeConvertersWithParentClasses($typeConverter) as $typeConverter) {
            $this->configuration[$typeConverter] = $options;
        }
        return $this;
    }

    /**
     * Set a single option (denoted by $optionKey) for the given $typeConverter.
     *
     * @param class-string<TypeConverterInterface> $typeConverter class name of type converter
     * @param non-empty-string|int $optionKey
     * @param mixed $optionValue
     * @return $this
     */
    public function setTypeConverterOption(string $typeConverter, string|int $optionKey, mixed $optionValue): self
    {
        foreach ($this->getTypeConvertersWithParentClasses($typeConverter) as $typeConverter) {
            $this->configuration[$typeConverter][$optionKey] = $optionValue;
        }
        return $this;
    }

    /**
     * Get type converter classes including parents for the given type converter
     *
     * When setting an option on a subclassed type converter, this option must also be set on
     * all its parent type converters.
     *
     * @param class-string<TypeConverterInterface> $typeConverter The type converter class
     * @return array<class-string> Class names of type converters
     */
    protected function getTypeConvertersWithParentClasses(string $typeConverter): array
    {
        $typeConverterClasses = class_parents($typeConverter);
        $typeConverterClasses = $typeConverterClasses ?: [];
        $typeConverterClasses[] = $typeConverter;
        return $typeConverterClasses;
    }

    /**
     * Returns the configuration for the specific property path, ready to be modified. Should be used
     * inside a fluent interface like:
     * $configuration->forProperty('foo.bar')->setTypeConverterOption(....)
     *
     * @param non-empty-string $propertyPath
     */
    public function forProperty(string $propertyPath): PropertyMappingConfigurationInterface
    {
        $splitPropertyPath = explode('.', $propertyPath);
        return $this->traverseProperties($splitPropertyPath);
    }

    /**
     * Traverse the property configuration. Only used by forProperty().
     */
    public function traverseProperties(array $splitPropertyPath): PropertyMappingConfigurationInterface
    {
        if (empty($splitPropertyPath)) {
            return $this;
        }

        $currentProperty = array_shift($splitPropertyPath);
        if (!isset($this->subConfigurationForProperty[$currentProperty])) {
            $type = static::class;
            if (isset($this->subConfigurationForProperty[self::PROPERTY_PATH_PLACEHOLDER])) {
                $this->subConfigurationForProperty[$currentProperty] = clone $this->subConfigurationForProperty[self::PROPERTY_PATH_PLACEHOLDER];
            } else {
                $this->subConfigurationForProperty[$currentProperty] = new $type();
            }
        }
        return $this->subConfigurationForProperty[$currentProperty]->traverseProperties($splitPropertyPath);
    }

    /**
     * Return the type converter set for this configuration.
     */
    public function getTypeConverter(): ?TypeConverterInterface
    {
        return $this->typeConverter;
    }

    /**
     * Set a type converter which should be used for this specific conversion.
     *
     * @return $this
     */
    public function setTypeConverter(TypeConverterInterface $typeConverter)
    {
        $this->typeConverter = $typeConverter;
        return $this;
    }
}
