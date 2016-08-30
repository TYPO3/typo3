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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Contains class with basic file management functions
 *
 * Contains functions for management, validation etc of files in TYPO3,
 * using the concepts of web- and ftp-space. Please see the comment for the
 * init() function
 *
 * Note: All methods in this class should not be used anymore since TYPO3 6.0.
 * Please use corresponding TYPO3\\CMS\\Core\\Resource\\ResourceStorage
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
     * Prefix which will be prepended the file when using the getUniqueName-function
     *
     * @var string
     */
    public $getUniqueNamePrefix = '';

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
     * This is the maximum length of names treated by cleanFileName()
     *
     * @var int
     */
    public $maxInputNameLen = 60;

    /**
     * Temp-foldername. A folder in the root of one of the mounts with this name is regarded a TEMP-folder (used for upload from clipboard)
     *
     * @var string
     */
    public $tempFN = '_temp_';

    /**
     * @var array
     */
    public $f_ext = [];

    /**
     * See comment in header
     *
     * @var array
     */
    public $mounts = [];

    /**
     * See comment in header
     *
     * @var string
     */
    public $webPath = '';

    /**
     * Set to DOCUMENT_ROOT.
     *
     * @var bool
     */
    public $isInit = 0;

    /**
     * @var \TYPO3\CMS\Core\Charset\CharsetConverter
     */
    public $csConvObj;

    /**********************************
     *
     * Checking functions
     *
     **********************************/
    /**
     * Constructor
     * This function should be called to initialise the internal arrays $this->mounts and $this->f_ext
     *
     * A typical example of the array $mounts is this:
     * $mounts[xx][path] = (..a mounted path..)
     * the 'xx'-keys is just numerical from zero. There are also a [name] and [type] value that just denotes the mountname and type. Not used for athentication here.
     * $this->mounts is traversed in the function checkPathAgainstMounts($thePath), and it is checked that $thePath is actually below one of the mount-paths
     * The mountpaths are with a trailing '/'. $thePath must be with a trailing '/' also!
     * As you can see, $this->mounts is very critical! This is the array that decides where the user will be allowed to copy files!!
     *
     * A typical example of the array $f_ext is this:
     * $f_ext['webspace']['allow']='';
     * $f_ext['webspace']['deny']= PHP_EXTENSIONS_DEFAULT;
     * $f_ext['ftpspace']['allow']='*';
     * $f_ext['ftpspace']['deny']='';
     * The control of fileextensions goes in two catagories. Webspace and Ftpspace. Webspace is folders accessible from a webbrowser (below TYPO3_DOCUMENT_ROOT) and ftpspace is everything else.
     * The control is done like this: If an extension matches 'allow' then the check returns TRUE. If not and an extension matches 'deny' then the check return FALSE. If no match at all, returns TRUE.
     * You list extensions comma-separated. If the value is a '*' every extension is allowed
     * The list is case-insensitive when used in this class (see init())
     * Typically TYPO3_CONF_VARS['BE']['fileExtensions'] would be passed along as $f_ext.
     *
     * Example:
     * $basicff->init(array(), $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
     *
     * @param array Not in use anymore
     * @param array Array with information about allowed and denied file extensions. Typically passed: $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']
     * @return void
     */
    public function init($mounts, $f_ext)
    {
        $this->f_ext['webspace']['allow'] = GeneralUtility::uniqueList(strtolower($f_ext['webspace']['allow']));
        $this->f_ext['webspace']['deny'] = GeneralUtility::uniqueList(strtolower($f_ext['webspace']['deny']));
        $this->f_ext['ftpspace']['allow'] = GeneralUtility::uniqueList(strtolower($f_ext['ftpspace']['allow']));
        $this->f_ext['ftpspace']['deny'] = GeneralUtility::uniqueList(strtolower($f_ext['ftpspace']['deny']));

        $this->mounts = (!empty($mounts) ? $mounts : []);
        $this->webPath = GeneralUtility::getIndpEnv('TYPO3_DOCUMENT_ROOT');
        $this->isInit = 1;
        $this->maxInputNameLen = $GLOBALS['TYPO3_CONF_VARS']['SYS']['maxFileNameLength'] ?: $this->maxInputNameLen;
    }

    /**
     * Checks if a $iconkey (fileextension) is allowed according to $this->f_ext.
     *
     * @param string The extension to check, eg. "php" or "html" etc.
     * @param string Either "webspage" or "ftpspace" - points to a key in $this->f_ext
     * @return bool TRUE if file extension is allowed.
     * @todo Deprecate, but still in use by checkIfAllowed()
     * @deprecated but still in use in the Core. Don't use in your extensions!
     */
    public function is_allowed($iconkey, $type)
    {
        if (isset($this->f_ext[$type])) {
            $ik = strtolower($iconkey);
            if ($ik) {
                // If the extension is found amongst the allowed types, we return TRUE immediately
                if ($this->f_ext[$type]['allow'] == '*' || GeneralUtility::inList($this->f_ext[$type]['allow'], $ik)) {
                    return true;
                }
                // If the extension is found amongst the denied types, we return FALSE immediately
                if ($this->f_ext[$type]['deny'] == '*' || GeneralUtility::inList($this->f_ext[$type]['deny'], $ik)) {
                    return false;
                }
                // If no match we return TRUE
                return true;
            } else {
                // If no extension:
                if ($this->f_ext[$type]['allow'] == '*') {
                    return true;
                }
                if ($this->f_ext[$type]['deny'] == '*') {
                    return false;
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Returns TRUE if you can operate of ANY file ('*') in the space $theDest is in ('webspace' / 'ftpspace')
     *
     * @param string Absolute path
     * @return bool
     * @todo Deprecate: but still in use by through func_unzip in ExtendedFileUtility
     * @deprecated but still in use in the Core. Don't use in your extensions!
     */
    public function checkIfFullAccess($theDest)
    {
        $type = $this->is_webpath($theDest) ? 'webspace' : 'ftpspace';
        if (isset($this->f_ext[$type])) {
            if ((string)$this->f_ext[$type]['deny'] == '' || $this->f_ext[$type]['allow'] == '*') {
                return true;
            }
        }
    }

    /**
     * Checks if $this->webPath (should be TYPO3_DOCUMENT_ROOT) is in the first part of $path
     * Returns TRUE also if $this->init is not set or if $path is empty...
     *
     * @param string Absolute path to check
     * @return bool
     * @todo Deprecate, but still in use by DataHandler
     * @deprecated but still in use in the Core. Don't use in your extensions!
     */
    public function is_webpath($path)
    {
        if ($this->isInit) {
            $testPath = $this->slashPath($path);
            $testPathWeb = $this->slashPath($this->webPath);
            if ($testPathWeb && $testPath) {
                return GeneralUtility::isFirstPartOfStr($testPath, $testPathWeb);
            }
        }
        return true;
    }

    /**
     * If the filename is given, check it against the TYPO3_CONF_VARS[BE][fileDenyPattern] +
     * Checks if the $ext fileextension is allowed in the path $theDest (this is based on whether $theDest is below the $this->webPath)
     *
     * @param string File extension, eg. "php" or "html
     * @param string Absolute path for which to test
     * @param string Filename to check against TYPO3_CONF_VARS[BE][fileDenyPattern]
     * @return bool TRUE if extension/filename is allowed
     * @todo Deprecate, but still in use by DataHandler
     * @deprecated but still in use in the Core. Don't use in your extensions!
     */
    public function checkIfAllowed($ext, $theDest, $filename = '')
    {
        return GeneralUtility::verifyFilenameAgainstDenyPattern($filename) && $this->is_allowed($ext, ($this->is_webpath($theDest) ? 'webspace' : 'ftpspace'));
    }

    /**
     * Cleans $theDir for slashes in the end of the string and returns the new path, if it exists on the server.
     *
     * @param string Directory path to check
     * @return string Returns the cleaned up directory name if OK, otherwise FALSE.
     * @todo Deprecate: but still in use by getUniqueName (used by DataHandler)
     * @deprecated but still in use in the Core. Don't use in your extensions!
     */
    public function is_directory($theDir)
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
     * This function is used by fx. TCEmain when files are attached to records and needs to be uniquely named in the uploads/* folders
     *
     * @param string The input filename to check
     * @param string The directory for which to return a unique filename for $theFile. $theDest MUST be a valid directory. Should be absolute.
     * @param bool If set the filename is returned with the path prepended without checking whether it already existed!
     * @return string The destination absolute filepath (not just the name!) of a unique filename/foldername in that path.
     * @see \TYPO3\CMS\Core\DataHandling\DataHandler::checkValue()
     * @todo Deprecate, but still in use by the Core (DataHandler...)
     * @deprecated but still in use in the Core. Don't use in your extensions!
     */
    public function getUniqueName($theFile, $theDest, $dontCheckForUnique = 0)
    {
        // @todo: should go into the LocalDriver in a protected way (not important to the outside world)
        $theDest = $this->is_directory($theDest);
        // $theDest is cleaned up
        $origFileInfo = GeneralUtility::split_fileref($theFile);
        // Fetches info about path, name, extension of $theFile
        if ($theDest) {
            if ($this->getUniqueNamePrefix) {
                // Adds prefix
                $origFileInfo['file'] = $this->getUniqueNamePrefix . $origFileInfo['file'];
                $origFileInfo['filebody'] = $this->getUniqueNamePrefix . $origFileInfo['filebody'];
            }
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

    /**
     * Checks if $thePath is a path under one of the paths in $this->mounts
     * See comment in the header of this class.
     *
     * @param string $thePath MUST HAVE a trailing '/' in order to match correctly with the mounts
     * @return string The key to the first mount found, otherwise nothing is returned.
     * @see init()
     * @todo: deprecate this function, now done in the Storage object. But still in use by impexp and ExtendedFileUtility
     * @deprecated but still in use in the Core. Don't use in your extensions!
     */
    public function checkPathAgainstMounts($thePath)
    {
        if ($thePath && GeneralUtility::validPathStr($thePath) && is_array($this->mounts)) {
            foreach ($this->mounts as $k => $val) {
                if (GeneralUtility::isFirstPartOfStr($thePath, $val['path'])) {
                    return $k;
                }
            }
        }
    }

    /**
     * Find first web folder (relative to PATH_site.'fileadmin') in filemounts array
     *
     * @return string The key to the first mount inside PATH_site."fileadmin" found, otherwise nothing is returned.
     * @todo: deprecate this function. But still in use by impexp
     * @deprecated but still in use in the Core. Don't use in your extensions!
     */
    public function findFirstWebFolder()
    {
        // @todo: where and when to use this function?
        if (is_array($this->mounts)) {
            foreach ($this->mounts as $k => $val) {
                if (GeneralUtility::isFirstPartOfStr($val['path'], PATH_site . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'])) {
                    return $k;
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
     * Returns a string which has a slash '/' appended if it doesn't already have that slash
     *
     * @param string Input string
     * @return string Output string with a slash in the end (if not already there)
     * @todo Deprecate, but still in use by is_webpath, used by DataHandler
     * @deprecated but still in use in the Core. Don't use in your extensions!
     */
    public function slashPath($path)
    {
        // @todo: should go into the LocalDriver in a protected way (not important to the outside world)
        // @todo: should be done with rtrim($path, '/') . '/';
        if (substr($path, -1) != '/') {
            return $path . '/';
        }
        return $path;
    }

    /**
     * Returns a string where any character not matching [.a-zA-Z0-9_-] is substituted by '_'
     * Trailing dots are removed
     *
     * @param string $fileName Input string, typically the body of a filename
     * @param string $charset Charset of the a filename (defaults to current charset; depending on context)
     * @return string Output string with any characters not matching [.a-zA-Z0-9_-] is substituted by '_' and trailing dots removed
     * @todo Deprecate, but still in use by the core
     * @deprecated but still in use in the Core. Don't use in your extensions!
     */
    public function cleanFileName($fileName, $charset = '')
    {
        // Handle UTF-8 characters
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
            // allow ".", "-", 0-9, a-z, A-Z and everything beyond U+C0 (latin capital letter a with grave)
            $cleanFileName = preg_replace('/[' . self::UNSAFE_FILENAME_CHARACTER_EXPRESSION . ']/u', '_', trim($fileName));
        } else {
            // Get conversion object or initialize if needed
            if (!is_object($this->csConvObj)) {
                if (TYPO3_MODE == 'FE') {
                    $this->csConvObj = $GLOBALS['TSFE']->csConvObj;
                } elseif (is_object($GLOBALS['LANG'])) {
                    // BE assumed:
                    $this->csConvObj = $GLOBALS['LANG']->csConvObj;
                } else {
                    // The object may not exist yet, so we need to create it now. Happens in the Install Tool for example.
                    $this->csConvObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Charset\CharsetConverter::class);
                }
            }
            // Define character set
            if (!$charset) {
                if (TYPO3_MODE == 'FE') {
                    $charset = $GLOBALS['TSFE']->renderCharset;
                } else {
                    // Backend
                    $charset = 'utf-8';
                }
            }
            // If a charset was found, convert filename
            if ($charset) {
                $fileName = $this->csConvObj->specCharsToASCII($charset, $fileName);
            }
            // Replace unwanted characters by underscores
            $cleanFileName = preg_replace('/[' . self::UNSAFE_FILENAME_CHARACTER_EXPRESSION . '\\xC0-\\xFF]/', '_', trim($fileName));
        }
        // Strip trailing dots and return
        return rtrim($cleanFileName, '.');
    }
}
