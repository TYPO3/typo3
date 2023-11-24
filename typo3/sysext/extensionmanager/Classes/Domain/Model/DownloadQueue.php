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

namespace TYPO3\CMS\Extensionmanager\Domain\Model;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;

/**
 * Download Queue - storage for extensions to be downloaded
 * @internal This class is a specific domain model implementation and is not part of the Public TYPO3 API.
 */
class DownloadQueue implements SingletonInterface
{
    /**
     * Storage for extensions to be downloaded
     *
     * @var array<string, array<string>>
     */
    protected array $extensionStorage = [];

    /**
     * Storage for extensions to be installed
     */
    protected array $extensionInstallStorage = [];

    /**
     * Adds an extension to the download queue.
     * If the extension was already requested in a different version
     * an exception is thrown.
     */
    public function addExtensionToQueue(Extension $extension, string $stack = 'download'): void
    {
        if (!in_array($stack, ['download', 'update'])) {
            throw new ExtensionManagerException('Stack has to be either "download" or "update"', 1342432103);
        }
        if (!isset($this->extensionStorage[$stack])) {
            $this->extensionStorage[$stack] = [];
        }
        if (array_key_exists($extension->getExtensionKey(), $this->extensionStorage[$stack])) {
            if ($this->extensionStorage[$stack][$extension->getExtensionKey()] !== $extension) {
                throw new ExtensionManagerException(
                    $extension->getExtensionKey() . ' was requested to be downloaded in different versions (' . $extension->getVersion()
                        . ' and ' . $this->extensionStorage[$stack][$extension->getExtensionKey()]->getVersion() . ').',
                    1342432101
                );
            }
        }
        $this->extensionStorage[$stack][$extension->getExtensionKey()] = $extension;
    }

    public function getExtensionQueue(): array
    {
        return $this->extensionStorage;
    }

    /**
     * Remove an extension from download queue
     *
     * @param string $stack Stack to remove extension from (download or update)
     */
    public function removeExtensionFromQueue(Extension $extension, string $stack = 'download'): void
    {
        if (!in_array($stack, ['download', 'update'])) {
            throw new ExtensionManagerException('Stack has to be either "download" or "update"', 1342432104);
        }
        if (array_key_exists($stack, $this->extensionStorage) && is_array($this->extensionStorage[$stack])) {
            if (array_key_exists($extension->getExtensionKey(), $this->extensionStorage[$stack])) {
                unset($this->extensionStorage[$stack][$extension->getExtensionKey()]);
            }
        }
    }

    /**
     * Adds an extension to the install queue for later installation
     */
    public function addExtensionToInstallQueue(Extension $extension): void
    {
        $this->extensionInstallStorage[$extension->getExtensionKey()] = $extension;
    }

    /**
     * Gets the extension installation queue
     */
    public function getExtensionInstallStorage(): array
    {
        return $this->extensionInstallStorage;
    }

    /**
     * Return whether the queue contains extensions or not
     *
     * @param string $stack either "download" or "update"
     * @return bool
     */
    public function isQueueEmpty(string $stack): bool
    {
        return empty($this->extensionStorage[$stack]);
    }

    /**
     * Resets the extension queue and returns old extensions
     *
     * @param string|null $stack if null, all stacks are reset
     */
    public function resetExtensionQueue(?string $stack = null): array
    {
        $storage = [];
        if ($stack === null) {
            $storage = $this->extensionStorage;
            $this->extensionStorage = [];
        } elseif (isset($this->extensionStorage[$stack])) {
            $storage = $this->extensionStorage[$stack];
            $this->extensionStorage[$stack] = [];
        }

        return $storage;
    }

    /**
     * Resets the install queue and returns the old extensions
     */
    public function resetExtensionInstallStorage(): array
    {
        $storage = $this->extensionInstallStorage;
        $this->extensionInstallStorage = [];

        return $storage;
    }
}
