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

namespace TYPO3\CMS\Extbase\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference as CoreFileReference;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Event\Service\ModifyUploadedFileTargetFilenameEvent;
use TYPO3\CMS\Extbase\Mvc\Controller\Argument;
use TYPO3\CMS\Extbase\Mvc\Controller\Arguments;
use TYPO3\CMS\Extbase\Mvc\Controller\FileUploadConfiguration;
use TYPO3\CMS\Extbase\Mvc\Controller\FileUploadDeletionConfiguration;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Reflection\ClassSchema\Property;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Extbase\Utility\TypeHandlingUtility;

/**
 * @internal Only to be used within Extbase, not part of TYPO3 Core API.
 */
class FileHandlingService implements SingletonInterface
{
    public const DELETE_IDENTIFIER = '@delete';

    public function __construct(
        protected readonly ReflectionService $reflectionService,
        protected readonly ResourceFactory $resourceFactory,
        protected readonly DataMapFactory $dataMapFactory,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly HashService $hashService,
        protected readonly ExtensionService $extensionService,
    ) {}

    /**
     * Initializes file upload configurations for all FileUpload properties of the argument. Note, that this is only
     * applied for the HTTP method POST.
     */
    public function initializeFileUploadConfigurationsFromRequest(
        RequestInterface $request,
        Arguments $arguments
    ): void {
        if ($request->getMethod() !== 'POST' || $arguments->count() === 0) {
            return;
        }
        /** @var Argument $argument */
        foreach ($arguments as $argument) {
            if (!$argument->getValidator() ||
                !class_exists($argument->getDataType())
            ) {
                // Either argument has no validator (IgnoreValidation) or the datatype of the argument is not a class.
                continue;
            }

            $classSchema = $this->reflectionService->getClassSchema($argument->getDataType());
            foreach ($classSchema->getProperties() as $property) {
                $this->addUploadConfigurationForProperty($argument, $property);
            }
        }
    }

    /**
     * Adds a new upload configuration for the given property to the given argument.
     */
    private function addUploadConfigurationForProperty(
        Argument $argument,
        Property $property
    ): void {
        $primaryType = $property->getPrimaryType();
        if (!$primaryType) {
            throw new \InvalidArgumentException(
                sprintf(
                    'There is no @var annotation or type declaration for file upload property "%s" in class "%s".',
                    $property->getName(),
                    $argument->getDataType()
                ),
                1712309171
            );
        }

        $propertyTargetClassName = $primaryType->getClassName() ?? $primaryType->getBuiltinType();
        if ($propertyTargetClassName !== FileReference::class &&
            !TypeHandlingUtility::isSimpleType($propertyTargetClassName)
        ) {
            $primaryCollectionValueType = $property->getPrimaryCollectionValueType();
            if ($propertyTargetClassName === ObjectStorage::class &&
                $primaryCollectionValueType &&
                $primaryType->isCollection()
            ) {
                $propertyTargetClassName = $primaryCollectionValueType->getClassName() ?? $primaryCollectionValueType->getBuiltinType();
            }
        }

        // Skip unsupported classes for FileUpload annotation or properties with empty FileUpload configuration
        if ($propertyTargetClassName !== FileReference::class || $property->getFileUpload() === null) {
            return;
        }

        $fileUploadConfiguration = $property->getFileUpload();
        $configurationPropertyName = $property->getName();

        $configuration = (new FileUploadConfiguration($configurationPropertyName))
            ->initializeWithConfiguration($fileUploadConfiguration);
        $configuration->ensureValidConfiguration($propertyTargetClassName);

        $argument->getFileHandlingServiceConfiguration()->addFileUploadConfiguration($configuration);

        // If FileUpload is configured, the property mapping must be skipped
        $argument->getPropertyMappingConfiguration()->skipProperties($configurationPropertyName);
    }

    /**
     * Initializes file deletion configurations for properties of the given argument.
     */
    public function initializeFileUploadDeletionConfigurationsFromRequest(
        RequestInterface $request,
        Arguments $arguments
    ): void {
        if ($request->getMethod() !== 'POST' || $arguments->count() === 0) {
            return;
        }

        $pluginNamespace = $this->extensionService->getPluginNamespace(
            $request->getControllerExtensionName(),
            $request->getPluginName()
        );
        $fileDeletions = $request->getParsedBody()[$pluginNamespace][self::DELETE_IDENTIFIER] ?? [];

        // In case of validation errors, file deletions must not be processed
        if ($fileDeletions === [] || $this->hasMappingErrorOccurred($request)) {
            return;
        }

        /** @var Argument $argument */
        foreach ($arguments as $argument) {
            if (isset($fileDeletions[$argument->getName()]) && is_array($fileDeletions[$argument->getName()])) {
                $this->addDeletionConfigurationsToArgument($argument, $fileDeletions[$argument->getName()]);
            }
        }
    }

    /**
     * Maps and persists (if required) uploaded files for the given argument.
     */
    public function mapUploadedFilesToArgument(Argument $argument): void
    {
        foreach ($argument->getFileHandlingServiceConfiguration()->getFileUploadConfigurations() as $configuration) {
            $this->mapUploadedFilesToArgumentForConfiguration($argument, $configuration);
        }
    }

    /**
     * Maps uploaded files to the argument for configuration.
     *
     * Maps uploaded files to the specified property of the argument, if property is allowed in current
     * PropertyMappingConfiguration
     */
    private function mapUploadedFilesToArgumentForConfiguration(
        Argument $argument,
        FileUploadConfiguration $configuration
    ): void {
        $propertyName = $configuration->getPropertyName();

        if ($this->shouldMapProperty($argument, $propertyName)) {
            $argumentValue = $argument->getValue();
            $uploadedFiles = $argument->getUploadedFilesForProperty($propertyName);
            $this->mapUploadedFilesToArgumentForProperty(
                $argumentValue,
                $propertyName,
                $uploadedFiles,
                $configuration
            );
        }
    }

    /**
     * Maps uploaded files to the specified property of the object, based on the provided configuration.
     */
    private function mapUploadedFilesToArgumentForProperty(
        mixed $argumentValue,
        string $propertyName,
        array $uploadedFiles,
        FileUploadConfiguration $configuration
    ): void {
        if ($uploadedFiles === [] ||
            !ObjectAccess::isPropertyGettable($argumentValue, $propertyName) ||
            !ObjectAccess::isPropertySettable($argumentValue, $propertyName)
        ) {
            return;
        }

        $classSchema = $this->reflectionService->getClassSchema($argumentValue);
        $property = $classSchema->getProperty($propertyName);
        if (!$property->getPrimaryType()) {
            return;
        }

        $isObjectStorage = $property->isObjectStorageType();
        $targetType = $isObjectStorage ? $property->getPrimaryCollectionValueType()->getClassName() : $property->getPrimaryType()->getClassName();

        if ($targetType === FileReference::class) {
            $configuration->ensureValidConfiguration($targetType);
            $this->persistUploadedFilesAndMapAsFileReferencesToProperty(
                $argumentValue,
                $propertyName,
                $isObjectStorage,
                $configuration,
                $uploadedFiles
            );
        }
    }

    /**
     * Moves PSR-7 uploaded files to the target storage defined in the given file upload configuration.
     *
     * For target property type FileReference, either a new FileReference object is created or a possible existing
     * FileReference object is reused and the uploaded file is set.
     *
     * For target property type ObjectStorage<FileReference>, new FileReference objects are created and attached
     * to the property.
     */
    private function persistUploadedFilesAndMapAsFileReferencesToProperty(
        mixed $argumentValue,
        string $propertyName,
        bool $isObjectStorage,
        FileUploadConfiguration $configuration,
        array $uploadedFiles,
    ): void {
        $uploadFolder = $this->provideUploadFolder($configuration);
        $storage = $uploadFolder->getStorage();

        if ($isObjectStorage) {
            /** @var ObjectStorage $currentPropertyValue */
            $currentPropertyValue = ObjectAccess::getProperty($argumentValue, $propertyName);

            foreach ($uploadedFiles as $uploadedFile) {
                $targetFilename = $this->getTargetFilename($uploadedFile->getClientFilename(), $configuration);
                $file = $storage->addUploadedFile($uploadedFile, $uploadFolder, $targetFilename, $configuration->getDuplicationBehavior());
                $coreFileReference = $this->createCoreFileReference($file);
                $fileReference = $this->createExtbaseFileReference($coreFileReference);
                $currentPropertyValue->attach($fileReference);
            }
        } else {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $uploadedFiles[0];
            $targetFilename = $this->getTargetFilename($uploadedFile->getClientFilename(), $configuration);
            $file = $storage->addUploadedFile($uploadedFile, $uploadFolder, $targetFilename, $configuration->getDuplicationBehavior());
            $coreFileReference = $this->createCoreFileReference($file);

            /** @var FileReference|null $currentPropertyValue */
            $currentPropertyValue = ObjectAccess::getProperty($argumentValue, $propertyName);

            if ($currentPropertyValue) {
                $currentPropertyValue->setOriginalResource($coreFileReference);
            } else {
                $currentPropertyValue = $this->createExtbaseFileReference($coreFileReference);
            }
        }

        ObjectAccess::setProperty($argumentValue, $propertyName, $currentPropertyValue);
    }

    private function addDeletionConfigurationsToArgument(Argument $argument, array $fileDeletions): void
    {
        foreach ($fileDeletions as $signedDeletionData) {
            $deletionData = $this->hashService->validateAndStripHmac(
                $signedDeletionData,
                self::DELETE_IDENTIFIER
            );
            $deletionData = json_decode($deletionData, true, 512, JSON_THROW_ON_ERROR);
            $fileReferenceUid = (int)$deletionData['fileReference'];
            $property = $deletionData['property'];

            $argumentValue = $argument->getValue();
            $propertyValue = ObjectAccess::getPropertyPath($argumentValue, $property);

            if ($propertyValue instanceof FileReference) {
                if ($propertyValue->getUid() === $fileReferenceUid) {
                    $argument->getFileHandlingServiceConfiguration()
                        ->registerFileDeletion($property, $fileReferenceUid);
                }
            } elseif ($propertyValue instanceof ObjectStorage) {
                foreach ($propertyValue as $fileReference) {
                    if ($fileReference instanceof FileReference && $fileReference->getUid() === $fileReferenceUid) {
                        $argument->getFileHandlingServiceConfiguration()
                            ->registerFileDeletion($property, $fileReferenceUid);
                    }
                }
            }
        }
    }

    public function applyDeletionsToArgument(Argument $argument): void
    {
        $fileUploadDeletionConfigurations = $argument->getFileHandlingServiceConfiguration()
            ->getFileUploadDeletionConfigurations();

        /** @var FileUploadDeletionConfiguration $fileUploadDeletionConfiguration */
        foreach ($fileUploadDeletionConfigurations as $fileUploadDeletionConfiguration) {
            $property = $fileUploadDeletionConfiguration->getPropertyName();
            foreach ($fileUploadDeletionConfiguration->getFileReferenceUids() as $fileReferenceUid) {
                $argumentValue = $argument->getValue();
                $propertyValue = ObjectAccess::getPropertyPath($argumentValue, $property);

                if ($propertyValue instanceof FileReference) {
                    if ($propertyValue->getUid() === $fileReferenceUid) {
                        $propertyValue->getOriginalResource()->getOriginalFile()->delete();
                        $propertyValue->getOriginalResource()->delete();
                        ObjectAccess::setProperty($argumentValue, $property, null);
                    }
                } elseif ($propertyValue instanceof ObjectStorage) {
                    foreach ($propertyValue as $fileReference) {
                        if ($fileReference instanceof FileReference && $fileReference->getUid() === $fileReferenceUid) {
                            $propertyValue->detach($fileReference);
                            $fileReference->getOriginalResource()->getOriginalFile()->delete();
                            $fileReference->getOriginalResource()->delete();
                        }
                    }
                    ObjectAccess::setProperty($argumentValue, $property, $propertyValue);
                }
            }
        }
    }

    /**
     * Checks if a property mapping error has occurred in given request.
     */
    private function hasMappingErrorOccurred(RequestInterface $request): bool
    {
        /** @var ExtbaseRequestParameters $extbaseRequestParameters */
        $extbaseRequestParameters = $request->getAttribute('extbase');
        return $extbaseRequestParameters->getOriginalRequest() !== null;
    }

    /**
     * Determines whether a property should be mapped for the given argument and property name.
     */
    private function shouldMapProperty(Argument $argument, string $propertyName): bool
    {
        if ($propertyName === '') {
            return false;
        }

        return $argument->getPropertyMappingConfiguration()->shouldMap($propertyName);
    }

    /**
     * Returns the target filename to use for the given client filename provided by the file upload.
     */
    private function getTargetFilename(string $clientFilename, FileUploadConfiguration $configuration): string
    {
        $targetFilename = $clientFilename;

        if ($configuration->isAddRandomSuffix()) {
            $pathInfo = pathinfo($targetFilename);
            $name = $pathInfo['filename'];
            $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
            $randomSuffix = GeneralUtility::makeInstance(Random::class)->generateRandomHexString(16);
            $targetFilename = $name . '-' . $randomSuffix . $extension;
        }

        $event = new ModifyUploadedFileTargetFilenameEvent(
            targetFilename: $targetFilename,
            configuration: $configuration
        );
        $this->eventDispatcher->dispatch($event);
        return $event->getTargetFilename();
    }

    /**
     * Ensures that upload folder exists, creates it if it does not and if automatic folder creation is defined
     * @throws FolderDoesNotExistException
     */
    private function provideUploadFolder(FileUploadConfiguration $configuration): Folder
    {
        $uploadFolderIdentifier = $configuration->getUploadFolder();
        try {
            return $this->resourceFactory->getFolderObjectFromCombinedIdentifier($uploadFolderIdentifier);
        } catch (FolderDoesNotExistException $exception) {
            if (!$configuration->isCreateUploadFolderIfNotExist()) {
                throw $exception;
            }

            [$storageId, $storagePath] = explode(':', $uploadFolderIdentifier, 2);
            $storage = $this->resourceFactory->getStorageObject((int)$storageId);

            if (!$storage->hasFolder($storagePath)) {
                $folder = $storage->createFolder($storagePath);
            } else {
                $folder = $storage->getFolder($storagePath);
            }

            return $folder;
        }
    }

    private function createCoreFileReference(FileInterface $file): CoreFileReference
    {
        if (!$file instanceof File) {
            throw new \RuntimeException('Given file must be a TYPO3\\CMS\\Core\\Resource.', 1712062607);
        }

        return $this->resourceFactory->createFileReferenceObject(
            [
                'uid_local' => $file->getUid(),
                'uid_foreign' => StringUtility::getUniqueId('NEW_'),
                'uid' => StringUtility::getUniqueId('NEW_'),
            ]
        );
    }

    private function createExtbaseFileReference(
        CoreFileReference $falFileReference
    ): FileReference {
        $fileReference = GeneralUtility::makeInstance(FileReference::class);
        $fileReference->setOriginalResource($falFileReference);
        return $fileReference;
    }
}
