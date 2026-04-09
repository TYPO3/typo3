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
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Form\Domain\DTO\FormData;
use TYPO3\CMS\Form\Domain\DTO\FormMetadata;
use TYPO3\CMS\Form\Domain\DTO\SearchCriteria;
use TYPO3\CMS\Form\Domain\DTO\StorageContext;
use TYPO3\CMS\Form\Domain\Repository\FormDefinitionRepository;
use TYPO3\CMS\Form\Domain\ValueObject\FormIdentifier;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Storage\Permission\DatabasePermissionChecker;

/**
 * Storage adapter for database-based form persistence
 *
 * Scope: frontend / backend
 * @internal
 */
final readonly class DatabaseStorageAdapter implements StorageAdapterInterface
{
    public function __construct(
        private FormDefinitionRepository $repository,
        private DatabasePermissionChecker $permissionChecker,
    ) {}

    public function getTypeIdentifier(): string
    {
        return 'database';
    }

    public function supports(string $identifier): bool
    {
        return str_starts_with($identifier, 'NEW') || MathUtility::canBeInterpretedAsInteger($identifier);
    }

    public function getPriority(): int
    {
        return 100;
    }

    public function getLabel(): string
    {
        return 'formManager.storage.database.label';
    }

    public function getDescription(): string
    {
        return 'formManager.storage.database.description';
    }

    public function getIconIdentifier(): string
    {
        return 'content-database';
    }

    public function getUniquePersistenceIdentifier(string $formIdentifier, string $storageLocation): string
    {
        return StringUtility::getUniqueId('NEW');
    }

    /**
     * @throws PersistenceManagerException
     */
    public function read(FormIdentifier $identifier, ?ServerRequestInterface $request = null): FormData
    {
        $uid = $this->extractUidFromIdentifier($identifier);

        $record = $this->repository->findByUid($uid);
        if (!$record) {
            throw new PersistenceManagerException(
                sprintf('The form with uid "%s" could not be loaded.', $uid),
                1767199422
            );
        }

        $applicationType = $request !== null ? ApplicationType::fromRequest($request) : null;
        // Skip permission checks in frontend context: Forms must be readable without a
        // backend user session, so no backend permission checks are applied for frontend
        // requests. In all other contexts (e.g. backend), permission checks are enforced.
        if (!$applicationType?->isFrontend()) {
            $this->permissionChecker->assertReadAccessForRecord($uid, $record);
        }

        try {
            $formDefinitionArray = json_decode($record['configuration'] ?? '', true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new PersistenceManagerException(
                sprintf('The form definition for uid "%s" is invalid: %s', $uid, $e->getMessage()),
                1767199423,
                $e
            );
        }

        if (!is_array($formDefinitionArray)) {
            throw new PersistenceManagerException(
                sprintf('The form definition for uid "%s" is invalid.', $uid),
                1767199444
            );
        }

        $formDefinitionArray['identifier'] = $record['identifier'];

        return FormData::fromArray($formDefinitionArray);
    }

    /**
     * @throws PersistenceManagerException
     */
    public function write(FormIdentifier $identifier, FormData $data, ?StorageContext $context = null): FormIdentifier
    {
        if (!$this->exists($identifier)) {
            $pid = 0;

            if (!$this->permissionChecker->hasWritePermission($pid)) {
                throw new PersistenceManagerException(
                    'Access denied: You do not have permission to create a form.',
                    1767199435
                );
            }

            $uid = $this->repository->add($identifier->identifier, $pid, $data);

            if (!$uid) {
                throw new PersistenceManagerException(
                    'Failed to create form definition in database.',
                    1767199424
                );
            }

            return new FormIdentifier((string)$uid);
        }

        $uid = $this->extractUidFromIdentifier($identifier);

        $record = $this->repository->findByUid($uid);
        if (!$record) {
            throw new PersistenceManagerException(
                sprintf('The form with uid "%s" could not be found.', $uid),
                1767199425
            );
        }

        $this->permissionChecker->assertWriteAccessForRecord($uid, $record);

        $result = $this->repository->update($uid, $data);

        if (!$result) {
            throw new PersistenceManagerException(
                sprintf('Failed to update form definition with uid "%s".', $uid),
                1767199426
            );
        }

        return $identifier;
    }

    /**
     * @throws PersistenceManagerException
     */
    public function delete(FormIdentifier $identifier): void
    {
        $uid = $this->extractUidFromIdentifier($identifier);

        $record = $this->repository->findByUid($uid);
        if (!$record) {
            throw new PersistenceManagerException(
                sprintf('The form with uid "%s" could not be found.', $uid),
                1767199431
            );
        }

        $this->permissionChecker->assertWriteAccessForRecord($uid, $record);

        $success = $this->repository->remove($uid);

        if (!$success) {
            throw new PersistenceManagerException(
                sprintf('Failed to delete form definition with uid "%s".', $uid),
                1767199427
            );
        }
    }

    /**
     * @throws PersistenceManagerException
     */
    public function exists(FormIdentifier $identifier): bool
    {
        if (str_starts_with($identifier->identifier, 'NEW')) {
            return false;
        }

        $uid = $this->extractUidFromIdentifier($identifier);
        $record = $this->repository->findByUid($uid);

        if ($record === null) {
            return false;
        }

        $pid = (int)($record['pid'] ?? -1);
        return $this->permissionChecker->hasReadPermission($pid);
    }

    public function existsByFormIdentifier(string $formIdentifier): bool
    {
        return $this->repository->existsByFormIdentifier($formIdentifier);
    }

    /**
     * Find all form definitions for listing.
     *
     * Uses findAllForListing() which only selects metadata columns (uid, pid, identifier, label)
     * instead of the full configuration JSON. This avoids loading and parsing potentially large
     * JSON blobs just for the form listing view.
     */
    public function findAll(SearchCriteria $criteria): array
    {
        $rows = $this->repository->findAllForListing($criteria);

        $results = [];
        foreach ($rows as $row) {
            if ($row['uid'] === null) {
                continue;
            }

            $pageId = (int)($row['pid'] ?? 0);
            $uid = (int)$row['uid'];

            if (!$this->permissionChecker->hasReadPermission($pageId)) {
                continue;
            }

            $persistenceIdentifier = (string)$uid;

            $hasWritePermission = $this->permissionChecker->hasWritePermission($pageId);
            $metadata = new FormMetadata(
                identifier: $row['identifier'] ?? '',
                type: 'Form',
                name: $row['label'] ?? $row['identifier'] ?? '',
                prototypeName: 'standard',
                persistenceIdentifier: $persistenceIdentifier,
                readOnly: !$hasWritePermission,
                removable: $hasWritePermission,
                fileUid: null,
                storageLocation: $this->getStorageLocationLabel(),
            );

            $results[] = $metadata;
        }

        return $results;
    }

    public function getFormManagerOptions(): array
    {
        if (!$this->permissionChecker->hasWritePermission(0)) {
            return [];
        }

        return [
            'allowedStorageLocations' => [
                [
                    'value' => '0',
                    'label' => $this->getStorageLocationLabel(),
                ],
            ],
        ];
    }

    public function isAccessible(): bool
    {
        return $this->permissionChecker->hasWritePermission(0);
    }

    public function isAllowedStorageLocation(string $storageLocation): bool
    {
        if (MathUtility::canBeInterpretedAsInteger($storageLocation)) {
            return (int)$storageLocation === 0;
        }

        return false;
    }

    public function isAllowedPersistenceIdentifier(string $persistenceIdentifier): bool
    {
        if (str_starts_with($persistenceIdentifier, 'NEW')) {
            return true;
        }

        if (!MathUtility::canBeInterpretedAsInteger($persistenceIdentifier)) {
            return false;
        }

        if (!$this->isAccessible()) {
            return false;
        }

        $uid = (int)$persistenceIdentifier;
        $record = $this->repository->findByUid($uid);

        return $record !== null;
    }

    /**
     * @throws PersistenceManagerException
     */
    private function extractUidFromIdentifier(FormIdentifier $identifier): int
    {
        if (!MathUtility::canBeInterpretedAsInteger($identifier->identifier)) {
            throw new PersistenceManagerException(
                sprintf('Invalid database identifier "%s". Expected numeric UID.', $identifier->identifier),
                1767199428
            );
        }

        return (int)$identifier->identifier;
    }

    private function getStorageLocationLabel(): string
    {
        $languageService = $this->getLanguageService();
        return $languageService?->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:' . $this->getLabel()) ?: 'Database';
    }

    private function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
