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

namespace TYPO3\CMS\Core\SystemResource\Type;

use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\SystemResource\Exception\SystemResourceDoesNotExistException;
use TYPO3\CMS\Core\SystemResource\Identifier\PackageResourceIdentifier;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * @internal Only to be used in TYPO3\CMS\Core\SystemResource namespace
 */
class PackageResource implements SystemResourceInterface
{
    private ?FileInfo $fileInfo = null;

    public function __construct(
        protected readonly PackageInterface $package,
        protected readonly string $relativePath,
        protected readonly PackageResourceIdentifier $identifier,
    ) {}

    public function getName(): string
    {
        return PathUtility::pathinfo($this->relativePath)['basename'];
    }

    public function getNameWithoutExtension(): string
    {
        return PathUtility::pathinfo($this->relativePath)['filename'];
    }

    public function getExtension(): string
    {
        return PathUtility::pathinfo($this->relativePath)['extension'];
    }

    /**
     * @throws SystemResourceDoesNotExistException
     */
    public function getContents(): string
    {
        $fileInfo = $this->getValidatedFileInfo();
        $content = file_get_contents($fileInfo->getPathname());
        if ($content === false) {
            throw new SystemResourceDoesNotExistException(sprintf('Can not get contents from referenced system resource "%s" (resolved as "%s")', $this->identifier->givenIdentifier, $this), 1758714587);
        }
        return $content;
    }

    /**
     * @throws SystemResourceDoesNotExistException
     */
    public function getMimeType(): string
    {
        $fileInfo = $this->getValidatedFileInfo();
        $mimeType = $fileInfo->getMimeType();
        if ($mimeType === false) {
            throw new SystemResourceDoesNotExistException(sprintf('Can not get mime type from referenced system resource "%s" (resolved as "%s")', $this->identifier->givenIdentifier, $this), 1758786841);
        }
        return $mimeType;
    }

    /**
     * @throws SystemResourceDoesNotExistException
     */
    public function getHash(): string
    {
        $fileInfo = $this->getValidatedFileInfo();
        return md5_file($fileInfo->getPathname());
    }

    /**
     * @throws SystemResourceDoesNotExistException
     */
    private function getValidatedFileInfo(): FileInfo
    {
        if ($this->fileInfo !== null) {
            return $this->fileInfo;
        }
        $fileInfo = GeneralUtility::makeInstance(FileInfo::class, $this->package->getPackagePath() . $this->relativePath);
        if (!$fileInfo->isFile()) {
            throw new SystemResourceDoesNotExistException(sprintf('Referenced system resource "%s" (resolved as "%s") does not exist, or is not a file', $this->identifier->givenIdentifier, $this), 1758785343);
        }
        return $this->fileInfo = $fileInfo;
    }

    public function getResourceIdentifier(): string
    {
        return (string)$this->identifier;
    }

    public function __toString(): string
    {
        return $this->getResourceIdentifier();
    }
}
