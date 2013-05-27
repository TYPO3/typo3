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
 * Converter which transforms arrays to arrays.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Tx_Extbase_Property_TypeConverter_ObjectStorageConverter extends Tx_Extbase_Property_TypeConverter_AbstractTypeConverter implements t3lib_Singleton {

	/**
	 * @var Tx_Extbase_Service_TypeHandlingService
	 */
	protected $typeHandlingService;

	/**
	 * @param Tx_Extbase_Service_TypeHandlingService $typeHandlingService
	 * @return void
	 */
	public function injectTypeHandlingService(Tx_Extbase_Service_TypeHandlingService $typeHandlingService) {
		$this->typeHandlingService = $typeHandlingService;
	}

	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('string', 'array');

	/**
	 * @var string
	 */
	protected $targetType = 'Tx_Extbase_Persistence_ObjectStorage';

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * Returns the source, if it is an array, otherwise an empty array.
	 *
	 * @return array
	 * @api
	 */
	public function getSourceChildPropertiesToBeConverted($source) {
		if (is_array($source)) {
			return $source;
		}
		return array();
	}

	/**
	 * Actually convert from $source to $targetType, in fact a noop here.
	 *
	 * @param array $source
	 * @param string $targetType
	 * @param array $convertedChildProperties
	 * @param Tx_Extbase_Property_PropertyMappingConfigurationInterface $configuration
	 * @return array
	 * @api
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), Tx_Extbase_Property_PropertyMappingConfigurationInterface $configuration = NULL) {
		$objectStorage = new Tx_Extbase_Persistence_ObjectStorage();

		foreach ($convertedChildProperties as $subProperty) {
			$objectStorage->attach($subProperty);
		}

		return $objectStorage;
	}

	/**
	 * Return the type of a given sub-property inside the $targetType
	 *
	 * @param string $targetType
	 * @param string $propertyName
	 * @param Tx_Extbase_Property_PropertyMappingConfigurationInterface $configuration
	 * @return string
	 * @api
	 */
	public function getTypeOfChildProperty($targetType, $propertyName, Tx_Extbase_Property_PropertyMappingConfigurationInterface $configuration) {

		$parsedTargetType = $this->typeHandlingService->parseType($targetType);
		return $parsedTargetType['elementType'];
	}
}
?>