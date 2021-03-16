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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Form\Mvc\Property\TypeConverter\PseudoFile;
use TYPO3\CMS\Form\Mvc\Validation\Exception\InvalidValidationOptionsException;

/**
 * Validator for countable types
 *
 * Scope: frontend
 * @internal
 */
class FileSizeValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'minimum' => ['0B', 'The minimum file size to accept', 'string'],
        'maximum' => [PHP_INT_MAX . 'B', 'The maximum file size to accept', 'string']
    ];

    /**
     * The given value is valid
     *
     * @param FileReference|File|PseudoFile $resource
     */
    public function isValid($resource)
    {
        $this->validateOptions();
        if ($resource instanceof FileReference) {
            $fileSize = $resource->getOriginalResource()->getSize();
        } elseif ($resource instanceof File) {
            $fileSize = $resource->getSize();
        } elseif ($resource instanceof PseudoFile) {
            $fileSize = $resource->getSize();
        } else {
            $this->addError(
                $this->translateErrorMessage(
                    'validation.error.1505303626',
                    'form'
                ),
                1505303626
            );
            return;
        }

        $minFileSize = GeneralUtility::getBytesFromSizeMeasurement($this->options['minimum']);
        $maxFileSize = GeneralUtility::getBytesFromSizeMeasurement($this->options['maximum']);

        $labels = ' Bytes| Kilobyte| Megabyte| Gigabyte';
        if ($fileSize < $minFileSize) {
            $this->addError(
                $this->translateErrorMessage(
                    'validation.error.1505305752',
                    'form',
                    [GeneralUtility::formatSize($minFileSize, $labels)]
                ),
                1505305752,
                [GeneralUtility::formatSize($minFileSize, $labels)]
            );
        }
        if ($fileSize > $maxFileSize) {
            $this->addError(
                $this->translateErrorMessage(
                    'validation.error.1505305753',
                    'form',
                    [GeneralUtility::formatSize($maxFileSize, $labels)]
                ),
                1505305753,
                [GeneralUtility::formatSize($maxFileSize, $labels)]
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
        if (!preg_match('/^(\d*\.?\d+)(B|K|M|G)$/i', $this->options['minimum'])) {
            throw new InvalidValidationOptionsException('The option "minimum" has an invalid format. Valid formats are something like this: "10B|K|M|G".', 1505304205);
        }
        if (!preg_match('/^(\d*\.?\d+)(B|K|M|G)$/i', $this->options['maximum'])) {
            throw new InvalidValidationOptionsException('The option "maximum" has an invalid format. Valid formats are something like this: "10B|K|M|G".', 1505304206);
        }
    }
}
