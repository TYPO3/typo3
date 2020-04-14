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

namespace TYPO3\CMS\Core\Tests\Unit\Database\Query\Restriction;

use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;

class WorkspaceRestrictionTest extends AbstractRestrictionTestCase
{
    /**
     * @test
     */
    public function buildExpressionAddsLiveWorkspaceWhereClause()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $subject = new WorkspaceRestriction(0);
        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('("aTable"."t3ver_wsid" = 0) AND ("aTable"."t3ver_oid" = 0)', (string)$expression);
    }

    /**
     * @test
     */
    public function buildExpressionAddsNonLiveWorkspaceWhereClause()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $subject = new WorkspaceRestriction(42);
        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('("aTable"."t3ver_wsid" IN (0, 42)) AND ("aTable"."t3ver_oid" = 0)', (string)$expression);
    }

    /**
     * @test
     */
    public function buildExpressionAddsLiveWorkspaceLimitedWhereClause()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => false,
        ];
        $subject = new WorkspaceRestriction(0);
        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('', (string)$expression);
    }

    /**
     * @test
     */
    public function buildExpressionAddsNonLiveWorkspaceLimitedWhereClause()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => false,
        ];
        $subject = new WorkspaceRestriction(42);
        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('', (string)$expression);
    }
}
