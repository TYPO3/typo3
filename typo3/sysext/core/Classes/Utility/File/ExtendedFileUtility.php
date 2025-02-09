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

namespace TYPO3\CMS\Core\Utility\File;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Event\AfterFileCommandProcessedEvent;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use TYPO3\CMS\Core\Resource\Exception\IllegalFileExtensionException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFileWritePermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientUserPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileException;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException;
use TYPO3\CMS\Core\Resource\Exception\InvalidTargetFolderException;
use TYPO3\CMS\Core\Resource\Exception\NotInMountPointException;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\UploadException;
use TYPO3\CMS\Core\Resource\Exception\UploadSizeException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\SysLog\Action\File as SystemLogFileAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\Exception\NotImplementedMethodException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains functions for performing file operations like copying, pasting, uploading, moving,
 * deleting etc. through the TCE
 *
 * See document "TYPO3 Core API" for syntax
 *
 * This class contains functions primarily used by tce_file.php (TYPO3 Core Engine for file manipulation)
 * Functions include copying, moving, deleting, uploading and so on...
 *
 * All fileoperations must be within the file mount paths of the user.
 *
 * @internal Since TYPO3 v10, this class should not be used anymore outside of TYPO3 Core, and is considered internal,
 * as the FAL API should be used instead.
 */
class ExtendedFileUtility extends BasicFileUtility
{
    /**
     * Defines behaviour when uploading files with names that already exist;
     */
    protected DuplicationBehavior $existingFilesConflictMode;

    /**
     * This array is self-explaining (look in the class below).
     * It grants access to the functions. This could be set from outside in order to enabled functions to users.
     * See also the function setActionPermissions() which takes input directly from the user-record
     *
     * @var array
     */
    public $actionPerms = [
        // File permissions
        'addFile' => false,
        'readFile' => false,
        'writeFile' => false,
        'copyFile' => false,
        'moveFile' => false,
        'renameFile' => false,
        'deleteFile' => false,
        // Folder permissions
        'addFolder' => false,
        'readFolder' => false,
        'writeFolder' => false,
        'copyFolder' => false,
        'moveFolder' => false,
        'renameFolder' => false,
        'deleteFolder' => false,
        'recursivedeleteFolder' => false,
    ];

    /**
     * Will contain map between upload ID and the final filename
     *
     * @var array
     */
    public $internalUploadMap = [];

    /**
     * Container for FlashMessages so they can be localized
     *
     * @var array
     */
    protected $flashMessages = [];

    /**
     * @var array
     */
    protected $fileCmdMap;

    /**
     * The File Factory
     *
     * @var \TYPO3\CMS\Core\Resource\ResourceFactory
     */
    protected $fileFactory;

    /**
     * Get existingFilesConflictMode
     */
    public function getExistingFilesConflictMode(): string
    {
        return $this->existingFilesConflictMode->value;
    }

    /**
     * Set existingFilesConflictMode
     */
    public function setExistingFilesConflictMode(DuplicationBehavior $existingFilesConflictMode): void
    {
        $this->existingFilesConflictMode = $existingFilesConflictMode;
    }

    /**
     * Initialization of the class
     *
     * @param array $fileCmds Array with the commands to execute. See "TYPO3 Core API" document
     */
    public function start($fileCmds)
    {
        // Initialize Object Factory
        $this->fileFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        // Initializing file processing commands:
        $this->fileCmdMap = $fileCmds;
    }

    /**
     * Sets the file action permissions.
     * If no argument is given, permissions of the currently logged in backend user are taken into account.
     *
     * @param array $permissions File Permissions.
     */
    public function setActionPermissions(array $permissions = [])
    {
        if (empty($permissions)) {
            $permissions = $this->getBackendUser()->getFilePermissions();
        }
        $this->actionPerms = $permissions;
    }

    /**
     * Processing the command array in $this->fileCmdMap
     *
     * @return mixed FALSE, if the file functions were not initialized
     * @throws \UnexpectedValueException
     */
    public function processData()
    {
        $result = [];
        if (is_array($this->fileCmdMap)) {
            // Check if there were uploads expected, but no one made
            if ($this->fileCmdMap['upload'] ?? false) {
                $uploads = $this->fileCmdMap['upload'];
                foreach ($uploads as $upload) {
                    if (empty($_FILES['upload_' . $upload['data']]['name'])
                        || (
                            is_array($_FILES['upload_' . $upload['data']]['name'])
                            && empty($_FILES['upload_' . $upload['data']]['name'][0])
                        )
                    ) {
                        unset($this->fileCmdMap['upload'][$upload['data']]);
                    }
                }
                if (empty($this->fileCmdMap['upload'])) {
                    $this->writeLog(SystemLogFileAction::UPLOAD, SystemLogErrorClassification::USER_ERROR, 'No file was uploaded');
                    $this->addMessageToFlashMessageQueue('FileUtility.NoFileWasUploaded');
                }
            }

            // Check if there were new folder names expected, but non given
            if ($this->fileCmdMap['newfolder'] ?? false) {
                foreach ($this->fileCmdMap['newfolder'] as $key => $cmdArr) {
                    if ((string)($cmdArr['data'] ?? '') === '') {
                        unset($this->fileCmdMap['newfolder'][$key]);
                    }
                }
                if (empty($this->fileCmdMap['newfolder'])) {
                    $this->writeLog(SystemLogFileAction::NEW_FOLDER, SystemLogErrorClassification::USER_ERROR, 'No name for new folder given');
                    $this->addMessageToFlashMessageQueue('FileUtility.NoNameForNewFolderGiven');
                }
            }

            // Traverse each set of actions
            foreach ($this->fileCmdMap as $action => $actionData) {
                // Traverse all action data. More than one file might be affected at the same time.
                if (is_array($actionData)) {
                    $result[$action] = [];
                    // We reset the array keys of $actionData to keep track of the corresponding
                    // result, while not changing the previous behaviour of $result[$action][].
                    foreach (array_values($actionData) as $key => $cmdArr) {
                        // Clear file stats
                        clearstatcache();
                        // Branch out based on command:
                        switch ($action) {
                            case 'delete':
                                $result[$action][$key] = $this->func_delete($cmdArr);
                                break;
                            case 'copy':
                                $result[$action][$key] = $this->func_copy($cmdArr);
                                break;
                            case 'move':
                                $result[$action][$key] = $this->func_move($cmdArr);
                                break;
                            case 'rename':
                                $result[$action][$key] = $this->func_rename($cmdArr);
                                break;
                            case 'newfolder':
                                $result[$action][$key] = $this->func_newfolder($cmdArr);
                                break;
                            case 'newfile':
                                $result[$action][$key] = $this->func_newfile($cmdArr);
                                break;
                            case 'editfile':
                                $result[$action][$key] = $this->func_edit($cmdArr);
                                break;
                            case 'upload':
                                $result[$action][$key] = $this->func_upload($cmdArr);
                                break;
                            case 'replace':
                                $result[$action][$key] = $this->replaceFile($cmdArr);
                                break;
                        }

                        GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch(
                            new AfterFileCommandProcessedEvent([$action => $cmdArr], $result[$action][$key], $this->existingFilesConflictMode->value)
                        );
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @param int $action The action number. See the functions in the class for a hint. Eg. edit is '9', upload is '1' ...
     * @param int $severity The severity: 0 = message, 1 = error, 2 = System Error, 3 = security notice (admin)
     * @param string $message This is the default, raw error message in english
     * @param array $context Additional information when the log is shown
     */
    protected function writeLog(int $action, int $severity, string $message, array $context = []): void
    {
        if (!is_object($this->getBackendUser())) {
            return;
        }
        $this->getBackendUser()->writelog(SystemLogType::FILE, $action, $severity, null, $message, $context);
    }

    /**
     * Adds a localized FlashMessage to the message queue
     *
     * @param string $localizationKey
     * @param ContextualFeedbackSeverity $severity
     * @throws \InvalidArgumentException
     */
    protected function addMessageToFlashMessageQueue($localizationKey, array $replaceMarkers = [], ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::ERROR)
    {
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
        ) {
            $label = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/fileMessages.xlf:' . $localizationKey);
            $message = vsprintf($label, $replaceMarkers);
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $message,
                '',
                $severity,
                true
            );
            $this->addFlashMessage($flashMessage);
        }
    }

    /*************************************
     *
     * File operation functions
     *
     **************************************/
    /**
     * Deleting files and folders (action=4)
     *
     * @param array $cmds $cmds['data'] is the file/folder to delete
     * @return bool Returns TRUE upon success
     */
    public function func_delete(array $cmds)
    {
        $result = false;
        // Example identifier for $cmds['data'] => "4:mypath/tomyfolder/myfile.jpg"
        // for backwards compatibility: the combined file identifier was the path+filename
        try {
            $fileObject = $this->getFileObject($cmds['data']);
        } catch (ResourceDoesNotExistException $e) {
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:message.description.fileNotFound'),
                    $cmds['data']
                ),
                $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:message.header.fileNotFound'),
                ContextualFeedbackSeverity::ERROR,
                true
            );
            $this->addFlashMessage($flashMessage);

            return false;
        }
        // checks to delete the file
        if ($fileObject instanceof File) {
            // check if the file still has references
            // Exclude sys_file_metadata records as these are no use references
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');
            $refIndexRecords = $queryBuilder
                ->select('tablename', 'recuid', 'ref_uid')
                ->from('sys_refindex')
                ->where(
                    $queryBuilder->expr()->eq(
                        'ref_table',
                        $queryBuilder->createNamedParameter('sys_file')
                    ),
                    $queryBuilder->expr()->eq(
                        'ref_uid',
                        $queryBuilder->createNamedParameter($fileObject->getUid(), Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->neq(
                        'tablename',
                        $queryBuilder->createNamedParameter('sys_file_metadata')
                    )
                )
                ->executeQuery()
                ->fetchAllAssociative();
            $deleteFile = true;
            if (!empty($refIndexRecords)) {
                $shortcutContent = [];
                $brokenReferences = [];

                foreach ($refIndexRecords as $fileReferenceRow) {
                    if ($fileReferenceRow['tablename'] === 'sys_file_reference') {
                        $row = $this->transformFileReferenceToRecordReference($fileReferenceRow);
                        $shortcutRecord = BackendUtility::getRecord($row['tablename'], $row['recuid']);

                        if ($shortcutRecord) {
                            $shortcutContent[] = '[record:' . $row['tablename'] . ':' . $row['recuid'] . ']';
                        } else {
                            $brokenReferences[] = $fileReferenceRow['ref_uid'];
                        }
                    } else {
                        $shortcutContent[] = '[record:' . $fileReferenceRow['tablename'] . ':' . $fileReferenceRow['recuid'] . ']';
                    }
                }
                if (!empty($brokenReferences)) {
                    // render a message that the file has broken references
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        sprintf($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:message.description.fileHasBrokenReferences'), count($brokenReferences)),
                        $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:message.header.fileHasBrokenReferences'),
                        ContextualFeedbackSeverity::INFO,
                        true
                    );
                    $this->addFlashMessage($flashMessage);
                }
                if (!empty($shortcutContent)) {
                    // render a message that the file could not be deleted
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        sprintf($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:message.description.fileNotDeletedHasReferences'), $fileObject->getName()) . ' ' . implode(', ', $shortcutContent),
                        $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:message.header.fileNotDeletedHasReferences'),
                        ContextualFeedbackSeverity::WARNING,
                        true
                    );
                    $this->addFlashMessage($flashMessage);
                    $deleteFile = false;
                }
            }

            if ($deleteFile) {
                try {
                    $result = $fileObject->delete();

                    // show the user that the file was deleted
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        sprintf($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:message.description.fileDeleted'), $fileObject->getName()),
                        $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:message.header.fileDeleted'),
                        ContextualFeedbackSeverity::OK,
                        true
                    );
                    $this->addFlashMessage($flashMessage);
                    // Log success
                    $this->writeLog(SystemLogFileAction::DELETE, SystemLogErrorClassification::MESSAGE, 'File "{identifier}" deleted', ['identifier' => $fileObject->getIdentifier()]);
                } catch (InsufficientFileAccessPermissionsException $e) {
                    $this->writeLog(SystemLogFileAction::DELETE, SystemLogErrorClassification::USER_ERROR, 'You are not allowed to access the file "{identifier}"', ['identifier' => $fileObject->getIdentifier()]);
                    $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToAccessTheFile', [$fileObject->getIdentifier()]);
                } catch (NotInMountPointException $e) {
                    $this->writeLog(SystemLogFileAction::DELETE, SystemLogErrorClassification::USER_ERROR, 'Target "{identifier}" was not within your mountpoints"', ['identifier' => $fileObject->getIdentifier()]);
                    $this->addMessageToFlashMessageQueue('FileUtility.TargetWasNotWithinYourMountpoints', [$fileObject->getIdentifier()]);
                } catch (\RuntimeException $e) {
                    $this->writeLog(SystemLogFileAction::DELETE, SystemLogErrorClassification::USER_ERROR, 'Could not delete file "{identifier}". Write-permission problem?', ['identifier' => $fileObject->getIdentifier()]);
                    $this->addMessageToFlashMessageQueue('FileUtility.CouldNotDeleteFile', [$fileObject->getIdentifier()]);
                }
            }
        } else {
            /** @var Folder $fileObject */
            if (!$this->folderHasFilesInUse($fileObject)) {
                try {
                    $result = $fileObject->delete(true);
                    if ($result) {
                        // notify the user that the folder was deleted
                        $flashMessage = GeneralUtility::makeInstance(
                            FlashMessage::class,
                            sprintf($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:message.description.folderDeleted'), $fileObject->getName()),
                            $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:message.header.folderDeleted'),
                            ContextualFeedbackSeverity::OK,
                            true
                        );
                        $this->addFlashMessage($flashMessage);
                        // Log success
                        $this->writeLog(SystemLogFileAction::DELETE, SystemLogErrorClassification::MESSAGE, 'Directory "{identifier}" deleted', ['identifier' => $fileObject->getIdentifier()]);
                    }
                } catch (InsufficientUserPermissionsException $e) {
                    $this->writeLog(SystemLogFileAction::DELETE, SystemLogErrorClassification::USER_ERROR, 'Could not delete directory. Is directory "{identifier}" empty? (You are not allowed to delete directories recursively)', ['identifier' => $fileObject->getIdentifier()]);
                    $this->addMessageToFlashMessageQueue('FileUtility.CouldNotDeleteDirectory', [$fileObject->getIdentifier()]);
                } catch (InsufficientFolderAccessPermissionsException $e) {
                    $this->writeLog(SystemLogFileAction::DELETE, SystemLogErrorClassification::USER_ERROR, 'You are not allowed to access the directory "{identifier}"', ['identifier' => $fileObject->getIdentifier()]);
                    $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToAccessTheDirectory', [$fileObject->getIdentifier()]);
                } catch (NotInMountPointException $e) {
                    $this->writeLog(SystemLogFileAction::DELETE, SystemLogErrorClassification::USER_ERROR, 'Target "{identifier}" was not within your mountpoints', ['identifier' => $fileObject->getIdentifier()]);
                    $this->addMessageToFlashMessageQueue('FileUtility.TargetWasNotWithinYourMountpoints', [$fileObject->getIdentifier()]);
                } catch (FileOperationErrorException $e) {
                    $this->writeLog(SystemLogFileAction::DELETE, SystemLogErrorClassification::USER_ERROR, 'Could not delete directory "{identifier}". Write-permission problem?', ['identifier' => $fileObject->getIdentifier()]);
                    $this->addMessageToFlashMessageQueue('FileUtility.CouldNotDeleteDirectory', [$fileObject->getIdentifier()]);
                }
            }
        }

        return $result;
    }

    /**
     * Checks files in given folder recursively for for existing references.
     *
     * Creates a flash message if there are references.
     *
     * @param Folder $folder
     * @return bool TRUE if folder has files in use, FALSE otherwise
     */
    public function folderHasFilesInUse(Folder $folder)
    {
        $files = $folder->getFiles(0, 0, Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, true);
        if (empty($files)) {
            return false;
        }

        /** @var int[] $fileUids */
        $fileUids = [];
        foreach ($files as $file) {
            $fileUids[] = $file->getUid();
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');
        $numberOfReferences = $queryBuilder
            ->count('hash')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq(
                    'ref_table',
                    $queryBuilder->createNamedParameter('sys_file')
                ),
                $queryBuilder->expr()->in(
                    'ref_uid',
                    $queryBuilder->createNamedParameter($fileUids, Connection::PARAM_INT_ARRAY)
                ),
                $queryBuilder->expr()->neq(
                    'tablename',
                    $queryBuilder->createNamedParameter('sys_file_metadata')
                )
            )->executeQuery()->fetchOne();

        $hasReferences = $numberOfReferences > 0;
        if ($hasReferences) {
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:message.description.folderNotDeletedHasFilesWithReferences'),
                $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:message.header.folderNotDeletedHasFilesWithReferences'),
                ContextualFeedbackSeverity::WARNING,
                true
            );
            $this->addFlashMessage($flashMessage);
        }

        return $hasReferences;
    }

    /**
     * Maps results from the fal file reference table on the
     * structure of  the normal reference index table.
     *
     * @return array
     */
    protected function transformFileReferenceToRecordReference(array $referenceRecord)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');
        $queryBuilder->getRestrictions()->removeAll();
        $fileReference = $queryBuilder
            ->select('uid_foreign', 'tablenames', 'fieldname', 'sorting_foreign')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($referenceRecord['recuid'], Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        return [
            'recuid' => $fileReference['uid_foreign'],
            'tablename' => $fileReference['tablenames'],
            'field' => $fileReference['fieldname'],
            'flexpointer' => '',
            'softref_key' => '',
            'sorting' => $fileReference['sorting_foreign'],
        ];
    }

    /**
     * Gets a File or a Folder object from an identifier [storage]:[fileId]
     */
    protected function getFileObject(string $identifier)
    {
        $object = $this->fileFactory->retrieveFileOrFolderObject($identifier);
        if ($object === null) {
            throw new InvalidFileException('The item ' . $identifier . ' was not a file or directory', 1320122453);
        }
        if ($object->getStorage()->isFallbackStorage()) {
            throw new InsufficientFileAccessPermissionsException('You are not allowed to access files outside your storages', 1375889830);
        }
        return $object;
    }

    /**
     * Copying files and folders (action=2)
     *
     * $cmds['data'] (string): The file/folder to copy
     * + example "4:mypath/tomyfolder/myfile.jpg")
     * + for backwards compatibility: the identifier was the path+filename
     * $cmds['target'] (string): The path where to copy to.
     * + example "2:targetpath/targetfolder/"
     * $cmds['altName'] (string): Use an alternative name if the target already exists
     *
     * @param array $cmds Command details as described above
     * @return \TYPO3\CMS\Core\Resource\File|false
     */
    protected function func_copy($cmds)
    {
        $sourceFileObject = $this->getFileObject($cmds['data']);
        /** @var Folder $targetFolderObject */
        $targetFolderObject = $this->getFileObject($cmds['target']);
        // Basic check
        if (!$targetFolderObject instanceof Folder) {
            $this->writeLog(SystemLogFileAction::COPY, SystemLogErrorClassification::SYSTEM_ERROR, 'Destination "{identifier}" was not a directory', ['identifier' => $cmds['target']]);
            $this->addMessageToFlashMessageQueue('FileUtility.DestinationWasNotADirectory', [$cmds['target']]);
            return false;
        }
        // If this is TRUE, we append _XX to the file name if
        $appendSuffixOnConflict = (string)($cmds['altName'] ?? '');
        $resultObject = null;
        $conflictMode = $appendSuffixOnConflict !== '' ? DuplicationBehavior::RENAME : DuplicationBehavior::CANCEL;
        // Copying the file
        if ($sourceFileObject instanceof File) {
            try {
                $resultObject = $sourceFileObject->copyTo($targetFolderObject, null, $conflictMode);
            } catch (InsufficientUserPermissionsException $e) {
                $this->writeLog(SystemLogFileAction::COPY, SystemLogErrorClassification::USER_ERROR, 'You are not allowed to copy files');
                $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToCopyFiles');
            } catch (InsufficientFileAccessPermissionsException $e) {
                $this->writeLog(SystemLogFileAction::COPY, SystemLogErrorClassification::USER_ERROR, 'Could not access all necessary resources. Source file "{identifier}" or destination "{destination}" was not within your mountpoints maybe', ['identifier' => $sourceFileObject->getIdentifier(), 'destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.CouldNotAccessAllNecessaryResources', [$sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()]);
            } catch (IllegalFileExtensionException $e) {
                $this->writeLog(SystemLogFileAction::COPY, SystemLogErrorClassification::USER_ERROR, 'Extension of file name "{identifier}" is not allowed in "{destination}"', ['identifier' => $sourceFileObject->getIdentifier(), 'destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.ExtensionOfFileNameIsNotAllowedIn', [$sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()]);
            } catch (ExistingTargetFileNameException $e) {
                $this->writeLog(SystemLogFileAction::COPY, SystemLogErrorClassification::USER_ERROR, 'File "{identifier}" already exists in folder "{destination}"', ['identifier' => $sourceFileObject->getIdentifier(), 'destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.FileAlreadyExistsInFolder', [$sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()]);
            } catch (NotImplementedMethodException $e) {
                $this->writeLog(SystemLogFileAction::MOVE, SystemLogErrorClassification::USER_ERROR, 'The function to copy a file between storages is not yet implemented');
                $this->addMessageToFlashMessageQueue('FileUtility.TheFunctionToCopyAFileBetweenStoragesIsNotYetImplemented');
            } catch (\RuntimeException $e) {
                $this->writeLog(SystemLogFileAction::COPY, SystemLogErrorClassification::SYSTEM_ERROR, 'File "{identifier}" was not copied to "{destination}" - Write-permission problem?', ['identifier' => $sourceFileObject->getIdentifier(), 'destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.FileWasNotCopiedTo', [$sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()]);
            }
            if ($resultObject) {
                $this->writeLog(SystemLogFileAction::COPY, SystemLogErrorClassification::MESSAGE, 'File "{identifier}" copied to "{destination}"', ['identifier' => $sourceFileObject->getIdentifier(), 'destination' => $resultObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.FileCopiedTo', [$sourceFileObject->getIdentifier(), $resultObject->getIdentifier()], ContextualFeedbackSeverity::OK);
            }
        } else {
            // Else means this is a Folder
            $sourceFolderObject = $sourceFileObject;
            try {
                $resultObject = $sourceFolderObject->copyTo($targetFolderObject, null, $conflictMode);
            } catch (InsufficientUserPermissionsException $e) {
                $this->writeLog(SystemLogFileAction::COPY, SystemLogErrorClassification::USER_ERROR, 'You are not allowed to copy directories');
                $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToCopyDirectories');
            } catch (InsufficientFileAccessPermissionsException $e) {
                $this->writeLog(SystemLogFileAction::COPY, SystemLogErrorClassification::USER_ERROR, 'Could not access all necessary resources. Maybe source file "{identifier}" or destination "{destination}" was not within your mountpoints', ['identifier' => $sourceFolderObject->getIdentifier(), 'destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.CouldNotAccessAllNecessaryResources', [$sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()]);
            } catch (InsufficientFolderAccessPermissionsException $e) {
                $this->writeLog(SystemLogFileAction::COPY, SystemLogErrorClassification::USER_ERROR, 'You don\'t have full access to the destination directory "{destination}"', ['destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.YouDontHaveFullAccessToTheDestinationDirectory', [$targetFolderObject->getIdentifier()]);
            } catch (InvalidTargetFolderException $e) {
                $this->writeLog(SystemLogFileAction::COPY, SystemLogErrorClassification::USER_ERROR, 'Cannot copy folder "{name}" into target folder "{destination}", because there is already a folder or file with that name in the target folder', ['name' => $sourceFolderObject->getName(), 'destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.CannotCopyFolderIntoTargetFolderBecauseTheTargetFolderIsAlreadyWithinTheFolderToBeCopied', [$sourceFolderObject->getName(), $targetFolderObject->getIdentifier()]);
            } catch (ExistingTargetFolderException $e) {
                $this->writeLog(SystemLogFileAction::COPY, SystemLogErrorClassification::USER_ERROR, 'Target "{destination}" already exists', ['destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.TargetAlreadyExists', [$targetFolderObject->getIdentifier()]);
            } catch (NotImplementedMethodException $e) {
                $this->writeLog(SystemLogFileAction::MOVE, SystemLogErrorClassification::USER_ERROR, 'The function to copy a folder between storages is not yet implemented');
                $this->addMessageToFlashMessageQueue('FileUtility.TheFunctionToCopyAFolderBetweenStoragesIsNotYetImplemented');
            } catch (\RuntimeException $e) {
                $this->writeLog(SystemLogFileAction::COPY, SystemLogErrorClassification::SYSTEM_ERROR, 'Directory "{identifier}" was not copied to "{destination}". Write-permission problem?', ['identifier' => $sourceFolderObject->getIdentifier(), 'destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.DirectoryWasNotCopiedTo', [$sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()]);
            }
            if ($resultObject) {
                $this->writeLog(SystemLogFileAction::COPY, SystemLogErrorClassification::MESSAGE, 'Directory "{identifier}" copied to "{destination}"', ['identifier' => $sourceFolderObject->getIdentifier(), 'destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.DirectoryCopiedTo', [$sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()], ContextualFeedbackSeverity::OK);
            }
        }
        return $resultObject;
    }

    /**
     * Moving files and folders (action=3)
     *
     * $cmds['data'] (string): The file/folder to move
     * + example "4:mypath/tomyfolder/myfile.jpg")
     * + for backwards compatibility: the identifier was the path+filename
     * $cmds['target'] (string): The path where to move to.
     * + example "2:targetpath/targetfolder/"
     * $cmds['altName'] (string): Use an alternative name if the target already exists
     *
     * @param array $cmds Command details as described above
     * @return \TYPO3\CMS\Core\Resource\File|false
     */
    protected function func_move($cmds)
    {
        $sourceFileObject = $this->getFileObject($cmds['data']);
        $targetFolderObject = $this->getFileObject($cmds['target']);
        // Basic check
        if (!$targetFolderObject instanceof Folder) {
            $this->writeLog(SystemLogFileAction::MOVE, SystemLogErrorClassification::SYSTEM_ERROR, 'Destination "{destination}" was not a directory', ['destination' => $cmds['target']]);
            $this->addMessageToFlashMessageQueue('FileUtility.DestinationWasNotADirectory', [$cmds['target']]);
            return false;
        }
        $alternativeName = (string)($cmds['altName'] ?? '');
        $resultObject = null;
        // Moving the file
        if ($sourceFileObject instanceof File) {
            try {
                $sourcePath = $sourceFileObject->getIdentifier();
                if ($alternativeName !== '') {
                    // Don't allow overwriting existing files, but find a new name
                    $resultObject = $sourceFileObject->moveTo($targetFolderObject, $alternativeName, DuplicationBehavior::RENAME);
                } else {
                    // Don't allow overwriting existing files
                    $resultObject = $sourceFileObject->moveTo($targetFolderObject, null, DuplicationBehavior::CANCEL);
                }
                $this->writeLog(SystemLogFileAction::MOVE, SystemLogErrorClassification::MESSAGE, 'File "{identifier}" moved to "{destination}"', ['identifier' => $sourcePath, 'destination' => $resultObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.FileMovedTo', [$sourcePath, $resultObject->getIdentifier()], ContextualFeedbackSeverity::OK);
            } catch (InsufficientUserPermissionsException $e) {
                $this->writeLog(SystemLogFileAction::MOVE, SystemLogErrorClassification::USER_ERROR, 'You are not allowed to move files');
                $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToMoveFiles');
            } catch (InsufficientFileAccessPermissionsException $e) {
                $this->writeLog(SystemLogFileAction::MOVE, SystemLogErrorClassification::USER_ERROR, 'Could not access all necessary resources. Maybe source file "{identifier}" or destination "{destination}" was not within your mountpoints', ['identifier' => $sourceFileObject->getIdentifier(), 'destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.CouldNotAccessAllNecessaryResources', [$sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()]);
            } catch (IllegalFileExtensionException $e) {
                $this->writeLog(SystemLogFileAction::MOVE, SystemLogErrorClassification::USER_ERROR, 'Extension of file name "{identifier}" is not allowed in "{destination}"', ['identifier' => $sourceFileObject->getIdentifier(), 'destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.ExtensionOfFileNameIsNotAllowedIn', [$sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()]);
            } catch (ExistingTargetFileNameException $e) {
                $this->writeLog(SystemLogFileAction::MOVE, SystemLogErrorClassification::USER_ERROR, 'File "{identifier}" already exists in folder "{destination}"', ['identifier' => $sourceFileObject->getIdentifier(), 'destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.FileAlreadyExistsInFolder', [$sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()]);
            } catch (NotImplementedMethodException $e) {
                $this->writeLog(SystemLogFileAction::MOVE, SystemLogErrorClassification::USER_ERROR, 'The function to move a file between storages is not yet implemented');
                $this->addMessageToFlashMessageQueue('FileUtility.TheFunctionToMoveAFileBetweenStoragesIsNotYetImplemented');
            } catch (\RuntimeException $e) {
                $this->writeLog(SystemLogFileAction::MOVE, SystemLogErrorClassification::SYSTEM_ERROR, 'File "{identifier}" was not copied to "{destination}". Write-permission problem?', ['identifier' => $sourceFileObject->getIdentifier(), 'destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.FileWasNotCopiedTo', [$sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()]);
            }
        } else {
            // Else means this is a Folder
            $sourceFolderObject = $sourceFileObject;
            try {
                if ($alternativeName !== '') {
                    // Don't allow overwriting existing files, but find a new name
                    $resultObject = $sourceFolderObject->moveTo($targetFolderObject, $alternativeName, DuplicationBehavior::RENAME);
                } else {
                    // Don't allow overwriting existing files
                    $resultObject = $sourceFolderObject->moveTo($targetFolderObject, null, DuplicationBehavior::RENAME);
                }
                $this->writeLog(SystemLogFileAction::MOVE, SystemLogErrorClassification::MESSAGE, 'Directory "{identifier}" moved to "{destination}"', ['identifier' => $sourceFolderObject->getIdentifier(), 'destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.DirectoryMovedTo', [$sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()], ContextualFeedbackSeverity::OK);
            } catch (InsufficientUserPermissionsException $e) {
                $this->writeLog(SystemLogFileAction::MOVE, SystemLogErrorClassification::USER_ERROR, 'You are not allowed to move directories');
                $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToMoveDirectories');
            } catch (InsufficientFileAccessPermissionsException $e) {
                $this->writeLog(SystemLogFileAction::MOVE, SystemLogErrorClassification::USER_ERROR, 'Could not access all necessary resources. Maybe source folder "{identifier}" or destination "{destination}" was not within your mountpoints', ['identifier' => $sourceFolderObject->getIdentifier(), 'destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.CouldNotAccessAllNecessaryResources', [$sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()]);
            } catch (InsufficientFolderAccessPermissionsException $e) {
                $this->writeLog(SystemLogFileAction::MOVE, SystemLogErrorClassification::USER_ERROR, 'You don\'t have full access to the destination directory "{destination}"', ['destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.YouDontHaveFullAccessToTheDestinationDirectory', [$targetFolderObject->getIdentifier()]);
            } catch (InvalidTargetFolderException $e) {
                $this->writeLog(SystemLogFileAction::MOVE, SystemLogErrorClassification::USER_ERROR, 'Cannot move folder "{identifier}" into target folder "{destination}", because the target folder is already within the folder to be moved', ['identifier' => $sourceFolderObject->getName(), 'destination' => $targetFolderObject->getName()]);
                $this->addMessageToFlashMessageQueue('FileUtility.CannotMoveFolderIntoTargetFolderBecauseTheTargetFolderIsAlreadyWithinTheFolderToBeMoved', [$sourceFolderObject->getName(), $targetFolderObject->getName()]);
            } catch (ExistingTargetFolderException $e) {
                $this->writeLog(SystemLogFileAction::MOVE, SystemLogErrorClassification::USER_ERROR, 'Target "{destination}" already exists', ['destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.TargetAlreadyExists', [$targetFolderObject->getIdentifier()]);
            } catch (NotImplementedMethodException $e) {
                $this->writeLog(SystemLogFileAction::MOVE, SystemLogErrorClassification::USER_ERROR, 'The function to move a folder between storages is not yet implemented');
                $this->addMessageToFlashMessageQueue('FileUtility.TheFunctionToMoveAFolderBetweenStoragesIsNotYetImplemented');
            } catch (\RuntimeException $e) {
                $this->writeLog(SystemLogFileAction::MOVE, SystemLogErrorClassification::SYSTEM_ERROR, 'Directory "{identifier}" was not moved to "{destination}". Write-permission problem?', ['identifier' => $sourceFolderObject->getIdentifier(), 'destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.DirectoryWasNotMovedTo', [$sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()]);
            }
        }
        return $resultObject;
    }

    /**
     * Renaming files or folders (action=5)
     *
     * $cmds['data'] (string): The file/folder to copy
     * + example "4:mypath/tomyfolder/myfile.jpg")
     * + for backwards compatibility: the identifier was the path+filename
     * $cmds['target'] (string): New name of the file/folder
     *
     * @param array $cmds Command details as described above
     * @return \TYPO3\CMS\Core\Resource\File Returns the new file upon success
     */
    public function func_rename($cmds)
    {
        $sourceFileObject = $this->getFileObject($cmds['data']);
        $sourceFile = $sourceFileObject->getName();
        $targetFile = $cmds['target'];
        $resultObject = null;
        if ($sourceFileObject instanceof File) {
            try {
                // Try to rename the File
                $resultObject = $sourceFileObject->rename($targetFile, $this->existingFilesConflictMode);
                if ($resultObject->getName() !== $targetFile) {
                    $this->writeLog(SystemLogFileAction::RENAME, SystemLogErrorClassification::USER_ERROR, 'File renamed from "{identifier}" to "{destination}". Filename had to be sanitized', ['identifier' => $sourceFile, 'destination' => $targetFile]);
                    $this->addMessageToFlashMessageQueue('FileUtility.FileNameSanitized', [$targetFile, $resultObject->getName()], ContextualFeedbackSeverity::WARNING);
                } else {
                    $this->writeLog(SystemLogFileAction::RENAME, SystemLogErrorClassification::MESSAGE, 'File renamed from "{identifier}" to "{destination}"', ['identifier' => $sourceFile, 'destination' => $targetFile]);
                }
                if ($sourceFile === $resultObject->getName()) {
                    $this->addMessageToFlashMessageQueue('FileUtility.FileRenamedSameName', [$sourceFile], ContextualFeedbackSeverity::INFO);
                } else {
                    $this->addMessageToFlashMessageQueue('FileUtility.FileRenamedFromTo', [$sourceFile, $resultObject->getName()], ContextualFeedbackSeverity::OK);
                }
            } catch (InsufficientUserPermissionsException $e) {
                $this->writeLog(SystemLogFileAction::RENAME, SystemLogErrorClassification::USER_ERROR, 'You are not allowed to rename files');
                $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToRenameFiles');
            } catch (IllegalFileExtensionException $e) {
                $this->writeLog(SystemLogFileAction::RENAME, SystemLogErrorClassification::USER_ERROR, 'Extension of file name "{identifier}" or "{destination}" was not allowed', ['identifier' => $sourceFileObject->getName(), 'destination' => $targetFile]);
                $this->addMessageToFlashMessageQueue('FileUtility.ExtensionOfFileNameOrWasNotAllowed', [$sourceFileObject->getName(), $targetFile]);
            } catch (ExistingTargetFileNameException $e) {
                $this->writeLog(SystemLogFileAction::RENAME, SystemLogErrorClassification::USER_ERROR, 'Destination "{destination}" existed already', ['destination' => $targetFile]);
                $this->addMessageToFlashMessageQueue('FileUtility.DestinationExistedAlready', [$targetFile]);
            } catch (NotInMountPointException $e) {
                $this->writeLog(SystemLogFileAction::RENAME, SystemLogErrorClassification::USER_ERROR, 'Destination path "{destination}" was not within your mountpoints', ['destination' => $targetFile]);
                $this->addMessageToFlashMessageQueue('FileUtility.DestinationPathWasNotWithinYourMountpoints', [$targetFile]);
            } catch (\RuntimeException $e) {
                $this->writeLog(SystemLogFileAction::RENAME, SystemLogErrorClassification::USER_ERROR, 'File "{identifier}" was not renamed. Write-permission problem in "{destination}"?', ['identifier' => $sourceFileObject->getName(), 'destination' => $targetFile]);
                $this->addMessageToFlashMessageQueue('FileUtility.FileWasNotRenamed', [$sourceFileObject->getName(), $targetFile]);
            }
        } else {
            // Else means this is a Folder
            try {
                // Try to rename the Folder
                $resultObject = $sourceFileObject->rename($targetFile);
                $newFolderName = $resultObject->getName();
                $this->writeLog(SystemLogFileAction::RENAME, SystemLogErrorClassification::MESSAGE, 'Directory renamed from "{identifier}" to "{destination}"', ['identifier' => $sourceFile, 'destination' => $targetFile]);
                if ($sourceFile === $newFolderName) {
                    $this->addMessageToFlashMessageQueue('FileUtility.DirectoryRenamedSameName', [$sourceFile], ContextualFeedbackSeverity::INFO);
                } else {
                    if ($newFolderName === $targetFile) {
                        $this->addMessageToFlashMessageQueue('FileUtility.DirectoryRenamedFromTo', [$sourceFile, $newFolderName], ContextualFeedbackSeverity::OK);
                    } else {
                        $this->addMessageToFlashMessageQueue('FileUtility.DirectoryRenamedFromToCharReplaced', [$sourceFile, $newFolderName], ContextualFeedbackSeverity::WARNING);
                    }
                }
            } catch (InsufficientUserPermissionsException $e) {
                $this->writeLog(SystemLogFileAction::RENAME, SystemLogErrorClassification::USER_ERROR, 'You are not allowed to rename directories');
                $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToRenameDirectories');
            } catch (ExistingTargetFileNameException $e) {
                $this->writeLog(SystemLogFileAction::RENAME, SystemLogErrorClassification::USER_ERROR, 'Destination "{destination}" existed already', ['destination' => $targetFile]);
                $this->addMessageToFlashMessageQueue('FileUtility.DestinationExistedAlready', [$targetFile]);
            } catch (NotInMountPointException $e) {
                $this->writeLog(SystemLogFileAction::RENAME, SystemLogErrorClassification::USER_ERROR, 'Destination path "{destination}" was not within your mountpoints', ['destination' => $targetFile]);
                $this->addMessageToFlashMessageQueue('FileUtility.DestinationPathWasNotWithinYourMountpoints', [$targetFile]);
            } catch (\RuntimeException $e) {
                $this->writeLog(SystemLogFileAction::RENAME, SystemLogErrorClassification::USER_ERROR, 'Directory "{identifier}" was not renamed. Write-permission problem in "{destination}"?', ['identifier' => $sourceFileObject->getName(), 'destination' => $targetFile]);
                $this->addMessageToFlashMessageQueue('FileUtility.DirectoryWasNotRenamed', [$sourceFileObject->getName(), $targetFile]);
            }
        }
        return $resultObject;
    }

    /**
     * This creates a new folder. (action=6)
     *
     * $cmds['data'] (string): The new folder name
     * $cmds['target'] (string): The path where to copy to.
     * + example "2:targetpath/targetfolder/"
     *
     * @param array $cmds Command details as described above
     * @return Folder|false Returns the new foldername upon success
     */
    public function func_newfolder($cmds)
    {
        $resultObject = false;
        $targetFolderObject = $this->getFileObject($cmds['target']);
        if (!$targetFolderObject instanceof Folder) {
            $this->writeLog(SystemLogFileAction::NEW_FOLDER, SystemLogErrorClassification::SYSTEM_ERROR, 'Destination "{destination}" was not a directory', ['destination' => $cmds['target']]);
            $this->addMessageToFlashMessageQueue('FileUtility.DestinationWasNotADirectory', [$cmds['target']]);
            return false;
        }
        $folderName = $cmds['data'];
        try {
            $resultObject = $targetFolderObject->createFolder($folderName);
            $this->writeLog(SystemLogFileAction::NEW_FOLDER, SystemLogErrorClassification::MESSAGE, 'Directory "{identifier}" created in "{destination}"', ['identifier' => $folderName, 'destination' => $targetFolderObject->getIdentifier()]);
            $this->addMessageToFlashMessageQueue('FileUtility.DirectoryCreatedIn', [$folderName, $targetFolderObject->getIdentifier()], ContextualFeedbackSeverity::OK);
        } catch (InvalidFileNameException $e) {
            $this->writeLog(SystemLogFileAction::NEW_FOLDER, SystemLogErrorClassification::USER_ERROR, 'Invalid folder name "{identifier}"', ['identifier' => $folderName]);
            $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToCreateDirectories', [$folderName]);
        } catch (InsufficientFolderWritePermissionsException $e) {
            $this->writeLog(SystemLogFileAction::NEW_FOLDER, SystemLogErrorClassification::USER_ERROR, 'You are not allowed to create directories');
            $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToCreateDirectories');
        } catch (NotInMountPointException $e) {
            $this->writeLog(SystemLogFileAction::NEW_FOLDER, SystemLogErrorClassification::USER_ERROR, 'Destination path "{destination}" was not within your mountpoints', ['destination' => $targetFolderObject->getIdentifier()]);
            $this->addMessageToFlashMessageQueue('FileUtility.DestinationPathWasNotWithinYourMountpoints', [$targetFolderObject->getIdentifier()]);
        } catch (ExistingTargetFolderException $e) {
            $this->writeLog(SystemLogFileAction::NEW_FOLDER, SystemLogErrorClassification::USER_ERROR, 'File or directory "{identifier}" existed already', ['identifier' => $folderName]);
            $this->addMessageToFlashMessageQueue('FileUtility.FileOrDirectoryExistedAlready', [$folderName]);
        } catch (\RuntimeException $e) {
            $this->writeLog(SystemLogFileAction::NEW_FOLDER, SystemLogErrorClassification::USER_ERROR, 'Directory "{identifier}" not created. Write-permission problem in "{destination}"?', ['identifier' => $folderName, 'destination' => $targetFolderObject->getIdentifier()]);
            $this->addMessageToFlashMessageQueue('FileUtility.DirectoryNotCreated', [$folderName, $targetFolderObject->getIdentifier()]);
        }
        return $resultObject;
    }

    /**
     * This creates a new file. (action=8)
     * $cmds['data'] (string): The new file name
     * $cmds['target'] (string): The path where to create it.
     * + example "2:targetpath/targetfolder/"
     *
     * @param array $cmds Command details as described above
     */
    public function func_newfile($cmds): File|false|null
    {
        $targetFolderObject = $this->getFileObject($cmds['target']);
        if (!$targetFolderObject instanceof Folder) {
            $this->writeLog(SystemLogFileAction::NEW_FILE, SystemLogErrorClassification::SYSTEM_ERROR, 'Destination "{destination}" was not a directory', ['destination' => $cmds['target']]);
            $this->addMessageToFlashMessageQueue('FileUtility.DestinationWasNotADirectory', [$cmds['target']]);
            return false;
        }
        $resultObject = null;
        $fileName = $cmds['data'];
        try {
            $resultObject = $targetFolderObject->createFile($fileName);
            $this->writeLog(SystemLogFileAction::NEW_FILE, SystemLogErrorClassification::MESSAGE, 'File "{identifier}" created', ['identifier' => $fileName]);
            if ($resultObject->getName() !== $fileName) {
                $this->addMessageToFlashMessageQueue('FileUtility.FileNameSanitized', [$fileName, $resultObject->getName()], ContextualFeedbackSeverity::WARNING);
            }
            $this->addMessageToFlashMessageQueue('FileUtility.FileCreated', [$resultObject->getName()], ContextualFeedbackSeverity::OK);
        } catch (IllegalFileExtensionException $e) {
            $this->writeLog(SystemLogFileAction::NEW_FILE, SystemLogErrorClassification::USER_ERROR, 'Extension of file "{identifier}" was not allowed', ['identifier' => $fileName]);
            $this->addMessageToFlashMessageQueue('FileUtility.ExtensionOfFileWasNotAllowed', [$fileName]);
        } catch (InsufficientFolderWritePermissionsException $e) {
            $this->writeLog(SystemLogFileAction::NEW_FILE, SystemLogErrorClassification::USER_ERROR, 'You are not allowed to create files');
            $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToCreateFiles');
        } catch (NotInMountPointException $e) {
            $this->writeLog(SystemLogFileAction::NEW_FILE, SystemLogErrorClassification::USER_ERROR, 'Destination path "{destination}" was not within your mountpoints', ['destination' => $targetFolderObject->getIdentifier()]);
            $this->addMessageToFlashMessageQueue('FileUtility.DestinationPathWasNotWithinYourMountpoints', [$targetFolderObject->getIdentifier()]);
        } catch (ExistingTargetFileNameException $e) {
            $this->writeLog(SystemLogFileAction::NEW_FILE, SystemLogErrorClassification::USER_ERROR, 'File existed already in "{destination}"', ['destination' => $targetFolderObject->getIdentifier()]);
            $this->addMessageToFlashMessageQueue('FileUtility.FileExistedAlreadyIn', [$targetFolderObject->getIdentifier()]);
        } catch (InvalidFileNameException $e) {
            $this->writeLog(SystemLogFileAction::NEW_FILE, SystemLogErrorClassification::USER_ERROR, 'File name "{identifier}" was not allowed', ['identifier' => $fileName]);
            $this->addMessageToFlashMessageQueue('FileUtility.FileNameWasNotAllowed', [$fileName]);
        } catch (\RuntimeException $e) {
            $this->writeLog(SystemLogFileAction::NEW_FILE, SystemLogErrorClassification::USER_ERROR, 'File "{identifier}" was not created. Write-permission problem in "{destination}"?', ['identifier' => $fileName, 'destination' => $targetFolderObject->getIdentifier()]);
            $this->addMessageToFlashMessageQueue('FileUtility.FileWasNotCreated', [$fileName, $targetFolderObject->getIdentifier()]);
        }
        return $resultObject;
    }

    /**
     * Editing textfiles or folders (action=9)
     *
     * @param array $cmds $cmds['data'] is the new content. $cmds['target'] is the target (file or dir)
     * @return bool Returns TRUE on success
     */
    public function func_edit($cmds)
    {
        // Example identifier for $cmds['target'] => "4:mypath/tomyfolder/myfile.jpg"
        // for backwards compatibility: the combined file identifier was the path+filename
        $fileIdentifier = $cmds['target'];
        $fileObject = $this->getFileObject($fileIdentifier);
        if (!$fileObject instanceof File) {
            $this->writeLog(SystemLogFileAction::EDIT, SystemLogErrorClassification::SYSTEM_ERROR, 'Target "{destination}" was not a file', ['destination' => $fileIdentifier]);
            $this->addMessageToFlashMessageQueue('FileUtility.TargetWasNotAFile', [$fileIdentifier]);
            return false;
        }
        if (!$fileObject->isTextFile()) {
            $extList = $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'];
            $this->writeLog(SystemLogFileAction::EDIT, SystemLogErrorClassification::USER_ERROR, 'File extension "{extension}" is not a textfile format ({allowedExtensions})', ['extension' => $fileObject->getExtension(), 'allowedExtensions' => $extList]);
            $this->addMessageToFlashMessageQueue('FileUtility.FileExtensionIsNotATextfileFormat', [$fileObject->getExtension(), $extList]);
            return false;
        }
        try {
            // Example identifier for $cmds['target'] => "2:targetpath/targetfolder/"
            $content = $cmds['data'];
            $fileObject->setContents($content);
            clearstatcache();
            $this->writeLog(SystemLogFileAction::EDIT, SystemLogErrorClassification::MESSAGE, 'File saved to "{identifier}", bytes: {size}', ['identifier' => $fileObject->getIdentifier(), 'size' => $fileObject->getSize()]);
            $this->addMessageToFlashMessageQueue('FileUtility.FileSavedTo', [$fileObject->getIdentifier()], ContextualFeedbackSeverity::OK);
            return true;
        } catch (InsufficientUserPermissionsException $e) {
            $this->writeLog(SystemLogFileAction::EDIT, SystemLogErrorClassification::USER_ERROR, 'You are not allowed to edit files');
            $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToEditFiles');
            return false;
        } catch (InsufficientFileWritePermissionsException $e) {
            $this->writeLog(SystemLogFileAction::EDIT, SystemLogErrorClassification::USER_ERROR, 'File "{identifier}" was not saved. Write-permission problem?', ['identifier' => $fileObject->getIdentifier()]);
            $this->addMessageToFlashMessageQueue('FileUtility.FileWasNotSaved', [$fileObject->getIdentifier()]);
            return false;
        } catch (IllegalFileExtensionException|\RuntimeException $e) {
            $this->writeLog(SystemLogFileAction::EDIT, SystemLogErrorClassification::USER_ERROR, 'File "{identifier}" was not saved. File extension rejected', ['identifier' => $fileObject->getIdentifier()]);
            $this->addMessageToFlashMessageQueue('FileUtility.FileWasNotSaved', [$fileObject->getIdentifier()]);
            return false;
        }
    }

    /**
     * Upload of files (action=1)
     * when having multiple uploads (HTML5-style), the array $_FILES looks like this:
     * Array(
     * [upload_1] => Array(
     * [name] => Array(
     * [0] => GData - Content-Elements and Media-Gallery.pdf
     * [1] => CMS Expo 2011.txt
     * )
     * [type] => Array(
     * [0] => application/pdf
     * [1] => text/plain
     * )
     * [tmp_name] => Array(
     * [0] => /Applications/MAMP/tmp/php/phpNrOB43
     * [1] => /Applications/MAMP/tmp/php/phpD2HQAK
     * )
     * [size] => Array(
     * [0] => 373079
     * [1] => 1291
     * )
     * )
     * )
     * in HTML you'd need sth like this: <input type="file" name="upload_1[]" multiple="true" />
     *
     * @param array $cmds $cmds['data'] is the ID-number (points to the global var that holds the filename-ref
     *                    ($_FILES['upload_' . $id]['name']) . $cmds['target'] is the target directory, $cmds['charset']
     *                    is the the character set of the file name (utf-8 is needed for JS-interaction)
     * @return File[]|bool Returns an array of new file objects upon success. False otherwise
     */
    public function func_upload($cmds)
    {
        $uploadPosition = $cmds['data'];
        $uploadedFileData = $_FILES['upload_' . $uploadPosition];
        if (empty($uploadedFileData['name']) || is_array($uploadedFileData['name']) && empty($uploadedFileData['name'][0])) {
            $this->writeLog(SystemLogFileAction::UPLOAD, SystemLogErrorClassification::SYSTEM_ERROR, 'No file was uploaded');
            $this->addMessageToFlashMessageQueue('FileUtility.NoFileWasUploaded');
            return false;
        }
        // Example identifier for $cmds['target'] => "2:targetpath/targetfolder/"
        $targetFolderObject = $this->getFileObject($cmds['target']);
        // Uploading with non HTML-5-style, thus, make an array out of it, so we can loop over it
        if (!is_array($uploadedFileData['name'])) {
            $uploadedFileData = [
                'name' => [$uploadedFileData['name']],
                'type' => [$uploadedFileData['type']],
                'tmp_name' => [$uploadedFileData['tmp_name']],
                'size' => [$uploadedFileData['size']],
            ];
        }
        $uploadedFileData['name'] = array_map(\Normalizer::normalize(...), $uploadedFileData['name']);
        $resultObjects = [];
        $numberOfUploadedFilesForPosition = count($uploadedFileData['name']);
        // Loop through all uploaded files
        for ($i = 0; $i < $numberOfUploadedFilesForPosition; $i++) {
            $fileInfo = [
                'name' => $uploadedFileData['name'][$i],
                'type' => $uploadedFileData['type'][$i],
                'tmp_name' => $uploadedFileData['tmp_name'][$i],
                'size' => $uploadedFileData['size'][$i],
            ];
            try {
                $fileObject = $targetFolderObject->addUploadedFile($fileInfo, $this->existingFilesConflictMode);
                if ($this->existingFilesConflictMode === DuplicationBehavior::REPLACE) {
                    $this->getIndexer($fileObject->getStorage())->updateIndexEntry($fileObject);
                }
                $resultObjects[] = $fileObject;
                $this->internalUploadMap[$uploadPosition] = $fileObject->getCombinedIdentifier();
                if ($fileObject->getName() !== $fileInfo['name']) {
                    $this->addMessageToFlashMessageQueue('FileUtility.FileNameSanitized', [$fileInfo['name'], $fileObject->getName()], ContextualFeedbackSeverity::WARNING);
                }
                $this->writeLog(SystemLogFileAction::UPLOAD, SystemLogErrorClassification::MESSAGE, 'Uploading file "{identifier}" to "{destination}"', ['identifier' => $fileInfo['name'], 'destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.UploadingFileTo', [$fileInfo['name'], $targetFolderObject->getIdentifier()], ContextualFeedbackSeverity::OK);
            } catch (InsufficientFileWritePermissionsException $e) {
                $this->writeLog(SystemLogFileAction::UPLOAD, SystemLogErrorClassification::USER_ERROR, 'You are not allowed to override {identifier}', ['identifier' => $fileInfo['name']]);
                $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToOverride', [$fileInfo['name']]);
            } catch (UploadException $e) {
                $this->writeLog(SystemLogFileAction::UPLOAD, SystemLogErrorClassification::SYSTEM_ERROR, 'The upload has failed, no uploaded file found');
                $this->addMessageToFlashMessageQueue('FileUtility.TheUploadHasFailedNoUploadedFileFound');
            } catch (InsufficientUserPermissionsException $e) {
                $this->writeLog(SystemLogFileAction::UPLOAD, SystemLogErrorClassification::USER_ERROR, 'You are not allowed to upload files');
                $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToUploadFiles');
            } catch (UploadSizeException $e) {
                $this->writeLog(SystemLogFileAction::UPLOAD, SystemLogErrorClassification::USER_ERROR, 'The uploaded file "{identifier}" exceeds the size-limit', ['identifier' => $fileInfo['name']]);
                $this->addMessageToFlashMessageQueue('FileUtility.TheUploadedFileExceedsTheSize-limit', [$fileInfo['name']]);
            } catch (InsufficientFolderWritePermissionsException $e) {
                $this->writeLog(SystemLogFileAction::UPLOAD, SystemLogErrorClassification::USER_ERROR, 'Destination path "{destination}" was not within your mountpoints', ['destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.DestinationPathWasNotWithinYourMountpoints', [$targetFolderObject->getIdentifier()]);
            } catch (IllegalFileExtensionException $e) {
                $this->writeLog(SystemLogFileAction::UPLOAD, SystemLogErrorClassification::USER_ERROR, 'Extension of file name "{identifier}" is not allowed in "{destination}"', ['identifier' => $fileInfo['name'], 'destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.ExtensionOfFileNameIsNotAllowedIn', [$fileInfo['name'], $targetFolderObject->getIdentifier()]);
            } catch (ExistingTargetFileNameException $e) {
                $this->writeLog(SystemLogFileAction::UPLOAD, SystemLogErrorClassification::USER_ERROR, 'No unique filename available in "{destination}"', ['destination' => $targetFolderObject->getIdentifier()]);
                $this->addMessageToFlashMessageQueue('FileUtility.NoUniqueFilenameAvailableIn', [$targetFolderObject->getIdentifier()]);
            } catch (\RuntimeException $e) {
                $this->writeLog(SystemLogFileAction::UPLOAD, SystemLogErrorClassification::USER_ERROR, 'Uploaded file could not be moved. Write-permission problem in "{destination}"? Error: {error}', ['destination' => $targetFolderObject->getIdentifier(), 'error' => $e->getMessage()]);
                $this->addMessageToFlashMessageQueue('FileUtility.UploadedFileCouldNotBeMoved', [$targetFolderObject->getIdentifier()]);
            }
        }

        return $resultObjects;
    }

    /**
     * Replaces a file on the filesystem and changes the identifier of the persisted file object in sys_file if
     * keepFilename is not checked. If keepFilename is checked, only the file content will be replaced.
     *
     * @return array|bool
     * @throws Exception\InsufficientFileAccessPermissionsException
     * @throws Exception\InvalidFileException
     * @throws \RuntimeException
     */
    protected function replaceFile(array $cmdArr)
    {
        $fileObjectToReplace = null;
        $uploadPosition = $cmdArr['data'];
        $fileInfo = $_FILES['replace_' . $uploadPosition];
        if (empty($fileInfo['name'])) {
            $this->writeLog(SystemLogFileAction::UPLOAD, SystemLogErrorClassification::SYSTEM_ERROR, 'No file was uploaded for replacing');
            $this->addMessageToFlashMessageQueue('FileUtility.NoFileWasUploadedForReplacing');
            return false;
        }

        $keepFileName = (bool)($cmdArr['keepFilename'] ?? false);
        $resultObjects = [];

        try {
            $fileObjectToReplace = $this->getFileObject($cmdArr['uid']);
            $folder = $fileObjectToReplace->getParentFolder();
            $resourceStorage = $fileObjectToReplace->getStorage();

            $fileObject = $resourceStorage->addUploadedFile($fileInfo, $folder, $fileObjectToReplace->getName(), DuplicationBehavior::REPLACE);

            // Check if there is a file that is going to be uploaded that has a different name as the replacing one
            // but exists in that folder as well.
            // rename to another name, but check if the name is already given
            if ($keepFileName === false) {
                // if a file with the same name already exists, we need to change it to _01 etc.
                // if the file does not exist, we can do a simple rename
                $resourceStorage->moveFile($fileObject, $folder, $fileInfo['name'], DuplicationBehavior::RENAME);
            }

            $resultObjects[] = $fileObject;
            $this->internalUploadMap[$uploadPosition] = $fileObject->getCombinedIdentifier();

            $this->writeLog(SystemLogFileAction::UPLOAD, SystemLogErrorClassification::MESSAGE, 'Replacing file "{identifier}" to "{destination}"', ['identifier' => $fileInfo['name'], 'destination' => $fileObjectToReplace->getIdentifier()]);
            $this->addMessageToFlashMessageQueue('FileUtility.ReplacingFileTo', [$fileInfo['name'], $fileObjectToReplace->getIdentifier()], ContextualFeedbackSeverity::OK);
        } catch (InsufficientFileWritePermissionsException $e) {
            $this->writeLog(SystemLogFileAction::UPLOAD, SystemLogErrorClassification::USER_ERROR, 'You are not allowed to override "{destination}"', ['destination' => $fileInfo['name']]);
            $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToOverride', [$fileInfo['name']]);
        } catch (UploadException $e) {
            $this->writeLog(SystemLogFileAction::UPLOAD, SystemLogErrorClassification::SYSTEM_ERROR, 'The upload has failed, no uploaded file found');
            $this->addMessageToFlashMessageQueue('FileUtility.TheUploadHasFailedNoUploadedFileFound');
        } catch (InsufficientUserPermissionsException $e) {
            $this->writeLog(SystemLogFileAction::UPLOAD, SystemLogErrorClassification::USER_ERROR, 'You are not allowed to upload files');
            $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToUploadFiles');
        } catch (UploadSizeException $e) {
            $this->writeLog(SystemLogFileAction::UPLOAD, SystemLogErrorClassification::USER_ERROR, 'The uploaded file "{identifier}" exceeds the size-limit', ['identifier' => $fileInfo['name']]);
            $this->addMessageToFlashMessageQueue('FileUtility.TheUploadedFileExceedsTheSize-limit', [$fileInfo['name']]);
        } catch (InsufficientFolderWritePermissionsException $e) {
            $this->writeLog(SystemLogFileAction::UPLOAD, SystemLogErrorClassification::USER_ERROR, 'Destination path "{destination}" was not within your mountpoints', ['destination' => $fileObjectToReplace->getIdentifier()]);
            $this->addMessageToFlashMessageQueue('FileUtility.DestinationPathWasNotWithinYourMountpoints', [$fileObjectToReplace->getIdentifier()]);
        } catch (IllegalFileExtensionException $e) {
            $this->writeLog(SystemLogFileAction::UPLOAD, SystemLogErrorClassification::USER_ERROR, 'Extension of file name "{identifier}" is not allowed in "{destination}"', ['identifier' => $fileInfo['name'], 'destination' => $fileObjectToReplace->getIdentifier()]);
            $this->addMessageToFlashMessageQueue('FileUtility.ExtensionOfFileNameIsNotAllowedIn', [$fileInfo['name'], $fileObjectToReplace->getIdentifier()]);
        } catch (ExistingTargetFileNameException $e) {
            $this->writeLog(SystemLogFileAction::UPLOAD, SystemLogErrorClassification::USER_ERROR, 'No unique filename available in "{destination}"', ['destination' => $fileObjectToReplace->getIdentifier()]);
            $this->addMessageToFlashMessageQueue('FileUtility.NoUniqueFilenameAvailableIn', [$fileObjectToReplace->getIdentifier()]);
        } catch (\RuntimeException $e) {
            throw $e;
        }
        return $resultObjects;
    }

    /**
     * Add flash message to message queue
     */
    protected function addFlashMessage(FlashMessage $flashMessage)
    {
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);

        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * Gets Indexer
     *
     * @return \TYPO3\CMS\Core\Resource\Index\Indexer
     */
    protected function getIndexer(ResourceStorage $storage)
    {
        return GeneralUtility::makeInstance(Indexer::class, $storage);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
