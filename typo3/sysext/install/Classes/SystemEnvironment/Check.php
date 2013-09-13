<?php
namespace TYPO3\CMS\Install\SystemEnvironment;

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
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class Check {

	/**
	 * @var array List of required PHP extensions
	 */
	protected $requiredPhpExtensions = array(
		'fileinfo',
		'filter',
		'gd',
		'hash',
		'json',
		'mysqli',
		'openssl',
		'pcre',
		'session',
		'soap',
		'SPL',
		'standard',
		'xml',
		'zip',
		'zlib',
	);

	/**
	 * Get all status information as array with status objects
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	public function getStatus() {
		$statusArray = array();
		$statusArray[] = $this->checkCurrentDirectoryIsInIncludePath();
		$statusArray[] = $this->checkFileUploadEnabled();
		$statusArray[] = $this->checkMaximumFileUploadSize();
		$statusArray[] = $this->checkPostUploadSizeIsHigherOrEqualMaximumFileUploadSize();
		$statusArray[] = $this->checkMemorySettings();
		$statusArray[] = $this->checkPhpVersion();
		$statusArray[] = $this->checkMaxExecutionTime();
		$statusArray[] = $this->checkDisableFunctions();
		$statusArray[] = $this->checkSafeMode();
		$statusArray[] = $this->checkDocRoot();
		$statusArray[] = $this->checkOpenBaseDir();
		$statusArray[] = $this->checkXdebugMaxNestingLevel();
		$statusArray[] = $this->checkOpenSslInstalled();
		$statusArray[] = $this->checkSuhosinLoaded();
		$statusArray[] = $this->checkSuhosinRequestMaxVars();
		$statusArray[] = $this->checkSuhosinPostMaxVars();
		$statusArray[] = $this->checkSuhosinGetMaxValueLength();
		$statusArray[] = $this->checkSuhosinExecutorIncludeWhitelistContainsPhar();
		$statusArray[] = $this->checkSuhosinExecutorIncludeWhitelistContainsVfs();
		$statusArray[] = $this->checkSomePhpOpcodeCacheIsLoaded();
		$statusArray[] = $this->checkReflectionDocComment();
		$statusArray[] = $this->checkWindowsApacheThreadStackSize();
		foreach ($this->requiredPhpExtensions as $extension) {
			$statusArray[] = $this->checkRequiredPhpExtension($extension);
		}
		$statusArray[] = $this->checkGdLibTrueColorSupport();
		$statusArray[] = $this->checkGdLibGifSupport();
		$statusArray[] = $this->checkGdLibJpgSupport();
		$statusArray[] = $this->checkGdLibPngSupport();
		$statusArray[] = $this->checkGdLibFreeTypeSupport();
		$statusArray[] = $this->checkPhpMagicQuotes();
		$statusArray[] = $this->checkRegisterGlobals();
		$statusArray[] = $this->isTrueTypeFontDpiStandard();
		return $statusArray;
	}

	/**
	 * Checks if current directory (.) is in PHP include path
	 *
	 * @return Status\StatusInterface
	 */
	protected function checkCurrentDirectoryIsInIncludePath() {
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
	protected function checkFileUploadEnabled() {
		if (!ini_get('file_uploads')) {
			$status = new Status\ErrorStatus();
			$status->setTitle('File uploads not allowed in PHP');
			$status->setMessage(
				'file_uploads=' . ini_get('file_uploads') . LF .
				'TYPO3 uses the ability to upload files from the browser in various cases.' .
				' As long as this flag is disabled in PHP, you\'ll not be able to upload files.' .
				' But it doesn\'t end here, because not only are files not accepted by' .
				' the server - ALL content in the forms are discarded and therefore' .
				' nothing at all will be editable if you don\'t set this flag!' .
				' However if you cannot enable fileupload for some reason in PHP, alternatively' .
				' change the default form encoding value with \\$TYPO3_CONF_VARS[SYS][form_enctype].'
			);
		} else {
			$status = new Status\OkStatus();
			$status->setTitle('File uploads allowed in PHP');
		}
		return $status;
	}

	/**
	 * Check maximum file upload size against default value of 10MB
	 *
	 * @return Status\StatusInterface
	 */
	protected function checkMaximumFileUploadSize() {
		$maximumUploadFilesize = $this->getBytesFromSizeMeasurement(ini_get('upload_max_filesize'));
		if ($maximumUploadFilesize < 1024 * 1024 * 10) {
			$status = new Status\ErrorStatus();
			$status->setTitle('PHP Maximum upload filesize too small');
			$status->setMessage(
				'upload_max_filesize=' . ini_get('upload_max_filesize') . LF .
				'By default TYPO3 supports uploading, copying and moving' .
				' files of sizes up to 10MB (you can alter the TYPO3 defaults' .
				' by the config option TYPO3_CONF_VARS[BE][maxFileSize]).' .
				' Your current PHP value is below this, so at this point, PHP determines' .
				' the limits for uploaded filesizes and not TYPO3.' .
				' It is recommended that the value of upload_max_filesize at least equals to the value' .
				' of TYPO3_CONF_VARS[BE][maxFileSize]'
			);
		} else {
			$status = new Status\OkStatus();
			$status->setTitle('PHP Maximum file upload size is higher or equal to 10MB');
		}
		return $status;
	}

	/**
	 * Check maximum post upload size correlates with maximum file upload
	 *
	 * @return Status\StatusInterface
	 */
	protected function checkPostUploadSizeIsHigherOrEqualMaximumFileUploadSize() {
		$maximumUploadFilesize = $this->getBytesFromSizeMeasurement(ini_get('upload_max_filesize'));
		$maximumPostSize = $this->getBytesFromSizeMeasurement(ini_get('post_max_size'));
		if ($maximumPostSize < $maximumUploadFilesize) {
			$status = new Status\ErrorStatus();
			$status->setTitle('Maximum size for POST requests is smaller than maximum upload filesize in PHP');
			$status->setMessage(
				'upload_max_filesize=' . ini_get('upload_max_filesize') . LF .
				'post_max_size=' . ini_get('post_max_size') . LF .
				'You have defined a maximum size for file uploads in PHP which' .
				' exceeds the allowed size for POST requests. Therefore the' .
				' file uploads can not be larger than ' . ini_get('post_max_size') . '.'
			);
		} else {
			$status = new Status\OkStatus();
			$status->setTitle('Maximum post upload size correlates with maximum upload file size in PHP');
		}
		return $status;
	}

	/**
	 * Check memory settings
	 *
	 * @return Status\StatusInterface
	 */
	protected function checkMemorySettings() {
		$minimumMemoryLimit = 32;
		$recommendedMemoryLimit = 64;
		$memoryLimit = $this->getBytesFromSizeMeasurement(ini_get('memory_limit'));
		if ($memoryLimit <= 0) {
			$status = new Status\WarningStatus();
			$status->setTitle('Unlimited memory limit for PHP');
			$status->setMessage(
				'PHP is configured to not limit memory usage at all. This is a risk' .
				' and should be avoided in production setup. In general it\'s best practice to limit this.' .
				' To be safe, set a limit in PHP, but with a minimum of ' . $recommendedMemoryLimit . 'MB:' . LF .
				'memory_limit=' . $recommendedMemoryLimit . 'M'
			);
		} elseif ($memoryLimit < 1024 * 1024 * $minimumMemoryLimit) {
			$status = new Status\ErrorStatus();
			$status->setTitle('PHP Memory limit below ' . $minimumMemoryLimit . 'MB');
			$status->setMessage(
				'memory_limit=' . ini_get('memory_limit') . LF .
				'Your system is configured to enforce a memory limit of PHP scripts lower than ' .
				$minimumMemoryLimit . 'MB. It is required to raise the limit.' .
				' We recommend a minimum PHP memory limit of ' . $recommendedMemoryLimit . 'MB:' . LF .
				'memory_limit=' . $recommendedMemoryLimit . 'M'
			);
		} elseif ($memoryLimit < 1024 * 1024 * $recommendedMemoryLimit) {
			$status = new Status\WarningStatus();
			$status->setTitle('PHP Memory limit below ' . $recommendedMemoryLimit . 'MB');
			$status->setMessage(
				'memory_limit=' . ini_get('memory_limit') . LF .
				'Your system is configured to enforce a memory limit of PHP scripts lower than ' .
				$recommendedMemoryLimit . 'MB.' .
				' A slim TYPO3 instance without many extensions will probably work, but you should monitor your' .
				' system for exhausted messages, especially if using the backend. To be on the safe side,' .
				' we recommend a minimum PHP memory limit of ' . $recommendedMemoryLimit . 'MB:' . LF .
				'memory_limit=' . $recommendedMemoryLimit . 'M'
			);
		} else {
			$status = new Status\OkStatus();
			$status->setTitle('PHP Memory limit equals to ' . $recommendedMemoryLimit . 'MB or more');
		}
		return $status;
	}

	/**
	 * Check minimum PHP version
	 *
	 * @return Status\StatusInterface
	 */
	protected function checkPhpVersion() {
		$minimumPhpVersion = '5.3.7';
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
	 * Check maximum execution time
	 *
	 * @return Status\StatusInterface
	 */
	protected function checkMaxExecutionTime() {
		$minimumMaximumExecutionTime = 30;
		$recommendedMaximumExecutionTime = 240;
		$currentMaximumExecutionTime = ini_get('max_execution_time');
		if ($currentMaximumExecutionTime == 0) {
			if (PHP_SAPI === 'cli') {
				$status = new Status\OkStatus();
				$status->setTitle('Infinite PHP script execution time');
				$status->setMessage(
					'Maximum PHP script execution time is always set to infinite (0) in cli mode.' .
					' The setting used for web requests can not be checked from command line.'
				);
			} else {
				$status = new Status\WarningStatus();
				$status->setTitle('Infinite PHP script execution time');
				$status->setMessage(
					'max_execution_time=' . $currentMaximumExecutionTime . LF .
					'While TYPO3 is fine with this, you risk a denial-of-service of your system if for whatever' .
					' reason some script hangs in an infinite loop. You are usually on safe side ' .
					' if it is reduced to ' . $recommendedMaximumExecutionTime . ' seconds:' . LF .
					'max_execution_time=' . $recommendedMaximumExecutionTime
				);
			}
		} elseif ($currentMaximumExecutionTime < $minimumMaximumExecutionTime) {
			$status = new Status\ErrorStatus();
			$status->setTitle('Low PHP script execution time');
			$status->setMessage(
				'max_execution_time=' . $currentMaximumExecutionTime . LF .
				'Your max_execution_time is too low. Some expensive operation in TYPO3 can take longer than that.' .
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
				' it still may happen that script execution is stopped before finishing' .
				' calculations. You should monitor the system for messages in this area' .
				' and maybe raise the limit to ' . $recommendedMaximumExecutionTime . ' seconds:' . LF .
				'max_execution_time=' . $recommendedMaximumExecutionTime
			);
		} else {
			$status = new Status\OkStatus();
			$status->setTitle('Maximum PHP script execution time equals ' . $recommendedMaximumExecutionTime . ' or more');
		}
		return $status;
	}

	/**
	 * Check for disabled functions
	 *
	 * @return Status\StatusInterface
	 */
	protected function checkDisableFunctions() {
		$disabledFunctions = trim(ini_get('disable_functions'));

		// Filter "disable_functions"
		$disabledFunctionsArray = $this->trimExplode(',', $disabledFunctions);

		// Array with strings to find
		$findStrings = array(
			// Disabled by default on Ubuntu OS but this is okay since the Core does not use them
			'pcntl_',
		);
		foreach ($disabledFunctionsArray as $key => $disabledFunction) {
			foreach ($findStrings as $findString) {
				if (strpos($disabledFunction, $findString) !== FALSE) {
					unset($disabledFunctionsArray[$key]);
				}
			}
		}

		if (strlen($disabledFunctions) > 0 && count($disabledFunctionsArray) > 0) {
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
		} elseif (strlen($disabledFunctions) > 0 && count($disabledFunctionsArray) === 0) {
			$status = new Status\NoticeStatus();
			$status->setTitle('Some PHP functions currently disabled but OK');
			$status->setMessage(
				'disable_functions=' . implode(' ', explode(',', $disabledFunctions)) . LF .
				'These function(s) are disabled. TYPO3 uses currently none of those, so you are good to go.'
			);
		} else {
			$status  = new Status\OkStatus();
			$status->setTitle('No disabled PHP functions');
		}
		return $status;
	}

	/**
	 * Check if safe mode is enabled
	 *
	 * @return Status\StatusInterface
	 */
	protected function checkSafeMode() {
		$safeModeEnabled = FALSE;
		if (version_compare(phpversion(), '5.4', '<')) {
			$safeModeEnabled = filter_var(
				ini_get('safe_mode'),
				FILTER_VALIDATE_BOOLEAN,
				array(FILTER_REQUIRE_SCALAR, FILTER_NULL_ON_FAILURE)
			);
		}
		if ($safeModeEnabled) {
			$status = new Status\ErrorStatus();
			$status->setTitle('PHP safe mode on');
			$status->setMessage(
				'PHP safe_mode enabled. This is unsupported by TYPO3 CMS, it must be turned off:' . LF .
				'safe_mode=Off'
			);
		} else {
			$status = new Status\OkStatus();
			$status->setTitle('PHP safe mode off');
		}
		return $status;
	}

	/**
	 * Check for doc_root ini setting
	 *
	 * @return Status\StatusInterface
	 */
	protected function checkDocRoot() {
		$docRootSetting = trim(ini_get('doc_root'));
		if (strlen($docRootSetting) > 0) {
			$status = new Status\NoticeStatus();
			$status->setTitle('doc_root is set');
			$status->setMessage(
				'doc_root=' . $docRootSetting . LF .
				'PHP cannot execute scripts' .
				' outside this directory. This setting is used seldom and must correlate' .
				' with your actual document root. You might be in trouble if your' .
				' TYPO3 CMS core code is linked to some different location.' .
				' If that is a problem, the setting must be adapted.'
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
	protected function checkOpenBaseDir() {
		$openBaseDirSetting = trim(ini_get('open_basedir'));
		if (strlen($openBaseDirSetting) > 0) {
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
	protected function checkXdebugMaxNestingLevel() {
		if (extension_loaded('xdebug')) {
			$recommendedMaxNestingLevel = 250;
			$currentMaxNestingLevel = ini_get('xdebug.max_nesting_level');
			if ($currentMaxNestingLevel < $recommendedMaxNestingLevel) {
				$status = new Status\ErrorStatus();
				$status->setTitle('PHP xdebug.max_nesting_level too low');
				$status->setMessage(
					'xdebug.max_nesting_level=' . $currentMaxNestingLevel . LF .
					'This setting controls the maximum number of nested function calls to protect against' .
					' infinite recursion. The current value is too low for TYPO3 CMS and must' .
					' be either raised or xdebug unloaded. A value of ' . $recommendedMaxNestingLevel .
					' is recommended. Warning: Expect fatal PHP errors in central parts of the CMS' .
					' if the default value of 100 is not raised significantly to:' . LF .
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
	protected function checkOpenSslInstalled() {
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
				' TYPO3 CMS requires it to be able to store passwords encrypted to improve the security on database layer.'
			);
		}

		return $status;
	}

	/**
	 * Check enabled suhosin
	 *
	 * @return Status\StatusInterface
	 */
	protected function checkSuhosinLoaded() {
		if ($this->isSuhosinLoaded()) {
			$status = new Status\OkStatus();
			$status->setTitle('PHP suhosin extension loaded');
		} else {
			$status = new Status\NoticeStatus();
			$status->setTitle('PHP suhosin extension not loaded');
			$status->setMessage(
				'suhosin is an extension to harden the PHP environment. In general, it is' .
				' good to have it from a security point of view. While TYPO3 CMS works' .
				' fine with suhosin, it has some requirements different from default settings' .
				' to be set if enabled.'
			);
		}
		return $status;
	}

	/**
	 * Check suhosin.request.max_vars
	 *
	 * @return Status\StatusInterface
	 */
	protected function checkSuhosinRequestMaxVars() {
		$recommendedRequestMaxVars = 400;
		if ($this->isSuhosinLoaded()) {
			$currentRequestMaxVars = ini_get('suhosin.request.max_vars');
			if ($currentRequestMaxVars < $recommendedRequestMaxVars) {
				$status = new Status\ErrorStatus();
				$status->setTitle('PHP suhosin.request.max_vars too low');
				$status->setMessage(
					'suhosin.request.max_vars=' . $currentRequestMaxVars . LF .
					'This setting can lead to lost information if submitting big forms in TYPO3 CMS like' .
					' it is done in the install tool. It is heavily recommended to raise this' .
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
	 * Check suhosin.post.max_vars
	 *
	 * @return Status\StatusInterface
	 */
	protected function checkSuhosinPostMaxVars() {
		$recommendedPostMaxVars = 400;
		if ($this->isSuhosinLoaded()) {
			$currentPostMaxVars = ini_get('suhosin.post.max_vars');
			if ($currentPostMaxVars < $recommendedPostMaxVars) {
				$status = new Status\ErrorStatus();
				$status->setTitle('PHP suhosin.post.max_vars too low');
				$status->setMessage(
					'suhosin.post.max_vars=' . $currentPostMaxVars . LF .
					'This setting can lead to lost information if submitting big forms in TYPO3 CMS like' .
					' it is done in the install tool. It is heavily recommended to raise this' .
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
	protected function checkSuhosinGetMaxValueLength() {
		$recommendedGetMaxValueLength = 2000;
		if ($this->isSuhosinLoaded()) {
			$currentGetMaxValueLength = ini_get('suhosin.get.max_value_length');
			if ($currentGetMaxValueLength < $recommendedGetMaxValueLength) {
				$status = new Status\ErrorStatus();
				$status->setTitle('PHP suhosin.get.max_value_length too low');
				$status->setMessage(
					'suhosin.get.max_value_length=' . $currentGetMaxValueLength . LF .
					'This setting can lead to lost information if submitting big forms in TYPO3 CMS like' .
					' it is done in the install tool. It is heavily recommended to raise this' .
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
	 * Check suhosin.executor.include.whitelist contains phar
	 *
	 * @return Status\StatusInterface
	 */
	protected function checkSuhosinExecutorIncludeWhiteListContainsPhar() {
		if ($this->isSuhosinLoaded()) {
			$currentWhiteListArray = $this->trimExplode(' ', ini_get('suhosin.executor.include.whitelist'));
			if (!in_array('phar', $currentWhiteListArray)) {
				$status = new Status\NoticeStatus();
				$status->setTitle('PHP suhosin.executor.include.whitelist does not contain phar');
				$status->setMessage(
					'suhosin.executor.include.whitelist= ' . implode(' ', $currentWhiteListArray) . LF .
					'"phar" is currently not a hard requirement of TYPO3 CMS but is nice to have and a possible' .
					' requirement in future versions. A useful setting is:' . LF .
					'suhosin.executor.include.whitelist=phar vfs'
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
				'suhosin.executor.include.whitelist=phar vfs'
			);
		}
		return $status;
	}

	/**
	 * Check suhosin.executor.include.whitelist contains vfs
	 *
	 * @return Status\StatusInterface
	 */
	protected function checkSuhosinExecutorIncludeWhiteListContainsVfs() {
		if ($this->isSuhosinLoaded()) {
			$currentWhiteListArray = $this->trimExplode(' ', ini_get('suhosin.executor.include.whitelist'));
			if (!in_array('vfs', $currentWhiteListArray)) {
				$status = new Status\WarningStatus();
				$status->setTitle('PHP suhosin.executor.include.whitelist does not contain vfs');
				$status->setMessage(
					'suhosin.executor.include.whitelist= ' . implode(' ', $currentWhiteListArray) . LF .
					'"vfs" is currently not a hard requirement of TYPO3 CMS but tons of unit tests rely on it.' .
					' Furthermore, vfs is likely a base for an additional compatibility layer in the future.' .
					' A useful setting is:' . LF .
					'suhosin.executor.include.whitelist=phar vfs'
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
				'suhosin.executor.include.whitelist=phar vfs'
			);
		}
		return $status;
	}

	/**
	 * Check if some opcode cache is loaded
	 *
	 * @return Status\StatusInterface
	 */
	protected function checkSomePhpOpcodeCacheIsLoaded() {
		if (
			// Currently APCu identifies itself both as "apcu" and "apc" (for compatibility) although it doesn't provide the APC-opcache functionality
			extension_loaded('eaccelerator')
			|| extension_loaded('xcache')
			|| (extension_loaded('apc') && !extension_loaded('apcu'))
			|| extension_loaded('Zend Optimizer+')
			|| extension_loaded('Zend OPcache')
			|| extension_loaded('wincache')
		) {
			$status = new Status\OkStatus();
			$status->setTitle('A PHP opcode cache is loaded');
		} else {
			$status = new Status\WarningStatus();
			$status->setTitle('No PHP opcode cache loaded');
			$status->setMessage(
				'PHP opcode caches hold a compiled version of executed PHP scripts in' .
				' memory and do not require to recompile any script on each access.' .
				' This can be a massive performance improvement and can put load off a' .
				' server in general, a parse time reduction by factor three for full cached' .
				' pages can be achieved easily if using some opcode cache.' .
				' If in doubt choosing one, APC runs well and can be used as data' .
				' cache layer in TYPO3 CMS as additional feature.'
			);
		}
		return $status;
	}

	/**
	 * Check doc comments can be fetched by reflection
	 *
	 * @return Status\StatusInterface
	 */
	protected function checkReflectionDocComment() {
		$testReflection = new \ReflectionMethod(get_class($this), __FUNCTION__);
		if (strlen($testReflection->getDocComment()) === 0) {
			$status = new Status\ErrorStatus();
			$status->setTitle('PHP Doc comment reflection broken');
			$status->setMessage(
				'TYPO3 CMS core extensions like extbase and fluid heavily rely on method' .
				' comment parsing to fetch annotations and add magic according to them.' .
				' This does not work in the current environment and will lead to a lot of' .
				' broken extensions. The PHP extension eaccelerator is known to break this if' .
				' it is compiled without --with-eaccelerator-doc-comment-inclusion flag.' .
				' This compile flag must be given, otherwise TYPO3 CMS is no fun.'
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
	protected function checkWindowsApacheThreadStackSize() {
		if (
			$this->isWindowsOs()
			&& substr($_SERVER['SERVER_SOFTWARE'], 0, 6) === 'Apache'
		) {
			$status = new Status\WarningStatus();
			$status->setTitle('Windows apache thread stack size');
			$status->setMessage(
				'This current value can not be checked by the system, so please ignore this warning if it' .
				' is already taken care of: Fluid uses complex regular expressions which require a lot' .
				' of stack space during the first processing.' .
				' On Windows the default stack size for Apache is a lot smaller than on UNIX.' .
				' You can increase the size to 8MB (default on UNIX) by adding the following configuration' .
				' to httpd.conf and restart Apache afterwards:' . LF .
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
	protected function checkRequiredPhpExtension($extension) {
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
	protected function checkGdLibTrueColorSupport() {
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
	protected function checkGdLibGifSupport() {
		if (
			function_exists('imagecreatefromgif')
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
	protected function checkGdLibJpgSupport() {
		if (
			function_exists('imagecreatefromjpeg')
			&& function_exists('imagejpeg')
			&& (imagetypes() & IMG_JPG)
		) {
			$status = new Status\OkStatus();
			$status->setTitle('PHP GD library has jpg support');
		} else {
			$status= new Status\ErrorStatus();
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
	protected function checkGdLibPngSupport() {
		if (
			function_exists('imagecreatefrompng')
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
	protected function checkGdLibFreeTypeSupport() {
		if (function_exists('imagettftext')) {
			$status = new Status\OkStatus();
			$status->setTitle('PHP GD library has freetype font support');
			$status->setMessage(
				'There is a difference between the font size setting the GD' .
				' library should be feeded with. If installation is completed' .
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
	protected function isTrueTypeFontDpiStandard() {
		if (function_exists('imageftbbox')) {
			// 20 Pixels at 96 DPI - the DefaultConfiguration
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
					'This server renderes fonts based on 96 DPI correctly'
				);
			} else {
				$status = new Status\NoticeStatus();
				$status->setTitle('FreeType True Type Font DPI');
				$status->setMessage('Fonts are rendered by FreeType library. ' .
					'This server renders fonts not as expected. ' .
					'Please configure FreeType or TYPO3_CONF_VARS[GFX][TTFdpi]'
				);
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
	 * Check php magic quotes
	 *
	 * @return Status\StatusInterface
	 */
	protected function checkPhpMagicQuotes() {
		$magicQuotesGpc = get_magic_quotes_gpc();
		if ($magicQuotesGpc) {
			$status = new Status\WarningStatus();
			$status->setTitle('PHP magic quotes on');
			$status->setMessage(
				'magic_quotes_gpc=' . $magicQuotesGpc . LF .
				'Setting magic_quotes_gpc is deprecated since PHP 5.3.' .
				' You are advised to disable it until it gets completely removed:' . LF .
				'magic_quotes_gpc=Off'
			);
		} else {
			$status = new Status\OkStatus();
			$status->setTitle('PHP magic quotes off');
		}
		return $status;
	}

	/**
	 * Check register globals
	 *
	 * @return Status\StatusInterface
	 */
	protected function checkRegisterGlobals() {
		$registerGlobalsEnabled = filter_var(
			ini_get('register_globals'),
			FILTER_VALIDATE_BOOLEAN,
			array(FILTER_REQUIRE_SCALAR, FILTER_NULL_ON_FAILURE)
		);
		if ($registerGlobalsEnabled === TRUE) {
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
	 * Helper methods
	 */

	/**
	 * Validate a given IP address.
	 *
	 * @param string $ip IP address to be tested
	 * @return boolean
	 */
	protected function isValidIp($ip) {
		return filter_var($ip, FILTER_VALIDATE_IP) !== FALSE;
	}

	/**
	 * Test if this instance runs on windows OS
	 *
	 * @return boolean TRUE if operating system is windows
	 */
	protected function isWindowsOs() {
		$windowsOs = FALSE;
		if (!stristr(PHP_OS, 'darwin') && stristr(PHP_OS, 'win')) {
			$windowsOs = TRUE;
		}
		return $windowsOs;
	}

	/**
	 * Helper method to find out if suhosin extension is loaded
	 *
	 * @return boolean TRUE if suhosin PHP extension is loaded
	 */
	protected function isSuhosinLoaded() {
		$suhosinLoaded = FALSE;
		if (extension_loaded('suhosin')) {
			$suhosinLoaded = TRUE;
		}
		return $suhosinLoaded;
	}

	/**
	 * Helper method to explode a string by delimeter and throw away empty values.
	 * Removes empty values from result array.
	 *
	 * @param string $delimiter Delimiter string to explode with
	 * @param string $string The string to explode
	 * @return array Exploded values
	 */
	protected function trimExplode($delimiter, $string) {
		$explodedValues = explode($delimiter, $string);
		$resultWithPossibleEmptyValues = array_map('trim', $explodedValues);
		$result = array();
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
	 * @return integer The bytes value (e.g. 102400)
	 */
	protected function getBytesFromSizeMeasurement($measurement) {
		$bytes = doubleval($measurement);
		if (stripos($measurement, 'G')) {
			$bytes *= 1024 * 1024 * 1024;
		} elseif (stripos($measurement, 'M')) {
			$bytes *= 1024 * 1024;
		} elseif (stripos($measurement, 'K')) {
			$bytes *= 1024;
		}
		return $bytes;
	}
}
?>
