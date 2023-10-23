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

namespace TYPO3\CMS\Backend\Form\Event;

use TYPO3\CMS\Core\Resource\File;

/**
 * Listeners to this Event will be able to modify the preview url, used in the ImageManipulation element
 */
final class ModifyImageManipulationPreviewUrlEvent
{
    private string $previewUrl = '';

    public function __construct(
        private readonly array $databaseRow,
        private readonly array $fieldConfiguration,
        private readonly File $file,
    ) {}

    public function getDatabaseRow(): array
    {
        return $this->databaseRow;
    }

    public function getFieldConfiguration(): array
    {
        return $this->fieldConfiguration;
    }

    public function getFile(): File
    {
        return $this->file;
    }

    public function getPreviewUrl(): string
    {
        return $this->previewUrl;
    }

    public function setPreviewUrl(string $previewUrl): void
    {
        $this->previewUrl = $previewUrl;
    }
}
