<?php
namespace TYPO3\CMS\Extbase\Property;

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
 * Configuration object for the property mapper.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
interface PropertyMappingConfigurationInterface
{
    /**
     * returns TRUE if the given propertyName should be mapped, FALSE otherwise.
     *
     * @param string $propertyName
     * @return bool
     * @api
     */
    public function shouldSkip($propertyName);

    /**
     * Whether unknown (unconfigured) properties should be skipped during
     * mapping, instead if causing an error.
     *
     * @return bool
     * @api
     */
    public function shouldSkipUnknownProperties();

    /**
     * Returns the sub-configuration for the passed $propertyName. Must ALWAYS return a valid configuration object!
     *
     * @param string $propertyName
     * @return \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface the property mapping configuration for the given $propertyName.
     * @api
     */
    public function getConfigurationFor($propertyName);

    /**
     * Maps the given $sourcePropertyName to a target property name.
     * Can be used to rename properties from source to target.
     *
     * @param string $sourcePropertyName
     * @return string property name of target
     * @api
     */
    public function getTargetPropertyName($sourcePropertyName);

    /**
     * @param string $typeConverterClassName
     * @param string $key
     * @return mixed configuration value for the specific $typeConverterClassName. Can be used by Type Converters to fetch converter-specific configuration
     * @api
     */
    public function getConfigurationValue($typeConverterClassName, $key);

    /**
     * This method can be used to explicitly force a TypeConverter to be used for this Configuration.
     *
     * @return \TYPO3\CMS\Extbase\Property\TypeConverterInterface The type converter to be used for this particular PropertyMappingConfiguration, or NULL if the system-wide configured type converter should be used.
     * @api
     */
    public function getTypeConverter();
}
