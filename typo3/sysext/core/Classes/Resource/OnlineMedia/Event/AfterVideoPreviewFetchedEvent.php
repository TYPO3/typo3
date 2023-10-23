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

namespace TYPO3\CMS\Core\Resource\OnlineMedia\Event;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperInterface;

/**
 * Allows to modify a generated YouTube/Vimeo (or other Online Media) preview images
 */
final class AfterVideoPreviewFetchedEvent
{
    public function __construct(
        private readonly File $file,
        private readonly OnlineMediaHelperInterface $onlineMediaHelper,
        private string $previewImageFilename
    ) {}

    public function getFile(): File
    {
        return $this->file;
    }

    public function getOnlineMediaId(): string
    {
        return $this->onlineMediaHelper->getOnlineMediaId($this->file);
    }

    public function getPreviewImageFilename(): string
    {
        return $this->previewImageFilename;
    }

    public function setPreviewImageFilename(string $previewImageFilename): void
    {
        $this->previewImageFilename = $previewImageFilename;
    }
}
