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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Type\File\ImageInfo;
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
        protected ?File $originalFile = null,
        protected ?ProcessedFile $processedFile = null
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

    public static function createFromProcessedFile(ProcessedFile $processedFile): self
    {
        return new self(
            width: (int)$processedFile->getProperty('width'),
            height: (int)$processedFile->getProperty('height'),
            extension: $processedFile->getExtension(),
            fullPath: $processedFile->getForLocalProcessing(false),
            publicUrl: $processedFile->getPublicUrl(),
            originalFile: $processedFile->getOriginalFile(),
            processedFile: $processedFile
        );
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function withWidth(int $width): self
    {
        $imageResource = clone $this;
        $imageResource->width = $width;
        return $imageResource;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function withHeight(int $height): self
    {
        $imageResource = clone $this;
        $imageResource->height = $height;
        return $imageResource;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function withExtension(string $extension): self
    {
        $imageResource = clone $this;
        $imageResource->extension = $extension;
        return $imageResource;
    }

    public function getFullPath(): string
    {
        return $this->fullPath;
    }

    public function withFullPath(string $fullPath): self
    {
        $imageResource = clone $this;
        $imageResource->fullPath = $fullPath;
        return $imageResource;
    }

    public function getPublicUrl(): ?string
    {
        return $this->publicUrl;
    }

    public function withPublicUrl(string $publicUrl): self
    {
        $imageResource = clone $this;
        $imageResource->publicUrl = $publicUrl;
        return $imageResource;
    }

    public function getOriginalFile(): ?File
    {
        return $this->originalFile;
    }

    public function withOriginalFile(?File $originalFile): self
    {
        $imageResource = clone $this;
        $imageResource->originalFile = $originalFile;
        return $imageResource;
    }

    public function getProcessedFile(): ?ProcessedFile
    {
        return $this->processedFile;
    }

    public function withProcessedFile(?ProcessedFile $processedFile): self
    {
        $imageResource = clone $this;
        $imageResource->processedFile = $processedFile;
        return $imageResource;
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
            'origFile_mtime' => $this->originalFile?->getModificationTime(),
        ];
    }
}
