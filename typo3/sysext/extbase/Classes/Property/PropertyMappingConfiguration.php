<?php

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
 * Concrete configuration object for the PropertyMapper.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Tx_Extbase_Property_PropertyMappingConfiguration implements Tx_Extbase_Property_PropertyMappingConfigurationInterface {

	/**
	 * multi-dimensional array which stores type-converter specific configuration:
	 * 1. Dimension: Fully qualified class name of the type converter
	 * 2. Dimension: Configuration Key
	 * Value: Configuration Value
	 *
	 * @var array
	 */
	protected $configuration;

	/**
	 * Stores the configuration for specific child properties.
	 *
	 * @var array<Tx_Extbase_Property_PropertyMappingConfigurationInterface>
	 */
	protected $subConfigurationForProperty = array();

	/**
	 * The parent PropertyMappingConfiguration. If a configuration value for the current entry is not found, we propagate the question to the parent.
	 *
	 * @var Tx_Extbase_Property_PropertyMappingConfigurationInterface
	 */
	protected $parentConfiguration;

	/**
	 * Keys which should be renamed
	 *
	 * @var array
	 */
	protected $mapping = array();

	/**
	 * @var Tx_Extbase_Property_TypeConverterInterface
	 */
	protected $typeConverter = NULL;

	/**
	 * Set the parent PropertyMappingConfiguration. Only used internally!
	 *
	 * @param Tx_Extbase_Property_PropertyMappingConfigurationInterface $parentConfiguration
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function setParent(Tx_Extbase_Property_PropertyMappingConfigurationInterface $parentConfiguration) {
		$this->parentConfiguration = $parentConfiguration;
	}

	/**
	 * @return TRUE if the given propertyName should be mapped, FALSE otherwise.
	 * @todo: extend to enable whitelisting / blacklisting of properties.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function shouldMap($propertyName) {
		return TRUE;
	}

	/**
	 * Returns the sub-configuration for the passed $propertyName. Must ALWAYS return a valid configuration object!
	 *
	 * @param string $propertyName
	 * @return Tx_Extbase_Property_PropertyMappingConfigurationInterface the property mapping configuration for the given $propertyName.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function getConfigurationFor($propertyName) {
		if (isset($this->subConfigurationForProperty[$propertyName])) {
			return $this->subConfigurationForProperty[$propertyName];
		}

		return new Tx_Extbase_Property_PropertyMappingConfiguration();
	}

	/**
	 * Maps the given $sourcePropertyName to a target property name.
	 *
	 * @param string $sourcePropertyName
	 * @return string property name of target
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function getTargetPropertyName($sourcePropertyName) {
		if (isset($this->mapping[$sourcePropertyName])) {
			return $this->mapping[$sourcePropertyName];
		}
		return $sourcePropertyName;
	}

	/**
	 * @param string $typeConverterClassName
	 * @param string $key
	 * @return mixed configuration value for the specific $typeConverterClassName. Can be used by Type Converters to fetch converter-specific configuration.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function getConfigurationValue($typeConverterClassName, $key) {
		if (!isset($this->configuration[$typeConverterClassName][$key])) {
			return NULL;
		}

		return $this->configuration[$typeConverterClassName][$key];
	}

	/**
	 * Define renaming from Source to Target property.
	 *
	 * @param string $sourcePropertyName
	 * @param string $targetPropertyName
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function setMapping($sourcePropertyName, $targetPropertyName) {
		$this->mapping[$sourcePropertyName] = $targetPropertyName;
	}

	/**
	 * Set all options for the given $typeConverter.
	 * @param string $typeConverter class name of type converter
	 * @param array $options
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function setTypeConverterOptions($typeConverter, array $options) {
		$this->configuration[$typeConverter] = $options;
	}

	/**
	 * Set a single option (denoted by $optionKey) for the given $typeConverter.
	 *
	 * @param string $typeConverter class name of type converter
	 * @param string $optionKey
	 * @param mixed $optionValue
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function setTypeConverterOption($typeConverter, $optionKey, $optionValue) {
		$this->configuration[$typeConverter][$optionKey] = $optionValue;
	}

	/**
	 * Returns the configuration for the specific property path, ready to be modified. Should be used
	 * inside a fluent interface like:
	 * $configuration->forProperty('foo.bar')->setTypeConverterOption(....)
	 *
	 * @param string $propertyPath
	 * @return Tx_Extbase_Property_PropertyMappingConfiguration (or a subclass thereof)
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function forProperty($propertyPath) {
		$splittedPropertyPath = explode('.', $propertyPath);
		return $this->traverseProperties($splittedPropertyPath);
	}

	/**
	 * Traverse the property configuration. Only used by forProperty().
	 *
	 * @param array $splittedPropertyPath
	 * @return Tx_Extbase_Property_PropertyMappingConfiguration (or a subclass thereof)
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function traverseProperties(array $splittedPropertyPath) {
		if (count($splittedPropertyPath) === 0) {
			return $this;
		}

		$currentProperty = array_shift($splittedPropertyPath);
		if (!isset($this->subConfigurationForProperty[$currentProperty])) {
			$type = get_class($this);
			$this->subConfigurationForProperty[$currentProperty] = new $type;
			$this->subConfigurationForProperty[$currentProperty]->setParent($this);
		}
		return $this->subConfigurationForProperty[$currentProperty]->traverseProperties($splittedPropertyPath);
	}

	/**
	 * Return the type converter set for this configuration.
	 *
	 * @return Tx_Extbase_Property_TypeConverterInterface
	 * @api
	 */
	public function getTypeConverter() {
		return $this->typeConverter;
	}

	/**
	 * Set a type converter which should be used for this specific conversion.
	 *
	 * @param Tx_Extbase_Property_TypeConverterInterface $typeConverter
	 * @return void
	 * @api
	 */
	public function setTypeConverter(Tx_Extbase_Property_TypeConverterInterface $typeConverter) {
		$this->typeConverter = $typeConverter;
	}
}
?>