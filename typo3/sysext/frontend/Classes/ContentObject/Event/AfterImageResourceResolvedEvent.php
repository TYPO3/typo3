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

namespace TYPO3\CMS\Frontend\ContentObject\Event;

use TYPO3\CMS\Core\Imaging\ImageResource;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;

/**
 * Listeners are able to modify the resolved ContentObjectRenderer->getImgResource() result
 */
final class AfterImageResourceResolvedEvent
{
    public function __construct(
        private readonly string|File|FileReference $file,
        private readonly array $fileArray,
        private ?ImageResource $imageResource
    ) {}

    public function getFile(): string|File|FileReference
    {
        return $this->file;
    }

    public function getFileArray(): array
    {
        return $this->fileArray;
    }

    public function getImageResource(): ?ImageResource
    {
        return $this->imageResource;
    }

    public function setImageResource(?ImageResource $imageResource): void
    {
        $this->imageResource = $imageResource;
    }
}
