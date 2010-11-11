<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Marco Bresch (marco.bresch@starfinanz.de)
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

class tx_Workspaces_Service_GridData {

	private $dataArray = array();
	private $sort = '';
	private $sortDir = '';

	public function generateGridListFromVersions($versions, $parameter) {

		$id = isset($parameter->id) ? intval($parameter->id) : 0;
		$depth = isset($parameter->depth) ? intval($parameter->depth) : 999;
		$qurey = isset($parameter->query) ? $parameter->query : '';
		$start = isset($parameter->start) ? intval($parameter->start) : 0;
		$limit = isset($parameter->limit) ? intval($parameter->limit) : 10;
		$this->sort = isset($parameter->sort) ? $parameter->sort : 't3ver_oid';
		$this->sortDir = isset($parameter->dir) ? $parameter->dir : 'ASC';

		$data = array();
		$data['data'] = array();

		$this->generateDataArray($versions);

		$data['total'] = count($this->dataArray);
		$data['data'] = $this->getDataArray($start, $limit);

		$pathLen = is_numeric($GLOBALS['BE_USER']->uc['titleLen']) ? $GLOBALS['BE_USER']->uc['titleLen'] : 30;

		return $data;
	}

	private function generateDataArray($versions)	{

		require_once('Stages.php');
		$stagesObj = t3lib_div::makeInstance('Tx_Workspaces_Service_Stages');
		$versionArray = array();

		foreach($versions as $table => $records) {
			$versionArray = array('table' => $table);

			foreach($records as $record) {

				$origRecord = t3lib_BEFunc::getRecord($table, $record['t3ver_oid']);
				$versionRecord = t3lib_BEFunc::getRecord($table, $record['uid']);

				$pctChange = $this->calculateChangePercentage($table, $origRecord, $versionRecord);
				$versionArray['uid'] = $record['uid'];
				$versionArray['workspace'] = $versionRecord['t3ver_id'];
				$versionArray['label_Workspace'] = $versionRecord[$GLOBALS['TCA'][$table]['ctrl']['label']];
				$versionArray['label_Live'] = $origRecord[$GLOBALS['TCA'][$table]['ctrl']['label']];
				$versionArray['label_Stage'] = $stagesObj->getStageTitle($versionRecord['t3ver_stage']);
				$versionArray['change'] = $pctChange;
				$versionArray['path_Live'] = t3lib_BEfunc::getRecordPath($record['livepid'], '', 999);
				$versionArray['path_Workspace'] = t3lib_BEfunc::getRecordPath($record['wspid'],'', 999);
				$versionArray['workspace_Title'] = tx_Workspaces_Service_Workspaces::getWorkspaceTitle($versionRecord['t3ver_wsid']);

				$versionArray['workspace_Tstamp'] = $versionRecord['tstamp'];
				$versionArray['workspace_Formated_Tstamp'] = t3lib_BEfunc::datetime($versionRecord['tstamp']);
				$versionArray['t3ver_oid'] = $record['t3ver_oid'];
				$versionArray['livepid'] = $record['livepid'];
				$versionArray['stage'] = $versionRecord['t3ver_stage'];
				$versionArray['icon_Live'] = t3lib_iconWorks::mapRecordTypeToSpriteIconClass($table, $record);
				$versionArray['icon_Workspace'] = t3lib_iconWorks::mapRecordTypeToSpriteIconClass($table, $versionRecord);

				$versionArray['allowedAction_nextStage'] = $stagesObj->isNextStageAllowedForUser($versionRecord['t3ver_stage']);
				$versionArray['allowedAction_prevStage'] = $stagesObj->isPrevStageAllowedForUser($versionRecord['t3ver_stage']);
				$versionArray['allowedAction_swap'] = TRUE;
				$versionArray['allowedAction_delete']= TRUE;
				$versionArray['allowedAction_view'] = TRUE;
				$versionArray['allowedAction_edit'] = TRUE;
				$versionArray['allowedAction_editVersionedPage'] = TRUE;

				if (isset($GLOBALS['TCA'][$table]['columns']['hidden']))	{
					$versionArray['state_Workspace'] = $this->workspaceState($versionRecord['t3ver_state'], $origRecord['hidden'], $versionRecord['hidden']);
				} else {
					$versionArray['state_Workspace'] = $this->workspaceState($versionRecord['t3ver_state']);
				}

				$this->dataArray[] = $versionArray;

			}
		}
		$this->sortDataArray();
	}


	private function getDataArray($start, $limit)	{
		$dataArrayPart = array();
		$end = $start + $limit < count($this->dataArray) ? $start + $limit : count($this->dataArray);

		for ($i = $start; $i < $end; $i++)	{
			$dataArrayPart[] = $this->dataArray[$i];
		}

		return $dataArrayPart;
	}


	private function sortDataArray()	{
		switch ($this->sort) {
			case 'uid';
			case 'change';
			case 'workspace_Tstamp';
			case 't3ver_oid';
			case 'liveid';
			case 'lifepid':
				usort($this->dataArray, array($this, 'intSort'));
				break;
			case 'label_Workspace';
			case 'label_Live';
			case 'label_Stage';
			case 'workspace_Title';
			case 'path_Live';
			case 'path_Workspace':
				usort($this->dataArray, array($this, 'stringSort'));
				break;
		}
	}


	private function intSort($a, $b) {
		if ($a[$this->sort] == $b[$this->sort]) {
			return 0;
		}
		if ($this->sortDir == 'ASC') {
			return ($a[$this->sort] < $b[$this->sort]) ? -1 : 1;
		}
		elseif ($this->sortDir == 'DESC') {
			return ($a[$this->sort] > $b[$this->sort]) ? -1 : 1;
		}
		return 0; //ToDo: Throw Exception
	}


	private function stringSort($a, $b) {
		if ($a[$this->sort] == $b[$this->sort]) {
			return 0;
		}
		if ($this->sortDir == 'ASC') {
			return (strcasecmp($a[$this->sort], $b[$this->sort]));
		}
		elseif ($this->sortDir == 'DESC') {
			return (strcasecmp($a[$this->sort], $b[$this->sort]) * (-1));
		}
		return 0; //ToDo: Throw Exception
	}


	public function calculateChangePercentage($table, $diff_1_record, $diff_2_record) {
		global $TCA, $LANG;

		// Initialize:
		$pctChange = 0;
		$pctChangeArr = array();

		// Check that records are arrays:
		if (is_array($diff_1_record) && is_array($diff_2_record))	{

			// Load full table description
			t3lib_div::loadTCA($table);

			// Initialize variables to pick up string lengths in:
			$allStrLen = 0;
			$diffStrLen = 0;

			$pctSimilar = 0;

			// Traversing the first record and process all fields which are editable:
			foreach($diff_1_record as $fN => $fV)	{
				if ($TCA[$table]['columns'][$fN] && $TCA[$table]['columns'][$fN]['config']['type']!='passthrough' && !t3lib_div::inList('t3ver_label',$fN))	{

					// Check if it is files:
					$isFiles = FALSE;
					if (strcmp(trim($diff_1_record[$fN]),trim($diff_2_record[$fN])) &&
					$TCA[$table]['columns'][$fN]['config']['type']=='group' &&
					$TCA[$table]['columns'][$fN]['config']['internal_type']=='file')	{

						// Initialize:
						$uploadFolder = $TCA[$table]['columns'][$fN]['config']['uploadfolder'];
						$files1 = array_flip(t3lib_div::trimExplode(',', $diff_1_record[$fN],1));
						$files2 = array_flip(t3lib_div::trimExplode(',', $diff_2_record[$fN],1));

						// Traverse filenames and read their md5 sum:
						foreach($files1 as $filename => $tmp)	{
							$files1[$filename] = @is_file(PATH_site.$uploadFolder.'/'.$filename) ? md5(t3lib_div::getUrl(PATH_site.$uploadFolder.'/'.$filename)) : $filename;
						}
						foreach($files2 as $filename => $tmp)	{
							$files2[$filename] = @is_file(PATH_site.$uploadFolder.'/'.$filename) ? md5(t3lib_div::getUrl(PATH_site.$uploadFolder.'/'.$filename)) : $filename;
						}

						// Implode MD5 sums and set flag:
						$diff_1_record[$fN] = implode(' ',$files1);
						$diff_2_record[$fN] = implode(' ',$files2);
						$isFiles = TRUE;
					}

					// If there is a change of value:
					if (strcmp(trim($diff_1_record[$fN]),trim($diff_2_record[$fN])))	{

						// Get the best visual presentation of the value to calcutate differences:
						$val1 = t3lib_BEfunc::getProcessedValue($table,$fN,$diff_1_record[$fN],0,1);
						$val2 = t3lib_BEfunc::getProcessedValue($table,$fN,$diff_2_record[$fN],0,1);

						$cntSimilarChars = similar_text($val1,$val2,$pctSimilar);
						$pctChangeArr[] = $pctSimilar > 0 ? abs($pctSimilar-100) : 0;

						// If the compared values were files, substituted MD5 hashes:
						if ($isFiles)	{
							$allFiles = array_merge($files1,$files2);
							foreach($allFiles as $filename => $token)	{
								if (strlen($token)==32 && strstr($diffres,$token))	{
									$filename =
									t3lib_BEfunc::thumbCode(array($fN=>$filename),$table,$fN,$this->doc->backPath).
									$filename;
									$diffres = str_replace($token,$filename,$diffres);
								}
							}
						}

					}
				}
			}


			// Calculate final change percentage:
			if(is_array($pctChangeArr))	{
				$sumPctChange = 0;
				foreach($pctChangeArr as $singlePctChange)	{
					$sumPctChange += $singlePctChange;
				}
				count($pctChangeArr) > 0 ? $pctChange = round($sumPctChange / count($pctChangeArr)) : $pctChange = 0;
			}

		}
		// Return value:
		return $pctChange;

	}


	/**
	 *
	 * @param	integer	stateId of offline record
	 * @param	integer	hidden flag of online record
	 * @param	integer	hidden flag of offline record
	 * @return	string
	 */
	private function workspaceState($stateId, $hiddenOnline = FALSE, $hiddenOffline = FALSE)	{
		$state = FALSE;

		switch($stateId)	{
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

		if ($hiddenOnline == 0 && $hiddenOffline == 1)	{
			$state = 'hidden';
		} elseif ($hiddenOnline == 1 && $hiddenOffline == 0)	{
			$state = 'unhidden';
		}

		return $state;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Service/GridData.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Service/GridData.php']);
}
?>