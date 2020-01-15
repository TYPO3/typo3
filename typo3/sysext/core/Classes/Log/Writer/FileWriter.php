<?php

namespace TYPO3\CMS\Core\Log\Writer;

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\Exception\InvalidLogWriterConfigurationException;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Log writer that writes the log records into a file.
 */
class FileWriter extends AbstractWriter
{
    /**
     * Log file path, relative to TYPO3's base project folder
     *
     * @var string
     */
    protected $logFile = '';

    /**
     * @var string
     */
    protected $logFileInfix = '';

    /**
     * Default log file path
     *
     * @var string
     */
    protected $defaultLogFileTemplate = '/log/typo3_%s.log';

    /**
     * Log file handle storage
     *
     * To avoid concurrent file handles on a the same file when using several FileWriter instances,
     * we share the file handles in a static class variable
     *
     * @static
     * @var array
     */
    protected static $logFileHandles = [];

    /**
     * Keep track of used file handles by different fileWriter instances
     * As the logger gets instantiated by class name but the resources
     * are shared via the static $logFileHandles we need to track usage
     * of file handles to avoid closing handles that are still needed
     * by different instances. Only if the count is zero may the file
     * handle be closed.
     *
     * @var array
     */
    protected static $logFileHandlesCount = [];

    /**
     * Constructor, opens the log file handle
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        // the parent constructor reads $options and sets them
        parent::__construct($options);
        if (empty($options['logFile'])) {
            $this->setLogFile($this->getDefaultLogFileName());
        }
    }

    /**
     * Destructor, closes the log file handle
     */
    public function __destruct()
    {
        self::$logFileHandlesCount[$this->logFile]--;
        if (self::$logFileHandlesCount[$this->logFile] <= 0) {
            $this->closeLogFile();
        }
    }

    public function setLogFileInfix(string $infix)
    {
        $this->logFileInfix = $infix;
    }

    /**
     * Sets the path to the log file.
     *
     * @param string $relativeLogFile path to the log file, relative to public web dir
     * @return WriterInterface
     * @throws InvalidLogWriterConfigurationException
     */
    public function setLogFile($relativeLogFile)
    {
        $logFile = $relativeLogFile;
        // Skip handling if logFile is a stream resource. This is used by unit tests with vfs:// directories
        if (false === strpos($logFile, '://') && !PathUtility::isAbsolutePath($logFile)) {
            $logFile = GeneralUtility::getFileAbsFileName($logFile);
            if (empty($logFile)) {
                throw new InvalidLogWriterConfigurationException(
                    'Log file path "' . $relativeLogFile . '" is not valid!',
                    1444374805
                );
            }
        }
        $this->logFile = $logFile;
        $this->openLogFile();

        return $this;
    }

    /**
     * Gets the path to the log file.
     *
     * @return string Path to the log file.
     */
    public function getLogFile()
    {
        return $this->logFile;
    }

    /**
     * Writes the log record
     *
     * @param LogRecord $record Log record
     * @return WriterInterface $this
     * @throws \RuntimeException
     */
    public function writeLog(LogRecord $record)
    {
        $timestamp = date('r', (int)$record->getCreated());
        $levelName = LogLevel::getName($record->getLevel());
        $data = '';
        $recordData = $record->getData();
        if (!empty($recordData)) {
            // According to PSR3 the exception-key may hold an \Exception
            // Since json_encode() does not encode an exception, we run the _toString() here
            if (isset($recordData['exception']) && $recordData['exception'] instanceof \Exception) {
                $recordData['exception'] = (string)$recordData['exception'];
            }
            $data = '- ' . json_encode($recordData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $message = sprintf(
            '%s [%s] request="%s" component="%s": %s %s',
            $timestamp,
            $levelName,
            $record->getRequestId(),
            $record->getComponent(),
            $record->getMessage(),
            $data
        );

        if (false === fwrite(self::$logFileHandles[$this->logFile], $message . LF)) {
            throw new \RuntimeException('Could not write log record to log file', 1345036335);
        }

        return $this;
    }

    /**
     * Opens the log file handle
     *
     * @throws \RuntimeException if the log file can't be opened.
     */
    protected function openLogFile()
    {
        if (isset(self::$logFileHandlesCount[$this->logFile])) {
            self::$logFileHandlesCount[$this->logFile]++;
        } else {
            self::$logFileHandlesCount[$this->logFile] = 1;
        }
        if (isset(self::$logFileHandles[$this->logFile]) && is_resource(self::$logFileHandles[$this->logFile] ?? false)) {
            return;
        }

        $this->createLogFile();
        self::$logFileHandles[$this->logFile] = fopen($this->logFile, 'a');
        if (!is_resource(self::$logFileHandles[$this->logFile])) {
            throw new \RuntimeException('Could not open log file "' . $this->logFile . '"', 1321804422);
        }
    }

    /**
     * Closes the log file handle.
     */
    protected function closeLogFile()
    {
        if (!empty(self::$logFileHandles[$this->logFile]) && is_resource(self::$logFileHandles[$this->logFile])) {
            fclose(self::$logFileHandles[$this->logFile]);
            unset(self::$logFileHandles[$this->logFile]);
        }
    }

    /**
     * Creates the log file with correct permissions
     * and parent directories, if needed
     */
    protected function createLogFile()
    {
        if (file_exists($this->logFile)) {
            return;
        }

        // skip mkdir if logFile refers to any scheme but vfs://, file:// or empty
        $scheme = parse_url($this->logFile, PHP_URL_SCHEME);
        if ($scheme === null || $scheme === 'file' || $scheme === 'vfs' || GeneralUtility::isAbsPath($this->logFile)) {
            // remove file:/ before creating the directory
            $logFileDirectory = PathUtility::dirname(preg_replace('#^file:/#', '', $this->logFile));
            if (!@is_dir($logFileDirectory)) {
                GeneralUtility::mkdir_deep($logFileDirectory);
                // create .htaccess file if log file is within the site path
                if (PathUtility::getCommonPrefix([Environment::getPublicPath() . '/', $logFileDirectory]) === (Environment::getPublicPath() . '/')) {
                    // only create .htaccess, if we created the directory on our own
                    $this->createHtaccessFile($logFileDirectory . '/.htaccess');
                }
            }
        }
        // create the log file
        GeneralUtility::writeFile($this->logFile, '');
    }

    /**
     * Creates .htaccess file inside a new directory to access protect it
     *
     * @param string $htaccessFile Path of .htaccess file
     */
    protected function createHtaccessFile($htaccessFile)
    {
        // write .htaccess file to protect the log file
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['generateApacheHtaccess']) && !file_exists($htaccessFile)) {
            $htaccessContent = '
# Apache < 2.3
<IfModule !mod_authz_core.c>
	Order allow,deny
	Deny from all
	Satisfy All
</IfModule>

# Apache ≥ 2.3
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>
			';
            GeneralUtility::writeFile($htaccessFile, $htaccessContent);
        }
    }

    /**
     * Returns the path to the default log file.
     * Uses the defaultLogFileTemplate and replaces the %s placeholder with a short MD5 hash
     * based on a static string and the current encryption key.
     *
     * @return string
     */
    protected function getDefaultLogFileName()
    {
        $namePart = substr(GeneralUtility::hmac($this->defaultLogFileTemplate, 'defaultLogFile'), 0, 10);
        if ($this->logFileInfix !== '') {
            $namePart = $this->logFileInfix . '_' . $namePart;
        }
        return Environment::getVarPath() . sprintf($this->defaultLogFileTemplate, $namePart);
    }
}
