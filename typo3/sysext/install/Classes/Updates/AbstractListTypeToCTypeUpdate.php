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

use Doctrine\DBAL\Schema\Column;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class can be extended by 3rd party extensions to easily add a custom
 * `list_type` to `CType` update for deprecated "plugin" content element usages.
 *
 * @since 13.4
 */
abstract class AbstractListTypeToCTypeUpdate implements UpgradeWizardInterface
{
    protected const TABLE_CONTENT = 'tt_content';
    protected const TABLE_BACKEND_USER_GROUPS = 'be_groups';

    public function __construct(private readonly ConnectionPool $connectionPool)
    {
        $this->validateRequirements();
    }

    /**
     * This must return an array containing the "list_type" to "CType" mapping
     *
     *  Example:
     *
     *  [
     *      'pi_plugin1' => 'pi_plugin1',
     *      'pi_plugin2' => 'new_content_element',
     *  ]
     *
     * @return array<string, string>
     */
    abstract protected function getListTypeToCTypeMapping(): array;

    abstract public function getTitle(): string;

    abstract public function getDescription(): string;

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        return (
            $this->getListTypeToCTypeMapping() !== [] &&
            $this->columnsExistInContentTable() &&
            $this->hasContentElementsToUpdate()
        )
            || (
                $this->getListTypeToCTypeMapping() !== [] &&
                $this->columnsExistInBackendUserGroupsTable()
                && $this->hasNoLegacyBackendGroupsExplicitAllowDenyConfiguration()
                && $this->hasBackendUserGroupsToUpdate()
            );
    }

    public function executeUpdate(): bool
    {
        if ($this->getListTypeToCTypeMapping() !== [] &&
            $this->columnsExistInContentTable() &&
            $this->hasContentElementsToUpdate()
        ) {
            $this->updateContentElements();
        }
        if ($this->getListTypeToCTypeMapping() !== [] &&
            $this->columnsExistInBackendUserGroupsTable()
            && $this->hasNoLegacyBackendGroupsExplicitAllowDenyConfiguration()
            && $this->hasBackendUserGroupsToUpdate()
        ) {
            $this->updateBackendUserGroups();
        }

        return true;
    }

    protected function columnsExistInContentTable(): bool
    {
        $schemaManager = $this->connectionPool
            ->getConnectionForTable(self::TABLE_CONTENT)
            ->createSchemaManager();

        $tableColumnNames = array_flip(
            array_map(
                static fn(Column $column) => $column->getName(),
                $schemaManager->listTableColumns(self::TABLE_CONTENT)
            )
        );

        foreach (['CType', 'list_type'] as $column) {
            if (!isset($tableColumnNames[$column])) {
                return false;
            }
        }

        return true;
    }

    protected function columnsExistInBackendUserGroupsTable(): bool
    {
        $schemaManager = $this->connectionPool
            ->getConnectionForTable(self::TABLE_BACKEND_USER_GROUPS)
            ->createSchemaManager();

        return isset($schemaManager->listTableColumns(self::TABLE_BACKEND_USER_GROUPS)['explicit_allowdeny']);
    }

    protected function hasContentElementsToUpdate(): bool
    {
        $listTypesToUpdate = array_keys($this->getListTypeToCTypeMapping());

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_CONTENT);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->count('uid')
            ->from(self::TABLE_CONTENT)
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list')),
                $queryBuilder->expr()->in(
                    'list_type',
                    $queryBuilder->createNamedParameter($listTypesToUpdate, Connection::PARAM_STR_ARRAY)
                ),
            );

        return (bool)$queryBuilder->executeQuery()->fetchOne();
    }

    protected function hasBackendUserGroupsToUpdate(): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_BACKEND_USER_GROUPS);
        $queryBuilder->getRestrictions()->removeAll();

        $searchConstraints = [];
        foreach ($this->getListTypeToCTypeMapping() as $listTyp) {
            $searchConstraints[] = $queryBuilder->expr()->like(
                'explicit_allowdeny',
                $queryBuilder->createNamedParameter(
                    '%' . $queryBuilder->escapeLikeWildcards('tt_content:list_type:' . $listTyp) . '%'
                )
            );
        }

        $queryBuilder
            ->count('uid')
            ->from(self::TABLE_BACKEND_USER_GROUPS)
            ->where(
                $queryBuilder->expr()->or(...$searchConstraints),
            );

        return (bool)$queryBuilder->executeQuery()->fetchOne();
    }

    /**
     * Returns true, if no legacy explicit_allowdeny be_groups configuration is found.
     */
    protected function hasNoLegacyBackendGroupsExplicitAllowDenyConfiguration(): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_BACKEND_USER_GROUPS);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->count('uid')
            ->from(self::TABLE_BACKEND_USER_GROUPS)
            ->where(
                $queryBuilder->expr()->like(
                    'explicit_allowdeny',
                    $queryBuilder->createNamedParameter(
                        '%ALLOW%'
                    )
                ),
            );
        return (int)$queryBuilder->executeQuery()->fetchOne() === 0;
    }

    protected function getContentElementsToUpdate(string $listType): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_CONTENT);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select('uid')
            ->from(self::TABLE_CONTENT)
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list')),
                $queryBuilder->expr()->eq('list_type', $queryBuilder->createNamedParameter($listType)),
            );

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    protected function getBackendUserGroupsToUpdate(string $listType): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_BACKEND_USER_GROUPS);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select('uid', 'explicit_allowdeny')
            ->from(self::TABLE_BACKEND_USER_GROUPS)
            ->where(
                $queryBuilder->expr()->like(
                    'explicit_allowdeny',
                    $queryBuilder->createNamedParameter(
                        '%' . $queryBuilder->escapeLikeWildcards('tt_content:list_type:' . $listType) . '%'
                    )
                ),
            );
        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    protected function updateContentElements(): void
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_CONTENT);

        foreach ($this->getListTypeToCTypeMapping() as $listType => $contentType) {
            foreach ($this->getContentElementsToUpdate($listType) as $record) {
                $connection->update(
                    self::TABLE_CONTENT,
                    [
                        'CType' => $contentType,
                        'list_type' => '',
                    ],
                    ['uid' => (int)$record['uid']]
                );
            }
        }
    }

    protected function updateBackendUserGroups(): void
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_BACKEND_USER_GROUPS);

        foreach ($this->getListTypeToCTypeMapping() as $listType => $contentType) {
            foreach ($this->getBackendUserGroupsToUpdate($listType) as $record) {
                $fields = GeneralUtility::trimExplode(',', $record['explicit_allowdeny'], true);
                foreach ($fields as $key => $field) {
                    if ($field === 'tt_content:list_type:' . $listType) {
                        unset($fields[$key]);
                        $fields[] = 'tt_content:CType:' . $contentType;
                    }
                }

                $connection->update(
                    self::TABLE_BACKEND_USER_GROUPS,
                    [
                        'explicit_allowdeny' => implode(',', array_unique($fields)),
                    ],
                    ['uid' => (int)$record['uid']]
                );
            }
        }
    }

    private function validateRequirements(): void
    {
        if ($this->getTitle() === '') {
            throw new \RuntimeException('The update class "' . static::class . '" must provide a title by extending "getTitle()"', 1727605675);
        }
        if ($this->getDescription() === '') {
            throw new \RuntimeException('The update class "' . static::class . '" must provide a description by extending "getDescription()"', 1727605676);
        }
        if ($this->getListTypeToCTypeMapping() === []) {
            throw new \RuntimeException('The update class "' . static::class . '" does not provide a "list_type" to "CType" migration mapping', 1727605677);
        }

        foreach ($this->getListTypeToCTypeMapping() as $listType => $contentElement) {
            if (!is_string($listType) || $listType === '') {
                throw new \RuntimeException('Invalid mapping item "' . $listType . '" in class "' . static::class, 1727605678);
            }
            if (!is_string($contentElement) || $contentElement === '') {
                throw new \RuntimeException('Invalid mapping item "' . $contentElement . '" in class "' . static::class, 1727605679);
            }
        }
    }
}
