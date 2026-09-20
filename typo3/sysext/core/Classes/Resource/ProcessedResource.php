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

use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\SystemResource\Exception\CanNotDetectImageDimensionOfSystemResourceException;
use TYPO3\CMS\Core\SystemResource\Exception\SystemResourceDoesNotExistException;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\Publishing\UriGenerationOptions;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\SystemResource\Type\PublicResourceInterface;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Representation of a specific processed version of a resource that is not backed by the
 * File Abstraction Layer - most commonly a system resource shipped with a package. This is
 * the non-FAL counterpart to ProcessedFile: instances are created by ProcessedResourceCache,
 * which also owns where a rendered variant physically lives - nothing outside that class
 * ever reads this object's local path.
 *
 * There is no database row backing this object: "does a current rendered variant already
 * exist" is answered by ProcessedResourceCache checking the filesystem, the same way
 * GraphicalFunctions::resize() already does for its own output, keyed by the original
 * resource's identifier and content hash rather than an unstable absolute path/mtime pair.
 *
 * @internal Not part of TYPO3's public API, use FileProcessingService instead
 */
final class ProcessedResource implements ProcessedResourceInterface
{
    private ?string $renderPath;
    private bool $isNew;
    private array $properties = [];

    public function __construct(
        private readonly ProcessedResourceCache $cache,
        private readonly SystemResourceInterface $originalResource,
        private readonly string $taskType,
        private readonly array $processingConfiguration,
        ?string $existingRenderPath = null,
        ?bool $isNew = null,
        array $properties = [],
    ) {
        $this->renderPath = $existingRenderPath;
        $this->isNew = $isNew ?? $existingRenderPath === null;
        $this->properties = $properties;
    }

    public function getOriginalResource(): SystemResourceInterface
    {
        return $this->originalResource;
    }

    public function getTaskIdentifier(): string
    {
        return $this->taskType;
    }

    public function getProcessingConfiguration(): array
    {
        return $this->processingConfiguration;
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }

    public function isProcessed(): bool
    {
        return !$this->isNew && !$this->needsReprocessing();
    }

    public function usesOriginalFile(): bool
    {
        return $this->renderPath === null;
    }

    public function exists(): bool
    {
        if ($this->usesOriginalFile()) {
            return true;
        }
        return is_file($this->renderPath);
    }

    /**
     * Given the content-addressed cache key ProcessedResourceCache computes this object's
     * render path from, a render that exists at all is, by construction, still current -
     * there is nothing further to compare against, unlike ProcessedFile::needsReprocessing(),
     * which has to detect a changed original against a stored sha1.
     */
    public function needsReprocessing(): bool
    {
        return !$this->usesOriginalFile() && !$this->exists();
    }

    public function delete(): bool
    {
        if ($this->renderPath !== null && is_file($this->renderPath)) {
            @unlink($this->renderPath);
        }
        $this->renderPath = null;
        return true;
    }

    public function setUsesOriginalFile(): void
    {
        $this->renderPath = null;
        $this->isNew = false;
    }

    public function setName(string $name): void
    {
        // The on-disk location of a rendered variant is entirely owned and computed by
        // ProcessedResourceCache from the original resource's identifier and content hash,
        // not from a human-readable name - there is nothing to set here.
    }

    public function updateWithLocalFile(string $localFilePath): void
    {
        $this->renderPath = $this->cache->persistRender($this, $localFilePath);
        $this->isNew = false;
    }

    /**
     * There is no database row to read width/height/size back from like ProcessedFile
     * does, so this falls back to deriving them from the render, or from the original
     * when it is used as-is.
     */
    public function getProperty(string $key): mixed
    {
        if (array_key_exists($key, $this->properties)) {
            return $this->properties[$key];
        }
        if (!$this->exists()) {
            return null;
        }
        try {
            return match ($key) {
                'width' => $this->getImageDimension()->getWidth(),
                'height' => $this->getImageDimension()->getHeight(),
                'size' => $this->getSize(),
                default => null,
            };
        } catch (SystemResourceDoesNotExistException|CanNotDetectImageDimensionOfSystemResourceException) {
            return null;
        }
    }

    public function updateProperties(array $properties): void
    {
        $this->properties = array_merge($this->properties, $properties);
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * A render lives in typo3temp/assets/, a public path of the app package, so its URL is
     * generated by the same publisher as the original's - honouring the site path and
     * config.absRefPrefix, which a FAL ProcessedFile only gets via PublicUrlPrefixer.
     * Cache busting is not needed for a render, its file name already is a content hash.
     */
    public function getPublicUrl(): ?string
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        $publisher = GeneralUtility::makeInstance(SystemResourcePublisherInterface::class);
        if ($this->renderPath !== null) {
            $renderResource = GeneralUtility::makeInstance(SystemResourceFactory::class)
                ->createPublicResource(PathUtility::stripPathSitePrefix($this->renderPath));
            return (string)$publisher->generateUri($renderResource, $request, new UriGenerationOptions(cacheBusting: false));
        }
        if (!$this->originalResource instanceof PublicResourceInterface || !$this->originalResource->isPublished()) {
            return null;
        }
        return (string)$publisher->generateUri($this->originalResource, $request);
    }

    public function getName(): string
    {
        if ($this->usesOriginalFile()) {
            return $this->originalResource->getName();
        }
        return basename($this->renderPath);
    }

    public function getNameWithoutExtension(): string
    {
        if ($this->usesOriginalFile()) {
            return $this->originalResource->getNameWithoutExtension();
        }
        return PathUtility::pathinfo($this->getName())['filename'] ?? $this->getName();
    }

    public function getExtension(): string
    {
        if ($this->usesOriginalFile()) {
            return $this->originalResource->getExtension();
        }
        return strtolower((string)(PathUtility::pathinfo($this->getName())['extension'] ?? ''));
    }

    public function getMimeType(): string
    {
        if ($this->usesOriginalFile()) {
            return $this->originalResource->getMimeType();
        }
        $mimeType = $this->getValidatedFileInfo()->getMimeType();
        if ($mimeType === false) {
            throw new SystemResourceDoesNotExistException(sprintf('Can not get mime type of processed resource "%s"', $this->getResourceIdentifier()), 1789785601);
        }
        return $mimeType;
    }

    public function getSize(): int
    {
        if ($this->usesOriginalFile()) {
            return strlen($this->originalResource->getContents());
        }
        return (int)$this->getValidatedFileInfo()->getSize();
    }

    public function getContents(): string
    {
        if ($this->usesOriginalFile()) {
            return $this->originalResource->getContents();
        }
        $content = file_get_contents($this->getValidatedFileInfo()->getPathname());
        if ($content === false) {
            throw new SystemResourceDoesNotExistException(sprintf('Can not get contents of processed resource "%s"', $this->getResourceIdentifier()), 1789785602);
        }
        return $content;
    }

    public function getHash(): string
    {
        if ($this->usesOriginalFile()) {
            return $this->originalResource->getHash();
        }
        return md5_file($this->getValidatedFileInfo()->getPathname());
    }

    public function isImage(): bool
    {
        if ($this->usesOriginalFile()) {
            return $this->originalResource->isImage();
        }
        return FileType::tryFromMimeType($this->getMimeType()) === FileType::IMAGE;
    }

    /**
     * Dimensions a processor stored for this resource take precedence, as
     * ProcessedFile does - e.g. an SVG which is reused as-is but rendered at
     * a different size.
     */
    public function getImageDimension(): ImageDimension
    {
        $width = (int)($this->properties['width'] ?? 0);
        $height = (int)($this->properties['height'] ?? 0);
        if ($width > 0 && $height > 0) {
            return new ImageDimension($width, $height);
        }
        if ($this->usesOriginalFile()) {
            return $this->originalResource->getImageDimension();
        }
        if (!$this->isImage()) {
            throw new CanNotDetectImageDimensionOfSystemResourceException(sprintf('Cannot determine image dimensions for processed resource "%s". File is not an image.', $this->getResourceIdentifier()), 1789785603);
        }
        $imageInfo = GeneralUtility::makeInstance(ImageInfo::class, $this->renderPath);
        $width = $imageInfo->getWidth();
        $height = $imageInfo->getHeight();
        if ($width === 0 || $height === 0) {
            throw new CanNotDetectImageDimensionOfSystemResourceException(sprintf('Cannot determine image dimensions for processed resource "%s".', $this->getResourceIdentifier()), 1789785604);
        }
        return new ImageDimension($width, $height);
    }

    public function getResourceIdentifier(): string
    {
        return $this->originalResource->getResourceIdentifier() . ':' . $this->taskType;
    }

    public function __toString(): string
    {
        return $this->getResourceIdentifier();
    }

    private function getValidatedFileInfo(): FileInfo
    {
        if ($this->renderPath === null || !is_file($this->renderPath)) {
            throw new SystemResourceDoesNotExistException(sprintf('Processed resource "%s" does not have a rendered variant on disk.', $this->getResourceIdentifier()), 1789785600);
        }
        return GeneralUtility::makeInstance(FileInfo::class, $this->renderPath);
    }
}
