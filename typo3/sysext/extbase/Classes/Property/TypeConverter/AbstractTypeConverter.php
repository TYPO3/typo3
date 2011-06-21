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
 * Type converter which provides sensible default implementations for most methods. If you extend this class
 * you only need to do the following:
 * - set $sourceTypes
 * - set $targetType
 * - set $priority
 * - implement convertFrom()
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
abstract class Tx_Extbase_Property_TypeConverter_AbstractTypeConverter implements Tx_Extbase_Property_TypeConverterInterface, t3lib_Singleton {

	/**
	 * The source types this converter can convert.
	 *
	 * @var array<string>
	 * @api
	 */
	protected $sourceTypes = array();

	/**
	 * The target type this converter can convert to.
	 *
	 * @var string
	 * @api
	 */
	protected $targetType = '';

	/**
	 * The priority for this converter.
	 *
	 * @var integer
	 * @api
	 */
	protected $priority;

	/**
	 * Returns the list of source types the TypeConverter can handle.
	 * Must be PHP simple types, classes or object is not allowed.
	 *
	 * @return array<string>
	 * @api
	 */
	public function getSupportedSourceTypes() {
		return $this->sourceTypes;
	}

	/**
	 * Return the target type this TypeConverter converts to.
	 * Can be a simple type or a class name.
	 *
	 * @return string
	 * @api
	 */
	public function getSupportedTargetType() {
		return $this->targetType;
	}

	/**
	 * Return the priority of this TypeConverter. TypeConverters with a high priority are chosen before low priority.
	 *
	 * @return integer
	 * @api
	 */
	public function getPriority() {
		return $this->priority;
	}

	/**
	 * This implementation always returns TRUE for this method.
	 *
	 * @param mixed $source the source data
	 * @param string $targetType the type to convert to.
	 * @return boolean TRUE if this TypeConverter can convert from $source to $targetType, FALSE otherwise.
	 * @api
	 */
	public function canConvertFrom($source, $targetType) {
		return TRUE;
	}

	/**
	 * Returns an empty list of sub property names
	 *
	 * @return array<string>
	 * @api
	 */
	public function getSourceChildPropertiesToBeConverted($source) {
		return array();
	}

	/**
	 * This method is never called, as getSourceChildPropertiesToBeConverted() returns an empty array.
	 *
	 * @param string $targetType
	 * @param string $propertyName
	 * @param Tx_Extbase_Property_PropertyMappingConfigurationInterface $configuration
	 * @return string
	 * @api
	 */
	public function getTypeOfChildProperty($targetType, $propertyName, Tx_Extbase_Property_PropertyMappingConfigurationInterface $configuration) {
	}
}
?>