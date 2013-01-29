<?php
namespace TYPO3\CMS\Extensionmanager\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Utility to find and execute class.ext_update.php scripts of extensions
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class UpdateScriptUtility {

	/**
	 * Returns true, if ext_update class says it wants to run.
	 *
	 * @param string $extensionKey extension key
	 * @return mixed NULL, if update is not availabel, else update script return
	 */
	public function executeUpdateIfNeeded($extensionKey) {
		$this->requireUpdateScript($extensionKey);
		$scriptObject = new \ext_update;
		// old em always assumed the method exist, we do so too.
		// @TODO: Make this smart, let scripts implement interfaces
		// @TODO: Enforce different update class script names per extension
		// @TODO: With different class names it would be easily possible to check for updates in list view.
		$accessReturnValue = $scriptObject->access();

		$result = NULL;
		if ($accessReturnValue === TRUE) {
			// @TODO: With current ext_update construct it is impossible
			// @TODO: to enforce some type of return
			$result = $scriptObject->main();
		}

		return $result;
	}

	/**
	 * Require update script.
	 * Throws exception if update script does not exist, so checkUpdateScriptExists()
	 * should be called before
	 *
	 * @param string $extensionKey
	 * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
	 */
	protected function requireUpdateScript($extensionKey) {
		if (class_exists('ext_update')) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException(
				'class ext_update for this run does already exist, requiring impossible',
				1359748085
			);
		}

		$fileLocation = $this->getUpdateFileLocation($extensionKey);

		if (!$this->checkUpdateScriptExists($extensionKey)) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException(
				'Requested update script of extension does not exist',
				1359747976
			);

		}
		require $fileLocation;

		if (!class_exists('ext_update')) {
			throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException(
				'class.ext_update.php of extension did not declare ext_update class',
				1359748132
			);
		}
	}

	/**
	 * Checks if an update class file exists.
	 * Does not check if some update is needed.
	 *
	 * @param string $extensionKey Extension key
	 * @return boolean True, if there is some update script
	 */
	public function checkUpdateScriptExists($extensionKey) {
		$updateScriptFileExists = FALSE;
		if (file_exists($this->getUpdateFileLocation($extensionKey))) {
			$updateScriptFileExists = TRUE;
		}
		return $updateScriptFileExists;
	}

	/**
	 * Determines location of update file.
	 * Does not check if the file exists.
	 *
	 * @param string $extensionKey Extension key
	 * @return string Absolute path to possible update file of extension
	 */
	protected function getUpdateFileLocation($extensionKey) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
			'EXT:' . $extensionKey . '/class.ext_update.php',
			FALSE
		);
	}
}

?>