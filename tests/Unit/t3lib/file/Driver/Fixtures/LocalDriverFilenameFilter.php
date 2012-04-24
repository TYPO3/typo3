<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 * Fixture class for the filename filters in the local driver.
 */
class t3lib_file_Tests_Driver_Fixtures_LocalDriverFilenameFilter {
	/**
	 * @param string $itemName
	 * @param string $itemIdentifier
	 * @param string $parentIdentifier
	 * @param t3lib_file_Driver_AbstractDriver $driverInstance
	 * @return boolean|integer
	 */
	public static function filterFilename($itemName, $itemIdentifier, $parentIdentifier, array $additionalInformation, t3lib_file_Driver_AbstractDriver $driverInstance) {
		if ($itemName == 'fileA' || $itemName == 'folderA/') {
			return -1;
		} else {
			return TRUE;
		}
	}
}

?>