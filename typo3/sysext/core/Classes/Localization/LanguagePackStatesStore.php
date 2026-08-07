<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Localization;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireException;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException;
use TYPO3\CMS\Core\Locking\Exception\LockCreateException;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service class that handles the read/write of the states of the downloaded language packs.
 *
 * Reads and writes are serialized through the core locking API, so that
 * concurrent callers (e.g. parallel language pack downloads from the
 * backend UI and a CLI command) do not overwrite each other's changes.
 *
 * @internal This class is only meant to be used within EXT:core and is not part of the TYPO3 Core API.
 */
final class LanguagePackStatesStore
{
    private const string STORAGE_FILE = 'language_pack_states.json';
    private const string LOCK_ID = 'core:languagePackStatesStore';

    private array $data = [];
    private bool $loaded = false;
    private bool $batching = false;

    public function __construct(
        private readonly LockFactory $lockFactory,
    ) {}

    public function get(string $path): mixed
    {
        try {
            return ArrayUtility::getValueByPath($this->load(), $path);
        } catch (MissingArrayPathException) {
            return null;
        }
    }

    /**
     * @throws LockAcquireException
     * @throws LockAcquireWouldBlockException
     * @throws LockCreateException
     */
    public function set(string $path, mixed $input): array
    {
        if ($this->batching) {
            $this->data = ArrayUtility::setValueByPath($this->data, $path, $input);
            return $this->data;
        }

        $lock = $this->lockFactory->createLocker(self::LOCK_ID);
        $lock->acquire();
        try {
            $this->data = ArrayUtility::setValueByPath($this->readStorageFile(), $path, $input);
            $this->writeStorageFile($this->data);
        } finally {
            $lock->release();
        }
        $this->loaded = true;

        return $this->data;
    }

    /**
     * Runs $work() with a single locked read-modify-write cycle shared by
     * every set() call made from within it (directly, or indirectly through
     * e.g. LanguagePackService), instead of one cycle per call. Intended for
     * bulk operations such as updating many language packs in one request.
     *
     * Nested calls (a batch started while already inside one) join the
     * running batch instead of acquiring the lock again.
     *
     * @template T
     * @param callable(): T $work
     * @return T
     * @throws LockAcquireException
     * @throws LockAcquireWouldBlockException
     * @throws LockCreateException
     */
    public function withBatch(callable $work): mixed
    {
        if ($this->batching) {
            return $work();
        }

        $lock = $this->lockFactory->createLocker(self::LOCK_ID);
        $lock->acquire();
        $this->batching = true;
        try {
            $this->data = $this->readStorageFile();
            $this->loaded = true;
            return $work();
        } finally {
            $this->batching = false;
            $this->writeStorageFile($this->data);
            $lock->release();
        }
    }

    private function load(): array
    {
        if (!$this->loaded) {
            $lock = $this->lockFactory->createLocker(self::LOCK_ID);
            $lock->acquire();
            try {
                $this->data = $this->readStorageFile();
            } finally {
                $lock->release();
            }
            $this->loaded = true;
        }

        return $this->data;
    }

    private function readStorageFile(): array
    {
        $storageFile = self::getStorageFile();
        if (!is_readable($storageFile)) {
            return [];
        }
        $contents = file_get_contents($storageFile);
        return $contents !== '' ? json_decode($contents, true, 512, JSON_THROW_ON_ERROR) : [];
    }

    private function writeStorageFile(array $data): void
    {
        $storageFile = self::getStorageFile();
        if (!is_dir(dirname($storageFile))) {
            GeneralUtility::mkdir_deep(dirname($storageFile));
        }
        file_put_contents($storageFile, json_encode($data, JSON_THROW_ON_ERROR));
    }

    private static function getStorageFile(): string
    {
        return Environment::getLabelsPath() . '/' . self::STORAGE_FILE;
    }
}
