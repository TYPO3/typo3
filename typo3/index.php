<?php
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
 * Login-screen of TYPO3.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
define('TYPO3_PROCEED_IF_NO_USER', 1);
require __DIR__ . '/init.php';

// This is a deprecation layer for extensions registered as submodules
// of func and info module: Those modules now use mod.php as entry
// points and not own index.php anymore, not adapted extensions will
// therefor route to this script here. The code sorts out these script
// calls and redirects to mod.php.
// @deprecated since 6.2, remove two versions later.
if (!empty($_SERVER['HTTP_REFERER'])) {
	$typo3RequestDir = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR');
	if (strpos($_SERVER['HTTP_REFERER'], $typo3RequestDir . 'mod.php') === 0) {
		parse_str(substr($_SERVER['HTTP_REFERER'], strpos($_SERVER['HTTP_REFERER'], '?') + 1), $referrerParameters);
		// As of now, only web_info and web_func have been converted and need the compatibility layer
		if (!empty($referrerParameters['M']) && in_array($referrerParameters['M'], array('web_info', 'web_func'), TRUE)) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog(
				'Module ' . $referrerParameters['M'] . ' called index.php. This is deprecated since TYPO3 6.2, use' .
				' BackendUtility::getModuleUrl() instead to get the target for your call.'
			);
			parse_str($_SERVER['QUERY_STRING'], $queryParameters);
			header('Location: ' . \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl($referrerParameters['M'], $queryParameters, FALSE, TRUE));
			exit;
		}
		unset($referrerParameters);
	}
	unset($typo3RequestDir);
}

$loginController = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Controller\\LoginController');
$loginController->main();
$loginController->printContent();
