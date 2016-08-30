<?php
namespace TYPO3\CMS\Install\Service;

/*
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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Controller\Exception\RedirectException;

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
class SilentConfigurationUpgradeService
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager = null;

    /**
     * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager
     */
    protected $configurationManager = null;

    /**
     * List of obsolete configuration options in LocalConfiguration to be removed
     * Example:
     *    // #forge-ticket
     *    'BE/somesetting',
     *
     * @var array
     */
    protected $obsoleteLocalConfigurationSettings = [
        // #62402
        'INSTALL/wizardDone/TYPO3\\CMS\\Install\\Updates\\ExtensionManagerTables',
        'INSTALL/wizardDone/TYPO3\\CMS\\Install\\Updates\\FileIdentifierHashUpdate',
        'INSTALL/wizardDone/TYPO3\\CMS\\Install\\Updates\\FilemountUpdateWizard',
        'INSTALL/wizardDone/TYPO3\\CMS\\Install\\Updates\\FilePermissionUpdate',
        'INSTALL/wizardDone/TYPO3\\CMS\\Install\\Updates\\FileTableSplittingUpdate',
        'INSTALL/wizardDone/TYPO3\\CMS\\Install\\Updates\\InitUpdateWizard',
        'INSTALL/wizardDone/TYPO3\\CMS\\Install\\Updates\\MediaFlexformUpdate',
        'INSTALL/wizardDone/TYPO3\\CMS\\Install\\Updates\\ReferenceIntegrityUpdateWizard',
        'INSTALL/wizardDone/TYPO3\\CMS\\Install\\Updates\\RteFileLinksUpdateWizard',
        'INSTALL/wizardDone/TYPO3\\CMS\\Install\\Updates\\RteMagicImagesUpdateWizard',
        'INSTALL/wizardDone/TYPO3\\CMS\\Install\\Updates\\TceformsUpdateWizard',
        'INSTALL/wizardDone/TYPO3\\CMS\\Install\\Updates\\TtContentUploadsUpdateWizard',
        'INSTALL/wizardDone/TYPO3\\CMS\\Install\\Updates\\TruncateSysFileProcessedFileTable',
        // #68183
        'INSTALL/wizardDone/TYPO3\\CMS\\Install\\Updates\\MigrateShortcutUrlsUpdate',
        // #63818
        'BE/staticFileEditPath',
        // #64226
        'BE/accessListRenderMode',
        // #66431
        'BE/loginNewsTitle',
        // #24900
        'SYS/compat_version',
        // #64643
        'GFX/enable_typo3temp_db_tracking',
        // #48542
        'GFX/TTFdpi',
        // #64872
        'SYS/useCachingFramework',
        // #65912
        'FE/allowedTempPaths',
        // #66034
        'FE/activateContentAdapter',
        // #66902
        'SYS/loginCopyrightShowVersion',
        // #66903
        'BE/RTEenabled',
        // #66906
        'GFX/png_to_gif',
        // #67411
        'SYS/caching/cacheConfigurations/cache_classes',
        // #68178
        'SYS/form_enctype',
        // #69904
        'BE/diff_path',
        // #69930
        'SYS/serverTimeZone',
        // #70138
        'BE/flexFormXMLincludeDiffBase',
        // #71110
        'BE/maxFileSize',
    ];

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager
     */
    public function injectConfigurationManager(\TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Executed configuration upgrades. Single upgrade methods must throw a
     * RedirectException if something was written to LocalConfiguration.
     *
     * @return void
     */
    public function execute()
    {
        $this->generateEncryptionKeyIfNeeded();
        $this->configureBackendLoginSecurity();
        $this->configureSaltedPasswords();
        $this->setProxyAuthScheme();
        $this->transferDeprecatedCurlSettings();
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
    protected function removeObsoleteLocalConfigurationSettings()
    {
        $removed = $this->configurationManager->removeLocalConfigurationKeysByPath($this->obsoleteLocalConfigurationSettings);

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
    protected function configureBackendLoginSecurity()
    {
        $rsaauthLoaded = ExtensionManagementUtility::isLoaded('rsaauth');
        try {
            $currentLoginSecurityLevelValue = $this->configurationManager->getLocalConfigurationValueByPath('BE/loginSecurityLevel');
            if ($rsaauthLoaded && $currentLoginSecurityLevelValue !== 'rsa') {
                $this->configurationManager->setLocalConfigurationValueByPath('BE/loginSecurityLevel', 'rsa');
                $this->throwRedirectException();
            } elseif (!$rsaauthLoaded && $currentLoginSecurityLevelValue !== 'normal') {
                $this->configurationManager->setLocalConfigurationValueByPath('BE/loginSecurityLevel', 'normal');
                $this->throwRedirectException();
            }
        } catch (\RuntimeException $e) {
            // If an exception is thrown, the value is not set in LocalConfiguration
            $this->configurationManager->setLocalConfigurationValueByPath('BE/loginSecurityLevel', $rsaauthLoaded ? 'rsa' : 'normal');
            $this->throwRedirectException();
        }
    }

    /**
     * Check the settings for salted passwords extension to load it as a required extension.
     * Unset obsolete configuration options if given.
     *
     * @return void
     */
    protected function configureSaltedPasswords()
    {
        $defaultConfiguration = $this->configurationManager->getDefaultConfiguration();
        $defaultExtensionConfiguration = unserialize($defaultConfiguration['EXT']['extConf']['saltedpasswords']);
        try {
            $extensionConfiguration = @unserialize($this->configurationManager->getLocalConfigurationValueByPath('EXT/extConf/saltedpasswords'));
        } catch (\RuntimeException $e) {
            $extensionConfiguration = [];
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
    protected function generateEncryptionKeyIfNeeded()
    {
        try {
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
    protected function setProxyAuthScheme()
    {
        // Get current value from LocalConfiguration
        try {
            $currentValueInLocalConfiguration = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/proxy_auth_scheme');
        } catch (\RuntimeException $e) {
            // If an exception is thrown, the value is not set in LocalConfiguration, so we don't need to do anything
            return;
        }
        if ($currentValueInLocalConfiguration !== 'digest') {
            $this->configurationManager->removeLocalConfigurationKeysByPath(['HTTP/proxy_auth_scheme']);
            $this->throwRedirectException();
        }
    }

    /**
     * Parse old curl options and set new http ones instead
     *
     * @return void
     */
    protected function transferDeprecatedCurlSettings()
    {
        $changed = false;
        try {
            $curlProxyServer = $this->configurationManager->getLocalConfigurationValueByPath('SYS/curlProxyServer');
        } catch (\RuntimeException $e) {
            $curlProxyServer = '';
        }
        try {
            $proxyHost = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/proxy_host');
        } catch (\RuntimeException $e) {
            $proxyHost = '';
        }
        if (!empty($curlProxyServer) && empty($proxyHost)) {
            $curlProxy = rtrim(preg_replace('#^https?://#', '', $curlProxyServer), '/');
            $proxyParts = GeneralUtility::revExplode(':', $curlProxy, 2);
            $this->configurationManager->setLocalConfigurationValueByPath('HTTP/proxy_host', $proxyParts[0]);
            $this->configurationManager->setLocalConfigurationValueByPath('HTTP/proxy_port', $proxyParts[1]);
            $changed = true;
        }

        try {
            $curlProxyUserPass = $this->configurationManager->getLocalConfigurationValueByPath('SYS/curlProxyUserPass');
        } catch (\RuntimeException $e) {
            $curlProxyUserPass = '';
        }
        try {
            $proxyUser = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/proxy_user');
        } catch (\RuntimeException $e) {
            $proxyUser = '';
        }
        if (!empty($curlProxyUserPass) && empty($proxyUser)) {
            $userPassParts = explode(':', $curlProxyUserPass, 2);
            $this->configurationManager->setLocalConfigurationValueByPath('HTTP/proxy_user', $userPassParts[0]);
            $this->configurationManager->setLocalConfigurationValueByPath('HTTP/proxy_password', $userPassParts[1]);
            $changed = true;
        }

        try {
            $curlUse = $this->configurationManager->getLocalConfigurationValueByPath('SYS/curlUse');
        } catch (\RuntimeException $e) {
            $curlUse = '';
        }
        try {
            $adapter = $this->configurationManager->getConfigurationValueByPath('HTTP/adapter');
        } catch (\RuntimeException $e) {
            $adapter = '';
        }
        if (!empty($curlUse) && $adapter !== 'curl') {
            $GLOBALS['TYPO3_CONF_VARS']['HTTP']['adapter'] = 'curl';
            $this->configurationManager->setLocalConfigurationValueByPath('HTTP/adapter', 'curl');
            $changed = true;
        }
        if ($changed) {
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
    protected function disableImageMagickAndGdlibIfImageProcessingIsDisabled()
    {
        $changedValues = [];
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
        if (!empty($changedValues)) {
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
    protected function disableImageMagickDetailSettingsIfImageMagickIsDisabled()
    {
        $changedValues = [];
        try {
            $currentImValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/im');
        } catch (\RuntimeException $e) {
            $currentImValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/im');
        }
        try {
            $currentImPathValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/im_path');
        } catch (\RuntimeException $e) {
            $currentImPathValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/im_path');
        }
        try {
            $currentImPathLzwValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/im_path_lzw');
        } catch (\RuntimeException $e) {
            $currentImPathLzwValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/im_path_lzw');
        }
        try {
            $currentImageFileExtValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/imagefile_ext');
        } catch (\RuntimeException $e) {
            $currentImageFileExtValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/imagefile_ext');
        }
        try {
            $currentThumbnailsValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/thumbnails');
        } catch (\RuntimeException $e) {
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
        if (!empty($changedValues)) {
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
    protected function setImageMagickDetailSettings()
    {
        $changedValues = [];
        try {
            $currentIm5Value = $this->configurationManager->getLocalConfigurationValueByPath('GFX/im_version_5');
        } catch (\RuntimeException $e) {
            $currentIm5Value = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/im_version_5');
        }
        try {
            $currentImMaskValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/im_mask_temp_ext_gif');
        } catch (\RuntimeException $e) {
            $currentImMaskValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/im_mask_temp_ext_gif');
        }
        try {
            $currentIm5EffectsValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/im_v5effects');
        } catch (\RuntimeException $e) {
            $currentIm5EffectsValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/im_v5effects');
        }
        if ((string)$currentIm5Value !== '') {
            if ($currentImMaskValue != 1) {
                $changedValues['GFX/im_mask_temp_ext_gif'] = 1;
            }
            if ($currentIm5Value === 'gm') {
                if ($currentIm5EffectsValue != -1) {
                    $changedValues['GFX/im_v5effects'] = -1;
                }
            }
        }
        if (!empty($changedValues)) {
            $this->configurationManager->setLocalConfigurationValuesByPathValuePairs($changedValues);
            $this->throwRedirectException();
        }
    }

    /**
     * Throw exception after configuration change to trigger a redirect.
     *
     * @throws RedirectException
     */
    protected function throwRedirectException()
    {
        throw new RedirectException(
            'Configuration updated, reload needed',
            1379024938
        );
    }
}
