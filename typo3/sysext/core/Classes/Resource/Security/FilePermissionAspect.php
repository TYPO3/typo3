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

namespace TYPO3\CMS\Core\Resource\Security;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\DataHandlerCheckModifyAccessListHookInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SysLog\Action\Database as SystemLogDatabaseAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * `DataHandler` hook handling to avoid direct access to `sys_file` related entities:
 *
 * + denies any write access to `sys_file` (in datamap and cmdmap, unless it is an internal process)
 * + denies any write access to `sys_file` that is on legacy storage
 * + denies any write access to `sys_file_reference`, referencing a file on legacy storage,
 *   or not part of the file-mounts of the corresponding user
 * + denies any write access to `sys_file_metadata`, referencing a file on legacy storage,
 *   or not part of the file-mounts of the corresponding user
 */
class FilePermissionAspect implements DataHandlerCheckModifyAccessListHookInterface
{
    protected ResourceFactory $resourceFactory;

    public function __construct(?ResourceFactory $resourceFactory = null)
    {
        $this->resourceFactory = $resourceFactory ?? GeneralUtility::makeInstance(ResourceFactory::class);
    }

    /**
     * Denies write access to `sys_file` in general, unless it is an internal process.
     *
     * @param bool &$accessAllowed
     * @param string $table
     * @param DataHandler $parent
     */
    public function checkModifyAccessList(&$accessAllowed, $table, DataHandler $parent): void
    {
        $isInternalProcess = $parent->isImporting || $parent->bypassAccessCheckForRecords;
        if ($table === 'sys_file' && !$isInternalProcess) {
            $accessAllowed = false;
        }
    }

    /**
     * Checks file related data being processed in `DataHandler`:
     * + `sys_file` (only if `checkModifyAccessList` passed -> during internal process)
     * + `sys_file_reference`
     * + `sys_file_metadata`
     *
     * @param mixed $incomingFieldArray
     * @param string $table
     * @param int|string $id
     * @param DataHandler $dataHandler
     */
    public function processDatamap_preProcessFieldArray(&$incomingFieldArray, string $table, $id, DataHandler $dataHandler): void
    {
        if (!is_array($incomingFieldArray) || !is_scalar($id)) {
            $incomingFieldArray = null;
            return;
        }
        $isInternalProcess = $dataHandler->isImporting || $dataHandler->bypassAccessCheckForRecords;
        $isNew = !MathUtility::canBeInterpretedAsInteger($id);
        $logId = $isNew ? 0 : (int)$id;
        if ($table === 'sys_file') {
            $file = $this->resolveFile((int)$id);
            if (!$this->isValidStorageData($incomingFieldArray)
                || (!$isNew && $file !== null && $this->usesLegacyStorage($file))
            ) {
                $incomingFieldArray = null;
                $this->logError($table, $logId, 'Attempt to set legacy storage directly is disallowed', $dataHandler);
            }
        } elseif ($table === 'sys_file_reference') {
            $files = $this->resolveReferencedFiles($incomingFieldArray, 'uid_local');
            foreach ($files as $file) {
                if ($file === null) {
                    $incomingFieldArray = null;
                    $this->logError($table, $logId, 'Attempt to reference invalid file is disallowed', $dataHandler);
                } elseif ($this->usesLegacyStorage($file)) {
                    $incomingFieldArray = null;
                    $this->logError($table, $logId, sprintf('Attempt to reference file "%d" in legacy storage is disallowed', $file->getUid()), $dataHandler);
                } elseif (!$isInternalProcess && $this->usesDisallowedFileMount($file, 'read', $dataHandler->BE_USER)) {
                    $incomingFieldArray = null;
                    $this->logError($table, $logId, sprintf('Attempt to reference file "%d" without permission is disallowed', $file->getUid()), $dataHandler);
                }
            }
        } elseif ($table === 'sys_file_metadata') {
            $file = $this->resolveReferencedFile($incomingFieldArray, 'file');
            if ($file !== null && $this->usesLegacyStorage($file)) {
                $incomingFieldArray = null;
                $this->logError($table, $logId, sprintf('Attempt to alter metadata of file "%d" in legacy storage is disallowed', $file->getUid()), $dataHandler);
            } elseif (!$isInternalProcess && $file !== null && $this->usesDisallowedFileMount($file, 'editMeta', $dataHandler->BE_USER)) {
                $incomingFieldArray = null;
                $this->logError($table, $logId, sprintf('Attempt to alter metadata of file "%d" without permission is disallowed', $file->getUid()), $dataHandler);
            }
        }
    }

    protected function logError(string $table, int $id, string $message, DataHandler $dataHandler): void
    {
        $dataHandler->log(
            $table,
            $id,
            SystemLogDatabaseAction::UPDATE,
            0,
            SystemLogErrorClassification::USER_ERROR,
            $message,
            1,
            [$table]
        );
    }

    protected function usesLegacyStorage(File $file): bool
    {
        return $file->getStorage()->getUid() === 0;
    }

    /**
     * @param non-empty-string $fileAction
     * @param BackendUserAuthentication|mixed $backendUser
     * @return bool
     */
    protected function usesDisallowedFileMount(File $file, string $fileAction, mixed $backendUser): bool
    {
        // strict: disallow, in case it cannot be determined from BE_USER
        if (!$backendUser instanceof BackendUserAuthentication) {
            return true;
        }
        foreach ($backendUser->getFileStorages() as $storage) {
            if ($storage->getUid() === $file->getStorage()->getUid()) {
                return !$storage->checkFileActionPermission($fileAction, $file);
            }
        }
        return false;
    }

    /**
     * @return list<?File>
     */
    protected function resolveReferencedFiles(array $data, string $propertyName): array
    {
        $propertyItems = GeneralUtility::trimExplode(',', (string)($data[$propertyName] ?? ''), true);
        return array_map(
            function (string $item): ?File {
                if (MathUtility::canBeInterpretedAsInteger($item)) {
                    return $this->resolveFile((int)$item);
                }
                if (preg_match('/^sys_file_(?P<fileId>\d+)$/', $item, $matches) && (int)$matches['fileId'] > 0) {
                    return $this->resolveFile((int)$matches['fileId']);
                }
                return null;
            },
            $propertyItems
        );
    }

    protected function resolveReferencedFile(array $data, string $propertyName): ?File
    {
        $propertyValue = $data[$propertyName] ?? null;
        if ($propertyValue === null || !MathUtility::canBeInterpretedAsInteger($propertyValue)) {
            return null;
        }
        return $this->resolveFile((int)$propertyValue);
    }

    protected function resolveFile(int $fileId): ?File
    {
        try {
            return $this->resourceFactory->getFileObject($fileId);
        } catch (\Throwable $t) {
            return null;
        }
    }

    protected function isValidStorageData(array $data): bool
    {
        $storage = $data['storage'] ?? '';
        if (!MathUtility::canBeInterpretedAsInteger($storage)) {
            return false;
        }
        return (int)$storage > 0;
    }
}
