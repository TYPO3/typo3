<?php
namespace TYPO3\CMS\Core\Resource\Service;

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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderReadPermissionsException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service class for implementing the user filemounts,
 * used for BE_USER (\TYPO3\CMS\Core\Authentication\BackendUserAuthentication)
 * and TCEforms hooks
 *
 * Note: This is now also used by sys_file_category table (fieldname "folder")!
 */
class UserFileMountService
{
    /**
     * User function for sys_filemounts (the userfilemounts)
     * to render a dropdown for selecting a folder
     * of a selected mount
     *
     * @param array $PA the array with additional configuration options.
     */
    public function renderTceformsSelectDropdown(&$PA)
    {
        $allowedStorageIds = array_map(
            function (\TYPO3\CMS\Core\Resource\ResourceStorage $storage) {
                return $storage->getUid();
            },
            $this->getBackendUserAuthentication()->getFileStorages()
        );
        // If working for sys_filemounts table
        $storageUid = (int)$PA['row']['base'][0];
        if (!$storageUid) {
            // If working for sys_file_collection table
            $storageUid = (int)$PA['row']['storage'][0];
        }
        if ($storageUid > 0 && in_array($storageUid, $allowedStorageIds, true)) {
            /** @var $storageRepository StorageRepository */
            $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
            /** @var $storage \TYPO3\CMS\Core\Resource\ResourceStorage */
            $storage = $storageRepository->findByUid($storageUid);
            if ($storage === null) {
                /** @var FlashMessageService $flashMessageService */
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                $queue = $flashMessageService->getMessageQueueByIdentifier();
                $queue->enqueue(new FlashMessage('Storage #' . $storageUid . ' does not exist. No folder is currently selectable.', '', FlashMessage::ERROR));
                if (empty($PA['items'])) {
                    $PA['items'][] = [
                        $PA['row'][$PA['field']],
                        $PA['row'][$PA['field']]
                    ];
                }
            } elseif ($storage->isBrowsable()) {
                $rootLevelFolders = [];

                $fileMounts = $storage->getFileMounts();
                if (!empty($fileMounts)) {
                    foreach ($fileMounts as $fileMountInfo) {
                        $rootLevelFolders[] = $fileMountInfo['folder'];
                    }
                } else {
                    $rootLevelFolders[] = $storage->getRootLevelFolder();
                }

                foreach ($rootLevelFolders as $rootLevelFolder) {
                    $folderItems = $this->getSubfoldersForOptionList($rootLevelFolder);
                    foreach ($folderItems as $item) {
                        $PA['items'][] = [
                            $item->getIdentifier(),
                            $item->getIdentifier()
                        ];
                    }
                }
            } else {
                /** @var FlashMessageService $flashMessageService */
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                $queue = $flashMessageService->getMessageQueueByIdentifier();
                $queue->enqueue(new FlashMessage('Storage "' . $storage->getName() . '" is not browsable. No folder is currently selectable.', '', FlashMessage::WARNING));
                if (empty($PA['items'])) {
                    $PA['items'][] = [
                        $PA['row'][$PA['field']],
                        $PA['row'][$PA['field']]
                    ];
                }
            }
        } else {
            $PA['items'][] = ['', 'Please choose a FAL mount from above first.'];
        }
    }

    /**
     * Simple function to make a hierarchical subfolder request into
     * a "flat" option list
     *
     * @param Folder $parentFolder
     * @param int $level a limiter
     * @return Folder[]
     */
    protected function getSubfoldersForOptionList(Folder $parentFolder, $level = 0)
    {
        $level++;
        // hard break on recursion
        if ($level > 99) {
            return [];
        }
        $allFolderItems = [$parentFolder];
        $subFolders = $parentFolder->getSubfolders();
        foreach ($subFolders as $subFolder) {
            try {
                $subFolderItems = $this->getSubfoldersForOptionList($subFolder, $level);
            } catch (InsufficientFolderReadPermissionsException $e) {
                $subFolderItems  = [];
            }
            $allFolderItems = array_merge($allFolderItems, $subFolderItems);
        }
        return $allFolderItems;
    }

    /**
     * Returns the BE USER Object
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
