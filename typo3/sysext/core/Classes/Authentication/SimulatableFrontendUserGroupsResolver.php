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

namespace TYPO3\CMS\Core\Authentication;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

/**
 * This class finds the frontend user groups that a backend user may simulate.
 * The list contains the groups on the pages that the backend user has the
 * permission `Permission::PAGE_SHOW` for. The default restrictions of the
 * query builder apply to the groups and to the pages. Thus the list does not
 * contain these groups:
 *
 * - a hidden group
 * - a group on a hidden page
 * - a group on a page with a start time in the future
 * - a group on a page with an end time in the past
 * - a group on the root level, because it is not on a page
 *
 * @internal
 */
#[Autoconfigure(public: true)]
final readonly class SimulatableFrontendUserGroupsResolver
{
    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * @return list<array{uid: int, title: string}> ordered by title
     */
    public function resolve(BackendUserAuthentication $backendUser): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_groups');
        $rows = $queryBuilder
            ->select('fe_groups.uid', 'fe_groups.title')
            ->from('fe_groups')
            ->innerJoin(
                'fe_groups',
                'pages',
                'pages',
                $queryBuilder->expr()->eq('pages.uid', $queryBuilder->quoteIdentifier('fe_groups.pid'))
            )
            ->where($backendUser->getPagePermsClause(Permission::PAGE_SHOW))
            ->orderBy('fe_groups.title')
            ->executeQuery()
            ->fetchAllAssociative();
        $groups = [];
        foreach ($rows as $row) {
            $groups[] = ['uid' => (int)$row['uid'], 'title' => (string)$row['title']];
        }
        return $groups;
    }
}
