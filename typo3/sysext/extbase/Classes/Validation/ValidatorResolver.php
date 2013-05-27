<?php
namespace TYPO3\CMS\Extbase\Validation;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\ClassNamingUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException;

/**
 * Validator resolver to automatically find a appropriate validator for a given subject
 */
class ValidatorResolver implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Match validator names and options
	 *
	 * @var string
	 */
	const PATTERN_MATCH_VALIDATORS = '/
			(?:^|,\\s*)
			(?P<validatorName>[a-z0-9_:.\\\\]+)
			\\s*
			(?:\\(
				(?P<validatorOptions>(?:\\s*[a-z0-9]+\\s*=\\s*(?:
					"(?:\\\\"|[^"])*"
					|\'(?:\\\\\'|[^\'])*\'
					|(?:\\s|[^,"\']*)
				)(?:\\s|,)*)*)
			\\))?
		/ixS';

	/**
	 * Match validator options (to parse actual options)
	 *
	 * @var string
	 */
	const PATTERN_MATCH_VALIDATOROPTIONS = '/
			\\s*
			(?P<optionName>[a-z0-9]+)
			\\s*=\\s*
			(?P<optionValue>
				"(?:\\\\"|[^"])*"
				|\'(?:\\\\\'|[^\'])*\'
				|(?:\\s|[^,"\']*)
			)
		/ixS';

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var array
	 */
	protected $baseValidatorConjunctions = array();

	/**
	 * Injects the object manager
	 *
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager A reference to the object manager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the reflection service
	 *
	 * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Get a validator for a given data type. Returns a validator implementing
	 * the \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface or NULL if no validator
	 * could be resolved.
	 *
	 * @param string $validatorName Either one of the built-in data types or fully qualified validator class name
	 * @param array $validatorOptions Options to be passed to the validator
	 * @return \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface Validator or NULL if none found.
	 */
	public function createValidator($validatorName, array $validatorOptions = array()) {
		$validatorClassName = $this->resolveValidatorObjectName($validatorName);
		$validator = $this->objectManager->get($validatorClassName, $validatorOptions);
		if (method_exists($validator, 'setOptions')) {
			// @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
			$validator->setOptions($validatorOptions);
		}
		return $validator;
	}

	/**
	 * Resolves and returns the base validator conjunction for the given data type.
	 *
	 * If no validator could be resolved (which usually means that no validation is necessary),
	 * NULL is returned.
	 *
	 * @param string $dataType The data type to search a validator for. Usually the fully qualified object name
	 * @return \TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator The validator conjunction or NULL
	 */
	public function getBaseValidatorConjunction($dataType) {
		if (!isset($this->baseValidatorConjunctions[$dataType])) {
			$this->baseValidatorConjunctions[$dataType] = $this->buildBaseValidatorConjunction($dataType);
		}
		return $this->baseValidatorConjunctions[$dataType];
	}

	/**
	 * Detects and registers any validators for arguments:
	 * - by the data type specified in the
	 *
	 * @param string $className
	 * @param string $methodName
	 * @throws NoSuchValidatorException
	 * @throws Exception\InvalidValidationConfigurationException
	 * @return array An Array of ValidatorConjunctions for each method parameters.
	 */
	public function buildMethodArgumentsValidatorConjunctions($className, $methodName) {
		$validatorConjunctions = array();
		$methodParameters = $this->reflectionService->getMethodParameters($className, $methodName);
		$methodTagsValues = $this->reflectionService->getMethodTagsValues($className, $methodName);
		if (count($methodParameters)) {
			foreach ($methodParameters as $parameterName => $methodParameter) {
				/** @var $validatorConjunction \TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator */
				$validatorConjunction = $this->createValidator('Conjunction');
				$validatorConjunctions[$parameterName] = $validatorConjunction;
				try {
					$validator = $this->createValidator($methodParameter['type']);
				} catch (NoSuchValidatorException $e) {
					GeneralUtility::sysLog('Classname ' . $methodParameter['type'] . ' is no valid Validator.', 'extbase', GeneralUtility::SYSLOG_SEVERITY_INFO);
					continue;
				}
				$validatorConjunctions[$parameterName]->addValidator($validator);
			}
			if (isset($methodTagsValues['validate'])) {
				foreach ($methodTagsValues['validate'] as $validateValue) {
					$parsedAnnotation = $this->parseValidatorAnnotation($validateValue);
					foreach ($parsedAnnotation['validators'] as $validatorConfiguration) {
						try {
							$newValidator = $this->createValidator($validatorConfiguration['validatorName'], $validatorConfiguration['validatorOptions']);
						} catch (NoSuchValidatorException $e) {
							GeneralUtility::sysLog('Classname ' . $validatorConfiguration['validatorName'] . ' is no valid Validator.', 'extbase', GeneralUtility::SYSLOG_SEVERITY_INFO);
							continue;
						}
						if (isset($validatorConjunctions[$parsedAnnotation['argumentName']])) {
							$validatorConjunctions[$parsedAnnotation['argumentName']]->addValidator($newValidator);
						} else {
							throw new \TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationConfigurationException('Invalid validate annotation in ' . $className . '->' . $methodName . '(): Validator specified for argument name "' . $parsedAnnotation['argumentName'] . '", but this argument does not exist.', 1253172726);
						}
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
	 * a model) through some @validate annotations on properties.
	 *
	 * Additionally, if a custom validator was defined for the class in question, it will be added
	 * to the end of the conjunction. A custom validator is found if it follows the naming convention
	 * "Replace '\Model\' by '\Validator\' and append "Validator".
	 *
	 * Example: $dataType is F3\Foo\Domain\Model\Quux, then the Validator will be found if it has the
	 * name F3\Foo\Domain\Validator\QuuxValidator
	 *
	 * @param string $dataType The data type to build the validation conjunction for. Needs to be the fully qualified object name.
	 * @throws NoSuchValidatorException
	 * @return \TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator The validator conjunction or NULL
	 */
	protected function buildBaseValidatorConjunction($dataType) {
		$validatorConjunction = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ConjunctionValidator');
		// Model based validator
		if (class_exists($dataType)) {
			$validatorCount = 0;
			try {
				$objectValidator = $this->createValidator('GenericObject');
			} catch (NoSuchValidatorException $e) {
				GeneralUtility::sysLog('Classname GenericObject is no valid Validator.', 'extbase', SYSLOG_SEVERITY_INFO);
			}
			foreach ($this->reflectionService->getClassPropertyNames($dataType) as $classPropertyName) {
				$classPropertyTagsValues = $this->reflectionService->getPropertyTagsValues($dataType, $classPropertyName);
				if (!isset($classPropertyTagsValues['validate'])) {
					continue;
				}
				foreach ($classPropertyTagsValues['validate'] as $validateValue) {
					$parsedAnnotation = $this->parseValidatorAnnotation($validateValue);
					foreach ($parsedAnnotation['validators'] as $validatorConfiguration) {
						try {
							$newValidator = $this->createValidator($validatorConfiguration['validatorName'], $validatorConfiguration['validatorOptions']);
						} catch (NoSuchValidatorException $e) {
							GeneralUtility::sysLog('Classname ' . $validatorConfiguration['validatorName'] . ' is no valid Validator.', 'extbase', SYSLOG_SEVERITY_INFO);
							continue;
						}
						$objectValidator->addPropertyValidator($classPropertyName, $newValidator);
						$validatorCount++;
					}
				}
			}
			if ($validatorCount > 0) {
				$validatorConjunction->addValidator($objectValidator);
			}
		}

		// Custom validator for the class
		$possibleValidatorClassName = ClassNamingUtility::translateModelNameToValidatorName($dataType);
		$customValidator = NULL;
		try {
			$customValidator = $this->createValidator($possibleValidatorClassName);
		} catch (NoSuchValidatorException $e) {
			GeneralUtility::sysLog('Classname ' . $possibleValidatorClassName . ' is no valid Validator.', 'extbase', SYSLOG_SEVERITY_INFO);
		}
		if ($customValidator !== NULL) {
			$validatorConjunction->addValidator($customValidator);
		}
		return $validatorConjunction;
	}

	/**
	 * Parses the validator options given in @validate annotations.
	 *
	 * @param string $validateValue
	 * @return array
	 */
	protected function parseValidatorAnnotation($validateValue) {
		$matches = array();
		if ($validateValue[0] === '$') {
			$parts = explode(' ', $validateValue, 2);
			$validatorConfiguration = array('argumentName' => ltrim($parts[0], '$'), 'validators' => array());
			preg_match_all(self::PATTERN_MATCH_VALIDATORS, $parts[1], $matches, PREG_SET_ORDER);
		} else {
			$validatorConfiguration = array('validators' => array());
			preg_match_all(self::PATTERN_MATCH_VALIDATORS, $validateValue, $matches, PREG_SET_ORDER);
		}
		foreach ($matches as $match) {
			$validatorOptions = array();
			if (isset($match['validatorOptions'])) {
				$validatorOptions = $this->parseValidatorOptions($match['validatorOptions']);
			}
			$validatorConfiguration['validators'][] = array('validatorName' => $match['validatorName'], 'validatorOptions' => $validatorOptions);
		}
		return $validatorConfiguration;
	}

	/**
	 * Parses $rawValidatorOptions not containing quoted option values.
	 * $rawValidatorOptions will be an empty string afterwards (pass by ref!).
	 *
	 * @param string $rawValidatorOptions
	 * @return array An array of optionName/optionValue pairs
	 */
	protected function parseValidatorOptions($rawValidatorOptions) {
		$validatorOptions = array();
		$parsedValidatorOptions = array();
		preg_match_all(self::PATTERN_MATCH_VALIDATOROPTIONS, $rawValidatorOptions, $validatorOptions, PREG_SET_ORDER);
		foreach ($validatorOptions as $validatorOption) {
			$parsedValidatorOptions[trim($validatorOption['optionName'])] = trim($validatorOption['optionValue']);
		}
		array_walk($parsedValidatorOptions, array($this, 'unquoteString'));
		return $parsedValidatorOptions;
	}

	/**
	 * Removes escapings from a given argument string and trims the outermost
	 * quotes.
	 *
	 * This method is meant as a helper for regular expression results.
	 *
	 * @param string &$quotedValue Value to unquote
	 * @return void
	 */
	protected function unquoteString(&$quotedValue) {
		switch ($quotedValue[0]) {
			case '"':
				$quotedValue = str_replace('\\"', '"', trim($quotedValue, '"'));
				break;
			case '\'':
				$quotedValue = str_replace('\\\'', '\'', trim($quotedValue, '\''));
				break;
		}
		$quotedValue = str_replace('\\\\', '\\', $quotedValue);
	}

	/**
	 * Returns an object of an appropriate validator for the given class. If no validator is available
	 * FALSE is returned
	 *
	 * @param string $validatorName Either the fully qualified class name of the validator or the short name of a built-in validator
	 *
	 * @throws Exception\NoSuchValidatorException
	 * @return string Name of the validator object
	 */
	protected function resolveValidatorObjectName($validatorName) {
		if (strpos($validatorName, ':') !== FALSE || strpbrk($validatorName, '_\\') === FALSE) {
			/**
			 * Found shorthand validator, either extbase or foreign extension
			 * NotEmpty or Acme.MyPck.Ext:MyValidator
			 */
			list($extensionName, $extensionValidatorName) = explode(':', $validatorName);

			if ($validatorName !== $extensionName && strlen($extensionValidatorName) > 0) {
				/**
				 * Shorthand custom
				 */
				if (strpos($extensionName, '.') !== FALSE) {
					$extensionNameParts = explode('.', $extensionName);
					$extensionName = array_pop($extensionNameParts);
					$vendorName = implode('\\', $extensionNameParts);
					$possibleClassName = $vendorName . '\\' . $extensionName . '\\Validation\\Validator\\' . $extensionValidatorName;
				} else {
					$possibleClassName = 'Tx_' . $extensionName . '_Validation_Validator_' . $extensionValidatorName;
				}
			} else {
				/**
				 * Shorthand built in
				 */
				$possibleClassName = 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\' . $this->unifyDataType($validatorName);
			}
		} else {
			/**
			 * Full qualified
			 * Tx_MyExt_Validation_Validator_MyValidator or \Acme\Ext\Validation\Validator\FooValidator
			 */
			$possibleClassName = $validatorName;
		}

		if (substr($possibleClassName, - strlen('Validator')) !== 'Validator') {
			$possibleClassName .= 'Validator';
		}

		if (class_exists($possibleClassName)) {
			$possibleClassNameInterfaces = class_implements($possibleClassName);
			if (!in_array('TYPO3\\CMS\\Extbase\\Validation\\Validator\\ValidatorInterface', $possibleClassNameInterfaces)) {
				// The guessed validatorname is a valid class name, but does not implement the ValidatorInterface
				throw new NoSuchValidatorException('Validator class ' . $validatorName . ' must implement \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface', 1365776838);
			}
			$resolvedValidatorName = $possibleClassName;
		} else {
			throw new NoSuchValidatorException('Validator class ' . $validatorName . ' does not exist', 1365799920);
		}

		return $resolvedValidatorName;
	}

	/**
	 * Preprocess data types. Used to map primitive PHP types to DataTypes used in Extbase.
	 *
	 * @param string $type Data type to unify
	 * @return string unified data type
	 */
	protected function unifyDataType($type) {
		switch ($type) {
			case 'int':
				$type = 'Integer';
				break;
			case 'bool':
				$type = 'Boolean';
				break;
			case 'double':
				$type = 'Float';
				break;
			case 'numeric':
				$type = 'Number';
				break;
			case 'mixed':
				$type = 'Raw';
				break;
			default:
				$type = ucfirst($type);
				break;
		}
		return $type;
	}
}

?>