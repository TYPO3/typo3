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

namespace TYPO3\CMS\Install\Updates;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\MimeTypeCompatibilityTypeGuesser;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;

/**
 * @since 12.4
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('sysFileMimeTypeMigration')]
class SysFileMimeTypeMigration implements UpgradeWizardInterface, RepeatableInterface
{
    private const TABLE_NAME = 'sys_file';

    public function __construct(
        private readonly ConnectionPool $connectionPool
    ) {}

    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return 'Update "mime_type" field to extension specific types';
    }

    /**
     * @return string Longer description of this updater
     * @throws \RuntimeException
     */
    public function getDescription(): string
    {
        $count = 0;
        $description = '';
        foreach ($this->getMimeTypesThatNeedUpdate() as $info) {
            $count += $info['count'];
            $description .= LF . sprintf(
                '*.%s: %s => %s (affects %d rows)',
                $info['extension'],
                $info['currentMimeType'],
                $info['newMimeType'],
                $info['count'],
            );
        }

        return sprintf(
            'Update %d records to set proper "mime_type":%s',
            $count,
            $description
        );
    }

    /**
     * @return list<array{count: int, extension: string, currentMimeType: string, newMimeType: string}>
     */
    protected function getMimeTypesThatNeedUpdate(): array
    {
        $qb = $this->getPreparedQueryBuilder();

        $mimeTypeCompatibility = (new MimeTypeCompatibilityTypeGuesser())->getMimeTypeCompatibilityList();

        $conditions = [];
        foreach ($mimeTypeCompatibility as $currentMimeType => $map) {
            foreach ($map as $extension => $newMimeType) {
                $conditions[] = $qb->expr()->and(
                    $qb->expr()->eq(
                        'extension',
                        $qb->createNamedParameter($extension)
                    ),
                    $qb->expr()->eq(
                        'mime_type',
                        $qb->createNamedParameter($currentMimeType)
                    ),
                );
            }
        }

        $result = $qb
            ->select('mime_type', 'extension')
            ->addSelectLiteral($qb->expr()->count(self::TABLE_NAME . '.uid', 'count'))
            ->from(self::TABLE_NAME)
            ->where(
                $qb->expr()->or(...$conditions)
            )
            ->groupBy('mime_type', 'extension')
            ->executeQuery();

        $info = [];
        while ($row = $result->fetchAssociative()) {
            $newMimeType = $mimeTypeCompatibility[$row['mime_type']][mb_strtolower($row['extension'])] ?? null;
            if ($newMimeType === null) {
                throw new \LogicException('Invalid query result, misses matching mime_type', 1747912272);
            }
            $info[] = [
                'count' => (int)$row['count'],
                'extension' => $row['extension'],
                'currentMimeType' => $row['mime_type'],
                'newMimeType' => $newMimeType,
            ];
        }

        return $info;
    }

    public function updateNecessary(): bool
    {
        return $this->getMimeTypesThatNeedUpdate() !== [];
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function executeUpdate(): bool
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_NAME);
        foreach ($this->getMimeTypesThatNeedUpdate() as $info) {
            $currentMimeType = $info['currentMimeType'];
            $extension = $info['extension'];
            $newMimeType = $info['newMimeType'];

            $connection->update(
                self::TABLE_NAME,
                ['mime_type' => $newMimeType],
                ['mime_type' => $currentMimeType, 'extension' => $extension],
            );
        }

        return true;
    }

    private function getPreparedQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder;
    }
}
