<?php
namespace TYPO3\CMS\Install\CoreUpdates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Contains the update class to split existing image_link field by comma and
 * switch to newlines.
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class ImagelinkUpdate extends \TYPO3\CMS\Install\Updates\AbstractUpdate {

	protected $title = 'Update Existing image links';

	/**
	 * Checks if an update is needed
	 *
	 * @param 	string		&$description: The description for the update
	 * @return 	boolean		TRUE if an update is needed, FALSE otherwise
	 */
	public function checkForUpdate(&$description) {
		$description = 'Since TYPO3 4.5 links to images of "Image" and "Text with image" content elements are separated by newline and not by comma anymore. This update converts existing comma separated links to the new form.';
		$result = FALSE;
		if ($this->versionNumber >= 4005000) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tt_content', 'image_link<>\'\' AND image_link LIKE \'%,%\' AND image_link NOT LIKE \'%\\n%\'', '', '', '1');
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
				$result = TRUE;
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $result;
	}

	/**
	 * Performs the database update.
	 *
	 * @param 	array		&$dbQueries: queries done in this update
	 * @param 	mixed		&$customMessages: custom messages
	 * @return 	boolean		TRUE on success, FALSE on error
	 */
	public function performUpdate(array &$dbQueries, &$customMessages) {
		$result = TRUE;
		if ($this->versionNumber >= 4005000) {
			$affectedRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, image_link', 'tt_content', 'image_link<>\'\' AND image_link LIKE \'%,%\' AND image_link NOT LIKE \'%\\n%\'');
			foreach ($affectedRows as $row) {
				$newImageLink = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $row['image_link']);
				$newImageLink = implode(LF, $newImageLink);
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_content', 'uid=' . $row['uid'], array('image_link' => $newImageLink));
				$dbQueries[] = str_replace(LF, ' ', $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery);
				if ($GLOBALS['TYPO3_DB']->sql_error()) {
					$customMessages = 'SQL-ERROR: ' . htmlspecialchars($GLOBALS['TYPO3_DB']->sql_error());
					$result = $result & FALSE;
				}
			}
		}
		return $result;
	}

}


?>