<?php

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

namespace TYPO3\CMS\Core\Resource\Utility;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;

/**
 * Utility function for working with resource-lists
 */
class ListUtility
{
    /**
     * Resolve special folders (by their role) into localised string
     *
     * @param Folder[] $folders
     * @return array<string|int, Folder> Folders using the Folder name (with or without role) as key
     */
    public static function resolveSpecialFolderNames(array $folders)
    {
        /** @var LanguageService $lang */
        $lang = $GLOBALS['LANG'];
        $resolvedFolders = [];

        foreach ($folders as $folder) {
            $name = $folder->getName();
            $role = $folder->getRole();
            if ($role !== FolderInterface::ROLE_DEFAULT) {
                $tempName = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_file.xlf:role_folder_' . $role));
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
