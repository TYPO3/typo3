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

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Collection\AbstractRecordCollection;
use TYPO3\CMS\Core\Collection\CollectionInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Collection\FileCollectionRegistry;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Factory class for FAL objects
 */
readonly class ResourceFactory implements SingletonInterface
{
    public function __construct(
        protected StorageRepository $storageRepository,
        #[Autowire(service: 'cache.runtime')]
        protected FrontendInterface $runtimeCache,
    ) {}

    /**
     * Returns the Default Storage
     *
     * The Default Storage is considered to be the replacement for the fileadmin/ construct.
     * It is automatically created with the setting fileadminDir from install tool.
     * getDefaultStorage->getDefaultFolder() will get you fileadmin/user_upload/ in a standard
     * TYPO3 installation.
     *
     * @return ResourceStorage|null
     * @internal It is recommended to use the StorageRepository in the future, and this is only kept as backwards-compat layer
     */
    public function getDefaultStorage()
    {
        return $this->storageRepository->getDefaultStorage();
    }

    /**
     * Creates an instance of the storage from given UID. The $recordData can
     * be supplied to increase performance.
     *
     * @param int|null $uid The uid of the storage to instantiate.
     * @param array $recordData The record row from database.
     * @param string $fileIdentifier Identifier for a file. Used for auto-detection of a storage, but only if $uid === 0 (Local default storage) is used
     *
     * @throws \InvalidArgumentException
     * @return ResourceStorage
     * @internal It is recommended to use the StorageRepository in the future, and this is only kept as backwards-compat layer
     */
    public function getStorageObject($uid, array $recordData = [], &$fileIdentifier = null)
    {
        return $this->storageRepository->getStorageObject($uid, $recordData, $fileIdentifier);
    }

    /**
     * Creates an instance of the collection from given UID. The $recordData can be supplied to increase performance.
     *
     * @param int $uid The uid of the collection to instantiate.
     * @param array $recordData The record row from database.
     *
     * @throws \InvalidArgumentException
     * @return Collection\AbstractFileCollection
     */
    public function getCollectionObject($uid, array $recordData = [])
    {
        if (!is_numeric($uid)) {
            throw new \InvalidArgumentException('The UID of collection has to be numeric. UID given: "' . $uid . '"', 1314085999);
        }
        $uid = (int)$uid;
        $collectionObject = $this->collectionCacheGet($uid);
        if ($collectionObject === null) {
            // Get mount data if not already supplied as argument to this function
            if (empty($recordData) || $recordData['uid'] !== $uid) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_collection');
                $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
                $recordData = $queryBuilder->select('*')
                    ->from('sys_file_collection')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                        )
                    )
                    ->executeQuery()
                    ->fetchAssociative();
                if (empty($recordData)) {
                    throw new \InvalidArgumentException('No collection found for given UID: "' . $uid . '"', 1314085992);
                }
            }
            $collectionObject = $this->createCollectionObject($recordData);
            $this->collectionCacheSet($uid, $collectionObject);
        }
        return $collectionObject;
    }

    /**
     * Creates a collection object.
     *
     * @param array $collectionData The database row of the sys_file_collection record.
     * @return CollectionInterface<File>
     */
    public function createCollectionObject(array $collectionData)
    {
        $registry = GeneralUtility::makeInstance(FileCollectionRegistry::class);

        /** @var AbstractRecordCollection $class */
        $class = $registry->getFileCollectionClass($collectionData['type']);

        return $class::create($collectionData);
    }

    /**
     * Creates a folder to directly access (a part of) a storage.
     *
     * @param ResourceStorage $storage The storage the folder belongs to
     * @param string $identifier The path to the folder. Might also be a simple unique string, depending on the storage driver.
     * @param string $name The name of the folder (e.g. the folder name)
     * @return Folder
     * @internal it is recommended to access the ResourceStorage object directly and access ->getFolder($identifier) this method is kept for backwards compatibility
     */
    public function createFolderObject(ResourceStorage $storage, $identifier, $name)
    {
        return GeneralUtility::makeInstance(Folder::class, $storage, $identifier, $name);
    }

    /**
     * Creates an instance of the file given UID. The $fileData can be supplied
     * to increase performance.
     *
     * @param int $uid The uid of the file to instantiate.
     * @param array $fileData The record row from database.
     *
     * @throws \InvalidArgumentException
     * @throws Exception\FileDoesNotExistException
     */
    public function getFileObject($uid, array $fileData = []): File
    {
        if (!is_numeric($uid)) {
            throw new \InvalidArgumentException('The UID of file has to be numeric. UID given: "' . $uid . '"', 1300096564);
        }
        $uid = (int)$uid;
        $fileObject = $this->fileCacheGet($uid);
        if ($fileObject === null) {
            // Fetches data in case $fileData is empty
            if (empty($fileData)) {
                $fileData = $this->getFileIndexRepository()->findOneByUid($uid);
                if ($fileData === false) {
                    throw new FileDoesNotExistException('No file found for given UID: ' . $uid, 1317178604);
                }
            }
            $fileObject = $this->createFileObject($fileData);
            $this->fileCacheSet($fileObject);
        }
        return $fileObject;
    }

    /**
     * Gets a file object from an identifier [storage]:[fileId]
     *
     * @param string $identifier
     * @return File|ProcessedFile|null
     * @throws \InvalidArgumentException
     */
    public function getFileObjectFromCombinedIdentifier($identifier)
    {
        if (!is_string($identifier) || $identifier === '') {
            throw new \InvalidArgumentException('Invalid file identifier given. It must be of type string and not empty. "' . gettype($identifier) . '" given.', 1401732564);
        }
        $parts = GeneralUtility::trimExplode(':', $identifier);
        if (count($parts) === 2) {
            $storageUid = (int)$parts[0];
            $fileIdentifier = $parts[1];
        } else {
            // We only got a path: Go into backwards compatibility mode and
            // use virtual Storage (uid=0)
            $storageUid = 0;
            $fileIdentifier = $parts[0];
        }
        return $this->storageRepository->getStorageObject($storageUid, [], $fileIdentifier)
            ->getFileByIdentifier($fileIdentifier);
    }

    /**
     * Gets a file object from storage by file identifier
     * If the file is outside of the process folder, it gets indexed and returned as file object afterwards
     * If the file is within processing folder, the file object will be directly returned
     *
     * @param ResourceStorage|int $storage
     * @param string $fileIdentifier
     * @return File|ProcessedFile|null
     * @internal It is recommended to use the StorageRepository in the future, and this is only kept as backwards-compat layer
     */
    public function getFileObjectByStorageAndIdentifier($storage, &$fileIdentifier)
    {
        if (!($storage instanceof ResourceStorage)) {
            $storage = $this->storageRepository->getStorageObject($storage, [], $fileIdentifier);
        }
        return $storage->getFileByIdentifier($fileIdentifier);
    }

    /**
     * Bulk function, can be used for anything to get a file or folder
     *
     * 1. It's a UID
     * 2. It's a combined identifier
     * 3. It's just a path/filename (coming from the oldstyle/backwards compatibility)
     *
     * Files, previously laid on fileadmin/ or something, will be "mapped" to the storage the file is
     * in now. Files like typo3temp/ or typo3conf/ will be moved to the first writable storage
     * in its processing folder
     *
     * $input could be
     * - "2:myfolder/myfile.jpg" (combined identifier)
     * - "23" (file UID)
     * - "uploads/myfile.png" (backwards-compatibility, storage "0")
     * - "file:23"
     *
     * @param string $input
     * @return ProcessedFile|File|Folder|null
     */
    public function retrieveFileOrFolderObject($input)
    {
        // Remove Environment::getPublicPath() because absolute paths under Windows systems contain ':'
        // This is done in all considered sub functions anyway
        $input = str_replace(Environment::getPublicPath() . '/', '', (string)$input);

        if (str_starts_with($input, 'file:')) {
            $input = substr($input, 5);
            return $this->retrieveFileOrFolderObject($input);
        }
        if (MathUtility::canBeInterpretedAsInteger($input)) {
            return $this->getFileObject((int)$input);
        }
        if (strpos($input, ':') > 0) {
            [$prefix] = explode(':', $input);
            if (MathUtility::canBeInterpretedAsInteger($prefix)) {
                // path or folder in a valid storageUID
                return $this->getObjectFromCombinedIdentifier($input);
            }
            if ($prefix === 'EXT') {
                $absoluteFilePath = GeneralUtility::getFileAbsFileName($input);
                if (empty($absoluteFilePath)) {
                    return null;
                }
                if (str_starts_with($absoluteFilePath, Environment::getPublicPath())) {
                    $relativePath = PathUtility::getRelativePath(Environment::getPublicPath() . '/', PathUtility::dirname($absoluteFilePath)) . PathUtility::basename($absoluteFilePath);
                } else {
                    try {
                        // The second parameter needs to be false in order to have getFileObjectFromCombinedIdentifier()
                        // use a non-absolute web path and detect this properly as FAL fallback storage.
                        $relativePath = PathUtility::getPublicResourceWebPath($input, false);
                    } catch (\Throwable $e) {
                        throw new ResourceDoesNotExistException(sprintf('Tried to access a private resource file "%s" from fallback compatibility storage. This storage only handles public files.', $input), 1633777536);
                    }
                }

                return $this->getFileObjectFromCombinedIdentifier($relativePath);
            }
            return null;
        }
        // this is a backwards-compatible way to access "0-storage" files or folders
        // @todo: this needs to be removed once we remove support for fallback storage
        // eliminate double slashes, /./ and /../
        $input = PathUtility::getCanonicalPath(ltrim($input, '/'));
        if (@is_file(Environment::getPublicPath() . '/' . $input)) {
            // only the local file
            return $this->getFileObjectFromCombinedIdentifier($input);
        }
        if (@is_dir(Environment::getPublicPath() . '/' . ltrim($input, '/'))) {
            // only the local path
            return $this->getFolderObjectFromCombinedIdentifier(ltrim($input, '/'));
        }
        return null;
    }

    /**
     * Gets a folder object from an identifier [storage]:[fileId]
     *
     * @todo Check naming, inserted by SteffenR while working on filelist.
     * @param string $identifier
     * @return Folder
     */
    public function getFolderObjectFromCombinedIdentifier($identifier)
    {
        $parts = GeneralUtility::trimExplode(':', $identifier);
        if (count($parts) === 2) {
            $storageUid = (int)$parts[0];
            $folderIdentifier = $parts[1];
        } else {
            // We only got a path: Go into backwards compatibility mode and
            // use virtual Storage (uid=0)
            $storageUid = 0;

            // please note that getStorageObject() might modify $folderIdentifier when
            // auto-detecting the best-matching storage to use
            $folderIdentifier = $parts[0];
            // make sure to not use an absolute path, and remove Environment::getPublicPath if it is prepended
            if (str_starts_with($folderIdentifier, Environment::getPublicPath() . '/')) {
                $folderIdentifier = PathUtility::stripPathSitePrefix($parts[0]);
            }
        }
        return $this->storageRepository->getStorageObject($storageUid, [], $folderIdentifier)->getFolder($folderIdentifier);
    }

    /**
     * Gets a file or folder object.
     *
     * @param string $identifier
     *
     * @throws Exception\ResourceDoesNotExistException
     * @return FileInterface|Folder
     */
    public function getObjectFromCombinedIdentifier($identifier)
    {
        [$storageId, $objectIdentifier] = array_pad(GeneralUtility::trimExplode(':', $identifier), 2, null);
        if (!MathUtility::canBeInterpretedAsInteger($storageId) && $objectIdentifier === null) {
            $objectIdentifier = $storageId;
            $storageId = 0;
        }
        if (MathUtility::canBeInterpretedAsInteger($storageId)) {
            $storage = $this->storageRepository->findByUid($storageId);
            if ($storage->hasFile($objectIdentifier)) {
                return $storage->getFile($objectIdentifier);
            }
            if ($storage->hasFolder($objectIdentifier)) {
                return $storage->getFolder($objectIdentifier);
            }
        }
        throw new ResourceDoesNotExistException('Object with identifier "' . $identifier . '" does not exist in storage', 1329647780);
    }

    /**
     * Creates a file object from an array of file data. Requires a database
     * row to be fetched.
     *
     * @return File
     */
    public function createFileObject(array $fileData, ?ResourceStorage $storage = null)
    {
        if (array_key_exists('storage', $fileData) && MathUtility::canBeInterpretedAsInteger($fileData['storage'])) {
            $storageObject = $this->storageRepository->findByUid((int)$fileData['storage']);
        } else {
            $storageObject = $storage;
        }

        // Ensure a storage could be fetched to create the file.
        if ($storageObject === null) {
            throw new \RuntimeException('A file needs to reside in a Storage', 1381570997);
        }

        $fileData['storage'] = $storageObject->getUid();

        $fileObject = GeneralUtility::makeInstance(File::class, $fileData, $storageObject);
        return $fileObject;
    }

    /**
     * Creates an instance of a FileReference object. The $fileReferenceData can
     * be supplied to increase performance.
     *
     * @param int $uid The uid of the file usage (sys_file_reference) to instantiate.
     * @param array $fileReferenceData The record row from database.
     * @param bool $raw Whether to get raw results without performing overlays
     * @throws Exception\ResourceDoesNotExistException
     */
    public function getFileReferenceObject(int $uid, array $fileReferenceData = [], bool $raw = false): FileReference
    {
        $fileReference = $this->fileReferenceCacheGet($uid);
        if ($fileReference === null) {
            // Fetches data in case $fileData is empty
            if (empty($fileReferenceData)) {
                $fileReferenceData = $this->getFileReferenceData($uid, $raw);
                if (!is_array($fileReferenceData)) {
                    throw new ResourceDoesNotExistException(
                        'No file reference (sys_file_reference) was found for given UID: "' . $uid . '"',
                        1317178794
                    );
                }
            }
            $fileReference = $this->createFileReferenceObject($fileReferenceData);
            $this->fileReferenceCacheSet($fileReference);
        }
        return $fileReference;
    }

    /**
     * Creates a file usage object from an array of fileReference data
     * from sys_file_reference table.
     * Requires a database row to be already fetched and present.
     */
    public function createFileReferenceObject(array $fileReferenceData): FileReference
    {
        return GeneralUtility::makeInstance(FileReference::class, $fileReferenceData);
    }

    /**
     * Gets data for the given uid of the file reference record.
     *
     * @param int $uid The uid of the file usage (sys_file_reference) to be fetched
     * @param bool $raw Whether to get raw results without performing overlays
     * @return array|null
     */
    protected function getFileReferenceData(int $uid, bool $raw = false): ?array
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$raw
            && $request instanceof ServerRequestInterface
            && ApplicationType::fromRequest($request)->isBackend()
        ) {
            $fileReferenceData = BackendUtility::getRecordWSOL('sys_file_reference', $uid);
        } elseif (!$raw
            && $request instanceof ServerRequestInterface
            && ApplicationType::fromRequest($request)->isFrontend()
        ) {
            $fileReferenceData = GeneralUtility::makeInstance(PageRepository::class)->checkRecord('sys_file_reference', $uid);
        } else {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_reference');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $fileReferenceData = $queryBuilder->select('*')
                ->from('sys_file_reference')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                    )
                )
                ->executeQuery()
                ->fetchAssociative();
        }
        return $fileReferenceData;
    }

    /**
     * Returns an instance of the FileIndexRepository
     *
     * @return FileIndexRepository
     */
    protected function getFileIndexRepository()
    {
        return GeneralUtility::makeInstance(FileIndexRepository::class);
    }

    protected function collectionCacheIdentifier(int $uid): string
    {
        return sprintf('resourcefactory-collection-%s', $uid);
    }

    /**
     * @return CollectionInterface<File>|null
     */
    protected function collectionCacheGet(int $uid): ?CollectionInterface
    {
        $entry = $this->runtimeCache->get($this->collectionCacheIdentifier($uid));
        if ($entry instanceof CollectionInterface) {
            return $entry;
        }
        return null;
    }

    /**
     * @param CollectionInterface<File> $collection
     */
    protected function collectionCacheSet(int $uid, CollectionInterface $collection): void
    {
        $this->runtimeCache->set($this->collectionCacheIdentifier($uid), $collection);
    }

    protected function fileCacheIdentifier(int $uid): string
    {
        return sprintf('resourcestorage-file-%s', $uid);
    }

    protected function fileCacheGet(int $uid): ?File
    {
        $entry = $this->runtimeCache->get($this->fileCacheIdentifier($uid));
        if ($entry instanceof File) {
            return $entry;
        }
        return null;
    }

    protected function fileCacheSet(File $file): void
    {
        $this->runtimeCache->set($this->fileCacheIdentifier($file->getUid()), $file);
    }

    protected function fileReferenceCacheIdentifier(int $uid): string
    {
        return sprintf('resourcestorage-filereference-%s', $uid);
    }

    protected function fileReferenceCacheGet(int $uid): ?FileReference
    {
        $entry = $this->runtimeCache->get($this->fileReferenceCacheIdentifier($uid));
        return ($entry instanceof FileReference) ? $entry : null;
    }

    protected function fileReferenceCacheSet(FileReference $fileReference): void
    {
        $this->runtimeCache->set($this->fileReferenceCacheIdentifier($fileReference->getUid()), $fileReference);
    }
}
