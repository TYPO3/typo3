<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Michael Stucki (michael@typo3.org)
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
 * Class for providing locking features in TYPO3
 *
 * $Id$
 *
 * @author	Michael Stucki <michael@typo3.org>
 */

require_once(PATH_t3lib.'class.t3lib_div.php');














/**
 * TYPO3 locking class
 * This class provides an abstract layer to various locking features for TYPO3
 *
 * It is intended to blocks requests until some data has been generated.
 * This is especially useful if two clients are requesting the same website short after each other. While the request of client 1 triggers building and caching of the website, client 2 will be waiting at this lock.
 *
 * @author	Michael Stucki <michael@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 * @see	class.t3lib_tstemplate.php, class.tslib_fe.php
 */
class t3lib_lock {
	private $method;
	private $id;		// Identifier used for this lock
	private $resource;	// Resource used for this lock (can be a file or a semaphore resource) 
	private $filepointer;
	private $isAcquired = false;

	private $loops = 150;	// Number of times a locked resource is tried to be acquired. This is only used by manual locks like the "simple" method.
	private $step = 200;	// Milliseconds after lock acquire is retried. $loops * $step results in the maximum delay of a lock. Only used by manual locks like the "simple" method.





	/**
	 * Constructor:
	 * initializes locking, check input parameters and set variables accordingly.
	 *
	 * @param	string		ID to identify this lock in the system
	 * @param	string		Define which locking method to use. Defaults to "simple".
	 * @param	integer		Number of times a locked resource is tried to be acquired. This is only used by manual locks like the "simple" method.
	 * @param	integer		Milliseconds after lock acquire is retried. $loops * $step results in the maximum delay of a lock. Only used by manual locks like the "simple" method.
	 * @return	boolean		Returns true unless something went wrong
	 */
	public function __construct($id, $method='', $loops=0, $steps=0)	{

			// Input checks
		$id = (string)$id;	// Force ID to be string
		if (intval($loops)) {
			$this->loops = intval($loops);
		}
		if (intval($step)) {
			$this->step = intval($step);
		}

			// Detect locking method
		if (in_array($method, array('disable', 'simple', 'flock', 'semaphore'))) {
			$this->method = $method;
		} else {
			throw new Exception('No such method "'.$method.'"');
		}

		$success = false;
		switch ($this->method) {
			case 'simple':
			case 'flock':
				$path = PATH_site.'typo3temp/locks/';
				if (!is_dir($path)) {
					t3lib_div::mkdir($path);
				}
				$this->id = md5($id);
				$this->resource = $path.$this->id;
				$success = true;
			break;
			case 'semaphore':
				$this->id = abs(crc32($id));
				if (($this->resource = sem_get($this->id, 1))==true) {
					$success = true;
				}
			break;
			case 'disable':
				return false;
			break;
		}

		return $success;
	}

	/**
	 * Acquire a lock and return when successful. If the lock is already open, the client will be
	 *
	 * It is important to know that the lock will be acquired in any case, even if the request was blocked first. Therefore, the lock needs to be released in every situation.
	 *
	 * @return	boolean		Returns true if lock could be acquired without waiting, false otherwise.
	 */
	public function acquire()	{
		$noWait = true;	// Default is true, which means continue without caring for other clients. In the case of TYPO3s cache management, this has no negative effect except some resource overhead.
		$isAcquired = true;

		switch ($this->method) {
			case 'simple':
				if (is_file($this->resource)) {
					$this->sysLog('Waiting for a different process to release the lock');
					$i = 0;
					while ($i<$this->loops) {
						$i++;
						usleep($this->step*1000);
						clearstatcache();
						if (!is_file($this->resource)) {	// Lock became free, leave the loop
							$this->sysLog('Different process released the lock');
							$noWait = false;
							break;
						}
					}
				} else {
					$noWait = true;
				}

				if (($this->filepointer = touch($this->resource)) == false) {
					throw new Exception('Lock file could not be created');
				}
			break;
			case 'flock':
				if (($this->filepointer = fopen($this->resource, 'w+')) == false) {
					throw new Exception('Lock file could not be opened');
				}

				if (flock($this->filepointer, LOCK_EX|LOCK_NB) == true) {	// Lock without blocking
					$noWait = true;
				} elseif (flock($this->filepointer, LOCK_EX) == true) {		// Lock with blocking (waiting for similar locks to become released)
					$noWait = false;
				} else {
					throw new Exception('Could not lock file "'.$this->resource.'"');
				}
			break;
			case 'semaphore':
				if (sem_acquire($this->resource)) {
						// Unfortunately it seems not possible to find out if the request was blocked, so we return false in any case to make sure the operation is tried again.
					$noWait = false;
				}
			break;
			case 'disable':
				$noWait = false;
				$isAcquired = false;
			break;
		}

		$this->isAcquired = $isAcquired;
		return $noWait;
	}

	/**
	 * Release the lock
	 *
	 * @return	boolean		Returns true on success or false on failure
	 */
	public function release()	{
		if (!$this->isAcquired) {
			return true;
		}

		$success = true;
		switch ($this->method) {
			case 'simple':
				if (unlink($this->resource) == false) {
					$success = false;
				}
			break;
			case 'flock':
				if (flock($this->filepointer, LOCK_UN) == false) {
					$success = false;
				}
				fclose($this->filepointer);
				unlink($this->resource);
			break;
			case 'semaphore':
				if (sem_release($this->resource)) {
					sem_remove($this->resource);
				} else {
					$success = false;
				}
			break;
			case 'disable':
				$success = false;
			break;
		}

		$this->isAcquired = false;
		return $success;
	}

	/**
	 * Return the locking method which is currently used
	 *
	 * @return	string		Locking method
	 */
	public function getMethod()	{
		return $this->method;
	}

	/**
	 * Return the ID which is currently used
	 *
	 * @return	string		Locking ID
	 */
	public function getId()	{
		return $this->id;
	}

	/**
	 * Return the resource which is currently used.
	 * Depending on the locking method this can be a filename or a semaphore resource.
	 *
	 * @return	mixed		Locking resource (filename as string or semaphore as resource)
	 */
	public function getResource()	{
		return $this->resource;
	}

	/**
	 * Return the status of a lock
	 *
	 * @return	string		Returns true if lock is acquired, false otherwise
	 */
	public function getLockStatus()	{
		return $this->isAcquired;
	}

	/**
	 * Adds a common log entry for this locking API using t3lib_div::sysLog().
	 * Example: 25-02-08 17:58 - cms: Locking [simple::0aeafd2a67a6bb8b9543fb9ea25ecbe2]: Acquired
	 *
	 * @param	string		$message: The message to be logged
	 * @param	integer		$severity: Severity - 0 is info (default), 1 is notice, 2 is warning, 3 is error, 4 is fatal error
	 * @return	void
	 */
	public function sysLog($message, $severity=0) {
		t3lib_div::sysLog('Locking ['.$this->method.'::'.$this->id.']: '.trim($message), 'cms', $severity);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_lock.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_lock.php']);
}
?>