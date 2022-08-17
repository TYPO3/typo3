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
    public function findAll(): array
    {
        return $this->map($this->getQueryBuilder()
            ->select('*')
            ->from('sys_reaction')
            ->executeQuery()
            ->fetchAllAssociative());
    }

    public function countAll(): int
    {
        return (int)$this->getQueryBuilder()
            ->count('*')
            ->from('sys_reaction')
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
            ->select('*')
            ->from('sys_reaction')
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

    public function findByDemand(ReactionDemand $demand): array
    {
        return $this->map($this->getQueryBuilderForDemand($demand)
            ->setMaxResults($demand->getLimit())
            ->setFirstResult($demand->getOffset())
            ->executeQuery()
            ->fetchAllAssociative());
    }

    protected function getQueryBuilderForDemand(ReactionDemand $demand): QueryBuilder
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('sys_reaction');

        $queryBuilder->orderBy(
            $demand->getOrderField(),
            $demand->getOrderDirection()
        );

        $constraints = [];
        if ($demand->hasName()) {
            $escapedLikeString = '%' . $queryBuilder->escapeLikeWildcards($demand->getName()) . '%';
            $constraints[] = $queryBuilder->expr()->like(
                'name',
                $queryBuilder->createNamedParameter($escapedLikeString)
            );
        }
        if ($demand->hasReaction()) {
            $constraints[] = $queryBuilder->expr()->eq(
                'reactiontype',
                $queryBuilder->createNamedParameter($demand->getReaction())
            );
        }

        if (!empty($constraints)) {
            $queryBuilder->where(...$constraints);
        }
        return $queryBuilder;
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

    protected function getQueryBuilder(): QueryBuilder
    {
        // @todo ConnectionPool could be injected
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_reaction');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder
            ->orderBy('name');
    }
}
