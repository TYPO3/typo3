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
 * Cleaner module: Versions of records
 * User function called from tx_lowlevel_cleaner_core configured in ext_localconf.php
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * Looking for versions of records
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class VersionsCommand extends CleanerCommand {

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
		$this->cli_options[] = array('--flush-live', 'If set, not only published versions from Live workspace are flushed, but ALL versions from Live workspace (which are offline of course)');
		$this->cli_help['name'] = 'versions -- To find information about versions and workspaces in the system';
		$this->cli_help['description'] = trim('
Traversing page tree and finding versions, categorizing them by various properties.
Published versions from the Live workspace are registered. So are all offline versions from Live workspace in general. Further, versions in non-existing workspaces are found.

Automatic Repair:
- Deleting (completely) published versions from LIVE workspace OR _all_ offline versions from Live workspace (toogle by --flush-live)
- Resetting workspace for versions where workspace is deleted. (You might want to run this tool again after this operation to clean out those new elements in the Live workspace)
- Deleting unused placeholders
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
				'versions' => array('All versions', 'Showing all versions of records found', 0),
				'versions_published' => array('All published versions', 'This is all records that has been published and can therefore be removed permanently', 1),
				'versions_liveWS' => array('All versions in Live workspace', 'This is all records that are offline versions in the Live workspace. You may wish to flush these if you only use workspaces for versioning since then you might find lots of versions piling up in the live workspace which have simply been disconnected from the workspace before they were published.', 1),
				'versions_lost_workspace' => array('Versions outside a workspace', 'Versions that has lost their connection to a workspace in TYPO3.', 3),
				'versions_inside_versioned_page' => array('Versions in versions', 'Versions inside an already versioned page. Something that is confusing to users and therefore should not happen but is technically possible.', 2),
				'versions_unused_placeholders' => array('Unused placeholder records', 'Placeholder records which are not used anymore by offline versions.', 2),
				'versions_move_placeholders_ok' => array('Move placeholders', 'Move-to placeholder records which has good integrity', 0),
				'versions_move_placeholders_bad' => array('Move placeholders with bad integrity', 'Move-to placeholder records which has bad integrity', 2),
				'versions_move_id_check' => array('Checking if t3ver_move_id is correct', 't3ver_move_id must only be set with online records having t3ver_state=3.', 2)
			),
			'versions' => array()
		);
		$startingPoint = $this->cli_isArg('--pid') ? \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->cli_argValue('--pid'), 0) : 0;
		$depth = $this->cli_isArg('--depth') ? \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->cli_argValue('--depth'), 0) : 1000;
		$this->genTree($startingPoint, $depth, (int) $this->cli_argValue('--echotree'));
		$resultArray['versions'] = $this->recStats['versions'];
		$resultArray['versions_published'] = $this->recStats['versions_published'];
		$resultArray['versions_liveWS'] = $this->recStats['versions_liveWS'];
		$resultArray['versions_lost_workspace'] = $this->recStats['versions_lost_workspace'];
		$resultArray['versions_inside_versioned_page'] = $this->recStats['versions_inside_versioned_page'];
		// Finding all placeholders with no records attached!
		$resultArray['versions_unused_placeholders'] = array();
		foreach ($GLOBALS['TCA'] as $table => $cfg) {
			if ($cfg['ctrl']['versioningWS']) {
				$placeHolders = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,pid', $table, 't3ver_state=1 AND pid>=0' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table));
				foreach ($placeHolders as $phrec) {
					if (count(\TYPO3\CMS\Backend\Utility\BackendUtility::selectVersionsOfRecord($table, $phrec['uid'], 'uid')) <= 1) {
						$resultArray['versions_unused_placeholders'][\TYPO3\CMS\Core\Utility\GeneralUtility::shortmd5($table . ':' . $phrec['uid'])] = $table . ':' . $phrec['uid'];
					}
				}
			}
		}
		asort($resultArray['versions_unused_placeholders']);
		// Finding all move placeholders with inconsistencies:
		$resultArray['versions_move_placeholders_ok'] = array();
		$resultArray['versions_move_placeholders_bad'] = array();
		foreach ($GLOBALS['TCA'] as $table => $cfg) {
			if ((int) $cfg['ctrl']['versioningWS'] >= 2) {
				$placeHolders = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,pid,t3ver_move_id,t3ver_wsid,t3ver_state', $table, 't3ver_state=3 AND pid>=0' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table));
				foreach ($placeHolders as $phrec) {
					$shortID = \TYPO3\CMS\Core\Utility\GeneralUtility::shortmd5($table . ':' . $phrec['uid']);
					if ((int) $phrec['t3ver_wsid'] != 0) {
						$phrecCopy = $phrec;
						if (\TYPO3\CMS\Backend\Utility\BackendUtility::movePlhOL($table, $phrec)) {
							if ($wsAlt = \TYPO3\CMS\Backend\Utility\BackendUtility::getWorkspaceVersionOfRecord($phrecCopy['t3ver_wsid'], $table, $phrec['uid'], 'uid,pid,t3ver_state')) {
								if ($wsAlt['t3ver_state'] != 4) {
									$resultArray['versions_move_placeholders_bad'][$shortID] = array($table . ':' . $phrec['uid'], 'State for version was not "4" as it should be!', $phrecCopy);
								} else {
									$resultArray['versions_move_placeholders_ok'][$shortID] = array(
										$table . ':' . $phrec['uid'],
										'PLH' => $phrecCopy,
										'online' => $phrec,
										'PNT' => $wsAlt
									);
								}
							} else {
								$resultArray['versions_move_placeholders_bad'][$shortID] = array($table . ':' . $phrec['uid'], 'No version was found for online record to be moved. A version must exist.', $phrecCopy);
							}
						} else {
							$resultArray['versions_move_placeholders_bad'][$shortID] = array($table . ':' . $phrec['uid'], 'Did not find online record for "t3ver_move_id" value ' . $phrec['t3ver_move_id'], $phrec);
						}
					} else {
						$resultArray['versions_move_placeholders_bad'][$shortID] = array($table . ':' . $phrec['uid'], 'Placeholder was not assigned a workspace value in t3ver_wsid.', $phrec);
					}
				}
			}
		}
		ksort($resultArray['versions_move_placeholders_ok']);
		ksort($resultArray['versions_move_placeholders_bad']);
		// Finding move_id_check inconsistencies:
		$resultArray['versions_move_id_check'] = array();
		foreach ($GLOBALS['TCA'] as $table => $cfg) {
			if ((int) $cfg['ctrl']['versioningWS'] >= 2) {
				$placeHolders = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,pid,t3ver_move_id,t3ver_wsid,t3ver_state', $table, 't3ver_move_id<>0' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table));
				foreach ($placeHolders as $phrec) {
					if ((int) $phrec['t3ver_state'] == 3) {
						if ($phrec['pid'] != -1) {

						} else {
							$resultArray['versions_move_id_check'][] = array($table . ':' . $phrec['uid'], 'Record was offline, must not be!', $phrec);
						}
					} else {
						$resultArray['versions_move_id_check'][] = array($table . ':' . $phrec['uid'], 'Record had t3ver_move_id set to "' . $phrec['t3ver_move_id'] . '" while having t3ver_state=' . $phrec['t3ver_state'], $phrec);
					}
				}
			}
		}
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
		$kk = $this->cli_isArg('--flush-live') ? 'versions_liveWS' : 'versions_published';
		// Putting "pages" table in the bottom:
		if (isset($resultArray[$kk]['pages'])) {
			$_pages = $resultArray[$kk]['pages'];
			unset($resultArray[$kk]['pages']);
			$resultArray[$kk]['pages'] = $_pages;
		}
		// Traversing records:
		foreach ($resultArray[$kk] as $table => $list) {
			echo 'Flushing published records from table "' . $table . '":' . LF;
			foreach ($list as $uid) {
				echo '	Flushing record "' . $table . ':' . $uid . '": ';
				if ($bypass = $this->cli_noExecutionCheck($table . ':' . $uid)) {
					echo $bypass;
				} else {
					// Execute CMD array:
					$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
					$tce->stripslashes_values = FALSE;
					$tce->start(array(), array());
					$tce->deleteEl($table, $uid, TRUE, TRUE);
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
		// Traverse workspace:
		foreach ($resultArray['versions_lost_workspace'] as $table => $list) {
			echo 'Resetting workspace to zero for records from table "' . $table . '":' . LF;
			foreach ($list as $uid) {
				echo '	Flushing record "' . $table . ':' . $uid . '": ';
				if ($bypass = $this->cli_noExecutionCheck($table . ':' . $uid)) {
					echo $bypass;
				} else {
					$fields_values = array(
						't3ver_wsid' => 0
					);
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid=' . intval($uid), $fields_values);
					echo 'DONE';
				}
				echo LF;
			}
		}
		// Delete unused placeholders
		foreach ($resultArray['versions_unused_placeholders'] as $recID) {
			list($table, $uid) = explode(':', $recID);
			echo 'Deleting unused placeholder (soft) "' . $table . ':' . $uid . '": ';
			if ($bypass = $this->cli_noExecutionCheck($table . ':' . $uid)) {
				echo $bypass;
			} else {
				// Execute CMD array:
				$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
				$tce->stripslashes_values = FALSE;
				$tce->start(array(), array());
				$tce->deleteAction($table, $uid);
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