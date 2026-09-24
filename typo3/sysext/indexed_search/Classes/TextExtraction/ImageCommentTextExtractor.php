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

use TYPO3\CMS\Core\Attribute\AsTextExtractor;
use TYPO3\CMS\Core\Resource\TextExtraction\ExtractedText;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;

/**
 * Extracts the comment and the description of the EXIF data of images.
 *
 * @internal
 */
#[AsTextExtractor]
final class ImageCommentTextExtractor extends AbstractTextExtractor
{
    protected function getMimeTypes(): array
    {
        return ['image/jpeg', 'image/tiff'];
    }

    protected function extractFromLocalFile(SystemResourceInterface $file, string $localFilePath): ExtractedText
    {
        $exif = function_exists('exif_read_data') ? @exif_read_data($localFilePath, 'IFD0') : false;
        $comment = '';
        if ($exif) {
            $comment = trim(($exif['COMMENT'][0] ?? '') . ' ' . ($exif['ImageDescription'] ?? ''));
        }
        return new ExtractedText($comment, $file->getName());
    }
}
