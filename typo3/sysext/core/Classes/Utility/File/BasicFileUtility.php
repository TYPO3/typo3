<?php
namespace TYPO3\CMS\Core\Utility\File;

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

use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Contains class with basic file management functions
 *
 * Contains functions for management, validation etc of files in TYPO3.
 *
 * Note: All methods in this class should not be used anymore since TYPO3 6.0.
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
     * Allowed and denied file extensions
     * @var array
     */
    protected $fileExtensionPermissions = [
        'allow' => '*',
        'deny' => PHP_EXTENSIONS_DEFAULT

    ];

    /**********************************
     *
     * Checking functions
     *
     **********************************/

    /**
     * Sets the file permissions, used in DataHandler e.g.
     *
     * @param string $allowedFilePermissions
     * @param string $deniedFilePermissions
     */
    public function setFileExtensionPermissions($allowedFilePermissions, $deniedFilePermissions)
    {
        $this->fileExtensionPermissions['allow'] = GeneralUtility::uniqueList(strtolower($allowedFilePermissions));
        $this->fileExtensionPermissions['deny'] = GeneralUtility::uniqueList(strtolower($deniedFilePermissions));
    }

    /**
     * Checks if a $fileExtension is allowed according to $this->fileExtensionPermissions.
     *
     * @param string $fileExtension The extension to check, eg. "php" or "html" etc.
     * @return bool TRUE if file extension is allowed.
     */
    protected function is_allowed($fileExtension)
    {
        $fileExtension = strtolower($fileExtension);
        if ($fileExtension) {
            // If the extension is found amongst the allowed types, we return TRUE immediately
            if ($this->fileExtensionPermissions['allow'] === '*' || GeneralUtility::inList($this->fileExtensionPermissions['allow'], $fileExtension)) {
                return true;
            }
            // If the extension is found amongst the denied types, we return FALSE immediately
            if ($this->fileExtensionPermissions['deny'] === '*' || GeneralUtility::inList($this->fileExtensionPermissions['deny'], $fileExtension)) {
                return false;
            }
        } else {
            // If no extension
            if ($this->fileExtensionPermissions['allow'] === '*') {
                return true;
            }
            if ($this->fileExtensionPermissions['deny'] === '*') {
                return false;
            }
        }
        // If no match we return TRUE
        return true;
    }

    /**
     * If the filename is given, check it against the TYPO3_CONF_VARS[BE][fileDenyPattern] +
     * Checks if the $ext fileextension is allowed
     *
     * @param string $ext File extension, eg. "php" or "html
     * @param string $_ not in use anymore
     * @param string $filename Filename to check against TYPO3_CONF_VARS[BE][fileDenyPattern]
     * @return bool TRUE if extension/filename is allowed
     * @todo Deprecate, but still in use by DataHandler
     * @deprecated but still in use in the Core. Don't use in your extensions!
     */
    public function checkIfAllowed($ext, $_, $filename = '')
    {
        return GeneralUtility::verifyFilenameAgainstDenyPattern($filename) && $this->is_allowed($ext);
    }

    /**
     * Cleans $theDir for slashes in the end of the string and returns the new path, if it exists on the server.
     *
     * @param string $theDir Directory path to check
     * @return bool|string Returns the cleaned up directory name if OK, otherwise FALSE.
     */
    protected function is_directory($theDir)
    {
        // @todo: should go into the LocalDriver in a protected way (not important to the outside world)
        if (GeneralUtility::validPathStr($theDir)) {
            $theDir = PathUtility::getCanonicalPath($theDir);
            if (@is_dir($theDir)) {
                return $theDir;
            }
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
     * @return string The destination absolute filepath (not just the name!) of a unique filename/foldername in that path.
     * @see \TYPO3\CMS\Core\DataHandling\DataHandler::checkValue()
     * @todo Deprecate, but still in use by the Core (DataHandler...)
     * @deprecated but still in use in the Core. Don't use in your extensions!
     */
    public function getUniqueName($theFile, $theDest, $dontCheckForUnique = false)
    {
        // @todo: should go into the LocalDriver in a protected way (not important to the outside world)
        $theDest = $this->is_directory($theDest);
        // $theDest is cleaned up
        $origFileInfo = GeneralUtility::split_fileref($theFile);
        // Fetches info about path, name, extension of $theFile
        if ($theDest) {
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
                    $insert = '_' . substr(md5(uniqid('', true)), 0, $this->uniquePrecision);
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
    }

    /*********************
     *
     * Cleaning functions
     *
     *********************/

    /**
     * Returns a string where any character not matching [.a-zA-Z0-9_-] is substituted by '_'
     * Trailing dots are removed
     *
     * @param string $fileName Input string, typically the body of a filename
     * @return string Output string with any characters not matching [.a-zA-Z0-9_-] is substituted by '_' and trailing dots removed
     * @todo Deprecate, but still in use by the core
     * @deprecated but still in use in the Core. Don't use in your extensions!
     */
    public function cleanFileName($fileName)
    {
        // Handle UTF-8 characters
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
            // allow ".", "-", 0-9, a-z, A-Z and everything beyond U+C0 (latin capital letter a with grave)
            $cleanFileName = preg_replace('/[' . self::UNSAFE_FILENAME_CHARACTER_EXPRESSION . ']/u', '_', trim($fileName));
        } else {
            $fileName = GeneralUtility::makeInstance(CharsetConverter::class)->utf8_char_mapping($fileName);
            // Replace unwanted characters by underscores
            $cleanFileName = preg_replace('/[' . self::UNSAFE_FILENAME_CHARACTER_EXPRESSION . '\\xC0-\\xFF]/', '_', trim($fileName));
        }
        // Strip trailing dots and return
        return rtrim($cleanFileName, '.');
    }
}
