<?php
namespace TYPO3\CMS\Install\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Execute "silent" LocalConfiguration upgrades if needed.
 *
 * Some LocalConfiguration settings are obsolete or changed over time.
 * This class handles upgrades of these settings. It is called by
 * the step controller at an early point.
 *
 * Every change is encapsulated in one method an must throw a RedirectException
 * if new data is written to LocalConfiguration. This is caught by above
 * step controller to initiate a redirect and start again with adapted configuration.
 */
class SilentConfigurationUpgradeService {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager = NULL;

	/**
	 * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager
	 * @inject
	 */
	protected $configurationManager = NULL;

	/**
	 * @var array List of obsolete configuration options in LocalConfiguration to be removed
	 */
	protected $obsoleteLocalConfigurationSettings = array(
		// #34092
		'BE/forceCharset',
		// #26519
		'BE/loginLabels',
		// #44506
		'BE/loginNews',
		// #52013
		'BE/TSconfigConditions',
		// #30613
		'BE/useOnContextMenuHandler',
		// #57921
		'BE/usePHPFileFunctions',
		// #48179
		'EXT/em_mirrorListURL',
		'EXT/em_wsdlURL',
		// #43094
		'EXT/extList',
		// #47018
		'EXT/extListArray',
		// #35877
		'EXT/extList_FE',
		// #41813
		'EXT/noEdit',
		// #47018
		'EXT/requiredExt',
		// #26090
		'FE/defaultTypoScript_editorcfg',
		'FE/defaultTypoScript_editorcfg.',
		// #25099
		'FE/simulateStaticDocuments',
		// #52786
		'FE/logfile_dir',
		// #55549
		'FE/dontSetCookie',
		// #52011
		'GFX/im_combine_filename',
		// #52088
		'GFX/im_imvMaskState',
		// #22687
		'GFX/gdlib_2',
		// #52012
		'GFX/im_mask_temp_ext_noloss',
		// #52088
		'GFX/im_negate_mask',
		// #52010
		'GFX/im_no_effects',
		// #18431
		'GFX/noIconProc',
		// #17606
		'GFX/TTFLocaleConv',
		// #39164
		'SYS/additionalAllowedClassPrefixes',
		// #27689
		'SYS/caching/cacheBackends',
		'SYS/caching/cacheFrontends',
		// #38414
		'SYS/extCache',
		// #35923
		'SYS/multiplyDBfieldSize',
		// #46993
		'SYS/T3instID',
		// #52857
		'SYS/forceReturnPath',
	    // #54930
	    'INSTALL/wizardDone/tx_coreupdates_compressionlevel',
	    'INSTALL/wizardDone/TYPO3\\CMS\\Install\\Updates\\CompressionLevelUpdate',
	    'INSTALL/wizardDone/tx_coreupdates_installsysexts',
	    'INSTALL/wizardDone/TYPO3\\CMS\\Install\\Updates\\InstallSysExtsUpdate',
	    'INSTALL/wizardDone/tx_coreupdates_migrateworkspaces',
	    'INSTALL/wizardDone/TYPO3\\CMS\\Install\\Updates\\MigrateWorkspacesUpdate',
	);

	/**
	 * Executed configuration upgrades. Single upgrade methods must throw a
	 * RedirectException if something was written to LocalConfiguration.
	 *
	 * @return void
	 */
	public function execute() {
		$this->generateEncryptionKeyIfNeeded();
		$this->configureBackendLoginSecurity();
		$this->configureSaltedPasswords();
		$this->migrateOldInstallWizardDoneSettingsToNewClassNames();
		$this->setProxyAuthScheme();
		$this->disableImageMagickAndGdlibIfImageProcessingIsDisabled();
		$this->disableImageMagickDetailSettingsIfImageMagickIsDisabled();
		$this->setImageMagickDetailSettings();
		$this->addFileTableToDefaultCategorizedTablesIfAlreadyCustomized();
		$this->removeObsoleteLocalConfigurationSettings();
	}

	/**
	 * Some settings in LocalConfiguration vanished in DefaultConfiguration
	 * and have no impact on the core anymore.
	 * To keep the configuration clean, those old settings are just silently
	 * removed from LocalConfiguration if set.
	 *
	 * @return void
	 */
	protected function removeObsoleteLocalConfigurationSettings() {
		$removed = $this->configurationManager->removeLocalConfigurationKeysByPath($this->obsoleteLocalConfigurationSettings);

		// The old default value is not needed anymore. So if the user
		// did not set a different value we can remove it.
		$currentSetDbInitValue = $this->configurationManager->getConfigurationValueByPath('SYS/setDBinit');
		if (preg_match('/^\s*SET\s+NAMES\s+[\'"]?utf8[\'"]?\s*[;]?\s*$/i', $currentSetDbInitValue) === 1) {
			$removed = $removed || $this->configurationManager->removeLocalConfigurationKeysByPath(array('SYS/setDBinit'));
		}

		// If something was changed: Trigger a reload to have new values in next request
		if ($removed) {
			$this->throwRedirectException();
		}
	}

	/**
	 * Backend login security is set to rsa if rsaauth
	 * is installed (but not used) otherwise the default value "normal" has to be used.
	 * This forces either 'normal' or 'rsa' to be set in LocalConfiguration.
	 *
	 * @return void
	 */
	protected function configureBackendLoginSecurity() {
		try {
			$currentLoginSecurityLevelValue = $this->configurationManager->getLocalConfigurationValueByPath('BE/loginSecurityLevel');
			if (ExtensionManagementUtility::isLoaded('rsaauth')
				&& $currentLoginSecurityLevelValue !== 'rsa'
			) {
				$this->configurationManager->setLocalConfigurationValueByPath('BE/loginSecurityLevel', 'rsa');
				$this->throwRedirectException();
			} elseif (!ExtensionManagementUtility::isLoaded('rsaauth')
				&& $currentLoginSecurityLevelValue !== 'normal'
			) {
				$this->configurationManager->setLocalConfigurationValueByPath('BE/loginSecurityLevel', 'normal');
				$this->throwRedirectException();
			}
		} catch (\RuntimeException $e) {
			// If an exception is thrown, the value is not set in LocalConfiguration
			if (ExtensionManagementUtility::isLoaded('rsaauth')) {
				$this->configurationManager->setLocalConfigurationValueByPath('BE/loginSecurityLevel', 'rsa');
				$this->throwRedirectException();
			} elseif (!ExtensionManagementUtility::isLoaded('rsaauth')) {
				$this->configurationManager->setLocalConfigurationValueByPath('BE/loginSecurityLevel', 'normal');
				$this->throwRedirectException();
			}
		}
	}

	/**
	 * Check the settings for salted passwords extension to load it as a required extension.
	 * Unset obsolete configuration options if given.
	 *
	 * @return void
	 */
	protected function configureSaltedPasswords() {
		$defaultConfiguration = $this->configurationManager->getDefaultConfiguration();
		$defaultExtensionConfiguration = unserialize($defaultConfiguration['EXT']['extConf']['saltedpasswords']);
		try {
			$extensionConfiguration = @unserialize($this->configurationManager->getLocalConfigurationValueByPath('EXT/extConf/saltedpasswords'));
		} catch (\RuntimeException $e) {
			$extensionConfiguration = array();
		}
		if (is_array($extensionConfiguration) && !empty($extensionConfiguration)) {
			if (isset($extensionConfiguration['BE.']['enabled'])) {
				if ($extensionConfiguration['BE.']['enabled']) {
					unset($extensionConfiguration['BE.']['enabled']);
				} else {
					$extensionConfiguration['BE.'] = $defaultExtensionConfiguration['BE.'];
				}
				$this->configurationManager->setLocalConfigurationValueByPath(
					'EXT/extConf/saltedpasswords',
					serialize($extensionConfiguration)
				);
				$this->throwRedirectException();
			}
		} else {
			$this->configurationManager->setLocalConfigurationValueByPath(
				'EXT/extConf/saltedpasswords',
				serialize($defaultExtensionConfiguration)
			);
			$this->throwRedirectException();
		}
	}

	/**
	 * The encryption key is crucial for securing form tokens
	 * and the whole TYPO3 link rendering later on. A random key is set here in
	 * LocalConfiguration if it does not exist yet. This might possible happen
	 * during upgrading and will happen during first install.
	 *
	 * @return void
	 */
	protected function generateEncryptionKeyIfNeeded() {
		try{
			$currentValue = $this->configurationManager->getLocalConfigurationValueByPath('SYS/encryptionKey');
		} catch (\RuntimeException $e) {
			// If an exception is thrown, the value is not set in LocalConfiguration
			$currentValue = '';
		}

		if (empty($currentValue)) {
			$randomKey = GeneralUtility::getRandomHexString(96);
			$this->configurationManager->setLocalConfigurationValueByPath('SYS/encryptionKey', $randomKey);
			$this->throwRedirectException();
		}
	}

	/**
	 * $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy_auth_scheme'] must be either
	 * 'digest' or 'basic'. 'basic' is default in DefaultConfiguration, so the
	 * setting can be removed from LocalConfiguration if it is not set to 'digest'.
	 *
	 * @return void
	 */
	protected function setProxyAuthScheme() {
		// Get current value from LocalConfiguration
		try {
			$currentValueInLocalConfiguration = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/proxy_auth_scheme');
		} catch (\RuntimeException $e) {
			// If an exception is thrown, the value is not set in LocalConfiguration, so we don't need to do anything
			return;
		}
		if ($currentValueInLocalConfiguration !== 'digest') {
			$this->configurationManager->removeLocalConfigurationKeysByPath(array('HTTP/proxy_auth_scheme'));
			$this->throwRedirectException();
		}
	}

	/**
	 * GFX/im and GFX/gdlib must be set to 0 if image_processing is disabled.
	 *
	 * "Configuration presets" in install tool is not type safe, so value
	 * comparisons here are not type safe too, to not trigger changes to
	 * LocalConfiguration again.
	 *
	 * @return void
	 */
	protected function disableImageMagickAndGdlibIfImageProcessingIsDisabled() {
		$changedValues = array();
		try {
			$currentImageProcessingValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/image_processing');
		} catch (\RuntimeException $e) {
			$currentImageProcessingValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/image_processing');
		}
		try {
			$currentImValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/im');
		} catch (\RuntimeException $e) {
			$currentImValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/im');
		}
		try {
			$currentGdlibValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/gdlib');
		} catch (\RuntimeException $e) {
			$currentGdlibValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/gdlib');
		}
		// If image processing is fully disabled, im and gdlib sub settings must be 0
		if (!$currentImageProcessingValue) {
			if ($currentImValue != 0) {
				$changedValues['GFX/im'] = 0;
			}
			if ($currentGdlibValue != 0) {
				$changedValues['GFX/gdlib'] = 0;
			}
		}
		if (count($changedValues) > 0) {
			$this->configurationManager->setLocalConfigurationValuesByPathValuePairs($changedValues);
			$this->throwRedirectException();
		}
	}

	/**
	 * Detail configuration of Image Magick settings must be cleared
	 * if Image Magick handling is disabled.
	 *
	 * "Configuration presets" in install tool is not type safe, so value
	 * comparisons here are not type safe too, to not trigger changes to
	 * LocalConfiguration again.
	 *
	 * @return void
	 */
	protected function disableImageMagickDetailSettingsIfImageMagickIsDisabled() {
		$changedValues = array();
		try {
			$currentImValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/im');
		}
		catch (\RuntimeException $e) {
			$currentImValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/im');
		}
		try {
			$currentImPathValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/im_path');
		}
		catch (\RuntimeException $e) {
			$currentImPathValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/im_path');
		}
		try {
			$currentImPathLzwValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/im_path_lzw');
		}
		catch (\RuntimeException $e) {
			$currentImPathLzwValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/im_path_lzw');
		}
		try {
			$currentImageFileExtValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/imagefile_ext');
		}
		catch (\RuntimeException $e) {
			$currentImageFileExtValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/imagefile_ext');
		}
		try {
			$currentThumbnailsValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/thumbnails');
		}
		catch (\RuntimeException $e) {
			$currentThumbnailsValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/thumbnails');
		}
		if (!$currentImValue) {
			if ($currentImPathValue != '') {
				$changedValues['GFX/im_path'] = '';
			}
			if ($currentImPathLzwValue != '') {
				$changedValues['GFX/im_path_lzw'] = '';
			}
			if ($currentImageFileExtValue !== 'gif,jpg,jpeg,png') {
				$changedValues['GFX/imagefile_ext'] = 'gif,jpg,jpeg,png';
			}
			if ($currentThumbnailsValue != 0) {
				$changedValues['GFX/thumbnails'] = 0;
			}
		}
		if (count($changedValues) > 0) {
			$this->configurationManager->setLocalConfigurationValuesByPathValuePairs($changedValues);
			$this->throwRedirectException();
		}
	}

	/**
	 * Detail configuration of Image Magick and Graphics Magick settings
	 * depending on main values.
	 *
	 * "Configuration presets" in install tool is not type safe, so value
	 * comparisons here are not type safe too, to not trigger changes to
	 * LocalConfiguration again.
	 *
	 * @return void
	 */
	protected function setImageMagickDetailSettings() {
		$changedValues = array();
		try {
			$currentIm5Value = $this->configurationManager->getLocalConfigurationValueByPath('GFX/im_version_5');
		}
		catch (\RuntimeException $e) {
			$currentIm5Value = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/im_version_5');
		}
		try {
			$currentImMaskValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/im_mask_temp_ext_gif');
		}
		catch (\RuntimeException $e) {
			$currentImMaskValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/im_mask_temp_ext_gif');
		}
		try {
			$currentIm5EffectsValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/im_v5effects');
		}
		catch (\RuntimeException $e) {
			$currentIm5EffectsValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/im_v5effects');
		}
		if (strlen($currentIm5Value) > 0) {
			if ($currentImMaskValue != 1) {
				$changedValues['GFX/im_mask_temp_ext_gif'] = 1;
			}
			if ($currentIm5Value === 'gm') {
				if ($currentIm5EffectsValue != -1) {
					$changedValues['GFX/im_v5effects'] = -1;
				}
			}
		}
		if (count($changedValues) > 0) {
			$this->configurationManager->setLocalConfigurationValuesByPathValuePairs($changedValues);
			$this->throwRedirectException();
		}
	}

	/**
	 * Make sure file table is categorized as of TYPO3 6.2. To enable DAM Migration
	 * sys_file_metadata table is included in DefaultConfiguration.
	 * If the setting already has been modified but does not contain sys_file_metadata: add it
	 *
	 * @return void
	 */
	protected function addFileTableToDefaultCategorizedTablesIfAlreadyCustomized() {
		/** @var \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager */
		$configurationManager = $this->objectManager->get('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');

		$default = $configurationManager->getDefaultConfigurationValueByPath('SYS/defaultCategorizedTables');
		try {
			$actual = $configurationManager->getLocalConfigurationValueByPath('SYS/defaultCategorizedTables');
		} catch(\RuntimeException $e) {
			$actual = '';
		}

		$tables =  GeneralUtility::trimExplode(',', $actual);
		if ($actual !== '' && $actual !== $default && !in_array('sys_file_metadata', $tables)) {
			$tables[] = 'sys_file_metadata';
			$configurationManager->setLocalConfigurationValueByPath('SYS/defaultCategorizedTables', implode(',', $tables));
			$this->throwRedirectException();
		}
	}

	/**
	 * Migrate old Install Tool Wizard "done"-settings to new class names
	 * this happens usually when an existing 6.0/6.1 has called the TceformsUpdateWizard wizard
	 * and has written Tx_Install_Updates_File_TceformsUpdateWizard in TYPO3's LocalConfiguration.php
	 *
	 * @return void
	 */
	protected function migrateOldInstallWizardDoneSettingsToNewClassNames() {
		$classNamesToConvert = array();
		$localConfiguration = $this->configurationManager->getLocalConfiguration();
		// check for wizards that have been run already and don't start with TYPO3...
		if (isset($localConfiguration['INSTALL']['wizardDone']) && is_array($localConfiguration['INSTALL']['wizardDone'])) {
			$classNames = array_keys($localConfiguration['INSTALL']['wizardDone']);
			foreach ($classNames as $className) {
				if (!GeneralUtility::isFirstPartOfStr($className, 'TYPO3')) {
					$classNamesToConvert[] = $className;
				}
			}
		}
		if (!count($classNamesToConvert)) {
			return;
		}

		$migratedClassesMapping = array(
			'Tx_Install_Updates_File_TceformsUpdateWizard' => 'TYPO3\\CMS\\Install\\Updates\\TceformsUpdateWizard'
		);

		$migratedSettings = array();
		$settingsToRemove = array();
		foreach ($classNamesToConvert as $oldClassName) {
			if (isset($migratedClassesMapping[$oldClassName])) {
				$newClassName = $migratedClassesMapping[$oldClassName];
			} else {
				continue;
			}
			$oldValue = NULL;
			$newValue = NULL;
			try {
				$oldValue = $this->configurationManager->getLocalConfigurationValueByPath('INSTALL/wizardDone/' . $oldClassName);
			} catch (\RuntimeException $e) {
				// The old configuration does not exist
				continue;
			}
			try {
				$newValue = $this->configurationManager->getLocalConfigurationValueByPath('INSTALL/wizardDone/' . $newClassName);
			} catch (\RuntimeException $e) {
				// The new configuration does not exist yet
			}
			if ($newValue === NULL) {
				// Migrate the old configuration to the new one
				$migratedSettings['INSTALL/wizardDone/' . $newClassName] = $oldValue;
			}
			$settingsToRemove[] = 'INSTALL/wizardDone/' . $oldClassName;

		}

		if (count($migratedSettings)) {
			$this->configurationManager->setLocalConfigurationValuesByPathValuePairs($migratedSettings);
		}
		$this->configurationManager->removeLocalConfigurationKeysByPath($settingsToRemove);
		if (count($migratedSettings) || count($settingsToRemove)) {
			$this->throwRedirectException();
		}
	}

	/**
	 * Throw exception after configuration change to trigger a redirect.
	 *
	 * @throws \TYPO3\CMS\Install\Controller\Exception\RedirectException
	 */
	protected function throwRedirectException() {
		throw new \TYPO3\CMS\Install\Controller\Exception\RedirectException(
			'Configuration updated, reload needed',
			1379024938
		);
	}
}
