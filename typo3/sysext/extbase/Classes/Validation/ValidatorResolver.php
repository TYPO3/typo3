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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;

/**
 * Validator resolver to automatically find a appropriate validator for a given subject
 */
class ValidatorResolver implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Match validator names and options
	 * @todo: adjust [a-z0-9_:.\\\\] once Tx_Extbase_Foo syntax is outdated.
	 *
	 * @var string
	 */
	const PATTERN_MATCH_VALIDATORS = '/
			(?:^|,\s*)
			(?P<validatorName>[a-z0-9_:.\\\\]+)
			\s*
			(?:\(
				(?P<validatorOptions>(?:\s*[a-z0-9]+\s*=\s*(?:
					"(?:\\\\"|[^"])*"
					|\'(?:\\\\\'|[^\'])*\'
					|(?:\s|[^,"\']*)
				)(?:\s|,)*)*)
			\))?
		/ixS';

	/**
	 * Match validator options (to parse actual options)
	 * @var string
	 */
	const PATTERN_MATCH_VALIDATOROPTIONS = '/
			\s*
			(?P<optionName>[a-z0-9]+)
			\s*=\s*
			(?P<optionValue>
				"(?:\\\\"|[^"])*"
				|\'(?:\\\\\'|[^\'])*\'
				|(?:\s|[^,"\']*)
			)
		/ixS';

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
	 * @inject
	 */
	protected $reflectionService;

	/**
	 * @var array
	 */
	protected $baseValidatorConjunctions = array();

	/**
	 * Get a validator for a given data type. Returns a validator implementing
	 * the \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface or NULL if no validator
	 * could be resolved.
	 *
	 * @param string $validatorName Either one of the built-in data types or fully qualified validator class name
	 * @param array $validatorOptions Options to be passed to the validator
	 * @return \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface Validator or NULL if none found.
	 */
	public function createValidator($validatorType, array $validatorOptions = array()) {
		try {
			/**
			 * todo: remove throwing Exceptions in resolveValidatorObjectName
			 */
			$validatorObjectName = $this->resolveValidatorObjectName($validatorType);

			$validator = $this->objectManager->get($validatorObjectName, $validatorOptions);

			if (!($validator instanceof \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface)) {
				throw new Exception\NoSuchValidatorException('The validator "' . $validatorObjectName . '" does not implement TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface!', 1300694875);
			}

			return $validator;
		} catch (NoSuchValidatorException $e) {
			GeneralUtility::devLog($e->getMessage(), 'extbase', GeneralUtility::SYSLOG_SEVERITY_INFO);
			return NULL;
		}
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
	public function getBaseValidatorConjunction($targetClassName) {
		if (!array_key_exists($targetClassName, $this->baseValidatorConjunctions)) {
			$this->buildBaseValidatorConjunction($targetClassName, $targetClassName);
		}

		return $this->baseValidatorConjunctions[$targetClassName];
	}

	/**
	 * Detects and registers any validators for arguments:
	 * - by the data type specified in the param annotations
	 * - additional validators specified in the validate annotations of a method
	 *
	 * @param string $className
	 * @param string $methodName
	 * @param array $methodParameters Optional pre-compiled array of method parameters
	 * @param array $methodValidateAnnotations Optional pre-compiled array of validate annotations (as array)
	 * @return array An Array of ValidatorConjunctions for each method parameters.
	 * @throws \TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationConfigurationException
	 * @throws \TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException
	 * @throws \TYPO3\CMS\Extbase\Validation\Exception\InvalidTypeHintException
	 */
	public function buildMethodArgumentsValidatorConjunctions($className, $methodName, array $methodParameters = NULL, array $methodValidateAnnotations = NULL) {
		$validatorConjunctions = array();

		if ($methodParameters === NULL) {
			$methodParameters = $this->reflectionService->getMethodParameters($className, $methodName);
		}
		if (count($methodParameters) === 0) {
			return $validatorConjunctions;
		}

		foreach ($methodParameters as $parameterName => $methodParameter) {
			$validatorConjunction = $this->createValidator('TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator');

			if (!array_key_exists('type', $methodParameter)) {
				throw new Exception\InvalidTypeHintException('Missing type information, probably no @param annotation for parameter "$' . $parameterName . '" in ' . $className . '->' . $methodName . '()', 1281962564);
			}

			// @todo: remove check for old underscore model name syntax once it's possible
			if (strpbrk($methodParameter['type'], '_\\') === FALSE) {
				$typeValidator = $this->createValidator($methodParameter['type']);
			} elseif (preg_match('/[\\_]Model[\\_]/', $methodParameter['type']) !== FALSE) {
				$possibleValidatorClassName = str_replace(array('\\Model\\', '_Model_'), array('\\Validator\\', '_Validator_'), $methodParameter['type']) . 'Validator';
				$typeValidator = $this->createValidator($possibleValidatorClassName);
			} else {
				$typeValidator = NULL;
			}

			if ($typeValidator !== NULL) {
				$validatorConjunction->addValidator($typeValidator);
			}
			$validatorConjunctions[$parameterName] = $validatorConjunction;
		}

		if ($methodValidateAnnotations === NULL) {
			$validateAnnotations = $this->getMethodValidateAnnotations($className, $methodName);
			$methodValidateAnnotations = array_map(function($validateAnnotation) {
				return array(
					'type' => $validateAnnotation['validatorName'],
					'options' => $validateAnnotation['validatorOptions'],
					'argumentName' => $validateAnnotation['argumentName'],
				);
			}, $validateAnnotations);
		}

		foreach ($methodValidateAnnotations as $annotationParameters) {
			$newValidator = $this->createValidator($annotationParameters['type'], $annotationParameters['options']);
			if ($newValidator === NULL) {
				throw new Exception\NoSuchValidatorException('Invalid validate annotation in ' . $className . '->' . $methodName . '(): Could not resolve class name for  validator "' . $annotationParameters['type'] . '".', 1239853109);
			}
			if (isset($validatorConjunctions[$annotationParameters['argumentName']])) {
				$validatorConjunctions[$annotationParameters['argumentName']]->addValidator($newValidator);
			} elseif (strpos($annotationParameters['argumentName'], '.') !== FALSE) {
				$objectPath = explode('.', $annotationParameters['argumentName']);
				$argumentName = array_shift($objectPath);
				$validatorConjunctions[$argumentName]->addValidator($this->buildSubObjectValidator($objectPath, $newValidator));
			} else {
				throw new Exception\InvalidValidationConfigurationException('Invalid validate annotation in ' . $className . '->' . $methodName . '(): Validator specified for argument name "' . $annotationParameters['argumentName'] . '", but this argument does not exist.', 1253172726);
			}
		}

		return $validatorConjunctions;
	}

	/**
	 * Builds a chain of nested object validators by specification of the given
	 * object path.
	 *
	 * @param array $objectPath The object path
	 * @param \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface $propertyValidator The validator which should be added to the property specified by objectPath
	 * @return \TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator
	 */
	protected function buildSubObjectValidator(array $objectPath, \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface $propertyValidator) {
		$rootObjectValidator = $this->objectManager->get('TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator', array());
		$parentObjectValidator = $rootObjectValidator;

		while (count($objectPath) > 1) {
			$subObjectValidator = $this->objectManager->get('TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator', array());
			$subPropertyName = array_shift($objectPath);
			$parentObjectValidator->addPropertyValidator($subPropertyName, $subObjectValidator);
			$parentObjectValidator = $subObjectValidator;
		}

		$parentObjectValidator->addPropertyValidator(array_shift($objectPath), $propertyValidator);

		return $rootObjectValidator;
	}

	/**
	 * Builds a base validator conjunction for the given data type.
	 *
	 * The base validation rules are those which were declared directly in a class (typically
	 * a model) through some validate annotations on properties.
	 *
	 * If a property holds a class for which a base validator exists, that property will be
	 * checked as well, regardless of a validate annotation
	 *
	 * Additionally, if a custom validator was defined for the class in question, it will be added
	 * to the end of the conjunction. A custom validator is found if it follows the naming convention
	 * "Replace '\Model\' by '\Validator\' and append 'Validator'".
	 *
	 * Example: $targetClassName is TYPO3\Foo\Domain\Model\Quux, then the validator will be found if it has the
	 * name TYPO3\Foo\Domain\Validator\QuuxValidator
	 *
	 * @param string $indexKey The key to use as index in $this->baseValidatorConjunctions; calculated from target class name and validation groups
	 * @param string $targetClassName The data type to build the validation conjunction for. Needs to be the fully qualified class name.
	 * @param array $validationGroups The validation groups to build the validator for
	 * @return void
	 * @throws \TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException
	 * @throws \InvalidArgumentException
	 */
	protected function buildBaseValidatorConjunction($indexKey, $targetClassName, array $validationGroups = array()) {
		$conjunctionValidator = new \TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator();
		$this->baseValidatorConjunctions[$indexKey] = $conjunctionValidator;
		if (class_exists($targetClassName)) {
				// Model based validator
			/** @var \TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator $objectValidator */
			$objectValidator = $this->objectManager->get('TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator', array());
			foreach ($this->reflectionService->getClassPropertyNames($targetClassName) as $classPropertyName) {
				$classPropertyTagsValues = $this->reflectionService->getPropertyTagsValues($targetClassName, $classPropertyName);

				if (!isset($classPropertyTagsValues['var'])) {
					throw new \InvalidArgumentException(sprintf('There is no @var annotation for property "%s" in class "%s".', $classPropertyName, $targetClassName), 1363778104);
				}
				try {
					$parsedType = \TYPO3\CMS\Extbase\Utility\TypeHandlingUtility::parseType(trim(implode('', $classPropertyTagsValues['var']), ' \\'));
				} catch (\TYPO3\CMS\Extbase\Utility\Exception\InvalidTypeException $exception) {
					throw new \InvalidArgumentException(sprintf(' @var annotation of ' . $exception->getMessage(), 'class "' . $targetClassName . '", property "' . $classPropertyName . '"'), 1315564744, $exception);
				}
				$propertyTargetClassName = $parsedType['type'];
				if (\TYPO3\CMS\Extbase\Utility\TypeHandlingUtility::isCollectionType($propertyTargetClassName) === TRUE) {
					$collectionValidator = $this->createValidator('TYPO3\CMS\Extbase\Validation\Validator\CollectionValidator', array('elementType' => $parsedType['elementType'], 'validationGroups' => $validationGroups));
					$objectValidator->addPropertyValidator($classPropertyName, $collectionValidator);
				} elseif (class_exists($propertyTargetClassName) && !\TYPO3\CMS\Extbase\Utility\TypeHandlingUtility::isCoreType($propertyTargetClassName) && $this->objectManager->isRegistered($propertyTargetClassName) && $this->objectManager->getScope($propertyTargetClassName) === \TYPO3\CMS\Extbase\Object\Container\Container::SCOPE_PROTOTYPE) {
					$validatorForProperty = $this->getBaseValidatorConjunction($propertyTargetClassName, $validationGroups);
					if (count($validatorForProperty) > 0) {
						$objectValidator->addPropertyValidator($classPropertyName, $validatorForProperty);
					}
				}

				$validateAnnotations = array();
				// @todo: Resolve annotations via reflectionService once its available
				if (isset($classPropertyTagsValues['validate']) && is_array($classPropertyTagsValues['validate'])) {
					foreach ($classPropertyTagsValues['validate'] as $validateValue) {
						$parsedAnnotations = $this->parseValidatorAnnotation($validateValue);

						foreach ($parsedAnnotations['validators'] as $validator) {
							array_push($validateAnnotations, array(
								'argumentName' => $parsedAnnotations['argumentName'],
								'validatorName' => $validator['validatorName'],
								'validatorOptions' => $validator['validatorOptions']
							));
						}
					}
				}

				foreach ($validateAnnotations as $validateAnnotation) {
					// @todo: Respect validationGroups
					$newValidator = $this->createValidator($validateAnnotation['validatorName'], $validateAnnotation['validatorOptions']);
					if ($newValidator === NULL) {
						throw new Exception\NoSuchValidatorException('Invalid validate annotation in ' . $targetClassName . '::' . $classPropertyName . ': Could not resolve class name for  validator "' . $validateAnnotation->type . '".', 1241098027);
					}
					$objectValidator->addPropertyValidator($classPropertyName, $newValidator);
				}
			}

			if (count($objectValidator->getPropertyValidators()) > 0) {
				$conjunctionValidator->addValidator($objectValidator);
			}
		}

		$this->addCustomValidators($targetClassName, $conjunctionValidator);
	}

	/**
	 * This adds custom validators to the passed $conjunctionValidator.
	 *
	 * A custom validator is found if it follows the naming convention "Replace '\Model\' by '\Validator\' and
	 * append 'Validator'". If found, it will be added to the $conjunctionValidator.
	 *
	 * In addition canValidate() will be called on all implementations of the ObjectValidatorInterface to find
	 * all validators that could validate the target. The one with the highest priority will be added as well.
	 * If multiple validators have the same priority, which one will be added is not deterministic.
	 *
	 * @param string $targetClassName
	 * @param ConjunctionValidator $conjunctionValidator
	 * @return NULL|Validator\ObjectValidatorInterface
	 */
	protected function addCustomValidators($targetClassName, ConjunctionValidator &$conjunctionValidator) {

		$addedValidatorClassName = NULL;
		// @todo: get rid of ClassNamingUtility usage once we dropped underscored class name support
		$possibleValidatorClassName = ClassNamingUtility::translateModelNameToValidatorName($targetClassName);

		$customValidator = $this->createValidator($possibleValidatorClassName);
		if ($customValidator !== NULL) {
			$conjunctionValidator->addValidator($customValidator);
			$addedValidatorClassName = get_class($customValidator);
		}

		// @todo: find polytype validator for class
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
				$possibleClassName = 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\' . $this->getValidatorType($validatorName);
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
	 * Used to map PHP types to validator types.
	 *
	 * @param string $type Data type to unify
	 * @return string unified data type
	 */
	protected function getValidatorType($type) {
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
		}
		return $type;
	}

	/**
	 * Temporary replacement for $this->reflectionService->getMethodAnnotations()
	 *
	 * @param string $className
	 * @param string $methodName
	 *
	 * @return array
	 */
	public function getMethodValidateAnnotations($className, $methodName) {
		$validateAnnotations = array();
		$methodTagsValues = $this->reflectionService->getMethodTagsValues($className, $methodName);
		if (isset($methodTagsValues['validate']) && is_array($methodTagsValues['validate'])) {
			foreach ($methodTagsValues['validate'] as $validateValue) {
				$parsedAnnotations = $this->parseValidatorAnnotation($validateValue);

				foreach ($parsedAnnotations['validators'] as $validator) {
					array_push($validateAnnotations, array(
						'argumentName' => $parsedAnnotations['argumentName'],
						'validatorName' => $validator['validatorName'],
						'validatorOptions' => $validator['validatorOptions']
					));
				}
			}
		}

		return $validateAnnotations;
	}
}
