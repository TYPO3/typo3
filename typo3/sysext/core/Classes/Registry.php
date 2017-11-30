<?php
namespace TYPO3\CMS\Core;

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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A class to store and retrieve entries in a registry database table.
 *
 * This is a simple, persistent key-value-pair store.
 *
 * The intention is to have a place where we can store things (mainly settings)
 * that should live for more than one request, longer than a session, and that
 * shouldn't expire like it would with a cache. You can actually think of it
 * being like the Windows Registry in some ways.
 */
class Registry implements SingletonInterface
{
    /**
     * @var array
     */
    protected $entries = [];

    /**
     * @var array
     */
    protected $loadedNamespaces = [];

    /**
     * Returns a persistent entry.
     *
     * @param string $namespace Extension key of extension
     * @param string $key Key of the entry to return.
     * @param mixed $defaultValue Optional default value to use if this entry has never been set. Defaults to NULL.
     * @return mixed Value of the entry.
     * @throws \InvalidArgumentException Throws an exception if the given namespace is not valid
     */
    public function get($namespace, $key, $defaultValue = null)
    {
        $this->validateNamespace($namespace);
        if (!$this->isNamespaceLoaded($namespace)) {
            $this->loadEntriesByNamespace($namespace);
        }
        return $this->entries[$namespace][$key] ?? $defaultValue;
    }

    /**
     * Sets a persistent entry.
     *
     * This is the main method that can be used to store a key-value-pair.
     *
     * Do not store binary data into the registry, it's not build to do that,
     * instead use the proper way to store binary data: The filesystem.
     *
     * @param string $namespace Extension key of extension
     * @param string $key The key of the entry to set.
     * @param mixed $value The value to set. This can be any PHP data type; This class takes care of serialization
     * @throws \InvalidArgumentException Throws an exception if the given namespace is not valid
     */
    public function set($namespace, $key, $value)
    {
        $this->validateNamespace($namespace);
        if (!$this->isNamespaceLoaded($namespace)) {
            $this->loadEntriesByNamespace($namespace);
        }
        $serializedValue = serialize($value);
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_registry');
        $rowCount = $connection->count(
            '*',
            'sys_registry',
            ['entry_namespace' => $namespace, 'entry_key' => $key]
        );
        if ((int)$rowCount < 1) {
            $connection->insert(
                'sys_registry',
                ['entry_namespace' => $namespace, 'entry_key' => $key, 'entry_value' => $serializedValue],
                ['entry_value' => Connection::PARAM_LOB]
            );
        } else {
            $connection->update(
                'sys_registry',
                ['entry_value' => $serializedValue],
                ['entry_namespace' => $namespace, 'entry_key' => $key],
                ['entry_value' => Connection::PARAM_LOB]
            );
        }
        $this->entries[$namespace][$key] = $value;
    }

    /**
     * Unset a persistent entry.
     *
     * @param string $namespace Extension key of extension
     * @param string $key The key of the entry to unset.
     * @throws \InvalidArgumentException Throws an exception if the given namespace is not valid
     */
    public function remove($namespace, $key)
    {
        $this->validateNamespace($namespace);
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_registry')
            ->delete(
                'sys_registry',
                ['entry_namespace' => $namespace, 'entry_key' => $key]
            );
        unset($this->entries[$namespace][$key]);
    }

    /**
     * Unset all persistent entries of given namespace.
     *
     * @param string $namespace Extension key of extension
     * @throws \InvalidArgumentException Throws an exception if given namespace is invalid
     */
    public function removeAllByNamespace($namespace)
    {
        $this->validateNamespace($namespace);
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_registry')
            ->delete(
                'sys_registry',
                ['entry_namespace' => $namespace]
            );
        unset($this->entries[$namespace]);
    }

    /**
     * check if the given namespace is loaded
     *
     * @param string $namespace Extension key of extension
     * @return bool True if namespace was loaded already
     */
    protected function isNamespaceLoaded($namespace)
    {
        return isset($this->loadedNamespaces[$namespace]);
    }

    /**
     * Loads all entries of given namespace into the internal $entries cache.
     *
     * @param string $namespace Extension key of extension
     * @throws \InvalidArgumentException Thrown if given namespace is invalid
     */
    protected function loadEntriesByNamespace($namespace)
    {
        $this->validateNamespace($namespace);
        $this->entries[$namespace] = [];
        $result = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_registry')
            ->select(
                ['entry_key', 'entry_value'],
                'sys_registry',
                ['entry_namespace' => $namespace]
            );
        while ($row = $result->fetch()) {
            $this->entries[$namespace][$row['entry_key']] = unserialize($row['entry_value']);
        }
        $this->loadedNamespaces[$namespace] = true;
    }

    /**
     * Check namespace key
     * It must be at least two characters long. The word 'core' is reserved for TYPO3 core usage.
     *
     * @param string $namespace Namespace
     * @throws \InvalidArgumentException Thrown if given namespace is invalid
     */
    protected function validateNamespace($namespace)
    {
        if (strlen($namespace) < 2) {
            throw new \InvalidArgumentException('Given namespace must be longer than two characters.', 1249755131);
        }
    }
}
