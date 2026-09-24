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

/**
 * The textual content of a file, together with the metadata a text
 * extractor was able to read from it.
 */
final readonly class ExtractedText
{
    public function __construct(
        public string $text,
        public string $title = '',
        public string $description = '',
        public string $keywords = '',
    ) {}
}
