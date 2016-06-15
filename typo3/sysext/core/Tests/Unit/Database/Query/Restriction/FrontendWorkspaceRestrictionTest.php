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

use TYPO3\CMS\Core\Database\Query\Restriction\FrontendWorkspaceRestriction;
use TYPO3\CMS\Frontend\Page\PageRepository;

class FrontendWorkspaceRestrictionTest extends AbstractRestrictionTestCase
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
        $GLOBALS['TCA'] = [
            'aTable' => [
                'ctrl' => [
                    'versioningWS' => 2,
                ],
            ]
        ];

        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->versioningPreview = false;

        $subject = new FrontendWorkspaceRestriction(0);
        $expression = $subject->buildExpression(['aTable' => ''], $this->expressionBuilder);
        $this->assertSame('("aTable"."t3ver_state" <= 0) AND ("aTable"."pid" <> -1)', (string)$expression);
    }

    /**
     * @test
     */
    public function buildExpressionAddsNonLiveWorkspaceWhereClause()
    {
        $GLOBALS['TCA'] = [
            'aTable' => [
                'ctrl' => [
                    'versioningWS' => 2,
                ],
            ]
        ];

        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->versioningPreview = true;
        $pageRepository->versioningWorkspaceId = 42;

        $subject = new FrontendWorkspaceRestriction(42, true);
        $expression = $subject->buildExpression(['aTable' => ''], $this->expressionBuilder);
        $this->assertSame('(("aTable"."t3ver_wsid" = 0) OR ("aTable"."t3ver_wsid" = 42)) AND ("aTable"."pid" <> -1)', (string)$expression);
    }

    /**
     * @test
     */
    public function buildExpressionAddsNonLiveWorkspaceExclusiveWhereClause()
    {
        $GLOBALS['TCA'] = [
            'aTable' => [
                'ctrl' => [
                    'versioningWS' => 2,
                ],
            ]
        ];

        $pageRepository = $this->createMock(PageRepository::class);
        $pageRepository->versioningPreview = true;
        $pageRepository->versioningWorkspaceId = 42;

        $subject = new FrontendWorkspaceRestriction(42, true, false);
        $expression = $subject->buildExpression(['aTable' => ''], $this->expressionBuilder);
        $this->assertSame('("aTable"."t3ver_wsid" = 0) OR ("aTable"."t3ver_wsid" = 42)', (string)$expression);
    }
}
