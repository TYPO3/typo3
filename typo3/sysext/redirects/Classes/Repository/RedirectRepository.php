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

namespace TYPO3\CMS\Redirects\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class for accessing redirect records from the database
 * @internal
 */
class RedirectRepository
{
    /**
     * Used within the backend module, which also includes the hidden records, but never deleted records.
     */
    public function findRedirectsByDemand(Demand $demand): array
    {
        return $this->getQueryBuilderForDemand($demand)
            ->setMaxResults($demand->getLimit())
            ->setFirstResult($demand->getOffset())
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public function countRedirectsByByDemand(Demand $demand): int
    {
        return (int)$this->getQueryBuilderForDemand($demand)->count('*')->executeQuery()->fetchOne();
    }

    /**
     * Prepares the QueryBuilder with Constraints from the Demand
     */
    protected function getQueryBuilderForDemand(Demand $demand): QueryBuilder
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('sys_redirect');

        $queryBuilder->orderBy(
            $demand->getOrderField(),
            $demand->getOrderDirection()
        );

        if ($demand->hasSecondaryOrdering()) {
            $queryBuilder->addOrderBy($demand->getSecondaryOrderField());
        }

        $constraints = [];
        if ($demand->hasSourceHosts()) {
            $constraints[] =$queryBuilder->expr()->in(
                'source_host',
                $queryBuilder->createNamedParameter($demand->getSourceHosts(), Connection::PARAM_STR_ARRAY)
            );
        }

        if ($demand->hasSourcePath()) {
            $escapedLikeString = '%' . $queryBuilder->escapeLikeWildcards($demand->getSourcePath()) . '%';
            $constraints[] = $queryBuilder->expr()->like(
                'source_path',
                $queryBuilder->createNamedParameter($escapedLikeString, \PDO::PARAM_STR)
            );
        }

        if ($demand->hasTarget()) {
            $escapedLikeString = '%' . $queryBuilder->escapeLikeWildcards($demand->getTarget()) . '%';
            $constraints[] = $queryBuilder->expr()->like(
                'target',
                $queryBuilder->createNamedParameter($escapedLikeString, \PDO::PARAM_STR)
            );
        }

        if ($demand->hasStatusCodes()) {
            $constraints[] = $queryBuilder->expr()->in(
                'target_statuscode',
                $queryBuilder->createNamedParameter($demand->getStatusCodes(), Connection::PARAM_INT_ARRAY)
            );
        }

        if ($demand->hasMaxHits()) {
            $constraints[] = $queryBuilder->expr()->lt(
                'hitcount',
                $queryBuilder->createNamedParameter($demand->getMaxHits(), \PDO::PARAM_INT)
            );
            // When max hits is set, exclude records which explicitly disabled the hitcount feature
            $constraints[] = $queryBuilder->expr()->eq(
                'disable_hitcount',
                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
            );
        }

        if (!empty($constraints)) {
            $queryBuilder->where(...$constraints);
        }
        return $queryBuilder;
    }

    /**
     * Used for the filtering in the backend
     */
    public function findHostsOfRedirects(): array
    {
        return $this->getQueryBuilder()
            ->select('source_host as name')
            ->from('sys_redirect')
            ->orderBy('source_host')
            ->groupBy('source_host')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Used for the filtering in the backend
     */
    public function findStatusCodesOfRedirects(): array
    {
        return $this->getQueryBuilder()
            ->select('target_statuscode as code')
            ->from('sys_redirect')
            ->orderBy('target_statuscode')
            ->groupBy('target_statuscode')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_redirect');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder;
    }

    public function removeByDemand(Demand $demand): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_redirect');
        $queryBuilder
            ->delete('sys_redirect')
            ->where(
                $queryBuilder->expr()->eq('protected', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            );

        if ($demand->hasMaxHits()) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->lt('hitcount', $queryBuilder->createNamedParameter($demand->getMaxHits(), \PDO::PARAM_INT))
            );
        }
        if ($demand->hasSourceHosts()) {
            $queryBuilder
                ->andWhere('source_host IN (:domains)')
                ->setParameter('domains', $demand->getSourceHosts(), Connection::PARAM_STR_ARRAY);
        }
        if ($demand->hasStatusCodes()) {
            $queryBuilder
                ->andWhere('target_statuscode IN (:statusCodes)')
                ->setParameter('statusCodes', $demand->getStatusCodes(), Connection::PARAM_INT_ARRAY);
        }
        if ($demand->hasOlderThan()) {
            $timeStamp = $demand->getOlderThan()->getTimestamp();
            $queryBuilder->andWhere(
                $queryBuilder->expr()->lt('createdon', $queryBuilder->createNamedParameter($timeStamp, \PDO::PARAM_INT))
            );
        }
        if ($demand->hasSourcePath()) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->like('source_path', ':path'))
                ->setParameter('path', $demand->getSourcePath(), \PDO::PARAM_STR);
        }

        $queryBuilder->executeStatement();
    }
}
