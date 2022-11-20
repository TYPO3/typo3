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

namespace TYPO3\CMS\Form\Service;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class is subjected to change.
 * **Do NOT subclass**
 *
 * Scope: frontend / backend
 * @internal
 */
class DatabaseService
{
    /**
     * Returns an array with all sys_refindex database rows which be
     * connected to a formDefinition identified by $persistenceIdentifier
     *
     * @throws \InvalidArgumentException
     * @internal
     */
    public function getReferencesByPersistenceIdentifier(string $persistenceIdentifier): array
    {
        if (empty($persistenceIdentifier)) {
            throw new \InvalidArgumentException('$persistenceIdentifier must not be empty.', 1472238493);
        }

        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $file = $resourceFactory->retrieveFileOrFolderObject($persistenceIdentifier);

        if ($file === null) {
            return [];
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');

        return $queryBuilder
            ->select('*')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq('softref_key', $queryBuilder->createNamedParameter('formPersistenceIdentifier')),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->eq('ref_string', $queryBuilder->createNamedParameter($persistenceIdentifier)),
                    $queryBuilder->expr()->eq('ref_uid', $queryBuilder->createNamedParameter($file->getUid(), Connection::PARAM_INT))
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Returns an array with all form definition persistenceIdentifiers
     * as keys and their reference counts as values.
     *
     * @internal
     */
    public function getAllReferencesForPersistenceIdentifier(): array
    {
        $items = [];
        foreach ($this->getAllReferences('ref_string') as $item) {
            $items[$item['identifier']] = $item['items'];
        }
        return $items;
    }

    /**
     * Returns an array with all form definition file uids as keys
     * and their reference counts as values.
     *
     * @internal
     */
    public function getAllReferencesForFileUid(): array
    {
        $items = [];
        foreach ($this->getAllReferences('ref_uid') as $item) {
            $items[$item['identifier']] = $item['items'];
        }
        return $items;
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function getAllReferences(string $column): array
    {
        if ($column !== 'ref_string' && $column !== 'ref_uid') {
            throw new \InvalidArgumentException('$column must not be "ref_string" or "ref_uid".', 1535406600);
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');

        $constraints = [
            $queryBuilder->expr()->eq('softref_key', $queryBuilder->createNamedParameter('formPersistenceIdentifier')),
        ];

        if ($column === 'ref_string') {
            $constraints[] = $queryBuilder->expr()->neq('ref_string', $queryBuilder->createNamedParameter(''));
        } else {
            $constraints[] = $queryBuilder->expr()->gt('ref_uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT));
        }

        return $queryBuilder
            ->select($column . ' AS identifier')
            ->addSelectLiteral('COUNT(' . $queryBuilder->quoteIdentifier($column) . ') AS ' . $queryBuilder->quoteIdentifier('items'))
            ->from('sys_refindex')
            ->where(...$constraints)
            ->groupBy($column)
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
