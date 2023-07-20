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

namespace TYPO3\CMS\Extbase\Validation\Validator;

use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * A generic object validator which allows for specifying property validators.
 */
abstract class AbstractGenericObjectValidator extends AbstractValidator implements ObjectValidatorInterface
{
    protected array $propertyValidators = [];

    /**
     * @var \SplObjectStorage
     */
    protected $validatedInstancesContainer;

    /**
     * Checks if the given value is valid according to the validator, and returns
     * the Error Messages object which occurred.
     *
     * @param mixed $value The value that should be validated
     */
    public function validate(mixed $value): Result
    {
        if (is_object($value) && $this->isValidatedAlready($value)) {
            return $this->result;
        }

        $this->result = new Result();
        if ($this->acceptsEmptyValues === false || $this->isEmpty($value) === false) {
            if (!is_object($value)) {
                $this->addError('Object expected, %1$s given.', 1241099149, [gettype($value)]);
            } elseif ($this->isValidatedAlready($value) === false) {
                $this->markInstanceAsValidated($value);
                $this->isValid($value);
            }
        }

        return $this->result;
    }

    /**
     * Load the property value to be used for validation.
     * In case the object is a doctrine proxy, we need to load the real instance first.
     */
    protected function getPropertyValue(object $object, string $propertyName): mixed
    {
        if (ObjectAccess::isPropertyGettable($object, $propertyName)) {
            return ObjectAccess::getProperty($object, $propertyName);
        }
        throw new \RuntimeException(
            sprintf(
                'Could not get value of property "%s::%s", make sure the property is either public or has a getter get%3$s(), a hasser has%3$s() or an isser is%3$s().',
                get_class($object),
                $propertyName,
                ucfirst($propertyName)
            ),
            1546632293
        );
    }

    /**
     * Checks if the specified property of the given object is valid, and adds
     * found errors to the $messages object.
     */
    protected function checkProperty(mixed $value, \Traversable $validators, string $propertyName): void
    {
        /** @var Result|null $result */
        $result = null;
        foreach ($validators as $validator) {
            if ($validator instanceof ObjectValidatorInterface) {
                $validator->setValidatedInstancesContainer($this->validatedInstancesContainer);
            }
            $currentResult = $validator->validate($value);
            if ($currentResult->hasMessages()) {
                if ($result == null) {
                    $result = $currentResult;
                } else {
                    $result->merge($currentResult);
                }
            }
        }
        if ($result != null) {
            $this->result->forProperty($propertyName)->merge($result);
        }
    }

    /**
     * Checks if the given value is valid according to the property validators.
     */
    protected function isValid(mixed $object): void
    {
        foreach ($this->propertyValidators as $propertyName => $validators) {
            $propertyValue = $this->getPropertyValue($object, $propertyName);
            $this->checkProperty($propertyValue, $validators, $propertyName);
        }
    }

    /**
     * Checks the given object can be validated by the validator implementation
     */
    public function canValidate(mixed $object): bool
    {
        return is_object($object);
    }

    /**
     * Adds the given validator for validation of the specified property.
     */
    public function addPropertyValidator(string $propertyName, ValidatorInterface $validator): void
    {
        if (!isset($this->propertyValidators[$propertyName])) {
            $this->propertyValidators[$propertyName] = new \SplObjectStorage();
        }
        $this->propertyValidators[$propertyName]->attach($validator);
    }

    protected function isValidatedAlready(object $object): bool
    {
        if ($this->validatedInstancesContainer === null) {
            $this->validatedInstancesContainer = new \SplObjectStorage();
        }
        if ($this->validatedInstancesContainer->contains($object)) {
            return true;
        }

        return false;
    }

    protected function markInstanceAsValidated(object $object): void
    {
        $this->validatedInstancesContainer->attach($object);
    }

    /**
     * Returns all property validators - or only validators of the specified property
     */
    public function getPropertyValidators(string $propertyName = null): array
    {
        if ($propertyName !== null) {
            return $this->propertyValidators[$propertyName] ?? [];
        }
        return $this->propertyValidators;
    }

    /**
     * Allows to set a container to keep track of validated instances.
     */
    public function setValidatedInstancesContainer(\SplObjectStorage $validatedInstancesContainer): void
    {
        $this->validatedInstancesContainer = $validatedInstancesContainer;
    }
}
