<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog, <typo3@susannemoog.de>
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
 * @package Extension Manager
 * @subpackage Controller
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
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 * @return void
	 */
	public function extractAction() {
		$file = $_FILES['tx_extensionmanager_tools_extensionmanagerextensionmanager'];
		$fileExtension = pathinfo($file['name']['extensionFile'], PATHINFO_EXTENSION);
		$fileName = pathinfo($file['name']['extensionFile'], PATHINFO_BASENAME);
		if (isset($file['name']['extensionFile']) && ($fileExtension !== 't3x' && $fileExtension !== 'zip')) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Wrong file format given.', 1342858853);
		}
		if (isset($file['tmp_name']['extensionFile'])) {
			$tempFile = \TYPO3\CMS\Core\Utility\GeneralUtility::upload_to_tempfile($file['tmp_name']['extensionFile']);
		} else {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Creating temporary file failed.', 1342864339);
		}
		if ($fileExtension === 't3x') {
			$extensionData = $this->getExtensionFromT3xFile($tempFile);
		} else {
			$extensionData = $this->getExtensionFromZipFile($tempFile, $fileName);
		}
		$this->view->assign('extensionKey', $extensionData['extKey']);
	}

	/**
	 * Extracts a given t3x file and installs the extension
	 *
	 * @param string $file
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 * @return array
	 */
	protected function getExtensionFromT3xFile($file) {
		$fileContent = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($file);
		if (!$fileContent) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('File had no or wrong content.', 1342859339);
		}
		$extensionData = $this->terUtility->decodeExchangeData($fileContent);
		if ($extensionData['extKey']) {
			$this->fileHandlingUtility->unpackExtensionFromExtensionDataArray($extensionData);
			$this->installUtility->install($extensionData['extKey']);
		} else {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException('Decoding the file went wrong. No extension key found', 1342864309);
		}
		return $extensionData;
	}

	/**
	 * Extracts a given zip file and installs the extension
	 * As there is no information about the extension key in the zip
	 * we have to use the file name to get that information
	 * filename format is expected to be extensionkey_version.zip
	 *
	 * @param string $file path to uploaded file
	 * @param string $fileName filename (basename) of uploaded file
	 * @return array
	 */
	protected function getExtensionFromZipFile($file, $fileName) {
		$fileNameParts = \TYPO3\CMS\Core\Utility\GeneralUtility::revExplode('_', $fileName, 2);
		$this->fileHandlingUtility->unzipExtensionFromFile($file, $fileNameParts[0]);
		$this->installUtility->install($fileNameParts[0]);
		return array('extKey' => $fileNameParts[0]);
	}

}


?>