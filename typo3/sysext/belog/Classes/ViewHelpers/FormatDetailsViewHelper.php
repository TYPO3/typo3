<?php
namespace TYPO3\CMS\Belog\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Create detail string from log entry
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class FormatDetailsViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Create formatted detail string from log row.
	 *
	 * The method handles two properties of the model: details and logData
	 * Details is a string with possible %s placeholders, and logData an array
	 * with the substitutions.
	 * Furthermore, possible files in logData are stripped to their basename if
	 * the action logged was a file action
	 *
	 * @param \TYPO3\CMS\Belog\Domain\Model\LogEntry $logEntry
	 * @return string Formatted details
	 */
	public function render(\TYPO3\CMS\Belog\Domain\Model\LogEntry $logEntry) {
		$detailString = $logEntry->getDetails();
		$substitutes = $logEntry->getLogData();
		// Strip pathes from file names if the log was a file action
		if ($logEntry->getType() === 2) {
			$substitutes = $this->stripPathFromFilenames($substitutes);
		}
		// Substitute
		$detailString = vsprintf($detailString, $substitutes);
		// Remove possible pending other %s
		$detailString = str_replace('%s', '', $detailString);
		return htmlspecialchars($detailString);
	}

	/**
	 * Strips path from array of file names
	 *
	 * @param array $files
	 * @return array
	 */
	protected function stripPathFromFilenames(array $files = array()) {
		foreach ($files as $key => $file) {
			$files[$key] = basename($file);
		}
		return $files;
	}

}

?>