<?php
namespace TYPO3\CMS\Core\Resource\Collection;

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

use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A collection containing a set of files to be represented as a (virtual) folder.
 * This collection is persisted to the database with the accordant folder reference.
 */
class FolderBasedFileCollection extends AbstractFileCollection
{
    /**
     * @var string
     */
    protected static $storageTableName = 'sys_file_collection';

    /**
     * @var string
     */
    protected static $type = 'folder';

    /**
     * @var string
     */
    protected static $itemsCriteriaField = 'folder';

    /**
     * The folder
     *
     * @var \TYPO3\CMS\Core\Resource\Folder
     */
    protected $folder;

    /**
     * @var bool
     */
    protected $recursive;

    /**
     * Populates the content-entries of the storage
     *
     * Queries the underlying storage for entries of the collection
     * and adds them to the collection data.
     *
     * If the content entries of the storage had not been loaded on creation
     * ($fillItems = false) this function is to be used for loading the contents
     * afterwards.
     */
    public function loadContents()
    {
        if ($this->folder instanceof Folder) {
            $entries = $this->folder->getFiles(0, 0, Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, $this->recursive);
            foreach ($entries as $entry) {
                $this->add($entry);
            }
        }
    }

    /**
     * Gets the items criteria.
     *
     * @return string
     */
    public function getItemsCriteria()
    {
        return $this->folder->getCombinedIdentifier();
    }

    /**
     * Returns an array of the persistable properties and contents
     * which are processable by DataHandler.
     *
     * @return array
     */
    protected function getPersistableDataArray()
    {
        return [
            'title' => $this->getTitle(),
            'type' => self::$type,
            'description' => $this->getDescription(),
            'folder' => $this->folder->getIdentifier(),
            'storage' => $this->folder->getStorage()->getUid()
        ];
    }

    /**
     * Similar to method in \TYPO3\CMS\Core\Collection\AbstractRecordCollection,
     * but without $this->itemTableName= $array['table_name'],
     * but with $this->storageItemsFieldContent = $array[self::$storageItemsField];
     *
     * @param array $array
     */
    public function fromArray(array $array)
    {
        $this->uid = $array['uid'];
        $this->title = $array['title'];
        $this->description = $array['description'];
        $this->recursive = (bool)$array['recursive'];
        if (!empty($array['folder']) && !empty($array['storage'])) {
            /** @var $storageRepository StorageRepository */
            $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
            /** @var $storage \TYPO3\CMS\Core\Resource\ResourceStorage */
            $storage = $storageRepository->findByUid($array['storage']);
            if ($storage) {
                $this->folder = $storage->getFolder($array['folder']);
            }
        }
    }
}
