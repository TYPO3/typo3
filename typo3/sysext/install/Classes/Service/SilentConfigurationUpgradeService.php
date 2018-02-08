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

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Crypto\Random;
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
     * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager
     */
    protected $configurationManager;

    /**
     * List of obsolete configuration options in LocalConfiguration to be removed
     * Example:
     *    // #forge-ticket
     *    'BE/somesetting',
     *
     * @var array
     */
    protected $obsoleteLocalConfigurationSettings = [
        // #72400
        'BE/spriteIconGenerator_handler',
        // #72417
        'SYS/lockingMode',
        // #72473
        'FE/secureFormmail',
        'FE/strictFormmail',
        'FE/formmailMaxAttachmentSize',
        // #72337
        'SYS/t3lib_cs_utils',
        'SYS/t3lib_cs_convMethod',
        // #72604
        'SYS/maxFileNameLength',
        // #72602
        'BE/unzip_path',
        // #72615
        'BE/notificationPrefix',
        // #72616
        'BE/XCLASS',
        'FE/XCLASS',
        // #43085
        'GFX/image_processing',
        // #70056
        'SYS/curlUse',
        'SYS/curlProxyNTLM',
        'SYS/curlProxyServer',
        'SYS/curlProxyTunnel',
        'SYS/curlProxyUserPass',
        'SYS/curlTimeout',
        // #75355
        'BE/niceFlexFormXMLtags',
        'BE/compactFlexFormXML',
        // #75625
        'SYS/clearCacheSystem',
        // #77411
        'SYS/caching/cacheConfigurations/extbase_typo3dbbackend_tablecolumns',
        // #77460
        'SYS/caching/cacheConfigurations/extbase_typo3dbbackend_queries',
        // #79513
        'FE/lockHashKeyWords',
        'BE/lockHashKeyWords',
        // #78835
        'SYS/cookieHttpOnly',
        // #71095
        'BE/lang',
        // #80050
        'FE/cHashIncludePageId',
    ];

    public function __construct(ConfigurationManager $configurationManager = null)
    {
        $this->configurationManager = $configurationManager ?: GeneralUtility::makeInstance(ConfigurationManager::class);
    }

    /**
     * Executed configuration upgrades. Single upgrade methods must throw a
     * RedirectException if something was written to LocalConfiguration.
     */
    public function execute()
    {
        $this->generateEncryptionKeyIfNeeded();
        $this->configureBackendLoginSecurity();
        $this->migrateImageProcessorSetting();
        $this->transferHttpSettings();
        $this->disableImageMagickDetailSettingsIfImageMagickIsDisabled();
        $this->setImageMagickDetailSettings();
        $this->migrateThumbnailsPngSetting();
        $this->migrateLockSslSetting();
        $this->migrateDatabaseConnectionSettings();
        $this->migrateDatabaseConnectionCharset();
        $this->migrateDatabaseDriverOptions();
        $this->migrateLangDebug();
        $this->migrateDisplayErrorsSetting();

        // Should run at the end to prevent that obsolete settings are removed before migration
        $this->removeObsoleteLocalConfigurationSettings();
    }

    /**
     * Some settings in LocalConfiguration vanished in DefaultConfiguration
     * and have no impact on the core anymore.
     * To keep the configuration clean, those old settings are just silently
     * removed from LocalConfiguration if set.
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
            $this->configurationManager->setLocalConfigurationValueByPath(
                'BE/loginSecurityLevel',
                $rsaauthLoaded ? 'rsa' : 'normal'
            );
            $this->throwRedirectException();
        }
    }

    /**
     * The encryption key is crucial for securing form tokens
     * and the whole TYPO3 link rendering later on. A random key is set here in
     * LocalConfiguration if it does not exist yet. This might possible happen
     * during upgrading and will happen during first install.
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
            $randomKey = GeneralUtility::makeInstance(Random::class)->generateRandomHexString(96);
            $this->configurationManager->setLocalConfigurationValueByPath('SYS/encryptionKey', $randomKey);
            $this->throwRedirectException();
        }
    }

    /**
     * Parse old curl and HTTP options and set new HTTP options, related to Guzzle
     */
    protected function transferHttpSettings()
    {
        $changed = false;
        $newParameters = [];
        $obsoleteParameters = [];

        // Remove / migrate options to new options
        try {
            // Check if the adapter option is set, if so, set it to the parameters that are obsolete
            $this->configurationManager->getLocalConfigurationValueByPath('HTTP/adapter');
            $obsoleteParameters[] = 'HTTP/adapter';
        } catch (\RuntimeException $e) {
        }
        try {
            $newParameters['HTTP/version'] = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/protocol_version');
            $obsoleteParameters[] = 'HTTP/protocol_version';
        } catch (\RuntimeException $e) {
        }
        try {
            $this->configurationManager->getLocalConfigurationValueByPath('HTTP/ssl_verify_host');
            $obsoleteParameters[] = 'HTTP/ssl_verify_host';
        } catch (\RuntimeException $e) {
        }
        try {
            $legacyUserAgent = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/userAgent');
            $newParameters['HTTP/headers/User-Agent'] = $legacyUserAgent;
            $obsoleteParameters[] = 'HTTP/userAgent';
        } catch (\RuntimeException $e) {
        }

        // Redirects
        try {
            $legacyFollowRedirects = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/follow_redirects');
            $obsoleteParameters[] = 'HTTP/follow_redirects';
        } catch (\RuntimeException $e) {
            $legacyFollowRedirects = '';
        }
        try {
            $legacyMaximumRedirects = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/max_redirects');
            $obsoleteParameters[] = 'HTTP/max_redirects';
        } catch (\RuntimeException $e) {
            $legacyMaximumRedirects = '';
        }
        try {
            $legacyStrictRedirects = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/strict_redirects');
            $obsoleteParameters[] = 'HTTP/strict_redirects';
        } catch (\RuntimeException $e) {
            $legacyStrictRedirects = '';
        }

        // Check if redirects have been disabled
        if ($legacyFollowRedirects !== '' && (bool)$legacyFollowRedirects === false) {
            $newParameters['HTTP/allow_redirects'] = false;
        } elseif ($legacyMaximumRedirects !== '' || $legacyStrictRedirects !== '') {
            $newParameters['HTTP/allow_redirects'] = [];
            if ($legacyMaximumRedirects !== '' && (int)$legacyMaximumRedirects !== 5) {
                $newParameters['HTTP/allow_redirects']['max'] = (int)$legacyMaximumRedirects;
            }
            if ($legacyStrictRedirects !== '' && (bool)$legacyStrictRedirects === true) {
                $newParameters['HTTP/allow_redirects']['strict'] = true;
            }
            // defaults are used, no need to set the option in LocalConfiguration.php
            if (empty($newParameters['HTTP/allow_redirects'])) {
                unset($newParameters['HTTP/allow_redirects']);
            }
        }

        // Migrate Proxy settings
        try {
            // Currently without protocol or port
            $legacyProxyHost = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/proxy_host');
            $obsoleteParameters[] = 'HTTP/proxy_host';
        } catch (\RuntimeException $e) {
            $legacyProxyHost = '';
        }
        try {
            $legacyProxyPort = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/proxy_port');
            $obsoleteParameters[] = 'HTTP/proxy_port';
        } catch (\RuntimeException $e) {
            $legacyProxyPort = '';
        }
        try {
            $legacyProxyUser = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/proxy_user');
            $obsoleteParameters[] = 'HTTP/proxy_user';
        } catch (\RuntimeException $e) {
            $legacyProxyUser = '';
        }
        try {
            $legacyProxyPassword = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/proxy_password');
            $obsoleteParameters[] = 'HTTP/proxy_password';
        } catch (\RuntimeException $e) {
            $legacyProxyPassword = '';
        }
        // Auth Scheme: Basic, digest etc.
        try {
            $legacyProxyAuthScheme = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/proxy_auth_scheme');
            $obsoleteParameters[] = 'HTTP/proxy_auth_scheme';
        } catch (\RuntimeException $e) {
            $legacyProxyAuthScheme = '';
        }

        if ($legacyProxyHost !== '') {
            $proxy = 'http://';
            if ($legacyProxyAuthScheme !== '' && $legacyProxyUser !== '' && $legacyProxyPassword !== '') {
                $proxy .= $legacyProxyUser . ':' . $legacyProxyPassword . '@';
            }
            $proxy .= $legacyProxyHost;
            if ($legacyProxyPort !== '') {
                $proxy .= ':' . $legacyProxyPort;
            }
            $newParameters['HTTP/proxy'] = $proxy;
        }

        // Verify peers
        // see http://docs.guzzlephp.org/en/latest/request-options.html#verify
        try {
            $legacySslVerifyPeer = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/ssl_verify_peer');
            $obsoleteParameters[] = 'HTTP/ssl_verify_peer';
        } catch (\RuntimeException $e) {
            $legacySslVerifyPeer = '';
        }

        // Directory holding multiple Certificate Authority files
        try {
            $legacySslCaPath = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/ssl_capath');
            $obsoleteParameters[] = 'HTTP/ssl_capath';
        } catch (\RuntimeException $e) {
            $legacySslCaPath = '';
        }
        // Certificate Authority file to verify the peer with (use when ssl_verify_peer is TRUE)
        try {
            $legacySslCaFile = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/ssl_cafile');
            $obsoleteParameters[] = 'HTTP/ssl_cafile';
        } catch (\RuntimeException $e) {
            $legacySslCaFile = '';
        }
        if ($legacySslVerifyPeer !== '') {
            if ($legacySslCaFile !== '' && $legacySslCaPath !== '') {
                $newParameters['HTTP/verify'] = $legacySslCaPath . $legacySslCaFile;
            } elseif ((bool)$legacySslVerifyPeer === false) {
                $newParameters['HTTP/verify'] = false;
            }
        }

        // SSL Key + Passphrase
        // Name of a file containing local certificate
        try {
            $legacySslLocalCert = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/ssl_local_cert');
            $obsoleteParameters[] = 'HTTP/ssl_local_cert';
        } catch (\RuntimeException $e) {
            $legacySslLocalCert = '';
        }

        // Passphrase with which local certificate was encoded
        try {
            $legacySslPassphrase = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/ssl_passphrase');
            $obsoleteParameters[] = 'HTTP/ssl_passphrase';
        } catch (\RuntimeException $e) {
            $legacySslPassphrase = '';
        }

        if ($legacySslLocalCert !== '') {
            if ($legacySslPassphrase !== '') {
                $newParameters['HTTP/ssl_key'] = [
                    $legacySslLocalCert,
                    $legacySslPassphrase
                ];
            } else {
                $newParameters['HTTP/ssl_key'] = $legacySslLocalCert;
            }
        }

        // Update the LocalConfiguration file if obsolete parameters or new parameters are set
        if (!empty($obsoleteParameters)) {
            $this->configurationManager->removeLocalConfigurationKeysByPath($obsoleteParameters);
            $changed = true;
        }
        if (!empty($newParameters)) {
            $this->configurationManager->setLocalConfigurationValuesByPathValuePairs($newParameters);
            $changed = true;
        }
        if ($changed) {
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
     */
    protected function disableImageMagickDetailSettingsIfImageMagickIsDisabled()
    {
        $changedValues = [];
        try {
            $currentImValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/processor_enabled');
        } catch (\RuntimeException $e) {
            $currentImValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/processor_enabled');
        }

        try {
            $currentImPathValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/processor_path');
        } catch (\RuntimeException $e) {
            $currentImPathValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/processor_path');
        }

        try {
            $currentImPathLzwValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/processor_path_lzw');
        } catch (\RuntimeException $e) {
            $currentImPathLzwValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/processor_path_lzw');
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
                $changedValues['GFX/processor_path'] = '';
            }
            if ($currentImPathLzwValue != '') {
                $changedValues['GFX/processor_path_lzw'] = '';
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
     */
    protected function setImageMagickDetailSettings()
    {
        $changedValues = [];
        try {
            $currentProcessorValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/processor');
        } catch (\RuntimeException $e) {
            $currentProcessorValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/processor');
        }

        try {
            $currentProcessorMaskValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/processor_allowTemporaryMasksAsPng');
        } catch (\RuntimeException $e) {
            $currentProcessorMaskValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/processor_allowTemporaryMasksAsPng');
        }

        try {
            $currentProcessorEffectsValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/processor_effects');
        } catch (\RuntimeException $e) {
            $currentProcessorEffectsValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/processor_effects');
        }

        if ((string)$currentProcessorValue !== '') {
            if ($currentProcessorMaskValue != 0) {
                $changedValues['GFX/processor_allowTemporaryMasksAsPng'] = 0;
            }
            if ($currentProcessorValue === 'GraphicsMagick') {
                if ($currentProcessorEffectsValue != -1) {
                    $changedValues['GFX/processor_effects'] = -1;
                }
            }
        }
        if (!empty($changedValues)) {
            $this->configurationManager->setLocalConfigurationValuesByPathValuePairs($changedValues);
            $this->throwRedirectException();
        }
    }

    /**
     * Migrate the definition of the image processor from the configuration value
     * im_version_5 to the setting processor.
     */
    protected function migrateImageProcessorSetting()
    {
        $changedSettings = [];
        $settingsToRename = [
            'GFX/im' => 'GFX/processor_enabled',
            'GFX/im_version_5' => 'GFX/processor',
            'GFX/im_v5effects' => 'GFX/processor_effects',
            'GFX/im_path' => 'GFX/processor_path',
            'GFX/im_path_lzw' => 'GFX/processor_path_lzw',
            'GFX/im_mask_temp_ext_gif' => 'GFX/processor_allowTemporaryMasksAsPng',
            'GFX/im_noScaleUp' => 'GFX/processor_allowUpscaling',
            'GFX/im_noFramePrepended' => 'GFX/processor_allowFrameSelection',
            'GFX/im_stripProfileCommand' => 'GFX/processor_stripColorProfileCommand',
            'GFX/im_useStripProfileByDefault' => 'GFX/processor_stripColorProfileByDefault',
            'GFX/colorspace' => 'GFX/processor_colorspace',
        ];

        foreach ($settingsToRename as $oldPath => $newPath) {
            try {
                $value = $this->configurationManager->getLocalConfigurationValueByPath($oldPath);
                $this->configurationManager->setLocalConfigurationValueByPath($newPath, $value);
                $changedSettings[$oldPath] = true;
            } catch (\RuntimeException $e) {
                // If an exception is thrown, the value is not set in LocalConfiguration
                $changedSettings[$oldPath] = false;
            }
        }

        if (!empty($changedSettings['GFX/im_version_5'])) {
            $currentProcessorValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/im_version_5');
            $newProcessorValue = $currentProcessorValue === 'gm' ? 'GraphicsMagick' : 'ImageMagick';
            $this->configurationManager->setLocalConfigurationValueByPath('GFX/processor', $newProcessorValue);
        }

        if (!empty($changedSettings['GFX/im_noScaleUp'])) {
            $currentProcessorValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/im_noScaleUp');
            $newProcessorValue = !$currentProcessorValue;
            $this->configurationManager->setLocalConfigurationValueByPath(
                'GFX/processor_allowUpscaling',
                $newProcessorValue
            );
        }

        if (!empty($changedSettings['GFX/im_noFramePrepended'])) {
            $currentProcessorValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/im_noFramePrepended');
            $newProcessorValue = !$currentProcessorValue;
            $this->configurationManager->setLocalConfigurationValueByPath(
                'GFX/processor_allowFrameSelection',
                $newProcessorValue
            );
        }

        if (!empty($changedSettings['GFX/im_mask_temp_ext_gif'])) {
            $currentProcessorValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/im_mask_temp_ext_gif');
            $newProcessorValue = !$currentProcessorValue;
            $this->configurationManager->setLocalConfigurationValueByPath(
                'GFX/processor_allowTemporaryMasksAsPng',
                $newProcessorValue
            );
        }

        if (!empty(array_filter($changedSettings))) {
            $this->configurationManager->removeLocalConfigurationKeysByPath(array_keys($changedSettings));
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

    /**
     * Migrate the configuration value thumbnails_png to a boolean value.
     */
    protected function migrateThumbnailsPngSetting()
    {
        $changedValues = [];
        try {
            $currentThumbnailsPngValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/thumbnails_png');
        } catch (\RuntimeException $e) {
            $currentThumbnailsPngValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/thumbnails_png');
        }

        if (is_int($currentThumbnailsPngValue) && $currentThumbnailsPngValue > 0) {
            $changedValues['GFX/thumbnails_png'] = true;
        }
        if (!empty($changedValues)) {
            $this->configurationManager->setLocalConfigurationValuesByPathValuePairs($changedValues);
            $this->throwRedirectException();
        }
    }

    /**
     * Migrate the configuration setting BE/lockSSL to boolean if set in the LocalConfiguration.php file
     */
    protected function migrateLockSslSetting()
    {
        try {
            $currentOption = $this->configurationManager->getLocalConfigurationValueByPath('BE/lockSSL');
            // check if the current option is an integer/string and if it is active
            if (!is_bool($currentOption) && (int)$currentOption > 0) {
                $this->configurationManager->setLocalConfigurationValueByPath('BE/lockSSL', true);
                $this->throwRedirectException();
            }
        } catch (\RuntimeException $e) {
            // no change inside the LocalConfiguration.php found, so nothing needs to be modified
        }
    }

    /**
     * Move the database connection settings to a "Default" connection
     */
    protected function migrateDatabaseConnectionSettings()
    {
        $confManager = $this->configurationManager;

        $newSettings = [];
        $removeSettings = [];

        try {
            $value = $confManager->getLocalConfigurationValueByPath('DB/username');
            $removeSettings[] = 'DB/username';
            $newSettings['DB/Connections/Default/user'] = $value;
        } catch (\RuntimeException $e) {
            // Old setting does not exist, do nothing
        }

        try {
            $value= $confManager->getLocalConfigurationValueByPath('DB/password');
            $removeSettings[] = 'DB/password';
            $newSettings['DB/Connections/Default/password'] = $value;
        } catch (\RuntimeException $e) {
            // Old setting does not exist, do nothing
        }

        try {
            $value = $confManager->getLocalConfigurationValueByPath('DB/host');
            $removeSettings[] = 'DB/host';
            $newSettings['DB/Connections/Default/host'] = $value;
        } catch (\RuntimeException $e) {
            // Old setting does not exist, do nothing
        }

        try {
            $value = $confManager->getLocalConfigurationValueByPath('DB/port');
            $removeSettings[] = 'DB/port';
            $newSettings['DB/Connections/Default/port'] = $value;
        } catch (\RuntimeException $e) {
            // Old setting does not exist, do nothing
        }

        try {
            $value = $confManager->getLocalConfigurationValueByPath('DB/socket');
            $removeSettings[] = 'DB/socket';
            // Remove empty socket connects
            if (!empty($value)) {
                $newSettings['DB/Connections/Default/unix_socket'] = $value;
            }
        } catch (\RuntimeException $e) {
            // Old setting does not exist, do nothing
        }

        try {
            $value = $confManager->getLocalConfigurationValueByPath('DB/database');
            $removeSettings[] = 'DB/database';
            $newSettings['DB/Connections/Default/dbname'] = $value;
        } catch (\RuntimeException $e) {
            // Old setting does not exist, do nothing
        }

        try {
            $value = (bool)$confManager->getLocalConfigurationValueByPath('SYS/dbClientCompress');
            $removeSettings[] = 'SYS/dbClientCompress';
            if ($value) {
                $newSettings['DB/Connections/Default/driverOptions'] = [
                    'flags' => MYSQLI_CLIENT_COMPRESS,
                ];
            }
        } catch (\RuntimeException $e) {
            // Old setting does not exist, do nothing
        }

        try {
            $value = (bool)$confManager->getLocalConfigurationValueByPath('SYS/no_pconnect');
            $removeSettings[] = 'SYS/no_pconnect';
            if (!$value) {
                $newSettings['DB/Connections/Default/persistentConnection'] = true;
            }
        } catch (\RuntimeException $e) {
            // Old setting does not exist, do nothing
        }

        try {
            $value = $confManager->getLocalConfigurationValueByPath('SYS/setDBinit');
            $removeSettings[] = 'SYS/setDBinit';
            $newSettings['DB/Connections/Default/initCommands'] = $value;
        } catch (\RuntimeException $e) {
            // Old setting does not exist, do nothing
        }

        try {
            $confManager->getLocalConfigurationValueByPath('DB/Connections/Default/charset');
        } catch (\RuntimeException $e) {
            // If there is no charset option yet, add it.
            $newSettings['DB/Connections/Default/charset'] = 'utf8';
        }

        try {
            $confManager->getLocalConfigurationValueByPath('DB/Connections/Default/driver');
        } catch (\RuntimeException $e) {
            // Use the mysqli driver by default if no value has been provided yet
            $newSettings['DB/Connections/Default/driver'] = 'mysqli';
        }

        // Add new settings and remove old ones
        if (!empty($newSettings)) {
            $confManager->setLocalConfigurationValuesByPathValuePairs($newSettings);
        }
        if (!empty($removeSettings)) {
            $confManager->removeLocalConfigurationKeysByPath($removeSettings);
        }

        // Throw redirect if something was changed
        if (!empty($newSettings) || !empty($removeSettings)) {
            $this->throwRedirectException();
        }
    }

    /**
     * Migrate the configuration setting DB/Connections/Default/charset to 'utf8' as
     * 'utf-8' is not supported by all MySQL versions.
     */
    protected function migrateDatabaseConnectionCharset()
    {
        $confManager = $this->configurationManager;
        try {
            $driver = $confManager->getLocalConfigurationValueByPath('DB/Connections/Default/driver');
            $charset = $confManager->getLocalConfigurationValueByPath('DB/Connections/Default/charset');
            if (in_array($driver, ['mysqli', 'pdo_mysql', 'drizzle_pdo_mysql'], true) && $charset === 'utf-8') {
                $confManager->setLocalConfigurationValueByPath('DB/Connections/Default/charset', 'utf8');
                $this->throwRedirectException();
            }
        } catch (\RuntimeException $e) {
            // no incompatible charset configuration found, so nothing needs to be modified
        }
    }

    /**
     * Migrate the configuration setting DB/Connections/Default/driverOptions to array type.
     */
    protected function migrateDatabaseDriverOptions()
    {
        $confManager = $this->configurationManager;
        try {
            $options = $confManager->getLocalConfigurationValueByPath('DB/Connections/Default/driverOptions');
            if (!is_array($options)) {
                $confManager->setLocalConfigurationValueByPath(
                    'DB/Connections/Default/driverOptions',
                    ['flags' => (int)$options]
                );
            }
        } catch (\RuntimeException $e) {
            // no driver options found, nothing needs to be modified
        }
    }

    /**
     * Migrate the configuration setting BE/lang/debug if set in the LocalConfiguration.php file
     */
    protected function migrateLangDebug()
    {
        $confManager = $this->configurationManager;
        try {
            $currentOption = $confManager->getLocalConfigurationValueByPath('BE/lang/debug');
            // check if the current option is set and boolean
            if (isset($currentOption) && is_bool($currentOption)) {
                $confManager->setLocalConfigurationValueByPath('BE/languageDebug', $currentOption);
            }
        } catch (\RuntimeException $e) {
            // no change inside the LocalConfiguration.php found, so nothing needs to be modified
        }
    }

    /**
     * Migrate SYS/displayErrors to not contain 2
     */
    protected function migrateDisplayErrorsSetting()
    {
        $confManager = $this->configurationManager;
        try {
            $currentOption = (int)$confManager->getLocalConfigurationValueByPath('SYS/displayErrors');
            // make sure displayErrors is set to 2
            if ($currentOption === 2) {
                $confManager->setLocalConfigurationValueByPath('SYS/displayErrors', -1);
            }
        } catch (\RuntimeException $e) {
            // no change inside the LocalConfiguration.php found, so nothing needs to be modified
        }
    }
}
