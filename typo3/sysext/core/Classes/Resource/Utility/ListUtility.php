<?php
namespace TYPO3\CMS\Core\Resource\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Stefan Neufeind <info (at) speedpartner.de>
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
 * Utility function for working with resource-lists
 */
class ListUtility {

	/**
	 * Resolve special folders (by their role) into localised string
	 *
	 * @param array $folders Array of \TYPO3\CMS\Core\Resource\Folder
	 * @return array Array of \TYPO3\CMS\Core\Resource\Folder; folder name or role with folder name as keys
	 */
	static public function resolveSpecialFolderNames(array $folders) {
		$resolvedFolders = array();

		/** @var $folder \TYPO3\CMS\Core\Resource\Folder */
		foreach ($folders as $folder) {
			$name = $folder->getName();
			$role = $folder->getRole();
			if ($role !== \TYPO3\CMS\Core\Resource\FolderInterface::ROLE_DEFAULT) {
				$tempName = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:role_folder_' . $role, TRUE);
				if (!empty($tempName) && ($tempName !== $name)) {
					// Set new name and append original name
					$name = $tempName . ' (' . $name . ')';
				}
			}
			$resolvedFolders[$name] = $folder;
		}

		return $resolvedFolders;
	}

}

?>