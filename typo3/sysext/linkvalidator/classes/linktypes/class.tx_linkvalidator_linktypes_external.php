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
 * @package TYPO3
 * @subpackage linkvalidator
 */
class tx_linkvalidator_linkTypes_External extends tx_linkvalidator_linkTypes_Abstract implements tx_linkvalidator_linkTypes_Interface {

	var $url_reports = array();
	var $url_error_params = array();

	/**
	 * Checks a given URL + /path/filename.ext for validity
	 *
	 * @param	string		$url: url to check
	 * @param	 array	   $softRefEntry: the softref entry which builds the context of that url
	 * @param	object		$reference:  parent instance of tx_linkvalidator_processing
	 * @return	string		TRUE on success or FALSE on error
	 */
	public function checkLink($url, $softRefEntry, $reference) {
		$errorParams = array();
		if (isset($this->url_reports[$url])) {
			if(!$this->url_reports[$url]) {
				if(is_array($this->url_error_params[$url])) {
				    $this->setErrorParams($this->url_error_params[$url]);
				}
			}
			return $this->url_reports[$url];
		}

			// remove possible anchor from the url
		if (strrpos($url, '#') !== FALSE) {
			$url = substr($url, 0, strrpos($url, '#'));
		}

			// try to fetch the content of the URL (headers only)
		$report = array();

			// try fetching the content of the URL (just fetching the headers does not work correctly)
		$content = '';
		$content = t3lib_div::getURL($url, 1, FALSE, $report);

		$tries = 0;
		while (($report['http_code'] == 301 || $report['http_code'] == 302
			|| $report['http_code'] == 303 || $report['http_code'] == 307)
			&& ($tries < 5)) {
				$isCodeRedirect = preg_match('/Location: (.*)/', $content, $location);
				if (isset($location[1])) {
					$content = t3lib_div::getURL($location[1], 2, FALSE, $report);
				}
				$tries++;
		}

		$response = TRUE;

			// analyze the response
		if ($report['error']) {
				// More cURL error codes can be found here:
				// http://curl.haxx.se/libcurl/c/libcurl-errors.html
			if ($report['lib'] === 'cURL' && $report['error'] === 28) {
				$errorParams['errorType'] = 'cURL28';
			} elseif ($report['lib'] === 'cURL' && $report['error'] === 22) {
				if (strstr($report['message'], '404')) {
					$errorParams['errorType'] = 404;
				} elseif(strstr($report['message'], '403')) {
					$errorParams['errorType'] = 403;
				} elseif(strstr($report['message'], '500')) {
					$errorParams['errorType'] = 500;
				}
			} elseif ($report['lib'] === 'cURL' && $report['error'] === 6) {
				$errorParams['errorType'] = 'cURL6';
			} elseif ($report['lib'] === 'cURL' && $report['error'] === 56) {
				$errorParams['errorType'] = 'cURL56';
			}

			$response = FALSE;
		}


			// special handling for more information
		if (($report['http_code'] == 301) || ($report['http_code'] == 302)
			|| ($report['http_code'] == 303) || ($report['http_code'] == 307)) {
				$errorParams['errorType'] = $report['http_code'];
				$errorParams['location'] = $location[1];
				$response = FALSE;
		}

		if ($report['http_code'] == 404 || $report['http_code'] == 403) {
			$errorParams['errorType'] = $report['http_code'];
			$response = FALSE;
		}

		if ($report['http_code'] >= 300 && $response) {
			$errorParams['errorType'] = $report['http_code'];
			$response = FALSE;
		}

		if(!$response) {
			$this->setErrorParams($errorParams);
		}

		$this->url_reports[$url] = $response;
		$this->url_error_params[$url] = $errorParams;

		return $response;
	}

	/**
	 * Generate the localized error message from the error params saved from the parsing.
	 *
	 * @param   array    all parameters needed for the rendering of the error message
	 * @return  string    validation error message
	 */
	public function getErrorMessage($errorParams) {
		$errorType = $errorParams['errorType'];
		switch ($errorType) {
			case 300:
				$response = sprintf($GLOBALS['LANG']->getLL('list.report.externalerror'), $errorType);
				break;

			case 301:
			case 302:
			case 303:
			case 307:
				$response = sprintf($GLOBALS['LANG']->getLL('list.report.redirectloop'), $errorType, $errorParams['location']);
				break;

			case 404:
				$response = $GLOBALS['LANG']->getLL('list.report.pagenotfound404');
				break;

			case 403:
				$response = $GLOBALS['LANG']->getLL('list.report.pageforbidden403');
				break;

			case 500:
				$response = $GLOBALS['LANG']->getLL('list.report.internalerror500');
				break;

			case 'cURL6':
				$response = $GLOBALS['LANG']->getLL('list.report.couldnotresolvehost');
				break;

			case 'cURL28':
				$response = $GLOBALS['LANG']->getLL('list.report.timeout');
				break;

			case 'cURL56':
				$response = $GLOBALS['LANG']->getLL('list.report.errornetworkdata');
				break;

			default:
				$response = $GLOBALS['LANG']->getLL('list.report.noresponse');
		}

		return $response;
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