<?php
namespace TYPO3\CMS\Core\Utility\File;

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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException;
use TYPO3\CMS\Core\Resource\Exception\IllegalFileExtensionException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFileWritePermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientUserPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException;
use TYPO3\CMS\Core\Resource\Exception\NotInMountPointException;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\UploadException;
use TYPO3\CMS\Core\Resource\Exception\UploadSizeException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Contains functions for performing file operations like copying, pasting, uploading, moving,
 * deleting etc. through the TCE
 *
 * See document "TYPO3 Core API" for syntax
 *
 * This class contains functions primarily used by tce_file.php (TYPO3 Core Engine for file manipulation)
 * Functions include copying, moving, deleting, uploading and so on...
 *
 * Important internal variables:
 *
 * $filemounts (see basicFileFunctions)
 * $f_ext (see basicFileFunctions)
 *
 * All fileoperations must be within the filemount-paths. Further the fileextension
 * MUST validate TRUE with the f_ext array
 *
 * The unzip-function allows unzip only if the destination path has it's f_ext[]['allow'] set to '*'!!
 * You are allowed to copy/move folders within the same 'space' (web/ftp).
 * You are allowed to copy/move folders between spaces (web/ftp) IF the destination has it's f_ext[]['allow'] set to '*'!
 *
 * Advice:
 * You should always exclude php-files from the webspace. This will keep people from uploading, copy/moving and renaming files to become executable php scripts.
 * You should never mount a ftp_space 'below' the webspace so that it reaches into the webspace. This is because if somebody unzips a zip-file in the ftp-space so that it reaches out into the webspace this will be a violation of the safety
 * For example this is a bad idea: you have an ftp-space that is '/www/' and a web-space that is '/www/htdocs/'
 */
class ExtendedFileUtility extends BasicFileUtility
{
    /**
     * Defines behaviour when uploading files with names that already exist; possible values are
     * the values of the \TYPO3\CMS\Core\Resource\DuplicationBehavior enumeration
     *
     * @var \TYPO3\CMS\Core\Resource\DuplicationBehavior
     */
    protected $existingFilesConflictMode;

    /**
     * This array is self-explaining (look in the class below).
     * It grants access to the functions. This could be set from outside in order to enabled functions to users.
     * See also the function setActionPermissions() which takes input directly from the user-record
     *
     * @var array
     */
    public $actionPerms = array(
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
        'recursivedeleteFolder' => false
    );

    /**
     * This is regarded to be the recycler folder
     *
     * @var string
     */
    public $recyclerFN = '_recycler_';

    /**
     * Will contain map between upload ID and the final filename
     *
     * @var array
     */
    public $internalUploadMap = array();

    /**
     * @var string
     */
    public $lastError = '';

    /**
     * All error messages from the file operations of this script instance
     *
     * @var array
     */
    protected $errorMessages = array();

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
     *
     * @return string
     */
    public function getExistingFilesConflictMode()
    {
        return (string)$this->existingFilesConflictMode;
    }

    /**
     * Set existingFilesConflictMode
     *
     * @param \TYPO3\CMS\Core\Resource\DuplicationBehavior|string $existingFilesConflictMode Instance or constant of \TYPO3\CMS\Core\Resource\DuplicationBehavior
     * @return void
     * @throws Exception
     */
    public function setExistingFilesConflictMode($existingFilesConflictMode)
    {
        try {
            $this->existingFilesConflictMode = DuplicationBehavior::cast($existingFilesConflictMode);
        } catch (InvalidEnumerationValueException $e) {
            throw new Exception(
                sprintf(
                    'Invalid argument, received: "%s", expected a value from enumeration \TYPO3\CMS\Core\Resource\DuplicationBehavior (%s)',
                    $existingFilesConflictMode,
                    implode(', ', DuplicationBehavior::getConstants())
                )
            );
        }
    }

    /**
     * Initialization of the class
     *
     * @param array $fileCmds Array with the commands to execute. See "TYPO3 Core API" document
     * @return void
     */
    public function start($fileCmds)
    {
        // Initialize Object Factory
        $this->fileFactory = ResourceFactory::getInstance();
        // Initializing file processing commands:
        $this->fileCmdMap = $fileCmds;
    }

    /**
     * Sets the file action permissions.
     * If no argument is given, permissions of the currently logged in backend user are taken into account.
     *
     * @param array $permissions File Permissions.
     * @return void
     */
    public function setActionPermissions(array $permissions = array())
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
        $result = array();
        if (!$this->isInit) {
            return false;
        }
        if (is_array($this->fileCmdMap)) {
            // Check if there were uploads expected, but no one made
            if ($this->fileCmdMap['upload']) {
                $uploads = $this->fileCmdMap['upload'];
                foreach ($uploads as $upload) {
                    if (empty($_FILES['upload_' . $upload['data']]['name'])
                        || (is_array($_FILES['upload_' . $upload['data']]['name'])
                            && empty($_FILES['upload_' . $upload['data']]['name'][0])
                        )
                    ) {
                        unset($this->fileCmdMap['upload'][$upload['data']]);
                    }
                }
                if (empty($this->fileCmdMap['upload'])) {
                    $this->writeLog(1, 1, 108, 'No file was uploaded!', []);
                    $this->addMessageToFlashMessageQueue('FileUtility.NoFileWasUploaded');
                }
            }

            // Check if there were new folder names expected, but non given
            if ($this->fileCmdMap['newfolder']) {
                foreach ($this->fileCmdMap['newfolder'] as $key => $cmdArr) {
                    if (empty($cmdArr['data'])) {
                        unset($this->fileCmdMap['newfolder'][$key]);
                    }
                }
                if (empty($this->fileCmdMap['newfolder'])) {
                    $this->writeLog(6, 1, 108, 'No name for new folder given!', []);
                    $this->addMessageToFlashMessageQueue('FileUtility.NoNameForNewFolderGiven');
                }
            }

            // Traverse each set of actions
            foreach ($this->fileCmdMap as $action => $actionData) {
                // Traverse all action data. More than one file might be affected at the same time.
                if (is_array($actionData)) {
                    $result[$action] = array();
                    foreach ($actionData as $cmdArr) {
                        // Clear file stats
                        clearstatcache();
                        // Branch out based on command:
                        switch ($action) {
                            case 'delete':
                                $result[$action][] = $this->func_delete($cmdArr);
                                break;
                            case 'copy':
                                $result[$action][] = $this->func_copy($cmdArr);
                                break;
                            case 'move':
                                $result[$action][] = $this->func_move($cmdArr);
                                break;
                            case 'rename':
                                $result[$action][] = $this->func_rename($cmdArr);
                                break;
                            case 'newfolder':
                                $result[$action][] = $this->func_newfolder($cmdArr);
                                break;
                            case 'newfile':
                                $result[$action][] = $this->func_newfile($cmdArr);
                                break;
                            case 'editfile':
                                $result[$action][] = $this->func_edit($cmdArr);
                                break;
                            case 'upload':
                                $result[$action][] = $this->func_upload($cmdArr);
                                break;
                            case 'replace':
                                $result[$action][] = $this->replaceFile($cmdArr);
                                break;
                        }
                        // Hook for post-processing the action
                        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'])) {
                            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_extfilefunc.php']['processData'] as $classRef) {
                                $hookObject = GeneralUtility::getUserObj($classRef);
                                if (!$hookObject instanceof ExtendedFileUtilityProcessDataHookInterface) {
                                    throw new \UnexpectedValueException($classRef . ' must implement interface ' . ExtendedFileUtilityProcessDataHookInterface::class, 1279719168);
                                }
                                $hookObject->processData_postProcessAction($action, $cmdArr, $result[$action], $this);
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Adds all log error messages from the operations of this script instance to the FlashMessageQueue
     *
     * @return void
     * @deprecated since TYPO3 CMS 8, will be removed in TYPO3 CMS 9
     */
    public function pushErrorMessagesToFlashMessageQueue()
    {
        GeneralUtility::logDeprecatedFunction();
        foreach ($this->getErrorMessages() as $msg) {
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $msg,
                '',
                FlashMessage::ERROR,
                true
            );
            $this->addFlashMessage($flashMessage);
        }
    }

    /**
     * Return all error messages from the file operations of this script instance
     *
     * @return array all errorMessages as a numerical array
     */
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }

    /**
     * @param int $action The action number. See the functions in the class for a hint. Eg. edit is '9', upload is '1' ...
     * @param int $error The severity: 0 = message, 1 = error, 2 = System Error, 3 = security notice (admin)
     * @param int $details_nr This number is unique for every combination of $type and $action. This is the error-message number, which can later be used to translate error messages.
     * @param string $details This is the default, raw error message in english
     * @param array $data Array with special information that may go into $details by "%s" marks / sprintf() when the log is shown
     * @return void
     */
    public function writeLog($action, $error, $details_nr, $details, $data)
    {
        // Type value for tce_file.php
        $type = 2;
        if (is_object($this->getBackendUser())) {
            $this->getBackendUser()->writelog($type, $action, $error, $details_nr, $details, $data);
        }
        if ($error > 0) {
            $this->lastError = vsprintf($details, $data);
            $this->errorMessages[] = $this->lastError;
        }
    }

    /**
     * Adds a localized FlashMessage to the message queue
     *
     * @param string $localizationKey
     * @param array $replaceMarkers
     * @param int $severity
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function addMessageToFlashMessageQueue($localizationKey, array $replaceMarkers = [], $severity = FlashMessage::ERROR)
    {
        if (TYPO3_MODE !== 'BE') {
            return;
        }
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
        if (!$this->isInit) {
            return $result;
        }
        // Example indentifier for $cmds['data'] => "4:mypath/tomyfolder/myfile.jpg"
        // for backwards compatibility: the combined file identifier was the path+filename
        try {
            $fileObject = $this->getFileObject($cmds['data']);
        } catch (ResourceDoesNotExistException $e) {
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:message.description.fileNotFound'),
                    $cmds['data']
                ),
                $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:message.header.fileNotFound'),
                FlashMessage::ERROR,
                true
            );
            $this->addFlashMessage($flashMessage);

            return false;
        }
        // @todo implement the recycler feature which has been removed from the original implementation
        // checks to delete the file
        if ($fileObject instanceof File) {
            // check if the file still has references
            // Exclude sys_file_metadata records as these are no use references
            $databaseConnection = $this->getDatabaseConnection();
            $table = 'sys_refindex';
            $refIndexRecords = $databaseConnection->exec_SELECTgetRows(
                '*',
                $table,
                'deleted=0 AND ref_table=' . $databaseConnection->fullQuoteStr('sys_file', $table)
                . ' AND ref_uid=' . (int)$fileObject->getUid()
                . ' AND tablename != ' . $databaseConnection->fullQuoteStr('sys_file_metadata', $table)
            );
            $deleteFile = true;
            if (!empty($refIndexRecords)) {
                $shortcutContent = array();
                $brokenReferences = array();

                foreach ($refIndexRecords as $fileReferenceRow) {
                    if ($fileReferenceRow['tablename'] === 'sys_file_reference') {
                        $row = $this->transformFileReferenceToRecordReference($fileReferenceRow);
                        $shortcutRecord = BackendUtility::getRecord($row['tablename'], $row['recuid']);

                        if ($shortcutRecord) {
                            $shortcutContent[] = '[record:' . $row['tablename'] . ':' .  $row['recuid'] . ']';
                        } else {
                            $brokenReferences[] = $fileReferenceRow['ref_uid'];
                        }
                    }
                }
                if (!empty($brokenReferences)) {
                    // render a message that the file has broken references
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        sprintf($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:message.description.fileHasBrokenReferences'), count($brokenReferences)),
                        $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:message.header.fileHasBrokenReferences'),
                        FlashMessage::INFO,
                        true
                    );
                    $this->addFlashMessage($flashMessage);
                }
                if (!empty($shortcutContent)) {
                    // render a message that the file could not be deleted
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        sprintf($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:message.description.fileNotDeletedHasReferences'), $fileObject->getName()) . ' ' . implode(', ', $shortcutContent),
                        $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:message.header.fileNotDeletedHasReferences'),
                        FlashMessage::WARNING,
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
                        sprintf($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:message.description.fileDeleted'), $fileObject->getName()),
                        $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:message.header.fileDeleted'),
                        FlashMessage::OK,
                        true
                    );
                    $this->addFlashMessage($flashMessage);
                    // Log success
                    $this->writeLog(4, 0, 1, 'File "%s" deleted', array($fileObject->getIdentifier()));
                } catch (\TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException $e) {
                    $this->writeLog(4, 1, 112, 'You are not allowed to access the file', array($fileObject->getIdentifier()));
                    $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToAccessTheFile', array($fileObject->getIdentifier()));
                } catch (NotInMountPointException $e) {
                    $this->writeLog(4, 1, 111, 'Target was not within your mountpoints! T="%s"', array($fileObject->getIdentifier()));
                    $this->addMessageToFlashMessageQueue('FileUtility.TargetWasNotWithinYourMountpoints', array($fileObject->getIdentifier()));
                } catch (\RuntimeException $e) {
                    $this->writeLog(4, 1, 110, 'Could not delete file "%s". Write-permission problem?', array($fileObject->getIdentifier()));
                    $this->addMessageToFlashMessageQueue('FileUtility.CouldNotDeleteFile', array($fileObject->getIdentifier()));
                }
            }
        } else {
            /** @var Folder $fileObject */
            if (!$this->folderHasFilesInUse($fileObject)) {
                try {
                    $result = $fileObject->delete(true);
                    if ($result) {
                        // notify the user that the folder was deleted
                        /** @var FlashMessage $flashMessage */
                        $flashMessage = GeneralUtility::makeInstance(
                            FlashMessage::class,
                            sprintf($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:message.description.folderDeleted'), $fileObject->getName()),
                            $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:message.header.folderDeleted'),
                            FlashMessage::OK,
                            true
                        );
                        $this->addFlashMessage($flashMessage);
                        // Log success
                        $this->writeLog(4, 0, 3, 'Directory "%s" deleted', array($fileObject->getIdentifier()));
                    }
                } catch (InsufficientUserPermissionsException $e) {
                    $this->writeLog(4, 1, 120, 'Could not delete directory! Is directory "%s" empty? (You are not allowed to delete directories recursively).', array($fileObject->getIdentifier()));
                    $this->addMessageToFlashMessageQueue('FileUtility.CouldNotDeleteDirectory', array($fileObject->getIdentifier()));
                } catch (InsufficientFolderAccessPermissionsException $e) {
                    $this->writeLog(4, 1, 123, 'You are not allowed to access the directory', array($fileObject->getIdentifier()));
                    $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToAccessTheDirectory', array($fileObject->getIdentifier()));
                } catch (NotInMountPointException $e) {
                    $this->writeLog(4, 1, 121, 'Target was not within your mountpoints! T="%s"', array($fileObject->getIdentifier()));
                    $this->addMessageToFlashMessageQueue('FileUtility.TargetWasNotWithinYourMountpoints', array($fileObject->getIdentifier()));
                } catch (\TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException $e) {
                    $this->writeLog(4, 1, 120, 'Could not delete directory "%s"! Write-permission problem?', array($fileObject->getIdentifier()));
                    $this->addMessageToFlashMessageQueue('FileUtility.CouldNotDeleteDirectory', array($fileObject->getIdentifier()));
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
        $fileUids = array();
        foreach ($files as $file) {
            $fileUids[] = $file->getUid();
        }
        $numberOfReferences = $this->getDatabaseConnection()->exec_SELECTcountRows(
            '*',
            'sys_refindex',
            'deleted=0 AND ref_table="sys_file" AND ref_uid IN (' . implode(',', $fileUids) . ') AND tablename<>"sys_file_metadata"'
        );

        $hasReferences = $numberOfReferences > 0;
        if ($hasReferences) {
            /** @var FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:message.description.folderNotDeletedHasFilesWithReferences'),
                $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:message.header.folderNotDeletedHasFilesWithReferences'),
                FlashMessage::WARNING,
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
     * @param array $referenceRecord
     * @return array
     */
    protected function transformFileReferenceToRecordReference(array $referenceRecord)
    {
        $fileReference = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
            '*',
            'sys_file_reference',
            'uid=' . (int)$referenceRecord['recuid']
        );
        return array(
            'recuid' => $fileReference['uid_foreign'],
            'tablename' => $fileReference['tablenames'],
            'field' => $fileReference['fieldname'],
            'flexpointer' => '',
            'softref_key' => '',
            'sorting' => $fileReference['sorting_foreign']
        );
    }

    /**
     * Gets a File or a Folder object from an identifier [storage]:[fileId]
     *
     * @param string $identifier
     * @return File|Folder
     * @throws Exception\InsufficientFileAccessPermissionsException
     * @throws Exception\InvalidFileException
     */
    protected function getFileObject($identifier)
    {
        $object = $this->fileFactory->retrieveFileOrFolderObject($identifier);
        if (!is_object($object)) {
            throw new \TYPO3\CMS\Core\Resource\Exception\InvalidFileException('The item ' . $identifier . ' was not a file or directory!!', 1320122453);
        }
        if ($object->getStorage()->getUid() === 0) {
            throw new \TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException('You are not allowed to access files outside your storages', 1375889830);
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
     * @return \TYPO3\CMS\Core\Resource\File
     */
    protected function func_copy($cmds)
    {
        if (!$this->isInit) {
            return false;
        }
        $sourceFileObject = $this->getFileObject($cmds['data']);
        /** @var $targetFolderObject \TYPO3\CMS\Core\Resource\Folder */
        $targetFolderObject = $this->getFileObject($cmds['target']);
        // Basic check
        if (!$targetFolderObject instanceof Folder) {
            $this->writeLog(2, 2, 100, 'Destination "%s" was not a directory', array($cmds['target']));
            $this->addMessageToFlashMessageQueue('FileUtility.DestinationWasNotADirectory', array($cmds['target']));
            return false;
        }
        // If this is TRUE, we append _XX to the file name if
        $appendSuffixOnConflict = (string)$cmds['altName'];
        $resultObject = null;
        $conflictMode = $appendSuffixOnConflict !== '' ? DuplicationBehavior::RENAME : DuplicationBehavior::CANCEL;
        // Copying the file
        if ($sourceFileObject instanceof File) {
            try {
                $resultObject = $sourceFileObject->copyTo($targetFolderObject, null, $conflictMode);
            } catch (InsufficientUserPermissionsException $e) {
                $this->writeLog(2, 1, 114, 'You are not allowed to copy files', []);
                $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToCopyFiles');
            } catch (\TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException $e) {
                $this->writeLog(2, 1, 110, 'Could not access all necessary resources. Source file or destination maybe was not within your mountpoints? T="%s", D="%s"', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.CouldNotAccessAllNecessaryResources', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
            } catch (IllegalFileExtensionException $e) {
                $this->writeLog(2, 1, 111, 'Extension of file name "%s" is not allowed in "%s"!', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.ExtensionOfFileNameIsNotAllowedIn', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
            } catch (ExistingTargetFileNameException $e) {
                $this->writeLog(2, 1, 112, 'File "%s" already exists in folder "%s"!', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.FileAlreadyExistsInFolder', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
            } catch (\BadMethodCallException $e) {
                $this->writeLog(3, 1, 128, 'The function to copy a file between storages is not yet implemented', array());
                $this->addMessageToFlashMessageQueue('FileUtility.TheFunctionToCopyAFileBetweenStoragesIsNotYetImplemented');
            } catch (\RuntimeException $e) {
                $this->writeLog(2, 2, 109, 'File "%s" WAS NOT copied to "%s"! Write-permission problem?', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.FileWasNotCopiedTo', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
            }
            if ($resultObject) {
                $this->writeLog(2, 0, 1, 'File "%s" copied to "%s"', array($sourceFileObject->getIdentifier(), $resultObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.FileCopiedTo', array($sourceFileObject->getIdentifier(), $resultObject->getIdentifier()), FlashMessage::OK);
            }
        } else {
            // Else means this is a Folder
            $sourceFolderObject = $sourceFileObject;
            try {
                $resultObject = $sourceFolderObject->copyTo($targetFolderObject, null, $conflictMode);
            } catch (InsufficientUserPermissionsException $e) {
                $this->writeLog(2, 1, 125, 'You are not allowed to copy directories', []);
                $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToCopyDirectories');
            } catch (\TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException $e) {
                $this->writeLog(2, 1, 110, 'Could not access all necessary resources. Source file or destination maybe was not within your mountpoints? T="%s", D="%s"', array($sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.CouldNotAccessAllNecessaryResources', array($sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()));
            } catch (InsufficientFolderAccessPermissionsException $e) {
                $this->writeLog(2, 1, 121, 'You don\'t have full access to the destination directory "%s"!', array($targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.YouDontHaveFullAccessToTheDestinationDirectory', array($targetFolderObject->getIdentifier()));
            } catch (\TYPO3\CMS\Core\Resource\Exception\InvalidTargetFolderException $e) {
                $this->writeLog(2, 1, 122, 'Cannot copy folder "%s" into target folder "%s", because the target folder is already within the folder to be copied!', array($sourceFolderObject->getName(), $targetFolderObject->getName()));
                $this->addMessageToFlashMessageQueue('FileUtility.CannotCopyFolderIntoTargetFolderBecauseTheTargetFolderIsAlreadyWithinTheFolderToBeCopied', array($sourceFolderObject->getName(), $targetFolderObject->getName()));
            } catch (ExistingTargetFolderException $e) {
                $this->writeLog(2, 1, 123, 'Target "%s" already exists!', array($targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.TargetAlreadyExists', array($targetFolderObject->getIdentifier()));
            } catch (\BadMethodCallException $e) {
                $this->writeLog(3, 1, 129, 'The function to copy a folder between storages is not yet implemented', array());
                $this->addMessageToFlashMessageQueue('FileUtility.TheFunctionToCopyAFolderBetweenStoragesIsNotYetImplemented');
            } catch (\RuntimeException $e) {
                $this->writeLog(2, 2, 119, 'Directory "%s" WAS NOT copied to "%s"! Write-permission problem?', array($sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.DirectoryWasNotCopiedTo', array($sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()));
            }
            if ($resultObject) {
                $this->writeLog(2, 0, 2, 'Directory "%s" copied to "%s"', array($sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.DirectoryCopiedTo', array($sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()), FlashMessage::OK);
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
     * @return \TYPO3\CMS\Core\Resource\File
     */
    protected function func_move($cmds)
    {
        if (!$this->isInit) {
            return false;
        }
        $sourceFileObject = $this->getFileObject($cmds['data']);
        $targetFolderObject = $this->getFileObject($cmds['target']);
        // Basic check
        if (!$targetFolderObject instanceof Folder) {
            $this->writeLog(3, 2, 100, 'Destination "%s" was not a directory', array($cmds['target']));
            $this->addMessageToFlashMessageQueue('FileUtility.DestinationWasNotADirectory', array($cmds['target']));
            return false;
        }
        $alternativeName = (string)$cmds['altName'];
        $resultObject = null;
        // Moving the file
        if ($sourceFileObject instanceof File) {
            try {
                if ($alternativeName !== '') {
                    // Don't allow overwriting existing files, but find a new name
                    $resultObject = $sourceFileObject->moveTo($targetFolderObject, $alternativeName, DuplicationBehavior::RENAME);
                } else {
                    // Don't allow overwriting existing files
                    $resultObject = $sourceFileObject->moveTo($targetFolderObject, null, DuplicationBehavior::CANCEL);
                }
                $this->writeLog(3, 0, 1, 'File "%s" moved to "%s"', array($sourceFileObject->getIdentifier(), $resultObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.FileMovedTo', array($sourceFileObject->getIdentifier(), $resultObject->getIdentifier()), FlashMessage::OK);
            } catch (InsufficientUserPermissionsException $e) {
                $this->writeLog(3, 1, 114, 'You are not allowed to move files', []);
                $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToMoveFiles');
            } catch (\TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException $e) {
                $this->writeLog(3, 1, 110, 'Could not access all necessary resources. Source file or destination maybe was not within your mountpoints? T="%s", D="%s"', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.CouldNotAccessAllNecessaryResources', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
            } catch (IllegalFileExtensionException $e) {
                $this->writeLog(3, 1, 111, 'Extension of file name "%s" is not allowed in "%s"!', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.ExtensionOfFileNameIsNotAllowedIn', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
            } catch (ExistingTargetFileNameException $e) {
                $this->writeLog(3, 1, 112, 'File "%s" already exists in folder "%s"!', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.FileAlreadyExistsInFolder', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
            } catch (\BadMethodCallException $e) {
                $this->writeLog(3, 1, 126, 'The function to move a file between storages is not yet implemented', array());
                $this->addMessageToFlashMessageQueue('FileUtility.TheFunctionToMoveAFileBetweenStoragesIsNotYetImplemented');
            } catch (\RuntimeException $e) {
                $this->writeLog(3, 2, 109, 'File "%s" WAS NOT copied to "%s"! Write-permission problem?', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.FileWasNotCopiedTo', array($sourceFileObject->getIdentifier(), $targetFolderObject->getIdentifier()));
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
                $this->writeLog(3, 0, 2, 'Directory "%s" moved to "%s"', array($sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.DirectoryMovedTo', array($sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()), FlashMessage::OK);
            } catch (InsufficientUserPermissionsException $e) {
                $this->writeLog(3, 1, 125, 'You are not allowed to move directories', []);
                $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToMoveDirectories');
            } catch (\TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException $e) {
                $this->writeLog(3, 1, 110, 'Could not access all necessary resources. Source file or destination maybe was not within your mountpoints? T="%s", D="%s"', array($sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.CouldNotAccessAllNecessaryResources', array($sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()));
            } catch (InsufficientFolderAccessPermissionsException $e) {
                $this->writeLog(3, 1, 121, 'You don\'t have full access to the destination directory "%s"!', array($targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.YouDontHaveFullAccessToTheDestinationDirectory', array($targetFolderObject->getIdentifier()));
            } catch (\TYPO3\CMS\Core\Resource\Exception\InvalidTargetFolderException $e) {
                $this->writeLog(3, 1, 122, 'Cannot move folder "%s" into target folder "%s", because the target folder is already within the folder to be moved!', array($sourceFolderObject->getName(), $targetFolderObject->getName()));
                $this->addMessageToFlashMessageQueue('FileUtility.CannotMoveFolderIntoTargetFolderBecauseTheTargetFolderIsAlreadyWithinTheFolderToBeMoved', array($sourceFolderObject->getName(), $targetFolderObject->getName()));
            } catch (ExistingTargetFolderException $e) {
                $this->writeLog(3, 1, 123, 'Target "%s" already exists!', array($targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.TargetAlreadyExists', array($targetFolderObject->getIdentifier()));
            } catch (\BadMethodCallException $e) {
                $this->writeLog(3, 1, 127, 'The function to move a folder between storages is not yet implemented', array());
                $this->addMessageToFlashMessageQueue('FileUtility.TheFunctionToMoveAFolderBetweenStoragesIsNotYetImplemented', array());
            } catch (\RuntimeException $e) {
                $this->writeLog(3, 2, 119, 'Directory "%s" WAS NOT moved to "%s"! Write-permission problem?', array($sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.DirectoryWasNotMovedTo', array($sourceFolderObject->getIdentifier(), $targetFolderObject->getIdentifier()));
            }
        }
        return $resultObject;
    }

    /**
     * Renaming files or foldes (action=5)
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
        if (!$this->isInit) {
            return false;
        }
        $sourceFileObject = $this->getFileObject($cmds['data']);
        $sourceFile = $sourceFileObject->getName();
        $targetFile = $cmds['target'];
        $resultObject = null;
        if ($sourceFileObject instanceof File) {
            try {
                // Try to rename the File
                $resultObject = $sourceFileObject->rename($targetFile);
                $this->writeLog(5, 0, 1, 'File renamed from "%s" to "%s"', array($sourceFile, $targetFile));
                $this->addMessageToFlashMessageQueue('FileUtility.FileRenamedFromTo', array($sourceFile, $targetFile), FlashMessage::OK);
            } catch (InsufficientUserPermissionsException $e) {
                $this->writeLog(5, 1, 102, 'You are not allowed to rename files!', []);
                $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToRenameFiles');
            } catch (IllegalFileExtensionException $e) {
                $this->writeLog(5, 1, 101, 'Extension of file name "%s" or "%s" was not allowed!', array($sourceFileObject->getName(), $targetFile));
                $this->addMessageToFlashMessageQueue('FileUtility.ExtensionOfFileNameOrWasNotAllowed', array($sourceFileObject->getName(), $targetFile));
            } catch (ExistingTargetFileNameException $e) {
                $this->writeLog(5, 1, 120, 'Destination "%s" existed already!', array($targetFile));
                $this->addMessageToFlashMessageQueue('FileUtility.DestinationExistedAlready', array($targetFile));
            } catch (NotInMountPointException $e) {
                $this->writeLog(5, 1, 121, 'Destination path "%s" was not within your mountpoints!', array($targetFile));
                $this->addMessageToFlashMessageQueue('FileUtility.DestinationPathWasNotWithinYourMountpoints', array($targetFile));
            } catch (\RuntimeException $e) {
                $this->writeLog(5, 1, 100, 'File "%s" was not renamed! Write-permission problem in "%s"?', array($sourceFileObject->getName(), $targetFile));
                $this->addMessageToFlashMessageQueue('FileUtility.FileWasNotRenamed', array($sourceFileObject->getName(), $targetFile));
            }
        } else {
            // Else means this is a Folder
            try {
                // Try to rename the Folder
                $resultObject = $sourceFileObject->rename($targetFile);
                $this->writeLog(5, 0, 2, 'Directory renamed from "%s" to "%s"', array($sourceFile, $targetFile));
                $this->addMessageToFlashMessageQueue('FileUtility.DirectoryRenamedFromTo', array($sourceFile, $targetFile), FlashMessage::OK);
            } catch (InsufficientUserPermissionsException $e) {
                $this->writeLog(5, 1, 111, 'You are not allowed to rename directories!', []);
                $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToRenameDirectories');
            } catch (ExistingTargetFileNameException $e) {
                $this->writeLog(5, 1, 120, 'Destination "%s" existed already!', array($targetFile));
                $this->addMessageToFlashMessageQueue('FileUtility.DestinationExistedAlready', array($targetFile));
            } catch (NotInMountPointException $e) {
                $this->writeLog(5, 1, 121, 'Destination path "%s" was not within your mountpoints!', array($targetFile));
                $this->addMessageToFlashMessageQueue('FileUtility.DestinationPathWasNotWithinYourMountpoints', array($targetFile));
            } catch (\RuntimeException $e) {
                $this->writeLog(5, 1, 110, 'Directory "%s" was not renamed! Write-permission problem in "%s"?', array($sourceFileObject->getName(), $targetFile));
                $this->addMessageToFlashMessageQueue('FileUtility.DirectoryWasNotRenamed', array($sourceFileObject->getName(), $targetFile));
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
     * @return \TYPO3\CMS\Core\Resource\Folder Returns the new foldername upon success
     */
    public function func_newfolder($cmds)
    {
        if (!$this->isInit) {
            return false;
        }
        $targetFolderObject = $this->getFileObject($cmds['target']);
        if (!$targetFolderObject instanceof Folder) {
            $this->writeLog(6, 2, 104, 'Destination "%s" was not a directory', array($cmds['target']));
            $this->addMessageToFlashMessageQueue('FileUtility.DestinationWasNotADirectory', array($cmds['target']));
            return false;
        }
        $resultObject = null;
        try {
            $folderName = $cmds['data'];
            $resultObject = $targetFolderObject->createFolder($folderName);
            $this->writeLog(6, 0, 1, 'Directory "%s" created in "%s"', array($folderName, $targetFolderObject->getIdentifier()));
            $this->addMessageToFlashMessageQueue('FileUtility.DirectoryCreatedIn', array($folderName, $targetFolderObject->getIdentifier()), FlashMessage::OK);
        } catch (\TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException $e) {
            $this->writeLog(6, 1, 104, 'Invalid folder name "%s"!', [$folderName]);
            $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToCreateDirectories', [$folderName]);
        } catch (\TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException $e) {
            $this->writeLog(6, 1, 103, 'You are not allowed to create directories!', []);
            $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToCreateDirectories');
        } catch (\TYPO3\CMS\Core\Resource\Exception\NotInMountPointException $e) {
            $this->writeLog(6, 1, 102, 'Destination path "%s" was not within your mountpoints!', array($targetFolderObject->getIdentifier()));
            $this->addMessageToFlashMessageQueue('FileUtility.DestinationPathWasNotWithinYourMountpoints', array($targetFolderObject->getIdentifier()));
        } catch (\TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException $e) {
            $this->writeLog(6, 1, 101, 'File or directory "%s" existed already!', array($folderName));
            $this->addMessageToFlashMessageQueue('FileUtility.FileOrDirectoryExistedAlready', array($folderName));
        } catch (\RuntimeException $e) {
            $this->writeLog(6, 1, 100, 'Directory "%s" not created. Write-permission problem in "%s"?', array($folderName, $targetFolderObject->getIdentifier()));
            $this->addMessageToFlashMessageQueue('FileUtility.DirectoryNotCreated', array($folderName, $targetFolderObject->getIdentifier()));
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
     * @return string Returns the new filename upon success
     */
    public function func_newfile($cmds)
    {
        if (!$this->isInit) {
            return false;
        }
        $targetFolderObject = $this->getFileObject($cmds['target']);
        if (!$targetFolderObject instanceof Folder) {
            $this->writeLog(8, 2, 104, 'Destination "%s" was not a directory', array($cmds['target']));
            $this->addMessageToFlashMessageQueue('FileUtility.DestinationWasNotADirectory', array($cmds['target']));
            return false;
        }
        $resultObject = null;
        $fileName = $cmds['data'];
        try {
            $resultObject = $targetFolderObject->createFile($fileName);
            $this->writeLog(8, 0, 1, 'File created: "%s"', array($fileName));
            $this->addMessageToFlashMessageQueue('FileUtility.FileCreated', array($fileName), FlashMessage::OK);
        } catch (IllegalFileExtensionException $e) {
            $this->writeLog(8, 1, 106, 'Extension of file "%s" was not allowed!', array($fileName));
            $this->addMessageToFlashMessageQueue('FileUtility.ExtensionOfFileWasNotAllowed', array($fileName));
        } catch (InsufficientFolderWritePermissionsException $e) {
            $this->writeLog(8, 1, 103, 'You are not allowed to create files!', []);
            $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToCreateFiles');
        } catch (NotInMountPointException $e) {
            $this->writeLog(8, 1, 102, 'Destination path "%s" was not within your mountpoints!', array($targetFolderObject->getIdentifier()));
            $this->addMessageToFlashMessageQueue('FileUtility.DestinationPathWasNotWithinYourMountpoints', array($targetFolderObject->getIdentifier()));
        } catch (ExistingTargetFileNameException $e) {
            $this->writeLog(8, 1, 101, 'File existed already in "%s"!', array($targetFolderObject->getIdentifier()));
            $this->addMessageToFlashMessageQueue('FileUtility.FileExistedAlreadyIn', array($targetFolderObject->getIdentifier()));
        } catch (InvalidFileNameException $e) {
            $this->writeLog(8, 1, 106, 'File name "%s" was not allowed!', array($fileName));
            $this->addMessageToFlashMessageQueue('FileUtility.FileNameWasNotAllowed', array($fileName));
        } catch (\RuntimeException $e) {
            $this->writeLog(8, 1, 100, 'File "%s" was not created! Write-permission problem in "%s"?', array($fileName, $targetFolderObject->getIdentifier()));
            $this->addMessageToFlashMessageQueue('FileUtility.FileWasNotCreated', array($fileName, $targetFolderObject->getIdentifier()));
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
        if (!$this->isInit) {
            return false;
        }
        // Example indentifier for $cmds['target'] => "4:mypath/tomyfolder/myfile.jpg"
        // for backwards compatibility: the combined file identifier was the path+filename
        $fileIdentifier = $cmds['target'];
        $fileObject = $this->getFileObject($fileIdentifier);
        // Example indentifier for $cmds['target'] => "2:targetpath/targetfolder/"
        $content = $cmds['data'];
        if (!$fileObject instanceof File) {
            $this->writeLog(9, 2, 123, 'Target "%s" was not a file!', array($fileIdentifier));
            $this->addMessageToFlashMessageQueue('FileUtility.TargetWasNotAFile', array($fileIdentifier));
            return false;
        }
        $extList = $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'];
        if (!GeneralUtility::inList($extList, $fileObject->getExtension())) {
            $this->writeLog(9, 1, 102, 'File extension "%s" is not a textfile format! (%s)', array($fileObject->getExtension(), $extList));
            $this->addMessageToFlashMessageQueue('FileUtility.FileExtensionIsNotATextfileFormat', array($fileObject->getExtension(), $extList));
            return false;
        }
        try {
            $fileObject->setContents($content);
            clearstatcache();
            $this->writeLog(9, 0, 1, 'File saved to "%s", bytes: %s, MD5: %s ', array($fileObject->getIdentifier(), $fileObject->getSize(), md5($content)));
            $this->addMessageToFlashMessageQueue('FileUtility.FileSavedToBytesMd5', array($fileObject->getIdentifier(), $fileObject->getSize(), md5($content)), FlashMessage::OK);
            return true;
        } catch (InsufficientUserPermissionsException $e) {
            $this->writeLog(9, 1, 104, 'You are not allowed to edit files!', []);
            $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToEditFiles');
            return false;
        } catch (InsufficientFileWritePermissionsException $e) {
            $this->writeLog(9, 1, 100, 'File "%s" was not saved! Write-permission problem?', array($fileObject->getIdentifier()));
            $this->addMessageToFlashMessageQueue('FileUtility.FileWasNotSaved', array($fileObject->getIdentifier()));
            return false;
        } catch (IllegalFileExtensionException $e) {
            $this->writeLog(9, 1, 100, 'File "%s" was not saved! File extension rejected!', array($fileObject->getIdentifier()));
            $this->addMessageToFlashMessageQueue('FileUtility.FileWasNotSaved', array($fileObject->getIdentifier()));
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
     * @return File[] | FALSE Returns an array of new file objects upon success. False otherwise
     */
    public function func_upload($cmds)
    {
        if (!$this->isInit) {
            return false;
        }
        $uploadPosition = $cmds['data'];
        $uploadedFileData = $_FILES['upload_' . $uploadPosition];
        if (empty($uploadedFileData['name']) || is_array($uploadedFileData['name']) && empty($uploadedFileData['name'][0])) {
            $this->writeLog(1, 2, 108, 'No file was uploaded!', []);
            $this->addMessageToFlashMessageQueue('FileUtility.NoFileWasUploaded');
            return false;
        }
        // Example indentifier for $cmds['target'] => "2:targetpath/targetfolder/"
        $targetFolderObject = $this->getFileObject($cmds['target']);
        // Uploading with non HTML-5-style, thus, make an array out of it, so we can loop over it
        if (!is_array($uploadedFileData['name'])) {
            $uploadedFileData = array(
                'name' => array($uploadedFileData['name']),
                'type' => array($uploadedFileData['type']),
                'tmp_name' => array($uploadedFileData['tmp_name']),
                'size' => array($uploadedFileData['size'])
            );
        }
        $resultObjects = array();
        $numberOfUploadedFilesForPosition = count($uploadedFileData['name']);
        // Loop through all uploaded files
        for ($i = 0; $i < $numberOfUploadedFilesForPosition; $i++) {
            $fileInfo = array(
                'name' => $uploadedFileData['name'][$i],
                'type' => $uploadedFileData['type'][$i],
                'tmp_name' => $uploadedFileData['tmp_name'][$i],
                'size' => $uploadedFileData['size'][$i]
            );
            try {
                /** @var $fileObject File */
                $fileObject = $targetFolderObject->addUploadedFile($fileInfo, (string)$this->existingFilesConflictMode);
                $fileObject = ResourceFactory::getInstance()->getFileObjectByStorageAndIdentifier($targetFolderObject->getStorage()->getUid(), $fileObject->getIdentifier());
                if ($this->existingFilesConflictMode->equals(DuplicationBehavior::REPLACE)) {
                    $this->getIndexer($fileObject->getStorage())->updateIndexEntry($fileObject);
                }
                $resultObjects[] = $fileObject;
                $this->internalUploadMap[$uploadPosition] = $fileObject->getCombinedIdentifier();
                $this->writeLog(1, 0, 1, 'Uploading file "%s" to "%s"', array($fileInfo['name'], $targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.UploadingFileTo', array($fileInfo['name'], $targetFolderObject->getIdentifier()), FlashMessage::OK);
            } catch (InsufficientFileWritePermissionsException $e) {
                $this->writeLog(1, 1, 107, 'You are not allowed to override "%s"!', array($fileInfo['name']));
                $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToOverride', array($fileInfo['name']));
            } catch (UploadException $e) {
                $this->writeLog(1, 2, 106, 'The upload has failed, no uploaded file found!', []);
                $this->addMessageToFlashMessageQueue('FileUtility.TheUploadHasFailedNoUploadedFileFound');
            } catch (InsufficientUserPermissionsException $e) {
                $this->writeLog(1, 1, 105, 'You are not allowed to upload files!', []);
                $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToUploadFiles');
            } catch (UploadSizeException $e) {
                $this->writeLog(1, 1, 104, 'The uploaded file "%s" exceeds the size-limit', array($fileInfo['name']));
                $this->addMessageToFlashMessageQueue('FileUtility.TheUploadedFileExceedsTheSize-limit', array($fileInfo['name']));
            } catch (InsufficientFolderWritePermissionsException $e) {
                $this->writeLog(1, 1, 103, 'Destination path "%s" was not within your mountpoints!', array($targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.DestinationPathWasNotWithinYourMountpoints', array($targetFolderObject->getIdentifier()));
            } catch (IllegalFileExtensionException $e) {
                $this->writeLog(1, 1, 102, 'Extension of file name "%s" is not allowed in "%s"!', array($fileInfo['name'], $targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.ExtensionOfFileNameIsNotAllowedIn', array($fileInfo['name'], $targetFolderObject->getIdentifier()));
            } catch (ExistingTargetFileNameException $e) {
                $this->writeLog(1, 1, 101, 'No unique filename available in "%s"!', array($targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.NoUniqueFilenameAvailableIn', array($targetFolderObject->getIdentifier()));
            } catch (\RuntimeException $e) {
                $this->writeLog(1, 1, 100, 'Uploaded file could not be moved! Write-permission problem in "%s"?', array($targetFolderObject->getIdentifier()));
                $this->addMessageToFlashMessageQueue('FileUtility.UploadedFileCouldNotBeMoved', array($targetFolderObject->getIdentifier()));
            }
        }

        return $resultObjects;
    }

    /**
     * Replaces a file on the filesystem and changes the identifier of the persisted file object in sys_file if
     * keepFilename is not checked. If keepFilename is checked, only the file content will be replaced.
     *
     * @param array $cmdArr
     * @return array|bool
     * @throws Exception\InsufficientFileAccessPermissionsException
     * @throws Exception\InvalidFileException
     * @throws \RuntimeException
     */
    protected function replaceFile(array $cmdArr)
    {
        if (!$this->isInit) {
            return false;
        }

        $uploadPosition = $cmdArr['data'];
        $fileInfo = $_FILES['replace_' . $uploadPosition];
        if (empty($fileInfo['name'])) {
            $this->writeLog(1, 2, 108, 'No file was uploaded for replacing!', []);
            $this->addMessageToFlashMessageQueue('FileUtility.NoFileWasUploadedForReplacing');
            return false;
        }

        $keepFileName = ($cmdArr['keepFilename'] == 1) ? true : false;
        $resultObjects = array();

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

            $this->writeLog(1, 0, 1, 'Replacing file "%s" to "%s"', array($fileInfo['name'], $fileObjectToReplace->getIdentifier()));
            $this->addMessageToFlashMessageQueue('FileUtility.ReplacingFileTo', array($fileInfo['name'], $fileObjectToReplace->getIdentifier()), FlashMessage::OK);
        } catch (InsufficientFileWritePermissionsException $e) {
            $this->writeLog(1, 1, 107, 'You are not allowed to override "%s"!', array($fileInfo['name']));
            $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToOverride', array($fileInfo['name']));
        } catch (UploadException $e) {
            $this->writeLog(1, 2, 106, 'The upload has failed, no uploaded file found!', []);
            $this->addMessageToFlashMessageQueue('FileUtility.TheUploadHasFailedNoUploadedFileFound');
        } catch (InsufficientUserPermissionsException $e) {
            $this->writeLog(1, 1, 105, 'You are not allowed to upload files!', []);
            $this->addMessageToFlashMessageQueue('FileUtility.YouAreNotAllowedToUploadFiles');
        } catch (UploadSizeException $e) {
            $this->writeLog(1, 1, 104, 'The uploaded file "%s" exceeds the size-limit', array($fileInfo['name']));
            $this->addMessageToFlashMessageQueue('FileUtility.TheUploadedFileExceedsTheSize-limit', array($fileInfo['name']));
        } catch (InsufficientFolderWritePermissionsException $e) {
            $this->writeLog(1, 1, 103, 'Destination path "%s" was not within your mountpoints!', array($fileObjectToReplace->getIdentifier()));
            $this->addMessageToFlashMessageQueue('FileUtility.DestinationPathWasNotWithinYourMountpoints', array($fileObjectToReplace->getIdentifier()));
        } catch (IllegalFileExtensionException $e) {
            $this->writeLog(1, 1, 102, 'Extension of file name "%s" is not allowed in "%s"!', array($fileInfo['name'], $fileObjectToReplace->getIdentifier()));
            $this->addMessageToFlashMessageQueue('FileUtility.ExtensionOfFileNameIsNotAllowedIn', array($fileInfo['name'], $fileObjectToReplace->getIdentifier()));
        } catch (ExistingTargetFileNameException $e) {
            $this->writeLog(1, 1, 101, 'No unique filename available in "%s"!', array($fileObjectToReplace->getIdentifier()));
            $this->addMessageToFlashMessageQueue('FileUtility.NoUniqueFilenameAvailableIn', array($fileObjectToReplace->getIdentifier()));
        } catch (\RuntimeException $e) {
            throw $e;
        }
        return $resultObjects;
    }

    /**
     * Add flash message to message queue
     *
     * @param FlashMessage $flashMessage
     * @return void
     */
    protected function addFlashMessage(FlashMessage $flashMessage)
    {
        /** @var $flashMessageService FlashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);

        /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * Gets Indexer
     *
     * @param \TYPO3\CMS\Core\Resource\ResourceStorage $storage
     * @return \TYPO3\CMS\Core\Resource\Index\Indexer
     */
    protected function getIndexer(ResourceStorage $storage)
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Index\Indexer::class, $storage);
    }

    /**
     * Get database connection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
