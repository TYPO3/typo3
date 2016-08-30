<?php
namespace TYPO3\CMS\Extbase\Validation;

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

use TYPO3\CMS\Core\Utility\ClassNamingUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\TypeHandlingUtility;
use TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;

/**
 * Validator resolver to automatically find an appropriate validator for a given subject
 */
class ValidatorResolver implements \TYPO3\CMS\Core\SingletonInterface
{
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
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
     */
    protected $reflectionService;

    /**
     * @var array
     */
    protected $baseValidatorConjunctions = [];

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
     */
    public function injectReflectionService(\TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * Get a validator for a given data type. Returns a validator implementing
     * the \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface or NULL if no validator
     * could be resolved.
     *
     * @param string $validatorType Either one of the built-in data types or fully qualified validator class name
     * @param array $validatorOptions Options to be passed to the validator
     * @return \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface Validator or NULL if none found.
     */
    public function createValidator($validatorType, array $validatorOptions = [])
    {
        try {
            /**
             * @todo remove throwing Exceptions in resolveValidatorObjectName
             */
            $validatorObjectName = $this->resolveValidatorObjectName($validatorType);

            $validator = $this->objectManager->get($validatorObjectName, $validatorOptions);

            if (!($validator instanceof \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface)) {
                throw new Exception\NoSuchValidatorException('The validator "' . $validatorObjectName . '" does not implement TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface!', 1300694875);
            }

            return $validator;
        } catch (NoSuchValidatorException $e) {
            GeneralUtility::devLog($e->getMessage(), 'extbase', GeneralUtility::SYSLOG_SEVERITY_INFO);
            return null;
        }
    }

    /**
     * Resolves and returns the base validator conjunction for the given data type.
     *
     * If no validator could be resolved (which usually means that no validation is necessary),
     * NULL is returned.
     *
     * @param string $targetClassName The data type to search a validator for. Usually the fully qualified object name
     * @return ConjunctionValidator The validator conjunction or NULL
     */
    public function getBaseValidatorConjunction($targetClassName)
    {
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
     * @return ConjunctionValidator[] An Array of ValidatorConjunctions for each method parameters.
     * @throws \TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationConfigurationException
     * @throws \TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException
     * @throws \TYPO3\CMS\Extbase\Validation\Exception\InvalidTypeHintException
     */
    public function buildMethodArgumentsValidatorConjunctions($className, $methodName, array $methodParameters = null, array $methodValidateAnnotations = null)
    {
        /** @var ConjunctionValidator[] $validatorConjunctions */
        $validatorConjunctions = [];

        if ($methodParameters === null) {
            $methodParameters = $this->reflectionService->getMethodParameters($className, $methodName);
        }
        if (empty($methodParameters)) {
            return $validatorConjunctions;
        }

        foreach ($methodParameters as $parameterName => $methodParameter) {
            /** @var ConjunctionValidator $validatorConjunction */
            $validatorConjunction = $this->createValidator(ConjunctionValidator::class);

            if (!array_key_exists('type', $methodParameter)) {
                throw new Exception\InvalidTypeHintException('Missing type information, probably no @param annotation for parameter "$' . $parameterName . '" in ' . $className . '->' . $methodName . '()', 1281962564);
            }

            // @todo: remove check for old underscore model name syntax once it's possible
            if (strpbrk($methodParameter['type'], '_\\') === false) {
                $typeValidator = $this->createValidator($methodParameter['type']);
            } else {
                $typeValidator = null;
            }

            if ($typeValidator !== null) {
                $validatorConjunction->addValidator($typeValidator);
            }
            $validatorConjunctions[$parameterName] = $validatorConjunction;
        }

        if ($methodValidateAnnotations === null) {
            $validateAnnotations = $this->getMethodValidateAnnotations($className, $methodName);
            $methodValidateAnnotations = array_map(function ($validateAnnotation) {
                return [
                    'type' => $validateAnnotation['validatorName'],
                    'options' => $validateAnnotation['validatorOptions'],
                    'argumentName' => $validateAnnotation['argumentName'],
                ];
            }, $validateAnnotations);
        }

        foreach ($methodValidateAnnotations as $annotationParameters) {
            $newValidator = $this->createValidator($annotationParameters['type'], $annotationParameters['options']);
            if ($newValidator === null) {
                throw new Exception\NoSuchValidatorException('Invalid validate annotation in ' . $className . '->' . $methodName . '(): Could not resolve class name for  validator "' . $annotationParameters['type'] . '".', 1239853109);
            }
            if (isset($validatorConjunctions[$annotationParameters['argumentName']])) {
                $validatorConjunctions[$annotationParameters['argumentName']]->addValidator($newValidator);
            } elseif (strpos($annotationParameters['argumentName'], '.') !== false) {
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
    protected function buildSubObjectValidator(array $objectPath, \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface $propertyValidator)
    {
        $rootObjectValidator = $this->objectManager->get(\TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator::class, []);
        $parentObjectValidator = $rootObjectValidator;

        while (count($objectPath) > 1) {
            $subObjectValidator = $this->objectManager->get(\TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator::class, []);
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
    protected function buildBaseValidatorConjunction($indexKey, $targetClassName, array $validationGroups = [])
    {
        $conjunctionValidator = new ConjunctionValidator();
        $this->baseValidatorConjunctions[$indexKey] = $conjunctionValidator;

        // note: the simpleType check reduces lookups to the class loader
        if (!TypeHandlingUtility::isSimpleType($targetClassName) && class_exists($targetClassName)) {
            // Model based validator
            /** @var \TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator $objectValidator */
            $objectValidator = $this->objectManager->get(\TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator::class, []);
            foreach ($this->reflectionService->getClassPropertyNames($targetClassName) as $classPropertyName) {
                $classPropertyTagsValues = $this->reflectionService->getPropertyTagsValues($targetClassName, $classPropertyName);

                if (!isset($classPropertyTagsValues['var'])) {
                    throw new \InvalidArgumentException(sprintf('There is no @var annotation for property "%s" in class "%s".', $classPropertyName, $targetClassName), 1363778104);
                }
                try {
                    $parsedType = TypeHandlingUtility::parseType(trim(implode('', $classPropertyTagsValues['var']), ' \\'));
                } catch (\TYPO3\CMS\Extbase\Utility\Exception\InvalidTypeException $exception) {
                    throw new \InvalidArgumentException(sprintf(' @var annotation of ' . $exception->getMessage(), 'class "' . $targetClassName . '", property "' . $classPropertyName . '"'), 1315564744, $exception);
                }
                $propertyTargetClassName = $parsedType['type'];
                // note: the outer simpleType check reduces lookups to the class loader
                if (!TypeHandlingUtility::isSimpleType($propertyTargetClassName)) {
                    if (TypeHandlingUtility::isCollectionType($propertyTargetClassName)) {
                        $collectionValidator = $this->createValidator(\TYPO3\CMS\Extbase\Validation\Validator\CollectionValidator::class, ['elementType' => $parsedType['elementType'], 'validationGroups' => $validationGroups]);
                        $objectValidator->addPropertyValidator($classPropertyName, $collectionValidator);
                    } elseif (class_exists($propertyTargetClassName) && !TypeHandlingUtility::isCoreType($propertyTargetClassName) && $this->objectManager->isRegistered($propertyTargetClassName) && $this->objectManager->getScope($propertyTargetClassName) === \TYPO3\CMS\Extbase\Object\Container\Container::SCOPE_PROTOTYPE) {
                        $validatorForProperty = $this->getBaseValidatorConjunction($propertyTargetClassName);
                        if ($validatorForProperty !== null && $validatorForProperty->count() > 0) {
                            $objectValidator->addPropertyValidator($classPropertyName, $validatorForProperty);
                        }
                    }
                }

                $validateAnnotations = [];
                // @todo: Resolve annotations via reflectionService once its available
                if (isset($classPropertyTagsValues['validate']) && is_array($classPropertyTagsValues['validate'])) {
                    foreach ($classPropertyTagsValues['validate'] as $validateValue) {
                        $parsedAnnotations = $this->parseValidatorAnnotation($validateValue);

                        foreach ($parsedAnnotations['validators'] as $validator) {
                            array_push($validateAnnotations, [
                                'argumentName' => $parsedAnnotations['argumentName'],
                                'validatorName' => $validator['validatorName'],
                                'validatorOptions' => $validator['validatorOptions']
                            ]);
                        }
                    }
                }

                foreach ($validateAnnotations as $validateAnnotation) {
                    // @todo: Respect validationGroups
                    $newValidator = $this->createValidator($validateAnnotation['validatorName'], $validateAnnotation['validatorOptions']);
                    if ($newValidator === null) {
                        throw new Exception\NoSuchValidatorException('Invalid validate annotation in ' . $targetClassName . '::' . $classPropertyName . ': Could not resolve class name for  validator "' . $validateAnnotation->type . '".', 1241098027);
                    }
                    $objectValidator->addPropertyValidator($classPropertyName, $newValidator);
                }
            }

            if (!empty($objectValidator->getPropertyValidators())) {
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
    protected function addCustomValidators($targetClassName, ConjunctionValidator &$conjunctionValidator)
    {
        $addedValidatorClassName = null;
        // @todo: get rid of ClassNamingUtility usage once we dropped underscored class name support
        $possibleValidatorClassName = ClassNamingUtility::translateModelNameToValidatorName($targetClassName);

        $customValidator = $this->createValidator($possibleValidatorClassName);
        if ($customValidator !== null) {
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
    protected function parseValidatorAnnotation($validateValue)
    {
        $matches = [];
        if ($validateValue[0] === '$') {
            $parts = explode(' ', $validateValue, 2);
            $validatorConfiguration = ['argumentName' => ltrim($parts[0], '$'), 'validators' => []];
            preg_match_all(self::PATTERN_MATCH_VALIDATORS, $parts[1], $matches, PREG_SET_ORDER);
        } else {
            $validatorConfiguration = ['validators' => []];
            preg_match_all(self::PATTERN_MATCH_VALIDATORS, $validateValue, $matches, PREG_SET_ORDER);
        }
        foreach ($matches as $match) {
            $validatorOptions = [];
            if (isset($match['validatorOptions'])) {
                $validatorOptions = $this->parseValidatorOptions($match['validatorOptions']);
            }
            $validatorConfiguration['validators'][] = ['validatorName' => $match['validatorName'], 'validatorOptions' => $validatorOptions];
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
    protected function parseValidatorOptions($rawValidatorOptions)
    {
        $validatorOptions = [];
        $parsedValidatorOptions = [];
        preg_match_all(self::PATTERN_MATCH_VALIDATOROPTIONS, $rawValidatorOptions, $validatorOptions, PREG_SET_ORDER);
        foreach ($validatorOptions as $validatorOption) {
            $parsedValidatorOptions[trim($validatorOption['optionName'])] = trim($validatorOption['optionValue']);
        }
        array_walk($parsedValidatorOptions, [$this, 'unquoteString']);
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
    protected function unquoteString(&$quotedValue)
    {
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
    protected function resolveValidatorObjectName($validatorName)
    {
        if (strpos($validatorName, ':') !== false || strpbrk($validatorName, '_\\') === false) {
            // Found shorthand validator, either extbase or foreign extension
            // NotEmpty or Acme.MyPck.Ext:MyValidator
            list($extensionName, $extensionValidatorName) = explode(':', $validatorName);

            if ($validatorName !== $extensionName && $extensionValidatorName !== '') {
                // Shorthand custom
                if (strpos($extensionName, '.') !== false) {
                    $extensionNameParts = explode('.', $extensionName);
                    $extensionName = array_pop($extensionNameParts);
                    $vendorName = implode('\\', $extensionNameParts);
                    $possibleClassName = $vendorName . '\\' . $extensionName . '\\Validation\\Validator\\' . $extensionValidatorName;
                } else {
                    $possibleClassName = 'Tx_' . $extensionName . '_Validation_Validator_' . $extensionValidatorName;
                }
            } else {
                // Shorthand built in
                $possibleClassName = 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\' . $this->getValidatorType($validatorName);
            }
        } else {
            // Full qualified
             // Tx_MyExt_Validation_Validator_MyValidator or \Acme\Ext\Validation\Validator\FooValidator
            $possibleClassName = $validatorName;
            if (!empty($possibleClassName) && $possibleClassName[0] === '\\') {
                $possibleClassName = substr($possibleClassName, 1);
            }
        }

        if (substr($possibleClassName, - strlen('Validator')) !== 'Validator') {
            $possibleClassName .= 'Validator';
        }

        if (class_exists($possibleClassName)) {
            $possibleClassNameInterfaces = class_implements($possibleClassName);
            if (!in_array(\TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface::class, $possibleClassNameInterfaces)) {
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
    protected function getValidatorType($type)
    {
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
    public function getMethodValidateAnnotations($className, $methodName)
    {
        $validateAnnotations = [];
        $methodTagsValues = $this->reflectionService->getMethodTagsValues($className, $methodName);
        if (isset($methodTagsValues['validate']) && is_array($methodTagsValues['validate'])) {
            foreach ($methodTagsValues['validate'] as $validateValue) {
                $parsedAnnotations = $this->parseValidatorAnnotation($validateValue);

                foreach ($parsedAnnotations['validators'] as $validator) {
                    array_push($validateAnnotations, [
                        'argumentName' => $parsedAnnotations['argumentName'],
                        'validatorName' => $validator['validatorName'],
                        'validatorOptions' => $validator['validatorOptions']
                    ]);
                }
            }
        }

        return $validateAnnotations;
    }
}
