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
 * Script Class for redirecting the user to the Web > List module if a wizard-link has been clicked in TCEforms
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ListController {

	// Internal, static:
	// PID
	/**
	 * @todo Define visibility
	 */
	public $pid;

	// Internal, static: GPvars
	// Wizard parameters, coming from TCEforms linking to the wizard.
	/**
	 * @todo Define visibility
	 */
	public $P;

	// Table to show, if none, then all tables are listed in list module.
	/**
	 * @todo Define visibility
	 */
	public $table;

	// Page id to list.
	/**
	 * @todo Define visibility
	 */
	public $id;

	/**
	 * Initialization of the class, setting GPvars.
	 *
	 * @return 	void
	 * @todo Define visibility
	 */
	public function init() {
		$this->P = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');
		$this->table = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('table');
		$this->id = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id');
	}

	/**
	 * Main function
	 * Will issue a location-header, redirecting either BACK or to a new alt_doc.php instance...
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		// Get this record
		$origRow = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($this->P['table'], $this->P['uid']);
		// Get TSconfig for it.
		$TSconfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getTCEFORM_TSconfig($this->table, is_array($origRow) ? $origRow : array('pid' => $this->P['pid']));
		// Set [params][pid]
		if (substr($this->P['params']['pid'], 0, 3) == '###' && substr($this->P['params']['pid'], -3) == '###') {
			$this->pid = intval($TSconfig['_' . substr($this->P['params']['pid'], 3, -3)]);
		} else {
			$this->pid = intval($this->P['params']['pid']);
		}
		// Make redirect:
		// If pid is blank OR if id is set, then return...
		if (!strcmp($this->pid, '') || strcmp($this->id, '')) {
			$redirectUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl($this->P['returnUrl']);
		} else {
			// Otherwise, show the list:
			$urlParameters = array();
			$urlParameters['id'] = $this->pid;
			$urlParameters['table'] = $this->P['params']['table'];
			$urlParameters['returnUrl'] = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI');
			$redirectUrl = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_list', $urlParameters);
		}
		\TYPO3\CMS\Core\Utility\HttpUtility::redirect($redirectUrl);
	}

}


?>