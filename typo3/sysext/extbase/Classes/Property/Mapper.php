<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * The Property Mapper maps properties from a source onto a given target object, often a
 * (domain-) model. Which properties are required and how they should be filtered can
 * be customized.
 *
 * During the mapping process, the property values are validated and the result of this
 * validation can be queried.
 *
 * The following code would map the property of the source array to the target:
 *
 * $target = new ArrayObject();
 * $source = new ArrayObject(
 *    array(
 *       'someProperty' => 'SomeValue'
 *    )
 * );
 * $mapper->mapAndValidate(array('someProperty'), $source, $target);
 *
 * Now the target object equals the source object.
 *
 * @package TYPO3
 * @subpackage extbase
 * @version $Id: $
 */
class Tx_Extbase_Property_Mapper {

	/**
	 * Results of the last mapping operation
	 * @var Tx_Extbase_Property_MappingResults
	 */
	protected $mappingResults;
		
	/**
	 * @var Tx_Extbase_Validation_ValidatorResolver
	 */
	protected $validatorResolver;
	
	
	/**
	 * Constructs the Property Mapper.
	 */
	public function __construct() {
		$this->validatorResolver = t3lib_div::makeInstance('Tx_Extbase_Validation_ValidatorResolver');
	}
	
	/**
	 * Maps the given properties to the target object and validates the properties according to the defined
	 * validators. If the result object is not valid, the operation will be undone (the target object remains
	 * unchanged) and this method returns FALSE.
	 *
	 * If in doubt, always prefer this method over the map() method because skipping validation can easily become
	 * a security issue.
	 *
	 * @param array $propertyNames Names of the properties to map.
	 * @param mixed $source Source containing the properties to map to the target object. Must either be an array, ArrayObject or any other object.
	 * @param object $target The target object
	 * @param Tx_Extbase_Validation_Validator_ObjectValidatorInterface $targetObjectValidator A validator used for validating the target object
	 * @param array $optionalPropertyNames Names of optional properties. If a property is specified here and it doesn't exist in the source, no error is issued.
	 * @return boolean TRUE if the mapped properties are valid, otherwise FALSE
	 * @see getMappingResults()
	 * @see map()
	 */
	public function mapAndValidate(array $propertyNames, $source, $target, $optionalPropertyNames = array(), Tx_Extbase_Validation_Validator_ObjectValidatorInterface $targetObjectValidator) {
		$backupProperties = array();

		$this->map($propertyNames, $source, $backupProperties, $optionalPropertyNames);
		if ($this->mappingResults->hasErrors()) return FALSE;

		$this->map($propertyNames, $source, $target, $optionalPropertyNames);
		if ($this->mappingResults->hasErrors()) return FALSE;

		if ($targetObjectValidator->isValid($target) !== TRUE) {
			$this->mappingResults->addError('Validation errors: ' . implode('. ', $targetObjectValidator->getErrors()), '*');
			$backupMappingResult = $this->mappingResults;
			$this->map($propertyNames, $backupProperties, $source, $optionalPropertyNames);
			$this->mappingResults = $backupMappingResult;
		}
		return (!$this->mappingResults->hasErrors());
	}

	/**
	 * Maps the given properties to the target object WITHOUT VALIDATING THE RESULT.
	 * If the properties could be set, this method returns TRUE, otherwise FALSE.
	 * Returning TRUE does not mean that the target object is valid and secure!
	 * 
	 * Only use this method if you're sure that you don't need validation!
	 *
	 * @param array $propertyNames Names of the properties to map.
	 * @param mixed $source Source containing the properties to map to the target object. Must either be an array, ArrayObject or any other object.
	 * @param object $target The target object
	 * @param array $optionalPropertyNames Names of optional properties. If a property is specified here and it doesn't exist in the source, no error is issued.
	 * @return boolean TRUE if the properties could be mapped, otherwise FALSE
	 * @see mapAndValidate()
	 */
	public function map(array $propertyNames, $source, $target, $optionalPropertyNames = array()) {
		if (!is_object($source) && !is_array($source)) throw new Tx_Extbase_Property_Exception_InvalidSource('The source object must be a valid object or array, ' . gettype($target) . ' given.', 1187807099);
		if (!is_object($target) && !is_array($target)) throw new Tx_Extbase_Property_Exception_InvalidTarget('The target object must be a valid object or array, ' . gettype($target) . ' given.', 1187807099);

		$this->mappingResults = t3lib_div::makeInstance('Tx_Extbase_Property_MappingResults');
		$propertyValues = array();

		foreach ($propertyNames as $propertyName) {
			if (is_array($source) || $source instanceof ArrayAccess) {
				if (isset($source[$propertyName])) $propertyValues[$propertyName] = $source[$propertyName];
			} else {
				$propertyValues[$propertyName] = Tx_Extbase_Reflection_ObjectAccess::getProperty($source, $propertyName);
			}
		}
		foreach ($propertyNames as $propertyName) {
			if (isset($propertyValues[$propertyName])) {
				if (is_array($target)) {
					$target[$propertyName] = $source[$propertyName];
				} elseif (Tx_Extbase_Reflection_ObjectAccess::setProperty($target, $propertyName, $propertyValues[$propertyName]) === FALSE) {
					$this->mappingResults->addError("Property '$propertyName' could not be set.", $propertyName);
				}
			} elseif (!in_array($propertyName, $optionalPropertyNames)) {
				$this->mappingResults->addError("Required property '$propertyName' does not exist.", $propertyName);
			}
		}
		return (!$this->mappingResults->hasErrors() && !$this->mappingResults->hasWarnings());
	}

	/**
	 * Returns the results of the last mapping operation.
	 *
	 * @return Tx_Extbase_Property_MappingResults The mapping results (or NULL if no mapping has been carried out yet)
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMappingResults() {
		return $this->mappingResults;
	}
}

?>