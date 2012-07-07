<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2011 Soren Malling <soren.malling@gmail.com>
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

require_once(t3lib_extMgm::extPath('css_styled_content') . 'pi1/class.tx_cssstyledcontent_pi1.php');

/**
 * Contains the update class for converting table content elements to text.
 *
 * @author Soren Malling <soren.malling@gmail.com>
 * @version
 */
class tx_coreupdates_converttableandbullets extends Tx_Install_Updates_Base {
	protected $title = 'Convert "Table" and "Bullets" content elements to "Text" element with RTE content';


	/**
	 * Checks if an update is needed
	 *
	 * @param string &$description The description for the update
	 * @return boolean whether an update is needed (TRUE) or not (FALSE)
	 */
	public function checkForUpdate(&$description) {
		$result = FALSE;
		$description = 'Convert all tt_content element of CType "table" and "bullets" to CType = Text';

		if ($this->versionNumber >= 4003000) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'tt_content', 'CTYPE IN (\'table\', \'bullets\')');
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
				$result = TRUE;
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
		return $result;
	}


	/**
	 * Performs the database update. Converts all table and bullet content
	 * elements to RTE text content elements.
	 *
	 * @param array &$dbQueries Queries done in this update
	 * @param mixed &$customMessages Custom messages
	 * @return boolean whether it worked (TRUE) or not (FALSE)
	 */
	public function performUpdate(&$dbQueries, &$customMessages) {
		$result = FALSE;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tt_content', 'CType IN (\'table\', \'bullets\')');
			// Get the last executed query
		$dbQueries[] = str_replace('\n', ' ', htmlspecialchars($GLOBALS['TYPO3_DB']->debug_lastBuiltQuery));

		$contentObject = t3lib_div::makeInstance('tslib_cObj');
		$renderer = t3lib_div::makeInstance('tx_cssstyledcontent_pi1');
		foreach ($res as $row) {
			$contentObject->start($row);
			$renderer->cObj = $contentObject;
			if ($row['CType'] == 'table') {
				$bodytext = $renderer->render_table($row['bodytext'], array());
			}
			if ($row['CType'] == 'bullets') {
				debug($renderer->render_bullets($row['bodytext'], array()), 'output');
				$bodytext = $renderer->render_bullets($row['bodytext'], array());
			}
			$updateArray = array(
				'bodytext' => trim($bodytext),
				'CType' => 'text'
			);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_content', 'uid=' . $row['uid'], $updateArray);

				// Get the last executed query
			$dbQueries[] = str_replace('\n', ' ', htmlspecialchars($GLOBALS['TYPO3_DB']->debug_lastBuiltQuery));
		}

		if ($GLOBALS['TYPO3_DB']->sql_error()) {
			$customMessages = 'SQL-ERROR: ' . htmlspecialchars($GLOBALS['TYPO3_DB']->sql_error());
		} else {
			$result = TRUE;
		}

		return $result;
	}
}
?>