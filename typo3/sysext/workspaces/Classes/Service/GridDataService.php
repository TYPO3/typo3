<?php
namespace TYPO3\CMS\Workspaces\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
 * Grid data service
 *
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 */
class GridDataService {

	const SIGNAL_GenerateDataArray_BeforeCaching = 'generateDataArray.beforeCaching';
	const SIGNAL_GenerateDataArray_PostProcesss = 'generateDataArray.postProcess';
	const SIGNAL_GetDataArray_PostProcesss = 'getDataArray.postProcess';
	const SIGNAL_SortDataArray_PostProcesss = 'sortDataArray.postProcess';
	/**
	 * Id of the current active workspace.
	 *
	 * @var integer
	 */
	protected $currentWorkspace = NULL;

	/**
	 * Version record information (filtered, sorted and limited)
	 *
	 * @var array
	 */
	protected $dataArray = array();

	/**
	 * Name of the field used for sorting.
	 *
	 * @var string
	 */
	protected $sort = '';

	/**
	 * Direction used for sorting (ASC, DESC).
	 *
	 * @var string
	 */
	protected $sortDir = '';

	/**
	 * @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
	 */
	protected $workspacesCache = NULL;

	/**
	 * @var array
	 */
	protected $systemLanguages;

	/**
	 * @var \TYPO3\CMS\Workspaces\Service\IntegrityService
	 */
	protected $integrityService;

	/**
	 * Generates grid list array from given versions.
	 *
	 * @param array $versions All records uids etc. First key is table name, second key incremental integer. Records are associative arrays with uid and t3ver_oid fields. The pid of the online record is found as "livepid" the pid of the offline record is found in "wspid
	 * @param object $parameter Parameters as submitted by JavaScript component
	 * @param integer $currentWorkspace The current workspace
	 * @return array Version record information (filtered, sorted and limited)
	 * @throws \InvalidArgumentException
	 */
	public function generateGridListFromVersions($versions, $parameter, $currentWorkspace) {
		// Read the given parameters from grid. If the parameter is not set use default values.
		$filterTxt = isset($parameter->filterTxt) ? $parameter->filterTxt : '';
		$start = isset($parameter->start) ? intval($parameter->start) : 0;
		$limit = isset($parameter->limit) ? intval($parameter->limit) : 30;
		$this->sort = isset($parameter->sort) ? $parameter->sort : 't3ver_oid';
		$this->sortDir = isset($parameter->dir) ? $parameter->dir : 'ASC';
		if (is_int($currentWorkspace)) {
			$this->currentWorkspace = $currentWorkspace;
		} else {
			throw new \InvalidArgumentException('No such workspace defined');
		}
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
	 * @param array $versions All available version records
	 * @param string $filterTxt Text to be used to filter record result
	 * @return void
	 */
	protected function generateDataArray(array $versions, $filterTxt) {
		$workspaceAccess = $GLOBALS['BE_USER']->checkWorkspace($GLOBALS['BE_USER']->workspace);
		$swapStage = $workspaceAccess['publish_access'] & 1 ? \TYPO3\CMS\Workspaces\Service\StagesService::STAGE_PUBLISH_ID : 0;
		$swapAccess = $GLOBALS['BE_USER']->workspacePublishAccess($GLOBALS['BE_USER']->workspace) && $GLOBALS['BE_USER']->workspaceSwapAccess();
		$this->initializeWorkspacesCachingFramework();
		// check for dataArray in cache
		if ($this->getDataArrayFromCache($versions, $filterTxt) === FALSE) {
			/** @var $stagesObj \TYPO3\CMS\Workspaces\Service\StagesService */
			$stagesObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\StagesService');
			foreach ($versions as $table => $records) {
				$versionArray = array('table' => $table);
				$hiddenField = $this->getTcaEnableColumnsFieldName($table, 'disabled');
				$isRecordTypeAllowedToModify = $GLOBALS['BE_USER']->check('tables_modify', $table);

				foreach ($records as $record) {
					$origRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $record['t3ver_oid']);
					$versionRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $record['uid']);
					$combinedRecord = \TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord::createFromArrays($table, $origRecord, $versionRecord);
					$this->getIntegrityService()->checkElement($combinedRecord);

					if ($hiddenField !== NULL) {
						$recordState = $this->workspaceState($versionRecord['t3ver_state'], $origRecord[$hiddenField], $versionRecord[$hiddenField]);
					} else {
						$recordState = $this->workspaceState($versionRecord['t3ver_state']);
					}

					$isDeletedPage = $table == 'pages' && $recordState == 'deleted';
					$viewUrl = \TYPO3\CMS\Workspaces\Service\WorkspaceService::viewSingleRecord($table, $record['uid'], $origRecord, $versionRecord);
					$versionArray['id'] = $table . ':' . $record['uid'];
					$versionArray['uid'] = $record['uid'];
					$versionArray['workspace'] = $versionRecord['t3ver_id'];
					$versionArray['label_Workspace'] = htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $versionRecord));
					$versionArray['label_Live'] = htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($table, $origRecord));
					$versionArray['label_Stage'] = htmlspecialchars($stagesObj->getStageTitle($versionRecord['t3ver_stage']));
					$tempStage = $stagesObj->getNextStage($versionRecord['t3ver_stage']);
					$versionArray['label_nextStage'] = htmlspecialchars($stagesObj->getStageTitle($tempStage['uid']));
					$tempStage = $stagesObj->getPrevStage($versionRecord['t3ver_stage']);
					$versionArray['label_prevStage'] = htmlspecialchars($stagesObj->getStageTitle($tempStage['uid']));
					$versionArray['path_Live'] = htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordPath($record['livepid'], '', 999));
					$versionArray['path_Workspace'] = htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecordPath($record['wspid'], '', 999));
					$versionArray['workspace_Title'] = htmlspecialchars(\TYPO3\CMS\Workspaces\Service\WorkspaceService::getWorkspaceTitle($versionRecord['t3ver_wsid']));
					$versionArray['workspace_Tstamp'] = $versionRecord['tstamp'];
					$versionArray['workspace_Formated_Tstamp'] = \TYPO3\CMS\Backend\Utility\BackendUtility::datetime($versionRecord['tstamp']);
					$versionArray['t3ver_oid'] = $record['t3ver_oid'];
					$versionArray['livepid'] = $record['livepid'];
					$versionArray['stage'] = $versionRecord['t3ver_stage'];
					$versionArray['icon_Live'] = \TYPO3\CMS\Backend\Utility\IconUtility::mapRecordTypeToSpriteIconClass($table, $origRecord);
					$versionArray['icon_Workspace'] = \TYPO3\CMS\Backend\Utility\IconUtility::mapRecordTypeToSpriteIconClass($table, $versionRecord);
					$languageValue = $this->getLanguageValue($table, $versionRecord);
					$versionArray['languageValue'] = $languageValue;
					$versionArray['language'] = array(
						'cls' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses($this->getSystemLanguageValue($languageValue, 'flagIcon')),
						'title' => htmlspecialchars($this->getSystemLanguageValue($languageValue, 'title'))
					);
					$versionArray['allowedAction_nextStage'] = $isRecordTypeAllowedToModify && $stagesObj->isNextStageAllowedForUser($versionRecord['t3ver_stage']);
					$versionArray['allowedAction_prevStage'] = $isRecordTypeAllowedToModify && $stagesObj->isPrevStageAllowedForUser($versionRecord['t3ver_stage']);
					if ($swapAccess && $swapStage != 0 && $versionRecord['t3ver_stage'] == $swapStage) {
						$versionArray['allowedAction_swap'] = $isRecordTypeAllowedToModify && $stagesObj->isNextStageAllowedForUser($swapStage);
					} elseif ($swapAccess && $swapStage == 0) {
						$versionArray['allowedAction_swap'] = $isRecordTypeAllowedToModify;
					} else {
						$versionArray['allowedAction_swap'] = FALSE;
					}
					$versionArray['allowedAction_delete'] = $isRecordTypeAllowedToModify;
					// preview and editing of a deleted page won't work ;)
					$versionArray['allowedAction_view'] = !$isDeletedPage && $viewUrl;
					$versionArray['allowedAction_edit'] = $isRecordTypeAllowedToModify && !$isDeletedPage;
					$versionArray['allowedAction_editVersionedPage'] = $isRecordTypeAllowedToModify && !$isDeletedPage;
					$versionArray['state_Workspace'] = $recordState;
					if ($filterTxt == '' || $this->isFilterTextInVisibleColumns($filterTxt, $versionArray)) {
						$this->dataArray[] = $versionArray;
					}
				}
			}
			// Suggested slot method:
			// methodName(Tx_Workspaces_Service_GridData $gridData, array &$dataArray, array $versions)
			$this->emitSignal(self::SIGNAL_GenerateDataArray_BeforeCaching, $this->dataArray, $versions);
			// Enrich elements after everything has been processed:
			foreach ($this->dataArray as &$element) {
				$identifier = $element['table'] . ':' . $element['t3ver_oid'];
				$element['integrity'] = array(
					'status' => $this->getIntegrityService()->getStatusRepresentation($identifier),
					'messages' => htmlspecialchars($this->getIntegrityService()->getIssueMessages($identifier, TRUE))
				);
			}
			$this->setDataArrayIntoCache($versions, $filterTxt);
		}
		// Suggested slot method:
		// methodName(Tx_Workspaces_Service_GridData $gridData, array &$dataArray, array $versions)
		$this->emitSignal(self::SIGNAL_GenerateDataArray_PostProcesss, $this->dataArray, $versions);
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
		// Suggested slot method:
		// methodName(Tx_Workspaces_Service_GridData $gridData, array &$dataArray, $start, $limit)
		$this->emitSignal(self::SIGNAL_GetDataArray_PostProcesss, $this->dataArray, $start, $limit);
		return $dataArrayPart;
	}

	/**
	 * Initializes the workspace cache
	 *
	 * @return void
	 */
	protected function initializeWorkspacesCachingFramework() {
		$this->workspacesCache = $GLOBALS['typo3CacheManager']->getCache('workspaces_cache');
	}

	/**
	 * Puts the generated dataArray into the workspace cache.
	 *
	 * @param array $versions All records uids etc. First key is table name, second key incremental integer. Records are associative arrays with uid and t3ver_oid fields. The pid of the online record is found as "livepid" the pid of the offline record is found in "wspid
	 * @param string $filterTxt The given filter text from the grid.
	 */
	protected function setDataArrayIntoCache(array $versions, $filterTxt) {
		$hash = $this->calculateHash($versions, $filterTxt);
		$this->workspacesCache->set($hash, $this->dataArray, array($this->currentWorkspace, 'user_' . $GLOBALS['BE_USER']->user['uid']));
	}

	/**
	 * Checks if a cache entry is given for given versions and filter text and tries to load the data array from cache.
	 *
	 * @param array $versions All records uids etc. First key is table name, second key incremental integer. Records are associative arrays with uid and t3ver_oid fields. The pid of the online record is found as "livepid" the pid of the offline record is found in "wspid
	 * @param string $filterTxt The given filter text from the grid.
	 * @return boolean TRUE if cache entry was successfully fetched from cache and content put to $this->dataArray
	 */
	protected function getDataArrayFromCache(array $versions, $filterTxt) {
		$cacheEntry = FALSE;
		$hash = $this->calculateHash($versions, $filterTxt);
		$content = $this->workspacesCache->get($hash);
		if ($content !== FALSE) {
			$this->dataArray = $content;
			$cacheEntry = TRUE;
		}
		return $cacheEntry;
	}

	/**
	 * Calculates the hash value of the used workspace, the user id, the versions array, the filter text, the sorting attribute, the workspace selected in grid and the sorting direction.
	 *
	 * @param array $versions All records uids etc. First key is table name, second key incremental integer. Records are associative arrays with uid and t3ver_oid fields. The pid of the online record is found as "livepid" the pid of the offline record is found in "wspid
	 * @param string $filterTxt The given filter text from the grid.
	 * @return string
	 */
	protected function calculateHash(array $versions, $filterTxt) {
		$hashArray = array(
			$GLOBALS['BE_USER']->workspace,
			$GLOBALS['BE_USER']->user['uid'],
			$versions,
			$filterTxt,
			$this->sort,
			$this->sortDir,
			$this->currentWorkspace
		);
		$hash = md5(serialize($hashArray));
		return $hash;
	}

	/**
	 * Performs sorting on the data array accordant to the
	 * selected column in the grid view to be used for sorting.
	 *
	 * @return void
	 */
	protected function sortDataArray() {
		if (is_array($this->dataArray)) {
			switch ($this->sort) {
			case 'uid':

			case 'change':

			case 'workspace_Tstamp':

			case 't3ver_oid':

			case 'liveid':

			case 'livepid':

			case 'languageValue':
				usort($this->dataArray, array($this, 'intSort'));
				break;
			case 'label_Workspace':

			case 'label_Live':

			case 'label_Stage':

			case 'workspace_Title':

			case 'path_Live':
				// case 'path_Workspace': This is the first sorting attribute
				usort($this->dataArray, array($this, 'stringSort'));
				break;
			}
		} else {
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('Try to sort "' . $this->sort . '" in "TYPO3\\CMS\\Workspaces\\Service\\GridDataService::sortDataArray" but $this->dataArray is empty! This might be the Bug #26422 which could not reproduced yet.', 3);
		}
		// Suggested slot method:
		// methodName(Tx_Workspaces_Service_GridData $gridData, array &$dataArray, $sortColumn, $sortDirection)
		$this->emitSignal(self::SIGNAL_SortDataArray_PostProcesss, $this->dataArray, $this->sort, $this->sortDir);
	}

	/**
	 * Implements individual sorting for columns based on integer comparison.
	 *
	 * @param array $a First value
	 * @param array $b Second value
	 * @return integer
	 */
	protected function intSort(array $a, array $b) {
		// First sort by using the page-path in current workspace
		$path_cmp = strcasecmp($a['path_Workspace'], $b['path_Workspace']);
		if ($path_cmp < 0) {
			return $path_cmp;
		} elseif ($path_cmp == 0) {
			if ($a[$this->sort] == $b[$this->sort]) {
				return 0;
			}
			if ($this->sortDir == 'ASC') {
				return $a[$this->sort] < $b[$this->sort] ? -1 : 1;
			} elseif ($this->sortDir == 'DESC') {
				return $a[$this->sort] > $b[$this->sort] ? -1 : 1;
			}
		} elseif ($path_cmp > 0) {
			return $path_cmp;
		}
		return 0;
	}

	/**
	 * Implements individual sorting for columns based on string comparison.
	 *
	 * @param string $a First value
	 * @param string $b Second value
	 * @return integer
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
				return strcasecmp($a[$this->sort], $b[$this->sort]);
			} elseif ($this->sortDir == 'DESC') {
				return strcasecmp($a[$this->sort], $b[$this->sort]) * -1;
			}
		} elseif ($path_cmp > 0) {
			return $path_cmp;
		}
		return 0;
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
		if (is_array($GLOBALS['BE_USER']->uc['moduleData']['Workspaces'][$GLOBALS['BE_USER']->workspace]['columns'])) {
			foreach ($GLOBALS['BE_USER']->uc['moduleData']['Workspaces'][$GLOBALS['BE_USER']->workspace]['columns'] as $column => $value) {
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
	 * Gets the state of a given state value.
	 *
	 * @param integer $stateId stateId of offline record
	 * @param boolean $hiddenOnline hidden status of online record
	 * @param boolean $hiddenOffline hidden status of offline record
	 * @return string
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

	/**
	 * Gets the field name of the enable-columns as defined in $TCA.
	 *
	 * @param string $table Name of the table
	 * @param string $type Type to be fetches (e.g. 'disabled', 'starttime', 'endtime', 'fe_group)
	 * @return string|NULL The accordant field name or NULL if not defined
	 */
	protected function getTcaEnableColumnsFieldName($table, $type) {
		$fieldName = NULL;

		if (!(empty($GLOBALS['TCA'][$table]['ctrl']['enablecolumns'][$type]))) {
			$fieldName = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns'][$type];
		}

		return $fieldName;
	}

	/**
	 * Gets the used language value (sys_language.uid) of
	 * a given database record.
	 *
	 * @param string $table Name of the table
	 * @param array $record Database record
	 * @return integer
	 */
	protected function getLanguageValue($table, array $record) {
		$languageValue = 0;
		if (\TYPO3\CMS\Backend\Utility\BackendUtility::isTableLocalizable($table)) {
			$languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
			if (!empty($record[$languageField])) {
				$languageValue = $record[$languageField];
			}
		}
		return $languageValue;
	}

	/**
	 * Gets a named value of the available sys_language elements.
	 *
	 * @param integer $id sys_language uid
	 * @param string $key Name of the value to be fetched (e.g. title)
	 * @return string|NULL
	 * @see getSystemLanguages
	 */
	protected function getSystemLanguageValue($id, $key) {
		$value = NULL;
		$systemLanguages = $this->getSystemLanguages();
		if (!empty($systemLanguages[$id][$key])) {
			$value = $systemLanguages[$id][$key];
		}
		return $value;
	}

	/**
	 * Gets all available system languages.
	 *
	 * @return array
	 */
	public function getSystemLanguages() {
		if (!isset($this->systemLanguages)) {
			/** @var $translateTools \TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider */
			$translateTools = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Configuration\\TranslationConfigurationProvider');
			$this->systemLanguages = $translateTools->getSystemLanguages();
		}
		return $this->systemLanguages;
	}

	/**
	 * Gets an instance of the integrity service.
	 *
	 * @return \TYPO3\CMS\Workspaces\Service\IntegrityService
	 */
	protected function getIntegrityService() {
		if (!isset($this->integrityService)) {
			$this->integrityService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\IntegrityService');
		}
		return $this->integrityService;
	}

	/**
	 * Emits a signal to be handled by any registered slots.
	 *
	 * @param string $signalName Name of the signal
	 * @return void
	 */
	protected function emitSignal($signalName) {
		// Arguments are always ($this, [method argument], [method argument], ...)
		$signalArguments = array_merge(array($this), array_slice(func_get_args(), 1));
		$this->getSignalSlotDispatcher()->dispatch('TYPO3\\CMS\\Workspaces\\Service\\GridDataService', $signalName, $signalArguments);
	}

	/**
	 * @return \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 */
	protected function getSignalSlotDispatcher() {
		return $this->getObjectManager()->get('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Object\ObjectManagerException
	 */
	protected function getObjectManager() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerException');
	}

}


?>
