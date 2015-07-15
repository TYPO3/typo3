<?php
namespace TYPO3\CMS\Openid;

/*
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Utility\EidUtility;

/**
 * This class is the OpenID return script for the TYPO3 Frontend.
 */
class OpenidEid {

	/**
	 * Processes eID request.
	 *
	 * @return void
	 */
	public function main() {
		// Due to the nature of OpenID (redrections, etc) we need to force user
		// session fetching if there is no session around. This ensures that
		// our service is called even if there is no login data in the request.
		// Inside the service we will process OpenID response and authenticate
		// the user.
		$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['FE_fetchUserIfNoSession'] = TRUE;
		// Initialize Frontend user
		EidUtility::initFeUser();
		// Redirect to the original location in any case (authenticated or not)
		@ob_end_clean();

		$location = GeneralUtility::_GP('tx_openid_location');
		$signature = GeneralUtility::hmac($location, 'openid');
		if ($signature === GeneralUtility::_GP('tx_openid_location_signature')) {
			HttpUtility::redirect($location, HttpUtility::HTTP_STATUS_303);
		}
	}

}
