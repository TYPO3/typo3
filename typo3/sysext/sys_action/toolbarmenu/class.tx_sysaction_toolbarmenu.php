<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Steffen Kamper <info@sk-typo3.de>
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

	// load the language file
$GLOBALS['LANG']->includeLLFile('EXT:sys_action/locallang.xml');

/**
 * Adds action links to the backend's toolbar
 *
 * @author	Steffen Kamper <info@sk-typo3.de>
 * @package TYPO3
 * @subpackage tx_sysaction
 */
class tx_sysactionToolbarMenu implements backend_toolbarItem {

	/**
	 * reference back to the backend object
	 *
	 * @var	TYPO3backend
	 */
	protected $backendReference;
	protected $EXTKEY = 'sys_action';

	/**
	 * constructor
	 *
	 * @return	void
	 */
	public function __construct(TYPO3backend &$backendReference = null) {
		$this->backendReference = $backendReference;
	}

	/**
	 * sets the backend reference
	 *
	 * @param	TYPO3backend	backend object reference
	 * @return	void
	 */
	public function setBackend(TYPO3backend &$backendReference) {
		$this->backendReference = $backendReference;
	}

	/**
	 * renders the toolbar menu
	 *
	 * @return	string	the rendered backend menu
	 * @author	Ingo Renner <ingo@typo3.org>
	 */
	public function render() {
		$actionMenu    = array();
		$actionEntries = $this->getActionEntries();

		if ($actionEntries) {
			$this->addJavascriptToBackend();
			$this->addCssToBackend();
			$title = $GLOBALS['LANG']->getLL('action_toolbaritem', TRUE);

			$actionMenu[] = '<a href="#" class="toolbar-item">'.
				t3lib_iconWorks::getSpriteIcon('apps-toolbar-menu-actions', array('title' => $title)) .
				'</a>';

			$actionMenu[] = '<ul class="toolbar-item-menu" style="display: none;">';
			foreach ($actionEntries as $linkConf) {
				$actionMenu[] = '<li><a href="' . $linkConf[1] .
					'" target="content">' . $linkConf[2] .
					htmlspecialchars($linkConf[0]) . '</a></li>';
			}

			$actionMenu[] = '</ul>';
			return implode("\n", $actionMenu);
		} else {
			return '';
		}


	}

	/**
	 * gets the entries for the action menu
	 *
	 * @return	array	array of action menu entries
	 * @author	Steffen Kamper <info@sk-typo3.de>
	 * @author	Ingo Renner <ingo@typo3.org>
	 */
	protected function getActionEntries() {
		$actions = array();

		if ($GLOBALS['BE_USER']->isAdmin()) {
			$queryResource = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				'sys_action',
				'pid = 0 AND hidden=0',
				'',
				'sys_action.sorting'
			);
		} else {
			$groupList = 0;
			if ($GLOBALS['BE_USER']->groupList) {
				$groupList = $GLOBALS['BE_USER']->groupList;
			}

			$queryResource = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
				'sys_action.*',
				'sys_action',
				'sys_action_asgr_mm',
				'be_groups',
				' AND be_groups.uid IN (' . $groupList .
					') AND sys_action.pid = 0 AND sys_action.hidden = 0',
				'sys_action.uid',
				'sys_action.sorting'
			);
		}

		if ($queryResource) {
			while ($actionRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($queryResource)) {
				$actions[] = array(
					$actionRow['title'],
					'mod.php?M=user_task&SET[function]==sys_action.tx_sysaction_task&show=' . $actionRow['uid'],
					t3lib_iconworks::getSpriteIconForRecord(
						'sys_action',
						$actionRow
					),
				);
			}

			$GLOBALS['TYPO3_DB']->sql_free_result($queryResource);
		}

		return $actions;
	}

	/**
	 * returns additional attributes for the list item in the toolbar
	 *
	 * @return	string	list item HTML attibutes
	 */
	public function getAdditionalAttributes() {
		return ' id="tx-sys-action-menu"';
	}

	/**
	 * adds the neccessary javascript ot the backend
	 *
	 * @return	void
	 */
	protected function addJavascriptToBackend() {
		$this->backendReference->addJavascriptFile(
			t3lib_extMgm::extRelPath($this->EXTKEY) . 'toolbarmenu/tx_sysactions.js'
		);
	}

	/**
	 * adds the neccessary css ot the backend
	 *
	 * @return	void
	 */
	protected function addCssToBackend() {
		$this->backendReference->addCssFile(
			'sysaction',
			t3lib_extMgm::extRelPath($this->EXTKEY) .
				'toolbarmenu/tx_sysactions.css'
		);
	}

	/**
	 * Checks if user has access to the sys action menu
	 *
	 * @return	boolean	true if the user has access, false otherwise
	 */
	public function checkAccess() {
			// taskcenter is enabled for everybody
		return TRUE;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sys_action/toolbarmenu/class.tx_sysaction_toolbarmenu.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sys_action/toolbarmenu/class.tx_sysaction_toolbarmenu.php']);
}

?>
