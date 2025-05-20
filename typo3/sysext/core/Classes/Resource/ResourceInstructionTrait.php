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

namespace TYPO3\CMS\Core\Resource;

use Psr\Http\Message\UploadedFileInterface;
use TYPO3\CMS\Core\Resource\Service\ResourceConsistencyService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Trait for creating skip-instructions for `ResourceConsistencyService::validate()`.
 */
trait ResourceInstructionTrait
{
    /**
     * Registers an instruction to skip validation in `ResourceConsistencyService` for a specific uploaded file.
     */
    private function skipResourceConsistencyCheckForUploads(
        ResourceStorage $storage,
        array|UploadedFileInterface $uploadedFile,
        ?string $targetFileName = null,
    ): void {
        GeneralUtility::makeInstance(ResourceConsistencyService::class)->addExceptionItem(
            $storage,
            $storage->getUploadedLocalFilePath($uploadedFile),
            $storage->getUploadedTargetFileName($uploadedFile, $targetFileName),
        );
    }

    /**
     * Registers an instruction to skip validation in `ResourceConsistencyService`
     * for commands (such as rename or replace) for existing files.
     */
    private function skipResourceConsistencyCheckForCommands(
        ResourceStorage $storage,
        string|FileInterface $resource,
        ?string $targetFileName = null,
    ): void {
        $targetFileName ??= PathUtility::basename(
            $resource instanceof FileInterface ? $resource->getName() : $resource
        );
        GeneralUtility::makeInstance(ResourceConsistencyService::class)->addExceptionItem(
            $storage,
            $resource,
            $targetFileName
        );
    }
}
