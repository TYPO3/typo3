<?php
namespace TYPO3\CMS\Core\Utility\File;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Contains class with basic file management functions
 *
 * Contains functions for management, validation etc of files in TYPO3,
 * using the concepts of web- and ftp-space. Please see the comment for the
 * init() function
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class BasicFileUtility {

	/**
	 * @todo Define visibility
	 */
	public $getUniqueNamePrefix = '';

	// Prefix which will be prepended the file when using the getUniqueName-function
	/**
	 * @todo Define visibility
	 */
	public $maxNumber = 99;

	// This number decides the highest allowed appended number used on a filename before we use naming with unique strings
	/**
	 * @todo Define visibility
	 */
	public $uniquePrecision = 6;

	// This number decides how many characters out of a unique MD5-hash that is appended to a filename if getUniqueName is asked to find an available filename.
	/**
	 * @todo Define visibility
	 */
	public $maxInputNameLen = 60;

	// This is the maximum length of names treated by cleanFileName()
	/**
	 * @todo Define visibility
	 */
	public $tempFN = '_temp_';

	// Temp-foldername. A folder in the root of one of the mounts with this name is regarded a TEMP-folder (used for upload from clipboard)
	// internal
	/**
	 * @todo Define visibility
	 */
	public $f_ext = array();

	// See comment in header
	/**
	 * @todo Define visibility
	 */
	public $mounts = array();

	// See comment in header
	/**
	 * @todo Define visibility
	 */
	public $webPath = '';

	// Set to DOCUMENT_ROOT.
	/**
	 * @todo Define visibility
	 */
	public $isInit = 0;

	// Set to TRUE after init()/start();
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
	 * Typically the global var $WEBMOUNTS would be passed along as $mounts
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
	 * $basicff->init($GLOBALS['FILEMOUNTS'],$GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
	 *
	 * @param 	array		Contains the paths of the file mounts for the current BE user. Normally $GLOBALS['FILEMOUNTS'] is passed. This variable is set during backend user initialization; $FILEMOUNTS = $GLOBALS['BE_USER']->returnFilemounts(); (see typo3/init.php)
	 * @param 	array		Array with information about allowed and denied file extensions. Typically passed: $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']
	 * @return 	void
	 * @see typo3/init.php, \TYPO3\CMS\Core\Authentication\BackendUserAuthentication::returnFilemounts()
	 * @todo Define visibility
	 */
	public function init($mounts, $f_ext) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction('All methods in this class should not be used anymore since TYPO3 6.0. Please use corresponding TYPO3\\CMS\\Core\\Resource\\ResourceStorage (fetched via BE_USERS->getFileStorages()), as all functions should be found there (in a cleaner manner).');
		$this->f_ext['webspace']['allow'] = \TYPO3\CMS\Core\Utility\GeneralUtility::uniqueList(strtolower($f_ext['webspace']['allow']));
		$this->f_ext['webspace']['deny'] = \TYPO3\CMS\Core\Utility\GeneralUtility::uniqueList(strtolower($f_ext['webspace']['deny']));
		$this->f_ext['ftpspace']['allow'] = \TYPO3\CMS\Core\Utility\GeneralUtility::uniqueList(strtolower($f_ext['ftpspace']['allow']));
		$this->f_ext['ftpspace']['deny'] = \TYPO3\CMS\Core\Utility\GeneralUtility::uniqueList(strtolower($f_ext['ftpspace']['deny']));
		$this->mounts = $mounts;
		$this->webPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_DOCUMENT_ROOT');
		$this->isInit = 1;
		$this->maxInputNameLen = $GLOBALS['TYPO3_CONF_VARS']['SYS']['maxFileNameLength'] ? $GLOBALS['TYPO3_CONF_VARS']['SYS']['maxFileNameLength'] : $this->maxInputNameLen;
	}

	/**
	 * Returns an array with a whole lot of fileinformation.
	 * Information includes:
	 * - path			: path part of give file
	 * - file			: filename
	 * - filebody		: filename without extension
	 * - fileext		: lowercase extension
	 * - realFileext	: extension
	 * - tstamp			: timestamp of modification
	 * - size			: file size
	 * - type			: file type (block/char/dir/fifo/file/link)
	 * - owner			: user ID of owner of file
	 * - perms			: numerical representation of file permissions
	 * - writable		: is file writeable by web user (FALSE = yes; TRUE = no) *)
	 * - readable		: is file readable by web user (FALSE = yes; TRUE = no) *)
	 *
	 * ) logic is reversed because of handling by functions in class.file_list.inc
	 *
	 * @param 	string		Filepath to existing file. Should probably be absolute. Filefunctions are performed on this value.
	 * @return 	array		Information about the file in the filepath
	 * @todo Define visibility
	 */
	public function getTotalFileInfo($wholePath) {
		// @todo: deprecate this function, and replace its use in the storage/mounts
		$theuser = getmyuid();
		$info = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($wholePath);
		$info['tstamp'] = @filemtime($wholePath);
		$info['size'] = @filesize($wholePath);
		$info['type'] = @filetype($wholePath);
		$info['owner'] = @fileowner($wholePath);
		$info['perms'] = @fileperms($wholePath);
		$info['writable'] = !@is_writable($wholePath);
		$info['readable'] = !@is_readable($wholePath);
		return $info;
	}

	/**
	 * Checks if a $iconkey (fileextension) is allowed according to $this->f_ext.
	 *
	 * @param 	string		The extension to check, eg. "php" or "html" etc.
	 * @param 	string		Either "webspage" or "ftpspace" - points to a key in $this->f_ext
	 * @return 	boolean		TRUE if file extension is allowed.
	 * @todo Define visibility
	 */
	public function is_allowed($iconkey, $type) {
		if (isset($this->f_ext[$type])) {
			$ik = strtolower($iconkey);
			if ($ik) {
				// If the extension is found amongst the allowed types, we return TRUE immediately
				if ($this->f_ext[$type]['allow'] == '*' || \TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->f_ext[$type]['allow'], $ik)) {
					return TRUE;
				}
				// If the extension is found amongst the denied types, we return FALSE immediately
				if ($this->f_ext[$type]['deny'] == '*' || \TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->f_ext[$type]['deny'], $ik)) {
					return FALSE;
				}
				// If no match we return TRUE
				return TRUE;
			} else {
				// If no extension:
				if ($this->f_ext[$type]['allow'] == '*') {
					return TRUE;
				}
				if ($this->f_ext[$type]['deny'] == '*') {
					return FALSE;
				}
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Returns TRUE if you can operate of ANY file ('*') in the space $theDest is in ('webspace' / 'ftpspace')
	 *
	 * @param 	string		Absolute path
	 * @return 	boolean
	 * @todo Define visibility
	 */
	public function checkIfFullAccess($theDest) {
		$type = $this->is_webpath($theDest) ? 'webspace' : 'ftpspace';
		if (isset($this->f_ext[$type])) {
			if ((string) $this->f_ext[$type]['deny'] == '' || $this->f_ext[$type]['allow'] == '*') {
				return TRUE;
			}
		}
	}

	/**
	 * Checks if $this->webPath (should be TYPO3_DOCUMENT_ROOT) is in the first part of $path
	 * Returns TRUE also if $this->init is not set or if $path is empty...
	 *
	 * @param 	string		Absolute path to check
	 * @return 	boolean
	 * @todo Define visibility
	 */
	public function is_webpath($path) {
		if ($this->isInit) {
			$testPath = $this->slashPath($path);
			$testPathWeb = $this->slashPath($this->webPath);
			if ($testPathWeb && $testPath) {
				return \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($testPath, $testPathWeb);
			}
		}
		return TRUE;
	}

	/**
	 * If the filename is given, check it against the TYPO3_CONF_VARS[BE][fileDenyPattern] +
	 * Checks if the $ext fileextension is allowed in the path $theDest (this is based on whether $theDest is below the $this->webPath)
	 *
	 * @param 	string		File extension, eg. "php" or "html
	 * @param 	string		Absolute path for which to test
	 * @param 	string		Filename to check against TYPO3_CONF_VARS[BE][fileDenyPattern]
	 * @return 	boolean		TRUE if extension/filename is allowed
	 * @todo Define visibility
	 */
	public function checkIfAllowed($ext, $theDest, $filename = '') {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::verifyFilenameAgainstDenyPattern($filename) && $this->is_allowed($ext, ($this->is_webpath($theDest) ? 'webspace' : 'ftpspace'));
	}

	/**
	 * Returns TRUE if the input filename string is shorter than $this->maxInputNameLen.
	 *
	 * @param 	string		Filename, eg "somefile.html
	 * @return 	boolean
	 * @todo Define visibility
	 */
	public function checkFileNameLen($fileName) {
		// @todo: should go into the LocalDriver in a protected way (not important to the outside world)
		return strlen($fileName) <= $this->maxInputNameLen;
	}

	/**
	 * Cleans $theDir for slashes in the end of the string and returns the new path, if it exists on the server.
	 *
	 * @param 	string		Directory path to check
	 * @return 	string		Returns the cleaned up directory name if OK, otherwise FALSE.
	 * @todo Define visibility
	 */
	public function is_directory($theDir) {
		// @todo: should go into the LocalDriver in a protected way (not important to the outside world)
		if ($this->isPathValid($theDir)) {
			$theDir = $this->cleanDirectoryName($theDir);
			if (@is_dir($theDir)) {
				return $theDir;
			}
		}
		return FALSE;
	}

	/**
	 * Wrapper for \TYPO3\CMS\Core\Utility\GeneralUtility::validPathStr()
	 *
	 * @param 	string		Filepath to evaluate
	 * @return 	boolean		TRUE, if no '//', '..' or '\' is in the $theFile
	 * @see 	\TYPO3\CMS\Core\Utility\GeneralUtility::validPathStr()
	 * @todo Define visibility
	 */
	public function isPathValid($theFile) {
		// @todo: should go into the LocalDriver in a protected way (not important to the outside world)
		return \TYPO3\CMS\Core\Utility\GeneralUtility::validPathStr($theFile);
	}

	/**
	 * Returns the destination path/filename of a unique filename/foldername in that path.
	 * If $theFile exists in $theDest (directory) the file have numbers appended up to $this->maxNumber. Hereafter a unique string will be appended.
	 * This function is used by fx. TCEmain when files are attached to records and needs to be uniquely named in the uploads/* folders
	 *
	 * @param 	string		The input filename to check
	 * @param 	string		The directory for which to return a unique filename for $theFile. $theDest MUST be a valid directory. Should be absolute.
	 * @param 	boolean		If set the filename is returned with the path prepended without checking whether it already existed!
	 * @return 	string		The destination absolute filepath (not just the name!) of a unique filename/foldername in that path.
	 * @see \TYPO3\CMS\Core\DataHandling\DataHandler::checkValue()
	 * @todo Define visibility
	 */
	public function getUniqueName($theFile, $theDest, $dontCheckForUnique = 0) {
		// @todo: should go into the LocalDriver in a protected way (not important to the outside world)
		$theDest = $this->is_directory($theDest);
		// $theDest is cleaned up
		$origFileInfo = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($theFile);
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
					$insert = '_' . substr(md5(uniqId('')), 0, $this->uniquePrecision);
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
	 * @param 	string		$thePath MUST HAVE a trailing '/' in order to match correctly with the mounts
	 * @return 	string		The key to the first mount found, otherwise nothing is returned.
	 * @see init()
	 * @todo Define visibility
	 */
	public function checkPathAgainstMounts($thePath) {
		// @todo: deprecate this function, now done in the Storage object
		if ($thePath && $this->isPathValid($thePath) && is_array($this->mounts)) {
			foreach ($this->mounts as $k => $val) {
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($thePath, $val['path'])) {
					return $k;
				}
			}
		}
	}

	/**
	 * Find first web folder (relative to PATH_site.'fileadmin') in filemounts array
	 *
	 * @return 	string		The key to the first mount inside PATH_site."fileadmin" found, otherwise nothing is returned.
	 * @todo Define visibility
	 */
	public function findFirstWebFolder() {
		// @todo: where and when to use this function?
		if (is_array($this->mounts)) {
			foreach ($this->mounts as $k => $val) {
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($val['path'], PATH_site . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'])) {
					return $k;
				}
			}
		}
	}

	/**
	 * Removes filemount part of a path, thus blinding the position.
	 * Takes a path, $thePath, and removes the part of the path which equals the filemount.
	 *
	 * @param 	string		$thePath is a path which MUST be found within one of the internally set filemounts, $this->mounts
	 * @return 	string		The processed input path
	 * @todo Define visibility
	 */
	public function blindPath($thePath) {
		// @todo: where and when to use this function?
		$k = $this->checkPathAgainstMounts($thePath);
		if ($k) {
			$name = '';
			$name .= '[' . $this->mounts[$k]['name'] . ']: ';
			$name .= substr($thePath, strlen($this->mounts[$k]['path']));
			return $name;
		}
	}

	/**
	 * Find temporary folder
	 * Finds the first $this->tempFN ('_temp_' usually) -folder in the internal array of filemounts, $this->mounts
	 *
	 * @return 	string		Returns the path if found, otherwise nothing if error.
	 * @todo Define visibility
	 */
	public function findTempFolder() {
		// @todo: where and when to use this function?
		if ($this->tempFN && is_array($this->mounts)) {
			foreach ($this->mounts as $k => $val) {
				$tDir = $val['path'] . $this->tempFN;
				if (@is_dir($tDir)) {
					return $tDir;
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
	 * Removes all dots, slashes and spaces after a path...
	 *
	 * @param 	string		Input string
	 * @return 	string		Output string
	 * @todo Define visibility
	 */
	public function cleanDirectoryName($theDir) {
		// @todo: should go into the LocalDriver in a protected way (not important to the outside world)
		return preg_replace('/[\\/\\. ]*$/', '', $this->rmDoubleSlash($theDir));
	}

	/**
	 * Converts any double slashes (//) to a single slash (/)
	 *
	 * @param 	string		Input value
	 * @return 	string		Returns the converted string
	 * @todo Define visibility
	 */
	public function rmDoubleSlash($string) {
		// @todo: should go into the LocalDriver in a protected way (not important to the outside world)
		return str_replace('//', '/', $string);
	}

	/**
	 * Returns a string which has a slash '/' appended if it doesn't already have that slash
	 *
	 * @param 	string		Input string
	 * @return 	string		Output string with a slash in the end (if not already there)
	 * @todo Define visibility
	 */
	public function slashPath($path) {
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
	 * @param 	string		Input string, typically the body of a filename
	 * @param 	string		Charset of the a filename (defaults to current charset; depending on context)
	 * @return 	string		Output string with any characters not matching [.a-zA-Z0-9_-] is substituted by '_' and trailing dots removed
	 * @todo Define visibility
	 */
	public function cleanFileName($fileName, $charset = '') {
		// Handle UTF-8 characters
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
			// allow ".", "-", 0-9, a-z, A-Z and everything beyond U+C0 (latin capital letter a with grave)
			$cleanFileName = preg_replace('/[\\x00-\\x2C\\/\\x3A-\\x3F\\x5B-\\x60\\x7B-\\xBF]/u', '_', trim($fileName));
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
					$this->csConvObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
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
			$cleanFileName = preg_replace('/[^.[:alnum:]_-]/', '_', trim($fileName));
		}
		// Strip trailing dots and return
		return preg_replace('/\\.*$/', '', $cleanFileName);
	}

}


?>