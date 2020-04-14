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
     * @var array|string[]
     */
    protected $extensionStorage = [];

    /**
     * Storage for extensions to be installed
     *
     * @var array
     */
    protected $extensionInstallStorage = [];

    /**
     * Storage for extensions to be copied
     *
     * @var array
     */
    protected $extensionCopyStorage = [];

    /**
     * Adds an extension to the download queue.
     * If the extension was already requested in a different version
     * an exception is thrown.
     *
     * @param Extension $extension
     * @param string $stack
     * @throws ExtensionManagerException
     */
    public function addExtensionToQueue(Extension $extension, $stack = 'download')
    {
        if (!is_string($stack) || !in_array($stack, ['download', 'update'])) {
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

    /**
     * @return array
     */
    public function getExtensionQueue()
    {
        return $this->extensionStorage;
    }

    /**
     * Remove an extension from download queue
     *
     * @param Extension $extension
     * @param string $stack Stack to remove extension from (download, update or install)
     * @throws ExtensionManagerException
     */
    public function removeExtensionFromQueue(Extension $extension, $stack = 'download')
    {
        if (!is_string($stack) || !in_array($stack, ['download', 'update'])) {
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
     *
     * @param Extension $extension
     */
    public function addExtensionToInstallQueue($extension)
    {
        $this->extensionInstallStorage[$extension->getExtensionKey()] = $extension;
    }

    /**
     * Adds an extension to the copy queue for later copying
     *
     * @param string $extensionKey
     * @param string $sourceFolder
     */
    public function addExtensionToCopyQueue($extensionKey, $sourceFolder)
    {
        $this->extensionCopyStorage[$extensionKey] = $sourceFolder;
    }

    /**
     * Remove an extension from extension copy storage
     *
     * @param string $extensionKey
     */
    public function removeExtensionFromCopyQueue($extensionKey)
    {
        if (array_key_exists($extensionKey, $this->extensionCopyStorage)) {
            unset($this->extensionCopyStorage[$extensionKey]);
        }
    }

    /**
     * Gets the extension installation queue
     *
     * @return array
     */
    public function getExtensionInstallStorage()
    {
        return $this->extensionInstallStorage;
    }

    /**
     * Return whether the queue contains extensions or not
     *
     * @param string $stack
     * @return bool
     */
    public function isQueueEmpty($stack = 'download')
    {
        return empty($this->extensionStorage[$stack]);
    }

    /**
     * Return whether the copy queue contains extensions or not
     *
     * @return bool
     */
    public function isCopyQueueEmpty()
    {
        return empty($this->extensionCopyStorage);
    }

    /**
     * Resets the extension queue and returns old extensions
     *
     * @param string|null $stack if null, all stacks are reset
     * @return array
     */
    public function resetExtensionQueue($stack = null)
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
     * Resets the copy queue and returns the old extensions
     * @return array
     */
    public function resetExtensionCopyStorage()
    {
        $storage = $this->extensionCopyStorage;
        $this->extensionCopyStorage = [];

        return $storage;
    }

    /**
     * Resets the install queue and returns the old extensions
     * @return array
     */
    public function resetExtensionInstallStorage()
    {
        $storage = $this->extensionInstallStorage;
        $this->extensionInstallStorage = [];

        return $storage;
    }
}
