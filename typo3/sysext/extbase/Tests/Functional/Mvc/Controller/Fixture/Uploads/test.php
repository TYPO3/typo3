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

$iterator = new DirectoryIterator('.');

foreach ($iterator as $fileInfo) {
    if ($fileInfo->isDot() || !$fileInfo->isFile()) {
        continue;
    }

    $mimeType1 = $mimeType2 = null;

    if (function_exists('finfo_file')) {
        $finfo = new finfo();
        $mimeType1 = $finfo->file($fileInfo->getPathname(), FILEINFO_MIME_TYPE);
    }

    if (function_exists('mime_content_type')) {
        $mimeType2 = mime_content_type($fileInfo->getPathname());
    }

    echo $fileInfo->getFilename() . ': ' . ($mimeType1 ?: 'unknown') . ' | ' . ($mimeType2 ?: 'unknown') . "\n";
}
