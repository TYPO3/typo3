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

namespace TYPO3Tests\TestTextExtractor\TextExtraction;

use TYPO3\CMS\Core\Attribute\AsTextExtractor;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\TextExtraction\TextExtractorInterface;

#[AsTextExtractor(priority: 10)]
final class HighPriorityPlainTextExtractor implements TextExtractorInterface
{
    public function canExtractText(FileInterface $file): bool
    {
        return $file->getMimeType() === 'text/plain';
    }

    public function extractText(FileInterface $file): string
    {
        return 'high-priority-text';
    }
}
