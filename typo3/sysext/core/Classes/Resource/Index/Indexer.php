<?php
namespace TYPO3\CMS\Core\Resource\Index;

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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Resource\Exception\IllegalFileExtensionException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InvalidHashException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * The FAL Indexer
 */
class Indexer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var array
     */
    protected $filesToUpdate = [];

    /**
     * @var int[]
     */
    protected $identifiedFileUids = [];

    /**
     * @var ResourceStorage
     */
    protected $storage;

    /**
     * @var ExtractorInterface[]
     */
    protected $extractionServices;

    /**
     * @param ResourceStorage $storage
     */
    public function __construct(ResourceStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Create index entry
     *
     * @param string $identifier
     * @return File
     * @throws \InvalidArgumentException
     */
    public function createIndexEntry($identifier)
    {
        if (!isset($identifier) || !is_string($identifier) || $identifier === '') {
            throw new \InvalidArgumentException('Invalid file identifier given. It must be of type string and not empty. "' . gettype($identifier) . '" given.', 1401732565);
        }
        $fileProperties = $this->gatherFileInformationArray($identifier);
        $record = $this->getFileIndexRepository()->addRaw($fileProperties);
        $fileObject = $this->getResourceFactory()->getFileObject($record['uid'], $record);
        $this->extractRequiredMetaData($fileObject);

        if ($this->storage->autoExtractMetadataEnabled()) {
            $this->extractMetaData($fileObject);
        }

        return $fileObject;
    }

    /**
     * Update index entry
     *
     * @param File $fileObject
     */
    public function updateIndexEntry(File $fileObject)
    {
        $updatedInformation = $this->gatherFileInformationArray($fileObject->getIdentifier());
        $fileObject->updateProperties($updatedInformation);
        $this->getFileIndexRepository()->update($fileObject);
        $this->extractRequiredMetaData($fileObject);
    }

    /**
     */
    public function processChangesInStorages()
    {
        // get all file-identifiers from the storage
        $availableFiles = $this->storage->getFileIdentifiersInFolder($this->storage->getRootLevelFolder(false)->getIdentifier(), true, true);
        $this->detectChangedFilesInStorage($availableFiles);
        $this->processChangedAndNewFiles();

        $this->detectMissingFiles();
    }

    /**
     * @param int $maximumFileCount
     */
    public function runMetaDataExtraction($maximumFileCount = -1)
    {
        $fileIndexRecords = $this->getFileIndexRepository()->findInStorageWithIndexOutstanding($this->storage, $maximumFileCount);
        foreach ($fileIndexRecords as $indexRecord) {
            $fileObject = $this->getResourceFactory()->getFileObject($indexRecord['uid'], $indexRecord);
            // Check for existence of file before extraction
            if ($fileObject->exists()) {
                try {
                    $this->extractMetaData($fileObject);
                } catch (InsufficientFileAccessPermissionsException $e) {
                    //  We skip files that are not accessible
                } catch (IllegalFileExtensionException $e) {
                    //  We skip files that have an extension that we don't allow
                }
            } else {
                // Mark file as missing and continue with next record
                $this->getFileIndexRepository()->markFileAsMissing($indexRecord['uid']);
            }
        }
    }

    /**
     * Extract metadata for given fileObject
     *
     * @param File $fileObject
     */
    public function extractMetaData(File $fileObject)
    {
        $newMetaData = [
            0 => $fileObject->_getMetaData()
        ];

        // Loop through available extractors and fetch metadata for the given file.
        foreach ($this->getExtractionServices() as $service) {
            if ($this->isFileTypeSupportedByExtractor($fileObject, $service) && $service->canProcess($fileObject)) {
                $newMetaData[$service->getPriority()] = $service->extractMetaData($fileObject, $newMetaData);
            }
        }

        // Sort metadata by priority so that merging happens in order of precedence.
        ksort($newMetaData);

        // Merge the collected metadata.
        $metaData = [];
        foreach ($newMetaData as $data) {
            $metaData = array_merge($metaData, $data);
        }
        $fileObject->_updateMetaDataProperties($metaData);
        $this->getMetaDataRepository()->update($fileObject->getUid(), $metaData);
        $this->getFileIndexRepository()->updateIndexingTime($fileObject->getUid());
    }

    /**
     * Get available extraction services
     *
     * @return ExtractorInterface[]
     */
    protected function getExtractionServices()
    {
        if ($this->extractionServices === null) {
            $this->extractionServices = $this->getExtractorRegistry()->getExtractorsWithDriverSupport($this->storage->getDriverType());
        }
        return $this->extractionServices;
    }

    /**
     * Since by now all files in filesystem have been looked at it is save to assume,
     * that files that are in indexed but not touched in this run are missing
     */
    protected function detectMissingFiles()
    {
        $indexedNotExistentFiles = $this->getFileIndexRepository()->findInStorageAndNotInUidList(
            $this->storage,
            $this->identifiedFileUids
        );

        foreach ($indexedNotExistentFiles as $record) {
            if (!$this->storage->hasFile($record['identifier'])) {
                $this->getFileIndexRepository()->markFileAsMissing($record['uid']);
            }
        }
    }

    /**
     * Check whether the extractor service supports this file according to file type restrictions.
     *
     * @param File $file
     * @param ExtractorInterface $extractor
     * @return bool
     */
    protected function isFileTypeSupportedByExtractor(File $file, ExtractorInterface $extractor)
    {
        $isSupported = true;
        $fileTypeRestrictions = $extractor->getFileTypeRestrictions();
        if (!empty($fileTypeRestrictions) && !in_array($file->getType(), $fileTypeRestrictions)) {
            $isSupported = false;
        }
        return $isSupported;
    }

    /**
     * Adds updated files to the processing queue
     *
     * @param array $fileIdentifierArray
     */
    protected function detectChangedFilesInStorage(array $fileIdentifierArray)
    {
        foreach ($fileIdentifierArray as $fileIdentifier) {
            // skip processed files
            if ($this->storage->isWithinProcessingFolder($fileIdentifier)) {
                continue;
            }
            // Get the modification time for file-identifier from the storage
            $modificationTime = $this->storage->getFileInfoByIdentifier($fileIdentifier, ['mtime']);
            // Look if the the modification time in FS is higher than the one in database (key needed on timestamps)
            $indexRecord = $this->getFileIndexRepository()->findOneByStorageUidAndIdentifier($this->storage->getUid(), $fileIdentifier);

            if ($indexRecord !== false) {
                $this->identifiedFileUids[] = $indexRecord['uid'];

                if ((int)$indexRecord['modification_date'] !== $modificationTime['mtime'] || $indexRecord['missing']) {
                    $this->filesToUpdate[$fileIdentifier] = $indexRecord;
                }
            } else {
                $this->filesToUpdate[$fileIdentifier] = null;
            }
        }
    }

    /**
     * Processes the Files which have been detected as "changed or new"
     * in the storage
     */
    protected function processChangedAndNewFiles()
    {
        foreach ($this->filesToUpdate as $identifier => $data) {
            try {
                if ($data === null) {
                    // search for files with same content hash in indexed storage
                    $fileHash = $this->storage->hashFileByIdentifier($identifier, 'sha1');
                    $files = $this->getFileIndexRepository()->findByContentHash($fileHash);
                    $fileObject = null;
                    if (!empty($files)) {
                        foreach ($files as $fileIndexEntry) {
                            // check if file is missing then we assume it's moved/renamed
                            if (!$this->storage->hasFile($fileIndexEntry['identifier'])) {
                                $fileObject = $this->getResourceFactory()->getFileObject(
                                    $fileIndexEntry['uid'],
                                    $fileIndexEntry
                                );
                                $fileObject->updateProperties(
                                    [
                                        'identifier' => $identifier,
                                    ]
                                );
                                $this->updateIndexEntry($fileObject);
                                $this->identifiedFileUids[] = $fileObject->getUid();
                                break;
                            }
                        }
                    }
                    // create new index when no missing file with same content hash is found
                    if ($fileObject === null) {
                        $fileObject = $this->createIndexEntry($identifier);
                        $this->identifiedFileUids[] = $fileObject->getUid();
                    }
                } else {
                    // update existing file
                    $fileObject = $this->getResourceFactory()->getFileObject($data['uid'], $data);
                    $this->updateIndexEntry($fileObject);
                }
            } catch (InvalidHashException $e) {
                $this->logger->error('Unable to create hash for file ' . $identifier);
            } catch (\Exception $e) {
                $this->logger->error('Unable to index / update file with identifier ' . $identifier . ' (Error: ' . $e->getMessage() . ')');
            }
        }
    }

    /**
     * Since the core desperately needs image sizes in metadata table put them there
     * This should be called after every "content" update and "record" creation
     *
     * @param File $fileObject
     */
    protected function extractRequiredMetaData(File $fileObject)
    {
        // since the core desperately needs image sizes in metadata table do this manually
        // prevent doing this for remote storages, remote storages must provide the data with extractors
        if ($this->storage->getDriverType() === 'Local' && $fileObject->getSize() > 0 && GeneralUtility::inList(strtolower($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] ?? ''), $fileObject->getExtension())) {
            $rawFileLocation = $fileObject->getForLocalProcessing(false);
            $imageInfo = GeneralUtility::makeInstance(ImageInfo::class, $rawFileLocation);
            $metaData = [
                'width' => $imageInfo->getWidth(),
                'height' => $imageInfo->getHeight(),
            ];
            $this->getMetaDataRepository()->update($fileObject->getUid(), $metaData);
            $fileObject->_updateMetaDataProperties($metaData);
        }
    }

    /****************************
     *
     *         UTILITY
     *
     ****************************/

    /**
     * Collects the information to be cached in sys_file
     *
     * @param string $identifier
     * @return array
     * @throws \TYPO3\CMS\Core\Resource\Exception\InvalidHashException
     */
    protected function gatherFileInformationArray($identifier): array
    {
        $fileInfo = $this->storage->getFileInfoByIdentifier($identifier);
        $fileInfo = $this->transformFromDriverFileInfoArrayToFileObjectFormat($fileInfo);
        $fileInfo['type'] = $this->getFileType($fileInfo['mime_type']);
        $fileInfo['sha1'] = $this->storage->hashFileByIdentifier($identifier, 'sha1');
        if (!isset($fileInfo['extension'])) {
            trigger_error('Guessing FAL file extensions will be removed in TYPO3 v10.0. The FAL (' . $this->storage->getDriverType() . ') driver method getFileInfoByIdentifier() should return the file extension.', E_USER_DEPRECATED);
            $fileInfo['extension'] = PathUtility::pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
        }
        $fileInfo['missing'] = 0;

        return $fileInfo;
    }

    /**
     * Maps the mimetype to a sys_file table type
     *
     * @param string $mimeType
     * @return string
     */
    protected function getFileType($mimeType)
    {
        list($fileType) = explode('/', $mimeType);
        switch (strtolower($fileType)) {
            case 'text':
                $type = File::FILETYPE_TEXT;
                break;
            case 'image':
                $type = File::FILETYPE_IMAGE;
                break;
            case 'audio':
                $type = File::FILETYPE_AUDIO;
                break;
            case 'video':
                $type = File::FILETYPE_VIDEO;
                break;
            case 'application':
            case 'software':
                $type = File::FILETYPE_APPLICATION;
                break;
            default:
                $type = File::FILETYPE_UNKNOWN;
        }
        return $type;
    }

    /**
     * However it happened, the properties of a file object which
     * are persisted to the database are named different than the
     * properties the driver returns in getFileInfo.
     * Therefore a mapping must happen.
     *
     * @param array $fileInfo
     *
     * @return array
     */
    protected function transformFromDriverFileInfoArrayToFileObjectFormat(array $fileInfo)
    {
        $mappingInfo = [
            // 'driverKey' => 'fileProperty' Key is from the driver, value is for the property in the file
            'size' => 'size',
            'atime' => null,
            'mtime' => 'modification_date',
            'ctime' => 'creation_date',
            'mimetype' => 'mime_type'
        ];
        $mappedFileInfo = [];
        foreach ($fileInfo as $key => $value) {
            if (array_key_exists($key, $mappingInfo)) {
                if ($mappingInfo[$key] !== null) {
                    $mappedFileInfo[$mappingInfo[$key]] = $value;
                }
            } else {
                $mappedFileInfo[$key] = $value;
            }
        }
        return $mappedFileInfo;
    }

    /**
     * Returns an instance of the FileIndexRepository
     *
     * @return FileIndexRepository
     */
    protected function getFileIndexRepository()
    {
        return FileIndexRepository::getInstance();
    }

    /**
     * Returns an instance of the FileIndexRepository
     *
     * @return MetaDataRepository
     */
    protected function getMetaDataRepository()
    {
        return MetaDataRepository::getInstance();
    }

    /**
     * Returns the ResourceFactory
     *
     * @return ResourceFactory
     */
    protected function getResourceFactory()
    {
        return ResourceFactory::getInstance();
    }

    /**
     * Returns an instance of the FileIndexRepository
     *
     * @return ExtractorRegistry
     */
    protected function getExtractorRegistry()
    {
        return ExtractorRegistry::getInstance();
    }
}
