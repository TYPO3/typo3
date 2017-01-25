<?php
namespace TYPO3\CMS\Install\SystemEnvironment;

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

use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Status;

/**
 * Check system environment status
 *
 * This class is a hardcoded requirement check of the underlying
 * server and PHP system.
 *
 * The class *must not* check for any TYPO3 specific things like
 * specific configuration values or directories. It should not fail
 * if there is no TYPO3 at all.
 *
 * The only core code used is the class loader
 *
 * This class is instantiated as the *very first* class during
 * installation. It is meant to be *standalone* und must not have
 * any requirements, except the status classes. It must be possible
 * to run this script separated from the rest of the core, without
 * dependencies.
 *
 * This means especially:
 * * No hooks or anything like that
 * * No usage of *any* TYPO3 code like GeneralUtility
 * * No require of anything but the status classes
 * * No localization
 *
 * The status messages and title *must not* include HTML, use plain
 * text only. The return values of this class are not bound to HTML
 * and can be used in different scopes (eg. as json array).
 */
class Check
{
    /**
     * @var array List of required PHP extensions
     */
    protected $requiredPhpExtensions = [
        'filter',
        'gd',
        'hash',
        'json',
        'mysqli',
        'openssl',
        'session',
        'soap',
        'SPL',
        'standard',
        'xml',
        'zip',
        'zlib',
    ];

    /**
     * Get all status information as array with status objects
     *
     * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
     */
    public function getStatus()
    {
        $statusArray = [];
        $statusArray[] = $this->checkCurrentDirectoryIsInIncludePath();
        $statusArray[] = $this->checkTrustedHostPattern();
        $statusArray[] = $this->checkFileUploadEnabled();
        $statusArray[] = $this->checkPostUploadSizeIsHigherOrEqualMaximumFileUploadSize();
        $statusArray[] = $this->checkMemorySettings();
        $statusArray[] = $this->checkPhpVersion();
        $statusArray[] = $this->checkMaxExecutionTime();
        $statusArray[] = $this->checkDisableFunctions();
        $statusArray[] = $this->checkDownloadsPossible();
        $statusArray[] = $this->checkMysqliReconnectSetting();
        $statusArray[] = $this->checkAlwaysPopulateRawPostDataSetting();
        $statusArray[] = $this->checkDocRoot();
        $statusArray[] = $this->checkOpenBaseDir();
        $statusArray[] = $this->checkXdebugMaxNestingLevel();
        $statusArray[] = $this->checkOpenSslInstalled();
        if ($this->isSuhosinLoadedAndActive()) {
            $statusArray[] = $this->getSuhosinLoadedStatus();
            $statusArray[] = $this->checkSuhosinRequestMaxVars();
            $statusArray[] = $this->checkSuhosinRequestMaxVarnameLength();
            $statusArray[] = $this->checkSuhosinPostMaxNameLength();
            $statusArray[] = $this->checkSuhosinPostMaxVars();
            $statusArray[] = $this->checkSuhosinGetMaxNameLength();
            $statusArray[] = $this->checkSuhosinGetMaxValueLength();
            $statusArray[] = $this->checkSuhosinExecutorIncludeWhiteListContainsPhar();
            $statusArray[] = $this->checkSuhosinExecutorIncludeWhiteListContainsVfs();
        }
        $statusArray[] = $this->checkMaxInputVars();
        $statusArray[] = $this->checkSomePhpOpcodeCacheIsLoaded();
        $statusArray[] = $this->checkReflectionDocComment();
        $statusArray[] = $this->checkSystemLocale();
        $statusArray[] = $this->checkLocaleWithUTF8filesystem();
        $statusArray[] = $this->checkWindowsApacheThreadStackSize();
        foreach ($this->requiredPhpExtensions as $extension) {
            $statusArray[] = $this->checkRequiredPhpExtension($extension);
        }
        $statusArray[] = $this->checkPcreVersion();
        $statusArray[] = $this->checkGdLibTrueColorSupport();
        $statusArray[] = $this->checkGdLibGifSupport();
        $statusArray[] = $this->checkGdLibJpgSupport();
        $statusArray[] = $this->checkGdLibPngSupport();
        $statusArray[] = $this->checkGdLibFreeTypeSupport();
        $statusArray[] = $this->checkRegisterGlobals();
        $statusArray[] = $this->checkLibXmlBug();
        $statusArray[] = $this->isTrueTypeFontWorking();
        return $statusArray;
    }

    /**
     * Checks if current directory (.) is in PHP include path
     *
     * @return Status\StatusInterface
     */
    protected function checkCurrentDirectoryIsInIncludePath()
    {
        $includePath = ini_get('include_path');
        $delimiter = $this->isWindowsOs() ? ';' : ':';
        $pathArray = $this->trimExplode($delimiter, $includePath);
        if (!in_array('.', $pathArray)) {
            $status = new Status\WarningStatus();
            $status->setTitle('Current directory (./) is not within PHP include path');
            $status->setMessage(
                'include_path = ' . implode(' ', $pathArray) . LF .
                'Normally the current path \'.\' is included in the' .
                ' include_path of PHP. Although TYPO3 does not rely on this,' .
                ' it is an unusual setting that may introduce problems for' .
                ' some extensions.'
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('Current directory (./) is within PHP include path.');
        }
        return $status;
    }

    /**
     * Checks the status of the trusted hosts pattern check
     *
     * @return Status\StatusInterface
     */
    protected function checkTrustedHostPattern()
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] === GeneralUtility::ENV_TRUSTED_HOSTS_PATTERN_ALLOW_ALL) {
            $status = new Status\WarningStatus();
            $status->setTitle('Trusted hosts pattern is insecure');
            $status->setMessage('Trusted hosts pattern is configured to allow all header values. Check the pattern defined in Install Tool -> All configuration -> System -> trustedHostsPattern and adapt it to expected host value(s).');
        } else {
            if (GeneralUtility::hostHeaderValueMatchesTrustedHostsPattern($_SERVER['HTTP_HOST'])) {
                $status = new Status\OkStatus();
                $status->setTitle('Trusted hosts pattern is configured to allow current host value.');
            } else {
                $status = new Status\ErrorStatus();
                $status->setTitle('Trusted hosts pattern mismatch');
                $status->setMessage('The trusted hosts pattern will be configured to allow all header values. This is because your $SERVER_NAME is "' . htmlspecialchars($_SERVER['SERVER_NAME']) . '" while your HTTP_HOST is "' . htmlspecialchars($_SERVER['HTTP_HOST']) . '". Check the pattern defined in Install Tool -> All configuration -> System -> trustedHostsPattern and adapt it to expected host value(s).');
            }
        }

        return $status;
    }

    /**
     * Check if file uploads are enabled in PHP
     *
     * @return Status\StatusInterface
     */
    protected function checkFileUploadEnabled()
    {
        if (!ini_get('file_uploads')) {
            $status = new Status\ErrorStatus();
            $status->setTitle('File uploads not allowed in PHP');
            $status->setMessage(
                'file_uploads=' . ini_get('file_uploads') . LF .
                'TYPO3 uses the ability to upload files from the browser in various cases.' .
                ' If this flag is disabled in PHP, you won\'t be able to upload files.' .
                ' But it doesn\'t end here, because not only are files not accepted by' .
                ' the server - ALL content in the forms are discarded and therefore' .
                ' nothing at all will be editable if you don\'t set this flag!'
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('File uploads allowed in PHP');
        }
        return $status;
    }

    /**
     * Check maximum post upload size correlates with maximum file upload
     *
     * @return Status\StatusInterface
     */
    protected function checkPostUploadSizeIsHigherOrEqualMaximumFileUploadSize()
    {
        $maximumUploadFilesize = $this->getBytesFromSizeMeasurement(ini_get('upload_max_filesize'));
        $maximumPostSize = $this->getBytesFromSizeMeasurement(ini_get('post_max_size'));
        if ($maximumPostSize > 0 && $maximumPostSize < $maximumUploadFilesize) {
            $status = new Status\ErrorStatus();
            $status->setTitle('Maximum size for POST requests is smaller than maximum upload filesize in PHP');
            $status->setMessage(
                'upload_max_filesize=' . ini_get('upload_max_filesize') . LF .
                'post_max_size=' . ini_get('post_max_size') . LF .
                'You have defined a maximum size for file uploads in PHP which' .
                ' exceeds the allowed size for POST requests. Therefore the' .
                ' file uploads can also not be larger than ' . ini_get('post_max_size') . '.'
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('Maximum post upload size correlates with maximum upload file size in PHP');
            $status->setMessage('The maximum size for file uploads is actually set to ' . ini_get('post_max_size'));
        }
        return $status;
    }

    /**
     * Check memory settings
     *
     * @return Status\StatusInterface
     */
    protected function checkMemorySettings()
    {
        $minimumMemoryLimit = 64;
        $recommendedMemoryLimit = 128;
        $memoryLimit = $this->getBytesFromSizeMeasurement(ini_get('memory_limit'));
        if ($memoryLimit <= 0) {
            $status = new Status\WarningStatus();
            $status->setTitle('Unlimited memory limit for PHP');
            $status->setMessage(
                'PHP is configured not to limit memory usage at all. This is a risk' .
                ' and should be avoided in production setup. In general it\'s best practice to limit this.' .
                ' To be safe, set a limit in PHP, but with a minimum of ' . $recommendedMemoryLimit . 'MB:' . LF .
                'memory_limit=' . $recommendedMemoryLimit . 'M'
            );
        } elseif ($memoryLimit < 1024 * 1024 * $minimumMemoryLimit) {
            $status = new Status\ErrorStatus();
            $status->setTitle('PHP Memory limit below ' . $minimumMemoryLimit . 'MB');
            $status->setMessage(
                'memory_limit=' . ini_get('memory_limit') . LF .
                'Your system is configured to enforce a memory limit for PHP scripts lower than ' .
                $minimumMemoryLimit . 'MB. It is required to raise the limit.' .
                ' We recommend a minimum PHP memory limit of ' . $recommendedMemoryLimit . 'MB:' . LF .
                'memory_limit=' . $recommendedMemoryLimit . 'M'
            );
        } elseif ($memoryLimit < 1024 * 1024 * $recommendedMemoryLimit) {
            $status = new Status\WarningStatus();
            $status->setTitle('PHP Memory limit below ' . $recommendedMemoryLimit . 'MB');
            $status->setMessage(
                'memory_limit=' . ini_get('memory_limit') . LF .
                'Your system is configured to enforce a memory limit for PHP scripts lower than ' .
                $recommendedMemoryLimit . 'MB.' .
                ' A slim TYPO3 instance without many extensions will probably work, but you should monitor your' .
                ' system for "allowed memory size of X bytes exhausted" messages, especially if using the backend.' .
                ' To be on the safe side,' . ' we recommend a minimum PHP memory limit of ' .
                $recommendedMemoryLimit . 'MB:' . LF .
                'memory_limit=' . $recommendedMemoryLimit . 'M'
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('PHP Memory limit is equal to or more than ' . $recommendedMemoryLimit . 'MB');
        }
        return $status;
    }

    /**
     * Check minimum PHP version
     *
     * @return Status\StatusInterface
     */
    protected function checkPhpVersion()
    {
        $minimumPhpVersion = '5.5.0';
        $currentPhpVersion = phpversion();
        if (version_compare($currentPhpVersion, $minimumPhpVersion) < 0) {
            $status = new Status\ErrorStatus();
            $status->setTitle('PHP version too low');
            $status->setMessage(
                'Your PHP version ' . $currentPhpVersion . ' is too old. TYPO3 CMS does not run' .
                ' with this version. Update to at least PHP ' . $minimumPhpVersion
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('PHP version is fine');
        }
        return $status;
    }

    /**
     * Check PRCE module is loaded and minimum version
     *
     * @return Status\StatusInterface
     */
    protected function checkPcreVersion()
    {
        $minimumPcreVersion = '8.30';
        if (!extension_loaded('pcre')) {
            $status = new Status\ErrorStatus();
            $status->setTitle('PHP extension pcre not loaded');
            $status->setMessage(
                'TYPO3 CMS uses PHP extension pcre but it is not loaded' .
                ' in your environment. Change your environment to provide this extension' .
                ' in with minimum version ' . $minimumPcreVersion . '.'
            );
        } else {
            $installedPcreVersionString = trim(PCRE_VERSION); // '8.31 2012-07-06'
            $mainPcreVersionString = explode(' ', $installedPcreVersionString);
            $mainPcreVersionString = $mainPcreVersionString[0]; // '8.31'
            if (version_compare($mainPcreVersionString, $minimumPcreVersion) < 0) {
                $status = new Status\ErrorStatus();
                $status->setTitle('PCRE version too low');
                $status->setMessage(
                    'Your PCRE version ' . PCRE_VERSION . ' is too old. TYPO3 CMS may trigger PHP segmentantion' .
                    ' faults with this version. Update to at least PCRE ' . $minimumPcreVersion
                );
            } else {
                $status = new Status\OkStatus();
                $status->setTitle('PHP extension PCRE is loaded and version is fine');
            }
        }
        return $status;
    }

    /**
     * Check maximum execution time
     *
     * @return Status\StatusInterface
     */
    protected function checkMaxExecutionTime()
    {
        $minimumMaximumExecutionTime = 30;
        $recommendedMaximumExecutionTime = 240;
        $currentMaximumExecutionTime = ini_get('max_execution_time');
        if ($currentMaximumExecutionTime == 0) {
            $status = new Status\WarningStatus();
            $status->setTitle('Infinite PHP script execution time');
            $status->setMessage(
                'max_execution_time=0' . LF .
                'While TYPO3 is fine with this, you risk a denial-of-service for your system if for whatever' .
                ' reason some script hangs in an infinite loop. You are usually on the safe side ' .
                ' if it is reduced to ' . $recommendedMaximumExecutionTime . ' seconds:' . LF .
                'max_execution_time=' . $recommendedMaximumExecutionTime
            );
        } elseif ($currentMaximumExecutionTime < $minimumMaximumExecutionTime) {
            $status = new Status\ErrorStatus();
            $status->setTitle('Low PHP script execution time');
            $status->setMessage(
                'max_execution_time=' . $currentMaximumExecutionTime . LF .
                'Your max_execution_time is too low. Some expensive operations in TYPO3 can take longer than that.' .
                ' It is recommended to raise the limit to ' . $recommendedMaximumExecutionTime . ' seconds:' . LF .
                'max_execution_time=' . $recommendedMaximumExecutionTime
            );
        } elseif ($currentMaximumExecutionTime < $recommendedMaximumExecutionTime) {
            $status = new Status\WarningStatus();
            $status->setTitle('Low PHP script execution time');
            $status->setMessage(
                'max_execution_time=' . $currentMaximumExecutionTime . LF .
                'Your max_execution_time is low. While TYPO3 often runs without problems' .
                ' with ' . $minimumMaximumExecutionTime . ' seconds,' .
                ' it may still happen that script execution is stopped before finishing' .
                ' calculations. You should monitor the system for messages in this area' .
                ' and maybe raise the limit to ' . $recommendedMaximumExecutionTime . ' seconds:' . LF .
                'max_execution_time=' . $recommendedMaximumExecutionTime
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('Maximum PHP script execution time is equal to or more than '
                . $recommendedMaximumExecutionTime);
        }
        return $status;
    }

    /**
     * Check for disabled functions
     *
     * @return Status\StatusInterface
     */
    protected function checkDisableFunctions()
    {
        $disabledFunctions = trim(ini_get('disable_functions'));

        // Filter "disable_functions"
        $disabledFunctionsArray = $this->trimExplode(',', $disabledFunctions);

        // Array with strings to find
        $findStrings = [
            // Disabled by default on Ubuntu OS but this is okay since the Core does not use them
            'pcntl_',
        ];
        foreach ($disabledFunctionsArray as $key => $disabledFunction) {
            foreach ($findStrings as $findString) {
                if (strpos($disabledFunction, $findString) !== false) {
                    unset($disabledFunctionsArray[$key]);
                }
            }
        }

        if ($disabledFunctions !== '') {
            if (!empty($disabledFunctionsArray)) {
                $status = new Status\ErrorStatus();
                $status->setTitle('Some PHP functions disabled');
                $status->setMessage(
                    'disable_functions=' . implode(' ', explode(',', $disabledFunctions)) . LF .
                    'These function(s) are disabled. TYPO3 uses some of those, so there might be trouble.' .
                    ' TYPO3 is designed to use the default set of PHP functions plus some common extensions.' .
                    ' Possibly these functions are disabled' .
                    ' due to security considerations and most likely the list would include a function like' .
                    ' exec() which is used by TYPO3 at various places. Depending on which exact functions' .
                    ' are disabled, some parts of the system may just break without further notice.'
                );
            } else {
                $status = new Status\NoticeStatus();
                $status->setTitle('Some PHP functions currently disabled but OK');
                $status->setMessage(
                    'disable_functions=' . implode(' ', explode(',', $disabledFunctions)) . LF .
                    'These function(s) are disabled. TYPO3 uses currently none of those, so you are good to go.'
                );
            }
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('No disabled PHP functions');
        }
        return $status;
    }

    /**
     * Check if it is possible to download external data (e.g. TER)
     * Either allow_url_fopen must be enabled or curl must be used
     *
     * @return Status\OkStatus|Status\WarningStatus
     */
    protected function checkDownloadsPossible()
    {
        $allowUrlFopen = (bool)ini_get('allow_url_fopen');
        $curlEnabled = !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['curlUse']);
        if ($allowUrlFopen || $curlEnabled) {
            $status = new Status\OkStatus();
            $status->setTitle('Fetching external URLs is allowed');
        } else {
            $status = new Status\WarningStatus();
            $status->setTitle('Fetching external URLs is not allowed');
            $status->setMessage(
                'Either enable PHP runtime setting "allow_url_fopen"' . LF . 'or enable curl by setting [SYS][curlUse] accordingly.'
            );
        }
        return $status;
    }

    /**
     * Verify that mysqli.reconnect is set to 0 in order to avoid improper reconnects
     *
     * @return Status\StatusInterface
     */
    protected function checkMysqliReconnectSetting()
    {
        $currentMysqliReconnectSetting = ini_get('mysqli.reconnect');
        if ($currentMysqliReconnectSetting === '1') {
            $status = new Status\ErrorStatus();
            $status->setTitle('PHP mysqli.reconnect is enabled');
            $status->setMessage(
                'mysqli.reconnect=1' . LF .
                'PHP is configured to automatically reconnect the database connection on disconnection.' . LF .
                ' Warning: If (e.g. during a long-running task) the connection is dropped and automatically reconnected, ' .
                ' it may not be reinitialized properly (e.g. charset) and write mangled data to the database!'
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('PHP mysqli.reconnect is fine');
        }
        return $status;
    }

    /**
     * Check that always_populate_raw_post_data has been set to -1 on PHP 5.6 or newer
     *
     * @return Status\StatusInterface
     */
    protected function checkAlwaysPopulateRawPostDataSetting()
    {
        $minimumPhpVersion = '5.6.0';
        $maximumPhpVersion = '7.0.0';
        $currentPhpVersion = phpversion();
        $currentAlwaysPopulaterRawPostDataSetting = ini_get('always_populate_raw_post_data');
        if (version_compare($currentPhpVersion, $maximumPhpVersion) >= 0) {
            $status = new Status\OkStatus();
            $status->setTitle('PHP always_populate_raw_post_data is removed as of PHP 7.0 or newer');
        } elseif (version_compare($currentPhpVersion, $minimumPhpVersion) >= 0 && $currentAlwaysPopulaterRawPostDataSetting !== '-1') {
            $status = new Status\ErrorStatus();
            $status->setTitle('PHP always_populate_raw_post_data is deprecated');
            $status->setMessage(
                'always_populate_raw_post_data=' . $currentAlwaysPopulaterRawPostDataSetting . LF .
                'PHP is configured to automatically populate $HTTP_RAW_POST_DATA.' . LF .
                ' Warning: Expect fatal errors in central parts of the CMS' .
                ' if the value is not changed to:' . LF .
                'always_populate_raw_post_data=-1'
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('PHP always_populate_raw_post_data is fine');
        }
        return $status;
    }

    /**
     * Check for doc_root ini setting
     *
     * @return Status\StatusInterface
     */
    protected function checkDocRoot()
    {
        $docRootSetting = trim(ini_get('doc_root'));
        if ($docRootSetting !== '') {
            $status = new Status\NoticeStatus();
            $status->setTitle('doc_root is set');
            $status->setMessage(
                'doc_root=' . $docRootSetting . LF .
                'PHP cannot execute scripts' .
                ' outside this directory. This setting is seldom used and must correlate' .
                ' with your actual document root. You might be in trouble if your' .
                ' TYPO3 CMS core code is linked to some different location.' .
                ' If that is a problem, the setting must be changed.'
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('PHP doc_root is not set');
        }
        return $status;
    }

    /**
     * Check open_basedir
     *
     * @return Status\StatusInterface
     */
    protected function checkOpenBaseDir()
    {
        $openBaseDirSetting = trim(ini_get('open_basedir'));
        if ($openBaseDirSetting !== '') {
            $status = new Status\NoticeStatus();
            $status->setTitle('PHP open_basedir is set');
            $status->setMessage(
                'open_basedir = ' . ini_get('open_basedir') . LF .
                'This restricts TYPO3 to open and include files only in this' .
                ' path. Please make sure that this does not prevent TYPO3 from running,' .
                ' if for example your TYPO3 CMS core is linked to a different directory' .
                ' not included in this path.'
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('PHP open_basedir is off');
        }
        return $status;
    }

    /**
     * If xdebug is loaded, the default max_nesting_level of 100 must be raised
     *
     * @return Status\StatusInterface
     */
    protected function checkXdebugMaxNestingLevel()
    {
        if (extension_loaded('xdebug')) {
            $recommendedMaxNestingLevel = 400;
            $errorThreshold = 250;
            $currentMaxNestingLevel = ini_get('xdebug.max_nesting_level');
            if ($currentMaxNestingLevel < $errorThreshold) {
                $status = new Status\ErrorStatus();
                $status->setTitle('PHP xdebug.max_nesting_level is critically low');
                $status->setMessage(
                    'xdebug.max_nesting_level=' . $currentMaxNestingLevel . LF .
                    'This setting controls the maximum number of nested function calls to protect against' .
                    ' infinite recursion. The current value is too low for TYPO3 CMS and must' .
                    ' be either raised or xdebug has to be unloaded. A value of ' . $recommendedMaxNestingLevel .
                    ' is recommended. Warning: Expect fatal PHP errors in central parts of the CMS' .
                    ' if the value is not raised significantly to:' . LF .
                    'xdebug.max_nesting_level=' . $recommendedMaxNestingLevel
                );
            } elseif ($currentMaxNestingLevel < $recommendedMaxNestingLevel) {
                $status = new Status\WarningStatus();
                $status->setTitle('PHP xdebug.max_nesting_level is low');
                $status->setMessage(
                    'xdebug.max_nesting_level=' . $currentMaxNestingLevel . LF .
                    'This setting controls the maximum number of nested function calls to protect against' .
                    ' infinite recursion. The current value is high enough for the TYPO3 CMS core to work' .
                    ' fine, but still some extensions could raise fatal PHP errors if the setting is not' .
                    ' raised further. A value of ' . $recommendedMaxNestingLevel . ' is recommended.' . LF .
                    'xdebug.max_nesting_level=' . $recommendedMaxNestingLevel
                );
            } else {
                $status = new Status\OkStatus();
                $status->setTitle('PHP xdebug.max_nesting_level ok');
            }
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('PHP xdebug extension not loaded');
        }
        return $status;
    }

    /**
     * Check accessibility and functionality of OpenSSL
     *
     * @return Status\StatusInterface
     */
    protected function checkOpenSslInstalled()
    {
        if (extension_loaded('openssl')) {
            $testKey = @openssl_pkey_new();
            if (is_resource($testKey)) {
                openssl_free_key($testKey);
                $status = new Status\OkStatus();
                $status->setTitle('PHP OpenSSL extension installed properly');
            } else {
                $status = new Status\ErrorStatus();
                $status->setTitle('PHP OpenSSL extension not working');
                $status->setMessage(
                    'Something went wrong while trying to create a new private key for testing.' .
                    ' Please check the integration of the PHP OpenSSL extension and if it is installed correctly.'
                );
            }
        } else {
            $status = new Status\ErrorStatus();
            $status->setTitle('PHP OpenSSL extension not loaded');
            $status->setMessage(
                'OpenSSL is a PHP extension to encrypt/decrypt data between requests.' .
                ' TYPO3 CMS requires it to be able to encrypt stored passwords to improve the security in the' .
                ' database layer.'
            );
        }

        return $status;
    }

    /**
     * Get max_input_vars status
     *
     * @return Status\StatusInterface
     */
    protected function checkMaxInputVars()
    {
        $recommendedMaxInputVars = 1500;
        $minimumMaxInputVars = 1000;
        $currentMaxInputVars = ini_get('max_input_vars');

        if ($currentMaxInputVars < $minimumMaxInputVars) {
            $status = new Status\ErrorStatus();
            $status->setTitle('PHP max_input_vars too low');
            $status->setMessage(
                'max_input_vars=' . $currentMaxInputVars . LF .
                'This setting can lead to lost information if submitting forms with lots of data in TYPO3 CMS' .
                ' (as the install tool does). It is highly recommended to raise this' .
                ' to at least ' . $recommendedMaxInputVars . ':' . LF .
                'max_input_vars=' . $recommendedMaxInputVars
            );
        } elseif ($currentMaxInputVars < $recommendedMaxInputVars) {
            $status = new Status\WarningStatus();
            $status->setTitle('PHP max_input_vars very low');
            $status->setMessage(
                'max_input_vars=' . $currentMaxInputVars . LF .
                'This setting can lead to lost information if submitting forms with lots of data in TYPO3 CMS' .
                ' (as the install tool does). It is highly recommended to raise this' .
                ' to at least ' . $recommendedMaxInputVars . ':' . LF .
                'max_input_vars=' . $recommendedMaxInputVars
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('PHP max_input_vars ok');
        }
        return $status;
    }

    /**
     * Get suhosin loaded status
     * Should be called only if suhosin extension is loaded
     *
     * @return Status\StatusInterface
     * @throws \BadMethodCallException
     */
    protected function getSuhosinLoadedStatus()
    {
        if ($this->isSuhosinLoadedAndActive()) {
            $status = new Status\OkStatus();
            $status->setTitle('PHP suhosin extension loaded and active');
            return $status;
        } else {
            throw new \BadMethodCallException('Should be called only if suhosin extension is loaded', 1422634778);
        }
    }

    /**
     * Check suhosin.request.max_vars
     *
     * @return Status\StatusInterface
     */
    protected function checkSuhosinRequestMaxVars()
    {
        $recommendedRequestMaxVars = 400;
        if ($this->isSuhosinLoadedAndActive()) {
            $currentRequestMaxVars = ini_get('suhosin.request.max_vars');
            if ($currentRequestMaxVars < $recommendedRequestMaxVars) {
                $status = new Status\ErrorStatus();
                $status->setTitle('PHP suhosin.request.max_vars too low');
                $status->setMessage(
                    'suhosin.request.max_vars=' . $currentRequestMaxVars . LF .
                    'This setting can lead to lost information if submitting forms with lots of data in TYPO3 CMS' .
                    ' (as the install tool does). It is highly recommended to raise this' .
                    ' to at least ' . $recommendedRequestMaxVars . ':' . LF .
                    'suhosin.request.max_vars=' . $recommendedRequestMaxVars
                );
            } else {
                $status = new Status\OkStatus();
                $status->setTitle('PHP suhosin.request.max_vars ok');
            }
        } else {
            $status = new Status\InfoStatus();
            $status->setTitle('Suhosin not loaded');
            $status->setMessage(
                'If enabling suhosin, suhosin.request.max_vars' .
                ' should be set to at least ' . $recommendedRequestMaxVars . ':' . LF .
                'suhosin.request.max_vars=' . $recommendedRequestMaxVars
            );
        }
        return $status;
    }

    /**
     * Check suhosin.request.max_varname_length
     *
     * @return Status\StatusInterface
     */
    protected function checkSuhosinRequestMaxVarnameLength()
    {
        $recommendedRequestMaxVarnameLength = 200;
        if ($this->isSuhosinLoadedAndActive()) {
            $currentRequestMaxVarnameLength = ini_get('suhosin.request.max_varname_length');
            if ($currentRequestMaxVarnameLength < $recommendedRequestMaxVarnameLength) {
                $status = new Status\ErrorStatus();
                $status->setTitle('PHP suhosin.request.max_varname_length too low');
                $status->setMessage(
                    'suhosin.request.max_varname_length=' . $currentRequestMaxVarnameLength . LF .
                    'This setting can lead to lost information if submitting forms with lots of data in TYPO3 CMS' .
                    ' (as the install tool does). It is highly recommended to raise this' .
                    ' to at least ' . $recommendedRequestMaxVarnameLength . ':' . LF .
                    'suhosin.request.max_varname_length=' . $recommendedRequestMaxVarnameLength
                );
            } else {
                $status = new Status\OkStatus();
                $status->setTitle('PHP suhosin.request.max_varname_length ok');
            }
        } else {
            $status = new Status\InfoStatus();
            $status->setTitle('Suhosin not loaded');
            $status->setMessage(
                'If enabling suhosin, suhosin.request.max_varname_length' .
                ' should be set to at least ' . $recommendedRequestMaxVarnameLength . ':' . LF .
                'suhosin.request.max_varname_length=' . $recommendedRequestMaxVarnameLength
            );
        }
        return $status;
    }

    /**
     * Check suhosin.post.max_name_length
     *
     * @return Status\StatusInterface
     */
    protected function checkSuhosinPostMaxNameLength()
    {
        $recommendedPostMaxNameLength = 200;
        if ($this->isSuhosinLoadedAndActive()) {
            $currentPostMaxNameLength = ini_get('suhosin.post.max_name_length');
            if ($currentPostMaxNameLength < $recommendedPostMaxNameLength) {
                $status = new Status\ErrorStatus();
                $status->setTitle('PHP suhosin.post.max_name_length too low');
                $status->setMessage(
                    'suhosin.post.max_name_length=' . $currentPostMaxNameLength . LF .
                    'This setting can lead to lost information if submitting forms with lots of data in TYPO3 CMS' .
                    ' (as the install tool does). It is highly recommended to raise this' .
                    ' to at least ' . $recommendedPostMaxNameLength . ':' . LF .
                    'suhosin.post.max_name_length=' . $recommendedPostMaxNameLength
                );
            } else {
                $status = new Status\OkStatus();
                $status->setTitle('PHP suhosin.post.max_name_length ok');
            }
        } else {
            $status = new Status\InfoStatus();
            $status->setTitle('Suhosin not loaded');
            $status->setMessage(
                'If enabling suhosin, suhosin.post.max_name_length' .
                ' should be set to at least ' . $recommendedPostMaxNameLength . ':' . LF .
                'suhosin.post.max_name_length=' . $recommendedPostMaxNameLength
            );
        }
        return $status;
    }

    /**
     * Check suhosin.post.max_vars
     *
     * @return Status\StatusInterface
     */
    protected function checkSuhosinPostMaxVars()
    {
        $recommendedPostMaxVars = 400;
        if ($this->isSuhosinLoadedAndActive()) {
            $currentPostMaxVars = ini_get('suhosin.post.max_vars');
            if ($currentPostMaxVars < $recommendedPostMaxVars) {
                $status = new Status\ErrorStatus();
                $status->setTitle('PHP suhosin.post.max_vars too low');
                $status->setMessage(
                    'suhosin.post.max_vars=' . $currentPostMaxVars . LF .
                    'This setting can lead to lost information if submitting forms with lots of data in TYPO3 CMS' .
                    ' (as the install tool does). It is highly recommended to raise this' .
                    ' to at least ' . $recommendedPostMaxVars . ':' . LF .
                    'suhosin.post.max_vars=' . $recommendedPostMaxVars
                );
            } else {
                $status = new Status\OkStatus();
                $status->setTitle('PHP suhosin.post.max_vars ok');
            }
        } else {
            $status = new Status\InfoStatus();
            $status->setTitle('Suhosin not loaded');
            $status->setMessage(
                'If enabling suhosin, suhosin.post.max_vars' .
                ' should be set to at least ' . $recommendedPostMaxVars . ':' . LF .
                'suhosin.post.max_vars=' . $recommendedPostMaxVars
            );
        }
        return $status;
    }

    /**
     * Check suhosin.get.max_value_length
     *
     * @return Status\StatusInterface
     */
    protected function checkSuhosinGetMaxValueLength()
    {
        $recommendedGetMaxValueLength = 2000;
        if ($this->isSuhosinLoadedAndActive()) {
            $currentGetMaxValueLength = ini_get('suhosin.get.max_value_length');
            if ($currentGetMaxValueLength < $recommendedGetMaxValueLength) {
                $status = new Status\ErrorStatus();
                $status->setTitle('PHP suhosin.get.max_value_length too low');
                $status->setMessage(
                    'suhosin.get.max_value_length=' . $currentGetMaxValueLength . LF .
                    'This setting can lead to lost information if submitting forms with lots of data in TYPO3 CMS' .
                    ' (as the install tool does). It is highly recommended to raise this' .
                    ' to at least ' . $recommendedGetMaxValueLength . ':' . LF .
                    'suhosin.get.max_value_length=' . $recommendedGetMaxValueLength
                );
            } else {
                $status = new Status\OkStatus();
                $status->setTitle('PHP suhosin.get.max_value_length ok');
            }
        } else {
            $status = new Status\InfoStatus();
            $status->setTitle('Suhosin not loaded');
            $status->setMessage(
                'If enabling suhosin, suhosin.get.max_value_length' .
                ' should be set to at least ' . $recommendedGetMaxValueLength . ':' . LF .
                'suhosin.get.max_value_length=' . $recommendedGetMaxValueLength
            );
        }
        return $status;
    }

    /**
     * Check suhosin.get.max_name_length
     *
     * @return Status\StatusInterface
     */
    protected function checkSuhosinGetMaxNameLength()
    {
        $recommendedGetMaxNameLength = 200;
        if ($this->isSuhosinLoadedAndActive()) {
            $currentGetMaxNameLength = ini_get('suhosin.get.max_name_length');
            if ($currentGetMaxNameLength < $recommendedGetMaxNameLength) {
                $status = new Status\ErrorStatus();
                $status->setTitle('PHP suhosin.get.max_name_length too low');
                $status->setMessage(
                    'suhosin.get.max_name_length=' . $currentGetMaxNameLength . LF .
                    'This setting can lead to lost information if submitting forms with lots of data in TYPO3 CMS' .
                    ' (as the install tool does). It is highly recommended to raise this' .
                    ' to at least ' . $recommendedGetMaxNameLength . ':' . LF .
                    'suhosin.get.max_name_length=' . $recommendedGetMaxNameLength
                );
            } else {
                $status = new Status\OkStatus();
                $status->setTitle('PHP suhosin.get.max_name_length ok');
            }
        } else {
            $status = new Status\InfoStatus();
            $status->setTitle('Suhosin not loaded');
            $status->setMessage(
                'If enabling suhosin, suhosin.get.max_name_length' .
                ' should be set to at least ' . $recommendedGetMaxNameLength . ':' . LF .
                'suhosin.get.max_name_length=' . $recommendedGetMaxNameLength
            );
        }
        return $status;
    }

    /**
     * Check suhosin.executor.include.whitelist contains phar
     *
     * @return Status\StatusInterface
     */
    protected function checkSuhosinExecutorIncludeWhiteListContainsPhar()
    {
        if ($this->isSuhosinLoadedAndActive()) {
            $whitelist = (string)ini_get('suhosin.executor.include.whitelist');
            if (strpos($whitelist, 'phar') === false) {
                $status = new Status\NoticeStatus();
                $status->setTitle('PHP suhosin.executor.include.whitelist does not contain phar');
                $status->setMessage(
                    'suhosin.executor.include.whitelist= ' . $whitelist . LF .
                    '"phar" is currently not a hard requirement of TYPO3 CMS but is nice to have and a possible' .
                    ' requirement in future versions. A useful setting is:' . LF .
                    'suhosin.executor.include.whitelist=phar,vfs'
                );
            } else {
                $status = new Status\OkStatus();
                $status->setTitle('PHP suhosin.executor.include.whitelist contains phar');
            }
        } else {
            $status = new Status\InfoStatus();
            $status->setTitle('Suhosin not loaded');
            $status->setMessage(
                'If enabling suhosin, a useful setting is:' . LF .
                'suhosin.executor.include.whitelist=phar,vfs'
            );
        }
        return $status;
    }

    /**
     * Check suhosin.executor.include.whitelist contains vfs
     *
     * @return Status\StatusInterface
     */
    protected function checkSuhosinExecutorIncludeWhiteListContainsVfs()
    {
        if ($this->isSuhosinLoadedAndActive()) {
            $whitelist = (string)ini_get('suhosin.executor.include.whitelist');
            if (strpos($whitelist, 'vfs') === false) {
                $status = new Status\WarningStatus();
                $status->setTitle('PHP suhosin.executor.include.whitelist does not contain vfs');
                $status->setMessage(
                    'suhosin.executor.include.whitelist= ' . $whitelist . LF .
                    '"vfs" is currently not a hard requirement of TYPO3 CMS but tons of unit tests rely on it.' .
                    ' Furthermore, vfs will likely be a base for an additional compatibility layer in the future.' .
                    ' A useful setting is:' . LF .
                    'suhosin.executor.include.whitelist=phar,vfs'
                );
            } else {
                $status = new Status\OkStatus();
                $status->setTitle('PHP suhosin.executor.include.whitelist contains vfs');
            }
        } else {
            $status = new Status\InfoStatus();
            $status->setTitle('Suhosin not loaded');
            $status->setMessage(
                'If enabling suhosin, a useful setting is:' . LF .
                'suhosin.executor.include.whitelist=phar,vfs'
            );
        }
        return $status;
    }

    /**
     * Check if some opcode cache is loaded
     *
     * @return Status\StatusInterface
     */
    protected function checkSomePhpOpcodeCacheIsLoaded()
    {
        // Link to our wiki page, so we can update opcode cache issue information independent of TYPO3 CMS releases.
        $wikiLink = 'For more information take a look in our wiki ' . TYPO3_URL_WIKI_OPCODECACHE . '.';
        $opcodeCaches = GeneralUtility::makeInstance(OpcodeCacheService::class)->getAllActive();
        if (empty($opcodeCaches)) {
            // Set status to notice. It needs to be notice so email won't be triggered.
            $status = new Status\NoticeStatus();
            $status->setTitle('No PHP opcode cache loaded');
            $status->setMessage(
                'PHP opcode caches hold a compiled version of executed PHP scripts in' .
                ' memory and do not require to recompile a script each time it is accessed.' .
                ' This can be a massive performance improvement and can reduce the load on a' .
                ' server in general. A parse time reduction by factor three for fully cached' .
                ' pages can be achieved easily if using an opcode cache.' .
                LF . $wikiLink
            );
        } else {
            $status = new Status\OkStatus();
            $message = '';

            foreach ($opcodeCaches as $opcodeCache => $properties) {
                $message .= 'Name: ' . $opcodeCache . ' Version: ' . $properties['version'];
                $message .= LF;

                if ($properties['error']) {
                    // Set status to error if not already set
                    if ($status->getSeverity() !== 'error') {
                        $status = new Status\ErrorStatus();
                    }
                    $message .= ' This opcode cache is marked as malfunctioning by the TYPO3 CMS Team.';
                } elseif ($properties['canInvalidate']) {
                    $message .= ' This opcode cache should work correctly and has good performance.';
                } else {
                    // Set status to notice if not already error set. It needs to be notice so email won't be triggered.
                    if ($status->getSeverity() !== 'error' || $status->getSeverity() !== 'warning') {
                        $status = new Status\NoticeStatus();
                    }
                    $message .= ' This opcode cache may work correctly but has medium performance.';
                }
                $message .= LF;
            }

            $message .= $wikiLink;

            // Set title of status depending on serverity
            switch ($status->getSeverity()) {
                case 'error':
                    $status->setTitle('A possibly malfunctioning PHP opcode cache is loaded');
                    break;
                case 'warning':
                    $status->setTitle('A PHP opcode cache is loaded which may cause problems');
                    break;
                case 'ok':
                default:
                    $status->setTitle('A PHP opcode cache is loaded');
                    break;
            }
            $status->setMessage($message);
        }
        return $status;
    }

    /**
     * Check doc comments can be fetched by reflection
     *
     * @return Status\StatusInterface
     */
    protected function checkReflectionDocComment()
    {
        $testReflection = new \ReflectionMethod(get_class($this), __FUNCTION__);
        if ($testReflection->getDocComment() === false) {
            $status = new Status\AlertStatus();
            $status->setTitle('PHP Doc comment reflection broken');
            $status->setMessage(
                'TYPO3 CMS core extensions like extbase and fluid heavily rely on method'
                . ' comment parsing to fetch annotations and add magic belonging to them.'
                . ' This does not work in the current environment and so we cannot install'
                . ' TYPO3 CMS.' . LF
                . ' Here are some possibilities: ' . LF
                . '* In Zend OPcache you can disable saving/loading comments. If you are using'
                . ' Zend OPcache (included since PHP 5.5) then check your php.ini settings for'
                . ' opcache.save_comments and opcache.load_comments and enable them.' . LF
                . '* In Zend Optimizer+ you can disable saving comments. If you are using'
                . ' Zend Optimizer+ then check your php.ini settings for'
                . ' zend_optimizerplus.save_comments and enable it.' . LF
                . '* The PHP extension eaccelerator is known to break this if'
                . ' it is compiled without --with-eaccelerator-doc-comment-inclusion flag.'
                . ' This compile flag must be specified, otherwise TYPO3 CMS will not work.' . LF
                . 'For more information take a look in our wiki ' . TYPO3_URL_WIKI_OPCODECACHE . '.'
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('PHP Doc comment reflection works');
        }
        return $status;
    }

    /**
     * Check if systemLocale setting is correct (locale exists in the OS)
     *
     * @return Status\StatusInterface
     */
    protected function checkSystemLocale()
    {
        $currentLocale = setlocale(LC_CTYPE, 0);

        // On Windows an empty locale value uses the regional settings from the Control Panel
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale'] === '' && TYPO3_OS !== 'WIN') {
            $status = new Status\InfoStatus();
            $status->setTitle('Empty systemLocale setting');
            $status->setMessage(
                '$GLOBALS[TYPO3_CONF_VARS][SYS][systemLocale] is not set. This is fine as long as no UTF-8' .
                ' file system is used.'
            );
        } elseif (setlocale(LC_CTYPE, $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']) === false) {
            $status = new Status\ErrorStatus();
            $status->setTitle('Incorrect systemLocale setting');
            $status->setMessage(
                'Current value of the $GLOBALS[TYPO3_CONF_VARS][SYS][systemLocale] is incorrect. A locale with' .
                ' this name doesn\'t exist in the operating system.'
            );
            setlocale(LC_CTYPE, $currentLocale);
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('System locale is correct');
        }

        return $status;
    }

    /**
     * Checks whether we can use file names with UTF-8 characters.
     * Configured system locale must support UTF-8 when UTF8filesystem is set
     *
     * @return Status\StatusInterface
     */
    protected function checkLocaleWithUTF8filesystem()
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
            // On Windows an empty local value uses the regional settings from the Control Panel
            if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale'] === '' && TYPO3_OS !== 'WIN') {
                $status = new Status\ErrorStatus();
                $status->setTitle('System locale not set on UTF-8 file system');
                $status->setMessage(
                    '$GLOBALS[TYPO3_CONF_VARS][SYS][UTF8filesystem] is set, but $GLOBALS[TYPO3_CONF_VARS][SYS][systemLocale]' .
                    ' is empty. Make sure a valid locale which supports UTF-8 is set.'
                );
            } else {
                $testString = 'ÖöĄĆŻĘĆćążąęó.jpg';
                $currentLocale = setlocale(LC_CTYPE, 0);
                $quote = TYPO3_OS === 'WIN' ? '"' : '\'';

                setlocale(LC_CTYPE, $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']);

                if (escapeshellarg($testString) === $quote . $testString . $quote) {
                    $status = new Status\OkStatus();
                    $status->setTitle('File names with UTF-8 characters can be used.');
                } else {
                    $status = new Status\ErrorStatus();
                    $status->setTitle('System locale setting doesn\'t support UTF-8 file names.');
                    $status->setMessage(
                        'Please check your $GLOBALS[TYPO3_CONF_VARS][SYS][systemLocale] setting.'
                    );
                }

                setlocale(LC_CTYPE, $currentLocale);
            }
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('Skipping test, as UTF8filesystem is not enabled.');
        }

        return $status;
    }

    /**
     * Checks thread stack size if on windows with apache
     *
     * @return Status\StatusInterface
     */
    protected function checkWindowsApacheThreadStackSize()
    {
        if ($this->isWindowsOs()
            && substr($_SERVER['SERVER_SOFTWARE'], 0, 6) === 'Apache'
        ) {
            $status = new Status\WarningStatus();
            $status->setTitle('Windows apache thread stack size');
            $status->setMessage(
                'This current value cannot be checked by the system, so please ignore this warning if it' .
                ' is already taken care of: Fluid uses complex regular expressions which require a lot' .
                ' of stack space during the first processing.' .
                ' On Windows the default stack size for Apache is a lot smaller than on UNIX.' .
                ' You can increase the size to 8MB (default on UNIX) by adding the following configuration' .
                ' to httpd.conf and restarting Apache afterwards:' . LF .
                '<IfModule mpm_winnt_module>' . LF .
                'ThreadStackSize 8388608' . LF .
                '</IfModule>'
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('Apache ThreadStackSize is not an issue on UNIX systems');
        }
        return $status;
    }

    /**
     * Check if a specific required PHP extension is loaded
     *
     * @param string $extension
     * @return Status\StatusInterface
     */
    protected function checkRequiredPhpExtension($extension)
    {
        if (!extension_loaded($extension)) {
            $status = new Status\ErrorStatus();
            $status->setTitle('PHP extension ' . $extension . ' not loaded');
            $status->setMessage(
                'TYPO3 CMS uses PHP extension ' . $extension . ' but it is not loaded' .
                ' in your environment. Change your environment to provide this extension.'
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('PHP extension ' . $extension . ' loaded');
        }
        return $status;
    }

    /**
     * Check imagecreatetruecolor to verify gdlib works as expected
     *
     * @return Status\StatusInterface
     */
    protected function checkGdLibTrueColorSupport()
    {
        if (function_exists('imagecreatetruecolor')) {
            $imageResource = @imagecreatetruecolor(50, 100);
            if (is_resource($imageResource)) {
                imagedestroy($imageResource);
                $status = new Status\OkStatus();
                $status->setTitle('PHP GD library true color works');
            } else {
                $status = new Status\ErrorStatus();
                $status->setTitle('PHP GD library true color support broken');
                $status->setMessage(
                    'GD is loaded, but calling imagecreatetruecolor() fails.' .
                    ' This must be fixed, TYPO3 CMS won\'t work well otherwise.'
                );
            }
        } else {
            $status = new Status\ErrorStatus();
            $status->setTitle('PHP GD library true color support missing');
            $status->setMessage(
                'Gdlib is essential for TYPO3 CMS to work properly.'
            );
        }
        return $status;
    }

    /**
     * Check gif support of GD library
     *
     * @return Status\StatusInterface
     */
    protected function checkGdLibGifSupport()
    {
        if (function_exists('imagecreatefromgif')
            && function_exists('imagegif')
            && (imagetypes() & IMG_GIF)
        ) {
            $imageResource = @imagecreatefromgif(__DIR__ . '/../../Resources/Public/Images/TestInput/Test.gif');
            if (is_resource($imageResource)) {
                imagedestroy($imageResource);
                $status = new Status\OkStatus();
                $status->setTitle('PHP GD library has gif support');
            } else {
                $status = new Status\ErrorStatus();
                $status->setTitle('PHP GD library gif support broken');
                $status->setMessage(
                    'GD is loaded, but calling imagecreatefromgif() fails.' .
                    ' This must be fixed, TYPO3 CMS won\'t work well otherwise.'
                );
            }
        } else {
            $status = new Status\ErrorStatus();
            $status->setTitle('PHP GD library gif support missing');
            $status->setMessage(
                'GD must be compiled with gif support. This is essential for' .
                ' TYPO3 CMS to work properly.'
            );
        }
        return $status;
    }

    /**
     * Check jgp support of GD library
     *
     * @return Status\StatusInterface
     */
    protected function checkGdLibJpgSupport()
    {
        if (function_exists('imagecreatefromjpeg')
            && function_exists('imagejpeg')
            && (imagetypes() & IMG_JPG)
        ) {
            $status = new Status\OkStatus();
            $status->setTitle('PHP GD library has jpg support');
        } else {
            $status = new Status\ErrorStatus();
            $status->setTitle('PHP GD library jpg support missing');
            $status->setMessage(
                'GD must be compiled with jpg support. This is essential for' .
                ' TYPO3 CMS to work properly.'
            );
        }
        return $status;
    }

    /**
     * Check png support of GD library
     *
     * @return Status\StatusInterface
     */
    protected function checkGdLibPngSupport()
    {
        if (function_exists('imagecreatefrompng')
            && function_exists('imagepng')
            && (imagetypes() & IMG_PNG)
        ) {
            $imageResource = @imagecreatefrompng(__DIR__ . '/../../Resources/Public/Images/TestInput/Test.png');
            if (is_resource($imageResource)) {
                imagedestroy($imageResource);
                $status = new Status\OkStatus();
                $status->setTitle('PHP GD library has png support');
            } else {
                $status = new Status\ErrorStatus();
                $status->setTitle('PHP GD library png support broken');
                $status->setMessage(
                    'GD is compiled with png support, but calling imagecreatefrompng() fails.' .
                    ' Check your environment and fix it, png in GD lib is important' .
                    ' for TYPO3 CMS to work properly.'
                );
            }
        } else {
            $status = new Status\ErrorStatus();
            $status->setTitle('PHP GD library png support missing');
            $status->setMessage(
                'GD must be compiled with png support. This is essential for' .
                ' TYPO3 CMS to work properly'
            );
        }
        return $status;
    }

    /**
     * Check gdlib supports freetype
     *
     * @return Status\StatusInterface
     */
    protected function checkGdLibFreeTypeSupport()
    {
        if (function_exists('imagettftext')) {
            $status = new Status\OkStatus();
            $status->setTitle('PHP GD library has freetype font support');
            $status->setMessage(
                'There is a difference between the font size setting which the GD' .
                ' library should be supplied  with. If installation is completed' .
                ' a test in the install tool helps to find out the value you need.'
            );
        } else {
            $status = new Status\ErrorStatus();
            $status->setTitle('PHP GD library freetype support missing');
            $status->setMessage(
                'Some core functionality and extension rely on the GD' .
                ' to render fonts on images. This support is missing' .
                ' in your environment. Install it.'
            );
        }
        return $status;
    }

    /**
     * Create true type font test image
     *
     * @return Status\StatusInterface
     */
    protected function isTrueTypeFontWorking()
    {
        if (function_exists('imageftbbox')) {
            // 20 Pixels at 96 DPI
            $fontSize = (20 / 96 * 72);
            $textDimensions = @imageftbbox(
                $fontSize,
                0,
                __DIR__ . '/../../Resources/Private/Font/vera.ttf',
                'Testing true type support'
            );
            $fontBoxWidth = $textDimensions[2] - $textDimensions[0];
            if ($fontBoxWidth < 300 && $fontBoxWidth > 200) {
                $status = new Status\OkStatus();
                $status->setTitle('FreeType True Type Font DPI');
                $status->setMessage('Fonts are rendered by FreeType library. ' .
                    'We need to ensure that the final dimensions are as expected. ' .
                    'This server renderes fonts based on 96 DPI correctly');
            } else {
                $status = new Status\NoticeStatus();
                $status->setTitle('FreeType True Type Font DPI');
                $status->setMessage('Fonts are rendered by FreeType library. ' .
                    'This server does not render fonts as expected. ' .
                    'Please check your FreeType 2 module.');
            }
        } else {
            $status = new Status\ErrorStatus();
            $status->setTitle('PHP GD library freetype2 support missing');
            $status->setMessage(
                'The core relies on GD library compiled into PHP with freetype2' .
                ' support. This is missing on your system. Please install it.'
            );
        }

        return $status;
    }

    /**
     * Check register globals
     *
     * @return Status\StatusInterface
     */
    protected function checkRegisterGlobals()
    {
        $registerGlobalsEnabled = filter_var(
            ini_get('register_globals'),
            FILTER_VALIDATE_BOOLEAN,
            [FILTER_REQUIRE_SCALAR, FILTER_NULL_ON_FAILURE]
        );
        if ($registerGlobalsEnabled === true) {
            $status = new Status\ErrorStatus();
            $status->setTitle('PHP register globals on');
            $status->setMessage(
                'register_globals=' . ini_get('register_globals') . LF .
                'TYPO3 requires PHP setting "register_globals" set to off.' .
                ' This ancient PHP setting is a big security problem and should' .
                ' never be enabled:' . LF .
                'register_globals=Off'
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('PHP register globals off');
        }
        return $status;
    }

    /**
     * Check for bug in libxml
     *
     * @return Status\StatusInterface
     */
    protected function checkLibXmlBug()
    {
        $sampleArray = ['Test>><<Data'];

        $xmlContent = '<numIndex index="0">Test&gt;&gt;&lt;&lt;Data</numIndex>' . LF;

        $xml = \TYPO3\CMS\Core\Utility\GeneralUtility::array2xml($sampleArray, '', -1);

        if ($xmlContent !== $xml) {
            $status = new Status\ErrorStatus();
            $status->setTitle('PHP libxml bug present');
            $status->setMessage(
                'Some hosts have problems saving ">><<" in a flexform.' .
                ' To fix this, enable [BE][flexformForceCDATA] in' .
                ' All Configuration.'
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('PHP libxml bug not present');
        }
        return $status;
    }

    /**
     * Helper methods
     */

    /**
     * Validate a given IP address.
     *
     * @param string $ip IP address to be tested
     * @return bool
     */
    protected function isValidIp($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Test if this instance runs on windows OS
     *
     * @return bool TRUE if operating system is windows
     */
    protected function isWindowsOs()
    {
        $windowsOs = false;
        if (!stristr(PHP_OS, 'darwin') && stristr(PHP_OS, 'win')) {
            $windowsOs = true;
        }
        return $windowsOs;
    }

    /**
     * Helper method to find out if suhosin extension is loaded
     *
     * @return bool TRUE if suhosin PHP extension is loaded
     */
    protected function isSuhosinLoadedAndActive()
    {
        $suhosinLoaded = false;
        if (extension_loaded('suhosin')) {
            $suhosinInSimulationMode = filter_var(
                ini_get('suhosin.simulation'),
                FILTER_VALIDATE_BOOLEAN,
                [FILTER_REQUIRE_SCALAR, FILTER_NULL_ON_FAILURE]
            );
            if (!$suhosinInSimulationMode) {
                $suhosinLoaded = true;
            }
        }
        return $suhosinLoaded;
    }

    /**
     * Helper method to explode a string by delimiter and throw away empty values.
     * Removes empty values from result array.
     *
     * @param string $delimiter Delimiter string to explode with
     * @param string $string The string to explode
     * @return array Exploded values
     */
    protected function trimExplode($delimiter, $string)
    {
        $explodedValues = explode($delimiter, $string);
        $resultWithPossibleEmptyValues = array_map('trim', $explodedValues);
        $result = [];
        foreach ($resultWithPossibleEmptyValues as $value) {
            if ($value !== '') {
                $result[] = $value;
            }
        }
        return $result;
    }

    /**
     * Helper method to get the bytes value from a measurement string like "100k".
     *
     * @param string $measurement The measurement (e.g. "100k")
     * @return int The bytes value (e.g. 102400)
     */
    protected function getBytesFromSizeMeasurement($measurement)
    {
        $bytes = floatval($measurement);
        if (stripos($measurement, 'G')) {
            $bytes *= 1024 * 1024 * 1024;
        } elseif (stripos($measurement, 'M')) {
            $bytes *= 1024 * 1024;
        } elseif (stripos($measurement, 'K')) {
            $bytes *= 1024;
        }
        return (int)$bytes;
    }
}
