<?php
namespace TYPO3\CMS\Openid;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Dmitry Dulepov <dmitry@typo3.org>
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
 * This class is the OpenID return script for the TYPO3 Frontend.
 *
 * @author 	Dmitry Dulepov <dmitry@typo3.org>
 */
class OpenidEid {

	/**
	 * Processes eID request.
	 *
	 * @return 	void
	 */
	public function main() {
		// Due to the nature of OpenID (redrections, etc) we need to force user
		// session fetching if there is no session around. This ensures that
		// our service is called even if there is no login data in the request.
		// Inside the service we will process OpenID response and authenticate
		// the user.
		$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['FE_fetchUserIfNoSession'] = TRUE;
		// Initialize Frontend user
		\TYPO3\CMS\Frontend\Utility\EidUtility::initFeUser();
		// Redirect to the original location in any case (authenticated or not)
		@ob_end_clean();
		\TYPO3\CMS\Core\Utility\HttpUtility::redirect(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('tx_openid_location'), \TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_303);
	}

}


?>