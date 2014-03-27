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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;

/**
 * Controller for handling upload of a local extension file
 * Handles .t3x or .zip files
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 */
class UploadExtensionFileController extends AbstractController {

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility
	 * @inject
	 */
	protected $fileHandlingUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\Connection\TerUtility
	 * @inject
	 */
	protected $terUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\InstallUtility
	 * @inject
	 */
	protected $installUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService
	 * @inject
	 */
	protected $managementService;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility
	 * @inject
	 */
	protected $extensionModelUtility;

	/**
	 * Render upload extension form
	 *
	 * @return void
	 */
	public function formAction() {

	}

	/**
	 * Extract an uploaded file and install the matching extension
	 *
	 * @param boolean $overwrite Overwrite existing extension if TRUE
	 * @throws ExtensionManagerException
	 * @return void
	 */
	public function extractAction($overwrite = FALSE) {
		try {
			$file = $_FILES['tx_extensionmanager_tools_extensionmanagerextensionmanager'];
			$fileExtension = pathinfo($file['name']['extensionFile'], PATHINFO_EXTENSION);
			$fileName = pathinfo($file['name']['extensionFile'], PATHINFO_BASENAME);
			if (empty($file['name']['extensionFile'])) {
				throw new ExtensionManagerException('No file given.', 1342858852);
			}
			if ($fileExtension !== 't3x' && $fileExtension !== 'zip') {
				throw new ExtensionManagerException('Wrong file format given.', 1342858853);
			}
			if (!empty($file['tmp_name']['extensionFile'])) {
				$tempFile = \TYPO3\CMS\Core\Utility\GeneralUtility::upload_to_tempfile($file['tmp_name']['extensionFile']);
			} else {
				throw new ExtensionManagerException(
					'Creating temporary file failed. Check your upload_max_filesize and post_max_size limits.',
					1342864339
				);
			}
			if ($fileExtension === 't3x') {
				$extensionData = $this->getExtensionFromT3xFile($tempFile, $overwrite);
			} else {
				$extensionData = $this->getExtensionFromZipFile($tempFile, $fileName, $overwrite);
			}
			$this->view->assign('extensionKey', $extensionData['extKey']);
		} catch (\Exception $exception) {
			$this->view->assign('error', $exception->getMessage());
		}
	}

	/**
	 * Extracts a given t3x file and installs the extension
	 *
	 * @param string $file Path to uploaded file
	 * @param boolean $overwrite Overwrite existing extension if TRUE
	 * @throws ExtensionManagerException
	 * @return array
	 */
	protected function getExtensionFromT3xFile($file, $overwrite = FALSE) {
		$fileContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($file);
		if (!$fileContent) {
			throw new ExtensionManagerException('File had no or wrong content.', 1342859339);
		}
		$extensionData = $this->terUtility->decodeExchangeData($fileContent);
		if (empty($extensionData['extKey'])) {
			throw new ExtensionManagerException('Decoding the file went wrong. No extension key found', 1342864309);
		}
		if (!$overwrite && $this->installUtility->isAvailable($extensionData['extKey'])) {
			throw new ExtensionManagerException($this->translate('extensionList.overwritingDisabled'), 1342864310);
		}
		$this->fileHandlingUtility->unpackExtensionFromExtensionDataArray($extensionData);
		$this->installExtension($extensionData['extKey']);
		return $extensionData;
	}

	/**
	 * Extracts a given zip file and installs the extension
	 * As there is no information about the extension key in the zip
	 * we have to use the file name to get that information
	 * filename format is expected to be extensionkey_version.zip
	 *
	 * @param string $file Path to uploaded file
	 * @param string $fileName Filename (basename) of uploaded file
	 * @param boolean $overwrite Overwrite existing extension if TRUE
	 * @return array
	 * @throws ExtensionManagerException
	 */
	protected function getExtensionFromZipFile($file, $fileName, $overwrite = FALSE) {
			// Remove version and ending from filename to determine extension key
		$extensionKey = preg_replace('/_(\d+)(\.|\-)(\d+)(\.|\-)(\d+).*/i', '', strtolower(substr($fileName, 0, -4)));
		if (!$overwrite && $this->installUtility->isAvailable($extensionKey)) {
			throw new ExtensionManagerException('Extension is already available and overwriting is disabled.', 1342864311);
		}
		$this->fileHandlingUtility->unzipExtensionFromFile($file, $extensionKey);
		$this->installExtension($extensionKey);
		return array('extKey' => $extensionKey);
	}

	/**
	 * Install extension if not yet installed
	 *
	 * @param string $extensionKey
	 * @return bool
	 */
	protected function installExtension($extensionKey) {
		$installedExtensions = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getLoadedExtensionListArray();
		if (in_array($extensionKey, $installedExtensions)) {
			return TRUE;
		}
		try {
			// install
			$this->managementService->resolveDependenciesAndInstall(
				$this->extensionModelUtility->mapExtensionArrayToModel(
					$this->installUtility->enrichExtensionWithDetails($extensionKey)
				)
			);
			return TRUE;
		} catch (ExtensionManagerException $e) {
			$message = nl2br(htmlspecialchars($e->getMessage())) . $this->getForceInstallationMessage($extensionKey, 'Action');
			$this->addFlashMessage($message, '', \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
		} catch (\TYPO3\Flow\Package\Exception\PackageStatesFileNotWritableException $e) {
			$this->addFlashMessage(htmlspecialchars($e->getMessage()), '', \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
		}

		return FALSE;
	}
}
