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

namespace TYPO3\CMS\Form\Mvc\Property;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime\Lifecycle\AfterFormStateInitializedInterface;
use TYPO3\CMS\Form\Mvc\Property\TypeConverter\UploadedFileReferenceConverter;

/**
 * Scope: frontend
 * @internal
 */
#[Autoconfigure(public: true)]
class PropertyMappingConfiguration implements AfterFormStateInitializedInterface
{
    /**
     * @param FormRuntime $formRuntime holding current form state and static form definition
     */
    public function afterFormStateInitialized(FormRuntime $formRuntime): void
    {
        foreach ($formRuntime->getFormDefinition()->getRenderablesRecursively() as $renderable) {
            $this->adjustPropertyMappingForFileUploadsAtRuntime($formRuntime, $renderable);
        }
    }

    /**
     * Adjusts property mapping configuration for file upload elements at runtime.
     *
     * At this point, form definition properties (from YAML) are fully available,
     * unlike in initializeFormElement() which runs before YAML properties are set.
     *
     * This sets:
     * - CONFIGURATION_UPLOAD_SEED: derived from the form session identifier
     *   for creating storage sub-folders.
     * - CONFIGURATION_ALLOW_REMOVAL: from the element's 'allowRemoval' property
     *   to enable HMAC-signed file deletion.
     */
    protected function adjustPropertyMappingForFileUploadsAtRuntime(
        FormRuntime $formRuntime,
        RenderableInterface $renderable
    ): void {
        if (!$renderable instanceof FileUpload
            || $formRuntime->getFormSession() === null
            || !$formRuntime->canProcessFormSubmission()
        ) {
            return;
        }
        $processingRule = $renderable->getRootForm()
            ->getProcessingRule($renderable->getIdentifier());

        if ($renderable->getProperties()['multiple'] ?? false) {
            $processingRule->setDataType(ObjectStorage::class);
        }

        $propertyMappingConfiguration = $processingRule->getPropertyMappingConfiguration();

        $propertyMappingConfiguration->setTypeConverterOption(
            UploadedFileReferenceConverter::class,
            UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED,
            $formRuntime->getFormSession()->getIdentifier()
        );

        $propertyMappingConfiguration->setTypeConverterOption(
            UploadedFileReferenceConverter::class,
            UploadedFileReferenceConverter::CONFIGURATION_ALLOW_REMOVAL,
            (bool)($renderable->getProperties()['allowRemoval'] ?? false)
        );
    }
}
