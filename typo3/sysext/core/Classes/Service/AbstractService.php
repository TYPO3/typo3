<?php
namespace TYPO3\CMS\Core\Service;

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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Security\BlockSerializationTrait;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Parent class for "Services" classes
 */
abstract class AbstractService implements LoggerAwareInterface
{
    use BlockSerializationTrait;
    use LoggerAwareTrait;

    // General error - something went wrong
    const ERROR_GENERAL = -1;

    // During execution it showed that the service is not available and
    // should be ignored. The service itself should call $this->setNonAvailable()
    const ERROR_SERVICE_NOT_AVAILABLE = -2;

    // Passed subtype is not possible with this service
    const ERROR_WRONG_SUBTYPE = -3;

    // Passed subtype is not possible with this service
    const ERROR_NO_INPUT = -4;

    // File not found which the service should process
    const ERROR_FILE_NOT_FOUND = -20;

    // File not readable
    const ERROR_FILE_NOT_READABLE = -21;

    // File not writable
    // @todo: check writeable vs. writable
    const ERROR_FILE_NOT_WRITEABLE = -22;

    // Passed subtype is not possible with this service
    const ERROR_PROGRAM_NOT_FOUND = -40;

    // Passed subtype is not possible with this service
    const ERROR_PROGRAM_FAILED = -41;
    /**
     * @var array service description array
     */
    public $info = [];

    /**
     * @var array error stack
     */
    public $error = [];

    /**
     * @var string The output content. That's what the services produced as result.
     */
    public $out = '';

    /**
     * @var string The file that should be processed.
     */
    public $inputFile = '';

    /**
     * @var string The content that should be processed.
     */
    public $inputContent = '';

    /**
     * @var string The type of the input content (or file). Might be the same as the service subtypes.
     */
    public $inputType = '';

    /**
     * @var string The file where the output should be written to.
     */
    public $outputFile = '';

    /**
     * Temporary files which have to be deleted
     *
     * @private
     * @var array
     */
    public $tempFiles = [];

    /**
     * @var array list of registered shutdown functions; should be used to prevent registering the same function multiple times
     */
    protected $shutdownRegistry = [];

    /**
     * @var string Prefix for temporary files
     */
    protected $prefixId = '';

    /***************************************
     *
     *	 Get service meta information
     *
     ***************************************/
    /**
     * Returns internal information array for service
     *
     * @return array Service description array
     */
    public function getServiceInfo()
    {
        return $this->info;
    }

    /**
     * Returns the service key of the service
     *
     * @return string Service key
     */
    public function getServiceKey()
    {
        return $this->info['serviceKey'];
    }

    /**
     * Returns the title of the service
     *
     * @return string Service title
     */
    public function getServiceTitle()
    {
        return $this->info['title'];
    }

    /**
     * Returns service configuration values from the $TYPO3_CONF_VARS['SVCONF'] array
     *
     * @param string $optionName Name of the config option
     * @param mixed $defaultValue Default configuration if no special config is available
     * @param bool $includeDefaultConfig If set the 'default' config will be returned if no special config for this service is available (default: TRUE)
     * @return mixed Configuration value for the service
     */
    public function getServiceOption($optionName, $defaultValue = '', $includeDefaultConfig = true)
    {
        $config = null;
        $serviceType = $this->info['serviceType'] ?? '';
        $serviceKey = $this->info['serviceKey'] ?? '';
        $svOptions = $GLOBALS['TYPO3_CONF_VARS']['SVCONF'][$serviceType] ?? [];
        if (isset($svOptions[$serviceKey][$optionName])) {
            $config = $svOptions[$serviceKey][$optionName];
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
     * Logs debug messages to the Logging API
     *
     * @param string $msg Debug message
     * @param int $severity Severity: 0 is info, 1 is notice, 2 is warning, 3 is fatal error, -1 is "OK" message
     * @param array|bool $dataVar additional data you want to pass to the logger.
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function devLog($msg, $severity = 0, $dataVar = false)
    {
        trigger_error('AbstractService->devLog() will be removed with TYPO3 v10.0.', E_USER_DEPRECATED);
        $this->logger->debug($this->info['serviceKey'] . ': ' . $msg, (array)$dataVar);
    }

    /**
     * Puts an error on the error stack. Calling without parameter adds a general error.
     *
     * @param int $errNum Error number (see class constants)
     * @param string $errMsg Error message
     */
    public function errorPush($errNum = self::ERROR_GENERAL, $errMsg = 'Unspecified error occurred')
    {
        $this->error[] = ['nr' => $errNum, 'msg' => $errMsg];
        /** @var \TYPO3\CMS\Core\TimeTracker\TimeTracker $timeTracker */
        $timeTracker = GeneralUtility::makeInstance(TimeTracker::class);
        $timeTracker->setTSlogMessage($errMsg, 2);
    }

    /**
     * Removes the last error from the error stack.
     */
    public function errorPull()
    {
        array_pop($this->error);
    }

    /**
     * Returns the last error number from the error stack.
     *
     * @return int|bool Error number (or TRUE if no error)
     */
    public function getLastError()
    {
        // Means all is ok - no error
        $lastError = true;
        if (!empty($this->error)) {
            $error = end($this->error);
            $lastError = $error['nr'];
        }
        return $lastError;
    }

    /**
     * Returns the last message from the error stack.
     *
     * @return string Error message
     */
    public function getLastErrorMsg()
    {
        $lastErrorMessage = '';
        if (!empty($this->error)) {
            $error = end($this->error);
            $lastErrorMessage = $error['msg'];
        }
        return $lastErrorMessage;
    }

    /**
     * Returns all error messages as array.
     *
     * @return array Error messages
     */
    public function getErrorMsgArray()
    {
        $errArr = [];
        if (!empty($this->error)) {
            foreach ($this->error as $error) {
                $errArr[] = $error['msg'];
            }
        }
        return $errArr;
    }

    /**
     * Returns the last array from the error stack.
     *
     * @return array Error number and message
     */
    public function getLastErrorArray()
    {
        return end($this->error);
    }

    /**
     * Reset the error stack.
     */
    public function resetErrors()
    {
        $this->error = [];
    }

    /***************************************
     *
     *	 General service functions
     *
     ***************************************/
    /**
     * check the availability of external programs
     *
     * @param string $progList Comma list of programs 'perl,python,pdftotext'
     * @return bool Return FALSE if one program was not found
     */
    public function checkExec($progList)
    {
        $ret = true;
        $progList = GeneralUtility::trimExplode(',', $progList, true);
        foreach ($progList as $prog) {
            if (!CommandUtility::checkCommand($prog)) {
                // Program not found
                $this->errorPush(self::ERROR_PROGRAM_NOT_FOUND, 'External program not found: ' . $prog);
                $ret = false;
            }
        }
        return $ret;
    }

    /**
     * Deactivate the service. Use this if the service fails at runtime and will not be available.
     */
    public function deactivateService()
    {
        ExtensionManagementUtility::deactivateService($this->info['serviceType'], $this->info['serviceKey']);
    }

    /***************************************
     *
     *	 IO tools
     *
     ***************************************/
    /**
     * Check if a file exists and is readable.
     *
     * @param string $absFile File name with absolute path.
     * @return string|bool File name or FALSE.
     */
    public function checkInputFile($absFile)
    {
        $checkResult = false;
        if (GeneralUtility::isAllowedAbsPath($absFile) && @is_file($absFile)) {
            if (@is_readable($absFile)) {
                $checkResult = $absFile;
            } else {
                $this->errorPush(self::ERROR_FILE_NOT_READABLE, 'File is not readable: ' . $absFile);
            }
        } else {
            $this->errorPush(self::ERROR_FILE_NOT_FOUND, 'File not found: ' . $absFile);
        }
        return $checkResult;
    }

    /**
     * Read content from a file a file.
     *
     * @param string $absFile File name to read from.
     * @param int $length Maximum length to read. If empty the whole file will be read.
     * @return string|bool $content or FALSE
     */
    public function readFile($absFile, $length = 0)
    {
        $out = false;
        if ($this->checkInputFile($absFile)) {
            $out = file_get_contents($absFile);
            if ($out === false) {
                $this->errorPush(self::ERROR_FILE_NOT_READABLE, 'Can not read from file: ' . $absFile);
            }
        }
        return $out;
    }

    /**
     * Write content to a file.
     *
     * @param string $content Content to write to the file
     * @param string $absFile File name to write into. If empty a temp file will be created.
     * @return string|bool File name or FALSE
     */
    public function writeFile($content, $absFile = '')
    {
        if (!$absFile) {
            $absFile = $this->tempFile($this->prefixId);
        }
        if ($absFile && GeneralUtility::isAllowedAbsPath($absFile)) {
            if ($fd = @fopen($absFile, 'wb')) {
                @fwrite($fd, $content);
                @fclose($fd);
            } else {
                $this->errorPush(self::ERROR_FILE_NOT_WRITEABLE, 'Can not write to file: ' . $absFile);
                $absFile = false;
            }
        }
        return $absFile;
    }

    /**
     * Create a temporary file.
     *
     * @param string $filePrefix File prefix.
     * @return string|bool File name or FALSE
     */
    public function tempFile($filePrefix)
    {
        $absFile = GeneralUtility::tempnam($filePrefix);
        if ($absFile) {
            $ret = $absFile;
            $this->registerTempFile($absFile);
        } else {
            $ret = false;
            $this->errorPush(self::ERROR_FILE_NOT_WRITEABLE, 'Can not create temp file.');
        }
        return $ret;
    }

    /**
     * Register file which should be deleted afterwards.
     *
     * @param string $absFile File name with absolute path.
     */
    public function registerTempFile($absFile)
    {
        if (!isset($this->shutdownRegistry[__METHOD__])) {
            register_shutdown_function([$this, 'unlinkTempFiles']);
            $this->shutdownRegistry[__METHOD__] = true;
        }
        $this->tempFiles[] = $absFile;
    }

    /**
     * Delete registered temporary files.
     */
    public function unlinkTempFiles()
    {
        foreach ($this->tempFiles as $absFile) {
            GeneralUtility::unlink_tempfile($absFile);
        }
        $this->tempFiles = [];
    }

    /***************************************
     *
     *	 IO input
     *
     ***************************************/
    /**
     * Set the input content for service processing.
     *
     * @param mixed $content Input content (going into ->inputContent)
     * @param string $type The type of the input content (or file). Might be the same as the service subtypes.
     */
    public function setInput($content, $type = '')
    {
        $this->inputContent = $content;
        $this->inputFile = '';
        $this->inputType = $type;
    }

    /**
     * Set the input file name for service processing.
     *
     * @param string $absFile File name
     * @param string $type The type of the input content (or file). Might be the same as the service subtypes.
     */
    public function setInputFile($absFile, $type = '')
    {
        $this->inputContent = '';
        $this->inputFile = $absFile;
        $this->inputType = $type;
    }

    /**
     * Get the input content.
     * Will be read from input file if needed. (That is if ->inputContent is empty and ->inputFile is not)
     *
     * @return mixed
     */
    public function getInput()
    {
        if ($this->inputContent == '') {
            $this->inputContent = $this->readFile($this->inputFile);
        }
        return $this->inputContent;
    }

    /**
     * Get the input file name.
     * If the content was set by setContent a file will be created.
     *
     * @param string $createFile File name. If empty a temp file will be created.
     * @return string File name or FALSE if no input or file error.
     */
    public function getInputFile($createFile = '')
    {
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
     * @param string $absFile File name
     */
    public function setOutputFile($absFile)
    {
        $this->outputFile = $absFile;
    }

    /**
     * Get the output content.
     *
     * @return mixed
     */
    public function getOutput()
    {
        if ($this->outputFile) {
            $this->out = $this->readFile($this->outputFile);
        }
        return $this->out;
    }

    /**
     * Get the output file name. If no output file is set, the ->out buffer is written to the file given by input parameter filename
     *
     * @param string $absFile Absolute filename to write to
     * @return mixed
     */
    public function getOutputFile($absFile = '')
    {
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
     * @return bool TRUE if the service is available
     */
    public function init()
    {
        // look in makeInstanceService()
        $this->reset();
        // Check for external programs which are defined by $info['exec']
        if (trim($this->info['exec'])) {
            $this->checkExec($this->info['exec']);
        }
        return $this->getLastError() === true;
    }

    /**
     * Resets the service.
     * Will be called by init(). Should be used before every use if a service instance is used multiple times.
     */
    public function reset()
    {
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
     */
    public function __destruct()
    {
        $this->unlinkTempFiles();
    }
}
