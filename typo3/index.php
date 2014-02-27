<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
