<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

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
 * Controller for handling extension related actions like
 * installing, removing, downloading of data or files
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 */
class ActionController extends \TYPO3\CMS\Extensionmanager\Controller\AbstractController {

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\InstallUtility
	 */
	protected $installUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility
	 */
	protected $fileHandlingUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService
	 */
	protected $managementService;

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\InstallUtility $installUtility
	 */
	public function injectInstallUtility(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility $installUtility) {
		$this->installUtility = $installUtility;
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility $fileHandlingUtility
	 */
	public function injectFileHandlingUtility(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility $fileHandlingUtility) {
		$this->fileHandlingUtility = $fileHandlingUtility;
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService $managementService
	 */
	public function injectManagementService(\TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService $managementService) {
		$this->managementService = $managementService;
	}

	/**
	 * Toggle extension installation state action
	 *
	 * @param string $extension
	 */
	protected function toggleExtensionInstallationStateAction($extension) {
		$installedExtensions = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getLoadedExtensionListArray();
		if (in_array($extension, $installedExtensions)) {
			// uninstall
			$this->installUtility->uninstall($extension);
		} else {
			// install
			$this->managementService->resolveDependenciesAndInstall(
				$this->installUtility->enrichExtensionWithDetails($extension)
			);
		}
		$this->redirect('index', 'List', NULL, array(self::TRIGGER_RefreshModuleMenu => TRUE));
	}

	/**
	 * Remove an extension (if it is still installed, uninstall it first)
	 *
	 * @param string $extension
	 */
	protected function removeExtensionAction($extension) {
		$success = TRUE;
		$message = '';
		try {
			if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extension)) {
				$this->installUtility->uninstall($extension);
			}
			$this->installUtility->removeExtension($extension);
		} catch (\TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException $e) {
			$message = $e->getMessage();
			$success = FALSE;
		}
		$this->view->assign('success', $success)->assign('message', $message)->assign('extension', $extension);
	}

	/**
	 * Download an extension as a zip file
	 *
	 * @param string $extension
	 */
	protected function downloadExtensionZipAction($extension) {
		$fileName = $this->fileHandlingUtility->createZipFileFromExtension($extension);
		$this->fileHandlingUtility->sendZipFileToBrowserAndDelete($fileName);
	}

	/**
	 * Download data of an extension as sql statements
	 *
	 * @param string $extension
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 */
	protected function downloadExtensionDataAction($extension) {
		$error = NULL;
		$sqlData = $this->installUtility->getExtensionSqlDataDump($extension);
		$dump = $sqlData['extTables'] . $sqlData['staticSql'];
		$fileName = $extension . '_sqlDump.sql';
		$filePath = PATH_site . 'typo3temp/' . $fileName;
		$error = \TYPO3\CMS\Core\Utility\GeneralUtility::writeFileToTypo3tempDir($filePath, $dump);
		if (is_string($error)) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException($error, 1343048718);
		}
		$this->fileHandlingUtility->sendSqlDumpFileToBrowserAndDelete($filePath, $fileName);
	}
}
?>