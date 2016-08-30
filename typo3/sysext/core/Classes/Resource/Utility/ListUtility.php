<?php
namespace TYPO3\CMS\Core\Resource\Utility;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Utility function for working with resource-lists
 */
class ListUtility
{
    /**
     * Resolve special folders (by their role) into localised string
     *
     * @param array $folders Array of \TYPO3\CMS\Core\Resource\Folder
     * @return array Array of \TYPO3\CMS\Core\Resource\Folder; folder name or role with folder name as keys
     */
    public static function resolveSpecialFolderNames(array $folders)
    {
        $resolvedFolders = [];

        /** @var $folder \TYPO3\CMS\Core\Resource\Folder */
        foreach ($folders as $folder) {
            $name = $folder->getName();
            $role = $folder->getRole();
            if ($role !== \TYPO3\CMS\Core\Resource\FolderInterface::ROLE_DEFAULT) {
                $tempName = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:role_folder_' . $role, true);
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
