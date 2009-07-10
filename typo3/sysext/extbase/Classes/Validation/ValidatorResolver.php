<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * Validator resolver to automatically find a appropriate validator for a given subject
 *
 * @package Extbase
 * @subpackage extbase
 * @version $Id$
 */
class Tx_Extbase_Validation_ValidatorResolver {

	/**
	 * @var Tx_Extbase_Object_ManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var Tx_Extbase_Reflection_Service
	 */
	protected $reflectionService;

	/**
	 * @var array
	 */
	protected $baseValidatorConjunctions = array();

	/**
	 * Injects the object manager
	 *
	 * @param Tx_Extbase_Object_ManagerInterface $objectManager A reference to the object manager
	 * @return void
	 * @internal
	 */
	public function injectObjectManager(Tx_Extbase_Object_ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}
		
	/**
	 * Injects the reflection service
	 *
	 * @param Tx_Extbase_Reflection_Service $reflectionService
	 * @return void
	 * @internal
	 */
	public function injectReflectionService(Tx_Extbase_Reflection_Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Get a validator for a given data type. Returns a validator implementing
	 * the Tx_Extbase_Validation_Validator_ValidatorInterface or NULL if no validator
	 * could be resolved.
	 *
	 * @param string $validatorName Either one of the built-in data types or fully qualified validator class name
	 * @param array $validatorOptions Options to be passed to the validator
	 * @return Tx_Extbase_Validation_Validator_ValidatorInterface Validator or NULL if none found.
	 * @internal
	 */
	public function createValidator($validatorName, array $validatorOptions = array()) {
		$validatorClassName = $this->resolveValidatorObjectName($validatorName);
		if ($validatorClassName === FALSE) return NULL;
		$validator = $this->objectManager->getObject($validatorClassName);
		$validator->setOptions($validatorOptions);
		return ($validator instanceof Tx_Extbase_Validation_Validator_ValidatorInterface) ? $validator : NULL;
	}

	/**
	 * Resolves and returns the base validator conjunction for the given data type.
	 *
	 * If no validator could be resolved (which usually means that no validation is necessary),
	 * NULL is returned.
	 *
	 * @param string $dataType The data type to search a validator for. Usually the fully qualified object name
	 * @return Tx_Extbase_Validation_Validator_ConjunctionValidator The validator conjunction or NULL
	 * @internal
	 */
	public function getBaseValidatorConjunction($dataType) {
		if (!isset($this->baseValidatorConjunctions[$dataType])) {
			$this->baseValidatorConjunctions[$dataType] = $this->buildBaseValidatorConjunction($dataType);
		}
		return $this->baseValidatorConjunctions[$dataType];
	}

	/**
	 * Detects and registers any additional validators for arguments which were specified in the @validate
	 * annotations of a method.
	 *
	 * @return array Validator Conjunctions
	 * @internal
	 */
	public function buildMethodArgumentsValidatorConjunctions($className, $methodName) {
		$validatorConjunctions = array();

		$methodTagsValues = $this->reflectionService->getMethodTagsValues($className, $methodName);
		if (isset($methodTagsValues['validate'])) {
			foreach ($methodTagsValues['validate'] as $validateValue) {
				$matches = array();
				preg_match('/^\$(?P<argumentName>[a-zA-Z0-9]+)\s+(?P<validators>.*)$/', $validateValue, $matches);
				$argumentName = $matches['argumentName'];

				preg_match_all('/(?P<validatorName>[a-zA-Z0-9]+)(?:\((?P<validatorOptions>[^)]+)\))?/', $matches['validators'], $matches, PREG_SET_ORDER);
				foreach ($matches as $match) {
					$validatorName = $match['validatorName'];
					$validatorOptions = array();
					$rawValidatorOptions = isset($match['validatorOptions']) ? explode(',', $match['validatorOptions']) : array();
					foreach ($rawValidatorOptions as $rawValidatorOption) {
						if (strpos($rawValidatorOption, '=') !== FALSE) {
							list($optionName, $optionValue) = explode('=', $rawValidatorOption);
							$validatorOptions[trim($optionName)] = trim($optionValue);
						}
					}
					$newValidator = $this->createValidator($validatorName, $validatorOptions);
					if ($newValidator === NULL) throw new Tx_Extbase_Validation_Exception_NoSuchValidator('Invalid validate annotation in ' . $className . '->' . $methodName . '(): Could not resolve class name for  validator "' . $validatorName . '".', 1239853109);

					if  (isset($validatorConjunctions[$argumentName])) {
						$validatorConjunctions[$argumentName]->addValidator($newValidator);
					} else {
						$validatorConjunctions[$argumentName] = $this->createValidator('Conjunction');
						$validatorConjunctions[$argumentName]->addValidator($newValidator);
					}
				}
			}
		}
		return $validatorConjunctions;
	}

	/**
	 * Builds a base validator conjunction for the given data type.
	 *
	 * The base validation rules are those which were declared directly in a class (typically
	 * a model) through some @validate annotations.
	 *
	 * Additionally, if a custom validator was defined for the class in question, it will be added
	 * to the end of the conjunction. A custom validator is found if it follows the naming convention
	 * "[FullyqualifiedModelClassName]Validator".
	 *
	 * @param string $dataType The data type to build the validation conjunction for. Usually the fully qualified object name.
	 * @return Tx_Extbase_Validation_Validator_ConjunctionValidator The validator conjunction or NULL
	 */
	protected function buildBaseValidatorConjunction($dataType) {
		$validatorConjunction = $this->objectManager->getObject('Tx_Extbase_Validation_Validator_ConjunctionValidator');
		$possibleValidatorClassName = str_replace('_Model_', '_Validator_', $dataType) . 'Validator';
		$customValidatorObjectName = $this->resolveValidatorObjectName($possibleValidatorClassName);
		if ($customValidatorObjectName !== FALSE) {
			$validatorConjunction->addValidator($this->objectManager->getObject($customValidatorObjectName));
		}
		
		if (class_exists($dataType)) {
			$validatorCount = 0;
			$objectValidator = $this->createValidator('GenericObject');

			foreach ($this->reflectionService->getClassPropertyNames($dataType) as $classPropertyName) {
				$classPropertyTagsValues = $this->reflectionService->getPropertyTagsValues($dataType, $classPropertyName);
				if (!isset($classPropertyTagsValues['validate'])) continue;

				foreach ($classPropertyTagsValues['validate'] as $validateValue) {
					$matches = array();
					preg_match_all('/(?P<validatorName>[a-zA-Z0-9]+)(?:\((?P<validatorOptions>[^)]+)\))?/', $validateValue, $matches, PREG_SET_ORDER);
					foreach ($matches as $match) {
						$validatorName = $match['validatorName'];
						$validatorOptions = array();
						$rawValidatorOptions = isset($match['validatorOptions']) ? explode(',', $match['validatorOptions']) : array();
						foreach ($rawValidatorOptions as $rawValidatorOption) {
							if (strpos($rawValidatorOption, '=') !== FALSE) {
								list($optionName, $optionValue) = explode('=', $rawValidatorOption);
								$validatorOptions[trim($optionName)] = trim($optionValue);
							}
						}
						$newValidator = $this->createValidator($validatorName, $validatorOptions);
						if ($newValidator === NULL) throw new Tx_Extbase_Validation_Exception_NoSuchValidator('Invalid validate annotation in ' . $dataType . '::' . $classPropertyName . ': Could not resolve class name for  validator "' . $validatorName . '".', 1241098027);
						$objectValidator->addPropertyValidator($classPropertyName, $newValidator);
						$validatorCount ++;
					}
				}
			}
			if ($validatorCount > 0) $validatorConjunction->addValidator($objectValidator);
		}

		return $validatorConjunction;
	}

	/**
	 * Returns an object of an appropriate validator for the given class. If no validator is available
	 * NULL is returned
	 *
	 * @param string $validatorName Either the fully qualified class name of the validator or the short name of a built-in validator
	 * @return string Name of the validator object or FALSE
	 */
	protected function resolveValidatorObjectName($validatorName) {
		if (class_exists($validatorName)) return $validatorName;

		$possibleClassName = 'Tx_Extbase_Validation_Validator_' . $this->unifyDataType($validatorName) . 'Validator';
		if (class_exists($possibleClassName)) return $possibleClassName;

		return FALSE;
	}

	/**
	 * Preprocess data types. Used to map primitive PHP types to DataTypes used in Extbase.
	 *
	 * @param string $type Data type to unify
	 * @return string unified data type
	 */
	protected function unifyDataType($type) {
		switch ($type) {
			case 'int' :
				$type = 'Integer';
				break;
			case 'string' :
				$type = 'Text';
				break;
			case 'bool' :
				$type = 'Boolean';
				break;
			case 'double' :
				$type = 'Float';
				break;
			case 'numeric' :
				$type = 'Number';
				break;
			case 'mixed' :
				$type = 'Raw';
				break;
		}
		return ucfirst($type);
	}

}

?>