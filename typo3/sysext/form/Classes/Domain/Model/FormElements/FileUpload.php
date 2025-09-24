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

namespace TYPO3\CMS\Form\Domain\Model\FormElements;

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;
use TYPO3\CMS\Form\Mvc\Property\TypeConverter\UploadedFileReferenceConverter;
use TYPO3\CMS\Form\Mvc\Validation\MimeTypeValidator;

/**
 * A generic file upload form element
 *
 * Scope: frontend
 */
class FileUpload extends AbstractFormElement
{
    /**
     * Initializes the Form Element by setting the data type to an Extbase File Reference
     * @internal
     */
    public function initializeFormElement()
    {
        $this->setDataType(FileReference::class);

        // Set the property mapping configuration for the file upload element.
        // * Add the UploadedFileReferenceConverter to convert an uploaded file to a
        //   FileReference.
        // * Add the MimeTypeValidator to the UploadedFileReferenceConverter to
        //   delete non-valid file types directly.
        // * Setup the storage:
        //   If the property "saveToFileMount" exist for this element it will be used.
        //   If this file mount or the property "saveToFileMount" does not exist
        //   the default storage "1:/user_uploads/" will be used. Uploads are placed
        //   in a dedicated sub-folder (e.g. ".../form_<40-chars-hash>/actual.file").
        $typeConverter = GeneralUtility::makeInstance(UploadedFileReferenceConverter::class);
        /** @var \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration $propertyMappingConfiguration */
        $propertyMappingConfiguration = $this->getRootForm()
            ->getProcessingRule($this->getIdentifier())
            ->getPropertyMappingConfiguration()
            ->setTypeConverter($typeConverter);

        $allowedMimeTypes = [];
        $validators = [];
        if (isset($this->getProperties()['allowedMimeTypes']) && \is_array($this->getProperties()['allowedMimeTypes'])) {
            $allowedMimeTypes = array_filter($this->getProperties()['allowedMimeTypes']);
        }
        if (!empty($allowedMimeTypes)) {
            $validatorResolver = GeneralUtility::makeInstance(ValidatorResolver::class);
            $mimeTypeValidator = $validatorResolver->createValidator(MimeTypeValidator::class, ['allowedMimeTypes' => $allowedMimeTypes]);
            $validators = [$mimeTypeValidator];
        }

        $this->getRootForm()
            ->getProcessingRule($this->getIdentifier())
            ->filterValidators(
                static function ($validator) use (&$validators) {
                    if ($validator instanceof NotEmptyValidator) {
                        return true;
                    }
                    $validators[] = $validator;
                    return false;
                }
            );

        $uploadConfiguration = [
            UploadedFileReferenceConverter::CONFIGURATION_FILE_VALIDATORS => $validators,
            UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
        ];

        $saveToFileMountIdentifier = $this->getProperties()['saveToFileMount'] ?? '';
        if ($this->checkSaveFileMountAccess($saveToFileMountIdentifier)) {
            $uploadConfiguration[UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER] = $saveToFileMountIdentifier;
        } else {
            // @todo Why should uploaded files be stored to the same directory as the *.form.yaml definitions?
            $persistenceIdentifier = $this->getRootForm()->getPersistenceIdentifier();
            if (!empty($persistenceIdentifier)) {
                $pathinfo = PathUtility::pathinfo($persistenceIdentifier);
                $saveToFileMountIdentifier = $pathinfo['dirname'];
                if ($this->checkSaveFileMountAccess($saveToFileMountIdentifier)) {
                    $uploadConfiguration[UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER] = $saveToFileMountIdentifier;
                }
            }
        }
        $propertyMappingConfiguration->setTypeConverterOptions(UploadedFileReferenceConverter::class, $uploadConfiguration);
    }

    /**
     * @internal
     */
    protected function checkSaveFileMountAccess(string $saveToFileMountIdentifier): bool
    {
        if (empty($saveToFileMountIdentifier)) {
            return false;
        }

        if (PathUtility::isExtensionPath($saveToFileMountIdentifier)) {
            return false;
        }

        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        try {
            $resourceFactory->getFolderObjectFromCombinedIdentifier($saveToFileMountIdentifier);
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }
}
