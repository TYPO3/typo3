<?php
namespace TYPO3\CMS\Core\Resource\Filter;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 * Utility methods for filtering filenames
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class FileNameFilter {

	/**
	 * whether to also show the hidden files (don't show them by default)
	 *
	 * @var boolean
	 */
	static protected $showHiddenFilesAndFolders = FALSE;

	/**
	 * Filter method that checks if a file/folder name starts with a dot (e.g. .htaccess)
	 *
	 * We have to use -1 as the „don't include“ return value, as call_user_func() will return FALSE
	 * If calling the method succeeded and thus we can't use that as a return value.
	 *
	 * @param string $itemName
	 * @param string $itemIdentifier
	 * @param string $parentIdentifier
	 * @param array $additionalInformation Additional information (driver dependent) about the inspected item
	 * @param \TYPO3\CMS\Core\Resource\Driver\AbstractDriver $driverInstance
	 * @return boolean|integer -1 if the file should not be included in a listing
	 */
	static public function filterHiddenFilesAndFolders($itemName, $itemIdentifier, $parentIdentifier, array $additionalInformation, \TYPO3\CMS\Core\Resource\Driver\AbstractDriver $driverInstance) {
		// Only apply the filter if you want to hide the hidden files
		if (self::$showHiddenFilesAndFolders === FALSE && substr($itemName, 0, 1) == '.') {
			return -1;
		} else {
			return TRUE;
		}
	}

	/**
	 * Gets the info whether the hidden files are also displayed currently
	 *
	 * @static
	 * @return boolean
	 */
	static public function getShowHiddenFilesAndFolders() {
		return self::$showHiddenFilesAndFolders;
	}

	/**
	 * set the flag to show (or hide) the hidden files
	 *
	 * @static
	 * @param boolean $showHiddenFilesAndFolders
	 * @return boolean
	 */
	static public function setShowHiddenFilesAndFolders($showHiddenFilesAndFolders) {
		return self::$showHiddenFilesAndFolders = (bool) $showHiddenFilesAndFolders;
	}

}


?>