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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;

/**
 * A processing Rule contains information for property mapping and validation.
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 * @internal
 */
#[Autoconfigure(public: true, shared: false)]
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

    /**
     * Constructs this processing rule
     * @internal
     */
    public function __construct(
        protected readonly PropertyMapper $propertyMapper,
        ValidatorResolver $validatorResolver,
    ) {
        $this->propertyMappingConfiguration = GeneralUtility::makeInstance(PropertyMappingConfiguration::class);
        /** @var ConjunctionValidator $validator */
        $validator = $validatorResolver->createValidator(ConjunctionValidator::class);
        $this->validator = $validator;
        $this->processingMessages = GeneralUtility::makeInstance(Result::class);
    }

    /**
     * @internal
     */
    public function getPropertyMappingConfiguration(): PropertyMappingConfiguration
    {
        return $this->propertyMappingConfiguration;
    }

    /**
     * @internal
     */
    public function getDataType(): string
    {
        return $this->dataType;
    }

    /**
     * @internal
     */
    public function setDataType(string $dataType)
    {
        $this->dataType = $dataType;
    }

    /**
     * Returns the child validators of the ConjunctionValidator that is bound to this processing rule
     *
     * @internal
     */
    public function getValidators(): \SplObjectStorage
    {
        return $this->validator->getValidators();
    }

    /**
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
        $this->filterValidators(fn() => false);
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
                $validatorsToRemove->offsetSet($validator);
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
     * @internal
     */
    public function getProcessingMessages(): Result
    {
        return $this->processingMessages;
    }
}
