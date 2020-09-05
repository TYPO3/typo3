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

namespace TYPO3\CMS\Core\Database\Query\Restriction;

use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Restriction to make queries workspace-aware. This restriction is new compared to the "older"
 * FrontendWorkspaceRestriction and BackendWorkspaceRestriction in a way that it ALWAYS fetches the live version,
 * plus in current workspace the workspace records).
 * It does not care about the state, as this should be done by overlays.
 *
 * As workspaces cannot be fully overlaid within ONE query, this query does the following:
 * - In live context, only fetch published records
 * - In a workspace, fetch all LIVE records and all workspace records which do not have "-1" (= all new placeholders get fetched as well)
 *
 * This means, that all records which are fetched need to run through either
 * - BackendUtility::getRecordWSOL() (when having one or a few records)
 * - PageRepository->versionOL()
 * - PlainDataResolver (when having lots of records)
 */
class WorkspaceRestriction implements QueryRestrictionInterface
{
    /**
     * @var int
     */
    protected $workspaceId;

    /**
     * @param int $workspaceId
     */
    public function __construct(int $workspaceId = 0)
    {
        $this->workspaceId = (int)$workspaceId;
    }

    /**
     * Main method to build expressions for given tables
     *
     * @param array $queriedTables Array of tables, where array key is table alias and value is a table name
     * @param ExpressionBuilder $expressionBuilder Expression builder instance to add restrictions with
     * @return CompositeExpression The result of query builder expression(s)
     */
    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];
        foreach ($queriedTables as $tableAlias => $tableName) {
            if (empty($GLOBALS['TCA'][$tableName]['ctrl']['versioningWS'] ?? false)) {
                continue;
            }
            if ($this->workspaceId === 0) {
                // Only include records from live workspace
                $workspaceIdExpression = $expressionBuilder->eq($tableAlias . '.t3ver_wsid', 0);
            } else {
                // Include live records PLUS records from the given workspace
                $workspaceIdExpression = $expressionBuilder->in(
                    $tableAlias . '.t3ver_wsid',
                    [0, $this->workspaceId]
                );
            }
            // Always filter out versioned records that have an "offline" record
            // But include moved records AND newly created records (t3ver_oid=0)
            $constraints[] = $expressionBuilder->andX(
                $workspaceIdExpression,
                $expressionBuilder->orX(
                    $expressionBuilder->eq(
                        $tableAlias . '.t3ver_oid',
                        0
                    ),
                    $expressionBuilder->eq(
                        $tableAlias . '.t3ver_state',
                        VersionState::MOVE_POINTER
                    )
                )
            );
        }
        return $expressionBuilder->andX(...$constraints);
    }
}
