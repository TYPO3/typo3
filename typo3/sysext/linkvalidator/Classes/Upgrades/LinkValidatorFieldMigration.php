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

namespace TYPO3\CMS\Linkvalidator\Upgrades;

use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;

/**
 * Migrates `tx_linkvalidator_link.field` from 'url' to 'link' for records
 * where `tx_linkvalidator_link.table_name = 'pages'` and
 * `tx_linkvalidator_link.element_type = '3'` (DOKTYPE_LINK).
 *
 * @since 14.2
 * @internal This class is only meant to be used within EXT:linkvalidator and is not part of the TYPO3 Core API.
 * @todo Remove in 16.0 as breaking change.
 */
#[UpgradeWizard('linkValidatorFieldMigration')]
final readonly class LinkValidatorFieldMigration implements UpgradeWizardInterface
{
    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return 'Migrate field reference from "url" to "link" in tx_linkvalidator_link for pages of type Link.';
    }

    public function getDescription(): string
    {
        return 'Migrates field references in tx_linkvalidator_link table from "pages.url" to "pages.link" for pages of type "External Link" (doktype 3).';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        return $this->linkValidatorTableExists() && $this->hasRecordsToUpdate();
    }

    public function executeUpdate(): bool
    {
        if (!$this->updateNecessary()) {
            return true;
        }
        $updateQueryBuilder = $this->getPreparedQueryBuilder();
        $updateQueryBuilder->getRestrictions()->removeAll();
        $updateQueryBuilder->update('tx_linkvalidator_link')
            ->set('field', 'link')
            ->executeStatement();
        return true;
    }

    private function hasRecordsToUpdate(): bool
    {
        $queryBuilder = $this->getPreparedQueryBuilder();
        return (bool)$queryBuilder
            ->count('*')
            ->from('tx_linkvalidator_link')
            ->executeQuery()->fetchOne();
    }

    private function getPreparedQueryBuilder(): QueryBuilder
    {
        $connection = $this->connectionPool->getConnectionForTable('tx_linkvalidator_link');
        $queryBuilder = $connection->createQueryBuilder();
        $expression = $queryBuilder->expr();
        $queryBuilder->where(
            $expression->and(
                $expression->eq('table_name', $queryBuilder->createNamedParameter('pages')),
                $expression->eq('field', $queryBuilder->createNamedParameter('url')),
                $expression->eq('element_type', $queryBuilder->createNamedParameter((string)PageRepository::DOKTYPE_LINK)),
            ),
        );
        return $queryBuilder;
    }

    private function linkValidatorTableExists(): bool
    {
        $schemaManager = $this->connectionPool->getConnectionForTable('tx_linkvalidator_link')->createSchemaManager();
        return $schemaManager->tablesExist(['tx_linkvalidator_link']);
    }
}
