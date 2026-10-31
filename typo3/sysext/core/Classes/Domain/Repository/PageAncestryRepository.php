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

use Doctrine\DBAL\Exception as DoctrineException;
use Doctrine\DBAL\Platforms\TrimMode;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Exception\Page\BrokenRootLineException;
use TYPO3\CMS\Core\Exception\Page\CircularRootLineException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Fetches the ancestry of a page - the page itself and all its parent pages up to the
 * virtual tree root - with one recursive common table expression (CTE).
 *
 * Records are workspace-resolved for the given workspace, but neither language overlays,
 * mount points, relations nor any kind of access restriction besides the deleted flag
 * are applied: this is the raw building block for frontend and backend consumers.
 *
 * @internal not part of the public TYPO3 Core API.
 */
#[Autoconfigure(public: true)]
readonly class PageAncestryRepository
{
    // Note that having a nesting depth of 100 is quite high, but defined to be more on a "safe" side here. Main goal
    // is to mitigate unforeseen recursion which are not covered by the ancestor guard (checking page uid in path).
    public const int MAX_TRAVERSAL_LEVELS = 100;

    public function __construct(
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * Yields the workspace-resolved page records, starting with the page itself and ending with
     * the page on the root level (pid=0), as long as the ancestry is not broken.
     *
     * The recursive CTE does the workspace overlay handling within the traversal. Workspace
     * records keep the live uid, with `_ORIG_uid` pointing to the version record and `_ORIG_pid`
     * holding the live pid of a moved page. Both are only set if they carry a value.
     *
     * Rows are yielded one by one, so a consumer stopping early (for example on a mounted page)
     * never sees exceptions caused by records further up.
     *
     * The created recursive CTE contains two guards against cycling ancestry:
     *
     * Guard 1 - ancestor path guard:
     * ------------------------------
     *
     * During the recursing a uid path is created (`__CTE_PATH__`) and in case that a page is already contained in the
     * parent record `__CTE_PATH__` the flag field `__CTE_IS_CYCLE__` is set to 1, otherwise it is 0.
     *
     * If the parent record `__CTE_IS_CYLCE__` is `1` no records are retrieved which retrieves a duplicate page record
     * except of this flag. During the record retrieval {@see CircularRootLineException} is thrown to abort and state
     * this crucial data corruption.
     *
     * This guard is designed to abort cycling recursion on the database side as early as possible while still transport
     * the cycling issue information to this method.
     *
     * Guard 2 - max recursion level guard:
     * ------------------------------------
     *
     * During the recursive CTE handling recursion level info is created (`__CTE_LEVEL__`) and used as a hard recursion
     * limit fence. That means, if the level reaches {@see self::MAX_TRAVERSAL_LEVELS} no more page records are
     * received. In the case that neither GUARD 1, nor PID=0 abort criteria is reached and max level hit a meaningful
     * {@see BrokenRootLineException} exception is thrown.
     *
     * @return \Generator<int, array<string, mixed>>
     * @throws BrokenRootLineException
     * @throws CircularRootLineException
     * @throws DoctrineException
     */
    public function findAncestry(int $pageId, int $workspaceId): \Generator
    {
        if ($pageId === 0) {
            return;
        }
        $cte = $this->createQueryBuilder('pages');
        $cte->getRestrictions()->removeAll();
        $expr = $cte->expr();
        $pagesFields = $this->connectionPool
            ->getConnectionForTable('pages')
            ->getSchemaInformation()
            ->listTableColumnNames('pages');
        $resolvedPagesFields = array_filter($pagesFields, static fn($value) => $value !== 'uid');
        array_walk(
            $resolvedPagesFields,
            static function (string &$value, int|string $key, string $prefixAlias) {
                $value = sprintf('%s.%s', $prefixAlias, $value);
            },
            'finalpages',
        );
        $cte
            ->typo3_withRecursive(
                'cte',
                // Unique rows is omitted to avoid superfluous distinct row determination by the database query executor
                // due to having level based data in the traversal part they would be distinct anyway. Duplicates are
                // sorted out by the implemented ancestor guard - see self::createTraversalQueryBuilder().
                false,
                $this->createInitialQueryBuilder($cte, $pageId, $workspaceId),
                $this->createTraversalQueryBuilder($cte, $workspaceId),
                [
                    // data fields
                    $cte->quoteIdentifier('uid'),
                    $cte->quoteIdentifier('pid'),
                    // workspace handling values
                    $cte->quoteIdentifier('_ORIG_pid'),
                    $cte->quoteIdentifier('_ORIG_uid'),
                    // recursive handling fields
                    $cte->quoteIdentifier('__CTE_JOIN_UID__'),
                    $cte->quoteIdentifier('__CTE_LEVEL__'),
                    $cte->quoteIdentifier('__CTE_PATH__'),
                    $cte->quoteIdentifier('__CTE_IS_CYCLE__'),
                ],
            )
            ->select(...[
                'cte.uid',
                ...$resolvedPagesFields,
                'cte._ORIG_uid',
                'cte._ORIG_pid',
                // Cycle detection guard implemented manually due to the fact that CTE cycle is not implemented by
                // all supported Database vendors at all or not following the SQL standard. The guard stops cycling
                // ancestry early to avoid use-less cycle retrieval until __CTE_LEVEL__ reaches maximal hard level.
                'cte.__CTE_PATH__',
                'cte.__CTE_IS_CYCLE__',
                'cte.__CTE_LEVEL__',
            ])
            ->from('cte')
            // The recursive CTE builds up the CTE level starting from current record as level 1 and incrementing the
            // level for each parent record, ordering by level ascending yields the page first and the root page last.
            ->orderBy('cte.__CTE_LEVEL__', 'ASC')
            ->addOrderBy('cte.uid', 'ASC')
            ->innerJoin(
                'cte',
                'pages',
                'finalpages',
                $expr->eq('finalpages.uid', $cte->quoteIdentifier('cte.__CTE_JOIN_UID__'))
            );
        $result = $cte->executeQuery();
        try {
            while ($record = $result->fetchAssociative()) {
                if ((bool)($record['__CTE_IS_CYCLE__'] ?? false)) {
                    throw new CircularRootLineException(
                        'Circular connection in rootline for page with uid ' . $pageId . ' found. '
                        . 'Check your mountpoint configuration and page data with pid value pointing to a sub page.',
                        1343464103
                    );
                }
                if ((int)($record['__CTE_LEVEL__'] ?? 0) >= self::MAX_TRAVERSAL_LEVELS && (int)$record['pid'] !== 0) {
                    throw new BrokenRootLineException(
                        sprintf(
                            'Broken rootline. Could not resolve full rootline for uid %s. '
                            . 'Reached max traversal level %s without ending on pid 0.',
                            $pageId,
                            self::MAX_TRAVERSAL_LEVELS,
                        ),
                        1722118090,
                    );
                }
                yield $this->cleanRecord($record);
            }
        } finally {
            $result->free();
        }
    }

    /**
     * Removes the CTE guard fields, and the workspace helper fields unless they carry a value,
     * to mimic the way PHP methods throughout the TYPO3 Core add these fields.
     */
    private function cleanRecord(array $record): array
    {
        unset(
            $record['__CTE_PATH__'],
            $record['__CTE_IS_CYCLE__'],
            $record['__CTE_LEVEL__'],
        );
        foreach (['_ORIG_uid', '_ORIG_pid'] as $nullableField) {
            if (array_key_exists($nullableField, $record) && $record[$nullableField] === null) {
                unset($record[$nullableField]);
            }
        }
        return $record;
    }

    private function createQueryBuilder(string $tableName): QueryBuilder
    {
        return $this->connectionPool->getQueryBuilderForTable($tableName);
    }

    /**
     * Creates the QueryBuilder for the recursive CTE initial part within {@see self::findAncestry()}.
     *
     * Not to be used standalone. {@see self::findAncestry()} method docblock for overall CTE information.
     */
    private function createInitialQueryBuilder(QueryBuilder $cte, int $pageId, int $workspaceId): QueryBuilder
    {
        $expr = $cte->expr();
        $initial = $this->createQueryBuilder('pages');
        $initial->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        if ($workspaceId === 0) {
            // Return simplified initial expression for live workspace resolving only.
            return $initial
                ->selectLiteral(...[
                    // data fields
                    $cte->quoteIdentifier('uid'),
                    $cte->quoteIdentifier('pid'),
                    // adding fake columns to be compatible with workspace aware columns
                    $expr->as('null', '_ORIG_pid'),
                    $expr->as('null', '_ORIG_uid'),
                    // recursive handling fields
                    $expr->as($cte->quoteIdentifier('uid'), '__CTE_JOIN_UID__'),
                    $expr->castInt('1', '__CTE_LEVEL__'),
                    // Cycle detection guard implemented manually due to the fact that CTE cycle is not implemented by
                    // all supported Database vendors at all or not following the SQL standard. The guard stops cycling
                    // ancestry early to avoid use-less cycle retrieval until __CTE_LEVEL__ reaches maximal hard level.
                    $expr->castText($cte->quoteIdentifier('uid'), '__CTE_PATH__'),
                    // Because of Postgres we need to have this cte colum boolean type and thus needing a comparison here
                    $expr->castInt('0 <> 0', '__CTE_IS_CYCLE__'),
                ])
                ->from('pages')
                ->where(...[
                    $expr->eq('uid', $cte->createNamedParameter($pageId, Connection::PARAM_INT)),
                    // only select live workspace
                    $expr->eq('t3ver_wsid', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                ]);
        }

        $initial
            ->selectLiteral(...[
                // data fields
                $cte->quoteIdentifier('live.uid'),
                $cte->quoteIdentifier('workspace_resolved.pid'),
                // workspace handling
                // For move pointers, store the actual live PID in the _ORIG_pid
                // The only place where PID is actually different in a workspace
                $expr->if(
                    $expr->and(
                        $expr->isNotNull('workspace.t3ver_state'),
                        $expr->eq(
                            'workspace.t3ver_state',
                            $cte->createNamedParameter(VersionState::MOVE_POINTER->value, Connection::PARAM_INT),
                        ),
                    ),
                    $cte->quoteIdentifier('live.pid'),
                    'null',
                    '_ORIG_pid'
                ),
                // For versions of single elements or page+content, preserve online UID
                // (this will produce true "overlay" of element _content_, not any references)
                // For new versions there is no online counterpart
                $expr->if(
                    $expr->and(
                        $expr->isNotNull('workspace.t3ver_state'),
                        $expr->neq(
                            'workspace.t3ver_state',
                            $cte->createNamedParameter(VersionState::NEW_PLACEHOLDER->value, Connection::PARAM_INT),
                        ),
                    ),
                    $cte->quoteIdentifier('workspace.uid'),
                    'null',
                    '_ORIG_uid',
                ),
                // recursive handling fields
                $expr->if(
                    $expr->and(
                        $expr->isNotNull('workspace.t3ver_state'),
                        $expr->neq(
                            'workspace.t3ver_state',
                            $cte->createNamedParameter(VersionState::NEW_PLACEHOLDER->value, Connection::PARAM_INT),
                        ),
                    ),
                    $cte->quoteIdentifier('workspace_resolved.uid'),
                    $cte->quoteIdentifier('live.uid'),
                    '__CTE_JOIN_UID__',
                ),
                $expr->castInt('1', '__CTE_LEVEL__'),
                // Cycle detection guard implemented manually due to the fact that CTE cycle is not implemented by
                // all supported Database vendors at all or not following the SQL standard. The guard stops cycling
                // ancestry early to avoid use-less cycle retrieval until __CTE_LEVEL__ reaches maximal hard level.
                $expr->castText($cte->quoteIdentifier('live.uid'), '__CTE_PATH__'),
                // Because of Postgres we need to have this cte colum boolean type and thus needing a comparison here
                $expr->castInt('0 <> 0', '__CTE_IS_CYCLE__'),
            ])
            ->from('pages', 'source')
            ->innerJoin(
                'source',
                'pages',
                'live',
                $expr->eq(
                    'live.uid',
                    $expr->if(
                        $expr->and(
                            $expr->gt('source.t3ver_oid', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                            $expr->eq('source.t3ver_state', $cte->createNamedParameter(VersionState::MOVE_POINTER->value, Connection::PARAM_INT)),
                        ),
                        $cte->quoteIdentifier('source.t3ver_oid'),
                        $cte->quoteIdentifier('source.uid'),
                    )
                ),
            )
            ->leftJoin(
                'live',
                'pages',
                'workspace',
                $expr->and(
                    $expr->eq(
                        'workspace.t3ver_wsid',
                        $cte->createNamedParameter($workspaceId, Connection::PARAM_INT)
                    ),
                    $expr->or(
                        // t3ver_state=1 does not contain a t3ver_oid, and returns itself
                        $expr->and(
                            $expr->eq(
                                'workspace.uid',
                                $cte->quoteIdentifier('live.uid'),
                            ),
                            $expr->eq(
                                'workspace.t3ver_state',
                                $cte->createNamedParameter(VersionState::NEW_PLACEHOLDER->value, Connection::PARAM_INT),
                            ),
                        ),
                        $expr->eq(
                            'workspace.t3ver_oid',
                            $cte->quoteIdentifier('live.uid'),
                        )
                    )
                ),
            )
            ->innerJoin(
                'workspace',
                'pages',
                'workspace_resolved',
                (string)$expr->and(
                    $expr->eq(
                        'workspace_resolved.uid',
                        $expr->if(
                            $expr->isNotNull('workspace.uid'),
                            $cte->quoteIdentifier('workspace.uid'),
                            $cte->quoteIdentifier('live.uid'),
                        ),
                    ),
                ),
            )
            ->where(...[
                $expr->eq('source.uid', $cte->createNamedParameter($pageId, Connection::PARAM_INT)),
                $expr->in('source.t3ver_wsid', $cte->createNamedParameter([0, $workspaceId], Connection::PARAM_INT_ARRAY)),
                $expr->or(
                    // retrieve live workspace if no overlays exists
                    $expr->isNull('workspace.uid'),
                    // discard(omit) record if it turned out to be deleted in workspace
                    $expr->and(
                        $expr->isNotNull('workspace.uid'),
                        $expr->neq(
                            'workspace.t3ver_state',
                            $cte->createNamedParameter(VersionState::DELETE_PLACEHOLDER->value, Connection::PARAM_INT),
                        ),
                    ),
                ),
            ]);

        return $initial;
    }

    /**
     * Creates the QueryBuilder for the recursive CTE traversal part within {@see self::findAncestry()}.
     *
     * Not to be used standalone. {@see self::findAncestry()} method docblock for overall CTE information.
     */
    private function createTraversalQueryBuilder(QueryBuilder $cte, int $workspaceId): QueryBuilder
    {
        $expr = $cte->expr();
        $traversal = $this->createQueryBuilder('pages');
        $traversal->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        if ($workspaceId === 0) {
            $traversal
                ->selectLiteral(...[
                    // data fields
                    $cte->quoteIdentifier('p.uid'),
                    $cte->quoteIdentifier('p.pid'),
                    // adding fake columns to be compatible with workspace aware columns
                    $expr->as('null', '_ORIG_pid'),
                    $expr->as('null', '_ORIG_uid'),
                    // recursive handling fields
                    $expr->as($cte->quoteIdentifier('p.uid'), '__CTE_JOIN_UID__'),
                    $expr->castInt(sprintf('(%s + 1)', $expr->castInt($cte->quoteIdentifier('c.__CTE_LEVEL__'))), '__CTE_LEVEL__'),
                    // Cycle detection guard implemented manually due to the fact that CTE cycle is not implemented by
                    // all supported Database vendors at all or not following the SQL standard. The guard stops cycling
                    // ancestry early to avoid use-less cycle retrieval until __CTE_LEVEL__ reaches maximal hard level.
                    $expr->castText(
                        $expr->concat(
                            $expr->trim('c.__CTE_PATH__', TrimMode::TRAILING, ' '),
                            $cte->quote(','),
                            $cte->quoteIdentifier('p.uid')
                        ),
                        '__CTE_PATH__'
                    ),
                    $expr->as(
                        sprintf(
                            '%s <> 0',
                            $expr->castInt(sprintf(
                                // Nesting is needed because inSet() creates a comparision expression return a boolean value
                                '(%s)',
                                $expr->inSet(
                                    'c.__CTE_PATH__',
                                    $cte->quoteIdentifier('p.uid'),
                                    true,
                                ),
                            )),
                        ),
                        '__CTE_IS_CYCLE__'
                    ),
                ])
                ->from('cte', 'c')
                ->innerJoin(
                    'c',
                    'pages',
                    'p',
                    (string)$expr->and(
                        $expr->eq('p.uid', $cte->quoteIdentifier('c.pid')),
                        // only select live workspace
                        $expr->eq('p.t3ver_wsid', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                        // ensure that resolve page is not the child page
                        $expr->neq('c.uid', $cte->quoteIdentifier('p.uid')),
                    )
                )
                ->where(...[
                    // If last parent has been detected as start of a recursive cycle, stop here. Note that this is done to
                    // keep the cycle detection value in the result set to allow proper handling later on retrieved rows.
                    $expr->eq('c.__CTE_IS_CYCLE__', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                    // do not try to fetch page with uid 0, which is the virtual tree root point
                    $expr->neq('c.pid', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                    // place a maximal traversal level guard against invalid cycling rootlines to mitigate endless recursion
                    $expr->lt('c.__CTE_LEVEL__', $cte->createNamedParameter(self::MAX_TRAVERSAL_LEVELS, Connection::PARAM_INT)),
                ]);

            return $traversal;
        }

        $traversal
            ->selectLiteral(...[
                // data fields
                $cte->quoteIdentifier('traversal_live.uid'),
                $cte->quoteIdentifier('traversal_workspace_resolved.pid'),
                // workspace handling
                // For move pointers, store the actual live PID in the _ORIG_pid
                // The only place where PID is actually different in a workspace
                $expr->if(
                    $expr->and(
                        $expr->isNotNull('traversal_workspace.t3ver_state'),
                        $expr->eq(
                            'traversal_workspace.t3ver_state',
                            $cte->createNamedParameter(VersionState::MOVE_POINTER->value, Connection::PARAM_INT),
                        ),
                    ),
                    $cte->quoteIdentifier('traversal_live.pid'),
                    'null',
                    '_ORIG_pid'
                ),
                // For versions of single elements or page+content, preserve online UID
                // (this will produce true "overlay" of element _content_, not any references)
                // For new versions there is no online counterpart
                $expr->if(
                    $expr->and(
                        $expr->isNotNull('traversal_workspace.t3ver_state'),
                        $expr->neq(
                            'traversal_workspace.t3ver_state',
                            $cte->createNamedParameter(VersionState::NEW_PLACEHOLDER->value, Connection::PARAM_INT),
                        ),
                    ),
                    $cte->quoteIdentifier('traversal_workspace.uid'),
                    'null',
                    '_ORIG_uid',
                ),
                // recursive handling fields
                $expr->if(
                    $expr->and(
                        $expr->isNotNull('traversal_workspace.t3ver_state'),
                        $expr->neq(
                            'traversal_workspace.t3ver_state',
                            $cte->createNamedParameter(VersionState::NEW_PLACEHOLDER->value, Connection::PARAM_INT),
                        ),
                    ),
                    $cte->quoteIdentifier('traversal_workspace_resolved.uid'),
                    $cte->quoteIdentifier('traversal_live.uid'),
                    '__CTE_JOIN_UID__',
                ),
                $expr->castInt(sprintf('(%s + 1)', $expr->castInt($cte->quoteIdentifier('traversal_c.__CTE_LEVEL__'))), '__CTE_LEVEL__'),
                // Cycle detection guard implemented manually due to the fact that CTE cycle is not implemented by
                // all supported Database vendors at all or not following the SQL standard. The guard stops cycling
                // ancestry early to avoid use-less cycle retrieval until __CTE_LEVEL__ reaches maximal hard level.
                $expr->castText(
                    $expr->concat(
                        $expr->trim('traversal_c.__CTE_PATH__', TrimMode::TRAILING, ' '),
                        $cte->quote(','),
                        $cte->quoteIdentifier('traversal_live.uid')
                    ),
                    '__CTE_PATH__'
                ),
                $expr->as(
                    sprintf(
                        '%s <> 0',
                        $expr->castInt(sprintf(
                            // Nesting is needed because inSet() creates a comparision expression return a boolean value
                            '(%s)',
                            $expr->inSet(
                                'traversal_c.__CTE_PATH__',
                                $cte->quoteIdentifier('traversal_live.uid'),
                                true,
                            )
                        )),
                    ),
                    '__CTE_IS_CYCLE__'
                ),
            ])
            ->from('cte', 'traversal_c')
            ->innerJoin(
                'traversal_c',
                'pages',
                'traversal_source',
                (string)$expr->and(
                    $expr->eq(
                        'traversal_source.uid',
                        $cte->quoteIdentifier('traversal_c.pid')
                    ),
                    $expr->in('traversal_source.t3ver_wsid', $cte->createNamedParameter([0, $workspaceId], Connection::PARAM_INT_ARRAY)),
                ),
            )
            ->innerJoin(
                'traversal_source',
                'pages',
                'traversal_live',
                $expr->eq(
                    'traversal_live.uid',
                    $expr->if(
                        $expr->and(
                            $expr->gt('traversal_source.t3ver_oid', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                            $expr->eq('traversal_source.t3ver_state', $cte->createNamedParameter(VersionState::MOVE_POINTER->value, Connection::PARAM_INT)),
                        ),
                        $cte->quoteIdentifier('traversal_source.t3ver_oid'),
                        $cte->quoteIdentifier('traversal_source.uid'),
                    )
                ),
            )
            ->leftJoin(
                'traversal_live',
                'pages',
                'traversal_workspace',
                (string)$expr->and(
                    $expr->eq(
                        'traversal_workspace.t3ver_wsid',
                        $cte->createNamedParameter($workspaceId, Connection::PARAM_INT)
                    ),
                    $expr->or(
                        // t3ver_state=1 does not contain a t3ver_oid, and returns itself
                        $expr->and(
                            $expr->eq(
                                'traversal_workspace.uid',
                                $cte->quoteIdentifier('traversal_live.uid'),
                            ),
                            $expr->eq(
                                'traversal_workspace.t3ver_state',
                                $cte->createNamedParameter(VersionState::NEW_PLACEHOLDER->value, Connection::PARAM_INT),
                            ),
                        ),
                        $expr->eq(
                            'traversal_workspace.t3ver_oid',
                            $cte->quoteIdentifier('traversal_live.uid'),
                        )
                    )
                ),
            )
            ->innerJoin(
                'traversal_workspace',
                'pages',
                'traversal_workspace_resolved',
                (string)$expr->and(
                    $expr->eq(
                        'traversal_workspace_resolved.uid',
                        $expr->if(
                            $expr->isNotNull('traversal_workspace.uid'),
                            $cte->quoteIdentifier('traversal_workspace.uid'),
                            $cte->quoteIdentifier('traversal_live.uid'),
                        ),
                    ),
                ),
            )
            ->where(...[
                // If last parent has been detected as start of a recursive cycle, stop here. Note that this is done to
                // keep the cycle detection value in the result set to allow proper handling later on retrieved rows.
                $expr->eq('traversal_c.__CTE_IS_CYCLE__', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                // do not try to fetch page with uid 0, which is the virtual tree root point
                $expr->neq('traversal_c.pid', $cte->createNamedParameter(0, Connection::PARAM_INT)),
                // place a maximal traversal level guard against invalid cycling rootlines to mitigate endless recursion
                $expr->lt('traversal_c.__CTE_LEVEL__', $cte->createNamedParameter(self::MAX_TRAVERSAL_LEVELS, Connection::PARAM_INT)),
                // workspace handling
                $expr->or(
                    // retrieve live workspace if no overlays exists
                    $expr->isNull('traversal_workspace.uid'),
                    // discard(omit) record if it turned out to be deleted in workspace
                    $expr->and(
                        $expr->isNotNull('traversal_workspace.uid'),
                        $expr->neq(
                            'traversal_workspace.t3ver_state',
                            $cte->createNamedParameter(VersionState::DELETE_PLACEHOLDER->value, Connection::PARAM_INT),
                        ),
                    ),
                ),
            ]);

        return $traversal;
    }
}
