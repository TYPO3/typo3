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

namespace TYPO3\CMS\Reactions\Repository;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reactions\Model\ReactionInstruction;

/**
 * Accessing reaction records from the database
 *
 * @internal This class is not part of TYPO3's Core API.
 */
class ReactionRepository
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly Context $context,
    ) {}

    public function findAll(): array
    {
        return $this->map($this->getQueryBuilder()
            ->executeQuery()
            ->fetchAllAssociative());
    }

    public function countAll(?ReactionDemand $demand = null): int
    {
        $qb = $demand ? $this->getQueryBuilderForDemand($demand, false) : $this->getQueryBuilder(false);
        return (int)$qb
            ->count('*')
            ->executeQuery()
            ->fetchOne();
    }

    public function getReactionRecords(?ReactionDemand $demand = null): array
    {
        return $demand !== null ? $this->findByDemand($demand) : $this->findAll();
    }

    /**
     * Used within the resolving / execution process, so starttime / endtime is added.
     */
    public function getReactionRecordByIdentifier(string $identifier): ?ReactionInstruction
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->getRestrictions()
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class))
            ->add(GeneralUtility::makeInstance(StartTimeRestriction::class))
            ->add(GeneralUtility::makeInstance(EndTimeRestriction::class));
        $result = $queryBuilder
            ->where(
                $queryBuilder->expr()->eq('identifier', $queryBuilder->createNamedParameter($identifier))
            )
            ->executeQuery()
            ->fetchAssociative();
        if (!empty($result)) {
            return $this->mapSingleRow($result);
        }
        return null;
    }

    /**
     * Used within the backend module, so hidden and scheduled reactions are found as well.
     */
    public function getReactionRecordByUid(int $uid): ?ReactionInstruction
    {
        $queryBuilder = $this->getQueryBuilder(false);
        $result = $queryBuilder
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();
        return $result !== false ? $this->mapSingleRow($result) : null;
    }

    /**
     * Written without DataHandler on purpose: a call is no change of the reaction, so it
     * neither touches the modification date nor ends up in the history or the log.
     */
    public function updateLastCall(int $uid, int $statusCode, string $failure): void
    {
        $this->connectionPool->getConnectionForTable('sys_reaction')->update(
            'sys_reaction',
            [
                'last_called' => (int)$this->context->getPropertyFromAspect('date', 'timestamp'),
                'last_status' => $statusCode,
                'last_failure' => $failure,
            ],
            ['uid' => $uid],
            [
                'last_called' => Connection::PARAM_INT,
                'last_status' => Connection::PARAM_INT,
                'last_failure' => Connection::PARAM_STR,
                'uid' => Connection::PARAM_INT,
            ]
        );
    }

    public function findByDemand(ReactionDemand $demand): array
    {
        return $this->map($this->getQueryBuilderForDemand($demand)
            ->setMaxResults($demand->getLimit())
            ->setFirstResult($demand->getOffset())
            ->executeQuery()
            ->fetchAllAssociative());
    }

    protected function getQueryBuilderForDemand(ReactionDemand $demand, bool $addOrderBy = true): QueryBuilder
    {
        $queryBuilder = $this->getQueryBuilder(false);
        if ($addOrderBy) {
            if ($demand->getOrderField() === 'impersonate_user') {
                // A reaction without a user, or with one that does not exist, sorts as if the name were empty on every DBMS.
                $queryBuilder
                    ->addSelectLiteral(
                        'COALESCE(' . $queryBuilder->quoteIdentifier('impersonated.username') . ', ' . $queryBuilder->quote('') . ')'
                        . ' AS ' . $queryBuilder->quoteIdentifier('impersonated_username')
                    )
                    ->leftJoin(
                        'sys_reaction',
                        'be_users',
                        'impersonated',
                        $queryBuilder->expr()->eq('impersonated.uid', $queryBuilder->quoteIdentifier('sys_reaction.impersonate_user'))
                    )
                    ->orderBy('impersonated_username', $demand->getOrderDirection());
            } else {
                $queryBuilder->orderBy('sys_reaction.' . $demand->getOrderField(), $demand->getOrderDirection());
            }
            // Ensure deterministic ordering.
            $queryBuilder->addOrderBy('sys_reaction.uid', 'asc');
        }

        $constraints = [];
        if ($demand->hasName()) {
            $escapedLikeString = '%' . $queryBuilder->escapeLikeWildcards($demand->getName()) . '%';
            $constraints[] = $queryBuilder->expr()->like(
                'sys_reaction.name',
                $queryBuilder->createNamedParameter($escapedLikeString)
            );
        }
        if ($demand->hasReactionType()) {
            $constraints[] = $queryBuilder->expr()->eq(
                'sys_reaction.reaction_type',
                $queryBuilder->createNamedParameter($demand->getReactionType())
            );
        }
        if ($demand->isOnlyEnabled()) {
            $constraints[] = $queryBuilder->expr()->eq(
                'sys_reaction.disabled',
                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
            );
        }
        if ($demand->hasImpersonateUser()) {
            $constraints[] = $queryBuilder->expr()->eq(
                'sys_reaction.impersonate_user',
                $queryBuilder->createNamedParameter($demand->getImpersonateUser(), Connection::PARAM_INT)
            );
        }
        if ($demand->hasCalled()) {
            $constraints[] = $demand->getCalled() === 'ever'
                ? $queryBuilder->expr()->gt('sys_reaction.last_called', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
                : $queryBuilder->expr()->eq('sys_reaction.last_called', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT));
        }

        if (!empty($constraints)) {
            $queryBuilder->where(...$constraints);
        }
        return $queryBuilder;
    }

    /**
     * The backend users that reactions impersonate, by username.
     *
     * @return array<int, string>
     */
    public function findImpersonatedUsers(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $rows = $queryBuilder
            ->select('be_users.uid', 'be_users.username')
            ->distinct()
            ->from('be_users')
            ->join(
                'be_users',
                'sys_reaction',
                'reaction',
                $queryBuilder->expr()->eq('reaction.impersonate_user', $queryBuilder->quoteIdentifier('be_users.uid'))
            )
            ->orderBy('be_users.username')
            ->addOrderBy('be_users.uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $users = [];
        foreach ($rows as $row) {
            $users[(int)$row['uid']] = (string)$row['username'];
        }
        return $users;
    }

    protected function map(array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapSingleRow($row);
        }
        return $items;
    }

    protected function mapSingleRow(array $row): ReactionInstruction
    {
        $row = BackendUtility::convertDatabaseRowValuesToPhp('sys_reaction', $row);
        return new ReactionInstruction($row);
    }

    protected function getQueryBuilder(bool $addDefaultOrderByClause = true): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_reaction');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder->select('sys_reaction.*')->from('sys_reaction');
        if ($addDefaultOrderByClause) {
            $queryBuilder
                ->orderBy('name', 'asc')
                // Ensure deterministic ordering.
                ->addOrderBy('uid', 'asc');
        }
        return $queryBuilder;
    }
}
