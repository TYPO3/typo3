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

namespace TYPO3\CMS\Core\Domain\Repository;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;

/**
 * Fetches the descendants of pages - their subpages, the subpages of these and so on -
 * with one recursive common table expression (CTE).
 *
 * Only pages of the default language are part of the page tree. Pages are resolved for
 * the given workspace: a page with a workspace version is found at the position of its
 * version, pages new in the workspace are included, and pages deleted in the workspace
 * are left out together with their subpages, unless they are requested.
 *
 * Access restrictions are not applied by default, callers hand them in as constraints:
 * a page not matching them is left out together with its subpages.
 *
 * @internal The building block for backend and frontend consumers, not part of the public API yet
 */
#[Autoconfigure(public: true)]
final readonly class PageDescendantsRepository
{
    public const int MAX_TRAVERSAL_LEVELS = RecordDescendantsRepository::MAX_TRAVERSAL_LEVELS;

    public function __construct(
        private RecordDescendantsRepository $recordDescendantsRepository,
    ) {}

    /**
     * Returns the descendants of the given pages, without the given pages themselves,
     * ordered depth-first: each page is followed by its subpages, pages with the same
     * parent page are ordered by their "sorting" value.
     *
     * Each row holds the uid of the live page as "uid", the parent page uid (in the
     * workspace) as "pid", "sorting" and the level below the given pages as "_LEVEL",
     * starting with 1. A page with a workspace version additionally holds the uid of the
     * version as "_ORIG_uid", other requested fields are then taken from the version.
     *
     * @param list<int> $pageIds Pages to start from, their descendants are returned in this order
     * @param int $depth Number of levels below the given pages, 1 for their direct subpages only
     * @param (\Closure(ExpressionBuilder, string): string)|null $liveConstraint SQL constraint
     *        evaluated on the live page, receiving the table alias of the live page ("pages"),
     *        e.g. for the page permissions, which are not versioned. Column names may be
     *        unqualified or qualified with "pages"
     * @param list<QueryRestrictionInterface> $restrictions Restrictions evaluated on the page
     *        (the workspace version, if there is one), e.g. to leave out hidden pages
     * @param list<string> $fields Additional fields of the page records to return, '*' for all
     * @param bool $includeDeleted Whether deleted pages and their subpages are included
     * @param bool $includeDeletedInWorkspace Whether pages deleted in the workspace and their subpages are included
     * @return list<array<string, mixed>>
     */
    public function findDescendants(
        array $pageIds,
        int $depth,
        int $workspaceId,
        ?\Closure $liveConstraint = null,
        array $restrictions = [],
        array $fields = [],
        bool $includeDeleted = false,
        bool $includeDeletedInWorkspace = false,
    ): array {
        return $this->recordDescendantsRepository->findDescendants(
            'pages',
            'pid',
            $pageIds,
            $depth,
            $workspaceId,
            $liveConstraint,
            $restrictions,
            $fields,
            $includeDeleted,
            $includeDeletedInWorkspace,
        );
    }

    /**
     * Returns a constraint for a field holding a page uid - for example the "pid" of the
     * records of a table - to be one of the given pages or one of their descendants, down
     * to $depth levels below the given pages.
     *
     * The descendants are resolved by the database within the query, as a subquery with a
     * recursive common table expression, so no list of page uids is built. The descendants
     * are resolved as by findDescendants(). If the query builder does not use the database
     * connection of the pages table, the descendants are fetched first and the constraint
     * holds their uids.
     *
     * @param QueryBuilder $queryBuilder Query the constraint is added to, its named parameters are used
     * @param string $fieldName Field holding a page uid, e.g. "pid" or "tt_content.pid"
     * @param list<int> $pageIds Pages, whose uids are always matched by the constraint
     * @param int $depth Number of levels below the given pages, 0 for the given pages only
     * @param (\Closure(ExpressionBuilder, string): string)|null $liveConstraint See findDescendants()
     * @param list<QueryRestrictionInterface> $restrictions See findDescendants()
     * @param bool $includeDeleted See findDescendants()
     * @param bool $includeDeletedInWorkspace See findDescendants()
     */
    public function createPageTreeConstraint(
        QueryBuilder $queryBuilder,
        string $fieldName,
        array $pageIds,
        int $depth,
        int $workspaceId,
        ?\Closure $liveConstraint = null,
        array $restrictions = [],
        bool $includeDeleted = false,
        bool $includeDeletedInWorkspace = false,
    ): string {
        return $this->recordDescendantsRepository->createTreeConstraint(
            $queryBuilder,
            $fieldName,
            'pages',
            'pid',
            $pageIds,
            $depth,
            $workspaceId,
            $liveConstraint,
            $restrictions,
            $includeDeleted,
            $includeDeletedInWorkspace,
        );
    }
}
