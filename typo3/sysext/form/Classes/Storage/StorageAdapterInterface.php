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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Form\Domain\DTO\FormData;
use TYPO3\CMS\Form\Domain\DTO\FormMetadata;
use TYPO3\CMS\Form\Domain\DTO\SearchCriteria;
use TYPO3\CMS\Form\Domain\DTO\StorageContext;
use TYPO3\CMS\Form\Domain\ValueObject\FormIdentifier;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\NoUniquePersistenceIdentifierException;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;

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
     * Get the human-readable label for this storage type
     *
     * @return string translation key
     */
    public function getLabel(): string;

    /**
     * Get the description for this storage type
     *
     * @return string translation key
     */
    public function getDescription(): string;

    /**
     * Get the icon identifier for this storage type
     *
     * @return string icon identifier
     */
    public function getIconIdentifier(): string;

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
     * @param string $identifier Persistence identifier (e.g., "EXT:my_extension/Forms/contact.form.yaml", "1:/forms/contact.form.yaml")
     * @return bool True if this adapter can handle the identifier
     */
    public function supports(string $identifier): bool;

    /**
     * Get options for the form manager interface
     */
    public function getFormManagerOptions(): array;

    /**
     * Check if this storage is currently accessible
     */
    public function isAccessible(): bool;

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
    public function read(FormIdentifier $identifier, ?ServerRequestInterface $request = null): FormData;

    /**
     * Write form definition to storage
     *
     * @param StorageContext|null $context Additional storage context (e.g., PID for database storage)
     * @return FormIdentifier The identifier of the saved form (might differ for new forms in database storage)
     * @throws PersistenceManagerException
     */
    public function write(FormIdentifier $identifier, FormData $data, ?StorageContext $context = null): FormIdentifier;

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
     * Check if a form with the given form identifier (not persistence identifier) exists
     *
     * This is used for efficient duplicate identifier checking without loading all forms.
     * The form identifier is the logical name (e.g., "contact-form"), not the persistence
     * identifier (e.g., UID or file path).
     *
     * @param string $formIdentifier The form identifier to check (e.g., "contact-form")
     * @return bool True if a form with this identifier exists in this storage
     */
    public function existsByFormIdentifier(string $formIdentifier): bool;

    /**
     * Find all form definitions matching the search criteria
     *
     * @return array<FormMetadata>
     */
    public function findAll(SearchCriteria $criteria): array;

    /**
     * Get unique persistence identifier for a new form in this storage
     *
     * @param string $formIdentifier The form identifier (e.g., "contact-form")
     * @param string $storageLocation The save path (e.g., "1:/forms/" for filemount, pid for database)
     * @return string Unique persistence identifier
     * @throws NoUniquePersistenceIdentifierException
     */
    public function getUniquePersistenceIdentifier(string $formIdentifier, string $storageLocation): string;

    /**
     * Check if a storage location is allowed for this adapter
     *
     * For database storage: storageLocation is a PID
     * For file storage: storageLocation is a folder path (e.g., "1:/forms/")
     *
     * @param string $storageLocation The storage location to check
     * @return bool True if the storage location is allowed
     */
    public function isAllowedStorageLocation(string $storageLocation): bool;

    /**
     * Check if a persistence identifier is allowed for this adapter
     *
     * For database storage: identifier is a UID or NEW*
     * For file storage: identifier is a full file path (e.g., "1:/forms/contact.form.yaml")
     *
     * @param string $persistenceIdentifier The persistence identifier to check
     * @return bool True if the persistence identifier is allowed
     */
    public function isAllowedPersistenceIdentifier(string $persistenceIdentifier): bool;
}
