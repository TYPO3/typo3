<?php
namespace TYPO3\CMS\Backend\Controller\Wizard;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
	 */
	public function __construct() {
		$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_wizards.xlf');
		$GLOBALS['SOBE'] = $this;
		$this->P = GeneralUtility::_GP('P');
		$this->table = GeneralUtility::_GP('table');
		$this->id = GeneralUtility::_GP('id');
	}

	/**
	 * Main function
	 * Will issue a location-header, redirecting either BACK or to a new alt_doc.php instance...
	 *
	 * @return void
	 */
	public function main() {
		// Get this record
		$origRow = BackendUtility::getRecord($this->P['table'], $this->P['uid']);
		// Get TSconfig for it.
		$TSconfig = BackendUtility::getTCEFORM_TSconfig($this->table, is_array($origRow) ? $origRow : array('pid' => $this->P['pid']));
		// Set [params][pid]
		if (substr($this->P['params']['pid'], 0, 3) === '###' && substr($this->P['params']['pid'], -3) === '###') {
			$keyword = substr($this->P['params']['pid'], 3, -3);
			if (strpos($keyword, 'PAGE_TSCONFIG_') === 0) {
				$this->pid = (int)$TSconfig[$this->P['field']][$keyword];
			} else {
				$this->pid = (int)$TSconfig['_' . $keyword];
			}
		} else {
			$this->pid = (int)$this->P['params']['pid'];
		}
		// Make redirect:
		// If pid is blank OR if id is set, then return...
		if ((string)$this->id !== '') {
			$redirectUrl = GeneralUtility::sanitizeLocalUrl($this->P['returnUrl']);
		} else {
			// Otherwise, show the list:
			$urlParameters = array();
			$urlParameters['id'] = $this->pid;
			$urlParameters['table'] = $this->P['params']['table'];
			$urlParameters['returnUrl'] = GeneralUtility::getIndpEnv('REQUEST_URI');
			$redirectUrl = BackendUtility::getModuleUrl('web_list', $urlParameters);
		}
		\TYPO3\CMS\Core\Utility\HttpUtility::redirect($redirectUrl);
	}

}
