<?php
namespace TYPO3\CMS\Linkvalidator\Linktype;

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

/**
 * This class provides Check File Links plugin implementation
 *
 * @author Dimitri König <dk@cabag.ch>
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 */
class FileLinktype extends \TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype {

	/**
	 * Checks a given URL + /path/filename.ext for validity
	 *
	 * @param string $url Url to check
	 * @param array $softRefEntry The soft reference entry which builds the context of the url
	 * @param \TYPO3\CMS\Linkvalidator\LinkAnalyzer $reference Parent instance
	 * @return boolean TRUE on success or FALSE on error
	 */
	public function checkLink($url, $softRefEntry, $reference) {
		if (!@file_exists((PATH_site . rawurldecode($url)))) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Generate the localized error message from the error params saved from the parsing
	 *
	 * @param array $errorParams All parameters needed for the rendering of the error message
	 * @return string Validation error message
	 */
	public function getErrorMessage($errorParams) {
		$response = $GLOBALS['LANG']->getLL('list.report.filenotexisting');
		return $response;
	}

	/**
	 * Construct a valid Url for browser output
	 *
	 * @param array $row Broken link record
	 * @return string Parsed broken url
	 */
	public function getBrokenUrl($row) {
		$brokenUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $row['url'];
		return $brokenUrl;
	}
}
