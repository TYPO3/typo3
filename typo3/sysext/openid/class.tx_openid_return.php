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
// Fix _GET/_POST values for authentication
if (isset($_GET['login_status'])) {
	$_POST['login_status'] = $_GET['login_status'];
}
define('TYPO3_MOD_PATH', 'sysext/openid/');
require_once '../../init.php';

$module = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Openid\\OpenidReturn');
/* @var \TYPO3\CMS\Openid\OpenidReturn $module */
$module->main();
