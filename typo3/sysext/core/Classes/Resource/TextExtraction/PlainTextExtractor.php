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

use TYPO3\CMS\Core\Attribute\AsTextExtractor;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;

/**
 * A simple text extractor to extract text from plain text files.
 */
#[AsTextExtractor]
class PlainTextExtractor implements TextExtractorInterface, StructuredTextExtractorInterface
{
    /**
     * @return list<string>
     */
    public function getSupportedMimeTypes(): array
    {
        return ['text/plain'];
    }

    public function extractStructuredText(SystemResourceInterface $resource): ExtractedText
    {
        return new ExtractedText($resource->getContents());
    }

    /**
     * Checks if the given file can be read by this extractor
     */
    public function canExtractText(FileInterface $file): bool
    {
        return in_array($file->getMimeType(), $this->getSupportedMimeTypes(), true);
    }

    /**
     * The actual text extraction.
     */
    public function extractText(FileInterface $file): string
    {
        return $file->getContents();
    }
}
