<?php
namespace TYPO3\CMS\Core\Resource;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * File representation in the file abstraction layer.
 *
 */
class File extends AbstractFile
{
    /**
     * @var bool
     */
    protected $metaDataLoaded = false;

    /**
     * @var array
     */
    protected $metaDataProperties = [];

    /**
     * Set to TRUE while this file is being indexed - used to prevent some endless loops
     *
     * @var bool
     */
    protected $indexingInProgress = false;

    /**
     * Contains the names of all properties that have been update since the
     * instantiation of this object
     *
     * @var array
     */
    protected $updatedProperties = [];

    /**
     * Constructor for a file object. Should normally not be used directly, use
     * the corresponding factory methods instead.
     *
     * @param array $fileData
     * @param ResourceStorage $storage
     * @param array $metaData
     */
    public function __construct(array $fileData, ResourceStorage $storage, array $metaData = [])
    {
        $this->identifier = $fileData['identifier'];
        $this->name = $fileData['name'];
        $this->properties = $fileData;
        $this->storage = $storage;
        if (!empty($metaData)) {
            $this->metaDataLoaded = true;
            $this->metaDataProperties = $metaData;
        }
    }

    /*******************************
     * VARIOUS FILE PROPERTY GETTERS
     *******************************/
    /**
     * Returns a property value
     *
     * @param string $key
     * @return mixed Property value
     */
    public function getProperty($key)
    {
        if (parent::hasProperty($key)) {
            return parent::getProperty($key);
        } else {
            $metaData = $this->_getMetaData();
            return isset($metaData[$key]) ? $metaData[$key] : null;
        }
    }

    /**
     * Checks if the file has a (metadata) property which
     * can be retrieved by "getProperty"
     *
     * @param string $key
     * @return bool
     */
    public function hasProperty($key)
    {
        if (!parent::hasProperty($key)) {
            return array_key_exists($key, $this->_getMetaData());
        }
        return true;
    }

    /**
     * Returns the properties of this object.
     *
     * @return array
     */
    public function getProperties()
    {
        return array_merge(parent::getProperties(), array_diff_key($this->_getMetaData(), parent::getProperties()));
    }

    /**
     * Returns the MetaData
     *
     * @return array
     * @internal
     */
    public function _getMetaData()
    {
        if (!$this->metaDataLoaded) {
            $this->loadMetaData();
        }
        return $this->metaDataProperties;
    }

    /******************
     * CONTENTS RELATED
     ******************/
    /**
     * Get the contents of this file
     *
     * @return string File contents
     */
    public function getContents()
    {
        return $this->getStorage()->getFileContents($this);
    }

    /**
     * Gets SHA1 hash.
     *
     * @return string
     */
    public function getSha1()
    {
        if (empty($this->properties['sha1'])) {
            $this->properties['sha1'] = parent::getSha1();
        }
        return $this->properties['sha1'];
    }

    /**
     * Replace the current file contents with the given string
     *
     * @param string $contents The contents to write to the file.
     * @return File The file object (allows chaining).
     */
    public function setContents($contents)
    {
        $this->getStorage()->setFileContents($this, $contents);
        return $this;
    }

    /***********************
     * INDEX RELATED METHODS
     ***********************/
    /**
     * Returns TRUE if this file is indexed
     *
     * @return bool|NULL
     */
    public function isIndexed()
    {
        return true;
    }

    /**
     * Loads MetaData from Repository
     * @return void
     */
    protected function loadMetaData()
    {
        if (!$this->indexingInProgress) {
            $this->indexingInProgress = true;
            $this->metaDataProperties = $this->getMetaDataRepository()->findByFile($this);
            $this->metaDataLoaded = true;
            $this->indexingInProgress = false;
        }
    }

    /**
     * Updates the properties of this file, e.g. after re-indexing or moving it.
     * By default, only properties that exist as a key in the $properties array
     * are overwritten. If you want to explicitly unset a property, set the
     * corresponding key to NULL in the array.
     *
     * NOTE: This method should not be called from outside the File Abstraction Layer (FAL)!
     *
     * @param array $properties
     * @return void
     * @internal
     */
    public function updateProperties(array $properties)
    {
        // Setting identifier and name to update values; we have to do this
        // here because we might need a new identifier when loading
        // (and thus possibly indexing) a file.
        if (isset($properties['identifier'])) {
            $this->identifier = $properties['identifier'];
        }
        if (isset($properties['name'])) {
            $this->name = $properties['name'];
        }

        if ($this->properties['uid'] != 0 && isset($properties['uid'])) {
            unset($properties['uid']);
        }
        foreach ($properties as $key => $value) {
            if ($this->properties[$key] !== $value) {
                if (!in_array($key, $this->updatedProperties)) {
                    $this->updatedProperties[] = $key;
                }
                $this->properties[$key] = $value;
            }
        }
        // If the mime_type property should be updated and it was changed also update the type.
        if (array_key_exists('mime_type', $properties) && in_array('mime_type', $this->updatedProperties)) {
            $this->updatedProperties[] = 'type';
            unset($this->properties['type']);
            $this->getType();
        }
        if (array_key_exists('storage', $properties) && in_array('storage', $this->updatedProperties)) {
            $this->storage = ResourceFactory::getInstance()->getStorageObject($properties['storage']);
        }
    }

    /**
     * Updates MetaData properties
     *
     * @internal Do not use outside the FileAbstraction Layer classes
     *
     * @param array $properties
     * @return void
     */
    public function _updateMetaDataProperties(array $properties)
    {
        $this->metaDataProperties = array_merge($this->metaDataProperties, $properties);
    }

    /**
     * Returns the names of all properties that have been updated in this record
     *
     * @return array
     */
    public function getUpdatedProperties()
    {
        return $this->updatedProperties;
    }

    /****************************************
     * STORAGE AND MANAGEMENT RELATED METHODS
     ****************************************/
    /**
     * Check if a file operation (= action) is allowed for this file
     *
     * @param 	string	$action, can be read, write, delete
     * @return bool
     */
    public function checkActionPermission($action)
    {
        return $this->getStorage()->checkFileActionPermission($action, $this);
    }

    /*****************
     * SPECIAL METHODS
     *****************/
    /**
     * Creates a MD5 hash checksum based on the combined identifier of the file,
     * the files' mimetype and the systems' encryption key.
     * used to generate a thumbnail, and this hash is checked if valid
     *
     * @return string the MD5 hash
     */
    public function calculateChecksum()
    {
        return md5(
            $this->getCombinedIdentifier() . '|' .
            $this->getMimeType() . '|' .
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']
        );
    }

    /**
     * Returns a modified version of the file.
     *
     * @param string $taskType The task type of this processing
     * @param array $configuration the processing configuration, see manual for that
     * @return ProcessedFile The processed file
     */
    public function process($taskType, array $configuration)
    {
        return $this->getStorage()->processFile($this, $taskType, $configuration);
    }

    /**
     * Returns an array representation of the file.
     * (This is used by the generic listing module vidi when displaying file records.)
     *
     * @return array Array of main data of the file. Don't rely on all data to be present here, it's just a selection of the most relevant information.
     */
    public function toArray()
    {
        $array = [
            'id' => $this->getCombinedIdentifier(),
            'name' => $this->getName(),
            'extension' => $this->getExtension(),
            'type' => $this->getType(),
            'mimetype' => $this->getMimeType(),
            'size' => $this->getSize(),
            'url' => $this->getPublicUrl(),
            'indexed' => true,
            'uid' => $this->getUid(),
            'permissions' => [
                'read' => $this->checkActionPermission('read'),
                'write' => $this->checkActionPermission('write'),
                'delete' => $this->checkActionPermission('delete')
            ],
            'checksum' => $this->calculateChecksum()
        ];
        foreach ($this->properties as $key => $value) {
            $array[$key] = $value;
        }
        $stat = $this->getStorage()->getFileInfo($this);
        foreach ($stat as $key => $value) {
            $array[$key] = $value;
        }
        return $array;
    }

    /**
     * @return bool
     */
    public function isMissing()
    {
        return (bool)$this->getProperty('missing');
    }

    /**
     * @param bool $missing
     */
    public function setMissing($missing)
    {
        $this->updateProperties(['missing' => $missing ? 1 : 0]);
    }

    /**
     * Returns a publicly accessible URL for this file
     * When file is marked as missing or deleted no url is returned
     *
     * WARNING: Access to the file may be restricted by further means, e.g. some
     * web-based authentication. You have to take care of this yourself.
     *
     * @param bool  $relativeToCurrentScript   Determines whether the URL returned should be relative to the current script, in case it is relative at all (only for the LocalDriver)
     *
     * @return string
     */
    public function getPublicUrl($relativeToCurrentScript = false)
    {
        if ($this->isMissing() || $this->deleted) {
            return false;
        } else {
            return $this->getStorage()->getPublicUrl($this, $relativeToCurrentScript);
        }
    }

    /**
     * @return \TYPO3\CMS\Core\Resource\Index\MetaDataRepository
     */
    protected function getMetaDataRepository()
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Index\MetaDataRepository::class);
    }

    /**
     * @return \TYPO3\CMS\Core\Resource\Index\FileIndexRepository
     */
    protected function getFileIndexRepository()
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Index\FileIndexRepository::class);
    }

    /**
     * @return void
     * @internal Only for usage in Indexer
     */
    public function setIndexingInProgess($indexingState)
    {
        $this->indexingInProgress = (bool)$indexingState;
    }

    /**
     * @param $key
     * @internal Only for use in Repositories and indexer
     * @return mixed
     */
    public function _getPropertyRaw($key)
    {
        return parent::getProperty($key);
    }
}
