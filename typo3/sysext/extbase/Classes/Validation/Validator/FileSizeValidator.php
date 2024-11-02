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

namespace TYPO3\CMS\Extbase\Validation\Validator;

use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;

/**
 * Validator for file size of a given UploadedFile or ObjectStorage of UploadedFile objects.
 */
final class FileSizeValidator extends AbstractValidator
{
    protected string $lessMessage = 'LLL:EXT:extbase/Resources/Private/Language/locallang.xlf:validation.filesize.less';
    protected string $exceedMessage = 'LLL:EXT:extbase/Resources/Private/Language/locallang.xlf:validation.filesize.exceed';

    protected array $translationOptions = ['lessMessage', 'exceedMessage'];

    /**
     * @var array
     */
    protected $supportedOptions = [
        'minimum' => ['0B', 'The minimum file size to accept', 'string'],
        'maximum' => [PHP_INT_MAX . 'B', 'The maximum file size to accept', 'string'],
        'lessMessage' => [null, 'Translation key or message for value less than minimum', 'string'],
        'exceedMessage' => [null, 'Translation key or message for value exceeds maximum', 'string'],
    ];

    public function isValid(mixed $value): void
    {
        $this->ensureFileUploadTypes($value);
        $this->validateOptions();

        if ($value instanceof UploadedFile) {
            $this->validateUploadedFile($value);
        } elseif ($value instanceof ObjectStorage && $value->count() > 0) {
            $index = 0;
            foreach ($value as $uploadedFile) {
                $this->validateUploadedFile($uploadedFile, $index);
                $index++;
            }
        }
    }

    protected function validateUploadedFile(UploadedFile $uploadedFile, ?int $index = null): void
    {
        $fileSize = $this->getFileInfo($uploadedFile->getTemporaryFileName())->getSize();

        $minFileSize = GeneralUtility::getBytesFromSizeMeasurement($this->options['minimum']);
        if ($this->options['maximum'] !== PHP_INT_MAX . 'B') {
            $maxFileSize = GeneralUtility::getBytesFromSizeMeasurement($this->options['maximum']);
        } else {
            $maxFileSize = PHP_INT_MAX;
        }

        $labels = ' Bytes| Kilobyte| Megabyte| Gigabyte';
        if ($fileSize < $minFileSize) {
            $message = $this->translateErrorMessage(
                $this->lessMessage,
                '',
                [GeneralUtility::formatSize($minFileSize, $labels)]
            );
            $code = 1708595754;
            if ($index !== null) {
                $this->addErrorForProperty((string)$index, $message, $code);
            } else {
                $this->addError($message, $code);
            }
        }
        if ($fileSize > $maxFileSize) {
            $message = $this->translateErrorMessage(
                $this->exceedMessage,
                '',
                [GeneralUtility::formatSize($maxFileSize, $labels)]
            );
            $code = 1708595755;
            if ($index !== null) {
                $this->addErrorForProperty((string)$index, $message, $code);
            } else {
                $this->addError($message, $code);
            }
        }
    }

    protected function getFileInfo(string $filePath): FileInfo
    {
        return GeneralUtility::makeInstance(FileInfo::class, $filePath);
    }

    /**
     * Checks if this validator is correctly configured
     */
    protected function validateOptions(): void
    {
        if (!preg_match('/^(\d*\.?\d+)(B|K|M|G)$/i', $this->options['minimum'])) {
            throw new InvalidValidationOptionsException('The option "minimum" has an invalid format. Valid formats are something like this: "10B|K|M|G".', 1708595605);
        }
        if (!preg_match('/^(\d*\.?\d+)(B|K|M|G)$/i', $this->options['maximum'])) {
            throw new InvalidValidationOptionsException('The option "maximum" has an invalid format. Valid formats are something like this: "10B|K|M|G".', 1708595606);
        }
    }
}
