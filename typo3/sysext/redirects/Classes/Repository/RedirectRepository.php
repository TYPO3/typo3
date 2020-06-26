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
use TYPO3\CMS\Redirects\Configuration\RedirectCleanupConfiguration;

/**
 * Class for accessing redirect records from the database
 * @internal
 */
class RedirectRepository
{
    /**
     * @var Demand
     */
    protected $demand;

    /**
     * With a possible demand object
     *
     * @param Demand|null $demand
     */
    public function __construct(Demand $demand = null)
    {
        $this->demand = $demand ?? new Demand();
    }

    /**
     * Used within the backend module, which also includes the hidden records, but never deleted records.
     *
     * @return array
     */
    public function findRedirectsByDemand(): array
    {
        return $this->getQueryBuilderForDemand()
            ->setMaxResults($this->demand->getLimit())
            ->setFirstResult($this->demand->getOffset())
            ->execute()
            ->fetchAll();
    }

    /**
     * @return int
     */
    public function countRedirectsByByDemand(): int
    {
        return $this->getQueryBuilderForDemand()->execute()->rowCount();
    }

    /**
     * Prepares the QueryBuilder with Constraints from the Demand
     *
     * @return QueryBuilder
     */
    protected function getQueryBuilderForDemand(): QueryBuilder
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('sys_redirect')
            ->orderBy('source_host')
            ->addOrderBy('source_path');

        $constraints = [];
        if ($this->demand->hasSourceHost()) {
            $constraints[] =$queryBuilder->expr()->eq(
                'source_host',
                $queryBuilder->createNamedParameter($this->demand->getSourceHost(), \PDO::PARAM_STR)
            );
        }

        if ($this->demand->hasSourcePath()) {
            $escapedLikeString = '%' . $queryBuilder->escapeLikeWildcards($this->demand->getSourcePath()) . '%';
            $constraints[] = $queryBuilder->expr()->like(
                'source_path',
                $queryBuilder->createNamedParameter($escapedLikeString, \PDO::PARAM_STR)
            );
        }

        if ($this->demand->hasTarget()) {
            $escapedLikeString = '%' . $queryBuilder->escapeLikeWildcards($this->demand->getTarget()) . '%';
            $constraints[] = $queryBuilder->expr()->like(
                'target',
                $queryBuilder->createNamedParameter($escapedLikeString, \PDO::PARAM_STR)
            );
        }

        if ($this->demand->hasStatusCode()) {
            $constraints[] =$queryBuilder->expr()->eq(
                'target_statuscode',
                $queryBuilder->createNamedParameter($this->demand->getStatusCode(), \PDO::PARAM_INT)
            );
        }

        if (!empty($constraints)) {
            $queryBuilder->where(...$constraints);
        }
        return $queryBuilder;
    }

    /**
     * Used for the filtering in the backend
     *
     * @return array
     */
    public function findHostsOfRedirects(): array
    {
        return $this->getQueryBuilder()
            ->select('source_host as name')
            ->from('sys_redirect')
            ->orderBy('source_host')
            ->groupBy('source_host')
            ->execute()
            ->fetchAll();
    }

    /**
     * Used for the filtering in the backend
     *
     * @return array
     */
    public function findStatusCodesOfRedirects(): array
    {
        return $this->getQueryBuilder()
            ->select('target_statuscode as code')
            ->from('sys_redirect')
            ->orderBy('target_statuscode')
            ->groupBy('target_statuscode')
            ->execute()
            ->fetchAll();
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_redirect');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder;
    }

    public function removeRecordsByConfguration(RedirectCleanupConfiguration $redirectCleanupConfiguration)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_redirect');
        $queryBuilder
            ->delete('sys_redirect')
            ->where(
                $queryBuilder->expr()->eq('protected', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT))
            );

        if ($redirectCleanupConfiguration->getHitCount() !== null) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->lt('hitcount', $queryBuilder->createNamedParameter($redirectCleanupConfiguration->getHitCount(), \PDO::PARAM_INT))
            );
        }
        if ($redirectCleanupConfiguration->getDomains() !== []) {
            $queryBuilder
                ->andWhere('source_host IN (:domains)')
                ->setParameter('domains', $redirectCleanupConfiguration->getDomains(), Connection::PARAM_STR_ARRAY);
        }
        if ($redirectCleanupConfiguration->getStatusCodes() !== []) {
            $queryBuilder
                ->andWhere('target_statuscode IN (:statusCodes)')
                ->setParameter('statusCodes', $redirectCleanupConfiguration->getStatusCodes(), Connection::PARAM_INT_ARRAY);
        }
        if ($redirectCleanupConfiguration->getDays() !== null) {
            $timeStamp = (new \DateTimeImmutable($redirectCleanupConfiguration->getDays() . ' days ago'))->getTimestamp();
            $queryBuilder->andWhere(
                $queryBuilder->expr()->lt('createdon', $queryBuilder->createNamedParameter($timeStamp, \PDO::PARAM_INT))
            );
        }
        if ($redirectCleanupConfiguration->getPath() !== null) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->like('source_path', ':path'))
                ->setParameter('path', $redirectCleanupConfiguration->getPath(), \PDO::PARAM_STR);
        }

        $queryBuilder->execute();
    }
}
