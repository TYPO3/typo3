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
 * Utility for dealing with files and folders
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 * @package Extension Manager
 * @subpackage Utility
 */
class Tx_Extensionmanager_Utility_FileHandling implements t3lib_Singleton {

	/**
	 * @var Tx_Extensionmanager_Utility_EmConf
	 */
	protected $emConfUtility;

	/**
	 * Injector for Tx_Extensionmanager_Utility_EmConf
	 *
	 * @param Tx_Extensionmanager_Utility_EmConf $emConfUtility
	 * @return void
	 */
	public function injectEmConfUtility(Tx_Extensionmanager_Utility_EmConf $emConfUtility) {
		$this->emConfUtility = $emConfUtility;
	}

	/**
	 * @var Tx_Extensionmanager_Utility_Install
	 */
	protected $installUtility;

	/**
	 * Injector for Tx_Extensionmanager_Utility_Install
	 *
	 * @param Tx_Extensionmanager_Utility_Install $installUtility
	 * @return void
	 */
	public function injectInstallUtility(Tx_Extensionmanager_Utility_Install $installUtility) {
		$this->installUtility = $installUtility;
	}

	/**
	 * Unpack an extension in t3x data format and write files
	 *
	 * @param array $extensionData
	 * @param Tx_Extensionmanager_Domain_Model_Extension $extension
	 * @param string $pathType
	 * @return void
	 */
	public function unpackExtensionFromExtensionDataArray(
		array $extensionData,
		Tx_Extensionmanager_Domain_Model_Extension $extension = NULL,
		$pathType = 'Local'
	) {
		$extensionDir = $this->makeAndClearExtensionDir($extensionData['extKey'], $pathType);
		$files = $this->extractFilesArrayFromExtensionData($extensionData);
		$directories = $this->extractDirectoriesFromExtensionData($files);
		$this->createDirectoriesForExtensionFiles($directories, $extensionDir);
		$this->writeExtensionFiles($files, $extensionDir);
		$this->writeEmConfToFile($extensionData, $extensionDir, $extension);
	}

	/**
	 * Extract needed directories from given extensionDataFilesArray
	 *
	 * @param array $files
	 * @return array
	 */
	protected function extractDirectoriesFromExtensionData(array $files) {
		$directories = array();
		foreach ($files as $filePath => $file) {
			preg_match('/(.*)\//', $filePath, $matches);
			$directories[] = $matches[0];
		}
		return $directories;
	}

	/**
	 * Returns the "FILES" part from the data array
	 *
	 * @param array $extensionData
	 * @return mixed
	 */
	protected function extractFilesArrayFromExtensionData(array $extensionData) {
		return $extensionData['FILES'];
	}

	/**
	 * Loops over an array of directories and creates them in the given root path
	 * It also creates nested directory structures
	 *
	 * @param array $directories
	 * @param string $rootPath
	 * @return void
	 */
	protected function createDirectoriesForExtensionFiles(array $directories, $rootPath) {
		foreach ($directories as $directory) {
			t3lib_div::mkdir_deep($rootPath . $directory);
		}
	}

	/**
	 * Loops over an array of files and writes them to the given rootPath
	 *
	 * @param array $files
	 * @param string $rootPath
	 * @return void
	 */
	protected function writeExtensionFiles(array $files, $rootPath) {
		foreach ($files as $file) {
			t3lib_div::writeFile($rootPath . $file['name'], $file['content']);
		}
	}
	/**
	 * Removes the current extension of $type and creates the base folder for
	 * the new one (which is going to be imported)
	 *
	 * @param string $extensionkey
	 * @param string $pathType Extension installation scope (Local,Global,System)
	 * @throws Tx_Extensionmanager_Exception_ExtensionManager
	 * @return string
	 */
	protected function makeAndClearExtensionDir($extensionkey, $pathType = 'Local') {
		$paths = Tx_Extensionmanager_Domain_Model_Extension::returnInstallPaths();
		$path = $paths[$pathType];
		if (!$path || !is_dir($path) || !$extensionkey) {
			throw new Tx_Extensionmanager_Exception_ExtensionManager(
				sprintf('ERROR: The extension install path "%s" was no directory!', $path),
				1337280417
			);
		} else {
			$extDirPath = $path . $extensionkey . '/';
			if (is_dir($extDirPath)) {
				$this->removeDirectory($extDirPath);
			}
			$this->addDirectory($extDirPath);
		}
		return $extDirPath;
	}

	/**
	 * Add specified directory
	 *
	 * @param string $extDirPath
	 * @throws Tx_Extensionmanager_Exception_ExtensionManager
	 * @return void
	 */
	protected function addDirectory($extDirPath) {
		t3lib_div::mkdir($extDirPath);
		if (!is_dir($extDirPath)) {
			throw new Tx_Extensionmanager_Exception_ExtensionManager(
				sprintf($GLOBALS['LANG']->getLL('clearMakeExtDir_could_not_create_dir'), $extDirPath),
				1337280416
			);
		}
	}

	/**
	 * Remove specified directory
	 *
	 * @param string $extDirPath
	 * @throws Tx_Extensionmanager_Exception_ExtensionManager
	 * @return void
	 */
	public function removeDirectory($extDirPath) {
		$res = t3lib_div::rmdir($extDirPath, TRUE);
		if ($res === FALSE) {
			throw new Tx_Extensionmanager_Exception_ExtensionManager(
				sprintf($GLOBALS['LANG']->getLL('clearMakeExtDir_could_not_remove_dir'), $extDirPath),
				1337280415
			);
		}
	}

	/**
	 * Constructs emConf and writes it to corresponding file
	 *
	 * @param array $extensionData
	 * @param string $rootPath
	 * @param Tx_Extensionmanager_Domain_Model_Extension $extension
	 * @return void
	 */
	protected function writeEmConfToFile(
		array $extensionData,
		$rootPath,
		Tx_Extensionmanager_Domain_Model_Extension $extension = NULL
	) {
		$emConfContent = $this->emConfUtility->constructEmConf($extensionData, $extension);
		t3lib_div::writeFile($rootPath . 'ext_emconf.php', $emConfContent);
	}

	/**
	 * Is the given path a valid path for extension installation
	 *
	 * @param string $path the absolute (!) path in question
	 * @return boolean
	 */
	public function isValidExtensionPath($path) {
		$allowedPaths = Tx_Extensionmanager_Domain_Model_Extension::returnAllowedInstallPaths();
		foreach ($allowedPaths as $allowedPath) {
			if (t3lib_div::isFirstPartOfStr($path, $allowedPath)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Returns absolute path
	 *
	 * @param string $relativePath
	 * @return string
	 */
	public function returnAbsolutePath($relativePath) {
		return t3lib_div::resolveBackPath(PATH_site . $relativePath);
	}

	/**
	 * Get extension path for an available or installed extension
	 *
	 * @param string $extension
	 * @return string
	 */
	public function getAbsoluteExtensionPath($extension) {
		$extension = $this->installUtility->enrichExtensionWithDetails($extension);
		$absolutePath = $this->returnAbsolutePath($extension['siteRelPath']);
		return $absolutePath;
	}

	/**
	 * Create a zip file from an extension
	 *
	 * @param array $extension
	 * @return string
	 */
	public function createZipFileFromExtension($extension) {
		$extensionPath = $this->getAbsoluteExtensionPath($extension);
		$fileName = PATH_site . 'typo3temp/' . $extension . '.zip';
		$zip = new ZipArchive;
		$zip->open($fileName, ZipArchive::CREATE);
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($extensionPath)
		);

		foreach ($iterator as $key => $value) {
			$archiveName = str_replace($extensionPath, '', $key);
			if (t3lib_utility_String::isLastPartOfString($key, '.')) {
				continue;
			} else {
				$zip->addFile($key, $archiveName);
			}
		}

		$zip->close();
		return $fileName;
	}

	/**
	 * Unzip an extension.zip.
	 *
	 * @param string $file path to zip file
	 * @param string $fileName file name
	 * @param string $pathType path type (Local, Global, System)
	 * @throws Tx_Extensionmanager_Exception_ExtensionManager
	 * @return void
	 */
	public function unzipExtensionFromFile($file, $fileName, $pathType = 'Local') {
		$extensionDir = $this->makeAndClearExtensionDir($fileName, $pathType);
		$zip = zip_open($file);
		if (is_resource($zip)) {
			while (($zipEntry = zip_read($zip)) !== FALSE) {
				if (strpos(zip_entry_name($zipEntry), DIRECTORY_SEPARATOR) !== FALSE) {
					$last = strrpos(zip_entry_name($zipEntry), DIRECTORY_SEPARATOR);
					$dir = substr(zip_entry_name($zipEntry), 0, $last);
					$file = substr(zip_entry_name($zipEntry), strrpos(zip_entry_name($zipEntry), DIRECTORY_SEPARATOR) + 1);
					if (!is_dir($dir)) {
						t3lib_div::mkdir_deep($extensionDir . $dir);
					}
					if (strlen(trim($file)) > 0) {
						$return = t3lib_div::writeFile(
							$extensionDir . $dir . '/' . $file, zip_entry_read($zipEntry, zip_entry_filesize($zipEntry))
						);
						if ($return === FALSE) {
							throw new Tx_Extensionmanager_Exception_ExtensionManager('Could not write file ' . $file, 1344691048);
						}
					}
				} else {
					t3lib_div::writeFile($extensionDir . zip_entry_name($zipEntry), zip_entry_read($zipEntry, zip_entry_filesize($zipEntry)));
				}
			}
		} else {
			throw new Tx_Extensionmanager_Exception_ExtensionManager('Unable to open zip file ' . $file, 1344691049);
		}
	}

	/**
	 * Sends a zip file to the browser and deletes it afterwards
	 *
	 * @param string $fileName
	 * @param string $downloadName
	 * @return void
	 */
	public function sendZipFileToBrowserAndDelete($fileName, $downloadName = '') {
		if ($downloadName === '') {
			$downloadName = basename($fileName, '.zip');
		}
		header('Content-Type: application/zip');
		header('Content-Length: ' . filesize($fileName));
		header('Content-Disposition: attachment; filename="' . $downloadName . '.zip"');
		readfile($fileName);
		unlink($fileName);
		exit();
	}

	/**
	 * Sends the sql dump file to the browser and deletes it afterwards
	 *
	 * @param string $fileName
	 * @param string $downloadName
	 * @return void
	 */
	public function sendSqlDumpFileToBrowserAndDelete($fileName, $downloadName = '') {
		if ($downloadName === '') {
			$downloadName = basename($fileName, '.sql');
		} else {
			$downloadName = basename($downloadName, '.sql');
		}
		header('Content-Type: text');
		header('Content-Length: ' . filesize($fileName));
		header('Content-Disposition: attachment; filename="' . $downloadName . '.sql"');
		readfile($fileName);
		unlink($fileName);
		exit();
	}


}

?>