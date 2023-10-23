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

namespace TYPO3\CMS\Core\Resource\Event;

use TYPO3\CMS\Core\Resource\File;

/**
 * This event is fired once an index was just updated inside the database (= indexed).
 * Custom listeners can update further index values when a file was updated.
 */
final class AfterFileUpdatedInIndexEvent
{
    public function __construct(
        private readonly File $file,
        private readonly array $properties,
        private readonly array $updatedFields
    ) {}

    public function getFile(): File
    {
        return $this->file;
    }

    public function getRelevantProperties(): array
    {
        return $this->properties;
    }

    public function getUpdatedFields(): array
    {
        return $this->updatedFields;
    }
}
