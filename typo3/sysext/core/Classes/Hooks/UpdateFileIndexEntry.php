<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Hooks;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SysLog\Action\File as SystemLogFileAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hook for updating the file index entry after a new sys_file_metadata record is created, e.g. manually via FormEngine
 *
 * @internal This class is a hook implementation and is not part of the TYPO3 Core API.
 */
#[Autoconfigure(public: true)]
class UpdateFileIndexEntry
{
    public function __construct(
        private readonly ResourceFactory $resourceFactory,
    ) {}

    public function processDatamap_afterDatabaseOperations(string $status, string $table, $id, array $fieldValues, DataHandler $dataHandler): void
    {
        /**
         * Take action only on
         *   - new records
         *   - sys_file_metadata table
         *   - live workspace
         *   - resolved uids
         *   - resolved file uid
         *   - record on root level
         *   - record in default language
         *   - non-versioned records
         *   - not bulk importing things via CLI
         */
        if ($status !== 'new'
            || $table !== 'sys_file_metadata'
            || $dataHandler->BE_USER->workspace > 0
            || !isset($dataHandler->substNEWwithIDs[$id])
            || !($fieldValues['file'] ?? false)
            || (int)$fieldValues['l10n_parent'] !== 0
            || (int)$fieldValues['pid'] !== 0
            || (isset($fieldValues['t3ver_oid']) && (int)$fieldValues['t3ver_oid'] > 0)
            || $dataHandler->isImporting
        ) {
            return;
        }

        $uid = (int)$dataHandler->substNEWwithIDs[$id];

        try {
            $fileObject = $this->resourceFactory->getFileObject((int)$fieldValues['file']);
            GeneralUtility::makeInstance(Indexer::class, $fileObject->getStorage())->updateIndexEntry($fileObject);
        } catch (FileDoesNotExistException $e) {
            $dataHandler->log(
                'sys_file_metadata',
                $uid,
                SystemLogFileAction::EDIT,
                null,
                SystemLogErrorClassification::SYSTEM_ERROR,
                'The referenced file "{fileUid}" was not found.',
                null,
                ['fileUid' => $fieldValues['file']]
            );
        }
    }
}
