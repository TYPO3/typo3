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

use TYPO3\CMS\Form\Domain\DTO\FormData;
use TYPO3\CMS\Form\Domain\DTO\FormMetadata;
use TYPO3\CMS\Form\Domain\DTO\SearchCriteria;
use TYPO3\CMS\Form\Domain\ValueObject\FormIdentifier;

/**
 * Storage adapter interface for form persistence
 *
 * Storage adapters are responsible for reading, writing, and finding form definitions
 * from various storage backends (file mounts, extensions, database, etc.).
 *
 * Adapters are selected using the Chain of Responsibility pattern:
 * - Each adapter declares what identifiers it can handle via supports()
 * - Factory iterates through adapters by priority until one matches
 * - This allows third-party extensions to register custom storage adapters
 *
 * @internal
 */
interface StorageAdapterInterface
{
    /**
     * Get unique identifier for this storage type
     *
     * Used for metadata, display, and debugging purposes.
     * Examples: 'extension', 'filemount', 'database'
     *
     * @return string Unique type identifier (lowercase, alphanumeric + underscore)
     */
    public function getTypeIdentifier(): string;

    /**
     * Check if this adapter can handle the given persistence identifier
     *
     * @param string $identifier Persistence identifier (e.g., "EXT:my_ext/Forms/contact.form.yaml", "1:/forms/contact.form.yaml")
     * @return bool True if this adapter can handle the identifier
     */
    public function supports(string $identifier): bool;

    /**
     * Get priority for capability checking
     *
     * Higher priority adapters are checked first.
     * Allows extensions to override core adapters by providing higher priority.
     *
     * Suggested ranges:
     * - 0-49: Low priority / fallback adapters
     * - 50-99: Normal priority (file mounts, database)
     * - 100+: High priority (extension paths, specific handlers)
     *
     * @return int Priority (higher = checked first)
     */
    public function getPriority(): int;

    /**
     * Read form definition from storage
     *
     * @throws \TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException
     */
    public function read(FormIdentifier $identifier): FormData;

    /**
     * Write form definition to storage
     *
     * @throws \TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException
     */
    public function write(FormIdentifier $identifier, FormData $data): void;

    /**
     * Delete form definition from storage
     *
     * @throws \TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException
     */
    public function delete(FormIdentifier $identifier): void;

    /**
     * Check if form definition exists in storage
     */
    public function exists(FormIdentifier $identifier): bool;

    /**
     * Find all form definitions matching the search criteria
     *
     * @return array<FormMetadata>
     */
    public function findAll(SearchCriteria $criteria): array;
}
