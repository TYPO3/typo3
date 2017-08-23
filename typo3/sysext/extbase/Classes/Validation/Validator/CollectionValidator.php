<?php
namespace TYPO3\CMS\Extbase\Validation\Validator;

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

/**
 * A generic collection validator.
 *
 * @api
 */
class CollectionValidator extends GenericObjectValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'elementValidator' => [null, 'The validator type to use for the collection elements', 'string'],
        'elementType' => [null, 'The type of the elements in the collection', 'string'],
        'validationGroups' => [null, 'The validation groups to link to', 'string'],
    ];

    /**
     * @var \TYPO3\CMS\Extbase\Validation\ValidatorResolver
     */
    protected $validatorResolver;

    /**
     * @param \TYPO3\CMS\Extbase\Validation\ValidatorResolver $validatorResolver
     */
    public function injectValidatorResolver(\TYPO3\CMS\Extbase\Validation\ValidatorResolver $validatorResolver)
    {
        $this->validatorResolver = $validatorResolver;
    }

    /**
     * Checks if the given value is valid according to the validator, and returns
     * the Error Messages object which occurred.
     *
     * @param mixed $value The value that should be validated
     * @return \TYPO3\CMS\Extbase\Error\Result
     * @api
     */
    public function validate($value)
    {
        $this->result = new \TYPO3\CMS\Extbase\Error\Result();

        if ($this->acceptsEmptyValues === false || $this->isEmpty($value) === false) {
            if ((is_object($value) && !\TYPO3\CMS\Extbase\Utility\TypeHandlingUtility::isCollectionType(get_class($value))) && !is_array($value)) {
                $this->addError('The given subject was not a collection.', 1317204797);
                return $this->result;
            }
            if ($value instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage && !$value->isInitialized()) {
                return $this->result;
            }
            if (is_object($value) && $this->isValidatedAlready($value)) {
                return $this->result;
            }
            $this->isValid($value);
        }
        return $this->result;
    }

    /**
     * Checks for a collection and if needed validates the items in the collection.
     * This is done with the specified element validator or a validator based on
     * the given element type and validation group.
     *
     * Either elementValidator or elementType must be given, otherwise validation
     * will be skipped.
     *
     * @param mixed $value A collection to be validated
     */
    protected function isValid($value)
    {
        foreach ($value as $index => $collectionElement) {
            if (isset($this->options['elementValidator'])) {
                $collectionElementValidator = $this->validatorResolver->createValidator($this->options['elementValidator']);
            } elseif (isset($this->options['elementType'])) {
                if (isset($this->options['validationGroups'])) {
                    $collectionElementValidator = $this->validatorResolver->getBaseValidatorConjunction($this->options['elementType'], $this->options['validationGroups']);
                } else {
                    $collectionElementValidator = $this->validatorResolver->getBaseValidatorConjunction($this->options['elementType']);
                }
            } else {
                return;
            }
            if ($collectionElementValidator instanceof ObjectValidatorInterface) {
                $collectionElementValidator->setValidatedInstancesContainer($this->validatedInstancesContainer);
            }
            $this->result->forProperty($index)->merge($collectionElementValidator->validate($collectionElement));
        }
    }
}
