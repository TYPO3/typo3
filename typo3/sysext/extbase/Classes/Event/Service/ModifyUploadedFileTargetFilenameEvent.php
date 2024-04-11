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

namespace TYPO3\CMS\Extbase\Event\Service;

use TYPO3\CMS\Extbase\Mvc\Controller\FileUploadConfiguration;

/**
 * Allows to modify the target filename of uploaded files when handled
 * by the extbase fileupload service
 */
final class ModifyUploadedFileTargetFilenameEvent
{
    public function __construct(
        private string $targetFilename,
        private readonly FileUploadConfiguration $configuration
    ) {}

    public function getTargetFilename(): string
    {
        return $this->targetFilename;
    }

    public function setTargetFilename(string $targetFilename): void
    {
        $this->targetFilename = $targetFilename;
    }

    public function getConfiguration(): FileUploadConfiguration
    {
        return $this->configuration;
    }
}
