<?php
namespace TYPO3\CMS\Belog\ViewHelpers;

/**
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
