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
 * This locker type used simple file lockings (is_file).
 *
 * @author Daniel Hürtgen <huertgen@rheinschafe.de>
 */
class FileLocker extends AbstractFileLocker implements LockerInterface {

	/**
	 * Returns string with the locker name.
	 *
	 * @return string
	 * @api
	 */
	public function getType() {
		return 'simple';
	}

	/**
	 * Runs garbage collection tasks for current lock.
	 *
	 * @return boolean TRUE, if found & cleaned up an stale lock, otherwise FALSE. Returning TRUE will trigger the overall garbage collection.
	 */
	protected function doCleanStaleLock() {
		if ($this->hasLockFile()) {
			$maxAgeTime = time() - $this->getOption('maxAge');
			if (@filectime($this->getFilePath()) < $maxAgeTime) {
				$this->deleteLockFile();
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Runs overall garbage collection.
	 *  Will be triggered, if doCLeanStaleLock() method returned TRUE due lock acquiring.
	 *
	 * @return void
	 */
	protected function doGarbageCollection() {
		$maxAgeTime = time() - $this->getOption('maxAge');
		$lockFiles = $this->getAllLockFiles();
		foreach ($lockFiles as $lockFile) {
			if (@filectime($lockFile) < $maxAgeTime) {

			}
		}

	}

	/**
	 * Do real lock acquiring magic.
	 *  Will be called several times from API method acquire() until lock was acquired successfully or maxRetries was reached.
	 *
	 * @param integer $try Current try counter
	 * @return boolean Return boolean TRUE on success, otherwise throw exceptions or at least you should return FALSE.
	 */
	protected function doAcquire($try) {
		return $this->createLockFile('x');
	}

	/**
	 * Do real lock releasing magic.
	 *  Will be called from API method release() and maybe from shutdown method to release posibile acquired locks.
	 *
	 * @return boolean TRUE if lock was released, otherwise throw exception.
	 */
	protected function doRelease() {
		return $this->deleteLockFile();
	}

}
