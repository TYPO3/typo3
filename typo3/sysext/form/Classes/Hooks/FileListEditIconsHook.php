<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Hooks;

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

use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Filelist\FileList;
use TYPO3\CMS\Filelist\FileListEditIconHookInterface;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;

/**
 * @internal
 */
class FileListEditIconsHook implements FileListEditIconHookInterface
{

    /**
     * Modifies edit icon array
     *
     * @param array $cells
     * @param FileList $parentObject
     */
    public function manipulateEditIcons(&$cells, &$parentObject)
    {
        $fileOrFolderObject = $cells['__fileOrFolderObject'];
        $fullIdentifier = $fileOrFolderObject->getCombinedIdentifier();
        $isFormDefinition = StringUtility::endsWith($fullIdentifier, FormPersistenceManager::FORM_DEFINITION_FILE_EXTENSION);

        if (!$isFormDefinition) {
            return;
        }

        $disableIconNames = ['edit', 'view', 'replace', 'rename'];
        foreach ($disableIconNames as $disableIconName) {
            if (!empty($cells[$disableIconName])) {
                $cells[$disableIconName] = $parentObject->spaceIcon;
            }
        }
    }
}
