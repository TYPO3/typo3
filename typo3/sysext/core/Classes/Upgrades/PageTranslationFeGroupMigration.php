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

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * Copies "pages.fe_group" of default language pages to their live translations,
 * which could deviate while the field was missing "l10n_mode" set to "exclude".
 *
 * @since 13.4
 * @internal This class is only meant to be used within EXT:core and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('pageTranslationFeGroupMigration')]
final readonly class PageTranslationFeGroupMigration implements UpgradeWizardInterface
{
    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return 'Synchronize "Usergroup Access Rights" of page translations';
    }

    public function getDescription(): string
    {
        return 'Copies the frontend user group restriction (pages.fe_group) of default language pages '
            . 'to their translations, where the value deviates.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        $queryBuilder = $this->getDeviatingTranslationsQueryBuilder();
        return (int)$queryBuilder
            ->count('translation.uid')
            ->executeQuery()
            ->fetchOne() > 0;
    }

    public function executeUpdate(): bool
    {
        $connection = $this->connectionPool->getConnectionForTable('pages');
        $result = $this->getDeviatingTranslationsQueryBuilder()
            ->select('translation.uid', 'parent.fe_group')
            ->executeQuery();
        try {
            while ($row = $result->fetchAssociative()) {
                $connection->update(
                    'pages',
                    ['fe_group' => (string)$row['fe_group']],
                    ['uid' => (int)$row['uid']],
                );
            }
        } finally {
            $result->free();
        }
        return true;
    }

    /**
     * Live page translations whose "fe_group" differs from the live default language page.
     * Workspace versions are left untouched, they are synchronized when the default page is saved.
     */
    private function getDeviatingTranslationsQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->from('pages', 'translation')
            ->join(
                'translation',
                'pages',
                'parent',
                $queryBuilder->expr()->eq('parent.uid', $queryBuilder->quoteIdentifier('translation.l10n_parent'))
            )
            ->where(
                $queryBuilder->expr()->gt('translation.sys_language_uid', $queryBuilder->createNamedParameter(0, ParameterType::INTEGER)),
                $queryBuilder->expr()->gt('translation.l10n_parent', $queryBuilder->createNamedParameter(0, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('translation.t3ver_wsid', $queryBuilder->createNamedParameter(0, ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('parent.t3ver_wsid', $queryBuilder->createNamedParameter(0, ParameterType::INTEGER)),
                $queryBuilder->expr()->neq('translation.fe_group', $queryBuilder->quoteIdentifier('parent.fe_group')),
            );
        return $queryBuilder;
    }
}
