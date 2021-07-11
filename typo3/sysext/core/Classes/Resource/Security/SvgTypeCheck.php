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

namespace TYPO3\CMS\Core\Resource\Security;

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\MimeTypeDetector;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SvgTypeCheck
{
    protected const MIME_TYPES = ['image/svg', 'image/svg+xml', 'application/svg', 'application/svg+xml'];

    /**
     * @var MimeTypeDetector
     */
    protected $mimeTypeDetector;

    /**
     * @var string[]
     */
    protected $fileExtensions;

    public function __construct(MimeTypeDetector $mimeTypeDetector)
    {
        $this->mimeTypeDetector = $mimeTypeDetector;
        $this->fileExtensions = $this->resolveFileExtensions();
    }

    public function forFilePath(string $filePath): bool
    {
        $fileInfo = GeneralUtility::makeInstance(FileInfo::class, $filePath);
        $fileExtension = $fileInfo->getExtension();
        $mimeType = $fileInfo->getMimeType();
        return in_array($fileExtension, $this->fileExtensions, true)
            || in_array($mimeType, self::MIME_TYPES, true);
    }

    public function forResource(FileInterface $file): bool
    {
        $fileExtension = $file->getExtension();
        $mimeType = $file->getMimeType();
        return in_array($fileExtension, $this->fileExtensions, true)
            || in_array($mimeType, self::MIME_TYPES, true);
    }

    /**
     * @return string[]
     */
    protected function resolveFileExtensions(): array
    {
        $fileExtensions = array_map(
            function (string $mimeType): array {
                return $this->mimeTypeDetector->getFileExtensionsForMimeType($mimeType);
            },
            self::MIME_TYPES
        );
        $fileExtensions = array_filter($fileExtensions);
        return count($fileExtensions) > 0 ? array_unique(array_merge(...$fileExtensions)) : [];
    }
}
