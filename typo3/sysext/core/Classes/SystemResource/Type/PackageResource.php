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

use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Package\Resource\Definition\ResourceDefinitionInterface;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\SystemResource\Exception\CanNotDetectImageDimensionOfSystemResourceException;
use TYPO3\CMS\Core\SystemResource\Exception\SystemResourceDoesNotExistException;
use TYPO3\CMS\Core\SystemResource\Identifier\PackageResourceIdentifier;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * @internal Only to be used in TYPO3\CMS\Core\SystemResource namespace
 */
class PackageResource implements SystemResourceInterface
{
    private ?FileInfo $fileInfo = null;
    private ?ImageDimension $imageDimension = null;

    public function __construct(
        protected readonly PackageResourceIdentifier $identifier,
        protected readonly ResourceDefinitionInterface $resourceDefinition,
    ) {}

    public function getName(): string
    {
        return PathUtility::pathinfo($this->identifier->getRelativePath())['basename'];
    }

    public function getNameWithoutExtension(): string
    {
        return PathUtility::pathinfo($this->identifier->getRelativePath())['filename'];
    }

    public function getExtension(): string
    {
        return PathUtility::pathinfo($this->identifier->getRelativePath())['extension'] ?? '';
    }

    /**
     * Returns whether the referenced system resource is an image.
     *
     * @throws SystemResourceDoesNotExistException
     */
    public function isImage(): bool
    {
        return FileType::tryFromMimeType(
            $this->getMimeType(),
        ) === FileType::IMAGE;
    }

    /**
     * Returns the dimensions of the referenced image.
     *
     * @throws CanNotDetectImageDimensionOfSystemResourceException
     * @throws SystemResourceDoesNotExistException
     */
    public function getImageDimension(): ImageDimension
    {
        if (isset($this->imageDimension)) {
            return $this->imageDimension;
        }
        $imageInfo = $this->getValidatedImageInfo();
        $width = $imageInfo->getWidth();
        $height = $imageInfo->getHeight();
        if ($width === 0 || $height === 0) {
            throw new CanNotDetectImageDimensionOfSystemResourceException(sprintf('Cannot determine image dimensions for system resource "%s" (resolved as "%s")', $this->identifier->givenIdentifier, $this), 1787399730);
        }
        return $this->imageDimension = new ImageDimension($width, $height);
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
        $fileInfo = GeneralUtility::makeInstance(
            FileInfo::class,
            $this->identifier->getPackage()->getPackagePath() . $this->identifier->getRelativePath(),
        );
        if (!$fileInfo->isFile()) {
            throw new SystemResourceDoesNotExistException(sprintf('Referenced system resource "%s" (resolved as "%s") does not exist, or is not a file', $this->identifier->givenIdentifier, $this), 1758785343);
        }

        return $this->fileInfo = $fileInfo;
    }

    /**
     * @throws SystemResourceDoesNotExistException
     * @throws CanNotDetectImageDimensionOfSystemResourceException
     */
    private function getValidatedImageInfo(): ImageInfo
    {
        if ($this->fileInfo instanceof ImageInfo) {
            return $this->fileInfo;
        }
        if (!$this->isImage()) {
            throw new CanNotDetectImageDimensionOfSystemResourceException(sprintf('Cannot determine image dimensions for system resource "%s" (resolved as "%s"). File is not an image.', $this->identifier->givenIdentifier, $this), 1787397106);
        }
        return $this->fileInfo = GeneralUtility::makeInstance(
            ImageInfo::class,
            $this->identifier->getPackage()->getPackagePath() . $this->identifier->getRelativePath(),
        );
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
