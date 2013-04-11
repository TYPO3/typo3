<?php
namespace TYPO3\CMS\Core\Resource\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Benjamin Mack <benni@typo3.org>
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
 * Service class for implementing the user filemounts,
 * used for BE_USER (\TYPO3\CMS\Core\Authentication\BackendUserAuthentication)
 * and TCEforms hooks
 *
 * Note: This is now also used by sys_file_category table (fieldname "folder")!
 *
 * @author Benjamin Mack <benni@typo3.org>
 */
class UserFileMountService {

	/**
	 * User function for sys_filemounts (the userfilemounts)
	 * to render a dropdown for selecting a folder
	 * of a selected mount
	 *
	 * @param array $PA the array with additional configuration options.
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $tceformsObj Parent object
	 * @return string The HTML code for the TCEform field
	 */
	public function renderTceformsSelectDropdown(&$PA, &$tceformsObj) {
		// If working for sys_filemounts table
		$storageUid = intval($PA['row']['base']);
		if (!$storageUid) {
			// If working for sys_file_collection table
			$storageUid = intval($PA['row']['storage']);
		}
		if ($storageUid > 0) {
			/** @var $storageRepository \TYPO3\CMS\Core\Resource\StorageRepository */
			$storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
			/** @var $storage \TYPO3\CMS\Core\Resource\ResourceStorage */
			$storage = $storageRepository->findByUid($storageUid);
			$rootLevelFolder = $storage->getRootLevelFolder();
			$folderItems = $this->getSubfoldersForOptionList($rootLevelFolder);
			foreach ($folderItems as $item) {
				$PA['items'][] = array(
					htmlspecialchars($item->getIdentifier()),
					htmlspecialchars($item->getIdentifier())
				);
			}
		} else {
			$PA['items'][] = array('', 'Please choose a FAL mount from above first.');
		}
	}

	/**
	 * Simple function to make a hierarchical subfolder request into
	 * a "flat" option list
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $parentFolder
	 * @param integer $level a limiter
	 * @return \TYPO3\CMS\Core\Resource\Folder[]
	 */
	protected function getSubfoldersForOptionList(\TYPO3\CMS\Core\Resource\Folder $parentFolder, $level = 0) {
		$level++;
		// hard break on recursion
		if ($level > 99) {
			return array();
		}
		$allFolderItems = array($parentFolder);
		$subFolders = $parentFolder->getSubfolders();
		foreach ($subFolders as $subFolder) {
			$subFolderItems = $this->getSubfoldersForOptionList($subFolder, $level);
			$allFolderItems = array_merge($allFolderItems, $subFolderItems);
		}
		return $allFolderItems;
	}

}


?>