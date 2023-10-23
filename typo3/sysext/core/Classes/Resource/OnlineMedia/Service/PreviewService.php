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

namespace TYPO3\CMS\Core\Resource\OnlineMedia\Service;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;

/**
 * Service for handling the preview of online media assets
 */
class PreviewService
{
    public function __construct(
        protected readonly OnlineMediaHelperRegistry $onlineMediaHelperRegistry,
        protected readonly ProcessedFileRepository $processedFileRepository
    ) {}

    public function updatePreviewImage(File $file): string
    {
        if (!$this->onlineMediaHelperRegistry->hasOnlineMediaHelper($file->getExtension())) {
            throw new \InvalidArgumentException('No online media helper exists for extension ' . $file->getExtension(), 1695130495);
        }

        $onlineMediaHelper = $this->onlineMediaHelperRegistry->getOnlineMediaHelper($file);

        // Remove the current preview image to force regeneration on calling getPreviewImage() again
        if (file_exists($previewImage = $onlineMediaHelper->getPreviewImage($file))) {
            // Remove preview image and processed files
            unlink($previewImage);
            foreach ($this->processedFileRepository->findAllByOriginalFile($file) as $processedFile) {
                $processedFile->delete();
            }
        }

        // Force regeneration of the preview image and return the path
        return $onlineMediaHelper->getPreviewImage($file);
    }
}
