<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Starter-script for install screen
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */

ob_start();

define('TYPO3_MODE', 'BE');
define('TYPO3_enterInstallScript', '1');

	// We use require instead of require_once here so we get a fatal error if classes/Bootstrap.php is accidentally included twice
	// (which would indicate a clear bug).
require('../classes/Bootstrap.php');
Typo3_Bootstrap::getInstance()->baseSetup('typo3/install/');

require('../classes/Bootstrap/Install.php');
Typo3_Bootstrap_Install::checkEnabledInstallToolOrDie();

Typo3_Bootstrap::getInstance()
	->registerExtDirectComponents()
	->populateLocalConfiguration()
	->initializeCachingFramework()
	->registerAutoloader()
	->checkUtf8DatabaseSettingsOrDie()
	->transferDeprecatedCurlSettings()
	->setCacheHashOptions()
	->enforceCorrectProxyAuthScheme()
	->setDefaultTimezone()
	->initializeL10nLocales()
	->configureImageProcessingOptions()
	->convertPageNotFoundHandlingToBoolean()
	->registerGlobalDebugFunctions()
	->registerSwiftMailer()
	->configureExceptionHandling()
	->setMemoryLimit()
	->defineTypo3RequestTypes()
	->populateTypo3LoadedExtGlobal(FALSE)
	->loadAdditionalConfigurationFromExtensions(FALSE)
	->deprecationLogForOldExtCacheSetting()
	->initializeExceptionHandling()
	->requireAdditionalExtensionFiles()
	->setFinalCachingFrameworkCacheConfiguration()
	->defineLoggingAndExceptionConstants()
	->unsetReservedGlobalVariables()
	->initializeTypo3DbGlobal(FALSE)
	->checkLockedBackendAndRedirectOrDie()
	->checkBackendIpOrDie()
	->checkSslBackendAndRedirectIfNeeded();

	// Run install script
if (!t3lib_extMgm::isLoaded('install')) {
	die('Install Tool is not loaded as an extension.<br />You must add the key "install" to the list of installed extensions in typo3conf/localconf.php, $TYPO3_CONF_VARS[\'EXT\'][\'extList\'].');
}

require_once(t3lib_extMgm::extPath('install') . 'mod/class.tx_install.php');
$install_check = t3lib_div::makeInstance('tx_install');
$install_check->allowUpdateLocalConf = 1;
$install_check->init();
?>