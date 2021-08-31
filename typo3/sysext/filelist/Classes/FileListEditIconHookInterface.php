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

namespace TYPO3\CMS\Filelist;

/**
 * Interface for classes which hook into filelist module and manipulated edit icon array
 * @deprecated will be removed in TYPO3 v12.0, Use the PSR-14 based ProcessFileListActionsEvent instead.
 */
interface FileListEditIconHookInterface
{
    /**
     * Modifies edit icon array
     *
     * @param array $cells Array of edit icons
     * @param \TYPO3\CMS\Filelist\FileList $parentObject Parent object
     */
    public function manipulateEditIcons(&$cells, &$parentObject);
}
