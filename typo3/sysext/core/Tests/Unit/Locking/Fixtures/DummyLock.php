<?php
namespace TYPO3\CMS\Core\Tests\Unit\Locking\Fixtures;

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

use TYPO3\CMS\Core\Locking\LockingStrategyInterface;

/**
 * Dummy locking
 */
class DummyLock implements LockingStrategyInterface
{
    /**
     * @return int LOCK_CAPABILITY_* elements combined with bit-wise OR
     */
    public static function getCapabilities()
    {
        return self::LOCK_CAPABILITY_EXCLUSIVE;
    }

    /**
     * @return int Returns a priority for the method. 0 to 100, 100 is highest
     */
    public static function getPriority()
    {
        return 100;
    }

    /**
     * @param string $subject ID to identify this lock in the system
     */
    public function __construct($subject)
    {
    }

    /**
     * Try to acquire a lock
     *
     * @param int $mode LOCK_CAPABILITY_EXCLUSIVE or LOCK_CAPABILITY_SHARED
     * @return bool Returns TRUE if the lock was acquired successfully
     */
    public function acquire($mode = self::LOCK_CAPABILITY_EXCLUSIVE)
    {
        return false;
    }

    /**
     * Release the lock
     *
     * @return bool Returns TRUE on success or FALSE on failure
     */
    public function release()
    {
        return false;
    }

    /**
     * Get status of this lock
     *
     * @return bool Returns TRUE if lock is acquired by this locker, FALSE otherwise
     */
    public function isAcquired()
    {
        return false;
    }

    /**
     * Destroys the resource associated with the lock
     *
     * @return void
     */
    public function destroy()
    {
    }
}
