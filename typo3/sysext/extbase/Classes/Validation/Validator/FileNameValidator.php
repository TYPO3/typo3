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
use TYPO3\CMS\Core\Resource\Security\FileNameValidator as CoreFileNameValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Validates, that the given UploadedFile or ObjectStorage of UploadedFile objects does not contain a php
 * executable file by checking the given file extension.
 */
final class FileNameValidator extends AbstractValidator
{
    protected string $message = 'LLL:EXT:extbase/Resources/Private/Language/locallang.xlf:validation.denyphpupload.notallowed';

    protected $supportedOptions = [
        'message' => [null, 'Translation key or message for invalid value', 'string'],
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
        if (!GeneralUtility::makeInstance(CoreFileNameValidator::class)->isValid($uploadedFile->getClientFilename())) {
            $message = $this->translateErrorMessage($this->message);
            $code = 1711367029;
            if ($index !== null) {
                $this->addErrorForProperty((string)$index, $message, $code);
            } else {
                $this->addError($message, $code);
            }
        }
    }
}
