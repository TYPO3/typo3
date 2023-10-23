<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extbase\Validation;

use Symfony\Component\PropertyInfo\Type;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Extbase\Utility\TypeHandlingUtility;
use TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException;
use TYPO3\CMS\Extbase\Validation\Validator\CollectionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

/**
 * Validator resolver to automatically find an appropriate validator for a given subject.
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class ValidatorResolver implements SingletonInterface
{
    protected array $baseValidatorConjunctions = [];

    public function __construct(protected readonly ReflectionService $reflectionService) {}

    /**
     * Get a validator for a given data type. Returns a validator implementing
     * the ValidatorInterface or NULL if no validator could be resolved.
     *
     * @param string $validatorType Either one of the built-in data types or fully qualified validator class name
     * @param array $validatorOptions Options to be passed to the validator
     */
    public function createValidator(string $validatorType, array $validatorOptions = []): ?ValidatorInterface
    {
        try {
            $validatorObjectName = ValidatorClassNameResolver::resolve($validatorType);
            /** @var ValidatorInterface $validator */
            $validator = GeneralUtility::makeInstance($validatorObjectName);
            $validator->setOptions($validatorOptions);
            return $validator;
        } catch (NoSuchValidatorException $e) {
            GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__)->debug($e->getMessage());
            return null;
        }
    }

    /**
     * Resolves and returns the base validator conjunction for the given data type.
     * If no validator could be resolved (which usually means that no validation is necessary), NULL is returned.
     *
     * @param string $targetClassName The data type to search a validator for. Usually the fully qualified object name
     */
    public function getBaseValidatorConjunction(string $targetClassName): ConjunctionValidator
    {
        if (!isset($this->baseValidatorConjunctions[$targetClassName])) {
            $conjunctionValidator = GeneralUtility::makeInstance(ConjunctionValidator::class);
            $this->baseValidatorConjunctions[$targetClassName] = $conjunctionValidator;
            // The simpleType check reduces lookups to the class loader
            if (!TypeHandlingUtility::isSimpleType($targetClassName) && class_exists($targetClassName)) {
                $this->buildBaseValidatorConjunction($conjunctionValidator, $targetClassName);
            }
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
     * checked as well, regardless of a validation annotation.
     *
     * Additionally, if a custom validator was defined for the class in question, it will be added
     * to the end of the conjunction. A custom validator is found if it follows the naming convention
     * "Replace '\Model\' by '\Validator\' and append 'Validator'".
     *
     * Example: $targetClassName is TYPO3\Foo\Domain\Model\Quux, then the validator will be found if it has the
     * name TYPO3\Foo\Domain\Validator\QuuxValidator
     *
     * @param class-string $targetClassName The data type to build the validation conjunction for. Needs to be the fully qualified class name.
     * @throws NoSuchValidatorException
     * @throws \InvalidArgumentException
     */
    protected function buildBaseValidatorConjunction(ConjunctionValidator $conjunctionValidator, string $targetClassName): void
    {
        $classSchema = $this->reflectionService->getClassSchema($targetClassName);

        // Model based validator
        /** @var GenericObjectValidator $objectValidator */
        $objectValidator = $this->createValidator(GenericObjectValidator::class);
        foreach ($classSchema->getProperties() as $property) {
            $primaryType = $property->getPrimaryType();
            if (!$primaryType instanceof Type) {
                // @todo: The type is only necessary here for further analyzing whether it's a simple type or
                //        a collection. If this is evaluated in the ClassSchema, this whole code part is not needed
                //        any longer and can be removed.
                throw new \InvalidArgumentException(
                    sprintf('There is no @var annotation or type declaration for property "%s" in class "%s".', $property->getName(), $targetClassName),
                    1363778104
                );
            }

            $propertyTargetClassName = $primaryType->getClassName() ?? $primaryType->getBuiltinType();

            if (!TypeHandlingUtility::isSimpleType($propertyTargetClassName)) {
                // The outer simpleType check reduces lookups to the class loader
                // @todo: Whether the property holds a simple type or not and whether it holds a collection is known in
                //        in the ClassSchema. The information could be made available and not evaluated here again.
                if (TypeHandlingUtility::isCollectionType($propertyTargetClassName)) {
                    /** @var CollectionValidator $collectionValidator */
                    $collectionValidator = $this->createValidator(CollectionValidator::class, ['elementType' => $property->getElementType()]);
                    $objectValidator->addPropertyValidator($property->getName(), $collectionValidator);
                } elseif (class_exists($propertyTargetClassName)
                    && !TypeHandlingUtility::isCoreType($propertyTargetClassName)
                    && !in_array(SingletonInterface::class, class_implements($propertyTargetClassName) ?: [], true)
                ) {
                    // class_exists($propertyTargetClassName) checks, if the type of the property is an object
                    // instead of a simple type. Like DateTime or another model.
                    //
                    // !TypeHandlingUtility::isCoreType($propertyTargetClassName) checks if the type of the property
                    // is not a core type, which are Enums and File objects for example.
                    // @todo: check why these types shouldn't be validated.
                    //
                    // !in_array(SingletonInterface::class, class_implements($propertyTargetClassName, true), true)
                    // checks if the class is an instance of a Singleton
                    // @todo: check why Singletons shouldn't be validated.
                    //
                    // (Alexander Schnitzler) By looking at this code I assume that this is the path for 1:1
                    // relations in models. Still, the question remains why it excludes core types and singletons.
                    // It makes sense on a theoretical level, but I don't see a technical issue allowing these as
                    // well.
                    $validatorForProperty = $this->getBaseValidatorConjunction($propertyTargetClassName);
                    if ($validatorForProperty->count() > 0) {
                        $objectValidator->addPropertyValidator($property->getName(), $validatorForProperty);
                    }
                }
            }

            foreach ($property->getValidators() as $validatorDefinition) {
                // @todo: At this point we already have the class name of the validator, thus there is not need
                //        calling ValidatorClassNameResolver::resolve inside
                //        \TYPO3\CMS\Extbase\Validation\ValidatorResolver::createValidator once again. However, to
                //        keep things simple for now, we still use the method createValidator here. In the future,
                //        createValidator must only accept FQCN's.
                $newValidator = $this->createValidator($validatorDefinition['className'], $validatorDefinition['options']);
                if ($newValidator === null) {
                    throw new NoSuchValidatorException(
                        'Invalid validate annotation in ' . $targetClassName . '::' . $property->getName() . ': ' .
                        'Could not resolve class name for validator "' . $validatorDefinition['className'] . '".',
                        1241098027
                    );
                }
                $objectValidator->addPropertyValidator($property->getName(), $newValidator);
            }
        }

        if (!empty($objectValidator->getPropertyValidators())) {
            $conjunctionValidator->addValidator($objectValidator);
        }
    }
}
