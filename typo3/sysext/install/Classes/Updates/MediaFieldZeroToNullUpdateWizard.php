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
use TYPO3\CMS\Install\Attribute\UpgradeWizard;

/**
 * Update fields imagewidth & imageheight to NULL if their value is 0
 *
 * @since 14.0
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('mediaFieldZeroToNullUpdateWizard')]
class MediaFieldZeroToNullUpdateWizard implements UpgradeWizardInterface, RepeatableInterface
{
    public function __construct(
        private readonly ConnectionPool $connectionPool
    ) {}

    public function getTitle(): string
    {
        return 'Migrate default value of fields imagewidth & imageheight to NULL';
    }

    public function getDescription(): string
    {
        $fieldsThatNeedUpdate = $this->getCountOfRowsWhichNeedUpdate();
        return sprintf('Update %d records to set the default value of the fields imagewidth & imageheight to NULL instead of 0.', $fieldsThatNeedUpdate);
    }

    protected function getCountOfRowsWhichNeedUpdate(): int
    {
        $qb = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $qb->getRestrictions()->removeAll();
        return (int)$qb
            ->count('*')
            ->from('tt_content')
            ->where(
                $qb->expr()->or(
                    $qb->expr()->eq('imagewidth', $qb->createNamedParameter(0, Connection::PARAM_INT)),
                    $qb->expr()->eq('imageheight', $qb->createNamedParameter(0, Connection::PARAM_INT)),
                )
            )
            ->executeQuery()
            ->fetchOne();
    }

    public function updateNecessary(): bool
    {
        return $this->getCountOfRowsWhichNeedUpdate() > 0;
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
        foreach (['imagewidth', 'imageheight'] as $fieldName) {
            $qb = $this->connectionPool->getQueryBuilderForTable('tt_content');
            $qb
                ->update('tt_content')
                ->set($fieldName, null)
                ->where($qb->expr()->eq($fieldName, $qb->createNamedParameter(0, Connection::PARAM_INT)))
                ->executeStatement();
        }
        return true;
    }
}
