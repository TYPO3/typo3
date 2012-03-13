<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Christian Kuhn <lolli@schwarzbu.ch>
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
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Create detail string from log row
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @package TYPO3
 * @subpackage belog
 */
class Tx_Belog_ViewHelpers_FormatDetailsViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * Create formatted detail string from log row.
	 *
	 * The method handles two properties of the model: details and logData
	 * Details is a string with possible %s placeholders, and logData an array
	 * with the substitutions.
	 * Furthermore, possible files in logData are stripped to their basename if
	 * the action logged was a file action
	 *
	 * This method was crafted from getDetails() of class t3lib_bedisplaylog.
	 *
	 * @param Tx_Belog_Domain_Model_SysLog $logRow
	 * @return string Formatted details
	 */
	public function render(Tx_Belog_Domain_Model_SysLog $logRow) {
		$detailString = $logRow->getDetails();
		$substitutes = $logRow->getLogData();

			// Strip pathes from file names if the log was a file action
		if (is_array($substitutes) && $logRow->getType() === 2) {
			$substitutes = $this->stripPathFromFiles($substitutes);
		}

			// Substitute
		if (is_array($substitutes)) {
			$detailString = vsprintf($detailString, $substitutes);
		}

			// Remove possible pending other %s
		$detailString = str_replace('%s', '', $detailString);

		return htmlspecialchars($detailString);
	}

	/**
	 * Strip path from files
	 *
	 * @param array $files
	 * @return array
	 */
	protected function stripPathFromFiles(array $files = array()) {
		foreach ($files as $key => $file) {
			$files[$key] = basename($file);
		}
		return $files;
	}
}
?>
