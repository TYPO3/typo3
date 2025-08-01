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

namespace TYPO3\CMS\Extbase\Mvc\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Validation\Validator\FileExtensionMimeTypeConsistencyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\FileNameValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

/**
 * @internal Only to be used within Extbase, not part of TYPO3 Core API.
 */
class FileHandlingServiceConfiguration
{
    /**
     * @var ObjectStorage<FileUploadConfiguration>
     */
    protected ObjectStorage $fileUploadConfigurations;

    /**
     * @var ObjectStorage<FileUploadDeletionConfiguration>
     */
    protected ObjectStorage $fileUploadDeletionConfigurations;

    public function __construct()
    {
        $this->fileUploadConfigurations = new ObjectStorage();
        $this->fileUploadDeletionConfigurations = new ObjectStorage();
    }

    public function addFileUploadConfiguration(FileUploadConfiguration $configuration): void
    {
        $this->fileUploadConfigurations->attach($configuration);
    }

    public function getFileUploadConfigurations(): ObjectStorage
    {
        return $this->fileUploadConfigurations;
    }

    public function hasfileUploadConfigurations(): bool
    {
        return $this->fileUploadConfigurations->count() > 0;
    }

    /**
     * Returns the FileUploadConfiguration for the given propertyName
     */
    public function getFileUploadConfigurationForProperty(string $propertyName): ?FileUploadConfiguration
    {
        foreach ($this->fileUploadConfigurations as $configuration) {
            if ($configuration->getPropertyName() === $propertyName) {
                return $configuration;
            }
        }

        return null;
    }

    /**
     * Registers a file deletion for the given property and file reference uid
     */
    public function registerFileDeletion(string $property, int $fileReferenceUid): void
    {
        $fileUploadDeletionConfiguration = $this->getFileUploadDeletionConfigurationForProperty($property);
        if (!$fileUploadDeletionConfiguration) {
            $fileUploadDeletionConfiguration = GeneralUtility::makeInstance(FileUploadDeletionConfiguration::class, $property);
            $this->fileUploadDeletionConfigurations->attach($fileUploadDeletionConfiguration);
        }
        $fileUploadDeletionConfiguration->addFileReferenceUid($fileReferenceUid);
    }

    /**
     * Returns all file deletion configurations
     */
    public function getFileUploadDeletionConfigurations(): ObjectStorage
    {
        return $this->fileUploadDeletionConfigurations;
    }

    /**
     * Returns the FileUploadDeletionConfiguration for the given propertyName
     */
    public function getFileUploadDeletionConfigurationForProperty(string $propertyName): ?FileUploadDeletionConfiguration
    {
        foreach ($this->fileUploadDeletionConfigurations as $configuration) {
            if ($configuration->getPropertyName() === $propertyName) {
                return $configuration;
            }
        }

        return null;
    }

    /**
     * Returns the amount of configured file deletions for the given property
     */
    private function getFileUploadDeletionCountForProperty(string $propertyName): int
    {
        $fileUploadDeletionConfiguration = $this->getFileUploadDeletionConfigurationForProperty($propertyName);
        if ($fileUploadDeletionConfiguration) {
            return count($fileUploadDeletionConfiguration->getFileReferenceUids());
        }

        return 0;
    }

    /**
     * Validates file operations for the given argument by checking file upload and file deletion configurations and
     * returning the validation result.
     */
    public function validateFileOperations(Argument $argument): Result
    {
        $validationResults = new Result();
        $value = $argument->getValue();

        foreach ($this->fileUploadConfigurations as $configuration) {
            $uploadedFilesForProperty = $argument->getUploadedFilesForProperty(
                $configuration->getPropertyName()
            );
            $fileDeletionCount = $this->getFileUploadDeletionCountForProperty($configuration->getPropertyName());
            $currentPropertyValue = null;
            if ($value) {
                $currentPropertyValue = ObjectAccess::getPropertyPath($value, $configuration->getPropertyName());
            }
            $validationResult = $this->getValidationResultsForProperty(
                $configuration,
                $configuration->getPropertyName(),
                $currentPropertyValue,
                $uploadedFilesForProperty,
                $fileDeletionCount
            );
            $validationResults->merge($validationResult);
        }

        return $validationResults;
    }

    /**
     * Validates file uploads and file deletions for the given propertyPath and currentPropertyValue and returns
     * the validation result.
     */
    private function getValidationResultsForProperty(
        FileUploadConfiguration $configuration,
        string $propertyPath,
        mixed $currentPropertyValue,
        array $uploadedFiles,
        int $fileDeletionCount
    ): Result {
        $validationResults = new Result();

        if ($currentPropertyValue instanceof FileReference) {
            $currentAmount = 1;
        } elseif ($currentPropertyValue instanceof ObjectStorage) {
            $currentAmount = $currentPropertyValue->count();
        } else {
            $currentAmount = 0;
        }

        // Validate, that minimum files requirement is valid after file deletion(s)
        if ($fileDeletionCount > 0 &&
            ($currentPropertyValue instanceof FileReference || $currentPropertyValue instanceof ObjectStorage)
        ) {
            $newAmount = $currentAmount - $fileDeletionCount + count($uploadedFiles);
            if ($newAmount < $configuration->getMinFiles()) {
                $minFilesError = new Error(
                    $this->translateErrorMessage(
                        'filehandlingserviceconfiguration.minfiles.delete.notvalid',
                        'extbase',
                    ),
                    1714557062
                );
                $validationResults->forProperty($propertyPath)
                    ->addError($minFilesError);
            }
        }

        // If the given $currentPropertyValue (which is the target property for file upload) is either a FileReference
        // or a non empty ObjectStorage and no uploaded files are available, the rest of the validation can be skipped.
        if ($uploadedFiles === [] &&
            ($currentPropertyValue instanceof FileReference ||
                ($currentPropertyValue instanceof ObjectStorage && $currentPropertyValue->count() > 0))
        ) {
            return $validationResults;
        }

        if (count($uploadedFiles) < $configuration->getMinFiles()) {
            $minFilesError = new Error(
                $this->translateErrorMessage(
                    'filehandlingserviceconfiguration.minfiles.notvalid',
                    'extbase',
                    [$configuration->getMinFiles()]
                ),
                1708596527
            );
            $validationResults->forProperty($propertyPath)
                ->addError($minFilesError);
        }

        if ((count($uploadedFiles) + $currentAmount - $fileDeletionCount) > $configuration->getMaxFiles()) {
            $minFilesError = new Error(
                $this->translateErrorMessage(
                    'filehandlingserviceconfiguration.maxfiles.notvalid',
                    'extbase',
                    [$configuration->getMaxFiles()]
                ),
                1708596528
            );
            $validationResults->forProperty($propertyPath)
                ->addError($minFilesError);
        }

        $validators = $this->enforceDefaultValidators(
            ...$configuration->getValidators()
        );
        foreach ($validators as $validator) {
            foreach ($uploadedFiles as $uploadedFile) {
                $validatorResult = $validator->validate($uploadedFile);
                if ($validatorResult->hasErrors()) {
                    $validationResults->forProperty($propertyPath)->merge($validatorResult);
                }
            }
        }

        return $validationResults;
    }

    /**
     * @return list<ValidatorInterface>
     */
    private function enforceDefaultValidators(ValidatorInterface ...$validators): array
    {
        $enforceValidators = [
            FileNameValidator::class,
            FileExtensionMimeTypeConsistencyValidator::class,
        ];
        $existingValidators = array_map(get_class(...), $validators);
        $missingValidators = array_diff($enforceValidators, $existingValidators);
        foreach ($missingValidators as $missingValidator) {
            $validators[] = GeneralUtility::makeInstance($missingValidator);
        }
        return $validators;
    }

    /**
     * Wrapper to translate error messages
     */
    private function translateErrorMessage(string $translateKey, string $extensionName, array $arguments = []): string
    {
        return LocalizationUtility::translate(
            $translateKey,
            $extensionName,
            $arguments
        ) ?? '';
    }
}
