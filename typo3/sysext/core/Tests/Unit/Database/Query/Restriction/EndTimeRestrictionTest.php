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

use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;

class EndTimeRestrictionTest extends AbstractRestrictionTestCase
{
    /**
     * @test
     */
    public function buildRestrictionsThrowsExceptionInStartTimeIfGlobalsAccessTimeIsMissing()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'enablecolumns' => [
                'endtime' => 'myEndTimeField',
            ],
        ];
        unset($GLOBALS['SIM_ACCESS_TIME']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1462821084);

        $subject = new EndTimeRestriction();
        $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
    }

    /**
     * @test
     */
    public function buildRestrictionsAddsStartTimeWhereClause()
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'enablecolumns' => [
                'endtime' => 'myEndTimeField',
            ],
        ];

        $subject = new EndTimeRestriction(42);
        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('("aTable"."myEndTimeField" = 0) OR ("aTable"."myEndTimeField" > 42)', (string)$expression);
    }
}
