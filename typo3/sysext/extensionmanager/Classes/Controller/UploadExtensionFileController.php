<?php
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
class Tx_Extensionmanager_Controller_UploadExtensionFileController extends Tx_Extensionmanager_Controller_AbstractController {

	/**
	 * @var Tx_Extensionmanager_Utility_FileHandling
	 */
	protected $fileHandlingUtility;

	/**
	 * @var Tx_Extensionmanager_Utility_Connection_Ter
	 */
	protected $terUtility;

	/**
	 * @var Tx_Extensionmanager_Utility_Install
	 */
	protected $installUtility;

	/**
	 * @param Tx_Extensionmanager_Utility_FileHandling $fileHandlingUtility
	 * @return void
	 */
	public function injectFileHandlingUtility(Tx_Extensionmanager_Utility_FileHandling $fileHandlingUtility) {
		$this->fileHandlingUtility = $fileHandlingUtility;
	}

	/**
	 * @param Tx_Extensionmanager_Utility_Connection_Ter $terUtility
	 * @return void
	 */
	public function injectTerUtility(Tx_Extensionmanager_Utility_Connection_Ter $terUtility) {
		$this->terUtility = $terUtility;
	}

	/**
	 * @param Tx_Extensionmanager_Utility_Install $installUtility
	 * @return void
	 */
	public function injectInstallUtility(Tx_Extensionmanager_Utility_Install $installUtility) {
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
	 * @throws Tx_Extensionmanager_Exception_ExtensionManager
	 * @return void
	 */
	public function extractAction() {
		$file = $_FILES['tx_extensionmanager_tools_extensionmanagerextensionmanager'];
		$fileExtension = pathinfo($file['name']['extensionFile'], PATHINFO_EXTENSION);
		$fileName = pathinfo($file['name']['extensionFile'], PATHINFO_BASENAME);
		if (isset($file['name']['extensionFile']) && (
				$fileExtension !== 't3x' &&
				$fileExtension !== 'zip'
			)
		) {
			throw new Tx_Extensionmanager_Exception_ExtensionManager('Wrong file format given.', 1342858853);
		}
		if (isset($file['tmp_name']['extensionFile'])) {
			$tempFile = t3lib_div::upload_to_tempfile($file['tmp_name']['extensionFile']);
		} else {
			throw new Tx_Extensionmanager_Exception_ExtensionManager('Creating temporary file failed.', 1342864339);
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
	 * @throws Tx_Extensionmanager_Exception_ExtensionManager
	 * @return array
	 */
	protected function getExtensionFromT3xFile($file) {
		$fileContent = t3lib_div::getUrl($file);
		if (!$fileContent) {
			throw new Tx_Extensionmanager_Exception_ExtensionManager('File had no or wrong content.', 1342859339);
		}
		$extensionData = $this->terUtility->decodeExchangeData($fileContent);
		if ($extensionData['extKey']) {
			$this->fileHandlingUtility->unpackExtensionFromExtensionDataArray($extensionData);
			$this->installUtility->install($extensionData['extKey']);
		} else {
			throw new Tx_Extensionmanager_Exception_ExtensionManager('Decoding the file went wrong. No extension key found', 1342864309);
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
		$fileNameParts = t3lib_div::revExplode('_', $fileName, 2);
		$this->fileHandlingUtility->unzipExtensionFromFile($file, $fileNameParts[0]);
		$this->installUtility->install($fileNameParts[0]);
		return array('extKey' => $fileNameParts[0]);
	}
}
?>