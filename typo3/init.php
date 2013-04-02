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
 *  A copy is found in the textfile GPL.txt and important notices to the license
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

require 'sysext/core/Classes/Core/Bootstrap.php';

\TYPO3\CMS\Core\Core\Bootstrap::getInstance()
	->baseSetup('typo3/')
	->redirectToInstallToolIfLocalConfigurationFileDoesNotExist('../')
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
	->initializeBackendUserMounts()
	->initializeLanguageObject()
	->initializeModuleMenuObject()
	->initializeBackendTemplate()
	->endOutputBufferingAndCleanPreviousOutput()
	->initializeOutputCompression();
?>
