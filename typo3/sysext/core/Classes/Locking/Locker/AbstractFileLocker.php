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
abstract class AbstractFileLocker extends AbstractLocker implements LockerInterface {

	/**
	 * File resource pointer.
	 *
	 * @var resource
	 */
	protected $fileResource;

	/**
	 * Constructs locker.
	 *
	 * @param string $context String sets a scope/context for current locking.
	 * @param string $id      String sets an unique lock identifier.
	 * @param array  $options Array with locker options.
	 * @return \TYPO3\CMS\Core\Locking\Locker\AbstractFileLocker
	 */
	public function __construct($context, $id, array $options = array()) {

		$this->options = array_merge(
			$this->options,
			array(
			     'path' => PATH_site . 'typo3temp/locks/',
			     'format' => '%type%_%hash%.lock',
			)
		);

		parent::__construct($context, $id, $options);
	}

	/**
	 * Sets locking file path.
	 *
	 * @param string $path
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return void
	 */
	public function setPath($path) {
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedAbsPath($path)) {
			// TODO
			throw new \InvalidArgumentException();
		}
		if ($this->isAcquired()) {
			// TODO
			throw new \RuntimeException();
		}
		$this->options['path'] = (string) $path;
	}

	/**
	 * Gets locking file path.
	 *
	 * @return string
	 */
	public function getPath() {
		return $this->options['path'];
	}

	/**
	 * Gets file lock name.
	 *
	 * @return string
	 */
	public function getFileName() {
		if ($this->hasOption('format')) {
			$fileName = $this->getOption('format');
			$fileName = str_replace('%type%', $this->getType(), $fileName);
			$fileName = str_replace('%hash%', $this->getIdHash(), $fileName);
			return $fileName;
		} else {
			return $this->getIdHash();
		}
	}

	/**
	 * Gets full file path.
	 *
	 * @return string
	 */
	public function getFilePath() {
		return $this->getPath() . $this->getFileName();
	}

	/**
	 * Creates lock path (mkdir) if required.
	 *
	 * @return void
	 */
	protected function createLockPath() {
		if (!is_dir($this->getPath())) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($this->getPath());
		}
	}

	/**
	 * Checks if lockfile exists.
	 *
	 * @return boolean
	 */
	protected function hasLockFile() {
		return is_file($this->getFilePath());
	}

	/**
	 * Creates/open file-lock file on filesystem (fopen).
	 *
	 * @param string  $mode
	 * @param boolean $releaseResource
	 * @return boolean
	 */
	protected function createLockFile($mode, $releaseResource = TRUE) {
		$this->createLockPath();
		if (FALSE !== $this->openFileResources($this->getFilePath(), $mode)) {
			$this->fixPermissions($this->getFilePath());
			if ($releaseResource) {
				$this->closeFileResource();
			}
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Open file resource.
	 *
	 * @param string $path
	 * @param string $mode
	 * @return boolean
	 */
	protected function openFileResources($path, $mode) {
		if (!is_resource($this->fileResource)) {
			$fp = @fopen($path, $mode);
			if ($fp !== FALSE) {
				$this->fileResource = $fp;
				return TRUE;
			}
		} else {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Close file resource (fclose).
	 *
	 * @return boolean
	 */
	protected function closeFileResource() {
		if (is_resource($this->fileResource)) {
			return @fclose($this->fileResource);
		}
		return TRUE;
	}

	/**
	 * Fix file/folder permissions for path.
	 *
	 * @param string  $path
	 * @param boolean $recursive
	 * @return boolean
	 */
	protected function fixPermissions($path, $recursive = FALSE) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::fixPermissions($path, $recursive);
	}

	/**
	 * Deletes file-lock file from filesystem (unlink).
	 *
	 * @return boolean
	 */
	protected function deleteLockFile() {
		return $this->closeFileResource() && @unlink($this->getFilePath());
	}

	/**
	 * Get array of all lock files.
	 *
	 * @return array
	 */
	protected function getAllLockFiles() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::getFilesInDir($this->getPath(), 'lock');
	}

}
