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
class Check implements CheckInterface
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
        'SPL',
        'standard',
        'xml',
        'zip',
        'zlib',
    ];

    /**
     * @var string[]
     */
    protected $suggestedPhpExtensions = [
        'fileinfo' => 'This extension is used for proper file type detection in the File Abstraction Layer.',
    ];

    /**
     * Get all status information as array with status objects
     *
     * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
     */
    public function getStatus(): array
    {
        $status = [];
        $status[] = $this->checkCurrentDirectoryIsInIncludePath();
        $status[] = $this->checkFileUploadEnabled();
        $status[] = $this->checkPostUploadSizeIsHigherOrEqualMaximumFileUploadSize();
        $status[] = $this->checkMemorySettings();
        $status[] = $this->checkPhpVersion();
        $status[] = $this->checkMaxExecutionTime();
        $status[] = $this->checkDisableFunctions();
        $status[] = $this->checkMysqliReconnectSetting();
        $status[] = $this->checkDocRoot();
        $status[] = $this->checkOpenBaseDir();
        $status[] = $this->checkXdebugMaxNestingLevel();
        $status[] = $this->checkOpenSslInstalled();

        $status[] = $this->checkMaxInputVars();
        $status[] = $this->checkReflectionDocComment();
        $status[] = $this->checkWindowsApacheThreadStackSize();

        foreach ($this->requiredPhpExtensions as $extension) {
            $status[] = $this->checkPhpExtension($extension);
        }

        foreach ($this->suggestedPhpExtensions as $extension => $purpose) {
            $status[] = $this->checkPhpExtension($extension, false, $purpose);
        }

        $status[] = $this->checkPcreVersion();
        $status[] = $this->checkGdLibTrueColorSupport();
        $status[] = $this->checkGdLibGifSupport();
        $status[] = $this->checkGdLibJpgSupport();
        $status[] = $this->checkGdLibPngSupport();
        $status[] = $this->checkGdLibFreeTypeSupport();

        return $status;
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
            $status->setMessage('The maximum size for file uploads is actually set to ' . ini_get('upload_max_filesize'));
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
        $minimumPhpVersion = '7.0.0';
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
        $minimumPcreVersion = '8.38';
        if (!extension_loaded('pcre')) {
            $status = new Status\ErrorStatus();
            $status->setTitle('PHP extension pcre not loaded');
            $status->setMessage(
                'TYPO3 CMS uses PHP extension pcre but it is not loaded' .
                ' in your environment. Change your environment to provide this extension' .
                ' in with minimum version ' . $minimumPcreVersion . '.'
            );
        } else {
            $installedPcreVersionString = trim(PCRE_VERSION); // '8.39 2016-06-14'
            $mainPcreVersionString = explode(' ', $installedPcreVersionString);
            $mainPcreVersionString = $mainPcreVersionString[0]; // '8.39'
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
                '&lt;IfModule mpm_winnt_module&gt;' . LF .
                'ThreadStackSize 8388608' . LF .
                '&lt;/IfModule&gt;'
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('Apache ThreadStackSize is not an issue on UNIX systems');
        }
        return $status;
    }

    /**
     * Checks if a specific PHP extension is loaded.
     *
     * @param string $extension
     * @param bool $required
     * @param string $purpose
     * @return Status\StatusInterface
     */
    protected function checkPhpExtension(string $extension, bool $required = true, string $purpose = '')
    {
        if (!extension_loaded($extension)) {
            $status = $required ? new Status\ErrorStatus() : new Status\WarningStatus();
            $status->setTitle('PHP extension ' . $extension . ' not loaded');
            $status->setMessage(
                'TYPO3 uses the PHP extension "' . $extension . '" but it is not loaded'
                . ' in your environment. Change your environment to provide this extension. '
                . $purpose
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('PHP extension "' . $extension . '" loaded');
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
            // Do not use data:// wrapper to be independent of allow_url_fopen
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
            // Do not use data:// wrapper to be independent of allow_url_fopen
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
        $bytes = (float)$measurement;
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
