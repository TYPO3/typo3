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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Mvc\Property\TypeConverter\UploadedFileReferenceConverter;

/**
 * A generic file upload form element
 *
 * Scope: frontend
 */
class FileUpload extends AbstractFormElement implements StringableFormElementInterface, ProcessableValueFormElementInterface
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
        //   FileReference (single upload) or ObjectStorage (multiple uploads).
        // * The upload folder is resolved in Mvc\Property\PropertyMappingConfiguration,
        //   once the element's own properties are set.
        $typeConverter = GeneralUtility::makeInstance(UploadedFileReferenceConverter::class);
        /** @var \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration $propertyMappingConfiguration */
        $propertyMappingConfiguration = $this->getRootForm()
            ->getProcessingRule($this->getIdentifier())
            ->getPropertyMappingConfiguration()
            ->setTypeConverter($typeConverter);

        $propertyMappingConfiguration->setTypeConverterOptions(UploadedFileReferenceConverter::class, [
            UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
        ]);
    }

    public function valueToString($value): string
    {
        if ($value instanceof ObjectStorage) {
            $fileNames = [];
            foreach ($value as $fileReference) {
                if ($fileReference instanceof FileReference) {
                    $fileNames[] = $fileReference->getOriginalResource()->getName();
                }
            }
            return implode(', ', $fileNames);
        }
        if ($value instanceof FileReference) {
            return $value->getOriginalResource()->getName();
        }
        return '';
    }

    public function processElementValue(mixed $value, FormRuntime $formRuntime): mixed
    {
        if ($value instanceof ObjectStorage || $value instanceof FileReference) {
            return $this->valueToString($value);
        }
        return $value;
    }
}
