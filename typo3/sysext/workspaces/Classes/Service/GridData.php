<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 * @package Workspaces
 * @subpackage Service
 */
class tx_Workspaces_Service_GridData {
	protected $dataArray = array();
	protected $sort = '';
	protected $sortDir = '';

	/**
	 * Generates grid list array from given versions.
	 *
	 * @param array $versions All records uids etc. First key is table name, second key incremental integer. Records are associative arrays with uid, t3ver_oid and t3ver_swapmode fields. The pid of the online record is found as "livepid" the pid of the offline record is found in "wspid"
	 * @param object $parameter
	 * @return array
	 */
	public function generateGridListFromVersions($versions, $parameter) {

			// Read the given parameters from grid. If the parameter is not set use default values.
		$filterTxt = isset($parameter->filterTxt) ? $parameter->filterTxt : '';
		$start = isset($parameter->start) ? intval($parameter->start) : 0;
		$limit = isset($parameter->limit) ? intval($parameter->limit) : 10;
		$this->sort = isset($parameter->sort) ? $parameter->sort : 't3ver_oid';
		$this->sortDir = isset($parameter->dir) ? $parameter->dir : 'ASC';

		$data = array();
		$data['data'] = array();

		$this->generateDataArray($versions, $filterTxt);

		$data['total'] = count($this->dataArray);
		$data['data'] = $this->getDataArray($start, $limit);

		return $data;
	}

	/**
	 * Generates grid list array from given versions.
	 *
	 * @param array $versions
	 * @param string $filterTxt
	 * @return void
	 */
	protected function generateDataArray(array $versions, $filterTxt) {
		/** @var $stagesObj Tx_Workspaces_Service_Stages */
		$stagesObj = t3lib_div::makeInstance('Tx_Workspaces_Service_Stages');
		
		/** @var $workspacesObj Tx_Workspaces_Service_Workspaces */
		$workspacesObj = t3lib_div::makeInstance('Tx_Workspaces_Service_Workspaces');
		$availableWorkspaces = $workspacesObj->getAvailableWorkspaces();

		foreach ($versions as $table => $records) {
			$versionArray = array('table' => $table);

			foreach ($records as $record) {

				$origRecord = t3lib_BEFunc::getRecord($table, $record['t3ver_oid']);
				$versionRecord = t3lib_BEFunc::getRecord($table, $record['uid']);

				// check the given version is from an available workspace
				if (array_key_exists($versionRecord['t3ver_wsid'], $availableWorkspaces)) {
					
					if (isset($GLOBALS['TCA'][$table]['columns']['hidden'])) {
						$recordState = $this->workspaceState($versionRecord['t3ver_state'], $origRecord['hidden'], $versionRecord['hidden']);
					} else {
						$recordState = $this->workspaceState($versionRecord['t3ver_state']);
					}
					$isDeletedPage = ($table == 'pages' && $recordState == 'deleted');

					$pctChange = $this->calculateChangePercentage($table, $origRecord, $versionRecord);
					$versionArray['uid'] = $record['uid'];
					$versionArray['workspace'] = $versionRecord['t3ver_id'];
					$versionArray['label_Workspace'] = $versionRecord[$GLOBALS['TCA'][$table]['ctrl']['label']];
					$versionArray['label_Live'] = $origRecord[$GLOBALS['TCA'][$table]['ctrl']['label']];
					$versionArray['label_Stage'] = $stagesObj->getStageTitle($versionRecord['t3ver_stage']);
					$versionArray['change'] = $pctChange;
					$versionArray['path_Live'] = t3lib_BEfunc::getRecordPath($record['livepid'], '', 999);
					$versionArray['path_Workspace'] = t3lib_BEfunc::getRecordPath($record['wspid'], '', 999);
					$versionArray['workspace_Title'] = tx_Workspaces_Service_Workspaces::getWorkspaceTitle($versionRecord['t3ver_wsid']);

					$versionArray['workspace_Tstamp'] = $versionRecord['tstamp'];
					$versionArray['workspace_Formated_Tstamp'] = t3lib_BEfunc::datetime($versionRecord['tstamp']);
					$versionArray['t3ver_oid'] = $record['t3ver_oid'];
					$versionArray['livepid'] = $record['livepid'];
					$versionArray['stage'] = $versionRecord['t3ver_stage'];
					$versionArray['icon_Live'] = t3lib_iconWorks::mapRecordTypeToSpriteIconClass($table, $origRecord);
					$versionArray['icon_Workspace'] = t3lib_iconWorks::mapRecordTypeToSpriteIconClass($table, $versionRecord);

					$versionArray['allowedAction_nextStage'] = $stagesObj->isNextStageAllowedForUser($versionRecord['t3ver_stage']);
					$versionArray['allowedAction_prevStage'] = $stagesObj->isPrevStageAllowedForUser($versionRecord['t3ver_stage']);
						// @todo hide the actions if the user is not allowed to edit the current stage
					$versionArray['allowedAction_swap'] = $GLOBALS['BE_USER']->workspaceSwapAccess();
					$versionArray['allowedAction_delete'] = TRUE;
						// preview and editing of a deleted page won't work ;)
					$versionArray['allowedAction_view'] = !$isDeletedPage;
					$versionArray['allowedAction_edit'] = !$isDeletedPage;
					$versionArray['allowedAction_editVersionedPage'] = !$isDeletedPage;

					$versionArray['state_Workspace'] = $recordState;

					if ($filterTxt == '' || $this->isFilterTextInVisibleColumns($filterTxt, $versionArray)) {
						$this->dataArray[] = $versionArray;
					}
				}
			}
		}
		$this->sortDataArray();
	}

	/**
	 * Gets the data array by considering the page to be shown in the grid view.
	 *
	 * @param integer $start
	 * @param integer $limit
	 * @return array
	 */
	protected function getDataArray($start, $limit) {
		$dataArrayPart = array();
		$end = $start + $limit < count($this->dataArray) ? $start + $limit : count($this->dataArray);

		for ($i = $start; $i < $end; $i++) {
			$dataArrayPart[] = $this->dataArray[$i];
		}

		return $dataArrayPart;
	}

	/**
	 * Performs sorting on the data array accordant to the
	 * selected column in the grid view to be used for sorting.
	 *
	 * @return void
	 */
	protected function sortDataArray() {
		switch ($this->sort) {
			case 'uid';
			case 'change';
			case 'workspace_Tstamp';
			case 't3ver_oid';
			case 'liveid';
			case 'livepid':
				usort($this->dataArray, array($this, 'intSort'));
				break;
			case 'label_Workspace';
			case 'label_Live';
			case 'label_Stage';
			case 'workspace_Title';
			case 'path_Live':
					// case 'path_Workspace': This is the first sorting attribute
				usort($this->dataArray, array($this, 'stringSort'));
				break;
		}
	}

	/**
	 * Implements individual sorting for columns based on integer comparison.
	 *
	 * @param array $a
	 * @param array $b
	 * @return integer
	 */
	protected function intSort(array $a, array $b) {
			// Als erstes nach dem Pfad sortieren
		$path_cmp = strcasecmp($a['path_Workspace'], $b['path_Workspace']);

		if ($path_cmp < 0) {
			return $path_cmp;
		} elseif ($path_cmp == 0) {
			if ($a[$this->sort] == $b[$this->sort]) {
				return 0;
			}
			if ($this->sortDir == 'ASC') {
				return ($a[$this->sort] < $b[$this->sort]) ? -1 : 1;
			} elseif ($this->sortDir == 'DESC') {
				return ($a[$this->sort] > $b[$this->sort]) ? -1 : 1;
			}
		} elseif ($path_cmp > 0) {
			return $path_cmp;
		}
		return 0; //ToDo: Throw Exception
	}

	/**
	 * Implements individual sorting for columns based on string comparison.
	 *
	 * @param  $a
	 * @param  $b
	 * @return int
	 */
	protected function stringSort($a, $b) {
		$path_cmp = strcasecmp($a['path_Workspace'], $b['path_Workspace']);

		if ($path_cmp < 0) {
			return $path_cmp;
		} elseif ($path_cmp == 0) {
			if ($a[$this->sort] == $b[$this->sort]) {
				return 0;
			}
			if ($this->sortDir == 'ASC') {
				return (strcasecmp($a[$this->sort], $b[$this->sort]));
			} elseif ($this->sortDir == 'DESC') {
				return (strcasecmp($a[$this->sort], $b[$this->sort]) * (-1));
			}
		} elseif ($path_cmp > 0) {
			return $path_cmp;
		}
		return 0; //ToDo: Throw Exception
	}

	/**
	 * Determines whether the text used to filter the results is part of
	 * a column that is visible in the grid view.
	 *
	 * @param string $filterText
	 * @param array $versionArray
	 * @return boolean
	 */
	protected function isFilterTextInVisibleColumns($filterText, array $versionArray) {
		if (is_array($GLOBALS['BE_USER']->uc['moduleData']['Workspaces']['columns'])) {
			foreach ($GLOBALS['BE_USER']->uc['moduleData']['Workspaces']['columns'] as $column => $value) {
				if (isset($value['hidden']) && isset($column) && isset($versionArray[$column])) {
					if ($value['hidden'] == 0) {
						switch ($column) {
							case 'workspace_Tstamp':
								if (stripos($versionArray['workspace_Formated_Tstamp'], $filterText) !== FALSE) {
									return TRUE;
								}
								break;
							case 'change':
								if (stripos(strval($versionArray[$column]), str_replace('%', '', $filterText)) !== FALSE) {
									return TRUE;
								}
								break;
							default:
								if (stripos(strval($versionArray[$column]), $filterText) !== FALSE) {
									return TRUE;
								}
						}
					}
				}
			}
		}
		return FALSE;
	}

	/**
	 * Calculates the percentage of changes between two records.
	 *
	 * @param string $table
	 * @param array $diffRecordOne
	 * @param array $diffRecordTwo
	 * @return integer
	 */
	public function calculateChangePercentage($table, array $diffRecordOne, array $diffRecordTwo) {
		global $TCA;

			// Initialize:
		$changePercentage = 0;
		$changePercentageArray = array();

			// Check that records are arrays:
		if (is_array($diffRecordOne) && is_array($diffRecordTwo)) {

				// Load full table description
			t3lib_div::loadTCA($table);

			$similarityPercentage = 0;

				// Traversing the first record and process all fields which are editable:
			foreach ($diffRecordOne as $fieldName => $fieldValue) {
				if ($TCA[$table]['columns'][$fieldName] && $TCA[$table]['columns'][$fieldName]['config']['type'] != 'passthrough' && !t3lib_div::inList('t3ver_label', $fieldName)) {

					if (strcmp(trim($diffRecordOne[$fieldName]), trim($diffRecordTwo[$fieldName]))
							&& $TCA[$table]['columns'][$fieldName]['config']['type'] == 'group'
							&& $TCA[$table]['columns'][$fieldName]['config']['internal_type'] == 'file'
					) {

							// Initialize:
						$uploadFolder = $TCA[$table]['columns'][$fieldName]['config']['uploadfolder'];
						$files1 = array_flip(t3lib_div::trimExplode(',', $diffRecordOne[$fieldName], 1));
						$files2 = array_flip(t3lib_div::trimExplode(',', $diffRecordTwo[$fieldName], 1));

							// Traverse filenames and read their md5 sum:
						foreach ($files1 as $filename => $tmp) {
							$files1[$filename] = @is_file(PATH_site . $uploadFolder . '/' . $filename) ? md5(t3lib_div::getUrl(PATH_site . $uploadFolder . '/' . $filename)) : $filename;
						}
						foreach ($files2 as $filename => $tmp) {
							$files2[$filename] = @is_file(PATH_site . $uploadFolder . '/' . $filename) ? md5(t3lib_div::getUrl(PATH_site . $uploadFolder . '/' . $filename)) : $filename;
						}

							// Implode MD5 sums and set flag:
						$diffRecordOne[$fieldName] = implode(' ', $files1);
						$diffRecordTwo[$fieldName] = implode(' ', $files2);
					}

						// If there is a change of value:
					if (strcmp(trim($diffRecordOne[$fieldName]), trim($diffRecordTwo[$fieldName]))) {
							// Get the best visual presentation of the value to calculate differences:
						$val1 = t3lib_BEfunc::getProcessedValue($table, $fieldName, $diffRecordOne[$fieldName], 0, 1);
						$val2 = t3lib_BEfunc::getProcessedValue($table, $fieldName, $diffRecordTwo[$fieldName], 0, 1);

						similar_text($val1, $val2, $similarityPercentage);
						$changePercentageArray[] = $similarityPercentage > 0 ? abs($similarityPercentage - 100) : 0;
					}
				}
			}

				// Calculate final change percentage:
			if (is_array($changePercentageArray)) {
				$sumPctChange = 0;
				foreach ($changePercentageArray as $singlePctChange) {
					$sumPctChange += $singlePctChange;
				}
				count($changePercentageArray) > 0 ? $changePercentage = round($sumPctChange / count($changePercentageArray)) : $changePercentage = 0;
			}

		}
		return $changePercentage;
	}

	/**
	 * Gets the state of a given state value.
	 *
	 * @param	integer	stateId of offline record
	 * @param	boolean	hidden flag of online record
	 * @param	boolean	hidden flag of offline record
	 * @return	string
	 */
	 protected function workspaceState($stateId, $hiddenOnline = FALSE, $hiddenOffline = FALSE) {
		switch ($stateId) {
			case -1:
				$state = 'new';
				break;
			case 1:
			case 2:
				$state = 'deleted';
				break;
			case 4:
				$state = 'moved';
				break;
			default:
				$state = 'modified';
		}

		if ($hiddenOnline == 0 && $hiddenOffline == 1) {
			$state = 'hidden';
		} elseif ($hiddenOnline == 1 && $hiddenOffline == 0) {
			$state = 'unhidden';
		}

		return $state;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Service/GridData.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Service/GridData.php']);
}
?>