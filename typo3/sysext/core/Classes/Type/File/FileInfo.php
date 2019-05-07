<?php
namespace TYPO3\CMS\Core\Type\File;

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

use TYPO3\CMS\Core\Type\TypeInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A SPL FileInfo class providing general information related to a file.
 */
class FileInfo extends \SplFileInfo implements TypeInterface
{
    /**
     * Return the mime type of a file.
     *
     * TYPO3 specific settings in $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType'] take
     * precedence over native resolving.
     *
     * @return string|bool Returns the mime type or FALSE if the mime type could not be discovered
     */
    public function getMimeType()
    {
        $mimeType = false;
        if ($this->isFile()) {
            $fileExtensionToMimeTypeMapping = $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType'];
            $lowercaseFileExtension = strtolower($this->getExtension());
            if (!empty($fileExtensionToMimeTypeMapping[$lowercaseFileExtension])) {
                $mimeType = $fileExtensionToMimeTypeMapping[$lowercaseFileExtension];
            } else {
                if (function_exists('finfo_file')) {
                    $fileInfo = new \finfo();
                    $mimeType = $fileInfo->file($this->getPathname(), FILEINFO_MIME_TYPE);
                } elseif (function_exists('mime_content_type')) {
                    $mimeType = mime_content_type($this->getPathname());
                }
            }
        }

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Type\File\FileInfo::class]['mimeTypeGuessers'] ?? [] as $mimeTypeGuesser) {
            $hookParameters = [
                'mimeType' => &$mimeType
            ];

            GeneralUtility::callUserFunction(
                $mimeTypeGuesser,
                $hookParameters,
                $this
            );
        }

        return $mimeType;
    }

    /**
     * Returns the file extensions appropiate for a the MIME type detected in the file. For types that commonly have
     * multiple file extensions, such as JPEG images, then the return value is multiple extensions, for instance that
     * could be ['jpeg', 'jpg', 'jpe', 'jfif']. For unknown types not available in the magic.mime database
     * (/etc/magic.mime, /etc/mime.types, ...), then return value is an empty array.
     *
     * TYPO3 specific settings in $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType'] take
     * precedence over native resolving.
     *
     * @return string[]
     */
    public function getMimeExtensions(): array
    {
        $mimeExtensions = [];
        if ($this->isFile()) {
            $fileExtensionToMimeTypeMapping = $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType'];
            $mimeType = $this->getMimeType();
            if (in_array($mimeType, $fileExtensionToMimeTypeMapping, true)) {
                $mimeExtensions = array_keys($fileExtensionToMimeTypeMapping, $mimeType, true);
            } elseif (function_exists('finfo_file')) {
                $fileInfo = new \finfo();
                $mimeExtensions = array_filter(
                    GeneralUtility::trimExplode(
                        '/',
                        (string)$fileInfo->file($this->getPathname(), FILEINFO_EXTENSION)
                    ),
                    function ($item) {
                        // filter invalid items ('???' is used if not found in magic.mime database)
                        return $item !== '' && $item !== '???';
                    }
                );
            }
        }
        return $mimeExtensions;
    }
}
