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

namespace TYPO3\CMS\Core\Upgrades;

use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Localization\LanguagePackStatesStore;
use TYPO3\CMS\Core\Serializer\DenyListDeserializer;

/**
 * Migrates the `languagePacks` registry entries to JSON storage
 *
 * @since 14.3
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('languagePackUpdateTimestampMigration')]
final readonly class LanguagePackUpdateTimestampMigration implements UpgradeWizardInterface
{
    private const TABLE_NAME = 'sys_registry';

    public function __construct(
        private ConnectionPool $connectionPool,
        private DenyListDeserializer $deserializer,
        private LanguagePackStatesStore $languagePackStatesStore,
    ) {}

    public function getTitle(): string
    {
        return 'Migrate the "languagePacks" registry entries to JSON storage';
    }

    public function getDescription(): string
    {
        $count = $this->getRecordsToUpdateCount();
        $entryLabel = $count === 1 ? 'entry' : 'entries';

        return sprintf(
            'The timestamps indicating the last language pack update are now stored in a JSON file. '
            . 'This wizard migrates %d registry %s.',
            $count,
            $entryLabel
        );
    }

    public function updateNecessary(): bool
    {
        return $this->getRecordsToUpdateCount() > 0;
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function executeUpdate(): bool
    {
        $queryBuilder = $this->getPreparedQueryBuilder();
        $statement = $queryBuilder
            ->select('entry_namespace', 'entry_key', 'entry_value')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'entry_namespace',
                    $queryBuilder->createNamedParameter('languagePacks')
                )
            )
            ->executeQuery();

        // Batch all writes for this migration run into a single locked
        // read-modify-write cycle instead of one per registry entry.
        $this->languagePackStatesStore->withBatch(function () use ($statement): void {
            while ($row = $statement->fetchAssociative()) {
                if (str_contains($row['entry_key'], '-')) {
                    [$iso, $extension] = explode('-', $row['entry_key'], 2);
                } else {
                    $iso = $row['entry_key'];
                    $extension = null;
                }

                $fullPath = implode('/', array_filter([$iso, $extension, 'lastUpdate']));
                $lastUpdateInFile = $this->languagePackStatesStore->get($fullPath);
                if ($lastUpdateInFile !== null) {
                    // The file exists and an update timestamp is already stored, nothing to do for this record
                    continue;
                }

                $entryValue = $this->deserializer->deserialize($row['entry_value']);
                $this->languagePackStatesStore->set($fullPath, (int)$entryValue);
            }
        });

        $this->connectionPool->getConnectionForTable(self::TABLE_NAME)->delete(
            self::TABLE_NAME,
            ['entry_namespace' => 'languagePacks'],
            ['entry_namespace' => Connection::PARAM_STR],
        );

        return true;
    }

    private function getRecordsToUpdateCount(): int
    {
        $queryBuilder = $this->getPreparedQueryBuilder();
        return (int)$queryBuilder
            ->count('uid')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'entry_namespace',
                    $queryBuilder->createNamedParameter('languagePacks')
                )
            )
            ->executeQuery()
            ->fetchOne();
    }

    private function getPreparedQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder;
    }
}
