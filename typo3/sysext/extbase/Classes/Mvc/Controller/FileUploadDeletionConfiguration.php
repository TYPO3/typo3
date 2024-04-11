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

namespace TYPO3\CMS\Extbase\Mvc\Controller;

/**
 * @internal Only to be used within Extbase, not part of TYPO3 Core API.
 */
class FileUploadDeletionConfiguration
{
    public function __construct(protected readonly string $propertyName, protected array $fileReferenceUids = []) {}

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function addFileReferenceUid(int $fileReferenceUid): void
    {
        $this->fileReferenceUids[] = $fileReferenceUid;
    }

    public function getFileReferenceUids(): array
    {
        return $this->fileReferenceUids;
    }
}
