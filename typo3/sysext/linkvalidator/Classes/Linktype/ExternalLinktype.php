<?php
namespace TYPO3\CMS\Linkvalidator\Linktype;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 - 2013 Jochen Rieger (j.rieger@connecta.ag)
 *  (c) 2010 - 2013 Michael Miousse (michael.miousse@infoglobe.ca)
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
 * This class provides Check External Links plugin implementation
 *
 * @author Dimitri König <dk@cabag.ch>
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 * @author Philipp Gampe <typo3.dev@philippgampe.info>
 */
class ExternalLinktype extends \TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype {

	/**
	 * Cached list of the URLs, which were already checked for the current processing
	 *
	 * @var array $urlReports
	 */
	protected $urlReports = array();

	/**
	 * Cached list of all error parameters of the URLs, which were already checked for the current processing
	 *
	 * @var array $urlErrorParams
	 */
	protected $urlErrorParams = array();

	/**
	 * List of headers to be used for matching an URL for the current processing
	 *
	 * @var array $additionalHeaders
	 */
	protected $additionalHeaders = array();

	/**
	 * Checks a given URL for validity
	 *
	 * @param string $url The URL to check
	 * @param array $softRefEntry The soft reference entry which builds the context of that URL
	 * @param \TYPO3\CMS\Linkvalidator\LinkAnalyzer $reference Parent instance of tx_linkvalidator_Processor
	 * @return boolean TRUE on success or FALSE on error
	 */
	public function checkLink($url, $softRefEntry, $reference) {
		$errorParams = array();
		$isValidUrl = TRUE;
		if (isset($this->urlReports[$url])) {
			if (!$this->urlReports[$url]) {
				if (is_array($this->urlErrorParams[$url])) {
					$this->setErrorParams($this->urlErrorParams[$url]);
				}
			}
			return $this->urlReports[$url];
		}
		$config = array(
			'follow_redirects' => TRUE,
			'strict_redirects' => TRUE
		);
		/** @var $request \TYPO3\CMS\Core\Http\HttpRequest|\HTTP_Request2 */
		$request = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Http\\HttpRequest', $url, 'HEAD', $config);
		// Observe cookies
		$request->setCookieJar(TRUE);
		try {
			/** @var $response \HTTP_Request2_Response */
			$response = $request->send();
			// HEAD was not allowed, now trying GET
			if (isset($response) && $response->getStatus() === 405) {
				$request->setMethod('GET');
				$request->setHeader('Range', 'bytes = 0 - 4048');
				/** @var $response \HTTP_Request2_Response */
				$response = $request->send();
			}
		} catch (\Exception $e) {
			$isValidUrl = FALSE;
			// A redirect loop occurred
			if ($e->getCode() === 40) {
				// Parse the exception for more information
				$trace = $e->getTrace();
				$traceUrl = $trace[0]['args'][0]->getUrl()->getUrl();
				$traceCode = $trace[0]['args'][1]->getStatus();
				$errorParams['errorType'] = 'loop';
				$errorParams['location'] = $traceUrl;
				$errorParams['errorCode'] = $traceCode;
			} else {
				$errorParams['errorType'] = 'exception';
			}
			$errorParams['message'] = $e->getMessage();
		}
		if (isset($response) && $response->getStatus() >= 300) {
			$isValidUrl = FALSE;
			$errorParams['errorType'] = $response->getStatus();
			$errorParams['message'] = $response->getReasonPhrase();
		}
		if (!$isValidUrl) {
			$this->setErrorParams($errorParams);
		}
		$this->urlReports[$url] = $isValidUrl;
		$this->urlErrorParams[$url] = $errorParams;
		return $isValidUrl;
	}

	/**
	 * Generate the localized error message from the error params saved from the parsing
	 *
	 * @param array $errorParams All parameters needed for the rendering of the error message
	 * @return string Validation error message
	 */
	public function getErrorMessage($errorParams) {
		$errorType = $errorParams['errorType'];
		switch ($errorType) {
			case 300:
				$response = sprintf($GLOBALS['LANG']->getLL('list.report.externalerror'), $errorType);
			break;
			case 403:
				$response = $GLOBALS['LANG']->getLL('list.report.pageforbidden403');
			break;
			case 404:
				$response = $GLOBALS['LANG']->getLL('list.report.pagenotfound404');
			break;
			case 500:
				$response = $GLOBALS['LANG']->getLL('list.report.internalerror500');
			break;
			case 'loop':
				$response = sprintf($GLOBALS['LANG']->getLL('list.report.redirectloop'), $errorParams['errorCode'], $errorParams['location']);
			break;
			case 'exception':
				$response = sprintf($GLOBALS['LANG']->getLL('list.report.httpexception'), $errorParams['message']);
			break;
			default:
				$response = sprintf($GLOBALS['LANG']->getLL('list.report.otherhttpcode'), $errorType, $errorParams['message']);
		}
		return $response;
	}

	/**
	 * Get the external type from the softRefParserObj result
	 *
	 * @param array $value Reference properties
	 * @param string $type Current type
	 * @param string $key Validator hook name
	 * @return string Fetched type
	 */
	public function fetchType($value, $type, $key) {
		preg_match_all('/((?:http|https))(?::\\/\\/)(?:[^\\s<>]+)/i', $value['tokenValue'], $urls, PREG_PATTERN_ORDER);
		if (!empty($urls[0][0])) {
			$type = 'external';
		}
		return $type;
	}

}
?>