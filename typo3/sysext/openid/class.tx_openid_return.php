<?php
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

// Fix _GET/_POST values for authentication (login_status has to be submitted via POST for BE auth)
if (isset($_GET['login_status'])) {
	$_POST['login_status'] = $_GET['login_status'];
}

define('TYPO3_MOD_PATH', 'sysext/openid/');
require_once '../../init.php';

/** @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $beUser */
$beUser = $GLOBALS['BE_USER'];
if ($beUser->user['uid']) {
	\TYPO3\CMS\Core\Utility\GeneralUtility::cleanOutputBuffers();
	$backendURL = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir . 'backend.php';
	\TYPO3\CMS\Core\Utility\HttpUtility::redirect($backendURL);
}
