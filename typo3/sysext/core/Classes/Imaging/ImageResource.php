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

namespace TYPO3\CMS\Core\Imaging;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedResourceInterface;
use TYPO3\CMS\Core\SystemResource\Exception\SystemResourceException;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * DTO for a resolved image resource. Mainly used by ContentObjectRenderer.
 *
 * @see ContentObjectRenderer::getImgResource()
 */
class ImageResource
{
    public function __construct(
        protected int $width,
        protected int $height,
        protected string $extension,
        protected string $fullPath,
        protected ?string $publicUrl = null,
        protected File|SystemResourceInterface|null $originalFile = null,
        protected ?ProcessedResourceInterface $processedFile = null
    ) {}

    public static function createFromImageInfo(ImageInfo $imageInfo): self
    {
        return new self(
            width: $imageInfo->getWidth(),
            height: $imageInfo->getHeight(),
            extension: $imageInfo->getExtension(),
            fullPath: $imageInfo->getPathname(),
            publicUrl: PathUtility::getAbsoluteWebPath($imageInfo->getPathname(), false),
        );
    }

    public static function createFromProcessedFile(ProcessedResourceInterface $processedFile): self
    {
        $imageDimension = self::getImageDimensionOfProcessedResource($processedFile);
        if ($processedFile instanceof ProcessedFile) {
            return new self(
                width: $imageDimension->getWidth(),
                height: $imageDimension->getHeight(),
                extension: $processedFile->getExtension(),
                fullPath: $processedFile->getForLocalProcessing(false),
                publicUrl: $processedFile->getPublicUrl(),
                originalFile: $processedFile->getOriginalFile(),
                processedFile: $processedFile
            );
        }
        return new self(
            width: $imageDimension->getWidth(),
            height: $imageDimension->getHeight(),
            extension: $processedFile->getExtension(),
            fullPath: self::createLocalCopyOfProcessedResource($processedFile),
            publicUrl: $processedFile->getPublicUrl(),
            originalFile: $processedFile->getOriginalResource(),
            processedFile: $processedFile,
        );
    }

    /**
     * A processing result without detectable dimensions (e.g. a non-image) keeps
     * being reported as 0x0, it is not an error at this point.
     */
    private static function getImageDimensionOfProcessedResource(ProcessedResourceInterface $processedResource): ImageDimension
    {
        try {
            return $processedResource->getImageDimension();
        } catch (SystemResourceException) {
            return new ImageDimension(0, 0);
        }
    }

    /**
     * A system resource has no local path of its own, but GIFBUILDER needs one to embed
     * it as an IMAGE sub-object. The path must stay stable across requests, since
     * GifBuilder folds it into its own output filename hash.
     */
    private static function createLocalCopyOfProcessedResource(ProcessedResourceInterface $processedResource): string
    {
        $path = Environment::getVarPath() . '/transient/system-resource-' . md5($processedResource->getResourceIdentifier() . $processedResource->getHash()) . '.' . $processedResource->getExtension();
        if (!is_file($path)) {
            GeneralUtility::mkdir_deep(dirname($path));
            GeneralUtility::writeFile($path, $processedResource->getContents(), true);
        }
        return $path;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function withWidth(int $width): self
    {
        return clone($this, ['width' => $width]);
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function withHeight(int $height): self
    {
        return clone($this, ['height' => $height]);
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function withExtension(string $extension): self
    {
        return clone($this, ['extension' => $extension]);
    }

    public function getFullPath(): string
    {
        return $this->fullPath;
    }

    public function withFullPath(string $fullPath): self
    {
        return clone($this, ['fullPath' => $fullPath]);
    }

    public function getPublicUrl(): ?string
    {
        return $this->publicUrl;
    }

    public function withPublicUrl(string $publicUrl): self
    {
        return clone($this, ['publicUrl' => $publicUrl]);
    }

    public function getOriginalFile(): File|SystemResourceInterface|null
    {
        return $this->originalFile;
    }

    public function withOriginalFile(File|SystemResourceInterface|null $originalFile): self
    {
        return clone($this, ['originalFile' => $originalFile]);
    }

    public function getProcessedFile(): ?ProcessedResourceInterface
    {
        return $this->processedFile;
    }

    public function withProcessedFile(?ProcessedResourceInterface $processedFile): self
    {
        return clone($this, ['processedFile' => $processedFile]);
    }

    /**
     * Legacy image resource information, used for asset collector and GifBuilder BBOX
     *
     * @return array{0: int<0, max>, 1: int<0, max>, 2: string, 3: string, 'origFile': string|null, 'origFile_mtime': int}
     */
    public function getLegacyImageResourceInformation(): array
    {
        return [
            0 => $this->width,
            1 => $this->height,
            2 => $this->extension,
            3 => $this->fullPath,
            'origFile' => $this->publicUrl,
            'origFile_mtime' => $this->originalFile instanceof File ? $this->originalFile->getModificationTime() : null,
        ];
    }
}
