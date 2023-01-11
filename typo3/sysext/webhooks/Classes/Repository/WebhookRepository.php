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

namespace TYPO3\CMS\Webhooks\Repository;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Webhooks\Factory\WebhookInstructionFactory;
use TYPO3\CMS\Webhooks\Model\WebhookInstruction;

/**
 * Accessing webhook records from the database
 *
 * @internal This class is not part of TYPO3's Core API.
 */
class WebhookRepository
{
    protected string $cacheIdentifierPrefix = 'webhooks_';

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
        protected readonly FrontendInterface $runtimeCache,
    ) {
    }

    /**
     * @return WebhookInstruction[]
     */
    public function findAll(): array
    {
        $cacheIdentifier = $this->cacheIdentifierPrefix . 'all';
        if (!$this->runtimeCache->has($cacheIdentifier)) {
            $data = $this->map(
                $this->getQueryBuilder()->executeQuery()->fetchAllAssociative()
            );
            $this->runtimeCache->set($cacheIdentifier, $data);
        } else {
            $data = $this->runtimeCache->get($cacheIdentifier);
        }
        return $data;
    }

    public function countAll(): int
    {
        return (int)$this->getQueryBuilder(false)
            ->count('*')
            ->executeQuery()
            ->fetchOne();
    }

    public function getWebhookRecords(?WebhookDemand $demand = null): array
    {
        return $demand !== null ? $this->findByDemand($demand) : $this->findAll();
    }

    /**
     * @return WebhookInstruction[]
     */
    public function findByDemand(WebhookDemand $demand): array
    {
        return $this->map($this->getQueryBuilderForDemand($demand)
            ->setMaxResults($demand->getLimit())
            ->setFirstResult($demand->getOffset())
            ->executeQuery()
            ->fetchAllAssociative());
    }

    /**
     * @return array<string, WebhookInstruction>
     */
    protected function getConfiguredWebhooks(): array
    {
        $webhooks = [];
        foreach ($this->findAll() as $webhook) {
            $webhooks[$webhook->getIdentifier()] = $webhook;
        }
        return $webhooks;
    }

    /**
     * @return array<string, WebhookInstruction>
     */
    public function getConfiguredWebhooksByType(string $type): array
    {
        $webhooks = $this->getConfiguredWebhooks();
        return array_filter($webhooks, static fn ($webhook) => $webhook->getWebhookType()?->getServiceName() === $type);
    }

    protected function getQueryBuilderForDemand(WebhookDemand $demand): QueryBuilder
    {
        $queryBuilder = $this->getQueryBuilder(false);
        $queryBuilder->orderBy(
            $demand->getOrderField(),
            $demand->getOrderDirection()
        );
        // Ensure deterministic ordering.
        if ($demand->getOrderField() !== 'uid') {
            $queryBuilder->addOrderBy('uid', 'asc');
        }

        $constraints = [];
        if ($demand->hasName()) {
            $escapedLikeString = '%' . $queryBuilder->escapeLikeWildcards($demand->getName()) . '%';
            $constraints[] = $queryBuilder->expr()->or(
                $queryBuilder->expr()->like(
                    'name',
                    $queryBuilder->createNamedParameter($escapedLikeString)
                ),
                $queryBuilder->expr()->like(
                    'description',
                    $queryBuilder->createNamedParameter($escapedLikeString)
                )
            );
        }
        if ($demand->hasWebhookType()) {
            $constraints[] = $queryBuilder->expr()->eq(
                'webhook_type',
                $queryBuilder->createNamedParameter($demand->getWebhookType())
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

    protected function mapSingleRow(array $row): WebhookInstruction
    {
        $row = BackendUtility::convertDatabaseRowValuesToPhp('sys_webhook', $row);
        return WebhookInstructionFactory::createFromRow($row);
    }

    protected function getQueryBuilder(bool $addDefaultOrderByClause = true): QueryBuilder
    {
        $queryBuilder = $this->connectionPool
            ->getQueryBuilderForTable('sys_webhook');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder->select('*')->from('sys_webhook');
        if ($addDefaultOrderByClause) {
            $queryBuilder
                ->orderBy('name', 'asc')
                // Ensure deterministic ordering.
                ->addOrderBy('uid', 'asc');
        }
        return $queryBuilder;
    }
}
