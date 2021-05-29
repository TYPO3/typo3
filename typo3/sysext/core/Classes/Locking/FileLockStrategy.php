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

namespace TYPO3\CMS\Core\Locking;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireException;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException;
use TYPO3\CMS\Core\Locking\Exception\LockCreateException;
use TYPO3\CMS\Core\Security\BlockSerializationTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * flock() locking
 */
class FileLockStrategy implements LockingStrategyInterface
{
    use BlockSerializationTrait;

    const FILE_LOCK_FOLDER = 'lock/';
    const DEFAULT_PRIORITY = 75;

    /**
     * @var resource File pointer if using flock method
     */
    protected $filePointer;

    /**
     * @var string File used for locking
     */
    protected $filePath;

    /**
     * @var bool True if lock is acquired
     */
    protected $isAcquired = false;

    /**
     * @param string $subject ID to identify this lock in the system
     * @throws LockCreateException if the lock could not be created
     */
    public function __construct($subject)
    {
        /*
         * Tests if the directory for file locks is available.
         * If not, the directory will be created. The lock path is usually
         * below typo3temp/var, typo3temp/var itself should exist already (or root-path/var/ respectively)
         */
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['locking']['strategies'][self::class]['lockFileDir'] ?? false) {
            $path = Environment::getProjectPath() . '/'
                . trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['locking']['strategies'][self::class]['lockFileDir'], ' /')
                . '/';
        } else {
            $path = Environment::getVarPath() . '/' . self::FILE_LOCK_FOLDER;
        }
        if (!is_dir($path)) {
            // Not using mkdir_deep on purpose here, if typo3temp itself
            // does not exist, this issue should be solved on a different
            // level of the application.
            if (!GeneralUtility::mkdir($path)) {
                throw new LockCreateException('Cannot create directory ' . $path, 1395140007);
            }
        }
        if (!is_writable($path)) {
            throw new LockCreateException('Cannot write to directory ' . $path, 1396278700);
        }
        $this->filePath = $path . 'flock_' . md5((string)$subject);
    }

    /**
     * Destructor:
     * Releases lock automatically when instance is destroyed and release resources
     */
    public function __destruct()
    {
        $this->release();
    }

    /**
     * Try to acquire an exclusive lock
     *
     * @param int $mode LOCK_CAPABILITY_EXCLUSIVE or LOCK_CAPABILITY_SHARED or self::LOCK_CAPABILITY_NOBLOCK
     * @return bool Returns TRUE if the lock was acquired successfully
     * @throws LockAcquireException if the lock could not be acquired
     * @throws LockAcquireWouldBlockException if the acquire would have blocked and NOBLOCK was set
     */
    public function acquire($mode = self::LOCK_CAPABILITY_EXCLUSIVE)
    {
        if ($this->isAcquired) {
            return true;
        }

        $filePointer = fopen($this->filePath, 'c');
        if ($filePointer === false) {
            throw new LockAcquireException('Lock file could not be opened', 1294586099);
        }
        $this->filePointer = $filePointer;
        GeneralUtility::fixPermissions($this->filePath);

        $operation = $mode & self::LOCK_CAPABILITY_EXCLUSIVE ? LOCK_EX : LOCK_SH;
        if ($mode & self::LOCK_CAPABILITY_NOBLOCK) {
            $operation |= LOCK_NB;
        }

        $wouldBlock = 0;
        $this->isAcquired = flock($this->filePointer, $operation, $wouldBlock);

        if (!$this->isAcquired) {
            // Make sure to cleanup any dangling resources for this process/thread, which are not needed any longer
            fclose($this->filePointer);
        }
        if ($mode & self::LOCK_CAPABILITY_NOBLOCK && !$this->isAcquired && $wouldBlock) {
            throw new LockAcquireWouldBlockException('Failed to acquire lock because the request would block.', 1428700748);
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
        if (is_resource($this->filePointer)) {
            if (flock($this->filePointer, LOCK_UN) === false) {
                $success = false;
            }
            fclose($this->filePointer);
        }
        $this->isAcquired = false;
        return $success;
    }

    /**
     * Get status of this lock
     *
     * @return bool Returns TRUE if lock is acquired by this locker, FALSE otherwise
     */
    public function isAcquired()
    {
        return $this->isAcquired;
    }

    /**
     * @return int Returns a priority for the method. 0 to 100, 100 is highest
     */
    public static function getPriority()
    {
        return $GLOBALS['TYPO3_CONF_VARS']['SYS']['locking']['strategies'][self::class]['priority']
            ?? self::DEFAULT_PRIORITY;
    }

    /**
     * @return int LOCK_CAPABILITY_* elements combined with bit-wise OR
     */
    public static function getCapabilities()
    {
        if (PHP_SAPI === 'isapi') {
            // From php docs: When using a multi-threaded server API like ISAPI you may not be able to rely on flock()
            // to protect files against other PHP scripts running in parallel threads of the same server instance!
            return 0;
        }
        $capabilities = self::LOCK_CAPABILITY_EXCLUSIVE | self::LOCK_CAPABILITY_SHARED | self::LOCK_CAPABILITY_NOBLOCK;

        return $capabilities;
    }

    /**
     * Destroys the resource associated with the lock
     */
    public function destroy()
    {
        @unlink($this->filePath);
    }
}
