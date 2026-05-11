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

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Validation\ValidatorResolver;
use TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Event\AfterFormStateInitializedEvent;
use TYPO3\CMS\Form\Mvc\ProcessingRule;
use TYPO3\CMS\Form\Mvc\Property\TypeConverter\UploadedFileReferenceConverter;
use TYPO3\CMS\Form\Mvc\Validation\MimeTypeValidator;

/**
 * Scope: frontend
 * @internal
 */
#[AsEventListener(identifier: 'form/property-mapping-configuration')]
class PropertyMappingConfiguration
{
    public function __invoke(AfterFormStateInitializedEvent $event): void
    {
        foreach ($event->formRuntime->getFormDefinition()->getRenderablesRecursively() as $renderable) {
            $this->adjustPropertyMappingForFileUploadsAtRuntime($event->formRuntime, $renderable);
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
     *
     * It also registers the MimeTypeValidator based on the 'allowedMimeTypes'
     * property. This must happen here (and not in FileUpload::initializeFormElement())
     * because the concrete form definition properties are only available at runtime.
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

        $this->registerMimeTypeValidator($processingRule, $renderable);

        $propertyMappingConfiguration = $processingRule->getPropertyMappingConfiguration();

        // Pass all registered validators to the TypeConverter so they can run on the
        // PseudoFile *before* the file is written to FAL storage. This prevents invalid
        // files (wrong MIME type, oversized, etc.) from ever being persisted.
        // All validators are forwarded — not only ObjectStorageElementValidatorInterface
        // ones — because that interface only controls per-element fan-out inside
        // ProcessingRule::process() for ObjectStorage values; it is unrelated to whether
        // a validator is meaningful at the individual-file level. Validators that do not
        // handle PseudoFile (e.g. NotEmptyValidator) treat it as a non-null value and
        // return valid, which is the correct behaviour at this stage.
        $preStorageValidators = iterator_to_array($processingRule->getValidators());
        if ($preStorageValidators !== []) {
            $propertyMappingConfiguration->setTypeConverterOption(
                UploadedFileReferenceConverter::class,
                UploadedFileReferenceConverter::CONFIGURATION_PRE_STORAGE_VALIDATORS,
                $preStorageValidators
            );
        }

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

    /**
     * Registers the MimeTypeValidator for the given file upload element based on
     * its 'allowedMimeTypes' property.
     *
     * The validator is only added once, even if this method is called multiple
     * times during a request.
     */
    protected function registerMimeTypeValidator(
        ProcessingRule $processingRule,
        FileUpload $renderable
    ): void {
        $allowedMimeTypes = [];
        if (is_array($renderable->getProperties()['allowedMimeTypes'] ?? null)) {
            $allowedMimeTypes = array_filter($renderable->getProperties()['allowedMimeTypes']);
        }
        if ($allowedMimeTypes === []) {
            return;
        }

        foreach ($processingRule->getValidators() as $validator) {
            if ($validator instanceof MimeTypeValidator) {
                return;
            }
        }

        $mimeTypeValidator = GeneralUtility::makeInstance(ValidatorResolver::class)
            ->createValidator(MimeTypeValidator::class, ['allowedMimeTypes' => $allowedMimeTypes]);
        $processingRule->addValidator($mimeTypeValidator);
    }
}
