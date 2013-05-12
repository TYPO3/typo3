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
		'zlib'
	);

	/**
	 * Constructor to load further classes
	 */
	public function __construct() {
		require(__DIR__ . '/StatusInterface.php');
		require(__DIR__ . '/AbstractStatus.php');
		require(__DIR__ . '/NoticeStatus.php');
		require(__DIR__ . '/InfoStatus.php');
		require(__DIR__ . '/OkStatus.php');
		require(__DIR__ . '/WarningStatus.php');
		require(__DIR__ . '/ErrorStatus.php');
	}

	/**
	 * Get all status information as array with status objects
	 *
	 * @return array<\TYPO3\CMS\Install\SystemEnvironment\StatusInterface>
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
		$statusArray[] = $this->checkSqlSafeMode();
		$statusArray[] = $this->checkOpenBaseDir();
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
		$statusArray[] = $this->checkMailCapabilities();
		$statusArray[] = $this->checkGdLibTrueColorSupport();
		$statusArray[] = $this->checkGdLibGifSupport();
		$statusArray[] = $this->checkGdLibJpgSupport();
		$statusArray[] = $this->checkGdLibPngSupport();
		$statusArray[] = $this->checkGdLibFreeTypeSupport();
		$statusArray[] = $this->checkPhpMagicQuotes();
		$statusArray[] = $this->checkRegisterGlobals();
		return $statusArray;
	}

	/**
	 * Checks if current directory (.) is in PHP include path
	 *
	 * @return WarningStatus|OkStatus
	 */
	protected function checkCurrentDirectoryIsInIncludePath() {
		$includePath = ini_get('include_path');
		$delimiter = $this->isWindowsOs() ? ';' : ':';
		$pathArray = $this->trimExplode($delimiter, $includePath);
		if (!in_array('.', $pathArray)) {
			$status = new WarningStatus();
			$status->setTitle('Current directory (./) is not in include path');
			$status->setMessage(
				'include_path = ' . implode(' ', $pathArray) .
				' Normally the current path, \'.\', is included in the' .
				' include_path of PHP. Although TYPO3 does not rely on this,' .
				' it is an unusual setting that may introduce problems for' .
				' some extensions.'
			);
		} else {
			$status = new OkStatus();
			$status->setTitle('Current directory (./) is in include path.');
		}
		return $status;
	}

	/**
	 * Check if file uploads are enabled in PHP
	 *
	 * @return ErrorStatus|OkStatus
	 */
	protected function checkFileUploadEnabled() {
		if (!ini_get('file_uploads')) {
			$status = new ErrorStatus();
			$status->setTitle('File uploads not allowed');
			$status->setMessage(
				'file_uploads=' . ini_get('file_uploads') .
				' TYPO3 uses the ability to upload files from the browser in various cases.' .
				' As long as this flag is disabled, you\'ll not be able to upload files.' .
				' But it doesn\'t end here, because not only are files not accepted by' .
				' the server - ALL content in the forms are discarded and therefore' .
				' nothing at all will be editable if you don\'t set this flag!' .
				' However if you cannot enable fileupload for some reason alternatively' .
				' you change the default form encoding value with \\$TYPO3_CONF_VARS[SYS][form_enctype].'
			);
		} else {
			$status = new OkStatus();
			$status->setTitle('File uploads allowed');
		}
		return $status;
	}

	/**
	 * Check maximum file upload size against default value of 10MB
	 *
	 * @return ErrorStatus|OkStatus
	 */
	protected function checkMaximumFileUploadSize() {
		$maximumUploadFilesize = $this->getBytesFromSizeMeasurement(ini_get('upload_max_filesize'));
		if ($maximumUploadFilesize < 1024 * 1024 * 10) {
			$status = new ErrorStatus();
			$status->setTitle('Maximum upload filesize too small');
			$status->setMessage(
				'upload_max_filesize=' . ini_get('upload_max_filesize') .
				'. By default TYPO3 supports uploading, copying and moving' .
				' files of sizes up to 10MB (you can alter the TYPO3 defaults' .
				' by the config option TYPO3_CONF_VARS[BE][maxFileSize]).' .
				' Your current value is below this, so at this point, PHP sets' .
				' the limits for uploaded filesizes and not TYPO3.'
			);
		} else {
			$status = new OkStatus();
			$status->setTitle('Maximum file upload size is higher or equal to 10MB');
		}
		return $status;
	}

	/**
	 * Check maximum post upload size correlates with maximum file upload
	 *
	 * @return ErrorStatus|OkStatus
	 */
	protected function checkPostUploadSizeIsHigherOrEqualMaximumFileUploadSize() {
		$maximumUploadFilesize = $this->getBytesFromSizeMeasurement(ini_get('upload_max_filesize'));
		$maximumPostSize = $this->getBytesFromSizeMeasurement(ini_get('post_max_size'));
		if ($maximumPostSize < $maximumUploadFilesize) {
			$status = new ErrorStatus();
			$status->setTitle('Maximum size for POST requests is smaller than max. upload filesize');
			$status->setMessage(
				'upload_max_filesize=' . ini_get('upload_max_filesize') .
				', post_max_size=' . ini_get('post_max_size') .
				' You have defined a maximum size for file uploads which' .
				' exceeds the allowed size for POST requests. Therefore the' .
				' file uploads can not be larger than ' . ini_get('post_max_size')
			);
		} else {
			$status = new OkStatus();
			$status->setTitle('Maximum post upload size correlates with maximum upload file size');
		}
		return $status;
	}

	/**
	 * Check memory settings
	 *
	 * @return ErrorStatus|WarningStatus|OkStatus
	 */
	protected function checkMemorySettings() {
		$memoryLimit = $this->getBytesFromSizeMeasurement(ini_get('memory_limit'));
		if ($memoryLimit <= 0) {
			$status = new WarningStatus();
			$status->setTitle('Unlimited memory limit!');
			$status->setMessage(
				'Your webserver is configured to not limit PHP memory usage at all. This is a risk' .
				' and should be avoided in production setup. In general it\'s best practice to limit this' .
				' in the configuration of your webserver. To be safe, ask the system administrator of the' .
				' webserver to raise the limit to something over 64MB'
			);
		} elseif ($memoryLimit < 1024 * 1024 * 32) {
			$status = new ErrorStatus();
			$status->setTitle('Memory limit below 32MB');
			$status->setMessage(
				'memory_limit=' . ini_get('memory_limit') .
				' Your system is configured to enforce a memory limit of PHP scripts lower than 32MB.' .
				' There is nothing else to do than raise the limit. To be safe, ask the system' .
				' administrator of the webserver to raise the limit to 64MB.'
			);
		} elseif ($memoryLimit < 1024 * 1024 * 64) {
			$status = new WarningStatus();
			$status->setTitle('Memory limit below 64MB');
			$status->setMessage(
				'memory_limit=' . ini_get('memory_limit') .
				' Your system is configured to enforce a memory limit of PHP scripts lower than 64MB.' .
				' A slim TYPO3 instance without many extensions will probably work, but you should ' .
				' monitor your system for exhausted messages, especially if using the backend. ' .
				' To be on the safe side, it would be better to raise the PHP memory limit to 64MB or more.'
			);
		} else {
			$status = new OkStatus();
			$status->setTitle('Memory limit equal 64MB or more');
		}
		return $status;
	}

	/**
	 * Check minimum PHP version
	 *
	 * @return ErrorStatus|OkStatus
	 */
	protected function checkPhpVersion() {
		$minimumPhpVersion = '5.3.0';
		$recommendedPhpVersion = '5.3.7';
		$currentPhpVersion = phpversion();
		if (version_compare($currentPhpVersion, $minimumPhpVersion) < 0) {
			$status = new ErrorStatus();
			$status->setTitle('PHP version too low');
			$status->setMessage(
				'Your PHP version ' . $currentPhpVersion . ' is too old. TYPO3 CMS does not run' .
				' with this version. Update to at least PHP ' . $recommendedPhpVersion
			);
		} elseif (version_compare($currentPhpVersion, $recommendedPhpVersion) < 0) {
			$status = new WarningStatus();
			$status->setTitle('PHP version below recommended version');
			$status->setMessage(
				'Your PHP version ' . $currentPhpVersion . ' is below the recommended version' .
				' ' . $recommendedPhpVersion . '. TYPO3 CMS will mostly run with your PHP' .
				' version, but it is not officially supported. Expect some problems,' .
				' and a performance penalty, monitor your system for errors and watch' .
				' out for an upgrade, soon.'
			);
		} else {
			$status = new OkStatus();
			$status->setTitle('PHP version is fine');
		}
		return $status;
	}

	/**
	 * Check maximum execution time
	 *
	 * @return ErrorStatus|WarningStatus|OkStatus
	 */
	protected function checkMaxExecutionTime() {
		$minimumMaximumExecutionTime = 30;
		$recommendedMaximumExecutionTime = 240;
		$currentMaximumExecutionTime = ini_get('max_execution_time');
		if ($currentMaximumExecutionTime == 0) {
			if (PHP_SAPI === 'cli') {
				$status = new OkStatus();
				$status->setTitle('Infinite PHP script execution time');
				$status->setMessage(
					'Maximum PHP script execution time is always set to infinite (0) in cli mode.' .
					' The setting used for web requests can not be checked from command line.'
				);
			} else {
				$status = new WarningStatus();
				$status->setTitle('Infinite PHP script execution time');
				$status->setMessage(
					'Your max_execution_time is set to 0 (infinite). While TYPO3 is fine' .
					' with this, you risk a denial-of-service of you system if for whatever' .
					' reason some script hangs in an infinite loop. You are usually on safe side ' .
					' if max_execution_time is reduced to ' . $recommendedMaximumExecutionTime
				);
			}
		} elseif ($currentMaximumExecutionTime < $minimumMaximumExecutionTime) {
			$status = new ErrorStatus();
			$status->setTitle('Low PHP script execution time');
			$status->setMessage(
				'Your max_execution_time is set to ' . $currentMaximumExecutionTime .
				'. Some expensive operation in TYPO3 can take longer than that. It is advised' .
				' to raise max_execution_time to ' . $recommendedMaximumExecutionTime
			);
		} elseif ($currentMaximumExecutionTime < $recommendedMaximumExecutionTime) {
			$status = new WarningStatus();
			$status->setTitle('Low PHP script execution time');
			$status->setMessage(
				'Your max_execution_time is set to ' . $currentMaximumExecutionTime .
				'. While TYPO3 often runs without problems with ' . $minimumMaximumExecutionTime .
				' it still may happen that script execution is stopped before finishing' .
				' calculations. You should monitor the system for messages in this area' .
				' and maybe raise the limit to ' . $recommendedMaximumExecutionTime . '.'
			);
		} else {
			$status = new OkStatus();
			$status->setTitle('Maximum PHP script execution equals ' . $recommendedMaximumExecutionTime . ' or more');
		}
		return $status;
	}

	/**
	 * Check for disabled functions
	 *
	 * @return ErrorStatus|OkStatus
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
			$status = new ErrorStatus();
			$status->setTitle('Some PHP functions disabled');
			$status->setMessage(
				'disable_functions=' . implode(' ', explode(',', $disabledFunctions)) . '. These function(s)' .
				' are disabled. TYPO3 uses some of those, so there might be trouble. TYPO3 is designed to use the' .
				' default set of PHP functions plus some common extensions. Possibly these functions are disabled' .
				' due to security considerations and most likely the list would include a function like' .
				' exec() which is used by TYPO3 at various places. Depending on which exact functions' .
				' are disabled, some parts of the system may just break without further notice.'
			);
		} elseif (strlen($disabledFunctions) > 0 && count($disabledFunctionsArray) === 0) {
			$status = new NoticeStatus();
			$status->setTitle('Some PHP functions currently disabled but OK');
			$status->setMessage(
				'disable_functions=' . implode(' ', explode(',', $disabledFunctions)) . '. These function(s)' .
				' are disabled. TYPO3 uses currently none of those, so you are good to go.'
			);
		} else {
			$status  = new OkStatus();
			$status->setTitle('No disabled PHP functions');
		}
		return $status;
	}

	/**
	 * Check if safe mode is enabled
	 *
	 * @return ErrorStatus|OkStatus
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
			$status = new ErrorStatus();
			$status->setTitle('PHP safe mode on');
			$status->setMessage(
				'safe_mode enabled. This is unsupported by TYPO3 CMS, it must be turned off.'
			);
		} else {
			$status = new OkStatus();
			$status->setTitle('PHP safe mode off');
		}
		return $status;
	}

	/**
	 * Check for doc_root ini setting
	 *
	 * @return NoticeStatus|OkStatus
	 */
	protected function checkDocRoot() {
		$docRootSetting = trim(ini_get('doc_root'));
		if (strlen($docRootSetting) > 0) {
			$status = new NoticeStatus();
			$status->setTitle('doc_root is set');
			$status->setMessage(
				'doc_root=' . $docRootSetting . ' PHP cannot execute scripts' .
				' outside this directory. This setting is used seldom and must correlate' .
				' with your actual document root. You might be in trouble if your' .
				' TYPO3 CMS core code is linked to some different location.' .
				' If that is a problem, the setting must be adapted.'
			);
		} else {
			$status = new OkStatus();
			$status->setTitle('PHP doc_root is not set');
		}
		return $status;
	}

	/**
	 * Check sql.safe_mode
	 *
	 * @return OkStatus|WarningStatus
	 */
	protected function checkSqlSafeMode() {
		$sqlSafeModeEnabled = FALSE;
		if (version_compare(phpversion(), '5.4', '<')) {
			$sqlSafeModeEnabled = filter_var(
				ini_get('sql.safe_mode'),
				FILTER_VALIDATE_BOOLEAN,
				array(FILTER_REQUIRE_SCALAR, FILTER_NULL_ON_FAILURE)
			);
		}
		if ($sqlSafeModeEnabled) {
			$status = new WarningStatus();
			$status->setTitle('sql.safe_mode is enabled');
			$status->setMessage(
				'This means that you can only connect to the database with a' .
				' username corresponding to the user of the webserver process' .
				' or file owner. Consult your ISP for information about this.' .
				' The owner of the current file is: ' . get_current_user()
			);
		} else {
			$status = new OkStatus();
			$status->setTitle('PHP sql.safe_mode is off');
		}
		return $status;
	}

	/**
	 * Check open_basedir
	 *
	 * @return NoticeStatus|OkStatus
	 */
	protected function checkOpenBaseDir() {
		$openBaseDirSetting = trim(ini_get('open_basedir'));
		if (strlen($openBaseDirSetting) > 0) {
			$status = new NoticeStatus();
			$status->setTitle('open_basedir set');
			$status->setMessage(
				'open_basedir = ' . ini_get('open_basedir') .
				' This restricts TYPO3 to open and include files only in this' .
				' path. Please make sure that this does not prevent TYPO3 from running,' .
				' if for example your TYPO3 CMS core is linked to a different directory' .
				' not included in this path.'
			);
		} else {
			$status = new OkStatus();
			$status->setTitle('PHP open_basedir is off');
		}
		return $status;
	}

	/**
	 * Check accessibility and functionality of OpenSSL
	 */
	protected function checkOpenSslInstalled() {
		if (extension_loaded('openssl')) {
			$testKey = @openssl_pkey_new();
			if (is_resource($testKey)) {
				openssl_free_key($testKey);
				$status = new OkStatus();
				$status->setTitle('OpenSSL installed properly');
			} else {
				$status = new ErrorStatus();
				$status->setTitle('OpenSSL extension not working');
				$status->setMessage(
					'Something went wrong while trying to create a new private key. ' .
					'Please check your OpenSSL integration to verify the extension is installed correctly.'
				);
			}
		} else {
			$status = new ErrorStatus();
			$status->setTitle('OpenSSL extension not loaded');
			$status->setMessage(
				'OpenSSL is an extension to encrypt/decrypt data between requests. ' .
				'TYPO3 CMS needs it to be able to store passwords encrypted to improve the security on database layer.'
			);
		}

		return $status;
	}

	/**
	 * Check enabled suhosin
	 *
	 * @return NoticeStatus|OkStatus
	 */
	protected function checkSuhosinLoaded() {
		if ($this->isSuhosinLoaded()) {
			$status = new OkStatus();
			$status->setTitle('PHP suhosin extension loaded');
		} else {
			$status = new NoticeStatus();
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
	 * @return ErrorStatus|InfoStatus|OkStatus
	 */
	protected function checkSuhosinRequestMaxVars() {
		$recommendedRequestMaxVars = 400;
		if ($this->isSuhosinLoaded()) {
			$currentRequestMaxVars = ini_get('suhosin.request.max_vars');
			if ($currentRequestMaxVars < $recommendedRequestMaxVars) {
				$status = new ErrorStatus();
				$status->setTitle('PHP suhosin.request.max_vars not high enough');
				$status->setMessage(
					'suhosin.request.max_vars=' . $currentRequestMaxVars . '. This setting' .
					' can lead to lost information if submitting big forms in TYPO3 CMS like' .
					' it is done in the install tool. It is heavily recommended to raise this' .
					' to at least ' . $recommendedRequestMaxVars
				);
			} else {
				$status = new OkStatus();
				$status->setTitle('PHP suhosin.request.max_vars ok');
			}
		} else {
			$status = new InfoStatus();
			$status->setTitle('Suhosin not loaded');
			$status->setMessage(
				'If enabling suhosin, suhosin.request.max_vars' .
				' should be set to at least ' . $recommendedRequestMaxVars
			);
		}
		return $status;
	}

	/**
	 * Check suhosin.post.max_vars
	 *
	 * @return ErrorStatus|InfoStatus|OkStatus
	 */
	protected function checkSuhosinPostMaxVars() {
		$recommendedPostMaxVars = 400;
		if ($this->isSuhosinLoaded()) {
			$currentPostMaxVars = ini_get('suhosin.post.max_vars');
			if ($currentPostMaxVars < $recommendedPostMaxVars) {
				$status = new ErrorStatus();
				$status->setTitle('PHP suhosin.post.max_vars not high enough');
				$status->setMessage(
					'suhosin.post.max_vars=' . $currentPostMaxVars . '. This setting' .
					' can lead to lost information if submitting big forms in TYPO3 CMS like' .
					' it is done in the install tool. It is heavily recommended to raise this' .
					' to at least ' . $recommendedPostMaxVars
				);
			} else {
				$status = new OkStatus();
				$status->setTitle('PHP suhosin.post.max_vars ok');
			}
		} else {
			$status = new InfoStatus();
			$status->setTitle('Suhosin not loaded');
			$status->setMessage(
				'If enabling suhosin, suhosin.post.max_vars' .
				' should be set to at least ' . $recommendedPostMaxVars
			);
		}
		return $status;
	}

	/**
	 * Check suhosin.get.max_value_length
	 *
	 * @return ErrorStatus|InfoStatus|OkStatus
	 */
	protected function checkSuhosinGetMaxValueLength() {
		$recommendedGetMaxValueLength = 2000;
		if ($this->isSuhosinLoaded()) {
			$currentGetMaxValueLength = ini_get('suhosin.get.max_value_length');
			if ($currentGetMaxValueLength < $recommendedGetMaxValueLength) {
				$status = new ErrorStatus();
				$status->setTitle('PHP suhosin.get.max_value_length not high enough');
				$status->setMessage(
					'suhosin.get.max_value_length=' . $currentGetMaxValueLength . '. This setting' .
					' can lead to lost information if submitting big forms in TYPO3 CMS like' .
					' it is done in the install tool. It is heavily recommended to raise this' .
					' to at least ' . $recommendedGetMaxValueLength
				);
			} else {
				$status = new OkStatus();
				$status->setTitle('PHP suhosin.get.max_value_length ok');
			}
		} else {
			$status = new InfoStatus();
			$status->setTitle('Suhosin not loaded');
			$status->setMessage(
				'If enabling suhosin, suhosin.get.max_value_length' .
				' should be set to at least ' . $recommendedGetMaxValueLength
			);
		}
		return $status;
	}

	/**
	 * Check suhosin.executor.include.whitelist contains phar
	 *
	 * @return NoticeStatus|InfoStatus|OkStatus
	 */
	protected function checkSuhosinExecutorIncludeWhiteListContainsPhar() {
		if ($this->isSuhosinLoaded()) {
			$currentWhiteListArray = $this->trimExplode(' ', ini_get('suhosin.executor.include.whitelist'));
			if (!in_array('phar', $currentWhiteListArray)) {
				$status = new NoticeStatus();
				$status->setTitle('PHP suhosin.executor.include.whitelist does not contain phar');
				$status->setMessage(
					'suhosin.executor.include.whitelist= ' . implode(' ', $currentWhiteListArray) . '. phar' .
					' is currently not a hard requirement of TYPO3 CMS but is nice to have and a possible requirement' .
					' in future versions. A useful setting is "suhosin.executor.include.whitelist = phar vfs"'
				);
			} else {
				$status = new OkStatus();
				$status->setTitle('PHP suhosin.executor.include.whitelist contains phar');
			}
		} else {
			$status = new InfoStatus();
			$status->setTitle('Suhosin not loaded');
			$status->setMessage(
				'If enabling suhosin, a useful setting is "suhosin.executor.include.whitelist = phar vfs"'
			);
		}
		return $status;
	}

	/**
	 * Check suhosin.executor.include.whitelist contains vfs
	 *
	 * @return NoticeStatus|InfoStatus|OkStatus
	 */
	protected function checkSuhosinExecutorIncludeWhiteListContainsVfs() {
		if ($this->isSuhosinLoaded()) {
			$currentWhiteListArray = $this->trimExplode(' ', ini_get('suhosin.executor.include.whitelist'));
			if (!in_array('vfs', $currentWhiteListArray)) {
				$status = new WarningStatus();
				$status->setTitle('PHP suhosin.executor.include.whitelist does not contain vfs');
				$status->setMessage(
					'suhosin.executor.include.whitelist= ' . implode(' ', $currentWhiteListArray) . '. vfs' .
					' is currently not a hard requirement of TYPO3 CMS but tons of unit tests rely on it.' .
					' Furthermore, vfs is likely a base for an additional compatibilyt layer in the future.' .
					' A useful setting is "suhosin.executor.include.whitelist = phar vfs"'
				);
			} else {
				$status = new OkStatus();
				$status->setTitle('PHP suhosin.executor.include.whitelist contains vfs');
			}
		} else {
			$status = new InfoStatus();
			$status->setTitle('Suhosin not loaded');
			$status->setMessage(
				'If enabling suhosin, a useful setting is "suhosin.executor.include.whitelist = phar vfs"'
			);
		}
		return $status;
	}

	/**
	 * Check if some opcode cache is loaded
	 *
	 * @return WarningStatus|OkStatus
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
			$status = new OkStatus();
			$status->setTitle('A PHP opcode cache is loaded');
		} else {
			$status = new WarningStatus();
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
	 * @return ErrorStatus|OkStatus
	 */
	protected function checkReflectionDocComment() {
		$testReflection = new \ReflectionMethod(get_class($this), __FUNCTION__);
		if (strlen($testReflection->getDocComment()) === 0) {
			$status = new ErrorStatus();
			$status->setTitle('Doc comment reflection broken');
			$status->setMessage(
				'TYPO3 CMS core extensions like extbase and fluid heavily rely on method' .
				' comment parsing to fetch annotations and add magic according to them.' .
				' This does not work in the current environment and will lead to a lot of' .
				' broken extensions. The PHP extension eaccelerator is known to break this if' .
				' it is compiled without --with-eaccelerator-doc-comment-inclusion flag.' .
				' This compile flag must be given, otherwise TYPO3 CMS is no fun.'
			);
		} else {
			$status = new OkStatus();
			$status->setTitle('Document comment reflection works');
		}
		return $status;
	}

	/**
	 * Checks thread stack size if on windows with apache
	 *
	 * @return WarningStatus|OkStatus
	 */
	protected function checkWindowsApacheThreadStackSize() {
		if (
			$this->isWindowsOs()
			&& substr($_SERVER['SERVER_SOFTWARE'], 0, 6) === 'Apache'
		) {
			$status = new WarningStatus();
			$status->setTitle('Windows apache thread stack size');
			$status->setMessage(
				'This current value can not be checked by the system, so please ignore this warning if it' .
				' is already taken care off: Fluid uses complex regular expressions which require a lot' .
				' of stack space during the first processing.' .
				' On Windows the default stack size for Apache is a lot smaller than on unix.' .
				' You can increase the size to 8MB (default on unix) by adding to the httpd.conf:' .
				' ThreadStackSize 8388608. Restart Apache after this change.'
			);
		} else {
			$status = new OkStatus();
			$status->setTitle('ThreadStackSize is not an issue on unix systems');
		}
		return $status;
	}

	/**
	 * Check if a specific required PHP extension is loaded
	 *
	 * @param string $extension
	 * @return ErrorStatus|OkStatus
	 */
	protected function checkRequiredPhpExtension($extension) {
		if (!extension_loaded($extension)) {
			$status = new ErrorStatus();
			$status->setTitle('PHP extension ' . $extension . ' not loaded');
			$status->setMessage(
				'TYPO3 CMS uses PHP extension ' . $extension . ' but it is not loaded' .
				' in your environment. Change your environment to provide this extension.'
			);
		} else {
			$status = new OkStatus();
			$status->setTitle('PHP extension ' . $extension . ' loaded');
		}
		return $status;
	}

	/**
	 * Check smtp settings
	 *
	 * @return ErrorStatus|OkStatus|WarningStatus
	 */
	protected function checkMailCapabilities() {
		if ($this->isWindowsOs()) {
			$smtpIni = ini_get('SMTP');
			$brokenSmtp = FALSE;
			$smtpIpAddress = '';
			if (!$this->isValidIp($smtpIni)) {
				if (!$this->isValidIp(@gethostbyname($smtpIni))) {
					$brokenSmtp = TRUE;
				} else {
					$smtpIpAddress = @gethostbyname($smtpIni);
				}
			} else {
				$smtpIpAddress = $smtpIni;
			}

			$smtpPortIni =  intval(ini_get('smtp_port'));
			$brokenSmtpPort = FALSE;
			if (intval($smtpPortIni) < 1 || intval($smtpPortIni) > 65535) {
				$brokenSmtpPort = TRUE;
			}

			if ($brokenSmtp || $brokenSmtpPort) {
				$status = new ErrorStatus();
				$status->setTitle('Mail configuration is not set correctly');
				$status->setMessage(
					'PHP mail() function requires SMTP and smtp_port to have' .
					' correct values on Windows. If installation is completed,' .
					' the mail system can be tested in the install tool.'
				);
			} elseif ($smtpIpAddress === '127.0.0.1' || $smtpIpAddress === '::1') {
				$status = new WarningStatus();
				$status->setTitle('Mail is configured, potential problem exists');
				$status->setMessage(
					'smtp=' . $smtpIni . ' - This server! Are you sure it runs SMTP server?' .
					' If installation is completed, the mail system can be tested in the install tool.'
				);
			} else {
				$status = new OkStatus();
				$status->setTitle('Mail is configured');
				$status->setMessage(
					'smtp=' . $smtpIni . ', smtp_port=' . ini_get('smtp_port') . '.' .
					' Values for mail setup look ok. If installation is completed,' .
					' the mail system can be tested in the install tool. '
				);
			}
		} elseif (!ini_get('sendmail_path')) {
			$status = new WarningStatus();
			$status->setTitle('PHP sendmail_path not defined');
			$status->setMessage(
				'This may be critical to TYPO3\'s use of the mail() function.' .
				' Your setup is rather uncommon. If installation is completed, the' .
				' mail system can be tested in the install tool.'
			);
		} else {
			list($mailBinary) = explode(' ', ini_get('sendmail_path'));
			if (!@is_executable($mailBinary)) {
				$status = new ErrorStatus();
				$status->setTitle('Mail program not found or not executable');
				$status->setMessage(
					'sendmail_path = ' . ini_get('sendmail_path') .
					' This may be critical to TYPO3\'s use of the mail() function. Please' .
					' be sure that the mail() function in your php-installation works.  If' .
					' installation is completed, the mail system can be tested in the install tool.'
				);
			} else {
				$status = new OkStatus();
				$status->setTitle('PHP sendmail path given');
				$status->setMessage(
					'sendmail_path = ' . ini_get('sendmail_path') . '.' .
					' This setting is crucial for TYPO3\'s use of the mail() function. The' .
					' current value looks fine. The mail system can be tested in the' .
					' install tool if the installation is completed'
				);
			}
		}
		return $status;
	}

	/**
	 * Check imagecreatetruecolor to verify gdlib works as expected
	 *
	 * @return ErrorStatus|OkStatus
	 */
	protected function checkGdLibTrueColorSupport() {
		if (function_exists('imagecreatetruecolor')) {
			$imageResource = @imagecreatetruecolor(50, 100);
			if (is_resource($imageResource)) {
				imagedestroy($imageResource);
				$status = new OkStatus();
				$status->setTitle('PHP GD library true color works');
			} else {
				$status = new ErrorStatus();
				$status->setTitle('PHP GD library true color support broken');
				$status->setMessage(
					'GD is loaded, but calling a imagecreatetruecolor returned an error.' .
					' This must be fixed, TYPO3 CMS won\'t work well otherwise'
				);
			}
		} else {
			$status = new ErrorStatus();
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
	 * @return ErrorStatus|OkStatus
	 */
	protected function checkGdLibGifSupport() {
		if (
			function_exists('imagecreatefromgif')
			&& function_exists('imagegif')
			&& (imagetypes() & IMG_GIF)
		) {
			$imageResource = @imagecreatefromgif(__DIR__ . '/TestImages/jesus.gif');
			if (is_resource($imageResource)) {
				imagedestroy($imageResource);
				$status = new OkStatus();
				$status->setTitle('PHP GD library has gif support');
			} else {
				$status = new ErrorStatus();
				$status->setTitle('PHP GD library gif support broken');
				$status->setMessage(
					'GD is loaded, but calling a gif related message gives errors.' .
					' This must be fixed, TYPO3 CMS won\'t work well otherwise'
				);
			}
		} else {
			$status = new ErrorStatus();
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
	 * @return ErrorStatus|OkStatus
	 */
	protected function checkGdLibJpgSupport() {
		if (
			function_exists('imagecreatefromjpeg')
			&& function_exists('imagejpeg')
			&& (imagetypes() & IMG_JPG)
		) {
			$status = new OkStatus();
			$status->setTitle('PHP GD library has jpg support');
		} else {
			$status= new ErrorStatus();
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
	 * @return ErrorStatus|OkStatus
	 */
	protected function checkGdLibPngSupport() {
		if (
			function_exists('imagecreatefrompng')
			&& function_exists('imagepng')
			&& (imagetypes() & IMG_PNG)
		) {
			$imageResource = @imagecreatefrompng(__DIR__ . '/TestImages/jesus.png');
			if (is_resource($imageResource)) {
				imagedestroy($imageResource);
				$status = new OkStatus();
				$status->setTitle('PHP GD library has png support');
			} else {
				$status = new ErrorStatus();
				$status->setTitle('PHP GD library png support broken');
				$status->setMessage(
					'GD is compiled with png support, but a test call fails.' .
					' Check your environment and fix it, png in GD lib is important' .
					' for TYPO3 CMS to work properly.'
				);
			}
		} else {
			$status = new ErrorStatus();
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
	 * @return ErrorStatus|OkStatus
	 */
	protected function checkGdLibFreeTypeSupport() {
		if (function_exists('imagettftext')) {
			$status = new OkStatus();
			$status->setTitle('PHP GD library has freetype font support');
			$status->setMessage(
				'There is a difference between the font size setting the GD' .
				' library should be feeded with. If installation is completed' .
				' a test in the install tool helps to find out the value you need'
			);
		} else {
			$status = new ErrorStatus();
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
	 * Check php magic quotes
	 *
	 * @return OkStatus|WarningStatus
	 */
	protected function checkPhpMagicQuotes() {
		if (get_magic_quotes_gpc()) {
			$status = new WarningStatus();
			$status->setTitle('PHP magic quotes on');
			$status->setMessage(
				'PHP ini setting magic_quotes_gpc in on. The setting is deprecated since PHP 5.3.' .
				' You are advised to set it to "Off" until it gets completely removed.'
			);
		} else {
			$status = new OkStatus();
			$status->setTitle('PHP magic quotes off');
		}
		return $status;
	}

	/**
	 * Check register globals
	 *
	 * @return ErrorStatus|OkStatus
	 */
	protected function checkRegisterGlobals() {
		$registerGlobalsEnabled = filter_var(
			ini_get('register_globals'),
			FILTER_VALIDATE_BOOLEAN,
			array(FILTER_REQUIRE_SCALAR, FILTER_NULL_ON_FAILURE)
		);
		if ($registerGlobalsEnabled === TRUE) {
			$status = new ErrorStatus();
			$status->setTitle('PHP register globals on');
			$status->setMessage(
				'TYPO3 requires PHP setting "register_globals" set to off.' .
				' This ancient PHP setting is a big security problem and should' .
				' never be enabled.'
			);
		} else {
			$status = new OkStatus();
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
