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

/**
 * Semaphore locking
 *
 * @author Markus Klein <klein.t3@reelworx.at>
 */
class SemaphoreLockStrategy implements LockingStrategyInterface {

	/**
	 * @var mixed Identifier used for this lock
	 */
	protected $id;

	/**
	 * @var resource Semaphore Resource used for this lock
	 */
	protected $resource;

	/**
	 * @var bool TRUE if lock is acquired
	 */
	protected $isAcquired = FALSE;

	/**
	 * @param string $subject ID to identify this lock in the system
	 */
	public function __construct($subject) {
		$this->id = abs(crc32((string)$subject));
	}

	/**
	 * Destructor
	 */
	public function __destruct() {
		$this->release();
		// We do not call sem_remove() since this would remove the resource for other processes,
		// we leave that to the system. This is not clean, but there's no other way to determine when
		// a semaphore is no longer needed.
	}

	/**
	 * Release the lock
	 *
	 * @return bool Returns TRUE on success or FALSE on failure
	 */
	public function release() {
		if (!$this->isAcquired) {
			return TRUE;
		}
		$this->isAcquired = FALSE;
		return (bool)@sem_release($this->resource);
	}

	/**
	 * Get status of this lock
	 *
	 * @return bool Returns TRUE if lock is acquired by this locker, FALSE otherwise
	 */
	public function isAcquired() {
		return $this->isAcquired;
	}

	/**
	 * @return int LOCK_CAPABILITY_* elements combined with bit-wise OR
	 */
	static public function getCapabilities() {
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
	 * @throws \RuntimeException
	 */
	public function acquire($mode = self::LOCK_CAPABILITY_EXCLUSIVE) {
		if ($this->isAcquired) {
			return TRUE;
		}

		$this->resource = sem_get($this->id, 1);
		if ($this->resource === FALSE) {
			throw new \RuntimeException('Unable to get semaphore with id ' . $this->id, 1313828196);
		}

		$this->isAcquired = (bool)sem_acquire($this->resource);
		return $this->isAcquired;
	}

	/**
	 * @return int Returns a priority for the method. 0 to 100, 100 is highest
	 */
	static public function getPriority() {
		return 75;
	}

}
