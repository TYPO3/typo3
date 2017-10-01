<?php
namespace TYPO3\CMS\Frontend\Resource;

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

use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\FileCollectionRepository;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Object to collect files from various sources during runtime
 * Sources can be file references, file collections or folders
 *
 * Use in FILES Content Object or for a Fluid Data Processor
 *
 * Is not persisted, use only in FE.
 */
class FileCollector implements \Countable
{
    /**
     * The files
     *
     * @var array
     */
    protected $files = [];

    /**
     * The file repository
     *
     * @var \TYPO3\CMS\Core\Resource\FileRepository
     */
    protected $fileRepository;

    /**
     * The file collection repository
     *
     * @var \TYPO3\CMS\Core\Resource\FileCollectionRepository
     */
    protected $fileCollectionRepository;

    /**
     * The resource factory
     *
     * @var \TYPO3\CMS\Core\Resource\ResourceFactory
     */
    protected $resourceFactory;

    /**
     * Add files
     *
     * @param array $fileUids
     */
    public function addFiles(array $fileUids = [])
    {
        if (!empty($fileUids)) {
            foreach ($fileUids as $fileUid) {
                try {
                    $this->addFileObject($this->getResourceFactory()->getFileObject($fileUid));
                } catch (Exception $e) {
                    $this->getLogger()->warning(
                        'The file with uid  "' . $fileUid
                        . '" could not be found and won\'t be included in frontend output',
                        ['exception' => $e]
                    );
                }
            }
        }
    }

    /**
     * Add files to the collection from a relation
     *
     * @param string $relationTable The table of the relation (e.g. tt_content or pages)
     * @param string $relationField The field which holds the files (e.g. media or images)
     * @param array $referenceRecord the record which is referencing the files
     */
    public function addFilesFromRelation($relationTable, $relationField, array $referenceRecord)
    {
        if (is_object($GLOBALS['TSFE']) && is_object($GLOBALS['TSFE']->sys_page)) {
            $fileReferences = $GLOBALS['TSFE']->sys_page->getFileReferences($relationTable, $relationField, $referenceRecord);
        } else {
            $fileReferences = $this->getFileRepository()->findByRelation($relationTable, $relationField, $referenceRecord['uid']);
        }

        if (!empty($fileReferences)) {
            $this->addFileObjects($fileReferences);
        }
    }

    /**
     * Add files from UIDs of a reference
     *
     * @param array $fileReferenceUids
     */
    public function addFileReferences(array $fileReferenceUids = [])
    {
        foreach ($fileReferenceUids as $fileReferenceUid) {
            $fileObject = $this->getFileRepository()->findFileReferenceByUid($fileReferenceUid);
            $this->addFileObject($fileObject);
        }
    }

    /**
     * Add files to the collection from multiple file collections
     *
     * @param array $fileCollectionUids The file collections uids
     */
    public function addFilesFromFileCollections(array $fileCollectionUids = [])
    {
        foreach ($fileCollectionUids as $fileCollectionUid) {
            $this->addFilesFromFileCollection($fileCollectionUid);
        }
    }

    /**
     * Add files to the collection from one single file collection
     *
     * @param int $fileCollectionUid The file collections uid
     */
    public function addFilesFromFileCollection($fileCollectionUid = null)
    {
        if (!empty($fileCollectionUid)) {
            try {
                $fileCollection = $this->getFileCollectionRepository()->findByUid($fileCollectionUid);

                if ($fileCollection instanceof \TYPO3\CMS\Core\Resource\Collection\AbstractFileCollection) {
                    $fileCollection->loadContents();
                    $files = $fileCollection->getItems();

                    $this->addFileObjects($files);
                }
            } catch (Exception $e) {
                $this->getLogger()->warning(
                    'The file-collection with uid  "' . $fileCollectionUid
                    . '" could not be found or contents could not be loaded and won\'t be included in frontend output.',
                    ['exception' => $e]
                );
            }
        }
    }

    /**
     * Add files to the collection from multiple folders
     *
     * @param array $folderIdentifiers The folder identifiers
     * @param bool $recursive Add files recursive from given folders
     */
    public function addFilesFromFolders(array $folderIdentifiers = [], $recursive = false)
    {
        foreach ($folderIdentifiers as $folderIdentifier) {
            $this->addFilesFromFolder($folderIdentifier, $recursive);
        }
    }

    /**
     * Add files to the collection from one single folder
     *
     * @param string $folderIdentifier The folder identifier
     * @param bool $recursive Add files recursive from given folders
     */
    public function addFilesFromFolder($folderIdentifier, $recursive = false)
    {
        if ($folderIdentifier) {
            try {
                if (strpos($folderIdentifier, 't3://folder') === 0) {
                    // a t3://folder link to a folder in FAL
                    $linkService = GeneralUtility::makeInstance(LinkService::class);
                    $data = $linkService->resolveByStringRepresentation($folderIdentifier);
                    $folder = $data['folder'];
                } else {
                    $folder = $this->getResourceFactory()->getFolderObjectFromCombinedIdentifier($folderIdentifier);
                }
                if ($folder instanceof Folder) {
                    $files = $folder->getFiles(0, 0, Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, $recursive);
                    $this->addFileObjects(array_values($files));
                }
            } catch (Exception $e) {
                $this->getLogger()->warning(
                    'The folder with identifier  "' . $folderIdentifier
                    . '" could not be found and won\'t be included in frontend output',
                    ['exception' => $e]
                );
            }
        }
    }

    /**
     * Sort the file objects based on a property
     *
     * @param string $sortingProperty The sorting property
     * @param string $sortingOrder can be ascending or descending or "random"
     */
    public function sort($sortingProperty = '', $sortingOrder = 'ascending')
    {
        if ($sortingProperty !== '' && count($this->files) > 1) {
            @usort(
                $this->files,
                function (
                    FileInterface $a,
                    FileInterface $b
                ) use ($sortingProperty) {
                    if ($a->hasProperty($sortingProperty) && $b->hasProperty($sortingProperty)) {
                        return strnatcasecmp($a->getProperty($sortingProperty), $b->getProperty($sortingProperty));
                    }
                    return 0;
                }
            );

            switch (strtolower($sortingOrder)) {
                case 'descending':
                case 'desc':
                    $this->files = array_reverse($this->files);
                    break;
                case 'random':
                case 'rand':
                    shuffle($this->files);
                    break;
            }
        }
    }

    /**
     * Add a file object to the collection
     *
     * @param FileInterface $file The file object
     */
    public function addFileObject(FileInterface $file)
    {
        $this->files[] = $file;
    }

    /**
     * Add multiple file objects to the collection
     *
     * @param FileInterface[] $files The file objects
     */
    public function addFileObjects($files)
    {
        $this->files = array_merge($this->files, $files);
    }

    /**
     * Final getter method to fetch the accumulated data
     *
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->files);
    }

    /**
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger()
    {
        return GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    /**
     * @return ResourceFactory
     */
    protected function getResourceFactory()
    {
        if ($this->resourceFactory === null) {
            $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        }
        return $this->resourceFactory;
    }

    /**
     * @return FileCollectionRepository
     */
    protected function getFileCollectionRepository()
    {
        if ($this->fileCollectionRepository === null) {
            $this->fileCollectionRepository = GeneralUtility::makeInstance(FileCollectionRepository::class);
        }
        return $this->fileCollectionRepository;
    }

    /**
     * @return FileRepository
     */
    protected function getFileRepository()
    {
        if ($this->fileRepository === null) {
            $this->fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        }
        return $this->fileRepository;
    }
}
