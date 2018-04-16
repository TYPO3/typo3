<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Database\Query\Restriction;

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

use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Restriction to filter records for fronted workspaces preview
 */
class FrontendWorkspaceRestriction implements QueryRestrictionInterface
{
    /**
     * @var int
     */
    protected $workspaceId;

    /**
     * @var bool
     */
    protected $includeRowsForWorkspacePreview;

    /**
     * @var bool
     */
    protected $enforceLiveRowsOnly;

    /**
     * @param int $workspaceId (PageRepository::$versioningWorkspaceId property)
     * @param bool $includeRowsForWorkspacePreview (PageRepository::$versioningPreview property)
     * @param bool $enforceLiveRowsOnly (!$noVersionPreview argument from PageRepository::enableFields()) This is ONLY for use in PageRepository class and most likely will be removed
     */
    public function __construct(int $workspaceId = null, bool $includeRowsForWorkspacePreview = null, bool $enforceLiveRowsOnly = true)
    {
        $this->workspaceId = $workspaceId === null ? $GLOBALS['TSFE']->sys_page->versioningWorkspaceId : $workspaceId;
        $this->includeRowsForWorkspacePreview = $includeRowsForWorkspacePreview === null ? $GLOBALS['TSFE']->sys_page->versioningPreview : $includeRowsForWorkspacePreview;
        $this->enforceLiveRowsOnly = $enforceLiveRowsOnly;
    }

    /**
     * Main method to build expressions for given tables
     * Evaluates the ctrl/versioningWS flag of the table and adds various workspace related restrictions if set
     *
     * @param array $queriedTables Array of tables, where array key is table alias and value is a table name
     * @param ExpressionBuilder $expressionBuilder Expression builder instance to add restrictions with
     * @return CompositeExpression The result of query builder expression(s)
     */
    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];
        foreach ($queriedTables as $tableAlias => $tableName) {
            $workspaceEnabled = $GLOBALS['TCA'][$tableName]['ctrl']['versioningWS'] ?? null;
            if (!empty($workspaceEnabled)) {
                if (!$this->includeRowsForWorkspacePreview) {
                    // Filter out placeholder records (new/moved/deleted items)
                    // in case we are NOT in a versioning preview (That means we are online!)
                    $constraints[] = $expressionBuilder->lte(
                        $tableAlias . '.t3ver_state',
                        // Trigger __toString(), then cast int
                        (int)(string)new VersionState(VersionState::DEFAULT_STATE)
                    );
                } elseif ($tableName !== 'pages') {
                    // Show only records of the live and current workspace in case we are in a versioning preview
                    $constraints[] = $expressionBuilder->orX(
                        $expressionBuilder->eq($tableAlias . '.t3ver_wsid', 0),
                        $expressionBuilder->eq($tableAlias . '.t3ver_wsid', (int)$this->workspaceId)
                    );
                }
                // Filter out versioned records
                if ($this->enforceLiveRowsOnly) {
                    $constraints[] = $expressionBuilder->neq($tableAlias . '.pid', -1);
                }
            }
        }
        return $expressionBuilder->andX(...$constraints);
    }
}
