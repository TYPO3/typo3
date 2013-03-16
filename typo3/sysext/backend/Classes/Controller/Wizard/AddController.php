<?php
namespace TYPO3\CMS\Backend\Controller\Wizard;

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
 * Script Class for adding new items to a group/select field. Performs proper redirection as needed.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class AddController {

	// Internal, dynamic:
	// Content accumulation for the module.
	/**
	 * @todo Define visibility
	 */
	public $content;

	// List of files to include.
	/**
	 * @todo Define visibility
	 */
	public $include_once = array();

	// If set, the TCEmain class is loaded and used to add the returning ID to the parent record.
	/**
	 * @todo Define visibility
	 */
	public $processDataFlag = 0;

	// Internal, static:
	// Create new record -pid (pos/neg). If blank, return immediately
	/**
	 * @todo Define visibility
	 */
	public $pid;

	// The parent table we are working on.
	/**
	 * @todo Define visibility
	 */
	public $table;

	// Loaded with the created id of a record when TCEforms (alt_doc.php) returns ...
	/**
	 * @todo Define visibility
	 */
	public $id;

	// Internal, static: GPvars
	// Wizard parameters, coming from TCEforms linking to the wizard.
	/**
	 * @todo Define visibility
	 */
	public $P;

	// Information coming back from alt_doc.php script, telling what the table/id was of the newly created record.
	/**
	 * @todo Define visibility
	 */
	public $returnEditConf;

	/**
	 * Initialization of the class.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function init() {
		// Init GPvars:
		$this->P = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');
		$this->returnEditConf = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('returnEditConf');
		// Get this record
		$origRow = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($this->P['table'], $this->P['uid']);
		// Set table:
		$this->table = $this->P['params']['table'];
		// Get TSconfig for it.
		$TSconfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getTCEFORM_TSconfig($this->P['table'], is_array($origRow) ? $origRow : array('pid' => $this->P['pid']));
		// Set [params][pid]
		if (substr($this->P['params']['pid'], 0, 3) == '###' && substr($this->P['params']['pid'], -3) == '###') {
			$this->pid = intval($TSconfig['_' . substr($this->P['params']['pid'], 3, -3)]);
		} else {
			$this->pid = intval($this->P['params']['pid']);
		}
		// Return if new record as parent (not possibly/allowed)
		if (!strcmp($this->pid, '')) {
			\TYPO3\CMS\Core\Utility\HttpUtility::redirect(\TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl($this->P['returnUrl']));
		}
		// Else proceed:
		// If a new id has returned from a newly created record...
		if ($this->returnEditConf) {
			$eC = unserialize($this->returnEditConf);
			if (is_array($eC[$this->table]) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->P['uid'])) {
				// Getting id and cmd from returning editConf array.
				reset($eC[$this->table]);
				$this->id = intval(key($eC[$this->table]));
				$cmd = current($eC[$this->table]);
				// ... and if everything seems OK we will register some classes for inclusion and instruct the object to perform processing later.
				if ($this->P['params']['setValue'] && $cmd == 'edit' && $this->id && $this->P['table'] && $this->P['field'] && $this->P['uid']) {
					if ($LiveRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getLiveVersionOfRecord($this->table, $this->id, 'uid')) {
						$this->id = $LiveRec['uid'];
					}
					$this->processDataFlag = 1;
				}
			}
		}
	}

	/**
	 * Main function
	 * Will issue a location-header, redirecting either BACK or to a new alt_doc.php instance...
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		if ($this->returnEditConf) {
			if ($this->processDataFlag) {
				// Preparing the data of the parent record...:
				$trData = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\DataPreprocessor');
				// 'new'
				$trData->fetchRecord($this->P['table'], $this->P['uid'], '');
				$current = reset($trData->regTableItems_data);
				// If that record was found (should absolutely be...), then init TCEmain and set, prepend or append the record
				if (is_array($current)) {
					$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
					$tce->stripslashes_values = 0;
					$data = array();
					$addEl = $this->table . '_' . $this->id;
					// Setting the new field data:
					// If the field is a flexform field, work with the XML structure instead:
					if ($this->P['flexFormPath']) {
						// Current value of flexform path:
						$currentFlexFormData = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($current[$this->P['field']]);
						$flexToolObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Configuration\\FlexForm\\FlexFormTools');
						$curValueOfFlexform = $flexToolObj->getArrayValueByPath($this->P['flexFormPath'], $currentFlexFormData);
						$insertValue = '';
						switch ((string) $this->P['params']['setValue']) {
						case 'set':
							$insertValue = $addEl;
							break;
						case 'prepend':
							$insertValue = $curValueOfFlexform . ',' . $addEl;
							break;
						case 'append':
							$insertValue = $addEl . ',' . $curValueOfFlexform;
							break;
						}
						$insertValue = implode(',', \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $insertValue, 1));
						$data[$this->P['table']][$this->P['uid']][$this->P['field']] = array();
						$flexToolObj->setArrayValueByPath($this->P['flexFormPath'], $data[$this->P['table']][$this->P['uid']][$this->P['field']], $insertValue);
					} else {
						switch ((string) $this->P['params']['setValue']) {
						case 'set':
							$data[$this->P['table']][$this->P['uid']][$this->P['field']] = $addEl;
							break;
						case 'prepend':
							$data[$this->P['table']][$this->P['uid']][$this->P['field']] = $current[$this->P['field']] . ',' . $addEl;
							break;
						case 'append':
							$data[$this->P['table']][$this->P['uid']][$this->P['field']] = $addEl . ',' . $current[$this->P['field']];
							break;
						}
						$data[$this->P['table']][$this->P['uid']][$this->P['field']] = implode(',', \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $data[$this->P['table']][$this->P['uid']][$this->P['field']], 1));
					}
					// Submit the data:
					$tce->start($data, array());
					$tce->process_datamap();
				}
			}
			// Return to the parent alt_doc.php record editing session:
			\TYPO3\CMS\Core\Utility\HttpUtility::redirect(\TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl($this->P['returnUrl']));
		} else {
			// Redirecting to alt_doc.php with instructions to create a new record
			// AND when closing to return back with information about that records ID etc.
			$redirectUrl = 'alt_doc.php?returnUrl=' . rawurlencode(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '&returnEditConf=1&edit[' . $this->P['params']['table'] . '][' . $this->pid . ']=new';
			\TYPO3\CMS\Core\Utility\HttpUtility::redirect($redirectUrl);
		}
	}

}


?>