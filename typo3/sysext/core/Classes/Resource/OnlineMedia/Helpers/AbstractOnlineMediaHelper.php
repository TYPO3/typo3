<?php
namespace TYPO3\CMS\Core\Resource\OnlineMedia\Helpers;

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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AbstractOnlineMediaHelper
 */
abstract class AbstractOnlineMediaHelper implements OnlineMediaHelperInterface
{
    /**
     * Cached OnlineMediaIds [fileUid => id]
     *
     * @var array
     */
    protected $onlineMediaIdCache = [];

    /**
     * File extension bind to the OnlineMedia helper
     *
     * @var string
     */
    protected $extension = '';

    /**
     * Constructor
     *
     * @param string $extension file extension bind to the OnlineMedia helper
     */
    public function __construct($extension)
    {
        $this->extension = $extension;
    }

    /**
     * Get Online Media item id
     *
     * @param File $file
     * @return string
     */
    public function getOnlineMediaId(File $file)
    {
        if (!isset($this->onlineMediaIdCache[$file->getUid()])) {
            // By definition these files only contain the ID of the remote media source
            $this->onlineMediaIdCache[$file->getUid()] = trim($file->getContents());
        }
        return $this->onlineMediaIdCache[$file->getUid()];
    }

    /**
     * Search for files with same onlineMediaId by content hash in indexed storage
     *
     * @param string $onlineMediaId
     * @param Folder $targetFolder
     * @param string $fileExtension
     * @return File|NULL
     */
    protected function findExistingFileByOnlineMediaId($onlineMediaId, Folder $targetFolder, $fileExtension)
    {
        $file = null;
        $fileHash = sha1($onlineMediaId);
        $files = $this->getFileIndexRepository()->findByContentHash($fileHash);
        if (!empty($files)) {
            foreach ($files as $fileIndexEntry) {
                if (
                    $fileIndexEntry['folder_hash'] === $targetFolder->getHashedIdentifier()
                    && (int)$fileIndexEntry['storage'] === $targetFolder->getStorage()->getUid()
                    && $fileIndexEntry['extension'] === $fileExtension
                ) {
                    $file = $this->getResourceFactory()->getFileObject($fileIndexEntry['uid'], $fileIndexEntry);
                    break;
                }
            }
        }
        return $file;
    }

    /**
     * Create new OnlineMedia item container file
     *
     * @param Folder $targetFolder
     * @param string $fileName
     * @param string $onlineMediaId
     * @return File
     */
    protected function createNewFile(Folder $targetFolder, $fileName, $onlineMediaId)
    {
        $tempFilePath = GeneralUtility::tempnam('online_media');
        file_put_contents($tempFilePath, $onlineMediaId);
        return $targetFolder->addFile($tempFilePath, $fileName, 'changeName');
    }

    /**
     * Get temporary folder path to save preview images
     *
     * @return string
     */
    protected function getTempFolderPath()
    {
        $path = PATH_site . 'typo3temp/online_media/';
        if (!is_dir($path)) {
            GeneralUtility::mkdir($path);
        }
        return $path;
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
     * Returns the ResourceFactory
     *
     * @return ResourceFactory
     */
    protected function getResourceFactory()
    {
        return ResourceFactory::getInstance();
    }
}
