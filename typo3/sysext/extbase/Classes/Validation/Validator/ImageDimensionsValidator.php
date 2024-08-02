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
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;

/**
 * Validator to validate image dimensions of a given UploadedFile or ObjectStorage of UploadedFile objects.
 */
final class ImageDimensionsValidator extends AbstractValidator
{
    protected string $widthMessage = 'LLL:EXT:extbase/Resources/Private/Language/locallang.xlf:validation.imagedimensions.width.notvalid';
    protected string $heightMessage = 'LLL:EXT:extbase/Resources/Private/Language/locallang.xlf:validation.imagedimensions.height.notvalid';
    protected string $minWidthMessage = 'LLL:EXT:extbase/Resources/Private/Language/locallang.xlf:validation.imagedimensions.minwidth.notvalid';
    protected string $minHeightMessage = 'LLL:EXT:extbase/Resources/Private/Language/locallang.xlf:validation.imagedimensions.minheight.notvalid';
    protected string $maxWidthMessage = 'LLL:EXT:extbase/Resources/Private/Language/locallang.xlf:validation.imagedimensions.maxwidth.notvalid';
    protected string $maxHeightMessage = 'LLL:EXT:extbase/Resources/Private/Language/locallang.xlf:validation.imagedimensions.maxheight.notvalid';

    /**
     * @var array
     */
    protected $supportedOptions = [
        'width' => [null, 'The exact width of the image', 'int'],
        'height' => [null, 'The exact height of the image', 'int'],
        'minWidth' => [0, 'The minimum width of the image', 'int'],
        'maxWidth' => [PHP_INT_MAX, 'The maximum width of the image', 'int'],
        'minHeight' => [0, 'The minimum height of the image', 'int'],
        'maxHeight' => [PHP_INT_MAX, 'The maximum heigt of the image', 'int'],
        'heightMessage' => [null, 'Translation key or message for invalid height', 'string'],
        'widthMessage' => [null, 'Translation key or message for invalid width', 'string'],
        'minWidthMessage' => [null, 'Translation key or message for invalid minimum width', 'string'],
        'maxWidthMessage' => [null, 'Translation key or message for invalid maximum width', 'string'],
        'minHeightMessage' => [null, 'Translation key or message for invalid minimum height', 'string'],
        'maxHeightMessage' => [null, 'Translation key or message for invalid maximum height', 'string'],
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
        $imageInfo = $this->getImageInfo($uploadedFile->getTemporaryFileName());
        if ($imageInfo->getWidth() === 0 || $imageInfo->getHeight() === 0) {
            // Silently ignore files, where the width or height could not be determined. Most likely no image file.
            return;
        }

        if (isset($this->options['width']) && (int)$this->options['width'] !== $imageInfo->getWidth()) {
            $message = $this->translateErrorMessage(
                $this->widthMessage,
                '',
                [$this->options['width']]
            );
            $code = 1715964040;
            if ($index !== null) {
                $this->addErrorForProperty((string)$index, $message, $code);
            } else {
                $this->addError($message, $code);
            }
        }

        if (isset($this->options['height']) && (int)$this->options['height'] !== $imageInfo->getHeight()) {
            $message = $this->translateErrorMessage(
                $this->heightMessage,
                '',
                [$this->options['height']]
            );
            $code = 1715964041;
            if ($index !== null) {
                $this->addErrorForProperty((string)$index, $message, $code);
            } else {
                $this->addError($message, $code);
            }
        }

        if ((int)$this->options['minWidth'] > $imageInfo->getWidth()) {
            $message = $this->translateErrorMessage(
                $this->minWidthMessage,
                '',
                [$this->options['minWidth']]
            );
            $code = 1715964042;
            if ($index !== null) {
                $this->addErrorForProperty((string)$index, $message, $code);
            } else {
                $this->addError($message, $code);
            }
        }

        if ((int)$this->options['minHeight'] > $imageInfo->getHeight()) {
            $message = $this->translateErrorMessage(
                $this->minHeightMessage,
                '',
                [$this->options['minHeight']]
            );
            $code = 1715964043;
            if ($index !== null) {
                $this->addErrorForProperty((string)$index, $message, $code);
            } else {
                $this->addError($message, $code);
            }
        }

        if ((int)$this->options['maxWidth'] < $imageInfo->getWidth()) {
            $message = $this->translateErrorMessage(
                $this->maxWidthMessage,
                '',
                [$this->options['maxWidth']]
            );
            $code = 1715964044;
            if ($index !== null) {
                $this->addErrorForProperty((string)$index, $message, $code);
            } else {
                $this->addError($message, $code);
            }
        }

        if ((int)$this->options['maxHeight'] < $imageInfo->getHeight()) {
            $message = $this->translateErrorMessage(
                $this->maxHeightMessage,
                '',
                [$this->options['maxHeight']]
            );
            $code = 1715964045;
            if ($index !== null) {
                $this->addErrorForProperty((string)$index, $message, $code);
            } else {
                $this->addError($message, $code);
            }
        }
    }

    protected function getImageInfo(string $filePath): ImageInfo
    {
        return GeneralUtility::makeInstance(ImageInfo::class, $filePath);
    }

    /**
     * Checks if this validator is correctly configured
     */
    protected function validateOptions(): void
    {
        if ((int)$this->options['minWidth'] > (int)$this->options['maxWidth']) {
            throw new InvalidValidationOptionsException('The option "minWidth" must not be greater than "maxWidth"', 1716008127);
        }
        if ((int)$this->options['minHeight'] > (int)$this->options['maxHeight']) {
            throw new InvalidValidationOptionsException('The option "minHeight" must not be greater than "maxHeight"', 1716008128);
        }
    }
}
