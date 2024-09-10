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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;

/**
 * @since 13.3
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('synchronizeColPosAndCTypeWithDefaultLanguage')]
class SynchronizeColPosAndCTypeWithDefaultLanguage implements UpgradeWizardInterface
{
    protected const TABLE_NAME = 'tt_content';

    public function getTitle(): string
    {
        return 'Migrate "colPos" and "CType" of "tt_content" translations to match their parent.';
    }

    public function getDescription(): string
    {
        return 'Inherit "colPos" and "CType" for "tt_content" translations from their parent elements for consistent translation behavior.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        return $this->getRecordsToUpdate() !== [];
    }

    public function executeUpdate(): bool
    {
        $connection = $this->getConnectionPool()->getConnectionForTable(self::TABLE_NAME);
        foreach ($this->getRecordsToUpdate() as $record) {
            $parent = $this->getParentRecord((int)$record['l18n_parent']);
            if ($parent === [] || $parent === false) {
                continue;
            }
            $connection
                ->update(
                    self::TABLE_NAME,
                    [
                        'colPos' => (int)$parent['colPos'],
                        'CType' => (string)$parent['CType'],
                    ],
                    [
                        'uid' => (int)$record['uid'],
                    ]
                );
        }

        return true;
    }

    protected function getRecordsToUpdate(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select(
                'translation.uid',
                'translation.l18n_parent',
                'translation.deleted',
                'translation.colPos',
                'translation.CType',
                'parent.deleted',
                'parent.colPos',
                'parent.CType'
            )
            ->from(self::TABLE_NAME, 'translation')
            ->leftJoin(
                'translation',
                self::TABLE_NAME,
                'parent',
                $queryBuilder->expr()->eq('translation.l18n_parent', 'parent.uid')
            )
            ->where(
                $queryBuilder->expr()->neq(
                    'translation.l18n_parent',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->neq('translation.colPos', $queryBuilder->quoteIdentifier('parent.colPos')),
                    $queryBuilder->expr()->neq('translation.CType', $queryBuilder->quoteIdentifier('parent.CType')),
                ),
                $queryBuilder->expr()->eq('translation.deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('parent.deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            );

        return $queryBuilder->executeQuery()->fetchAllAssociative() ?: [];
    }

    protected function getParentRecord(int $uid): array|false
    {
        return $this->getConnectionPool()->getConnectionForTable(self::TABLE_NAME)
            ->select(
                ['colPos', 'CType'],
                self::TABLE_NAME,
                ['uid' => $uid]
            )->fetchAssociative();
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
