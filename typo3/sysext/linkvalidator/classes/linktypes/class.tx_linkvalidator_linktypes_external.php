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
 * This class provides Check External Links plugin implementation.
 *
 * @author Dimitri König <dk@cabag.ch>
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 */
class tx_linkvalidator_linkTypes_External extends tx_linkvalidator_linkTypes_Abstract implements tx_linkvalidator_linkTypes_Interface {

	var $url_reports = array();

	/**
	 * Checks a given URL + /path/filename.ext for validity
	 *
	 * @param	string		$url: url to check
	 * @param	 array	   $softRefEntry: the softref entry which builds the context of that url
	 * @param	object		$reference:  parent instance of tx_linkvalidator_processing
	 * @return	string		validation error message or succes code
	 */
	public function checkLink($url, $softRefEntry, $reference) {
		if ($this->url_reports[$url]) {
			return $this->url_reports[$url];
		}
		
			// remove possible anchor from the url
		if (strrpos($url, '#') !== FALSE) {
			$url = substr($url, 0, strrpos($url, '#'));
		}

			// try to fetch the content of the URL (just fetching of headers doesn't work!)
		$report = array();

		t3lib_div::getURL($url, 1, FALSE, $report);

		$ret = 1;

			// analyze the response
		if ($report['error']) {
			$ret = $GLOBALS['LANG']->getLL('list.report.noresponse');
		}

		if($report['http_code'] === 404) {
			$ret = $GLOBALS['LANG']->getLL('list.report.pagenotfound404');
		}
		else if($report['http_code'] === 403) {
			$ret = $GLOBALS['LANG']->getLL('list.report.pageforbidden403');
		}
		else if ($report['http_code'] >= 400) {
			$ret = sprintf($GLOBALS['LANG']->getLL('list.report.externalerror'), $report['http_code']);
		}

		$this->url_reports[$url] = $ret;
		return $ret;
	}

	/**
	 * get the external type from the softRefParserObj result.
	 *
	 * @param   array	  $value: reference properties
	 * @param   string	 $type: current type
	 * @return	string		fetched type
	 */
	public function fetchType($value, $type) {
		preg_match_all('/((?:http|https|ftp|ftps))(?::\/\/)(?:[^\s<>]+)/i', $value['tokenValue'], $urls, PREG_PATTERN_ORDER);

		if (!empty($urls[0][0])) {
			$type = "external";
		}

		return $type;
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/linkvalidator/classes/linktypes/class.tx_linkvalidator_linktypes_external.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/linkvalidator/classes/linktypes/class.tx_linkvalidator_linktypes_external.php']);
}

?>