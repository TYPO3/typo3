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
use TYPO3\CMS\Core\SystemResource\Exception\SystemResourceException;
use TYPO3\CMS\Core\SystemResource\Publishing\UriGenerationOptions;
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
        private FileIndexRepository $fileIndexRepository,
    ) {}

    /**
     * Creates an instance of the collection from given UID. The $recordData can be supplied to increase performance.
     *
     * @param int $uid The uid of the collection to instantiate.
     * @param array $recordData The record row from database.
     *
     * @throws \InvalidArgumentException
     */
    public function getCollectionObject(int $uid, array $recordData = []): CollectionInterface
    {
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
    public function createCollectionObject(array $collectionData): CollectionInterface
    {
        $registry = GeneralUtility::makeInstance(FileCollectionRegistry::class);

        /** @var AbstractRecordCollection $class */
        $class = $registry->getFileCollectionClass($collectionData['type']);

        return $class::create($collectionData);
    }

    /**
     * Creates an instance of the file given UID. The $fileData can be supplied
     * to increase performance.
     *
     * @param int|string $uid The uid of the file to instantiate. (string is used for the time being as compat-mode)
     * @param array $fileData The record row from database.
     *
     * @throws \InvalidArgumentException
     * @throws Exception\FileDoesNotExistException
     */
    public function getFileObject(int|string $uid, array $fileData = []): File
    {
        $uid = (int)$uid;
        $fileObject = $this->fileCacheGet($uid);
        if ($fileObject === null) {
            // Fetches data in case $fileData is empty
            if (empty($fileData)) {
                $fileData = $this->fileIndexRepository->findOneByUid($uid);
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
     * @throws \InvalidArgumentException
     */
    public function getFileObjectFromCombinedIdentifier(string $identifier): File|ProcessedFile|null
    {
        if ($identifier === '') {
            throw new \InvalidArgumentException('Invalid file identifier given. It must be not empty.', 1401732564);
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
     */
    public function retrieveFileOrFolderObject(string|int $input): ProcessedFile|File|Folder|null
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
                try {
                    // @todo: We make an "URL" relative to public dir because the fallback storage root
                    //        is the public dir and in this case file identifier === url
                    //        this will be resolved once fallback storage is deprecated
                    //        This should be done asap, because other implementations of SystemResourcePublisherInterface
                    //        might not evaluate the uriPrefix options
                    $potentialPathRelativeToPublicDir = (string)PathUtility::getSystemResourceUri($input, null, new UriGenerationOptions(uriPrefix: '', cacheBusting: false));
                    if (!file_exists(Environment::getPublicPath() . $potentialPathRelativeToPublicDir)) {
                        throw new ResourceDoesNotExistException(sprintf('File "%s" does not exist in fallback compatibility storage.', $input), 1760532790);
                    }
                    return $this->getFileObjectFromCombinedIdentifier($potentialPathRelativeToPublicDir);
                } catch (SystemResourceException $e) {
                    throw new ResourceDoesNotExistException(sprintf('Tried to access a private resource file "%s" from fallback compatibility storage. This storage only handles public files.', $input), 1633777536, $e);
                }
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
     */
    public function getFolderObjectFromCombinedIdentifier(string|int $identifier): Folder
    {
        $parts = GeneralUtility::trimExplode(':', (string)$identifier);
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
     * @throws Exception\ResourceDoesNotExistException
     */
    public function getObjectFromCombinedIdentifier(string $identifier): FileInterface|Folder
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
     */
    public function createFileObject(array $fileData, ?ResourceStorage $storage = null): File
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

        return GeneralUtility::makeInstance(File::class, $fileData, $storageObject);
    }

    /**
     * Creates an instance of a FileReference object. The $fileReferenceData can
     * be supplied to increase performance.
     *
     * @param int|string $uid The uid of the file usage (sys_file_reference) to instantiate. string is kept for backwards-compat
     * @param array $fileReferenceData The record row from database.
     * @param bool $raw Whether to get raw results without performing overlays
     * @throws Exception\ResourceDoesNotExistException
     */
    public function getFileReferenceObject(int|string $uid, array $fileReferenceData = [], bool $raw = false): FileReference
    {
        $uid = (int)$uid;
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
     */
    protected function getFileReferenceData(int $uid, bool $raw = false): array|false|null
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
