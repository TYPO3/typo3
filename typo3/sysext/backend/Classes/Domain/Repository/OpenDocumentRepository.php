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

namespace TYPO3\CMS\Backend\Domain\Repository;

use TYPO3\CMS\Backend\Domain\Model\OpenDocument;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Repository for managing open and recent documents for backend users.
 *
 * @internal
 */
final readonly class OpenDocumentRepository
{
    private const MODULE_DATA_KEY_OPEN = 'opendocs::open';
    private const MODULE_DATA_KEY_RECENT = 'opendocs::recent';
    private const MAX_RECENT_DOCUMENTS = 8;

    /**
     * Find all open documents for a user.
     *
     * @return array<string, OpenDocument> Indexed by document identifier (table:uid)
     */
    public function findOpenDocumentsForUser(BackendUserAuthentication $user): array
    {
        // Try to migrate from legacy format if needed
        $this->migrateLegacyFormatIfNeeded($user);

        $data = $user->getModuleData(self::MODULE_DATA_KEY_OPEN) ?? [];
        if (!is_array($data)) {
            return [];
        }

        $documents = [];
        foreach ($data as $identifier => $serializedDocument) {
            if (!is_array($serializedDocument)) {
                continue;
            }
            try {
                $doc = OpenDocument::fromArray($serializedDocument);
                if ($doc->table) {
                    $documents[$identifier] = $doc;
                }
            } catch (\Throwable) {
                // Skip malformed documents
                continue;
            }
        }

        return $documents;
    }

    /**
     * Find all recent documents for a user.
     *
     * @return array<string, OpenDocument> Indexed by document identifier (table:uid), limited to MAX_RECENT_DOCUMENTS
     */
    public function findRecentDocumentsForUser(BackendUserAuthentication $backendUser): array
    {
        $data = $backendUser->getModuleData(self::MODULE_DATA_KEY_RECENT) ?? [];
        if (!is_array($data)) {
            return [];
        }

        $documents = [];
        $count = 0;
        foreach ($data as $identifier => $serializedDocument) {
            if ($count >= self::MAX_RECENT_DOCUMENTS) {
                break;
            }
            if (!is_array($serializedDocument)) {
                continue;
            }
            try {
                $documents[$identifier] = OpenDocument::fromArray($serializedDocument);
                $count++;
            } catch (\Throwable) {
                // Skip malformed documents
                continue;
            }
        }

        return $documents;
    }

    /**
     * Add or update an open document.
     *
     * If the document already exists, it updates it. Otherwise, creates a new entry.
     *
     * @param OpenDocument $document Document to add/update
     */
    public function addOrUpdateOpenDocument(OpenDocument $document, BackendUserAuthentication $backendUser): void
    {
        $openDocuments = $backendUser->getModuleData(self::MODULE_DATA_KEY_OPEN) ?? [];
        if (!is_array($openDocuments)) {
            $openDocuments = [];
        }

        // Store document indexed by identifier
        $openDocuments[$document->getIdentifier()] = $document->toArray();

        $backendUser->pushModuleData(self::MODULE_DATA_KEY_OPEN, $openDocuments);
    }

    /**
     * Close an open document and change its status to recent.
     *
     * @param string $table Table name
     * @param string $uid Record UID
     */
    public function closeDocument(string $table, string $uid, BackendUserAuthentication $backendUser): void
    {
        $identifier = $table . ':' . $uid;

        // Get open and recent documents
        $openDocuments = $backendUser->getModuleData(self::MODULE_DATA_KEY_OPEN) ?? [];
        $recentDocuments = $backendUser->getModuleData(self::MODULE_DATA_KEY_RECENT) ?? [];

        if (!is_array($openDocuments)) {
            $openDocuments = [];
        }
        if (!is_array($recentDocuments)) {
            $recentDocuments = [];
        }

        // If document is open, move it to recent
        if (isset($openDocuments[$identifier])) {
            // Add to beginning of recent documents
            $recentDocuments = [$identifier => $openDocuments[$identifier]] + $recentDocuments;

            // Limit recent documents
            if (count($recentDocuments) > self::MAX_RECENT_DOCUMENTS) {
                $recentDocuments = array_slice($recentDocuments, 0, self::MAX_RECENT_DOCUMENTS, true);
            }

            // Remove from open documents
            unset($openDocuments[$identifier]);

            // Save both
            $backendUser->pushModuleData(self::MODULE_DATA_KEY_OPEN, $openDocuments);
            $backendUser->pushModuleData(self::MODULE_DATA_KEY_RECENT, $recentDocuments);
        }
    }

    /**
     * Close all open documents for a user and change their status to recent.
     */
    public function closeAllDocuments(BackendUserAuthentication $backendUser): void
    {
        $openDocuments = $backendUser->getModuleData(self::MODULE_DATA_KEY_OPEN) ?? [];
        $recentDocuments = $backendUser->getModuleData(self::MODULE_DATA_KEY_RECENT) ?? [];

        if (!is_array($openDocuments)) {
            $openDocuments = [];
        }
        if (!is_array($recentDocuments)) {
            $recentDocuments = [];
        }

        // Move all open documents to recent
        $recentDocuments = $openDocuments + $recentDocuments;

        // Limit recent documents
        if (count($recentDocuments) > self::MAX_RECENT_DOCUMENTS) {
            $recentDocuments = array_slice($recentDocuments, 0, self::MAX_RECENT_DOCUMENTS, true);
        }

        // Clear open documents
        $backendUser->pushModuleData(self::MODULE_DATA_KEY_OPEN, []);
        $backendUser->pushModuleData(self::MODULE_DATA_KEY_RECENT, $recentDocuments);
    }

    /**
     * Migrate from legacy hash-based format to new identifier-based format.
     *
     * Legacy format stored in 'FormEngine' and 'opendocs::recent' with MD5 hashes.
     * New format uses 'opendocs::open' and 'opendocs::recent' with table:uid identifiers.
     */
    private function migrateLegacyFormatIfNeeded(BackendUserAuthentication $user): void
    {
        // Check if already using new format
        $newFormatData = $user->getModuleData(self::MODULE_DATA_KEY_OPEN);
        if ($newFormatData !== null) {
            // Already migrated or using new format
            return;
        }

        // Read legacy format from 'FormEngine' session key
        $legacyData = $user->getModuleData('FormEngine', 'ses');
        if (!is_array($legacyData) || !isset($legacyData[0]) || !is_array($legacyData[0])) {
            // No legacy data to migrate
            return;
        }

        $legacyOpenDocs = $legacyData[0]; // docHandler array
        $migratedOpen = [];

        // Migrate open documents
        foreach ($legacyOpenDocs as $legacyDocument) {
            if (!is_array($legacyDocument)) {
                continue;
            }

            try {
                // Legacy format: [0 => title, 1 => params, 2 => queryString, 3 => metadata, 4 => returnUrl]
                $document = OpenDocument::fromLegacyArray($legacyDocument);
                $migratedOpen[$document->getIdentifier()] = $document->toArray();
            } catch (\Throwable) {
                // Skip malformed documents
                continue;
            }
        }

        // Migrate recent documents (already in 'opendocs::recent' but with old structure)
        $legacyRecentDocs = $user->getModuleData('opendocs::recent');
        $migratedRecent = [];

        if (is_array($legacyRecentDocs)) {
            foreach ($legacyRecentDocs as $legacyDocument) {
                if (!is_array($legacyDocument)) {
                    continue;
                }

                try {
                    $document = OpenDocument::fromLegacyArray($legacyDocument);
                    $migratedRecent[$document->getIdentifier()] = $document->toArray();
                } catch (\Throwable) {
                    // Skip malformed documents
                    continue;
                }
            }
        }

        // Save migrated data in new format
        if ($migratedOpen !== []) {
            $user->pushModuleData(self::MODULE_DATA_KEY_OPEN, $migratedOpen);
        }
        if ($migratedRecent !== []) {
            $user->pushModuleData(self::MODULE_DATA_KEY_RECENT, $migratedRecent);
        }
    }
}
