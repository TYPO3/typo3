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
 * Controller for handling upload of a local extension file
 * Handles .t3x or .zip files
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 */
class UploadExtensionFileController extends \TYPO3\CMS\Extensionmanager\Controller\AbstractController {

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility
	 */
	protected $fileHandlingUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\Connection\TerUtility
	 */
	protected $terUtility;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\InstallUtility
	 */
	protected $installUtility;

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility $fileHandlingUtility
	 * @return void
	 */
	public function injectFileHandlingUtility(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility $fileHandlingUtility) {
		$this->fileHandlingUtility = $fileHandlingUtility;
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\Connection\TerUtility $terUtility
	 * @return void
	 */
	public function injectTerUtility(\TYPO3\CMS\Extensionmanager\Utility\Connection\TerUtility $terUtility) {
		$this->terUtility = $terUtility;
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Utility\InstallUtility $installUtility
	 * @return void
	 */
	public function injectInstallUtility(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility $installUtility) {
		$this->installUtility = $installUtility;
	}

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
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 * @return void
	 */
	public function extractAction($overwrite = FALSE) {
		try {
			$file = $_FILES['tx_extensionmanager_tools_extensionmanagerextensionmanager'];
			$fileExtension = pathinfo($file['name']['extensionFile'], PATHINFO_EXTENSION);
			$fileName = pathinfo($file['name']['extensionFile'], PATHINFO_BASENAME);
			if (empty($file['name']['extensionFile'])) {
				throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('No file given.', 1342858852);
			}
			if ($fileExtension !== 't3x' && $fileExtension !== 'zip') {
				throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Wrong file format given.', 1342858853);
			}
			if (!empty($file['tmp_name']['extensionFile'])) {
				$tempFile = \TYPO3\CMS\Core\Utility\GeneralUtility::upload_to_tempfile($file['tmp_name']['extensionFile']);
			} else {
				throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException(
					'Creating temporary file failed. Check your upload_max_filesize and post_max_size limits.',
					1342864339
				);
			}
			if ($fileExtension === 't3x') {
				$extensionData = $this->getExtensionFromT3xFile($tempFile, $overwrite);
			} else {
				$extensionData = $this->getExtensionFromZipFile($tempFile, $fileName, $overwrite);
			}
		} catch (\Exception $exception) {
			$this->view->assign('error', $exception->getMessage());
		}
		$this->view->assign('extensionKey', $extensionData['extKey']);
	}

	/**
	 * Extracts a given t3x file and installs the extension
	 *
	 * @param string $file Path to uploaded file
	 * @param boolean $overwrite Overwrite existing extension if TRUE
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 * @return array
	 */
	protected function getExtensionFromT3xFile($file, $overwrite = FALSE) {
		$fileContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($file);
		if (!$fileContent) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('File had no or wrong content.', 1342859339);
		}
		$extensionData = $this->terUtility->decodeExchangeData($fileContent);
		if (empty($extensionData['extKey'])) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Decoding the file went wrong. No extension key found', 1342864309);
		}
		if (!$overwrite && $this->installUtility->isAvailable($extensionData['extKey'])) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException($this->translate('extensionList.overwritingDisabled'), 1342864310);
		}
		$this->fileHandlingUtility->unpackExtensionFromExtensionDataArray($extensionData);
		$this->installUtility->install($extensionData['extKey']);
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
	 */
	protected function getExtensionFromZipFile($file, $fileName, $overwrite = FALSE) {
			// Remove version and ending from filename to determine extension key
		$extensionKey = preg_replace('/_(\d+)(\.|\-)(\d+)(\.|\-)(\d+)/i', '', strtolower($fileName));
		$extensionKey = substr($extensionKey, 0, strrpos($extensionKey, '.'));
		if (!$overwrite && $this->installUtility->isAvailable($extensionKey)) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Extension is already available and overwriting is disabled.', 1342864311);
		}
		$this->fileHandlingUtility->unzipExtensionFromFile($file, $extensionKey);
		$this->installUtility->install($extensionKey);
		return array('extKey' => $extensionKey);
	}

}


?>