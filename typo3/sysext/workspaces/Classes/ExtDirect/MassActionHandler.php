<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2010 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Class encapsulates all actions which are triggered for all elements within the current workspace
 *
 * @author Kasper Skårhøj (kasperYYYY@typo3.com)
 * @coauthor Tolleiv Nietsch (info@tolleiv.de)
 *
 */
class tx_Workspaces_ExtDirect_MassActionHandler extends tx_Workspaces_ExtDirect_AbstractHandler {

	const MAX_RECORDS_TO_PROCESS = 30;

	/**
	 * Get list of available mass workspace actions.
	 *
	 * @param object $parameter
	 * @return array $data
	 */
	public function getMassStageActions($parameter) {
		$actions = array();
		$currentWorkspace = $this->getCurrentWorkspace();

			// in case we're working within "All Workspaces" we can't provide Mass Actions
		if ($currentWorkspace != tx_Workspaces_Service_Workspaces::SELECT_ALL_WORKSPACES) {
			$publishAccess = $GLOBALS['BE_USER']->workspacePublishAccess();
			if ($publishAccess && !($GLOBALS['BE_USER']->workspaceRec['publish_access'] & 1)) {
				$actions[] = array('action' => 'publish', 'title' => 'Publish' //$GLOBALS['LANG']->getLL('label_doaction_publish'));
				);
				if ($GLOBALS['BE_USER']->workspaceSwapAccess()) {
					$actions[] = array('action' => 'swap', 'title' => 'Swap' //$GLOBALS['LANG']->getLL('label_doaction_swap')
					);
				}
			}

			if ($currentWorkspace !== tx_Workspaces_Service_Workspaces::LIVE_WORKSPACE_ID) {
				$actions[] = array('action' => 'release', 'title' => 'Release' // $GLOBALS['LANG']->getLL('label_doaction_release'));
				);
			}
		}

		$result = array(
			'total' => count($actions),
			'data' => $actions
		);
		return $result;
	}

	/**
	 *
	 * @param stdclass $parameters
	 * @return array
	 */
	public function publishWorkspace(stdclass $parameters) {
		$result = array(
			'init' => false,
			'total' => 0,
			'processed' => 0,
			'error' => false
		);

		try {
			if ($parameters->init) {
				$cnt = $this->initPublishData($this->getCurrentWorkspace(), $parameters->swap);
				$result['total'] = $cnt;
			} else {
				$result['processed'] = $this->processData();
				$result['total'] = $GLOBALS['BE_USER']->getSessionData('workspaceMassAction_total');
			}
		} catch (Exception $e) {
			$result['error'] = $e->getMessage();
		}
		return $result;
	}

	/**
	 *
	 * @param stdclass $parameters
	 * @return array
	 */
	public function flushWorkspace(stdclass $parameters) {
		$result = array(
			'init' => false,
			'total' => 0,
			'processed' => 0,
			'error' => false
		);

		try {
			if ($parameters->init) {
				$cnt = $this->initFlushData($this->getCurrentWorkspace());
				$result['total'] = $cnt;
			} else {
				$result['processed'] = $this->processData();
				$result['total'] = $GLOBALS['BE_USER']->getSessionData('workspaceMassAction_total');
			}
		} catch (Exception $e) {
			$result['error'] = $e->getMessage();
		}
		return $result;
	}

	/**
	 *
	 * @param $workspace
	 * @param $swap
	 * @return integer
	 */
	protected function initPublishData($workspace, $swap) {
		$workspaceService = t3lib_div::makeInstance('tx_Workspaces_Service_Workspaces');
			// workspace might be -98 a.k.a "All Workspaces but that's save here
		$publishData = $workspaceService->getCmdArrayForPublishWS($workspace, $swap);
		$recordCount = 0;
		foreach ($publishData as $table => $recs) {
			$recordCount += count($recs);
		}
		if ($recordCount > 0) {
			$GLOBALS['BE_USER']->setAndSaveSessionData('workspaceMassAction', $publishData);
			$GLOBALS['BE_USER']->setAndSaveSessionData('workspaceMassAction_total', $recordCount);
			$GLOBALS['BE_USER']->setAndSaveSessionData('workspaceMassAction_processed', 0);
		}
		return $recordCount;
	}

	/**
	 *
	 * @param $workspace
	 * @param $swap
	 * @return integer
	 */
	protected function initFlushData($workspace) {
		$workspaceService = t3lib_div::makeInstance('tx_Workspaces_Service_Workspaces');
			// workspace might be -98 a.k.a "All Workspaces but that's save here
		$flushData = $workspaceService->getCmdArrayForFlushWS($workspace);
		$recordCount = 0;
		foreach ($flushData as $table => $recs) {
			$recordCount += count($recs);
		}
		if ($recordCount > 0) {
			$GLOBALS['BE_USER']->setAndSaveSessionData('workspaceMassAction', $flushData);
			$GLOBALS['BE_USER']->setAndSaveSessionData('workspaceMassAction_total', $recordCount);
			$GLOBALS['BE_USER']->setAndSaveSessionData('workspaceMassAction_processed', 0);
		}
		return $recordCount;
	}

	/**
	 *
	 * @param integer $workspace
	 * @return integer
	 */
	protected function processData($workspace) {
		$processData = $GLOBALS['BE_USER']->getSessionData('workspaceMassAction');
		$recordsProcessed = $GLOBALS['BE_USER']->getSessionData('workspaceMassAction_processed');
		$limitedCmd = array();
		$numRecs = 0;

		foreach ($processData as $table => $recs) {
			foreach ($recs as $key => $value) {
				$numRecs++;
				$limitedCmd[$table][$key] = $value;
				if ($numRecs == self::MAX_RECORDS_TO_PROCESS) {
					break;
				}
			}
			if ($numRecs == self::MAX_RECORDS_TO_PROCESS) {
				break;
			}
		}

		if ($numRecs == 0) {
				// All done
			$GLOBALS['BE_USER']->setAndSaveSessionData('workspaceMassAction', null);
			$GLOBALS['BE_USER']->setAndSaveSessionData('workspaceMassAction_total', 0);
		} else {
				// Execute the commands:
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->stripslashes_values = 0;
			$tce->start(array(), $limitedCmd);
			$tce->process_cmdmap();

			$errors = $tce->errorLog;
			if (count($errors) > 0) {
				throw new Exception(implode(', ', $errors));
			} else {
					// Unset processed records
				foreach ($limitedCmd as $table => $recs) {
					foreach ($recs as $key => $value) {
						$recordsProcessed++;
						unset($processData[$table][$key]);
					}
				}
				$GLOBALS['BE_USER']->setAndSaveSessionData('workspaceMassAction', $processData);
				$GLOBALS['BE_USER']->setAndSaveSessionData('workspaceMassAction_processed', $recordsProcessed);
			}
		}

		return $recordsProcessed;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/ExtDirect/MassActionHandler.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/ExtDirect/MassActionHandler.php']);
}