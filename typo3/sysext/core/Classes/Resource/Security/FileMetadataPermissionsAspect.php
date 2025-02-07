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

namespace TYPO3\CMS\Core\Resource\Security;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Form\Event\ModifyEditFormUserAccessEvent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\DataHandlerCheckModifyAccessListHookInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;

/**
 * Dealing with file metadata data security is an assembly of hooks to
 * check permissions on files belonging to file metadata records
 */
#[Autoconfigure(public: true)]
readonly class FileMetadataPermissionsAspect implements DataHandlerCheckModifyAccessListHookInterface
{
    public function __construct(
        private ResourceFactory $resourceFactory,
    ) {}

    /**
     * This hook is called before any write operation by DataHandler
     *
     * @param string $table
     * @param int $id
     * @param array $fileMetadataRecord
     * @param int|null $otherHookGrantedAccess
     * @return int|null
     */
    public function checkRecordUpdateAccess($table, $id, $fileMetadataRecord, $otherHookGrantedAccess, DataHandler $dataHandler)
    {
        $accessAllowed = $otherHookGrantedAccess;
        if ($table === 'sys_file_metadata' && $accessAllowed !== 0) {
            $existingFileMetadataRecord = BackendUtility::getRecord('sys_file_metadata', $id);
            if ($existingFileMetadataRecord === null || (empty($existingFileMetadataRecord['file']) && !empty($fileMetadataRecord['file']))) {
                $existingFileMetadataRecord = $fileMetadataRecord;
            }
            $accessAllowed = $this->checkFileWriteAccessForFileMetaData($existingFileMetadataRecord) ? 1 : 0;
        }

        return $accessAllowed;
    }

    /**
     * Hook that determines whether a user has access to modify a table.
     * We "abuse" it here to actually check if access is allowed to sys_file_metadata.
     *
     * @param bool $accessAllowed Whether the user has access to modify a table
     * @param string $table The name of the table to be modified
     */
    public function checkModifyAccessList(&$accessAllowed, $table, DataHandler $parent): void
    {
        if ($table !== 'sys_file_metadata') {
            return;
        }
        foreach (($parent->cmdmap['sys_file_metadata'] ?? []) as $id => $command) {
            $fileMetadataRecord = (array)BackendUtility::getRecord('sys_file_metadata', (int)$id);
            $accessAllowed = $this->checkFileWriteAccessForFileMetaData($fileMetadataRecord);
            if (!$accessAllowed) {
                // If for any item in the array, access is not allowed, we deny the whole operation
                break;
            }
        }
        if (isset($parent->datamap[$table])) {
            foreach ($parent->datamap[$table] as $id => $data) {
                $recordAccessAllowed = false;
                if (!str_contains((string)$id, 'NEW')) {
                    $fileMetadataRecord = BackendUtility::getRecord('sys_file_metadata', (int)$id);
                    if ($fileMetadataRecord !== null) {
                        if ($parent->isImporting && empty($fileMetadataRecord['file'])) {
                            // When importing the record was added with an empty file relation as first step
                            $recordAccessAllowed = true;
                        } else {
                            $recordAccessAllowed = $this->checkFileWriteAccessForFileMetaData($fileMetadataRecord);
                        }
                    }
                } else {
                    // For new records record access is allowed
                    $recordAccessAllowed = true;
                }
                if (isset($data['file'])) {
                    if ($parent->isImporting && empty($data['file'])) {
                        // When importing the record will be created with an empty file relation as first step
                        $dataAccessAllowed = true;
                    } elseif (empty($data['file'])) {
                        $dataAccessAllowed = false;
                    } else {
                        $dataAccessAllowed = $this->checkFileWriteAccessForFileMetaData($data);
                    }
                } else {
                    $dataAccessAllowed = true;
                }
                if (!$recordAccessAllowed || !$dataAccessAllowed) {
                    // If for any item in the array, access is not allowed, we deny the whole operation
                    $accessAllowed = false;
                    break;
                }
            }
        }
    }

    /**
     * Deny access to the edit form. This is not mandatory, but better to show this right away that access is denied.
     */
    #[AsEventListener('evaluate-file-meta-data-edit-form-access')]
    public function isAllowedToShowEditForm(ModifyEditFormUserAccessEvent $event): void
    {
        if (!$event->doesUserHaveAccess() || $event->getTableName() !== 'sys_file_metadata' || $event->getCommand() !== 'edit') {
            return;
        }
        $this->checkFileWriteAccessForFileMetaData(
            (array)BackendUtility::getRecord('sys_file_metadata', (int)($event->getDatabaseRow()['uid'] ?? 0))
        ) ? $event->allowUserAccess() : $event->denyUserAccess();
    }

    /**
     * Checks write access to the file belonging to a metadata entry
     */
    protected function checkFileWriteAccessForFileMetaData(array $fileMetadataRecord): bool
    {
        if (empty($fileMetadataRecord['file'])) {
            return false;
        }
        $file = $fileMetadataRecord['file'];
        if (str_contains($file, 'sys_file_')) {
            // The file relation could be written as sys_file_[uid], strip this off before checking access rights
            $file = substr($file, strlen('sys_file_'));
        }
        $fileObject = $this->resourceFactory->getFileObject((int)$file);
        return $fileObject->checkActionPermission('editMeta');
    }
}
