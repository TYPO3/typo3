<?php
namespace TYPO3\CMS\Core\Locking;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Michael Stucki (michael@typo3.org)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
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
 * TYPO3 locking class
 * This class provides an abstract layer to various locking features for TYPO3
 *
 * It is intended to blocks requests until some data has been generated.
 * This is especially useful if two clients are requesting the same website short after each other. While the request of client 1 triggers building and caching of the website, client 2 will be waiting at this lock.
 *
 * @author Michael Stucki <michael@typo3.org>
 */
class Locker {

	/**
	 * @var string Locking method: One of 'simple', 'flock', 'semaphore' or 'disable'
	 */
	protected $method;

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
	protected $filepointer;

	/**
	 * @var boolean True if lock is acquired
	 */
	protected $isAcquired = FALSE;

	/**
	 * @var integer Number of times a locked resource is tried to be acquired. Only used in manual locks method "simple".
	 */
	protected $loops = 150;

	/**
	 * @var integer Milliseconds after lock acquire is retried. $loops * $step results in the maximum delay of a lock. Only used in manual lock method "simple".
	 */
	protected $step = 200;

	/**
	 * @var string Logging facility
	 */
	protected $syslogFacility = 'cms';

	/**
	 * @var boolean True if locking should be logged
	 */
	protected $isLoggingEnabled = TRUE;

	/**
	 * Constructor:
	 * initializes locking, check input parameters and set variables accordingly.
	 *
	 * @param string $id ID to identify this lock in the system
	 * @param string $method Define which locking method to use. Defaults to "simple".
	 * @param integer $loops Number of times a locked resource is tried to be acquired. Only used in manual locks method "simple".
	 * @param integer step Milliseconds after lock acquire is retried. $loops * $step results in the maximum delay of a lock. Only used in manual lock method "simple".
	 */
	public function __construct($id, $method = 'simple', $loops = 0, $step = 0) {
		// Force ID to be string
		$id = (string) $id;
		if (intval($loops)) {
			$this->loops = intval($loops);
		}
		if (intval($step)) {
			$this->step = intval($step);
		}
		$this->method = $method;
		switch ($this->method) {
		case 'simple':

		case 'flock':
			$path = PATH_site . 'typo3temp/locks/';
			if (!is_dir($path)) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir($path);
			}
			$this->id = md5($id);
			$this->resource = $path . $this->id;
			break;
		case 'semaphore':
			$this->id = abs(crc32($id));
			if (($this->resource = sem_get($this->id, 1)) === FALSE) {
				throw new \RuntimeException('Unable to get semaphore', 1313828196);
			}
			break;
		case 'disable':
			break;
		default:
			throw new \InvalidArgumentException('No such method "' . $method . '"', 1294586097);
		}
	}

	/**
	 * Destructor:
	 * Releases lock automatically when instance is destroyed.
	 *
	 * @return 	void
	 * @todo Define visibility
	 */
	public function __destruct() {
		$this->release();
	}

	/**
	 * Acquire a lock and return when successful. If the lock is already open, the client will be
	 *
	 * It is important to know that the lock will be acquired in any case, even if the request was blocked first. Therefore, the lock needs to be released in every situation.
	 *
	 * @return boolean Returns TRUE if lock could be acquired without waiting, FALSE otherwise.
	 */
	public function acquire() {
		// Default is TRUE, which means continue without caring for other clients. In the case of TYPO3s cache management, this has no negative effect except some resource overhead.
		$noWait = TRUE;
		$isAcquired = TRUE;
		switch ($this->method) {
		case 'simple':
			if (is_file($this->resource)) {
				$this->sysLog('Waiting for a different process to release the lock');
				$maxExecutionTime = ini_get('max_execution_time');
				$maxAge = time() - ($maxExecutionTime ? $maxExecutionTime : 120);
				if (@filectime($this->resource) < $maxAge) {
					@unlink($this->resource);
					$this->sysLog('Unlink stale lockfile');
				}
			}
			$isAcquired = FALSE;
			for ($i = 0; $i < $this->loops; $i++) {
				$filepointer = @fopen($this->resource, 'x');
				if ($filepointer !== FALSE) {
					fclose($filepointer);
					$this->sysLog('Lock acquired');
					$noWait = $i === 0;
					$isAcquired = TRUE;
					break;
				}
				usleep($this->step * 1000);
			}
			if (!$isAcquired) {
				throw new \RuntimeException('Lock file could not be created', 1294586098);
			}
			\TYPO3\CMS\Core\Utility\GeneralUtility::fixPermissions($this->resource);
			break;
		case 'flock':
			if (($this->filepointer = fopen($this->resource, 'w+')) == FALSE) {
				throw new \RuntimeException('Lock file could not be opened', 1294586099);
			}
			// Lock without blocking
			if (flock($this->filepointer, (LOCK_EX | LOCK_NB)) == TRUE) {
				$noWait = TRUE;
			} elseif (flock($this->filepointer, LOCK_EX) == TRUE) {
				// Lock with blocking (waiting for similar locks to become released)
				$noWait = FALSE;
			} else {
				throw new \RuntimeException('Could not lock file "' . $this->resource . '"', 1294586100);
			}
			break;
		case 'semaphore':
			if (sem_acquire($this->resource)) {
				// Unfortunately it seems not possible to find out if the request was blocked, so we return FALSE in any case to make sure the operation is tried again.
				$noWait = FALSE;
			}
			break;
		case 'disable':
			$noWait = FALSE;
			$isAcquired = FALSE;
			break;
		}
		$this->isAcquired = $isAcquired;
		return $noWait;
	}

	/**
	 * Release the lock
	 *
	 * @return boolean Returns TRUE on success or FALSE on failure
	 */
	public function release() {
		if (!$this->isAcquired) {
			return TRUE;
		}
		$success = TRUE;
		switch ($this->method) {
		case 'simple':
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedAbsPath($this->resource) && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($this->resource, PATH_site . 'typo3temp/locks/')) {
				if (@unlink($this->resource) == FALSE) {
					$success = FALSE;
				}
			}
			break;
		case 'flock':
			if (is_resource($this->filepointer)) {
				if (flock($this->filepointer, LOCK_UN) == FALSE) {
					$success = FALSE;
				}
				fclose($this->filepointer);
			}
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedAbsPath($this->resource) && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($this->resource, PATH_site . 'typo3temp/locks/')) {
				@unlink($this->resource);
			}
			break;
		case 'semaphore':
			if (@sem_release($this->resource)) {
				sem_remove($this->resource);
			} else {
				$success = FALSE;
			}
			break;
		case 'disable':
			$success = FALSE;
			break;
		}
		$this->isAcquired = FALSE;
		return $success;
	}

	/**
	 * Return the locking method which is currently used
	 *
	 * @return string Locking method
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * Return the ID which is currently used
	 *
	 * @return string Locking ID
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Return the resource which is currently used.
	 * Depending on the locking method this can be a filename or a semaphore resource.
	 *
	 * @return mixed Locking resource (filename as string or semaphore as resource)
	 */
	public function getResource() {
		return $this->resource;
	}

	/**
	 * Return the status of a lock
	 *
	 * @return string Returns TRUE if lock is acquired, FALSE otherwise
	 */
	public function getLockStatus() {
		return $this->isAcquired;
	}

	/**
	 * Sets the facility (extension name) for the syslog entry.
	 *
	 * @param string $syslogFacility
	 */
	public function setSyslogFacility($syslogFacility) {
		$this->syslogFacility = $syslogFacility;
	}

	/**
	 * Enable/ disable logging
	 *
	 * @param boolean $isLoggingEnabled
	 */
	public function setEnableLogging($isLoggingEnabled) {
		$this->isLoggingEnabled = $isLoggingEnabled;
	}

	/**
	 * Adds a common log entry for this locking API using \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog().
	 * Example: 25-02-08 17:58 - cms: Locking [simple::0aeafd2a67a6bb8b9543fb9ea25ecbe2]: Acquired
	 *
	 * @param string $message The message to be logged
	 * @param integer $severity Severity - 0 is info (default), 1 is notice, 2 is warning, 3 is error, 4 is fatal error
	 * @return void
	 */
	public function sysLog($message, $severity = 0) {
		if ($this->isLoggingEnabled) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('Locking [' . $this->method . '::' . $this->id . ']: ' . trim($message), $this->syslogFacility, $severity);
		}
	}

}


?>