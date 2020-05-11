<?php

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
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * A simple text extractor to extract text from plain text files.
 */
class PlainTextExtractor implements TextExtractorInterface
{
    /**
     * Checks if the given file can be read by this extractor
     *
     * @param FileInterface $file
     * @return bool
     */
    public function canExtractText(FileInterface $file)
    {
        $canExtract = false;

        if ($file->getMimeType() === 'text/plain') {
            $canExtract = true;
        }

        return $canExtract;
    }

    /**
     * The actual text extraction.
     *
     * @param FileInterface $file
     * @return string
     */
    public function extractText(FileInterface $file)
    {
        $localTempFile = $file->getForLocalProcessing(false);

        // extract text
        $content = (string)file_get_contents($localTempFile);

        // In case of remote storage, the temporary copy of the
        // original file in typo3temp must be removed
        // Simply compare the filenames, because the filename is so unique that
        // it is nearly impossible to have a file with this name in a storage
        if (PathUtility::basename($localTempFile) !== $file->getName()) {
            unlink($localTempFile);
        }

        return $content;
    }
}
