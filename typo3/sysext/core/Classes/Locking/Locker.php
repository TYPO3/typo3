<?php
namespace TYPO3\CMS\Core\Locking;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TYPO3 locking class
 * This class provides an abstract layer to various locking features for TYPO3
 *
 * It is intended to blocks requests until some data has been generated.
 * This is especially useful if two clients are requesting the same website short after each other. While the request of client 1 triggers building and caching of the website, client 2 will be waiting at this lock.
 */
class Locker
{
    const LOCKING_METHOD_SIMPLE = 'simple';
    const LOCKING_METHOD_FLOCK = 'flock';
    const LOCKING_METHOD_SEMAPHORE = 'semaphore';
    const LOCKING_METHOD_DISABLED = 'disable';

    const FILE_LOCK_FOLDER = 'typo3temp/locks/';

    /**
     * @var string Locking method: One of the constants above
     */
    protected $method = '';

    /**
     * @var mixed Identifier used for this lock
     */
    protected $id;

    /**
     * @var mixed Resource used for this lock (can be a file or a semaphore resource)
     */
    protected $resource;

    /**
     * @var resource File pointer if using flock method
     */
    protected $filePointer;

    /**
     * @var bool True if lock is acquired
     */
    protected $isAcquired = false;

    /**
     * @var int Number of times a locked resource is tried to be acquired. Only used in manual locks method "simple".
     */
    protected $loops = 150;

    /**
     * @var int Milliseconds after lock acquire is retried. $loops * $step results in the maximum delay of a lock. Only used in manual lock method "simple".
     */
    protected $step = 200;

    /**
     * @var string Logging facility
     */
    protected $syslogFacility = 'cms';

    /**
     * @var bool True if locking should be logged
     */
    protected $isLoggingEnabled = true;

    /**
     * Constructor:
     * initializes locking, check input parameters and set variables accordingly.
     *
     * Parameters $loops and $step only apply to the locking method LOCKING_METHOD_SIMPLE.
     *
     * @param string $id ID to identify this lock in the system
     * @param string $method Define which locking method to use. Use one of the LOCKING_METHOD_* constants. Defaults to LOCKING_METHOD_SIMPLE. Use '' to use setting from Install Tool.
     * @param int $loops Number of times a locked resource is tried to be acquired.
     * @param int $step Milliseconds after lock acquire is retried. $loops * $step results in the maximum delay of a lock.
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @deprecated since TYPO3 CMS 7, will be removed with TYPO3 CMS 8
     */
    public function __construct($id, $method = self::LOCKING_METHOD_SIMPLE, $loops = 0, $step = 0)
    {
        GeneralUtility::logDeprecatedFunction();
        // Force ID to be string
        $id = (string)$id;
        if ((int)$loops) {
            $this->loops = (int)$loops;
        }
        if ((int)$step) {
            $this->step = (int)$step;
        }
        if ($method === '' && isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['lockingMode'])) {
            $method = (string)$GLOBALS['TYPO3_CONF_VARS']['SYS']['lockingMode'];
        }

        switch ($method) {
            case self::LOCKING_METHOD_SIMPLE:
                // intended fall through
            case self::LOCKING_METHOD_FLOCK:
                $this->id = md5($id);
                $this->createPathIfNeeded();
                break;
            case self::LOCKING_METHOD_SEMAPHORE:
                $this->id = abs(crc32($id));
                break;
            case self::LOCKING_METHOD_DISABLED:
                break;
            default:
                throw new \InvalidArgumentException('No such locking method "' . $method . '"', 1294586097);
        }
        $this->method = $method;
    }

    /**
     * Destructor:
     * Releases lock automatically when instance is destroyed and release resources
     */
    public function __destruct()
    {
        $this->release();
        switch ($this->method) {
            case self::LOCKING_METHOD_FLOCK:
                if (
                    GeneralUtility::isAllowedAbsPath($this->resource)
                    && GeneralUtility::isFirstPartOfStr($this->resource, PATH_site . self::FILE_LOCK_FOLDER)
                ) {
                    @unlink($this->resource);
                }
                break;
            case self::LOCKING_METHOD_SEMAPHORE:
                @sem_remove($this->resource);
                break;
            default:
                // do nothing
        }
    }

    /**
     * Tries to allocate the semaphore
     *
     * @return void
     * @throws \RuntimeException
     */
    protected function getSemaphore()
    {
        $this->resource = sem_get($this->id, 1);
        if ($this->resource === false) {
            throw new \RuntimeException('Unable to get semaphore with id ' . $this->id, 1313828196);
        }
    }

    /**
     * Acquire a lock and return when successful.
     *
     * It is important to know that the lock will be acquired in any case, even if the request was blocked first.
     * Therefore, the lock needs to be released in every situation.
     *
     * @return bool Returns TRUE if lock could be acquired without waiting, FALSE otherwise.
     * @throws \RuntimeException
     * @deprecated since 6.2 - will be removed two versions later; use new API instead
     */
    public function acquire()
    {
        // @todo refactor locking in TSFE to use the new API, then this call can be logged
        // GeneralUtility::logDeprecatedFunction();

        // Default is TRUE, which means continue without caring for other clients.
        // In the case of TYPO3s cache management, this has no negative effect except some resource overhead.
        $noWait = false;
        $isAcquired = false;
        switch ($this->method) {
            case self::LOCKING_METHOD_SIMPLE:
                if (file_exists($this->resource)) {
                    $this->sysLog('Waiting for a different process to release the lock');
                    $maxExecutionTime = (int)ini_get('max_execution_time');
                    $maxAge = time() - ($maxExecutionTime ?: 120);
                    if (@filectime($this->resource) < $maxAge) {
                        @unlink($this->resource);
                        $this->sysLog('Unlinking stale lockfile', GeneralUtility::SYSLOG_SEVERITY_WARNING);
                    }
                }
                for ($i = 0; $i < $this->loops; $i++) {
                    $filePointer = @fopen($this->resource, 'x');
                    if ($filePointer !== false) {
                        fclose($filePointer);
                        GeneralUtility::fixPermissions($this->resource);
                        $this->sysLog('Lock acquired');
                        $noWait = $i === 0;
                        $isAcquired = true;
                        break;
                    }
                    usleep($this->step * 1000);
                }
                if (!$isAcquired) {
                    throw new \RuntimeException('Lock file could not be created', 1294586098);
                }
                break;
            case self::LOCKING_METHOD_FLOCK:
                $this->filePointer = fopen($this->resource, 'c');
                if ($this->filePointer === false) {
                    throw new \RuntimeException('Lock file could not be opened', 1294586099);
                }
                // Lock without blocking
                if (flock($this->filePointer, LOCK_EX | LOCK_NB)) {
                    $noWait = true;
                } elseif (flock($this->filePointer, LOCK_EX)) {
                    // Lock with blocking (waiting for similar locks to become released)
                    $noWait = false;
                } else {
                    throw new \RuntimeException('Could not lock file "' . $this->resource . '"', 1294586100);
                }
                $isAcquired = true;
                break;
            case self::LOCKING_METHOD_SEMAPHORE:
                $this->getSemaphore();
                while (!$isAcquired) {
                    if (@sem_acquire($this->resource)) {
                        // Unfortunately it is not possible to find out if the request has blocked,
                        // as sem_acquire will block until we get the resource.
                        // So we do not set $noWait here at all
                        $isAcquired = true;
                    }
                }
                break;
            case self::LOCKING_METHOD_DISABLED:
                break;
            default:
                // will never be reached
        }
        $this->isAcquired = $isAcquired;
        return $noWait;
    }

    /**
     * Try to acquire an exclusive lock
     *
     * @throws \RuntimeException
     * @return bool Returns TRUE if the lock was acquired successfully
     */
    public function acquireExclusiveLock()
    {
        if ($this->isAcquired) {
            return true;
        }
        $this->isAcquired = false;
        switch ($this->method) {
            case self::LOCKING_METHOD_SIMPLE:
                if (file_exists($this->resource)) {
                    $this->sysLog('Waiting for a different process to release the lock');
                    $maxExecutionTime = (int)ini_get('max_execution_time');
                    $maxAge = time() - ($maxExecutionTime ?: 120);
                    if (@filectime($this->resource) < $maxAge) {
                        @unlink($this->resource);
                        $this->sysLog('Unlinking stale lockfile', GeneralUtility::SYSLOG_SEVERITY_WARNING);
                    }
                }
                for ($i = 0; $i < $this->loops; $i++) {
                    $filePointer = @fopen($this->resource, 'x');
                    if ($filePointer !== false) {
                        fclose($filePointer);
                        GeneralUtility::fixPermissions($this->resource);
                        $this->sysLog('Lock acquired');
                        $this->isAcquired = true;
                        break;
                    }
                    usleep($this->step * 1000);
                }
                break;
            case self::LOCKING_METHOD_FLOCK:
                $this->filePointer = fopen($this->resource, 'c');
                if ($this->filePointer === false) {
                    throw new \RuntimeException('Lock file could not be opened', 1294586099);
                }
                if (flock($this->filePointer, LOCK_EX)) {
                    $this->isAcquired = true;
                }
                break;
            case self::LOCKING_METHOD_SEMAPHORE:
                $this->getSemaphore();
                if (@sem_acquire($this->resource)) {
                    $this->isAcquired = true;
                }
                break;
            case self::LOCKING_METHOD_DISABLED:
                break;
            default:
                // will never be reached
        }
        return $this->isAcquired;
    }

    /**
     * Try to acquire a shared lock
     *
     * (Only works for the flock() locking method currently)
     *
     * @return bool Returns TRUE if the lock was acquired successfully
     * @throws \RuntimeException
     */
    public function acquireSharedLock()
    {
        if ($this->isAcquired) {
            return true;
        }
        if ($this->method === self::LOCKING_METHOD_FLOCK) {
            $this->filePointer = fopen($this->resource, 'c');
            if ($this->filePointer === false) {
                throw new \RuntimeException('Lock file could not be opened', 1294586099);
            }
            if (flock($this->filePointer, LOCK_SH)) {
                $this->isAcquired = true;
            }
        }
        return $this->isAcquired;
    }

    /**
     * Release the lock
     *
     * @return bool Returns TRUE on success or FALSE on failure
     */
    public function release()
    {
        if (!$this->isAcquired) {
            return true;
        }
        $success = true;
        switch ($this->method) {
            case self::LOCKING_METHOD_SIMPLE:
                if (
                    GeneralUtility::isAllowedAbsPath($this->resource)
                    && GeneralUtility::isFirstPartOfStr($this->resource, PATH_site . self::FILE_LOCK_FOLDER)
                ) {
                    if (@unlink($this->resource) === false) {
                        $success = false;
                    }
                }
                break;
            case self::LOCKING_METHOD_FLOCK:
                if (is_resource($this->filePointer)) {
                    if (flock($this->filePointer, LOCK_UN) === false) {
                        $success = false;
                    }
                    fclose($this->filePointer);
                }
                break;
            case self::LOCKING_METHOD_SEMAPHORE:
                if (!@sem_release($this->resource)) {
                    $success = false;
                }
                break;
            case self::LOCKING_METHOD_DISABLED:
                break;
            default:
                // will never be reached
        }
        $this->isAcquired = false;
        return $success;
    }

    /**
     * Return the locking method which is currently used
     *
     * @return string Locking method
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Return the ID which is currently used
     *
     * @return string Locking ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Return the resource which is currently used.
     * Depending on the locking method this can be a filename or a semaphore resource.
     *
     * @return mixed Locking resource (filename as string or semaphore as resource)
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Return the local status of a lock
     *
     * @return bool Returns TRUE if lock is acquired by this process, FALSE otherwise
     */
    public function getLockStatus()
    {
        return $this->isAcquired;
    }

    /**
     * Return the global status of the lock
     *
     * @return bool Returns TRUE if the lock is locked by either this or another process, FALSE otherwise
     */
    public function isLocked()
    {
        $result = false;
        switch ($this->method) {
            case self::LOCKING_METHOD_SIMPLE:
                if (file_exists($this->resource)) {
                    $maxExecutionTime = (int)ini_get('max_execution_time');
                    $maxAge = time() - ($maxExecutionTime ?: 120);
                    if (@filectime($this->resource) < $maxAge) {
                        @unlink($this->resource);
                        $this->sysLog('Unlinking stale lockfile', GeneralUtility::SYSLOG_SEVERITY_WARNING);
                    } else {
                        $result = true;
                    }
                }
                break;
            case self::LOCKING_METHOD_FLOCK:
                // we can't detect this reliably here, since the third parameter of flock() does not work on windows
                break;
            case self::LOCKING_METHOD_SEMAPHORE:
                // no way to detect this at all, no PHP API for that
                break;
            case self::LOCKING_METHOD_DISABLED:
                break;
            default:
                // will never be reached
        }
        return $result;
    }

    /**
     * Sets the facility (extension name) for the syslog entry.
     *
     * @param string $syslogFacility
     */
    public function setSyslogFacility($syslogFacility)
    {
        $this->syslogFacility = $syslogFacility;
    }

    /**
     * Enable/ disable logging
     *
     * @param bool $isLoggingEnabled
     */
    public function setEnableLogging($isLoggingEnabled)
    {
        $this->isLoggingEnabled = $isLoggingEnabled;
    }

    /**
     * Adds a common log entry for this locking API using \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog().
     * Example: 25-02-08 17:58 - cms: Locking [simple::0aeafd2a67a6bb8b9543fb9ea25ecbe2]: Acquired
     *
     * @param string $message The message to be logged
     * @param int $severity Severity - 0 is info (default), 1 is notice, 2 is warning, 3 is error, 4 is fatal error
     * @return void
     */
    public function sysLog($message, $severity = 0)
    {
        if ($this->isLoggingEnabled) {
            GeneralUtility::sysLog('Locking [' . $this->method . '::' . $this->id . ']: ' . trim($message), $this->syslogFacility, $severity);
        }
    }

    /**
     * Tests if the directory for simple locks is available.
     * If not, the directory will be created. The lock path is usually
     * below typo3temp, typo3temp itself should exist already
     *
     * @return void
     * @throws \RuntimeException If path couldn't be created.
     */
    protected function createPathIfNeeded()
    {
        $path = PATH_site . self::FILE_LOCK_FOLDER;
        if (!is_dir($path)) {
            // Not using mkdir_deep on purpose here, if typo3temp itself
            // does not exist, this issue should be solved on a different
            // level of the application.
            if (!GeneralUtility::mkdir($path)) {
                throw new \RuntimeException('Cannot create directory ' . $path, 1395140007);
            }
        }
        if (!is_writable($path)) {
            throw new \RuntimeException('Cannot write to directory ' . $path, 1396278700);
        }
        $this->resource = $path . $this->id;
    }
}
