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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException;
use TYPO3\CMS\Core\Locking\Exception\LockCreateException;
use TYPO3\CMS\Core\Security\BlockSerializationTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Simple file locking
 */
class SimpleLockStrategy implements LockingStrategyInterface
{
    use BlockSerializationTrait;

    const FILE_LOCK_FOLDER = 'lock/';

    /**
     * @var string File path used for this lock
     */
    protected $filePath;

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
     * @param string $subject ID to identify this lock in the system
     * @throws LockCreateException if the lock could not be created
     */
    public function __construct($subject)
    {
        // Tests if the directory for simple locks is available.
        // If not, the directory will be created. The lock path is usually
        // below typo3temp/var, typo3temp/var itself should exist already (or getProjectPath . /var/ respectively)
        $path = Environment::getVarPath() . '/' . self::FILE_LOCK_FOLDER;
        if (!is_dir($path)) {
            // Not using mkdir_deep on purpose here, if typo3temp/var itself
            // does not exist, this issue should be solved on a different
            // level of the application.
            if (!GeneralUtility::mkdir($path)) {
                throw new LockCreateException('Cannot create directory ' . $path, 1460976286);
            }
        }
        if (!is_writable($path)) {
            throw new LockCreateException('Cannot write to directory ' . $path, 1460976340);
        }
        $this->filePath = $path . 'simple_' . md5((string)$subject);
    }

    /**
     * @param int $loops Number of times a locked resource is tried to be acquired.
     * @param int $step Milliseconds after lock acquire is retried. $loops * $step results in the maximum delay of a lock.
     */
    public function init($loops = 0, $step = 0)
    {
        $this->loops = (int)$loops;
        $this->step = (int)$step;
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
        if (
            GeneralUtility::isAllowedAbsPath($this->filePath)
            && GeneralUtility::isFirstPartOfStr($this->filePath, Environment::getVarPath() . '/' . self::FILE_LOCK_FOLDER)
        ) {
            if (@unlink($this->filePath) === false) {
                $success = false;
            }
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
     * @return int LOCK_CAPABILITY_* elements combined with bit-wise OR
     */
    public static function getCapabilities()
    {
        return self::LOCK_CAPABILITY_EXCLUSIVE | self::LOCK_CAPABILITY_NOBLOCK;
    }

    /**
     * Try to acquire a lock
     *
     * @param int $mode LOCK_CAPABILITY_EXCLUSIVE or self::LOCK_CAPABILITY_NOBLOCK
     * @return bool Returns TRUE if the lock was acquired successfully
     * @throws LockAcquireWouldBlockException
     */
    public function acquire($mode = self::LOCK_CAPABILITY_EXCLUSIVE)
    {
        if ($this->isAcquired) {
            return true;
        }

        if (file_exists($this->filePath)) {
            $maxExecutionTime = (int)ini_get('max_execution_time');
            $maxAge = time() - ($maxExecutionTime ?: 120);
            if (@filectime($this->filePath) < $maxAge) {
                // Remove stale lock file
                @unlink($this->filePath);
            }
        }

        $this->isAcquired = false;
        $wouldBlock = false;
        for ($i = 0; $i < $this->loops; $i++) {
            $filePointer = @fopen($this->filePath, 'x');
            if ($filePointer !== false) {
                fclose($filePointer);
                GeneralUtility::fixPermissions($this->filePath);
                $this->isAcquired = true;
                break;
            }
            if ($mode & self::LOCK_CAPABILITY_NOBLOCK) {
                $wouldBlock = true;
                break;
            }
            usleep($this->step * 1000);
        }

        if ($mode & self::LOCK_CAPABILITY_NOBLOCK && !$this->isAcquired && $wouldBlock) {
            throw new LockAcquireWouldBlockException('Failed to acquire lock because the request would block.', 1460976403);
        }

        return $this->isAcquired;
    }

    /**
     * @return int Returns a priority for the method. 0 to 100, 100 is highest
     */
    public static function getPriority()
    {
        return 50;
    }

    /**
     * Destroys the resource associated with the lock
     */
    public function destroy()
    {
        @unlink($this->filePath);
    }
}
