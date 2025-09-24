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
     * If the form runtime is able to process form submissions
     * (determined by $formRuntime->canProcessFormSubmission()) then a
     * 'form session' is available.
     * This form session identifier will be used to deriving storage sub-folders
     * for the file uploads.
     * This is done by setting `UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED`
     * type converter option.
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
        $renderable->getRootForm()
            ->getProcessingRule($renderable->getIdentifier())
            ->getPropertyMappingConfiguration()
            ->setTypeConverterOption(
                UploadedFileReferenceConverter::class,
                UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_SEED,
                $formRuntime->getFormSession()->getIdentifier()
            );
    }
}
