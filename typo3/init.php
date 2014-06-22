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
 * TYPO3 Backend initialization
 *
 * This script is called by every backend script.
 * The script authenticates the backend user.
 * In addition this script also initializes the database and other depending on LocalConfiguration.php
 *
 * IMPORTANT:
 * This script exits if no user is logged in!
 * If you want the script to return even if no user is logged in,
 * you must define the constant TYPO3_PROCEED_IF_NO_USER=1
 * before you include this script.
 *
 *
 * This script does the following:
 * - extracts and defines path's
 * - includes certain libraries
 * - authenticates the user
 * - load the groupdata for the user and set filemounts / webmounts
 *
 * For a detailed description of this script, the scope of constants and variables in it,
 * please refer to the document "Inside TYPO3"
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
define('TYPO3_MODE', 'BE');

require __DIR__ . '/sysext/core/Classes/Core/Bootstrap.php';

\TYPO3\CMS\Core\Core\Bootstrap::getInstance()
	->baseSetup('typo3/')
	->redirectToInstallerIfEssentialConfigurationDoesNotExist('../')
	->startOutputBuffering()
	->loadConfigurationAndInitialize()
	->loadTypo3LoadedExtAndExtLocalconf(TRUE)
	->applyAdditionalConfigurationSettings()
	->initializeTypo3DbGlobal()
	->checkLockedBackendAndRedirectOrDie()
	->checkBackendIpOrDie()
	->checkSslBackendAndRedirectIfNeeded()
	->checkValidBrowserOrDie()
	->loadExtensionTables(TRUE)
	->initializeSpriteManager()
	->initializeBackendUser()
	->initializeBackendAuthentication()
	->initializeBackendUserMounts()
	->initializeLanguageObject()
	->initializeBackendTemplate()
	->endOutputBufferingAndCleanPreviousOutput()
	->initializeOutputCompression()
	->sendHttpHeaders();
