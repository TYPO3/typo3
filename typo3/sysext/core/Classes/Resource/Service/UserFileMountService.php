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

namespace TYPO3\CMS\Core\Resource\Service;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderReadPermissionsException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service class for implementing the user filemounts,
 * used for BE_USER (\TYPO3\CMS\Core\Authentication\BackendUserAuthentication)
 * and TCEforms hooks
 *
 * Note: This is now also used by sys_file_collection table (fieldname "folder")!
 *
 * @deprecated since TYPO3 v12, will be removed in TYPO3 v13
 */
class UserFileMountService
{
    public function __construct()
    {
        trigger_error('Class ' . __CLASS__ . ' will be removed with TYPO3 v13.0.', E_USER_DEPRECATED);
    }

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
            static function (ResourceStorage $storage) {
                return $storage->getUid();
            },
            $this->getBackendUserAuthentication()->getFileStorages()
        );
        $storageUid = (int)($PA['row']['storage'][0] ?? 0);
        if ($storageUid > 0 && in_array($storageUid, $allowedStorageIds, true)) {
            $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
            /** @var ResourceStorage $storage */
            $storage = $storageRepository->findByUid($storageUid);
            if ($storage === null) {
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                $queue = $flashMessageService->getMessageQueueByIdentifier();
                $queue->enqueue(new FlashMessage('Storage #' . $storageUid . ' does not exist. No folder is currently selectable.', '', ContextualFeedbackSeverity::ERROR));
                if (empty($PA['items'])) {
                    $PA['items'][] = [
                        $PA['row'][$PA['field']],
                        $PA['row'][$PA['field']],
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
                            $item->getIdentifier(),
                        ];
                    }
                }
            } else {
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                $queue = $flashMessageService->getMessageQueueByIdentifier();
                $queue->enqueue(new FlashMessage('Storage "' . $storage->getName() . '" is not browsable. No folder is currently selectable.', '', ContextualFeedbackSeverity::WARNING));
                if (empty($PA['items'])) {
                    $PA['items'][] = [
                        $PA['row'][$PA['field']],
                        $PA['row'][$PA['field']],
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
                $subFolderItems = [];
            }
            $allFolderItems = array_merge($allFolderItems, $subFolderItems);
        }
        return $allFolderItems;
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
