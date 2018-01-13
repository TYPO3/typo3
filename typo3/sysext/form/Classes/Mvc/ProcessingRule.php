<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Mvc;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
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

    /**
     * @var \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration
     */
    protected $propertyMappingConfiguration;

    /**
     * @var \TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator
     */
    protected $validator;

    /**
     * @var \TYPO3\CMS\Extbase\Error\Result
     */
    protected $processingMessages;

    /**
     * @var \TYPO3\CMS\Extbase\Property\PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration $propertyMappingConfiguration
     * @internal
     */
    public function injectPropertyMappingConfiguration(\TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration $propertyMappingConfiguration)
    {
        $this->propertyMappingConfiguration = $propertyMappingConfiguration;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator $validator
     * @internal
     */
    public function injectConjunctionValidator(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Property\PropertyMapper $propertyMapper
     * @internal
     */
    public function injectPropertyMapper(\TYPO3\CMS\Extbase\Property\PropertyMapper $propertyMapper)
    {
        $this->propertyMapper = $propertyMapper;
    }

    /**
     * Constructs this processing rule
     * @internal
     */
    public function __construct()
    {
        $this->processingMessages = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(Result::class);
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
     * @return \SplObjectStorage<ValidatorInterface>
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
        } else {
            $messages = GeneralUtility::makeInstance(ObjectManager::class)
                ->get(Result::class);
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
