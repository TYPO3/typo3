<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Parent class for "Services" classes
 *
 * $Id$
 * TODO: temp files are not removed
 *
 * @author	René Fritz <r.fritz@colorcube.de>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  125: class t3lib_svbase
 *
 *			  SECTION: Get service meta information
 *  191:	 function getServiceInfo()
 *  201:	 function getServiceKey()
 *  211:	 function getServiceTitle()
 *  224:	 function getServiceOption($optionName, $defaultValue='', $includeDefaultConfig=TRUE)
 *
 *			  SECTION: Error handling
 *  259:	 function devLog($msg, $severity=0, $dataVar=FALSE)
 *  273:	 function errorPush($errNum=T3_ERR_SV_GENERAL, $errMsg='Unspecified error occured')
 *  288:	 function errorPull()
 *  300:	 function getLastError()
 *  315:	 function getLastErrorMsg()
 *  330:	 function getErrorMsgArray()
 *  348:	 function getLastErrorArray()
 *  357:	 function resetErrors()
 *
 *			  SECTION: General service functions
 *  377:	 function checkExec($progList)
 *  401:	 function deactivateService()
 *
 *			  SECTION: IO tools
 *  427:	 function checkInputFile ($absFile)
 *  448:	 function readFile ($absFile, $length=0)
 *  473:	 function writeFile ($content, $absFile='')
 *  499:	 function tempFile ($filePrefix)
 *  517:	 function registerTempFile ($absFile)
 *  527:	 function unlinkTempFiles ()
 *
 *			  SECTION: IO input
 *  549:	 function setInput ($content, $type='')
 *  563:	 function setInputFile ($absFile, $type='')
 *  576:	 function getInput ()
 *  591:	 function getInputFile ($createFile='')
 *
 *			  SECTION: IO output
 *  616:	 function setOutputFile ($absFile)
 *  626:	 function getOutput ()
 *  640:	 function getOutputFile ($absFile='')
 *
 *			  SECTION: Service implementation
 *  664:	 function init()
 *  688:	 function reset()
 *  703:	 function __destruct()
 *
 * TOTAL FUNCTIONS: 30
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


define ('T3_ERR_SV_GENERAL', -1); // General error - something went wrong
define ('T3_ERR_SV_NOT_AVAIL', -2); // During execution it showed that the service is not available and should be ignored. The service itself should call $this->setNonAvailable()
define ('T3_ERR_SV_WRONG_SUBTYPE', -3); // passed subtype is not possible with this service
define ('T3_ERR_SV_NO_INPUT', -4); // passed subtype is not possible with this service


define ('T3_ERR_SV_FILE_NOT_FOUND', -20); // File not found which the service should process
define ('T3_ERR_SV_FILE_READ', -21); // File not readable
define ('T3_ERR_SV_FILE_WRITE', -22); // File not writable

define ('T3_ERR_SV_PROG_NOT_FOUND', -40); // passed subtype is not possible with this service
define ('T3_ERR_SV_PROG_FAILED', -41); // passed subtype is not possible with this service

// define ('T3_ERR_SV_serviceType_myerr, -100); // All errors with prefix T3_ERR_SV_[serviceType]_ and lower than -99 are service type dependent error


/**
 * Parent class for "Services" classes
 *
 * @author	René Fritz <r.fritz@colorcube.de>
 * @package TYPO3
 * @subpackage t3lib
 */
abstract class t3lib_svbase {

	/**
	 * @var array service description array
	 */
	var $info = array();

	/**
	 * @var array error stack
	 */
	var $error = array();

	/**
	 * @var bool Defines if debug messages should be written with t3lib_div::devLog
	 */
	var $writeDevLog = FALSE;


	/**
	 * @var string The output content. That's what the services produced as result.
	 */
	var $out = '';

	/**
	 * @var string The file that should be processed.
	 */
	var $inputFile = '';

	/**
	 * @var string The content that should be processed.
	 */
	var $inputContent = '';

	/**
	 * @var string The type of the input content (or file). Might be the same as the service subtypes.
	 */
	var $inputType = '';

	/**
	 * @var string The file where the output should be written to.
	 */
	var $outputFile = '';


	/**
	 * Temporary files which have to be deleted
	 *
	 * @private
	 */
	var $tempFiles = array();

	/** @var string Prefix for temporary files */
	protected $prefixId = '';

	/***************************************
	 *
	 *	 Get service meta information
	 *
	 ***************************************/


	/**
	 * Returns internal information array for service
	 *
	 * @return	array		service description array
	 */
	function getServiceInfo() {
		return $this->info;
	}


	/**
	 * Returns the service key of the service
	 *
	 * @return	string		service key
	 */
	function getServiceKey() {
		return $this->info['serviceKey'];
	}


	/**
	 * Returns the title of the service
	 *
	 * @return	string		service title
	 */
	function getServiceTitle() {
		return $this->info['title'];
	}


	/**
	 * Returns service configuration values from the $TYPO3_CONF_VARS['SVCONF'] array
	 *
	 * @param	string		Name of the config option
	 * @param	mixed		Default configuration if no special config is available
	 * @param	bool		If set the 'default' config will be returned if no special config for this service is available (default: true)
	 * @return	mixed		configuration value for the service
	 */
	function getServiceOption($optionName, $defaultValue = '', $includeDefaultConfig = TRUE) {
		$config = NULL;

		$svOptions = $GLOBALS['TYPO3_CONF_VARS']['SVCONF'][$this->info['serviceType']];

		if (isset($svOptions[$this->info['serviceKey']][$optionName])) {
			$config = $svOptions[$this->info['serviceKey']][$optionName];
		} elseif ($includeDefaultConfig && isset($svOptions['default'][$optionName])) {
			$config = $svOptions['default'][$optionName];
		}
		if (!isset($config)) {
			$config = $defaultValue;
		}
		return $config;
	}


	/***************************************
	 *
	 *	 Error handling
	 *
	 ***************************************/


	/**
	 * Logs debug messages to t3lib_div::devLog()
	 *
	 * @param	string		Debug message
	 * @param	integer		Severity: 0 is info, 1 is notice, 2 is warning, 3 is fatal error, -1 is "OK" message
	 * @param	array|bool	Additional data you want to pass to the logger.
	 * @return	void
	 */
	function devLog($msg, $severity = 0, $dataVar = FALSE) {
		if ($this->writeDevLog) {
			t3lib_div::devLog($msg, $this->info['serviceKey'], $severity, $dataVar);
		}
	}


	/**
	 * Puts an error on the error stack. Calling without parameter adds a general error.
	 *
	 * @param	integer		error number (see T3_ERR_SV_* constants)
	 * @param	string		error message
	 * @return	void
	 */
	function errorPush($errNum = T3_ERR_SV_GENERAL, $errMsg = 'Unspecified error occured') {
		array_push($this->error, array('nr' => $errNum, 'msg' => $errMsg));

		if (is_object($GLOBALS['TT'])) {
			$GLOBALS['TT']->setTSlogMessage($errMsg, 2);
		}

	}


	/**
	 * Removes the last error from the error stack.
	 *
	 * @return	void
	 */
	function errorPull() {
		array_pop($this->error);

		// pop for $GLOBALS['TT']->setTSlogMessage is not supported
	}


	/**
	 * Returns the last error number from the error stack.
	 *
	 * @return	int|bool		error number (or TRUE if no error)
	 */
	function getLastError() {
		$lastError = TRUE; // means all is ok - no error
		if (count($this->error)) {
			$error = end($this->error);
			$lastError = $error['nr'];
		}
		return $lastError;
	}


	/**
	 * Returns the last message from the error stack.
	 *
	 * @return	string		error message
	 */
	function getLastErrorMsg() {
		$lastErrorMessage = '';
		if (count($this->error)) {
			$error = end($this->error);
			$lastErrorMessage = $error['msg'];
		}
		return $lastErrorMessage;
	}


	/**
	 * Returns all error messages as array.
	 *
	 * @return	array		error messages
	 */
	function getErrorMsgArray() {
		$errArr = array();

		if (count($this->error)) {
			foreach ($this->error as $error) {
				$errArr[] = $error['msg'];
			}
		}
		return $errArr;
	}


	/**
	 * Returns the last array from the error stack.
	 *
	 * @return	array		error nr and message
	 */
	function getLastErrorArray() {
		return end($this->error);
	}

	/**
	 * Reset the error stack.
	 *
	 * @return	void
	 */
	function resetErrors() {
		$this->error = array();
	}


	/***************************************
	 *
	 *	 General service functions
	 *
	 ***************************************/


	/**
	 * check the availability of external programs
	 *
	 * @param	string		comma list of programs 'perl,python,pdftotext'
	 * @return	boolean		return FALSE if one program was not found
	 */
	function checkExec($progList) {
		$ret = TRUE;

		$progList = t3lib_div::trimExplode(',', $progList, 1);
		foreach ($progList as $prog) {
			if (!t3lib_exec::checkCommand($prog)) {
					// program not found
				$this->errorPush(T3_ERR_SV_PROG_NOT_FOUND, 'External program not found: ' . $prog);
				$ret = FALSE;
			}
		}
		return $ret;
	}


	/**
	 * Deactivate the service. Use this if the service fails at runtime and will not be available.
	 *
	 * @return	void
	 */
	function deactivateService() {
		t3lib_extMgm::deactivateService($this->info['serviceType'], $this->info['serviceKey']);
	}


	/***************************************
	 *
	 *	 IO tools
	 *
	 ***************************************/


	/**
	 * Check if a file exists and is readable.
	 *
	 * @param	string		File name with absolute path.
	 * @return	string|bool	File name or FALSE.
	 */
	function checkInputFile($absFile) {
		$checkResult = FALSE;
		if (t3lib_div::isAllowedAbsPath($absFile) && @is_file($absFile)) {
			if (@is_readable($absFile)) {
				$checkResult = $absFile;
			} else {
				$this->errorPush(T3_ERR_SV_FILE_READ, 'File is not readable: ' . $absFile);
			}
		} else {
			$this->errorPush(T3_ERR_SV_FILE_NOT_FOUND, 'File not found: ' . $absFile);
		}
		return $checkResult;
	}


	/**
	 * Read content from a file a file.
	 *
	 * @param	string		File name to read from.
	 * @param	integer		Maximum length to read. If empty the whole file will be read.
	 * @return	string|bool	$content or FALSE
	 */
	function readFile($absFile, $length = 0) {
		$out = FALSE;

		if ($this->checkInputFile($absFile)) {
			$out = file_get_contents($absFile);
			if ($out === FALSE) {
				$this->errorPush(T3_ERR_SV_FILE_READ, 'Can not read from file: ' . $absFile);
			}
		}
		return $out;
	}


	/**
	 * Write content to a file.
	 *
	 * @param	string		Content to write to the file
	 * @param	string		File name to write into. If empty a temp file will be created.
	 * @return	string|bool	File name or FALSE
	 */
	function writeFile($content, $absFile = '') {
		if (!$absFile) {
			$absFile = $this->tempFile($this->prefixId);
		}

		if ($absFile && t3lib_div::isAllowedAbsPath($absFile)) {
			if ($fd = @fopen($absFile, 'wb')) {
				@fwrite($fd, $content);
				@fclose($fd);
			} else {
				$this->errorPush(T3_ERR_SV_FILE_WRITE, 'Can not write to file: ' . $absFile);
				$absFile = FALSE;
			}
		}

		return $absFile;
	}

	/**
	 * Create a temporary file.
	 *
	 * @param	string		File prefix.
	 * @return	string|bool	File name or FALSE
	 */
	function tempFile($filePrefix) {
		$absFile = t3lib_div::tempnam($filePrefix);
		if ($absFile) {
			$ret = $absFile;
			$this->registerTempFile($absFile);
		} else {
			$ret = FALSE;
			$this->errorPush(T3_ERR_SV_FILE_WRITE, 'Can not create temp file.');
		}
		return $ret;
	}

	/**
	 * Register file which should be deleted afterwards.
	 *
	 * @param	string		File name with absolute path.
	 * @return	void
	 */
	function registerTempFile($absFile) {
		$this->tempFiles[] = $absFile;
	}

	/**
	 * Delete registered temporary files.
	 *
	 * @param	string		File name with absolute path.
	 * @return	void
	 */
	function unlinkTempFiles() {
		foreach ($this->tempFiles as $absFile) {
			t3lib_div::unlink_tempfile($absFile);
		}
		$this->tempFiles = array();
	}


	/***************************************
	 *
	 *	 IO input
	 *
	 ***************************************/


	/**
	 * Set the input content for service processing.
	 *
	 * @param	mixed		Input content (going into ->inputContent)
	 * @param	string		The type of the input content (or file). Might be the same as the service subtypes.
	 * @return	void
	 */
	function setInput($content, $type = '') {
		$this->inputContent = $content;
		$this->inputFile = '';
		$this->inputType = $type;
	}


	/**
	 * Set the input file name for service processing.
	 *
	 * @param	string		file name
	 * @param	string		The type of the input content (or file). Might be the same as the service subtypes.
	 * @return	void
	 */
	function setInputFile($absFile, $type = '') {
		$this->inputContent = '';
		$this->inputFile = $absFile;
		$this->inputType = $type;
	}


	/**
	 * Get the input content.
	 * Will be read from input file if needed. (That is if ->inputContent is empty and ->inputFile is not)
	 *
	 * @return	mixed
	 */
	function getInput() {
		if ($this->inputContent == '') {
			$this->inputContent = $this->readFile($this->inputFile);
		}
		return $this->inputContent;
	}


	/**
	 * Get the input file name.
	 * If the content was set by setContent a file will be created.
	 *
	 * @param	string		File name. If empty a temp file will be created.
	 * @return	string		File name or FALSE if no input or file error.
	 */
	function getInputFile($createFile = '') {
		if ($this->inputFile) {
			$this->inputFile = $this->checkInputFile($this->inputFile);
		} elseif ($this->inputContent) {
			$this->inputFile = $this->writeFile($this->inputContent, $createFile);
		}
		return $this->inputFile;
	}


	/***************************************
	 *
	 *	 IO output
	 *
	 ***************************************/


	/**
	 * Set the output file name.
	 *
	 * @param	string		file name
	 * @return	void
	 */
	function setOutputFile($absFile) {
		$this->outputFile = $absFile;
	}


	/**
	 * Get the output content.
	 *
	 * @return	mixed
	 */
	function getOutput() {
		if ($this->outputFile) {
			$this->out = $this->readFile($this->outputFile);
		}
		return $this->out;
	}


	/**
	 * Get the output file name. If no output file is set, the ->out buffer is written to the file given by input parameter filename
	 *
	 * @param	string		Absolute filename to write to
	 * @return	mixed
	 */
	function getOutputFile($absFile = '') {
		if (!$this->outputFile) {
			$this->outputFile = $this->writeFile($this->out, $absFile);
		}
		return $this->outputFile;
	}


	/***************************************
	 *
	 *	 Service implementation
	 *
	 ***************************************/

	/**
	 * Initialization of the service.
	 *
	 * The class have to do a strict check if the service is available.
	 * example: check if the perl interpreter is available which is needed to run an extern perl script.
	 *
	 * @return	boolean		TRUE if the service is available
	 */
	function init() {
			// do not work :-(  but will not hurt
			// use it as inspiration for a service based on this class
		register_shutdown_function(array(&$this, '__destruct'));
			// look in makeInstanceService()

		$this->reset();

			// check for external programs which are defined by $info['exec']
		if (trim($this->info['exec'])) {
			if (!$this->checkExec($this->info['exec'])) {
				// nothing todo here or?
			}
		}

		return ($this->getLastError() === TRUE);
	}


	/**
	 * Resets the service.
	 * Will be called by init(). Should be used before every use if a service instance is used multiple times.
	 *
	 * @return	void
	 */
	function reset() {
		$this->unlinkTempFiles();
		$this->resetErrors();
		$this->out = '';
		$this->inputFile = '';
		$this->inputContent = '';
		$this->inputType = '';
		$this->outputFile = '';
	}

	/**
	 * Clean up the service.
	 * Child classes should explicitly call parent::__destruct() in their destructors for this to work
	 *
	 * @return	void
	 */
	function __destruct() {
		$this->unlinkTempFiles();
	}

}

// XCLASS handling does not make sense, because this class is always extended by the service classes..
?>