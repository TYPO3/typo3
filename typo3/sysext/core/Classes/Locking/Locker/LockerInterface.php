<?php
namespace TYPO3\CMS\Core\Locking\Locker;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Daniel Hürtgen <huertgen@rheinschafe.de>
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
 * Locker interface
 *
 * @author Daniel Hürtgen <huertgen@rheinschafe.de>
 */
interface LockerInterface {

	/**
	 * Constructs locker.
	 *
	 * @param string $context String sets a scope/context for current locking.
	 * @param string $id      String sets an unique lock identifier.
	 * @param array  $options Array with locker options.
	 * @return \TYPO3\CMS\Core\Locking\Locker\LockerInterface
	 */
	public function __construct($context, $id, array $options = array());

	/**
	 * Acquire lock.
	 *
	 * @return boolean TRUE if lock could be acquired without beeing blocked. Otherwise throws exceptions.
	 * @throws \TYPO3\CMS\Core\Locking\Exception\LockBlockedException Thrown for internal usage only.
	 * @throws \TYPO3\CMS\Core\Locking\Exception\LockDelayedException Thrown if lock was acquired successfully, but has been blocked by other locks during acquiring.
	 * @throws \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeAcquiredWithinProposedRetriesException Thrown if lock can't be acquired within configured retries.
	 * @throws \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeAcquiredOnTimeException Thrown if lock can't be acquired before max_execution_time will be reached.
	 * @throws \TYPO3\CMS\Core\Locking\Exception\LockCouldNotBeAcquiredException Thrown if something wen't wrong during acquiring. Also base class of 'LockCouldNotBeAcquiredOnTimeException' && 'LockCouldNotBeAcquiredWithinProposedRetriesException'.
	 * @api
	 */
	public function acquire();

	/**
	 * Release lock.
	 *
	 * @return boolean TRUE if locked was release, otherwise throw lock exception.
	 * @api
	 */
	public function release();

	/**
	 * Is lock aquired?
	 *
	 * @return boolean TRUE if lock was acquired, otherwise FALSE.
	 * @api
	 */
	public function isAcquired();

	/**
	 * Returns string with the locker name.
	 *
	 * @return string
	 * @api
	 */
	public function getType();

	/**
	 * Returns unique lock identifier.
	 *
	 * @return mixed
	 * @api
	 */
	public function getId();

	/**
	 * Return unique id hash.
	 * 40 chars long string sha1().
	 *
	 * @return string
	 * @api
	 */
	public function getIdHash();

	/**
	 * Get context/prefix for hash.
	 *
	 * @return string
	 * @api
	 */
	public function getContext();

	/**
	 * Get acquire retries setting.
	 *
	 * @return integer
	 * @api
	 */
	public function getRetries();

	/**
	 * Set acquire fail retries setting.
	 *
	 * @param int $retries
	 * @return void
	 * @api
	 */
	public function setRetries($retries);

	/**
	 * Get acquire retry interval setting.
	 *
	 * @return integer
	 * @api
	 */
	public function getRetryInterval();

	/**
	 * Set acquire retry interval setting.
	 *
	 * @param int $retryInterval
	 * @return void
	 * @api
	 */
	public function setRetryInterval($retryInterval);

}

?>