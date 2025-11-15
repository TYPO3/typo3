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

namespace TYPO3\CMS\Form\Storage;

/**
 * Factory for finding storage adapters using Chain of Responsibility pattern
 *
 * This factory manages storage adapters and finds the appropriate adapter
 * for a given persistence identifier by asking each adapter if it can handle
 * the identifier (via supports() method).
 *
 * Adapters are checked in priority order (highest priority first), allowing
 * extensions to provide custom adapters that override core adapters.
 *
 * @internal
 */
final readonly class StorageAdapterFactory
{
    /**
     * @var list<StorageAdapterInterface>
     */
    private array $adapters;

    /**
     * @param iterable<StorageAdapterInterface> $adapters
     */
    public function __construct(iterable $adapters)
    {
        $this->adapters = $this->sortAdaptersByPriority($adapters);
    }

    /**
     * Get storage adapter that can handle the given persistence identifier
     *
     * Uses Chain of Responsibility pattern to find the first adapter
     * (in priority order) that supports the given identifier.
     *
     * @param string $identifier Persistence identifier (e.g., "EXT:my_ext/Forms/contact.form.yaml", "1:/forms/contact.form.yaml")
     * @throws \RuntimeException if no adapter can handle the identifier
     */
    public function getAdapterForIdentifier(string $identifier): StorageAdapterInterface
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($identifier)) {
                return $adapter;
            }
        }

        throw new \RuntimeException(
            sprintf(
                'No storage adapter found that can handle identifier "%s". Registered adapters: %s',
                $identifier,
                implode(', ', array_map(fn($a) => $a->getTypeIdentifier(), $this->adapters))
            ),
            1731672000
        );
    }

    /**
     * Get adapter by type identifier
     *
     * @param string $typeIdentifier Type identifier (e.g., 'extension', 'filemount')
     * @throws \InvalidArgumentException if no adapter with this type identifier exists
     */
    public function getAdapterByType(string $typeIdentifier): StorageAdapterInterface
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->getTypeIdentifier() === $typeIdentifier) {
                return $adapter;
            }
        }

        throw new \InvalidArgumentException(
            sprintf(
                'No storage adapter found with type identifier "%s". Available types: %s',
                $typeIdentifier,
                implode(', ', array_map(fn($a) => $a->getTypeIdentifier(), $this->adapters))
            ),
            1731672002
        );
    }

    /**
     * Check if an adapter with the given type identifier exists
     */
    public function hasAdapterType(string $typeIdentifier): bool
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->getTypeIdentifier() === $typeIdentifier) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all registered storage adapters
     *
     * @return list<StorageAdapterInterface>
     */
    public function getAllAdapters(): array
    {
        return $this->adapters;
    }

    /**
     * Get all registered storage type identifiers
     *
     * @return list<string>
     */
    public function getRegisteredTypeIdentifiers(): array
    {
        return array_map(
            fn(StorageAdapterInterface $adapter) => $adapter->getTypeIdentifier(),
            $this->adapters
        );
    }

    /**
     * Sort adapters by priority (highest first)
     *
     * @param iterable<StorageAdapterInterface> $adapters
     * @return list<StorageAdapterInterface>
     */
    private function sortAdaptersByPriority(iterable $adapters): array
    {
        $sortedAdapters = [...$adapters];

        usort(
            $sortedAdapters,
            fn(StorageAdapterInterface $a, StorageAdapterInterface $b) => $b->getPriority() <=> $a->getPriority()
        );

        return $sortedAdapters;
    }
}
