<?php
namespace TYPO3\CMS\Extensionmanager\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Susanne Moog, <typo3@susannemoog.de>
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
 * Download Queue - storage for extensions to be downloaded
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 */
class DownloadQueue implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Storage for extensions to be downloaded
	 *
	 * @var array<Tx_Extensionmanager_Domain_Model_Extension>
	 */
	protected $extensionStorage = array();

	/**
	 * Storage for extensions to be installed
	 *
	 * @var array
	 */
	protected $extensionInstallStorage = array();

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
	 */
	protected $listUtility;

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility
	 * @return void
	 */
	public function injectListUtility(\TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility) {
		$this->listUtility = $listUtility;
	}

	/**
	 * Adds an extension to the download queue.
	 * If the extension was already requested in a different version
	 * an exception is thrown.
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 * @param string $stack
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 * @return void
	 */
	public function addExtensionToQueue(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension, $stack = 'download') {
		if (!is_string($stack) || !in_array($stack, array('download', 'update'))) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Stack has to be either "download" or "update"', 1342432103);
		}
		if (array_key_exists($extension->getExtensionKey(), $this->extensionStorage)) {
			if (!($this->extensionStorage[$extension->getExtensionKey()] === $extension)) {
				throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException($extension->getExtensionKey() . ' was requested to be downloaded in different versions.', 1342432101);
			}
		}
		$this->extensionStorage[$stack][$extension->getExtensionKey()] = $extension;
	}

	/**
	 * @return array
	 */
	public function getExtensionQueue() {
		return $this->extensionStorage;
	}

	/**
	 * Remove an extension from download queue
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 * @param string $stack Stack to remove extension from (download, update or install)
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 * @return void
	 */
	public function removeExtensionFromQueue(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension, $stack = 'download') {
		if (!is_string($stack) || !in_array($stack, array('download', 'update'))) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Stack has to be either "download" or "update"', 1342432103);
		}
		if (array_key_exists($stack, $this->extensionStorage) && is_array($this->extensionStorage[$stack])) {
			if (array_key_exists($extension->getExtensionKey(), $this->extensionStorage[$stack])) {
				unset($this->extensionStorage[$stack][$extension->getExtensionKey()]);
			}
		}
	}

	/**
	 * Adds an extension to the install queue for later installation
	 *
	 * @param string $extensionKey
	 * @return void
	 */
	public function addExtensionToInstallQueue($extensionKey) {
		$this->extensionInstallStorage[$extensionKey] = $extensionKey;
	}

	/**
	 * Removes an extension from the install queue
	 *
	 * @param string $extensionKey
	 * @return void
	 */
	public function removeExtensionFromInstallQueue($extensionKey) {
		if (array_key_exists($extensionKey, $this->extensionInstallStorage)) {
			unset($this->extensionInstallStorage[$extensionKey]);
		}
	}

	/**
	 * Gets the extension installation queue
	 *
	 * @return array
	 */
	public function getExtensionInstallStorage() {
		return $this->extensionInstallStorage;
	}

}


?>