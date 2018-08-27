<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Service;

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
     * @param string $persistenceIdentifier
     * @return array
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
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->eq('softref_key', $queryBuilder->createNamedParameter('formPersistenceIdentifier', \PDO::PARAM_STR)),
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('ref_string', $queryBuilder->createNamedParameter($persistenceIdentifier, \PDO::PARAM_STR)),
                    $queryBuilder->expr()->eq('ref_uid', $queryBuilder->createNamedParameter($file->getUid(), \PDO::PARAM_INT))
                ),
                $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter('tt_content', \PDO::PARAM_STR))
            )
            ->execute()
            ->fetchAll();
    }

    /**
     * Returns an array with all form definition persistenceIdentifiers
     * as keys and their reference counts as values.
     *
     * @return array
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
     * @return array
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
     * @param string $column
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function getAllReferences(string $column): array
    {
        if ($column !== 'ref_string' && $column !== 'ref_uid') {
            throw new \InvalidArgumentException('$column must not be "ref_string" or "ref_uid".', 1535406600);
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');

        $constraints = [$queryBuilder->expr()->eq('softref_key', $queryBuilder->createNamedParameter('formPersistenceIdentifier', \PDO::PARAM_STR))];

        if ($column === 'ref_string') {
            $constraints[] = $queryBuilder->expr()->neq('ref_string', $queryBuilder->createNamedParameter('', \PDO::PARAM_STR));
        } else {
            $constraints[] = $queryBuilder->expr()->gt('ref_uid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT));
        }

        return $queryBuilder
            ->select($column . ' AS identifier')
            ->addSelectLiteral('COUNT(' . $queryBuilder->quoteIdentifier($column) . ') AS ' . $queryBuilder->quoteIdentifier('items'))
            ->from('sys_refindex')
            ->where(...$constraints)
            ->groupBy($column)
            ->execute()
            ->fetchAll();
    }
}
