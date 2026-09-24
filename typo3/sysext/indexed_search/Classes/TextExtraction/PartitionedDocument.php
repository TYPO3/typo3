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

namespace TYPO3\CMS\IndexedSearch\TextExtraction;

use TYPO3\CMS\Core\Resource\TextExtraction\ExtractedText;

/**
 * A document divided into parts, such as the page ranges of a PDF. It is only
 * valid within PartitionedTextExtractorInterface::withPartitionedDocument(),
 * which provides the local file all parts are extracted from.
 *
 * @internal
 */
final readonly class PartitionedDocument
{
    /**
     * @param list<string> $parts Pointers to the parts, for example "1-5" and "6-10", or a single "0" for an undivided document
     * @param \Closure(string): ExtractedText $partExtractor
     */
    public function __construct(
        public array $parts,
        private \Closure $partExtractor,
    ) {}

    /**
     * The text and metadata of a single part, one of $parts
     */
    public function extractPart(string $part): ExtractedText
    {
        return ($this->partExtractor)($part);
    }
}
