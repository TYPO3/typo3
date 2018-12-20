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

use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\ClassNamingUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\TypeHandlingUtility;
use TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;

/**
 * Validator resolver to automatically find an appropriate validator for a given subject
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class ValidatorResolver implements \TYPO3\CMS\Core\SingletonInterface
{
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

            // Move this check into ClassSchema
            if (!($validator instanceof \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface)) {
                throw new Exception\NoSuchValidatorException('The validator "' . $validatorObjectName . '" does not implement TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface!', 1300694875);
            }

            return $validator;
        } catch (NoSuchValidatorException $e) {
            GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__)->debug($e->getMessage());
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
     * @throws \TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException
     * @throws \InvalidArgumentException
     */
    protected function buildBaseValidatorConjunction($indexKey, $targetClassName, array $validationGroups = [])
    {
        $conjunctionValidator = new ConjunctionValidator();
        $this->baseValidatorConjunctions[$indexKey] = $conjunctionValidator;

        // note: the simpleType check reduces lookups to the class loader
        if (!TypeHandlingUtility::isSimpleType($targetClassName) && class_exists($targetClassName)) {
            $classSchema = $this->reflectionService->getClassSchema($targetClassName);

            // Model based validator
            /** @var \TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator $objectValidator */
            $objectValidator = $this->objectManager->get(\TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator::class, []);
            foreach ($classSchema->getProperties() as $classPropertyName => $classPropertyDefinition) {
                /** @var array|array[] $classPropertyDefinition */
                $classPropertyTagsValues = $classPropertyDefinition['tags'];

                if (!isset($classPropertyTagsValues['var'])) {
                    throw new \InvalidArgumentException(sprintf('There is no @var annotation for property "%s" in class "%s".', $classPropertyName, $targetClassName), 1363778104);
                }

                $propertyTargetClassName = $classPropertyDefinition['type'];
                // note: the outer simpleType check reduces lookups to the class loader
                if (!TypeHandlingUtility::isSimpleType($propertyTargetClassName)) {
                    if (TypeHandlingUtility::isCollectionType($propertyTargetClassName)) {
                        $collectionValidator = $this->createValidator(
                            \TYPO3\CMS\Extbase\Validation\Validator\CollectionValidator::class,
                            [
                                'elementType' => $classPropertyDefinition['elementType'],
                                'validationGroups' => $validationGroups
                            ]
                        );
                        $objectValidator->addPropertyValidator($classPropertyName, $collectionValidator);
                    } elseif (class_exists($propertyTargetClassName) && !TypeHandlingUtility::isCoreType($propertyTargetClassName) && $this->objectManager->isRegistered($propertyTargetClassName) && $this->objectManager->getScope($propertyTargetClassName) === \TYPO3\CMS\Extbase\Object\Container\Container::SCOPE_PROTOTYPE) {
                        $validatorForProperty = $this->getBaseValidatorConjunction($propertyTargetClassName);
                        if ($validatorForProperty !== null && $validatorForProperty->count() > 0) {
                            $objectValidator->addPropertyValidator($classPropertyName, $validatorForProperty);
                        }
                    }
                }

                foreach ($classPropertyDefinition['validators'] as $validatorDefinition) {
                    // @todo: Respect validationGroups

                    // @todo: At this point we already have the class name of the validator, thus there is not need
                    // @todo: calling \TYPO3\CMS\Extbase\Validation\ValidatorResolver::resolveValidatorObjectName inside
                    // @todo: \TYPO3\CMS\Extbase\Validation\ValidatorResolver::createValidator once again. However, to
                    // @todo: keep things simple for now, we still use the method createValidator here. In the future,
                    // @todo: createValidator must only accept FQCN's.
                    $newValidator = $this->createValidator($validatorDefinition['className'], $validatorDefinition['options']);
                    if ($newValidator === null) {
                        throw new Exception\NoSuchValidatorException('Invalid validate annotation in ' . $targetClassName . '::' . $classPropertyName . ': Could not resolve class name for validator "' . $validatorDefinition['className'] . '".', 1241098027);
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
     */
    protected function addCustomValidators($targetClassName, ConjunctionValidator &$conjunctionValidator)
    {
        // @todo: get rid of ClassNamingUtility usage once we dropped underscored class name support
        $possibleValidatorClassName = ClassNamingUtility::translateModelNameToValidatorName($targetClassName);

        $customValidator = $this->createValidator($possibleValidatorClassName);
        if ($customValidator !== null) {
            $conjunctionValidator->addValidator($customValidator);
        }

        // @todo: find polytype validator for class
    }

    /**
     * Returns an object of an appropriate validator for the given class. If no validator is available
     * FALSE is returned
     *
     * @param string $validatorName Either the fully qualified class name of the validator or the short name of a built-in validator
     *
     * @throws Exception\NoSuchValidatorException
     * @return string Name of the validator object
     * @internal
     */
    public function resolveValidatorObjectName($validatorName)
    {
        if (strpos($validatorName, ':') !== false) {
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
                }
            } else {
                // Shorthand built in
                $possibleClassName = 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\' . $this->getValidatorType($validatorName);
            }
        } elseif (strpbrk($validatorName, '\\') === false) {
            // Shorthand built in
            $possibleClassName = 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\' . $this->getValidatorType($validatorName);
        } else {
            // Full qualified
            // Example: \Acme\Ext\Validation\Validator\FooValidator
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
}
