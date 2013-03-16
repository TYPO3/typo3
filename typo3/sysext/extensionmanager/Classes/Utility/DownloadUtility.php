<?php
namespace TYPO3\CMS\Extensionmanager\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Susanne Moog <susanne.moog@typo3.org>
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
 * Utility for dealing with ext_emconf
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 */
class DownloadUtility implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\Connection\TerUtility
	 */
	protected $terUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper
	 */
	protected $repositoryHelper;

	/**
	 * @var string
	 */
	protected $downloadPath = 'Local';

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\Connection\TerUtility $terUtility
	 * @return void
	 */
	public function injectTerUtility(\TYPO3\CMS\Extensionmanager\Utility\Connection\TerUtility $terUtility) {
		$this->terUtility = $terUtility;
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper $repositoryHelper
	 * @return void
	 */
	public function injectRepositoryHelper(\TYPO3\CMS\Extensionmanager\Utility\Repository\Helper $repositoryHelper) {
		$this->repositoryHelper = $repositoryHelper;
	}

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility
	 */
	protected $fileHandlingUtility;

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility $fileHandlingUtility
	 * @return void
	 */
	public function injectFileHandlingUtility(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility $fileHandlingUtility) {
		$this->fileHandlingUtility = $fileHandlingUtility;
	}

	/**
	 * Download an extension
	 *
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension
	 * @return void
	 */
	public function download(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension $extension) {
		$mirrorUrl = $this->repositoryHelper->getMirrors()->getMirrorUrl();
		$fetchedExtension = $this->terUtility->fetchExtension($extension->getExtensionKey(), $extension->getVersion(), $extension->getMd5hash(), $mirrorUrl);
		if (isset($fetchedExtension['extKey']) && !empty($fetchedExtension['extKey']) && is_string($fetchedExtension['extKey'])) {
			$this->fileHandlingUtility->unpackExtensionFromExtensionDataArray($fetchedExtension, $extension, $this->getDownloadPath());
		}
	}

	/**
	 * Set the download path
	 *
	 * @param string $downloadPath
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 * @return void
	 */
	public function setDownloadPath($downloadPath) {
		if (!in_array($downloadPath, \TYPO3\CMS\Extensionmanager\Domain\Model\Extension::returnAllowedInstallTypes())) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException(htmlspecialchars($downloadPath) . ' not in allowed download paths', 1344766387);
		}
		$this->downloadPath = $downloadPath;
	}

	/**
	 * Get the download path
	 *
	 * @return string
	 */
	public function getDownloadPath() {
		return $this->downloadPath;
	}

}


?>