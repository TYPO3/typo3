<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj <kasperYYYY@typo3.com>
*  (c) 2008-2011 Benjamin Mack <benni . typo3 . o)rg>
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
 * Class that does the simulatestatic feature (Speaking URLs)
 * Was extracted for TYPO3 4.3 from the core
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Benjamin Mack <benni . typo3 . o)rg>
 */
class tx_simulatestatic {
	public $enabled = FALSE;
	public $replacementChar = '';
	public $conf = array();
	public $pEncodingAllowedParamNames = array();

	/**
	 * Initializes the extension, sets some configuration options and does some basic checks
	 *
	 * @param	array		holds all the information about the link that is about to be created
	 * @param	tslib_fe	is a reference to the parent object that calls the hook
	 * @return	void
	 */
	public function hookInitConfig(array &$parameters, tslib_fe &$parentObject) {
		$TSconf = &$parameters['config'];

		// if .simulateStaticDocuments was not present, the installation-wide default value will be used
		if (!isset($TSconf['simulateStaticDocuments'])) {
			$TSconf['simulateStaticDocuments'] = trim($parentObject->TYPO3_CONF_VARS['FE']['simulateStaticDocuments']);
		}

		// simulateStatic was not activated
		if (!$TSconf['simulateStaticDocuments']) {
			return;
		}

		$this->enabled = TRUE;

		// setting configuration options
		$this->conf = array(
			'mode' => $TSconf['simulateStaticDocuments'],
			'dontRedirectPathInfoError' => ($TSconf['simulateStaticDocuments_dontRedirectPathInfoError'] ? $TSconf['simulateStaticDocuments_dontRedirectPathInfoError'] : $TSconf['simulateStaticDocuments.']['dontRedirectPathInfoError']),
			'pEncoding' => ($TSconf['simulateStaticDocuments_pEnc'] ? $TSconf['simulateStaticDocuments_pEnc'] : $TSconf['simulateStaticDocuments.']['pEncoding']),
			'pEncodingOnlyP' => ($TSconf['simulateStaticDocuments_pEnc_onlyP'] ? $TSconf['simulateStaticDocuments_pEnc_onlyP'] : $TSconf['simulateStaticDocuments.']['pEncoding_onlyP']),
			'addTitle'  => ($TSconf['simulateStaticDocuments_addTitle'] ? $TSconf['simulateStaticDocuments_addTitle'] : $TSconf['simulateStaticDocuments.']['addTitle']),
			'noTypeIfNoTitle' => ($TSconf['simulateStaticDocuments_noTypeIfNoTitle'] ? $TSconf['simulateStaticDocuments_noTypeIfNoTitle'] : $TSconf['simulateStaticDocuments.']['noTypeIfNoTitle']),
			'replacementChar' => (t3lib_div::compat_version('4.0') ? '-' : '_')
		);

		if ($this->conf['pEncodingOnlyP']) {
			$tempParts = t3lib_div::trimExplode(',', $this->conf['pEncodingOnlyP'], 1);
			foreach ($tempParts as $tempPart) {
				$this->pEncodingAllowedParamNames[$tempPart] = 1;
			}
		}


	 	// Checks and sets replacement character for simulateStaticDocuments.
		$replacement = trim($TSconf['simulateStaticDocuments_replacementChar'] ? $TSconf['simulateStaticDocuments_replacementChar'] : $TSconf['simulateStaticDocuments.']['replacementChar']);
		if ($replacement && (urlencode($replacement) == $replacement)) {
			$this->conf['replacementChar'] = $replacement;
		}

		// Force absRefPrefix to this value is PATH_INFO is used.
		$absRefPrefix = $TSconf['absRefPrefix'];
		$absRefPrefix = trim($absRefPrefix);
		if ((!strcmp($this->conf['mode'], 'PATH_INFO') || $parentObject->absRefPrefix_force) && !$absRefPrefix) {
			$absRefPrefix = t3lib_div::dirname(t3lib_div::getIndpEnv('SCRIPT_NAME')) . '/';
		}
		$parentObject->absRefPrefix = $absRefPrefix;
		$parentObject->config['config']['absRefPrefix'] = $absRefPrefix;


		// Check PATH_INFO url
		if ($parentObject->absRefPrefix_force && strcmp($this->conf['mode'], 'PATH_INFO')) {
			$redirectUrl = t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR') . 'index.php?id=' . $parentObject->id . '&type='.$parentObject->type;
			if ($this->conf['dontRedirectPathInfoError']) {
				if ($parentObject->checkPageUnavailableHandler()) {
					$parentObject->pageUnavailableAndExit('PATH_INFO was not configured for this website, and the URL tries to find the page by PATH_INFO!');
				} else {
					$message = 'PATH_INFO was not configured for this website, and the URL tries to find the page by PATH_INFO!';
					header(t3lib_utility_Http::HTTP_STATUS_503);
					t3lib_div::sysLog($message, 'cms', t3lib_div::SYSLOG_SEVERITY_ERROR);
					$message = 'Error: PATH_INFO not configured: ' . $message . '<br /><br /><a href="' . htmlspecialchars($redirectUrl) . '">Click here to get to the right page.</a>';
					throw new RuntimeException($message, 1294587706);
				}
			} else {
				t3lib_utility_Http::redirect($redirectUrl);
			}
			exit;
			// Set no_cache if PATH_INFO is NOT used as simulateStaticDoc.
			// and if absRefPrefix_force shows that such an URL has been passed along.
			// $this->set_no_cache();
		}
	}


	/**
	 * Hook for creating a speaking URL when using the generic linkData function
	 *
	 * @param	array				holds all the information about the link that is about to be created
	 * @param	t3lib_TStemplate	is a reference to the parent object that calls the hook
	 * @return	void
	 */
	public function hookLinkDataPostProc(array &$parameters, t3lib_TStemplate &$parentObject) {
		if (!$this->enabled) {
			return;
		}

		$LD = &$parameters['LD'];
		$page = &$parameters['args']['page'];
		$LD['type'] = '';

		// MD5/base64 method limitation
		$remainLinkVars = '';
		$flag_pEncoding = (t3lib_div::inList('md5,base64', $this->conf['pEncoding']) && !$LD['no_cache']);
		if ($flag_pEncoding) {
			list($LD['linkVars'], $remainLinkVars) = $this->processEncodedQueryString($LD['linkVars']);
		}

		$url = $this->makeSimulatedFileName(
			$page['title'],
			($page['alias'] ? $page['alias'] : $page['uid']),
			intval($parameters['typeNum']),
			$LD['linkVars'],
			($LD['no_cache'] ? TRUE : FALSE)
		);
		if ($this->conf['mode'] == 'PATH_INFO') {
			$url = 'index.php/' . str_replace('.', '/', $url) . '/';
		} else {
			$url .= '.html';
		}
		$LD['url'] = $GLOBALS['TSFE']->absRefPrefix . $url . '?';

		if ($flag_pEncoding) {
			$LD['linkVars'] = $remainLinkVars;
		}

		// If the special key 'sectionIndex_uid' (added 'manually' in tslib/menu.php to the page-record) is set,
		// then the link jumps directly to a section on the page.
		$LD['sectionIndex'] = ($page['sectionIndex_uid'] ? '#c'.$page['sectionIndex_uid'] : '');

			// Compile the normal total url
		$LD['totalURL'] = $parentObject->removeQueryString($LD['url'] . $LD['type'] . $LD['no_cache'] . $LD['linkVars'] . $GLOBALS['TSFE']->getMethodUrlIdToken) . $LD['sectionIndex'];
	}


	/**
	 * Hook for checking to see if the URL is a speaking URL
	 *
	 * Here a .htaccess file maps all .html-files to index.php and
	 *  then we extract the id and type from the name of that HTML-file. (AKA "simulateStaticDocuments")
	 * Support for RewriteRule to generate   (simulateStaticDocuments)
	 * With the mod_rewrite compiled into apache, put these lines into a .htaccess in this directory:
	 * RewriteEngine On
	 * RewriteRule   ^[^/]*\.html$  index.php
	 * The url must end with '.html' and the format must comply with either of these:
	 * 1:      '[title].[id].[type].html'  - title is just for easy recognition in the
	 *                                       logfile!; no practical use of the title for TYPO3.
	 * 2:      '[id].[type].html'          - above, but title is omitted; no practical use of
	 *                                       the title for TYPO3.
	 * 3:      '[id].html'                 - only id, type is set to the default, zero!
	 * NOTE: In all case 'id' may be the uid-number OR the page alias (if any)
	 *
	 * @param	array		includes a reference to the parent Object (which is the global TSFE)
	 * @param	tslib_fe	is a reference to the global TSFE
	 * @return	void
	 */
	public function hookCheckAlternativeIDMethods(array &$parameters, tslib_fe &$parentObject) {
		// If there has been a redirect (basically; we arrived here otherwise
		// than via "index.php" in the URL)
		// this can happend either due to a CGI-script or because of reWrite rule.
		// Earlier we used $_SERVER['REDIRECT_URL'] to check
		if ($parentObject->siteScript && substr($parentObject->siteScript, 0, 9) != 'index.php') {
			$uParts = parse_url($parentObject->siteScript);
			$fI = t3lib_div::split_fileref($uParts['path']);

			if (!$fI['path'] && $fI['file'] && substr($fI['file'], -5) == '.html') {
				$parts = explode('.', $fI['file']);
				$pCount = count($parts);
				if ($pCount > 2) {
					$parentObject->type = intval($parts[$pCount-2]);
					$parentObject->id = $parts[$pCount-3];
				} else {
					$parentObject->type = 0;
					$parentObject->id = $parts[0];
				}
			}
		}

		// If PATH_INFO is defined as simulateStaticDocuments mode and has information:
		if (t3lib_div::getIndpEnv('PATH_INFO') && strpos(t3lib_div::getIndpEnv('TYPO3_SITE_SCRIPT'), 'index.php/') === 0) {
			$parts = t3lib_div::trimExplode('/', t3lib_div::getIndpEnv('PATH_INFO'), TRUE);
			$pCount = count($parts);
			if ($pCount > 1) {
				$parentObject->type = intval($parts[$pCount-1]);
				$parentObject->id = $parts[$pCount-2];
			} else {
				$parentObject->type = 0;
				$parentObject->id = $parts[0];
			}
			$parentObject->absRefPrefix_force = 1;
		}
	}


	/**
	 * Analyzes the second part of a id-string (after the "+"), looking for B6 or M5 encoding
	 * and if found it will resolve it and restore the variables in global $_GET.
	 * If values for ->cHash, ->no_cache, ->jumpurl and ->MP is found,
	 * they are also loaded into the internal vars of this class.
	 * => Not yet used, could be ported from tslib_fe as well
	 *
	 * @param	string		String to analyze
	 * @return	void
	 */
	protected function idPartsAnalyze($string) {
		$getVars = '';
		switch (substr($string, 0, 2)) {
			case 'B6':
				$addParams = base64_decode(str_replace('_', '=', str_replace('-', '/', substr($string, 2))));
				parse_str($addParams, $getVars);
			break;
			case 'M5':
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('params', 'cache_md5params', 'md5hash=' . $GLOBALS['TYPO3_DB']->fullQuoteStr(substr($string, 2), 'cache_md5params'));
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

				$GLOBALS['TSFE']->updateMD5paramsRecord(substr($string, 2));
				parse_str($row['params'], $getVars);
			break;
		}
		$GLOBALS['TSFE']->mergingWithGetVars($getVars);
	}




	/********************************************
	 *
	 * Various internal API functions
	 *
	 *******************************************/

	/**
	 * This is just a wrapper function to use the params from the array split up. Can be deleted once the function in class.t3lib_fe.php is deleted
	 *
	 * @param	array		Parameter array delivered from tslib_fe::makeSimulFileName
	 * @param	tslib_fe	Reference to the calling TSFE instance
	 * @return	string		The body of the filename.
	 * @see makeSimulatedFileName()
	 * @deprecated since TYPO3 4.3, will be deleted in TYPO3 4.6
	 */
	public function makeSimulatedFileNameCompat(array &$parameters, tslib_fe &$parentObject) {
		t3lib_div::logDeprecatedFunction();

		return $this->makeSimulatedFileName(
			$parameters['inTitle'],
			$parameters['page'],
			$parameters['type'],
			$parameters['addParams'],
			$parameters['no_cache']
		);
	}


	/**
	 * Make simulation filename (without the ".html" ending, only body of filename)
	 *
	 * @param	string		The page title to use
	 * @param	mixed		The page id (integer) or alias (string)
	 * @param	integer		The type number
	 * @param	string		Query-parameters to encode (will be done only if caching is enabled and TypoScript configured for it. I don't know it this makes much sense in fact...)
	 * @param	boolean		The "no_cache" status of the link.
	 * @return	string		The body of the filename.
	 * @see getSimulFileName(), t3lib_tstemplate::linkData(), tslib_frameset::frameParams()
	 */
	public function makeSimulatedFileName($inTitle, $page, $type, $addParams = '', $no_cache = FALSE) {
			// Default value is 30 but values > 1 will be override this
		$titleChars = intval($this->conf['addTitle']);
		if ($titleChars == 1) {
			$titleChars = 30;
		}

		$out = ($titleChars ? $this->fileNameASCIIPrefix($inTitle, $titleChars) : '');
		$enc = '';

		if (strcmp($addParams, '') && !$no_cache) {
			switch ((string)$this->conf['pEncoding']) {
				case 'md5':
					$md5 = substr(md5($addParams), 0, 10);
					$enc = '+M5'.$md5;

					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'md5hash',
						'cache_md5params',
						'md5hash=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($md5, 'cache_md5params')
					);
					if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
						$insertFields = array(
							'md5hash' => $md5,
							'tstamp'  => $GLOBALS['EXEC_TIME'],
							'type'    => 1,
							'params'  => $addParams
						);

						$GLOBALS['TYPO3_DB']->exec_INSERTquery('cache_md5params', $insertFields);
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($res);
				break;
				case 'base64':
					$enc = '+B6' . str_replace('=', '_', str_replace('/', '-', base64_encode($addParams)));
				break;
			}
		}
			// Setting page and type number:
		return $out . $page . $enc . (($type || $out || !$this->conf['noTypeIfNoTitle']) ? '.' . $type : '');
	}


	/**
	 * Returns the simulated static file name (*.html) for the current page (using the page record in $this->page)
	 *
	 * @return	string		The filename (without path)
	 * @see makeSimulatedFileName(), publish.php
	 */
	public function getSimulatedFileName() {
		return $this->makeSimulatedFileName(
			$GLOBALS['TSFE']->page['title'],
			($GLOBALS['TSFE']->page['alias'] ? $GLOBALS['TSFE']->page['alias'] : $GLOBALS['TSFE']->id),
			$GLOBALS['TSFE']->type
		) . '.html';
	}


	/**
	 * Processes a query-string with GET-parameters and returns two strings, one with the parameters that CAN be encoded and one array with those which can't be encoded (encoded by the M5 or B6 methods)
	 *
	 * @param	string		Query string to analyse
	 * @return	array		Two num keys returned, first is the parameters that MAY be encoded, second is the non-encodable parameters.
	 * @see makeSimulatedFileName(), t3lib_tstemplate::linkData()
	 */
	public function processEncodedQueryString($linkVars) {
		$remainingLinkVars = '';
		if (strcmp($linkVars, '')) {
			$parts = t3lib_div::trimExplode('&', $linkVars);
			// This sorts the parameters - and may not be needed and further
			// it will generate new MD5 hashes in many cases. Maybe not so smart. Hmm?
			sort($parts);
			$remainingParts = array();
			foreach ($parts as $index => $value) {
				if (strlen($value)) {
					list($parameterName) = explode('=', $value, 2);
					$parameterName = rawurldecode($parameterName);
					if (!$this->pEncodingAllowedParamNames[$parameterName]) {
						unset($parts[$index]);
						$remainingParts[] = $value;
					}
				} else {
					unset($parts[$index]);
				}
			}
			$linkVars = (count($parts) ? '&' . implode('&', $parts) : '');
			$remainingLinkVars = (count($remainingParts) ? '&' . implode('&', $remainingParts) : '');
		}
		return array($linkVars, $remainingLinkVars);
	}


	/**
	 * Converts input string to an ASCII based file name prefix
	 *
	 * @param	string		String to base output on
	 * @param	integer		Number of characters in the string
	 * @param	string		Character to put in the end of string to merge it with the next value.
	 * @return	string		Converted string
	 */
	public function fileNameASCIIPrefix($inTitle, $maxTitleChars, $mergeChar = '.') {
		$out = $GLOBALS['TSFE']->csConvObj->specCharsToASCII($GLOBALS['TSFE']->renderCharset, $inTitle);

		// Get replacement character
		$replacementChar = $this->conf['replacementChar'];
		$replacementChars = '_\-' . ($replacementChar != '_' && $replacementChar != '-' ? $replacementChar : '');
		$out = preg_replace('/[^A-Za-z0-9_-]/', $replacementChar, trim(substr($out, 0, $maxTitleChars)));
		$out = preg_replace('/([' . $replacementChars . ']){2,}/', '\1', $out);
		$out = preg_replace('/['  . $replacementChars . ']?$/', '', $out);
		$out = preg_replace('/^[' . $replacementChars . ']?/', '', $out);

		return (strlen($out) ? $out . $mergeChar : '');
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/simulatestatic/class.tx_simulatestatic.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/simulatestatic/class.tx_simulatestatic.php']);
}
?>