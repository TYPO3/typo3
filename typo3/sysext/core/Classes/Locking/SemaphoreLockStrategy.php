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

use TYPO3\CMS\Core\Locking\Exception\LockAcquireException;
use TYPO3\CMS\Core\Locking\Exception\LockCreateException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Semaphore locking
 */
class SemaphoreLockStrategy implements LockingStrategyInterface
{
    const FILE_LOCK_FOLDER = 'typo3temp/locks/';

    /**
     * @var mixed Identifier used for this lock
     */
    protected $id;

    /**
     * @var resource Semaphore Resource used for this lock
     */
    protected $resource;

    /**
     * @var string
     */
    protected $filePath = '';

    /**
     * @var bool TRUE if lock is acquired
     */
    protected $isAcquired = false;

    /**
     * @param string $subject ID to identify this lock in the system
     * @throws LockCreateException
     */
    public function __construct($subject)
    {
        $path = PATH_site . self::FILE_LOCK_FOLDER;
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
        $this->filePath = $path . 'sem_' . md5((string)$subject);
        touch($this->filePath);
        $this->id = ftok($this->filePath, 'A');
        if ($this->id === false) {
            throw new LockCreateException('Cannot create key for semaphore using path ' . $this->filePath, 1396278734);
        }
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->release();
        // We do not call sem_remove() since this would remove the resource for other processes,
        // we leave that to the system. This is not clean, but there's no other way to determine when
        // a semaphore is no longer needed as a website is generally running endlessly
        // and we have no way to detect if there is a process currently waiting on that lock
        // or if the server is shutdown
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
        $this->isAcquired = false;
        return (bool)@sem_release($this->resource);
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
     * @return int LOCK_CAPABILITY_* elements combined with bit-wise OR
     */
    public static function getCapabilities()
    {
        if (function_exists('sem_get')) {
            return self::LOCK_CAPABILITY_EXCLUSIVE;
        }
        return 0;
    }

    /**
     * Try to acquire a lock
     *
     * @param int $mode LOCK_CAPABILITY_EXCLUSIVE
     * @return bool Returns TRUE if the lock was acquired successfully
     * @throws LockAcquireException if a semaphore could not be retrieved
     */
    public function acquire($mode = self::LOCK_CAPABILITY_EXCLUSIVE)
    {
        if ($this->isAcquired) {
            return true;
        }

        $this->resource = sem_get($this->id, 1);
        if ($this->resource === false) {
            throw new LockAcquireException('Unable to get semaphore with id ' . $this->id, 1313828196);
        }

        $this->isAcquired = (bool)sem_acquire($this->resource);
        return $this->isAcquired;
    }

    /**
     * @return int Returns a priority for the method. 0 to 100, 100 is highest
     */
    public static function getPriority()
    {
        return 25;
    }

    /**
     * Destroys the resource associated with the lock
     *
     * @return void
     */
    public function destroy()
    {
        if ($this->resource) {
            sem_remove($this->resource);
            @unlink($this->filePath);
        }
    }
}
