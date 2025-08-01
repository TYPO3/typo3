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
use TYPO3\CMS\Core\Resource\MimeTypeCompatibilityTypeGuesser;
use TYPO3\CMS\Core\Resource\MimeTypeDetector;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Validator comparing file extension to the expected mime-type of a given UploadedFile
 * or ObjectStorage of UploadedFile objects.
 */
final class FileExtensionMimeTypeConsistencyValidator extends AbstractValidator
{
    protected string $notAllowedMessage = 'LLL:EXT:extbase/Resources/Private/Language/locallang.xlf:validation.fileextensionmimetypeconsistency.notallowed';
    protected array $translationOptions = ['inconsistentMessage'];

    /**
     * @var array
     */
    protected $supportedOptions = [
        'notAllowedMessage' => [null, 'Translation key or message for inconsistent mime-type for file extension', 'string'],
    ];

    public function isValid(mixed $value): void
    {
        $this->ensureFileUploadTypes($value);

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

        // The file extension of the uploaded file must match the mime-type for this file.
        // Example: myfile.txt is actually a PDF file (defined by mime-type), but .txt is not associated
        // for application/pdf, so this is not valid.
        $fileExtension =  pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $assumedMimesTypeOfFileExtension = (new MimeTypeDetector())->getMimeTypesForFileExtension($fileExtension);

        // pass, in case no assumed mime-type was found (e.g., for individual file extension)
        if ($mimeType === '' || $assumedMimesTypeOfFileExtension === []) {
            return;
        }

        // Example in case of "exe", which has over 9000 possible MIME types:
        // mime-db/db.json is only aware of "application/octet-stream", "application/x-msdos-program", "application/x-msdownload"
        // However, PHP detects this as "application/vnd.microsoft.portable-executable" (PHP 8.4+) or "application/x-dosexec" (PHP < 8.4)
        // DefaultConfiguration $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['mimeTypeCompatibility'] registers this (and some more),
        // so we also need these fallbacks to be evaluated.
        $mimeTypeCompatibility = (new MimeTypeCompatibilityTypeGuesser())->getMimeTypeCompatibilityList();
        $additionalMappedMimeType = $mimeTypeCompatibility[$mimeType][$fileExtension] ?? null;

        if (!in_array($mimeType, $assumedMimesTypeOfFileExtension, true) &&
            !in_array($additionalMappedMimeType, $assumedMimesTypeOfFileExtension, true)
        ) {
            $message = $this->translateErrorMessage(
                $this->notAllowedMessage,
                '',
                [$mimeType, $fileExtension]
            );
            $code = 1754045716;
            if ($index !== null) {
                $this->addErrorForProperty((string)$index, $message, $code);
            } else {
                $this->addError($message, $code);
            }
        }
    }
}
