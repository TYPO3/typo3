<?php
declare (strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Database\Query\Restriction;

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

use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;

class BackendWorkspaceRestrictionTest extends AbstractRestrictionTestCase
{
    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function buildExpressionAddsLiveWorkspaceWhereClause()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => 2,
        ];
        $subject = new BackendWorkspaceRestriction(0);
        $expression = $subject->buildExpression(['aTable' => ''], $this->expressionBuilder);
        $this->assertSame('("aTable"."t3ver_wsid" = 0) OR ("aTable"."t3ver_state" <= 0)', (string)$expression);
    }

    /**
     * @test
     */
    public function buildExpressionAddsNonLiveWorkspaceWhereClause()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => 2,
        ];
        $subject = new BackendWorkspaceRestriction(42);
        $expression = $subject->buildExpression(['aTable' => ''], $this->expressionBuilder);
        $this->assertSame('("aTable"."t3ver_wsid" = 42) OR ("aTable"."t3ver_state" <= 0)', (string)$expression);
    }

    /**
     * @test
     */
    public function buildExpressionAddsLiveWorkspaceLimitedWhereClause()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => 2,
        ];
        $subject = new BackendWorkspaceRestriction(0, false);
        $expression = $subject->buildExpression(['aTable' => ''], $this->expressionBuilder);
        $this->assertSame('("aTable"."t3ver_wsid" = 0) AND ("aTable"."pid" <> -1)', (string)$expression);
    }

    /**
     * @test
     */
    public function buildExpressionAddsNonLiveWorkspaceLimitedWhereClause()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => 2,
        ];
        $subject = new BackendWorkspaceRestriction(42, false);
        $expression = $subject->buildExpression(['aTable' => ''], $this->expressionBuilder);
        $this->assertSame('("aTable"."t3ver_wsid" = 42) AND ("aTable"."pid" = -1)', (string)$expression);
    }
}
