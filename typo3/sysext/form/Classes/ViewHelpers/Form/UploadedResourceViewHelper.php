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

namespace TYPO3\CMS\Form\ViewHelpers\Form;

use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Property\PropertyMapper;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;
use TYPO3\CMS\Form\Security\HashScope;

/**
 * This ViewHelper makes the specified Image object available for its
 * childNodes.
 * In case the form is redisplayed because of validation errors, a previously
 * uploaded image will be correctly used.
 *
 * Scope: frontend
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-form-form-uploadedresource
 */
final class UploadedResourceViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'input';

    public function __construct(
        private readonly HashService $hashService,
        private readonly PropertyMapper $propertyMapper,
    ) {
        parent::__construct();
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('as', 'string', '');
        $this->registerArgument('accept', 'array', 'Values for the accept attribute', false, []);
        $this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this ViewHelper', false, 'f3-form-error');
        $this->registerArgument('multiple', 'boolean', 'Defines the upload element accepting multiple files', false, false);
    }

    public function render(): string
    {
        $output = '';

        $name = $this->getName();
        $as = $this->arguments['as'];
        $accept = $this->arguments['accept'];
        $multiple = $this->arguments['multiple'];
        $resource = $this->getUploadedResource();

        if (!empty($accept)) {
            $this->tag->addAttribute('accept', implode(',', $accept));
        }

        if ($resource !== null) {
            $resourcePointerIdAttribute = '';
            if (isset($this->additionalArguments['id'])) {
                $resourcePointerIdAttribute = ' id="' . htmlspecialchars($this->additionalArguments['id']) . '-file-reference"';
            }
            if ($resource instanceof FileReference) {
                $resourcePointerValue = $resource->getUid();
                if ($resourcePointerValue === null) {
                    $resourcePointerValue = 'file:' . $resource->getOriginalResource()->getOriginalFile()->getUid();
                }
                $output .= '<input type="hidden" name="' . htmlspecialchars($this->getName()) . '[__submittedFiles][0][submittedFile][resourcePointer]" value="' . htmlspecialchars($this->hashService->appendHmac((string)$resourcePointerValue, HashScope::ResourcePointer->prefix())) . '"' . $resourcePointerIdAttribute . ' />';
            } elseif ($resource instanceof ObjectStorage) {
                foreach ($resource as $index => $file) {
                    $resourcePointerValue = $file->getUid();
                    if ($resourcePointerValue === null) {
                        $resourcePointerValue = 'file:' . $file->getOriginalResource()->getOriginalFile()->getUid();
                    }
                    $output .= '<input type="hidden" name="' . htmlspecialchars($this->getName()) . '[__submittedFiles][' . $index . '][submittedFile][resourcePointer]" value="' . htmlspecialchars($this->hashService->appendHmac((string)$resourcePointerValue, HashScope::ResourcePointer->prefix())) . '"' . $resourcePointerIdAttribute . ' />';
                }
            }

            $this->templateVariableContainer->add($as, $resource);
            $output .= $this->renderChildren();
            $this->templateVariableContainer->remove($as);
        }

        foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $fieldName) {
            $this->registerFieldNameForFormTokenGeneration($name . '[' . $fieldName . ']');
        }
        $this->tag->addAttribute('type', 'file');

        if ($multiple === true) {
            $this->tag->addAttribute('name', $name . '[]');
            $this->tag->addAttribute('multiple', true);
        } else {
            $this->tag->addAttribute('name', $name);
        }

        $this->setErrorClassAttribute();
        $output .= $this->tag->render();

        return $output;
    }

    /**
     * Return a previously uploaded resource.
     * Return NULL if errors occurred during property mapping for this property.
     */
    private function getUploadedResource(): FileReference|ObjectStorage|null
    {
        if ($this->getMappingResultsForProperty()->hasErrors()) {
            return null;
        }
        $resource = $this->getValueAttribute();
        if ($resource instanceof ObjectStorage) {
            return $resource;
        }
        if ($resource instanceof FileReference) {
            // When multiple uploads are enabled but the stored value is a single
            // FileReference, wrap it in an ObjectStorage so that the Fluid template's
            // f:for ViewHelper receives an iterable instead of crashing.
            if ($this->arguments['multiple']) {
                $storage = new ObjectStorage();
                $storage->attach($resource);
                return $storage;
            }
            return $resource;
        }
        return $this->propertyMapper->convert($resource, FileReference::class);
    }
}
