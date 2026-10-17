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
        'byteSizeUnits' => [' Bytes| Kilobyte| Megabyte| Gigabyte', 'Byte size units string for "formatSize" function', 'string'],
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

    private function validateUploadedFile(UploadedFile $uploadedFile, ?int $index = null): void
    {
        $fileSize = $this->getFileInfo($uploadedFile->getTemporaryFileName())->getSize();

        $minFileSize = $this->getBytesFromOption('minimum');
        $maxFileSize = $this->getBytesFromOption('maximum');

        $labels = htmlspecialchars($this->options['byteSizeUnits']);
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

    /**
     * Checks if this validator is correctly configured
     */
    private function validateOptions(): void
    {
        if (!preg_match('/^(\d*\.?\d+)(B|K|M|G)$/i', $this->options['minimum'])) {
            throw new InvalidValidationOptionsException('The option "minimum" has an invalid format. Valid formats are something like this: "10B|K|M|G".', 1708595605);
        }
        if (!preg_match('/^(\d*\.?\d+)(B|K|M|G)$/i', $this->options['maximum'])) {
            throw new InvalidValidationOptionsException('The option "maximum" has an invalid format. Valid formats are something like this: "10B|K|M|G".', 1708595606);
        }
    }

    private function getBytesFromOption(string $optionName): int
    {
        $size = rtrim(strtoupper($this->options[$optionName]), 'B');
        if (!str_contains($size, '.')) {
            return ini_parse_quantity($size);
        }
        trigger_error(
            'Using decimal values like "' . $this->options[$optionName] . '" for the option "' . $optionName . '" of '
            . self::class . ' is deprecated since TYPO3 v15 and will stop working in TYPO3 v16.'
            . ' Use a whole number with a smaller unit instead, for example "1536K" instead of "1.5M".',
            E_USER_DEPRECATED
        );
        $bytes = (float)$size * match (substr($size, -1)) {
            'G' => 1024 ** 3,
            'M' => 1024 ** 2,
            'K' => 1024,
            default => 1,
        };
        return $bytes >= PHP_INT_MAX ? PHP_INT_MAX : (int)$bytes;
    }
}
