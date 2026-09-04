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

namespace TYPO3Tests\TestMetadataExtraction\Resources\Metadata\Extractors;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;

class TextFileExtractor3 implements ExtractorInterface
{
    public function getFileTypeRestrictions(): array
    {
        return [FileType::TEXT];
    }

    public function getDriverRestrictions(): array
    {
        return ['anUnsettingDriverRestriction'];
    }

    public function getPriority(): int
    {
        return 200; // higher than TextFileExtractor1
    }

    public function getExecutionPriority(): int
    {
        return 20; // same as TextFileExtractor1
    }

    public function canProcess(File $file): bool
    {
        return true;
    }

    public function extractMetaData(File $file, array $previousExtractedData = []): array
    {
        // Deliberately empty, to unset metadata extracted by an extractor of lower priority
        return ['title' => ''];
    }
}
