<?php
namespace TYPO3\CMS\Core\Resource\TextExtraction;

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

use TYPO3\CMS\Core\Resource\FileInterface;

/**
 * An interface for text extractors
 */
interface TextExtractorInterface
{
    /**
     * Checks if the given file can be read by this extractor
     *
     * @param FileInterface $file
     * @return bool
     */
    public function canExtractText(FileInterface $file);

    /**
     * The actual text extraction.
     *
     * Should return a string of the file's content
     *
     * @param FileInterface $file
     * @return string
     */
    public function extractText(FileInterface $file);
}
