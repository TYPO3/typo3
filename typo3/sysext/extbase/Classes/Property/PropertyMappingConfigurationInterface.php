<?php
namespace TYPO3\CMS\Extbase\Property;

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
 * Configuration object for the property mapper.
 */
interface PropertyMappingConfigurationInterface
{
    /**
     * returns TRUE if the given propertyName should be mapped, FALSE otherwise.
     *
     * @param string $propertyName
     * @return bool
     */
    public function shouldSkip($propertyName);

    /**
     * Whether unknown (unconfigured) properties should be skipped during
     * mapping, instead if causing an error.
     *
     * @return bool
     */
    public function shouldSkipUnknownProperties();

    /**
     * Returns the sub-configuration for the passed $propertyName. Must ALWAYS return a valid configuration object!
     *
     * @param string $propertyName
     * @return \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface the property mapping configuration for the given $propertyName.
     */
    public function getConfigurationFor($propertyName);

    /**
     * Maps the given $sourcePropertyName to a target property name.
     * Can be used to rename properties from source to target.
     *
     * @param string $sourcePropertyName
     * @return string property name of target
     */
    public function getTargetPropertyName($sourcePropertyName);

    /**
     * @param string $typeConverterClassName
     * @param string $key
     * @return mixed configuration value for the specific $typeConverterClassName. Can be used by Type Converters to fetch converter-specific configuration
     */
    public function getConfigurationValue($typeConverterClassName, $key);

    /**
     * This method can be used to explicitly force a TypeConverter to be used for this Configuration.
     *
     * @return \TYPO3\CMS\Extbase\Property\TypeConverterInterface The type converter to be used for this particular PropertyMappingConfiguration, or NULL if the system-wide configured type converter should be used.
     */
    public function getTypeConverter();
}
