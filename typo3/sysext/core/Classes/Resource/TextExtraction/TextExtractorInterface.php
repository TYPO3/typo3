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

namespace TYPO3\CMS\Core\Resource\TextExtraction;

use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * Interface for text extractors, which are registered as tagged services
 * via the #[AsTextExtractor] attribute or the 'fal.text_extractor'
 * service tag.
 */
interface TextExtractorInterface
{
    /**
     * Checks if the given file can be read by this extractor
     */
    public function canExtractText(FileInterface $file): bool;

    /**
     * The actual text extraction, returning a string of the file's content
     */
    public function extractText(FileInterface $file): string;
}
