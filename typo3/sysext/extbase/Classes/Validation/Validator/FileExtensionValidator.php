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
 * Validator file extensions of a given UploadedFile or ObjectStorage of UploadedFile objects.
 */
final class FileExtensionValidator extends AbstractValidator
{
    protected string $notAllowedMessage = 'LLL:EXT:extbase/Resources/Private/Language/locallang.xlf:validation.fileextension.notallowed';
    protected array $translationOptions = ['notAllowedMessage'];

    /**
     * @var array
     */
    protected $supportedOptions = [
        'allowedFileExtensions' => [null, 'Allowed file extensions', 'array'],
        'useStorageDefaults' => [null, 'Whether to use the default allowed file extension of the storage', 'bool'],
        'notAllowedMessage' => [null, 'Translation key or message for disallowed file extension', 'string'],
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
        $allowedFileExtensions = [];
        if (!empty($this->options['useStorageDefaults'])) {
            $allowedFileExtensions = GeneralUtility::trimExplode(
                ',',
                ($GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'] ?? '') . ','
                . ($GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'] ?? '') . ','
                . ($GLOBALS['TYPO3_CONF_VARS']['SYS']['miscfile_ext'] ?? ''),
                true
            );
        }
        if (is_array($this->options['allowedFileExtensions'] ?? null)) {
            $allowedFileExtensions = array_merge($allowedFileExtensions, $this->options['allowedFileExtensions']);
        }
        $allowedFileExtensions = array_map(mb_strtolower(...), $allowedFileExtensions);
        $fileExtension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);

        if (!in_array($fileExtension, $allowedFileExtensions, true)) {
            $message = $this->translateErrorMessage(
                $this->notAllowedMessage,
                '',
                [$fileExtension]
            );
            $code = 1754043401;
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
    protected function validateOptions(): void
    {
        $hasAllowedFileExtensions = is_array($this->options['allowedFileExtensions'] ?? null)
            && $this->options['allowedFileExtensions'] !== [];
        $shallUseStorageDefaults = !empty($this->options['useStorageDefaults']);

        if (!$hasAllowedFileExtensions && !$shallUseStorageDefaults) {
            throw new InvalidValidationOptionsException(
                'Either the option "allowedFileExtensions" must be an array with at least one item, '
                    . 'or the option "useStorageDefaults" must be enabled.',
                1754043328
            );
        }
    }
}
