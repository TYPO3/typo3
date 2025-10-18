<?php

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

namespace TYPO3\CMS\Extbase\Mvc\Controller;

use Psr\Http\Message\UploadedFileInterface;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Utility\TypeHandlingUtility;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

/**
 * A controller argument
 */
class Argument
{
    protected MvcPropertyMappingConfiguration $propertyMappingConfiguration;
    protected FileHandlingServiceConfiguration $fileHandlingServiceConfiguration;
    protected string $name = '';
    protected string $shortName = '';
    protected string $dataType = '';
    protected bool $isRequired = false;
    protected mixed $value = null;
    private bool $hasBeenValidated = false;

    /**
     * Uploaded files for the argument
     * @var array<string, UploadedFileInterface|list<UploadedFileInterface>>
     */
    protected array $uploadedFiles = [];

    /**
     * Default value. Used if argument is optional.
     */
    protected mixed $defaultValue = null;

    /**
     * A custom validator, used supplementary to the base validation
     */
    protected ?ValidatorInterface $validator = null;

    /**
     * The validation results. This can be asked if the argument has errors.
     */
    protected Result $validationResults;

    /**
     * Constructs this controller argument
     *
     * @throws \InvalidArgumentException if $name is empty string
     */
    public function __construct(string $name, string $dataType)
    {
        if ($name === '') {
            throw new \InvalidArgumentException('$name must be a non-empty string.', 1232551853);
        }
        $this->name = $name;
        $this->dataType = TypeHandlingUtility::normalizeType($dataType);

        $this->validationResults = new Result();
        $this->propertyMappingConfiguration = GeneralUtility::makeInstance(MvcPropertyMappingConfiguration::class);
        $this->fileHandlingServiceConfiguration = GeneralUtility::makeInstance(FileHandlingServiceConfiguration::class);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @throws \InvalidArgumentException if $shortName is not a character
     */
    public function setShortName(string $shortName): Argument
    {
        if (strlen($shortName) !== 1) {
            throw new \InvalidArgumentException('$shortName must be a single character or NULL', 1195824959);
        }
        $this->shortName = $shortName;
        return $this;
    }

    public function getShortName(): string
    {
        return $this->shortName;
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }

    public function setRequired(bool $required): Argument
    {
        $this->isRequired = $required;
        return $this;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function setDefaultValue(mixed $defaultValue): Argument
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
    }

    /**
     * Sets a custom validator which is used supplementary to the base validation
     */
    public function setValidator(ValidatorInterface $validator): Argument
    {
        $this->validator = $validator;
        return $this;
    }

    public function getValidator(): ?ValidatorInterface
    {
        return $this->validator;
    }

    public function setValue(mixed $rawValue): Argument
    {
        $this->value = $rawValue;
        return $this;
    }

    public function getValue(): mixed
    {
        if ($this->value === null) {
            return $this->defaultValue;
        }
        return $this->value;
    }

    /**
     * Return the Property Mapping Configuration used for this argument; can be used by the initialize*action to modify the Property Mapping.
     */
    public function getPropertyMappingConfiguration(): MvcPropertyMappingConfiguration
    {
        return $this->propertyMappingConfiguration;
    }

    /**
     * Return the FileHandlingServiceConfiguration used for this argument; can be used by the
     * initialize*action to modify the file upload configuration for properties.
     */
    public function getFileHandlingServiceConfiguration(): FileHandlingServiceConfiguration
    {
        return $this->fileHandlingServiceConfiguration;
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function setUploadedFiles(array $uploadedFiles): void
    {
        $this->uploadedFiles = $uploadedFiles;
    }

    /**
     * @return bool TRUE if the argument is valid, FALSE otherwise
     */
    public function isValid(): bool
    {
        return !$this->validate()->hasErrors();
    }

    /**
     * Returns a string representation of this argument's value
     */
    public function __toString(): string
    {
        return (string)$this->value;
    }

    public function validate(): Result
    {
        if ($this->hasBeenValidated) {
            return $this->validationResults;
        }

        if ($this->validator !== null) {
            $validationMessages = $this->validator->validate($this->value);
            $this->validationResults->merge($validationMessages);
        }

        if ($this->fileHandlingServiceConfiguration->hasfileUploadConfigurations()) {
            $fileOperationValidationResults = $this->fileHandlingServiceConfiguration->validateFileOperations($this);
            $this->validationResults->merge($fileOperationValidationResults);
        }

        $this->hasBeenValidated = true;
        return $this->validationResults;
    }

    /**
     * Returns an array of possible UploadedFile objects for the given property
     * @return list<UploadedFileInterface>
     */
    public function getUploadedFilesForProperty(string $propertyName): array
    {
        $result = [];

        try {
            $uploadedFiles = ArrayUtility::getValueByPath($this->uploadedFiles, $propertyName, '.');
            if ($uploadedFiles instanceof UploadedFile) {
                $result = [$uploadedFiles];
            } elseif (is_iterable($uploadedFiles)) {
                foreach ($uploadedFiles as $uploadedFile) {
                    $result[] = $uploadedFile;
                }
            }
        } catch (MissingArrayPathException) {
            // Do nothing, empty array will be returned
        }

        return $result;
    }

    /**
     * @internal only to be used within Extbase, not part of TYPO3 Core API.
     */
    public function getValidationResults(): Result
    {
        return $this->validationResults;
    }
}
