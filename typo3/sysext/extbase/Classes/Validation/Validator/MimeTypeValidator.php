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
use TYPO3\CMS\Core\Resource\MimeTypeDetector;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;

/**
 * Validator for mime types of a given UploadedFile or ObjectStorage of UploadedFile objects. Does also validate, if
 * the given file extension matches allowed file extensions for the detected mime type.
 */
final class MimeTypeValidator extends AbstractValidator
{
    protected string $notAllowedMessage = 'LLL:EXT:extbase/Resources/Private/Language/locallang.xlf:validation.mimetype.notallowed';
    protected string $invalidExtensionMessage = 'LLL:EXT:extbase/Resources/Private/Language/locallang.xlf:validation.mimetype.invalidextension';

    protected array $translationOptions = ['notAllowedMessage', 'invalidExtensionMessage'];

    /**
     * @var array
     */
    protected $supportedOptions = [
        'allowedMimeTypes' => [null, 'Allowed mime types (using */* IANA media types)', 'array', true],
        'ignoreFileExtensionCheck' => [false, 'If set to "true", it is checked, the file extension check is disabled', 'boolean'],
        'notAllowedMessage' => [null, 'Translation key or message for not allowed MIME type', 'string'],
        'invalidExtensionMessage' => [null, 'Translation key or message for invalid file extension', 'string'],
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
        $fileInfo = $this->getFileInfo($uploadedFile->getTemporaryFileName());
        $mimeType = $fileInfo->getMimeType();

        $allowedMimeTypes = $this->options['allowedMimeTypes'];
        $ignoreFileExtensionCheck = $this->options['ignoreFileExtensionCheck'];

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            $message = $this->translateErrorMessage(
                $this->notAllowedMessage,
                '',
                [$mimeType]
            );
            $code = 1708538973;
            if ($index !== null) {
                $this->addErrorForProperty((string)$index, $message, $code);
            } else {
                $this->addError($message, $code);
            }
        }

        if (!$ignoreFileExtensionCheck && !$this->result->hasErrors()) {
            // The file extension of the uploaded file must match the mime-type for this file.
            // Example: myfile.txt is actually a PDF file (defined by mime-type), but .txt is not associated
            // for application/pdf, so this is not valid.
            $fileExtension =  pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
            $assumedMimesTypeOfFileExtension = (new MimeTypeDetector())->getMimeTypesForFileExtension($fileExtension);
            if (empty(array_intersect($allowedMimeTypes, $assumedMimesTypeOfFileExtension))) {
                $message = $this->translateErrorMessage(
                    $this->invalidExtensionMessage,
                    '',
                    [$fileExtension]
                );
                $code = 1718469466;
                if ($index !== null) {
                    $this->addErrorForProperty((string)$index, $message, $code);
                } else {
                    $this->addError($message, $code);
                }
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
        if (!is_array($this->options['allowedMimeTypes'] ?? false) || $this->options['allowedMimeTypes'] === []) {
            throw new InvalidValidationOptionsException('The option "allowedMimeTypes" must be an array with at least one item.', 1708526223);
        }
    }
}
