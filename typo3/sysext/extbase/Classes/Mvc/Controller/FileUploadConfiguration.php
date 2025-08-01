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

use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Validation\Validator\FileExtensionValidator;
use TYPO3\CMS\Extbase\Validation\Validator\FileSizeValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ImageDimensionsValidator;
use TYPO3\CMS\Extbase\Validation\Validator\MimeTypeValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

/**
 * @internal Only to be used within Extbase, not part of TYPO3 Core API.
 */
class FileUploadConfiguration
{
    protected string $uploadFolder = '';
    protected int $minFiles = 0;
    protected int $maxFiles = PHP_INT_MAX;
    protected bool $addRandomSuffix = true;
    protected bool $createUploadFolderIfNotExist = true;
    protected DuplicationBehavior $duplicationBehavior = DuplicationBehavior::RENAME;

    /**
     * @var array<ValidatorInterface>
     */
    protected array $validators = [];

    public function __construct(protected readonly string $propertyName) {}

    /**
     * Initializes the object with the given configuration array. Typically used with configuration from
     * FileUpload annotation/attribute.
     */
    public function initializeWithConfiguration(array $configuration): self
    {
        if (!isset($configuration['validation']) || $configuration['validation'] === []) {
            throw new \RuntimeException('Extbase file upload must at least define one validation rule.', 1711947120);
        }

        $this->initializeUploadValidation($configuration['validation']);

        if (isset($configuration['uploadFolder']) && $configuration['uploadFolder'] !== '') {
            $this->uploadFolder = $configuration['uploadFolder'];
        }

        if (isset($configuration['addRandomSuffix'])) {
            $this->addRandomSuffix = (bool)$configuration['addRandomSuffix'];
        }

        if (isset($configuration['duplicationBehavior'])) {
            $this->duplicationBehavior = $configuration['duplicationBehavior'];
        }

        if (isset($configuration['createUploadFolderIfNotExist'])) {
            $this->createUploadFolderIfNotExist = $configuration['createUploadFolderIfNotExist'];
        }

        return $this;
    }

    public function addValidator(ValidatorInterface $validator): self
    {
        $this->validators[] = $validator;
        return $this;
    }

    public function getValidators(): array
    {
        return $this->validators;
    }

    public function resetValidators(): self
    {
        $this->validators = [];
        return $this;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function setRequired(): self
    {
        $this->minFiles = 1;
        return $this;
    }

    public function getMinFiles(): int
    {
        return $this->minFiles;
    }

    public function setMinFiles(int $minFiles): self
    {
        $this->minFiles = $minFiles;
        return $this;
    }

    public function getMaxFiles(): int
    {
        return $this->maxFiles;
    }

    public function setMaxFiles(int $maxFiles): self
    {
        $this->maxFiles = $maxFiles;
        return $this;
    }

    public function getUploadFolder(): string
    {
        return $this->uploadFolder;
    }

    public function setUploadFolder(string $uploadFolder): self
    {
        $this->uploadFolder = $uploadFolder;
        return $this;
    }

    public function isAddRandomSuffix(): bool
    {
        return $this->addRandomSuffix;
    }

    public function setAddRandomSuffix(bool $addRandomSuffix): self
    {
        $this->addRandomSuffix = $addRandomSuffix;
        return $this;
    }

    public function isCreateUploadFolderIfNotExist(): bool
    {
        return $this->createUploadFolderIfNotExist;
    }

    public function setCreateUploadFolderIfNotExist(bool $createUploadFolderIfNotExist): self
    {
        $this->createUploadFolderIfNotExist = $createUploadFolderIfNotExist;
        return $this;
    }

    public function getDuplicationBehavior(): DuplicationBehavior
    {
        return $this->duplicationBehavior;
    }

    public function setDuplicationBehavior(DuplicationBehavior $duplicationBehavior): void
    {
        $this->duplicationBehavior = $duplicationBehavior;
    }

    /**
     * Checks if the current configuration is considered valid for the given target type and throws
     * an exception, if the configuration is invalid.
     */
    public function ensureValidConfiguration(string $targetType): void
    {
        if ($targetType !== FileReference::class) {
            throw new \RuntimeException('The FileUploadConfiguration can only be used for properties of type FileReference.', 1721623184);
        }

        if (str_contains($this->getPropertyName(), '.')) {
            throw new \RuntimeException('The property name for the FileUploadConfiguration must not contain any dot.', 1724585391);
        }

        if ($this->getUploadFolder() === '') {
            throw new \RuntimeException('An upload folder must be defined for the FileUploadConfiguration.', 1711799735);
        }

        if (!$this->isCombinedStoragePathIdentifier($this->getUploadFolder())) {
            throw new \RuntimeException('The upload folder must be a combined identifier - e.g. 1:/user_upload/', 1711801071);
        }

        if ($this->getMaxFiles() < $this->getMinFiles()) {
            throw new \RuntimeException('Maximum number of files cannot be less than minimum number of files.', 1711799765);
        }
    }

    private function isCombinedStoragePathIdentifier(string $identifier): bool
    {
        return str_contains($identifier, ':') &&
            !str_starts_with($identifier, ':') &&
            !str_ends_with($identifier, ':') &&
            MathUtility::canBeInterpretedAsInteger(substr($identifier, 0, strpos($identifier, ':')));
    }

    /**
     * Initializes validators based on the given array of validation configuration
     */
    private function initializeUploadValidation(array $validationConfiguration): void
    {
        if ($validationConfiguration['required'] ?? false) {
            $this->minFiles = 1;
        }

        if ((int)($validationConfiguration['minFiles'] ?? PHP_INT_MAX) < PHP_INT_MAX) {
            $this->minFiles = (int)($validationConfiguration['minFiles']);
        }

        if ((int)($validationConfiguration['maxFiles'] ?? PHP_INT_MAX) < PHP_INT_MAX) {
            $this->maxFiles = (int)($validationConfiguration['maxFiles']);
        }

        // Migrate allowedMimeTypes to mimeType configuration, if mimeType configuration is not defined
        if (($validationConfiguration['allowedMimeTypes'] ?? false) &&
            is_array($validationConfiguration['allowedMimeTypes']) &&
            !isset($validationConfiguration['mimeType'])
        ) {
            $validationConfiguration['mimeType'] = ['allowedMimeTypes' => $validationConfiguration['allowedMimeTypes']];
            unset($validationConfiguration['allowedMimeTypes']);
        }

        if (($validationConfiguration['mimeType'] ?? false) &&
            is_array($validationConfiguration['mimeType'])
        ) {
            $mimeTypeValidator = GeneralUtility::makeInstance(MimeTypeValidator::class);
            $mimeTypeValidator->setOptions($validationConfiguration['mimeType']);
            $this->addValidator($mimeTypeValidator);
        }

        if (($validationConfiguration['fileExtension'] ?? false) &&
            is_array($validationConfiguration['fileExtension'])
        ) {
            $fileExtensionValidator = GeneralUtility::makeInstance(FileExtensionValidator::class);
            $fileExtensionValidator->setOptions($validationConfiguration['fileExtension']);
            $this->addValidator($fileExtensionValidator);
        }

        if (($validationConfiguration['fileSize'] ?? false) &&
            is_array($validationConfiguration['fileSize'])
        ) {
            $fileSizeValidator = GeneralUtility::makeInstance(FileSizeValidator::class);
            $fileSizeValidator->setOptions($validationConfiguration['fileSize']);
            $this->addValidator($fileSizeValidator);
        }

        if (($validationConfiguration['imageDimensions'] ?? false) &&
            is_array($validationConfiguration['imageDimensions'])
        ) {
            $imageDimensionsValidator = GeneralUtility::makeInstance(ImageDimensionsValidator::class);
            $imageDimensionsValidator->setOptions($validationConfiguration['imageDimensions']);
            $this->addValidator($imageDimensionsValidator);
        }
    }
}
