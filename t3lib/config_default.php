<?php
/**
 * TYPO3 default configuration
 *
 * TYPO3_CONF_VARS is a global array with configuration for the TYPO3 libraries
 * THESE VARIABLES MAY BE OVERRIDDEN FROM WITHIN localconf.php
 *
 * 'IM' is short for 'ImageMagick', which is an external image manipulation package available from www.imagemagick.org. Version is ABSOLUTELY preferred to be 4.2.9, but may be 5+. See the install notes for TYPO3!!
 * 'GD' is short for 'GDLib/FreeType', which are libraries that should be compiled into PHP4. GDLib <=1.3 supports GIF, while the latest version 1.8.x and 2.x supports only PNG. GDLib is available from www.boutell.com/gd/. Freetype has a link from there.
 * Revised for TYPO3 3.6 2/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */

if (!defined ('PATH_typo3conf')) {
	die('The configuration path was not properly defined!');
}

Typo3_Bootstrap_Backend::getInstance()
	->loadDefaultTypo3ConfVars()
	->registerExtDirectComponents()
	->initializeGlobalVariables()
	->checkLocalconfExistsOrDie()
	->setGlobalDatabaseVariablesToEmptyString();

require(PATH_typo3conf . 'localconf.php');

Typo3_Bootstrap_Backend::getInstance()
	->defineTypo3DatabaseConstants()
	->initializeCachingFramework()
	->registerAutoloader()
	->addCorePearPathToIncludePath()
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
	->defineTypo3RequestTypes();

	// Load extensions:
$TYPO3_LOADED_EXT = t3lib_extMgm::typo3_loadExtensions();
if ($TYPO3_LOADED_EXT['_CACHEFILE']) {
	require(PATH_typo3conf . $TYPO3_LOADED_EXT['_CACHEFILE'] . '_ext_localconf.php');
} else {
	$temp_TYPO3_LOADED_EXT = $TYPO3_LOADED_EXT;
	foreach ($temp_TYPO3_LOADED_EXT as $_EXTKEY => $temp_lEDat) {
		if (is_array($temp_lEDat) && $temp_lEDat['ext_localconf.php']) {
			$_EXTCONF = $TYPO3_CONF_VARS['EXT']['extConf'][$_EXTKEY];
			require($temp_lEDat['ext_localconf.php']);
		}
	}
}

Typo3_Bootstrap_Backend::getInstance()
	->deprecationLogForOldXclassRegistration()
	->initializeExceptionHandling()
	->requireAdditionalExtensionFiles()
	->setFinalCachingFrameworkCacheConfiguration()
	->defineLoggingAndExceptionConstants()
	->unsetReservedGlobalVariables()
	->initializeGlobalTimeVariables();
?>