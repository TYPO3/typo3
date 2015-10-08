<?php
namespace TYPO3\CMS\Core\Resource\Security;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\DataHandlerCheckModifyAccessListHookInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * We do not have AOP in TYPO3 for now, thus the aspect which
 * deals with file metadata data security is an assembly of hooks to
 * check permissions on files belonging to file meta data records
 */
class FileMetadataPermissionsAspect implements DataHandlerCheckModifyAccessListHookInterface, SingletonInterface
{
    /**
     * This hook is called before any write operation by DataHandler
     *
     * @param string $table
     * @param int $id
     * @param array $fileMetadataRecord
     * @param int|NULL $otherHookGrantedAccess
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
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
     *
     * @param int &$accessAllowed Whether the user has access to modify a table
     * @param string $table The name of the table to be modified
     * @param DataHandler $parent The calling parent object
     * @throws \UnexpectedValueException
     * @return void
     */
    public function checkModifyAccessList(&$accessAllowed, $table, DataHandler $parent)
    {
        if ($table === 'sys_file_metadata') {
            if (isset($parent->cmdmap[$table]) && is_array($parent->cmdmap[$table])) {
                foreach ($parent->cmdmap[$table] as $id => $command) {
                    if (empty($id) || !MathUtility::canBeInterpretedAsInteger($id)) {
                        throw new \UnexpectedValueException(
                            'Integer expected for data manipulation command.
							This can only happen in the case of an attack attempt or when something went horribly wrong.
							To not compromise security, we exit here.',
                            1399982816
                        );
                    }

                    $fileMetadataRecord = BackendUtility::getRecord('sys_file_metadata', $id);
                    $accessAllowed = $this->checkFileWriteAccessForFileMetaData($fileMetadataRecord);
                    if (!$accessAllowed) {
                        // If for any item in the array, access is not allowed, we deny the whole operation
                        break;
                    }
                }
            }

            if (isset($parent->datamap[$table]) && is_array($parent->datamap[$table])) {
                foreach ($parent->datamap[$table] as $id => $data) {
                    $recordAccessAllowed = false;

                    if (strpos($id, 'NEW') === false) {
                        $fileMetadataRecord = BackendUtility::getRecord('sys_file_metadata', $id);
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
    }

    /**
     * Deny access to the edit form. This is not mandatory, but better to show this right away that access is denied.
     *
     * @param array $parameters
     * @return bool
     */
    public function isAllowedToShowEditForm(array $parameters)
    {
        $table = $parameters['table'];
        $uid = $parameters['uid'];
        $cmd = $parameters['cmd'];
        $accessAllowed = $parameters['hasAccess'];

        if ($accessAllowed && $table === 'sys_file_metadata' && $cmd === 'edit') {
            $fileMetadataRecord = BackendUtility::getRecord('sys_file_metadata', $uid);
            $accessAllowed = $this->checkFileWriteAccessForFileMetaData($fileMetadataRecord);
        }
        return $accessAllowed;
    }

    /**
     * Checks write access to the file belonging to a metadata entry
     *
     * @param array $fileMetadataRecord
     * @return bool
     */
    protected function checkFileWriteAccessForFileMetaData($fileMetadataRecord)
    {
        $accessAllowed = false;
        if (is_array($fileMetadataRecord) && !empty($fileMetadataRecord['file'])) {
            $file = $fileMetadataRecord['file'];
            // The file relation could be written as sys_file_[uid], strip this off before checking the rights
            if (strpos($file, 'sys_file_') !== false) {
                $file = substr($file, strlen('sys_file_'));
            }
            $fileObject = ResourceFactory::getInstance()->getFileObject((int)$file);
            $accessAllowed = $fileObject->checkActionPermission('write');
        }
        return $accessAllowed;
    }
}
