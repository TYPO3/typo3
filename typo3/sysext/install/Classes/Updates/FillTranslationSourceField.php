<?php
namespace TYPO3\CMS\Install\Updates;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Fill translation source field (l10n_source)
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class FillTranslationSourceField implements UpgradeWizardInterface
{
    /**
     * @return string Unique identifier of this updater
     */
    public function getIdentifier(): string
    {
        return 'fillTranslationSourceField';
    }

    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return 'Fill translation source field (l10n_source)';
    }

    /**
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'Fill translation source field (l10n_source) for tt_contents which have l18n_parent set.';
    }

    /**
     * Checks if an update is needed
     *
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content');
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $query = $queryBuilder->count('uid')
            ->from('tt_content')
            ->where($queryBuilder->expr()->andX(
                $queryBuilder->expr()->gt('l18n_parent', $queryBuilder->createNamedParameter(0)),
                $queryBuilder->expr()->eq('l10n_source', $queryBuilder->createNamedParameter(0))
            ));
        return (bool)$query->execute()->fetchColumn(0);
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    /**
     * Performs the database update
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content');
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->update('tt_content', 't')
            ->set('t.l10n_source', 't.l18n_parent', false)
            ->where($queryBuilder->expr()->andX(
                $queryBuilder->expr()->gt('t.l18n_parent', $queryBuilder->createNamedParameter(0)),
                $queryBuilder->expr()->eq('t.l10n_source', $queryBuilder->createNamedParameter(0))
            ))
            ->execute();
        return true;
    }
}
