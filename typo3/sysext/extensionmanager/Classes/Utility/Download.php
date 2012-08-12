<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog <susanne.moog@typo3.org>
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
 * @package Extension Manager
 * @subpackage Utility
 */
class Tx_Extensionmanager_Utility_Download implements t3lib_Singleton {

	/**
	 * @var Tx_Extensionmanager_Utility_Connection_Ter
	 */
	protected $terUtility;

	/**
	 * @var Tx_Extensionmanager_Utility_Repository_Helper
	 */
	protected $repositoryHelper;

	/**
	 * @var string
	 */
	protected $downloadPath = 'Local';

	/**
	 * @param Tx_Extensionmanager_Utility_Connection_Ter $terUtility
	 * @return void
	 */
	public function injectTerUtility(Tx_Extensionmanager_Utility_Connection_Ter $terUtility) {
		$this->terUtility = $terUtility;
	}

	/**
	 * @param Tx_Extensionmanager_Utility_Repository_Helper $repositoryHelper
	 * @return void
	 */
	public function injectRepositoryHelper(Tx_Extensionmanager_Utility_Repository_Helper $repositoryHelper) {
		$this->repositoryHelper = $repositoryHelper;
	}

	/**
	 * @var Tx_Extensionmanager_Utility_FileHandling
	 */
	protected $fileHandlingUtility;

	/**
	 * @param Tx_Extensionmanager_Utility_FileHandling $fileHandlingUtility
	 * @return void
	 */
	public function injectFileHandlingUtility(Tx_Extensionmanager_Utility_FileHandling $fileHandlingUtility) {
		$this->fileHandlingUtility = $fileHandlingUtility;
	}

	/**
	 * Download an extension
	 *
	 * @param Tx_Extensionmanager_Domain_Model_Extension $extension
	 * @return void
	 */
	public function download(Tx_Extensionmanager_Domain_Model_Extension $extension) {
		$mirrorUrl = $this->repositoryHelper->getMirrors()->getMirrorUrl();
		$fetchedExtension = $this->terUtility->fetchExtension(
			$extension->getExtensionKey(),
			$extension->getVersion(),
			$extension->getMd5hash(),
			$mirrorUrl
		);
		if (isset($fetchedExtension['extKey']) && !empty($fetchedExtension['extKey']) && is_string($fetchedExtension['extKey'])) {
			$this->fileHandlingUtility->unpackExtensionFromExtensionDataArray($fetchedExtension, $extension, $this->getDownloadPath());
		}
	}

	/**
	 * Set the download path
	 *
	 * @param string $downloadPath
	 * @throws Tx_Extensionmanager_Exception_ExtensionManager
	 * @return void
	 */
	public function setDownloadPath($downloadPath) {
		if (!in_array($downloadPath, Tx_Extensionmanager_Domain_Model_Extension::returnAllowedInstallTypes())) {
			throw new Tx_Extensionmanager_Exception_ExtensionManager(
				htmlspecialchars($downloadPath) . ' not in allowed download paths',
				1344766387
			);
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