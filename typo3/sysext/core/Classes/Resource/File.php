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

namespace TYPO3\CMS\Core\Resource;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * File representation in the file abstraction layer.
 */
class File extends AbstractFile
{
    /**
     * Contains the names of all properties that have been update since the
     * instantiation of this object
     *
     * @var array
     */
    protected $updatedProperties = [];

    /**
     * @var MetaDataAspect
     */
    private $metaDataAspect;

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
        $this->identifier = $fileData['identifier'] ?? null;
        $this->name = $fileData['name'] ?? '';
        $this->properties = $fileData;
        $this->storage = $storage;

        if (!empty($metaData)) {
            $this->getMetaData()->add($metaData);
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
        }
        return $this->getMetaData()[$key];
    }

    /**
     * Checks if the file has a (metadata) property which
     * can be retrieved by "getProperty"
     *
     * @param string $key
     * @return bool
     */
    public function hasProperty($key): bool
    {
        if (!parent::hasProperty($key)) {
            return isset($this->getMetaData()[$key]);
        }
        return true;
    }

    /**
     * Returns the properties of this object.
     *
     * @return array
     */
    public function getProperties(): array
    {
        return array_merge(
            parent::getProperties(),
            array_diff_key($this->getMetaData()->get(), parent::getProperties()),
            [
                'metadata_uid' => $this->getMetaData()->get()['uid'] ?? 0,
            ]
        );
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
     * @return bool|null
     */
    public function isIndexed()
    {
        return true;
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

        if (isset($properties['uid']) && $this->properties['uid'] != 0) {
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
            $this->storage = GeneralUtility::makeInstance(StorageRepository::class)->findByUid((int)$properties['storage']);
        }
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
     * @param string $action can be read, write, delete
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
                'delete' => $this->checkActionPermission('delete'),
            ],
            'checksum' => $this->calculateChecksum(),
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
     * @param bool  $relativeToCurrentScript   Determines whether the URL returned should be relative to the current script, in case it is relative at all (only for the LocalDriver). Deprecated since TYPO3 v11, will be removed in TYPO3 v12.0
     *
     * @return string|null NULL if file is missing or deleted, the generated url otherwise
     */
    public function getPublicUrl($relativeToCurrentScript = false)
    {
        if ($this->isMissing() || $this->deleted) {
            return null;
        }
        // @deprecated $relativeToCurrentScript since v11, will be removed in TYPO3 v12.0
        return $this->getStorage()->getPublicUrl($this, $relativeToCurrentScript);
    }

    /**
     * @param string $key
     * @internal Only for use in Repositories and indexer
     * @return mixed
     */
    public function _getPropertyRaw($key)
    {
        return parent::getProperty($key);
    }

    /**
     * Loads the metadata of a file in an encapsulated aspect
     *
     * @return MetaDataAspect
     */
    public function getMetaData(): MetaDataAspect
    {
        if ($this->metaDataAspect === null) {
            $this->metaDataAspect = GeneralUtility::makeInstance(MetaDataAspect::class, $this);
        }
        return $this->metaDataAspect;
    }
}
