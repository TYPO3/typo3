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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Execute "silent" LocalConfiguration upgrades if needed.
 *
 * Some LocalConfiguration settings are obsolete or changed over time.
 * This class handles upgrades of these settings. It is called by
 * the step controller at an early point.
 *
 * Every change is encapsulated in one method an must throw a RedirectException
 * if new data is written to LocalConfiguration. This is catched by above
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
		// #48179
		'EXT/em_mirrorListURL',
		'EXT/em_wsdlURL',
		// #43094
		'EXT/extList',
		// #35877
		'EXT/extList_FE',
		// #41813
		'EXT/noEdit',
		// #26090
		'FE/defaultTypoScript_editorcfg',
		'FE/defaultTypoScript_editorcfg.',
		// #25099
		'FE/simulateStaticDocuments',
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
		$this->setProxyAuthScheme();
		$this->disableImageMagickAndGdlibIfImageProcessingIsDisabled();
		$this->disableImageMagickDetailSettingsIfImageMagickIsDisabled();
		$this->setImageMagickDetailSettings();
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
		// If something was changed: Trigger a reload to have new values in next request
		if ($removed) {
			$this->throwRedirectException();
		}
	}

	/**
	 * Backend login security is set to rsa if rsaauth
	 * is installed (but not used) otherwise the default value "normal" has to be used.
	 *
	 * @return void
	 */
	protected function configureBackendLoginSecurity() {
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('rsaauth')
			&& $GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel'] !== 'rsa')
		{
			$this->configurationManager->setLocalConfigurationValueByPath('BE/loginSecurityLevel', 'rsa');
			$this->throwRedirectException();
		} elseif (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('rsaauth')
			&& $GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel'] !== 'normal'
		) {
			$configurationManager = $this->objectManager->get('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
			$configurationManager->setLocalConfigurationValueByPath('BE/loginSecurityLevel', 'normal');
			$this->throwRedirectException();
		}
	}

	/**
	 * Check the settings for salted passwords extension to
	 * load it as a required extension.
	 *
	 * @return void
	 */
	protected function configureSaltedPasswords() {
		$defaultConfiguration = $this->configurationManager->getDefaultConfiguration();
		$defaultExtensionConfiguration = unserialize($defaultConfiguration['EXT']['extConf']['saltedpasswords']);
		$extensionConfiguration = @unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['saltedpasswords']);
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
		if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
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
		// If image processing is fully disabled, im and gdlib sub settings must be 0
		if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['image_processing']) {
			if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im'] != 0) {
				$changedValues['GFX/im'] = 0;
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'] != 0) {
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
		if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['im']) {
			if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path'] != '') {
				$changedValues['GFX/im_path'] = '';
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw'] != '') {
				$changedValues['GFX/im_path_lzw'] = '';
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] !== 'gif,jpg,jpeg,png') {
				$changedValues['GFX/imagefile_ext'] = 'gif,jpg,jpeg,png';
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] != 0) {
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
		if (isset($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5'])
			&& strlen($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5']) > 0
		) {
			if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_mask_temp_ext_gif'] != 1) {
				$changedValues['GFX/im_mask_temp_ext_gif'] = 1;
			}
			if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5'] === 'gm') {
				if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_v5effects'] != -1) {
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
