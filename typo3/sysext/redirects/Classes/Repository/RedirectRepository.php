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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Security\RedirectPermissionGuard;

/**
 * Class for accessing redirect records from the database
 * @internal
 */
class RedirectRepository
{
    private TcaSchema $schema;

    public function __construct(
        TcaSchemaFactory $schemaFactory,
        private readonly RedirectPermissionGuard $redirectPermissionGuard,
    ) {
        $this->schema = $schemaFactory->get('sys_redirect');
    }

    /**
     * Used within the backend module, which also includes the hidden records, but never deleted records.
     */
    public function findRedirectsByDemand(Demand $demand): array
    {
        // Fast path for admin users - use SQL pagination directly
        if ($this->getBackendUser()->isAdmin()) {
            return $this->getQueryBuilderForDemand($demand)
                ->select('*')
                ->setMaxResults($demand->getLimit())
                ->setFirstResult($demand->getOffset())
                ->executeQuery()
                ->fetchAllAssociative();
        }

        // Non-admin: Two-phase fetch without caching
        // Phase 1: Fetch minimal fields for ALL matching records with SQL source host filtering
        $queryBuilder = $this->getQueryBuilderForDemand($demand);

        try {
            $this->addSourceHostConstraint($queryBuilder);
        } catch (StopQueryException) {
            return [];
        }

        $redirects = $queryBuilder
            ->executeQuery()
            ->fetchAllAssociative();

        // Phase 2: Apply PHP target permission filtering
        $filteredRedirects = $this->sortOutInaccessibleRedirects($redirects);

        // Phase 3: Get UIDs for current page (applying pagination in PHP)
        $filteredUids = array_column($filteredRedirects, 'uid');
        $currentPageUids = array_slice($filteredUids, $demand->getOffset(), $demand->getLimit());

        if ($currentPageUids === []) {
            return [];
        }

        // Phase 4: Fetch full records only for the current page
        $queryBuilder = $this->getQueryBuilder();
        return $queryBuilder
            ->select('*')
            ->from('sys_redirect')
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($currentPageUids, Connection::PARAM_INT_ARRAY)
                )
            )
            ->orderBy($demand->getOrderField(), $demand->getOrderDirection())
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public function countRedirectsByDemand(Demand $demand): int
    {
        // Fast path for admin users - use SQL COUNT
        if ($this->getBackendUser()->isAdmin()) {
            $queryBuilder = $this->getQueryBuilderForDemand($demand, true);
            return (int)$queryBuilder
                ->count('uid')
                ->executeQuery()
                ->fetchOne();
        }

        // Non-admin: Fetch minimal fields with SQL source host filtering
        $queryBuilder = $this->getQueryBuilderForDemand($demand);

        try {
            $this->addSourceHostConstraint($queryBuilder);
        } catch (StopQueryException) {
            return 0;
        }

        $redirects = $queryBuilder
            ->executeQuery()
            ->fetchAllAssociative();

        // Apply PHP target permission filtering and count
        $filteredRedirects = $this->sortOutInaccessibleRedirects($redirects);

        return count($filteredRedirects);
    }

    public function countActiveRedirects(): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_redirect');
        return (int)$queryBuilder
            ->count('uid')
            ->from('sys_redirect')
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Adds source host constraint to query for non-admin users
     * This significantly reduces the dataset before PHP filtering
     * @throws StopQueryException
     */
    protected function addSourceHostConstraint(QueryBuilder $queryBuilder): void
    {
        // Admin users see all hosts
        if ($this->getBackendUser()->isAdmin()) {
            return;
        }

        // Get allowed hosts for the current user
        $allowedHosts = $this->redirectPermissionGuard->getAllowedHosts();
        if (empty($allowedHosts)) {
            throw new StopQueryException('No allowed hosts found for current user', 1764702053);
        }

        $queryBuilder->andWhere(
            $queryBuilder->expr()->in(
                'source_host',
                $queryBuilder->createNamedParameter($allowedHosts, Connection::PARAM_STR_ARRAY)
            )
        );
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Prepares the QueryBuilder with Constraints from the Demand
     */
    protected function getQueryBuilderForDemand(Demand $demand, bool $createCountQuery = false): QueryBuilder
    {
        $queryBuilder = $this->getQueryBuilder();

        if ($createCountQuery) {
            $queryBuilder->count('uid');
        } else {
            $queryBuilder->select('uid', 'source_host', 'target');
        }

        $queryBuilder->from('sys_redirect');

        if (!$createCountQuery) {
            $queryBuilder->orderBy(
                $demand->getOrderField(),
                $demand->getOrderDirection()
            );

            if ($demand->hasSecondaryOrdering()) {
                $queryBuilder->addOrderBy($demand->getSecondaryOrderField());
            }
        }

        $constraints = [];

        if ($demand->hasRedirectType()) {
            $constraints[] = $queryBuilder->expr()->eq(
                'redirect_type',
                $queryBuilder->createNamedParameter($demand->getRedirectType())
            );
        }

        if ($demand->hasSourceHosts()) {
            $constraints[] = $queryBuilder->expr()->in(
                'source_host',
                $queryBuilder->createNamedParameter($demand->getSourceHosts(), Connection::PARAM_STR_ARRAY)
            );
        }

        if ($demand->hasSourcePath()) {
            $escapedLikeString = '%' . $queryBuilder->escapeLikeWildcards($demand->getSourcePath()) . '%';
            $constraints[] = $queryBuilder->expr()->like(
                'source_path',
                $queryBuilder->createNamedParameter($escapedLikeString)
            );
        }

        if ($demand->hasTarget()) {
            $escapedLikeString = '%' . $queryBuilder->escapeLikeWildcards($demand->getTarget()) . '%';
            $constraints[] = $queryBuilder->expr()->like(
                'target',
                $queryBuilder->createNamedParameter($escapedLikeString)
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
                $queryBuilder->createNamedParameter($demand->getMaxHits(), Connection::PARAM_INT)
            );
            // When max hits is set, exclude records which explicitly disabled the hitcount feature
            $constraints[] = $queryBuilder->expr()->eq(
                'disable_hitcount',
                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
            );
        }

        if ($demand->hasCreationType()) {
            $constraints[] = $queryBuilder->expr()->eq(
                'creation_type',
                $queryBuilder->createNamedParameter($demand->getCreationType(), Connection::PARAM_INT)
            );
        }

        if ($demand->hasProtected()) {
            $constraints[] = $queryBuilder->expr()->eq(
                'protected',
                $queryBuilder->createNamedParameter($demand->getProtected(), Connection::PARAM_INT)
            );
        }

        if ($demand->hasIntegrityStatus()) {
            $constraints[] = $queryBuilder->expr()->eq(
                'integrity_status',
                $queryBuilder->createNamedParameter($demand->getIntegrityStatus())
            );
        }

        if (!empty($constraints)) {
            $queryBuilder->where(...$constraints);
        }

        return $queryBuilder;
    }

    /**
     * Get all used hosts
     */
    public function findHostsOfRedirects(?string $type = null): array
    {
        return $this->getGroupedRows('source_host', 'name', $type);
    }

    /**
     * Get all used status codes
     */
    public function findStatusCodesOfRedirects(?string $type = null): array
    {
        return $this->getGroupedRows('target_statuscode', 'code', $type);
    }

    /**
     * Get all used creation types
     */
    public function findCreationTypes(?string $type = null): array
    {
        $types = [];
        $availableTypes = $this->schema->getField('creation_type')->getConfiguration()['items'];
        foreach ($this->getGroupedRows('creation_type', 'type', $type) as $row) {
            foreach ($availableTypes as $availableType) {
                if ($availableType['value'] === $row['type']) {
                    $types[$row['type']] = $availableType['label'];
                }
            }
        }

        return $types;
    }

    /**
     * Get all used integrity status codes
     */
    public function findIntegrityStatusCodes(?string $type = null): array
    {
        $statusCodes = [];
        $availableStatusCodes = $this->schema->getField('integrity_status')->getConfiguration()['items'];
        foreach ($this->getGroupedRows('integrity_status', 'status_code', $type) as $row) {
            foreach ($availableStatusCodes as $availableStatusCode) {
                if ($availableStatusCode['value'] === $row['status_code']) {
                    $statusCodes[$row['status_code']] = $availableStatusCode['label'];
                }
            }
        }

        return $statusCodes;
    }

    /**
     * Get all available redirect_types
     */
    public function findRedirectTypes(): array
    {
        // Admin: Direct SQL query with GROUP BY
        if ($this->getBackendUser()->isAdmin()) {
            $result = $this->getQueryBuilder()
                ->select('redirect_type')
                ->from('sys_redirect')
                ->groupBy('redirect_type')
                ->executeQuery()
                ->fetchAllAssociative();

            return array_column($result, 'redirect_type');
        }

        // Non-admin: GROUP BY + SQL source host filter + minimal PHP target filtering
        $queryBuilder = $this->getQueryBuilder()
            ->select('redirect_type', 'source_host', 'target')
            ->from('sys_redirect')
            ->groupBy('redirect_type', 'source_host', 'target');

        try {
            $this->addSourceHostConstraint($queryBuilder);
        } catch (StopQueryException) {
            return [];
        }

        $redirects = $queryBuilder
            ->executeQuery()
            ->fetchAllAssociative();

        $filteredRedirects = $this->sortOutInaccessibleRedirects($redirects);

        return array_values(array_unique(array_column($filteredRedirects, 'redirect_type')));
    }

    /**
     * @return list<array<string, scalar|null>>
     */
    protected function getGroupedRows(string $field, string $as, ?string $type = 'default'): array
    {
        // Admin: Direct SQL query
        if ($this->getBackendUser()->isAdmin()) {
            $queryBuilder = $this->getQueryBuilder()
                ->select(sprintf('%s as %s', $field, $as))
                ->from('sys_redirect')
                ->orderBy($field)
                ->groupBy($field);

            if ($type !== null) {
                $queryBuilder->where($queryBuilder->expr()->eq('redirect_type', $queryBuilder->createNamedParameter($type)));
            }

            return $queryBuilder
                ->executeQuery()
                ->fetchAllAssociative();
        }

        // Non-admin: Need to include source_host and target for filtering
        $fields = [$field];
        if ($field !== 'source_host') {
            $fields[] = 'source_host';
        }
        if ($field !== 'target') {
            $fields[] = 'target';
        }

        $queryBuilder = $this->getQueryBuilder()
            ->select(...$fields)
            ->from('sys_redirect')
            ->orderBy($field)
            ->groupBy(...$fields);

        if ($type !== null) {
            $queryBuilder->where($queryBuilder->expr()->eq('redirect_type', $queryBuilder->createNamedParameter($type)));
        }

        try {
            $this->addSourceHostConstraint($queryBuilder);
        } catch (StopQueryException) {
            return [];
        }

        $redirects = $queryBuilder
            ->executeQuery()
            ->fetchAllAssociative();

        $filteredRedirects = $this->sortOutInaccessibleRedirects($redirects);

        return array_map(
            static fn(mixed $value) => [$as => $value],
            array_values(array_unique(array_column($filteredRedirects, $field))),
        );
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
                $queryBuilder->expr()->eq('protected', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            );

        if ($demand->hasMaxHits()) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->lt('hitcount', $queryBuilder->createNamedParameter($demand->getMaxHits(), Connection::PARAM_INT))
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
                $queryBuilder->expr()->lt('createdon', $queryBuilder->createNamedParameter($timeStamp, Connection::PARAM_INT))
            );
        }
        if ($demand->hasSourcePath()) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->like('source_path', ':path'))
                ->setParameter('path', $demand->getSourcePath());
        }

        if ($demand->hasCreationType()) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('creation_type', $queryBuilder->createNamedParameter($demand->getCreationType(), Connection::PARAM_INT))
            );
        }

        $queryBuilder->executeStatement();
    }

    /**
     * @param list<non-empty-array> $redirects
     * @return list<non-empty-array>
     */
    protected function sortOutInaccessibleRedirects(array $redirects): array
    {
        return array_filter($redirects, $this->redirectPermissionGuard->isAllowedRedirect(...));
    }
}
