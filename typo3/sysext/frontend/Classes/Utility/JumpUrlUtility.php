<?php
namespace TYPO3\CMS\Frontend\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Alexander Stehlik <alexander.stehlik (at) gmail.com>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * This class contains functions for generating and validating jump URLs
 *
 * @author Alexander Stehlik <alexander.stehlik (at) gmail.com>
 */
class JumpUrlUtility implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Builds a jump URL for the given URL
	 *
	 * @param string $url The URL to which will be jumped
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject Reference to the calling content object renderer
	 * @param array $typoLinkConfig Optional TypoLink configuration
	 * @return string The generated URL
	 */
	public function buildJumpurlFor($url, $contentObject, $typoLinkConfig = array()) {

		$jumpUrlConfig = isset($typoLinkConfig['jumpurl.']) ? $typoLinkConfig['jumpurl.'] : array();

		$urlParameters['jumpurl'] =  $url;

		if ($jumpUrlConfig['secure']) {
			$jumpUrlSecureUrlParameters = $this->getJumpUrlSecureParameters($url, $jumpUrlConfig['secure.'], $contentObject->currentRecord);
			$urlParameters = array_merge($urlParameters, $jumpUrlSecureUrlParameters);
		} else {
			$urlParameters['juHash'] = $this->calculateHash($url);
		}

		$jumpUrlLinkConfig = array(
			'parameter' => $this->getJumpUrlTypoLinkParameter($jumpUrlConfig, $contentObject),
			'additionalParams' => GeneralUtility::implodeArrayForUrl('', $urlParameters),
			'jumpurl.' => array('forceDisable' => '1'),
		);

		$contentObject->typoLink('', $jumpUrlLinkConfig);
		return $contentObject->lastTypoLinkUrl;
	}

	/**
	 * Returns a URL parameter string setting parameters for secure downloads by "jumpurl".
	 * Helper function for filelink()
	 *
	 * @param string $jumpUrl The URL to jump to, basically the filepath
	 * @param array $conf TypoScript properties for the "jumpurl.secure" property of "filelink
	 * @param $currentRecord
	 * @return array URL parameters
	 */
	public function getJumpUrlSecureParameters($jumpUrl, $conf, $currentRecord) {
		$parameters['juSecure'] = 1;
		$fI = pathinfo($jumpUrl);
		$mimetypeValue = '';
		if ($fI['extension']) {
			$mimeTypes = GeneralUtility::trimExplode(',', $conf['mimeTypes'], TRUE);
			foreach ($mimeTypes as $v) {
				$parts = explode('=', $v, 2);
				if (strtolower($fI['extension']) == strtolower(trim($parts[0]))) {
					$mimetypeValue = trim($parts[1]);
					$parameters['mimeType'] = $mimetypeValue;
					break;
				}
			}
		}
		$parameters['locationData'] = $GLOBALS['TSFE']->id . ':' . $currentRecord;
		$parameters['juHash'] = $this->calculateHashSecure($jumpUrl, $parameters['locationData'], $mimetypeValue);
		return $parameters;
	}

	/**
	 * If a valid hash was submitted the user will either be redirected
	 * to the given jumpUrl or if it is a secure jumpUrl the file data
	 * will be passed to the user.
	 *
	 * @param string $jumpUrl The current jumpUrl parameter
	 * @return void
	 */
	public function handleJumpUrl($jumpUrl) {

		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('juSecure')) {
			$this->forwardJumpUrlSecureFileData($jumpUrl);
		} else {
			$this->redirectToJumpUrl($jumpUrl);
		}
	}

	/**
	 * Returns TRUE if jump URL was enabled in the global configuration
	 * of in the given configuration
	 *
	 * @param array $conf Optional jump URL configuration
	 * @return bool TRUE if enabled, FALSE if disabled
	 */
	public function isEnabled($conf = array()) {

		$enabled = FALSE;

		if ($conf['jumpurl']) {
			$enabled = TRUE;
		}

		if (!$enabled && $GLOBALS['TSFE']->config['config']['jumpurl_enable']) {

			// if jumpurl is explicitly set to 0 we override the global
			// configuration
			if (isset($conf['jumpurl']) && $conf['jumpurl'] == 0) {
				$enabled = FALSE;
			} else {
				$enabled = TRUE;
			}
		}

		return $enabled;
	}

	/**
	 * Calculates the hash for the given jump URL
	 *
	 * @param string $jumpUrl The target URL
	 * @return string The calculated hash
	 */
	protected function calculateHash($jumpUrl) {
		return GeneralUtility::hmac($jumpUrl, 'jumpurl');
	}

	/**
	 * Calculates the hash for the given jump URL secure data.
	 *
	 * @param string $jumpUrl The URL to the file
	 * @param string $locationData Information about the record that rendered the jump URL, format is [pid]:[table]:[uid]
	 * @param string $mimeType Mime type of the file or an empty string
	 * @return string The calculated hash
	 */
	protected function calculateHashSecure($jumpUrl, $locationData, $mimeType) {

		$jumpUrlData = array(
			$jumpUrl,
			$locationData,
			$mimeType
		);

		return GeneralUtility::hmac(serialize($jumpUrlData));
	}

	/**
	 * If the submitted hash is correct and the user has access to the
	 * related content element the contents of the submitted file will
	 * be putted out to the user.
	 *
	 * @param string $jumpUrl The URL to the file that should be putted out to the user
	 * @throws \Exception
	 */
	protected function forwardJumpUrlSecureFileData($jumpUrl) {

		$locationData = (string) GeneralUtility::_GP('locationData');
		// Need a type cast here because mimeType is optional!
		$mimeType = (string) GeneralUtility::_GP('mimeType');
		$calcJuHash = $this->calculateHashSecure($jumpUrl, $locationData, $mimeType);
		$juHash = (string) GeneralUtility::_GP('juHash');

		if ($juHash !== $calcJuHash) {
			throw new \Exception('jumpurl Secure: Calculated juHash did not match the submitted juHash.', 1294585196);
		}

		if (!$GLOBALS['TSFE']->locDataCheck($locationData)) {
			throw new \Exception('jumpurl Secure: locationData, ' . $locationData . ', was not accessible.', 1294585195);
		}
		// 211002 - goes with cObj->filelink() rawurlencode() of filenames so spaces can be allowed.
		$jumpUrl = rawurldecode($jumpUrl);

		// Deny access to files that match TYPO3_CONF_VARS[SYS][fileDenyPattern] and whose parent directory is typo3conf/ (there could be a backup file in typo3conf/ which does not match against the fileDenyPattern)
		$absoluteFileName = GeneralUtility::getFileAbsFileName(\TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($jumpUrl), FALSE);

		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedAbsPath($absoluteFileName) && GeneralUtility::verifyFilenameAgainstDenyPattern($absoluteFileName) && !\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($absoluteFileName, (PATH_site . 'typo3conf'))) {
			throw new \Exception('jumpurl Secure: The requested file was not allowed to be accessed through jumpUrl (path or file not allowed)!', 1294585194);
		}

		if (!@is_file($absoluteFileName)) {
			throw new \Exception('jumpurl Secure: "' . $jumpUrl . '" was not a valid file!', 1294585193);
		}

		$mimeType = $mimeType ? $mimeType : 'application/octet-stream';
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Type: ' . $mimeType);
		header('Content-Disposition: attachment; filename="' . basename($absoluteFileName) . '"');
		header('Content-Length: ' . filesize($absoluteFileName));
		\TYPO3\CMS\Core\Utility\GeneralUtility::flushOutputBuffers();
		$this->readFileAndExit($absoluteFileName);
	}

	/**
	 * Checks if an alternative link parameter was configured and if not
	 * a default parameter will be generated based on the current page
	 * ID and type.
	 *
	 * @param array $jumpUrlConfig Data from the TypoLink jumpurl configuration
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject Reference to the calling content object renderer
	 * @return string The parameter for the jump URL TypoLink
	 */
	protected function getJumpUrlTypoLinkParameter($jumpUrlConfig, $contentObject) {

		$linkParameter = '';

		if (isset($jumpUrlConfig['parameter']) && isset($jumpUrlConfig['parameter.'])) {
			$linkParameter = $contentObject->stdWrap($jumpUrlConfig['parameter'], $jumpUrlConfig['parameter.']);
		} elseif (isset($jumpUrlConfig['parameter'])) {
			$linkParameter = $jumpUrlConfig['parameter'];
		}

		if (!$linkParameter) {
			$linkParameter = $GLOBALS['TSFE']->id . ',' . $GLOBALS['TSFE']->type;
		}

		return $linkParameter;
	}

	/**
	 * Calls the PHP readfile function and exits.
	 *
	 * Required for unit testing.
	 *
	 * @param string $file The file that should be read
	 */
	protected function readFileAndExit($file) {
		readfile($file);
		die;
	}

	/**
	 * Simply calls the redirect method in the HttpUtility.
	 *
	 * Required for unit testing.
	 *
	 * @param string $jumpUrl
	 * @param int $statusCode
	 */
	protected function redirect($jumpUrl, $statusCode) {
		HttpUtility::redirect($jumpUrl, $statusCode);
	}

	/**
	 * Redirects the user to the given jump URL if all submitted values
	 * are valid
	 *
	 * @param string $jumpUrl The URL to which the user should be redirected
	 * @throws \Exception
	 */
	protected function redirectToJumpUrl($jumpUrl) {

		$TSConf = $GLOBALS['TSFE']->getPagesTSconfig();

		if ($TSConf['TSFE.']['jumpUrl_transferSession']) {
			$uParts = parse_url($jumpUrl);
			$params = '&FE_SESSION_KEY=' . rawurlencode(($GLOBALS['TSFE']->fe_user->id . '-' . md5(($GLOBALS['TSFE']->fe_user->id . '/' . $GLOBALS['TSFE']->TYPO3_CONF_VARS['SYS']['encryptionKey']))));
			// Add the session parameter ...
			$jumpUrl .= ($uParts['query'] ? '' : '?') . $params;
		}

		$statusCode = HttpUtility::HTTP_STATUS_303;
		if ($TSConf['TSFE.']['jumpURL_HTTPStatusCode']) {
			switch (intval($TSConf['TSFE.']['jumpURL_HTTPStatusCode'])) {
				case 301:
					$statusCode = HttpUtility::HTTP_STATUS_301;
					break;
				case 302:
					$statusCode = HttpUtility::HTTP_STATUS_302;
					break;
				case 307:
					$statusCode = HttpUtility::HTTP_STATUS_307;
					break;
			}
		}

		$allowRedirect = FALSE;
		if ($this->validateSubmittedJumpUrlHash($jumpUrl)) {
			$allowRedirect = TRUE;
		} elseif (is_array($GLOBALS['TSFE']->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['jumpurlRedirectHandler'])) {
			foreach ($GLOBALS['TSFE']->TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['jumpurlRedirectHandler'] as $classReference) {
				$hookObject = GeneralUtility::getUserObj($classReference);
				$allowRedirectFromHook = FALSE;
				if (method_exists($hookObject, 'jumpurlRedirectHandler')) {
					$allowRedirectFromHook = $hookObject->jumpurlRedirectHandler($jumpUrl, $this);
				}
				if ($allowRedirectFromHook === TRUE) {
					$allowRedirect = TRUE;
					break;
				}
			}
		}

		if (!$allowRedirect) {
			throw new \Exception('jumpurl: Calculated juHash did not match the submitted juHash.', 1359987599);
		}

		$this->redirect($jumpUrl, $statusCode);
	}

	protected function validateSubmittedJumpUrlHash($jumpUrl) {

		$validated = FALSE;
		$submittedHash = (string)\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('juHash');
		$calculatedHash = $this->calculateHash($jumpUrl);

		if ($submittedHash === $calculatedHash) {
			$validated = TRUE;
		}

		return $validated;
	}
}

?>