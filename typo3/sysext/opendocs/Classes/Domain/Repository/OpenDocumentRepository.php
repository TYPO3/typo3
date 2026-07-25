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

namespace TYPO3\CMS\Opendocs\Domain\Repository;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Opendocs\Domain\Model\OpenDocument;

/**
 * Repository for managing recent documents for backend users.
 *
 * @internal
 */
final readonly class OpenDocumentRepository
{
    private const string MODULE_DATA_KEY_RECENT = 'opendocs::recent';
    private const int MAX_RECENT_DOCUMENTS = 8;

    /**
     * Find all recent documents for a user.
     *
     * @return array<string, OpenDocument> Indexed by document identifier (table:uid), limited to MAX_RECENT_DOCUMENTS
     */
    public function findForUser(BackendUserAuthentication $backendUser): array
    {
        $this->migrateLegacyFormatIfNeeded($backendUser);

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

        return $this->sortDocumentsByTime($documents);
    }

    /**
     * Add or update a document in the recent list.
     *
     * @param string $table Table name
     * @param int $uid Record UID
     */
    public function add(string $table, int $uid, BackendUserAuthentication $backendUser): void
    {
        $documents = $backendUser->getModuleData(self::MODULE_DATA_KEY_RECENT) ?? [];
        if (!is_array($documents)) {
            $documents = [];
        }

        $document = new OpenDocument(
            table: $table,
            uid: $uid,
            updatedAt: new \DateTimeImmutable(),
        );

        $identifier = $document->getIdentifier();
        $documents[$identifier] = $document->toArray();

        uasort($documents, static function (array $a, array $b): int {
            return ($b['updatedAt'] ?? '') <=> ($a['updatedAt'] ?? '');
        });

        if (count($documents) > self::MAX_RECENT_DOCUMENTS) {
            $documents = array_slice($documents, 0, self::MAX_RECENT_DOCUMENTS, true);
        }

        $backendUser->pushModuleData(self::MODULE_DATA_KEY_RECENT, $documents);
    }

    /**
     * Remove a document from the recent list.
     * Used when a record is deleted.
     *
     * @param string $identifier Document identifier (table:uid)
     */
    public function remove(string $identifier, BackendUserAuthentication $backendUser): void
    {
        $documents = $backendUser->getModuleData(self::MODULE_DATA_KEY_RECENT) ?? [];
        if (!is_array($documents)) {
            $documents = [];
        }

        unset($documents[$identifier]);

        $backendUser->pushModuleData(self::MODULE_DATA_KEY_RECENT, $documents);
    }

    /**
     * Sort recent documents by updatedAt timestamp in descending order (most recent first).
     *
     * @param array<string, OpenDocument> $documents
     * @return array<string, OpenDocument>
     */
    private function sortDocumentsByTime(array $documents): array
    {
        uasort($documents, static function (OpenDocument $a, OpenDocument $b): int {
            return $b->updatedAt <=> $a->updatedAt;
        });

        return $documents;
    }

    /**
     * Migrate recent documents from legacy hash-based format to the current identifier-based format.
     *
     * Legacy format stored values as numeric-indexed arrays (e.g. [0 => title, 1 => params, ...]).
     * Current format uses string-keyed arrays with 'table', 'uid', and 'updatedAt' keys.
     *
     * Each entry is evaluated individually: new-format entries are kept as-is (invalid ones dropped),
     * legacy entries are migrated, and any entry that cannot produce a valid table name is discarded.
     */
    private function migrateLegacyFormatIfNeeded(BackendUserAuthentication $user): void
    {
        $data = $user->getModuleData(self::MODULE_DATA_KEY_RECENT);
        if (!is_array($data) || $data === []) {
            return;
        }

        $processed = [];
        foreach ($data as $key => $entry) {
            if (!is_array($entry)) {
                continue;
            }

            if (isset($entry['table'])) {
                if ($entry['table'] === '') {
                    continue;
                }
                try {
                    $document = OpenDocument::fromArray($entry);
                    $normalized = $document->toArray();
                    $processed[$key] = $normalized;
                } catch (\Throwable) {
                    continue;
                }
            } else {
                try {
                    $document = OpenDocument::fromLegacyArray($entry);
                    if ($document->table === '') {
                        continue;
                    }
                    $processed[$document->getIdentifier()] = $document->toArray();
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        if ($processed !== $data) {
            $user->pushModuleData(self::MODULE_DATA_KEY_RECENT, $processed);
        }
    }
}
