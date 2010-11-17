<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005 - 2010 Michael Miousse (michael.miousse@infoglobe.ca)
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * This class provides Check Link Handler plugin implementation.
 *
 * @author Dimitri König <dk@cabag.ch>
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 */
class tx_linkvalidator_checklinkhandlerlinks extends tx_linkvalidator_checkbase {
	public $tsconfig;

	/**
	 * Get TsConfig on loading of the class
	 */
	function __construct() {
		$this->tsconfig = t3lib_BEfunc::getModTSconfig(1, 'mod.tx_linkhandler');
	}

	/**
	 * Checks a given URL + /path/filename.ext for validity
	 *
	 * @param   string	  $url: url to check
	 * @param	 array	   $softRefEntry: the softref entry which builds the context of that url
	 * @param   object	  $reference:  parent instance of tx_linkvalidator_processing
	 * @return  string	  validation error message or succes code
	 */
	function checkLink($url, $softRefEntry, $reference) {
		$parts = explode(":", $url);
		if (count($parts) == 3) {
			$tablename = htmlspecialchars($parts[1]);
			$rowid = intval($parts[2]);
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*',
				$tablename,
				'uid = ' . intval($rowid)
			);
			$title = 'Link';
			if ($this->tsconfig['properties'][$tablename . '.']) {
				$title = $this->tsconfig['properties'][$tablename . '.']['label'];
			}
			if ($rows[0]) {
				if ($rows[0]['deleted'] == '1') {
					$response = $GLOBALS['LANG']->getLL('list.report.rowdeleted');
					$response = str_replace('###title###', $title, $response);
					return $response;
				}
			} else {
				$response = $GLOBALS['LANG']->getLL('list.report.rownotexisting');
				$response = str_replace('###title###', $title, $response);
				return $response;
			}
		}

		return 1;
	}

	/**
	 * type fetching method, based on the type that softRefParserObj returns.
	 *
	 * @param   array	  $value: reference properties
	 * @param   string	 $type: current type
	 * @return  string	 fetched type
	 */
	function fetchType($value, $type) {
		if ($type == 'string' && strtolower(substr($value['tokenValue'], 0, 7)) == 'record:') {
			$type = 'linkhandler';
		}
		return $type;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/linkvalidator/lib/class.tx_linkvalidator_checklinkhandlerlinks.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/linkvalidator/lib/class.tx_linkvalidator_checklinkhandlerlinks.php']);
}

?>