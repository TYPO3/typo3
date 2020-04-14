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

use TYPO3\CMS\Core\Database\Query\Restriction\FrontendWorkspaceRestriction;

/**
 * Test case
 */
class FrontendWorkspaceRestrictionTest extends AbstractRestrictionTestCase
{
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function buildExpressionAddsLiveWorkspaceWhereClause()
    {
        $GLOBALS['TCA'] = [
            'aTable' => [
                'ctrl' => [
                    'versioningWS' => true,
                ],
            ]
        ];

        $subject = new FrontendWorkspaceRestriction(0);
        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('("aTable"."t3ver_state" <= 0) AND ("aTable"."t3ver_oid" = 0)', (string)$expression);
    }

    /**
     * @test
     */
    public function buildExpressionAddsNonLiveWorkspaceWhereClause()
    {
        $GLOBALS['TCA'] = [
            'aTable' => [
                'ctrl' => [
                    'versioningWS' => true,
                ],
            ]
        ];

        $subject = new FrontendWorkspaceRestriction(42, true);
        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('(("aTable"."t3ver_wsid" = 0) OR ("aTable"."t3ver_wsid" = 42)) AND ("aTable"."t3ver_oid" = 0)', (string)$expression);
    }

    /**
     * @test
     */
    public function buildExpressionAddsNonLiveWorkspaceExclusiveWhereClause()
    {
        $GLOBALS['TCA'] = [
            'aTable' => [
                'ctrl' => [
                    'versioningWS' => true,
                ],
            ]
        ];

        $subject = new FrontendWorkspaceRestriction(42, true, false);
        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('("aTable"."t3ver_wsid" = 0) OR ("aTable"."t3ver_wsid" = 42)', (string)$expression);
    }
}
