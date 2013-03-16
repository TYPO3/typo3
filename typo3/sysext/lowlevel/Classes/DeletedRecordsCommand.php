<?php
namespace TYPO3\CMS\Lowlevel;

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
 * Cleaner module: Deleted records
 * User function called from tx_lowlevel_cleaner_core configured in ext_localconf.php
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Looking for Deleted records
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class DeletedRecordsCommand extends CleanerCommand {

	/**
	 * Constructor
	 *
	 * @todo Define visibility
	 */
	public function __construct() {
		parent::__construct();
		// Setting up help:
		$this->cli_options[] = array('--echotree level', 'When "level" is set to 1 or higher you will see the page of the page tree outputted as it is traversed. A value of 2 for "level" will show even more information.');
		$this->cli_options[] = array('--pid id', 'Setting start page in page tree. Default is the page tree root, 0 (zero)');
		$this->cli_options[] = array('--depth int', 'Setting traversal depth. 0 (zero) will only analyse start page (see --pid), 1 will traverse one level of subpages etc.');
		$this->cli_help['name'] = 'deleted -- To find and flush deleted records in the page tree';
		$this->cli_help['description'] = trim('
Traversing page tree and finding deleted records

Automatic Repair:
Although deleted records are not errors to be repaired, this tool allows you to flush the deleted records completely from the system as an automatic action. Limiting this lookup by --pid and --depth can help you to narrow in the operation to a part of the page tree.
');
		$this->cli_help['examples'] = '';
	}

	/**
	 * Find orphan records
	 * VERY CPU and memory intensive since it will look up the whole page tree!
	 *
	 * @return array
	 * @todo Define visibility
	 */
	public function main() {
		global $TYPO3_DB;
		// Initialize result array:
		$resultArray = array(
			'message' => $this->cli_help['name'] . LF . LF . $this->cli_help['description'],
			'headers' => array(
				'deleted' => array('Index of deleted records', 'These are records from the page tree having the deleted-flag set. The --AUTOFIX option will flush them completely!', 1)
			),
			'deleted' => array()
		);
		$startingPoint = $this->cli_isArg('--pid') ? \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->cli_argValue('--pid'), 0) : 0;
		$depth = $this->cli_isArg('--depth') ? \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->cli_argValue('--depth'), 0) : 1000;
		$this->genTree($startingPoint, $depth, (int) $this->cli_argValue('--echotree'));
		$resultArray['deleted'] = $this->recStats['deleted'];
		return $resultArray;
	}

	/**
	 * Mandatory autofix function
	 * Will run auto-fix on the result array. Echos status during processing.
	 *
	 * @param array $resultArray Result array from main() function
	 * @return void
	 * @todo Define visibility
	 */
	public function main_autoFix($resultArray) {
		// Putting "tx_templavoila_datastructure" table in the bottom:
		if (isset($resultArray['deleted']['tx_templavoila_datastructure'])) {
			$_tx_templavoila_datastructure = $resultArray['deleted']['tx_templavoila_datastructure'];
			unset($resultArray['deleted']['tx_templavoila_datastructure']);
			$resultArray['deleted']['tx_templavoila_datastructure'] = $_tx_templavoila_datastructure;
		}
		// Putting "pages" table in the bottom:
		if (isset($resultArray['deleted']['pages'])) {
			$_pages = $resultArray['deleted']['pages'];
			unset($resultArray['deleted']['pages']);
			// To delete sub pages first assuming they are accumulated from top of page tree.
			$resultArray['deleted']['pages'] = array_reverse($_pages);
		}
		// Traversing records:
		foreach ($resultArray['deleted'] as $table => $list) {
			echo 'Flushing deleted records from table "' . $table . '":' . LF;
			foreach ($list as $uid) {
				echo '	Flushing record "' . $table . ':' . $uid . '": ';
				if ($bypass = $this->cli_noExecutionCheck($table . ':' . $uid)) {
					echo $bypass;
				} else {
					// Execute CMD array:
					$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
					$tce->stripslashes_values = FALSE;
					$tce->start(array(), array());
					// Notice, we are deleting pages with no regard to subpages/subrecords - we do this since they
					// should also be included in the set of deleted pages of course (no un-deleted record can exist
					// under a deleted page...)
					$tce->deleteRecord($table, $uid, TRUE, TRUE);
					// Return errors if any:
					if (count($tce->errorLog)) {
						echo '	ERROR from "TCEmain":' . LF . 'TCEmain:' . implode((LF . 'TCEmain:'), $tce->errorLog);
					} else {
						echo 'DONE';
					}
				}
				echo LF;
			}
		}
	}

}


?>