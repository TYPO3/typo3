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
 * Cleaner module: cleanflexform
 * User function called from tx_lowlevel_cleaner_core configured in ext_localconf.php
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * cleanflexform
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class CleanFlexformCommand extends CleanerCommand {

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
		$this->cli_help['name'] = 'cleanflexform -- Find flexform fields with unclean XML';
		$this->cli_help['description'] = trim('
Traversing page tree and finding records with FlexForm fields with XML that could be cleaned up. This will just remove obsolete data garbage.

Automatic Repair:
Cleaning XML for FlexForm fields.
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
				'dirty' => array('', '', 2)
			),
			'dirty' => array()
		);
		$startingPoint = $this->cli_isArg('--pid') ? \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->cli_argValue('--pid'), 0) : 0;
		$depth = $this->cli_isArg('--depth') ? \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->cli_argValue('--depth'), 0) : 1000;
		$this->cleanFlexForm_dirtyFields = &$resultArray['dirty'];
		// Do not repair flexform data in deleted records.
		$this->genTree_traverseDeleted = FALSE;
		$this->genTree($startingPoint, $depth, (int) $this->cli_argValue('--echotree'), 'main_parseTreeCallBack');
		asort($resultArray);
		return $resultArray;
	}

	/**
	 * Call back function for page tree traversal!
	 *
	 * @param string $tableName Table name
	 * @param integer $uid UID of record in processing
	 * @param integer $echoLevel Echo level  (see calling function
	 * @param string $versionSwapmode Version swap mode on that level (see calling function
	 * @param integer $rootIsVersion Is root version (see calling function
	 * @return void
	 * @todo Define visibility
	 */
	public function main_parseTreeCallBack($tableName, $uid, $echoLevel, $versionSwapmode, $rootIsVersion) {
		foreach ($GLOBALS['TCA'][$tableName]['columns'] as $colName => $config) {
			if ($config['config']['type'] == 'flex') {
				if ($echoLevel > 2) {
					echo LF . '			[cleanflexform:] Field "' . $colName . '" in ' . $tableName . ':' . $uid . ' was a flexform and...';
				}
				$recRow = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordRaw($tableName, 'uid=' . intval($uid));
				$flexObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');
				if ($recRow[$colName]) {
					// Clean XML:
					$newXML = $flexObj->cleanFlexFormXML($tableName, $colName, $recRow);
					if (md5($recRow[$colName]) != md5($newXML)) {
						if ($echoLevel > 2) {
							echo ' was DIRTY, needs cleanup!';
						}
						$this->cleanFlexForm_dirtyFields[\TYPO3\CMS\Core\Utility\GeneralUtility::shortMd5($tableName . ':' . $uid . ':' . $colName)] = $tableName . ':' . $uid . ':' . $colName;
					} else {
						if ($echoLevel > 2) {
							echo ' was CLEAN';
						}
					}
				} elseif ($echoLevel > 2) {
					echo ' was EMPTY';
				}
			}
		}
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
		foreach ($resultArray['dirty'] as $fieldID) {
			list($table, $uid, $field) = explode(':', $fieldID);
			echo 'Cleaning XML in "' . $fieldID . '": ';
			if ($bypass = $this->cli_noExecutionCheck($fieldID)) {
				echo $bypass;
			} else {
				// Clean XML:
				$data = array();
				$recRow = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordRaw($table, 'uid=' . intval($uid));
				$flexObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');
				if ($recRow[$field]) {
					$data[$table][$uid][$field] = $flexObj->cleanFlexFormXML($table, $field, $recRow);
				}
				// Execute Data array:
				$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
				$tce->stripslashes_values = FALSE;
				$tce->dontProcessTransformations = TRUE;
				$tce->bypassWorkspaceRestrictions = TRUE;
				$tce->bypassFileHandling = TRUE;
				// Check has been done previously that there is a backend user which is Admin and also in live workspace
				$tce->start($data, array());
				$tce->process_datamap();
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


?>