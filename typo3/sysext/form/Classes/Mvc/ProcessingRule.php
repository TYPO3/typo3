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

namespace TYPO3\CMS\Form\Mvc;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

/**
 * A processing Rule contains information for property mapping and validation.
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 * @internal
 */
class ProcessingRule
{

    /**
     * The target data type the data should be converted to
     *
     * @var string
     */
    protected $dataType;

    protected PropertyMappingConfiguration $propertyMappingConfiguration;
    protected ConjunctionValidator $validator;
    protected Result $processingMessages;
    protected PropertyMapper $propertyMapper;

    /**
     * Constructs this processing rule
     * @internal
     */
    public function __construct(PropertyMapper $propertyMapper)
    {
        $this->propertyMapper = $propertyMapper;
        $this->propertyMappingConfiguration = GeneralUtility::makeInstance(PropertyMappingConfiguration::class);
        $this->validator = GeneralUtility::makeInstance(ConjunctionValidator::class);
        $this->processingMessages = GeneralUtility::makeInstance(Result::class);
    }

    /**
     * @return PropertyMappingConfiguration
     * @internal
     */
    public function getPropertyMappingConfiguration(): PropertyMappingConfiguration
    {
        return $this->propertyMappingConfiguration;
    }

    /**
     * @return string
     * @internal
     */
    public function getDataType(): string
    {
        return $this->dataType;
    }

    /**
     * @param string $dataType
     * @internal
     */
    public function setDataType(string $dataType)
    {
        $this->dataType = $dataType;
    }

    /**
     * Returns the child validators of the ConjunctionValidator that is bound to this processing rule
     *
     * @return \SplObjectStorage
     * @internal
     */
    public function getValidators(): \SplObjectStorage
    {
        return $this->validator->getValidators();
    }

    /**
     * @param ValidatorInterface $validator
     * @internal
     */
    public function addValidator(ValidatorInterface $validator)
    {
        $this->validator->addValidator($validator);
    }

    /**
     * Initializes a new validator container
     *
     * @internal
     */
    public function removeAllValidators(): void
    {
        $this->filterValidators(fn () => false);
    }

    /**
     * Filters validators based on a closure
     *
     * @internal
     */
    public function filterValidators(\Closure $filter): void
    {
        $validatorsToRemove = new \SplObjectStorage();
        $validators = $this->getValidators();
        foreach ($validators as $validator) {
            if (!$filter($validator)) {
                $validatorsToRemove->attach($validator);
            }
        }
        $validators->removeAll($validatorsToRemove);
    }

    /**
     * Removes the specified validator.
     *
     * @param ValidatorInterface $validator The validator to remove
     * @throws \TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException
     * @internal
     */
    public function removeValidator(ValidatorInterface $validator)
    {
        $this->validator->removeValidator($validator);
    }

    /**
     * @param mixed $value
     * @return mixed
     * @internal
     */
    public function process($value)
    {
        if ($this->dataType !== null) {
            $value = $this->propertyMapper->convert($value, $this->dataType, $this->propertyMappingConfiguration);
            $messages = $this->propertyMapper->getMessages();
            $this->propertyMapper->resetMessages();
        } else {
            $messages = GeneralUtility::makeInstance(Result::class);
        }

        $validationResult = $this->validator->validate($value);
        $messages->merge($validationResult);

        $this->processingMessages->merge($messages);
        return $value;
    }

    /**
     * @return Result
     * @internal
     */
    public function getProcessingMessages(): Result
    {
        return $this->processingMessages;
    }
}
