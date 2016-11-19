<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Domain\Model\FormElements;

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

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
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
     * @return void
     * @internal
     */
    public function initializeFormElement()
    {
        $this->setDataType('TYPO3\CMS\Extbase\Domain\Model\FileReference');
    }

    /**
     * Set the property mapping configuration for the file upload element.
     * * Add the UploadedFileReferenceConverter to convert an uploaded file to an
     *   FileReference.
     * * Add the MimeTypeValidator to the UploadedFileReferenceConverter to
     *   delete non valid filetypes directly.
     * * Setup the storage:
     *   If the property "saveToFileMount" exist for this element it will be used.
     *   If this file mount or the property "saveToFileMount" does not exist
     *   the folder in which the form definition lies (persistence identifier) will be used.
     *   If the form is generated programmatically and therefore no
     *   persistence identifier exist the default storage "1:/user_upload/" will be used.
     *
     * @return void
     * @internal
     * @todo: could we find a not so ugly solution for that?
     */
    public function onBuildingFinished()
    {
        /** @var \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration $propertyMappingConfiguration */
        $propertyMappingConfiguration = $this->getRootForm()->getProcessingRule($this->getIdentifier())->getPropertyMappingConfiguration();

        $mimeTypeValidator = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(MimeTypeValidator::class, ['allowedMimeTypes' => $this->properties['allowedMimeTypes']]);
        $uploadConfiguration = [
            UploadedFileReferenceConverter::CONFIGURATION_FILE_VALIDATORS => [$mimeTypeValidator],
            UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_CONFLICT_MODE => 'rename',
        ];

        $saveToFileMountIdentifier = (isset($this->properties['saveToFileMount'])) ? $this->properties['saveToFileMount'] : null;
        if ($this->checkSaveFileMountAccess($saveToFileMountIdentifier)) {
            $uploadConfiguration[UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER] = $saveToFileMountIdentifier;
        } else {
            $persistenceIdentifier = $this->getRootForm()->getPersistenceIdentifier();
            if (!empty($persistenceIdentifier)) {
                $pathinfo = PathUtility::pathinfo($persistenceIdentifier);
                $saveToFileMountIdentifier  = $pathinfo['dirname'];
                if ($this->checkSaveFileMountAccess($saveToFileMountIdentifier)) {
                    $uploadConfiguration[UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER] = $saveToFileMountIdentifier;
                }
            }
        }

        $propertyMappingConfiguration->setTypeConverterOptions(UploadedFileReferenceConverter::class, $uploadConfiguration);
    }

    /**
     * @param string $saveToFileMountIdentifier
     * @return bool
     * @internal
     */
    protected function checkSaveFileMountAccess(string $saveToFileMountIdentifier): bool
    {
        if (empty($saveToFileMountIdentifier)) {
            return false;
        }

        $resourceFactory = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(ResourceFactory::class);

        try {
            $resourceFactory->getFolderObjectFromCombinedIdentifier($saveToFileMountIdentifier);
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }
}
