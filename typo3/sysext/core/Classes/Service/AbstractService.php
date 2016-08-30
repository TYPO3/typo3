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

/**
 * Parent class for "Services" classes
 */
abstract class AbstractService
{
    /**
     * @var array service description array
     */
    public $info = [];

    /**
     * @var array error stack
     */
    public $error = [];

    /**
     * @var bool Defines if debug messages should be written with \TYPO3\CMS\Core\Utility\GeneralUtility::devLog
     */
    public $writeDevLog = false;

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
     * Logs debug messages to \TYPO3\CMS\Core\Utility\GeneralUtility::devLog()
     *
     * @param string $msg Debug message
     * @param int $severity Severity: 0 is info, 1 is notice, 2 is warning, 3 is fatal error, -1 is "OK" message
     * @param array|bool $dataVar dditional data you want to pass to the logger.
     * @return void
     */
    public function devLog($msg, $severity = 0, $dataVar = false)
    {
        if ($this->writeDevLog) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::devLog($msg, $this->info['serviceKey'], $severity, $dataVar);
        }
    }

    /**
     * Puts an error on the error stack. Calling without parameter adds a general error.
     *
     * @param int $errNum Error number (see T3_ERR_SV_* constants)
     * @param string $errMsg Error message
     * @return void
     */
    public function errorPush($errNum = T3_ERR_SV_GENERAL, $errMsg = 'Unspecified error occurred')
    {
        array_push($this->error, ['nr' => $errNum, 'msg' => $errMsg]);
        if (is_object($GLOBALS['TT'])) {
            $GLOBALS['TT']->setTSlogMessage($errMsg, 2);
        }
    }

    /**
     * Removes the last error from the error stack.
     *
     * @return void
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
     *
     * @return void
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
        $progList = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $progList, true);
        foreach ($progList as $prog) {
            if (!\TYPO3\CMS\Core\Utility\CommandUtility::checkCommand($prog)) {
                // Program not found
                $this->errorPush(T3_ERR_SV_PROG_NOT_FOUND, 'External program not found: ' . $prog);
                $ret = false;
            }
        }
        return $ret;
    }

    /**
     * Deactivate the service. Use this if the service fails at runtime and will not be available.
     *
     * @return void
     */
    public function deactivateService()
    {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::deactivateService($this->info['serviceType'], $this->info['serviceKey']);
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
        if (\TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedAbsPath($absFile) && @is_file($absFile)) {
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
                $this->errorPush(T3_ERR_SV_FILE_READ, 'Can not read from file: ' . $absFile);
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
        if ($absFile && \TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedAbsPath($absFile)) {
            if ($fd = @fopen($absFile, 'wb')) {
                @fwrite($fd, $content);
                @fclose($fd);
            } else {
                $this->errorPush(T3_ERR_SV_FILE_WRITE, 'Can not write to file: ' . $absFile);
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
        $absFile = \TYPO3\CMS\Core\Utility\GeneralUtility::tempnam($filePrefix);
        if ($absFile) {
            $ret = $absFile;
            $this->registerTempFile($absFile);
        } else {
            $ret = false;
            $this->errorPush(T3_ERR_SV_FILE_WRITE, 'Can not create temp file.');
        }
        return $ret;
    }

    /**
     * Register file which should be deleted afterwards.
     *
     * @param string File name with absolute path.
     * @return void
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
     *
     * @return void
     */
    public function unlinkTempFiles()
    {
        foreach ($this->tempFiles as $absFile) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::unlink_tempfile($absFile);
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
     * @return void
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
     * @return void
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
     * @return void
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
            if (!$this->checkExec($this->info['exec'])) {
            }
        }
        return $this->getLastError() === true;
    }

    /**
     * Resets the service.
     * Will be called by init(). Should be used before every use if a service instance is used multiple times.
     *
     * @return void
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
     *
     * @return void
     */
    public function __destruct()
    {
        $this->unlinkTempFiles();
    }
}
