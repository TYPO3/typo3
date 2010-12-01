<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 FAL development team <fal@wmdb.de>
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
 * File Abtraction Layer Helper
 *
 * @author		FAL development team <fal@wmdb.de>
 * @package		TYPO3
 * @subpackage	tx_fal
 * @version		$Id$
 */
class tx_fal_Helper {

	/**
	 * DESCRIPTION
	 *
	 * @static
	 * @param	string	$fileNameAndPath	DESCRIPTION
	 * @return	void
	 */
	public static function getOrCreateFileObjectFromPath($fileNameAndPath) {
		/** @var $fileRepository tx_fal_Repository */
		$fileRepository = t3lib_div::makeInstance('tx_fal_Repository');
		$relativePath = substr($fileNameAndPath, strlen(PATH_site));

		try {
			return $fileRepository->getFileByPath($relativePath);
		} catch(tx_fal_exception_FileNotFound $e) {

			$mount = self::getMountFromFilePath($relativePath);
			$fileUid = tx_fal_Indexer::addFileToIndex($mount, $relativePath);

			return $fileRepository->getFileById($fileUid);
		}
	}

	/**
	 * Creates a comma separated list of file paths from a list of file objects
	 *
	 * @todo check how this will work for remote files
	 *
	 * @static
	 * @param	array	$fileObjects	The file objects to iterate over
	 * @return	string					A comma separated list of paths to the files.
	 */
	public static function createCsvListOfFilepaths(array $fileObjects) {
		$csv = array();
		foreach ($fileObjects as $file) {
			$csv[] = $file->getPath() . $file->getName();
		}

		return implode(',', $csv);
	}

	/**
	 * Extracts the mount from a FAL file path (<mount>/path/to/file/inside/mount)
	 *
	 * @static
	 * @param	string					$filePath	The path to the file
	 * @return t3lib_file_Mount|boolean				Mount object, FALSE if the mount could not be resolved
	 */
	public static function getMountFromFilePath($filePath) {
		if (t3lib_div::isAbsPath($filePath)) {
			$filePath = substr($filePath, strlen(PATH_site));
		}

		$pathParts = explode('/', $filePath, 2);
		$mountAlias = array_shift($pathParts);
		$mount = tx_fal_Mount::getInstanceForAlias($mountAlias);

		if (!is_object($mount)) {
			return FALSE;
		}
		return $mount;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/class.tx_fal_Helper.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/fal/classes/class.tx_fal_Helper.php']);
}
?>