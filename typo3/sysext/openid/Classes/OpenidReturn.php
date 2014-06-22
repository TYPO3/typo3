<?php
namespace TYPO3\CMS\Openid;

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
 * This class is the OpenID return script for the TYPO3 Backend.
 *
 * @author Dmitry Dulepov <dmitry@typo3.org>
 */
class OpenidReturn {

	/**
	 * Processed Backend session creation and redirect to backend.php
	 *
	 * @return void
	 */
	public function main() {
		if ($GLOBALS['BE_USER']->user['uid']) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::cleanOutputBuffers();
			$backendURL = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir . 'backend.php';
			\TYPO3\CMS\Core\Utility\HttpUtility::redirect($backendURL);
		}
	}

}
