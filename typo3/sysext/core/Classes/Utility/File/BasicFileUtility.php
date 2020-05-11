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

namespace TYPO3\CMS\Core\Utility\File;

use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Contains class with basic file management functions
 *
 * Contains functions for management, validation etc of files in TYPO3.
 *
 * @internal All methods in this class should not be used anymore since TYPO3 6.0, this class is therefore marked
 * as internal.
 * Please use corresponding \TYPO3\CMS\Core\Resource\ResourceStorage
 * (fetched via BE_USERS->getFileStorages()), as all functions should be
 * found there (in a cleaner manner).
 */
class BasicFileUtility
{
    /**
     * @var string
     */
    const UNSAFE_FILENAME_CHARACTER_EXPRESSION = '\\x00-\\x2C\\/\\x3A-\\x3F\\x5B-\\x60\\x7B-\\xBF';

    /**
     * This number decides the highest allowed appended number used on a filename before we use naming with unique strings
     *
     * @var int
     */
    public $maxNumber = 99;

    /**
     * This number decides how many characters out of a unique MD5-hash that is appended to a filename if getUniqueName is asked to find an available filename.
     *
     * @var int
     */
    public $uniquePrecision = 6;

    /**
     * Cleans $theDir for slashes in the end of the string and returns the new path, if it exists on the server.
     *
     * @param string $theDir Directory path to check
     * @return bool|string Returns the cleaned up directory name if OK, otherwise FALSE.
     * @todo: should go into the LocalDriver in a protected way (not important to the outside world)
     */
    protected function sanitizeFolderPath($theDir)
    {
        if (!GeneralUtility::validPathStr($theDir)) {
            return false;
        }
        $theDir = PathUtility::getCanonicalPath($theDir);
        if (@is_dir($theDir)) {
            return $theDir;
        }
        return false;
    }

    /**
     * Returns the destination path/filename of a unique filename/foldername in that path.
     * If $theFile exists in $theDest (directory) the file have numbers appended up to $this->maxNumber. Hereafter a unique string will be appended.
     * This function is used by fx. DataHandler when files are attached to records and needs to be uniquely named in the uploads/* folders
     *
     * @param string $theFile The input filename to check
     * @param string $theDest The directory for which to return a unique filename for $theFile. $theDest MUST be a valid directory. Should be absolute.
     * @param bool $dontCheckForUnique If set the filename is returned with the path prepended without checking whether it already existed!
     * @return string|null The destination absolute filepath (not just the name!) of a unique filename/foldername in that path.
     * @internal May be removed without further notice. Method has been marked as deprecated for various versions but is still used in core.
     * @todo: should go into the LocalDriver in a protected way (not important to the outside world)
     */
    public function getUniqueName($theFile, $theDest, $dontCheckForUnique = false)
    {
        // $theDest is cleaned up
        $theDest = $this->sanitizeFolderPath($theDest);
        if ($theDest) {
            // Fetches info about path, name, extension of $theFile
            $origFileInfo = GeneralUtility::split_fileref($theFile);
            // Check if the file exists and if not - return the filename...
            $fileInfo = $origFileInfo;
            $theDestFile = $theDest . '/' . $fileInfo['file'];
            // The destinations file
            if (!file_exists($theDestFile) || $dontCheckForUnique) {
                // If the file does NOT exist we return this filename
                return $theDestFile;
            }
            // Well the filename in its pure form existed. Now we try to append numbers / unique-strings and see if we can find an available filename...
            $theTempFileBody = preg_replace('/_[0-9][0-9]$/', '', $origFileInfo['filebody']);
            // This removes _xx if appended to the file
            $theOrigExt = $origFileInfo['realFileext'] ? '.' . $origFileInfo['realFileext'] : '';
            for ($a = 1; $a <= $this->maxNumber + 1; $a++) {
                if ($a <= $this->maxNumber) {
                    // First we try to append numbers
                    $insert = '_' . sprintf('%02d', $a);
                } else {
                    // .. then we try unique-strings...
                    $insert = '_' . substr(md5(StringUtility::getUniqueId()), 0, $this->uniquePrecision);
                }
                $theTestFile = $theTempFileBody . $insert . $theOrigExt;
                $theDestFile = $theDest . '/' . $theTestFile;
                // The destinations file
                if (!file_exists($theDestFile)) {
                    // If the file does NOT exist we return this filename
                    return $theDestFile;
                }
            }
        }

        return null;
    }

    /**
     * Returns a string where any character not matching [.a-zA-Z0-9_-] is substituted by '_'
     * Trailing dots are removed
     *
     * @param string $fileName Input string, typically the body of a filename
     * @return string Output string with any characters not matching [.a-zA-Z0-9_-] is substituted by '_' and trailing dots removed
     * @internal May be removed without further notice. Method has been marked as deprecated for various versions but is still used in core.
     */
    public function cleanFileName($fileName)
    {
        // Handle UTF-8 characters
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
            // allow ".", "-", 0-9, a-z, A-Z and everything beyond U+C0 (latin capital letter a with grave)
            $cleanFileName = preg_replace('/[' . self::UNSAFE_FILENAME_CHARACTER_EXPRESSION . ']/u', '_', trim($fileName)) ?? '';
        } else {
            $fileName = GeneralUtility::makeInstance(CharsetConverter::class)->utf8_char_mapping($fileName);
            // Replace unwanted characters by underscores
            $cleanFileName = preg_replace('/[' . self::UNSAFE_FILENAME_CHARACTER_EXPRESSION . '\\xC0-\\xFF]/', '_', trim($fileName)) ?? '';
        }
        // Strip trailing dots and return
        return rtrim($cleanFileName, '.');
    }
}
