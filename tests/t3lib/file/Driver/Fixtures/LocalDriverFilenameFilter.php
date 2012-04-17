<?php

/**
 * Fixture class for the filename filters in the local driver.
 */
class t3lib_file_Tests_Driver_Fixtures_LocalDriverFilenameFilter {

	public static function filterFilename($itemName, $itemIdentifier, $parentIdentifier, t3lib_file_Driver_AbstractDriver $driverInstance) {
		if ($itemName == 'fileA' || $itemName == 'folderA/') {
			return -1;
		} else {
			return TRUE;
		}
	}
}