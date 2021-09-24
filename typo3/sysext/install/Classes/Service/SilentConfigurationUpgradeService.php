<?php

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

namespace TYPO3\CMS\Install\Service;

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2idPasswordHash;
use TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash;
use TYPO3\CMS\Core\Crypto\PasswordHashing\BcryptPasswordHash;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashInterface;
use TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\Exception\ConfigurationChangedException;

/**
 * Execute "silent" LocalConfiguration upgrades if needed.
 *
 * Some LocalConfiguration settings are obsolete or changed over time.
 * This class handles upgrades of these settings. It is called by
 * the step controller at an early point.
 *
 * Every change is encapsulated in one method and must throw a ConfigurationChangedException
 * if new data is written to LocalConfiguration. This is caught by above
 * step controller to initiate a redirect and start again with adapted configuration.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class SilentConfigurationUpgradeService
{
    /**
     * @var ConfigurationManager
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
        // #80711
        'FE/noPHPscriptInclude',
        'FE/maxSessionDataSize',
        // #82162
        'SYS/enable_errorDLOG',
        'SYS/enable_exceptionDLOG',
        // #82377
        'EXT/allowSystemInstall',
        // #82421
        'SYS/sqlDebug',
        'SYS/no_pconnect',
        'SYS/setDBinit',
        'SYS/dbClientCompress',
        // #82430
        'SYS/syslogErrorReporting',
        // #82639
        'SYS/enable_DLOG',
        'SC_OPTIONS/t3lib/class.t3lib_userauth.php/writeDevLog',
        'SC_OPTIONS/t3lib/class.t3lib_userauth.php/writeDevLogBE',
        'SC_OPTIONS/t3lib/class.t3lib_userauth.php/writeDevLogFE',
        // #82438
        'SYS/enableDeprecationLog',
        // #82680
        'GFX/png_truecolor',
        // #82803
        'FE/content_doktypes',
        // #83081
        'BE/fileExtensions',
        // #83768
        'SYS/doNotCheckReferer',
        // #83878
        'SYS/isInitialInstallationInProgress',
        'SYS/isInitialDatabaseImportDone',
        // #84810
        'BE/explicitConfirmationOfTranslation',
        // #87482
        'EXT/extConf',
        // #87767
        'SYS/recursiveDomainSearch',
        // #88376
        'FE/pageNotFound_handling',
        'FE/pageNotFound_handling_statheader',
        'FE/pageNotFound_handling_accessdeniedheader',
        'FE/pageUnavailable_handling',
        'FE/pageUnavailable_handling_statheader',
        // #88458
        'FE/get_url_id_token',
        // #88500
        'BE/RTE_imageStorageDir',
        // #89645
        'SYS/systemLog',
        'SYS/systemLogLevel',
        // #91974
        'FE/IPmaskMountGroups',
        // #87301
        'SYS/cookieSecure',
        // #92940
        'BE/lockBeUserToDBmounts',
        // #92941
        'BE/enabledBeUserIPLock',
        // #94312
        'BE/loginSecurityLevel',
        'FE/loginSecurityLevel',
        // #94871
        'SYS/features/form.legacyUploadMimeTypes',
    ];

    public function __construct(ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Executed configuration upgrades. Single upgrade methods must throw a
     * ConfigurationChangedException if something was written to LocalConfiguration.
     *
     * @throws ConfigurationChangedException
     */
    public function execute()
    {
        $this->generateEncryptionKeyIfNeeded();
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
        $this->migrateCacheHashOptions();
        $this->migrateExceptionErrors();
        $this->migrateDisplayErrorsSetting();
        $this->migrateSaltedPasswordsSettings();
        $this->migrateCachingFrameworkCaches();
        $this->migrateMailSettingsToSendmail();
        $this->migrateMailSmtpEncryptSetting();
        $this->migrateExplicitADmode();

        // Should run at the end to prevent obsolete settings are removed before migration
        $this->removeObsoleteLocalConfigurationSettings();
    }

    /**
     * Some settings in LocalConfiguration vanished in DefaultConfiguration
     * and have no impact on the core anymore.
     * To keep the configuration clean, those old settings are just silently
     * removed from LocalConfiguration if set.
     *
     * @throws ConfigurationChangedException
     */
    protected function removeObsoleteLocalConfigurationSettings()
    {
        $removed = $this->configurationManager->removeLocalConfigurationKeysByPath($this->obsoleteLocalConfigurationSettings);

        // If something was changed: Trigger a reload to have new values in next request
        if ($removed) {
            $this->throwConfigurationChangedException();
        }
    }

    /**
     * The encryption key is crucial for securing form tokens
     * and the whole TYPO3 link rendering later on. A random key is set here in
     * LocalConfiguration if it does not exist yet. This might possible happen
     * during upgrading and will happen during first install.
     *
     * @throws ConfigurationChangedException
     */
    protected function generateEncryptionKeyIfNeeded()
    {
        try {
            $currentValue = $this->configurationManager->getLocalConfigurationValueByPath('SYS/encryptionKey');
        } catch (MissingArrayPathException $e) {
            // If an exception is thrown, the value is not set in LocalConfiguration
            $currentValue = '';
        }

        if (empty($currentValue)) {
            $randomKey = GeneralUtility::makeInstance(Random::class)->generateRandomHexString(96);
            $this->configurationManager->setLocalConfigurationValueByPath('SYS/encryptionKey', $randomKey);
            $this->throwConfigurationChangedException();
        }
    }

    /**
     * Parse old curl and HTTP options and set new HTTP options, related to Guzzle
     *
     * @throws ConfigurationChangedException
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
        } catch (MissingArrayPathException $e) {
            // Migration done already
        }
        try {
            $newParameters['HTTP/version'] = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/protocol_version');
            $obsoleteParameters[] = 'HTTP/protocol_version';
        } catch (MissingArrayPathException $e) {
            // Migration done already
        }
        try {
            $this->configurationManager->getLocalConfigurationValueByPath('HTTP/ssl_verify_host');
            $obsoleteParameters[] = 'HTTP/ssl_verify_host';
        } catch (MissingArrayPathException $e) {
            // Migration done already
        }
        try {
            $legacyUserAgent = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/userAgent');
            $newParameters['HTTP/headers/User-Agent'] = $legacyUserAgent;
            $obsoleteParameters[] = 'HTTP/userAgent';
        } catch (MissingArrayPathException $e) {
            // Migration done already
        }

        // Redirects
        try {
            $legacyFollowRedirects = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/follow_redirects');
            $obsoleteParameters[] = 'HTTP/follow_redirects';
        } catch (MissingArrayPathException $e) {
            $legacyFollowRedirects = '';
        }
        try {
            $legacyMaximumRedirects = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/max_redirects');
            $obsoleteParameters[] = 'HTTP/max_redirects';
        } catch (MissingArrayPathException $e) {
            $legacyMaximumRedirects = '';
        }
        try {
            $legacyStrictRedirects = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/strict_redirects');
            $obsoleteParameters[] = 'HTTP/strict_redirects';
        } catch (MissingArrayPathException $e) {
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
        } catch (MissingArrayPathException $e) {
            $legacyProxyHost = '';
        }
        try {
            $legacyProxyPort = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/proxy_port');
            $obsoleteParameters[] = 'HTTP/proxy_port';
        } catch (MissingArrayPathException $e) {
            $legacyProxyPort = '';
        }
        try {
            $legacyProxyUser = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/proxy_user');
            $obsoleteParameters[] = 'HTTP/proxy_user';
        } catch (MissingArrayPathException $e) {
            $legacyProxyUser = '';
        }
        try {
            $legacyProxyPassword = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/proxy_password');
            $obsoleteParameters[] = 'HTTP/proxy_password';
        } catch (MissingArrayPathException $e) {
            $legacyProxyPassword = '';
        }
        // Auth Scheme: Basic, digest etc.
        try {
            $legacyProxyAuthScheme = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/proxy_auth_scheme');
            $obsoleteParameters[] = 'HTTP/proxy_auth_scheme';
        } catch (MissingArrayPathException $e) {
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
        } catch (MissingArrayPathException $e) {
            $legacySslVerifyPeer = '';
        }

        // Directory holding multiple Certificate Authority files
        try {
            $legacySslCaPath = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/ssl_capath');
            $obsoleteParameters[] = 'HTTP/ssl_capath';
        } catch (MissingArrayPathException $e) {
            $legacySslCaPath = '';
        }
        // Certificate Authority file to verify the peer with (use when ssl_verify_peer is TRUE)
        try {
            $legacySslCaFile = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/ssl_cafile');
            $obsoleteParameters[] = 'HTTP/ssl_cafile';
        } catch (MissingArrayPathException $e) {
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
        } catch (MissingArrayPathException $e) {
            $legacySslLocalCert = '';
        }

        // Passphrase with which local certificate was encoded
        try {
            $legacySslPassphrase = $this->configurationManager->getLocalConfigurationValueByPath('HTTP/ssl_passphrase');
            $obsoleteParameters[] = 'HTTP/ssl_passphrase';
        } catch (MissingArrayPathException $e) {
            $legacySslPassphrase = '';
        }

        if ($legacySslLocalCert !== '') {
            if ($legacySslPassphrase !== '') {
                $newParameters['HTTP/ssl_key'] = [
                    $legacySslLocalCert,
                    $legacySslPassphrase,
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
            $this->throwConfigurationChangedException();
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
     * @throws ConfigurationChangedException
     */
    protected function disableImageMagickDetailSettingsIfImageMagickIsDisabled()
    {
        $changedValues = [];
        try {
            $currentEnabledValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/processor_enabled');
        } catch (MissingArrayPathException $e) {
            $currentEnabledValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/processor_enabled');
        }

        try {
            $currentPathValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/processor_path');
        } catch (MissingArrayPathException $e) {
            $currentPathValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/processor_path');
        }

        try {
            $currentPathLzwValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/processor_path_lzw');
        } catch (MissingArrayPathException $e) {
            $currentPathLzwValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/processor_path_lzw');
        }

        try {
            $currentImageFileExtValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/imagefile_ext');
        } catch (MissingArrayPathException $e) {
            $currentImageFileExtValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/imagefile_ext');
        }

        try {
            $currentThumbnailsValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/thumbnails');
        } catch (MissingArrayPathException $e) {
            $currentThumbnailsValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/thumbnails');
        }

        if (!$currentEnabledValue) {
            if ($currentPathValue != '') {
                $changedValues['GFX/processor_path'] = '';
            }
            if ($currentPathLzwValue != '') {
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
            $this->throwConfigurationChangedException();
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
     * @throws ConfigurationChangedException
     */
    protected function setImageMagickDetailSettings()
    {
        $changedValues = [];
        try {
            $currentProcessorValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/processor');
        } catch (MissingArrayPathException $e) {
            $currentProcessorValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/processor');
        }

        try {
            $currentProcessorMaskValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/processor_allowTemporaryMasksAsPng');
        } catch (MissingArrayPathException $e) {
            $currentProcessorMaskValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/processor_allowTemporaryMasksAsPng');
        }

        try {
            $currentProcessorEffectsValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/processor_effects');
        } catch (MissingArrayPathException $e) {
            $currentProcessorEffectsValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/processor_effects');
        }

        if ((string)$currentProcessorValue !== '') {
            if (!is_bool($currentProcessorEffectsValue)) {
                $changedValues['GFX/processor_effects'] = (int)$currentProcessorEffectsValue > 0;
            }

            if ($currentProcessorMaskValue != 0) {
                $changedValues['GFX/processor_allowTemporaryMasksAsPng'] = 0;
            }
        }
        if (!empty($changedValues)) {
            $this->configurationManager->setLocalConfigurationValuesByPathValuePairs($changedValues);
            $this->throwConfigurationChangedException();
        }
    }

    /**
     * Migrate the definition of the image processor from the configuration value
     * im_version_5 to the setting processor.
     *
     * @throws ConfigurationChangedException
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
            } catch (MissingArrayPathException $e) {
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
            $this->throwConfigurationChangedException();
        }
    }

    /**
     * Throw exception after configuration change to trigger a redirect.
     *
     * @throws ConfigurationChangedException
     */
    protected function throwConfigurationChangedException()
    {
        throw new ConfigurationChangedException(
            'Configuration updated, reload needed',
            1379024938
        );
    }

    /**
     * Migrate the configuration value thumbnails_png to a boolean value.
     *
     * @throws ConfigurationChangedException
     */
    protected function migrateThumbnailsPngSetting()
    {
        $changedValues = [];
        try {
            $currentThumbnailsPngValue = $this->configurationManager->getLocalConfigurationValueByPath('GFX/thumbnails_png');
        } catch (MissingArrayPathException $e) {
            $currentThumbnailsPngValue = $this->configurationManager->getDefaultConfigurationValueByPath('GFX/thumbnails_png');
        }

        if (is_int($currentThumbnailsPngValue) && $currentThumbnailsPngValue > 0) {
            $changedValues['GFX/thumbnails_png'] = true;
        }
        if (!empty($changedValues)) {
            $this->configurationManager->setLocalConfigurationValuesByPathValuePairs($changedValues);
            $this->throwConfigurationChangedException();
        }
    }

    /**
     * Migrate the configuration setting BE/lockSSL to boolean if set in the LocalConfiguration.php file
     *
     * @throws ConfigurationChangedException
     */
    protected function migrateLockSslSetting()
    {
        try {
            $currentOption = $this->configurationManager->getLocalConfigurationValueByPath('BE/lockSSL');
            // check if the current option is an integer/string and if it is active
            if (!is_bool($currentOption) && (int)$currentOption > 0) {
                $this->configurationManager->setLocalConfigurationValueByPath('BE/lockSSL', true);
                $this->throwConfigurationChangedException();
            }
        } catch (MissingArrayPathException $e) {
            // no change inside the LocalConfiguration.php found, so nothing needs to be modified
        }
    }

    /**
     * Move the database connection settings to a "Default" connection
     *
     * @throws ConfigurationChangedException
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
        } catch (MissingArrayPathException $e) {
            // Old setting does not exist, do nothing
        }

        try {
            $value = $confManager->getLocalConfigurationValueByPath('DB/password');
            $removeSettings[] = 'DB/password';
            $newSettings['DB/Connections/Default/password'] = $value;
        } catch (MissingArrayPathException $e) {
            // Old setting does not exist, do nothing
        }

        try {
            $value = $confManager->getLocalConfigurationValueByPath('DB/host');
            $removeSettings[] = 'DB/host';
            $newSettings['DB/Connections/Default/host'] = $value;
        } catch (MissingArrayPathException $e) {
            // Old setting does not exist, do nothing
        }

        try {
            $value = $confManager->getLocalConfigurationValueByPath('DB/port');
            $removeSettings[] = 'DB/port';
            $newSettings['DB/Connections/Default/port'] = $value;
        } catch (MissingArrayPathException $e) {
            // Old setting does not exist, do nothing
        }

        try {
            $value = $confManager->getLocalConfigurationValueByPath('DB/socket');
            $removeSettings[] = 'DB/socket';
            // Remove empty socket connects
            if (!empty($value)) {
                $newSettings['DB/Connections/Default/unix_socket'] = $value;
            }
        } catch (MissingArrayPathException $e) {
            // Old setting does not exist, do nothing
        }

        try {
            $value = $confManager->getLocalConfigurationValueByPath('DB/database');
            $removeSettings[] = 'DB/database';
            $newSettings['DB/Connections/Default/dbname'] = $value;
        } catch (MissingArrayPathException $e) {
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
        } catch (MissingArrayPathException $e) {
            // Old setting does not exist, do nothing
        }

        try {
            $value = (bool)$confManager->getLocalConfigurationValueByPath('SYS/no_pconnect');
            $removeSettings[] = 'SYS/no_pconnect';
            if (!$value) {
                $newSettings['DB/Connections/Default/persistentConnection'] = true;
            }
        } catch (MissingArrayPathException $e) {
            // Old setting does not exist, do nothing
        }

        try {
            $value = $confManager->getLocalConfigurationValueByPath('SYS/setDBinit');
            $removeSettings[] = 'SYS/setDBinit';
            $newSettings['DB/Connections/Default/initCommands'] = $value;
        } catch (MissingArrayPathException $e) {
            // Old setting does not exist, do nothing
        }

        try {
            $confManager->getLocalConfigurationValueByPath('DB/Connections/Default/charset');
        } catch (MissingArrayPathException $e) {
            // If there is no charset option yet, add it.
            $newSettings['DB/Connections/Default/charset'] = 'utf8';
        }

        try {
            $confManager->getLocalConfigurationValueByPath('DB/Connections/Default/driver');
        } catch (MissingArrayPathException $e) {
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
            $this->throwConfigurationChangedException();
        }
    }

    /**
     * Migrate the configuration setting DB/Connections/Default/charset to 'utf8' as
     * 'utf-8' is not supported by all MySQL versions.
     *
     * @throws ConfigurationChangedException
     */
    protected function migrateDatabaseConnectionCharset()
    {
        $confManager = $this->configurationManager;
        try {
            $driver = $confManager->getLocalConfigurationValueByPath('DB/Connections/Default/driver');
            $charset = $confManager->getLocalConfigurationValueByPath('DB/Connections/Default/charset');
            if (in_array($driver, ['mysqli', 'pdo_mysql', 'drizzle_pdo_mysql'], true) && $charset === 'utf-8') {
                $confManager->setLocalConfigurationValueByPath('DB/Connections/Default/charset', 'utf8');
                $this->throwConfigurationChangedException();
            }
        } catch (MissingArrayPathException $e) {
            // no incompatible charset configuration found, so nothing needs to be modified
        }
    }

    /**
     * Migrate the configuration setting DB/Connections/Default/driverOptions to array type.
     *
     * @throws ConfigurationChangedException
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
                $this->throwConfigurationChangedException();
            }
        } catch (MissingArrayPathException $e) {
            // no driver options found, nothing needs to be modified
        }
    }

    /**
     * Migrate the configuration setting BE/lang/debug if set in the LocalConfiguration.php file
     *
     * @throws ConfigurationChangedException
     */
    protected function migrateLangDebug()
    {
        $confManager = $this->configurationManager;
        try {
            $currentOption = $confManager->getLocalConfigurationValueByPath('BE/lang/debug');
            // check if the current option is set and boolean
            if (isset($currentOption) && is_bool($currentOption)) {
                $confManager->setLocalConfigurationValueByPath('BE/languageDebug', $currentOption);
                $this->throwConfigurationChangedException();
            }
        } catch (MissingArrayPathException $e) {
            // no change inside the LocalConfiguration.php found, so nothing needs to be modified
        }
    }

    /**
     * Migrate single cache hash related options under "FE" into "FE/cacheHash"
     *
     * @throws ConfigurationChangedException
     */
    protected function migrateCacheHashOptions()
    {
        $confManager = $this->configurationManager;
        $removeSettings = [];
        $newSettings = [];

        try {
            $value = $confManager->getLocalConfigurationValueByPath('FE/cHashOnlyForParameters');
            $removeSettings[] = 'FE/cHashOnlyForParameters';
            $newSettings['FE/cacheHash/cachedParametersWhiteList'] = GeneralUtility::trimExplode(',', $value, true);
        } catch (MissingArrayPathException $e) {
            // Migration done already
        }

        try {
            $value = $confManager->getLocalConfigurationValueByPath('FE/cHashExcludedParameters');
            $removeSettings[] = 'FE/cHashExcludedParameters';
            $newSettings['FE/cacheHash/excludedParameters'] = GeneralUtility::trimExplode(',', $value, true);
        } catch (MissingArrayPathException $e) {
            // Migration done already
        }

        try {
            $value = $confManager->getLocalConfigurationValueByPath('FE/cHashRequiredParameters');
            $removeSettings[] = 'FE/cHashRequiredParameters';
            $newSettings['FE/cacheHash/requireCacheHashPresenceParameters'] = GeneralUtility::trimExplode(',', $value, true);
        } catch (MissingArrayPathException $e) {
            // Migration done already
        }

        try {
            $value = $confManager->getLocalConfigurationValueByPath('FE/cHashExcludedParametersIfEmpty');
            $removeSettings[] = 'FE/cHashExcludedParametersIfEmpty';
            if (trim($value) === '*') {
                $newSettings['FE/cacheHash/excludeAllEmptyParameters'] = true;
            } else {
                $newSettings['FE/cacheHash/excludedParametersIfEmpty'] = GeneralUtility::trimExplode(',', $value, true);
            }
        } catch (MissingArrayPathException $e) {
            // Migration done already
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
            $this->throwConfigurationChangedException();
        }
    }

    /**
     * Migrate SYS/exceptionalErrors to not contain E_USER_DEPRECATED
     *
     * @throws ConfigurationChangedException
     */
    protected function migrateExceptionErrors()
    {
        $confManager = $this->configurationManager;
        try {
            $currentOption = (int)$confManager->getLocalConfigurationValueByPath('SYS/exceptionalErrors');
            // make sure E_USER_DEPRECATED is not part of the exceptionalErrors
            if ($currentOption & E_USER_DEPRECATED) {
                $confManager->setLocalConfigurationValueByPath('SYS/exceptionalErrors', $currentOption & ~E_USER_DEPRECATED);
                $this->throwConfigurationChangedException();
            }
        } catch (MissingArrayPathException $e) {
            // no change inside the LocalConfiguration.php found, so nothing needs to be modified
        }
    }

    /**
     * Migrate SYS/displayErrors to not contain 2
     *
     * @throws ConfigurationChangedException
     */
    protected function migrateDisplayErrorsSetting()
    {
        $confManager = $this->configurationManager;
        try {
            $currentOption = (int)$confManager->getLocalConfigurationValueByPath('SYS/displayErrors');
            // make sure displayErrors is set to 2
            if ($currentOption === 2) {
                $confManager->setLocalConfigurationValueByPath('SYS/displayErrors', -1);
                $this->throwConfigurationChangedException();
            }
        } catch (MissingArrayPathException $e) {
            // no change inside the LocalConfiguration.php found, so nothing needs to be modified
        }
    }

    /**
     * Migrate salted passwords extension configuration settings to BE/passwordHashing and FE/passwordHashing
     *
     * @throws ConfigurationChangedException
     */
    protected function migrateSaltedPasswordsSettings()
    {
        $confManager = $this->configurationManager;
        $configsToRemove = [];
        try {
            $extensionConfiguration = (array)$confManager->getLocalConfigurationValueByPath('EXTENSIONS/saltedpasswords');
            $configsToRemove[] = 'EXTENSIONS/saltedpasswords';
        } catch (MissingArrayPathException $e) {
            $extensionConfiguration = [];
        }
        // Migration already done
        if (empty($extensionConfiguration)) {
            return;
        }
        // Upgrade to best available hash method. This is only done once since that code will no longer be reached
        // after first migration because extConf and EXTENSIONS array entries are gone then. Thus, a manual selection
        // to some different hash mechanism will not be touched again after first upgrade.
        // Phpass is always available, so we have some last fallback if the others don't kick in
        $okHashMethods = [
            Argon2iPasswordHash::class,
            Argon2idPasswordHash::class,
            BcryptPasswordHash::class,
            Pbkdf2PasswordHash::class,
            PhpassPasswordHash::class,
        ];
        $newMethods = [];
        foreach (['BE', 'FE'] as $mode) {
            foreach ($okHashMethods as $className) {
                /** @var PasswordHashInterface $instance */
                $instance = GeneralUtility::makeInstance($className);
                if ($instance->isAvailable()) {
                    $newMethods[$mode] = $className;
                    break;
                }
            }
        }
        // We only need to write to LocalConfiguration if method is different than Argon2i from DefaultConfiguration
        $newConfig = [];
        if ($newMethods['BE'] !== Argon2iPasswordHash::class) {
            $newConfig['BE/passwordHashing/className'] = $newMethods['BE'];
        }
        if ($newMethods['FE'] !== Argon2iPasswordHash::class) {
            $newConfig['FE/passwordHashing/className'] = $newMethods['FE'];
        }
        if (!empty($newConfig)) {
            $confManager->setLocalConfigurationValuesByPathValuePairs($newConfig);
        }
        $confManager->removeLocalConfigurationKeysByPath($configsToRemove);
        $this->throwConfigurationChangedException();
    }

    /**
     * Renames all SYS[caching][cache] configuration names to names without the prefix "cache_".
     * see #88366
     */
    protected function migrateCachingFrameworkCaches()
    {
        $confManager = $this->configurationManager;
        try {
            $cacheConfigurations = (array)$confManager->getLocalConfigurationValueByPath('SYS/caching/cacheConfigurations');
            $newConfig = [];
            $hasBeenModified = false;
            foreach ($cacheConfigurations as $identifier => $cacheConfiguration) {
                if (strpos($identifier, 'cache_') === 0) {
                    $identifier = substr($identifier, 6);
                    $hasBeenModified = true;
                }
                $newConfig[$identifier] = $cacheConfiguration;
            }

            if ($hasBeenModified) {
                $confManager->setLocalConfigurationValueByPath('SYS/caching/cacheConfigurations', $newConfig);
                $this->throwConfigurationChangedException();
            }
        } catch (MissingArrayPathException $e) {
            // no change inside the LocalConfiguration.php found, so nothing needs to be modified
        }
    }

    /**
     * Migrates "mail" to "sendmail" as "mail" (PHP's built-in mail() method) is not supported anymore
     * with Symfony components.
     * See #88643
     */
    protected function migrateMailSettingsToSendmail()
    {
        $confManager = $this->configurationManager;
        try {
            $transport = $confManager->getLocalConfigurationValueByPath('MAIL/transport');
            if ($transport === 'mail') {
                $confManager->setLocalConfigurationValueByPath('MAIL/transport', 'sendmail');
                $confManager->setLocalConfigurationValueByPath('MAIL/transport_sendmail_command', (string)@ini_get('sendmail_path'));
                $this->throwConfigurationChangedException();
            }
        } catch (MissingArrayPathException $e) {
            // no change inside the LocalConfiguration.php found, so nothing needs to be modified
        }
    }

    /**
     * Migrates MAIL/transport_smtp_encrypt to a boolean value
     * See #91070, #90295, #88643 and https://github.com/symfony/symfony/commit/5b8c4676d059
     */
    protected function migrateMailSmtpEncryptSetting()
    {
        $confManager = $this->configurationManager;
        try {
            $transport = $confManager->getLocalConfigurationValueByPath('MAIL/transport');
            if ($transport === 'smtp') {
                $encrypt = $confManager->getLocalConfigurationValueByPath('MAIL/transport_smtp_encrypt');
                if (is_string($encrypt)) {
                    // SwiftMailer used 'tls' as identifier to connect with STARTTLS via SMTP (as usually used with port 587).
                    // See https://github.com/swiftmailer/swiftmailer/blob/v5.4.10/lib/classes/Swift/Transport/EsmtpTransport.php#L144
                    if ($encrypt === 'tls') {
                        // With TYPO3 v10 the MAIL/transport_smtp_encrypt option is passed as constructor parameter $tls to
                        // Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport
                        // $tls = true instructs to start a SMTPS connection – that means SSL/TLS via SMTPS, not STARTTLS via SMTP.
                        // That means symfony/mailer will use STARTTLS when $tls = false or ($tls = null with port != 465) is passed.
                        // Actually symfony/mailer will use STARTTLS by default now.
                        // Due to the misleading name (transport_smtp_encrypt) we avoid to set the option to false, but rather remove it.
                        // Note: symfony/mailer provides no way to enforce STARTTLS usage, see https://github.com/symfony/symfony/commit/5b8c4676d059
                        $confManager->removeLocalConfigurationKeysByPath(['MAIL/transport_smtp_encrypt']);
                    } elseif ($encrypt === '') {
                        $confManager->setLocalConfigurationValueByPath('MAIL/transport_smtp_encrypt', false);
                    } else {
                        $confManager->setLocalConfigurationValueByPath('MAIL/transport_smtp_encrypt', true);
                    }
                    $this->throwConfigurationChangedException();
                }
            }
        } catch (MissingArrayPathException $e) {
            // no change inside the LocalConfiguration.php found, so nothing needs to be modified
        }
    }

    /**
     * The default in DefaultConfiguration for BE/explicitADmode changed from explicitDeny to
     * explicitAllow. This upgrade checks if there is any value in LocalConfiguration yet, and
     * sets it to explicitDeny if not, to stay b/w compatible for affected instances that used
     * the old default from DefaultConfiguration before.
     * @see #94721
     */
    protected function migrateExplicitADmode(): void
    {
        $confManager = $this->configurationManager;
        try {
            // If set in LocalConfiguration, just keep it:
            // If set to 'explicitAllow', which is what we want and prefer, it is in line with DefaultConfiguration,
            // but we still do not remove it, since a second call to the silent upgrade would then write 'explicitDeny'.
            // If set to 'explicitDeny', we also simply leave it as it, since this may have been written explicitly
            // already by a previous silent upgrade run.
            // If set to something else, we don't care about this here.
            $confManager->getLocalConfigurationValueByPath('BE/explicitADmode');
        } catch (MissingArrayPathException $e) {
            // No explicit setting in LocalConfiguration, yet. This means the system is currently being upgraded - a
            // "new" instance would have been set to 'explicitAllow' via FactoryConfiguration already. So we're catching
            // instances here that are being upgraded from a previous core version where explicitADmode has never been set
            // in LocalConfiguration, which then fell back to 'explicitDeny' with old cores due to the default in
            // DefaultConfiguration. This default has been changed to 'explicitAllow' with core v11.4, so we now need to
            // set this to 'explicitDeny' for those upgrading instances to keep their behavior.
            $confManager->setLocalConfigurationValueByPath('BE/explicitADmode', 'explicitDeny');
            $this->throwConfigurationChangedException();
        }
    }
}
