<?php
namespace TYPO3\CMS\SysAction;

/*************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Thomas Maroschik <tmaroschik@dfau.de>
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
 * Class for the list rendering of Web>Task Center module
 *
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 */
class ActionList extends \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList {

	/**
	 * Creates the URL to this script, including all relevant GPvars
	 * Fixed GPvars are id, table, imagemode, returnUrl, search_field, search_levels and showLimit
	 * The GPvars "sortField" and "sortRev" are also included UNLESS they are found in the $excludeList variable.
	 *
	 * @param string $alternativeId Alternative id value. Enter blank string for the current id ($this->id)
	 * @param string $table Table name to display. Enter "-1" for the current table.
	 * @param string $excludeList Comma separated list of fields NOT to include ("sortField" or "sortRev")
	 * @return string
	 */
	public function listURL($alternativeId = '', $table = -1, $excludeList = '') {
		$urlParameters = array();
		if (strcmp($alternativeId, '')) {
			$urlParameters['id'] = $alternativeId;
		} else {
			$urlParameters['id'] = $this->id;
		}
		if ($table === -1) {
			$urlParameters['table'] = $this->table;
		} else {
			$urlParameters['table'] = $table;
		}
		if ($this->thumbs) {
			$urlParameters['imagemode'] = $this->thumbs;
		}
		if ($this->returnUrl) {
			$urlParameters['returnUrl'] = $this->returnUrl;
		}
		if ($this->searchString) {
			$urlParameters['search_field'] = $this->searchString;
		}
		if ($this->searchLevels) {
			$urlParameters['search_levels'] = $this->searchLevels;
		}
		if ($this->showLimit) {
			$urlParameters['showLimit'] = $this->showLimit;
		}
		if ($this->firstElementNumber) {
			$urlParameters['pointer'] = $this->firstElementNumber;
		}
		if ((!$excludeList || !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($excludeList, 'sortField')) && $this->sortField) {
			$urlParameters['sortField'] = $this->sortField;
		}
		if ((!$excludeList || !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($excludeList, 'sortRev')) && $this->sortRev) {
			$urlParameters['sortRev'] = $this->sortRev;
		}
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SET')) {
			$urlParameters['SET'] = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SET');
		}
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('show')) {
			$urlParameters['show'] = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('show'));
		}
		return \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('user_task', $urlParameters);
	}

}

?>