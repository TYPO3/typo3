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

namespace TYPO3\CMS\Core\Tests\Functional\Database\Query\Restriction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

final class EndTimeRestrictionTest extends AbstractRestrictionTestCase
{
    #[Test]
    public function buildRestrictionsThrowsExceptionInStartTimeIfGlobalsAccessTimeIsMissing(): void
    {
        $this->get(TcaSchemaFactory::class)->rebuild(array_replace_recursive($GLOBALS['TCA'], [
            'aTable' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'endtime' => 'myEndTimeField',
                    ],
                ],
                'columns' => [
                    'myEndTimeField' => [
                        'config' => [
                            'type' => 'datetime',
                        ],
                    ],
                ],
            ],
        ]));

        unset($GLOBALS['SIM_ACCESS_TIME']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1462821084);

        $subject = new EndTimeRestriction();
        $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
    }

    #[Test]
    public function buildRestrictionsAddsStartTimeWhereClause(): void
    {

        $this->get(TcaSchemaFactory::class)->rebuild(array_replace_recursive($GLOBALS['TCA'], [
            'aTable' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'endtime' => 'myEndTimeField',
                    ],
                ],
                'columns' => [
                    'myEndTimeField' => [
                        'config' => [
                            'type' => 'datetime',
                        ],
                    ],
                ],
            ],
        ]));

        $subject = new EndTimeRestriction(42);
        $expression = $subject->buildExpression(['aTable' => 'aTable'], $this->expressionBuilder);
        self::assertSame('(("aTable"."myEndTimeField" = 0) OR ("aTable"."myEndTimeField" > 42))', (string)$expression);
    }
}
