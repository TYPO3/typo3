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

namespace TYPO3\CMS\Form\Hooks;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Slot\FilePersistenceSlot;

/**
 * @internal
 */
class ImportExportHook
{

    /**
     * @param array $params
     */
    public function beforeAddSysFileRecordOnImport(array $params): void
    {
        $fileRecord = $params['fileRecord'];
        $temporaryFile = $params['temporaryFile'];

        $formPersistenceSlot = GeneralUtility::makeInstance(FilePersistenceSlot::class);
        $formPersistenceSlot->allowInvocation(
            FilePersistenceSlot::COMMAND_FILE_ADD,
            implode(':', [$fileRecord['storage'], $fileRecord['identifier']]),
            $formPersistenceSlot->getContentSignature(file_get_contents($temporaryFile))
        );
    }
}
