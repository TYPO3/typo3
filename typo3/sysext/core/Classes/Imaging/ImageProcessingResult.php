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

use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Decorator around ImageInfo.
 *
 * The main benefit over ImageInfo is
 * - Can have different values due to processing instructions being set, or "noScale" option being set.
 * - Can be filled with arbitrary information without the file path being accessed at all.
 * - The result object can be used to retrieve virtual or possible values, so the file does not need to exist (yet).
 *
 * @internal because this might only make sense if it used in the local environment.
 */
class ImageProcessingResult
{
    public function __construct(
        private readonly string $filePath,
        /**
         * @var int<0, max>
         */
        private readonly int $width,
        /**
         * @var int<0, max>
         */
        private readonly int $height,
        private readonly ?ImageInfo $imageInfo = null
    ) {}

    public function isFile(): bool
    {
        return $this->getImageInfoObject()->isFile();
    }

    public function getRealPath(): string
    {
        return $this->getImageInfoObject()->getRealPath();
    }

    /**
     * @return int<0, max>
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @return int<0, max>
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    public function getExtension(): string
    {
        return $this->getImageInfoObject()->getExtension();
    }

    public static function createFromImageInfo(ImageInfo $imageInfo): self
    {
        return new self($imageInfo->getRealPath(), $imageInfo->getWidth(), $imageInfo->getHeight(), $imageInfo);
    }

    /**
     * @return array{0: int<0, max>, 1: int<0, max>, 2: string, 3: string}
     */
    public function toLegacyArray(): array
    {
        return [
            0 => $this->width,
            1 => $this->height,
            2 => $this->getExtension(),
            3 => $this->filePath,
        ];
    }

    private function getImageInfoObject(): ImageInfo
    {
        return !$this->imageInfo instanceof ImageInfo
            ? GeneralUtility::makeInstance(ImageInfo::class, $this->filePath)
            : $this->imageInfo;
    }
}
