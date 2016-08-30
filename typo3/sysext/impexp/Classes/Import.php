<?php
namespace TYPO3\CMS\Impexp;

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
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * T3D file Import library (TYPO3 Record Document)
 */
class Import extends ImportExport
{
    /**
     * Used to register the forged UID values for imported records that we want
     * to create with the same UIDs as in the import file. Admin-only feature.
     *
     * @var array
     */
    public $suggestedInsertUids = [];

    /**
     * Disable logging when importing
     *
     * @var bool
     */
    public $enableLogging = false;

    /**
     * Keys are [tablename]:[new NEWxxx ids (or when updating it is uids)]
     * while values are arrays with table/uid of the original record it is based on.
     * With the array keys the new ids can be looked up inside tcemain
     *
     * @var array
     */
    public $import_newId = [];

    /**
     * Page id map for page tree (import)
     *
     * @var array
     */
    public $import_newId_pids = [];

    /**
     * Internal data accumulation for writing records during import
     *
     * @var array
     */
    public $import_data = [];

    /**
     * Array of current registered storage objects
     *
     * @var ResourceStorage[]
     */
    protected $storageObjects = [];

    /**
     * Is set, if the import file has a TYPO3 version below 6.0
     *
     * @var bool
     */
    protected $legacyImport = false;

    /**
     * @var \TYPO3\CMS\Core\Resource\Folder
     */
    protected $legacyImportFolder = null;

    /**
     * Related to the default storage root
     *
     * @var string
     */
    protected $legacyImportTargetPath = '_imported/';

    /**
     * Table fields to migrate
     *
     * @var array
     */
    protected $legacyImportMigrationTables = [
        'tt_content' => [
            'image' => [
                'titleTexts' => 'titleText',
                'description' => 'imagecaption',
                'links' => 'image_link',
                'alternativeTexts' => 'altText'
            ],
            'media' => [
                'description' => 'imagecaption',
            ]
        ],
        'pages' => [
            'media' => []
        ],
        'pages_language_overlay' => [
            'media' => []
        ]
    ];

    /**
     * Records to be migrated after all
     * Multidimensional array [table][uid][field] = array([related sys_file_reference uids])
     *
     * @var array
     */
    protected $legacyImportMigrationRecords = [];

    /**
     * @var NULL|string
     */
    protected $filesPathForImport = null;

    /**
     * @var array
     */
    protected $unlinkFiles = [];

    /**
     * @var array
     */
    protected $alternativeFileName = [];

    /**
     * @var array
     */
    protected $alternativeFilePath = [];

    /**
     * @var array
     */
    protected $filePathMap = [];

    /**************************
     * Initialize
     *************************/

    /**
     * Init the object
     *
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->mode = 'import';
    }

    /***********************
     * Import
     ***********************/

    /**
     * Initialize all settings for the import
     *
     * @return void
     */
    protected function initializeImport()
    {
        // Set this flag to indicate that an import is being/has been done.
        $this->doesImport = 1;
        // Initialize:
        // These vars MUST last for the whole section not being cleared. They are used by the method setRelations() which are called at the end of the import session.
        $this->import_mapId = [];
        $this->import_newId = [];
        $this->import_newId_pids = [];
        // Temporary files stack initialized:
        $this->unlinkFiles = [];
        $this->alternativeFileName = [];
        $this->alternativeFilePath = [];

        $this->initializeStorageObjects();
    }

    /**
     * Initialize the all present storage objects
     *
     * @return void
     */
    protected function initializeStorageObjects()
    {
        /** @var $storageRepository StorageRepository */
        $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $this->storageObjects = $storageRepository->findAll();
    }

    /**
     * Imports the internal data array to $pid.
     *
     * @param int $pid Page ID in which to import the content
     * @return void
     */
    public function importData($pid)
    {
        $this->initializeImport();

        // Write sys_file_storages first
        $this->writeSysFileStorageRecords();
        // Write sys_file records and write the binary file data
        $this->writeSysFileRecords();
        // Write records, first pages, then the rest
        // Fields with "hard" relations to database, files and flexform fields are kept empty during this run
        $this->writeRecords_pages($pid);
        $this->writeRecords_records($pid);
        // Finally all the file and DB record references must be fixed. This is done after all records have supposedly been written to database:
        // $this->import_mapId will indicate two things: 1) that a record WAS written to db and 2) that it has got a new id-number.
        $this->setRelations();
        // And when all DB relations are in place, we can fix file and DB relations in flexform fields (since data structures often depends on relations to a DS record):
        $this->setFlexFormRelations();
        // Unlink temporary files:
        $this->unlinkTempFiles();
        // Finally, traverse all records and process softreferences with substitution attributes.
        $this->processSoftReferences();
        // After all migrate records using sys_file_reference now
        if ($this->legacyImport) {
            $this->migrateLegacyImportRecords();
        }
    }

    /**
     * Imports the sys_file_storage records from internal data array.
     *
     * @return void
     */
    protected function writeSysFileStorageRecords()
    {
        if (!isset($this->dat['header']['records']['sys_file_storage'])) {
            return;
        }
        $sysFileStorageUidsToBeResetToDefaultStorage = [];
        foreach ($this->dat['header']['records']['sys_file_storage'] as $sysFileStorageUid => $_) {
            $storageRecord = $this->dat['records']['sys_file_storage:' . $sysFileStorageUid]['data'];
            // continue with Local, writable and online storage only
            if ($storageRecord['driver'] === 'Local' && $storageRecord['is_writable'] && $storageRecord['is_online']) {
                foreach ($this->storageObjects as $localStorage) {
                    if ($this->isEquivalentObjectStorage($localStorage, $storageRecord)) {
                        $this->import_mapId['sys_file_storage'][$sysFileStorageUid] = $localStorage->getUid();
                        break;
                    }
                }

                if (!isset($this->import_mapId['sys_file_storage'][$sysFileStorageUid])) {
                    // Local, writable and online storage. Is allowed to be used to later write files in.
                    // Does currently not exist so add the record.
                    $this->addSingle('sys_file_storage', $sysFileStorageUid, 0);
                }
            } else {
                // Storage with non Local drivers could be imported but must not be used to saves files in, because you
                // could not be sure, that this is supported. The default storage will be used in this case.
                // It could happen that non writable and non online storage will be created as dupes because you could not
                // check the detailed configuration options at this point
                $this->addSingle('sys_file_storage', $sysFileStorageUid, 0);
                $sysFileStorageUidsToBeResetToDefaultStorage[] = $sysFileStorageUid;
            }
        }

        // Importing the added ones
        $tce = $this->getNewTCE();
        // Because all records are being submitted in their correct order with positive pid numbers - and so we should reverse submission order internally.
        $tce->reverseOrder = 1;
        $tce->isImporting = true;
        $tce->start($this->import_data, []);
        $tce->process_datamap();
        $this->addToMapId($tce->substNEWwithIDs);

        $defaultStorageUid = null;
        // get default storage
        $defaultStorage = ResourceFactory::getInstance()->getDefaultStorage();
        if ($defaultStorage !== null) {
            $defaultStorageUid = $defaultStorage->getUid();
        }
        foreach ($sysFileStorageUidsToBeResetToDefaultStorage as $sysFileStorageUidToBeResetToDefaultStorage) {
            $this->import_mapId['sys_file_storage'][$sysFileStorageUidToBeResetToDefaultStorage] = $defaultStorageUid;
        }

        // unset the sys_file_storage records to prevent an import in writeRecords_records
        unset($this->dat['header']['records']['sys_file_storage']);
    }

    /**
     * Determines whether the passed storage object and record (sys_file_storage) can be
     * seen as equivalent during import.
     *
     * @param ResourceStorage $storageObject The storage object which should get compared
     * @param array $storageRecord The storage record which should get compared
     * @return bool Returns TRUE when both object storages can be seen as equivalent
     */
    protected function isEquivalentObjectStorage(ResourceStorage $storageObject, array $storageRecord)
    {
        // compare the properties: driver, writable and online
        if ($storageObject->getDriverType() === $storageRecord['driver']
            && (bool)$storageObject->isWritable() === (bool)$storageRecord['is_writable']
            && (bool)$storageObject->isOnline() === (bool)$storageRecord['is_online']
        ) {
            $storageRecordConfiguration = ResourceFactory::getInstance()
                ->convertFlexFormDataToConfigurationArray($storageRecord['configuration']);
            $storageObjectConfiguration = $storageObject->getConfiguration();
            // compare the properties: pathType and basePath
            if ($storageRecordConfiguration['pathType'] === $storageObjectConfiguration['pathType']
                && $storageRecordConfiguration['basePath'] === $storageObjectConfiguration['basePath']
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks any prerequisites necessary to get fullfilled before import
     *
     * @return array Messages explaining issues which need to get resolved before import
     */
    public function checkImportPrerequisites()
    {
        $messages = [];

        // Check #1: Extension dependencies
        $extKeysToInstall = [];
        foreach ($this->dat['header']['extensionDependencies'] as $extKey) {
            if (!empty($extKey) && !ExtensionManagementUtility::isLoaded($extKey)) {
                $extKeysToInstall[] = $extKey;
            }
        }
        if (!empty($extKeysToInstall)) {
            $messages['missingExtensions'] = 'Before you can install this T3D file you need to install the extensions "'
                . implode('", "', $extKeysToInstall) . '".';
        }

        // Check #2: If the path for every local storage object exists.
        // Else files can't get moved into a newly imported storage.
        if (!empty($this->dat['header']['records']['sys_file_storage'])) {
            foreach ($this->dat['header']['records']['sys_file_storage'] as $sysFileStorageUid => $_) {
                $storageRecord = $this->dat['records']['sys_file_storage:' . $sysFileStorageUid]['data'];
                // continue with Local, writable and online storage only
                if ($storageRecord['driver'] === 'Local'
                    && $storageRecord['is_writable']
                    && $storageRecord['is_online']
                ) {
                    foreach ($this->storageObjects as $localStorage) {
                        if ($this->isEquivalentObjectStorage($localStorage, $storageRecord)) {
                            // There is already an existing storage
                            break;
                        }

                        // The storage from the import does not have an equivalent storage
                        // in the current instance (same driver, same path, etc.). Before
                        // the storage record can get inserted later on take care the path
                        // it points to really exists and is accessible.
                        $storageRecordUid = $storageRecord['uid'];
                        // Unset the storage record UID when trying to create the storage object
                        // as the record does not already exist in DB. The constructor of the
                        // storage object will check whether the target folder exists and set the
                        // isOnline flag depending on the outcome.
                        $storageRecord['uid'] = 0;
                        $resourceStorage = ResourceFactory::getInstance()->createStorageObject($storageRecord);
                        if (!$resourceStorage->isOnline()) {
                            $configuration = $resourceStorage->getConfiguration();
                            $messages['resourceStorageFolderMissing_' . $storageRecordUid] =
                                'The resource storage "'
                                . $resourceStorage->getName()
                                . $configuration['basePath']
                                . '" does not exist. Please create the directory prior to starting the import!';
                        }
                    }
                }
            }
        }

        return $messages;
    }

    /**
     * Imports the sys_file records and the binary files data from internal data array.
     *
     * @return void
     */
    protected function writeSysFileRecords()
    {
        if (!isset($this->dat['header']['records']['sys_file'])) {
            return;
        }
        $this->addGeneralErrorsByTable('sys_file');

        // fetch fresh storage records from database
        $storageRecords = $this->fetchStorageRecords();

        $defaultStorage = ResourceFactory::getInstance()->getDefaultStorage();

        $sanitizedFolderMappings = [];

        foreach ($this->dat['header']['records']['sys_file'] as $sysFileUid => $_) {
            $fileRecord = $this->dat['records']['sys_file:' . $sysFileUid]['data'];

            $temporaryFile = null;
            // check if there is the right file already in the local folder
            if ($this->filesPathForImport !== null) {
                if (is_file($this->filesPathForImport . '/' . $fileRecord['sha1']) && sha1_file($this->filesPathForImport . '/' . $fileRecord['sha1']) === $fileRecord['sha1']) {
                    $temporaryFile = $this->filesPathForImport . '/' . $fileRecord['sha1'];
                }
            }

            // save file to disk
            if ($temporaryFile === null) {
                $fileId = md5($fileRecord['storage'] . ':' . $fileRecord['identifier_hash']);
                $temporaryFile = $this->writeTemporaryFileFromData($fileId);
                if ($temporaryFile === null) {
                    // error on writing the file. Error message was already added
                    continue;
                }
            }

            $originalStorageUid = $fileRecord['storage'];
            $useStorageFromStorageRecords = false;

            // replace storage id, if an alternative one was registered
            if (isset($this->import_mapId['sys_file_storage'][$fileRecord['storage']])) {
                $fileRecord['storage'] = $this->import_mapId['sys_file_storage'][$fileRecord['storage']];
                $useStorageFromStorageRecords = true;
            }

            if (empty($fileRecord['storage']) && !$this->isFallbackStorage($fileRecord['storage'])) {
                // no storage for the file is defined, mostly because of a missing default storage.
                $this->error('Error: No storage for the file "' . $fileRecord['identifier'] . '" with storage uid "' . $originalStorageUid . '"');
                continue;
            }

            // using a storage from the local storage is only allowed, if the uid is present in the
            // mapping. Only in this case we could be sure, that it's a local, online and writable storage.
            if ($useStorageFromStorageRecords && isset($storageRecords[$fileRecord['storage']])) {
                /** @var $storage \TYPO3\CMS\Core\Resource\ResourceStorage */
                $storage = ResourceFactory::getInstance()->getStorageObject($fileRecord['storage'], $storageRecords[$fileRecord['storage']]);
            } elseif ($this->isFallbackStorage($fileRecord['storage'])) {
                $storage = ResourceFactory::getInstance()->getStorageObject(0);
            } elseif ($defaultStorage !== null) {
                $storage = $defaultStorage;
            } else {
                $this->error('Error: No storage available for the file "' . $fileRecord['identifier'] . '" with storage uid "' . $fileRecord['storage'] . '"');
                continue;
            }

            $newFile = null;

            // check, if there is an identical file
            try {
                if ($storage->hasFile($fileRecord['identifier'])) {
                    $file = $storage->getFile($fileRecord['identifier']);
                    if ($file->getSha1() === $fileRecord['sha1']) {
                        $newFile = $file;
                    }
                }
            } catch (Exception $e) {
            }

            if ($newFile === null) {
                $folderName = PathUtility::dirname(ltrim($fileRecord['identifier'], '/'));
                if (in_array($folderName, $sanitizedFolderMappings)) {
                    $folderName = $sanitizedFolderMappings[$folderName];
                }
                if (!$storage->hasFolder($folderName)) {
                    try {
                        $importFolder = $storage->createFolder($folderName);
                        if ($importFolder->getIdentifier() !== $folderName && !in_array($folderName, $sanitizedFolderMappings)) {
                            $sanitizedFolderMappings[$folderName] = $importFolder->getIdentifier();
                        }
                    } catch (Exception $e) {
                        $this->error('Error: Folder "' . $folderName . '" could not be created for file "' . $fileRecord['identifier'] . '" with storage uid "' . $fileRecord['storage'] . '"');
                        continue;
                    }
                } else {
                    $importFolder = $storage->getFolder($folderName);
                }

                try {
                    /** @var $newFile File */
                    $newFile = $storage->addFile($temporaryFile, $importFolder, $fileRecord['name']);
                } catch (Exception $e) {
                    $this->error('Error: File could not be added to the storage: "' . $fileRecord['identifier'] . '" with storage uid "' . $fileRecord['storage'] . '"');
                    continue;
                }

                if ($newFile->getSha1() !== $fileRecord['sha1']) {
                    $this->error('Error: The hash of the written file is not identical to the import data! File could be corrupted! File: "' . $fileRecord['identifier'] . '" with storage uid "' . $fileRecord['storage'] . '"');
                }
            }

            // save the new uid in the import id map
            $this->import_mapId['sys_file'][$fileRecord['uid']] = $newFile->getUid();
            $this->fixUidLocalInSysFileReferenceRecords($fileRecord['uid'], $newFile->getUid());
        }

        // unset the sys_file records to prevent an import in writeRecords_records
        unset($this->dat['header']['records']['sys_file']);
        // remove all sys_file_reference records that point to file records which are unknown
        // in the system to prevent exceptions
        $this->removeSysFileReferenceRecordsFromImportDataWithRelationToMissingFile();
    }

    /**
     * Removes all sys_file_reference records from the import data array that are pointing to sys_file records which
     * are missing not in the import data to prevent exceptions on checking the related file started by the Datahandler.
     *
     * @return void
     */
    protected function removeSysFileReferenceRecordsFromImportDataWithRelationToMissingFile()
    {
        if (!isset($this->dat['header']['records']['sys_file_reference'])) {
            return;
        }

        foreach ($this->dat['header']['records']['sys_file_reference'] as $sysFileReferenceUid => $_) {
            $fileReferenceRecord = $this->dat['records']['sys_file_reference:' . $sysFileReferenceUid]['data'];
            if (!in_array($fileReferenceRecord['uid_local'], $this->import_mapId['sys_file'])) {
                unset($this->dat['header']['records']['sys_file_reference'][$sysFileReferenceUid]);
                unset($this->dat['records']['sys_file_reference:' . $sysFileReferenceUid]);
                $this->error('Error: sys_file_reference record ' . (int)$sysFileReferenceUid
                             . ' with relation to sys_file record ' . (int)$fileReferenceRecord['uid_local']
                             . ', which is not part of the import data, was not imported.'
                );
            }
        }
    }

    /**
     * Checks if the $storageId is the id of the fallback storage
     *
     * @param int|string $storageId
     * @return bool
     */
    protected function isFallbackStorage($storageId)
    {
        return $storageId === 0 || $storageId === '0';
    }

    /**
     * Normally the importer works like the following:
     * Step 1: import the records with cleared field values of relation fields (see addSingle())
     * Step 2: update the records with the right relation ids (see setRelations())
     *
     * In step 2 the saving fields of type "relation to sys_file_reference" checks the related sys_file_reference
     * record (created in step 1) with the FileExtensionFilter for matching file extensions of the related file.
     * To make this work correct, the uid_local of sys_file_reference records has to be not empty AND has to
     * relate to the correct (imported) sys_file record uid!!!
     *
     * This is fixed here.
     *
     * @param int $oldFileUid
     * @param int $newFileUid
     * @return void
    */
    protected function fixUidLocalInSysFileReferenceRecords($oldFileUid, $newFileUid)
    {
        if (!isset($this->dat['header']['records']['sys_file_reference'])) {
            return;
        }

        foreach ($this->dat['header']['records']['sys_file_reference'] as $sysFileReferenceUid => $_) {
            $fileReferenceRecord = $this->dat['records']['sys_file_reference:' . $sysFileReferenceUid]['data'];
            if ($fileReferenceRecord['uid_local'] == $oldFileUid) {
                $fileReferenceRecord['uid_local'] = $newFileUid;
                $this->dat['records']['sys_file_reference:' . $sysFileReferenceUid]['data'] = $fileReferenceRecord;
            }
        }
    }

    /**
     * Initializes the folder for legacy imports as subfolder of backend users default upload folder
     *
     * @return void
     */
    protected function initializeLegacyImportFolder()
    {
        /** @var \TYPO3\CMS\Core\Resource\Folder $folder */
        $folder = $this->getBackendUser()->getDefaultUploadFolder();
        if ($folder === false) {
            $this->error('Error: the backend users default upload folder is missing! No files will be imported!');
        }
        if (!$folder->hasFolder($this->legacyImportTargetPath)) {
            try {
                $this->legacyImportFolder = $folder->createFolder($this->legacyImportTargetPath);
            } catch (Exception $e) {
                $this->error('Error: the import folder in the default upload folder could not be created! No files will be imported!');
            }
        } else {
            $this->legacyImportFolder = $folder->getSubfolder($this->legacyImportTargetPath);
        }
    }

    /**
     * Fetched fresh storage records from database because the new imported
     * ones are not in cached data of the StorageRepository
     *
     * @return bool|array
     */
    protected function fetchStorageRecords()
    {
        $whereClause = BackendUtility::BEenableFields('sys_file_storage');
        $whereClause .= BackendUtility::deleteClause('sys_file_storage');

        $rows = $this->getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            'sys_file_storage',
            '1=1' . $whereClause,
            '',
            '',
            '',
            'uid'
        );

        return $rows;
    }

    /**
     * Writes the file from import array to temp dir and returns the filename of it.
     *
     * @param string $fileId
     * @param string $dataKey
     * @return string Absolute filename of the temporary filename of the file
     */
    protected function writeTemporaryFileFromData($fileId, $dataKey = 'files_fal')
    {
        $temporaryFilePath = null;
        if (is_array($this->dat[$dataKey][$fileId])) {
            $temporaryFilePathInternal = GeneralUtility::tempnam('import_temp_');
            GeneralUtility::writeFile($temporaryFilePathInternal, $this->dat[$dataKey][$fileId]['content']);
            clearstatcache();
            if (@is_file($temporaryFilePathInternal)) {
                $this->unlinkFiles[] = $temporaryFilePathInternal;
                if (filesize($temporaryFilePathInternal) == $this->dat[$dataKey][$fileId]['filesize']) {
                    $temporaryFilePath = $temporaryFilePathInternal;
                } else {
                    $this->error('Error: temporary file ' . $temporaryFilePathInternal . ' had a size (' . filesize($temporaryFilePathInternal) . ') different from the original (' . $this->dat[$dataKey][$fileId]['filesize'] . ')');
                }
            } else {
                $this->error('Error: temporary file ' . $temporaryFilePathInternal . ' was not written as it should have been!');
            }
        } else {
            $this->error('Error: No file found for ID ' . $fileId);
        }
        return $temporaryFilePath;
    }

    /**
     * Writing pagetree/pages to database:
     *
     * @param int $pid PID in which to import. If the operation is an update operation, the root of the page tree inside will be moved to this PID unless it is the same as the root page from the import
     * @return void
     * @see writeRecords_records()
     */
    public function writeRecords_pages($pid)
    {
        // First, write page structure if any:
        if (is_array($this->dat['header']['records']['pages'])) {
            $this->addGeneralErrorsByTable('pages');
            // $pageRecords is a copy of the pages array in the imported file. Records here are unset one by one when the addSingle function is called.
            $pageRecords = $this->dat['header']['records']['pages'];
            $this->import_data = [];
            // First add page tree if any
            if (is_array($this->dat['header']['pagetree'])) {
                $pagesFromTree = $this->flatInversePageTree($this->dat['header']['pagetree']);
                foreach ($pagesFromTree as $uid) {
                    $thisRec = $this->dat['header']['records']['pages'][$uid];
                    // PID: Set the main $pid, unless a NEW-id is found
                    $setPid = isset($this->import_newId_pids[$thisRec['pid']]) ? $this->import_newId_pids[$thisRec['pid']] : $pid;
                    $this->addSingle('pages', $uid, $setPid);
                    unset($pageRecords[$uid]);
                }
            }
            // Then add all remaining pages not in tree on root level:
            if (!empty($pageRecords)) {
                $remainingPageUids = array_keys($pageRecords);
                foreach ($remainingPageUids as $pUid) {
                    $this->addSingle('pages', $pUid, $pid);
                }
            }
            // Now write to database:
            $tce = $this->getNewTCE();
            $tce->isImporting = true;
            $this->callHook('before_writeRecordsPages', [
                'tce' => &$tce,
                'data' => &$this->import_data
            ]);
            $tce->suggestedInsertUids = $this->suggestedInsertUids;
            $tce->start($this->import_data, []);
            $tce->process_datamap();
            $this->callHook('after_writeRecordsPages', [
                'tce' => &$tce
            ]);
            // post-processing: Registering new ids (end all tcemain sessions with this)
            $this->addToMapId($tce->substNEWwithIDs);
            // In case of an update, order pages from the page tree correctly:
            if ($this->update && is_array($this->dat['header']['pagetree'])) {
                $this->writeRecords_pages_order();
            }
        }
    }

    /**
     * Organize all updated pages in page tree so they are related like in the import file
     * Only used for updates and when $this->dat['header']['pagetree'] is an array.
     *
     * @return void
     * @access private
     * @see writeRecords_pages(), writeRecords_records_order()
     */
    public function writeRecords_pages_order()
    {
        $cmd_data = [];
        // Get uid-pid relations and traverse them in order to map to possible new IDs
        $pidsFromTree = $this->flatInversePageTree_pid($this->dat['header']['pagetree']);
        foreach ($pidsFromTree as $origPid => $newPid) {
            if ($newPid >= 0 && $this->dontIgnorePid('pages', $origPid)) {
                // If the page had a new id (because it was created) use that instead!
                if (substr($this->import_newId_pids[$origPid], 0, 3) === 'NEW') {
                    if ($this->import_mapId['pages'][$origPid]) {
                        $mappedPid = $this->import_mapId['pages'][$origPid];
                        $cmd_data['pages'][$mappedPid]['move'] = $newPid;
                    }
                } else {
                    $cmd_data['pages'][$origPid]['move'] = $newPid;
                }
            }
        }
        // Execute the move commands if any:
        if (!empty($cmd_data)) {
            $tce = $this->getNewTCE();
            $this->callHook('before_writeRecordsPagesOrder', [
                'tce' => &$tce,
                'data' => &$cmd_data
            ]);
            $tce->start([], $cmd_data);
            $tce->process_cmdmap();
            $this->callHook('after_writeRecordsPagesOrder', [
                'tce' => &$tce
            ]);
        }
    }

    /**
     * Recursively flattening the idH array, setting PIDs as values
     *
     * @param array $idH Page uid hierarchy
     * @param array $a Accumulation array of pages (internal, don't set from outside)
     * @param int $pid PID value (internal)
     * @return array Array with uid-pid pairs for all pages in the page tree.
     * @see ImportExport::flatInversePageTree()
     */
    public function flatInversePageTree_pid($idH, $a = [], $pid = -1)
    {
        if (is_array($idH)) {
            $idH = array_reverse($idH);
            foreach ($idH as $v) {
                $a[$v['uid']] = $pid;
                if (is_array($v['subrow'])) {
                    $a = $this->flatInversePageTree_pid($v['subrow'], $a, $v['uid']);
                }
            }
        }
        return $a;
    }

    /**
     * Write all database records except pages (writtein in writeRecords_pages())
     *
     * @param int $pid Page id in which to import
     * @return void
     * @see writeRecords_pages()
     */
    public function writeRecords_records($pid)
    {
        // Write the rest of the records
        $this->import_data = [];
        if (is_array($this->dat['header']['records'])) {
            foreach ($this->dat['header']['records'] as $table => $recs) {
                $this->addGeneralErrorsByTable($table);
                if ($table != 'pages') {
                    foreach ($recs as $uid => $thisRec) {
                        // PID: Set the main $pid, unless a NEW-id is found
                        $setPid = isset($this->import_mapId['pages'][$thisRec['pid']])
                            ? (int)$this->import_mapId['pages'][$thisRec['pid']]
                            : (int)$pid;
                        if (is_array($GLOBALS['TCA'][$table]) && isset($GLOBALS['TCA'][$table]['ctrl']['rootLevel'])) {
                            $rootLevelSetting = (int)$GLOBALS['TCA'][$table]['ctrl']['rootLevel'];
                            if ($rootLevelSetting === 1) {
                                $setPid = 0;
                            } elseif ($rootLevelSetting === 0 && $setPid === 0) {
                                $this->error('Error: Record type ' . $table . ' is not allowed on pid 0');
                                continue;
                            }
                        }
                        // Add record:
                        $this->addSingle($table, $uid, $setPid);
                    }
                }
            }
        } else {
            $this->error('Error: No records defined in internal data array.');
        }
        // Now write to database:
        $tce = $this->getNewTCE();
        $this->callHook('before_writeRecordsRecords', [
            'tce' => &$tce,
            'data' => &$this->import_data
        ]);
        $tce->suggestedInsertUids = $this->suggestedInsertUids;
        // Because all records are being submitted in their correct order with positive pid numbers - and so we should reverse submission order internally.
        $tce->reverseOrder = 1;
        $tce->isImporting = true;
        $tce->start($this->import_data, []);
        $tce->process_datamap();
        $this->callHook('after_writeRecordsRecords', [
            'tce' => &$tce
        ]);
        // post-processing: Removing files and registering new ids (end all tcemain sessions with this)
        $this->addToMapId($tce->substNEWwithIDs);
        // In case of an update, order pages from the page tree correctly:
        if ($this->update) {
            $this->writeRecords_records_order($pid);
        }
    }

    /**
     * Organize all updated record to their new positions.
     * Only used for updates
     *
     * @param int $mainPid Main PID into which we import.
     * @return void
     * @access private
     * @see writeRecords_records(), writeRecords_pages_order()
     */
    public function writeRecords_records_order($mainPid)
    {
        $cmd_data = [];
        if (is_array($this->dat['header']['pagetree'])) {
            $pagesFromTree = $this->flatInversePageTree($this->dat['header']['pagetree']);
        } else {
            $pagesFromTree = [];
        }
        if (is_array($this->dat['header']['pid_lookup'])) {
            foreach ($this->dat['header']['pid_lookup'] as $pid => $recList) {
                $newPid = isset($this->import_mapId['pages'][$pid]) ? $this->import_mapId['pages'][$pid] : $mainPid;
                if (MathUtility::canBeInterpretedAsInteger($newPid)) {
                    foreach ($recList as $tableName => $uidList) {
                        // If $mainPid===$newPid then we are on root level and we can consider to move pages as well!
                        // (they will not be in the page tree!)
                        if (($tableName != 'pages' || !$pagesFromTree[$pid]) && is_array($uidList)) {
                            $uidList = array_reverse(array_keys($uidList));
                            foreach ($uidList as $uid) {
                                if ($this->dontIgnorePid($tableName, $uid)) {
                                    $cmd_data[$tableName][$uid]['move'] = $newPid;
                                } else {
                                }
                            }
                        }
                    }
                }
            }
        }
        // Execute the move commands if any:
        if (!empty($cmd_data)) {
            $tce = $this->getNewTCE();
            $this->callHook('before_writeRecordsRecordsOrder', [
                'tce' => &$tce,
                'data' => &$cmd_data
            ]);
            $tce->start([], $cmd_data);
            $tce->process_cmdmap();
            $this->callHook('after_writeRecordsRecordsOrder', [
                'tce' => &$tce
            ]);
        }
    }

    /**
     * Adds a single record to the $importData array. Also copies files to tempfolder.
     * However all File/DB-references and flexform field contents are set to blank for now!
     * That is done with setRelations() later
     *
     * @param string $table Table name (from import memory)
     * @param int $uid Record UID (from import memory)
     * @param int $pid Page id
     * @return void
     * @see writeRecords()
     */
    public function addSingle($table, $uid, $pid)
    {
        if ($this->import_mode[$table . ':' . $uid] === 'exclude') {
            return;
        }
        $record = $this->dat['records'][$table . ':' . $uid]['data'];
        if (is_array($record)) {
            if ($this->update && $this->doesRecordExist($table, $uid) && $this->import_mode[$table . ':' . $uid] !== 'as_new') {
                $ID = $uid;
            } elseif ($table === 'sys_file_metadata' && $record['sys_language_uid'] == '0' && $this->import_mapId['sys_file'][$record['file']]) {
                // on adding sys_file records the belonging sys_file_metadata record was also created
                // if there is one the record need to be overwritten instead of creating a new one.
                $recordInDatabase = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
                    'uid',
                    'sys_file_metadata',
                    'file = ' . $this->import_mapId['sys_file'][$record['file']] . ' AND sys_language_uid = 0 AND pid = 0'
                );
                // if no record could be found, $this->import_mapId['sys_file'][$record['file']] is pointing
                // to a file, that was already there, thus a new metadata record should be created
                if (is_array($recordInDatabase)) {
                    $this->import_mapId['sys_file_metadata'][$record['uid']] = $recordInDatabase['uid'];
                    $ID = $recordInDatabase['uid'];
                } else {
                    $ID = StringUtility::getUniqueId('NEW');
                }
            } else {
                $ID = StringUtility::getUniqueId('NEW');
            }
            $this->import_newId[$table . ':' . $ID] = ['table' => $table, 'uid' => $uid];
            if ($table == 'pages') {
                $this->import_newId_pids[$uid] = $ID;
            }
            // Set main record data:
            $this->import_data[$table][$ID] = $record;
            $this->import_data[$table][$ID]['tx_impexp_origuid'] = $this->import_data[$table][$ID]['uid'];
            // Reset permission data:
            if ($table === 'pages') {
                // Have to reset the user/group IDs so pages are owned by importing user. Otherwise strange things may happen for non-admins!
                unset($this->import_data[$table][$ID]['perms_userid']);
                unset($this->import_data[$table][$ID]['perms_groupid']);
            }
            // PID and UID:
            unset($this->import_data[$table][$ID]['uid']);
            // Updates:
            if (MathUtility::canBeInterpretedAsInteger($ID)) {
                unset($this->import_data[$table][$ID]['pid']);
            } else {
                // Inserts:
                $this->import_data[$table][$ID]['pid'] = $pid;
                if (($this->import_mode[$table . ':' . $uid] === 'force_uid' && $this->update || $this->force_all_UIDS) && $this->getBackendUser()->isAdmin()) {
                    $this->import_data[$table][$ID]['uid'] = $uid;
                    $this->suggestedInsertUids[$table . ':' . $uid] = 'DELETE';
                }
            }
            // Setting db/file blank:
            foreach ($this->dat['records'][$table . ':' . $uid]['rels'] as $field => $config) {
                switch ((string)$config['type']) {
                    case 'db':

                    case 'file':
                        // Fixed later in ->setRelations() [because we need to know ALL newly created IDs before we can map relations!]
                        // In the meantime we set NO values for relations.
                        //
                        // BUT for field uid_local of table sys_file_reference the relation MUST not be cleared here,
                        // because the value is already the uid of the right imported sys_file record.
                        // @see fixUidLocalInSysFileReferenceRecords()
                        // If it's empty or a uid to another record the FileExtensionFilter will throw an exception or
                        // delete the reference record if the file extension of the related record doesn't match.
                        if ($table !== 'sys_file_reference' && $field !== 'uid_local') {
                            $this->import_data[$table][$ID][$field] = '';
                        }
                        break;
                    case 'flex':
                        // Fixed later in setFlexFormRelations()
                        // In the meantime we set NO value for flexforms - this is mainly because file references
                        // inside will not be processed properly; In fact references will point to no file
                        // or existing files (in which case there will be double-references which is a big problem of course!)
                        $this->import_data[$table][$ID][$field] = '';
                        break;
                }
            }
        } elseif ($table . ':' . $uid != 'pages:0') {
            // On root level we don't want this error message.
            $this->error('Error: no record was found in data array!');
        }
    }

    /**
     * Registers the substNEWids in memory.
     *
     * @param array $substNEWwithIDs From tcemain to be merged into internal mapping variable in this object
     * @return void
     * @see writeRecords()
     */
    public function addToMapId($substNEWwithIDs)
    {
        foreach ($this->import_data as $table => $recs) {
            foreach ($recs as $id => $value) {
                $old_uid = $this->import_newId[$table . ':' . $id]['uid'];
                if (isset($substNEWwithIDs[$id])) {
                    $this->import_mapId[$table][$old_uid] = $substNEWwithIDs[$id];
                } elseif ($this->update) {
                    // Map same ID to same ID....
                    $this->import_mapId[$table][$old_uid] = $id;
                } else {
                    // if $this->import_mapId contains already the right mapping, skip the error msg.
                    // See special handling of sys_file_metadata in addSingle() => nothing to do
                    if (!($table === 'sys_file_metadata' && isset($this->import_mapId[$table][$old_uid]) && $this->import_mapId[$table][$old_uid] == $id)) {
                        $this->error('Possible error: ' . $table . ':' . $old_uid . ' had no new id assigned to it. This indicates that the record was not added to database during import. Please check changelog!');
                    }
                }
            }
        }
    }

    /**
     * Returns a new $TCE object
     *
     * @return DataHandler $TCE object
     */
    public function getNewTCE()
    {
        $tce = GeneralUtility::makeInstance(DataHandler::class);
        $tce->stripslashes_values = false;
        $tce->dontProcessTransformations = 1;
        $tce->enableLogging = $this->enableLogging;
        $tce->alternativeFileName = $this->alternativeFileName;
        $tce->alternativeFilePath = $this->alternativeFilePath;
        return $tce;
    }

    /**
     * Cleaning up all the temporary files stored in typo3temp/ folder
     *
     * @return void
     */
    public function unlinkTempFiles()
    {
        foreach ($this->unlinkFiles as $fileName) {
            if (GeneralUtility::isFirstPartOfStr($fileName, PATH_site . 'typo3temp/')) {
                GeneralUtility::unlink_tempfile($fileName);
                clearstatcache();
                if (is_file($fileName)) {
                    $this->error('Error: ' . $fileName . ' was NOT unlinked as it should have been!');
                }
            } else {
                $this->error('Error: ' . $fileName . ' was not in temp-path. Not removed!');
            }
        }
        $this->unlinkFiles = [];
    }

    /***************************
     * Import / Relations setting
     ***************************/

    /**
     * At the end of the import process all file and DB relations should be set properly (that is relations
     * to imported records are all re-created so imported records are correctly related again)
     * Relations in flexform fields are processed in setFlexFormRelations() after this function
     *
     * @return void
     * @see setFlexFormRelations()
     */
    public function setRelations()
    {
        $updateData = [];
        // import_newId contains a register of all records that was in the import memorys "records" key
        foreach ($this->import_newId as $nId => $dat) {
            $table = $dat['table'];
            $uid = $dat['uid'];
            // original UID - NOT the new one!
            // If the record has been written and received a new id, then proceed:
            if (is_array($this->import_mapId[$table]) && isset($this->import_mapId[$table][$uid])) {
                $thisNewUid = BackendUtility::wsMapId($table, $this->import_mapId[$table][$uid]);
                if (is_array($this->dat['records'][$table . ':' . $uid]['rels'])) {
                    $thisNewPageUid = 0;
                    if ($this->legacyImport) {
                        if ($table != 'pages') {
                            $oldPid = $this->dat['records'][$table . ':' . $uid]['data']['pid'];
                            $thisNewPageUid = BackendUtility::wsMapId($table, $this->import_mapId['pages'][$oldPid]);
                        } else {
                            $thisNewPageUid = $thisNewUid;
                        }
                    }
                    // Traverse relation fields of each record
                    foreach ($this->dat['records'][$table . ':' . $uid]['rels'] as $field => $config) {
                        // uid_local of sys_file_reference needs no update because the correct reference uid was already written
                        // @see ImportExport::fixUidLocalInSysFileReferenceRecords()
                        if ($table === 'sys_file_reference' && $field === 'uid_local') {
                            continue;
                        }
                        switch ((string)$config['type']) {
                            case 'db':
                                if (is_array($config['itemArray']) && !empty($config['itemArray'])) {
                                    $itemConfig = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
                                    $valArray = $this->setRelations_db($config['itemArray'], $itemConfig);
                                    $updateData[$table][$thisNewUid][$field] = implode(',', $valArray);
                                }
                                break;
                            case 'file':
                                if (is_array($config['newValueFiles']) && !empty($config['newValueFiles'])) {
                                    $valArr = [];
                                    foreach ($config['newValueFiles'] as $fI) {
                                        $valArr[] = $this->import_addFileNameToBeCopied($fI);
                                    }
                                    if ($this->legacyImport && $this->legacyImportFolder === null && isset($this->legacyImportMigrationTables[$table][$field])) {
                                        // Do nothing - the legacy import folder is missing
                                    } elseif ($this->legacyImport && $this->legacyImportFolder !== null && isset($this->legacyImportMigrationTables[$table][$field])) {
                                        $refIds = [];
                                        foreach ($valArr as $tempFile) {
                                            $fileName = $this->alternativeFileName[$tempFile];
                                            $fileObject = null;

                                            try {
                                                // check, if there is alreay the same file in the folder
                                                if ($this->legacyImportFolder->hasFile($fileName)) {
                                                    $fileStorage = $this->legacyImportFolder->getStorage();
                                                    $file = $fileStorage->getFile($this->legacyImportFolder->getIdentifier() . $fileName);
                                                    if ($file->getSha1() === sha1_file($tempFile)) {
                                                        $fileObject = $file;
                                                    }
                                                }
                                            } catch (Exception $e) {
                                            }

                                            if ($fileObject === null) {
                                                try {
                                                    $fileObject = $this->legacyImportFolder->addFile($tempFile, $fileName, DuplicationBehavior::RENAME);
                                                } catch (Exception $e) {
                                                    $this->error('Error: no file could be added to the storage for file name' . $this->alternativeFileName[$tempFile]);
                                                }
                                            }
                                            if ($fileObject !== null) {
                                                $refId = StringUtility::getUniqueId('NEW');
                                                $refIds[] = $refId;
                                                $updateData['sys_file_reference'][$refId] = [
                                                    'uid_local' => $fileObject->getUid(),
                                                    'uid_foreign' => $thisNewUid, // uid of your content record
                                                    'tablenames' => $table,
                                                    'fieldname' => $field,
                                                    'pid' => $thisNewPageUid, // parent id of the parent page
                                                    'table_local' => 'sys_file',
                                                ];
                                            }
                                        }
                                        $updateData[$table][$thisNewUid][$field] = implode(',', $refIds);
                                        if (!empty($this->legacyImportMigrationTables[$table][$field])) {
                                            $this->legacyImportMigrationRecords[$table][$thisNewUid][$field] = $refIds;
                                        }
                                    } else {
                                        $updateData[$table][$thisNewUid][$field] = implode(',', $valArr);
                                    }
                                }
                                break;
                        }
                    }
                } else {
                    $this->error('Error: no record was found in data array!');
                }
            } else {
                $this->error('Error: this records is NOT created it seems! (' . $table . ':' . $uid . ')');
            }
        }
        if (!empty($updateData)) {
            $tce = $this->getNewTCE();
            $tce->isImporting = true;
            $this->callHook('before_setRelation', [
                'tce' => &$tce,
                'data' => &$updateData
            ]);
            $tce->start($updateData, []);
            $tce->process_datamap();
            // Replace the temporary "NEW" ids with the final ones.
            foreach ($this->legacyImportMigrationRecords as $table => $records) {
                foreach ($records as $uid => $fields) {
                    foreach ($fields as $field => $referenceIds) {
                        foreach ($referenceIds as $key => $referenceId) {
                            $this->legacyImportMigrationRecords[$table][$uid][$field][$key] = $tce->substNEWwithIDs[$referenceId];
                        }
                    }
                }
            }
            $this->callHook('after_setRelations', [
                'tce' => &$tce
            ]);
        }
    }

    /**
     * Maps relations for database
     *
     * @param array $itemArray Array of item sets (table/uid) from a dbAnalysis object
     * @param array $itemConfig Array of TCA config of the field the relation to be set on
     * @return array Array with values [table]_[uid] or [uid] for field of type group / internal_type file_reference. These values have the regular tcemain-input group/select type which means they will automatically be processed into a uid-list or MM relations.
     */
    public function setRelations_db($itemArray, $itemConfig)
    {
        $valArray = [];
        foreach ($itemArray as $relDat) {
            if (is_array($this->import_mapId[$relDat['table']]) && isset($this->import_mapId[$relDat['table']][$relDat['id']])) {
                // Since non FAL file relation type group internal_type file_reference are handled as reference to
                // sys_file records Datahandler requires the value as uid of the the related sys_file record only
                if ($itemConfig['type'] === 'group' && $itemConfig['internal_type'] === 'file_reference') {
                    $value = $this->import_mapId[$relDat['table']][$relDat['id']];
                } elseif ($itemConfig['type'] === 'input' && isset($itemConfig['wizards']['link'])) {
                    // If an input field has a relation to a sys_file record this need to be converted back to
                    // the public path. But use getPublicUrl here, because could normally only be a local file path.
                    $fileUid = $this->import_mapId[$relDat['table']][$relDat['id']];
                    // Fallback value
                    $value = 'file:' . $fileUid;
                    try {
                        $file = ResourceFactory::getInstance()->retrieveFileOrFolderObject($fileUid);
                    } catch (\Exception $e) {
                        $file = null;
                    }
                    if ($file instanceof FileInterface) {
                        $value = $file->getPublicUrl();
                    }
                } else {
                    $value = $relDat['table'] . '_' . $this->import_mapId[$relDat['table']][$relDat['id']];
                }
                $valArray[] = $value;
            } elseif ($this->isTableStatic($relDat['table']) || $this->isExcluded($relDat['table'], $relDat['id']) || $relDat['id'] < 0) {
                // Checking for less than zero because some select types could contain negative values,
                // eg. fe_groups (-1, -2) and sys_language (-1 = ALL languages). This must be handled on both export and import.
                $valArray[] = $relDat['table'] . '_' . $relDat['id'];
            } else {
                $this->error('Lost relation: ' . $relDat['table'] . ':' . $relDat['id']);
            }
        }
        return $valArray;
    }

    /**
     * Writes the file from import array to temp dir and returns the filename of it.
     *
     * @param array $fI File information with three keys: "filename" = filename without path, "ID_absFile" = absolute filepath to the file (including the filename), "ID" = md5 hash of "ID_absFile
     * @return string|NULL Absolute filename of the temporary filename of the file. In ->alternativeFileName the original name is set.
     */
    public function import_addFileNameToBeCopied($fI)
    {
        if (is_array($this->dat['files'][$fI['ID']])) {
            $tmpFile = null;
            // check if there is the right file already in the local folder
            if ($this->filesPathForImport !== null) {
                if (is_file($this->filesPathForImport . '/' . $this->dat['files'][$fI['ID']]['content_md5']) &&
                    md5_file($this->filesPathForImport . '/' . $this->dat['files'][$fI['ID']]['content_md5']) === $this->dat['files'][$fI['ID']]['content_md5']) {
                    $tmpFile = $this->filesPathForImport . '/' . $this->dat['files'][$fI['ID']]['content_md5'];
                }
            }
            if ($tmpFile === null) {
                $tmpFile = GeneralUtility::tempnam('import_temp_');
                GeneralUtility::writeFile($tmpFile, $this->dat['files'][$fI['ID']]['content']);
            }
            clearstatcache();
            if (@is_file($tmpFile)) {
                $this->unlinkFiles[] = $tmpFile;
                if (filesize($tmpFile) == $this->dat['files'][$fI['ID']]['filesize']) {
                    $this->alternativeFileName[$tmpFile] = $fI['filename'];
                    $this->alternativeFilePath[$tmpFile] = $this->dat['files'][$fI['ID']]['relFileRef'];
                    return $tmpFile;
                } else {
                    $this->error('Error: temporary file ' . $tmpFile . ' had a size (' . filesize($tmpFile) . ') different from the original (' . $this->dat['files'][$fI['ID']]['filesize'] . ')');
                }
            } else {
                $this->error('Error: temporary file ' . $tmpFile . ' was not written as it should have been!');
            }
        } else {
            $this->error('Error: No file found for ID ' . $fI['ID']);
        }
        return null;
    }

    /**
     * After all DB relations has been set in the end of the import (see setRelations()) then it is time to correct all relations inside of FlexForm fields.
     * The reason for doing this after is that the setting of relations may affect (quite often!) which data structure is used for the flexforms field!
     *
     * @return void
     * @see setRelations()
     */
    public function setFlexFormRelations()
    {
        $updateData = [];
        // import_newId contains a register of all records that was in the import memorys "records" key
        foreach ($this->import_newId as $nId => $dat) {
            $table = $dat['table'];
            $uid = $dat['uid'];
            // original UID - NOT the new one!
            // If the record has been written and received a new id, then proceed:
            if (!isset($this->import_mapId[$table][$uid])) {
                $this->error('Error: this records is NOT created it seems! (' . $table . ':' . $uid . ')');
                continue;
            }

            if (!is_array($this->dat['records'][$table . ':' . $uid]['rels'])) {
                $this->error('Error: no record was found in data array!');
                continue;
            }
            $thisNewUid = BackendUtility::wsMapId($table, $this->import_mapId[$table][$uid]);
            // Traverse relation fields of each record
            foreach ($this->dat['records'][$table . ':' . $uid]['rels'] as $field => $config) {
                switch ((string)$config['type']) {
                    case 'flex':
                        // Get XML content and set as default value (string, non-processed):
                        $updateData[$table][$thisNewUid][$field] = $this->dat['records'][$table . ':' . $uid]['data'][$field];
                        // If there has been registered relations inside the flex form field, run processing on the content:
                        if (!empty($config['flexFormRels']['db']) || !empty($config['flexFormRels']['file'])) {
                            $origRecordRow = BackendUtility::getRecord($table, $thisNewUid, '*');
                            // This will fetch the new row for the element (which should be updated with any references to data structures etc.)
                            $conf = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
                            if (is_array($origRecordRow) && is_array($conf) && $conf['type'] === 'flex') {
                                // Get current data structure and value array:
                                $dataStructArray = BackendUtility::getFlexFormDS($conf, $origRecordRow, $table, $field);
                                $currentValueArray = GeneralUtility::xml2array($updateData[$table][$thisNewUid][$field]);
                                // Do recursive processing of the XML data:
                                $iteratorObj = GeneralUtility::makeInstance(DataHandler::class);
                                $iteratorObj->callBackObj = $this;
                                $currentValueArray['data'] = $iteratorObj->checkValue_flex_procInData(
                                    $currentValueArray['data'],
                                    [],
                                    [],
                                    $dataStructArray,
                                    [$table, $thisNewUid, $field, $config],
                                    'remapListedDBRecords_flexFormCallBack'
                                );
                                // The return value is set as an array which means it will be processed by tcemain for file and DB references!
                                if (is_array($currentValueArray['data'])) {
                                    $updateData[$table][$thisNewUid][$field] = $currentValueArray;
                                }
                            }
                        }
                        break;
                }
            }
        }
        if (!empty($updateData)) {
            $tce = $this->getNewTCE();
            $tce->isImporting = true;
            $this->callHook('before_setFlexFormRelations', [
                'tce' => &$tce,
                'data' => &$updateData
            ]);
            $tce->start($updateData, []);
            $tce->process_datamap();
            $this->callHook('after_setFlexFormRelations', [
                'tce' => &$tce
            ]);
        }
    }

    /**
     * Callback function for traversing the FlexForm structure in relation to remapping database relations
     *
     * @param array $pParams Set of parameters in numeric array: table, uid, field
     * @param array $dsConf TCA config for field (from Data Structure of course)
     * @param string $dataValue Field value (from FlexForm XML)
     * @param string $dataValue_ext1 Not used
     * @param string $dataValue_ext2 Not used
     * @param string $path Path of where the data structure of the element is found
     * @return array Array where the "value" key carries the value.
     * @see setFlexFormRelations()
     */
    public function remapListedDBRecords_flexFormCallBack($pParams, $dsConf, $dataValue, $dataValue_ext1, $dataValue_ext2, $path)
    {
        // Extract parameters:
        list(, , , $config) = $pParams;
        // In case the $path is used as index without a trailing slash we will remove that
        if (!is_array($config['flexFormRels']['db'][$path]) && is_array($config['flexFormRels']['db'][rtrim($path, '/')])) {
            $path = rtrim($path, '/');
        }
        if (is_array($config['flexFormRels']['db'][$path])) {
            $valArray = $this->setRelations_db($config['flexFormRels']['db'][$path], $dsConf);
            $dataValue = implode(',', $valArray);
        }
        if (is_array($config['flexFormRels']['file'][$path])) {
            $valArr = [];
            foreach ($config['flexFormRels']['file'][$path] as $fI) {
                $valArr[] = $this->import_addFileNameToBeCopied($fI);
            }
            $dataValue = implode(',', $valArr);
        }
        return ['value' => $dataValue];
    }

    /**************************
     * Import / Soft References
     *************************/

    /**
     * Processing of soft references
     *
     * @return void
     */
    public function processSoftReferences()
    {
        // Initialize:
        $inData = [];
        // Traverse records:
        if (is_array($this->dat['header']['records'])) {
            foreach ($this->dat['header']['records'] as $table => $recs) {
                foreach ($recs as $uid => $thisRec) {
                    // If there are soft references defined, traverse those:
                    if (isset($GLOBALS['TCA'][$table]) && is_array($thisRec['softrefs'])) {
                        // First traversal is to collect softref configuration and split them up based on fields.
                        // This could probably also have been done with the "records" key instead of the header.
                        $fieldsIndex = [];
                        foreach ($thisRec['softrefs'] as $softrefDef) {
                            // If a substitution token is set:
                            if ($softrefDef['field'] && is_array($softrefDef['subst']) && $softrefDef['subst']['tokenID']) {
                                $fieldsIndex[$softrefDef['field']][$softrefDef['subst']['tokenID']] = $softrefDef;
                            }
                        }
                        // The new id:
                        $thisNewUid = BackendUtility::wsMapId($table, $this->import_mapId[$table][$uid]);
                        // Now, if there are any fields that require substitution to be done, lets go for that:
                        foreach ($fieldsIndex as $field => $softRefCfgs) {
                            if (is_array($GLOBALS['TCA'][$table]['columns'][$field])) {
                                $conf = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
                                if ($conf['type'] === 'flex') {
                                    // This will fetch the new row for the element (which should be updated with any references to data structures etc.)
                                    $origRecordRow = BackendUtility::getRecord($table, $thisNewUid, '*');
                                    if (is_array($origRecordRow)) {
                                        // Get current data structure and value array:
                                        $dataStructArray = BackendUtility::getFlexFormDS($conf, $origRecordRow, $table, $field);
                                        $currentValueArray = GeneralUtility::xml2array($origRecordRow[$field]);
                                        // Do recursive processing of the XML data:
                                        /** @var $iteratorObj DataHandler */
                                        $iteratorObj = GeneralUtility::makeInstance(DataHandler::class);
                                        $iteratorObj->callBackObj = $this;
                                        $currentValueArray['data'] = $iteratorObj->checkValue_flex_procInData($currentValueArray['data'], [], [], $dataStructArray, [$table, $uid, $field, $softRefCfgs], 'processSoftReferences_flexFormCallBack');
                                        // The return value is set as an array which means it will be processed by tcemain for file and DB references!
                                        if (is_array($currentValueArray['data'])) {
                                            $inData[$table][$thisNewUid][$field] = $currentValueArray;
                                        }
                                    }
                                } else {
                                    // Get tokenizedContent string and proceed only if that is not blank:
                                    $tokenizedContent = $this->dat['records'][$table . ':' . $uid]['rels'][$field]['softrefs']['tokenizedContent'];
                                    if (strlen($tokenizedContent) && is_array($softRefCfgs)) {
                                        $inData[$table][$thisNewUid][$field] = $this->processSoftReferences_substTokens($tokenizedContent, $softRefCfgs, $table, $uid);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        // Now write to database:
        $tce = $this->getNewTCE();
        $tce->isImporting = true;
        $this->callHook('before_processSoftReferences', [
            'tce' => $tce,
            'data' => &$inData
        ]);
        $tce->enableLogging = true;
        $tce->start($inData, []);
        $tce->process_datamap();
        $this->callHook('after_processSoftReferences', [
            'tce' => $tce
        ]);
    }

    /**
     * Callback function for traversing the FlexForm structure in relation to remapping softreference relations
     *
     * @param array $pParams Set of parameters in numeric array: table, uid, field
     * @param array $dsConf TCA config for field (from Data Structure of course)
     * @param string $dataValue Field value (from FlexForm XML)
     * @param string $dataValue_ext1 Not used
     * @param string $dataValue_ext2 Not used
     * @param string $path Path of where the data structure where the element is found
     * @return array Array where the "value" key carries the value.
     * @see setFlexFormRelations()
     */
    public function processSoftReferences_flexFormCallBack($pParams, $dsConf, $dataValue, $dataValue_ext1, $dataValue_ext2, $path)
    {
        // Extract parameters:
        list($table, $origUid, $field, $softRefCfgs) = $pParams;
        if (is_array($softRefCfgs)) {
            // First, find all soft reference configurations for this structure path (they are listed flat in the header):
            $thisSoftRefCfgList = [];
            foreach ($softRefCfgs as $sK => $sV) {
                if ($sV['structurePath'] === $path) {
                    $thisSoftRefCfgList[$sK] = $sV;
                }
            }
            // If any was found, do processing:
            if (!empty($thisSoftRefCfgList)) {
                // Get tokenizedContent string and proceed only if that is not blank:
                $tokenizedContent = $this->dat['records'][$table . ':' . $origUid]['rels'][$field]['flexFormRels']['softrefs'][$path]['tokenizedContent'];
                if (strlen($tokenizedContent)) {
                    $dataValue = $this->processSoftReferences_substTokens($tokenizedContent, $thisSoftRefCfgList, $table, $origUid);
                }
            }
        }
        // Return
        return ['value' => $dataValue];
    }

    /**
     * Substition of softreference tokens
     *
     * @param string $tokenizedContent Content of field with soft reference tokens in.
     * @param array $softRefCfgs Soft reference configurations
     * @param string $table Table for which the processing occurs
     * @param string $uid UID of record from table
     * @return string The input content with tokens substituted according to entries in softRefCfgs
     */
    public function processSoftReferences_substTokens($tokenizedContent, $softRefCfgs, $table, $uid)
    {
        // traverse each softref type for this field:
        foreach ($softRefCfgs as $cfg) {
            // Get token ID:
            $tokenID = $cfg['subst']['tokenID'];
            // Default is current token value:
            $insertValue = $cfg['subst']['tokenValue'];
            // Based on mode:
            switch ((string)$this->softrefCfg[$tokenID]['mode']) {
                case 'exclude':
                    // Exclude is a simple passthrough of the value
                    break;
                case 'editable':
                    // Editable always picks up the value from this input array:
                    $insertValue = $this->softrefInputValues[$tokenID];
                    break;
                default:
                    // Mapping IDs/creating files: Based on type, look up new value:
                    switch ((string)$cfg['subst']['type']) {
                        case 'file':
                            // Create / Overwrite file:
                            $insertValue = $this->processSoftReferences_saveFile($cfg['subst']['relFileName'], $cfg, $table, $uid);
                            break;
                        case 'db':
                        default:
                            // Trying to map database element if found in the mapID array:
                            list($tempTable, $tempUid) = explode(':', $cfg['subst']['recordRef']);
                            if (isset($this->import_mapId[$tempTable][$tempUid])) {
                                $insertValue = BackendUtility::wsMapId($tempTable, $this->import_mapId[$tempTable][$tempUid]);
                                // Look if reference is to a page and the original token value was NOT an integer - then we assume is was an alias and try to look up the new one!
                                if ($tempTable === 'pages' && !MathUtility::canBeInterpretedAsInteger($cfg['subst']['tokenValue'])) {
                                    $recWithUniqueValue = BackendUtility::getRecord($tempTable, $insertValue, 'alias');
                                    if ($recWithUniqueValue['alias']) {
                                        $insertValue = $recWithUniqueValue['alias'];
                                    }
                                } elseif (strpos($cfg['subst']['tokenValue'], ':') !== false) {
                                    list($tokenKey) = explode(':', $cfg['subst']['tokenValue']);
                                    $insertValue = $tokenKey . ':' . $insertValue;
                                }
                            }
                    }
            }
            // Finally, swap the soft reference token in tokenized content with the insert value:
            $tokenizedContent = str_replace('{softref:' . $tokenID . '}', $insertValue, $tokenizedContent);
        }
        return $tokenizedContent;
    }

    /**
     * Process a soft reference file
     *
     * @param string $relFileName Old Relative filename
     * @param array $cfg soft reference configuration array
     * @param string $table Table for which the processing occurs
     * @param string $uid UID of record from table
     * @return string New relative filename (value to insert instead of the softref token)
     */
    public function processSoftReferences_saveFile($relFileName, $cfg, $table, $uid)
    {
        if ($fileHeaderInfo = $this->dat['header']['files'][$cfg['file_ID']]) {
            // Initialize; Get directory prefix for file and find possible RTE filename
            $dirPrefix = PathUtility::dirname($relFileName) . '/';
            $rteOrigName = $this->getRTEoriginalFilename(PathUtility::basename($relFileName));
            // If filename looks like an RTE file, and the directory is in "uploads/", then process as a RTE file!
            if ($rteOrigName && GeneralUtility::isFirstPartOfStr($dirPrefix, 'uploads/')) {
                // RTE:
                // First, find unique RTE file name:
                if (@is_dir((PATH_site . $dirPrefix))) {
                    // From the "original" RTE filename, produce a new "original" destination filename which is unused.
                    // Even if updated, the image should be unique. Currently the problem with this is that it leaves a lot of unused RTE images...
                    $fileProcObj = $this->getFileProcObj();
                    $origDestName = $fileProcObj->getUniqueName($rteOrigName, PATH_site . $dirPrefix);
                    // Create copy file name:
                    $pI = pathinfo($relFileName);
                    $copyDestName = PathUtility::dirname($origDestName) . '/RTEmagicC_' . substr(PathUtility::basename($origDestName), 10) . '.' . $pI['extension'];
                    if (
                        !@is_file($copyDestName) && !@is_file($origDestName)
                        && $origDestName === GeneralUtility::getFileAbsFileName($origDestName)
                        && $copyDestName === GeneralUtility::getFileAbsFileName($copyDestName)
                    ) {
                        if ($this->dat['header']['files'][$fileHeaderInfo['RTE_ORIG_ID']]) {
                            if ($this->legacyImport) {
                                $fileName = PathUtility::basename($copyDestName);
                                $this->writeSysFileResourceForLegacyImport($fileName, $cfg['file_ID']);
                                $relFileName = $this->filePathMap[$cfg['file_ID']] . '" data-htmlarea-file-uid="' . $fileName . '" data-htmlarea-file-table="sys_file';
                                // Also save the original file
                                $originalFileName = PathUtility::basename($origDestName);
                                $this->writeSysFileResourceForLegacyImport($originalFileName, $fileHeaderInfo['RTE_ORIG_ID']);
                            } else {
                                // Write the copy and original RTE file to the respective filenames:
                                $this->writeFileVerify($copyDestName, $cfg['file_ID'], true);
                                $this->writeFileVerify($origDestName, $fileHeaderInfo['RTE_ORIG_ID'], true);
                                // Return the relative path of the copy file name:
                                return PathUtility::stripPathSitePrefix($copyDestName);
                            }
                        } else {
                            $this->error('ERROR: Could not find original file ID');
                        }
                    } else {
                        $this->error('ERROR: The destination filenames "' . $copyDestName . '" and "' . $origDestName . '" either existed or have non-valid names');
                    }
                } else {
                    $this->error('ERROR: "' . PATH_site . $dirPrefix . '" was not a directory, so could not process file "' . $relFileName . '"');
                }
            } elseif (GeneralUtility::isFirstPartOfStr($dirPrefix, $this->fileadminFolderName . '/')) {
                // File in fileadmin/ folder:
                // Create file (and possible resources)
                $newFileName = $this->processSoftReferences_saveFile_createRelFile($dirPrefix, PathUtility::basename($relFileName), $cfg['file_ID'], $table, $uid);
                if (strlen($newFileName)) {
                    $relFileName = $newFileName;
                } else {
                    $this->error('ERROR: No new file created for "' . $relFileName . '"');
                }
            } else {
                $this->error('ERROR: Sorry, cannot operate on non-RTE files which are outside the fileadmin folder.');
            }
        } else {
            $this->error('ERROR: Could not find file ID in header.');
        }
        // Return (new) filename relative to PATH_site:
        return $relFileName;
    }

    /**
     * Create file in directory and return the new (unique) filename
     *
     * @param string $origDirPrefix Directory prefix, relative, with trailing slash
     * @param string $fileName Filename (without path)
     * @param string $fileID File ID from import memory
     * @param string $table Table for which the processing occurs
     * @param string $uid UID of record from table
     * @return string|NULL New relative filename, if any
     */
    public function processSoftReferences_saveFile_createRelFile($origDirPrefix, $fileName, $fileID, $table, $uid)
    {
        // If the fileID map contains an entry for this fileID then just return the relative filename of that entry;
        // we don't want to write another unique filename for this one!
        if (isset($this->fileIDMap[$fileID])) {
            return PathUtility::stripPathSitePrefix($this->fileIDMap[$fileID]);
        }
        if ($this->legacyImport) {
            // set dirPrefix to fileadmin because the right target folder is set and checked for permissions later
            $dirPrefix = $this->fileadminFolderName . '/';
        } else {
            // Verify FileMount access to dir-prefix. Returns the best alternative relative path if any
            $dirPrefix = $this->verifyFolderAccess($origDirPrefix);
        }
        if ($dirPrefix && (!$this->update || $origDirPrefix === $dirPrefix) && $this->checkOrCreateDir($dirPrefix)) {
            $fileHeaderInfo = $this->dat['header']['files'][$fileID];
            $updMode = $this->update && $this->import_mapId[$table][$uid] === $uid && $this->import_mode[$table . ':' . $uid] !== 'as_new';
            // Create new name for file:
            // Must have same ID in map array (just for security, is not really needed) and NOT be set "as_new".

            // Write main file:
            if ($this->legacyImport) {
                $fileWritten = $this->writeSysFileResourceForLegacyImport($fileName, $fileID);
                if ($fileWritten) {
                    $newName = 'file:' . $fileName;
                    return $newName;
                    // no support for HTML/CSS file resources attached ATM - see below
                }
            } else {
                if ($updMode) {
                    $newName = PATH_site . $dirPrefix . $fileName;
                } else {
                    // Create unique filename:
                    $fileProcObj = $this->getFileProcObj();
                    $newName = $fileProcObj->getUniqueName($fileName, PATH_site . $dirPrefix);
                }
                if ($this->writeFileVerify($newName, $fileID)) {
                    // If the resource was an HTML/CSS file with resources attached, we will write those as well!
                    if (is_array($fileHeaderInfo['EXT_RES_ID'])) {
                        $tokenizedContent = $this->dat['files'][$fileID]['tokenizedContent'];
                        $tokenSubstituted = false;
                        $fileProcObj = $this->getFileProcObj();
                        if ($updMode) {
                            foreach ($fileHeaderInfo['EXT_RES_ID'] as $res_fileID) {
                                if ($this->dat['files'][$res_fileID]['filename']) {
                                    // Resolve original filename:
                                    $relResourceFileName = $this->dat['files'][$res_fileID]['parentRelFileName'];
                                    $absResourceFileName = GeneralUtility::resolveBackPath(PATH_site . $origDirPrefix . $relResourceFileName);
                                    $absResourceFileName = GeneralUtility::getFileAbsFileName($absResourceFileName);
                                    if ($absResourceFileName && GeneralUtility::isFirstPartOfStr($absResourceFileName, PATH_site . $this->fileadminFolderName . '/')) {
                                        $destDir = PathUtility::stripPathSitePrefix(PathUtility::dirname($absResourceFileName) . '/');
                                        if ($this->verifyFolderAccess($destDir, true) && $this->checkOrCreateDir($destDir)) {
                                            $this->writeFileVerify($absResourceFileName, $res_fileID);
                                        } else {
                                            $this->error('ERROR: Could not create file in directory "' . $destDir . '"');
                                        }
                                    } else {
                                        $this->error('ERROR: Could not resolve path for "' . $relResourceFileName . '"');
                                    }
                                    $tokenizedContent = str_replace('{EXT_RES_ID:' . $res_fileID . '}', $relResourceFileName, $tokenizedContent);
                                    $tokenSubstituted = true;
                                }
                            }
                        } else {
                            // Create the resouces directory name (filename without extension, suffixed "_FILES")
                            $resourceDir = PathUtility::dirname($newName) . '/' . preg_replace('/\\.[^.]*$/', '', PathUtility::basename($newName)) . '_FILES';
                            if (GeneralUtility::mkdir($resourceDir)) {
                                foreach ($fileHeaderInfo['EXT_RES_ID'] as $res_fileID) {
                                    if ($this->dat['files'][$res_fileID]['filename']) {
                                        $absResourceFileName = $fileProcObj->getUniqueName($this->dat['files'][$res_fileID]['filename'], $resourceDir);
                                        $relResourceFileName = substr($absResourceFileName, strlen(PathUtility::dirname($resourceDir)) + 1);
                                        $this->writeFileVerify($absResourceFileName, $res_fileID);
                                        $tokenizedContent = str_replace('{EXT_RES_ID:' . $res_fileID . '}', $relResourceFileName, $tokenizedContent);
                                        $tokenSubstituted = true;
                                    }
                                }
                            }
                        }
                        // If substitutions has been made, write the content to the file again:
                        if ($tokenSubstituted) {
                            GeneralUtility::writeFile($newName, $tokenizedContent);
                        }
                    }
                    return PathUtility::stripPathSitePrefix($newName);
                }
            }
        }
        return null;
    }

    /**
     * Writes a file from the import memory having $fileID to file name $fileName which must be an absolute path inside PATH_site
     *
     * @param string $fileName Absolute filename inside PATH_site to write to
     * @param string $fileID File ID from import memory
     * @param bool $bypassMountCheck Bypasses the checking against filemounts - only for RTE files!
     * @return bool Returns TRUE if it went well. Notice that the content of the file is read again, and md5 from import memory is validated.
     */
    public function writeFileVerify($fileName, $fileID, $bypassMountCheck = false)
    {
        $fileProcObj = $this->getFileProcObj();
        if (!$fileProcObj->actionPerms['addFile']) {
            $this->error('ERROR: You did not have sufficient permissions to write the file "' . $fileName . '"');
            return false;
        }
        // Just for security, check again. Should actually not be necessary.
        if (!$fileProcObj->checkPathAgainstMounts($fileName) && !$bypassMountCheck) {
            $this->error('ERROR: Filename "' . $fileName . '" was not allowed in destination path!');
            return false;
        }
        $fI = GeneralUtility::split_fileref($fileName);
        if (!$fileProcObj->checkIfAllowed($fI['fileext'], $fI['path'], $fI['file']) && (!$this->allowPHPScripts || !$this->getBackendUser()->isAdmin())) {
            $this->error('ERROR: Filename "' . $fileName . '" failed against extension check or deny-pattern!');
            return false;
        }
        if (!GeneralUtility::getFileAbsFileName($fileName)) {
            $this->error('ERROR: Filename "' . $fileName . '" was not a valid relative file path!');
            return false;
        }
        if (!$this->dat['files'][$fileID]) {
            $this->error('ERROR: File ID "' . $fileID . '" could not be found');
            return false;
        }
        GeneralUtility::writeFile($fileName, $this->dat['files'][$fileID]['content']);
        $this->fileIDMap[$fileID] = $fileName;
        if (md5(GeneralUtility::getUrl($fileName)) == $this->dat['files'][$fileID]['content_md5']) {
            return true;
        } else {
            $this->error('ERROR: File content "' . $fileName . '" was corrupted');
            return false;
        }
    }

    /**
     * Writes the file with the is $fileId to the legacy import folder. The file name will used from
     * argument $fileName and the file was successfully created or an identical file was already found,
     * $fileName will held the uid of the new created file record.
     *
     * @param string $fileName The file name for the new file. Value would be changed to the uid of the new created file record.
     * @param int $fileId The id of the file in data array
     * @return bool
     */
    protected function writeSysFileResourceForLegacyImport(&$fileName, $fileId)
    {
        if ($this->legacyImportFolder === null) {
            return false;
        }

        if (!isset($this->dat['files'][$fileId])) {
            $this->error('ERROR: File ID "' . $fileId . '" could not be found');
            return false;
        }

        $temporaryFile = $this->writeTemporaryFileFromData($fileId, 'files');
        if ($temporaryFile === null) {
            // error on writing the file. Error message was already added
            return false;
        }

        $importFolder = $this->legacyImportFolder;

        if (isset($this->dat['files'][$fileId]['relFileName'])) {
            $relativeFilePath = PathUtility::dirname($this->dat['files'][$fileId]['relFileName']);

            if (!$this->legacyImportFolder->hasFolder($relativeFilePath)) {
                $this->legacyImportFolder->createFolder($relativeFilePath);
            }
            $importFolder = $this->legacyImportFolder->getSubfolder($relativeFilePath);
        }

        $fileObject = null;

        try {
            // check, if there is alreay the same file in the folder
            if ($importFolder->hasFile($fileName)) {
                $fileStorage = $importFolder->getStorage();
                $file = $fileStorage->getFile($importFolder->getIdentifier() . $fileName);
                if ($file->getSha1() === sha1_file($temporaryFile)) {
                    $fileObject = $file;
                }
            }
        } catch (Exception $e) {
        }

        if ($fileObject === null) {
            try {
                $fileObject = $importFolder->addFile($temporaryFile, $fileName, DuplicationBehavior::RENAME);
            } catch (Exception $e) {
                $this->error('Error: no file could be added to the storage for file name ' . $this->alternativeFileName[$temporaryFile]);
            }
        }

        if (md5_file(PATH_site . $fileObject->getPublicUrl()) == $this->dat['files'][$fileId]['content_md5']) {
            $fileName = $fileObject->getUid();
            $this->fileIDMap[$fileId] = $fileName;
            $this->filePathMap[$fileId] = $fileObject->getPublicUrl();
            return true;
        } else {
            $this->error('ERROR: File content "' . $this->dat['files'][$fileId]['relFileName'] . '" was corrupted');
        }

        return false;
    }

    /**
     * Migrate legacy import records
     *
     * @return void
     */
    protected function migrateLegacyImportRecords()
    {
        $updateData= [];

        foreach ($this->legacyImportMigrationRecords as $table => $records) {
            foreach ($records as $uid => $fields) {
                $row = BackendUtility::getRecord($table, $uid);
                if (empty($row)) {
                    continue;
                }

                foreach ($fields as $field => $referenceIds) {
                    $fieldConfiguration = $this->legacyImportMigrationTables[$table][$field];

                    if (isset($fieldConfiguration['titleTexts'])) {
                        $titleTextField = $fieldConfiguration['titleTexts'];
                        if (isset($row[$titleTextField]) && $row[$titleTextField] !== '') {
                            $titleTextContents = explode(LF, $row[$titleTextField]);
                            $updateData[$table][$uid][$titleTextField] = '';
                        }
                    }

                    if (isset($fieldConfiguration['alternativeTexts'])) {
                        $alternativeTextField = $fieldConfiguration['alternativeTexts'];
                        if (isset($row[$alternativeTextField]) && $row[$alternativeTextField] !== '') {
                            $alternativeTextContents = explode(LF, $row[$alternativeTextField]);
                            $updateData[$table][$uid][$alternativeTextField] = '';
                        }
                    }
                    if (isset($fieldConfiguration['description'])) {
                        $descriptionField = $fieldConfiguration['description'];
                        if ($row[$descriptionField] !== '') {
                            $descriptionContents = explode(LF, $row[$descriptionField]);
                            $updateData[$table][$uid][$descriptionField] = '';
                        }
                    }
                    if (isset($fieldConfiguration['links'])) {
                        $linkField = $fieldConfiguration['links'];
                        if ($row[$linkField] !== '') {
                            $linkContents = explode(LF, $row[$linkField]);
                            $updateData[$table][$uid][$linkField] = '';
                        }
                    }

                    foreach ($referenceIds as $key => $referenceId) {
                        if (isset($titleTextContents[$key])) {
                            $updateData['sys_file_reference'][$referenceId]['title'] = trim($titleTextContents[$key]);
                        }
                        if (isset($alternativeTextContents[$key])) {
                            $updateData['sys_file_reference'][$referenceId]['alternative'] = trim($alternativeTextContents[$key]);
                        }
                        if (isset($descriptionContents[$key])) {
                            $updateData['sys_file_reference'][$referenceId]['description'] = trim($descriptionContents[$key]);
                        }
                        if (isset($linkContents[$key])) {
                            $updateData['sys_file_reference'][$referenceId]['link'] = trim($linkContents[$key]);
                        }
                    }
                }
            }
        }

        // update
        $tce = $this->getNewTCE();
        $tce->isImporting = true;
        $tce->start($updateData, []);
        $tce->process_datamap();
    }

    /**
     * Returns TRUE if directory exists  and if it doesn't it will create directory and return TRUE if that succeeded.
     *
     * @param string $dirPrefix Directory to create. Having a trailing slash. Must be in fileadmin/. Relative to PATH_site
     * @return bool TRUE, if directory exists (was created)
     */
    public function checkOrCreateDir($dirPrefix)
    {
        // Split dir path and remove first directory (which should be "fileadmin")
        $filePathParts = explode('/', $dirPrefix);
        $firstDir = array_shift($filePathParts);
        if ($firstDir === $this->fileadminFolderName && GeneralUtility::getFileAbsFileName($dirPrefix)) {
            $pathAcc = '';
            foreach ($filePathParts as $dirname) {
                $pathAcc .= '/' . $dirname;
                if (strlen($dirname)) {
                    if (!@is_dir((PATH_site . $this->fileadminFolderName . $pathAcc))) {
                        if (!GeneralUtility::mkdir((PATH_site . $this->fileadminFolderName . $pathAcc))) {
                            $this->error('ERROR: Directory could not be created....B');
                            return false;
                        }
                    }
                } elseif ($dirPrefix === $this->fileadminFolderName . $pathAcc) {
                    return true;
                } else {
                    $this->error('ERROR: Directory could not be created....A');
                }
            }
        }
        return false;
    }

    /**************************
     * File Input
     *************************/

    /**
     * Loads the header section/all of the $filename into memory
     *
     * @param string $filename Filename, absolute
     * @param bool $all If set, all information is loaded (header, records and files). Otherwise the default is to read only the header information
     * @return bool TRUE if the operation went well
     */
    public function loadFile($filename, $all = false)
    {
        if (!@is_file($filename)) {
            $this->error('Filename not found: ' . $filename);
            return false;
        }
        $fI = pathinfo($filename);
        if (@is_dir($filename . '.files')) {
            if (GeneralUtility::isAllowedAbsPath($filename . '.files')) {
                // copy the folder lowlevel to typo3temp, because the files would be deleted after import
                $temporaryFolderName = $this->getTemporaryFolderName();
                GeneralUtility::copyDirectory($filename . '.files', $temporaryFolderName);
                $this->filesPathForImport = $temporaryFolderName;
            } else {
                $this->error('External import files for the given import source is currently not supported.');
            }
        }
        if (strtolower($fI['extension']) == 'xml') {
            // XML:
            $xmlContent = GeneralUtility::getUrl($filename);
            if (strlen($xmlContent)) {
                $this->dat = GeneralUtility::xml2array($xmlContent, '', true);
                if (is_array($this->dat)) {
                    if ($this->dat['_DOCUMENT_TAG'] === 'T3RecordDocument' && is_array($this->dat['header']) && is_array($this->dat['records'])) {
                        $this->loadInit();
                        return true;
                    } else {
                        $this->error('XML file did not contain proper XML for TYPO3 Import');
                    }
                } else {
                    $this->error('XML could not be parsed: ' . $this->dat);
                }
            } else {
                $this->error('Error opening file: ' . $filename);
            }
        } else {
            // T3D
            if ($fd = fopen($filename, 'rb')) {
                $this->dat['header'] = $this->getNextFilePart($fd, 1, 'header');
                if ($all) {
                    $this->dat['records'] = $this->getNextFilePart($fd, 1, 'records');
                    $this->dat['files'] = $this->getNextFilePart($fd, 1, 'files');
                    $this->dat['files_fal'] = $this->getNextFilePart($fd, 1, 'files_fal');
                }
                $this->loadInit();
                return true;
            } else {
                $this->error('Error opening file: ' . $filename);
            }
            fclose($fd);
        }
        return false;
    }

    /**
     * Returns the next content part form the fileresource (t3d), $fd
     *
     * @param resource $fd File pointer
     * @param bool $unserialize If set, the returned content is unserialized into an array, otherwise you get the raw string
     * @param string $name For error messages this indicates the section of the problem.
     * @return string|NULL Data string or NULL in case of an error
     * @access private
     * @see loadFile()
     */
    public function getNextFilePart($fd, $unserialize = false, $name = '')
    {
        $initStrLen = 32 + 1 + 1 + 1 + 10 + 1;
        // Getting header data
        $initStr = fread($fd, $initStrLen);
        if (empty($initStr)) {
            $this->error('File does not contain data for "' . $name . '"');
            return null;
        }
        $initStrDat = explode(':', $initStr);
        if (strstr($initStrDat[0], 'Warning')) {
            $this->error('File read error: Warning message in file. (' . $initStr . fgets($fd) . ')');
            return null;
        }
        if ((string)$initStrDat[3] !== '') {
            $this->error('File read error: InitString had a wrong length. (' . $name . ')');
            return null;
        }
        $datString = fread($fd, (int)$initStrDat[2]);
        fread($fd, 1);
        if (md5($datString) === $initStrDat[0]) {
            if ($initStrDat[1]) {
                if ($this->compress) {
                    $datString = gzuncompress($datString);
                } else {
                    $this->error('Content read error: This file requires decompression, but this server does not offer gzcompress()/gzuncompress() functions.');
                    return null;
                }
            }
            return $unserialize ? unserialize($datString) : $datString;
        } else {
            $this->error('MD5 check failed (' . $name . ')');
        }
        return null;
    }

    /**
     * Loads T3D file content into the $this->dat array
     * (This function can be used to test the output strings from ->compileMemoryToFileContent())
     *
     * @param string $filecontent File content
     * @return void
     */
    public function loadContent($filecontent)
    {
        $pointer = 0;
        $this->dat['header'] = $this->getNextContentPart($filecontent, $pointer, 1, 'header');
        $this->dat['records'] = $this->getNextContentPart($filecontent, $pointer, 1, 'records');
        $this->dat['files'] = $this->getNextContentPart($filecontent, $pointer, 1, 'files');
        $this->loadInit();
    }

    /**
     * Returns the next content part from the $filecontent
     *
     * @param string $filecontent File content string
     * @param int $pointer File pointer (where to read from)
     * @param bool $unserialize If set, the returned content is unserialized into an array, otherwise you get the raw string
     * @param string $name For error messages this indicates the section of the problem.
     * @return string|NULL Data string
     */
    public function getNextContentPart($filecontent, &$pointer, $unserialize = false, $name = '')
    {
        $initStrLen = 32 + 1 + 1 + 1 + 10 + 1;
        // getting header data
        $initStr = substr($filecontent, $pointer, $initStrLen);
        $pointer += $initStrLen;
        $initStrDat = explode(':', $initStr);
        if ((string)$initStrDat[3] !== '') {
            $this->error('Content read error: InitString had a wrong length. (' . $name . ')');
            return null;
        }
        $datString = substr($filecontent, $pointer, (int)$initStrDat[2]);
        $pointer += (int)$initStrDat[2] + 1;
        if (md5($datString) === $initStrDat[0]) {
            if ($initStrDat[1]) {
                if ($this->compress) {
                    $datString = gzuncompress($datString);
                    return $unserialize ? unserialize($datString) : $datString;
                } else {
                    $this->error('Content read error: This file requires decompression, but this server does not offer gzcompress()/gzuncompress() functions.');
                }
            }
        } else {
            $this->error('MD5 check failed (' . $name . ')');
        }
        return null;
    }

    /**
     * Setting up the object based on the recently loaded ->dat array
     *
     * @return void
     */
    public function loadInit()
    {
        $this->relStaticTables = (array)$this->dat['header']['relStaticTables'];
        $this->excludeMap = (array)$this->dat['header']['excludeMap'];
        $this->softrefCfg = (array)$this->dat['header']['softrefCfg'];
        $this->fixCharsets();
        if (
            isset($this->dat['header']['meta']['TYPO3_version'])
            && VersionNumberUtility::convertVersionNumberToInteger($this->dat['header']['meta']['TYPO3_version']) < 6000000
        ) {
            $this->legacyImport = true;
            $this->initializeLegacyImportFolder();
        }
    }

    /**
     * Fix charset of import memory if different from system charset
     *
     * @return void
     * @see loadInit()
     */
    public function fixCharsets()
    {
        $importCharset = $this->dat['header']['charset'];
        if ($importCharset) {
            if ($importCharset !== $this->getLanguageService()->charSet) {
                $this->error('CHARSET: Converting charset of input file (' . $importCharset . ') to the system charset (' . $this->getLanguageService()->charSet . ')');
                // Convert meta data:
                if (is_array($this->dat['header']['meta'])) {
                    $this->getLanguageService()->csConvObj->convArray($this->dat['header']['meta'], $importCharset, $this->getLanguageService()->charSet);
                }
                // Convert record headers:
                if (is_array($this->dat['header']['records'])) {
                    $this->getLanguageService()->csConvObj->convArray($this->dat['header']['records'], $importCharset, $this->getLanguageService()->charSet);
                }
                // Convert records themselves:
                if (is_array($this->dat['records'])) {
                    $this->getLanguageService()->csConvObj->convArray($this->dat['records'], $importCharset, $this->getLanguageService()->charSet);
                }
            }
        } else {
            $this->error('CHARSET: No charset found in import file!');
        }
    }
}
