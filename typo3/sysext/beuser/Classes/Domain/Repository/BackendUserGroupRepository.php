<?php

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

namespace TYPO3\CMS\Beuser\Domain\Repository;

use TYPO3\CMS\Beuser\Domain\Dto\BackendUserGroup;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository for \TYPO3\CMS\Beuser\Domain\Model\BackendUserGroup
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 * @extends Repository<\TYPO3\CMS\Beuser\Domain\Model\BackendUserGroup>
 */
class BackendUserGroupRepository extends Repository
{
    public const TABLE_NAME = 'be_groups';

    protected $defaultOrderings = [
        'title' => QueryInterface::ORDER_ASCENDING,
    ];

    /**
     * Overwrite createQuery to don't respect enable fields
     *
     * @return QueryInterface
     */
    public function createQuery()
    {
        $query = parent::createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        return $query;
    }

    /**
     * Get QueryBuilder without restrictions for table be_groups
     *
     * @param bool $removeRestrictions
     * @return QueryBuilder
     */
    public function getQueryBuilder(bool $removeRestrictions = true): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        if ($removeRestrictions === true) {
            $queryBuilder->getRestrictions()->removeAll();
        }

        return $queryBuilder;
    }

    /**
     * Finds Backend Usergroups on a given list of uids
     */
    public function findByUidList(array $uidList): array
    {
        $query = $this->createQuery();
        // being explicit here, albeit `Typo3DbQueryParser::parseDynamicOperand` uses prepared parameters
        $uidList = array_map('intval', $uidList);
        $query->matching($query->in('uid', $uidList));
        return $query->execute(true);
    }

    /**
     * Preforms a query on be_groups, matching the field title with like
     *
     * @param BackendUserGroup $backendUserGroupDto
     * @return QueryResult
     * @throws InvalidQueryException
     */
    public function findByFilter(BackendUserGroup $backendUserGroupDto): QueryResult
    {
        $constraints = [];
        $query = $this->createQuery();
        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);
        if ($backendUserGroupDto->getTitle() !== '') {
            $searchConstraints = [];
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
            $searchConstraints[] = $query->like(
                'title',
                '%' . $queryBuilder->escapeLikeWildcards($backendUserGroupDto->getTitle()) . '%'
            );

            if (MathUtility::canBeInterpretedAsInteger($backendUserGroupDto->getTitle())) {
                $searchConstraints[] = $query->equals('uid', (int)$backendUserGroupDto->getTitle());
            }

            if (count($searchConstraints) >= 2) {
                $constraints[] = $query->logicalOr(...$searchConstraints);
            } else {
                $constraints = $searchConstraints;
            }
        }

        $query->matching($query->logicalAnd(...$constraints));
        /** @var QueryResult $result */
        $result = $query->execute();

        return $result;
    }
}
