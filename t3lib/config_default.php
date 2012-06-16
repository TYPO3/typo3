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

Typo3_Bootstrap_Backend::loadDefaultTypo3ConfVars();
Typo3_Bootstrap_Backend::registerExtDirectComponents();
Typo3_Bootstrap_Backend::initializeGlobalVariables();
Typo3_Bootstrap_Backend::checkLocalconfExistsOrDie();
Typo3_Bootstrap_Backend::setGlobalDatabaseVariablesToEmptyString();

require(PATH_typo3conf . 'localconf.php');

Typo3_Bootstrap_Backend::defineTypo3DatabaseConstants();
Typo3_Bootstrap_Backend::initializeCachingFramework();
Typo3_Bootstrap_Backend::registerAutoloader();
Typo3_Bootstrap_Backend::addCorePearPathToIncludePath();
Typo3_Bootstrap_Backend::checkUtf8DatabaseSettingsOrDie();
Typo3_Bootstrap_Backend::transferDeprecatedCurlSettings();
Typo3_Bootstrap_Backend::setCacheHashOptions();
Typo3_Bootstrap_Backend::enforceCorrectProxyAuthScheme();
Typo3_Bootstrap_Backend::setDefaultTimezone();
Typo3_Bootstrap_Backend::initializeL10nLocales();
Typo3_Bootstrap_Backend::configureImageProcessingOptions();
Typo3_Bootstrap_Backend::convertPageNotFoundHandlingToBoolean();
Typo3_Bootstrap_Backend::registerGlobalDebugFunctions();
Typo3_Bootstrap_Backend::registerSwiftMailer();
Typo3_Bootstrap_Backend::configureExceptionHandling();
Typo3_Bootstrap_Backend::setMemoryLimit();
Typo3_Bootstrap_Backend::defineTypo3RequestTypes();

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

Typo3_Bootstrap_Backend::deprecationLogForOldXclassRegistration();
Typo3_Bootstrap_Backend::initializeExceptionHandling();
Typo3_Bootstrap_Backend::requireAdditionalExtensionFiles();
Typo3_Bootstrap_Backend::setFinalCachingFrameworkCacheConfiguration();
Typo3_Bootstrap_Backend::defineLoggingAndExceptionConstants();
Typo3_Bootstrap_Backend::unsetReservedGlobalVariables();
Typo3_Bootstrap_Backend::initializeGlobalTimeVariables();
?>