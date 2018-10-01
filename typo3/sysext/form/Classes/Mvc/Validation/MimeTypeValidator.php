<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Mvc\Validation;

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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Form\Mvc\Validation\Exception\InvalidValidationOptionsException;

/**
 * Validator for mime types
 *
 * Scope: frontend
 */
class MimeTypeValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'allowedMimeTypes' => [null, 'Allowed mime types (using */* IANA media types)', 'array', true]
    ];

    /**
     * The given $value is valid if it is an FileReference of the
     * configured type (one of the image/* IANA media subtypes)
     *
     * Note: a value of NULL or empty string ('') is considered valid
     *
     * @param FileReference|File $resource The resource that should be validated
     */
    public function isValid($resource)
    {
        $this->validateOptions();

        if ($resource instanceof FileReference) {
            $resource = $resource->getOriginalResource();
        } elseif (!$resource instanceof File) {
            $this->addError(
                $this->translateErrorMessage(
                    'validation.error.1471708997',
                    'form'
                ),
                1471708997
            );
            return;
        }

        $allowedMimeTypes = $this->options['allowedMimeTypes'];
        if (!in_array($resource->getMimeType(), $allowedMimeTypes, true)) {
            $this->addError(
                $this->translateErrorMessage(
                    'validation.error.1471708998',
                    'form',
                    [$resource->getMimeType()]
                ),
                1471708998
            );
        }
    }

    /**
     * Checks if this validator is correctly configured
     *
     * @throws InvalidValidationOptionsException if the configured validation options are incorrect
     */
    protected function validateOptions()
    {
        if (!is_array($this->options['allowedMimeTypes']) || $this->options['allowedMimeTypes'] === []) {
            throw new InvalidValidationOptionsException('The option "allowedMimeTypes" must be an array with at least one item.', 1471713296);
        }
    }
}
