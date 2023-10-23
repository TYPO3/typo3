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

namespace TYPO3\CMS\Core\TypoScript\IncludeTree;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DefaultRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\AfterTemplatesHaveBeenDeterminedEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Fetch relevant sys_template records from database by given page rootline.
 *
 * The result sys_template rows are fed to the SysTemplateTreeBuilder for processing.
 *
 * @internal: Internal structure. There is optimization potential and especially getSysTemplateRowsByRootline() will probably vanish later.
 */
final class SysTemplateRepository
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ConnectionPool $connectionPool,
        private readonly Context $context,
    ) {}

    /**
     * To calculate the TS include tree, we have to find sys_template rows attached to all rootline pages.
     * When there are multiple active sys_template rows on a page, we pick the one with the lower sorting
     * value.
     *
     * The query implementation below does that with *one* query for all rootline pages at once, not
     * one query per page. To handle the capabilities mentioned above, the query is a bit nifty, but
     * the implementation should scale nearly O(1) instead of O(n) with the rootline depth.
     *
     * @param ServerRequestInterface|null $request Nullable since Request is not a hard dependency ond just convenient for the Event
     *
     * @todo: It's potentially possible to get rid of this method in the frontend by joining sys_template
     *        into the Page rootline resolving as soon as it uses a CTE: This would save one query in *all* FE
     *        requests, even for fully-cached page requests.
     */
    public function getSysTemplateRowsByRootline(array $rootline, ?ServerRequestInterface $request = null): array
    {
        // Site-root node first!
        $rootLinePageIds = array_reverse(array_column($rootline, 'uid'));
        $sysTemplateRows = [];
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_template');
        $queryBuilder->setRestrictions($this->getSysTemplateQueryRestrictionContainer());
        $queryBuilder->select('sys_template.*')->from('sys_template');
        // Build a value list as joined table to have sorting based on list sorting
        $valueList = [];
        // @todo: Use type/int cast from expression builder to handle this dbms aware
        //        when support for this has been extracted from CTE PoC patch (sbuerk).
        $isPostgres = $queryBuilder->getConnection()->getDatabasePlatform() instanceof PostgreSQLPlatform;
        $pattern = $isPostgres ? '%s::int as uid, %s::int as sorting' : '%s as uid, %s as sorting';
        foreach ($rootLinePageIds as $sorting => $rootLinePageId) {
            $valueList[] = sprintf(
                $pattern,
                $queryBuilder->createNamedParameter($rootLinePageId, Connection::PARAM_INT),
                $queryBuilder->createNamedParameter($sorting, Connection::PARAM_INT)
            );
        }
        $valueList = 'SELECT ' . implode(' UNION ALL SELECT ', $valueList);
        $queryBuilder->getConcreteQueryBuilder()->innerJoin(
            $queryBuilder->quoteIdentifier('sys_template'),
            sprintf('(%s)', $valueList),
            $queryBuilder->quoteIdentifier('pidlist'),
            '(' . $queryBuilder->expr()->eq(
                'sys_template.pid',
                $queryBuilder->quoteIdentifier('pidlist.uid')
            ) . ')'
        );
        // Sort by rootline determined depth as sort criteria
        $queryBuilder->orderBy('pidlist.sorting', 'ASC')
            ->addOrderBy('sys_template.root', 'DESC')
            ->addOrderBy('sys_template.sorting', 'ASC');
        $lastPid = null;
        $queryResult = $queryBuilder->executeQuery();
        while ($sysTemplateRow = $queryResult->fetchAssociative()) {
            // We're retrieving *all* templates per pid, but need the first one only. The
            // order restriction above at least takes care they're after-each-other per pid.
            if ($lastPid === (int)$sysTemplateRow['pid']) {
                continue;
            }
            $lastPid = (int)$sysTemplateRow['pid'];
            $sysTemplateRows[] = $sysTemplateRow;
        }
        $event = new AfterTemplatesHaveBeenDeterminedEvent($rootline, $request, $sysTemplateRows);
        $this->eventDispatcher->dispatch($event);
        return $event->getTemplateRows();
    }

    /**
     * To calculate the TS include tree, we have to find sys_template rows attached to all rootline pages.
     * When there are multiple active sys_template rows on a page, we pick the one with the lower sorting
     * value.
     *
     * This variant is tailored for ext:tstemplate use. It allows "overriding" the sys_template uid of
     * the deepest page, which is used when multiple sys_template records on one page are managed in the Backend.
     *
     * The query implementation below does that with *one* query for all rootline pages at once, not
     * one query per page. To handle the capabilities mentioned above, the query is a bit nifty, but
     * the implementation should scale nearly O(1) instead of O(n) with the rootline depth.
     */
    public function getSysTemplateRowsByRootlineWithUidOverride(array $rootline, ?ServerRequestInterface $request, int $templateUidOnDeepestRootline): array
    {
        // Site-root node first!
        $rootLinePageIds = array_reverse(array_column($rootline, 'uid'));
        $templatePidOnDeepestRootline = $rootline[array_key_first($rootline)]['uid'];
        $sysTemplateRows = [];
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_template');
        $queryBuilder->setRestrictions($this->getSysTemplateQueryRestrictionContainer());
        $queryBuilder->select('sys_template.*')->from('sys_template');
        if ($templateUidOnDeepestRootline && $templatePidOnDeepestRootline) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->neq('sys_template.pid', $queryBuilder->createNamedParameter($templatePidOnDeepestRootline, Connection::PARAM_INT)),
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq('sys_template.pid', $queryBuilder->createNamedParameter($templatePidOnDeepestRootline, Connection::PARAM_INT)),
                        $queryBuilder->expr()->eq('sys_template.uid', $queryBuilder->createNamedParameter($templateUidOnDeepestRootline, Connection::PARAM_INT)),
                    ),
                ),
            );
        }
        // Build a value list as joined table to have sorting based on list sorting
        $valueList = [];
        // @todo: Use type/int cast from expression builder to handle this dbms aware
        //        when support for this has been extracted from CTE PoC patch (sbuerk).
        $isPostgres = $queryBuilder->getConnection()->getDatabasePlatform() instanceof PostgreSQLPlatform;
        $pattern = $isPostgres ? '%s::int as uid, %s::int as sorting' : '%s as uid, %s as sorting';
        foreach ($rootLinePageIds as $sorting => $rootLinePageId) {
            $valueList[] = sprintf(
                $pattern,
                $queryBuilder->createNamedParameter($rootLinePageId, Connection::PARAM_INT),
                $queryBuilder->createNamedParameter($sorting, Connection::PARAM_INT)
            );
        }
        $valueList = 'SELECT ' . implode(' UNION ALL SELECT ', $valueList);
        $queryBuilder->getConcreteQueryBuilder()->innerJoin(
            $queryBuilder->quoteIdentifier('sys_template'),
            sprintf('(%s)', $valueList),
            $queryBuilder->quoteIdentifier('pidlist'),
            '(' . $queryBuilder->expr()->eq(
                'sys_template.pid',
                $queryBuilder->quoteIdentifier('pidlist.uid')
            ) . ')'
        );
        // Sort by rootline determined depth as sort criteria
        $queryBuilder->orderBy('pidlist.sorting', 'ASC')
            ->addOrderBy('sys_template.root', 'DESC')
            ->addOrderBy('sys_template.sorting', 'ASC');
        $lastPid = null;
        $queryResult = $queryBuilder->executeQuery();
        while ($sysTemplateRow = $queryResult->fetchAssociative()) {
            // We're retrieving *all* templates per pid, but need the first one only. The
            // order restriction above at least takes care they're after-each-other per pid.
            if ($lastPid === (int)$sysTemplateRow['pid']) {
                continue;
            }
            $lastPid = (int)$sysTemplateRow['pid'];
            $sysTemplateRows[] = $sysTemplateRow;
        }
        // @todo: This event should be able to be fired even if the sys_template resolving is
        //        merged into an early middleware like "SiteResolver" which could join / sub-select
        //        pages together with sys_template directly, which would be possible if we manage
        //        to switch away from RootlineUtility usage in SiteResolver by using a CTE instead.
        $event = new AfterTemplatesHaveBeenDeterminedEvent($rootline, $request, $sysTemplateRows);
        $this->eventDispatcher->dispatch($event);
        return $event->getTemplateRows();
    }

    /**
     * Get sys_template record query builder restrictions.
     * Allows hidden records if enabled in context.
     */
    private function getSysTemplateQueryRestrictionContainer(): DefaultRestrictionContainer
    {
        $restrictionContainer = GeneralUtility::makeInstance(DefaultRestrictionContainer::class);
        if ($this->context->getPropertyFromAspect('visibility', 'includeHiddenContent', false)) {
            $restrictionContainer->removeByType(HiddenRestriction::class);
        }
        return $restrictionContainer;
    }
}
